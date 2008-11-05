<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./library_search.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    29-Jul-02, 16:39
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// Search form providing the main fields.
	// Searches will be restricted to records belonging
	// to the IPOE <http://www.uni-kiel.de/ipoe/> library.
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

	// (1) Open the database connection and use the literature database:
	connectToMySQLDatabase(); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// If there's no stored message available:
	if (!isset($_SESSION['HeaderString']))
		$HeaderString = "Search the $hostInstitutionAbbrevName library:"; // Provide the default message
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

	// Get the default number of records per page preferred by the current user:
	$showRows = $_SESSION['userRecordsPerPage'];

	// Get the user's preference for displaying auto-completions:
	$showAutoCompletions = $_SESSION['userAutoCompletions'];

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (2a) Display header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(encodeHTML($officialDatabaseName) . " -- Library Search", "index,follow", "Search the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
	showPageHeader($HeaderString);

	// (2b) Start <form> and <table> holding the form elements:
	echo "\n<form action=\"search.php\" method=\"GET\">";
	echo "\n<input type=\"hidden\" name=\"formType\" value=\"librarySearch\">"
			. "\n<input type=\"hidden\" name=\"showQuery\" value=\"0\">";
	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds the search form\">"
			. "\n<tr>"
			. "\n\t<th align=\"left\">Show</th>\n\t<th align=\"left\">Field</th>\n\t<th align=\"left\">&nbsp;</th>\n\t<th align=\"left\">That...</th>\n\t<th align=\"left\">Search String</th>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"20\" valign=\"middle\"><input type=\"checkbox\" name=\"showAuthor\" value=\"1\" checked></td>"
			. "\n\t<td width=\"40\"><b>Author:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td width=\"130\">\n\t\t<select name=\"authorSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"authorName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showTitle\" value=\"1\" checked></td>"
			. "\n\t<td><b>Title:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"titleSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"titleName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showYear\" value=\"1\" checked></td>"
			. "\n\t<td><b>Year:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"yearSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t\t<option>is greater than</option>\n\t\t\t<option>is less than</option>\n\t\t\t<option>is within range</option>\n\t\t\t<option>is within list</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"yearNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"20\" valign=\"middle\"><input type=\"checkbox\" name=\"showEditor\" value=\"1\"></td>"
			. "\n\t<td width=\"40\"><b>Editor:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td width=\"130\">\n\t\t<select name=\"editorSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"editorName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showSeriesTitle\" value=\"1\" checked></td>"
			. "\n\t<td><b>Series:</b></td>\n\t<td align=\"center\"><input type=\"radio\" name=\"seriesTitleRadio\" value=\"1\" checked></td>"
			. "\n\t<td>\n\t\t<select name=\"seriesTitleSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>";

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. The field name of the table's primary key
	// 4. Table name of the user data table
	// 5. The field name within the user data table that corresponds to the field in 3.
	// 6. The field name of the user ID field within the user data table
	// 7. The user ID of the currently logged in user (which must be provided as a session variable)
	// 8. Attribute that contains values
	// 9. <SELECT> element name
	// 10. An additional non-database value (display string)
	// 11. String that gets submitted instead of the display string given in 10.
	// 12. Optional <OPTION SELECTED>
	// 13. Restrict query to field... (keep empty if no restriction wanted)
	// 14. ...where field contents are...
	// 15. Split field contents into substrings? (yes = true, no = false)
	// 16. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	echo selectDistinct($connection,
	                    $tableRefs,
	                    "serial",
	                    $tableUserData,
	                    "record_id",
	                    "user_id",
	                    $loginUserID,
	                    "series_title",
	                    "seriesTitleName",
	                    $loc["All"],
	                    "All",
	                    $loc["All"],
	                    "",
	                    "",
	                    false,
	                    "");

	echo "\n\t</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td align=\"right\">or:</td>\n\t<td align=\"center\"><input type=\"radio\" name=\"seriesTitleRadio\" value=\"0\"></td>"
			. "\n\t<td>\n\t\t<select name=\"seriesTitleSelector2\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"seriesTitleName2\" size=\"42\"></td>"
			. "\n</tr>";

	// (4) Complete the form:
	echo "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showVolume\" value=\"1\"></td>"
			. "\n\t<td><b>Volume:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"volumeSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t\t<option>is greater than</option>\n\t\t\t<option>is less than</option>\n\t\t\t<option>is within range</option>\n\t\t\t<option>is within list</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"volumeNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showPages\" value=\"1\" checked></td>"
			. "\n\t<td><b>Pages:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"pagesSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"pagesNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"20\" valign=\"middle\"><input type=\"checkbox\" name=\"showPublisher\" value=\"1\"></td>"
			. "\n\t<td width=\"40\"><b>Publisher:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td width=\"130\">\n\t\t<select name=\"publisherSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"publisherName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"20\" valign=\"middle\"><input type=\"checkbox\" name=\"showPlace\" value=\"1\"></td>"
			. "\n\t<td width=\"40\"><b>Place:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td width=\"130\">\n\t\t<select name=\"placeSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"placeName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"20\" valign=\"middle\"><input type=\"checkbox\" name=\"showCallNumber\" value=\"1\" checked></td>"
			. "\n\t<td width=\"40\"><b>Signature:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td width=\"130\">\n\t\t<select name=\"callNumberSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"callNumberName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"20\" valign=\"middle\"><input type=\"checkbox\" name=\"showKeywords\" value=\"1\"></td>"
			. "\n\t<td width=\"40\"><b>Keywords:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td width=\"130\">\n\t\t<select name=\"keywordsSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"keywordsName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"20\" valign=\"middle\"><input type=\"checkbox\" name=\"showNotes\" value=\"1\"></td>"
			. "\n\t<td width=\"40\"><b>Notes:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td width=\"130\">\n\t\t<select name=\"notesSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"notesName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\"><b>Display Options:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showLinks\" value=\"1\" checked>&nbsp;&nbsp;&nbsp;Display Links</td>"
			. "\n\t<td valign=\"middle\">Show&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"showRows\" value=\"" . $showRows . "\" size=\"4\" title=\"" .  $loc["DescriptionShowRecordsPerPage"] . "\">&nbsp;&nbsp;&nbsp;records per page"
			. "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"submit\" value=\"Search\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>1st&nbsp;sort&nbsp;by:</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"sortSelector1\">\n\t\t\t<option selected>author</option>\n\t\t\t<option>title</option>\n\t\t\t<option>year</option>\n\t\t\t<option>editor</option>\n\t\t\t<option>series_title</option>\n\t\t\t<option>series_volume</option>\n\t\t\t<option>pages</option>\n\t\t\t<option>publisher</option>\n\t\t\t<option>place</option>\n\t\t\t<option>call_number</option>\n\t\t\t<option>keywords</option>\n\t\t\t<option>notes</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>\n\t\t<input type=\"radio\" name=\"sortRadio1\" value=\"0\" checked>&nbsp;&nbsp;&nbsp;ascending&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
			. "\n\t\t<input type=\"radio\" name=\"sortRadio1\" value=\"1\">&nbsp;&nbsp;&nbsp;descending\n\t</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>2nd&nbsp;sort&nbsp;by:</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"sortSelector2\">\n\t\t\t<option>author</option>\n\t\t\t<option>title</option>\n\t\t\t<option selected>year</option>\n\t\t\t<option>editor</option>\n\t\t\t<option>series_title</option>\n\t\t\t<option>series_volume</option>\n\t\t\t<option>pages</option>\n\t\t\t<option>publisher</option>\n\t\t\t<option>place</option>\n\t\t\t<option>call_number</option>\n\t\t\t<option>keywords</option>\n\t\t\t<option>notes</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>\n\t\t<input type=\"radio\" name=\"sortRadio2\" value=\"0\">&nbsp;&nbsp;&nbsp;ascending&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
			. "\n\t\t<input type=\"radio\" name=\"sortRadio2\" value=\"1\" checked>&nbsp;&nbsp;&nbsp;descending\n\t</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>3rd&nbsp;sort&nbsp;by:</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"sortSelector3\">\n\t\t\t<option>author</option>\n\t\t\t<option selected>title</option>\n\t\t\t<option>year</option>\n\t\t\t<option>editor</option>\n\t\t\t<option>series_title</option>\n\t\t\t<option>series_volume</option>\n\t\t\t<option>pages</option>\n\t\t\t<option>publisher</option>\n\t\t\t<option>place</option>\n\t\t\t<option>call_number</option>\n\t\t\t<option>keywords</option>\n\t\t\t<option>notes</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>\n\t\t<input type=\"radio\" name=\"sortRadio3\" value=\"0\" checked>&nbsp;&nbsp;&nbsp;ascending&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
			. "\n\t\t<input type=\"radio\" name=\"sortRadio3\" value=\"1\">&nbsp;&nbsp;&nbsp;descending\n\t</td>"
			. "\n</tr>"
			. "\n</table>"
			. "\n</form>";

	// (5) Close the database connection:
	disconnectFromMySQLDatabase(); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
	showPageFooter($HeaderString);

	displayHTMLfoot();

	// --------------------------------------------------------------------
?>
