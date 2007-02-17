<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./sru.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    17-May-05, 16:22
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This script serves as a (faceless) routing page which takes a SRU query and
	// converts the query into a native refbase query which is then passed to 'search.php'.
	// More info is given at <http://sru.refbase.net/>.

	// Supports 'explain' and 'searchRetrieve' operations (but not 'scan') and outputs
	// records as MODS XML wrapped into SRW XML. Allows to query all global refbase fields
	// (the given index name must match either one of the 'set.index' names listed
	// in the explain response or match a refbase field name directly). If no
	// index name is given the 'serial' field will be searched by default.

	// Examples for recognized SRU/CQL queries:
	//
	// - ask the server to explain its SRW/U server & capabilities:
	//     sru.php
	//     sru.php?
	//     sru.php?operation=explain&version=1.1
	//
	// - return record with serial number 1:
	//     sru.php?version=1.1&query=1
	//     sru.php?version=1.1&query=1&operation=searchRetrieve&recordPacking=xml&recordSchema=mods
	//
	// - find all records where the title field contains either 'ecology' or 'diversity':
	//     sru.php?version=1.1&query=title%20any%20ecology%20diversity
	//
	// - find all records where the author field contains both 'dieckmann' and 'thomas':
	//     sru.php?version=1.1&query=author%20all%20dieckmann%20thomas
	//
	// - find all records where the publication field equals exactly 'Marine Ecology Progress Series':
	//     sru.php?version=1.1&query=publication%20exact%20Marine%20Ecology%20Progress%20Series
	//
	// - find all records where the year field is greater than or equals '2005':
	//     sru.php?version=1.1&query=year>=2005
	//
	// - find records with serial numbers 1, 123, 499, 612, 21654 & 23013 but
	//   return only the three last records:
	//     sru.php?version=1.1&query=1%20123%20499%20612%2021654%2023013&startRecord=4&maximumRecords=3
	//
	// - return just the number of found records (but not the full record data):
	//     sru.php?version=1.1&query=1%20123%20499%20612%2021654%2023013&maximumRecords=0
	//
	// - supress the default stylesheet or specify your own:
	//     sru.php?version=1.1&query=1&stylesheet=
	//     sru.php?version=1.1&query=1&stylesheet=xml2html.xsl

	// Note that (if the 'version' & 'query' parameters are present in the
	// query) 'operation=searchRetrieve' is assumed if omitted. Additionally,
	// only 'recordPacking=xml' and 'recordSchema=mods' are supported and
	// 'sru.php' will use these settings by default if not given in the query.
	// Data will be returned together with a default stylesheet if the
	// 'stylesheet' parameter wasn't given in the query. XPath, sort and
	// result sets are not supported and only SRW version 1.1 is recognized.

	// For more on SRW/SRU, see:
	//   <http://www.loc.gov/standards/sru>

	// TODO: - proper parsing of CQL query string (currently, 'sru.php' allows only for a limited set of CQL queries)
	//       - offer support for the boolean CQL operators 'and/or/not' and parentheses
	//       - honour the 'sortKeys' parameter and return records sorted accordingly


	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/include.inc.php'; // include common functions
	include 'initialize/ini.inc.php'; // include common variables
	include 'includes/srwxml.inc.php'; // include functions that deal with SRW XML
	include_once 'includes/webservice.inc.php'; // include functions that are commonly used with the refbase webservices

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// --------------------------------------------------------------------

	// Initialize preferred display language:
	// (note that 'locales.inc.php' has to be included *after* the call to the 'start_session()' function)
	include 'includes/locales.inc.php'; // include the locales

	// --------------------------------------------------------------------

	// Extract mandatory parameters passed to the script:

	if (isset($_REQUEST['query']))
		$sruQuery = $_REQUEST['query'];
	else
		$sruQuery = "";

	if (isset($_REQUEST['version']))
		$sruVersion = $_REQUEST['version'];
	else
		$sruVersion = "";

	// Extract optional parameters passed to the script:

	if (isset($_REQUEST['operation']))
		$sruOperation = $_REQUEST['operation'];
	else
		$sruOperation = "searchRetrieve"; // we assume a 'searchRetrieve' operation if not given

	if (isset($_REQUEST['recordSchema'])) // note that we'll currently always output as 'mods'
		$sruRecordSchema = $_REQUEST['recordSchema'];
	else
		$sruRecordSchema = "mods";

	if (isset($_REQUEST['recordPacking'])) // note that we'll currently always output as 'xml'
		$sruRecordPacking = $_REQUEST['recordPacking'];
	else
		$sruRecordPacking = "xml";

	if (isset($_REQUEST['maximumRecords'])) // contains the desired number of search results (OpenSearch equivalent: '{count}')
		$showRows = $_REQUEST['maximumRecords'];
	else
		$showRows = $defaultNumberOfRecords; // '$defaultNumberOfRecords' is defined in 'ini.inc.php'

	if (isset($_REQUEST['startRecord'])) // contains the offset of the first search result, starting with one (OpenSearch equivalent: '{startIndex}')
		$rowOffset = ($_REQUEST['startRecord']) - 1; // first row number in a MySQL result set is 0 (not 1)
	else
		$rowOffset = ""; // if no value to the 'startRecord' parameter is given, we'll output records starting with the first record in the result set

	if (isset($_REQUEST['stylesheet'])) // contains the desired stylesheet to be returned for transformation of XML data
		$exportStylesheet = $_REQUEST['stylesheet'];
	else
		$exportStylesheet = "srwmods2html.xsl"; // we provide a default stylesheet if no stylesheet was specified in the query (for 'operation=explain', a different default stylesheet will be assigned below)

	// Note that PHP will translate dots ('.') in parameter names into substrings ('_'). This is so that the
	// import_request_variables function can generate legitimate variable names (and a . is not permissable
	// in variable names in PHP). See the section labelled "Dots in incoming variable names" on this page:
	// <http://uk.php.net/variables.external>. So "$_REQUEST['x-info-2-auth1_0-authenticationToken']" will catch
	// the 'x-info-2-auth1.0-authenticationToken' parameter (thanks to Matthew J. Dovey for pointing this out!).
	if (isset($_REQUEST['x-info-2-auth1_0-authenticationToken'])) // PHP converts the dot in 'x-info-2-auth1.0-authenticationToken' into a substring!
		$authenticationToken = $_REQUEST['x-info-2-auth1_0-authenticationToken'];
	else
		$authenticationToken = "";

	// The following (optional) parameters are extracted but are not supported yet:

	if (isset($_REQUEST['sortKeys']))
		$sruSortKeys = $_REQUEST['sortKeys'];
	else
		$sruSortKeys = "";

	if (isset($_REQUEST['recordXPath']))
		$sruRecordXPath = $_REQUEST['recordXPath'];
	else
		$sruRecordXPath = "";

	if (isset($_REQUEST['resultSetTTL']))
		$sruResultSetTTL = $_REQUEST['resultSetTTL'];
	else
		$sruResultSetTTL = "";

	if (isset($_REQUEST['extraRequestData']))
		$sruExtraRequestData = $_REQUEST['extraRequestData'];
	else
		$sruExtraRequestData = "";

	// For the context of 'sru.php' we set some parameters explicitly:

	$displayType = "Export";
	$exportFormat = "SRW XML";
	$exportType = "xml";
	$showLinks = "1";
	$exportContentType = "application/xml";

	// -------------------------------------------------------------------------------------------------------------------

	$userID = "";

	if (preg_match('/^(bib.citekey|cite_key)( +(all|any|exact|within) +| *(<>|<=|>=|<|>|=) *)/', $sruQuery)) // if the given index is a recognized user-specific field
		$userSpecificIndex = true;
	else
		$userSpecificIndex = false;

	// return diagnostic if no authentication token was given while querying a user-specific index:
	if (empty($authenticationToken) AND $userSpecificIndex)
	{
		returnDiagnostic(3, "Querying of user-specific fields requires the 'x-info-2-auth1.0-authenticationToken' parameter (format: 'email=<email_address>')"); // authentication error: 'x-...authenticationToken' parameter is missing but required
		exit;
	}
	else if (!empty($authenticationToken)) // extract any authentication information that was passed with the query:
	{
		if (preg_match('/^email=.+/i', $authenticationToken))
		{
			$userEmail = preg_replace('/^email=(.+)/i', '\\1', $authenticationToken);

			$userID = getUserID($userEmail); // get the correct user ID for the passed email address (function 'getUserID()' is defined in 'include.inc.php')
		}

		// if an unrecognized email address was given while querying a user-specific index:
		if (empty($userID) AND $userSpecificIndex)
		{
			returnDiagnostic(3, "Couldn't map given authentication token to an existing user (expecting format: 'email=<email_address>'"); // authentication error: couldn't map email address to user ID
			exit;
		}
	}

	// -------------------------------------------------------------------------------------------------------------------

	// Parse CQL query:
	$searchArray = parseCQL($sruVersion, $sruQuery); // function 'parseCQL()' is defined in 'webservice.inc.php'

	// -------------------------------------------------------------------------------------------------------------------

	// Check for operation and that mandatory parameters have been passed:
	if ($sruOperation == "explain" OR (!isset($_REQUEST['query']) AND !isset($_REQUEST['version']) AND !isset($_REQUEST['operation']) AND !isset($_REQUEST['recordSchema']) AND !isset($_REQUEST['recordPacking']) AND !isset($_REQUEST['maximumRecords']) AND !isset($_REQUEST['startRecord']) AND !isset($_REQUEST['sortKeys']) AND !isset($_REQUEST['recordXPath']) AND !isset($_REQUEST['stylesheet']) AND !isset($_REQUEST['x-info-2-auth1_0-authenticationToken'])))
	{
		// if 'sru.php' was called with 'operation=explain' -OR- without any recognized parameters, we'll return an appropriate 'explainResponse':

		// use an appropriate default stylesheet:
		if ($exportStylesheet == "srwmods2html.xsl")
			$exportStylesheet = "srwExplainResponse2html.xsl";

		// Set the appropriate mimetype & set the character encoding to the one given
		// in '$contentTypeCharset' (which is defined in 'ini.inc.php'):
		setHeaderContentType($exportContentType, $contentTypeCharset); // function 'setHeaderContentType()' is defined in 'include.inc.php'

		echo srwExplainResponse($exportStylesheet); // function 'srwExplainResponse()' is defined in 'srwxml.inc.php'
	}

	// if 'sru.php' was called without any valid (or with incorrect) parameters, we'll return appropriate 'diagnostics':
	elseif (!eregi("^(explain|searchRetrieve)$",$sruOperation))
		returnDiagnostic(4, "Only 'explain' and 'searchRetrieve' operations are supported");

	elseif (empty($sruQuery))
		returnDiagnostic(7, "query"); // required 'query' parameter is missing

	elseif (empty($sruVersion))
		returnDiagnostic(7, "version"); // required 'version' parameter is missing

	elseif ($sruVersion != "1.1")
		returnDiagnostic(5, "1.1"); // only SRW version 1.1 is supported

	elseif (!eregi("^mods$",$sruRecordSchema) AND !eregi("^info:srw/schema/1/mods-v3\.0$",$sruRecordSchema) AND !eregi("^http://www\.loc\.gov/mods/v3$",$sruRecordSchema))
		returnDiagnostic(66, $sruRecordSchema); // no other schema than MODS is supported

	elseif (!eregi("^xml$",$sruRecordPacking))
		returnDiagnostic(71, "Only 'recordPacking=xml' is supported"); // no other record packing than XML is supported

	elseif (!empty($sruRecordXPath))
		returnDiagnostic(72, ""); // XPath isn't supported yet

	elseif (!empty($sruSortKeys))
		returnDiagnostic(80, ""); // Sort isn't supported yet

	elseif (!empty($sruResultSetTTL))
		returnDiagnostic(50, ""); // Result sets aren't supported

	// -------------------------------------------------------------------------------------------------------------------

	else // the script was called at least with the required parameters 'query' and 'version'
	{

//		// NOTE: the generation of SQL queries (or parts of) should REALLY be modular and be moved to separate dedicated functions!

		// CONSTRUCT SQL QUERY:

		// Note: the 'verifySQLQuery()' function that gets called by 'search.php' to process query data with "$formType = sqlSearch" will add the user-specific fields to the 'SELECT' clause
		// (with one exception: see note below!) and the 'LEFT JOIN...' part to the 'FROM' clause of the SQL query if a user is logged in. It will also add 'orig_record', 'serial', 'file',
		// 'url' & 'doi' columns as required. Therefore it's sufficient to provide just the plain SQL query here:

		// Build SELECT clause:
		// select all fields required to export a record as SRW XML:
		$query = "SELECT author, title, type, year, publication, abbrev_journal, volume, issue, pages, corporate_author, thesis, address, keywords, abstract, publisher, place, editor, language, summary_language, orig_title, series_editor, series_title, abbrev_series_title, series_volume, series_issue, edition, issn, isbn, medium, area, expedition, conference, notes, approved, location, online_publication, online_citation, call_number, serial";
		//           (the above string MUST end with ", call_number, serial" in order to have the described query completion feature work correctly!

		// if a user-specific index was queried together with an authentication token that could be resolved to a user ID
		// - AND no user is logged in
		// - OR a user is logged in but the user ID does not match the current user's own ID
		// then we'll add user-specific fields here (as opposed to have them added by function 'verifySQLQuery()').
		// By adding fields after ", call_number, serial" we'll avoid the described query completion from function 'verifySQLQuery()'. This is done on purpose
		// here since (while user 'A' should be allowed to query cite keys of user 'B') we don't want user 'A' to be able to view other user-specific content of
		// user 'B'. By adding only 'cite_key' here, no other user-specific fields will be disclosed in case a logged-in user queries another user's cite keys.
		if ($userSpecificIndex AND (!empty($userID)) AND (!isset($_SESSION['loginEmail']) OR (isset($_SESSION['loginEmail']) AND ($userID != getUserID($loginEmail))))) // the session variable '$loginEmail' is made available globally by the 'start_session()' function
			$query .= ", cite_key"; // add 'cite_key' field


		// Build FROM clause:
		// We'll explicitly add the 'LEFT JOIN...' part to the 'FROM' clause of the SQL query if '$userID' isn't empty. This is done to allow querying
		// of the user-specific 'cite_key' field by users who are not logged in (function 'verifySQLQuery()' won't touch the 'LEFT JOIN...' or WHERE clause part
		// for users who aren't logged in if the query originates from 'sru.php'). For logged in users, the 'verifySQLQuery()' function would add a 'LEFT JOIN...'
		// statement (if not present) containing the users *own* user ID. By adding the 'LEFT JOIN...' statement explicitly here (which won't get touched by
		// 'verifySQLQuery()') we allow any user's 'cite_key' field to be queried by every user (e.g., by URLs like: 'sru.php?version=1.1&query=bib.citekey=...&x-info-2-auth1.0-authenticationToken=email=...').
		// Note that if you enable other user-specific fields in function 'mapCQLIndexes()' (in 'webservice.inc.php') then these fields will be allowed to be queried by everyone as well!
		if (!empty($userID)) // the 'x-...authenticationToken' parameter was specified containing an email address that could be resolved to a user ID -> include user specific fields
			$query .= " FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = " . quote_smart($userID); // add FROM clause (including the 'LEFT JOIN...' part); '$tableRefs' and '$tableUserData' are defined in 'db.inc.php'
		else
			$query .= " FROM $tableRefs"; // add FROM clause


		if (!empty($searchArray))
		{
			// Build WHERE clause:
			$query .= " WHERE";

			appendToWhereClause($searchArray); // function 'appendToWhereClause()' is defined in 'webservice.inc.php'
		}


		// Build ORDER BY clause:
		$query .= " ORDER BY serial";

		// --------------------------------------------------------------------

		// Build the correct query URL:
		// (we skip unnecessary parameters here since 'search.php' will use it's default values for them)
		$queryURL = "sqlQuery=" . rawurlencode($query) . "&formType=sqlSearch&submit=" . $displayType . "&showRows=" . $showRows . "&rowOffset=" . $rowOffset . "&showLinks=" . $showLinks . "&exportFormatSelector=" . rawurlencode($exportFormat) . "&exportType=" . $exportType . "&exportStylesheet=" . $exportStylesheet;

		// call 'search.php' with the correct query URL in order to display record details:
		header("Location: search.php?$queryURL");
	}

	// -------------------------------------------------------------------------------------------------------------------

	// Return a diagnostic error message:
	function returnDiagnostic($diagCode, $diagDetails)
	{
		global $exportContentType;
		global $contentTypeCharset; // '$contentTypeCharset' is defined in 'ini.inc.php'
		global $exportStylesheet;

		// Set the appropriate mimetype & set the character encoding to the one given in '$contentTypeCharset':
		setHeaderContentType($exportContentType, $contentTypeCharset); // function 'setHeaderContentType()' is defined in 'include.inc.php'

		// Return SRW diagnostics (i.e. SRW error information) wrapped into SRW XML ('searchRetrieveResponse'):
		echo srwDiagnostics($diagCode, $diagDetails, $exportStylesheet); // function 'srwDiagnostics()' is defined in 'srwxml.inc.php'
	}

	// -------------------------------------------------------------------------------------------------------------------
?>
