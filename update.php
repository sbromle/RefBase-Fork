<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./update.php
	// Created:    01-Mar-05, 20:47
	// Modified:   13-Nov-06, 17:30

	// This file will update any refbase MySQL database installation from v0.8.0 (and, to a certain extent, intermediate cvs versions) to v0.9.0.
	// (Note that this script currently doesn't offer any conversion from 'latin1' to 'utf8')
	// CAUTION: YOU MUST REMOVE THIS SCRIPT FROM YOUR WEB DIRECTORY AFTER THE UPDATE!!

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

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
		//Display an update form:

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
			deleteSessionVariable("formVars"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
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
			deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
		}

		// Extract the view type requested by the user (either 'Print', 'Web' or ''):
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
		showPageHeader($HeaderString, "");

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
		showPageFooter($HeaderString, "");

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
		disconnectFromMySQLDatabase(""); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

		// (1) OPEN ADMIN CONNECTION, (2) SELECT DATABASE
		connectToMySQLDatabaseAsAdmin($adminUserName, $adminPassword); // function 'connectToMySQLDatabaseAsAdmin()' is defined in 'install.inc.php'

		// --------------------------------------------------------------------

		// (2) RUN the SQL queries on the database through the admin connection:

		$resultArray = array();

		// (2.1) Create new MySQL table 'user_options'
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
					. "INDEX (user_id))";

		$resultArray["Created table 'user_options'"] = addTableIfNotExists($tableUserOptions, $properties); // function 'addTableIfNotExists()' is defined in 'install.inc.php'

		// (2.2) Insert default user options for anyone who's not logged in
		$values = "(NULL, 0, 'yes', 'yes', 'no', 'no', '<:authors:><:year:>', 'yes', NULL, 'no', '<:authors[2| & | et al.]:>< :year:>< {:recordIdentifier:}>')";
		$resultArray["Table 'user_options': inserted default options for anyone who's not logged in"] = insertIfNotExists("user_id", 0, $tableUserOptions, $values); // function 'insertIfNotExists()' is defined in 'install.inc.php'

		// (2.3) Insert default user options for all users
		// First, check how many users are contained in table 'users':
		$query = "SELECT user_id, first_name, last_name FROM " . $tableUsers;
		$result = queryMySQLDatabase($query, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'
		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows (= user IDs) found ...
		{
			while ($row = @ mysql_fetch_array($result))
			{
				$values = "(NULL, " . $row['user_id'] . ", 'yes', 'yes', 'no', 'yes', '<:authors[2|+|++]:><:year:>', 'yes', 'transliterate', 'no', '<:authors[2| & | et al.]:>< :year:>< {:recordIdentifier:}>')";
				$resultArray["Table 'user_options': inserted default options for user " . $row['user_id'] . " (" . $row['first_name'] . " " . $row['last_name'] . ")"] = insertIfNotExists("user_id", $row['user_id'], $tableUserOptions, $values);
			}
		}

		// (2.4) Add field 'allow_browse_view' to table 'user_permissions'
		$properties = "ENUM('yes','no') NOT NULL AFTER allow_print_view";
		$resultArray["Table 'user_permissions': added field 'allow_browse_view'"] = addColumnIfNotExists("allow_browse_view", $tableUserPermissions, $properties); // function 'addColumnIfNotExists()' is defined in 'install.inc.php'

		// (2.5) Disable the Browse view feature (which isn't done yet) for all users
		$query= "UPDATE " . $tableUserPermissions . " SET allow_browse_view = 'no'";
		$result = queryMySQLDatabase($query, "");
		$resultArray["Table 'user_permissions': disabled the Browse view feature (which isn't done yet). Affected rows"] = ($result ? mysql_affected_rows($connection) : 0); // get the number of rows that were modified (or return 0 if an error occurred)

		// (2.6) Update table 'styles'
		$query = "UPDATE " . $tableStyles . " SET style_spec = REPLACE(style_spec,'cite_','styles/cite_') WHERE style_spec RLIKE '^cite_'";
		$result = queryMySQLDatabase($query, "");
		$resultArray["Table 'styles': updated 'style_spec' field. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$values = "(NULL, 'Ann Glaciol', 'true', 'styles/cite_AnnGlaciol_JGlaciol.php', 'B050', '1')";
		$resultArray["Table 'styles': inserted style 'Ann Glaciol'"] = insertIfNotExists("style_name", "Ann Glaciol", $tableStyles, $values);

		$values = "(NULL, 'J Glaciol', 'true', 'styles/cite_AnnGlaciol_JGlaciol.php', 'B060', '1')";
		$resultArray["Table 'styles': inserted style 'J Glaciol'"] = insertIfNotExists("style_name", "J Glaciol", $tableStyles, $values);

		$values = "(NULL, 'APA', 'true', 'styles/cite_APA.php', 'A010', '1')";
		$resultArray["Table 'styles': inserted style 'APA'"] = insertIfNotExists("style_name", "APA", $tableStyles, $values);
		
		$values = "(NULL, 'MLA', 'true', 'styles/cite_MLA.php', 'A030', '1')";
		$resultArray["Table 'styles': inserted style 'MLA'"] = insertIfNotExists("style_name", "MLA", $tableStyles, $values);

		$query = "UPDATE " . $tableStyles . " SET order_by = 'C010' WHERE style_name = 'Text Citation'";
		$result = queryMySQLDatabase($query, "");
		$resultArray["Table 'styles': updated 'Text Citation' style. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableStyles . " SET order_by = 'B010' WHERE style_name = 'Polar Biol'";
		$result = queryMySQLDatabase($query, "");
		$resultArray["Table 'styles': updated 'Polar Biol' style. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableStyles . " SET order_by = 'B020' WHERE style_name = 'Mar Biol'";
		$result = queryMySQLDatabase($query, "");
		$resultArray["Table 'styles': updated 'Mar Biol' style. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableStyles . " SET order_by = 'B030' WHERE style_name = 'MEPS'";
		$result = queryMySQLDatabase($query, "");
		$resultArray["Table 'styles': updated 'MEPS' style. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableStyles . " SET order_by = 'B040' WHERE style_name = 'Deep Sea Res'";
		$result = queryMySQLDatabase($query, "");
		$resultArray["Table 'styles': updated 'Deep Sea Res' style. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		// (2.7) Add the french language option to table 'languages'
		$values = "(NULL, 'fr', 'true', '3')";
		$resultArray["Table 'languages': inserted french language option"] = insertIfNotExists("language_name", "fr", $tableLanguages, $values);

		// (2.7b) Enable german localization
		$query = "UPDATE " . $tableLanguages . " SET language_enabled = 'true' WHERE language_name = 'de'";
		$result = queryMySQLDatabase($query, "");
		$resultArray["Table 'languages': enabled german language option. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		// (2.8) Alter table specification for table 'formats'
		$query = "ALTER table " . $tableFormats . " MODIFY format_type enum('export','import','cite') NOT NULL default 'export'";
		$result = queryMySQLDatabase($query, "");
		$resultArray["Table 'formats': altered table specification. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		// (2.9) Update existing formats in table 'formats'
		$query = "UPDATE " . $tableFormats . " SET format_name = 'BibTeX' WHERE format_name = 'Bibtex'";
		$result = queryMySQLDatabase($query, "");
		$resultArray["Table 'formats': renamed format name 'Bibtex' to 'BibTeX'. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET order_by = CONCAT('0',order_by) WHERE order_by RLIKE '^[0-9]$'";
		$result = queryMySQLDatabase($query, "");
		$resultArray["Table 'formats': reformatted numbers in 'order_by' field as two-digit numbers. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		// (2.10) Replace existing import formats with updated/new ones in table 'formats'
		// NOTE: Simple, brain-dead test of UPDATEing (we should probably have a SQL function and/or make this an array and process that)
		$query = "UPDATE " . $tableFormats . " SET format_spec = 'bibutils/import_modsxml2refbase.php', depends_id = 2 WHERE format_name = 'MODS XML' AND format_type = 'import'";
		$result = queryMySQLDatabase($query, "");
		$resultArray["Table 'formats': updated 'MODS XML' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET format_spec = 'bibutils/import_bib2refbase.php' WHERE format_name = 'BibTeX' AND format_type = 'import'";
		$result = queryMySQLDatabase($query, "");
		$resultArray["Table 'formats': updated 'BibTeX' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET format_spec = 'bibutils/import_end2refbase.php' WHERE format_name = 'Endnote' AND format_type = 'import'";
		$result = queryMySQLDatabase($query, "");
		$resultArray["Table 'formats': updated 'Endnote' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET format_spec = 'bibutils/import_med2refbase.php', order_by = '09' WHERE format_name = 'Pubmed XML' AND format_type = 'import'";
		$result = queryMySQLDatabase($query, "");
		$resultArray["Table 'formats': updated 'Pubmed XML' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET format_spec = 'import_ris2refbase.php', depends_id = 1 WHERE format_name = 'RIS' AND format_type = 'import'";
		$result = queryMySQLDatabase($query, "");
		$resultArray["Table 'formats': updated 'RIS' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$query = "UPDATE " . $tableFormats . " SET format_name = 'ISI', format_spec = 'import_isi2refbase.php', depends_id = 1 WHERE format_name = 'RIS (ISI)' AND format_type = 'import'";
		$result = queryMySQLDatabase($query, "");
		$resultArray["Table 'formats': updated 'ISI' import format. Affected rows"] = ($result ? mysql_affected_rows($connection) : 0);

		$values = "(NULL, 'Pubmed Medline', 'import', 'true', 'import_medline2refbase.php', '08', 1)";
		$resultArray["Table 'formats': inserted 'Pubmed Medline' import format"] = insertIfNotExists("format_name", "Pubmed Medline", $tableFormats, $values);

		$values = "(NULL, 'CSA', 'import', 'true', 'import_csa2refbase.php', '05', 1)";
		$resultArray["Table 'formats': inserted 'CSA' import format"] = insertIfNotExists("format_name", "CSA", $tableFormats, $values);

		$values = "(NULL, 'Copac', 'import', 'true', 'bibutils/import_copac2refbase.php', '10', 2)";
		$resultArray["Table 'formats': inserted 'Copac' import format"] = insertIfNotExists("format_name", "Copac", $tableFormats, $values);

		$values = "(NULL, 'RefWorks', 'import', 'true', 'import_refworks2refbase.php', '20', 1)";
		$resultArray["Table 'formats': inserted 'RefWorks' import format"] = insertIfNotExists("format_name", "RefWorks", $tableFormats, $values);

		// (2.11) Add new export & citation formats in table 'formats'
		$values = "(NULL, 'SRW XML', 'export', 'true', 'export_srwxml.php', '11', 1)";
		$resultArray["Table 'formats': inserted 'SRW XML' export format"] = insertIfNotExists("format_name", "SRW XML", $tableFormats, $values);

		$values = "(NULL, 'ODF XML', 'export', 'true', 'export_odfxml.php', '12', 1)";
		$resultArray["Table 'formats': inserted 'ODF XML' export format"] = insertIfNotExists("format_name", "ODF XML", $tableFormats, $values);

		$values = "(NULL, 'OpenSearch RSS', 'export', 'false', 'export_osrss.php', '13', 1)";
		$resultArray["Table 'formats': inserted 'OpenSearch RSS' export format"] = insertIfNotExists("format_name", "OpenSearch RSS", $tableFormats, $values);

		$values = "(NULL, 'html', 'cite', 'true', 'formats/cite_html.php', '14', 1)";
		$resultArray["Table 'formats': inserted 'html' citation format"] = insertIfNotExists("format_name", "html", $tableFormats, $values);

		$values = "(NULL, 'RTF', 'cite', 'true', 'formats/cite_rtf.php', '15', 1)";
		$resultArray["Table 'formats': inserted 'RTF' citation format"] = insertIfNotExists("format_name", "RTF", $tableFormats, $values);

		$values = "(NULL, 'PDF', 'cite', 'true', 'formats/cite_pdf.php', '16', 1)";
		$resultArray["Table 'formats': inserted 'PDF' citation format"] = insertIfNotExists("format_name", "PDF", $tableFormats, $values);

		$values = "(NULL, 'LaTeX', 'cite', 'true', 'formats/cite_latex.php', '17', 1)";
		$resultArray["Table 'formats': inserted 'LaTeX' citation format"] = insertIfNotExists("format_name", "LaTeX", $tableFormats, $values);

		$values = "(NULL, 'Markdown', 'cite', 'true', 'formats/cite_markdown.php', '18', 1)";
		$resultArray["Table 'formats': inserted 'Markdown' citation format"] = insertIfNotExists("format_name", "Markdown", $tableFormats, $values);

		$values = "(NULL, 'ASCII', 'cite', 'true', 'formats/cite_ascii.php', '19', 1)";
		$resultArray["Table 'formats': inserted 'ASCII' citation format"] = insertIfNotExists("format_name", "ASCII", $tableFormats, $values);

		// (2.12) Enable some of the newly created export/citation formats & citation styles for all users:
		// Fetch IDs for all formats that shall be enabled:
		$formatIDArray = array();
		$query = "SELECT format_id, format_name FROM " . $tableFormats . " WHERE format_name RLIKE '^(ODF XML|html|RTF|PDF|LaTeX)$'";
		$result = queryMySQLDatabase($query, "");
		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0)
		{
			while ($row = @ mysql_fetch_array($result))
				$formatIDArray[$row['format_id']] = $row['format_name'];
		}

		// Fetch IDs for all styles that shall be enabled:
		$styleIDArray = array(); // with just one citation style to enable, this code block is currently overkill, but future needs may be different
		$query = "SELECT style_id, style_name FROM " . $tableStyles . " WHERE style_name RLIKE '^(J Glaciol|APA|MLA)$'";
		$result = queryMySQLDatabase($query, "");
		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0)
		{
			while ($row = @ mysql_fetch_array($result))
				$styleIDArray[$row['style_id']] = $row['style_name'];
		}

		// Enable formats & styles for anyone who's not logged in ('$userID = 0'):
		foreach ($formatIDArray as $formatID => $formatName)
		{
			$values = "(NULL, " . $formatID . ", 0, 'true')";
			$resultArray["Table 'user_formats': enabled format '" . $formatName . "' for anyone who's not logged in"] = insertIfNotExists("format_id", $formatID, $tableUserFormats, $values, 0);
		}

		foreach ($styleIDArray as $styleID => $styleName)
		{
			$values = "(NULL, " . $styleID . ", 0, 'true')";
			$resultArray["Table 'user_styles': enabled style '" . $styleName . "' for anyone who's not logged in"] = insertIfNotExists("style_id", $styleID, $tableUserStyles, $values, 0);
		}

		// Enable formats & styles for all users:
		// First, check how many users are contained in table 'users':
		$query = "SELECT user_id, first_name, last_name FROM " . $tableUsers;
		$result = queryMySQLDatabase($query, "");
		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows (= user IDs) found ...
		{
			while ($row = @ mysql_fetch_array($result))
			{
				foreach ($formatIDArray as $formatID => $formatName)
				{
					$values = "(NULL, " . $formatID . ", " . $row['user_id'] . ", 'true')";
					$resultArray["Table 'user_formats': enabled format '" . $formatName . "' for user " . $row['user_id'] . " (" . $row['first_name'] . " " . $row['last_name'] . ")"] = insertIfNotExists("format_id", $formatID, $tableUserFormats, $values, $row['user_id']);
				}

				foreach ($styleIDArray as $styleID => $styleName)
				{
					$values = "(NULL, " . $styleID . ", " . $row['user_id'] . ", 'true')";
					$resultArray["Table 'user_styles': enabled style '" . $styleName . "' for user " . $row['user_id'] . " (" . $row['first_name'] . " " . $row['last_name'] . ")"] = insertIfNotExists("style_id", $styleID, $tableUserStyles, $values, $row['user_id']);
				}
			}
		}

		// (3) ERRORS

		// Check whether any tables/rows were affected by the performed SQL queries:
		unset($resultArray["Table 'formats': altered table specification. Affected rows"]); // currently, we simply remove again the result of the 'ALTER TABLE' query since it will always affect every row in that table

		foreach($resultArray as $varname => $value)
			if (($value == "0") OR ($value == "false"))
				unset($resultArray[$varname]); // remove any action that didn't affect any table or rows

		if (empty($resultArray))
		{
			$HeaderString = "<b><span class=\"ok\">Nothing was changed! Your refbase installation is up-to-date.</span></b>";

			// Write back session variables:
			saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		}

		// (4) CLOSE ADMIN CONNECTION
		disconnectFromMySQLDatabase(""); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

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
			deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
		}

		// Extract the view type requested by the user (either 'Print', 'Web' or ''):
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
		showPageHeader($HeaderString, "");

		// Start a <table>:
?>

<table align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds the update feedback info"><?php

		if (!empty($resultArray)) // if something was changed, add a welcome title:
		{
?>

	<tr>
		<td colspan="2"><h3>Welcome to refbase v0.9.0!</h3></td>
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
			In order to re-establish your existing settings, please open file <em>initialize/ini.inc.php</em> in a text editor and restore all values from your old <em>ini.inc.php</em> file. The new include file contains many new settings which you should check out and adopt to your needs if needed. Please see the comments within the file for further information.
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
		showPageFooter($HeaderString, "");

		displayHTMLfoot();

		// --------------------------------------------------------------------

	}
?>
