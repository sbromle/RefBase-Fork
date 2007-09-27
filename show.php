<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./show.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    02-Nov-03, 14:10
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This script serves as a routing page which takes e.g. any record serial number, date, year, author, contribution ID or thesis that was passed
	// as parameter to the script, builds an appropriate SQL query and passes that to 'search.php' which will then display the corresponding
	// record(s). This allows to provide short URLs (like: '.../show.php?record=12345') for email announcements or to generate publication lists.
	// TODO: I18n


	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/header.inc.php'; // include header
	include 'includes/footer.inc.php'; // include footer
	include 'includes/include.inc.php'; // include common functions
	include 'initialize/ini.inc.php'; // include common variables

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

	// Extract any generic parameters passed to the script:
	// (they control how found records are presented on screen)

	// Extract the type of display requested by the user. Normally, this will be one of the following:
	//  - '' => if the 'submit' parameter is empty, this will produce the default columnar output style ('displayColumns()' function)
	//  - 'Display' => display details for all found records ('displayDetails()' function)
	//  - 'Cite' => build a proper citation for all found records ('generateCitations()' function)
	//  - 'Export' => generate and return found records in the specified export format ('generateExport()' function)
	if (isset($_REQUEST['submit']))
		$displayType = $_REQUEST['submit'];
	else
		$displayType = "";

	// Note that for 'show.php' we don't accept any other display types than '', 'Display', 'Cite', 'Export' and 'Browse',
	// if any other types were specified, we'll use the default columnar output style instead:
	if (!empty($displayType) AND !eregi("^(Display|Cite|Export|Browse)$", $displayType))
		$displayType = "";

	// Extract the view type requested by the user (either 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";

	if (isset($_REQUEST['showQuery']))
		$showQuery = $_REQUEST['showQuery'];
	else
		$showQuery = "";

	if (isset($_REQUEST['showLinks']))
		$showLinks = $_REQUEST['showLinks'];
	else
		$showLinks = "1"; // for 'show.php' we'll always show the links column by default if the 'showLinks' parameter isn't set explicitly to "0"

	if (isset($_REQUEST['showRows']) AND ereg("^[1-9]+[0-9]*$", $_REQUEST['showRows'])) // contains the desired number of search results (OpenSearch equivalent: '{count}')
		$showRows = $_REQUEST['showRows'];
	else
		$showRows = $_SESSION['userRecordsPerPage']; // get the default number of records per page preferred by the current user

	if (isset($_REQUEST['startRecord'])) // contains the offset of the first search result, starting with one (OpenSearch equivalent: '{startIndex}')
		$rowOffset = ($_REQUEST['startRecord']) - 1; // first row number in a MySQL result set is 0 (not 1)
	else
		$rowOffset = ""; // if no value to the 'startRecord' parameter is given, we'll output records starting with the first record in the result set

	if (isset($_REQUEST['wrapResults']) AND ($_REQUEST['wrapResults'] == "0"))
		$wrapResults = $_REQUEST['wrapResults']; // for citation output, 'wrapResults=0' causes refbase to output only a partial document structure containing solely the search results (e.g. for HTML, everything is omitted except for the <table> block containing the search results)
	else
		$wrapResults = "1"; // we'll output a full document (HTML, RTF, LaTeX, etc) structure unless the 'wrapResults' parameter is set explicitly to "0"

	if (isset($_REQUEST['citeStyle']) AND !empty($_REQUEST['citeStyle'])) // NOTE: while this parameter is normally called 'citeStyleSelector' (e.g. in 'search.php') we call it just 'citeStyle' here in an attempt to ease legibility of 'show.php' URLs
		$citeStyle = $_REQUEST['citeStyle']; // get cite style
	else
		$citeStyle = $defaultCiteStyle; // if no cite style was given, we'll use the default cite style which is defined by the '$defaultCiteStyle' variable in 'ini.inc.php'

	if (isset($_REQUEST['citeOrder']))
		$citeOrder = $_REQUEST['citeOrder']; // get information how citation data should be sorted (if this parameter is set to 'year', records will be listed in blocks sorted by year)
	else
		$citeOrder = "";

	// for citation output, get information how citation data shall be returned:
	// - 'html' => return citations as HTML with mime type 'text/html'
	// - 'RTF' => return citations as RTF data with mime type 'application/rtf'
	// - 'PDF' => return citations as PDF data with mime type 'application/pdf'
	// - 'LaTeX' => return citations as LaTeX data with mime type 'application/x-latex'
	// - 'Markdown' => return citations as Markdown TEXT data with mime type 'text/plain'
	// - 'ASCII' => return citations as TEXT data with mime type 'text/plain'
	// - 'LaTeX .bbl' => return citations as LaTeX .bbl file (for use with LaTeX/BibTeX) with mime type 'application/x-latex'
	if (isset($_REQUEST['citeType']) AND eregi("^(html|RTF|PDF|LaTeX|Markdown|ASCII|LaTeX \.bbl)$", $_REQUEST['citeType']))
		$citeType = $_REQUEST['citeType'];
	else
		$citeType = "html";

	if (isset($_REQUEST['exportFormat']) AND !empty($_REQUEST['exportFormat'])) // NOTE: while this parameter is normally called 'exportFormatSelector' (e.g. in 'search.php') we call it just 'exportFormat' here in an attempt to ease legibility of 'show.php' URLs
		$exportFormat = $_REQUEST['exportFormat']; // get export format style
	else
		$exportFormat = $defaultExportFormat; // if no export format was given, we'll use the default export format which is defined by the '$defaultExportFormat' variable in 'ini.inc.php'

	// for export, get information how exported data shall be returned; possible values:
	// - 'text' => return data with mime type 'text/plain'
	// - 'html' => return data with mime type 'text/html
	// - 'xml' => return data with mime type 'application/xml
	// - 'rss' => return data with mime type 'application/rss+xml'
	// - 'file' => return data as downloadable file
	// - 'email' => send data as email (to the user's login email address)
	if (isset($_REQUEST['exportType']) AND eregi("^(text|html|xml|rss|file|email)$", $_REQUEST['exportType']))
		$exportType = $_REQUEST['exportType']; // get export type
	else
		$exportType = "html";

	if (isset($_REQUEST['headerMsg']))
		$headerMsg = stripTags($_REQUEST['headerMsg']); // we'll accept custom header messages but strip HTML tags from the custom header message to prevent cross-site scripting (XSS) attacks (function 'stripTags()' is defined in 'include.inc.php')
						// Note: custom header messages are provided so that it's possible to include an information string within a link. This info string could
						//       e.g. describe who's publications are being displayed (e.g.: "Publications of Matthias Steffens:"). I.e., a link pointing to a
						//       persons own publications can include the appropriate owner information (it will show up as header message)
	else
		$headerMsg = "";

	// --------------------------------------------------------------------

	// Extract any parameters that are specific to 'show.php':
	// (these parameters control which records will be returned by 'search.php')

	// Note: you can combine different parameters to achieve an "AND" query, e.g.:
	//
	//       show.php?contribution_id=AWI&author=steffens&year=2005
	//
	//       which will find all records where:  'contribution_id' contains 'AWI'  -AND-  'author' contains 'steffens'  -AND-  'year' contains '2005'

	if (isset($_REQUEST['serial']))
		$serial = $_REQUEST['serial']; // get the record serial number that was entered by a user in the 'show.php' web form

	elseif (isset($_REQUEST['record']))
		$serial = $_REQUEST['record']; // get the record serial number that was passed by an URL of the form '.../show.php?record=12345' (as it occurs in RSS feeds and email announcements)
	else
		$serial = "";

	if (isset($_REQUEST['recordIDSelector']))
		$recordIDSelector = $_REQUEST['recordIDSelector']; // get the value returned from the 'recordIDSelector' drop down menu (returned value is either 'serial', 'call_number' or 'cite_key')
	else
		$recordIDSelector = "";

	if (isset($_REQUEST['recordConditionalSelector']))
		$recordConditionalSelector = $_REQUEST['recordConditionalSelector']; // get the value returned from the 'recordConditionalSelector' drop down menu (returned value is either 'is equal to', 'contains' or 'is within list')
	else
	{
		if (isset($_REQUEST['record'])) // normally, '$recordConditionalSelector' get's only specified in the 'show.php' web form but not in RSS/Email announcement URLs, but...
			$recordConditionalSelector = "is equal to"; // ...if 'show.php' was called from a RSS/Email announcement URL (like '.../show.php?record=12345') we'll have to make sure that the serial field will be matched fully and not only partly
		else
			$recordConditionalSelector = "";
	}

	// If the 'records' parameter is present and contains any number(s) or 'all' as value, it will override any given 'serial' or 'record' parameters.
	// This param was introduced to provide an easy 'Show All' link ('.../show.php?records=all') which will display all records in the database.
	// It does also allow to easily link to multiple records (such as in '.../show.php?records=1234,5678,90123').
	if (isset($_REQUEST['records']))
	{
		// if the 'records' parameter is given, it's value must be either 'all' or any number(s) delimited by non-digit characters:
		if (eregi("^all$", $_REQUEST['records']))
		{
			// '.../show.php?records=all' is effectively a more nice looking variant of 'show.php?serial=%2E%2B&recordConditionalSelector=contains':
			$serial = ".+"; // show all records
			$recordConditionalSelector = "contains";
		}
		elseif (ereg("[0-9]", $_REQUEST['records']))
		{
			// '.../show.php?records=1234,5678,90123' is effectively a more nice looking variant of 'show.php?serial=1,12,123,1234&recordConditionalSelector=is%20within%20list':
			$serial = $_REQUEST['records']; // show all records whose serial numbers match the given numbers
			$recordConditionalSelector = "is within list";
		}
	}

	if (isset($_REQUEST['date']))
		$date = $_REQUEST['date']; // get date
	else
		$date = "";

	if (isset($_REQUEST['time']))
		$time = $_REQUEST['time']; // get time
	else
		$time = "";

	if (isset($_REQUEST['when'])) // if given only 'edited' is recognized as value
		$when = $_REQUEST['when']; // get info about what kind of date shall be searched for ("when=edited" -> search field 'modified_date'; otherwise -> search field 'created_date')
	else
		$when = "";

	if (isset($_REQUEST['range'])) // given value must be either 'after', 'before', 'equal_or_after' or 'equal_or_before'
		$range = $_REQUEST['range']; // check the date range ("range=after" -> return all records whose created/modified date/time is after '$date'/'$time'; "range=before" -> return all records whose created/modified date/time is before '$date'/'$time')
	else
		$range = "";

	if (isset($_REQUEST['year']))
		$year = $_REQUEST['year']; // get year
	else
		$year = "";

	if (isset($_REQUEST['author']))
		$author = $_REQUEST['author']; // get author
	else
		$author = "";

	if (isset($_REQUEST['without']) AND eregi("^dups$", $_REQUEST['without'])) // if given only 'dups' is currently recognized as value
		$without = $_REQUEST['without']; // check whether duplicate records should be excluded ("without=dups" -> exclude duplicate records)
	else
		$without = "";

	if (isset($_REQUEST['title']))
		$title = $_REQUEST['title']; // get title
	else
		$title = "";

	if (isset($_REQUEST['publication']))
		$publication = $_REQUEST['publication']; // get publication
	else
		$publication = "";

	if (isset($_REQUEST['abbrev_journal']))
		$abbrevJournal = $_REQUEST['abbrev_journal']; // get abbreviated journal
	else
		$abbrevJournal = "";

	if (isset($_REQUEST['keywords']))
		$keywords = $_REQUEST['keywords']; // get keywords
	else
		$keywords = "";

	if (isset($_REQUEST['abstract']))
		$abstract = $_REQUEST['abstract']; // get abstract
	else
		$abstract = "";

	if (isset($_REQUEST['area']))
		$area = $_REQUEST['area']; // get area
	else
		$area = "";

	if (isset($_REQUEST['notes']))
		$notes = $_REQUEST['notes']; // get notes
	else
		$notes = "";

	if (isset($_REQUEST['location']))
		$location = $_REQUEST['location']; // get location
	else
		$location = "";

	if (isset($_REQUEST['type']))
		$type = $_REQUEST['type']; // get type
	else
		$type = "";

	if (isset($_REQUEST['contribution_id']))
		$contributionID = $_REQUEST['contribution_id']; // get contribution ID
	else
		$contributionID = "";

	if (isset($_REQUEST['thesis'])) // given value must be either 'yes' (= find only theses) or 'no' (= exclude any theses) or a search string (like 'master', 'bachelor' or 'doctor')
		$thesis = $_REQUEST['thesis']; // get thesis
	else
		$thesis = "";

	if (isset($_REQUEST['selected'])) // given value must be either 'yes' or 'no'
		$selected = $_REQUEST['selected']; // if e.g. "selected=yes", we'll restrict the search results to those records that have the 'selected' bit set to 'yes' for a particular user.
	else								// IMPORTANT: Since the 'selected' field is specific to every user (table 'user_data'), the 'userID' parameter must be specified as well!
		$selected = "";					//            (the 'selected' parameter can be queried with a user ID that's different from the current user's own user ID, see note at "Build FROM clause")

	if (isset($_REQUEST['only']))
	{
		if ($_REQUEST['only'] == "selected"); // the 'only=selected' parameter/value combination was used in refbase-0.8.0 and earlier but is now replaced by 'selected=yes' (we still read it for reasons of backwards compatibility)
			$selected = "yes";
	}

	if (isset($_REQUEST['ismarked'])) // given value must be either 'yes' or 'no' (note that this parameter is named 'ismarked' instead of 'marked' to avoid any name collisions with the 'marked' parameter that's used in conjunction with checkboxes!)
		$marked = $_REQUEST['ismarked']; // if e.g. "ismarked=yes", we'll restrict the search results to those records that have the 'marked' bit set to 'yes' for a particular user.
	else								// IMPORTANT: Since the 'marked' field is specific to every user (table 'user_data'), the 'userID' parameter must be specified as well!
		$marked = "";					//            (currently, the 'ismarked' parameter can NOT be queried with a user ID that's different from the current user's own user ID!)

	if (isset($_REQUEST['cite_key']))
		$citeKey = $_REQUEST['cite_key'];
	else								// IMPORTANT: Since the 'cite_key' field is specific to every user (table 'user_data'), the 'userID' parameter must be specified as well!
		$citeKey = "";					//            (currently, the 'cite_key' parameter can NOT be queried with a user ID that's different from the current user's own user ID!)

	if (isset($_REQUEST['call_number']))
		$callNumber = $_REQUEST['call_number'];
	else								// IMPORTANT: We treat any 'call_number' query as specific to every user, i.e. a user can only query his own call numbers.
		$callNumber = "";

	if (isset($_REQUEST['userID']) AND ereg("^[0-9]+$", $_REQUEST['userID']))
		$userID = $_REQUEST['userID']; // when searching user specific fields (like the 'selected' or 'marked' field), this parameter specifies the user's user ID.
									// I.e., the 'userID' parameter does only make sense when specified together with either the 'selected' or the 'marked' parameter. As an example,
	else							// "show.php?author=...&selected=yes&userID=2" will show every record where the user who's identified by user ID "2" has set the selected bit to "yes".
		$userID = "";

	if (isset($_REQUEST['by']))
		$browseByField = $_REQUEST['by']; // get 'by' parameter
	else
		$browseByField = "";

	if (isset($_REQUEST['where']))
		$where = stripSlashesIfMagicQuotes($_REQUEST['where']); // get custom WHERE clause (and remove slashes from WHERE clause if 'magic_quotes_gpc = On'; function 'stripSlashesIfMagicQuotes()' is defined in 'include.inc.php')
	else
		$where = "";

	if (isset($_REQUEST['queryType']))
		$queryType = $_REQUEST['queryType']; // get 'queryType' parameter
	else
		$queryType = "";

	if ($queryType == "or")
		$queryType = "OR"; // we allow for lowercase 'or' but convert it to uppercase (in an attempt to increase consistency & legibility of the SQL query) 

	if ($queryType != "OR") // if given value is 'OR' multiple parameters will be connected by 'OR', otherwise an 'AND' query will be performed
		$queryType = "AND";


	// normally, 'show.php' requires that parameters must be specified explicitly to gain any view that's different from the default view
	// (which is columnar output as web view, display 5 records per page, show links but don't show query)
	// There's one exception to this general rule which is if a user uses 'show.php' to query a *single* record by use of its record identifier (e.g. via '.../show.php?record=12345' or via the web form when using the "is equal to" option).
	// In this case we'll directly jump to details view:
	if (!empty($serial)) // if the 'record' parameter is present
		if (empty($displayType) AND (($recordConditionalSelector == "is equal to") OR (empty($recordConditionalSelector) AND is_numeric($serial)))) // if the 'displayType' parameter wasn't explicitly specified -AND- we're EITHER supposed to match record identifiers exactly OR '$recordConditionalSelector' wasn't specified and '$serial' is a number (which is the case for email announcement URLs: '.../show.php?record=12345')
			$displayType = "Display"; // display record details (instead of the default columnar view)

	// shift some variable contents based on the value of '$recordIDSelector':
	if ($recordIDSelector == "call_number")
	{
		$callNumber = $serial; // treat content in '$serial' as call number
		$serial = "";
	}

	elseif ($recordIDSelector == "cite_key")
	{
		$citeKey = $serial; // treat content in '$serial' as cite key
		$serial = "";
	}


	// -------------------------------------------------------------------------------------------------------------------


	// Check the correct parameters have been passed:
	if (empty($serial) AND empty($date) AND empty($time) AND empty($year) AND empty($author) AND empty($title) AND empty($publication) AND empty($abbrevJournal) AND empty($keywords) AND empty($abstract) AND empty($area) AND empty($notes) AND empty($location) AND empty($type) AND empty($contributionID) AND empty($thesis) AND empty($without) AND (empty($selected) OR (!empty($selected) AND empty($userID))) AND (empty($marked) OR (!empty($marked) AND empty($userID))) AND (empty($citeKey) OR (!empty($citeKey) AND empty($userID))) AND empty($callNumber) AND empty($where) AND (empty($browseByField) OR (!empty($browseByField) AND $displayType != "Browse")))
	{
		// if 'show.php' was called without any valid parameters, we'll present a form where a user can input a record serial number.
		// Currently, this form will not present form elements for other supported options (like searching by date, year or author),
		// since this would just double search functionality from other search forms.

		// If there's no stored message available:
		if (!isset($_SESSION['HeaderString']))
			$HeaderString = "Display details for a particular record by entering its record identifier:"; // Provide the default message
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
		displayHTMLhead(encodeHTML($officialDatabaseName) . " -- " . $loc["ShowRecord"], "index,follow", "Search the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
		showPageHeader($HeaderString, "");

		// Define variables holding drop-down elements, i.e. build properly formatted <option> tag elements:
		$dropDownConditionalsArray = array("is equal to"    => $loc["equal to"],
		                                   "contains"       => $loc["contains"],
		                                   "is within list" => $loc["is within list"]);

		$dropDownItems1 = buildSelectMenuOptions($dropDownConditionalsArray, "", "\t\t\t", true); // function 'buildSelectMenuOptions()' is defined in 'include.inc.php'

		$dropDownFieldNameArray = array("serial" => $loc["DropDownFieldName_Serial"]);

		if (isset($_SESSION['loginEmail'])) // if a user is logged in
		{
			// add drop down items for user-specific record identifiers:
			$dropDownFieldNameArray["call_number"] = $loc["DropDownFieldName_MyCallNumber"];
			$dropDownFieldNameArray["cite_key"] = $loc["DropDownFieldName_MyCiteKey"];

			// adjust the width of the table cell holding the drop down:
			$recordIDCellWidth = "140";
		}
		else
			$recordIDCellWidth = "85";

		$dropDownItems2 = buildSelectMenuOptions($dropDownFieldNameArray, "", "\t\t\t", true); // function 'buildSelectMenuOptions()' is defined in 'include.inc.php'

		// Start <form> and <table> holding the form elements:
?>

<form action="show.php" method="POST">
<input type="hidden" name="formType" value="show">
<input type="hidden" name="submit" value="<?php echo $loc["ButtonTitle_ShowRecord"]; ?>">
<input type="hidden" name="showLinks" value="1">
<input type="hidden" name="userID" value="<?php echo $loginUserID; // '$loginUserID' is made available globally by the 'start_session()' function ?>">
<table align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds a form that offers to show a record by its serial number, call number or cite key">
<tr>
	<td width="58" valign="top"><b><?php echo $loc["ShowRecord"]; ?>:</b></td>
	<td width="10">&nbsp;</td>
	<td width="<?php echo $recordIDCellWidth; ?>">
		<select name="recordIDSelector"><?php echo $dropDownItems2; ?>

		</select>
	</td>
	<td width="122">
		<select name="recordConditionalSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="serial" value="" size="14"></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td><input type="submit" name="submit" value="<?php echo $loc["ButtonTitle_ShowRecord"]; ?>" title="display record details for the entered record identifier"></td>
</tr>
<tr>
	<td align="center" colspan="5">&nbsp;</td>
</tr>
<tr>
	<td valign="top"><b><?php echo $loc["Help"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td colspan="3" valign="top">This form enables you to directly jump to a particular record and display its record details. Just enter the database serial number for that record and press the 'Show Record' button. (In order to view the database serial number of a particular record, click the <img src="img/details.gif" alt="show details" title="show details" width="9" height="17" hspace="0" border="0" align="top"> icon that's available in any list view next to that record and note the number listed within the 'Serial' field.)</td>
</tr>
</table>
</form><?php

		// --------------------------------------------------------------------

		// DISPLAY THE HTML FOOTER:
		// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
		showPageFooter($HeaderString, "");

		displayHTMLfoot();

		// --------------------------------------------------------------------

	}


	// -------------------------------------------------------------------------------------------------------------------


	else // the script was called with at least one of the following parameters: 'record', 'records', 'date', 'time', 'year', 'author', 'title', 'publication', 'abbrev_journal', 'keywords', 'abstract', 'area', 'notes', 'location', 'type', 'contribution_id', 'thesis', 'without', 'selected', 'marked', 'cite_key', 'call_number', 'where', 'by'
	{
		// CONSTRUCT SQL QUERY:

		// Note: the 'verifySQLQuery()' function that gets called by 'search.php' to process query data with "$formType = sqlSearch" will add the user specific fields to the 'SELECT' clause
		// and the 'LEFT JOIN...' part to the 'FROM' clause of the SQL query if a user is logged in. It will also add 'orig_record', 'serial', 'file', 'url', 'doi', 'isbn' & 'type' columns
		// as required. Therefore it's sufficient to provide just the plain SQL query here:

		// Build SELECT clause:
		if (ereg("^(Display|Export)$", $displayType)) // select all fields required to display record details or to export a record:
		{
			$query = "SELECT author, title, type, year, publication, abbrev_journal, volume, issue, pages, corporate_author, thesis, address, keywords, abstract, publisher, place, editor, language, summary_language, orig_title, series_editor, series_title, abbrev_series_title, series_volume, series_issue, edition, issn, isbn, medium, area, expedition, conference, notes, approved";
			if (isset($_SESSION['loginEmail']))
				$query .= ", location"; // we only add the 'location' field if the user is logged in
			$query .= ", call_number, serial";
		//           (the above string MUST end with ", call_number, serial" in order to have the described query completion feature work correctly!

			if ($displayType == "Export") // for export, we inject some additional fields into the SELECT clause (again, we must add these additional fields *before* ", call_number, serial" in order to have the described query completion feature work correctly!)
				$query = eregi_replace(', call_number, serial', ', online_publication, online_citation, modified_date, modified_time, call_number, serial', $query);
		}

		elseif ($displayType == "Cite") // select all fields required to build proper record citations:
		{
			$query = "SELECT type, author, year, title, publication, abbrev_journal, volume, issue, pages, thesis, editor, publisher, place, abbrev_series_title, series_title, series_editor, series_volume, series_issue, language, author_count, online_publication, online_citation, doi";

			// Note that the if clause below is very weak since it will break if "Text Citation" gets renamed or translated (when localized).
			// Problem: The above mentioned 'verifySQLQuery()' function requires that 'selected' is the only user-specific field present in the SELECT or WHERE clause of the SQL query.
			//          If this is not the case (as with 'cite_key' being added below) the passed user ID will be replaced with the ID of the currently logged in user.
			//          As a result, you won't be able to see your collegues selected publications by using an URL like '../show.php?author=steffens&userID=2&selected=yes&submit=Cite&citeOrder=year'
			//          On the other hand, if the 'cite_key' field isn't included within the SELECT clause, user-specific cite keys can't be written out instead of serials when citing as "Text Citation".
			//          Since the latter is of minor importance we'll require $citeStyle == "Text Citation" here:
			if (!empty($userID)) // if the 'userID' parameter was specified...
					$query .= ", cite_key"; // add user-specific fields which are required in Citation view

			$query .= ", serial"; // add 'serial' column
		}

		elseif ($displayType == "Browse")
		{
			$query = "SELECT " . escapeSQL($browseByField) . ", COUNT(*) AS records";
		}

		else // produce the default columnar output style:
		{
			$query = "SELECT author, title, year, publication, volume, pages";

			if (!empty($recordIDSelector)) // if a record identifier (either 'serial', 'call_number' or 'cite_key') was entered via the 'show.php' web form
				$query .= ", " . escapeSQL($recordIDSelector); // display the appropriate column
		}


		// Build FROM clause:
		// We'll explicitly add the 'LEFT JOIN...' part to the 'FROM' clause of the SQL query if '$userID' isn't empty. This is done since the 'verifySQLQuery()' function
		// (mentioned above) excludes the 'selected' field from its magic. By that we allow the 'selected' field to be queried by any user (using 'show.php')
		// (e.g., by URLs of the form: 'show.php?author=...&userID=...&selected=yes').
		if (!empty($userID)) // the 'userID' parameter was specified -> we include user specific fields
			$query .= " FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = " . quote_smart($userID); // add FROM clause (including the 'LEFT JOIN...' part); '$tableRefs' and '$tableUserData' are defined in 'db.inc.php'
		else
			$query .= " FROM $tableRefs"; // add FROM clause


		// Build WHERE clause:
		$query .= " WHERE";

		$multipleParameters = false;

		if (!empty($serial)) // if the 'record' parameter is present:
		{
			// first, check if the user is allowed to display any record details:
			if ($displayType == "Display" AND isset($_SESSION['user_permissions']) AND !ereg("allow_details_view", $_SESSION['user_permissions'])) // no, the 'user_permissions' session variable does NOT contain 'allow_details_view'...
			{
				// return an appropriate error message:
				$HeaderString = returnMsg($loc["NoPermission"] . $loc["NoPermission_ForDisplayDetails"] . "!", "warning", "strong", "HeaderString"); // function 'returnMsg()' is defined in 'include.inc.php'

				if (!eregi("^cli", $client))
					header("Location: show.php"); // redirect back to 'show.php'

				exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
			}

			$query .= connectConditionals();

			if ($recordConditionalSelector == "is equal to")
				$query .= " serial = " . quote_smart($serial);

			elseif ($recordConditionalSelector == "is within list")
			{
				// replace any non-digit chars with "|":
				$serial = preg_replace("/\D+/", "|", $serial);
				// strip "|" from beginning/end of string (if any):
				$serial = preg_replace("/^\|?(.+?)\|?$/", "\\1", $serial);

				$query .= " serial RLIKE " . quote_smart("^(" . $serial . ")$");
			}

			else // $recordConditionalSelector == "contains"
				$query .= " serial RLIKE " . quote_smart($serial);
		}

		if (!empty($date) AND !empty($time)) // if both, 'date' AND 'time' parameters are present:
		{
			if ($when == "edited")
			{
				$queryDateField = "modified_date";
				$queryTimeField = "modified_time";
			}
			else
			{
				$queryDateField = "created_date";
				$queryTimeField = "created_time";
			}

			if ($range == "after")
			{
				// return all records whose created/modified time is after '$time' of the given '$date' -OR- where the created/modified date is after '$date':
				$searchOperatorDate = ">";
				$searchOperatorTime = ">";
			}

			elseif ($range == "equal_or_after")
			{
				// return all records whose created/modified time is equal or after '$time' of the given '$date' -OR- where the created/modified date is after '$date':
				$searchOperatorDate = ">";
				$searchOperatorTime = ">=";
			}

			elseif ($range == "before")
			{
				// return all records whose created/modified time is before '$time' of the given '$date' -OR- where the created/modified date is before '$date':
				$searchOperatorDate = "<";
				$searchOperatorTime = "<";
			}

			elseif ($range == "equal_or_before")
			{
				// return all records whose created/modified time is equal or before '$time' of the given '$date' -OR- where the created/modified date is before '$date':
				$searchOperatorDate = "<";
				$searchOperatorTime = "<=";
			}

			else
			{
				// return all records whose created/modified date & time matches exactly '$date' and '$time':
				$searchOperatorDate = "=";
				$searchOperatorTime = "=";
			}

			$query .= connectConditionals();

			if (($searchOperatorDate == "=") AND ($searchOperatorTime == "="))
				$query .= " " . $queryDateField . " = " . quote_smart($date) . " AND " . $queryTimeField . " = " . quote_smart($time);
			else
				$query .= " ((" . $queryDateField . " = " . quote_smart($date) . " AND " . $queryTimeField . " " . $searchOperatorTime . " " . quote_smart($time) . ") OR " . $queryDateField . " " . $searchOperatorDate . " " . quote_smart($date) . ")";
		}

		elseif (!empty($date)) // if only the 'date' parameter is present (and not the 'time' parameter):
		{
			if ($range == "after")
				$searchOperator = ">"; // return all records whose created/modified date is after '$date'
			elseif ($range == "equal_or_after")
				$searchOperator = ">="; // return all records whose created/modified date equals or is after '$date'
			elseif ($range == "before")
				$searchOperator = "<"; // return all records whose created/modified date is before '$date'
			elseif ($range == "equal_or_before")
				$searchOperator = "<="; // return all records whose created/modified date equals or is before '$date'
			else
				$searchOperator = "="; // return all records whose created/modified date matches exactly '$date'

			$query .= connectConditionals();

			if ($when == "edited")
				$query .= " modified_date " . $searchOperator . " " . quote_smart($date);
			else
				$query .= " created_date " . $searchOperator . " " . quote_smart($date);
		}

		elseif (!empty($time)) // if only the 'time' parameter is present (and not the 'date' parameter):
		{
			if ($range == "after")
				$searchOperator = ">"; // return all records whose created/modified time is after '$time'
			elseif ($range == "equal_or_after")
				$searchOperator = ">="; // return all records whose created/modified time equals or is after '$time'
			elseif ($range == "before")
				$searchOperator = "<"; // return all records whose created/modified time is before '$time'
			elseif ($range == "equal_or_before")
				$searchOperator = "<="; // return all records whose created/modified time equals or is before '$time'
			else
				$searchOperator = "="; // return all records whose created/modified time matches exactly '$time'

			$query .= connectConditionals();

			if ($when == "edited")
				$query .= " modified_time " . $searchOperator . " " . quote_smart($time);
			else
				$query .= " created_time " . $searchOperator . " " . quote_smart($time);
		}

		if (!empty($year)) // if the 'year' parameter is present:
		{
			$query .= connectConditionals();

			$query .= " year RLIKE " . quote_smart($year);
		}

		if (!empty($author)) // if the 'author' parameter is present:
		{
			$query .= connectConditionals();

			$query .= " author RLIKE " . quote_smart($author);
		}

		if (!empty($without)) // if the 'without' parameter is present:
		{
			$query .= connectConditionals();

			if (eregi("^dups$", $without))
				$query .= " (orig_record IS NULL OR orig_record < 0)";
		}

		if (!empty($title)) // if the 'title' parameter is present:
		{
			$query .= connectConditionals();

			$query .= " title RLIKE " . quote_smart($title);
		}

		if (!empty($publication)) // if the 'publication' parameter is present:
		{
			$query .= connectConditionals();

			$query .= " publication RLIKE " . quote_smart($publication);
		}

		if (!empty($abbrevJournal)) // if the 'abbrev_journal' parameter is present:
		{
			$query .= connectConditionals();

			$query .= " abbrev_journal RLIKE " . quote_smart($abbrevJournal);
		}

		if (!empty($keywords)) // if the 'keywords' parameter is present:
		{
			$query .= connectConditionals();

			$query .= " keywords RLIKE " . quote_smart($keywords);
		}

		if (!empty($abstract)) // if the 'abstract' parameter is present:
		{
			$query .= connectConditionals();

			$query .= " abstract RLIKE " . quote_smart($abstract);
		}

		if (!empty($area)) // if the 'area' parameter is present:
		{
			$query .= connectConditionals();

			$query .= " area RLIKE " . quote_smart($area);
		}

		if (!empty($notes)) // if the 'notes' parameter is present:
		{
			$query .= connectConditionals();

			$query .= " notes RLIKE " . quote_smart($notes);
		}

		if (!empty($location)) // if the 'location' parameter is present:
		{
			$query .= connectConditionals();

			$query .= " location RLIKE " . quote_smart($location);
		}

		if (!empty($type)) // if the 'type' parameter is present:
		{
			$query .= connectConditionals();

			$query .= " type RLIKE " . quote_smart($type);
		}

		if (!empty($contributionID)) // if the 'contribution_id' parameter is present:
		{
			$query .= connectConditionals();

			$query .= " contribution_id RLIKE " . quote_smart($contributionID);
		}

		if (!empty($thesis)) // if the 'thesis' parameter is present:
		{
			$query .= connectConditionals();

			if ($thesis == "yes")
				$query .= " thesis RLIKE \".+\"";				
			elseif ($thesis == "no")
				$query .= " (thesis IS NULL OR thesis = \"\")";
			else
				$query .= " thesis RLIKE " . quote_smart($thesis);
		}

		if (!empty($selected) AND !empty($userID)) // if the 'selected' parameter is present (in order to search for user specific fields (like 'selected'), the 'userID' parameter must be given as well!):
		{
			$query .= connectConditionals();

			$query .= " selected RLIKE " . quote_smart($selected); // we use 'selected RLIKE "..."' instead of 'selected = "..."' to allow command line utilities to query for '-s=.+' which will display records with 'selected=yes' AND with 'selected=no'
		}

		if (!empty($marked) AND !empty($userID)) // if the 'ismarked' parameter is present (in order to search for user specific fields (like 'marked'), the 'userID' parameter must be given as well!):
		{
			$query .= connectConditionals();

			$query .= " marked RLIKE " . quote_smart($marked); // regarding the use of RLIKE, see note for 'selected'
		}

		if (!empty($citeKey) AND !empty($userID)) // if the 'cite_key' parameter is present (in order to search for user specific fields (like 'cite_key'), the 'userID' parameter must be given as well!):
		{
			$query .= connectConditionals();

			if ($recordConditionalSelector == "is equal to")
				$query .= " cite_key = " . quote_smart($citeKey);

			elseif ($recordConditionalSelector == "is within list")
			{
				$citeKey = preg_quote($citeKey, ""); // escape any meta characters
				// replace any whitespace characters with "|":
				$citeKey = preg_replace("/\s+/", "|", $citeKey);
				// strip "|" from beginning/end of string (if any):
				$citeKey = preg_replace("/^\|?(.+?)\|?$/", "\\1", $citeKey);

				$query .= " cite_key RLIKE " . quote_smart("^(" . $citeKey . ")$");
			}

			else // $recordConditionalSelector == "contains"
				$query .= " cite_key RLIKE " . quote_smart($citeKey);
		}

		if (!empty($callNumber)) // if the 'call_number' parameter is present:
		{
			$query .= connectConditionals();

			// since 'show.php' will only allow a user to query his own call numbers we need to build a complete call number prefix (e.g. 'IPÖ @ msteffens') that's appropriate for this user:
			$callNumberPrefix = getCallNumberPrefix(); // function 'getCallNumberPrefix()' is defined in 'include.inc.php'

			if ($recordConditionalSelector == "is equal to")
				$query .= " call_number RLIKE " . quote_smart("(^|.*;) *" . $callNumberPrefix . " @ " . $callNumber . " *(;.*|$)");

			elseif ($recordConditionalSelector == "is within list")
			{
				$callNumber = preg_quote($callNumber, ""); // escape any meta characters
				// replace any whitespace characters with "|":
				$callNumber = preg_replace("/\s+/", "|", $callNumber);
				// strip "|" from beginning/end of string (if any):
				$callNumber = preg_replace("/^\|?(.+?)\|?$/", "\\1", $callNumber);

				$query .= " call_number RLIKE " . quote_smart("(^|.*;) *" . $callNumberPrefix . " @ (" . $callNumber . ") *(;.*|$)");
			}

			else // $recordConditionalSelector == "contains"
				$query .= " call_number RLIKE " . quote_smart($callNumberPrefix . " @ [^@;]*" . $callNumber . "[^@;]*");
		}

		if (!empty($where)) // if the 'where' parameter is present:
		{
			$query .= connectConditionals();

			$where = extractWhereClause(" WHERE " . $where); // attempt to sanitize custom WHERE clause from SQL injection attacks
			$query .= " (" . $where . ")"; // add custom WHERE clause
		}

		// If, for some odd reason, 'records=all' was passed together with other parameters (such as in '.../show.php?records=all&author=steffens') we'll remove again
		// the generic WHERE clause part (i.e. ' serial RLIKE ".+"') from the query since its superfluous and would confuse other features (such as the "Seach within Results" functionality):
		if (eregi('WHERE serial RLIKE "\.\+" AND', $query))
			$query = eregi_replace('WHERE serial RLIKE "\.\+" AND', 'WHERE', $query); // remove superfluous generic WHERE clause

		elseif (eregi("WHERE$", $query)) // if still no WHERE clause was added (which is the case for URLs like 'show.php?submit=Browse&by=author')
			$query .= " serial RLIKE \".+\""; // add generic WHERE clause


		// Build GROUP BY clause:
		if ($displayType == "Browse")
			$query .= " GROUP BY " . escapeSQL($browseByField); // for Browse view, group records by the chosen field


		// Build ORDER BY clause:
		if ($displayType == "Browse")
		{
			$query .= " ORDER BY records DESC, " . escapeSQL($browseByField);
		}
		else
		{
			if ($citeOrder == "year")
				$query .= " ORDER BY year DESC, first_author, author_count, author, title"; // sort records first by year (descending), then in the usual way

			elseif ($citeOrder == "type") // sort records first by record type (and thesis type), then in the usual way:
				$query .= " ORDER BY type DESC, thesis DESC, first_author, author_count, author, year, title";

			elseif ($citeOrder == "type-year") // sort records first by record type (and thesis type), then by year (descending), then in the usual way:
				$query .= " ORDER BY type DESC, thesis DESC, year DESC, first_author, author_count, author, title";

			else // if any other or no 'citeOrder' parameter is specified
			{
				if (!empty($recordIDSelector)) // if a record identifier (either 'serial', 'call_number' or 'cite_key') was entered via the 'show.php' web form
					$query .= " ORDER BY " . escapeSQL($recordIDSelector) . ", author, year DESC, publication"; // sort by the appropriate column

				else // supply the default ORDER BY clause:
				{
					if ($displayType == "Cite")
						$query .= " ORDER BY first_author, author_count, author, year, title";
					else
						$query .= " ORDER BY author, year DESC, publication";
				}
			}
		}

		// Build the correct query URL:
		// (we skip unnecessary parameters here since 'search.php' will use it's default values for them)
		$queryParametersArray = array("sqlQuery"             => $query,
		                              "client"               => $client,
		                              "formType"             => "sqlSearch",
		                              "submit"               => $displayType,
		                              "viewType"             => $viewType,
		                              "showQuery"            => $showQuery,
		                              "showLinks"            => $showLinks,
		                              "showRows"             => $showRows,
		                              "rowOffset"            => $rowOffset,
		                              "wrapResults"          => $wrapResults,
		                              "citeOrder"            => $citeOrder,
		                              "citeStyleSelector"    => $citeStyle,
		                              "exportFormatSelector" => $exportFormat,
		                              "exportType"           => $exportType,
		                              "citeType"             => $citeType,
		                              "headerMsg"            => $headerMsg
		                             );

		// Call 'search.php' in order to display record details:
		if ($_SERVER['REQUEST_METHOD'] == "POST")
		{
			// save POST data to session variable:
			// NOTE: If the original request was a POST (as is the case for the refbase command line client) saving POST data to a session
			//       variable allows to retain large param/value strings (that would exceed the maximum string limit for GET requests).
			//       'search.php' will then write the saved POST data back to '$_POST' and '$_REQUEST'. (see also note and commented code below)
			saveSessionVariable("postData", $queryParametersArray); // function 'saveSessionVariable()' is defined in 'include.inc.php'

			header("Location: search.php?client=" . $client); // we also pass the 'client' parameter in the GET request so that it's available to 'search.php' before sessions are initiated
		}
		else
		{
			$queryURL = "";
			foreach ($queryParametersArray as $varname => $value)
				$queryURL .= "&" . $varname . "=" . rawurlencode($value);

			header("Location: search.php?$queryURL");
		}

		// NOTE: If the original request was a POST (as is the case for the refbase command line client), we must also pass the data via POST to 'search.php'
		//       in order to retain large param/value strings (that would exceed the maximum string limit for GET requests). We could POST the data via function
		//       'sendPostRequest()' as shown in the commented code below. However, the problem with this is that this does NOT *redirect* to 'search.php' but
		//       directly prints results from within this script ('show.php'). Also, the printed results include the full HTTP response, including the HTTP header.
//		if ($_SERVER['REQUEST_METHOD'] == "POST") // redirect via a POST request:
//		{
//			// extract the host & path on server from the base URL:
//			$host = preg_replace("#^[^:]+://([^/]+).*#", "\\1", $databaseBaseURL); // variable '$databaseBaseURL' is defined in 'ini.inc.php'
//			$path = preg_replace("#^[^:]+://[^/]+(/.*)#", "\\1", $databaseBaseURL);
//
//			// send POST request:
//			$httpResult = sendPostRequest($host, $path . "search.php", $databaseBaseURL . "show.php", $queryURL); // function 'sendPostRequest()' is defined in 'include.inc.php'
//			echo $httpResult;
//		}
//		else // redirect via a GET request:
//			header("Location: search.php?$queryURL");
	}


	// -------------------------------------------------------------------------------------------------------------------


	// this function will connect multiple WHERE clause parts with " AND" if required:
	function connectConditionals()
	{
		global $multipleParameters;
		global $queryType;

		if ($multipleParameters)
		{
			$queryConnector = " " . $queryType;
		}
		else
		{
			$queryConnector = "";
			$multipleParameters = true;
		}

		return $queryConnector;
	}
?>
