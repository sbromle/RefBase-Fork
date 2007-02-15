<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./users.php
	// Created:    29-Jun-03, 00:25
	// Modified:   28-Sep-06, 22:03

	// This script shows the admin a list of all user entries available within the 'users' table.
	// User data will be shown in the familiar column view, complete with links to show a user's
	// details and add, edit or delete a user.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/header.inc.php'; // include header
	include 'includes/results_header.inc.php'; // include results header
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

	// Check if the admin is logged in
	if (!(isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail)))
	{
		// save an error message:
		$HeaderString = "<b><span class=\"warning\">You must be logged in as admin to view any user account details!</span></b>";

		// save the URL of the currently displayed page:
		$referer = $_SERVER['HTTP_REFERER'];

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		saveSessionVariable("referer", $referer);

		header("Location: index.php");
		exit;
	}

	// --------------------------------------------------------------------

	// [ Extract form variables sent through POST/GET by use of the '$_REQUEST' variable ]
	// [ !! NOTE !!: for details see <http://www.php.net/release_4_2_1.php> & <http://www.php.net/manual/en/language.variables.predefined.php> ]

	// Extract the form used for searching:
	if (isset($_REQUEST['formType']))
		$formType = $_REQUEST['formType'];
	else
		$formType = "";
	
	// Extract the type of display requested by the user. Normally, this will be one of the following:
	//  - '' => if the 'submit' parameter is empty, this will produce the default columnar output style ('showUsers()' function)
	//   - 'Add', 'Remove', 'Allow' or 'Disallow' => these values will trigger actions that act on the selected users
	if (isset($_REQUEST['submit']))
		$displayType = $_REQUEST['submit'];
	else
		$displayType = "";

	// extract the original value of the '$displayType' variable:
	// (which was included as a hidden form tag within the 'groupSearch' form of a search results page)
	if (isset($_REQUEST['originalDisplayType']))
		$originalDisplayType = $_REQUEST['originalDisplayType'];
	else
		$originalDisplayType = "";

	// For a given display type, extract the view type requested by the user (either 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";

	// Extract other variables from the request:
	if (isset($_REQUEST['sqlQuery']))
		$sqlQuery = $_REQUEST['sqlQuery'];
	else
		$sqlQuery = "";
	if (ereg("%20", $sqlQuery)) // if '$sqlQuery' still contains URL encoded data... ('%20' is the URL encoded form of a space, see note below!)
		$sqlQuery = rawurldecode($sqlQuery); // URL decode SQL query (it was URL encoded before incorporation into hidden tags of the 'groupSearch', 'refineSearch', 'displayOptions' and 'queryResults' forms to avoid any HTML syntax errors)
											// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_REQUEST'!
											//       But, opposed to that, URL encoded data that are included within a form by means of a hidden form tag will *NOT* get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!

	if (isset($_REQUEST['showQuery']))
		$showQuery = $_REQUEST['showQuery'];
	else
		$showQuery = "";

	if (isset($_REQUEST['showLinks']))
		$showLinks = $_REQUEST['showLinks'];
	else
		$showLinks = "";

	// If $showLinks is empty we set it to true (i.e., show the links column by default):
	if (empty($showLinks))
		$showLinks = "1";

	if (isset($_REQUEST['showRows']))
		$showRows = $_REQUEST['showRows'];
	else
		$showRows = 0;

	if (isset($_REQUEST['rowOffset']))
		$rowOffset = $_REQUEST['rowOffset'];
	else
		$rowOffset = "";

	// In order to generalize routines we have to query further variables here:
	if (isset($_REQUEST['citeStyleSelector']))
		$citeStyle = $_REQUEST['citeStyleSelector']; // get the cite style chosen by the user (only occurs in 'extract.php' form  and in query result lists)
	else
		$citeStyle = "";

	if (isset($_REQUEST['oldQuery']))
		$oldQuery = $_REQUEST['oldQuery']; // get the query URL of the formerly displayed results page so that its's available on the subsequent receipt page that follows any add/edit/delete action!
	else
		$oldQuery = "";

	// Extract checkbox variable values from the request:
	if (isset($_REQUEST['marked']))
		$recordSerialsArray = $_REQUEST['marked']; // extract the values of all checked checkboxes (i.e., the serials of all selected records)
	else
		$recordSerialsArray = array();

	// check if the user did mark any checkboxes (and set up variables accordingly)
	if (empty($recordSerialsArray)) // no checkboxes were marked
		$nothingChecked = true;
	else // some checkboxes were marked
		$nothingChecked = false;

	// --------------------------------------------------------------------

	// CONSTRUCT SQL QUERY:

	// --- Embedded sql query: ----------------------
	if ($formType == "sqlSearch") // the admin used a link with an embedded sql query for searching...
	{
		$query = eregi_replace(" FROM $tableUsers",", user_id FROM $tableUsers",$sqlQuery); // add 'user_id' column (which is required in order to obtain unique checkbox names as well as for use in the 'getUserID()' function)
		$query = stripSlashesIfMagicQuotes($query); // function 'stripSlashesIfMagicQuotes()' is defined in 'include.inc.php'
//		$query = str_replace('\"','"',$query); // replace any \" with "
//		$query = str_replace('\\\\','\\',$query);
	}

	// --- 'Search within Results' & 'Display Options' forms within 'users.php': ---------------
	elseif ($formType == "refineSearch" OR $formType == "displayOptions") // the user used the "Search within Results" (or "Display Options") form above the query results list (that was produced by 'users.php')
	{
		list($query, $displayType) = extractFormElementsRefineDisplay($tableUsers, $displayType, $originalDisplayType, $sqlQuery, $showLinks, ""); // function 'extractFormElementsRefineDisplay()' is defined in 'include.inc.php' since it's also used by 'users.php'
	}

	// --- 'Show User Group' form within 'users.php': ---------------------
	elseif ($formType == "groupSearch") // the user used the 'Show User Group' form above the query results list (that was produced by 'users.php')
	{
		$query = extractFormElementsGroup($sqlQuery);
	}

	// --- Query results form within 'users.php': ---------------
	elseif ($formType == "queryResults") // the user clicked one of the buttons under the query results list (that was produced by 'users.php')
	{
		$query = extractFormElementsQueryResults($displayType, $sqlQuery, $recordSerialsArray);
	}

	else // build the default query:
	{
		$query = "SELECT first_name, last_name, abbrev_institution, email, last_login, logins, user_id FROM $tableUsers WHERE user_id RLIKE \".+\" ORDER BY last_login DESC, last_name, first_name";
	}


	// ----------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(""); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// (3) RUN the query on the database through the connection:
	$result = queryMySQLDatabase($query, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

	// ----------------------------------------------

	// (4a) DISPLAY header:
	$query = eregi_replace(", user_id FROM $tableUsers"," FROM $tableUsers",$query); // strip 'user_id' column from SQL query (so that it won't get displayed in query strings)

	$queryURL = rawurlencode($query); // URL encode SQL query

	// First, find out how many rows are available:
	$rowsFound = @ mysql_num_rows($result);
	if ($rowsFound > 0) // If there were rows found ...
		{
			// ... setup variables in order to facilitate "previous" & "next" browsing:
			// a) Set '$rowOffset' to zero if not previously defined, or if a wrong number (<=0) was given
			if (empty($rowOffset) || ($rowOffset <= 0) || ($showRows >= $rowsFound)) // the third condition is only necessary if '$rowOffset' gets embedded within the 'displayOptions' form (see function 'buildDisplayOptionsElements()' in 'include.inc.php')
				$rowOffset = 0;

			// Adjust the '$showRows' value if not previously defined, or if a wrong number (<=0 or float) was given
			if (empty($showRows) || ($showRows <= 0) || !ereg("^[0-9]+$", $showRows))
				$showRows = $defaultNumberOfRecords; // by default, we'll return as many records as defined in variable '$defaultNumberOfRecords' in 'ini.inc.php'

			// NOTE: The current value of '$rowOffset' is embedded as hidden tag within the 'displayOptions' form. By this, the current row offset can be re-applied
			//       after the user pressed the 'Show'/'Hide' button within the 'displayOptions' form. But then, to avoid that browse links don't behave as expected,
			//       we need to adjust the actual value of '$rowOffset' to an exact multiple of '$showRows':
			$offsetRatio = ($rowOffset / $showRows);
			if (!is_integer($offsetRatio)) // check whether the value of the '$offsetRatio' variable is not an integer
			{ // if '$offsetRatio' is a float:
				$offsetCorrectionFactor = floor($offsetRatio); // get it's next lower integer
				if ($offsetCorrectionFactor != 0)
					$rowOffset = ($offsetCorrectionFactor * $showRows); // correct the current row offset to the closest multiple of '$showRows' *below* the current row offset
				else
					$rowOffset = 0;
			}

			// b) The "Previous" page begins at the current offset LESS the number of rows per page
			$previousOffset = $rowOffset - $showRows;

			// c) The "Next" page begins at the current offset PLUS the number of rows per page
			$nextOffset = $rowOffset + $showRows;

			// d) Seek to the current offset
			mysql_data_seek($result, $rowOffset);
		}
	else // set variables to zero in order to prevent 'Undefined variable...' messages when nothing was found ('$rowsFound = 0'):
		{
			$rowOffset = 0;
			$previousOffset = 0;
			$nextOffset = 0;
		}

	// Second, calculate the maximum result number on each page ('$showMaxRow' is required as parameter to the 'displayDetails()' function)
	if (($rowOffset + $showRows) < $rowsFound)
		$showMaxRow = ($rowOffset + $showRows); // maximum result number on each page
	else
		$showMaxRow = $rowsFound; // for the last results page, correct the maximum result number if necessary

	// Third, build the appropriate header string (which is required as parameter to the 'showPageHeader()' function):
	if (!isset($_SESSION['HeaderString'])) // if there's no stored message available provide the default message:
	{
		if ($rowsFound == 1)
			$HeaderString = " user found:";
		else
			$HeaderString = " users found:";

		if ($rowsFound > 0)
			$HeaderString = ($rowOffset + 1) . "&#8211;" . $showMaxRow . " of " . $rowsFound . $HeaderString;
		elseif ($rowsFound == 0)
			$HeaderString = $rowsFound . $HeaderString;
		else
			$HeaderString = $HeaderString; // well, this is actually bad coding but I do it for clearity reasons...
	}
	else
	{
		$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

		// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
		deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	}

	// Now, show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// Then, call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(encodeHTML($officialDatabaseName) . " -- Manage Users", "noindex,nofollow", "Administration page that lists users of the " . encodeHTML($officialDatabaseName) . ", with links for adding, editing or deleting any users", "", true, "", $viewType, array());
	if ($viewType != "Print") // Note: we omit the visible header in print view! ('viewType=Print')
		showPageHeader($HeaderString, "");

	// (4b) DISPLAY results:
	showUsers($result, $rowsFound, $query, $queryURL, $oldQuery, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $citeStyle, $showMaxRow, $viewType, $displayType); // show all users

	// ----------------------------------------------

	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase(""); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------
	
	// Display all users listed within the 'users' table
	function showUsers($result, $rowsFound, $query, $queryURL, $oldQuery, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $citeStyle, $showMaxRow, $viewType, $displayType)
	{
		global $connection;
		global $HeaderString;
		global $loginWelcomeMsg;
		global $loginStatus;
		global $loginLinks;
		global $loginEmail;
		global $adminLoginEmail;

		if ($rowsFound > 0) // If the query has results ...
		{
			// BEGIN RESULTS HEADER --------------------
			// 1) First, initialize some variables that we'll need later on
			// Note: In contrast to 'search.php', we don't hide any columns but the user_id column (see below)
			//       However, in order to maintain a similar code structure to 'search.php' we define $CounterMax here as well & simply set it to 0:
			$CounterMax = "0";

			// count the number of fields
			$fieldsFound = mysql_num_fields($result);
			// hide those last columns that were added by the script and not by the user
			$fieldsToDisplay = $fieldsFound-(1+$CounterMax); // (1+$CounterMax) -> $CounterMax is increased by 1 in order to hide the user_id column (which was added to make the checkbox work)

			// Calculate the number of all visible columns (which is needed as colspan value inside some TD tags)
			if ($showLinks == "1")
				$NoColumns = (1+$fieldsToDisplay+1); // add checkbox & Links column
			else
				$NoColumns = (1+$fieldsToDisplay); // add checkbox column

			// Note: we omit the 'Search Within Results' form in print view! ('viewType=Print')
			if ($viewType != "Print")
			{
				// Build a TABLE with forms containing options to show the user groups, refine the search results or change the displayed columns:

				//    - Build a FORM with a popup containing the user groups:
				$formElementsGroup = buildGroupSearchElements("users.php", $queryURL, $query, $showQuery, $showLinks, $showRows, $displayType); // function 'buildGroupSearchElements()' is defined in 'include.inc.php'

				//    - Build a FORM containing options to refine the search results:
				//      First, specify which colums should be available in the popup menu (column items must be separated by a comma or comma+space!):
				//      Since 'users.php' can be only called by the admin we simply specify all fields within the first variable...
				$refineSearchSelectorElements1 = "first_name, last_name, title, institution, abbrev_institution, corporate_institution, address_line_1, address_line_2, address_line_3, zip_code, city, state, country, phone, email, url, language, keywords, notes, marked, last_login, logins, user_id, user_groups, created_date, created_time, created_by, modified_date, modified_time, modified_by";
				$refineSearchSelectorElements2 = ""; // ... and keep the second one blank (compare with 'search.php')
				$refineSearchSelectorElementSelected = "last_name"; // this column will be selected by default
				//      Call the 'buildRefineSearchElements()' function (defined in 'include.inc.php') which does the actual work:
				$formElementsRefine = buildRefineSearchElements("users.php", $queryURL, $showQuery, $showLinks, $showRows, $refineSearchSelectorElements1, $refineSearchSelectorElements2, $refineSearchSelectorElementSelected, $displayType);

				//    - Build a FORM containing display options (show/hide columns or change the number of records displayed per page):
				//      Again, specify which colums should be available in the popup menu (column items must be separated by a comma or comma+space!):
				$displayOptionsSelectorElements1 = "first_name, last_name, title, institution, abbrev_institution, corporate_institution, address_line_1, address_line_2, address_line_3, zip_code, city, state, country, phone, email, url, language, keywords, notes, marked, last_login, logins, user_id, user_groups, created_date, created_time, created_by, modified_date, modified_time, modified_by";
				$displayOptionsSelectorElements2 = ""; // again we'll keep the second one blank (compare with 'search.php')
				$displayOptionsSelectorElementSelected = "last_name"; // this column will be selected by default
				//      Call the 'buildDisplayOptionsElements()' function (defined in 'include.inc.php') which does the actual work:
				$formElementsDisplayOptions = buildDisplayOptionsElements("users.php", $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $displayOptionsSelectorElements1, $displayOptionsSelectorElements2, $displayOptionsSelectorElementSelected, $fieldsToDisplay, $displayType);

				echo displayResultsHeader("users.php", $formElementsGroup, $formElementsRefine, $formElementsDisplayOptions); // function 'displayResultsHeader()' is defined in 'results_header.inc.php'
			}


			// and insert a divider line (which separates the 'Search Within Results' form from the browse links & results data below):
			if ($viewType != "Print") // Note: we omit the divider line in print view! ('viewType=Print')
				echo "\n<hr align=\"center\" width=\"93%\">";

			// Build a TABLE with links for "previous" & "next" browsing, as well as links to intermediate pages
			// call the 'buildBrowseLinks()' function (defined in 'include.inc.php'):
			$BrowseLinks = buildBrowseLinks("users.php", $query, $oldQuery, $NoColumns, $rowsFound, $showQuery, $showLinks, $showRows, $rowOffset, $previousOffset, $nextOffset, "25", "sqlSearch", "", "", "", "", "", $viewType); // Note: we set the last 3 fields ('$citeOrder', '$orderBy' & $headerMsg') to "" since they aren't (yet) required here
			echo $BrowseLinks;


			// Start a FORM
			echo "\n<form action=\"users.php\" method=\"POST\" name=\"queryResults\">"
					. "\n<input type=\"hidden\" name=\"formType\" value=\"queryResults\">"
					. "\n<input type=\"hidden\" name=\"submit\" value=\"Add\">" // provide a default value for the 'submit' form tag (then, hitting <enter> within the 'ShowRows' text entry field will act as if the user clicked the 'Add' button)
					. "\n<input type=\"hidden\" name=\"showRows\" value=\"$showRows\">" // embed the current values of '$showRows', '$rowOffset' and the current sqlQuery so that they can be re-applied after the user pressed the 'Add' or 'Remove' button within the 'queryResults' form
					. "\n<input type=\"hidden\" name=\"rowOffset\" value=\"$rowOffset\">"
					. "\n<input type=\"hidden\" name=\"sqlQuery\" value=\"$queryURL\">"
					. "\n<input type=\"hidden\" name=\"oldQuery\" value=\"" . rawurlencode($oldQuery) . "\">"; // embed the current value of '$oldQuery' so that it's available later on

			// And start a TABLE
			echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table displays users of this database\">";

			// For the column headers, start another TABLE ROW ...
			echo "\n<tr>";
	
			// ... print a marker ('x') column (which will hold the checkboxes within the results part)
			if ($viewType != "Print") // Note: we omit the marker column in print view! ('viewType=Print')
				echo "\n\t<th align=\"left\" valign=\"top\">&nbsp;</th>";
	
			// for each of the attributes in the result set...
			for ($i=0; $i<$fieldsToDisplay; $i++)
			{
				// ...print out each of the attribute names
				// in that row as a separate TH (Table Header)...
				$HTMLbeforeLink = "\n\t<th align=\"left\" valign=\"top\">"; // start the table header tag
				$HTMLafterLink = "</th>"; // close the table header tag
				// call the 'buildFieldNameLinks()' function (defined in 'include.inc.php'), which will return a properly formatted table header tag holding the current field's name
				// as well as the URL encoded query with the appropriate ORDER clause:
				$tableHeaderLink = buildFieldNameLinks("users.php", $query, $oldQuery, "", $result, $i, $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, "sqlSearch", "", "", "", $viewType);
				echo $tableHeaderLink; // print the attribute name as link
			 }
	
			if ($showLinks == "1")
				{
					$newORDER = ("ORDER BY user_id"); // Build the appropriate ORDER BY clause to facilitate sorting by Links column
	
					$HTMLbeforeLink = "\n\t<th align=\"left\" valign=\"top\">"; // start the table header tag
					$HTMLafterLink = "</th>"; // close the table header tag
					// call the 'buildFieldNameLinks()' function (defined in 'include.inc.php'), which will return a properly formatted table header tag holding the current field's name
					// as well as the URL encoded query with the appropriate ORDER clause:
					$tableHeaderLink = buildFieldNameLinks("users.php", $query, $oldQuery, $newORDER, $result, $i, $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, "sqlSearch", "", "Links", "user_id", $viewType);
					echo $tableHeaderLink; // print the attribute name as link
				}
	
			// Finish the row
			echo "\n</tr>";
			// END RESULTS HEADER ----------------------
			
			// BEGIN RESULTS DATA COLUMNS --------------
			for ($rowCounter=0; (($rowCounter < $showRows) && ($row = @ mysql_fetch_array($result))); $rowCounter++)
			{
				// ... start a TABLE ROW ...
				echo "\n<tr>";
	
				// ... print a column with a checkbox
				if ($viewType != "Print") // Note: we omit the marker column in print view! ('viewType=Print')
					echo "\n\t<td align=\"left\" valign=\"top\" width=\"10\"><input type=\"checkbox\" name=\"marked[]\" value=\"" . $row["user_id"] . "\"></td>";
	
				// ... and print out each of the attributes
				// in that row as a separate TD (Table Data)
				for ($i=0; $i<$fieldsToDisplay; $i++)
				{
					// the following two lines will fetch the current attribute name:
					$info = mysql_fetch_field($result, $i); // get the meta-data for the attribute
					$orig_fieldname = $info->name; // get the attribute name

					if (ereg("^email$", $orig_fieldname))
						echo "\n\t<td valign=\"top\"><a href=\"mailto:" . $row["email"] . "\">" . $row["email"] . "</a></td>";
					elseif (ereg("^url$", $orig_fieldname) AND !empty($row["url"]))
						echo "\n\t<td valign=\"top\"><a href=\"" . $row["url"] . "\">" . $row["url"] . "</a></td>";
					else
						echo "\n\t<td valign=\"top\">" . encodeHTML($row[$i]) . "</td>";
				}

				// embed appropriate links (if available):
				if ($showLinks == "1")
				{
					echo "\n\t<td valign=\"top\">";
	
					echo "\n\t\t<a href=\"user_receipt.php?userID=" . $row["user_id"]
						. "\"><img src=\"img/details.gif\" alt=\"details\" title=\"show details and options\" width=\"9\" height=\"17\" hspace=\"0\" border=\"0\"></a>&nbsp;&nbsp;";
	
					echo "\n\t\t<a href=\"user_details.php?userID=" . $row["user_id"]
						. "\"><img src=\"img/edit.gif\" alt=\"edit\" title=\"edit details\" width=\"11\" height=\"17\" hspace=\"0\" border=\"0\"></a>&nbsp;&nbsp;";
	
					echo "\n\t\t<a href=\"user_options.php?userID=" . $row["user_id"]
						. "\"><img src=\"img/options.gif\" alt=\"options\" title=\"edit options\" width=\"11\" height=\"17\" hspace=\"0\" border=\"0\"></a>&nbsp;&nbsp;";
	
					$adminUserID = getUserID($adminLoginEmail); // ...get the admin's 'user_id' using his/her 'adminLoginEmail' (function 'getUserID()' is defined in 'include.inc.php')
					if ($row["user_id"] != $adminUserID) // we only provide a delete link if this user isn't the admin:
						echo "\n\t\t<a href=\"user_receipt.php?userID=" . $row["user_id"] . "&amp;userAction=Delete"
							. "\"><img src=\"img/delete.gif\" alt=\"delete\" title=\"delete user\" width=\"11\" height=\"17\" hspace=\"0\" border=\"0\"></a>";
	
					echo "\n\t</td>";
				}
				// Finish the row
				echo "\n</tr>";
			}
			// Then, finish the table
			echo "\n</table>";
			// END RESULTS DATA COLUMNS ----------------

			// BEGIN RESULTS FOOTER --------------------
			// Note: we omit the results footer in print view! ('viewType=Print')
			if ($viewType != "Print")
			{
				// Again, insert the (already constructed) BROWSE LINKS
				// (i.e., a TABLE with links for "previous" & "next" browsing, as well as links to intermediate pages)
				echo $BrowseLinks;

				// Insert a divider line (which separates the results data from the results footer):
				echo "\n<hr align=\"center\" width=\"93%\">";

				// Build a TABLE containing rows with buttons which will trigger actions that act on the selected users
				// Call the 'buildResultsFooter()' function (which does the actual work):
				$userResultsFooter = buildUserResultsFooter($NoColumns);
				echo $userResultsFooter;
			}
			// END RESULTS FOOTER ----------------------

			// Finally, finish the form
			echo "\n</form>";
		}
		else
		{
			// Report that nothing was found:
			echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table displays users of this database\">"
					. "\n<tr>"
					. "\n\t<td valign=\"top\">Sorry, but your query didn't produce any results!&nbsp;&nbsp;<a href=\"javascript:history.back()\">Go Back</a></td>"
					. "\n</tr>"
					. "\n</table>";
		}// end if $rowsFound body
	}

	// --------------------------------------------------------------------

	//	BUILD USER RESULTS FOOTER
	// (i.e., build a TABLE containing a row with buttons for assigning selected users to a particular group)
	function buildUserResultsFooter($NoColumns)
	{
		// Start a TABLE
		$userResultsFooterRow = "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"90%\" summary=\"This table holds the results footer which offers a form to assign selected users to a group and set their permissions\">";

		$userResultsFooterRow .= "\n<tr>"

								. "\n\t<td align=\"left\" valign=\"top\">"
								. "Selected Users:"
								. "</td>";

		// Admin user groups functionality:
		if (!isset($_SESSION['adminUserGroups']))
		{
			$groupSearchDisabled = " disabled"; // disable the (part of the) 'Add to/Remove from group' form elements if the session variable holding the admin's user groups isn't available
			$groupSearchPopupMenuChecked = "";
			$groupSearchTextInputChecked = " checked";
			$groupSearchSelectorTitle = "(to setup a new group with all selected users, enter a group name to the right, then click the 'Add' button)";
			$groupSearchTextInputTitle = "to setup a new group with the selected users, specify the name of the group here, then click the 'Add' button";
		}
		else
		{
			$groupSearchDisabled = "";
			$groupSearchPopupMenuChecked = " checked";
			$groupSearchTextInputChecked = "";
			$groupSearchSelectorTitle = "choose the group to which the selected users shall belong (or from which they shall be removed)";
			$groupSearchTextInputTitle = "to setup a new group with the selected users, click the radio button to the left &amp; specify the name of the group here, then click the 'Add' button";
		}

		$userResultsFooterRow .= "\n\t<td align=\"left\" valign=\"top\" colspan=\"" . ($NoColumns - 1) . "\">"
								. "\n\t\t<input type=\"submit\" name=\"submit\" value=\"Add\" title=\"add all selected users to the specified group\">&nbsp;"
								. "\n\t\t<input type=\"submit\" name=\"submit\" value=\"Remove\" title=\"remove all selected users from the specified group\"$groupSearchDisabled>&nbsp;&nbsp;&nbsp;group:&nbsp;&nbsp;"
								. "\n\t\t<input type=\"radio\" name=\"userGroupActionRadio\" value=\"1\" title=\"click here if you want to add (remove) the selected users to (from) an existing group; then, choose the group name from the popup menu to the right\"$groupSearchDisabled$groupSearchPopupMenuChecked>"
								. "\n\t\t<select name=\"userGroupSelector\" title=\"$groupSearchSelectorTitle\"$groupSearchDisabled>";

		if (!isset($_SESSION['adminUserGroups']))
		{
			$userResultsFooterRow .= "\n\t\t\t<option>(no groups available)</option>";
		}
		else
		{
			$optionTags = buildSelectMenuOptions($_SESSION['adminUserGroups'], " *; *", "\t\t\t", false); // build properly formatted <option> tag elements from the items listed in the 'adminUserGroups' session variable
			$userResultsFooterRow .= $optionTags;
		}

		$userResultsFooterRow .= "\n\t\t</select>&nbsp;&nbsp;&nbsp;"
								. "\n\t\t<input type=\"radio\" name=\"userGroupActionRadio\" value=\"0\" title=\"click here if you want to setup a new group; then, enter the group name in the text box to the right\"$groupSearchTextInputChecked>"
								. "\n\t\t<input type=\"text\" name=\"userGroupName\" value=\"\" size=\"8\" title=\"$groupSearchTextInputTitle\">"
								. "\n\t</td>"
	
								. "\n</tr>";

		// Set user permissions functionality:
		$userResultsFooterRow .= "\n<tr>"

								. "\n\t<td align=\"left\" valign=\"top\">&nbsp;</td>"

								. "\n\t<td align=\"left\" valign=\"top\" colspan=\"" . ($NoColumns - 1) . "\">"
								. "\n\t\t<input type=\"submit\" name=\"submit\" value=\"Allow\" title=\"allow all selected users to use the specified feature\">&nbsp;"
								. "\n\t\t<input type=\"submit\" name=\"submit\" value=\"Disallow\" title=\"do not allow the selected users to use the specified feature\">&nbsp;&nbsp;&nbsp;feature:&nbsp;&nbsp;"
								. "\n\t\t<select name=\"userPermissionSelector\" title=\"select the permission setting you'd like to change for the selected users\">";

		$userPermissionsArray = array('allow_add'                => 'Add records',
										'allow_edit'             => 'Edit records',
										'allow_delete'           => 'Delete records',
										'allow_download'         => 'File download',
										'allow_upload'           => 'File upload',
										'allow_details_view'     => 'Details view',
										'allow_print_view'       => 'Print view',
										'allow_sql_search'       => 'SQL search',
										'allow_user_groups'      => 'User groups',
										'allow_user_queries'     => 'User queries',
										'allow_rss_feeds'        => 'RSS feeds',
										'allow_import'           => 'Import',
										'allow_export'           => 'Export',
										'allow_cite'             => 'Cite',
										'allow_batch_import'     => 'Batch import',
										'allow_batch_export'     => 'Batch export',
										'allow_modify_options'   => 'Modify options');
//										'allow_edit_call_number' => 'Edit call number');

		$optionTags = buildSelectMenuOptions($userPermissionsArray, "", "\t\t\t", true); // build properly formatted <option> tag elements from the items listed in the '$userPermissionsArray' variable
		$userResultsFooterRow .= $optionTags;

		$userResultsFooterRow .= "\n\t\t</select>"
								. "\n\t</td>"
	
								. "\n</tr>";

		// Finish the table:
		$userResultsFooterRow .= "\n</table>";

		return $userResultsFooterRow;
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the "Show User Group" form above the query results list (that was produced by 'users.php'):
	function extractFormElementsGroup($sqlQuery)
	{
		global $tableUsers; // defined in 'db.inc.php'

		if (!empty($sqlQuery)) // if there's a previous SQL query available
		{
			$query = preg_replace("/(SELECT .+?) FROM $tableUsers.+/i", "\\1", $sqlQuery); // use the custom set of colums chosen by the user
			$queryOrderBy = preg_replace("/.+( ORDER BY .+?)(?=LIMIT.*|GROUP BY.*|HAVING.*|PROCEDURE.*|FOR UPDATE.*|LOCK IN.*|$)/i", "\\1", $sqlQuery); // use the custom ORDER BY clause chosen by the user
		}
		else
		{
			$query = "SELECT author, title, year, publication, volume, pages, user_groups"; // use the default SELECT statement
			$queryOrderBy = " ORDER BY author, year DESC, publication"; // add the default ORDER BY clause
		}

		$groupSearchSelector = $_POST['groupSearchSelector']; // extract the user group chosen by the user

		$query .= ", user_id"; // add 'user_id' column (although it won't be visible the 'user_id' column gets included in every search query)
								// (which is required in order to obtain unique checkbox names as well as for use in the 'getUserID()' function)

		$query .= " FROM $tableUsers"; // add FROM clause

		$query .= " WHERE user_groups RLIKE " . quote_smart("(^|.*;) *" . $groupSearchSelector . " *(;.*|$)"); // add WHERE clause

		$query .= $queryOrderBy; // add ORDER BY clause


		return $query;
	}

	// --------------------------------------------------------------------

	// Build the database query from records selected by the user within the query results list (which, in turn, was returned by 'users.php'):
	function extractFormElementsQueryResults($displayType, $sqlQuery, $recordSerialsArray)
	{
		global $tableUsers; // defined in 'db.inc.php'

		$userGroupActionRadio = $_POST['userGroupActionRadio']; // extract user option whether we're supposed to process an existing group name or any custom/new group name that was specified by the user

		// Extract the chosen user group from the request:
		// first, we need to check whether the user did choose an existing group name from the popup menu
		// -OR- if he/she did enter a custom group name in the text entry field:
		if ($userGroupActionRadio == "1") // if the user checked the radio button next to the group popup menu ('userGroupSelector') [this is the default]
		{
			if (isset($_POST['userGroupSelector']))
				$userGroup = $_POST['userGroupSelector']; // extract the value of the 'userGroupSelector' popup menu
			else
				$userGroup = "";
		}
		else // $userGroupActionRadio == "0" // if the user checked the radio button next to the group text entry field ('userGroupName')
		{
			if (isset($_POST['userGroupName']))
				$userGroup = $_POST['userGroupName']; // extract the value of the 'userGroupName' text entry field
			else
				$userGroup = "";
		}

		// extract the specified permission setting:
		if (isset($_POST['userPermissionSelector']))
			$userPermission = $_POST['userPermissionSelector']; // extract the value of the 'userPermissionSelector' popup menu
		else
			$userPermission = "";


		// join array elements:
		if (!empty($recordSerialsArray)) // the user did check some checkboxes
			$recordSerialsString = implode("|", $recordSerialsArray); // separate record serials by "|" in order to facilitate regex querying...
		else // the user didn't check any checkboxes
			$recordSerialsString = "0"; // we use '0' which definitely doesn't exist as serial, resulting in a "nothing found" feedback

		if (!empty($recordSerialsArray))
		{
			if (ereg("^(Add|Remove)$", $displayType)) // (hitting <enter> within the 'userGroupName' text entry field will act as if the user clicked the 'Add' button)
			{
				modifyUserGroups($tableUsers, $displayType, $recordSerialsArray, $recordSerialsString, "", $userGroup, $userGroupActionRadio); // add (remove) selected records to (from) the specified user group (function 'modifyUserGroups()' is defined in 'include.inc.php')
			}
			elseif (ereg("^(Allow|Disallow)$", $displayType))
			{
				if ($displayType == "Allow")
					$userPermissionsArray = array("$userPermission" => "yes");
				else // ($displayType == "Disallow")
					$userPermissionsArray = array("$userPermission" => "no");
	
				// Update the specified user permission for the current user:
				updateUserPermissions($recordSerialsString, $userPermissionsArray);
	
				// save an informative message:
				$HeaderString = "User permission <code>$userPermission</code> was updated successfully!";
			
				// Write back session variables:
				saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
			}
		}


		// re-apply the current sqlQuery:
		$query = eregi_replace(" FROM $tableUsers",", user_id FROM $tableUsers",$sqlQuery); // add 'user_id' column (which is required in order to obtain unique checkbox names)

		return $query;
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
	if ($viewType != "Print") // Note: we omit the visible footer in print view! ('viewType=Print')
		showPageFooter($HeaderString, "");

	displayHTMLfoot();

	// --------------------------------------------------------------------
?>
