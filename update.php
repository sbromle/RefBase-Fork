<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./update.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    01-Mar-05, 20:47
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This file will update any refbase MySQL database installation from v0.8.0 or greater to v0.9.1.
	// (Note that this script currently doesn't offer any conversion from 'latin1' to 'utf8')
	// CAUTION: YOU MUST REMOVE THIS SCRIPT FROM YOUR WEB DIRECTORY AFTER THE UPDATE!!


	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/header.inc.php'; // include header
	include 'includes/footer.inc.php'; // include footer
	include 'includes/include.inc.php'; // include common functions
	include 'includes/install.inc.php'; // include install/update functions
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

	// This specifies the name of the database that handles the MySQL user access privileges.
	// Unless you've fiddled with it, you shouldn't change the default value ('mysql'):
	$adminDatabaseName = 'mysql';

	// Extract any parameters passed to the script:
	if (isset($_REQUEST['adminUserName']))
		$adminUserName = $_REQUEST['adminUserName'];
	else
		$adminUserName = "";

	if (isset($_REQUEST['adminPassword']))
		$adminPassword = $_REQUEST['adminPassword'];
	else
		$adminPassword = "";

	// --------------------------------------------------------------------

	// Check the correct parameters have been passed:
	if (empty($adminUserName) AND empty($adminPassword))
	{
		// if 'update.php' was called without any valid parameters:
		// Display an update form:

		if (isset($_SESSION['errors']))
		{
			$errors = $_SESSION['errors'];

			// Note: though we clear the session variable, the current error message is still available to this script via '$errors':
			deleteSessionVariable("errors"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
		}
		else
			$errors = array(); // initialize the '$errors' variable in order to prevent 'Undefined variable...' messages

		if (isset($_SESSION['formVars']))
		{
			$formVars = $_SESSION['formVars'];

			// Note: though we clear the session variable, the current form variables are still available to this script via '$formVars':
			deleteSessionVariable("formVars");
		}
		else
		{
			// Reset the '$formVars' variable (since we're providing the default values):
			$formVars = array();

			// provide the default values:
			$formVars["adminUserName"] = "root";
			$formVars["adminPassword"] = "";
		}

		// If there's no stored message available:
		if (!isset($_SESSION['HeaderString']))
		{
			if (empty($errors)) // provide the default message:
				$HeaderString = "To update refbase v0.8.0 or greater please fill out the form below and click the <em>Update</em> button:";
			else // -> there were errors when validating the fields
				$HeaderString = "<b><span class=\"warning\">There were validation errors regarding the details you entered. Please check the comments above the respective fields:</span></b>";
		}
		else
		{
			$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

			// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
			deleteSessionVariable("HeaderString");
		}

		// Extract the view type requested by the user (either 'Mobile', 'Print', 'Web' or ''):
		// ('' will produce the default 'Web' output style)
		if (isset($_REQUEST['viewType']))
			$viewType = $_REQUEST['viewType'];
		else
			$viewType = "";

		// Show the login status:
		showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

		// DISPLAY header:
		// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
		displayHTMLhead(encodeHTML($officialDatabaseName) . " -- Update", "index,follow", "Update form for the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
		showPageHeader($HeaderString);

		// Start <form> and <table> holding the form elements:
?>

<form action="update.php" method="POST">
<input type="hidden" name="formType" value="update">
<input type="hidden" name="submit" value="Update"><?php // provide a default value for the 'submit' form tag. Otherwise, some browsers may not recognize the correct output format when a user hits <enter> within a form field (instead of clicking the "Update" button) ?>

<table align="center" border="0" cellpadding="0" cellspacing="12" width="95%" summary="This table holds the update form">
	<tr>
		<td colspan="3"><h3>refbase Update</h3></td>
	</tr>
	<tr>
		<td width="190" valign="top"><b>Important Notes:</b></td>
		<td valign="top" colspan="2">
			It's important that you make a <span class="warning">backup</span> copy of your original files <em>db.inc.php</em> and <em>ini.inc.php</em> (which are located within the <em>initialize</em> subdirectory of your refbase base directory). We also strongly recommend to <a href="http://backup.refbase.net/" target="top" title="Instructions how to backup your refbase MySQL database">backup</a> your existing refbase MySQL database before proceeding.
			<br>
			<br>
			Before executing this script, you <span class="warning">must edit</span> the updated include file <span class="warning"><em>initialize/db.inc.php</em></span> in a text editor and re-enter the values from your old <em>db.inc.php</em> file for the variables <em>$databaseName</em>, <em>$username</em> and <em>$password</em>. Then, proceed with this form:
		</td>
	</tr>
	<tr>
		<td valign="top"><b>MySQL Admin User:</b></td>
		<td valign="top"><?php echo fieldError("adminUserName", $errors); ?>

			<input type="text" name="adminUserName" value="<?php echo $formVars["adminUserName"]; ?>" size="30">
		</td>
		<td valign="top">Give the name of an administrative user that has full access to your MySQL database. Often, this is the <em>root</em> user.</td>
	</tr>
	<tr>
		<td valign="top"><b>MySQL Admin Password:</b></td>
		<td valign="top"><?php
	// the form won't remember the password, so we'll ask the user to re-type it...
	if (!empty($errors) AND !isset($errors["adminPassword"])) // ...if there were some validation errors but not with the password field
		echo "\n\t\t\t<b>Please type your password again:</b>\n\t\t\t<br>";
	else
		echo fieldError("adminPassword", $errors);
?>

			<input type="password" name="adminPassword" size="30">
		</td>
		<td valign="top">Please enter the password for the administrative user you've specified above.</td>
	</tr>
	<tr>
		<td valign="top">&nbsp;</td>
		<td valign="top" align="right">
			<input type="submit" name="submit" value="Update">
		</td>
		<td valign="top">&nbsp;</td>
	</tr>
</table>
</form><?php

		// --------------------------------------------------------------------

		// DISPLAY THE HTML FOOTER:
		// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
		showPageFooter($HeaderString);

		displayHTMLfoot();

		// --------------------------------------------------------------------

	}
	else // some parameters have been passed, so let's validate the fields:
	{

		// --------------------------------------------------------------------

		// Clear any errors that might have been found previously:
		$errors = array();

		// Write the (POST) form variables into an array:
		foreach($_POST as $varname => $value)
			$formVars[$varname] = $value;


		// Validate the 'adminUserName' field:
		if (empty($formVars["adminUserName"]))
			// The 'adminUserName' field cannot be a null string
			$errors["adminUserName"] = "This field cannot be blank:";


		// Validate the 'adminPassword' field:
		if (empty($formVars["adminPassword"]))
			// The 'adminPassword' field cannot be a null string
			$errors["adminPassword"] = "This field cannot be blank:";

		// --------------------------------------------------------------------

		// Now the script has finished the validation, check if there were any errors:
		if (count($errors) > 0)
		{
			// Write back session variables:
			saveSessionVariable("errors", $errors); // function 'saveSessionVariable()' is defined in 'include.inc.php'
			saveSessionVariable("formVars", $formVars);

			// There are errors. Relocate back to the update form:
			header("Location: update.php");

			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}

		// --------------------------------------------------------------------

		// If we made it here, then the data is considered valid!

		// (0) CLOSE EXISTING CONNECTION
		// we have to close the existing connection since we need to establish a new connection with admin permissions
		disconnectFromMySQLDatabase(); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

		// (1) OPEN ADMIN CONNECTION, (2) SELECT DATABASE
		connectToMySQLDatabaseAsAdmin($adminUserName, $adminPassword); // function 'connectToMySQLDatabaseAsAdmin()' is defined in 'install.inc.php'

		// --------------------------------------------------------------------

		// (2) RUN the SQL queries on the database through the admin connection:

		//     NOTE: many simple, brain-dead UPDATE queries follow below
		//           (we should probably have a SQL function and/or make this an array and process that)

		$resultArray = array();

		// Alter table specification for table 'deleted'
		// TODO: create a new function 'changeColumn()' that only modifies the column spec if the new column spec is different from the old one
		$query = "ALTER table " . $tableDeleted . " MODIFY edition varchar(50) default NULL";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'deleted': altered table specification. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "ALTER table " . $tableDeleted . " MODIFY thesis enum('Bachelor''s thesis','Honours thesis','Master''s thesis','Ph.D. thesis','Diploma thesis','Doctoral thesis','Habilitation thesis') default NULL";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'deleted': altered table specification. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		// Alter table specification for table 'refs'
		$query = "ALTER table " . $tableRefs . " MODIFY edition varchar(50) default NULL";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'refs': altered table specification. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "ALTER table " . $tableRefs . " MODIFY thesis enum('Bachelor''s thesis','Honours thesis','Master''s thesis','Ph.D. thesis','Diploma thesis','Doctoral thesis','Habilitation thesis') default NULL";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'refs': altered table specification. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		// Add field 'version' to table 'deleted'
		$properties = "MEDIUMINT(8) UNSIGNED DEFAULT 1 AFTER modified_by";
		$resultArray["Table 'deleted': added field 'version'"] = addColumnIfNotExists("version", $tableDeleted, $properties); // function 'addColumnIfNotExists()' is defined in 'install.inc.php'

		// Add field 'version' to table 'refs'
		$properties = "MEDIUMINT(8) UNSIGNED DEFAULT 1 AFTER modified_by";
		$resultArray["Table 'refs': added field 'version'"] = addColumnIfNotExists("version", $tableRefs, $properties);

		// Update table 'refs'
		$query = "UPDATE " . $tableRefs . " SET thesis = NULL WHERE thesis = ''"; // this fix is required to ensure correct sorting when outputting citations with '$citeOrder="type"' or '$citeOrder="type-year"'
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'refs': updated 'thesis' field (replaced empty string with NULL). Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableRefs . " SET type = 'Conference Article' WHERE type RLIKE '^(Unsupported: )?Conference Proceeding$'"; // this may not be perfect since some items of type "Conference Proceeding" may be actually a "Conference Volume"
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'refs': updated 'type' field ('Conference Proceeding' => 'Conference Article'). Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableRefs . " SET type = 'Miscellaneous' WHERE type RLIKE '^(Unsupported: )?Generic$'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'refs': updated 'type' field ('Generic' => 'Miscellaneous'). Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableRefs . " SET type = 'Newspaper Article' WHERE type RLIKE '^(Unsupported: )?Newspaper$'"; // this may not be perfect since some items of type "Newspaper" may be actually a "Newspaper Volume"
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'refs': updated 'type' field ('Newspaper' => 'Newspaper Article'). Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableRefs . " SET type = 'Software' WHERE type RLIKE '^(Unsupported: )?Computer Program$'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'refs': updated 'type' field ('Computer Program' => 'Software'). Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableRefs . " SET type = REPLACE(type,'Unsupported: ','') WHERE type RLIKE '^Unsupported: (Abstract|Conference (Article|Volume)|Magazine Article|Manual|Miscellaneous|Newspaper Article|Patent|Report|Software)$'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'refs': updated 'type' field (removed 'Unsupported' label for all newly supported types). Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		// Create new MySQL table 'user_options'
		$properties = "(option_id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, "
					. "user_id MEDIUMINT UNSIGNED NOT NULL, "
					. "export_cite_keys ENUM('yes','no') NOT NULL, "
					. "autogenerate_cite_keys ENUM('yes','no') NOT NULL, "
					. "prefer_autogenerated_cite_keys ENUM('no','yes') NOT NULL, "
					. "use_custom_cite_key_format ENUM('no','yes') NOT NULL, "
					. "cite_key_format VARCHAR(255), "
					. "uniquify_duplicate_cite_keys ENUM('yes','no') NOT NULL, "
					. "nonascii_chars_in_cite_keys ENUM('transliterate','strip','keep'), "
					. "use_custom_text_citation_format ENUM('no','yes') NOT NULL, "
					. "text_citation_format VARCHAR(255), "
					. "records_per_page SMALLINT(5) UNSIGNED DEFAULT NULL, "
					. "main_fields TEXT, "
					. "INDEX (user_id))";

		$resultArray["Created table 'user_options'"] = addTableIfNotExists($tableUserOptions, $properties); // function 'addTableIfNotExists()' is defined in 'install.inc.php'

		// Insert default user options for anyone who's not logged in
		$values = "(NULL, 0, 'yes', 'yes', 'no', 'no', '<:authors:><:year:>', 'yes', NULL, 'no', '<:authors[2| & | et al.]:>< :year:>< {:recordIdentifier:}>', NULL, 'author, title, publication, keywords, abstract')";
		$resultArray["Table 'user_options': inserted default options for anyone who's not logged in"] = insertIfNotExists(array("user_id" => 0), $tableUserOptions, $values); // function 'insertIfNotExists()' is defined in 'install.inc.php'

		// Insert default user options for all users
		// First, check how many users are contained in table 'users':
		$query = "SELECT user_id, first_name, last_name FROM " . $tableUsers;
		$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'
		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows (= user IDs) found ...
		{
			while ($row = @ mysql_fetch_array($result))
			{
				$values = "(NULL, " . $row['user_id'] . ", 'yes', 'yes', 'no', 'yes', '<:authors[2|+|++]:><:year:>', 'yes', 'transliterate', 'no', '<:authors[2| & | et al.]:>< :year:>< {:recordIdentifier:}>', NULL, 'author, title, publication, keywords, abstract')";
				$resultArray["Table 'user_options': inserted default options for user " . $row['user_id'] . " (" . $row['first_name'] . " " . $row['last_name'] . ")"] = insertIfNotExists(array("user_id" => $row['user_id']), $tableUserOptions, $values);
			}
		}

		// Add field 'records_per_page' to table 'user_options'
		$properties = "SMALLINT(5) UNSIGNED DEFAULT NULL AFTER text_citation_format";
		$resultArray["Table 'user_options': added field 'records_per_page'"] = addColumnIfNotExists("records_per_page", $tableUserOptions, $properties);

		// Add field 'main_fields' to table 'user_options'
		$properties = "TEXT AFTER records_per_page";
		$resultArray["Table 'user_options': added field 'main_fields'"] = addColumnIfNotExists("main_fields", $tableUserOptions, $properties);

		// Add field 'allow_browse_view' to table 'user_permissions'
		$properties = "ENUM('yes','no') NOT NULL AFTER allow_print_view";
		$resultArray["Table 'user_permissions': added field 'allow_browse_view'"] = addColumnIfNotExists("allow_browse_view", $tableUserPermissions, $properties);

		// Add field 'allow_list_view' to table 'user_permissions'
		$properties = "ENUM('yes','no') NOT NULL AFTER allow_upload";
		$resultArray["Table 'user_permissions': added field 'allow_list_view'"] = addColumnIfNotExists("allow_list_view", $tableUserPermissions, $properties);

		// Disable the Browse view feature (which isn't done yet) for all users
		$query= "UPDATE " . $tableUserPermissions . " SET allow_browse_view = 'no'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'user_permissions': disabled the Browse view feature (which isn't done yet). Affected rows"] = ($result ? mysql_affected_rows($connection) : 0); // get the number of rows that were modified (or return 0 if an error occurred)

		// Update table 'styles'
		$query = "UPDATE " . $tableStyles . " SET style_spec = REPLACE(style_spec,'cite_','styles/cite_') WHERE style_spec RLIKE '^cite_'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'styles': updated 'style_spec' field. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$values = "(NULL, 'Ann Glaciol', 'true', 'styles/cite_AnnGlaciol_JGlaciol.php', 'B010', '1')";
		$resultArray["Table 'styles': inserted style 'Ann Glaciol'"] = insertIfNotExists(array("style_name" => "Ann Glaciol"), $tableStyles, $values);

		$values = "(NULL, 'J Glaciol', 'true', 'styles/cite_AnnGlaciol_JGlaciol.php', 'B030', '1')";
		$resultArray["Table 'styles': inserted style 'J Glaciol'"] = insertIfNotExists(array("style_name" => "J Glaciol"), $tableStyles, $values);

		$values = "(NULL, 'APA', 'true', 'styles/cite_APA.php', 'A010', '1')";
		$resultArray["Table 'styles': inserted style 'APA'"] = insertIfNotExists(array("style_name" => "APA"), $tableStyles, $values);

		$values = "(NULL, 'AMA', 'true', 'styles/cite_AMA.php', 'A020', '1')";
		$resultArray["Table 'styles': inserted style 'AMA'"] = insertIfNotExists(array("style_name" => "AMA"), $tableStyles, $values);

		$values = "(NULL, 'MLA', 'true', 'styles/cite_MLA.php', 'A030', '1')";
		$resultArray["Table 'styles': inserted style 'MLA'"] = insertIfNotExists(array("style_name" => "MLA"), $tableStyles, $values);

		$values = "(NULL, 'Chicago', 'true', 'styles/cite_Chicago.php', 'A070', '1')";
		$resultArray["Table 'styles': inserted style 'Chicago'"] = insertIfNotExists(array("style_name" => "Chicago"), $tableStyles, $values);

		$query = "UPDATE " . $tableStyles . " SET order_by = 'B010' WHERE style_name = 'Ann Glaciol'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'styles': updated 'Ann Glaciol' style. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableStyles . " SET order_by = 'B020' WHERE style_name = 'Deep Sea Res'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'styles': updated 'Deep Sea Res' style. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableStyles . " SET order_by = 'B030' WHERE style_name = 'J Glaciol'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'styles': updated 'J Glaciol' style. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableStyles . " SET order_by = 'B040' WHERE style_name = 'Mar Biol'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'styles': updated 'Mar Biol' style. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableStyles . " SET order_by = 'B050' WHERE style_name = 'MEPS'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'styles': updated 'MEPS' style. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableStyles . " SET order_by = 'B060' WHERE style_name = 'Polar Biol'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'styles': updated 'Polar Biol' style. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableStyles . " SET order_by = 'C010' WHERE style_name = 'Text Citation'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'styles': updated 'Text Citation' style. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		// Update table 'types'
		$query = "UPDATE " . $tableTypes . " SET order_by = '01' WHERE type_name = 'Journal Article'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'types': updated 'Journal Article' type. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableTypes . " SET order_by = '02' WHERE type_name = 'Abstract'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'types': updated 'Abstract' type. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableTypes . " SET order_by = '03' WHERE type_name = 'Book Chapter'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'types': updated 'Book Chapter' type. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableTypes . " SET order_by = '04' WHERE type_name = 'Book Whole'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'types': updated 'Book Whole' type. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableTypes . " SET order_by = '05' WHERE type_name = 'Conference Article'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'types': updated 'Conference Article' type. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableTypes . " SET order_by = '06' WHERE type_name = 'Conference Volume'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'types': updated 'Conference Volume' type. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableTypes . " SET order_by = '07' WHERE type_name = 'Journal'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'types': updated 'Journal' type. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableTypes . " SET order_by = '08' WHERE type_name = 'Magazine Article'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'types': updated 'Magazine Article' type. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableTypes . " SET order_by = '09' WHERE type_name = 'Manual'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'types': updated 'Manual' type. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableTypes . " SET order_by = '10' WHERE type_name = 'Manuscript'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'types': updated 'Manuscript' type. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableTypes . " SET order_by = '11' WHERE type_name = 'Map'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'types': updated 'Map' type. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableTypes . " SET order_by = '12' WHERE type_name = 'Miscellaneous'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'types': updated 'Miscellaneous' type. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableTypes . " SET order_by = '13' WHERE type_name = 'Newspaper Article'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'types': updated 'Newspaper Article' type. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableTypes . " SET order_by = '14' WHERE type_name = 'Patent'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'types': updated 'Patent' type. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableTypes . " SET order_by = '15' WHERE type_name = 'Report'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'types': updated 'Report' type. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableTypes . " SET order_by = '16' WHERE type_name = 'Software'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'types': updated 'Software' type. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$values = "(NULL, 'Abstract', 'true', 2, '02')";
		$resultArray["Table 'types': inserted type 'Abstract'"] = insertIfNotExists(array("type_name" => "Abstract"), $tableTypes, $values);

		$values = "(NULL, 'Conference Article', 'true', 2, '05')";
		$resultArray["Table 'types': inserted type 'Conference Article'"] = insertIfNotExists(array("type_name" => "Conference Article"), $tableTypes, $values);

		$values = "(NULL, 'Conference Volume', 'true', 3, '06')";
		$resultArray["Table 'types': inserted type 'Conference Volume'"] = insertIfNotExists(array("type_name" => "Conference Volume"), $tableTypes, $values);

		$values = "(NULL, 'Magazine Article', 'true', 1, '08')";
		$resultArray["Table 'types': inserted type 'Magazine Article'"] = insertIfNotExists(array("type_name" => "Magazine Article"), $tableTypes, $values);

		$values = "(NULL, 'Manual', 'true', 3, '09')";
		$resultArray["Table 'types': inserted type 'Manual'"] = insertIfNotExists(array("type_name" => "Manual"), $tableTypes, $values);

		$values = "(NULL, 'Miscellaneous', 'true', 3, '12')";
		$resultArray["Table 'types': inserted type 'Miscellaneous'"] = insertIfNotExists(array("type_name" => "Miscellaneous"), $tableTypes, $values);

		$values = "(NULL, 'Newspaper Article', 'true', 1, '13')";
		$resultArray["Table 'types': inserted type 'Newspaper Article'"] = insertIfNotExists(array("type_name" => "Newspaper Article"), $tableTypes, $values);

		$values = "(NULL, 'Patent', 'true', 3, '14')";
		$resultArray["Table 'types': inserted type 'Patent'"] = insertIfNotExists(array("type_name" => "Patent"), $tableTypes, $values);

		$values = "(NULL, 'Report', 'true', 3, '15')";
		$resultArray["Table 'types': inserted type 'Report'"] = insertIfNotExists(array("type_name" => "Report"), $tableTypes, $values);

		$values = "(NULL, 'Software', 'true', 3, '16')";
		$resultArray["Table 'types': inserted type 'Software'"] = insertIfNotExists(array("type_name" => "Software"), $tableTypes, $values);

		// Add new language options to table 'languages'
		$values = "(NULL, 'fr', 'true', '3')";
		$resultArray["Table 'languages': inserted French language option"] = insertIfNotExists(array("language_name" => "fr"), $tableLanguages, $values);

		$values = "(NULL, 'es', 'false', '4')";
		$resultArray["Table 'languages': inserted Spanish language option"] = insertIfNotExists(array("language_name" => "es"), $tableLanguages, $values);

		$values = "(NULL, 'cn', 'true', '5')";
		$resultArray["Table 'languages': inserted Chinese language option"] = insertIfNotExists(array("language_name" => "cn"), $tableLanguages, $values);

		// Enable disabled localizations
		$query = "UPDATE " . $tableLanguages . " SET language_enabled = 'true' WHERE language_name = 'de'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'languages': enabled German language option. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		// Alter table specification for table 'formats'
		$query = "ALTER table " . $tableFormats . " MODIFY format_type enum('export','import','cite') NOT NULL default 'export'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': altered table specification. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		// Update existing formats in table 'formats'
		$query = "UPDATE " . $tableFormats . " SET format_name = 'BibTeX' WHERE format_name = 'Bibtex'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': renamed format name 'Bibtex' to 'BibTeX'. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		// Update existing import formats in table 'formats'
		$query = "UPDATE " . $tableFormats . " SET format_spec = 'bibutils/import_bib2refbase.php', order_by = 'A010' WHERE format_name = 'BibTeX' AND format_type = 'import'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'BibTeX' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'A020' WHERE format_name = 'Copac' AND format_type = 'import'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'Copac' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'A030' WHERE format_name = 'CSA' AND format_type = 'import'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'CSA' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET format_spec = 'bibutils/import_end2refbase.php', order_by = 'A040' WHERE format_name = 'Endnote' AND format_type = 'import'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'Endnote' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'A045' WHERE format_name = 'Endnote XML' AND format_type = 'import'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'Endnote XML' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET format_name = 'ISI', format_spec = 'import_isi2refbase.php', order_by = 'A050', depends_id = 1 WHERE (format_name = 'RIS (ISI)' OR format_name = 'ISI') AND format_type = 'import'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'ISI' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'A060' WHERE format_name = 'Pubmed Medline' AND format_type = 'import'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'Pubmed Medline' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET format_spec = 'bibutils/import_med2refbase.php', order_by = 'A065' WHERE format_name = 'Pubmed XML' AND format_type = 'import'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'Pubmed XML' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'A070' WHERE format_name = 'RefWorks' AND format_type = 'import'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'RefWorks' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET format_spec = 'import_ris2refbase.php', order_by = 'A080', depends_id = 1 WHERE format_name = 'RIS' AND format_type = 'import'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'RIS' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'A090' WHERE format_name = 'SciFinder' AND format_type = 'import'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'SciFinder' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'A100' WHERE format_name = 'Text (Tab-Delimited)' AND format_type = 'import'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'Text (Tab-Delimited)' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'A150' WHERE format_name = 'CrossRef XML' AND format_type = 'import'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'CrossRef XML' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET format_spec = 'bibutils/import_modsxml2refbase.php', order_by = 'A160', depends_id = 2 WHERE format_name = 'MODS XML' AND format_type = 'import'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'MODS XML' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'A170' WHERE format_name = 'OAI_DC XML' AND format_type = 'import'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'OAI_DC XML' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		// Add new import formats in table 'formats'
		$values = "(NULL, 'Copac', 'import', 'true', 'bibutils/import_copac2refbase.php', 'A020', 2)";
		$resultArray["Table 'formats': inserted 'Copac' import format"] = insertIfNotExists(array("format_name" => "Copac", "format_type" => "import"), $tableFormats, $values);

		$values = "(NULL, 'CSA', 'import', 'true', 'import_csa2refbase.php', 'A030', 1)";
		$resultArray["Table 'formats': inserted 'CSA' import format"] = insertIfNotExists(array("format_name" => "CSA", "format_type" => "import"), $tableFormats, $values);

		$values = "(NULL, 'Endnote XML', 'import', 'true', 'bibutils/import_endx2refbase.php', 'A045', 2)";
		$resultArray["Table 'formats': inserted 'Endnote XML' import format"] = insertIfNotExists(array("format_name" => "Endnote XML", "format_type" => "import"), $tableFormats, $values);

		$values = "(NULL, 'Pubmed Medline', 'import', 'true', 'import_medline2refbase.php', 'A060', 1)";
		$resultArray["Table 'formats': inserted 'Pubmed Medline' import format"] = insertIfNotExists(array("format_name" => "Pubmed Medline", "format_type" => "import"), $tableFormats, $values);

		$values = "(NULL, 'RefWorks', 'import', 'true', 'import_refworks2refbase.php', 'A070', 1)";
		$resultArray["Table 'formats': inserted 'RefWorks' import format"] = insertIfNotExists(array("format_name" => "RefWorks", "format_type" => "import"), $tableFormats, $values);

		$values = "(NULL, 'SciFinder', 'import', 'true', 'import_scifinder2refbase.php', 'A090', 1)";
		$resultArray["Table 'formats': inserted 'SciFinder' import format"] = insertIfNotExists(array("format_name" => "SciFinder", "format_type" => "import"), $tableFormats, $values);

		$values = "(NULL, 'Text (Tab-Delimited)', 'import', 'true', 'import_tabdelim2refbase.php', 'A100', 1)";
		$resultArray["Table 'formats': inserted 'Text (Tab-Delimited)' import format"] = insertIfNotExists(array("format_name" => "Text (Tab-Delimited)", "format_type" => "import"), $tableFormats, $values);

		$values = "(NULL, 'CrossRef XML', 'import', 'true', 'import_crossref2refbase.php', 'A150', 1)";
		$resultArray["Table 'formats': inserted 'CrossRef XML' import format"] = insertIfNotExists(array("format_name" => "CrossRef XML", "format_type" => "import"), $tableFormats, $values);

		// Update existing export formats in table 'formats'
		$query = "UPDATE " . $tableFormats . " SET order_by = 'B010' WHERE format_name = 'BibTeX' AND format_type = 'export'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'BibTeX' export format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'B040' WHERE format_name = 'Endnote' AND format_type = 'export'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'Endnote' export format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'B050' WHERE format_name = 'ISI' AND format_type = 'export'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'ISI' export format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'B080' WHERE format_name = 'RIS' AND format_type = 'export'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'RIS' export format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'B105' WHERE format_name = 'Text (CSV)' AND format_type = 'export'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'Text (CSV)' export format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET format_name = 'Atom XML', format_enabled = 'true', format_spec ='export_atomxml.php', order_by = 'B140' WHERE format_name = 'OpenSearch RSS' AND format_type = 'export'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'OpenSearch RSS' export format, and renamed it to 'Atom XML'. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'B160' WHERE format_name = 'MODS XML' AND format_type = 'export'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'MODS XML' export format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'B170' WHERE format_name = 'OAI_DC XML' AND format_type = 'export'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'OAI_DC XML' export format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'B180' WHERE format_name = 'ODF XML' AND format_type = 'export'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'ODF XML' export format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET format_name = 'SRW_MODS XML', order_by = 'B195' WHERE format_name = 'SRW XML' AND format_type = 'export'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'SRW XML' export format, and renamed it to 'SRW_MODS XML'. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'B200' WHERE format_name = 'Word XML' AND format_type = 'export'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'Word XML' export format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		// Add new export formats in table 'formats'
		$values = "(NULL, 'ISI', 'export', 'true', 'bibutils/export_xml2isi.php', 'B050', 2)";
		$resultArray["Table 'formats': inserted 'ISI' export format"] = insertIfNotExists(array("format_name" => "ISI", "format_type" => "export"), $tableFormats, $values);

		$values = "(NULL, 'Atom XML', 'export', 'true', 'export_atomxml.php', 'B140', 1)";
		$resultArray["Table 'formats': inserted 'Atom XML' export format"] = insertIfNotExists(array("format_name" => "Atom XML", "format_type" => "export"), $tableFormats, $values);

		$values = "(NULL, 'OAI_DC XML', 'export', 'true', 'export_oaidcxml.php', 'B170', 1)";
		$resultArray["Table 'formats': inserted 'OAI_DC XML' export format"] = insertIfNotExists(array("format_name" => "OAI_DC XML", "format_type" => "export"), $tableFormats, $values);

		$values = "(NULL, 'ODF XML', 'export', 'true', 'export_odfxml.php', 'B180', 1)";
		$resultArray["Table 'formats': inserted 'ODF XML' export format"] = insertIfNotExists(array("format_name" => "ODF XML", "format_type" => "export"), $tableFormats, $values);

		$values = "(NULL, 'SRW_DC XML', 'export', 'true', 'export_srwxml.php', 'B190', 1)";
		$resultArray["Table 'formats': inserted 'SRW_DC XML' export format"] = insertIfNotExists(array("format_name" => "SRW_DC XML", "format_type" => "export"), $tableFormats, $values);

		$values = "(NULL, 'SRW_MODS XML', 'export', 'true', 'export_srwxml.php', 'B195', 1)";
		$resultArray["Table 'formats': inserted 'SRW_MODS XML' export format"] = insertIfNotExists(array("format_name" => "SRW_MODS XML", "format_type" => "export"), $tableFormats, $values);

		$values = "(NULL, 'Word XML', 'export', 'true', 'bibutils/export_xml2word.php', 'B200', 2)";
		$resultArray["Table 'formats': inserted 'Word XML' export format"] = insertIfNotExists(array("format_name" => "Word XML", "format_type" => "export"), $tableFormats, $values);

		// Update existing citation formats in table 'formats'
		$query = "UPDATE " . $tableFormats . " SET order_by = 'C010' WHERE format_name = 'html' AND format_type = 'cite'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'html' citation format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'C020' WHERE format_name = 'RTF' AND format_type = 'cite'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'RTF' citation format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'C030' WHERE format_name = 'PDF' AND format_type = 'cite'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'PDF' citation format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'C040' WHERE format_name = 'LaTeX' AND format_type = 'cite'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'LaTeX' citation format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'C045' WHERE format_name = 'LaTeX .bbl' AND format_type = 'cite'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'LaTeX .bbl' citation format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'C050' WHERE format_name = 'Markdown' AND format_type = 'cite'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'Markdown' citation format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = 'C060' WHERE format_name = 'ASCII' AND format_type = 'cite'";
		$result = queryMySQLDatabase($query);
		$resultArray["Table 'formats': updated 'ASCII' citation format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		// Add new citation formats in table 'formats'
		$values = "(NULL, 'html', 'cite', 'true', 'formats/cite_html.php', 'C010', 1)";
		$resultArray["Table 'formats': inserted 'html' citation format"] = insertIfNotExists(array("format_name" => "html", "format_type" => "cite"), $tableFormats, $values);

		$values = "(NULL, 'RTF', 'cite', 'true', 'formats/cite_rtf.php', 'C020', 1)";
		$resultArray["Table 'formats': inserted 'RTF' citation format"] = insertIfNotExists(array("format_name" => "RTF", "format_type" => "cite"), $tableFormats, $values);

		$values = "(NULL, 'PDF', 'cite', 'true', 'formats/cite_pdf.php', 'C030', 1)";
		$resultArray["Table 'formats': inserted 'PDF' citation format"] = insertIfNotExists(array("format_name" => "PDF", "format_type" => "cite"), $tableFormats, $values);

		$values = "(NULL, 'LaTeX', 'cite', 'true', 'formats/cite_latex.php', 'C040', 1)";
		$resultArray["Table 'formats': inserted 'LaTeX' citation format"] = insertIfNotExists(array("format_name" => "LaTeX", "format_type" => "cite"), $tableFormats, $values);

		$values = "(NULL, 'LaTeX .bbl', 'cite', 'true', 'formats/cite_latex_bbl.php', 'C045', 1)";
		$resultArray["Table 'formats': inserted 'LaTeX .bbl' citation format"] = insertIfNotExists(array("format_name" => "LaTeX .bbl", "format_type" => "cite"), $tableFormats, $values);

		$values = "(NULL, 'Markdown', 'cite', 'true', 'formats/cite_markdown.php', 'C050', 1)";
		$resultArray["Table 'formats': inserted 'Markdown' citation format"] = insertIfNotExists(array("format_name" => "Markdown", "format_type" => "cite"), $tableFormats, $values);

		$values = "(NULL, 'ASCII', 'cite', 'true', 'formats/cite_ascii.php', 'C060', 1)";
		$resultArray["Table 'formats': inserted 'ASCII' citation format"] = insertIfNotExists(array("format_name" => "ASCII", "format_type" => "cite"), $tableFormats, $values);

		// Enable some of the existing export formats (which were previously disabled by default) for anyone who's not logged in ('$userID = 0'):
		// Fetch IDs for all formats that shall be enabled:
		$formatIDArray = array();
		$query = "SELECT format_id, format_name FROM " . $tableFormats . " WHERE (format_name RLIKE '^(BibTeX|Endnote|RIS)$' AND format_type = 'export')";
		$result = queryMySQLDatabase($query);
		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0)
		{
			while ($row = @ mysql_fetch_array($result))
				$formatIDArray[$row['format_id']] = $row['format_name'];
		}

		foreach ($formatIDArray as $formatID => $formatName)
		{
			$values = "(NULL, " . $formatID . ", 0, 'true')";
			$resultArray["Table 'user_formats': enabled format '" . $formatName . "' for anyone who's not logged in"] = insertIfNotExists(array("format_id" => $formatID), $tableUserFormats, $values, "0");
		}

		// Enable some of the newly created export/citation formats, citation styles & resource types for all users:
		// Fetch IDs for all formats that shall be enabled:
		$formatIDArray = array();
		$query = "SELECT format_id, format_name FROM " . $tableFormats . " WHERE (format_name RLIKE '^(ISI|Atom XML|ODF XML|Word XML)$' AND format_type = 'export') OR (format_name RLIKE '^(html|RTF|PDF|LaTeX)$' AND format_type = 'cite')";
		$result = queryMySQLDatabase($query);
		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0)
		{
			while ($row = @ mysql_fetch_array($result))
				$formatIDArray[$row['format_id']] = $row['format_name'];
		}

		// Fetch IDs for all styles that shall be enabled:
		$styleIDArray = array();
		$query = "SELECT style_id, style_name FROM " . $tableStyles . " WHERE style_name RLIKE '^(AMA|APA|Chicago|J Glaciol|MLA)$'";
		$result = queryMySQLDatabase($query);
		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0)
		{
			while ($row = @ mysql_fetch_array($result))
				$styleIDArray[$row['style_id']] = $row['style_name'];
		}

		// Fetch IDs for all types that shall be enabled:
		$typeIDArray = array();
		$query = "SELECT type_id, type_name FROM " . $tableTypes . " WHERE type_name RLIKE '^(Abstract|Conference Article|Conference Volume|Magazine Article|Manual|Miscellaneous|Newspaper Article|Patent|Report|Software)$'";
		$result = queryMySQLDatabase($query);
		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0)
		{
			while ($row = @ mysql_fetch_array($result))
				$typeIDArray[$row['type_id']] = $row['type_name'];
		}

		// Enable formats, styles & types for anyone who's not logged in ('$userID = 0'):
		foreach ($formatIDArray as $formatID => $formatName)
		{
			$values = "(NULL, " . $formatID . ", 0, 'true')";
			$resultArray["Table 'user_formats': enabled format '" . $formatName . "' for anyone who's not logged in"] = insertIfNotExists(array("format_id" => $formatID), $tableUserFormats, $values, "0");
		}

		foreach ($styleIDArray as $styleID => $styleName)
		{
			$values = "(NULL, " . $styleID . ", 0, 'true')";
			$resultArray["Table 'user_styles': enabled style '" . $styleName . "' for anyone who's not logged in"] = insertIfNotExists(array("style_id" => $styleID), $tableUserStyles, $values, "0");
		}

		foreach ($typeIDArray as $typeID => $typeName)
		{
			$values = "(NULL, " . $typeID . ", 0, 'true')";
			$resultArray["Table 'user_types': enabled type '" . $typeName . "' for anyone who's not logged in"] = insertIfNotExists(array("type_id" => $typeID), $tableUserTypes, $values, "0");
		}

		// Enable formats, styles & types for all users:
		// First, check how many users are contained in table 'users':
		$query = "SELECT user_id, first_name, last_name FROM " . $tableUsers;
		$result = queryMySQLDatabase($query);
		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows (= user IDs) found ...
		{
			while ($row = @ mysql_fetch_array($result))
			{
				foreach ($formatIDArray as $formatID => $formatName)
				{
					$values = "(NULL, " . $formatID . ", " . $row['user_id'] . ", 'true')";
					$resultArray["Table 'user_formats': enabled format '" . $formatName . "' for user " . $row['user_id'] . " (" . $row['first_name'] . " " . $row['last_name'] . ")"] = insertIfNotExists(array("format_id" => $formatID), $tableUserFormats, $values, $row['user_id']);
				}

				foreach ($styleIDArray as $styleID => $styleName)
				{
					$values = "(NULL, " . $styleID . ", " . $row['user_id'] . ", 'true')";
					$resultArray["Table 'user_styles': enabled style '" . $styleName . "' for user " . $row['user_id'] . " (" . $row['first_name'] . " " . $row['last_name'] . ")"] = insertIfNotExists(array("style_id" => $styleID), $tableUserStyles, $values, $row['user_id']);
				}

				foreach ($typeIDArray as $typeID => $typeName)
				{
					$values = "(NULL, " . $typeID . ", " . $row['user_id'] . ", 'true')";
					$resultArray["Table 'user_types': enabled type '" . $typeName . "' for user " . $row['user_id'] . " (" . $row['first_name'] . " " . $row['last_name'] . ")"] = insertIfNotExists(array("type_id" => $typeID), $tableUserTypes, $values, $row['user_id']);
				}
			}
		}

		// (3) ERRORS

		// Check whether any tables/rows were affected by the performed SQL queries:
		unset($resultArray["Table 'deleted': altered table specification. Affected rows"]); // currently, we simply remove again the results of the 'ALTER TABLE' queries since they will always affect every row in that table
		unset($resultArray["Table 'refs': altered table specification. Affected rows"]);
		unset($resultArray["Table 'formats': altered table specification. Affected rows"]);

		foreach($resultArray as $varname => $value)
			if (($value == "0") OR ($value == "false"))
				unset($resultArray[$varname]); // remove any action that didn't affect any table or rows

		if (empty($resultArray))
		{
			$HeaderString = "<b><span class=\"ok\">Nothing was changed! Your refbase installation is up-to-date.</span></b>";

			// Write back session variables:
			saveSessionVariable("HeaderString", $HeaderString);
		}

		// (4) CLOSE ADMIN CONNECTION
		disconnectFromMySQLDatabase();

		// --------------------------------------------------------------------

		// Provide a feedback page:

		// If there's no stored message available:
		if (!isset($_SESSION['HeaderString'])) // provide a default message:
		{
			$HeaderString = "<b><span class=\"ok\">Update of the Web Reference Database was successful!</span></b>";
		}
		else
		{
			$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

			// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
			deleteSessionVariable("HeaderString");
		}

		// Extract the view type requested by the user (either 'Mobile', 'Print', 'Web' or ''):
		// ('' will produce the default 'Web' output style)
		if (isset($_REQUEST['viewType']))
			$viewType = $_REQUEST['viewType'];
		else
			$viewType = "";

		// Show the login status:
		showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

		// DISPLAY header:
		// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
		displayHTMLhead(encodeHTML($officialDatabaseName) . " -- Update Feedback", "index,follow", "Update feedback for the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
		showPageHeader($HeaderString);

		// Start a <table>:
?>

<table align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds the update feedback info"><?php

		if (!empty($resultArray)) // if something was changed, add a welcome title:
		{
?>

	<tr>
		<td colspan="2"><h3>Welcome to refbase v0.9.1!</h3></td>
	</tr><?php

		}

		// following note will be always displayed no matter if something was changed or not:
?>

	<tr>
		<td valign="top"><b>Important Note:</b></td>
		<td>
			The files <em>update.php</em> and <em>update.sql</em> (as well as <em>install.php</em> and <em>install.sql</em>) are only provided for update/installation purposes and are not needed anymore. Due to security considerations you should <span class="warning">remove these files</span> from your web directory NOW!!
		</td>
	</tr><?php

		if (!empty($resultArray)) // if something was changed, add some configuration info:
		{
?>

	<tr>
		<td valign="top"><b>Configure refbase:</b></td>
		<td>
			In order to re-establish your existing settings, please open file <em>initialize/ini.inc.php</em> in a text editor and restore all values from your old <em>ini.inc.php</em> file. The new include file contains new settings which you should check out and adopt to your needs if needed. Please see the comments within the file for further information.
		</td>
	</tr>
	<tr>
		<td valign="top"><b>Log:</b></td>
		<td>
			Following update actions were performed successfully:
		</td>
	</tr>
	<tr>
		<td valign="top">&nbsp;</td>
		<td>
			<pre><?php

			foreach($resultArray as $varname => $value)
			{
				if ($value == "true")
					echo $varname . ".\n";
				else
					echo $varname . ": " . $value . "\n";
			}
?>
			</pre>
		</td>
	</tr><?php

		}
?>

</table><?php

		// --------------------------------------------------------------------

		// DISPLAY THE HTML FOOTER:
		// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
		showPageFooter($HeaderString);

		displayHTMLfoot();

		// --------------------------------------------------------------------

	}
?>
