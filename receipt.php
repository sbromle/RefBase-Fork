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
	// Created:    02-Jan-03, 22:43
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This php script will display a feedback page after any action of
	// adding/editing/deleting a record. It will display links to the
	// modified/added record as well as to the previous search results page (if any)
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

	// First of all, check if this script was called by something else than 'record.php' (via 'modify.php'):
	// Notes: - although 'receipt.php' gets actually called by 'modify.php', the referrer will be still set to 'record.php'
	//        - if a user clicks on Login/Logout while viewing a 'receipt.php' page she should get directed back to this receipt page (which is why 'receipt.php' must be also among the recognized referrers)
	if (!preg_match("/.*(record|receipt)\.php.*/", $referer)) // variable '$referer' is globally defined in function 'start_session()' in 'include.inc.php'
	{
		// return an appropriate error message:
		$HeaderString = returnMsg($loc["Warning_InvalidCallToScript"] . " '" . scriptURL() . "'!", "warning", "strong", "HeaderString"); // functions 'returnMsg()' and 'scriptURL()' are defined in 'include.inc.php'
		
		header("Location: " . $referer); // redirect to calling page

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// [ Extract form variables sent through POST/GET by use of the '$_REQUEST' variable ]
	// [ !! NOTE !!: for details see <http://www.php.net/release_4_2_1.php> & <http://www.php.net/manual/en/language.variables.predefined.php> ]

	// Extract the type of action requested by the user (either 'add', 'edit', 'delet' or ''):
	// ('' will be treated equal to 'add')
	$recordAction = $_REQUEST['recordAction'];
	if ("$recordAction" == "")
		$recordAction = "add"; // '' will be treated equal to 'add'

	// Extract the id number of the record that was added/edited/deleted by the user:
	$serialNo = $_REQUEST['serialNo'];

	// Extract the header message that was returned by 'modify.php':
	$HeaderString = $_REQUEST['headerMsg'];

	// Function 'showLogin()' in 'include.inc.php' requires the header string being available in the '$headerMsg' variable so that it gets included within the Login/Logout links:
	$headerMsg = $HeaderString;

	// Extract the view type requested by the user (either 'Mobile', 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";

	// Get the query URL of the last multi-record query:
	if (isset($_SESSION['oldMultiRecordQuery']))
		$oldMultiRecordQuery = $_SESSION['oldMultiRecordQuery'];
	else
		$oldMultiRecordQuery = "";

	// --------------------------------------------------------------------

	// (4) DISPLAY HEADER & RESULTS
	//     (NOTE: Since there's no need to query the database here, we won't perform any of the following: (1) OPEN CONNECTION, (2) SELECT DATABASE, (3) RUN QUERY, (5) CLOSE CONNECTION)

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (4a) DISPLAY header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(encodeHTML($officialDatabaseName) . " -- Record Action Feedback", "noindex,nofollow", "Feedback page that confirms any adding, editing or deleting of records in the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
	showPageHeader($HeaderString);


	// (4b) DISPLAY results:
	// construct the correct SQL query that will link back to the added/edited record:
	$sqlQuery = buildSELECTclause("Display", "1", "", true, false); // function 'buildSELECTclause()' is defined in 'include.inc.php'

	if (isset($_SESSION['loginEmail'])) // if a user is logged in, show user specific fields:
		$sqlQuery .= " FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = " . quote_smart($loginUserID) . " WHERE serial RLIKE " . quote_smart("^(" . $serialNo . ")$") . " ORDER BY author, year DESC, publication"; // we simply use the fixed default ORDER BY clause here
	else // if NO user logged in, don't display any user specific fields:
		$sqlQuery .= " FROM $tableRefs WHERE serial RLIKE " . quote_smart("^(" . $serialNo . ")$") . " ORDER BY author, year DESC, publication"; // we simply use the fixed default ORDER BY clause here

	$sqlQuery = rawurlencode($sqlQuery);

	// Generate a 'search.php' URL that points to the formerly displayed results page:
	if (!empty($oldMultiRecordQuery))
		$oldMultiRecordQueryURL = generateURL("search.php", "html", $oldMultiRecordQuery, true); // function 'generateURL()' is defined in 'include.inc.php'


	// Build a TABLE, containing one ROW and DATA tag:
	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds links to the added/edited records as well as to the previously displayed search results page\">"
	   . "\n<tr>"
	   . "\n\t<td valign=\"top\">"
	   . "\n\t\tChoose how to proceed:&nbsp;&nbsp;";

	if (isset($_SESSION['user_permissions']) AND ereg("allow_details_view", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does contain 'allow_details_view'...
	{
		if ($recordAction != "delet")
			echo "\n\t\t<a href=\"search.php?sqlQuery=" . $sqlQuery . "&amp;showQuery=0&amp;showLinks=1&amp;formType=sqlSearch&amp;submit=Display\">Show " . $recordAction . "ed record</a>";
	
		if ($recordAction != "delet" && !empty($oldMultiRecordQuery))
			echo "\n\t\t&nbsp;&nbsp;-OR-&nbsp;&nbsp;";
	}

	if (!empty($oldMultiRecordQuery)) // only provide a link to any previous search results if '$oldMultiRecordQuery' isn't empty
		echo "\n\t\t<a href=\"" . $oldMultiRecordQueryURL . "\">Display previous search results</a>";

	if ((isset($_SESSION['user_permissions']) AND ereg("allow_details_view", $_SESSION['user_permissions']) AND ($recordAction != "delet")) || !empty($oldMultiRecordQuery))
		echo "\n\t\t&nbsp;&nbsp;-OR-&nbsp;&nbsp;";

	echo "\n\t\t<a href=\"index.php\">Goto " . encodeHTML($officialDatabaseName) . " Home</a>"; // we include the link to the home page here so that "Choose how to proceed:" never stands without any link to go

	echo "\n\t</td>"
	   . "\n</tr>"
	   . "\n</table>";

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
	showPageFooter($HeaderString);

	displayHTMLfoot();

	// --------------------------------------------------------------------
?>
