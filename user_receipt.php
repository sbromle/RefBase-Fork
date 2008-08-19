<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./user_receipt.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    16-Apr-02, 10:54
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This script shows the user a receipt for their user UPDATE or INSERT.
	// It carries out no database actions and can be bookmarked.
	// The user must be logged in to view it.
	// TODO: I18n, better separate HTML code from PHP code


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

	// Extract the 'userID' parameter from the request:
	if (isset($_REQUEST['userID']) AND ereg("^-?[0-9]+$", $_REQUEST['userID']))
		$userID = $_REQUEST['userID'];
	else
		$userID = ""; // we do it for clarity reasons here (and in order to prevent any 'Undefined variable...' messages)

	// Check if the user is logged in
	if (!isset($_SESSION['loginEmail']) && ($userID != -1))
	// Note: 'user_validation.php' uses the non-existing user ID '-1' as trigger to show the email notification receipt page (instead of the standard receipt page)
	{
		// save an error message:
		$HeaderString = "<b><span class=\"warning\">You must login to view your user account details and options!</span></b>";

		// save the URL of the currently displayed page:
		$referer = $_SERVER['HTTP_REFERER'];

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		saveSessionVariable("referer", $referer);

		header("Location: user_login.php");
		exit;
	}

	// Check the correct parameters have been passed
	if ($userID == "")
	{
		// save an error message:
		$HeaderString = "<b><span class=\"warning\">Incorrect parameters to script 'user_receipt.php'!</span></b>";

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

		// Redirect the browser back to the calling page
		header("Location: " . $referer); // variable '$referer' is globally defined in function 'start_session()' in 'include.inc.php'
		exit;
	}

	// Check if the logged-in user is allowed to modify his account details and options
	if (isset($_SESSION['loginEmail']) AND preg_match("/^\d+$/", $userID) AND isset($_SESSION['user_permissions']) AND !ereg("allow_modify_options", $_SESSION['user_permissions'])) // if a user is logged in but the 'user_permissions' session variable does NOT contain 'allow_modify_options'...
	{
		// save an error message:
		$HeaderString = "<b><span class=\"warning\">You have no permission to modify your user account details and options!</span></b>";

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

		// Redirect the browser back to the calling page
		header("Location: " . $referer);
		exit;
	}

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// For regular users, validate that the correct userID has been passed to the script:
	if (isset($_SESSION['loginEmail']) && ($loginEmail != $adminLoginEmail))
		// check this user matches the userID (viewing user account details is only allowed to the admin)
		if ($userID != getUserID($loginEmail))
		{
			// otherwise save an error message:
			$HeaderString = "<b><span class=\"warning\">You can only view your own user receipt!<span></b>";

			// Write back session variables:
			saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

			$userID = getUserID($loginEmail); // and re-establish the user's correct user_id
		}

	// Extract the type of action requested by the user, either 'delete' or ''.
	// ('' or anything else will be treated equal to 'edit').
	// We actually extract the variable 'userAction' only if the admin is logged in
	// (since only the admin will be allowed to delete a user):
	if (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail)) // ('$adminLoginEmail' is specified in 'ini.inc.php')
	{
		if (isset($_REQUEST['userAction']))
			$userAction = $_REQUEST['userAction'];
		else
			$userAction = ""; // we do it for clarity reasons here (and in order to prevent any 'Undefined variable...' messages)

		if ($userAction == "Delete")
		{
			if ($userID == getUserID($loginEmail)) // if the admin userID was passed to the script
			{
				// save an error message:
				$HeaderString = "<b><span class=\"warning\">You cannot delete your own user data!<span></b>";

				// Write back session variables:
				saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

				$userAction = "Edit"; // and re-set the user action to 'edit'
			}
		}
		else
			$userAction = "Edit"; // everything that isn't a 'delete' action will be an 'edit' action
	}
	else // otherwise we simply assume an 'edit' action, no matter what was passed to the script (thus, no regular user will be able to delete a user)
		$userAction = "Edit";

	// Extract the view type requested by the user (either 'Mobile', 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";

	// --------------------------------------------------------------------

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// Show the user confirmation:
	if ($userID == -1) // 'userID=-1' is sent by 'user_validation.php' to indicate a NEW user who has successfully submitted 'user_details.php'
		showEmailConfirmation($userID);
	else
		showUserData($userID, $userAction, $connection);

	// ----------------------------------------------

	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase(); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// Show a new user a confirmation screen, confirming that the submitted user data have been correctly received:
	function showEmailConfirmation($userID)
	{
		global $HeaderString;
		global $viewType;
		global $loginWelcomeMsg;
		global $loginStatus;
		global $loginLinks;
		global $loginEmail;
		global $adminLoginEmail;
		global $officialDatabaseName;

		// Build the correct header message:
		if (!isset($_SESSION['HeaderString']))
			$HeaderString = "Submission confirmation:"; // provide the default message
		else
		{
			$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

			// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
			deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
		}

		// Call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
		displayHTMLhead(encodeHTML($officialDatabaseName) . " -- User Receipt", "noindex,nofollow", "Receipt page confirming correct submission of new user details to the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
		showPageHeader($HeaderString);

		$confirmationText = "Thanks for your interest in the " . encodeHTML($officialDatabaseName) . "!"
		                  . "<br><br>The data you provided have been sent to our database admin."
		                  . "<br>We'll process your request and mail back to you as soon as we can!"
		                  . "<br><br>[Back to <a href=\"index.php\">" . encodeHTML($officialDatabaseName) . " Home</a>]";

		// Start a table:
		echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table displays user submission feedback\">";

		echo "\n<tr>\n\t<td>" . $confirmationText . "</td>\n</tr>";

		echo "\n</table>";
	}

	// --------------------------------------------------------------------

	// Show the user an UPDATE receipt:
	// (if the admin is logged in, this function will also provide a 'new user INSERT' receipt)
	function showUserData($userID, $userAction, $connection)
	{
		global $HeaderString;
		global $viewType;
		global $loginWelcomeMsg;
		global $loginStatus;
		global $loginLinks;
		global $loginEmail;
		global $adminLoginEmail;
		global $officialDatabaseName;
		global $defaultLanguage;
		global $tableUsers; // defined in 'db.inc.php'

		global $loc; // '$loc' is made globally available in 'core.php'

		// CONSTRUCT SQL QUERY:
		$query = "SELECT * FROM $tableUsers WHERE user_id = " . quote_smart($userID);

		// (3) RUN the query on the database through the connection:
		$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		// (4) EXTRACT results (since 'user_id' is the unique primary key for the 'users' table, there will be only one matching row)
		$row = @ mysql_fetch_array($result);

		// Build the correct header message:
		if (!isset($_SESSION['HeaderString'])) // if there's no saved message
			if ($userAction == "Delete") // provide an appropriate header message:
				$HeaderString = "<b><span class=\"warning\">Delete user</span> " . encodeHTML($row["first_name"]) . " " . encodeHTML($row["last_name"]) . " (" . $row["email"] . ")</b>:";
			elseif (empty($userID))
				$HeaderString = "Account details and options for anyone who isn't logged in:";
			else // provide the default message:
				$HeaderString = "Account details and options for <b>" . encodeHTML($row["first_name"]) . " " . encodeHTML($row["last_name"]) . " (" . $row["email"] . ")</b>:";
		else
		{
			$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

			// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
			deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
		}

		// Call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
		displayHTMLhead(encodeHTML($officialDatabaseName) . " -- User Receipt", "noindex,nofollow", "Receipt page confirming correct entry of user details and options for the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
		showPageHeader($HeaderString);

		// Start main table:
		echo "\n<table id=\"accountinfo\" align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table displays user account details and options\">";

			echo "\n<tr>"
			   . "\n\t<td valign=\"top\" width=\"28%\">";

			// Start left sub-table:
			echo "\n\t\t<table id=\"accountdetails\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" summary=\"User account details\">";

			echo "\n\t\t<tr>\n\t\t\t<td align=\"left\"><b>Account Details:</b></td>";

			if (mysql_num_rows($result) == 1) // If there's a user associated with this user ID
			{
				// Add edit/delete button:
				echo "\n\t\t\t<td align=\"left\">";

				// If the admin is logged in, allow the display of a button that will delete the currently shown user:
				if (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail)) // ('$adminLoginEmail' is specified in 'ini.inc.php')
				{
					if ($userAction == "Delete")
						echo "<a href=\"user_removal.php?userID=" . $userID . "\"><img src=\"img/delete.gif\" alt=\"delete\" title=\"delete user\" width=\"11\" height=\"17\" hspace=\"0\" border=\"0\"></a>";
				}

				if ($userAction != "Delete")
					echo "<a href=\"user_details.php?userID=" . $userID . "\"><img src=\"img/edit.gif\" alt=\"edit\" title=\"edit details\" width=\"11\" height=\"17\" hspace=\"0\" border=\"0\"></a>";

				echo "</td>\n\t\t</tr>";

				// Display a password reminder:
				// (but only if a normal user is logged in -OR- the admin is logged in AND the updated user data are his own!)
				if (($loginEmail != $adminLoginEmail) | (($loginEmail == $adminLoginEmail) && ($userID == getUserID($loginEmail))))
					echo "\n\t\t<tr>\n\t\t\t<td colspan=\"2\"><i>Please record your password somewhere safe for future use!</i></td>\n\t\t</tr>";

				// Print title, first name, last name and institutional abbreviation:
				echo "\n\t\t<tr>\n\t\t\t<td colspan=\"2\">\n\t\t\t\t";
				if (!empty($row["title"]))
					echo $row["title"] . ". ";
				echo encodeHTML($row["first_name"]) . " " . encodeHTML($row["last_name"]) . " (" . encodeHTML($row["abbrev_institution"]) . ")"; // Since the first name, last name and abbrev. institution fields are mandatory, we don't need to check if they're empty

				// Print institution name:
				if (!empty($row["institution"]))
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["institution"]);

				// Print corporate institution name:
				if (!empty($row["corporate_institution"]))
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["corporate_institution"]);

				// If any of the address lines contain data, add a spacer row:
				if (!empty($row["address_line_1"]) || !empty($row["address_line_2"]) || !empty($row["address_line_3"]) || !empty($row["zip_code"]) || !empty($row["city"]) || !empty($row["state"]) || !empty($row["country"]))
					echo "\n\t\t\t\t<br>";

				// Print first address line:
				if (!empty($row["address_line_1"]))
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["address_line_1"]);

				// Print second address line:
				if (!empty($row["address_line_2"]))
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["address_line_2"]);

				// Print third address line:
				if (!empty($row["address_line_3"]))
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["address_line_3"]);

				// Print zip code and city:
				if (!empty($row["zip_code"]) && !empty($row["city"])) // both fields are available
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["zip_code"]) . " " . encodeHTML($row["city"]);
				elseif (!empty($row["zip_code"]) && empty($row["city"])) // only 'zip_code' available
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["zip_code"]);
				elseif (empty($row["zip_code"]) && !empty($row["city"])) // only 'city' field available
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["city"]);

				// Print state:
				if (!empty($row["state"]))
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["state"]);

				// Print country:
				if (!empty($row["country"]))
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["country"]);

				// If any of the phone/url/email fields contain data, add a spacer row:
				if (!empty($row["phone"]) || !empty($row["url"]) || !empty($row["email"]))
					echo "\n\t\t\t\t<br>";

				// Print phone number:
				if (!empty($row["phone"]))
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . "Phone: " . encodeHTML($row["phone"]);

				// Print URL:
				if (!empty($row["url"]))
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . "URL: <a href=\"" . $row["url"] . "\">" . $row["url"] . "</a>";

				// Print email:
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . "Email: <a href=\"mailto:" . $row["email"] . "\">" . $row["email"] . "</a>"; // Since the email field is mandatory, we don't need to check if it's empty

				echo "\n\t\t\t</td>\n\t\t</tr>";
			}
			else // no user exists with this user ID
			{
				echo "\n\t\t\t<td align=\"right\"></td>\n\t\t</tr>";
				echo "\n\t\t<tr>\n\t\t\t<td colspan=\"2\">(none)</td>\n\t\t</tr>";
			}

			// Close left sub-table:
			echo "\n\t\t</table>";

			// Close left table cell of main table:
			echo "\n\t</td>";

			if ($userAction != "Delete") // we omit user options and permissions when displaying info for a user pending deletion
			{
				// ------------------------------------------------------------

				// Start middle table cell of main table:
				echo "\n\t<td valign=\"top\">";

				// Start middle sub-table:
				echo "\n\t\t<table id=\"accountopt\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" summary=\"User account options\">";

				echo "\n\t\t<tr>\n\t\t\t<td align=\"left\"><b>Display Options:</b></td>"
				   . "\n\t\t\t<td align=\"right\">";

				if ((mysql_num_rows($result) == 1) OR ($userID == 0)) // If there's a user associated with this user ID (or if we're supposed to display options/permissions for anyone who isn't logged in)
					echo "<a href=\"user_options.php?userID=" . $userID . "\"><img src=\"img/options.gif\" alt=\"options\" title=\"edit options\" width=\"11\" height=\"17\" hspace=\"0\" border=\"0\"></a>";

				echo "</td>\n\t\t</tr>";

				// Show the user's selected interface language:
				echo "\n\t\t<tr valign=\"top\">"
				   . "\n\t\t\t<td>Use language:</td>";

				if (mysql_num_rows($result) == 1) // If there's a user associated with this user ID
					echo "\n\t\t\t<td>\n\t\t\t\t<ul>\n\t\t\t\t\t<li>" . $row["language"] . "</li>\n\t\t\t\t</ul>\n\t\t\t</td>";
				else // no user exists with this user ID
					echo "\n\t\t\t<td>\n\t\t\t\t<ul>\n\t\t\t\t\t<li>" . $defaultLanguage . "</li>\n\t\t\t\t</ul>\n\t\t\t</td>";

				echo "\n\t\t</tr>";

				// get the default number of records per page preferred by the current user:
				$recordsPerPage = getDefaultNumberOfRecords($userID); // function 'getDefaultNumberOfRecords()' is defined in 'include.inc.php'

				// show the user's default number of records per page:
				echo "\n\t\t<tr valign=\"top\">"
				   . "\n\t\t\t<td>Show records per page:</td>"
				   . "\n\t\t\t<td>\n\t\t\t\t<ul>\n\t\t\t\t\t<li>" . $recordsPerPage . "</li>\n\t\t\t\t</ul>\n\t\t\t</td>"
				   . "\n\t\t</tr>";

				if ($loginEmail == $adminLoginEmail) // if the admin is logged in
				{
					$ShowEnabledDescriptor = "Enabled";

					// get all formats/styles/types that are available and were enabled by the admin for the current user:
					$userTypesArray = getEnabledUserFormatsStylesTypes($userID, "type", "", false); // function 'getEnabledUserFormatsStylesTypes()' is defined in 'include.inc.php'

					$citationStylesArray = getEnabledUserFormatsStylesTypes($userID, "style", "", false);

					$citationFormatsArray = getEnabledUserFormatsStylesTypes($userID, "format", "cite", false);

					$exportFormatsArray = getEnabledUserFormatsStylesTypes($userID, "format", "export", false);
				}
				else // if a normal user is logged in
				{
					$ShowEnabledDescriptor = "Show";

					// get all formats/styles/types that were selected by the current user
					// and (if some formats/styles/types were found) save them as semicolon-delimited string to an appropriate session variable:
					$userTypesArray = getVisibleUserFormatsStylesTypes($userID, "type", ""); // function 'getVisibleUserFormatsStylesTypes()' is defined in 'include.inc.php'

					$citationStylesArray = getVisibleUserFormatsStylesTypes($userID, "style", "");

					$citationFormatsArray = getVisibleUserFormatsStylesTypes($userID, "format", "cite");

					$exportFormatsArray = getVisibleUserFormatsStylesTypes($userID, "format", "export");

					// Note: the function 'getVisibleUserFormatsStylesTypes()' will only update the appropriate session variables if
					//       either a normal user is logged in -OR- the admin is logged in AND the updated user data are his own(*);
					//       otherwise, the function will simply return an array containing all matching values
					//       (*) the admin-condition won't apply here, though, since this function gets only called for normal users. This means, that
					//           the admin is currently not able to hide any items from his popup lists via the admin interface (he'll need to hack the MySQL tables)!
				}

				// list types:
				echo "\n\t\t<tr valign=\"top\">"
				   . "\n\t\t\t<td>" . $ShowEnabledDescriptor . " reference types:</td>"
				   . "\n\t\t\t<td>\n\t\t\t\t<ul>\n\t\t\t\t\t<li>";

				if (empty($userTypesArray))
					echo "(none)";
				else
					echo implode("</li>\n\t\t\t\t\t<li>", $userTypesArray);

				echo "</li>\n\t\t\t\t</ul>\n\t\t\t</td>"
				   . "\n\t\t</tr>";

				// list styles:
				echo "\n\t\t<tr valign=\"top\">"
				   . "\n\t\t\t<td>" . $ShowEnabledDescriptor . " citation styles:</td>"
				   . "\n\t\t\t<td>\n\t\t\t\t<ul>\n\t\t\t\t\t<li>";

				if (empty($citationStylesArray))
					echo "(none)";
				else
					echo implode("</li>\n\t\t\t\t\t<li>", $citationStylesArray);

				echo "</li>\n\t\t\t\t</ul>\n\t\t\t</td>"
				   . "\n\t\t</tr>";

				// list cite formats:
				echo "\n\t\t<tr valign=\"top\">"
				   . "\n\t\t\t<td>" . $ShowEnabledDescriptor . " citation formats:</td>"
				   . "\n\t\t\t<td>\n\t\t\t\t<ul>\n\t\t\t\t\t<li>";

				if (empty($citationFormatsArray))
					echo "(none)";
				else
					echo implode("</li>\n\t\t\t\t\t<li>", $citationFormatsArray);

				echo "</li>\n\t\t\t\t</ul>\n\t\t\t</td>"
				   . "\n\t\t</tr>";

				// list export formats:
				echo "\n\t\t<tr valign=\"top\">"
				   . "\n\t\t\t<td>" . $ShowEnabledDescriptor . " export formats:</td>"
				   . "\n\t\t\t<td>\n\t\t\t\t<ul>\n\t\t\t\t\t<li>";

				if (empty($exportFormatsArray))
					echo "(none)";
				else
					echo implode("</li>\n\t\t\t\t\t<li>", $exportFormatsArray);

				echo "</li>\n\t\t\t\t</ul>\n\t\t\t</td>"
				   . "\n\t\t</tr>";

				// get the list of "main fields" preferred by the current user:
				$mainFieldsArray = getMainFields($userID); // function 'getMainFields()' is defined in 'include.inc.php'

				// list all fields that were selected by the current user as "main fields":
				echo "\n\t\t<tr valign=\"top\">"
				   . "\n\t\t\t<td>\"Main fields\" searches:</td>"
				   . "\n\t\t\t<td>\n\t\t\t\t<ul>\n\t\t\t\t\t<li>";

				if (empty($mainFieldsArray))
					echo "(none)";
				else
					echo implode("</li>\n\t\t\t\t\t<li>", $mainFieldsArray);

				echo "</li>\n\t\t\t\t</ul>\n\t\t\t</td>"
				   . "\n\t\t</tr>";

				// Close middle sub-table:
				echo "\n\t\t</table>";

				// Close middle table cell of main table:
				echo "\n\t</td>";

				// ------------------------------------------------------------

				// Start right table cell of main table:
				echo "\n\t<td valign=\"top\">";

				// Start right sub-table:
				echo "\n\t\t<table id=\"accountperm\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" summary=\"User account permissions\">";

				if ($loginEmail == $adminLoginEmail) // if the admin is logged in
				{
					// get all user permissions for the current user:
					$userPermissionsArray = getPermissions($userID, "user", false); // function 'getPermissions()' is defined in 'include.inc.php'

					// map raw field names from table 'user_permissions' with items of the global localization array ('$loc'):
					$localizedUserPermissionsArray = array('allow_add'              => 'UserPermission_AllowAdd',
														   'allow_edit'             => 'UserPermission_AllowEdit',
														   'allow_delete'           => 'UserPermission_AllowDelete',
														   'allow_download'         => 'UserPermission_AllowDownload',
														   'allow_upload'           => 'UserPermission_AllowUpload',
														   'allow_list_view'        => 'UserPermission_AllowListView',
														   'allow_details_view'     => 'UserPermission_AllowDetailsView',
														   'allow_print_view'       => 'UserPermission_AllowPrintView',
														   'allow_browse_view'      => 'UserPermission_AllowBrowseView',
														   'allow_sql_search'       => 'UserPermission_AllowSQLSearch',
														   'allow_user_groups'      => 'UserPermission_AllowUserGroups',
														   'allow_user_queries'     => 'UserPermission_AllowUserQueries',
														   'allow_rss_feeds'        => 'UserPermission_AllowRSSFeeds',
														   'allow_import'           => 'UserPermission_AllowImport',
														   'allow_export'           => 'UserPermission_AllowExport',
														   'allow_cite'             => 'UserPermission_AllowCite',
														   'allow_batch_import'     => 'UserPermission_AllowBatchImport',
														   'allow_batch_export'     => 'UserPermission_AllowBatchExport',
														   'allow_modify_options'   => 'UserPermission_AllowModifyOptions',
														   'allow_edit_call_number' => 'UserPermission_AllowEditCallNumber');

					$enabledUserActionsArray = array(); // initialize array variables
					$disabledUserActionsArray = array();

					// separate enabled permission settings from disabled ones and assign localized permission names:
					foreach($userPermissionsArray as $permissionKey => $permissionValue)
					{
						if ($permissionValue == 'yes')
							$enabledUserActionsArray[] = $loc[$localizedUserPermissionsArray[$permissionKey]]; // append this field's localized permission name to the array of enabled user actions
						else
							$disabledUserActionsArray[] = $loc[$localizedUserPermissionsArray[$permissionKey]]; // append this field's localized permission name to the array of disabled user actions
					}

					if (empty($enabledUserActionsArray))
						$enabledUserActionsArray[] = "(none)";

					if (empty($disabledUserActionsArray))
						$disabledUserActionsArray[] = "(none)";

					echo "\n\t\t<tr>\n\t\t\t<td align=\"left\"><b>User Permissions:</b></td>"
					   . "\n\t\t\t<td align=\"right\">";

					if ((mysql_num_rows($result) == 1) OR ($userID == 0)) // If there's a user associated with this user ID (or if we're supposed to display options/permissions for anyone who isn't logged in)
						echo "<a href=\"user_options.php?userID=" . $userID . "#permissions\"><img src=\"img/options.gif\" alt=\"permissions\" title=\"edit permissions\" width=\"11\" height=\"17\" hspace=\"0\" border=\"0\"></a>";

					echo "</td>\n\t\t</tr>";

					echo "\n\t\t<tr valign=\"top\">"
					   . "\n\t\t\t<td>Enabled features:</td>"
					   . "\n\t\t\t<td>\n\t\t\t\t<ul>\n\t\t\t\t\t<li>" . implode("</li>\n\t\t\t\t\t<li>", $enabledUserActionsArray) . "</li>\n\t\t\t\t</ul>\n\t\t\t</td>"
					   . "\n\t\t</tr>";

					echo "\n\t\t<tr valign=\"top\">"
					   . "\n\t\t\t<td>Disabled features:</td>"
					   . "\n\t\t\t<td>\n\t\t\t\t<ul>\n\t\t\t\t\t<li>" . implode("</li>\n\t\t\t\t\t<li>", $disabledUserActionsArray) . "</li>\n\t\t\t\t</ul>\n\t\t\t</td>"
					   . "\n\t\t</tr>";
				}

				// Close right sub-table:
				echo "\n\t\t</table>";

				// Close right table cell of main table:
				echo "\n\t</td>";
			}

			echo "\n</tr>";

		// Close main table:
		echo "\n</table>";
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
	showPageFooter($HeaderString);

	displayHTMLfoot();

	// --------------------------------------------------------------------
?>
