<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./rss.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    25-Sep-04, 12:10
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This script will generate a dynamic RSS feed for the current query.
	// Usage: Perform your query until you've got the desired results. Then, copy the "RSS" link in the header
	// message of any search results page and use this URL as feed URL when subscribing within your Newsreader.

	// TODO: - support 'startRecord' parameter
	//       - support 'citeOrder' parameter to allow for sort options other than the current default
	//         (which equals '$citeOrder="creation-date"' in 'show.php')

	// NOTE: As an alternative to fixing/improving 'rss.php', it may be desirable to add "RSS XML" as a proper
	//       export format, and let 'show.php' generate the RSS output (this would effectively deprecate 'rss.php');
	//       However, the drawback would be longer/uglier RSS URLs...

	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/include.inc.php'; // include common functions
	include 'includes/cite.inc.php'; // include citation functions
	include 'initialize/ini.inc.php'; // include common variables

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// --------------------------------------------------------------------

	// Initialize preferred display language:
	// (note that 'locales.inc.php' has to be included *after* the call to the 'start_session()' function)
	include 'includes/locales.inc.php'; // include the locales

	// --------------------------------------------------------------------

	// Extract any parameters passed to the script:
	if (isset($_REQUEST['where']))
		$queryWhereClause = $_REQUEST['where']; // get the WHERE clause that was passed within the link
	else
		$queryWhereClause = "";

	if (isset($_REQUEST['showRows']) AND preg_match("/^[1-9]+[0-9]*$/", $_REQUEST['showRows'])) // contains the desired number of search results (OpenSearch equivalent: '{count}')
		$showRows = $_REQUEST['showRows'];
	else
		$showRows = $_SESSION['userRecordsPerPage']; // get the default number of records per page preferred by the current user

	// NOTE: while we read out the 'startRecord' parameter, it isn't yet supported by 'rss.php'!
	if (isset($_REQUEST['startRecord'])) // contains the offset of the first search result, starting with one (OpenSearch equivalent: '{startIndex}')
		$rowOffset = ($_REQUEST['startRecord']) - 1; // first row number in a MySQL result set is 0 (not 1)
	else
		$rowOffset = ""; // if no value to the 'startRecord' parameter is given, we'll output records starting with the first record in the result set

	if (isset($_REQUEST['recordSchema'])) // contains the desired response format; currently, 'rss.php' will only recognize 'rss' (outputs RSS 2.0), future versions may also allow for 'atom'
		$recordSchema = $_REQUEST['recordSchema'];
	else
		$recordSchema = "rss"; // if no particular response format was requested we'll output found results as RSS 2.0


	// Check the correct parameters have been passed:
	if (empty($queryWhereClause)) // if 'rss.php' was called without the 'where' parameter:
	{
		// return an appropriate error message:
		$HeaderString = returnMsg($loc["Warning_IncorrectOrMissingParams"] . " '" . scriptURL() . "'!", "warning", "strong", "HeaderString"); // functions 'returnMsg()' and 'scriptURL()' are defined in 'include.inc.php'

		// Redirect the browser back to the calling page:
		header("Location: " . $referer); // variable '$referer' is globally defined in function 'start_session()' in 'include.inc.php'
		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}
	else
	{
		$sanitizedWhereClause = extractWHEREclause(" WHERE " . $queryWhereClause); // attempt to sanitize custom WHERE clause from SQL injection attacks (function 'extractWHEREclause()' is defined in 'include.inc.php')
	}

	// --------------------------------------------------------------------

	// If we made it here, then the script was called with all required parameters (which, currently, is just the 'where' parameter :)

	// CONSTRUCT SQL QUERY:

	// Note: the 'verifySQLQuery()' function that gets called below will add the user specific fields to the 'SELECT' clause and the
	// 'LEFT JOIN...' part to the 'FROM' clause of the SQL query if a user is logged in. It will also add 'orig_record', 'serial', 'file', 'url', 'doi', 'isbn' & 'type' columns
	// as required. Therefore it's sufficient to provide just the plain SQL query here:
	$sqlQuery = buildSELECTclause("RSS", "1", "", false, false); // function 'buildSELECTclause()' is defined in 'include.inc.php'

	$sqlQuery .= " FROM $tableRefs WHERE " . $sanitizedWhereClause; // add FROM clause and the specified WHERE clause

	$sqlQuery .= " ORDER BY created_date DESC, created_time DESC, modified_date DESC, modified_time DESC, serial DESC"; // sort records such that newly added/edited records get listed top of the list

	// since a malicious user could change the 'where' parameter manually to gain access to user-specific data of other users, we'll run the SQL query thru the 'verifySQLQuery()' function:
	// (this function does also add/remove user-specific query code as required and will fix problems with escape sequences within the SQL query)
	$query = verifySQLQuery($sqlQuery, "", "RSS", "1"); // function 'verifySQLQuery()' is defined in 'include.inc.php'

	// the 'verifySQLQuery()' function will save an error message to the 'HeaderString' session variable if something went wrong (e.g., if a user who's NOT logged in tries to query user specific fields)
	if (isset($_SESSION['HeaderString'])) // if there's a 'HeaderString' session variable
	{
		header("Location: index.php"); // redirect to main page ('index.php') which will display the error message stored within the 'HeaderString' session variable
		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// (3) RUN the query on the database through the connection:
	$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

	// find out how many rows are available:
	$rowsFound = @ mysql_num_rows($result);

	// construct a meaningful channel description based on the specified 'WHERE' clause:
	$rssChannelDescription = "Displays all newly added records where " . explainSQLQuery($sanitizedWhereClause) . "."; // function 'explainSQLQuery()' is defined in 'include.inc.php'

	// Generate RSS XML data from the result set (upto the limit given in '$showRows'):
	$rssFeed = generateRSS($result, $showRows, $rssChannelDescription); // function 'generateRSS()' is defined in 'include.inc.php'
	// --------------------------------------------------------------------

	// (4) DISPLAY search results as RSS feed:
	// set mimetype to 'application/rss+xml' and character encoding to the one given in '$contentTypeCharset' (which is defined in 'ini.inc.php'):
	setHeaderContentType("application/rss+xml", $contentTypeCharset); // function 'setHeaderContentType()' is defined in 'include.inc.php'

	echo $rssFeed;

	// --------------------------------------------------------------------

	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase(); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------
?>
