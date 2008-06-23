<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./opensearch.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    04-Feb-06, 21:53
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This script serves as a (faceless) routing page which takes an OpenSearch query and
	// converts the query into a native refbase query which is then passed to 'show.php'.
	// More info is given at <http://opensearch.refbase.net/>.


	// Returns an OpenSearch response. Supports the CQL query language, i.e. it allows to
	// query all global refbase fields (the given index name must match either one of the
	// 'set.index' names listed in the 'sru.php' explain response or match a refbase field
	// name directly). If no index name is given the user's preferred list of "main fields"
	// will be searched by default.

	// Examples for recognized OpenSearch queries:
	//
	// - ask the server to return an OpenSearch Description file:
	//     opensearch.php?operation=explain
	//
	// - find all records where any of the "main fields" contains 'immunology':
	//     opensearch.php?query=immunology
	//     opensearch.php?query=immunology&recordSchema=atom
	//
	// - find all records where the title field contains either 'ecology' or 'diversity' but
	//   return only three records starting with record number 4:
	//     opensearch.php?query=title%20any%20ecology%20diversity&startRecord=4&maximumRecords=3

	// By default, 'opensearch.php' will output OpenSearch Atom XML ('recordSchema=atom') if not
	// specified otherwise in the query. Additionally, 'rss', 'srw_dc', 'srw_mods' and 'html' are
	// currently supported as response formats. Also note that, opposed to 'sru.php', this script
	// won't allow you to query any user-specific fields (such as 'cite_key').

	// For more info on OpenSearch, see:
	//   <http://opensearch.org/>

	// TODO: - I18n
	//       - proper parsing of CQL query string (currently, 'opensearch.php' allows only for a limited set of CQL queries)
	//       - offer support for the boolean CQL operators 'and/or/not' and parentheses
	//       (both of the above goals would be accomplished by adopting Rob's CQL-PHP parser, see 'includes/cql.inc.php')
	//       - if no context set & index name are given in the query, we should search the user's preferred list of "main fields" by default! (cql.serverChoice)
	//       - currently, 'opensearch.php' does not omit the records list in the response if the OpenSearch query did contain 'maximumRecords=0' (as is the case for an SRU query)
	//       - finish 'opensearch2xhtml.xsl', and serve it when returning Atom XML
	//       - finish the form-based query builder (function 'showQueryPage()')
	//       - what should be done with diagnostics when the client has requested html?
	//       - fix '$citeOrder' issues (see notes in 'rss.php' and below)
	//       - include OpenSearch elements in RSS & HTML output (see examples at <http://www.opensearch.org/Specifications/OpenSearch/1.1#OpenSearch_response_elements>)
	//       - it would be nice if users could somehow pass authentication details with the OpenSearch Query
	//       - rewrite HTML using divs + CSS
	//       - see also inline comments labeled with "TODO"

	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/header.inc.php'; // include header
	include 'includes/footer.inc.php'; // include footer
	include 'includes/include.inc.php'; // include common functions
	include 'initialize/ini.inc.php'; // include common variables
	include 'includes/atomxml.inc.php'; // include functions that deal with Atom XML
	include 'includes/opensearch.inc.php'; // include functions that return an OpenSearch response
	include 'includes/srwxml.inc.php'; // include functions that deal with SRW XML
	include_once 'includes/webservice.inc.php'; // include functions that are commonly used with the refbase webservices

	// --------------------------------------------------------------------

	// Extract the ID of the client from which the query originated:
	// this identifier is used to identify queries that originated from the refbase command line clients ("cli-refbase-1.1", "cli-refbase_import-1.0") or from a bookmarklet (e.g., "jsb-refbase-1.0")
	// (note that 'client' parameter has to be extracted *before* the call to the 'start_session()' function, since it's value is required by this function)
	if (isset($_REQUEST['client']))
		$client = $_REQUEST['client'];
	else
		$client = "";

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// --------------------------------------------------------------------

	// Initialize preferred display language:
	// (note that 'locales.inc.php' has to be included *after* the call to the 'start_session()' function)
	include 'includes/locales.inc.php'; // include the locales

	// --------------------------------------------------------------------

	// Extract mandatory parameters passed to the script:

	if (isset($_REQUEST['query'])) // contains the keywords to be searched for ('{searchTerms}')
		$cqlQuery = $_REQUEST['query'];
	else
		$cqlQuery = "";

	// Extract optional parameters passed to the script:

	if (isset($_REQUEST['operation']) AND eregi("^(explain|advanced|CQL)$", $_REQUEST['operation']))
		$operation = $_REQUEST['operation'];
	else
		$operation = "";

	if (isset($_REQUEST['recordSchema']) AND !empty($_REQUEST['recordSchema'])) // contains the desired response format; currently supports 'atom', 'rss', 'srw_dc', 'srw_mods' and 'html'
		$recordSchema = $_REQUEST['recordSchema'];
	else
		$recordSchema = "atom";

	if (isset($_REQUEST['maximumRecords'])) // contains the desired number of search results (OpenSearch equivalent: '{count}')
		$showRows = $_REQUEST['maximumRecords'];
	else
		$showRows = $_SESSION['userRecordsPerPage']; // get the default number of records per page preferred by the current user

	if (isset($_REQUEST['startRecord'])) // contains the offset of the first search result, starting with one (OpenSearch equivalent: '{startIndex}')
		$rowOffset = ($_REQUEST['startRecord']) - 1; // first row number in a MySQL result set is 0 (not 1)
	else
		$rowOffset = ""; // if no value to the 'startRecord' parameter is given, we'll output records starting with the first record in the result set

	if (isset($_REQUEST['stylesheet'])) // contains the desired stylesheet to be returned for transformation of XML data
		$exportStylesheet = $_REQUEST['stylesheet']; // if the 'stylesheet' parameter was given in the query without a value, this will suppress the default stylesheet
	else
		$exportStylesheet = "DEFAULT"; // the special keyword "DEFAULT" causes a default stylesheet to be assigned below based on the requested operation and response format

	// The following parameters are defined by the OpenSearch Query Syntax specification but aren't supported yet: 

//	if (isset($_REQUEST['startPage'])) // indicates groups (= pages) of search results, starting with one ('{startPage}'); e.g., if 'maximumRecords=10', 'startPage=3' will cause records 21-30 to be returned
//		$pageOffset = ($_REQUEST['startPage']);
//	else
//		$pageOffset = "";

//	if (isset($_REQUEST['language'])) // indicates that the client desires results in the specified language ('{language}')
//		$language = ($_REQUEST['language']);
//	else
//		$language = "";

//	if (isset($_REQUEST['outputEncoding'])) // indicates that the client desires results in the specified character encoding ('{outputEncoding}')
//		$outputEncoding = ($_REQUEST['outputEncoding']);
//	else
//		$outputEncoding = "";

//	if (isset($_REQUEST['inputEncoding'])) // indicates that query parameters are encoded via the specified character encoding ('{inputEncoding}')
//		$inputEncoding = ($_REQUEST['inputEncoding']);
//	else
//		$inputEncoding = "";

	// Extract the view type requested by the user (either 'Mobile', 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";

	// Set required variables based on the requested response format:

	if (eregi("^srw([ _]?(mods|dc))?([ _]?xml)?$", $recordSchema)) // if SRW XML is requested as response format
	{
		if (eregi("^srw[ _]?dc", $recordSchema))
		{
			$exportFormat = "SRW_DC XML";
			if ($exportStylesheet == "DEFAULT")
				$exportStylesheet = "srwdc2html.xsl";
		}
		else
		{
			$exportFormat = "SRW_MODS XML";
			if ($exportStylesheet == "DEFAULT")
				$exportStylesheet = "srwmods2html.xsl";
		}

		$displayType = "Export";
		$exportContentType = "application/xml";
		$citeOrder = "";
	}
	elseif (eregi("^rss([ _]?xml)?$", $recordSchema)) // if RSS XML is requested as response format
	{
		$exportFormat = "RSS XML";
		$displayType = "Export";
		$exportContentType = "application/rss+xml";
		if ($exportStylesheet == "DEFAULT")
			$exportStylesheet = "";
		$citeOrder = ""; // TODO/NOTE: currently, 'rss.php' always sorts records like as if '$citeOrder="creation-date"' was given, i.e. it sorts records such that newly added/edited records get listed top of the list; this means that Atom links to alternate formats (such as HTML or SRW XML) might return different records!
	}
	elseif (eregi("^html$", $recordSchema)) // if HTML is requested as response format
	{
		$exportFormat = ""; // since search results won't be routed thru the 'generateExport()' function, '$exportFormat' will be without effect (which is why we leave it blank)

		if (eregi("^Mobile$", $viewType)) // for Mobile view, we enforce the compact Citation view
			$displayType = "Cite";
		else
			$displayType = ""; // if '$displayType' is empty, 'show.php' will use the default view that's given in session variable 'userDefaultView'

		$exportContentType = "text/html";
		if ($exportStylesheet == "DEFAULT")
			$exportStylesheet = "";
		$citeOrder = "";
	}
	else // by default, OpenSearch Atom XML ('atom') is assumed as response format
	{
		$exportFormat = "Atom XML";
		$displayType = "Export";
		$exportContentType = "application/atom+xml";
		if ($exportStylesheet == "DEFAULT")
			$exportStylesheet = ""; // TODO: finish 'opensearch2xhtml.xsl'
		$citeOrder = ""; // TODO/NOTE: '$citeOrder="creation-date"' would sort records such that newly added/edited records get listed top of the list, but then Atom links to alternate formats (such as HTML or SRW XML) would be mismatched!
	}

	// -------------------------------------------------------------------------------------------------------------------

	// Parse CQL query:
	$searchArray = parseCQL("1.1", $cqlQuery); // function 'parseCQL()' is defined in 'webservice.inc.php'

	// -------------------------------------------------------------------------------------------------------------------

	// Check that mandatory parameters have been passed:
	// - if 'opensearch.php' was called with 'operation=explain', we'll return an appropriate OpenSearch description document:
	if (eregi("^explain$", $operation))
	{
		// Use an appropriate default stylesheet:
		if ($exportStylesheet == "DEFAULT")
			$exportStylesheet = ""; // TODO: create a stylesheet ('opensearchDescription2html.xsl') that's appropriate for the OpenSearch description

		// Set the appropriate mimetype & set the character encoding to the one given
		// in '$contentTypeCharset' (which is defined in 'ini.inc.php'):
		setHeaderContentType("application/opensearchdescription+xml", $contentTypeCharset); // function 'setHeaderContentType()' is defined in 'include.inc.php'

		echo openSearchDescription($exportStylesheet); // function 'openSearchDescription()' is defined in 'opensearch.inc.php'
	}

	// - If 'opensearch.php' was called without any recognized parameters, we'll present a form where a user can build a query:
	elseif (!isset($_REQUEST['query']) AND !isset($_REQUEST['recordSchema']) AND !isset($_REQUEST['maximumRecords']) AND !isset($_REQUEST['startRecord']) AND !isset($_REQUEST['stylesheet']))
		showQueryPage($operation, $viewType, $showRows, $rowOffset);

	// - If 'opensearch.php' was called without any valid (or with incorrect) parameters, we'll return appropriate 'diagnostics':
	elseif (empty($cqlQuery))
		returnDiagnostic(7, "query"); // required 'query' parameter is missing

	// - Currently, no other schemas than OpenSearch Atom XML, SRW_DC XML, SRW_MODS XML, RSS XML and HTML are supported:
	elseif (!eregi("^((atom|rss)([ _]?xml)?|srw([ _]?(mods|dc))?([ _]?xml)?|html)$",$recordSchema))
		returnDiagnostic(66, $recordSchema); // unknown record schema

	// -------------------------------------------------------------------------------------------------------------------

	else // the script was called at least with the required 'query' parameter
	{
		// Write the current OpenSearch/CQL query into a session variable:
		// (this session variable is used by functions 'atomCollection()' and 'citeRecords()' (in 'cite_html.php') to re-establish the original OpenSearch/CQL query;
		//  function 'atomCollection()' uses the OpenSearch/CQL query to output 'opensearch.php' URLs instead of 'show.php' URLs)
		saveSessionVariable("cqlQuery", $cqlQuery); // function 'saveSessionVariable()' is defined in 'include.inc.php'

		// Build WHERE clause:
		$query = ""; // NOTE: although we don't supply a full SQL query here, the variable MUST be named '$query' to have function 'appendToWhereClause()' work correctly

		if (!empty($searchArray))
			appendToWhereClause($searchArray); // function 'appendToWhereClause()' is defined in 'include.inc.php'

		// --------------------------------------------------------------------

		// Build the correct query URL:
		// (we skip unnecessary parameters here since function 'generateURL()' and 'show.php' will use their default values for them)
		$queryParametersArray = array("where"            => $query,
		                              "submit"           => $displayType,
		                              "viewType"         => $viewType,
		                              "exportStylesheet" => $exportStylesheet
		                             );

		// call 'show.php' (or 'rss.php' in case of RSS XML) with the correct query URL in order to output record details in the requested format:
		$queryURL = generateURL("show.php", $exportFormat, $queryParametersArray, false, $showRows, $rowOffset, "", $citeOrder); // function 'generateURL()' is defined in 'include.inc.php'

		header("Location: $queryURL");
	}

	// -------------------------------------------------------------------------------------------------------------------

	// Return a diagnostic error message:
	function returnDiagnostic($diagCode, $diagDetails)
	{
		global $recordSchema;
		global $exportContentType;
		global $contentTypeCharset; // '$contentTypeCharset' is defined in 'ini.inc.php'
		global $exportStylesheet;

		// Set the appropriate mimetype & set the character encoding to the one given in '$contentTypeCharset':
		setHeaderContentType($exportContentType, $contentTypeCharset); // function 'setHeaderContentType()' is defined in 'include.inc.php'

		if (eregi("^srw([ _]?(mods|dc))?([ _]?xml)?$", $recordSchema))
			// Return SRW diagnostics (i.e. SRW error information) wrapped into SRW XML ('searchRetrieveResponse'):
			echo srwDiagnostics($diagCode, $diagDetails, $exportStylesheet); // function 'srwDiagnostics()' is defined in 'srwxml.inc.php'
//		elseif (eregi("html", $recordSchema))
			// TODO!
		else
			// Return OpenSearch diagnostics (i.e. OpenSearch error information) wrapped into OpenSearch Atom XML:
			echo openSearchDiagnostics($diagCode, $diagDetails, $exportStylesheet); // function 'openSearchDiagnostics()' is defined in 'opensearch.inc.php'
	}

	// -------------------------------------------------------------------------------------------------------------------

	// Present a form where a user can build a query:
	function showQueryPage($operation, $viewType, $showRows, $rowOffset)
	{
		global $officialDatabaseName; // defined in 'ini.inc.php'

		global $loc; // defined in 'locales/core.php'

		global $client;

		// If there's no stored message available:
		if (!isset($_SESSION['HeaderString']))
			$HeaderString =  $loc["SearchDB"].":"; // Provide the default message
		else
		{
			$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

			// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
			deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
		}

		// Show the login status:
		showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

		// DISPLAY header:
		// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
		displayHTMLhead(encodeHTML($officialDatabaseName) . " -- " . $loc["Search"], "index,follow", "Search the " . encodeHTML($officialDatabaseName), "", true, "", $viewType, array());
		if ((!eregi("^Mobile$", $viewType)) AND (!eregi("^inc", $client))) // Note: we omit the visible header in mobile view ('viewType=Mobile') and for include mechanisms!
			showPageHeader($HeaderString);

		// Define variables holding common drop-down elements, i.e. build properly formatted <option> tag elements:
		$dropDownConditionals1Array = array("contains"         => $loc["contains"],
		                                    "does not contain" => $loc["contains not"],
		                                    "is equal to"      => $loc["equal to"],
		                                    "is not equal to"  => $loc["equal to not"],
		                                    "starts with"      => $loc["starts with"],
		                                    "ends with"        => $loc["ends with"]);

		$dropDownItems1 = buildSelectMenuOptions($dropDownConditionals1Array, "", "\t\t\t", true); // function 'buildSelectMenuOptions()' is defined in 'include.inc.php'


		$dropDownConditionals2Array = array("is greater than" => $loc["is greater than"],
		                                    "is less than"    => $loc["is less than"],
		                                    "is within range" => $loc["is within range"],
		                                    "is within list"  => $loc["is within list"]);

		$dropDownItems2 = buildSelectMenuOptions($dropDownConditionals2Array, "", "\t\t\t", true);


		$dropDownFieldNames1Array = array("author"                => $loc["DropDownFieldName_Author"],
		                                  "address"               => $loc["DropDownFieldName_Address"],
		                                  "corporate_author"      => $loc["DropDownFieldName_CorporateAuthor"],
		                                  "thesis"                => $loc["DropDownFieldName_Thesis"],
		                                  "", // empty array elements function as spacers between groups of drop-down menu items
		                                  "title"                 => $loc["DropDownFieldName_Title"],
		                                  "orig_title"            => $loc["DropDownFieldName_OrigTitle"],
		                                  "",
		                                  "year"                  => $loc["DropDownFieldName_Year"],
		                                  "publication"           => $loc["DropDownFieldName_Publication"],
		                                  "abbrev_journal"        => $loc["DropDownFieldName_AbbrevJournal"],
		                                  "editor"                => $loc["DropDownFieldName_Editor"],
		                                  "",
		                                  "volume_numeric"        => $loc["DropDownFieldName_Volume"], // 'volume' should get replaced automatically by 'volume_numeric' (in function 'buildFieldNameLinks()') but it doesn't ?:-/
		                                  "issue"                 => $loc["DropDownFieldName_Issue"],
		                                  "pages"                 => $loc["DropDownFieldName_Pages"],
		                                  "",
		                                  "series_title"          => $loc["DropDownFieldName_SeriesTitle"],
		                                  "abbrev_series_title"   => $loc["DropDownFieldName_AbbrevSeriesTitle"],
		                                  "series_editor"         => $loc["DropDownFieldName_SeriesEditor"],
		                                  "series_volume_numeric" => $loc["DropDownFieldName_SeriesVolume"], // 'series_volume' should get replaced automatically by 'series_volume_numeric' (in function 'buildFieldNameLinks()') but it doesn't ?:-/
		                                  "series_issue"          => $loc["DropDownFieldName_SeriesIssue"],
		                                  "",
		                                  "publisher"             => $loc["DropDownFieldName_Publisher"],
		                                  "place"                 => $loc["DropDownFieldName_Place"],
		                                  "",
		                                  "edition"               => $loc["DropDownFieldName_Edition"],
		                                  "medium"                => $loc["DropDownFieldName_Medium"],
		                                  "issn"                  => $loc["DropDownFieldName_Issn"],
		                                  "isbn"                  => $loc["DropDownFieldName_Isbn"],
		                                  "",
		                                  "language"              => $loc["DropDownFieldName_Language"],
		                                  "summary_language"      => $loc["DropDownFieldName_SummaryLanguage"],
		                                  "",
		                                  "keywords"              => $loc["DropDownFieldName_Keywords"],
		                                  "abstract"              => $loc["DropDownFieldName_Abstract"],
		                                  "",
		                                  "area"                  => $loc["DropDownFieldName_Area"],
		                                  "expedition"            => $loc["DropDownFieldName_Expedition"],
		                                  "conference"            => $loc["DropDownFieldName_Conference"],
		                                  "",
		                                  "doi"                   => $loc["DropDownFieldName_Doi"],
		                                  "url"                   => $loc["DropDownFieldName_Url"]);

		if (isset($_SESSION['loginEmail'])) // we only include the 'file' field if the user is logged in
			$dropDownFieldNames1Array["file"] = $loc["DropDownFieldName_File"];

		$dropDownFieldNames1Array[] = "";
		$dropDownFieldNames1Array["notes"] = $loc["DropDownFieldName_Notes"];

		if (isset($_SESSION['loginEmail'])) // we only include the 'location' field if the user is logged in
			$dropDownFieldNames1Array["location"] = $loc["DropDownFieldName_Location"];

		$dropDownFieldNames2Array = array("call_number"  => $loc["DropDownFieldName_CallNumber"],
		                                  "",
		                                  "serial"       => $loc["DropDownFieldName_Serial"],
		                                  "type"         => $loc["DropDownFieldName_Type"],
		                                  "approved"     => $loc["DropDownFieldName_Approved"],
		                                  "",
		                                  "created_date" => $loc["DropDownFieldName_CreatedDate"],
		                                  "created_time" => $loc["DropDownFieldName_CreatedTime"]);

		if (isset($_SESSION['loginEmail'])) // we only include the 'created_by' field if the user is logged in
			$dropDownFieldNames2Array["created_by"] = $loc["DropDownFieldName_CreatedBy"];

		$dropDownFieldNames2Array[] = "";
		$dropDownFieldNames2Array["modified_date"] = $loc["DropDownFieldName_ModifiedDate"];
		$dropDownFieldNames2Array["modified_time"] = $loc["DropDownFieldName_ModifiedTime"];

		if (isset($_SESSION['loginEmail'])) // we only include the 'modified_by' field if the user is logged in
			$dropDownFieldNames2Array["modified_by"] = $loc["DropDownFieldName_ModifiedBy"];

		$dropDownItems3 = buildSelectMenuOptions(array_merge($dropDownFieldNames1Array,$dropDownFieldNames2Array), "", "\t\t\t", true);


		$dropDownConditionals3Array = array("html"     => "html",
		                                    "atom"     => "Atom XML",
		                                    "rss"      => "RSS XML",
		                                    "srw_dc"   => "SRW_DC XML",
		                                    "srw_mods" => "SRW_MODS XML");

		$dropDownItems4 = buildSelectMenuOptions($dropDownConditionals3Array, "", "\t\t\t", true);


		// Map CQL indexes to refbase field names:
		$indexNamesArray = mapCQLIndexes(); // function 'mapCQLIndexes()' is defined in 'webservice.inc.php'

		// --------------------------------------------------------------------

		// TODO: when the simple CQL Query Builder interface is done, a call to 'opensearch.php' (or 'opensearch.php?operation=simple')
		//       should activate that simple GUI-based interface (currently, it activates the advanced interface that you'd normally only
		//       get via 'opensearch.php?operation=cql' or 'opensearch.php?operation=advanced')
//		if (eregi("^(advanced|CQL)$", $operation))
			showQueryFormAdvanced($dropDownItems1, $dropDownItems2, $dropDownItems3, $dropDownItems4, $showRows, $rowOffset, $indexNamesArray, $viewType); // let's you enter a standard CQL query directly
//		else
//			showQueryFormSimple($dropDownItems1, $dropDownItems2, $dropDownItems3, $dropDownItems4, $showRows, $rowOffset, $indexNamesArray, $viewType); // let's you build a CQL query via dropdown menues

		// --------------------------------------------------------------------

		// DISPLAY THE HTML FOOTER:
		// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
		if ((!eregi("^Mobile$", $viewType)) AND (!eregi("^inc", $client))) // Note: we omit the visible footer in mobile view ('viewType=Mobile') and for include mechanisms!
			showPageFooter($HeaderString);

		displayHTMLfoot();
	}

	// -------------------------------------------------------------------------------------------------------------------

	// Present a form where a user can build a CQL query via dropdown menues:
	// 
	// TODO: - add a button to add/remove query lines
	//       - for each form option chosen by the user, a little JavaScript should adopt the underlying CQL query (which finally gets sent to 'opensearch.php' in the 'query' parameter)
	//       - a 'setup' parameter should allow to pass a full CQL query to 'opensearch.php'; this will be parsed and used to setup the default choice of fields & options
	//       - offer to save the current choice of fields & options as a CQL query to the 'user_options' table, and reload it upon login using the 'setup' parameter
	function showQueryFormSimple($dropDownItems1, $dropDownItems2, $dropDownItems3, $dropDownItems4, $showRows, $rowOffset, $indexNamesArray, $viewType)
	{
		global $loc; // defined in 'locales/core.php'

		// Start <form> and <table> holding the form elements:
?>

<form action="opensearch.php" method="GET" name="openSearch">
<input type="hidden" name="formType" value="openSearch">
<input type="hidden" name="submit" value="<?php echo $loc["ButtonTitle_Search"]; ?>">
<input type="hidden" name="viewType" value="<?php echo $viewType; ?>">
<table id="queryform" align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds a query form">
<tr>
	<td width="120" valign="top">
		<div class="sect"><b><?php echo $loc["Query"]; ?>:</b></div>
	</td><?php

// NOTE: the field selectors and search options don't work yet (see the TODO items at the top of this function)
/*
	<td width="140">
		<select name="fieldSelector"><?php echo $dropDownItems3; ?>

		</select>
	</td>
	<td width="122">
		<select name="fieldConditionalSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
*/
?>

	<td colspan="2"><input type="text" name="query" value="" size="60"></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>
		<input type="submit" name="submit" value="<?php echo $loc["ButtonTitle_Search"]; ?>" title="<?php echo $loc["SearchDB"]; ?>">
	</td>
</tr>
</table>
</form><?php

	}

	// -------------------------------------------------------------------------------------------------------------------

	// Present a form where a user can enter a standard CQL query directly:
	// 
	// TODO: use divs + CSS styling (instead of a table-based layout) for _all_ output, especially for 'viewType=Mobile'
	function showQueryFormAdvanced($dropDownItems1, $dropDownItems2, $dropDownItems3, $dropDownItems4, $showRows, $rowOffset, $indexNamesArray, $viewType)
	{
		global $officialDatabaseName; // defined in 'ini.inc.php'

		global $loc; // defined in 'locales/core.php'

		// Start <form> and <table> holding the form elements:
?>

<form action="opensearch.php" method="GET" name="openSearch">
<input type="hidden" name="formType" value="openSearch">
<input type="hidden" name="submit" value="<?php echo $loc["ButtonTitle_Search"]; ?>">
<input type="hidden" name="viewType" value="<?php echo $viewType; ?>">
<table id="queryform" align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds the query form">
<tr>
	<td width="120" valign="top">
		<div class="sect"><b><?php

		if (eregi("^Mobile$", $viewType))
			echo $officialDatabaseName;
		else
			echo $loc["CQLQuery"];

?>:</b></div>
	</td>
	<td>
		<input type="text" name="query" value="" size="60" title="<?php echo $loc["DescriptionEnterSearchString"]; ?>">
	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>
		<input type="submit" name="submit" value="<?php echo $loc["ButtonTitle_Search"]; ?>" title="<?php echo $loc["SearchDB"]; ?>">
	</td>
</tr>
</table>
<table class="showhide" align="center" border="0" cellpadding="0" cellspacing="10" width="95%">
<tr>
	<td class="small" width="120" valign="top">
		<a href="javascript:toggleVisibility('searchopt','optToggleimg','optToggletxt','<?php echo rawurlencode($loc["SearchOptions"]); ?>')"<?php echo addAccessKey("attribute", "search_opt"); ?> title="<?php echo $loc["LinkTitle_ToggleVisibility"] . addAccessKey("title", "search_opt"); ?>">
			<img id="optToggleimg" class="toggleimg" src="img/closed.gif" alt="<?php echo $loc["LinkTitle_ToggleVisibility"]; ?>" width="9" height="9" hspace="0" border="0">
			<span id="optToggletxt" class="toggletxt"><?php echo $loc["SearchOptions"]; ?></span>
		</a>
	</td>
</tr>
</table>
<table id="searchopt" align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds display options">
<tr>
	<td width="120" valign="top">
		<div class="sect"><b><?php echo $loc["SearchOptions"]; ?>:</b></div>
	</td>
	<td width="215" valign="top">
		Start at record:&nbsp;&nbsp;
		<input type="text" name="startRecord" value="<?php echo ($rowOffset + 1); ?>" size="4">
	</td>
	<td valign="top">
		<?php echo $loc["ShowRecordsPerPage_Prefix"]; ?>&nbsp;&nbsp;&nbsp;<input type="text" name="maximumRecords" value="<?php echo $showRows; ?>" size="4" title="<?php echo $loc["DescriptionShowRecordsPerPage"]; ?>">&nbsp;&nbsp;&nbsp;<?php echo $loc["ShowRecordsPerPage_Suffix"]; ?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td valign="top">
		Output format:&nbsp;&nbsp;
		<select name="recordSchema"><?php echo $dropDownItems4; ?>

		</select>
	</td>
</tr>
</table>
<table class="showhide" align="center" border="0" cellpadding="0" cellspacing="10" width="95%">
<tr>
	<td class="small" width="120" valign="top">
		<a href="javascript:toggleVisibility('helptxt','helpToggleimg','helpToggletxt','<?php echo rawurlencode($loc["HelpAndExamples"]); ?>')"<?php echo addAccessKey("attribute", "search_help"); ?> title="<?php echo $loc["LinkTitle_ToggleVisibility"] . addAccessKey("title", "search_help"); ?>">
			<img id="helpToggleimg" class="toggleimg" src="img/closed.gif" alt="<?php echo $loc["LinkTitle_ToggleVisibility"]; ?>" width="9" height="9" hspace="0" border="0">
			<span id="helpToggletxt" class="toggletxt"><?php echo $loc["HelpAndExamples"]; ?></span>
		</a>
	</td>
</tr>
</table>
<table id="helptxt" align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds some help text and example queries">
<tr>
	<td width="120" valign="top">
		<div class="sect"><b><?php echo $loc["Help"]; ?>:</b></div>
	</td>
	<td valign="top">This form lets you search the literature database using a standard CQL query (<a href="http://www.loc.gov/standards/sru/specs/cql.html" target="top">Common Query Language</a>). You can simply enter a query term, in which case the <em><?php echo $indexNamesArray["cql.serverChoice"]; ?></em> field will be searched by default. You can also search any other field, some query examples are given below. An introduction to CQL is given <a href="http://zing.z3950.org/cql/intro.html" target="top">here</a>.</td>
</tr>
<tr>
	<td width="120" valign="top">
		<div class="sect"><b><?php echo $loc["Examples"]; ?>:</b></div>
	</td>
	<td class="examples" valign="top">
		<div class="even">
			Find all records where the <em><?php echo $indexNamesArray["cql.serverChoice"]; ?></em> field contains the word "ecology":
			<pre>ecology</pre>
		</div>
		<div class="odd">
			You can use wildcards anywhere in a search term to match one (<code>?</code>) or more (<code>*</code>) unspecified characters. E.g. this finds all records where the <em><?php echo $indexNamesArray["cql.serverChoice"]; ?></em> field contains a word that starts with "ecolog":
			<pre>ecolog*</pre>
		</div>
		<div class="even">
			Find all records where the <em>title</em> field contains <code>any</code> of the given words ("ecology" OR "diversity"):
			<pre>title any ecology diversity</pre>
		</div>
		<div class="odd">
			Find all records where the <em>author</em> field contains <code>all</code> of the given words ("dieckmann" AND "thomas" AND "sullivan"):
			<pre>author all dieckmann thomas sullivan</pre>
		</div>
		<div class="even">
			You can also search for <code>exact</code> field matches. E.g. this finds all records where the <em>publication</em> field equals EXACTLY "Marine Ecology Progress Series":
			<pre>publication exact Marine Ecology Progress Series</pre>
		</div>
		<div class="odd">
			For numeric fields, the obvious ordered relations (<code>&lt;</code>, <code>&lt;=</code>, <code>=</code>, <code>&gt;=</code>, <code>&gt;</code>) may be used. E.g. this finds all records where the <em>year</em> field is greater than or equals "2005":
			<pre>year &gt;= 2005</pre>
		</div>
		<div class="even">
			For numeric fields, you can match a range using the <code>within</code> relation followed by the lower and upper end of the range. E.g. this finds all records where the <em>volume</em> field contains a number between "10" and "20":
			<pre>volume within 10 20</pre>
		</div>
	</td>
</tr>
</table>
</form><?php

	}

	// -------------------------------------------------------------------------------------------------------------------
?>
