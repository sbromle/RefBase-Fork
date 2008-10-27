<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./install.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    07-Jan-04, 22:00
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This file will install the literature database for you. Note that you must have
	// an existing PHP and MySQL installation. Please see the readme for further information.
	// CAUTION: YOU MUST REMOVE THIS SCRIPT FROM YOUR WEB DIRECTORY AFTER INSTALLATION!!


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
	if (isset($_POST['adminUserName']))
		$adminUserName = $_POST['adminUserName'];
	else
		$adminUserName = "";

	if (isset($_POST['adminPassword']))
		$adminPassword = $_POST['adminPassword'];
	else
		$adminPassword = "";

	if (isset($_POST['pathToMYSQL']))
		$pathToMYSQL = $_POST['pathToMYSQL'];
	else
		$pathToMYSQL = "";

	if (isset($_POST['databaseStructureFile']))
		$databaseStructureFile = $_POST['databaseStructureFile'];
	else
		$databaseStructureFile = "";

	if (isset($_POST['pathToBibutils']))
		$pathToBibutils = $_POST['pathToBibutils'];
	else
		$pathToBibutils = "";

	if (isset($_POST['defaultCharacterSet']))
		$defaultCharacterSet = $_POST['defaultCharacterSet'];
	else
		$defaultCharacterSet = "";

	// --------------------------------------------------------------------

	// Check the correct parameters have been passed:
	if (empty($adminUserName) AND empty($adminPassword) AND empty($pathToMYSQL) AND empty($databaseStructureFile))
	{
		// if 'install.php' was called without any valid parameters:
		//Display an installation form:

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
			$pathSeparator = PATH_SEPARATOR;
			$pathItems = explode($pathSeparator, getenv("PATH"));

			// Provide default values for the form fields:
			$formVars["adminUserName"] = "root";
			$formVars["adminPassword"] = "";
			$formVars["databaseStructureFile"] = "./install.sql";
			
			// Try to find the 'mysql' command line interpreter:
			$mysqlLocations = array_unique(array_merge($pathItems, array("/Program Files/MySQL/bin", "/wamp/mysql/bin", "/Program Files/xampp/mysql/bin", "/www/xampp/mysql/bin", "/xampp/mysql/bin", "/apachefriends/xampp/mysql/bin", "/usr/local/mysql/bin", "/usr/local/bin/mysql/bin", "/usr/bin/mysql/bin", "/usr/mysql/bin", "/opt/local/bin/", "/opt/local/lib/mysql4/bin/", "/opt/local/lib/mysql5/bin/")));
			$mysqlNames = array("mysql", "mysql.exe");
			$formVars["pathToMYSQL"] = locateFile($mysqlLocations, $mysqlNames, false); // function 'locateFile()' is defined in 'install.inc.php'

			// Try to find the Bibutils programs:
			$bibutilsLocations = array_unique(array_merge($pathItems, array("/usr/bin", "/usr/local/bin", "/opt/local/bin/", ".", "./refbase", "./bibutils")));
			// We'll only check for one program to save time (and because, we currently don't allow the script to have a subset of the functionality provided by Bibutils)
			$bibutilsNames = array("xml2bib", "xml2bib.exe");
			$formVars["pathToBibutils"] = locateFile($bibutilsLocations, $bibutilsNames, true);

			$formVars["defaultCharacterSet"] = "latin1";
		}

		// If there's no stored message available:
		if (!isset($_SESSION['HeaderString']))
		{
			if (empty($errors)) // provide the default message:
				$HeaderString = "To install the refbase package please fill out the form below and click the <em>Install</em> button:";
			else // -> there were errors when validating the fields
				$HeaderString = "<b><span class=\"warning\">There were validation errors regarding the details you entered. Please check the comments above the respective fields:</span></b>";
		}
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

		// For the default character set, make sure that the correct popup menu entry is selected upon reload:
		if ($formVars["defaultCharacterSet"] == "utf8")
		{
			$latin1CharacterSetSelected = "";
			$unicodeCharacterSetSelected = " selected";
		}
		else // $formVars["defaultCharacterSet"] is 'latin1' or ''
		{
			$latin1CharacterSetSelected = " selected";
			$unicodeCharacterSetSelected = "";
		}

		// Show the login status:
		showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

		// DISPLAY header:
		// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
		displayHTMLhead(encodeHTML($officialDatabaseName) . " -- Installation", "index,follow", "Installation form for the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
		showPageHeader($HeaderString);

		// Start <form> and <table> holding the form elements:
?>

<form action="install.php" method="POST">
<input type="hidden" name="formType" value="install">
<input type="hidden" name="submit" value="Install"><?php // provide a default value for the 'submit' form tag. Otherwise, some browsers may not recognize the correct output format when a user hits <enter> within a form field (instead of clicking the "Install" button) ?>

<table align="center" border="0" cellpadding="0" cellspacing="12" width="95%" summary="This table holds the installation form">
	<tr>
		<td colspan="3"><h3>refbase Installation</h3></td>
	</tr>
	<tr>
		<td width="190" valign="top"><b>Important note:</b></td>
		<td valign="top" colspan="2">
			Before executing this script, it is highly recommended to <span class="warning">open the include file <em>initialize/db.inc.php</em></span> in a text editor and edit the values of the variables <em>$databaseName</em>, <em>$username</em> and <em>$password</em> to suit your setup! Then, proceed with this form:
		</td>
	</tr>
	<tr>
		<td valign="top"><b>MySQL admin user:</b></td>
		<td valign="top"><?php echo fieldError("adminUserName", $errors); ?>

			<input type="text" name="adminUserName" value="<?php echo $formVars["adminUserName"]; ?>" size="30">
		</td>
		<td valign="top">Give the name of an administrative user that has full access to the MySQL admin database. Often, this is the <em>root</em> user.</td>
	</tr>
	<tr>
		<td valign="top"><b>MySQL admin password:</b></td>
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
		<td valign="top"><b>Path to the MySQL application:</b></td>
		<td valign="top"><?php echo fieldError("pathToMYSQL", $errors); ?>

			<input type="text" name="pathToMYSQL" value="<?php echo $formVars["pathToMYSQL"]; ?>" size="30">
		</td>
		<td valign="top">Specify the full path to the <em>mysql</em> command line interpreter. The install script attempts to locate the <em>mysql</em> program for you. If the field is empty, please enter the full path manually.</td>
	</tr>
	<tr>
		<td valign="top"><b>Path to the database structure file:</b></td>
		<td valign="top"><?php echo fieldError("databaseStructureFile", $errors); ?>

			<input type="text" name="databaseStructureFile" value="<?php echo $formVars["databaseStructureFile"]; ?>" size="30">
		</td>
		<td valign="top">Enter the full path to the SQL dump file containing the database structure &amp; data. Keep the default value, if you're installing refbase for the first time.</td>
	</tr>
	<tr>
		<td valign="top"><b>Path to the Bibutils directory [optional]:</b></td>
		<td valign="top"><?php echo fieldError("pathToBibutils", $errors); ?>

			<input type="text" name="pathToBibutils" value="<?php echo $formVars["pathToBibutils"]; ?>" size="30">
		</td>
		<td valign="top"><a href="http://bibutils.refbase.net/" target="top" title="more info about the refbase integration with Bibutils"><em>Bibutils</em></a> provides additional import and export funtionality to refbase (e.g. support for Endnote &amp; BibTeX). It is optional, but highly recommended. The install script attempts to locate <em>Bibutils</em> for you. If you can't access <em>Bibutils</em> from your path, please fill this value in manually (and, if you think other people might have <em>Bibutils</em> installed to the same path, <a href="http://support.refbase.net/" target="top" title="refbase forums &amp; mailinglists">report</a> it to the refbase developers). The path must end with a slash!</td>
	</tr>
	<tr>
		<td valign="top"><b>Default character set:</b></td>
		<td valign="top"><?php echo fieldError("defaultCharacterSet", $errors); ?>

			<select name="defaultCharacterSet">
				<option<?php echo $latin1CharacterSetSelected; ?>>latin1</option>
				<option<?php echo $unicodeCharacterSetSelected; ?>>utf8</option>
			</select>
		</td>
		<td valign="top">Specify the default character set for the MySQL database used by refbase. Note that <em>utf8</em> (Unicode) requires MySQL 4.1.x or greater, otherwise <em>latin1</em> (i.e., ISO-8859-1 West European) will be used by default.</td>
	</tr>
	<tr>
		<td valign="top">&nbsp;</td>
		<td valign="top" align="right">
			<input type="submit" name="submit" value="Install">
		</td>
		<td valign="top"><span class="warning">CAUTION:</span> Note that, if there's already an existing refbase database with the name specified in <em>$databaseName</em>, clicking the <em>Install</em> button will overwrite ALL data in that database! If you'd like to install the refbase tables into another existing database, you must ensure that there are no table name conflicts (<a href="http://install.refbase.net/" target="top" title="howto install refbase over an existing database">more info</a>).</td>
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


		// Validate the 'pathToMYSQL' field:
		if (empty($formVars["pathToMYSQL"]))
			// The 'pathToMYSQL' field cannot be a null string
			$errors["pathToMYSQL"] = "This field cannot be blank:";

		elseif (ereg("[;|]", $formVars["pathToMYSQL"]))
			// For security reasons, the 'pathToMYSQL' field cannot contain the characters ';' or '|' (which would tie multiple shell commands together)
			$errors["pathToMYSQL"] = "Due to security reasons this field cannot contain the characters ';' or '|':";

		elseif (is_dir($formVars["pathToMYSQL"]))
			// Check if the specified path resolves to a directory
			$errors["pathToMYSQL"] = "You cannot specify a directory! Please give the path to the mysql command:";

		elseif (!is_readable($formVars["pathToMYSQL"]))
			// Check if the specified path resolves to the mysql application
			$errors["pathToMYSQL"] = "Your path specification is invalid (command not found):";

//		Note: Currently, the checks for whether the function is executable or whether it is mysql have been commented out,
//			  since they don't seem to work on windows! (see <http://sourceforge.net/forum/forum.php?thread_id=1021143&forum_id=218758>)

//		elseif (!is_executable($formVars["pathToMYSQL"]))
//			// Check if the given file is executable
//			$errors["pathToMYSQL"] = "This file does not appear to be an executable command:";

//		elseif (!ereg("(^|.*/)mysql$", $formVars["pathToMYSQL"]))
//			// Make sure that the given file is 'mysql'
//			$errors["pathToMYSQL"] = "This does not appear to be the 'mysql' command line interpreter:";


		// Validate the 'databaseStructureFile' field:
		if (empty($formVars["databaseStructureFile"]))
			// The 'databaseStructureFile' field cannot be a null string
			$errors["databaseStructureFile"] = "This field cannot be blank:";

		elseif (ereg("[;|]", $formVars["databaseStructureFile"]))
			// For security reasons, the 'databaseStructureFile' field cannot contain the characters ';' or '|' (which would tie multiple shell commands together)
			$errors["databaseStructureFile"] = "Due to security reasons this field cannot contain the characters ';' or '|':";

		elseif (is_dir($formVars["databaseStructureFile"]))
			// Check if the specified path resolves to a directory
			$errors["databaseStructureFile"] = "You cannot specify a directory! Please give the path to the database structure file:";

		elseif (!is_readable($formVars["databaseStructureFile"]))
			// Check if the specified path resolves to the database structure file
			$errors["databaseStructureFile"] = "Your path specification is invalid (file not found):";


		// Validate the 'pathToBibutils' field:
		if (!empty($formVars["pathToBibutils"])) // we'll only validate the 'pathToBibutils' field if it isn't empty (installation of Bibutils is optional)
		{
			if (ereg("[;|]", $formVars["pathToBibutils"]))
				// For security reasons, the 'pathToBibutils' field cannot contain the characters ';' or '|' (which would tie multiple shell commands together)
				$errors["pathToBibutils"] = "Due to security reasons this field cannot contain the characters ';' or '|':";
	
			elseif (!is_readable($formVars["pathToBibutils"]))
				// Check if the specified path resolves to an existing directory
				$errors["pathToBibutils"] = "Your path specification is invalid (directory not found):";
	
			elseif (!is_dir($formVars["pathToBibutils"]))
				// Check if the specified path resolves to a directory (and not a file)
				$errors["pathToBibutils"] = "You must specify a directory! Please give the path to the directory containing the Bibutils utilities:";
		}


		// Validate the 'defaultCharacterSet' field:
		// Note: Currently we're not generating an error & rooting back to the install form, if the user did choose 'utf8' but has some MySQL version < 4.1 installed.
		//       In this case, we'll simply ignore the setting and 'latin1' will be used by default.

		// --------------------------------------------------------------------

		// Now the script has finished the validation, check if there were any errors:
		if (count($errors) > 0)
		{
			// Write back session variables:
			saveSessionVariable("errors", $errors); // function 'saveSessionVariable()' is defined in 'include.inc.php'
			saveSessionVariable("formVars", $formVars);

			// There are errors. Relocate back to the installation form:
			header("Location: install.php");

			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}

		// --------------------------------------------------------------------

		// If we made it here, then the data is considered valid!

		// (1) Open the database connection and use the mysql database:
		if (!($connection = @ mysql_connect($hostName,$adminUserName,$adminPassword)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to connect to the host:", "");

		if (!(mysql_select_db($adminDatabaseName, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to connect to the database:", "");


		// First, check if we're a dealing with MySQL version 4.1.x or greater:
		// (MySQL 4.1.x is required if the refbase MySQL database/tables shall be installed using Unicode/UTF-8 as default character set)
		$queryCheckVersion = "SELECT VERSION()";

		// Run the version check query on the mysql database through the connection:
		if (!($result = @ mysql_query ($queryCheckVersion, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to query the MySQL version:", "");

		// Extract result:
		$row = mysql_fetch_row($result); // fetch the current row into the array $row (it'll be always *one* row, but anyhow)
		$mysqlVersionString = $row[0]; // extract the contents of the first (and only) row (returned version string will be something like "4.0.20-standard" etc.)
		$mysqlVersion = preg_replace("/^(\d+\.\d+).+/", "\\1", $mysqlVersionString); // extract main version number (e.g. "4.0") from version string

		// --------------------------------------------------------------------

		// Prepare the install queries and proceed with the actual installation procedure:

		// Build the database queries required for installation:
		$queryGrantStatement = "GRANT SELECT,INSERT,UPDATE,DELETE ON " . $databaseName . ".* TO " . quote_smart($username) . "@" . quote_smart($hostName) . " IDENTIFIED BY " . quote_smart($password);

		$queryCreateDB = "CREATE DATABASE IF NOT EXISTS " . $databaseName; // by default, 'latin1' will be used as default character set

		if ($mysqlVersion >= 4.1) // if MySQL 4.1.x (or greater) is installed...
		{
			$queryCreateDB = $queryCreateDB . " DEFAULT CHARACTER SET " . $defaultCharacterSet; // ...add the default character set chosen by the user

			if ($defaultCharacterSet == "utf8") // ...in case of UTF-8, adjust the path to the default database structure file if necessary
				if ($databaseStructureFile == "./install.sql")
					$databaseStructureFile = "./install_utf8.sql";
		}

		if (!empty($pathToBibutils)) // we'll only update the Bibutils path if '$pathToBibutils' isn't empty (installation of Bibutils is optional)
			$queryUpdateDependsTable = "UPDATE " . $databaseName . "." . $tableDepends . " SET depends_path = " . quote_smart($pathToBibutils) . " WHERE depends_external = \"bibutils\""; // update the Bibutils path spec
		else // we set the 'depends_enabled' field in table 'depends' to 'false' to indicate that Bibutils isn't installed
			$queryUpdateDependsTable = "UPDATE " . $databaseName . "." . $tableDepends . " SET depends_enabled = \"false\" WHERE depends_external = \"bibutils\""; // disable Bibutils functionality

		// (2) Run the INSTALL queries on the mysql database through the connection:
		if (!($result = @ mysql_query ($queryGrantStatement, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to query the database:", "");

		if (!($result = @ mysql_query ($queryCreateDB, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to query the database:", "");

		// IMPORT the literature database structure from file:
		exec($pathToMYSQL . " -h " . $hostName . " -u " . $adminUserName . " -p" . $adminPassword . " --database=" . $databaseName . " < " . $databaseStructureFile . " 2>&1", $resultArray);

		// User note from <http://de2.php.net/manual/en/ref.exec.php> regarding the use of PHP's 'exec()' command:
		// From 'eremy at ntb dot co dot nz' (28-Sep-2003 03:18):
		// If an error occurs in the code you're trying to exec(), it can be challenging to figure out what's going
		// wrong, since php echoes back the stdout stream rather than the stderr stream where all the useful error
		// reporting's done. The solution is to add the code "2>&1" to the end of your shell command, which redirects
		// stderr to stdout, which you can then easily print using something like print `shellcommand 2>&1`.

		// run the UPDATE query on the depends table of the (just imported) literature database:
		if (!($result = @ mysql_query ($queryUpdateDependsTable, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to query the database:", "");

		// (5) Close the database connection:
		if (!(mysql_close($connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to disconnect from the database:", "");

		$resultLines = ""; // initialize variable

		// Read out the execution result array:
		if (!empty($resultArray)) // if there were any execution errors
		{
			reset($resultArray); // reset the internal array pointer to the first element
			while (list ($key, $val) = each ($resultArray))
				$resultLines .= "\n" . trim($val); // append each of the array elements to a string
		}

		// --------------------------------------------------------------------

		// Provide a feedback page:

		// If there's no stored message available:
		if (!isset($_SESSION['HeaderString'])) // provide one of the default messages:
		{
			if (!empty($resultArray)) // if there were any execution errors
				$HeaderString = "The following error occurred while trying to import the SQL data into the database:";
			else // assume that the installation was successful
				$HeaderString = "<b><span class=\"ok\">Installation of the Web Reference Database was successful!</span></b>";
		}
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

		// Show the login status:
		showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

		// DISPLAY header:
		// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
		displayHTMLhead(encodeHTML($officialDatabaseName) . " -- Installation Feedback", "index,follow", "Installation feedback for the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
		showPageHeader($HeaderString);

		// Start a <table>:
?>

<table align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds the installation feedback info"><?php

		if (!empty($resultArray)) // if there were any execution errors:
		{
?>

	<tr>
		<td valign="top"><b>Error:</b></td>
		<td><?php echo encodeHTML($resultLines); ?></td>
	</tr>
	<tr>
		<td valign="top">&nbsp;</td>
		<td>
			<b>Please make sure that you've specified the correct path to the MySQL database structure file!</b>
		</td>
	</tr>
	<tr>
		<td valign="top">&nbsp;</td>
		<td>
			<a href="install.php">Go Back</a>
		</td>
	</tr><?php

		}
		else // no execution errors -> inform the user about successful database installation:
		{
?>

	<tr>
		<td colspan="2"><h3>Welcome to refbase!</h3></td>
	</tr>
	<tr>
		<td valign="top"><b>Important Note:</b></td>
		<td>
			The <em>install.php</em> script is only provided for installation purposes and is not needed anymore. Due to security considerations you should <span class="warning">remove this script</span> from your web directory NOW!!
		</td>
	</tr>
	<tr>
		<td valign="top"><b>Setup users:</b></td>
		<td>
			Here's how to setup the admin user account for your newly created literature database:
			<ul type="circle">
				<li>Goto <a href="index.php" target="_blank" title="Open the main page in a new window"><?php echo encodeHTML($officialDatabaseName); ?></a></li>
				<li>Login with email address = <em>user@refbase.net</em> &amp; password = <em>start</em></li>
				<li>Click on <em>Add User</em> and enter the name, institutional abbreviation, email address and password of the admin user</li>
				<li>Open the file <em>ini.inc.php</em> in a text editor and change the value of the <em>$adminLoginEmail</em> variable to the email address you've specified for the admin user</li>
				<li>Log out, then login again using the email address and password of your newly created admin account</li>
			</ul>
			If you want to add additional users use the <em>Add User</em> link and enter the user's name, institutional abbreviation, email address and password.
		</td>
	</tr>
	<tr>
		<td valign="top"><b>Configure refbase:</b></td>
		<td>
			In order to customize your literature database, please open again <em>ini.inc.php</em> in a text editor. This include file contains variables that are common to all scripts and whose values can/must be adopted to your needs. Please see the comments within the file for further information.
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
