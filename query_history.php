<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./receipt.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    09-May-08, 12:00
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This php script will display a query history for the user's
	// current session, i.e. it will display links to any previous
	// search results
	// TODO: I18n


	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/header.inc.php'; // include header
	include 'includes/footer.inc.php'; // include footer
	include 'includes/include.inc.php'; // include common functions
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

	// [ Extract form variables sent through POST/GET by use of the '$_REQUEST' variable ]
	// [ !! NOTE !!: for details see <http://www.php.net/release_4_2_1.php> & <http://www.php.net/manual/en/language.variables.predefined.php> ]

	// If there's no stored message available:
	if (!isset($_SESSION['HeaderString']))
		$HeaderString = "Recall a query from your current session:"; // Provide the default message
	else
	{
		$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

		// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
		deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	}

	// Extract the view type requested by the user (either 'Mobile', 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";

	if (isset($_REQUEST['wrapResults']) AND ($_REQUEST['wrapResults'] == "0"))
		$wrapResults = "0"; // 'wrapResults=0' causes refbase to output only a partial document structure containing solely the query history (i.e. everything is omitted except for the <div id="queryhistory">)
	else
		$wrapResults = "1"; // we'll output a full HTML document structure unless the 'wrapResults' parameter is set explicitly to "0"

	// Get the query URL of the formerly displayed results page:
	if (isset($_SESSION['oldQuery']))
		$oldQuery = $_SESSION['oldQuery'];
	else
		$oldQuery = array();

	// Get any saved links to previous search results:
	if (isset($_SESSION['queryHistory']))
		$queryHistory = $_SESSION['queryHistory'];
	else
		$queryHistory = array();

	// Check if there's any query history available:
	if (empty($queryHistory))
	{
		// return an appropriate error message:
		$HeaderString = returnMsg("No query history available!", "warning", "strong", "HeaderString"); // function 'returnMsg()' is defined in 'include.inc.php'
		
		header("Location: " . $referer); // variable '$referer' is globally defined in function 'start_session()' in 'include.inc.php'

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// (4) DISPLAY HEADER & RESULTS
	//     (NOTE: Since there's no need to query the database here, we won't perform any of the following: (1) OPEN CONNECTION, (2) SELECT DATABASE, (3) RUN QUERY, (5) CLOSE CONNECTION)

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (4a) DISPLAY header:
	// Call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	if ($wrapResults != "0")
	{
		displayHTMLhead(encodeHTML($officialDatabaseName) . " -- Query History", "noindex,nofollow", "Displays links to previous search results", "", false, "", $viewType, array());
		if (!eregi("^(Print|Mobile)$", $viewType)) // Note: we omit the visible header in print/mobile view ('viewType=Print' or 'viewType=Mobile')
			showPageHeader($HeaderString);

		echo "\n";
	}


	// (4b) DISPLAY results:
	echo "<div id=\"queryhistory\">";

	// Print a link to the current query:
	if (!empty($oldQuery))
	{
		echo "\n\t<div id=\"currentquery\">"
		   . "\n\t\t<h5>Current Query</h5>";

		// Extract the 'WHERE' clause from the current SQL query:
		$queryWhereClause = extractWHEREclause($oldQuery["sqlQuery"]); // function 'extractWHEREclause()' is defined in 'include.inc.php'
		$queryTitle = encodeHTML(explainSQLQuery($queryWhereClause)); // functions 'encodeHTML()' and 'explainSQLQuery()' are defined in 'include.inc.php'

		// Generate a 'search.php' URL that points to the current query:
		$queryURL = generateURL("search.php", "html", $oldQuery, true); // function 'generateURL()' is defined in 'include.inc.php'

		echo "\n\t\t<div class=\"even\">"
		   . "\n\t\t\t<a href=\"" . $queryURL . "\">" . $queryTitle . "</a>"
		   . "\n\t\t</div>"
		   . "\n\t</div>";
	}

	// Print links to any previous search results:
	if (!empty($queryHistory))
	{
		echo "\n\t<div id=\"previousqueries\">"
		   . "\n\t\t<h5>Previous Queries</h5>";

		$queryHistory = array_reverse($queryHistory);

		// Display links to previous search results:
		for ($i = 0; $i < count($queryHistory); $i++)
		{
			if (is_integer($i / 2)) // if we currently are at an even number of rows
				$rowClass = "even";
			else
				$rowClass = "odd";

			echo "\n\t\t<div class=\"" . $rowClass . "\">"
			   . "\n\t\t\t" . $queryHistory[$i]
			   . "\n\t\t</div>";
		}

		echo "\n\t</div>";
	}

	echo "\n</div>";

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// Call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
	if ($wrapResults != "0")
	{
		if (!eregi("^(Print|Mobile)$", $viewType)) // Note: we omit the visible footer in print/mobile view ('viewType=Print' or 'viewType=Mobile')
			showPageFooter($HeaderString);

		displayHTMLfoot();
	}

	// --------------------------------------------------------------------
?>
