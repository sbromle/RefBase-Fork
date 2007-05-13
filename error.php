<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./error.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    05-Jan-03, 16:35
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This php script will display an error page
	// showing any error that did occur. It will display
	// a link to the previous search results page (if any)


	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/header.inc.php'; // include header
	include 'includes/footer.inc.php'; // include footer
	include 'includes/include.inc.php'; // include common functions
	include 'initialize/ini.inc.php'; // include common variables

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(false);

	// --------------------------------------------------------------------

	// Initialize preferred display language:
	// (note that 'locales.inc.php' has to be included *after* the call to the 'start_session()' function)
	include 'includes/locales.inc.php'; // include the locales

	// --------------------------------------------------------------------

	// [ Extract form variables sent through POST/GET by use of the '$_REQUEST' variable ]
	// [ !! NOTE !!: for details see <http://www.php.net/release_4_2_1.php> & <http://www.php.net/manual/en/language.variables.predefined.php> ]

	// Check if any error occurred while processing the database UPDATE/INSERT/DELETE
	$errorNo = $_REQUEST['errorNo'];
	$errorMsg = $_REQUEST['errorMsg'];
	$errorMsg = stripSlashesIfMagicQuotes($errorMsg); // function 'stripSlashesIfMagicQuotes()' is defined in 'include.inc.php'
//	$errorMsg = ereg_replace("\\\\(['\"])","\\1",$errorMsg); // replace any \" or \' with " or ', respectively

	// Extract the header message that was returned by originating script:
	$HeaderString = $_REQUEST['headerMsg'];
	$HeaderString = stripSlashesIfMagicQuotes($HeaderString);
//	$HeaderString = ereg_replace("(\\\\)+(['\"])","\\2",$HeaderString); // replace any \" or \' with " or ', respectively (Note: the expression '\\\\' describes only *one* backslash! -> '\')

	// Extract the view type requested by the user (either 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";

	// Extract generic variables from the request:
	$oldQuery = $_REQUEST['oldQuery']; // fetch the query URL of the formerly displayed results page so that its's available on the subsequent receipt page that follows any add/edit/delete action!
	$oldQuery = stripSlashesIfMagicQuotes($oldQuery);
//	$oldQuery = str_replace('\"','"',$oldQuery); // replace any \" with "

	if (isset($_SERVER['HTTP_REFERER']))
		$referer = $_SERVER['HTTP_REFERER'];
	else
		$referer = "index.php"; // if there's no HTTP referer available we relocate back to the main page

	// --------------------------------------------------------------------

	// (4) DISPLAY HEADER & RESULTS
	//     (NOTE: Since there's no need to query the database here, we won't perform any of the following: (1) OPEN CONNECTION, (2) SELECT DATABASE, (3) RUN QUERY, (5) CLOSE CONNECTION)

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (4a) DISPLAY header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(encodeHTML($officialDatabaseName) . " -- Error", "noindex,nofollow", "Feedback page that shows any error that occurred while using the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
	showPageHeader($HeaderString, $oldQuery);


	// URL encode the sqlQuery part within '$oldQuery' while maintaining the rest unencoded(!):
	$oldQuerySQLPart = preg_replace("/sqlQuery=(.+?)&amp;.+/", "\\1", $oldQuery); // extract the sqlQuery part within '$oldQuery'
	$oldQueryOtherPart = preg_replace("/sqlQuery=.+?(&amp;.+)/", "\\1", $oldQuery); // extract the remaining part after the sqlQuery
	$oldQuerySQLPart = rawurlencode($oldQuerySQLPart); // URL encode sqlQuery part within '$oldQuery'
	$oldQueryPartlyEncoded = "sqlQuery=" . $oldQuerySQLPart . $oldQueryOtherPart; // Finally, we merge everything again

	// Build appropriate links:
	$links = "\n<tr>"
			. "\n\t<td>"
			. "\n\t\tChoose how to proceed:&nbsp;&nbsp;"
			. "\n\t\t<a href=\"" . str_replace('&','&amp;',$referer) . "\">Go Back</a>"; // provide a 'go back' link (the following would only work with javascript: <a href=\"javascript:history.back()\">Go Back</a>")

	if ($oldQuery != "") // only provide a link to any previous search results if '$oldQuery' isn't empty
		$links .= "\n\t\t&nbsp;&nbsp;-OR-&nbsp;&nbsp;"
				. "\n\t\t<a href=\"search.php?" . $oldQueryPartlyEncoded . "\">Display previous search results</a>";

	$links .= "\n\t\t&nbsp;&nbsp;-OR-&nbsp;&nbsp;"
			. "\n\t\t<a href=\"index.php\">Goto " . encodeHTML($officialDatabaseName) . " Home</a>" // we include the link to the home page here
			. "\n\t</td>"
			. "\n</tr>";

	// SHOW ERROR MESSAGE:

	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\">\n<tr>\n\t<td valign=\"top\"> Error "
		. $errorNo . " : <b>" . encodeHTML($errorMsg) . "</b>" // function 'encodeHTML()' is defined in 'include.inc.php'
		. "</td>\n</tr>"
		. $links		
		. "\n</table>";

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
	showPageFooter($HeaderString, $oldQuery);

	displayHTMLfoot();

	// --------------------------------------------------------------------

	exit; // die
?>
