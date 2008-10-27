<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/include.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    16-Apr-02, 10:54
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This file contains important
	// functions that are shared
	// between all scripts.


	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'initialize/ini.inc.php'; // include common variables

	// include transliteration tables:
	include 'includes/transtab_unicode_ascii.inc.php'; // include unicode -> ascii transliteration table
	include 'includes/transtab_latin1_ascii.inc.php'; // include latin1 -> ascii transliteration table
	include 'includes/transtab_unicode_latin1.inc.php'; // include unicode -> latin1 transliteration table
	include 'includes/transtab_unicode_refbase.inc.php'; // include unicode -> refbase transliteration table

	if ($contentTypeCharset == "UTF-8") // variable '$contentTypeCharset' is defined in 'ini.inc.php'
		include_once 'includes/transtab_unicode_charset.inc.php'; // include unicode character case conversion tables
	else // we assume "ISO-8859-1" by default
		include_once 'includes/transtab_latin1_charset.inc.php'; // include latin1 character case conversion tables

	// --------------------------------------------------------------------

	// Untaint user data:
	function clean($input, $maxlength)
	{
		$input = substr($input, 0, $maxlength);
		$input = EscapeShellCmd($input);
		return ($input);
	}

	// --------------------------------------------------------------------

	// Start a session:
	function start_session($updateUserFormatsStylesTypesPermissions)
	{
		global $loginEmail;
		global $loginUserID;
		global $loginFirstName;
		global $loginLastName;
		global $abbrevInstitution;
		global $lastLogin;
		global $referer; // '$referer' is made globally available from within this function

		global $connection;

		// Initialize the session:
		if (!isset($_SESSION["sessionID"]))
		{
			// Ensure that cookies are enabled:
			if (ini_get('session.use_cookies') == 0) // if 'session.use_cookies' is OFF for the current directory
				ini_set('session.use_cookies', 1); // enable storage of sessions within cookies

			session_start();

			$sessionID = session_id(); // get the current session ID

			if (!empty($sessionID))
				saveSessionVariable("sessionID", $sessionID);
		}

		// Set the system's locale information:
		list($systemLocaleCollate, $systemLocaleCType) = setSystemLocale();

		// Get the MySQL version and save it to a session variable:
		// Note: we only check for the MySQL version if a connection has been established already. Otherwise, a non-existing MySQL user
		//       (or incorrect MySQL pwd) would prevent 'install.php' or 'error.php' from loading correctly when setting up a new refbase database.
		if (!isset($_SESSION['mysqlVersion']) AND isset($connection))
		{
			$mysqlVersion = getMySQLversion();
			saveSessionVariable("mysqlVersion", $mysqlVersion);
		}

		// Extract session variables (only necessary if register globals is OFF!):
		if (isset($_SESSION['loginEmail']))
		{
			$loginEmail = $_SESSION['loginEmail'];
			$loginUserID = $_SESSION['loginUserID'];
			$loginFirstName = $_SESSION['loginFirstName'];
			$loginLastName = $_SESSION['loginLastName'];
			$abbrevInstitution = $_SESSION['abbrevInstitution'];
			$lastLogin = $_SESSION['lastLogin'];
		}
		elseif ($updateUserFormatsStylesTypesPermissions)
			// if the user isn't logged in we set the available export formats, citation styles, document types and permissions to
			// the defaults which are specified in the 'formats', 'styles', 'types' and 'user_permissions' tables for 'user_id = 0'.
			// (a 'user_id' of zero is used within these tables to indicate the default settings if the user isn't logged in)
			// NOTE: As an exception, for anyone who isn't logged in, we don't load the default number of records from option
			//       'records_per_page' in table 'user_options', but instead use the value given in variable '$defaultNumberOfRecords'
			//       in 'ini.inc.php'. Similarly, if the user isn't logged in, the list of "main fields" is taken from variable
			//       '$defaultMainFields' in 'ini.inc.php' and not from option 'main_fields' in table 'user_options.
		{
			// Get all export formats that were selected by the admin to be visible if a user isn't logged in
			// and (if some formats were found) save them as semicolon-delimited string to the session variable 'user_export_formats':
			getVisibleUserFormatsStylesTypes(0, "format", "export");

			// Get all citation formats that were selected by the admin to be visible if a user isn't logged in
			// and (if some formats were found) save them as semicolon-delimited string to the session variable 'user_cite_formats':
			getVisibleUserFormatsStylesTypes(0, "format", "cite");

			// Get all citation styles that were selected by the admin to be visible if a user isn't logged in
			// and (if some styles were found) save them as semicolon-delimited string to the session variable 'user_styles':
			getVisibleUserFormatsStylesTypes(0, "style", "");

			// Get all document types that were selected by the admin to be visible if a user isn't logged in
			// and (if some types were found) save them as semicolon-delimited string to the session variable 'user_types':
			getVisibleUserFormatsStylesTypes(0, "type", "");

			// Get the user permissions for the current user
			// and save all allowed user actions as semicolon-delimited string to the session variable 'user_permissions':
			getPermissions(0, "user", true);

			// Get the default view for the current user
			// and save it to the session variable 'userDefaultView':
			getDefaultView(0);

			// Get the default number of records per page preferred by the current user
			// and save it to the session variable 'userRecordsPerPage':
			getDefaultNumberOfRecords(0);

			// Get the list of "main fields" for the current user
			// and save the list of fields as comma-delimited string to the session variable 'userMainFields':
			getMainFields(0);
		}

		// Set the referrer:
		if (isset($_REQUEST['referer']) AND !empty($_REQUEST['referer']))
			$referer = $_REQUEST['referer']; // get the referring URL from the superglobal '$_REQUEST' variable (if any)

		elseif (isset($_SESSION['referer']) AND !empty($_SESSION['referer']))
		{
			$referer = $_SESSION['referer']; // get the referring URL from the superglobal '$_SESSION' variable (if any)
			deleteSessionVariable("referer");
		}

		elseif (isset($_SERVER['HTTP_REFERER']) AND !empty($_SERVER['HTTP_REFERER']))
			$referer = $_SERVER['HTTP_REFERER']; // get the referring URL from the superglobal '$_SERVER' variable (if any)

		else // as an example, the referrer won't be set if a user clicked on a URL of type 'show.php?record=12345' within an email announcement
			$referer = "index.php"; // if all other attempts fail, we'll re-direct to the main page
	}

	// --------------------------------------------------------------------

	// Create a new session variable:
	function saveSessionVariable($sessionVariableName, $sessionVariableContents)
	{
		// since PHP 4.1.0 or greater, adding variables directly to the '$_SESSION' variable
		//  will register a session variable regardless whether register globals is ON or OFF!
		$_SESSION[$sessionVariableName] = $sessionVariableContents;
	}

	// --------------------------------------------------------------------

	// Remove a session variable:
	function deleteSessionVariable($sessionVariableName)
	{
		if (ini_get('register_globals') == 1) // register globals is ON for the current directory
			session_unregister($sessionVariableName); // clear the specified session variable
		else // register globals is OFF for the current directory
			unset($_SESSION[$sessionVariableName]); // clear the specified session variable
	}

	// --------------------------------------------------------------------

	// Connect to the MySQL database:
	// TODO: I18n
	function connectToMySQLDatabase()
	{
		global $hostName; // these variables are specified in 'db.inc.php'
		global $username;
		global $password;
		global $databaseName;

		global $contentTypeCharset; // defined in 'ini.inc.php'

		global $connection;

		// If a connection parameter is not available, then use our own connection to avoid any locking problems
		if (!isset($connection))
		{
			// (1) OPEN the database connection:
			//      (variables are set by include file 'db.inc.php'!)
			if (!($connection = @ mysql_connect($hostName, $username, $password)))
				if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
					showErrorMsg("The following error occurred while trying to connect to the host:");

			// (2) Set the connection character set (if connected to MySQL 4.1.x or greater):
			//     more info at <http://dev.mysql.com/doc/refman/5.1/en/charset-connection.html>
			if (isset($_SESSION['mysqlVersion']) AND ereg("^(4\.1|5)", $_SESSION['mysqlVersion']))
			{
				if ($contentTypeCharset == "UTF-8")
					queryMySQLDatabase("SET NAMES utf8"); // set the character set for this connection to 'utf8'
				else
					queryMySQLDatabase("SET NAMES latin1"); // by default, we establish a 'latin1' connection
			}

			// (3) SELECT the database:
			//      (variables are set by include file 'db.inc.php'!)
			if (!(mysql_select_db($databaseName, $connection)))
				if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
					showErrorMsg("The following error occurred while trying to connect to the database:");
		}
	}

	// --------------------------------------------------------------------

	// Query the MySQL database:
	// TODO: I18n
	function queryMySQLDatabase($query)
	{
		global $connection;
		global $client;

		// (3) RUN the query on the database through the connection:
		if (!($result = @ mysql_query($query, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			{
				if (eregi("^cli", $client)) // if the query originated from a command line client such as the "refbase" CLI client ("cli-refbase-1.0")
					// note that we also HTML encode the query for CLI clients since a malicious user could use the client parameter to perform a cross-site scripting (XSS) attack
					showErrorMsg("Your query:\n\n" . encodeHTML($query) . "\n\ncaused the following error:");
				else
					showErrorMsg("Your query:\n<br>\n<br>\n<code>" . encodeHTML($query) . "</code>\n<br>\n<br>\n caused the following error:");
			}

		return $result;
	}

	// --------------------------------------------------------------------

	// Disconnect from the MySQL database:
	// TODO: I18n
	function disconnectFromMySQLDatabase()
	{
		global $connection;

		if (isset($connection))
			// (5) CLOSE the database connection:
			if (!(mysql_close($connection)))
				if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
					showErrorMsg("The following error occurred while trying to disconnect from the database:");
	}

	// --------------------------------------------------------------------

	// Get MySQL version:
	function getMySQLversion()
	{
		connectToMySQLDatabase();

		// CONSTRUCT SQL QUERY:
		$query = "SELECT VERSION()";

		$result = queryMySQLDatabase($query); // RUN the query on the database through the connection

		$row = mysql_fetch_row($result); // fetch the current row into the array $row (it'll be always *one* row, but anyhow)
		$mysqlVersionString = $row[0]; // extract the contents of the first (and only) row (returned version string will be something like "4.0.20-standard" etc.)
		$mysqlVersion = preg_replace("/^(\d+\.\d+).+/", "\\1", $mysqlVersionString); // extract main version number (e.g. "4.0") from version string

		return $mysqlVersion;
	}

	// --------------------------------------------------------------------

	// Get MySQL field info:
	// (i.e. fetch field (column) information from a given result resource; returns the
	//  field property given in '$propertyName', else an array of all field properties;
	//  see <http://www.php.net/mysql_fetch_field>)
	function getMySQLFieldInfo($result, $fieldOffset, $propertyName = "")
	{
		$fieldInfoArray = array();

		// Get field (column) metadata:
		$fieldInfo = mysql_fetch_field($result, $fieldOffset); // returns an object containing the field information

		// Copy object properties to an array:
		$fieldInfoArray["name"]         = $fieldInfo->name;         // column name
		$fieldInfoArray["table"]        = $fieldInfo->table;        // name of the table the column belongs to
		$fieldInfoArray["type"]         = $fieldInfo->type;         // the type of the column
		$fieldInfoArray["def"]          = $fieldInfo->def;          // default value of the column
		$fieldInfoArray["max_length"]   = $fieldInfo->max_length;   // maximum length of the column
		$fieldInfoArray["not_null"]     = $fieldInfo->not_null;     // 1 if the column cannot be NULL
		$fieldInfoArray["primary_key"]  = $fieldInfo->primary_key;  // 1 if the column is a primary key
		$fieldInfoArray["unique_key"]   = $fieldInfo->unique_key;   // 1 if the column is a unique key
		$fieldInfoArray["multiple_key"] = $fieldInfo->multiple_key; // 1 if the column is a non-unique key
		$fieldInfoArray["numeric"]      = $fieldInfo->numeric;      // 1 if the column is numeric
		$fieldInfoArray["blob"]         = $fieldInfo->blob;         // 1 if the column is a BLOB
		$fieldInfoArray["unsigned"]     = $fieldInfo->unsigned;     // 1 if the column is unsigned
		$fieldInfoArray["zerofill"]     = $fieldInfo->zerofill;     // 1 if the column is zero-filled


		if (!empty($propertyName) AND isset($fieldInfoArray[$propertyName]))
			return $fieldInfoArray[$propertyName];
		else
			return $fieldInfoArray;
	}

	// --------------------------------------------------------------------

	// Find out how many rows are available and (if there were rows found) seek to the current offset:
	// Note that this function will also (re-)assign values to the variables '$rowOffset', '$showRows',
	// '$rowsFound', '$previousOffset', '$nextOffset' and '$showMaxRow'.
	function seekInMySQLResultsToOffset($result, $rowOffset, $showRows, $displayType, $citeType)
	{
		// Find out how many rows are available:
		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows found ...
		{
			// ... setup variables in order to facilitate "previous" & "next" browsing:
			// a) Set '$rowOffset' to zero if not previously defined, or if a wrong number (<=0) was given
			if (empty($rowOffset) || ($rowOffset <= 0) || ((($displayType != "Export") AND !($displayType == "Cite" AND (!eregi("^html$", $citeType)))) && ($showRows >= $rowsFound))) // the third condition is only necessary if '$rowOffset' gets embedded within the 'displayOptions' form (see function 'buildDisplayOptionsElements()' in 'include.inc.php')
				$rowOffset = 0;

			// Adjust the '$showRows' value if not previously defined, or if a wrong number (<=0 or float) was given
			if (empty($showRows) || ($showRows <= 0) || !ereg("^[0-9]+$", $showRows))
				$showRows = $_SESSION['userRecordsPerPage']; // get the default number of records per page preferred by the current user

			// Adjust '$rowOffset' if it's value exceeds the number of rows found:
			if ($rowOffset > ($rowsFound - 1))
			{
				if ($rowsFound > $showRows)
					$rowOffset = ($rowsFound - $showRows); // start display at first record of last page to be displayed
				else
					$rowOffset = 0; // start display at the very first record
			}


			if (($displayType != "Export") AND !($displayType == "Cite" AND (!eregi("^html$", $citeType)))) // we have to exclude '$displayType=Export' here since, for export, '$rowOffset' must always point to the first row number in the result set that should be returned
			{
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
			}

			// b) The "Previous" page begins at the current offset LESS the number of rows per page
			$previousOffset = $rowOffset - $showRows;

			// c) The "Next" page begins at the current offset PLUS the number of rows per page
			$nextOffset = $rowOffset + $showRows;

			// d) Seek to the current offset
			mysql_data_seek($result, $rowOffset); // move internal result pointer to the row number given in '$rowOffset'
		}
		else // set variables to zero in order to prevent 'Undefined variable...' messages when nothing was found ('$rowsFound = 0'):
		{
			$rowOffset = 0;
			$previousOffset = 0;
			$nextOffset = 0;
		}

		// Calculate the maximum result number on each page ('$showMaxRow' is required as parameter to the 'displayDetails()' function)
		if (($rowOffset + $showRows) < $rowsFound)
			$showMaxRow = ($rowOffset + $showRows); // maximum result number on each page
		else
			$showMaxRow = $rowsFound; // for the last results page, correct the maximum result number if necessary

		return array($result, $rowOffset, $showRows, $rowsFound, $previousOffset, $nextOffset, $showMaxRow);
	}

	// --------------------------------------------------------------------

	// Show error (prepares error output and redirects it to 'error.php' which displays the error message):
	// TODO: I18n
	function showErrorMsg($headerMsg)
	{
		global $client;

		$errorNo = mysql_errno();
		$errorMsg = mysql_error();

		if (eregi("^cli", $client)) // if the query originated from a command line client such as the "refbase" CLI client ("cli-refbase-1.0")
			// note that we also HTML encode the '$errorMsg' for CLI clients since a malicious user could use the client parameter to perform a cross-site scripting (XSS) attack
			echo $headerMsg . "\n\nError " . $errorNo . ": " . encodeHTML($errorMsg) . "\n\n";
		else
			// in case of regular HTML output, '$errorMsg' gets HTML encoded in 'error.php'
			header("Location: error.php?errorNo=" . $errorNo . "&errorMsg=" . rawurlencode($errorMsg) . "&headerMsg=" . rawurlencode($headerMsg));

		exit;
	}

	// --------------------------------------------------------------------

	// Generate and return a message, and optionally save the message to a session variable:
	// Following optional variables can be passed with the '$message' and will be used for non-CLI clients:
	// - '$class' defines the name of the CSS class of the span element that encloses the message
	// - '$flavour' is the (valid!) name of an HTML phrase element (such as "strong" or "em") that's wrapped around the message
	// - '$sessionVariable' is the name of the session variable (such as "HeaderString") to which the message shall be saved
	// - '$prefix' is a string that's added at the beginning of the generated message string
	// - '$suffix' is a string that's appended at the end of the generated message string
	function returnMsg($message, $class = "", $flavour = "", $sessionVariable = "", $prefix = "", $suffix = "")
	{
		global $client;

		if (eregi("^cli", $client)) // if the query originated from a command line client such as the "refbase" CLI client ("cli-refbase-1.0")
		{
			$fullMsg = $message . "\n\n"; // for CLI clients, we just echo the message text
			echo $fullMsg;
		}
		else // return an HTML-formatted message:
		{
			$fullMsg = $prefix;

			if (!empty($flavour))
				$fullMsg .= '<' . $flavour . '>';

			if (!empty($class))
				$fullMsg .= '<span class="' . $class . '">' . $message . '</span>';
			else
				$fullMsg .= $message;

			if (!empty($flavour))
				$fullMsg .= '</' . $flavour . '>';

			$fullMsg .= $suffix;

			if (!empty($sessionVariable))
				saveSessionVariable($sessionVariable, $fullMsg); // write message to session variable
		}

		return $fullMsg;
	}

	// --------------------------------------------------------------------

	// Show whether the user is logged in or not:
	// TODO: I18n
	function showLogin()
	{
		global $loginEmail;
		global $loginWelcomeMsg;
		global $loginFirstName;
		global $loginLastName;
		global $abbrevInstitution;
		global $loginUserID;
		global $loginStatus;
		global $loginLinks;
		global $adminLoginEmail; // ('$adminLoginEmail' is specified in 'ini.inc.php')

		global $loc; // '$loc' is made globally available in 'core.php'


//		$referer = $_SERVER["REQUEST_URI"]; // 'REQUEST_URI' does only seem to work for GET requests (but not for POST requests!) ?:-/
		// so, as a workaround, we build an appropriate query string from scratch (which will also work for POST requests):

		// --- BEGIN WORKAROUND ---
		global $formType;
		global $displayType;
		global $queryURL;
		global $showQuery;
		global $showLinks;
		global $showRows;
		global $rowOffset;

		global $citeStyle;
		global $citeOrder;
		global $orderBy;

		global $recordAction;
		global $serialNo;
		global $headerMsg;

		global $errorNo;
		global $errorMsg;

		// Get the path to the currently executing script, relative to the document root:
		$scriptURL = scriptURL();

		// Extract checkbox variable values from the request:
		if (isset($_REQUEST['marked']))
			$recordSerialsArray = $_REQUEST['marked']; // extract the values of all checked checkboxes (i.e., the serials of all selected records)
		else
			$recordSerialsArray = "";
		$recordSerialsString = ""; // initialize variable
		// join array elements:
		if (!empty($recordSerialsArray)) // the user did check some checkboxes
			$recordSerialsString = implode("&marked[]=", $recordSerialsArray); // prefix each record serial (except the first one) with "&marked[]="
		$recordSerialsString = "&marked[]=" . $recordSerialsString; // prefix also the very first record serial with "&marked[]="

		// based on the refering script we adjust the parameters that get included in the link:
		if (eregi("/(index|install|update|simple_search|advanced_search|sql_search|library_search|duplicate_manager|duplicate_search|opensearch|query_history|extract|users|user_details|user_receipt)\.php", $scriptURL))
			$referer = $scriptURL; // we don't need to provide any parameters if the user clicked login/logout on the main page, the install/update page or any of the search pages (we just need
									// to re-locate back to these pages after successful login/logout). Logout on 'install.php', 'users.php', 'user_details.php' or 'user_receipt.php' will redirect to 'index.php'.

		elseif (eregi("/user_options\.php", $scriptURL))
			$referer = $scriptURL . "?" . "userID=" . $loginUserID;

		elseif (eregi("/(record|receipt)\.php", $scriptURL))
			$referer = $scriptURL . "?" . "recordAction=" . $recordAction . "&serialNo=" . $serialNo . "&headerMsg=" . rawurlencode($headerMsg);

		elseif (eregi("/error\.php", $scriptURL))
			$referer = $scriptURL . "?" . "errorNo=" . $errorNo . "&errorMsg=" . rawurlencode($errorMsg) . "&headerMsg=" . rawurlencode($headerMsg);

		else
			$referer = $scriptURL . "?" . "formType=" . "sqlSearch" . "&submit=" . $displayType . "&headerMsg=" . rawurlencode($headerMsg) . "&sqlQuery=" . $queryURL . "&showQuery=" . $showQuery . "&showLinks=" . $showLinks . "&showRows=" . $showRows . "&rowOffset=" . $rowOffset . $recordSerialsString . "&citeStyle=" . rawurlencode($citeStyle) . "&citeOrder=" . $citeOrder . "&orderBy=" . rawurlencode($orderBy);
		// --- END WORKAROUND -----


		// Is the user logged in?
		if (isset($_SESSION['loginEmail']))
			{
				$loginStatus = $loc["Welcome"];

				$loginWelcomeMsg = "<em>" . encodeHTML($loginFirstName) . " " . encodeHTML($loginLastName) . "</em>!";

				if ($loginEmail == $adminLoginEmail)
					$loginStatus .= " <span class=\"warning\">" . $loc["Admin"] . "</span>";

				$loginLinks = "";
				if ($loginEmail == $adminLoginEmail) // if the admin is logged in, add the 'Add User' & 'Manage Users' links:
				{
					$loginLinks .= "<a href=\"user_details.php\" title=\"add a user to the database\">Add User</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
					$loginLinks .= "<a href=\"users.php\" title=\"manage user data\">Manage Users</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
				}
				else // if a normal user is logged in, we add the 'My Refs' and 'Options' links instead:
				{
					$loginLinks .= "<a href=\"search.php?formType=myRefsSearch&amp;showQuery=0&amp;showLinks=1&amp;myRefsRadio=1\"" . addAccessKey("attribute", "my_refs") . " title=\"" . $loc["LinkTitle_MyRefs"] . addAccessKey("title", "my_refs") . "\">" . $loc["MyRefs"] . "</a>&nbsp;&nbsp;|&nbsp;&nbsp;";

					if (isset($_SESSION['user_permissions']) AND ereg("allow_modify_options", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_modify_options'...
						// ... include a link to 'user_receipt.php':
						$loginLinks .= "<a href=\"user_receipt.php?userID=" . $loginUserID . "\"" . addAccessKey("attribute", "my_opt") . " title=\"" . $loc["LinkTitle_Options"] . addAccessKey("title", "my_opt") . "\">" . $loc["Options"] . "</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
				}
				$loginLinks .= "<a href=\"user_logout.php?referer=" . rawurlencode($referer) . "\"" . addAccessKey("attribute", "login") . " title=\"" . $loc["LinkTitle_Logout"] . addAccessKey("title", "login") . "\">" . $loc["Logout"] . "</a>";
			}
		else
			{
				if (eregi(".*(record|import[^.]*)\.php", $scriptURL))
					$loginStatus = "<span class=\"warning\">" . $loc["Warning_LoginToSubmitForm"] . "!</span>";
				else
					$loginStatus = "";

				$loginWelcomeMsg = "";

				$loginLinks = "<a href=\"user_login.php?referer=" . rawurlencode($referer) . "\"" . addAccessKey("attribute", "login") . " title=\"" . $loc["LinkTitle_Login"] . addAccessKey("title", "login") . "\">" . $loc["Login"] . "</a>";
			}

		// Although the '$referer' variable gets included as GET parameter above, we'll also save the variable as session variable:
		// (this should help re-directing to the correct page if a user called 'user_login/logout.php' manually, i.e., without parameters)
		saveSessionVariable("referer", $referer);
	}

	// --------------------------------------------------------------------

	// Enable 'accesskey' attribute for the specified link/form element:
	// '$type' must be either "attribute" or "title", and '$key' must be the
	// name of an array key from variable '$accessKeys' (in 'ini.inc.php')
	function addAccessKey($type, $key)
	{
		global $accessKeys; // defined in 'ini.inc.php'

		$accessKeyString = "";

		if (isset($accessKeys) AND (!empty($accessKeys[$key]) OR ($accessKeys[$key] == "0")))
		{
			if ($type == "attribute") // add 'accesskey' attribute (like ' accesskey="h"') to the specified link or form element
				$accessKeyString = " accesskey=\"" . $accessKeys[$key] . "\"";

			elseif ($type == "title") // add access key hint (like ' [ctrl-h]') to the title attribute value of the specified link or form element
				$accessKeyString = " [ctrl-" . $accessKeys[$key] . "]";
		}

		return $accessKeyString;
	}

	// --------------------------------------------------------------------

	// Get the 'user_id' for the record entry in table 'auth' whose email matches that in '$emailAddress':
	function getUserID($emailAddress)
	{
		global $tableAuth; // defined in 'db.inc.php'

		connectToMySQLDatabase();

		// CONSTRUCT SQL QUERY:
		$query = "SELECT user_id FROM $tableAuth WHERE email = " . quote_smart($emailAddress);

		$result = queryMySQLDatabase($query); // RUN the query on the database through the connection
		$row = mysql_fetch_array($result);

		return($row["user_id"]);
	}

	// --------------------------------------------------------------------

	// ADD RECORDS
	// Adds record(s) to the database (i.e., add one or more row entries to MySQL table 'refs'):
	// Notes: - the function will return the serial number(s) of all newly created record(s) in an array structure
	//        - structure of '$importDataArray' (sample values given in "..."):
	//          array(
	//                 ['type']     =>  "refbase" // mandatory; indicates the array format of the 'records' element (currently only "refbase" is recognized, a standardized "bibliophile" format may be provided later on)
	//                 ['version']  =>  "1.0" // mandatory; the version of the given array structure
	//                 ['records']  =>  array( // mandatory; array of arrays containing the records & field data that should be imported; the sub-array element key must correspond to a refbase database field name from table 'refs'
	//                                         [0] => array(                   // first record
	//                                                       [author] => "..." //  - contents of 'author' field
	//                                                       [title]  => "..." //  - contents of 'title' field
	//                                                       ...
	//                                                     )
	//                                         [1] => array(                   // second record
	//                                                       [author] => "..." //  - contents of 'author' field
	//                                                       [title]  => "..." //  - contents of 'title' field
	//                                                       ...
	//                                                     )
	//                                         ...
	//                                       )
	//                 ['creator']  =>  "http://refbase.net" // optional; the (preferably unique) name of the calling script/importer, use an URI if possible
	//                 ['author']   =>  "Matthias Steffens" // optional; the name of the person who developed the script/importer and/or who can be contacted in case of problems
	//                 ['contact']  =>  "refbase@extracts.de" // optional; the contact address of the person specified under 'author', use an email address if possible
	//                 ['options']  =>  array( // optional; array with settings that control the behaviour of the 'addRecords()' function, currently there's only one option:
	//                                         [prefix_call_number] => "true" // if "true", any 'call_number' string will be prefixed with the correct call number prefix of the currently logged-in user (e.g. 'IPÖ @ msteffens @ ')
	//                                       )
	//               )
	function addRecords($importDataArray)
	{
		global $loginUserID;
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		global $connection;

		connectToMySQLDatabase();

		$recognizedArrayFormatsAndVersions = array('refbase' => array("1.0")); // for each recognized format, this array lists its format identifier as element key and an array of known versions as element value

		$serialNumbersArray = array(); // initialize array variable which will hold the serial numbers of all imported records

		// Verify the structure of the '$importDataArray':
		if (!empty($importDataArray['type']) AND !empty($importDataArray['version']) AND !empty($importDataArray['records'])) // the array elements 'type', 'version' and 'records' are mandatory and must not be empty
		{
			// Currently, we only support the default "refbase" array structure in its initial version ("1.0") (support for other more generalized array formats may come later)
			if (($importDataArray['type'] == "refbase") AND (in_array($importDataArray['version'], $recognizedArrayFormatsAndVersions['refbase'])))
			{
				$recordsArray = $importDataArray['records']; // get the array of records that shall be imported

				// First, setup some required variables:
				// Get the current date (e.g. '2003-12-31'), time (e.g. '23:59:49') and user name & email address (e.g. 'Matthias Steffens (refbase@extracts.de)'):
				// note that we use the same time stamp for ALL imported records (so that users can easily identify all records belonging to one import action)
				list ($currentDate, $currentTime, $currentUser) = getCurrentDateTimeUser();

				// LOOP OVER EACH RECORD:
				foreach ($recordsArray as $recordData) // for each record...
				{
					// Initialize some variables (in order to avoid "undefined index" messages when the particular array elements are not available):
					if (isset($recordData['author']))
						$author = $recordData['author'];
					else
						$author = "";

					if (isset($recordData['pages']))
						$pages = $recordData['pages'];
					else
						$pages = "";

					if (isset($recordData['volume']))
						$volume = $recordData['volume'];
					else
						$volume = "";

					if (isset($recordData['series_volume']))
						$seriesVolume = $recordData['series_volume'];
					else
						$seriesVolume = "";

					// Assign correct values to the calculation fields 'first_author', 'author_count', 'first_page', 'volume_numeric' and 'series_volume_numeric':
					list ($firstAuthor, $authorCount, $firstPage, $volumeNumeric, $seriesVolumeNumeric) = generateCalculationFieldContent($author, $pages, $volume, $seriesVolume);

					// CONSTRUCT SQL QUERY:

					// INSERT - construct a query to add data as new record
					$queryRefs = ""; // note: we'll prefix "INSERT INTO $tableRefs SET " *after* we've parsed all array elements to trap the case that none of the array elements did contain any data

					if (!empty($recordData['author']))
						$queryRefs .= "author = " . quote_smart($recordData['author']) . ", ";

					if (!empty($firstAuthor))
						$queryRefs .= "first_author = " . quote_smart($firstAuthor) . ", ";

					if (!empty($authorCount))
						$queryRefs .= "author_count = " . quote_smart($authorCount) . ", ";

					if (!empty($recordData['title']))
						$queryRefs .= "title = " . quote_smart($recordData['title']) . ", ";

					if (!empty($recordData['year']))
						$queryRefs .= "year = " . quote_smart($recordData['year']) . ", ";

					if (!empty($recordData['publication']))
						$queryRefs .= "publication = " . quote_smart($recordData['publication']) . ", ";

					if (!empty($recordData['abbrev_journal']))
						$queryRefs .= "abbrev_journal = " . quote_smart($recordData['abbrev_journal']) . ", ";

					if (!empty($recordData['volume']))
						$queryRefs .= "volume = " . quote_smart($recordData['volume']) . ", ";

					if (!empty($volumeNumeric))
						$queryRefs .= "volume_numeric = " . quote_smart($volumeNumeric) . ", ";

					if (!empty($recordData['issue']))
						$queryRefs .= "issue = " . quote_smart($recordData['issue']) . ", ";

					if (!empty($recordData['pages']))
						$queryRefs .= "pages = " . quote_smart($recordData['pages']) . ", ";

					if (!empty($firstPage))
						$queryRefs .= "first_page = " . quote_smart($firstPage) . ", ";

					if (!empty($recordData['address']))
						$queryRefs .= "address = " . quote_smart($recordData['address']) . ", ";

					if (!empty($recordData['corporate_author']))
						$queryRefs .= "corporate_author = " . quote_smart($recordData['corporate_author']) . ", ";

					if (!empty($recordData['keywords']))
						$queryRefs .= "keywords = " . quote_smart($recordData['keywords']) . ", ";

					if (!empty($recordData['abstract']))
						$queryRefs .= "abstract = " . quote_smart($recordData['abstract']) . ", ";

					if (!empty($recordData['publisher']))
						$queryRefs .= "publisher = " . quote_smart($recordData['publisher']) . ", ";

					if (!empty($recordData['place']))
						$queryRefs .= "place = " . quote_smart($recordData['place']) . ", ";

					if (!empty($recordData['editor']))
						$queryRefs .= "editor = " . quote_smart($recordData['editor']) . ", ";

					if (!empty($recordData['language']))
						$queryRefs .= "language = " . quote_smart($recordData['language']) . ", ";

					if (!empty($recordData['summary_language']))
						$queryRefs .= "summary_language = " . quote_smart($recordData['summary_language']) . ", ";

					if (!empty($recordData['orig_title']))
						$queryRefs .= "orig_title = " . quote_smart($recordData['orig_title']) . ", ";

					if (!empty($recordData['series_editor']))
						$queryRefs .= "series_editor = " . quote_smart($recordData['series_editor']) . ", ";

					if (!empty($recordData['series_title']))
						$queryRefs .= "series_title = " . quote_smart($recordData['series_title']) . ", ";

					if (!empty($recordData['abbrev_series_title']))
						$queryRefs .= "abbrev_series_title = " . quote_smart($recordData['abbrev_series_title']) . ", ";

					if (!empty($recordData['series_volume']))
						$queryRefs .= "series_volume = " . quote_smart($recordData['series_volume']) . ", ";

					if (!empty($seriesVolumeNumeric))
						$queryRefs .= "series_volume_numeric = " . quote_smart($seriesVolumeNumeric) . ", ";

					if (!empty($recordData['series_issue']))
						$queryRefs .= "series_issue = " . quote_smart($recordData['series_issue']) . ", ";

					if (!empty($recordData['edition']))
						$queryRefs .= "edition = " . quote_smart($recordData['edition']) . ", ";

					if (!empty($recordData['issn']))
						$queryRefs .= "issn = " . quote_smart($recordData['issn']) . ", ";

					if (!empty($recordData['isbn']))
						$queryRefs .= "isbn = " . quote_smart($recordData['isbn']) . ", ";

					if (!empty($recordData['medium']))
						$queryRefs .= "medium = " . quote_smart($recordData['medium']) . ", ";

					if (!empty($recordData['area']))
						$queryRefs .= "area = " . quote_smart($recordData['area']) . ", ";

					if (!empty($recordData['expedition']))
						$queryRefs .= "expedition = " . quote_smart($recordData['expedition']) . ", ";

					if (!empty($recordData['conference']))
						$queryRefs .= "conference = " . quote_smart($recordData['conference']) . ", ";

					// the 'location' and 'call_number' fields are handled below

					if (!empty($recordData['approved']))
						$queryRefs .= "approved = " . quote_smart($recordData['approved']) . ", ";

					if (!empty($recordData['file']))
						$queryRefs .= "file = " . quote_smart($recordData['file']) . ", ";

					// the 'serial' field is handled below

					if (!empty($recordData['orig_record']))
						$queryRefs .= "orig_record = " . quote_smart($recordData['orig_record']) . ", ";

					if (!empty($recordData['type']))
						$queryRefs .= "type = " . quote_smart($recordData['type']) . ", ";

					if (!empty($recordData['thesis']))
						$queryRefs .= "thesis = " . quote_smart($recordData['thesis']) . ", ";

					if (!empty($recordData['notes']))
						$queryRefs .= "notes = " . quote_smart($recordData['notes']) . ", ";

					if (!empty($recordData['url']))
						$queryRefs .= "url = " . quote_smart($recordData['url']) . ", ";

					if (!empty($recordData['doi']))
						$queryRefs .= "doi = " . quote_smart($recordData['doi']) . ", ";

					if (!empty($recordData['contribution_id']))
						$queryRefs .= "contribution_id = " . quote_smart($recordData['contribution_id']) . ", ";

					if (!empty($recordData['online_publication']))
						$queryRefs .= "online_publication = " . quote_smart($recordData['online_publication']) . ", ";

					if (!empty($recordData['online_citation']))
						$queryRefs .= "online_citation = " . quote_smart($recordData['online_citation']) . ", ";


					if (!empty($queryRefs)) // go ahead, if some array elements did contain data
					{
						// we only honour the 'call_number' string if some other record data were passed as well:
						// 
						// if the 'prefix_call_number' option is set to "true", any 'call_number' string will be prefixed with
						// the correct call number prefix of the currently logged-in user (e.g. 'IPÖ @ msteffens @ '):
						if ((isset($_SESSION['loginEmail'])) AND (isset($importDataArray['options']['prefix_call_number'])) AND ($importDataArray['options']['prefix_call_number'] == "true"))
						{
							$callNumberPrefix = getCallNumberPrefix(); // build a correct call number prefix for the currently logged-in user (e.g. 'IPÖ @ msteffens')

							if (!empty($recordData['call_number']))
								$queryRefs .= "call_number = " . quote_smart($callNumberPrefix . " @ " . $recordData['call_number']) . ", "; // add call number prefix to 'call_number' string
							else
								$queryRefs .= "call_number = " . quote_smart($callNumberPrefix . " @ ") . ", "; // similar to the GUI behaviour, we'll also add a call number prefix if the 'call_number' string is empty
						}
						else
						{
							if (!empty($recordData['call_number']))
								$queryRefs .= "call_number = " . quote_smart($recordData['call_number']) . ", ";
						}

						// if no specific cite key exists in '$recordData', any existing 'call_number' string gets also copied to the
						// user-specific 'cite_key' field (which will ensure that this original call number/cite key is retained as
						// cite key upon export); however, note that (depending on the user's settings) the cite key may get modified
						// or regenerated by function 'generateCiteKey()' below
						if (isset($_SESSION['loginEmail']) AND !empty($recordData['call_number']) AND empty($recordData['cite_key']))
							$recordData['cite_key'] = $recordData['call_number'];

						// for the 'location' field, we accept input from the '$recordData',
						// but if no data were given, we'll add the currently logged-in user to the 'location' field:
						if (!empty($recordData['location']))
							$queryRefs .= "location = " . quote_smart($recordData['location']) . ", ";
						elseif (isset($_SESSION['loginEmail']))
							$queryRefs .= "location = " . quote_smart($currentUser) . ", ";

						$queryRefs .= "serial = NULL, "; // inserting 'NULL' into an auto_increment PRIMARY KEY attribute allocates the next available key value

						// we accept custom values for the *date/*time/*by fields if they are in correct format (*date: 'YYYY-MM-DD'; *time: 'HH:MM:SS'; *by: 'string'),
						// otherwise we'll use the current date & time as well as the currently logged-in user name & email address:
						if (!empty($recordData['created_by']))
							$queryRefs .= "created_by = " . quote_smart($recordData['created_by']) . ", ";
						elseif (isset($_SESSION['loginEmail']))
							$queryRefs .= "created_by = " . quote_smart($currentUser) . ", ";

						if (!empty($recordData['created_date']) AND preg_match("/^\d{4}-\d{2}-\d{2}$/", $recordData['created_date']))
							$queryRefs .= "created_date = " . quote_smart($recordData['created_date']) . ", ";
						else
							$queryRefs .= "created_date = " . quote_smart($currentDate) . ", ";

						if (!empty($recordData['created_time']) AND preg_match("/^\d{2}:\d{2}:\d{2}$/", $recordData['created_time']))
							$queryRefs .= "created_time = " . quote_smart($recordData['created_time']) . ", ";
						else
							$queryRefs .= "created_time = " . quote_smart($currentTime) . ", ";

						if (!empty($recordData['modified_by']))
							$queryRefs .= "modified_by = " . quote_smart($recordData['modified_by']) . ", ";
						elseif (isset($_SESSION['loginEmail']))
							$queryRefs .= "modified_by = " . quote_smart($currentUser) . ", ";

						if (!empty($recordData['modified_date']) AND preg_match("/^\d{4}-\d{2}-\d{2}$/", $recordData['modified_date']))
							$queryRefs .= "modified_date = " . quote_smart($recordData['modified_date']) . ", ";
						else
							$queryRefs .= "modified_date = " . quote_smart($currentDate) . ", ";

						if (!empty($recordData['modified_time']) AND preg_match("/^\d{2}:\d{2}:\d{2}$/", $recordData['modified_time']))
							$queryRefs .= "modified_time = " . quote_smart($recordData['modified_time']) . "";
						else
							$queryRefs .= "modified_time = " . quote_smart($currentTime);


						$queryRefs = "INSERT INTO $tableRefs SET " . $queryRefs; // finalize the query by prefixing it with the actual MySQL command

						// ADD RECORD:

						// RUN the query on the database through the connection:
						$result = queryMySQLDatabase($queryRefs);

						// Get the record id that was created:
						$serialNo = @ mysql_insert_id($connection); // find out the unique ID number of the newly created record (Note: this function should be called immediately after the
						                                            // SQL INSERT statement! After any subsequent query it won't be possible to retrieve the auto_increment identifier value for THIS record!)

						// ADD USER DATA:

						if (isset($_SESSION['loginEmail']))
						{
							// Note: At the moment, the record in table 'user_data' will be always created for the currently logged-in user,
							//       i.e. we don't try to match any custom data given in the 'location' field with users from table 'users'
							//       in order to set the 'user_id' in table 'user_data' accordingly

							// This is a stupid hack that maps the names of the '$recordData' array keys to those used
							// by the '$formVars' array (which is required by function 'generateCiteKey()')
							// (eventually, the '$formVars' array should use the MySQL field names as names for its array keys)
							$formVars = buildFormVarsArray($recordData);

							// Generate or extract the cite key for this record:
							$citeKey = generateCiteKey($formVars);

							// Construct SQL query:
							$queryUserData = "INSERT INTO $tableUserData SET ";

							if (!empty($recordData['marked']) AND preg_match("/^(no|yes)$/", $recordData['marked']))
								$queryUserData .= "marked = " . quote_smart($recordData['marked']) . ", ";

							if (!empty($recordData['copy']) AND preg_match("/^(false|true|ordered|fetch)$/", $recordData['copy']))
								$queryUserData .= "copy = " . quote_smart($recordData['copy']) . ", ";
							else
								$queryUserData .= "copy = 'true', "; // by default, 'false' would get inserted if omitted; we insert 'true' here in order to be consistent with manual record additions

							if (!empty($recordData['selected']) AND preg_match("/^(no|yes)$/", $recordData['selected']))
								$queryUserData .= "selected = " . quote_smart($recordData['selected']) . ", ";

							if (!empty($recordData['user_keys']))
								$queryUserData .= "user_keys = " . quote_smart($recordData['user_keys']) . ", ";

							if (!empty($recordData['user_notes']))
								$queryUserData .= "user_notes = " . quote_smart($recordData['user_notes']) . ", ";

							if (!empty($recordData['user_file']))
								$queryUserData .= "user_file = " . quote_smart($recordData['user_file']) . ", ";

							if (!empty($recordData['user_groups']))
								$queryUserData .= "user_groups = " . quote_smart($recordData['user_groups']) . ", ";

							$queryUserData .= "cite_key = " . quote_smart($citeKey) . ", ";

							if (!empty($recordData['related']))
								$queryUserData .= "related = " . quote_smart($recordData['related']) . ", ";

							$queryUserData .= "record_id = " . quote_smart($serialNo) . ", "
							                . "user_id = " . quote_smart($loginUserID) . ", " // '$loginUserID' is provided as session variable
							                . "data_id = NULL"; // inserting 'NULL' into an auto_increment PRIMARY KEY attribute allocates the next available key value

							// RUN the query on the database through the connection:
							$result = queryMySQLDatabase($queryUserData);
						}

						// Append this record's serial number to the array of imported record serials:
						$serialNumbersArray[] = $serialNo;
					}
					// else: '$recordData' did not contain any data, so we skip this record
				}
				// (END LOOP OVER EACH RECORD)
			}
			// else: unknown array structure, return an empty '$serialNumbersArray'
		}
		// else: couldn't verify structure of '$importDataArray', return an empty '$serialNumbersArray'

		return $serialNumbersArray; // return list of serial numbers of all imported records
	}

	// --------------------------------------------------------------------

	// Assign correct values to the calculation fields 'first_author', 'author_count', 'first_page', 'volume_numeric' and 'series_volume_numeric':
	function generateCalculationFieldContent($author, $pages, $volume, $seriesVolume)
	{
		if (!empty($author))
		{
			// Standardize contents of the author field (which will ensure correct sorting upon Citation output):
			// - shorten author's full given name(s) to initial(s)
			// - remove any delimiters (such as dots and/or whitespace) from author's initials

			// Call the 'reArrangeAuthorContents()' function (defined in 'include.inc.php') in order to re-order contents of the author field. Required Parameters:
			//   1. input:  contents of the author field
			//   2. input:  boolean value that specifies whether the author's family name comes first (within one author) in the source string
			//              ('true' means that the family name is followed by the given name (or initials), 'false' if it's the other way around)
			//
			//   3. input:  pattern describing old delimiter that separates different authors
			//   4. output: for all authors except the last author: new delimiter that separates different authors
			//   5. output: for the last author: new delimiter that separates the last author from all other authors
			//
			//   6. input:  pattern describing old delimiter that separates author name & initials (within one author)
			//   7. output: for the first author: new delimiter that separates author name & initials (within one author)
			//   8. output: for all authors except the first author: new delimiter that separates author name & initials (within one author)
			//   9. output: new delimiter that separates multiple initials (within one author)
			//  10. output: for the first author: boolean value that specifies if initials go *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
			//  11. output: for all authors except the first author: boolean value that specifies if initials go *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
			//  12. output: boolean value that specifies whether an author's full given name(s) shall be shortened to initial(s)
			//
			//  13. output: if the total number of authors is greater than the given number (integer >= 1), only the number of authors given in (14) will be included in the citation along with the string given in (15); keep empty if all authors shall be returned
			//  14. output: number of authors (integer >= 1) that is included in the citation if the total number of authors is greater than the number given in (13); keep empty if not applicable
			//  15. output: string that's appended to the number of authors given in (14) if the total number of authors is greater than the number given in (13); the actual number of authors can be printed by including '__NUMBER_OF_AUTHORS__' (without quotes) within the string
			//
			//  16. output: boolean value that specifies whether the re-ordered string shall be returned with higher ASCII chars HTML encoded
			$author = reArrangeAuthorContents($author, // 1.
			                                  true, // 2.
			                                  " *; *", // 3.
			                                  "; ", // 4.
			                                  "; ", // 5.
			                                  " *, *", // 6.
			                                  ", ", // 7.
			                                  ", ", // 8.
			                                  "", // 9.
			                                  false, // 10.
			                                  false, // 11.
			                                  true, // 12.
			                                  "", // 13.
			                                  "", // 14.
			                                  "", // 15.
			                                  false); // 16.

			// 'first_author' field:
			$firstAuthor = ereg_replace("^([^;]+).*", "\\1", $author); // extract first author from 'author' field
			$firstAuthor = trim($firstAuthor); // remove leading & trailing whitespace (if any)
			$firstAuthor = ereg_replace(" *\(eds?\)$", "", $firstAuthor); // remove any existing editor info from the 'first_author' string, i.e., kill any trailing " (ed)" or " (eds)"

			// 'author_count' field:
			if (!ereg(";", $author)) // if the 'author' field does NOT contain a ';' (which would delimit multiple authors) => single author
				$authorCount = "1"; // indicates a single author
			elseif (ereg("^[^;]+;[^;]+$", $author)) // the 'author' field does contain exactly one ';' => two authors
				$authorCount = "2"; // indicates two authors
			elseif (ereg("^[^;]+;[^;]+;[^;]+", $author)) // the 'author' field does contain at least two ';' => more than two authors
				$authorCount = "3"; // indicates three (or more) authors
		}
		else
		{
			$firstAuthor = "";
			$authorCount = "";
		}

		// 'first_page' field:
		if (!empty($pages))
		{
			if (ereg("([0-9]+)", $pages)) // if the 'pages' field contains any numeric value(s)
				$firstPage = ereg_replace("^[^0-9]*([0-9]+).*", "\\1", $pages); // extract first page from 'pages' field
			else
				$firstPage = "";
		}
		else
			$firstPage = "";

		// 'volume_numeric' field:
		if (!empty($volume))
		{
			if (ereg("([0-9]+)", $volume)) // if the 'volume' field contains any numeric value(s)
				$volumeNumeric = ereg_replace("^[^0-9]*([0-9]+).*", "\\1", $volume); // extract first number from 'volume' field
			else
				$volumeNumeric = "";
		}
		else
			$volumeNumeric = "";

		// 'series_volume_numeric' field:
		if (!empty($seriesVolume))
		{
			if (ereg("([0-9]+)", $seriesVolume)) // if the 'series_volume' field contains any numeric value(s)
				$seriesVolumeNumeric = ereg_replace("^[^0-9]*([0-9]+).*", "\\1", $seriesVolume); // extract first number from 'series_volume' field
			else
				$seriesVolumeNumeric = "";
		}
		else
			$seriesVolumeNumeric = "";

		return array($firstAuthor, $authorCount, $firstPage, $volumeNumeric, $seriesVolumeNumeric);
	}

	// --------------------------------------------------------------------

	// Generic function that provides email sending capability:
	function sendEmail($emailRecipient, $emailSubject, $emailBody)
	{
		global $adminLoginEmail; // these variables are specified in 'ini.inc.php'
		global $contentTypeCharset;

		// Setup some additional headers:
		$emailHeaders = "From: " . $adminLoginEmail . "\n"
		              . "Return-Path: " . $adminLoginEmail . "\n"
		              . "X-Sender: " . $adminLoginEmail . "\n"
		              . "X-Mailer: PHP\n"
		              . "X-Priority: 3\n"
		              . "Content-Type: text/plain; charset=" . $contentTypeCharset;

		// Send the email:
		mail($emailRecipient, $emailSubject, $emailBody, $emailHeaders);
	}

	// --------------------------------------------------------------------

	// Map MySQL field names to their localized names:
	// 
	// TODO: - ensure that the names for field 'user_groups' in tables 'refs' and 'users' are
	//         set correctly (user-specific groups of references vs. admin groups of users)
	//       - add "DropDownFieldName_*" entries for unique field names of table 'users'
	function mapFieldNames($isDropDown = false)
	{
		global $loc; // '$loc' is made globally available in 'core.php'

		if ($isDropDown) // field names intended for inclusion into a dropdown form element:
		{
			$fieldNamesArray = array("author"                => $loc["DropDownFieldName_Author"],
			//                       "author_count"          => $loc[""],
			//                       "first_author"          => $loc[""],
			                         "address"               => $loc["DropDownFieldName_Address"],
			                         "corporate_author"      => $loc["DropDownFieldName_CorporateAuthor"],
			                         "thesis"                => $loc["DropDownFieldName_Thesis"],
			                         "title"                 => $loc["DropDownFieldName_Title"],
			                         "orig_title"            => $loc["DropDownFieldName_OrigTitle"],
			                         "year"                  => $loc["DropDownFieldName_Year"],
			                         "publication"           => $loc["DropDownFieldName_Publication"],
			                         "abbrev_journal"        => $loc["DropDownFieldName_AbbrevJournal"],
			                         "editor"                => $loc["DropDownFieldName_Editor"],
			                         "volume"                => $loc["DropDownFieldName_Volume"],
			//                       "volume_numeric"        => $loc[""],
			                         "issue"                 => $loc["DropDownFieldName_Issue"],
			                         "pages"                 => $loc["DropDownFieldName_Pages"],
			//                       "first_page"            => $loc[""],
			                         "series_title"          => $loc["DropDownFieldName_SeriesTitle"],
			                         "abbrev_series_title"   => $loc["DropDownFieldName_AbbrevSeriesTitle"],
			                         "series_editor"         => $loc["DropDownFieldName_SeriesEditor"],
			                         "series_volume"         => $loc["DropDownFieldName_SeriesVolume"],
			//                       "series_volume_numeric" => $loc[""],
			                         "series_issue"          => $loc["DropDownFieldName_SeriesIssue"],
			                         "publisher"             => $loc["DropDownFieldName_Publisher"],
			                         "place"                 => $loc["DropDownFieldName_Place"],
			                         "edition"               => $loc["DropDownFieldName_Edition"],
			                         "medium"                => $loc["DropDownFieldName_Medium"],
			                         "issn"                  => $loc["DropDownFieldName_Issn"],
			                         "isbn"                  => $loc["DropDownFieldName_Isbn"],
			                         "language"              => $loc["DropDownFieldName_Language"],
			                         "summary_language"      => $loc["DropDownFieldName_SummaryLanguage"],
			                         "keywords"              => $loc["DropDownFieldName_Keywords"],
			                         "abstract"              => $loc["DropDownFieldName_Abstract"],
			                         "area"                  => $loc["DropDownFieldName_Area"],
			                         "expedition"            => $loc["DropDownFieldName_Expedition"],
			                         "conference"            => $loc["DropDownFieldName_Conference"],
			                         "doi"                   => $loc["DropDownFieldName_Doi"],
			                         "url"                   => $loc["DropDownFieldName_Url"],
			                         "file"                  => $loc["DropDownFieldName_File"],
			                         "notes"                 => $loc["DropDownFieldName_Notes"],
			                         "location"              => $loc["DropDownFieldName_Location"],
			                         "call_number"           => $loc["DropDownFieldName_CallNumber"],
			                         "serial"                => $loc["DropDownFieldName_Serial"],
			                         "type"                  => $loc["DropDownFieldName_Type"],
			                         "approved"              => $loc["DropDownFieldName_Approved"],
			                         "created_date"          => $loc["DropDownFieldName_CreatedDate"],
			                         "created_time"          => $loc["DropDownFieldName_CreatedTime"],
			                         "created_by"            => $loc["DropDownFieldName_CreatedBy"],
			                         "modified_date"         => $loc["DropDownFieldName_ModifiedDate"],
			                         "modified_time"         => $loc["DropDownFieldName_ModifiedTime"],
			                         "modified_by"           => $loc["DropDownFieldName_ModifiedBy"],
			                         "marked"                => $loc["DropDownFieldName_Marked"],
			                         "copy"                  => $loc["DropDownFieldName_Copy"],
			                         "selected"              => $loc["DropDownFieldName_Selected"],
			                         "user_keys"             => $loc["DropDownFieldName_UserKeys"],
			                         "user_notes"            => $loc["DropDownFieldName_UserNotes"],
			                         "user_file"             => $loc["DropDownFieldName_UserFile"],
			                         "user_groups"           => $loc["DropDownFieldName_UserGroups"],
			                         "cite_key"              => $loc["DropDownFieldName_CiteKey"],
			//                       "related"               => $loc[""]
			                        );
		}
		else // field names intended as title word or column heading:
		{
			$fieldNamesArray = array("author"                => $loc["Author"],
			                         "author_count"          => $loc["AuthorCount"],
			                         "first_author"          => $loc["AuthorFirst"],
			                         "address"               => $loc["Address"],
			                         "corporate_author"      => $loc["CorporateAuthor"],
			                         "thesis"                => $loc["Thesis"],
			                         "title"                 => $loc["Title"],
			                         "orig_title"            => $loc["TitleOriginal"],
			                         "year"                  => $loc["Year"],
			                         "publication"           => $loc["Publication"],
			                         "abbrev_journal"        => $loc["JournalAbbr"],
			                         "editor"                => $loc["Editor"],
			                         "volume"                => $loc["Volume"],
			                         "volume_numeric"        => $loc["VolumeNumeric"],
			                         "issue"                 => $loc["Issue"],
			                         "pages"                 => $loc["Pages"],
			                         "first_page"            => $loc["PagesFirst"],
			                         "series_title"          => $loc["TitleSeries"],
			                         "abbrev_series_title"   => $loc["TitleSeriesAbbr"],
			                         "series_editor"         => $loc["SeriesEditor"],
			                         "series_volume"         => $loc["SeriesVolume"],
			                         "series_volume_numeric" => $loc["SeriesVolumeNumeric"],
			                         "series_issue"          => $loc["SeriesIssue"],
			                         "publisher"             => $loc["Publisher"],
			                         "place"                 => $loc["PublisherPlace"],
			                         "edition"               => $loc["Edition"],
			                         "medium"                => $loc["Medium"],
			                         "issn"                  => $loc["ISSN"],
			                         "isbn"                  => $loc["ISBN"],
			                         "language"              => $loc["Language"],
			                         "summary_language"      => $loc["LanguageSummary"],
			                         "keywords"              => $loc["Keywords"],
			                         "abstract"              => $loc["Abstract"],
			                         "area"                  => $loc["Area"],
			                         "expedition"            => $loc["Expedition"],
			                         "conference"            => $loc["Conference"],
			                         "doi"                   => $loc["DOI"],
			                         "url"                   => $loc["URL"],
			                         "file"                  => $loc["File"],
			                         "notes"                 => $loc["Notes"],
			                         "location"              => $loc["Location"],
			                         "call_number"           => $loc["CallNumber"],
			                         "serial"                => $loc["Serial"],
			                         "type"                  => $loc["Type"],
			                         "approved"              => $loc["Approved"],
			                         "created_date"          => $loc["CreationDate"],
			                         "created_time"          => $loc["CreationTime"],
			                         "created_by"            => $loc["Creator"],
			                         "modified_date"         => $loc["ModifiedDate"],
			                         "modified_time"         => $loc["ModifiedTime"],
			                         "modified_by"           => $loc["Modifier"],
			                         "marked"                => $loc["Marked"],
			                         "copy"                  => $loc["Copy"],
			                         "selected"              => $loc["Selected"],
			                         "user_keys"             => $loc["UserKeys"],
			                         "user_notes"            => $loc["UserNotes"],
			                         "user_file"             => $loc["UserFile"],
			                         "user_groups"           => $loc["UserGroups"], // see TODO note above
			                         "cite_key"              => $loc["CiteKey"],
			                         "related"               => $loc["Related"],

		// field names from table 'users' (that aren't covered by any of the above):
			                         "first_name"            => $loc["FirstName"],
			                         "last_name"             => $loc["LastName"],
			                         "institution"           => $loc["Institution"],
			                         "abbrev_institution"    => $loc["InstitutionAbbr"],
			                         "corporate_institution" => $loc["CorporateInstitution"],
			                         "address_line_1"        => $loc["AddressLine1"],
			                         "address_line_2"        => $loc["AddressLine2"],
			                         "address_line_3"        => $loc["AddressLine3"],
			                         "zip_code"              => $loc["ZipCode"],
			                         "city"                  => $loc["City"],
			                         "state"                 => $loc["State"],
			                         "country"               => $loc["Country"],
			                         "phone"                 => $loc["Phone"],
			                         "email"                 => $loc["Email"],
			                         "last_login"            => $loc["LastLogin"],
			                         "logins"                => $loc["Logins"],
			                         "user_id"               => $loc["UserID"],
		//	                         "user_groups"           => $loc["UserGroups"], // see TODO note above
			                        );
		}

		return $fieldNamesArray;
	}

	// --------------------------------------------------------------------

	// BUILD FIELD NAME LINKS
	// (i.e., build clickable column headers for each available column)
	// TODO: I18n
	function buildFieldNameLinks($href, $query, $newORDER, $result, $i, $showQuery, $showLinks, $rowOffset, $showRows, $wrapResults, $citeStyle, $HTMLbeforeLink, $HTMLafterLink, $formType, $submitType, $linkName, $orig_fieldname, $headerMsg, $viewType)
	{
		global $databaseBaseURL; // defined in 'ini.inc.php'

		global $loc; // '$loc' is made globally available in 'core.php'

		global $client;

		// Setup the base URL:
		if (eregi("^(cli|inc)", $client) OR ($wrapResults == "0")) // we use absolute links for CLI clients, for include mechanisms, or when returning only a partial document structure
			$baseURL = $databaseBaseURL;
		else
			$baseURL = "";

		// Map MySQL field names to localized column names:
		$fieldNamesArray = mapFieldNames();

		// Get all field properties of the current MySQL field:
		$fieldInfoArray = getMySQLFieldInfo($result, $i);

		if (empty($orig_fieldname)) // if there's no fixed original fieldname specified (as is the case for all fields but the 'Links' column)
		{
			// Get the attribute name:
			$orig_fieldname = $fieldInfoArray["name"];
		}

		if (empty($linkName)) // if there's no fixed link name specified (as is the case for all fields but the 'Links' column)...
		{
			if (isset($fieldNamesArray[$orig_fieldname]))
			{
				$linkName = $fieldNamesArray[$orig_fieldname]; // ...use the attribute's localized name as link name
			}
			else // ...use MySQL field name as fall back:
			{
				// Replace substrings with spaces:
				$linkName = str_replace("_"," ",$orig_fieldname);
				// Form words (i.e., make the first char of a word uppercase):
				$linkName = ucwords($linkName);
			}
		}

		// Setup some variables (in order to enable sorting by clicking on column titles)
		// NOTE: Column sorting with any queries that include the 'LIMIT'... parameter
		//       will (technically) work. However, every new query will limit the selection to a *different* list of records!! ?:-/
		if (empty($newORDER)) // if there's no fixed ORDER BY string specified (as is the case for all fields but the 'Links' column)
		{
			if ($fieldInfoArray["numeric"] == "1") // Check if the field's data type is numeric (if so we'll append " DESC" to the ORDER clause)
				$newORDER = ("ORDER BY " . $orig_fieldname . " DESC"); // Build the appropriate ORDER BY clause (sort numeric fields in DESCENDING order)
			else
				$newORDER = ("ORDER BY " . $orig_fieldname); // Build the appropriate ORDER BY clause
		}

		if ($orig_fieldname == "pages") // when original field name = 'pages' then...
		{
			$newORDER = eregi_replace("ORDER BY pages", "ORDER BY first_page DESC", $newORDER); // ...sort by 'first_page' instead
			$orig_fieldname = "first_page"; // adjust '$orig_fieldname' variable accordingly
		}

		if ($orig_fieldname == "volume") // when original field name = 'volume' then...
		{
			$newORDER = eregi_replace("ORDER BY volume", "ORDER BY volume_numeric DESC", $newORDER); // ...sort by 'volume_numeric' instead
			$orig_fieldname = "volume_numeric"; // adjust '$orig_fieldname' variable accordingly
		}

		if ($orig_fieldname == "series_volume") // when original field name = 'series_volume' then...
		{
			$newORDER = eregi_replace("ORDER BY series_volume", "ORDER BY series_volume_numeric DESC", $newORDER); // ...sort by 'series_volume_numeric' instead
			$orig_fieldname = "series_volume_numeric"; // adjust '$orig_fieldname' variable accordingly
		}

		if ($orig_fieldname == "marked") // when original field name = 'marked' then...
			$newORDER = eregi_replace("ORDER BY marked", "ORDER BY marked DESC", $newORDER); // ...sort 'marked' column in DESCENDING order (so that 'yes' sorts before 'no')

		if ($orig_fieldname == "last_login") // when original field name = 'last_login' (defined in 'users' table) then...
			$newORDER = eregi_replace("ORDER BY last_login", "ORDER BY last_login DESC", $newORDER); // ...sort 'last_login' column in DESCENDING order (so that latest date+time sorts first)

		$orderBy = eregi_replace("ORDER BY ", "", $newORDER); // remove 'ORDER BY ' phrase in order to store just the 'ORDER BY' field spec within the 'orderBy' variable

		// call the 'newORDERclause()' function to replace the ORDER clause:
		$queryURLNewOrder = newORDERclause($newORDER, $query);

		// in the link title, we'll report the field that is actually used for sorting:
		if (isset($fieldNamesArray[$orig_fieldname]))
			$linkTitleFieldName = $fieldNamesArray[$orig_fieldname];
		else
			$linkTitleFieldName = $linkName;

		// figure out if clicking on the current field name will sort in ascending or descending order:
		// (note that for 1st-level sort attributes, this value will be modified again below)
		if (eregi("ORDER BY [^ ]+ DESC", $newORDER)) // if 1st-level sort is in descending order...
			$linkTitleSortOrder = $loc["descendingOrder"]; // ...sorting will be conducted in DESCending order
		else
			$linkTitleSortOrder = $loc["ascendingOrder"]; // ...sorting will be conducted in ASCending order

		// toggle sort order for the 1st-level sort attribute:
		if (preg_match("/ORDER BY $orig_fieldname(?! DESC)/i", $query)) // if 1st-level sort is by this attribute (in ASCending order)...
		{
			$queryURLNewOrder = preg_replace("/(ORDER%20BY%20$orig_fieldname)(?!%20DESC)/i", "\\1%20DESC", $queryURLNewOrder); // ...change sort order to DESCending
			$linkTitleSortOrder = $loc["descendingOrder"]; // adjust the link title attribute's sort info accordingly
		}
		elseif (preg_match("/ORDER BY $orig_fieldname DESC/i", $query)) // if 1st-level sort is by this attribute (in DESCending order)...
		{
			$queryURLNewOrder = preg_replace("/(ORDER%20BY%20$orig_fieldname)%20DESC/i", "\\1", $queryURLNewOrder); // ...change sort order to ASCending
			$linkTitleSortOrder = $loc["ascendingOrder"]; // adjust the link title attribute's sort info accordingly
		}

		// build an informative string that get's displayed when a user mouses over a link:
		$linkTitle = "\"" . $loc["LinkTitle_SortByField_Prefix"] . $linkTitleFieldName . $loc["LinkTitle_SortByField_Suffix"] . ", " . $linkTitleSortOrder . "\"";

		// start the table header tag & print the attribute name as link:
		$tableHeaderLink = $HTMLbeforeLink
		                   . "<a href=\"" . $baseURL . $href
		                   . "?sqlQuery=" . $queryURLNewOrder
		                   . "&amp;submit=" . $submitType
		                   . "&amp;citeStyle=" . rawurlencode($citeStyle)
		                   . "&amp;orderBy=" . rawurlencode($orderBy)
		                   . "&amp;headerMsg=" . rawurlencode($headerMsg)
		                   . "&amp;showQuery=" . $showQuery
		                   . "&amp;showLinks=" . $showLinks
		                   . "&amp;formType=" . $formType
		                   . "&amp;showRows=" . $showRows
		                   . "&amp;rowOffset=" . $rowOffset
		                   . "&amp;client=" . rawurlencode($client)
		                   . "&amp;viewType=" . $viewType
		                   . "\" title=" . $linkTitle . ">" . $linkName . "</a>";

		// append sort indicator after the 1st-level sort attribute:
		if (preg_match("/ORDER BY $orig_fieldname(?! DESC)(?=,| LIMIT|$)/i", $query)) // if 1st-level sort is by this attribute (in ASCending order)...
			$tableHeaderLink .= "&nbsp;<img src=\"" . $baseURL . "img/sort_asc.gif\" alt=\"(up)\" title=\"" . $loc["LinkTitle_SortedByField_Prefix"] . $linkTitleFieldName . $loc["LinkTitle_SortedByField_Suffix"] . ", " . $loc["ascendingOrder"] . "\" width=\"8\" height=\"10\" hspace=\"0\" border=\"0\">"; // ...append an upward arrow image
		elseif (preg_match("/ORDER BY $orig_fieldname DESC/i", $query)) // if 1st-level sort is by this attribute (in DESCending order)...
			$tableHeaderLink .= "&nbsp;<img src=\"" . $baseURL . "img/sort_desc.gif\" alt=\"(down)\" title=\"" . $loc["LinkTitle_SortedByField_Prefix"] . $linkTitleFieldName . $loc["LinkTitle_SortedByField_Suffix"] . ", " . $loc["descendingOrder"] . "\" width=\"8\" height=\"10\" hspace=\"0\" border=\"0\">"; // ...append a downward arrow image

		$tableHeaderLink .=  $HTMLafterLink; // append any necessary HTML

		return $tableHeaderLink;
	}

	// --------------------------------------------------------------------

	// Build SELECT clause:
	// (if given, '$additionalFields' & '$customSELECTclause' must contain
	//  a string of comma-separated field names)
	// TODO: add support for 'users.php' SELECT clauses
	function buildSELECTclause($displayType, $showLinks, $additionalFields = "", $addUserSpecificFields = true, $addRequiredFields = true, $customSELECTclause = "", $browseByField = "")
	{
		global $defaultFieldsListViewMajor; // these variables are specified in 'ini.inc.php'
		global $defaultFieldsListViewMinor;
		global $additionalFieldsCitationView;
		global $showAdditionalFieldsDetailsViewDefault;
		global $showUserSpecificFieldsDetailsViewDefault;

		if (empty($displayType))
			$displayType = $_SESSION['userDefaultView']; // get the default view for the current user

		$querySELECTclause = "SELECT ";

		if (!empty($customSELECTclause)) // if given, honour any custom SQL SELECT clause:
		{
			$querySELECTclause .= $customSELECTclause;
		}
		else // build a new SELECT clause that's suitable for the given '$displayType':
		{
			// Details view:
			if (eregi("^(Display)$", $displayType)) // select all fields required to display record details:
			{
				if ($showAdditionalFieldsDetailsViewDefault == "no") // omit additional fields:
				{
					$querySELECTclause .= "author, title, type, year, publication, abbrev_journal, volume, issue, pages, keywords, abstract";
				}
				else // display all fields:
				{
					$querySELECTclause .= "author, title, type, year, publication, abbrev_journal, volume, issue, pages, keywords, abstract, address, corporate_author, thesis, publisher, place, editor, language, summary_language, orig_title, series_editor, series_title, abbrev_series_title, series_volume, series_issue, edition, issn, isbn, medium, area, expedition, conference, notes, approved";

					if (isset($_SESSION['loginEmail']))
						$querySELECTclause .= ", location"; // we only add the 'location' field if the user is logged in
				}

				$querySELECTclause .= ", call_number, serial";

				if ($showUserSpecificFieldsDetailsViewDefault == "no")
					$addUserSpecificFields = false;
			}

			// Edit mode & Export:
			elseif (eregi("^(Edit|Export)$", $displayType)) // select all fields required to display record details (in edit mode) or to export a record:
			{
				$querySELECTclause .= "author, title, type, year, publication, abbrev_journal, volume, issue, pages, keywords, abstract, address, corporate_author, thesis, publisher, place, editor, language, summary_language, orig_title, series_editor, series_title, abbrev_series_title, series_volume, series_issue, edition, issn, isbn, medium, area, expedition, conference, notes, approved";

				if (isset($_SESSION['loginEmail']))
					$querySELECTclause .= ", location"; // we only add the 'location' field if the user is logged in

				$querySELECTclause .= ", contribution_id, online_publication, online_citation, created_date, created_time, created_by, modified_date, modified_time, modified_by, call_number, serial";
			}

			// Citation view & RSS output:
			elseif (eregi("^(Cite|RSS)$", $displayType)) // select all fields required to build proper record citations:
			{
				$querySELECTclause .= "author, title, type, year, publication, abbrev_journal, volume, issue, pages, keywords, abstract, thesis, editor, publisher, place, abbrev_series_title, series_title, series_editor, series_volume, series_issue, edition, language, author_count, online_publication, online_citation, doi, serial";

				if ($displayType == "RSS") // for RSS output, we add some additional fields:
					$querySELECTclause .= ", created_date, created_time, created_by, modified_date, modified_time, modified_by";

				if (!empty($additionalFieldsCitationView)) // append all fields from '$additionalFieldsCitationView' that aren't yet included in the SELECT clause
					foreach ($additionalFieldsCitationView as $field)
						if (!preg_match("/\b" . $field . "\b/", $querySELECTclause))
						{
							if (preg_match("/^(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)$/", $field)) // if '$field' is one of the user-specific fields, we'll add all of them below
								$addUserSpecificFields = true;
							else // append field:
								$querySELECTclause .= ", " . $field;
						}
			}

			// Browse view:
			elseif (eregi("^Browse$", $displayType))
			{
				$querySELECTclause .= escapeSQL($browseByField) . ", COUNT(*) AS records";
			}

			// List view:
			else // produce the default columnar output style:
			{
				$querySELECTclause .= $defaultFieldsListViewMajor . ", " . $defaultFieldsListViewMinor;
			}
		}

		// All views (except Browse view):
		if (!eregi("^Browse$", $displayType))
		{
			if (!empty($additionalFields))
			{
				if ($querySELECTclause != "SELECT ")
					$querySELECTclause .= ", "; // add a comma as field separator, if other fields have already been added to the SELECT clause

				$querySELECTclause .= $additionalFields;
			}

			// NOTE: Functions 'displayColumns()' and 'displayDetails()' (in 'search.php') apply some logic that prevents some or all of the
			//       below fields from getting displayed. This means that you must adopt these functions if you add or remove fields below.

			if ($addUserSpecificFields)
			{
				if (isset($_SESSION['loginEmail'])) // if a user is logged in...
					$querySELECTclause .= ", marked, copy, selected, user_keys, user_notes, user_file, user_groups, cite_key, related"; // add user-specific fields
			}

			if ($addRequiredFields)
			{
				// NOTE: Although it won't be visible the 'orig_record' & 'serial' columns get included in every search query
				//       (that's executed directly and not included into HTML as a web link or routed again thru other scripts).
				//       The 'orig_record' column is required in order to present visual feedback on duplicate records, and
				//       the 'serial' column is required in order to obtain unique checkbox names. For SQL queries passed to
				//       'search.php' directly, function 'verifySQLQuery()' in 'include.inc.php' will add these columns.
				$querySELECTclause .= ", orig_record, serial"; // add 'orig_record' and 'serial' columns

				if ($showLinks == "1" OR (eregi("^(Edit|Export)$", $displayType)))
					$querySELECTclause .= ", file, url, doi"; // add 'file', 'url' & 'doi' columns

				if ($showLinks == "1" AND (!eregi("^(Edit|Export)$", $displayType)))
					$querySELECTclause .= ", isbn, type"; // add 'isbn' & 'type columns (for export and edit mode, these columns have already been added above)
			}
		}


		return $querySELECTclause;
	}

	// --------------------------------------------------------------------

	//	REPLACE ORDER CLAUSE IN SQL QUERY
	function newORDERclause($newOrderBy, $query, $encodeQuery = true)
	{
		// replace any existing ORDER BY clause with the new one given in '$newOrderBy':
		$newQuery = preg_replace("/ORDER BY .+?(?=LIMIT.*|GROUP BY.*|HAVING.*|PROCEDURE.*|FOR UPDATE.*|LOCK IN.*|$)/i", $newOrderBy, $query);

		if ($encodeQuery)
			$newQuery = rawurlencode($newQuery); // URL encode query

		return $newQuery;
	}

	// --------------------------------------------------------------------

	//	REPLACE SELECT CLAUSE IN SQL QUERY
	function newSELECTclause($newSelectClause, $query, $encodeQuery = true)
	{
		// replace any existing SELECT clause with the new one given in '$newSelectClause':
		$newQuery = preg_replace("/SELECT .+?(?= FROM)/i", $newSelectClause, $query);

		if ($encodeQuery)
			$newQuery = rawurlencode($newQuery); // URL encode query

		return $newQuery;
	}

	// --------------------------------------------------------------------

	//	BUILD BROWSE LINKS
	// (i.e., build a TABLE row with links for "previous" & "next" browsing, as well as links to intermediate pages)
	// TODO: - use divs + CSS styling (instead of a table-based layout) for _all_ output (not only for 'viewType=Mobile')
	//       - use function 'generateURL()' to build the link URLs
	function buildBrowseLinks($href, $query, $NoColumns, $rowsFound, $showQuery, $showLinks, $showRows, $rowOffset, $previousOffset, $nextOffset, $wrapResults, $maxPageNo, $formType, $displayType, $citeStyle, $citeOrder, $orderBy, $headerMsg, $viewType)
	{
		global $databaseBaseURL; // these variables are defined in 'ini.inc.php'
		global $displayResultsHeaderDefault;
		global $displayResultsFooterDefault;

		global $loc; // '$loc' is made globally available in 'core.php'

		global $client;

		// First, calculate the offset page number:
		$pageOffset = ($rowOffset / $showRows);
		// workaround for always rounding upward (since I don't know better! :-/):
		if (ereg("[0-9]+\.[0-9+]",$pageOffset)) // if the result number is not an integer..
			$pageOffset = (int) $pageOffset + 1; // we convert the number into an integer and add 1
		// set the offset page number to a multiple of $maxPageNo:
		$pageOffset = $maxPageNo * (int) ($pageOffset / $maxPageNo);

		// Plus, calculate the maximum number of pages needed:
		$lastPage = ($rowsFound / $showRows);
		// workaround for always rounding upward (since I don't know better! :-/):
		if (ereg("[0-9]+\.[0-9+]",$lastPage)) // if the result number is not an integer..
			$lastPage = (int) $lastPage + 1; // we convert the number into an integer and add 1

		// Setup the base URL:
		if (eregi("^(cli|inc)", $client) OR ($wrapResults == "0")) // we use absolute links for CLI clients, for include mechanisms, or when returning only a partial document structure
			$baseURL = $databaseBaseURL;
		else
			$baseURL = "";

		if (eregi("^Mobile$", $viewType))
		{
			$BrowseLinks = "\n<div class=\"resultnav\">";
		}
		else
		{
			// Start a <TABLE>:
			$BrowseLinks = "\n<table class=\"resultnav\" align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds browse links that link to the results pages of your query\">";

			// Start a <TABLE> row:
			$BrowseLinks .= "\n<tr>";
		}

		if (eregi("^Mobile$", $viewType))
			$BrowseLinks .= "\n\t<div class=\"mainnav\"><a href=\"" . $baseURL . "index.php\"" . addAccessKey("attribute", "home") . " title=\"" . $loc["LinkTitle_Home"] . addAccessKey("title", "home") . "\">" . $loc["Home"] . "</a></div>";
		elseif (eregi("^Print$", $viewType) OR eregi("^cli", $client))
			$BrowseLinks .= "\n\t<td class=\"mainnav\" align=\"left\" valign=\"bottom\" width=\"225\"><a href=\"" . $baseURL . "index.php\"" . addAccessKey("attribute", "home") . " title=\"" . $loc["LinkTitle_Home"] . addAccessKey("title", "home") . "\">" . $loc["Home"] . "</a></td>";
		elseif (($href == "users.php") OR !isset($displayResultsFooterDefault[$displayType]) OR (isset($displayResultsFooterDefault[$displayType]) AND ($displayResultsFooterDefault[$displayType] != "hidden")))
		{
			$BrowseLinks .= "\n\t<td class=\"small\" align=\"left\" valign=\"bottom\" width=\"225\">"
			              . "\n\t\t<a href=\"JavaScript:checkall(true,'marked%5B%5D')\" title=\"" . $loc["LinkTitle_SelectAll"] . "\">" . $loc["SelectAll"] . "</a>&nbsp;&nbsp;&nbsp;"
			              . "\n\t\t<a href=\"JavaScript:checkall(false,'marked%5B%5D')\" title=\"" . $loc["LinkTitle_DeselectAll"] . "\">" . $loc["DeselectAll"] . "</a>"
			              . "\n\t</td>";
		}
		else // don't show the select/deselect links when the results footer is hidden
		{
			$BrowseLinks .= "\n\t<td class=\"small\" align=\"left\" valign=\"bottom\" width=\"225\">&nbsp;</td>";
		}


		if (eregi("^Mobile$", $viewType))
			$BrowseLinks .= "\n\t<div class=\"pagenav\">";
		else
			$BrowseLinks .= "\n\t<td class=\"pagenav\" align=\"center\" valign=\"bottom\">";

		// a) If there's a page range below the one currently shown,
		// create a "[xx-xx]" link (linking directly to the previous range of pages):
		if ($pageOffset > "0")
			{
				$previousRangeFirstPage = ($pageOffset - $maxPageNo + 1); // calculate the first page of the previous page range

				$previousRangeLastPage = ($previousRangeFirstPage + $maxPageNo - 1); // calculate the last page of the previous page range

				$BrowseLinks .= "\n\t\t<a href=\"" . $baseURL . $href
				              . "?sqlQuery=" . rawurlencode($query)
				              . "&amp;submit=" . $displayType
				              . "&amp;citeStyle=" . rawurlencode($citeStyle)
				              . "&amp;citeOrder=" . $citeOrder
				              . "&amp;orderBy=" . rawurlencode($orderBy)
				              . "&amp;headerMsg=" . rawurlencode($headerMsg)
				              . "&amp;showQuery=" . $showQuery
				              . "&amp;showLinks=" . $showLinks
				              . "&amp;formType=" . $formType
				              . "&amp;showRows=" . $showRows
				              . "&amp;rowOffset=" . (($pageOffset - $maxPageNo) * $showRows)
				              . "&amp;client=" . rawurlencode($client)
				              . "&amp;viewType=" . $viewType
				              . "\" title=\"" . $loc["LinkTitle_DisplayResultsPage"] . " " . $previousRangeFirstPage . " " . $loc["LinkTitle_DisplayLinksToResultsPages"] . " " . $previousRangeFirstPage . "&#8211;" . $previousRangeLastPage . "\">[" . $previousRangeFirstPage . "&#8211;" . $previousRangeLastPage . "] </a>";
			}

		// b) Are there any previous pages?
		if ($rowOffset > 0)
			// Yes, so create a previous link
			$BrowseLinks .= "\n\t\t<a href=\"" . $baseURL . $href
			              . "?sqlQuery=" . rawurlencode($query)
			              . "&amp;submit=" . $displayType
			              . "&amp;citeStyle=" . rawurlencode($citeStyle)
			              . "&amp;citeOrder=" . $citeOrder
			              . "&amp;orderBy=" . rawurlencode($orderBy)
			              . "&amp;headerMsg=" . rawurlencode($headerMsg)
			              . "&amp;showQuery=" . $showQuery
			              . "&amp;showLinks=" . $showLinks
			              . "&amp;formType=" . $formType
			              . "&amp;showRows=" . $showRows
			              . "&amp;rowOffset=" . $previousOffset
			              . "&amp;client=" . rawurlencode($client)
			              . "&amp;viewType=" . $viewType
			              . "\"" . addAccessKey("attribute", "previous") . " title=\"" . $loc["LinkTitle_DisplayPreviousResultsPage"] . addAccessKey("title", "previous") . "\">&lt;&lt;</a>";
		else
			// No, there is no previous page so don't print a link
			$BrowseLinks .= "\n\t\t&lt;&lt;";

		// c) Output the page numbers as links:
		// Count through the number of pages in the results:
		for($x=($pageOffset * $showRows), $page=($pageOffset + 1);
			$x<$rowsFound && $page <= ($pageOffset + $maxPageNo);
			$x+=$showRows, $page++)
			// Is this the current page?
				if ($x < $rowOffset || 
					$x > ($rowOffset + $showRows - 1))
					// No, so print out a link
					$BrowseLinks .= " \n\t\t<a href=\"" . $baseURL . $href
					              . "?sqlQuery=" . rawurlencode($query)
					              . "&amp;submit=" . $displayType
					              . "&amp;citeStyle=" . rawurlencode($citeStyle)
					              . "&amp;citeOrder=" . $citeOrder
					              . "&amp;orderBy=" . rawurlencode($orderBy)
					              . "&amp;headerMsg=" . rawurlencode($headerMsg)
					              . "&amp;showQuery=" . $showQuery
					              . "&amp;showLinks=" . $showLinks
					              . "&amp;formType=" . $formType
					              . "&amp;showRows=" . $showRows
					              . "&amp;rowOffset=" . $x
					              . "&amp;client=" . rawurlencode($client)
					              . "&amp;viewType=" . $viewType
					              . "\" title=\"" . $loc["LinkTitle_DisplayResultsPage"] . " " . $page . "\">" . $page . "</a>";
				else
					// Yes, so don't print a link
					$BrowseLinks .= " \n\t\t<b>$page</b>"; // current page is set in <b>BOLD</b>

		$BrowseLinks .= " ";

		// d) Are there any Next pages?
		if ($rowsFound > $nextOffset)
			// Yes, so create a next link
			$BrowseLinks .= "\n\t\t<a href=\"" . $baseURL . $href
			              . "?sqlQuery=" . rawurlencode($query)
			              . "&amp;submit=" . $displayType
			              . "&amp;citeStyle=" . rawurlencode($citeStyle)
			              . "&amp;citeOrder=" . $citeOrder
			              . "&amp;orderBy=" . rawurlencode($orderBy)
			              . "&amp;headerMsg=" . rawurlencode($headerMsg)
			              . "&amp;showQuery=" . $showQuery
			              . "&amp;showLinks=" . $showLinks
			              . "&amp;formType=" . $formType
			              . "&amp;showRows=" . $showRows
			              . "&amp;rowOffset=" . $nextOffset
			              . "&amp;client=" . rawurlencode($client)
			              . "&amp;viewType=" . $viewType
			              . "\"" . addAccessKey("attribute", "next") . " title=\"" . $loc["LinkTitle_DisplayNextResultsPage"] . addAccessKey("title", "next") . "\">&gt;&gt;</a>";
		else
			// No,	there is no next page so don't print a link
			$BrowseLinks .= "\n\t\t&gt;&gt;";

		// e) If there's a page range above the one currently shown,
		// create a "[xx-xx]" link (linking directly to the next range of pages):
		if ($pageOffset < ($lastPage - $maxPageNo))
			{
				$nextRangeFirstPage = ($pageOffset + $maxPageNo + 1); // calculate the first page of the next page range

				$nextRangeLastPage = ($nextRangeFirstPage + $maxPageNo - 1); // calculate the last page of the next page range
				if ($nextRangeLastPage > $lastPage)
					$nextRangeLastPage = $lastPage; // adjust if this is the last range of pages and if it doesn't go up to the max allowed no of pages

				$BrowseLinks .= "\n\t\t<a href=\"" . $baseURL . $href
				              . "?sqlQuery=" . rawurlencode($query)
				              . "&amp;submit=" . $displayType
				              . "&amp;citeStyle=" . rawurlencode($citeStyle)
				              . "&amp;citeOrder=" . $citeOrder
				              . "&amp;orderBy=" . rawurlencode($orderBy)
				              . "&amp;headerMsg=" . rawurlencode($headerMsg)
				              . "&amp;showQuery=" . $showQuery
				              . "&amp;showLinks=" . $showLinks
				              . "&amp;formType=" . $formType
				              . "&amp;showRows=" . $showRows
				              . "&amp;rowOffset=" . (($pageOffset + $maxPageNo) * $showRows)
				              . "&amp;client=" . rawurlencode($client)
				              . "&amp;viewType=" . $viewType
				              . "\" title=\"" . $loc["LinkTitle_DisplayResultsPage"] . " " . $nextRangeFirstPage . " " . $loc["LinkTitle_DisplayLinksToResultsPages"] . " " . $nextRangeFirstPage . "&#8211;" . $nextRangeLastPage . "\"> [" . $nextRangeFirstPage . "&#8211;" . $nextRangeLastPage . "]</a>";
			}

		if (eregi("^Mobile$", $viewType))
			$BrowseLinks .= "\n\t</div>";
		else
			$BrowseLinks .= "\n\t</td>";

		if (eregi("^Mobile$", $viewType))
			$BrowseLinks .= "\n\t<div class=\"viewnav\">";
		else
			$BrowseLinks .= "\n\t<td class=\"viewnav\" align=\"right\" valign=\"bottom\" width=\"225\">";

		// Add view links:
		$viewLinksArray = array();

		if (($href == "search.php") AND !eregi("^Browse$", $displayType) AND !eregi("^Mobile$", $viewType))
		{
			if (isset($_SESSION['user_permissions']) AND ereg("allow_list_view", $_SESSION['user_permissions']))
			{
				if (eregi("^(Cite|Display)$", $displayType)) // display a link to List view:
				{
					// Replace current SELECT clause with one that's appropriate for List view:
					if (isset($_SESSION['lastListViewQuery']))
						$listViewSelectClause = "SELECT " . extractSELECTclause($_SESSION['lastListViewQuery']); // get SELECT clause from any previous List view query
					else
						$listViewSelectClause = buildSELECTclause("List", $showLinks, "", false, false); // produce the default columnar output style

					$listViewQuery = newSELECTclause($listViewSelectClause, $query); // replace SELECT clause in current query and URL encode query

					// f) create a 'List View' link that will show the currently displayed result set in List view:
					$viewLinksArray[] = "<div class=\"leftview\"><a href=\"" . $baseURL . $href
					                  . "?sqlQuery=" . $listViewQuery
					                  . "&amp;submit=List"
					                  . "&amp;citeStyle=" . rawurlencode($citeStyle)
					                  . "&amp;citeOrder=" . $citeOrder
					                  . "&amp;orderBy=" . rawurlencode($orderBy)
					                  . "&amp;headerMsg=" . rawurlencode($headerMsg)
					                  . "&amp;showQuery=" . $showQuery
					                  . "&amp;showLinks=" . $showLinks
					                  . "&amp;formType=" . $formType
					                  . "&amp;showRows=" . $showRows
					                  . "&amp;rowOffset=" . $rowOffset
					                  . "&amp;client=" . rawurlencode($client)
					                  . "&amp;viewType=" . $viewType
					                  . "\"" . addAccessKey("attribute", "list") . " title=\"" . $loc["LinkTitle_DisplayListView"] . addAccessKey("title", "list") . "\">" . $loc["ListView"] . "</a></div>";
				}
				else
					$viewLinksArray[] = "<div class=\"activeview\"><div class=\"leftview\">" . $loc["ListView"] . "</div></div>";
			}

			if (isset($_SESSION['user_permissions']) AND ereg("allow_cite", $_SESSION['user_permissions']))
			{
				if (!eregi("^Cite$", $displayType)) // display a link to Citation view:
				{
					// Replace current SELECT clause with one that's appropriate for Citation view:
					$citeViewSelectClause = buildSELECTclause("Cite", $showLinks, "", false, false); // select all fields required to build proper record citations
					$citeViewQuery = newSELECTclause($citeViewSelectClause, $query); // replace SELECT clause in current query and URL encode query

					// g) create a 'Citations' link that will show the currently displayed result set in Citation view:
					$viewLinksArray[] = "<div class=\"middleview\"><a href=\"" . $baseURL . $href
					                  . "?sqlQuery=" . $citeViewQuery
					                  . "&amp;submit=Cite"
					                  . "&amp;citeStyle=" . rawurlencode($citeStyle)
					                  . "&amp;citeOrder=" . $citeOrder
					                  . "&amp;orderBy=" . rawurlencode($orderBy)
					                  . "&amp;headerMsg=" . rawurlencode($headerMsg)
					                  . "&amp;showQuery=" . $showQuery
					                  . "&amp;showLinks=" . $showLinks
					                  . "&amp;formType=" . $formType
					                  . "&amp;showRows=" . $showRows
					                  . "&amp;rowOffset=" . $rowOffset
					                  . "&amp;client=" . rawurlencode($client)
					                  . "&amp;viewType=" . $viewType
					                  . "\"" . addAccessKey("attribute", "cite") . " title=\"" . $loc["LinkTitle_DisplayCiteView"] . addAccessKey("title", "cite") . "\">" . $loc["Citations"] . "</a></div>";
				}
				else
					$viewLinksArray[] = "<div class=\"activeview\"><div class=\"middleview\">" . $loc["Citations"] . "</div></div>";
			}

			if (isset($_SESSION['user_permissions']) AND ereg("allow_details_view", $_SESSION['user_permissions']))
			{
				if (!eregi("^Display$", $displayType)) // display a link to Details view:
				{
					// Replace current SELECT clause with one that's appropriate for Details view:
					if (isset($_SESSION['lastDetailsViewQuery']))
						$detailsViewSelectClause = "SELECT " . extractSELECTclause($_SESSION['lastDetailsViewQuery']); // get SELECT clause from previous Details view query (if any)
					else
						$detailsViewSelectClause = buildSELECTclause("Display", $showLinks, "", false, false); // select all fields required to display record details

					$detailsViewQuery = newSELECTclause($detailsViewSelectClause, $query); // replace SELECT clause in current query and URL encode query

					// h) create a 'Details' link that will show the currently displayed result set in Details view:
					$viewLinksArray[] = "<div class=\"rightview\"><a href=\"" . $baseURL . $href
					                  . "?sqlQuery=" . $detailsViewQuery
					                  . "&amp;submit=Display"
					                  . "&amp;citeStyle=" . rawurlencode($citeStyle)
					                  . "&amp;citeOrder=" . $citeOrder
					                  . "&amp;orderBy=" . rawurlencode($orderBy)
					                  . "&amp;headerMsg=" . rawurlencode($headerMsg)
					                  . "&amp;showQuery=" . $showQuery
					                  . "&amp;showLinks=" . $showLinks
					                  . "&amp;formType=" . $formType
					                  . "&amp;showRows=" . $showRows
					                  . "&amp;rowOffset=" . $rowOffset
					                  . "&amp;client=" . rawurlencode($client)
					                  . "&amp;viewType=" . $viewType
					                  . "\"" . addAccessKey("attribute", "details") . " title=\"" . $loc["LinkTitle_DisplayDetailsView"] . addAccessKey("title", "details") . "\">" . $loc["Details"] . "</a></div>";
				}
				else
					$viewLinksArray[] = "<div class=\"activeview\"><div class=\"rightview\">" . $loc["Details"] . "</div></div>";
			}

			if (count($viewLinksArray) > 1)
				$BrowseLinks .= "\n\t\t<div class=\"resultviews\">"
				              . "\n\t\t\t" . implode("\n\t\t\t&nbsp;|&nbsp;", $viewLinksArray)
				              . "\n\t\t</div>";
		}

		// Note: we omit 'Web/Print View' links for include mechanisms!
		if (!eregi("^inc", $client))
		{
			$BrowseLinks .= "\n\t\t";

			if (count($viewLinksArray) > 1)
				$BrowseLinks .= "&nbsp;&nbsp;&nbsp;";

			if (eregi("^(Print|Mobile)$", $viewType))
			{
				// i) create a 'Web View' link that will show the currently displayed result set in web view:
				$BrowseLinks .= "<a class=\"toggleprint\" href=\"" . $baseURL . $href
				              . "?sqlQuery=" . rawurlencode($query)
				              . "&amp;submit=" . $displayType
				              . "&amp;citeStyle=" . rawurlencode($citeStyle)
				              . "&amp;citeOrder=" . $citeOrder
				              . "&amp;orderBy=" . rawurlencode($orderBy)
				              . "&amp;headerMsg=" . rawurlencode($headerMsg)
				              . "&amp;showQuery=" . $showQuery
				              . "&amp;showLinks=1"
				              . "&amp;formType=" . $formType
				              . "&amp;showRows=" . $showRows
				              . "&amp;rowOffset=" . $rowOffset
				              . "&amp;viewType=Web"
				              . "\"" . addAccessKey("attribute", "print") . "><img src=\"" . $baseURL . "img/web.gif\" alt=\"web\" title=\"" . $loc["LinkTitle_DisplayWebView"] . addAccessKey("title", "print") . "\" width=\"16\" height=\"16\" hspace=\"0\" border=\"0\"></a>";
			}
			else
			{
				if (isset($_SESSION['user_permissions']) AND ereg("allow_print_view", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_print_view'...
					// j) create a 'Print View' link that will show the currently displayed result set in print view:
					$BrowseLinks .= "<a class=\"toggleprint\" href=\"" . $baseURL . $href
					              . "?sqlQuery=" . rawurlencode($query)
					              . "&amp;submit=" . $displayType
					              . "&amp;citeStyle=" . rawurlencode($citeStyle)
					              . "&amp;citeOrder=" . $citeOrder
					              . "&amp;orderBy=" . rawurlencode($orderBy)
					              . "&amp;headerMsg=" . rawurlencode($headerMsg)
					              . "&amp;showQuery=" . $showQuery
					              . "&amp;showLinks=0"
					              . "&amp;formType=" . $formType
					              . "&amp;showRows=" . $showRows
					              . "&amp;rowOffset=" . $rowOffset
					              . "&amp;viewType=Print"
					              . "\"" . addAccessKey("attribute", "print") . "><img src=\"" . $baseURL . "img/print.gif\" alt=\"print\" title=\"" . $loc["LinkTitle_DisplayPrintView"] . addAccessKey("title", "print") . "\" width=\"17\" height=\"18\" hspace=\"0\" border=\"0\"></a>";
			}
		}

		if (eregi("^Mobile$", $viewType))
		{
			$BrowseLinks .= "\n\t</div>"
			              . "\n</div>";
		}
		else
		{
			$BrowseLinks .= "\n\t</td>"
			              . "\n</tr>"
			              . "\n</table>";
		}

		return $BrowseLinks;
	}

	// --------------------------------------------------------------------

	//	BUILD QUICK SEARCH ELEMENTS
	// (i.e., generate the "Quick Search" form)
	function buildQuickSearchElements($query, $queryURL, $showQuery, $showLinks, $showRows, $citeStyle, $citeOrder, $displayType)
	{
		global $tableRefs; // defined in 'db.inc.php'
		global $autoCompleteUserInput; // defined in 'ini.inc.php'

		global $loc; // '$loc' is made globally available in 'core.php'

		global $client;

		if (!eregi("^SELECT", $queryURL) OR !eregi("%20FROM%20" . $tableRefs . "%20", $queryURL)) // only include SELECT queries that query table 'refs'
			$queryURL = ""; // this excludes e.g. queries that query table 'users'

		$encodedCiteStyle = rawurlencode($citeStyle);
		$encodedClient = rawurlencode($client);

		$accessKeyAttribute = addAccessKey("attribute", "qck_search");
		$accessKeyTitle = addAccessKey("title", "qck_search");

		// extract the first field from the 'WHERE' clause:
		if (preg_match("/ WHERE [ ()]*(\w+)/i", $query))
			$firstField = preg_replace("/.+ WHERE [ ()]*(\w+).*/i", "\\1", $query);
		else
			$firstField = "";

		// build HTML elements that allow for search suggestions for text entered by the user:
		if ($autoCompleteUserInput == "yes")
			$suggestElements = buildSuggestElements("quickSearchName", "quickSearchSuggestions", "quickSearchSuggestProgress", "id-quickSearchSelector-", "\t\t\t\t\t\t");
		else
			$suggestElements = "";

		// add the "Quick Search" form:
		$quickSearchForm = <<<EOF
			<form action="search.php" method="GET" name="quickSearch">
				<fieldset>
					<input type="hidden" name="formType" value="quickSearch">
					<input type="hidden" name="originalDisplayType" value="$displayType">
					<input type="hidden" name="sqlQuery" value="$queryURL">
					<input type="hidden" name="showQuery" value="$showQuery">
					<input type="hidden" name="showLinks" value="$showLinks">
					<input type="hidden" name="showRows" value="$showRows">
					<input type="hidden" name="client" value="$encodedClient">
					<input type="hidden" name="citeStyle" value="$encodedCiteStyle">
					<input type="hidden" name="citeOrder" value="$citeOrder">
					<legend>$loc[QuickSearch]:</legend>
					<div id="queryField">
						<label for="quickSearchSelector">$loc[Field]:</label>
						<select id="quickSearchSelector" name="quickSearchSelector" title="$loc[DescriptionSelectFieldQuickSearchForm]">
EOF;

		// build correct option tags:
		$userMainFieldsArray = split(" *, *", $_SESSION['userMainFields']); // get the list of "main fields" preferred by the current user

		$dropDownFieldNameArray = array("main_fields" => $loc["DropDownFieldName_MainFields"]);

		foreach($userMainFieldsArray as $userMainField)
		{
			// generate the variable name of the correct '$loc' locale for this field:
			$dropDownFieldNameLocale = preg_replace("/_(\w)/e", "ucfirst('\\1')", $userMainField); // the 'e' modifier allows to execute PHP code within the replacement pattern
			$dropDownFieldNameLocale = "DropDownFieldName_" . ucfirst($dropDownFieldNameLocale);
			// add this field's name and localized string to the array of fields that will be included in the "Quick Search" drop-down menu:
			$dropDownFieldNameArray[$userMainField] = $loc[$dropDownFieldNameLocale];
		}

		$optionTags = buildSelectMenuOptions($dropDownFieldNameArray, "", "\t\t\t\t\t\t\t", true);

		if (!empty($firstField) AND in_array($firstField, $userMainFieldsArray)) // if the first field from the 'WHERE' clause is one of the main fields
			$quickSearchForm .= ereg_replace("<option([^>]* value=\"$firstField\")", "<option\\1 selected", $optionTags); // we select that field by adding the 'selected' parameter to the appropriate <option> tag
		else
			$quickSearchForm .= ereg_replace("<option([^>]*)>" . $loc["DropDownFieldName_MainFields"], "<option\\1 selected>" . $loc["DropDownFieldName_MainFields"], $optionTags); // select the 'main fields' menu entry ...

		$quickSearchForm .= <<<EOF

						</select>
						<label for="quickSearchName">$loc[contains]:</label>
						<input type="text" id="quickSearchName" name="quickSearchName" size="11"$accessKeyAttribute title="$loc[DescriptionEnterSearchString]$accessKeyTitle">$suggestElements
					</div>
					<div id="querySubmit">
						<input type="submit" value="$loc[ButtonTitle_Search]" title="$loc[DescriptionSearchDB]">
					</div>
				</fieldset>
			</form>

EOF;

		return $quickSearchForm;
	}

	// --------------------------------------------------------------------

	//	BUILD REFINE SEARCH ELEMENTS
	// (i.e., provide options to refine the search results)
	function buildRefineSearchElements($href, $queryURL, $showQuery, $showLinks, $showRows, $citeStyle, $citeOrder, $dropDownFieldsArray, $dropDownFieldSelected, $displayType)
	{
		global $autoCompleteUserInput; // defined in 'ini.inc.php'

		global $loc; // '$loc' is made globally available in 'core.php'

		global $client;

		$encodedCiteStyle = rawurlencode($citeStyle);
		$encodedClient = rawurlencode($client);

		$accessKeyAttribute = addAccessKey("attribute", "refine");
		$accessKeyTitle = addAccessKey("title", "refine");

		// build HTML elements that allow for search suggestions for text entered by the user:
		if (($href == "search.php") AND ($autoCompleteUserInput == "yes"))
			$suggestElements = buildSuggestElements("refineSearchName", "refineSearchSuggestions", "refineSearchSuggestProgress", "id-refineSearchSelector-", "\t\t\t\t\t");
		else
			$suggestElements = "";

		$refineSearchForm = <<<EOF
		<form action="$href" method="GET" name="refineSearch">
			<fieldset>
				<input type="hidden" name="formType" value="refineSearch">
				<input type="hidden" name="submit" value="$loc[ButtonTitle_Search]">
				<input type="hidden" name="originalDisplayType" value="$displayType">
				<input type="hidden" name="sqlQuery" value="$queryURL">
				<input type="hidden" name="showQuery" value="$showQuery">
				<input type="hidden" name="showLinks" value="$showLinks">
				<input type="hidden" name="showRows" value="$showRows">
				<input type="hidden" name="client" value="$encodedClient">
				<input type="hidden" name="citeStyle" value="$encodedCiteStyle">
				<input type="hidden" name="citeOrder" value="$citeOrder">
				<legend>$loc[SearchWithinResults]:</legend>
				<div id="refineField">
					<label for="refineSearchSelector">$loc[Field]:</label>
					<select id="refineSearchSelector" name="refineSearchSelector" title="$loc[DescriptionSelectFieldRefineResultsForm]">
EOF;

		// build correct option tags from the column items provided:
		$optionTags = buildSelectMenuOptions($dropDownFieldsArray, "", "\t\t\t\t\t\t", true);

		$optionTags = ereg_replace("<option([^>]* value=\"$dropDownFieldSelected\")", "<option\\1 selected", $optionTags); // add 'selected' attribute

		$refineSearchForm .= $optionTags;

		$refineSearchForm .= <<<EOF

					</select>
					<label for="refineSearchName">$loc[contains]:</label>
					<input type="text" id="refineSearchName" name="refineSearchName" size="11"$accessKeyAttribute title="$loc[DescriptionEnterSearchString]$accessKeyTitle">$suggestElements
				</div>
				<div id="refineOpt">
					<input type="checkbox" id="refineSearchExclude" name="refineSearchExclude" value="1" title="$loc[DescriptionExcludeResultsCheckboxRefineResultsForm]">
					<label for="refineSearchExclude">$loc[ExcludeMatches]</label>
				</div>
				<div id="refineSubmit">
					<input type="submit" name="submit" value="$loc[ButtonTitle_Search]" title="$loc[DescriptionSearchButtonRefineResultsForm]">
				</div>
			</fieldset>
		</form>

EOF;

		return $refineSearchForm;
	}

	// --------------------------------------------------------------------

	//	BUILD USER GROUP FORM ELEMENTS
	// (i.e., provide options to show the user's personal reference groups -OR- the admin's user groups)
	// Note: this function serves two purposes (which must not be confused!):
	// 		 - if "$href = search.php", it will modify the values of the 'user_groups' field of the 'user_data' table (where a user can assign one or more groups to particular *references*)
	//       - if "$href = users.php", this function will modify the values of the 'user_groups' field of the 'users' table (where the admin can assign one or more groups to particular *users*)
	function buildGroupSearchElements($href, $queryURL, $query, $showQuery, $showLinks, $showRows, $citeStyle, $citeOrder, $displayType)
	{
		global $loc; // '$loc' is made globally available in 'core.php'

		global $client;

		if (preg_match("/.+user_groups RLIKE \"[()|^.;* ]+[^;]+?[()|$.;* ]+\"/i", $query)) // if the query does contain a 'WHERE' clause that searches for a particular user group
			// TODO: improve the legibility & robustness of the below regex pattern (yes, it's ugly)
			$currentGroup = preg_replace("/.+user_groups RLIKE \"(?:\[:(?:space|punct):\]|[()|^.;* \]\[])+([^;]+?(?:\[\^\[:space:\]\[:punct:\]\]\*)?)(?:\[:(?:space|punct):\]|[()|$.;* \]\[])+\".*/i", "\\1", $query); // extract the particular group name
		else
			$currentGroup = "";

		// show the 'Show My Groups' form:
		// - if the admin is logged in and calls 'users.php' (since only the admin will be allowed to call 'users.php', checking '$href' is sufficient here) -OR-
		// - if a user is logged in AND the 'user_permissions' session variable contains 'allow_user_groups'
		if (($href == "users.php") OR (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_user_groups", $_SESSION['user_permissions']))))
		{
			if (($href == "search.php" AND isset($_SESSION['userGroups'])) OR ($href == "users.php" AND isset($_SESSION['adminUserGroups']))) // if the appropriate session variable is set
			{
				$groupSearchDisabled = "";
				$groupSearchSelectorTitle = $loc["DescriptionSelectFieldGroupsForm"];
				$groupSearchButtonTitle = $loc["DescriptionShowButtonGroupsForm"];
			}
			else
			{
				$groupSearchDisabled = " disabled"; // disable the 'Show My Groups' form if the session variable holding the user's groups isnt't available
				$groupSearchSelectorTitle = "(" . $loc["DescriptionSelectFieldGroupsFormDisabled"] . ")";
				$groupSearchButtonTitle = "(" . $loc["DescriptionShowButtonGroupsFormDisabled"] . ")";
			}

			// adjust the form & dropdown labels according to the calling script (which is either 'search.php' or 'users.php')
			if ($href == "search.php")
			{
				$formLegend = $loc["ShowMyGroup"] . ":";
				$dropdownLabel = $loc["My"] . ":";
			}
			elseif ($href == "users.php")
			{
				$formLegend = $loc["ShowUserGroup"] . ":";
				$dropdownLabel = $loc["Users"] . ":";
			}
			else // currently, '$href' will be either 'search.php' or 'users.php', but anyhow
			{
				$formLegend = $loc["ShowGroup"] . ":";
				$dropdownLabel = "";
			}

			$encodedCiteStyle = rawurlencode($citeStyle);
			$encodedClient = rawurlencode($client);

			$groupSearchForm = <<<EOF
		<form action="$href" method="GET" name="groupSearch">
			<fieldset>
				<input type="hidden" name="formType" value="groupSearch">
				<input type="hidden" name="originalDisplayType" value="$displayType">
				<input type="hidden" name="sqlQuery" value="$queryURL">
				<input type="hidden" name="showQuery" value="$showQuery">
				<input type="hidden" name="showLinks" value="$showLinks">
				<input type="hidden" name="showRows" value="$showRows">
				<input type="hidden" name="client" value="$encodedClient">
				<input type="hidden" name="citeStyle" value="$encodedCiteStyle">
				<input type="hidden" name="citeOrder" value="$citeOrder">
				<legend>$formLegend</legend>
				<div id="groupSelect">
					<label for="groupSearchSelector">$dropdownLabel</label>
					<select id="groupSearchSelector" name="groupSearchSelector" title="$groupSearchSelectorTitle"$groupSearchDisabled>
EOF;

			if (($href == "search.php" AND isset($_SESSION['userGroups'])) OR ($href == "users.php" AND isset($_SESSION['adminUserGroups']))) // if the appropriate session variable is set
			{
				 // build properly formatted <option> tag elements from the items listed in the appropriate session variable:
				if ($href == "search.php")
					$optionTags = buildSelectMenuOptions($_SESSION['userGroups'], " *; *", "\t\t\t\t\t\t", false);
				elseif ($href == "users.php")
					$optionTags = buildSelectMenuOptions($_SESSION['adminUserGroups'], " *; *", "\t\t\t\t\t\t", false);

				if (!empty($currentGroup)) // if the current SQL query contains a 'WHERE' clause that searches for a particular user group
					$optionTags = preg_replace("#<option>(?=$currentGroup</option>)#i", "<option selected>", $optionTags); // we select that group by adding the 'selected' parameter to the appropriate <option> tag

				$groupSearchForm .= $optionTags;
			}
			else
				$groupSearchForm .= "<option>($loc[NoGroupsAvl])</option>";

			$groupSearchForm .= <<<EOF

					</select>
				</div>
				<div id="groupSubmit">
					<input type="submit" value="$loc[ButtonTitle_Show]" title="$groupSearchButtonTitle"$groupSearchDisabled>
				</div>
			</fieldset>
		</form>

EOF;
		}
		else
			$groupSearchForm = "";

		return $groupSearchForm;
	}

	// --------------------------------------------------------------------

	//	BUILD DISPLAY OPTIONS FORM ELEMENTS
	// (i.e., provide options to show/hide columns or change the number of records displayed per page)
	function buildDisplayOptionsElements($href, $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $citeStyle, $citeOrder, $dropDownFieldsArray, $dropDownFieldSelected, $fieldsToDisplay, $displayType, $headerMsg)
	{
		global $loc; // '$loc' is made globally available in 'core.php'

		global $client;

		if ($displayType == "Browse")
		{
			$submitValue = $loc["ButtonTitle_Browse"];
			$submitTitle = $loc["DescriptionShowButtonDisplayOptionsFormBrowseView"];
			$selectorDivID = "optShowHideField";
			$selectorID = "displayOptionsSelector";
			$selectorLabel = $loc["Field"] . ":";
			$selectorTitle = $loc["DescriptionSelectFieldDisplayOptionsFormBrowseView"];
			$showRowsLabel = $loc["ShowRecordsPerPage_SuffixBrowseView"];
			$showRowsTitle = $loc["DescriptionShowRecordsPerPageBrowseView"];
		}
		elseif ($displayType == "Cite")
		{
			$submitValue = $loc["ButtonTitle_Show"];
			$submitTitle = $loc["DescriptionShowButtonDisplayOptionsFormCiteView"];
			$selectorDivID = "optCiteStyle";
			$selectorID = "citeStyle";
			$selectorLabel = $loc["Style"] . ":";
			$selectorTitle = $loc["DescriptionSelectStyleDisplayOptionsFormCiteView"];
			$showRowsLabel = $loc["ShowRecordsPerPage_SuffixCiteView"];
			$showRowsTitle = $loc["DescriptionShowRecordsPerPage"];
		}
		elseif ($displayType == "Display")
		{
			$submitValue = $loc["ButtonTitle_Show"];
			$submitTitle = $loc["DescriptionShowButtonDisplayOptionsFormDetailsView"];
			$selectorDivID = "optShowHideField";
			$selectorID = "displayOptionsSelector";
			$selectorLabel = $loc["Field"] . ":";
			$selectorTitle = $loc["DescriptionSelectFieldDisplayOptionsFormDetailsView"];
			$showRowsLabel = $loc["ShowRecordsPerPage_Suffix"];
			$showRowsTitle = $loc["DescriptionShowRecordsPerPage"];
		}
		else
		{
			$submitValue = $loc["ButtonTitle_Show"];
			$submitTitle = $loc["DescriptionShowButtonDisplayOptionsForm"];
			$selectorDivID = "optShowHideField";
			$selectorID = "displayOptionsSelector";
			$selectorLabel = $loc["Field"] . ":";
			$selectorTitle = $loc["DescriptionSelectFieldDisplayOptionsForm"];
			$showRowsLabel = $loc["ShowRecordsPerPage_Suffix"];
			$showRowsTitle = $loc["DescriptionShowRecordsPerPage"];
		}

		if (($displayType != "Cite") AND ($fieldsToDisplay < 2))
		{
			$hideButtonDisabled = " disabled"; // disable the 'Hide' button if there's currently only one field being displayed (except the links column)
			$hideButtonTitle = "(" . $loc["DescriptionHideButtonDisplayOptionsFormOnlyOneField"] . ")";
		}
		else
		{
			$hideButtonDisabled = "";

			if ($displayType == "Display")
				$hideButtonTitle = $loc["DescriptionHideButtonDisplayOptionsFormDetailsView"];
			else
				$hideButtonTitle = $loc["DescriptionHideButtonDisplayOptionsForm"];
		}

		if (($displayType == "Cite") AND (!isset($_SESSION['user_styles'])))
			$citeStyleDisabled = " disabled"; // for Citation view, disable the style popup if the session variable holding the user's styles isn't available
		else
			$citeStyleDisabled = "";

		$encodedCiteStyle = rawurlencode($citeStyle);
		$encodedClient = rawurlencode($client);
		$encodedHeaderMsg = rawurlencode($headerMsg);

		$accessKeyAttribute = addAccessKey("attribute", "max_rows");
		$accessKeyTitle = addAccessKey("title", "max_rows");

		// NOTE: we embed the current value of '$rowOffset' as hidden tag within the 'displayOptions' form. By this, the current row offset can be re-applied after the user pressed the 'Show'/'Hide' button within the 'displayOptions' form.
		//       To avoid that browse links don't behave as expected, the actual value of '$rowOffset' will be adjusted in function 'seekInMySQLResultsToOffset()' to an exact multiple of '$showRows'!
		$displayOptionsForm = <<<EOF
		<form action="$href" method="GET" name="displayOptions">
			<fieldset>
				<input type="hidden" name="formType" value="displayOptions">
				<input type="hidden" name="submit" value="$submitValue">
				<input type="hidden" name="originalDisplayType" value="$displayType">
				<input type="hidden" name="sqlQuery" value="$queryURL">
				<input type="hidden" name="showQuery" value="$showQuery">
				<input type="hidden" name="showLinks" value="$showLinks">
				<input type="hidden" name="rowOffset" value="$rowOffset">
				<input type="hidden" name="showRows" value="$showRows">
				<input type="hidden" name="client" value="$encodedClient">
				<input type="hidden" name="citeStyle" value="$encodedCiteStyle">
				<input type="hidden" name="citeOrder" value="$citeOrder">
				<input type="hidden" name="headerMsg" value="$encodedHeaderMsg">
				<legend>$loc[DisplayOptions]:</legend>
				<div id="optMain">
					<div id="$selectorDivID">
						<label for="$selectorID">$selectorLabel</label>
						<select id="$selectorID" name="$selectorID" title="$selectorTitle"$citeStyleDisabled>
EOF;

		// build correct option tags from the column items provided:
		$optionTags = buildSelectMenuOptions($dropDownFieldsArray, "", "\t\t\t\t\t\t\t", true);

		$optionTags = ereg_replace("<option([^>]* value=\"$dropDownFieldSelected\")", "<option\\1 selected", $optionTags); // add 'selected' attribute

		$displayOptionsForm .= $optionTags;

		$displayOptionsForm .= <<<EOF

						</select>
					</div>
EOF;

		$displayOptionsForm .= <<<EOF

					<div id="optSubmit">
						<input type="submit" name="submit" value="$submitValue" title="$submitTitle">
EOF;

		if (!eregi("^(Browse|Cite)$", $displayType))
			$displayOptionsForm .= "\n\t\t\t\t\t\t<input type=\"submit\" name=\"submit\" value=\"" . $loc["ButtonTitle_Hide"] . "\" title=\"$hideButtonTitle\"$hideButtonDisabled>";

		$displayOptionsForm .= <<<EOF

					</div>
				</div>
				<div id="optOther">
EOF;

		if ($displayType == "Cite")
		{
			$displayOptionsForm .= <<<EOF

					<div id="optCiteOrder">
						<label for="citeOrder">$loc[SortBy]:</label>
						<select id="citeOrder" name="citeOrder" title="$loc[DescriptionSelectOrderDisplayOptionsFormCiteView]">
EOF;

			// build correct option tags for the "Sort by" ('citeOrder') dropdown menu (and select the currently chosen option):
			$citeOrderItemsArray = array("author"        => $loc["DropDownFieldName_Author"],
			                             "year"          => $loc["DropDownFieldName_Year"],
			                             "type"          => $loc["DropDownFieldName_Type"],
			                             "type-year"     => $loc["DropDownFieldName_TypeYear"],
			                             "creation-date" => $loc["DropDownFieldName_CreationDate"]);

			$citeOrderOptionTags = buildSelectMenuOptions($citeOrderItemsArray, "", "\t\t\t\t\t\t\t", true);

			if (isset($citeOrderItemsArray[$citeOrder]))
				$citeOrderOptionTags = ereg_replace("<option([^>]*)>(" . $citeOrderItemsArray[$citeOrder] . ")</option>", "<option\\1 selected>\\2</option>", $citeOrderOptionTags); // add 'selected' attribute to the currently chosen 'citeOrder' option
			else // add & select a "(custom order)" option (which indicates that the current sort order matches none of the above 'citeOrder' options):
				$citeOrderOptionTags = "\n\t\t\t\t\t\t\t<option value=\"\" selected>(" . $loc["DropDownFieldName_Custom"] . ")</option>" . $citeOrderOptionTags;

			$displayOptionsForm .= $citeOrderOptionTags;

			$displayOptionsForm .= <<<EOF

						</select>
					</div>
EOF;
		}

		$displayOptionsForm .= <<<EOF

					<div id="optRecsPerPage">
						<input type="text" id="showRows" name="showRows" value="$showRows" size="4"$accessKeyAttribute title="$showRowsTitle$accessKeyTitle">
						<label for="showRows">$showRowsLabel</label>
					</div>
				</div>
			</fieldset>
		</form>

EOF;

		return $displayOptionsForm;
	}

	// --------------------------------------------------------------------

	//	BUILD SUGGEST ELEMENTS
	// (i.e., provide HTML elements that will generate auto-completions or search suggestions for text entered by the user in text entry fields)
	// requires the Prototype & script.aculo.us JavaScript frameworks: <http://www.prototypejs.org/> and <http://script.aculo.us/>
	// more info about 'Ajax.Autocompleter': <http://github.com/madrobby/scriptaculous/wikis/ajax-autocompleter>
	// 
	// NOTE: I don't know how to pass custom values (such as the CQL index) to the callback function. Therefore, I'm using a dirty hack here where I add
	//       '$CQLIndex' (i.e. an "id-" prefix plus the ID of the HTML form element that contains the selected field) at the beginning of the query parameter
	//       ('paramName'). This ID is required by the callback function to fetch the name of the currently selected refbase field.
	function buildSuggestElements($searchFieldID, $searchSuggestionsID, $suggestProgressID, $CQLIndex, $prefix = "\t\t", $tokens = "''", $frequency = 0.8, $minChars = 2, $callBack = "addCQLIndex", $suggestURL = "opensearch.php", $paramName = "query", $parameters = "operation=suggest&recordSchema=html")
	{
		global $contentTypeCharset; // defined in 'ini.inc.php'

		$suggestElements = <<<EOF

$prefix<span id="$suggestProgressID" class="suggestProgress" style="display:none;">...</span>
$prefix<div id="$searchSuggestionsID" class="searchSuggestions" style="display:none;"></div>
$prefix<script language="JavaScript" type="text/javascript" charset="$contentTypeCharset">
$prefix// <![CDATA[
$prefix	new Ajax.Autocompleter('$searchFieldID','$searchSuggestionsID','$suggestURL',{tokens:$tokens,frequency:$frequency,minChars:$minChars,indicator:'$suggestProgressID',paramName:'$CQLIndex$paramName',parameters:'$parameters',callback:$callBack});
$prefix// ]]>
$prefix</script>
EOF;

		return $suggestElements;
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the "Search within Results" or "Display Options" forms
	// above the query results list (which, in turn, was returned by 'search.php' or 'users.php', respectively):
	// TODO: - build the complete SQL query using functions 'buildFROMclause()' and 'buildORDERclause()'
	function extractFormElementsRefineDisplay($queryTable, $displayType, $originalDisplayType, $query, $showLinks, $citeOrder, $userID)
	{
		global $tableRefs, $tableUserData, $tableUsers; // defined in 'db.inc.php'

		global $loc; // '$loc' is made globally available in 'core.php'

		$encodedDisplayType = encodeHTML($displayType); // note that we need to HTML encode '$displayType' for comparison with the HTML encoded locales

		// extract form variables:
		if ($encodedDisplayType == $loc["ButtonTitle_Search"]) // the user clicked the 'Search' button of the "Search within Results" form
		{
			$fieldSelector = $_REQUEST['refineSearchSelector']; // extract field name chosen by the user
			$refineSearchName = $_REQUEST['refineSearchName']; // extract search text entered by the user

			if (isset($_REQUEST['refineSearchExclude'])) // extract user option whether matched records should be included or excluded
				$refineSearchActionCheckbox = $_REQUEST['refineSearchExclude']; // the user marked the checkbox next to "Exclude matches"
			else
				$refineSearchActionCheckbox = "0"; // the user did NOT mark the checkbox next to "Exclude matches"
		}

		elseif (ereg("^(" . $loc["ButtonTitle_Show"] . "|" . $loc["ButtonTitle_Hide"] . "|" . $loc["ButtonTitle_Browse"] . ")$", $encodedDisplayType)) // the user clicked either the 'Browse' or 'Show'/'Hide' buttons of the "Display Options" form
		// (hitting <enter> within the 'ShowRows' text entry field of the "Display Options" form will act as if the user clicked the 'Browse'/'Show' button)
		{
			if (isset($_REQUEST['displayOptionsSelector']))
				$fieldSelector = $_REQUEST['displayOptionsSelector']; // extract field name chosen by the user
			else
				$fieldSelector = "";
		}
		else
			$fieldSelector = ""; // this avoids 'Undefined variable...' messages when a user has changed the language setting on the options page, and then reloads an existing page (whose URL still has a 'submit' value in the previously used language)

		// extract the fields of the SELECT clause from the current SQL query:
		$previousSelectClause = extractSELECTclause($query);

		// ensure to add any required fields to the SELECT clause:
		if ($queryTable == $tableRefs) // 'search.php':
			$addRequiredFields = true;
		elseif ($queryTable == $tableUsers) // 'users.php':
			$addRequiredFields = false; // we'll add any required fields to the 'users.php' SELECT clause below
		                                // TODO: this wouldn't be necessary if function 'buildSELECTclause()' would handle the requirements of 'users.php'

		$additionalFields = "";

		if ($encodedDisplayType == $loc["ButtonTitle_Search"])
		{
			// rebuild the current SELECT clause:
			$newSelectClause = buildSELECTclause($originalDisplayType, $showLinks, $additionalFields, false, $addRequiredFields, $previousSelectClause);

			// replace current SELECT clause:
			$query = newSELECTclause($newSelectClause, $query, false);

			if ($refineSearchName != "") // if the user typed a search string into the text entry field...
			{
				// Depending on the chosen output action, construct an appropriate SQL query:
				if ($refineSearchActionCheckbox == "0") // if the user did NOT mark the checkbox next to "Exclude matches"
				{
					// for the fields 'marked=no', 'copy=false' and 'selected=no', force NULL values to be matched:
					if (($fieldSelector == "marked" AND $refineSearchName == "no") OR ($fieldSelector == "copy" AND $refineSearchName == "false") OR ($fieldSelector == "selected" AND $refineSearchName == "no"))
						$query = eregi_replace(" WHERE ", " WHERE ($fieldSelector RLIKE " . quote_smart($refineSearchName) . " OR $fieldSelector IS NULL) AND ", $query); // ...add search field name & value to the SQL query
					else // add default 'WHERE' clause:
						$query = eregi_replace(" WHERE ", " WHERE $fieldSelector RLIKE " . quote_smart($refineSearchName) . " AND ", $query); // ...add search field name & value to the SQL query
				}
				else // $refineSearchActionCheckbox == "1" // if the user marked the checkbox next to "Exclude matches"
				{
					$query = eregi_replace(" WHERE ", " WHERE ($fieldSelector NOT RLIKE " . quote_smart($refineSearchName) . " OR $fieldSelector IS NULL) AND ", $query); // ...add search field name & value to the SQL query
				}

				$query = eregi_replace(' AND serial RLIKE "\.\+"', '', $query); // remove any 'AND serial RLIKE ".+"' which isn't required anymore
			}
			// else, if the user did NOT type a search string into the text entry field, we simply keep the old WHERE clause...
		}


		elseif (ereg("^(" . $loc["ButtonTitle_Show"] . "|" . $loc["ButtonTitle_Hide"] . ")$", $encodedDisplayType)) // the user clicked the 'Show'/'Hide' buttons of the "Display Options" form
		{
			if (eregi("^Cite$", $originalDisplayType)) // in case of Citation view, we regenerate the SELECT clause from scratch:
			{
				// generate a SELECT clause that's appropriate for Citation view (or Details view):
				$newSelectClause = buildSELECTclause($originalDisplayType, $showLinks, $additionalFields, false, $addRequiredFields);

				// rebuild the current ORDER clause:
				if (eregi("^(author|year|type|type-year|creation-date)$", $citeOrder))
				{
					if ($citeOrder == "year") // sort records first by year (descending):
						$newORDER = "ORDER BY year DESC, first_author, author_count, author, title";

					elseif ($citeOrder == "type") // sort records first by record type and thesis type (descending):
						$newORDER = "ORDER BY type DESC, thesis DESC, first_author, author_count, author, year, title";

					elseif ($citeOrder == "type-year") // sort records first by record type and thesis type (descending), then by year (descending):
						$newORDER = "ORDER BY type DESC, thesis DESC, year DESC, first_author, author_count, author, title";

					elseif ($citeOrder == "creation-date") // sort records such that newly added/edited records get listed top of the list:
						$newORDER = "ORDER BY created_date DESC, created_time DESC, modified_date DESC, modified_time DESC, serial DESC";

					elseif ($citeOrder == "author") // supply the default ORDER BY pattern (which is suitable for citation in a journal etc.):
						$newORDER = "ORDER BY first_author, author_count, author, year, title";

					// replace current ORDER clause:
					$query = newORDERclause($newORDER, $query, false);
				}
				// else if any other or no '$citeOrder' parameter is specified, we keep the current ORDER BY clause
				// NOTE: this behaviour is different from functions 'extractFormElementsQueryResults()' and 'extractFormElementsExtract()'
				//       where we always use 'ORDER BY first_author, author_count, author, year, title' as default ORDER BY clause
				//       (to ensure correct sorting for output to bibliographic reference lists)
			}

			elseif (eregi("^Display$", $originalDisplayType)) // Details view
			{
				// NOTE: the below code for displaying & hiding of fields in Details view must be adopted if either layout or field names are changed!

				$fieldsList = "";

				if ($fieldSelector == "all fields")
				{
					// generate a SELECT clause that shows all fields in Details view:
					$newSelectClause = buildSELECTclause($originalDisplayType, $showLinks, $additionalFields, true, $addRequiredFields);
				}
				else // add (or remove) the chosen fields from the SELECT clause:
				{
					if ($encodedDisplayType == $loc["ButtonTitle_Show"]) // if the user clicked the 'Show' button, add the chosen fields to the SELECT clause:
					{
						$matchField = "pages";

						if ($fieldSelector == "keywords, abstract")
						{
							$fieldsList = ", keywords, abstract";
						}
						elseif ($fieldSelector == "additional fields")
						{
							$fieldsList = ", address, corporate_author, thesis, publisher, place, editor, language, summary_language, orig_title, series_editor, series_title, abbrev_series_title, series_volume, series_issue, edition, issn, isbn, medium, area, expedition, conference, notes, approved";

							if (isset($_SESSION['loginEmail']))
								$fieldsList .= ", location"; // we only add the 'location' field if the user is logged in

							if (preg_match("/\babstract\b/i", $previousSelectClause))
								$matchField = "abstract";
						}
						elseif ($fieldSelector == "my fields")
						{
							$fieldsList = ", marked, copy, selected, user_keys, user_notes, user_file, user_groups, cite_key";

							if (preg_match("/\bserial\b/i", $previousSelectClause))
								$matchField = "serial";
							elseif (preg_match("/\babstract\b/i", $previousSelectClause))
								$matchField = "abstract";
						}

						if ((!empty($fieldsList)) AND (!preg_match("/\b" . $fieldsList . "\b/i", $previousSelectClause))) // if none of the chosen fields are currently displayed...
							$previousSelectClause = preg_replace("/(?<=\b" . $matchField . "\b)/i", $fieldsList, $previousSelectClause); // ...add the chosen fields to the current SELECT clause:
					}
					if ($encodedDisplayType == $loc["ButtonTitle_Hide"]) // if the user clicked the 'Hide' button, remove the chosen fields from the SELECT clause:
					{
						if ($fieldSelector == "keywords, abstract")
							$fieldsList = "\b(keywords|abstract)\b";
						elseif ($fieldSelector == "additional fields")
							$fieldsList = "\b(corporate_author|thesis|address|publisher|place|editor|language|summary_language|orig_title|series_editor|series_title|abbrev_series_title|series_volume|series_issue|edition|issn|isbn|medium|area|expedition|conference|notes|approved|location)\b";
						elseif ($fieldSelector == "my fields")
							$fieldsList = "\b(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key)\b";

						if ((!empty($fieldsList)) AND (preg_match("/\b" . $fieldsList . "\b/i", $previousSelectClause))) // ...and any of the chosen fields *are* currently displayed...
						{
							// ...remove the chosen fields from the fields given in the current SELECT clause:
							$previousSelectClause = preg_replace("/ *, *" . $fieldsList . " */i", "", $previousSelectClause); // all columns except the first

							$previousSelectClause = preg_replace("/ *" . $fieldsList . " *, */i", "", $previousSelectClause); // all columns except the last
						}
					}

					// rebuild the current SELECT clause, but include (or exclude) the chosen fields:
					$newSelectClause = buildSELECTclause($originalDisplayType, $showLinks, $additionalFields, false, $addRequiredFields, $previousSelectClause);
				}
			}

			else // otherwise, i.e. for List view, add (or remove) the chosen field from the SELECT clause:
			{
				if ($encodedDisplayType == $loc["ButtonTitle_Show"]) // if the user clicked the 'Show' button...
				{
					if (!preg_match("/\b" . $fieldSelector . "\b/i", $previousSelectClause)) // ...and the chosen field is *not* already displayed...
						$additionalFields = $fieldSelector; // ...add the chosen field to the current SELECT clause
				}
				elseif ($encodedDisplayType == $loc["ButtonTitle_Hide"]) // if the user clicked the 'Hide' button...
				{
					if (preg_match("/\b" . $fieldSelector . "\b/i", $previousSelectClause)) // ...and the chosen field *is* currently displayed...
					{
						// ...remove the chosen field from the fields given in the current SELECT clause:
						$previousSelectClause = preg_replace("/ *, *\b" . $fieldSelector . "\b */i", "", $previousSelectClause); // all columns except the first
						$previousSelectClause = preg_replace("/ *\b" . $fieldSelector . "\b *, */i", "", $previousSelectClause); // all columns except the last
					}
				}

				// rebuild the current SELECT clause, but include (or exclude) the chosen field:
				$newSelectClause = buildSELECTclause("", $showLinks, $additionalFields, false, $addRequiredFields, $previousSelectClause);
			}

			// replace current SELECT clause:
			$query = newSELECTclause($newSelectClause, $query, false);
		}


		// TODO: don't manipulate the SQL query in '$query' directly, but instead use functions 'extractSELECTclause()' and 'buildSELECTclause()' (similar as above)
		elseif ($encodedDisplayType == $loc["ButtonTitle_Browse"]) // if the user clicked the 'Browse' button within the "Display Options" form...
		{
			$previousField = preg_replace("/^SELECT (\w+).+/i", "\\1", $query); // extract the field that was previously used in Browse view

			if (!eregi("^" . $fieldSelector . "$", $previousField)) // if the user did choose another field in Browse view...
			{
				// ...modify the SQL query to show a summary for the new field that was chosen by the user:
				// (NOTE: these replace patterns aren't 100% safe and may fail if the user has modified the query using 'sql_search.php'!)
				$query = preg_replace("/^SELECT $previousField/i", "SELECT $fieldSelector", $query); // use the field that was chosen by the user for Browse view
				$query = preg_replace("/GROUP BY $previousField/i", "GROUP BY $fieldSelector", $query); // group data by the field that was chosen by the user
				$query = preg_replace("/ORDER BY( records( DESC)?,)? $previousField/i", "ORDER BY\\1 $fieldSelector", $query); // order data by the field that was chosen by the user
			}
		}


		// re-establish the original display type:
		// (resetting '$displayType' to its original value is required for Browse view; for List view, it does also correct incorrect
		//  display types such as 'Search' or 'Show'/'Hide' which stem from the submit buttons in the forms of the results header)
		$displayType = $originalDisplayType;


		// the following changes to the SQL query are performed for both forms ("Search within Results" and "Display Options"):
		if ($queryTable == $tableRefs) // 'search.php':
		{
			// if the chosen field is one of the user-specific fields from table 'user_data': 'marked', 'copy', 'selected', 'user_keys', 'user_notes', 'user_file', 'user_groups', 'cite_key' or 'related'
			if (eregi("^(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related|my fields)$", $fieldSelector)) // 'my fields' is used in Details view as an alias for all user-specific fields
				if (!eregi("LEFT JOIN $tableUserData", $query)) // ...and if the 'LEFT JOIN...' statement isn't already part of the 'FROM' clause...
					$query = eregi_replace(" FROM $tableRefs", " FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = $userID", $query); // ...add the 'LEFT JOIN...' part to the 'FROM' clause
		}
		elseif ($queryTable == $tableUsers) // 'users.php':
		{
			// TODO: this wouldn't be necessary if function 'buildSELECTclause()' would handle the requirements of 'users.php' (see also above)
			$query = eregi_replace(" FROM $tableUsers", ", user_id FROM $tableUsers", $query); // add 'user_id' column (although it won't be visible the 'user_id' column gets included in every search query)
			                                                                                 // (which is required in order to obtain unique checkbox names as well as for use in the 'getUserID()' function)
		}

		return array($query, $displayType);
	}

	// --------------------------------------------------------------------

	// SPLIT AND MERGE AGAIN
	// (this function takes a string and splits it on '$splitDelim' into an array, then re-joins the pieces inserting '$joinDelim' as separator)
	function splitAndMerge($splitDelim, $joinDelim, $sourceString)
	{
		// split the string on the specified delimiter (which is interpreted as regular expression!):
		$piecesArray = split($splitDelim, $sourceString);

		// re-join the array with the specified separator:
		$newString = implode($joinDelim, $piecesArray);

		return $newString;
	}

	// --------------------------------------------------------------------

	// EXTRACT PARTS FROM STRING
	// this function takes a '$sourceString', splits it on '$splitDelim' and returns x parts from the
	// beginning (if x > 0) or the end (if x < 0) of the string (x must be given in '$returnParts');
	// parts will be returned as a merged string using '$joinDelim' as delimiter
	function extractPartsFromString($sourceString, $splitDelim, $joinDelim, $returnParts)
	{
		// split the string on the specified delimiter (which is interpreted as perl-style regular expression!):
		$piecesArray = preg_split($splitDelim, $sourceString);

		if ($returnParts > 0)
			$spliceFromElementNo = 0; // splice from beginning of array
		else // ($returnParts < 0)
		{
			$spliceFromElementNo = $returnParts; // splice from end of array
			$returnParts = abs($returnParts);
		}

		// extracts parts from array:
		$extractedPiecesArray = array_splice($piecesArray, $spliceFromElementNo, $returnParts); // 'array_splice()' returns array with extracted elements

		// re-join the array with the specified separator:
		$newString = implode($joinDelim, $extractedPiecesArray);

		return $newString;
	}

	// --------------------------------------------------------------------

	// RE-ARRANGE AUTHOR FIELD CONTENTS
	// (this function separates contents of the author field into their functional parts, i.e.:
	// 		{
	//			{author_name}, {author_initial(s)}
	//		}
	// 		{
	//			{author_name}, {author_initial(s)}
	//		}
	// 		{
	//			...
	//		}
	//  then, these functional pieces will be joined again according to the separators specified)
	//  Note: this function assumes that:
	//			- within one author object, there's only *one* delimiter separating author name & initials!
	function reArrangeAuthorContents($authorContents, $familyNameFirst, $oldBetweenAuthorsDelim, $newBetweenAuthorsDelimStandard, $newBetweenAuthorsDelimLastAuthor, $oldAuthorsInitialsDelim, $newAuthorsInitialsDelimFirstAuthor, $newAuthorsInitialsDelimStandard, $betweenInitialsDelim, $initialsBeforeAuthorFirstAuthor, $initialsBeforeAuthorStandard, $shortenGivenNames, $numberOfAuthorsTriggeringEtAl, $includeNumberOfAuthors, $customStringAfterFirstAuthors, $encodeHTML)
	{
		global $alnum, $alpha, $cntrl, $dash, $digit, $graph, $lower, $print, $punct, $space, $upper, $word, $patternModifiers; // defined in 'transtab_unicode_charset.inc.php' and 'transtab_latin1_charset.inc.php'

		$authorsArray = split($oldBetweenAuthorsDelim, $authorContents); // get a list of all authors for this record

		$authorCount = count($authorsArray); // check how many authors we have to deal with
		$newAuthorContents = ""; // this variable will hold the final author string
		$includeStringAfterFirstAuthor = false;

		if (empty($numberOfAuthorsTriggeringEtAl))
			$numberOfAuthorsTriggeringEtAl = $authorCount;

		if (empty($includeNumberOfAuthors))
			$includeNumberOfAuthors = $authorCount;

		for ($i=0; $i < $authorCount; $i++)
		{
			$singleAuthorArray = split($oldAuthorsInitialsDelim, $authorsArray[$i]); // for each author, extract author name & initials to separate list items

			if (!$familyNameFirst) // if the family name comes *after* the given name (or initials) in the source string, put array elements in reverse order:
				$singleAuthorArray = array_reverse($singleAuthorArray); // (Note: this only works, if the array has only *two* elements, i.e., one containing the author's name and one holding the initials!)

			if (isset($singleAuthorArray[1]))
			{
				if ($shortenGivenNames) // if we're supposed to abbreviate given names
				{
					// within initials, reduce all full first names (-> defined by a starting uppercase character, followed by one ore more lowercase characters)
					// to initials, i.e., only retain their first character
					$singleAuthorArray[1] = preg_replace("/([$upper])[$lower]+/$patternModifiers", "\\1", $singleAuthorArray[1]);
				}

				// within initials, remove any dots:
				$singleAuthorArray[1] = preg_replace("/([$upper])\.+/$patternModifiers", "\\1", $singleAuthorArray[1]);

				// within initials, remove any spaces *between* initials:
				$singleAuthorArray[1] = preg_replace("/(?<=[-$upper]) +(?=[-$upper])/$patternModifiers", "", $singleAuthorArray[1]);

				// within initials, add a space after a hyphen, but only if ...
				if (ereg(" $", $betweenInitialsDelim)) // ... the delimiter that separates initials ends with a space
					$singleAuthorArray[1] = preg_replace("/-(?=[$upper])/$patternModifiers", "- ", $singleAuthorArray[1]);

				// then, separate initials with the specified delimiter:
				$singleAuthorArray[1] = preg_replace("/([$upper])(?=[^$lower]+|$)/$patternModifiers", "\\1$betweenInitialsDelim", $singleAuthorArray[1]);
			}


			if ((($i == 0) AND $initialsBeforeAuthorFirstAuthor) OR (($i > 0) AND $initialsBeforeAuthorStandard)) // put array elements in reverse order:
				$singleAuthorArray = array_reverse($singleAuthorArray); // (Note: this only works, if the array has only *two* elements, i.e., one containing the author's name and one holding the initials!)

			// re-join author name & initials, using the specified delimiter, and copy the string to the end of an array:
			if ($i == 0) // -> first author
				$singleAuthorString = implode($newAuthorsInitialsDelimFirstAuthor, $singleAuthorArray);
			else // $i > 0 // -> all authors except the first one
				$singleAuthorString = implode($newAuthorsInitialsDelimStandard, $singleAuthorArray);

			// append this author to the final author string:
			if (($i == 0) OR ($i + 1) < $authorCount) // -> first author, or (for multiple authors) all authors except the last one
			{
				if ($i == 0) // -> first author
					$newAuthorContents .= $singleAuthorString;
				else // -> for multiple authors, all authors except the first or the last one
					$newAuthorContents .= $newBetweenAuthorsDelimStandard . $singleAuthorString;

				// we'll append the string in '$customStringAfterFirstAuthors' to the number of authors given in '$includeNumberOfAuthors' if the total number of authors is greater than the number given in '$numberOfAuthorsTriggeringEtAl':
				if ((($i + 1) == $includeNumberOfAuthors) AND ($authorCount > $numberOfAuthorsTriggeringEtAl))
				{
					if (ereg("__NUMBER_OF_AUTHORS__", $customStringAfterFirstAuthors))
						$customStringAfterFirstAuthors = preg_replace("/__NUMBER_OF_AUTHORS__/", ($authorCount - $includeNumberOfAuthors), $customStringAfterFirstAuthors); // resolve placeholder

					$includeStringAfterFirstAuthor = true;
					break;
				}
			}
			elseif (($authorCount > 1) AND (($i + 1) == $authorCount)) // -> last author (if multiple authors)
			{
				$newAuthorContents .= $newBetweenAuthorsDelimLastAuthor . $singleAuthorString;
			}
		}

		// do some final clean up:
		if ($encodeHTML)
			$newAuthorContents = encodeHTML($newAuthorContents); // HTML encode higher ASCII characters within the newly arranged author contents

		if ($includeStringAfterFirstAuthor)
			$newAuthorContents .= $customStringAfterFirstAuthors; // the custom string won't get HTML encoded so that it's possible to include HTML tags (such as '<i>') within the string

		$newAuthorContents = preg_replace("/  +/", " ", $newAuthorContents); // remove double spaces (which occur e.g., when both, $betweenInitialsDelim & $newAuthorsInitialsDelim..., end with a space)
		$newAuthorContents = preg_replace("/ +([,.;:?!()]|$)/", "\\1", $newAuthorContents); // remove excess spaces before [,.;:?!()] and from the end of the author string

		return $newAuthorContents;
	}

	// --------------------------------------------------------------------

	// EXTRACT AUTHOR'S LAST NAME
	// this function takes the contents of the author field and will extract the last name of a particular author (specified by position)
	// (e.g., setting '$authorPosition' to "1" will return the 1st author's last name)
	//  Note: this function assumes that:
	//        1. within one author object, there's only *one* delimiter separating author name & initials!
	//        2. author objects are stored in the db as "<author_name><author_initials_delimiter><author_initials>", i.e., initials follow *after* the author's name!
	//  Required Parameters:
	//        1. pattern describing delimiter that separates different authors
	//        2. pattern describing delimiter that separates author name & initials (within one author)
	//        3. position of the author whose last name shall be extracted (e.g., "1" will return the 1st author's last name)
	//        4. contents of the author field
	function extractAuthorsLastName($oldBetweenAuthorsDelim, $oldAuthorsInitialsDelim, $authorPosition, $authorContents)
	{
		$authorsArray = split($oldBetweenAuthorsDelim, $authorContents); // get a list of all authors for this record

		$authorPosition = ($authorPosition-1); // php array elements start with "0", so we decrease the authors position by 1
		$singleAuthor = $authorsArray[$authorPosition]; // for the author in question, extract the full author name (last name & initials)
		$singleAuthorArray = split($oldAuthorsInitialsDelim, $singleAuthor); // then, extract author name & initials to separate list items
		$singleAuthorsLastName = $singleAuthorArray[0]; // extract this author's last name into a new variable

		return $singleAuthorsLastName;
	}

	// --------------------------------------------------------------------

	// EXTRACT AUTHOR'S GIVEN NAME
	// this function takes the contents of the author field and will extract the initials/given name of a particular author (specified by position)
	// (e.g., setting '$authorPosition' to "1" will return the 1st author's initials/given name)
	//  Required Parameters:
	//        1. pattern describing delimiter that separates different authors
	//        2. pattern describing delimiter that separates author name & initials/given name (within one author)
	//        3. position of the author whose last name shall be extracted (e.g., "1" will return the 1st author's initials/given name)
	//        4. contents of the author field
	function extractAuthorsGivenName($oldBetweenAuthorsDelim, $oldAuthorsInitialsDelim, $authorPosition, $authorContents)
	{
		$authorsArray = split($oldBetweenAuthorsDelim, $authorContents); // get a list of all authors for this record

		$authorPosition = ($authorPosition-1); // php array elements start with "0", so we decrease the authors position by 1
		$singleAuthor = $authorsArray[$authorPosition]; // for the author in question, extract the full author name (last name & initials/given name)
		$singleAuthorArray = split($oldAuthorsInitialsDelim, $singleAuthor); // then, extract author name & initials/given name to separate list items
		if (!empty($singleAuthorArray[1]))
			$singleAuthorsGivenName = $singleAuthorArray[1]; // extract this author's initials/given name into a new variable
		else
			$singleAuthorsGivenName = '';

		return $singleAuthorsGivenName;
	}

	// --------------------------------------------------------------------

	// PARSE PLACEHOLDER STRING
	// this function will parse a given placeholder string into its indiviual placeholders and replaces
	// them with content from the given record
	function parsePlaceholderString($formVars, $placeholderString, $fallbackPlaceholderString)
	{
		global $alnum, $alpha, $cntrl, $dash, $digit, $graph, $lower, $print, $punct, $space, $upper, $word, $patternModifiers; // defined in 'transtab_unicode_charset.inc.php' and 'transtab_latin1_charset.inc.php'

		if (empty($placeholderString))
			$placeholderString = $fallbackPlaceholderString; // if, for some odd reason, an empty placeholder string was given, we'll use the placeholder(s) given in '$fallbackPlaceholderString'

		$placeholderPartsArray = split("[<>]", $placeholderString); // split placeholder string into its individual components

		$convertedPlaceholderArray = array(); // initialize array variable which will hold the transformed placeholder parts

		foreach($placeholderPartsArray as $placeholderPart)
		{
			if (!empty($placeholderPart))
			{
				if (preg_match("/:\w+/", $placeholderPart)) // if the part contains a colon (":") followed by one or more word characters the part is assumed to be a placeholder (this will e.g. exclude "http://" strings)
				{
					// extract any custom options given within a placeholder:
					if (preg_match("/:\w+\[[^][]+\]:/i", $placeholderPart)) // the placeholder contains custom options
						$options = preg_replace("/.*:\w+(\[[^][]+\]):.*/i", "\\1", $placeholderPart);
					else
						$options = "";

					// extract any prefix given within a placeholder:
					if (preg_match("/^[^:]+:\w+(\[[^][]*\])?:/i", $placeholderPart)) // the placeholder contains a prefix
						$prefix = preg_replace("/^([^:]+):\w+(\[[^][]*\])?:.*/i", "\\1", $placeholderPart);
					else
						$prefix = "";

					// extract any suffix given within a placeholder:
					if (preg_match("/:\w+(\[[^][]*\])?:[^:]+$/i", $placeholderPart)) // the placeholder contains a suffix
						$suffix = preg_replace("/.*:\w+(?:\[[^][]*\])?:([^:]+)$/i", "\\1", $placeholderPart);
					else
						$suffix = "";

					// call dedicated functions for the different placeholders (if required):

					// '<:serial:>' placeholder:
					if (preg_match("/:serial:/i", $placeholderPart))
						$convertedPlaceholderArray[] = $prefix . $formVars['serialNo'] . $suffix;

					// '<:firstAuthor:>' placeholder:
					elseif (preg_match("/:firstAuthor:/i", $placeholderPart))
					{
						if (!empty($formVars['authorName'])) // if the 'author' field isn't empty
						{
							$firstAuthor = $prefix;
							// Call the 'extractAuthorsLastName()' function to extract the last name of a particular author (specified by position):
							// (see function header for description of required parameters)
							$firstAuthor .= extractAuthorsLastName(" *; *",
							                                       " *, *",
							                                       1,
							                                       $formVars['authorName']);
							$firstAuthor .= $suffix;
							$convertedPlaceholderArray[] = $firstAuthor;
						}
					}

					// '<:secondAuthor:>' placeholder:
					elseif (preg_match("/:secondAuthor:/i", $placeholderPart))
					{
						if (!empty($formVars['authorName']) AND ereg(";", $formVars['authorName'])) // if the 'author' field does contain at least one ';' => at least two authors
						{
							$secondAuthor = $prefix;
							// Call the 'extractAuthorsLastName()' function to extract the last name of a particular author (specified by position):
							// (see function header for description of required parameters)
							$secondAuthor .= extractAuthorsLastName(" *; *",
							                                        " *, *",
							                                        2,
							                                        $formVars['authorName']);
							$secondAuthor .= $suffix;
							$convertedPlaceholderArray[] = $secondAuthor;
						}
					}

					// '<:authors:>' placeholder:
					elseif (preg_match("/:authors(\[[^][]*\])?:/i", $placeholderPart))
					{
						if (!empty($formVars['authorName'])) // if the 'author' field isn't empty
						{
							$authors = $prefix;
							$authors .= extractDetailsFromAuthors($formVars['authorName'], $options);
							$authors .= $suffix;
							$convertedPlaceholderArray[] = $authors;
						}
					}

					// '<:title:>' placeholder:
					elseif (preg_match("/:title(\[[^][]*\])?:/i", $placeholderPart))
					{
						if (!empty($formVars['titleName'])) // if the 'title' field isn't empty
						{
							$title = $prefix;
							$title .= extractDetailsFromField("title", $formVars['titleName'], "/[^-$word]+/$patternModifiers", $options);
							$title .= $suffix;
							$convertedPlaceholderArray[] = $title;
						}
					}

					// '<:year:>' placeholder:
					elseif (preg_match("/:year(\[[^][]*\])?:/i", $placeholderPart))
					{
						if (!empty($formVars['yearNo']) AND preg_match("/\d+/i", $formVars['yearNo'])) // if the 'year' field contains a number
						{
							$year = $prefix;
							$year .= extractDetailsFromYear($formVars['yearNo'], $options);
							$year .= $suffix;
							$convertedPlaceholderArray[] = $year;
						}
					}

					// '<:publication:>' placeholder:
					elseif (preg_match("/:publication(\[[^][]*\])?:/i", $placeholderPart))
					{
						if (!empty($formVars['publicationName'])) // if the 'publication' field isn't empty
						{
							$publication = $prefix;
							$publication .= extractDetailsFromField("publication", $formVars['publicationName'], "/[^-$word]+/$patternModifiers", $options);
							$publication .= $suffix;
							$convertedPlaceholderArray[] = $publication;
						}
					}

					// '<:abbrevJournal:>' placeholder:
					elseif (preg_match("/:abbrevJournal(\[[^][]*\])?:/i", $placeholderPart))
					{
						if (!empty($formVars['abbrevJournalName'])) // if the 'abbrev_journal' field isn't empty
						{
							$abbrevJournal = $prefix;
							$abbrevJournal .= extractDetailsFromField("abbrev_journal", $formVars['abbrevJournalName'], "/[^-$word]+/$patternModifiers", $options);
							$abbrevJournal .= $suffix;
							$convertedPlaceholderArray[] = $abbrevJournal;
						}
					}

					// '<:volume:>' placeholder:
					elseif (preg_match("/:volume:/i", $placeholderPart))
					{
						if (!empty($formVars['volumeNo'])) // if the 'volume' field isn't empty
							$convertedPlaceholderArray[] = $prefix . $formVars['volumeNo'] . $suffix;
					}

					// '<:issue:>' placeholder:
					elseif (preg_match("/:issue:/i", $placeholderPart))
					{
						if (!empty($formVars['issueNo'])) // if the 'issue' field isn't empty
							$convertedPlaceholderArray[] = $prefix . $formVars['issueNo'] . $suffix;
					}

					// '<:pages:>' placeholder:
					elseif (preg_match("/:pages:/i", $placeholderPart))
					{
						if (!empty($formVars['pagesNo'])) // if the 'pages' field isn't empty
							$convertedPlaceholderArray[] = $prefix . $formVars['pagesNo'] . $suffix;
					}

					// '<:startPage:>' placeholder:
					elseif (preg_match("/:startPage:/i", $placeholderPart))
					{
						if (!empty($formVars['pagesNo']) AND preg_match("/\d+/i", $formVars['pagesNo'])) // if the 'pages' field contains a number
						{
							$startPage = $prefix;
							$startPage .= preg_replace("/^\D*?(\w*\d+\w*).*/i", "\\1", $formVars['pagesNo']); // extract starting page
							$startPage .= $suffix;
							$convertedPlaceholderArray[] = $startPage;
						}
					}

					// '<:endPage:>' placeholder:
					elseif (preg_match("/:endPage:/i", $placeholderPart))
					{
						if (!empty($formVars['pagesNo']) AND preg_match("/\d+/i", $formVars['pagesNo'])) // if the 'pages' field contains a number
						{
							$pages = preg_replace("/^\D*?(\w*\d+\w*)( *[$dash]+ *\w*\d+\w*)?.*/i$patternModifiers", "\\1\\2", $formVars['pagesNo']); // extract page range (if there's any), otherwise just the first number
							$endPage = $prefix;
							$endPage .= extractDetailsFromField("pages", $pages, "/\D+/", "[-1]"); // we'll use this function instead of just grabbing a matched regex pattern since it'll also work when just a number but no range is given (e.g. when startPage = endPage)
							$endPage .= $suffix;
							$convertedPlaceholderArray[] = $endPage;
						}
					}

					// '<:keywords:>' placeholder:
					elseif (preg_match("/:keywords(\[[^][]*\])?:/i", $placeholderPart))
					{
						if (!empty($formVars['keywordsName'])) // if the 'keywords' field isn't empty
						{
							$keywords = $prefix;
							$keywords .= extractDetailsFromField("keywords", $formVars['keywordsName'], "/ *[;,] */", $options);
							$keywords .= $suffix;
							$convertedPlaceholderArray[] = $keywords;
						}
					}

					// '<:issn:>' placeholder:
					elseif (preg_match("/:issn:/i", $placeholderPart))
					{
						if (!empty($formVars['issnName']))
							$convertedPlaceholderArray[] = $prefix . $formVars['issnName'] . $suffix;
					}

					// '<:isbn:>' placeholder:
					elseif (preg_match("/:isbn:/i", $placeholderPart))
					{
						if (!empty($formVars['isbnName']))
							$convertedPlaceholderArray[] = $prefix . $formVars['isbnName'] . $suffix;
					}

					// '<:issn_isbn:>' placeholder:
					elseif (preg_match("/:issn_isbn:/i", $placeholderPart))
					{
						if (!empty($formVars['issnName'])) // if both, an ISSN and ISBN number are present, the ISSN number will be preferred
							$convertedPlaceholderArray[] = $prefix . $formVars['issnName'] . $suffix;
						elseif (!empty($formVars['isbnName']))
							$convertedPlaceholderArray[] = $prefix . $formVars['isbnName'] . $suffix;
					}

					// '<:area:>' placeholder:
					elseif (preg_match("/:area(\[[^][]*\])?:/i", $placeholderPart))
					{
						if (!empty($formVars['areaName'])) // if the 'area' field isn't empty
						{
							$area = $prefix;
							$area .= extractDetailsFromField("area", $formVars['areaName'], "/ *[;,] */", $options);
							$area .= $suffix;
							$convertedPlaceholderArray[] = $area;
						}
					}

					// '<:notes:>' placeholder:
					elseif (preg_match("/:notes(\[[^][]*\])?:/i", $placeholderPart))
					{
						if (!empty($formVars['notesName'])) // if the 'notes' field isn't empty
						{
							$notes = $prefix;
							$notes .= extractDetailsFromField("notes", $formVars['notesName'], "/[^-$word]+/$patternModifiers", $options);
							$notes .= $suffix;
							$convertedPlaceholderArray[] = $notes;
						}
					}

					// '<:userKeys:>' placeholder:
					elseif (preg_match("/:userKeys(\[[^][]*\])?:/i", $placeholderPart))
					{
						if (!empty($formVars['userKeysName'])) // if the 'user_keys' field isn't empty
						{
							$userKeys = $prefix;
							$userKeys .= extractDetailsFromField("user_keys", $formVars['userKeysName'], "/ *[;,] */", $options);
							$userKeys .= $suffix;
							$convertedPlaceholderArray[] = $userKeys;
						}
					}

					// '<:citeKey:>' placeholder:
					elseif (preg_match("/:citeKey:/i", $placeholderPart))
					{
						if (!empty($formVars['citeKeyName'])) // if the 'cite_key' field isn't empty
							$convertedPlaceholderArray[] = $prefix . $formVars['citeKeyName'] . $suffix;
					}

					// '<:doi:>' placeholder:
					elseif (preg_match("/:doi:/i", $placeholderPart))
					{
						if (!empty($formVars['doiName'])) // if the 'doi' field isn't empty
							$convertedPlaceholderArray[] = $prefix . $formVars['doiName'] . $suffix;
					}

					// '<:recordIdentifier:>' placeholder:
					elseif (preg_match("/:recordIdentifier:/i", $placeholderPart))
					{
						if (!empty($formVars['citeKeyName'])) // if the 'cite_key' field isn't empty
							$convertedPlaceholderArray[] = $prefix . $formVars['citeKeyName'] . $suffix; // if available, we prefer the user-specific cite key as unique record identifier
						else
							$convertedPlaceholderArray[] = $prefix . $formVars['serialNo'] . $suffix; // otherwise we'll use the record's serial number
					}

					// '<:randomNumber:>' placeholder:
					elseif (preg_match("/:randomNumber(\[[^][]*\])?:/i", $placeholderPart))
					{
						$randomString = $prefix;
						$randomString .= generateRandomNumber($options);
						$randomString .= $suffix;
						$convertedPlaceholderArray[] = $randomString;
					}

					// else: un-recognized placeholders will be ignored
				}

				else // the part is assumed to be a literal string
				{
					$convertedPlaceholderArray[] = $placeholderPart; // add part as is to array of transformed placeholder parts
				}
			}
		}

		$convertedPlaceholderString = implode("", $convertedPlaceholderArray); // merge transformed placeholder parts

		return $convertedPlaceholderString;
	}

	// --------------------------------------------------------------------

	// EXTRACT AUTHOR NAMES AND GENERATE IDENTIFIER STRING
	// this function takes the contents of the author field and generates an author identifier string (see comments below for
	// some examples) which is used by the file name (and cite key) auto-generation feature when replacing the <:authors:> placeholder
	function extractDetailsFromAuthors($authorString, $options)
	{
		global $extractDetailsAuthorsDefault; // defined in 'ini.inc.php'

		$returnRawAuthorString = false;

		if (empty($options)) // if '$options' is empty
			$options = $extractDetailsAuthorsDefault; // load the default options

		if (preg_match("/^\[-?\d*\|[^][|]*\|[^][|]*\]$/i", $options)) // if the '$options' variable contains a recognized syntax (minimum spec must be: "[||]", i.e., second and third option delimiters are not optional but must be specified!)
		{
			// extract the individual options:
			if (preg_match("/^\[-?\d+\|/i", $options)) // if the first option contains a number
			{
				$useMaxNoOfAuthors = preg_replace("/\[(-?\d+)\|[^][|]*\|[^][|]*\]/i", "\\1", $options); // regex note: to include a literal closing bracket (']') in a negated character class ('[^...]') it must be positioned right after the caret character ('^') such as in: '[^]...]'

				if ($useMaxNoOfAuthors == 0) // the special number '0' indicates that all authors shall be retrieved
					$useMaxNoOfAuthors = 250; // by assigning a very high number to '$useMaxNoOfAuthors' we should be pretty safe to catch all authors from the author field (extremely high values may choke the regular expression engine, though)

				elseif ($useMaxNoOfAuthors < 0) // negative numbers have currently no meaning and will be treated as if the corresponding positive number was given
					$useMaxNoOfAuthors = abs($useMaxNoOfAuthors);
			}

			elseif (preg_match("/^\[\|/i", $options)) // if the first option was left empty we assume that the raw author string shall be returned without any modification
				$returnRawAuthorString = true;

			$authorConnectorString = preg_replace("/\[-?\d*\|([^][|]*)\|[^][|]*\]/i", "\\1", $options);

			$etalIdentifierString = preg_replace("/\[-?\d*\|[^][|]*\|([^][|]*)\]/i", "\\1", $options);
		}
		else // use yet another fallback if the given options contain a buggy syntax
		{
			$useMaxNoOfAuthors = 2;
			$authorConnectorString = "+";
			$etalIdentifierString = "_etal";
		}

		if ($returnRawAuthorString)
			$authorDetails = $authorString; // return the raw author string without any modification
		else
		{
			$authorDetails = ""; // initialize variable which will hold the author identifier string

			// Add first author (plus additional authors if available up to the number of authors specified in '$useMaxNoOfAuthors');
			// but if more authors are present as in '$useMaxNoOfAuthors', add the contents of '$etalIdentifierString' after the first(!) author ignoring all other authors.
			// Example with '$extractDetailsAuthorsDefault = "[2|+|_etal]"':
			//   $authorString = "Steffens, M"                            -> $authorDetails = "Steffens"
			//   $authorString = "Steffens, M; Thomas, D"                 -> $authorDetails = "Steffens+Thomas"
			//   $authorString = "Steffens, M; Thomas, D; Dieckmann, GS"  -> $authorDetails = "Steffens_etal"
			// Example with '$extractDetailsAuthorsDefault = "[1|+|++]"':
			//   $authorString = "Steffens, M"                            -> $authorDetails = "Steffens"
			//   $authorString = "Steffens, M; Thomas, D"                 -> $authorDetails = "Steffens++"
			//   $authorString = "Steffens, M; Thomas, D; Dieckmann, GS"  -> $authorDetails = "Steffens++"
			for ($i=1; $i <= ($useMaxNoOfAuthors + 1); $i++)
			{
				if (preg_match("/^[^;]+(;[^;]+){" . ($i - 1) . "}/", $authorString)) // if the 'author' field does contain (at least) as many authors as specified in '$i'
				{

					if ($i>1)
					{
						if (preg_match("/^[^;]+(;[^;]+){" . $useMaxNoOfAuthors . "}/", $authorString)) // if the 'author' field does contain more authors as specified in '$useMaxNoOfAuthors'
						{
							$authorDetails .= $etalIdentifierString;
							break;
						}
						else
							$authorDetails .= $authorConnectorString;
					}

					// Call the 'extractAuthorsLastName()' function to extract the last name of a particular author (specified by position):
					// (see function header for description of required parameters)
					$authorDetails .= extractAuthorsLastName(" *; *",
					                                         " *, *",
					                                         $i,
					                                         $authorString);
				}
				else
					break;
			}
		}

		return $authorDetails;
	}

	// --------------------------------------------------------------------

	// EXTRACT YEAR
	// this function takes the contents of the year field and returns the year in two-digit
	// or four-digit format (depending on the given '$option' which must be either "[2]" or "[4]")
	function extractDetailsFromYear($yearString, $options)
	{
		global $extractDetailsYearDefault; // defined in 'ini.inc.php'

		if (empty($options)) // if '$options' is empty
			$options = $extractDetailsYearDefault; // load the default option

		if (preg_match("/^\[[24]\]$/i", $options)) // if the '$options' variable contains a recognized syntax
			$yearDigitFormat = preg_replace("/^\[([24])\]$/i", "\\1", $options); // extract the individual option
		else // use yet another fallback if the given option contains a buggy syntax
			$yearDigitFormat = 4;

		if (preg_match("/^\D*\d{4}/i", $yearString))
		{
			if ($yearDigitFormat == 2)
				$yearDetails = preg_replace("/^\D*\d{2}(\d{2}).*/i", "\\1", $yearString);
			else
				$yearDetails = preg_replace("/^\D*(\d{4}).*/i", "\\1", $yearString);
		}
		else
			$yearDetails = $yearString; // fallback

		return $yearDetails;
	}

	// --------------------------------------------------------------------

	// EXTRACT DETAILS FROM FIELD
	// this function takes the contents of the title/publication/abbrev_journal/pages/keywords/area/notes/user_keys field
	// and returns x words/items from the beginning (or end) of the string (depending on the given '$option' which must be
	// of the form "[x]" where x is a number indicating how many words/items shall be returned; positive number: return x
	// words/items from beginning of string, negative number: return x words/items from end of string)
	function extractDetailsFromField($fieldName, $sourceString, $splitDelim, $options)
	{
		global $extractDetailsTitleDefault; // these variables are defined in 'ini.inc.php'
		global $extractDetailsPublicationDefault;
		global $extractDetailsAbbrevJournalDefault;
		global $extractDetailsKeywordsDefault;
		global $extractDetailsAreaDefault;
		global $extractDetailsNotesDefault;
		global $extractDetailsUserKeysDefault;

		$returnRawSourceString = false;

		if (empty($options)) // if '$options' is empty load the default option
		{
			if ($fieldName == "title")
				$options = $extractDetailsTitleDefault;
			elseif ($fieldName == "publication")
				$options = $extractDetailsPublicationDefault;
			elseif ($fieldName == "abbrev_journal")
				$options = $extractDetailsAbbrevJournalDefault;
			elseif ($fieldName == "pages")
				$options = "[1]";
			elseif ($fieldName == "keywords")
				$options = $extractDetailsKeywordsDefault;
			elseif ($fieldName == "area")
				$options = $extractDetailsAreaDefault;
			elseif ($fieldName == "notes")
				$options = $extractDetailsNotesDefault;
			elseif ($fieldName == "user_keys")
				$options = $extractDetailsUserKeysDefault;
		}

		if (preg_match("/^\[(-?\d+(\|[^][|]*)?|-?\d*\|[^][|]*)\]$/i", $options)) // if the '$options' variable contains a recognized syntax
		{
			// extract the individual options:
			if (preg_match("/^\[-?\d+/i", $options)) // if the first option contains a number
			{
				$extractNumberOfWords = preg_replace("/^\[(-?\d+)(\|[^][|]*)?\]$/i", "\\1", $options);

				if ($extractNumberOfWords == 0) // the special number '0' indicates that all field items shall be retrieved
					$extractNumberOfWords = 999; // by assigning a very high number to '$extractNumberOfWords' we should be pretty safe to catch all words/items from the given field (extremely high values may choke the regular expression engine, though)
			}

			elseif (preg_match("/^\[\|/i", $options)) // if the first option was left empty we assume that the raw source string shall be returned without any modification
				$returnRawSourceString = true;

			if (preg_match("/^\[-?\d*\|[^][|]+\]$/i", $options)) // if the second option contains some content
				$joinDelim = preg_replace("/^\[-?\d*\|([^][|]+)\]$/i", "\\1", $options);
			else
				$joinDelim = "";
		}
		else // use yet another fallback if the given option contains a buggy syntax
		{
			$extractNumberOfWords = 1;
			$joinDelim = "";
		}

		if (!($returnRawSourceString) AND preg_match($splitDelim, $sourceString))
			$sourceStringDetails = extractPartsFromString($sourceString, $splitDelim, $joinDelim, $extractNumberOfWords);
		else
			$sourceStringDetails = $sourceString; // fallback

		return $sourceStringDetails;
	}

	// --------------------------------------------------------------------

	// GENERATE RANDOM NUMBER
	// this function generates a random number taken from the range which is defined in '$options' (format: "[min|max]", e.g. "[0|9999]"),
	// if '$options' is empty the maximum possible range will be used
	function generateRandomNumber($options)
	{
		global $extractDetailsRandomNumberDefault; // defined in 'ini.inc.php'

		if (empty($options)) // if '$options' is empty
			$options = $extractDetailsRandomNumberDefault; // load the default options

		if (preg_match("/^\[\d+\|\d+\]$/i", $options)) // if the '$options' variable contains a recognized syntax
		{
			// extract the individual options:
			$minRandomNumber = preg_replace("/\[(\d+)\|.+/i", "\\1", $options); // extract first option which defines the minimum random number
			$maxRandomNumber = preg_replace("/\[\d+\|(\d+)\]/i", "\\1", $options); // extract second option which defines the maximum random number

			// generate random number:
			$randomNumber = mt_rand($minRandomNumber, $maxRandomNumber);
		}
		else // no (or unrecognized) options
		{
			// generate random number:
			$randomNumber = mt_rand(); // if called without the optional min, max arguments 'mt_rand()' returns a pseudo-random value between 0 and RAND_MAX
		}

		return $randomNumber;
	}

	// --------------------------------------------------------------------

	// GET UPLOAD INFO
	// Given the name of a file upload field, return a four (or five) element associative
	// array containing information about the file. The element names are:

	//     name     - original name of file on client
	//     type     - MIME type of file (e.g.: 'image/gif')
	//     tmp_name - name of temporary file on server
	//     error    - holds an error number >0 if something went wrong, otherwise 0
	//                (the 'error' element was added with PHP 4.2.0. Error code explanation: <http://www.php.net/file-upload.errors>)
	//     size     - size of file in bytes

	// depending what happend on upload, they will contain the following values (PHP 4.1 and above):
	//              no file upload  upload exceeds 'upload_max_filesize'  successful upload
	//              --------------  ------------------------------------  -----------------
	//     name           ""                       [name]                      [name]
	//     type           ""                         ""                        [type]
	//     tmp_name    "" OR "none"                  ""                      [tmp_name]
	//     error          4                          1                           0
	//     size           0                          0                         [size]

	// The function prefers the $_FILES array if it is available, falling back
	// to $HTTP_POST_FILES and $HTTP_POST_VARS as necessary.

	function getUploadInfo($name)
	{
		global $HTTP_POST_FILES, $HTTP_POST_VARS;

		$uploadFileInfo = array(); // initialize array variable

		// Look for information in PHP 4.1 $_FILES array first.
		// Note: The entry in $_FILES might be present even if no file was uploaded (see above).
		//       Check the 'tmp_name' and/or the 'error' member to make sure there is a file.
		if (isset($_FILES))
			if (isset($_FILES[$name]))
				$uploadFileInfo = ($_FILES[$name]);

		// Look for information in PHP 4 $HTTP_POST_FILES array next.
		// (Again, check the 'tmp_name' and/or the 'error' member to make sure there is a file.)
		elseif (isset($HTTP_POST_FILES))
			if (isset($HTTP_POST_FILES[$name]))
				$uploadFileInfo = ($HTTP_POST_FILES[$name]);

		// Look for PHP 3 style upload variables.
		// Check the _name member, because $HTTP_POST_VARS[$name] might not
		// actually be a file field.
		elseif (isset($HTTP_POST_VARS[$name])
			&& isset($HTTP_POST_VARS[$name . "_name"]))
		{
			// Map PHP 3 elements to PHP 4-style element names
			$uploadFileInfo["name"] = $HTTP_POST_VARS[$name . "_name"];
			$uploadFileInfo["tmp_name"] = $HTTP_POST_VARS[$name];
			$uploadFileInfo["size"] = $HTTP_POST_VARS[$name . "_size"];
			$uploadFileInfo["type"] = $HTTP_POST_VARS[$name . "_type"];
		}

		if (isset($uploadFileInfo["tmp_name"]) && ($uploadFileInfo["tmp_name"] == "none")) // on some systems (PHP versions) the 'tmp_name' element might contain 'none' if there was no file being uploaded
			$uploadFileInfo["tmp_name"] = ""; // in order to standardize array output we replace 'none' with an empty string

		return $uploadFileInfo;
	}

	// --------------------------------------------------------------------

	// BUILD RELATED RECORDS LINK
	// (this function generates a proper SQL query string from the contents of the user-specific 'related' field (table 'user_data') and returns a HTML link;
	//  clicking this link will show all records that match the serials or partial queries that were specified within the 'related' field)
	function buildRelatedRecordsLink($relatedFieldString, $userID)
	{
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		// initialize some arrays:
		$serialsArray = array(); // we'll use this array to hold all record serial numbers that we encounter
		$queriesArray = array(); // this array will hold all sub-queries that were extracted from the 'related' field

		// split the source string on any semi-colon ";" (optionally surrounded by whitespace) which works as our main delimiter:
		$relatedFieldArray = split(" *; *", $relatedFieldString);

		foreach ($relatedFieldArray as $relatedFieldArrayElement)
			{
				$relatedFieldArrayElement = trim($relatedFieldArrayElement); // remove any preceding or trailing whitespace

				if (!empty($relatedFieldArrayElement))
				{
					if (is_numeric($relatedFieldArrayElement)) // if the current array element is a number, we assume its a serial number
						$serialsArray[] = $relatedFieldArrayElement; // append the current array element to the end of the serials array
					else
					{
						// replace any colon ":" (optionally surrounded by whitespace) with " RLIKE " and enclose the search value with quotes:
						// (as an example, 'author:steffens, m' will be transformed to 'author RLIKE "steffens, m"')
						if (ereg(":",$relatedFieldArrayElement))
							$relatedFieldArrayElement = preg_replace("/ *: *(.+)/"," RLIKE \"\\1\"",$relatedFieldArrayElement);
						// else we assume '$relatedFieldArrayElement' to contain a valid 'WHERE' clause!

						$queriesArray[] = $relatedFieldArrayElement; // append the current array element to the end of the queries array
					}
				}
			}

		if (!empty($serialsArray)) // if the 'related' field did contain any record serials
		{
			$serialsString = implode("|", $serialsArray);
			$serialsString = "serial RLIKE " . quote_smart("^(" . $serialsString . ")$");
			$queriesArray[] = $serialsString; // append the serial query to the end of the queries array
		}

		// re-join the queries array with an "OR" separator:
		$queriesString = implode(" OR ", $queriesArray);

		// build the full SQL query:
		// TODO: build the complete SQL query using functions 'buildFROMclause()' and 'buildORDERclause()'
		$relatedQuery = buildSELECTclause("", "", "", false, false);

		// if any of the user-specific fields are present in the contents of the 'related' field, we'll add the 'LEFT JOIN...' part to the 'FROM' clause:
		if (ereg("marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related",$queriesString))
			$relatedQuery .= " FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = $userID";
		else // we skip the 'LEFT JOIN...' part of the 'FROM' clause:
			$relatedQuery .= " FROM $tableRefs";

		$relatedQuery .= " WHERE " . $queriesString . " ORDER BY author, year DESC, publication"; // add 'WHERE' & 'ORDER BY' clause

		// build the correct query URL:
		$relatedRecordsLink = "search.php?sqlQuery=" . rawurlencode($relatedQuery) . "&amp;formType=sqlSearch&amp;showLinks=1"; // we skip unnecessary parameters ('search.php' will use it's default values for them)

		return $relatedRecordsLink;
	}

	// --------------------------------------------------------------------

	// MODIFY USER GROUPS
	// add (remove) selected records to (from) the specified user group
	// Note: this function serves two purposes (which must not be confused!):
	// 		 - if "$queryTable = user_data", it will modify the values of the 'user_groups' field of the 'user_data' table (where a user can assign one or more groups to particular *references*)
	// 		 - if "$queryTable = users", this function will modify the values of the 'user_groups' field of the 'users' table (where the admin can assign one or more groups to particular *users*)
	function modifyUserGroups($queryTable, $displayType, $recordSerialsArray, $userID, $userGroup)
	{
		global $tableUserData, $tableUsers; // defined in 'db.inc.php'

		connectToMySQLDatabase();

		$userGroupQuoted = preg_quote($userGroup, "/"); // escape meta characters (including '/' that is used as delimiter for the PCRE match & replace functions below and which gets passed as second argument)

		if ($queryTable == $tableUserData) // for the current user, get all entries within the 'user_data' table that refer to the selected records (listed in '$recordSerialsArray'):
			$query = "SELECT record_id, user_groups FROM $tableUserData WHERE record_id RLIKE " . quote_smart("^(" . implode("|", $recordSerialsArray) . ")$") . " AND user_id = " . quote_smart($userID);
		elseif ($queryTable == $tableUsers) // for the admin, get all entries within the 'users' table that refer to the selected records (listed in '$recordSerialsArray'):
			$query = "SELECT user_id as record_id, user_groups FROM $tableUsers WHERE user_id RLIKE " . quote_smart("^(" . implode("|", $recordSerialsArray) . ")$");
			// (note that by using 'user_id as record_id' we can use the term 'record_id' as identifier of the primary key for both tables)

		$result = queryMySQLDatabase($query); // RUN the query on the database through the connection

		$foundSerialsArray = array(); // initialize array variable (which will hold the serial numbers of all found records)

		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows found ...
		{
			while ($row = @ mysql_fetch_array($result)) // for all rows found
			{
				$recordID = $row["record_id"]; // get the serial number of the current record
				$foundSerialsArray[] = $recordID; // add this record's serial to the array of found serial numbers

				$recordUserGroups = $row["user_groups"]; // extract the user groups that the current record belongs to

				// ADD the specified user group to the 'user_groups' field:
				if ($displayType == "Add" AND !preg_match("/(^|.*;) *$userGroupQuoted *(;.*|$)/", $recordUserGroups)) // if the specified group isn't listed already within the 'user_groups' field:
				{
					if (empty($recordUserGroups)) // and if the 'user_groups' field is completely empty
						$recordUserGroups = $userGroup; // add the specified user group to the 'user_groups' field
					else // if the 'user_groups' field does already contain some user content:
						$recordUserGroups .= "; " . $userGroup; // append the specified user group to the 'user_groups' field
				}

				// REMOVE the specified user group from the 'user_groups' field:
				elseif ($displayType == "Remove") // remove the specified group from the 'user_groups' field:
				{
					$recordUserGroups = preg_replace("/^ *$userGroupQuoted *(?=;|$)/", "", $recordUserGroups); // the specified group is listed at the very beginning of the 'user_groups' field
					$recordUserGroups = preg_replace("/ *; *$userGroupQuoted *(?=;|$)/", "", $recordUserGroups); // the specified group occurs after some other group name within the 'user_groups' field
					$recordUserGroups = ereg_replace("^ *; *", "", $recordUserGroups); // remove any remaining group delimiters at the beginning of the 'user_groups' field
				}

				if ($queryTable == $tableUserData) // for the current record & user ID, update the matching entry within the 'user_data' table:
					$queryUserData = "UPDATE $tableUserData SET user_groups = " . quote_smart($recordUserGroups) . " WHERE record_id = " . quote_smart($recordID) . " AND user_id = " . quote_smart($userID);
				elseif ($queryTable == $tableUsers) // for the current user ID, update the matching entry within the 'users' table:
					$queryUserData = "UPDATE $tableUsers SET user_groups = " . quote_smart($recordUserGroups) . " WHERE user_id = " . quote_smart($recordID);


				$resultUserData = queryMySQLDatabase($queryUserData); // RUN the query on the database through the connection
			}
		}

		if (($queryTable == $tableUserData) AND ($displayType == "Add"))
		{
			// for all selected records that have no entries in the 'user_data' table (for this user), we'll need to add a new entry containing the specified group:
			$leftoverSerialsArray = array_diff($recordSerialsArray, $foundSerialsArray); // get all unique array elements of '$recordSerialsArray' which are not in '$foundSerialsArray'

			foreach ($leftoverSerialsArray as $leftoverRecordID) // for each record that we haven't processed yet (since it doesn't have an entry in the 'user_data' table for this user)
			{
				if ($leftoverRecordID > 0) // function 'extractFormElementsQueryResults()' in 'search.php' assigns '$recordSerialsArray[]="0"' if '$recordSerialsArray' is empty
				{
					$foundSerialsArray[] = $leftoverRecordID; // add this record's serial to the array of found serial numbers

					// for the current record & user ID, add a new entry (containing the specified group) to the 'user_data' table:
					$queryUserData = "INSERT INTO $tableUserData SET "
					               . "user_groups = " . quote_smart($userGroup) . ", "
					               . "record_id = " . quote_smart($leftoverRecordID) . ", "
					               . "user_id = " . quote_smart($userID) . ", "
					               . "data_id = NULL"; // inserting 'NULL' into an auto_increment PRIMARY KEY attribute allocates the next available key value

					$resultUserData = queryMySQLDatabase($queryUserData); // RUN the query on the database through the connection
				}
			}
		}

// TODO!
		// save an informative message:
//		if (count($foundSerialsArray) == "1")
//			$recordHeader = $loc["record"]; // use singular form if only one record was updated
//		else
//			$recordHeader = $loc["records"]; // use plural form if multiple records were updated

//		$HeaderString = returnMsg("The groups of " .  . " records were updated successfully!", "", "", "HeaderString");

		getUserGroups($queryTable, $userID); // update the appropriate session variable
	}

	// --------------------------------------------------------------------

	// Get all user groups specified by the current user (or admin)
	// and (if some groups were found) save them as semicolon-delimited string to a session variable:
	// Note: this function serves two purposes (which must not be confused!):
	// 		 - if "$queryTable = user_data", it will fetch unique values from the 'user_groups' field of the 'user_data' table (where a user can assign one or more groups to particular *references*)
	//       - if "$queryTable = users", this function will fetch unique values from the 'user_groups' field of the 'users' table (where the admin can assign one or more groups to particular *users*)
	function getUserGroups($queryTable, $userID)
	{
		global $tableUserData, $tableUsers; // defined in 'db.inc.php'

		connectToMySQLDatabase();

		// CONSTRUCT SQL QUERY:
		// Note: 'user_groups RLIKE ".+"' will cause the database to only return user data entries where the 'user_groups' field
		//       is neither NULL (=> 'user_groups IS NOT NULL') nor the empty string (=> 'user_groups NOT RLIKE "^$"')
		if ($queryTable == $tableUserData)
			// Find all unique 'user_groups' entries in the 'user_data' table belonging to the current user:
			$query = "SELECT DISTINCT user_groups FROM $tableUserData WHERE user_id = " . quote_smart($userID) . " AND user_groups RLIKE \".+\"";
		elseif ($queryTable == $tableUsers)
			// Find all unique 'user_groups' entries in the 'users' table:
			$query = "SELECT DISTINCT user_groups FROM $tableUsers WHERE user_groups RLIKE \".+\"";

		$result = queryMySQLDatabase($query); // RUN the query on the database through the connection

		$userGroupsArray = array(); // initialize array variable

		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows found ...
		{
			while ($row = @ mysql_fetch_array($result)) // for all rows found
			{
				// remove any meaningless delimiter(s) from the beginning or end of a field string:
				$rowUserGroupsString = trimTextPattern($row["user_groups"], "( *; *)+", true, true);

				// split the contents of the 'user_groups' field on the specified delimiter (which is interpreted as regular expression!):
				$rowUserGroupsArray = split(" *; *", $rowUserGroupsString);

				$userGroupsArray = array_merge($userGroupsArray, $rowUserGroupsArray); // append this row's group names to the array of found user groups
			}

			// remove duplicate group names from array:
			$userGroupsArray = array_unique($userGroupsArray);
			// sort in ascending order:
			sort($userGroupsArray);

			// join array of unique user groups with '; ' as separator:
			$userGroupsString = implode('; ', $userGroupsArray);

			// Write the resulting string of user groups into a session variable:
			if ($queryTable == $tableUserData)
				saveSessionVariable("userGroups", $userGroupsString);
			elseif ($queryTable == $tableUsers)
				saveSessionVariable("adminUserGroups", $userGroupsString);
		}
		else // no user groups found
		{ // delete any session variable (which is now outdated):
			if ($queryTable == $tableUserData)
				deleteSessionVariable("userGroups");
			elseif ($queryTable == $tableUsers)
				deleteSessionVariable("adminUserGroups");
		}
	}

	// --------------------------------------------------------------------

	// Get all user queries specified by the current user
	// and (if some queries were found) save them as semicolon-delimited string to the session variable 'userQueries':
	function getUserQueries($userID)
	{
		global $tableQueries; // defined in 'db.inc.php'

		connectToMySQLDatabase();

		// CONSTRUCT SQL QUERY:
		// Find all unique query entries in the 'queries' table belonging to the current user:
		// (query names should be unique anyhow, so the DISTINCT parameter wouldn't be really necessary)
		$query = "SELECT DISTINCT query_name FROM $tableQueries WHERE user_id = " . quote_smart($userID) . " ORDER BY last_execution DESC";
		// Note: we sort (in descending order) by the 'last_execution' field to get the last used query entries first;
		//       by that, the last used query will be always at the top of the popup menu within the 'Recall My Query' form

		$result = queryMySQLDatabase($query); // RUN the query on the database through the connection

		$userQueriesArray = array(); // initialize array variable

		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows found ...
		{
			while ($row = @ mysql_fetch_array($result)) // for all rows found
				$userQueriesArray[] = $row["query_name"]; // append this row's query name to the array of found user queries

			// join array of unique user queries with '; ' as separator:
			$userQueriesString = implode('; ', $userQueriesArray);

			// Write the resulting string of user queries into a session variable:
			saveSessionVariable("userQueries", $userQueriesString);
		}
		else // no user queries found
			deleteSessionVariable("userQueries"); // delete any 'userQueries' session variable (which is now outdated)
	}

	// --------------------------------------------------------------------

	// Get all cite keys specified by the current user:
	function getUserCiteKeys($userID)
	{
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		connectToMySQLDatabase();

		// CONSTRUCT SQL QUERY:
		// Find all cite keys in table 'user_data' belonging to the current user:
		// (note that the SQL query is formulated such that only those records from table 'user_data' are returned
		//  which have a matching entry in table 'refs'; i.e. stray items from table 'user_data' are omitted)
		$query = "SELECT cite_key FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = " . quote_smart($userID) . " WHERE cite_key RLIKE \".+\" ORDER BY cite_key";

		$result = queryMySQLDatabase($query); // RUN the query on the database through the connection

		$userCiteKeysArray = array(); // initialize array variable

		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows found ...
		{
			while ($row = @ mysql_fetch_array($result)) // for all rows found
			{
				// If this row's cite key already exists in the global array of found cite keys ('$citeKeysArray'),
				// we'll uniquify it, otherwise we'll take it as is
				$citeKey = ensureUniqueCiteKey($row["cite_key"]);

				// We also append the original cite key to '$userCiteKeysArray' which holds all uniquified cite keys
				// as array keys and the corresponding original cite key names (including duplicate items!) as array
				// values
				$userCiteKeysArray[$citeKey] = $row["cite_key"];
			}
		}

		return $userCiteKeysArray;
	}

	// --------------------------------------------------------------------

	// This function checks if the given cite key already exists in the global array of found cite keys ('$citeKeysArray').
	// If the given cite key already exists, an incrementing number will be added to uniquify it (the number will be increased
	// until the cite key is truly unique); after ensuring that a given cite key is unique, it's added to '$citeKeysArray' and
	// returned:
	// Note: the global '$citeKeysArray' does NOT contain all cite keys defined in the entire refbase database; instead it holds:
	//       - on import: for records that just have been imported, the list of cite keys for all imported records (generated
	//                    according to the current user's prefs) -PLUS- the list of all of the user's existing cite keys
	//       - on export: for records that just have been exported, the list of cite keys (generated according to the current
	//                    user's prefs) for all exported records
	function ensureUniqueCiteKey($citeKey)
	{
		global $citeKeysArray; // '$citeKeysArray' is made globally available from within this function

		if (!isset($citeKeysArray))
			$citeKeysArray = array(); // initialize array variable

		if (isset($citeKeysArray[$citeKey])) // if this cite key already exists
		{
			if (preg_match("/(?<=_)\d+$/", $citeKey)) // if this cite key already contains a suffix such as "_2" we assume it to be the old number of occurrence
				$citeKey = preg_replace("/(?<=_)(\d+)$/e", "'\\1' + 1", $citeKey); // increment the old number of occurrence (that already exists in this cite key) by 1
			else
				$citeKey = $citeKey . "_2"; // append a number of occurrence to this cite key

			$citeKey = ensureUniqueCiteKey($citeKey); // recurse, to check again whether the generated cite key already exists
		}
		else
		{
			$citeKeysArray[$citeKey] = $citeKey; // append the cite key to the array of known cite keys
		}

		return $citeKey;
	}

	// --------------------------------------------------------------------

	// Get all available formats/styles/types:
	function getAvailableFormatsStylesTypes($dataType, $formatType) // '$dataType' must be one of the following: 'format', 'style', 'type'; '$formatType' must be either '', 'export', 'import' or 'cite'
	{
		global $tableDepends, $tableFormats, $tableStyles, $tableTypes; // defined in 'db.inc.php'

		connectToMySQLDatabase();

		// CONSTRUCT SQL QUERY:
		if ($dataType == "format")
			$query = "SELECT format_name, format_id FROM $tableFormats LEFT JOIN $tableDepends ON $tableFormats.depends_id = $tableDepends.depends_id WHERE format_type = " . quote_smart($formatType) . " AND format_enabled = 'true' AND depends_enabled = 'true' ORDER BY order_by, format_name";

		elseif ($dataType == "style")
			$query = "SELECT style_name, style_id FROM $tableStyles LEFT JOIN $tableDepends ON $tableStyles.depends_id = $tableDepends.depends_id WHERE style_enabled = 'true' AND depends_enabled = 'true' ORDER BY order_by, style_name";

		elseif ($dataType == "type")
			$query = "SELECT type_name, type_id FROM $tableTypes WHERE type_enabled = 'true' ORDER BY order_by, type_name";

		$result = queryMySQLDatabase($query); // RUN the query on the database through the connection

		$availableFormatsStylesTypesArray = array(); // initialize array variable

		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows found ...
			while ($row = @ mysql_fetch_array($result)) // for all rows found
				$availableFormatsStylesTypesArray[$row[$dataType . "_id"]] = $row[$dataType . "_name"]; // append this row's format/style/type name to the array of found user formats/styles/types

		return $availableFormatsStylesTypesArray;
	}

	// --------------------------------------------------------------------

	// Get all formats/styles/types that are available and were enabled by the admin for the current user:
	function getEnabledUserFormatsStylesTypes($userID, $dataType, $formatType, $returnIDsAsValues) // '$dataType' must be one of the following: 'format', 'style', 'type'; '$formatType' must be either '', 'export', 'import' or 'cite'
	{
		global $tableDepends, $tableFormats, $tableStyles, $tableTypes, $tableUserFormats, $tableUserStyles, $tableUserTypes; // defined in 'db.inc.php'

		connectToMySQLDatabase();

		// CONSTRUCT SQL QUERY:
		if ($dataType == "format")
			$query = "SELECT $tableFormats.format_name, $tableFormats.format_id FROM $tableFormats LEFT JOIN $tableUserFormats on $tableFormats.format_id = $tableUserFormats.format_id LEFT JOIN $tableDepends ON $tableFormats.depends_id = $tableDepends.depends_id WHERE format_type = " . quote_smart($formatType) . " AND format_enabled = 'true' AND depends_enabled = 'true' AND user_id = " . quote_smart($userID) . " ORDER BY $tableFormats.order_by, $tableFormats.format_name";

		elseif ($dataType == "style")
			$query = "SELECT $tableStyles.style_name, $tableStyles.style_id FROM $tableStyles LEFT JOIN $tableUserStyles on $tableStyles.style_id = $tableUserStyles.style_id LEFT JOIN $tableDepends ON $tableStyles.depends_id = $tableDepends.depends_id WHERE style_enabled = 'true' AND depends_enabled = 'true' AND user_id = " . quote_smart($userID) . " ORDER BY $tableStyles.order_by, $tableStyles.style_name";

		elseif ($dataType == "type")
			$query = "SELECT $tableTypes.type_name, $tableTypes.type_id FROM $tableTypes LEFT JOIN $tableUserTypes USING (type_id) WHERE type_enabled = 'true' AND user_id = " . quote_smart($userID) . " ORDER BY $tableTypes.order_by, $tableTypes.type_name";

		$result = queryMySQLDatabase($query); // RUN the query on the database through the connection

		$enabledFormatsStylesTypesArray = array(); // initialize array variable

		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows found ...
			while ($row = @ mysql_fetch_array($result)) // for all rows found
			{
				if ($returnIDsAsValues) // return format/style/type IDs as element values:
					$enabledFormatsStylesTypesArray[] = $row[$dataType . "_id"]; // append this row's format/style/type ID to the array of found user formats/styles/types
				else // return format/style/type names as element values and use the corresponding IDs as element keys:
					$enabledFormatsStylesTypesArray[$row[$dataType . "_id"]] = $row[$dataType . "_name"]; // append this row's format/style/type name to the array of found user formats/styles/types
			}

		return $enabledFormatsStylesTypesArray;
	}

	// --------------------------------------------------------------------

	// Get all user formats/styles/types that are available and enabled for the current user (by admins choice) AND which this user has chosen to be visible:
	// and (if some formats/styles/types were found) save them each as semicolon-delimited string to the session variables 'user_export_formats', 'user_cite_formats', 'user_styles' or 'user_types', respectively:
	function getVisibleUserFormatsStylesTypes($userID, $dataType, $formatType) // '$dataType' must be one of the following: 'format', 'style', 'type'; '$formatType' must be either '', 'export', 'import' or 'cite'
	{
		global $loginEmail;
		global $adminLoginEmail; // ('$adminLoginEmail' is specified in 'ini.inc.php')
		global $tableDepends, $tableFormats, $tableStyles, $tableTypes, $tableUserFormats, $tableUserStyles, $tableUserTypes; // defined in 'db.inc.php'

		connectToMySQLDatabase();

		// CONSTRUCT SQL QUERY:
		if ($dataType == "format")
		{
			// Find all enabled+visible formats in table 'user_formats' belonging to the current user:
			// Note: following conditions must be matched to have a format "enabled+visible" for a particular user:

			//       - 'formats' table: the 'format_enabled' field must contain 'true' for the given format
			//                          (the 'formats' table gives the admin control over which formats are available to the database users)

			//       - 'depends' table: the 'depends_enabled' field must contain 'true' for the 'depends_id' that matches the 'depends_id' of the given format in table 'formats'
			//                          (the 'depends' table specifies whether there are any external tools required for a particular format and if these tools are available)

			//       - 'user_formats' table: there must be an entry for the given user where the 'format_id' matches the 'format_id' of the given format in table 'formats' -AND-
			//                               the 'show_format' field must contain 'true' for the 'format_id' that matches the 'format_id' of the given format in table 'formats'
			//                               (the 'user_formats' table specifies all of the available formats for a particular user that have been selected by this user to be included in the format popups)
			$query = "SELECT format_name FROM $tableFormats LEFT JOIN $tableUserFormats on $tableFormats.format_id = $tableUserFormats.format_id LEFT JOIN $tableDepends ON $tableFormats.depends_id = $tableDepends.depends_id WHERE format_type = " . quote_smart($formatType) . " AND format_enabled = 'true' AND depends_enabled = 'true' AND user_id = " . quote_smart($userID) . " AND show_format = 'true' ORDER BY $tableFormats.order_by, $tableFormats.format_name";
		}
		elseif ($dataType == "style")
		{
			// Find all enabled+visible styles in table 'user_styles' belonging to the current user:
			// (same conditions apply as for formats)
			$query = "SELECT style_name FROM $tableStyles LEFT JOIN $tableUserStyles on $tableStyles.style_id = $tableUserStyles.style_id LEFT JOIN $tableDepends ON $tableStyles.depends_id = $tableDepends.depends_id WHERE style_enabled = 'true' AND depends_enabled = 'true' AND user_id = " . quote_smart($userID) . " AND show_style = 'true' ORDER BY $tableStyles.order_by, $tableStyles.style_name";
		}
		elseif ($dataType == "type")
		{
			// Find all enabled+visible types in table 'user_types' belonging to the current user:
			// (opposed to formats & styles, we're not checking for any dependencies here)
			$query = "SELECT type_name FROM $tableTypes LEFT JOIN $tableUserTypes USING (type_id) WHERE user_id = " . quote_smart($userID) . " AND show_type = 'true' ORDER BY $tableTypes.order_by, $tableTypes.type_name";
		}

		$result = queryMySQLDatabase($query); // RUN the query on the database through the connection

		$userFormatsStylesTypesArray = array(); // initialize array variable

		// generate the name of the session variable:
		if (!empty($formatType))
			$sessionVariableName = "user_" . $formatType . "_" . $dataType . "s"; // yields 'user_export_formats' or 'user_cite_formats'
		else
			$sessionVariableName = "user_" . $dataType . "s"; // yields 'user_styles' or 'user_types'

		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows found ...
		{
			while ($row = @ mysql_fetch_array($result)) // for all rows found
				$userFormatsStylesTypesArray[] = $row[$dataType . "_name"]; // append this row's format/style/type name to the array of found user formats/styles/types

			// we'll only update the appropriate session variable if either a normal user is logged in -OR- the admin is logged in and views his own user options page
			if (($loginEmail != $adminLoginEmail) OR (($loginEmail == $adminLoginEmail) && ($userID == getUserID($loginEmail))))
			{
				// join array of unique user formats/styles/types with '; ' as separator:
				$userFormatsStylesTypesString = implode('; ', $userFormatsStylesTypesArray);

				// Write the resulting string of user formats/styles/types into a session variable:
				saveSessionVariable($sessionVariableName, $userFormatsStylesTypesString);
			}
		}
		else // no user formats/styles/types found
			// we'll only delete the appropriate session variable if either a normal user is logged in -OR- the admin is logged in and views his own user options page
			if (($loginEmail != $adminLoginEmail) OR (($loginEmail == $adminLoginEmail) && ($userID == getUserID($loginEmail))))
				deleteSessionVariable($sessionVariableName); // delete any 'user_export_formats'/'user_cite_formats'/'user_styles'/'user_types' session variable (which is now outdated)

		return $userFormatsStylesTypesArray;
	}

	// --------------------------------------------------------------------

	// Get all formats/styles/types that are available (or enabled for the current user) and return them as properly formatted <option> tag elements.
	// Note that this function will return two pretty different things, depending on who's logged in:
	//   - if the admin is logged in, it will return all *available* formats/styles/types as <option> tags
	//     (with those items being selected which were _enabled_ by the admin for the current user)
	//   - if a normal user is logged in, this function will return all formats/styles/types as <option> tags which were *enabled* by the admin for the current user
	//     (with those items being selected which were chosen to be _visible_ by the current user)
	function returnFormatsStylesTypesAsOptionTags($userID, $dataType, $formatType) // '$dataType' must be one of the following: 'format', 'style', 'type'; '$formatType' must be either '', 'export', 'import' or 'cite'
	{
		global $loginEmail;
		global $adminLoginEmail; // ('$adminLoginEmail' is specified in 'ini.inc.php')

		if ($loginEmail == $adminLoginEmail) // if the admin is logged in
			$availableFormatsStylesTypesArray = getAvailableFormatsStylesTypes($dataType, $formatType); // get all available formats/styles/types

		$enabledFormatsStylesTypesArray = getEnabledUserFormatsStylesTypes($userID, $dataType, $formatType, false); // get all formats/styles/types that were enabled by the admin for the current user

		if ($loginEmail == $adminLoginEmail) // if the admin is logged in
		{
			$optionTags = buildSelectMenuOptions($availableFormatsStylesTypesArray, " *; *", "\t\t\t", true); // build properly formatted <option> tag elements from the items listed in '$availableFormatsStylesTypesArray'

			$selectedFormatsStylesTypesArray = $enabledFormatsStylesTypesArray; // get all formats/styles/types that were enabled by the admin for the current user
		}
		else // if ($loginEmail != $adminLoginEmail) // if a normal user is logged in
		{
			$optionTags = buildSelectMenuOptions($enabledFormatsStylesTypesArray, " *; *", "\t\t\t", true); // build properly formatted <option> tag elements from the items listed in '$enabledFormatsStylesTypesArray'

			$selectedFormatsStylesTypesArray = getVisibleUserFormatsStylesTypes($userID, $dataType, $formatType); // get all formats/styles/types that were chosen to be visible for the current user
		}

		foreach($selectedFormatsStylesTypesArray as $itemKey => $itemValue) // escape possible meta characters within names of formats/styles/types that shall be selected (otherwise the grep pattern below would fail)
			$selectedFormatsStylesTypesArray[$itemKey] = preg_quote($itemValue);

		$selectedFormatsStylesTypes = implode("|", $selectedFormatsStylesTypesArray); // merge array of formats/styles/types that shall be selected

		$optionTags = ereg_replace("<option([^>]*)>($selectedFormatsStylesTypes)</option>", "<option\\1 selected>\\2</option>", $optionTags); // select all formats/styles/types that are listed within '$selectedFormatsStylesTypesArray'

		return $optionTags;
	}

	// --------------------------------------------------------------------

	// Fetch the name of the citation style file that's associated with the style given in '$citeStyle'
	// Note: Refbase identifies popup items by their name (and not by ID numbers) which means that the style names within the 'styles' table must be unique!
	// That said, this function assumes unique style names, i.e., there's no error checking for duplicates!
	function getStyleFile($citeStyle)
	{
		global $tableStyles; // defined in 'db.inc.php'

		connectToMySQLDatabase();

		// CONSTRUCT SQL QUERY:
		// get the 'style_spec' for the record entry in table 'styles' whose 'style_name' matches that in '$citeStyle':
		$query = "SELECT style_spec FROM $tableStyles WHERE style_name = " . quote_smart($citeStyle);

		$result = queryMySQLDatabase($query); // RUN the query on the database through the connection
		$row = mysql_fetch_array($result);

		return($row["style_spec"]);
	}

	// --------------------------------------------------------------------

	// Fetch the path/name of the format file that's associated with the format given in '$formatName'
	function getFormatFile($formatName, $formatType) // '$formatType' must be either 'export', 'import' or 'cite'
	{
		global $tableFormats; // defined in 'db.inc.php'

		connectToMySQLDatabase();

		// CONSTRUCT SQL QUERY:
		// get the 'format_spec' for the record entry in table 'formats' whose 'format_name' matches that in '$formatName':
		$query = "SELECT format_spec FROM $tableFormats WHERE format_name = " . quote_smart($formatName) . " AND format_type = " . quote_smart($formatType);

		$result = queryMySQLDatabase($query); // RUN the query on the database through the connection
		$row = mysql_fetch_array($result);

		return($row["format_spec"]);
	}

	// --------------------------------------------------------------------

	// Fetch the path of the external utility that's required for a particular import/export format
	function getExternalUtilityPath($externalUtilityName)
	{
		global $tableDepends; // defined in 'db.inc.php'

		connectToMySQLDatabase();

		// CONSTRUCT SQL QUERY:
		// get the path for the record entry in table 'depends' whose field 'depends_external' matches that in '$externalUtilityName':
		$query = "SELECT depends_path FROM $tableDepends WHERE depends_external = " . quote_smart($externalUtilityName);

		$result = queryMySQLDatabase($query); // RUN the query on the database through the connection
		$row = mysql_fetch_array($result);

		return($row["depends_path"]);
	}

	// --------------------------------------------------------------------

	// Get the user (or group) permissions for the current user
	// and (optionally) save all allowed user actions as semicolon-delimited string to the session variable 'user_permissions':
	function getPermissions($user_OR_groupID, $permissionType, $savePermissionsToSessionVariable) // '$permissionType' must be either 'user' or 'group'; '$savePermissionsToSessionVariable' must be either 'true' or 'false'
	{
		global $tableUserPermissions; // defined in 'db.inc.php'

		// NOTE: the group permissions feature (table 'group_permissions') has not been implemented yet, i.e., currently, only '$permissionType=user' is recognized!
//		global $tableGroupPermissions;

//		if ($permissionType == "group")
//			$tablePermissions = $tableGroupPermissions;
//		else
			$tablePermissions = $tableUserPermissions;

		connectToMySQLDatabase();

		// CONSTRUCT SQL QUERY:
		// Fetch all permission settings from the 'user_permissions' (or 'group_permissions') table for the current user:
		$query = "SELECT allow_add, allow_edit, allow_delete, allow_download, allow_upload, allow_list_view, allow_details_view, allow_print_view, allow_browse_view, allow_sql_search, allow_user_groups, allow_user_queries, allow_rss_feeds, allow_import, allow_export, allow_cite, allow_batch_import, allow_batch_export, allow_modify_options FROM " . $tablePermissions . " WHERE " . $permissionType . "_id = " . quote_smart($user_OR_groupID);

		$result = queryMySQLDatabase($query); // RUN the query on the database through the connection

		if (mysql_num_rows($result) == 1) // interpret query result: Do we have exactly one row?
		{
			$userPermissionsArray = array(); // initialize array variables
			$userPermissionsFieldNameArray = array();

			$row = mysql_fetch_array($result); // fetch the one row into the array '$row'

			$fieldsFound = mysql_num_fields($result); // count the number of fields

			for ($i=0; $i<$fieldsFound; $i++)
			{
				// Fetch the current attribute name:
				$fieldName = getMySQLFieldInfo($result, $i, "name");

				$userPermissionsArray[$fieldName] = $row[$i]; // ... append this field's permission value using the field's permission name as key

				if ($row[$i] == "yes") // if the current permission is set to 'yes'...
					$userPermissionsFieldNameArray[] = $fieldName; // ... append this field's permission name (as value) to the array of allowed user actions
			}

			// join array of allowed user actions with '; ' as separator:
			$allowedUserActionsString = implode('; ', $userPermissionsFieldNameArray);

			if ($savePermissionsToSessionVariable)
				// Write the resulting string of allowed user actions into a session variable:
				saveSessionVariable("user_permissions", $allowedUserActionsString);

			return $userPermissionsArray;
		}
		else
		{
			if ($savePermissionsToSessionVariable)
				// since no (or more than one) user/group was found with the given ID, we fall back to the default permissions which apply when no user is logged in, i.e.,
				// we assume 'user_id' or 'group_id' is zero! (the 'start_session()' function will take care of setting up permissions when no user is logged in)
				deleteSessionVariable("user_permissions"); // therefore, we delete any existing 'user_permissions' session variable (which is now outdated)

			return array();
		}
	}

	// --------------------------------------------------------------------

	// Returns language information:
	// if empty($userID): get all languages that were setup and enabled by the admin
	// if !empty($userID): get the preferred language for the user with the specified userID
	function getLanguages($userID)
	{
		global $tableLanguages, $tableUsers; // defined in 'db.inc.php'

		connectToMySQLDatabase();

		// CONSTRUCT SQL QUERY:
		if (empty($userID))
			// Find all unique language entries in the 'languages' table that are enabled:
			// (language names should be unique anyhow, so the DISTINCT parameter wouldn't be really necessary)
			$query = "SELECT DISTINCT language_name FROM $tableLanguages WHERE language_enabled = 'true' ORDER BY order_by";
		else
			// Get the preferred language for the user with the user ID given in '$userID':
			$query = "SELECT language AS language_name FROM $tableUsers WHERE user_id = " . quote_smart($userID);


		$result = queryMySQLDatabase($query); // RUN the query on the database through the connection

		$languagesArray = array(); // initialize array variable

		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows found ...
		{
			while ($row = @ mysql_fetch_array($result)) // for all rows found
				$languagesArray[] = $row["language_name"]; // append this row's language name to the array of found languages
		}

		return $languagesArray;
	}

	// --------------------------------------------------------------------

	// Return the current user's preferred interface language:
	function getUserLanguage()
	{
		global $loginUserID; // saved as session variable on login

		global $defaultLanguage; // defined in 'ini.inc.php'

		if (isset($_SESSION['loginEmail'])) // if a user is logged in
		{
			// get the preferred language for the current user:
			$userLanguagesArray = getLanguages($loginUserID);
			$userLanguage = $userLanguagesArray[0];
		}
		else // NO user logged in
			$userLanguage = $defaultLanguage; // use the default language

		return $userLanguage;
	}

	// --------------------------------------------------------------------

	// Get all user options for the current user:
	function getUserOptions($userID)
	{
		global $tableUserOptions; // defined in 'db.inc.php'

		connectToMySQLDatabase();

		if (empty($userID))
			$userID = 0;

		// CONSTRUCT SQL QUERY:
		// Fetch all options from table 'user_options' for the user with the user ID given in '$userID':
		$query = "SELECT * FROM $tableUserOptions WHERE user_id = " . quote_smart($userID);


		$result = queryMySQLDatabase($query); // RUN the query on the database through the connection

		$userOptionsArray = array(); // initialize array variable

		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound == 1) // Interpret query result: Do we have exactly one row?
			$userOptionsArray = @ mysql_fetch_array($result); // fetch the one row into the array '$userOptionsArray'

		return $userOptionsArray;
	}

	// --------------------------------------------------------------------

	// Get the list of "main fields" preferred by the current user:
	// and save the list of fields as comma-delimited string to the session variable 'userMainFields'
	function getMainFields($userID)
	{
		global $loginEmail;

		global $adminLoginEmail; // these variables are defined in 'ini.inc.php'
		global $defaultMainFields;

		$userOptionsArray = array(); // initialize array variable

		// Get all user options for the current user:
		// note that if the user isn't logged in (userID=0), the list of "main fields" is taken from variable
		// '$defaultMainFields' in 'ini.inc.php' and not from option 'main_fields' in table 'user_options
		if ($userID != 0)
			$userOptionsArray = getUserOptions($userID);

		// Extract the list of "main fields":
		if (!empty($userOptionsArray) AND !empty($userOptionsArray['main_fields']))
			$userMainFieldsString = $userOptionsArray['main_fields']; // honour the logged in user's preferred list of "main fields" (if not empty or NULL)
		else
			$userMainFieldsString = $defaultMainFields; // by default, we take the list of "main fields" from the global variable '$defaultMainFields'

		// We'll only update the appropriate session variable if either a normal user is logged in -OR- the admin is logged in and views his own user options page
		if (($loginEmail != $adminLoginEmail) OR (($loginEmail == $adminLoginEmail) && ($userID == getUserID($loginEmail))))
			// Write the list of fields into a session variable:
			saveSessionVariable("userMainFields", $userMainFieldsString);

		$userMainFieldsArray = split(" *, *", $userMainFieldsString); // split the string of fields into its individual fields

		return $userMainFieldsArray;
	}

	// --------------------------------------------------------------------

	// Returns the current date (e.g. '2003-12-31'), time (e.g. '23:59:49') and user name & email address (e.g. 'Matthias Steffens (refbase@extracts.de)'):
	// this information is used when adding/updating/deleting records in the database
	function getCurrentDateTimeUser()
	{
		global $loginEmail;
		global $loginFirstName;
		global $loginLastName;

		$currentDate = date('Y-m-d'); // get the current date in a format recognized by MySQL (which is 'YYYY-MM-DD', e.g. '2003-12-31')
		$currentTime = date('H:i:s'); // get the current time in a format recognized by MySQL (which is 'HH:MM:SS', e.g. '23:59:49')
		$currentUser = $loginFirstName . " " . $loginLastName . " (" . $loginEmail . ")"; // we use session variables to construct the user name, e.g. 'Matthias Steffens (refbase@extracts.de)'

		return array($currentDate, $currentTime, $currentUser);
	}

	// --------------------------------------------------------------------

	// Build a correct call number prefix for the currently logged-in user (e.g. 'IPÖ @ msteffens'):
	function getCallNumberPrefix()
	{
		global $loginEmail;
		global $abbrevInstitution;

		// we use session variables to construct a correct call number prefix:
		$loginEmailArray = split("@", $loginEmail); // split the login email address at '@'
		$loginEmailUserName = $loginEmailArray[0]; // extract the user name (which is the first element of the array '$loginEmailArray')
		$callNumberPrefix = $abbrevInstitution . " @ " . $loginEmailUserName;

		return $callNumberPrefix;
	}

	// --------------------------------------------------------------------

	// Get the default view for the current user:
	function getDefaultView($userID)
	{
		global $defaultView; // defined in 'ini.inc.php'

		$userOptionsArray = array(); // initialize array variables
		$viewsArray = array("List"    => "allow_list_view",
		                    "Cite"    => "allow_cite",
		                    "Display" => "allow_details_view",
		                    "Browse"  => "allow_browse_view");

		$userDefaultView = $defaultView; // by default, we take the default view from the global variable '$defaultView'

		// Note that if the user isn't logged in (userID=0), the default view is taken from variable '$defaultView'
		// in 'ini.inc.php' and is not overridden by any of the '*_view' permissions ('allow_list_view', 'allow_details_view',
		// 'allow_browse_view', 'allow_cite') in table 'user_permissions'

		// Adopt the user's default view if he/she is NOT allowed to use the global default (given in '$defaultView'):
		if (isset($viewsArray[$defaultView]) AND isset($_SESSION['user_permissions']) AND !ereg($viewsArray[$defaultView], $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does NOT contain the '*_view' permission that corresponds to '$defaultView'
		{
			foreach ($viewsArray as $viewType => $viewPermission) // use the next allowed view as default view
			{
				if (ereg($viewPermission, $_SESSION['user_permissions']))
				{
					$userDefaultView = $viewType;
					break;
				}
			}
		}

		// Write the name of the default view into a session variable:
		saveSessionVariable("userDefaultView", $userDefaultView);

		return $userDefaultView;
	}

	// --------------------------------------------------------------------

	// Returns the total number of records in the database:
	function getTotalNumberOfRecords()
	{
		global $tableRefs; // defined in 'db.inc.php'

		connectToMySQLDatabase();

		// CONSTRUCT SQL QUERY:
		$query = "SELECT COUNT(serial) FROM $tableRefs"; // query the total number of records

		$result = queryMySQLDatabase($query); // RUN the query on the database through the connection

		$row = mysql_fetch_row($result); // fetch the current row into the array $row (it'll be always *one* row, but anyhow)
		$numberOfRecords = $row[0]; // extract the contents of the first (and only) row

		return $numberOfRecords;
	}

	// --------------------------------------------------------------------

	// Get the default number of records per page preferred by the current user:
	function getDefaultNumberOfRecords($userID)
	{
		global $loginEmail;

		global $adminLoginEmail; // these variables are defined in 'ini.inc.php'
		global $defaultNumberOfRecords;

		$userOptionsArray = array(); // initialize array variable

		// Get all user options for the current user:
		// note that if the user isn't logged in (userID=0), we don't load the default number of records from option
		// 'records_per_page' in table 'user_options' (where 'user_id = 0'). Instead, we'll return as many records as
		// defined in variable '$defaultNumberOfRecords' in 'ini.inc.php'.
		if ($userID != 0)
			$userOptionsArray = getUserOptions($userID);

		// Extract the number of records that's to be returned by default:
		if (!empty($userOptionsArray) AND !empty($userOptionsArray['records_per_page']))
			$showRows = $userOptionsArray['records_per_page']; // honour the logged in user's preferred number of records (if not empty or NULL)
		else
			$showRows = $defaultNumberOfRecords; // by default, we take the number of records from the global variable '$defaultNumberOfRecords'

		// We'll only update the appropriate session variable if either a normal user is logged in -OR- the admin is logged in and views his own user options page
		if (($loginEmail != $adminLoginEmail) OR (($loginEmail == $adminLoginEmail) && ($userID == getUserID($loginEmail))))
			// Write the list of fields into a session variable:
			saveSessionVariable("userRecordsPerPage", $showRows);

		return $showRows;
	}

	// --------------------------------------------------------------------

	// Returns the date/time information (in format 'YYYY-MM-DD hh-mm-ss') when the database was last modified:
	function getLastModifiedDateTime()
	{
		global $tableRefs; // defined in 'db.inc.php'

		connectToMySQLDatabase();

		// CONSTRUCT SQL QUERY:
		$query = "SELECT modified_date, modified_time FROM $tableRefs ORDER BY modified_date DESC, modified_time DESC, created_date DESC, created_time DESC LIMIT 1"; // get date/time info for the record that was added/edited most recently

		$result = queryMySQLDatabase($query); // RUN the query on the database through the connection

		$row = mysql_fetch_row($result); // fetch the current row into the array $row (it'll be always *one* row, but anyhow)
		$lastModifiedDateTime = $row[0] . " " . $row[1];

		return $lastModifiedDateTime;
	}

	// --------------------------------------------------------------------

	// Update the specified user permissions for the selected user(s):
	function updateUserPermissions($userIDArray, $userPermissionsArray) // '$userPermissionsArray' must contain one or more key/value elements of the form array('allow_add' => 'yes', 'allow_delete' => 'no') where key is a particular 'allow_*' field name from table 'user_permissions' and value is either 'yes' or 'no'
	{
		global $tableUserPermissions; // defined in 'db.inc.php'

		connectToMySQLDatabase();

		$permissionQueryArray = array();

		// CONSTRUCT SQL QUERY:
		// prepare the 'SET' part of the SQL query string:
		foreach($userPermissionsArray as $permissionKey => $permissionValue)
			$permissionQueryArray[] = $permissionKey . " = " . quote_smart($permissionValue);

		if (!empty($userIDArray) AND !empty($permissionQueryArray))
		{
			// Update all specified permission settings in the 'user_permissions' table for the selected user(s):
			$query = "UPDATE $tableUserPermissions SET " . implode(", ", $permissionQueryArray) . " WHERE user_id RLIKE " . quote_smart("^(" . implode("|", $userIDArray) . ")$");

			$result = queryMySQLDatabase($query); // RUN the query on the database through the connection

			return true;
		}
		else
			return false;
	}

	// --------------------------------------------------------------------

	// Generate or extract the cite key for the given record:
	function generateCiteKey($formVars)
	{
		global $defaultCiteKeyFormat; // defined in 'ini.inc.php'
		global $handleNonASCIICharsInCiteKeysDefault;
		global $userOptionsArray; // '$userOptionsArray' is made globally available in file 'import_modify.php' as well as by functions 'generateExport()' and 'generateCitations()' in 'search.php'

		// by default, we use any record-specific cite key that was entered manually by the user:
		if (isset($formVars['citeKeyName']))
			$citeKey = $formVars['citeKeyName'];
		else
			$citeKey = "";


		// check if the user's options for auto-generation of cite keys command us to replace the manually entered cite key:
		if (!empty($userOptionsArray))
		{
			if ($userOptionsArray['export_cite_keys'] == "yes") // if this user wants to include cite keys on import/export
			{
				if ($userOptionsArray['autogenerate_cite_keys'] == "yes") // if cite keys shall be auto-generated on import/export
				{
					if (empty($citeKey) OR ($userOptionsArray['prefer_autogenerated_cite_keys'] == "yes")) // if there's no manually entered cite key -OR- if the auto-generated cite key shall overwrite contents from the 'cite_key' field on import/export
					{
						if ($userOptionsArray['use_custom_cite_key_format'] == "yes") // if the user wants to use a custom cite key format
							$citeKeyFormat = $userOptionsArray['cite_key_format'];

						else // use the default cite key format that was specified by the admin in 'ini.inc.php'
							$citeKeyFormat = $defaultCiteKeyFormat;

						// auto-generate a cite key according to the given naming scheme:
						$citeKey = parsePlaceholderString($formVars, $citeKeyFormat, "<:authors:><:year:>");
					}
				}
			}
			else
				$citeKey = ""; // by omitting a cite key Bibutils will take care of generation of cite keys for its export formats (BibTeX, Endnote, RIS)
		}


		// check how to handle non-ASCII characters:
		if (!empty($userOptionsArray) AND !empty($userOptionsArray['nonascii_chars_in_cite_keys'])) // use the user's own setting
			$handleNonASCIIChars = $userOptionsArray['nonascii_chars_in_cite_keys'];
		else
			$handleNonASCIIChars = $handleNonASCIICharsInCiteKeysDefault; // use the default setting that was specified by the admin in 'ini.inc.php'

		// in addition to the handling of non-ASCII chars (given in '$handleNonASCIIChars') we'll
		// strip additional characters from the generated cite keys: for cite keys, we only allow
		// letters, digits, and the following characters: !$&*+-./:;<>?[]^_`|
		// see e.g. the discussion of cite keys at: <http://search.cpan.org/~gward/btparse-0.34/doc/bt_language.pod>
		if (!empty($citeKey))
			$citeKey = handleNonASCIIAndUnwantedCharacters($citeKey, "[:alnum:]" . preg_quote("!$&*+-./:;<>?[]^_`|", "/"), $handleNonASCIIChars);


		// ensure that each cite key is unique:
		if (!empty($citeKey) AND !empty($userOptionsArray) AND ($userOptionsArray['export_cite_keys'] == "yes") AND ($userOptionsArray['uniquify_duplicate_cite_keys'] == "yes"))
			// if the generated cite key already exists in the global array of found cite keys
			// ('$citeKeysArray'), we'll uniquify it, otherwise we'll keep it as is:
			$citeKey = ensureUniqueCiteKey($citeKey);

		return $citeKey;
	}

	// --------------------------------------------------------------------

	// Handle non-ASCII and unwanted characters:
	// this function controls the handling of any non-ASCII chars and
	// unwanted characters in file/directory names and cite keys
	function handleNonASCIIAndUnwantedCharacters($fileDirCitekeyName, $allowedFileDirCitekeyNameCharacters, $handleNonASCIIChars)
	{
		// we treat non-ASCII characters in file/directory names and cite keys depending on the setting of variable '$handleNonASCIIChars':
		if ($handleNonASCIIChars == "strip")
			$fileDirCitekeyName = convertToCharacterEncoding("ASCII", "IGNORE", $fileDirCitekeyName); // remove any non-ASCII characters

		elseif ($handleNonASCIIChars != "keep")
			// i.e., if '$handleNonASCIIChars = "keep"' we don't attempt to strip/transliterate any non-ASCII chars in the generated file/directory name or cite key;
			// otherwise if '$handleNonASCIIChars = "transliterate"' (or when '$handleNonASCIIChars' contains an unrecognized/empty string)
			// we'll transliterate most of the non-ASCII characters and strip all other non-ASCII chars that can't be converted into ASCII equivalents:
			$fileDirCitekeyName = convertToCharacterEncoding("ASCII", "TRANSLIT", $fileDirCitekeyName);


		// in addition, we remove all characters from the generated file/directory name or cite key which are not listed in variable '$allowedFileDirCitekeyNameCharacters':
		if (!empty($allowedFileDirCitekeyNameCharacters))
			$fileDirCitekeyName = preg_replace("/[^" . $allowedFileDirCitekeyNameCharacters . "]+/", "", $fileDirCitekeyName);

		return $fileDirCitekeyName;
	}

	// --------------------------------------------------------------------

	// this is a stupid hack that maps the names of the '$row' array keys to those used
	// by the '$formVars' array (which is required by function 'generateCiteKey()')
	// (eventually, the '$formVars' array should use the MySQL field names as names for its array keys)
	function buildFormVarsArray($row)
	{
		$formVars = array(); // initialize array variable

		if(isset($row['author']))
			$formVars['authorName'] = $row['author'];

		if(isset($row['title']))
			$formVars['titleName'] = $row['title'];

		if(isset($row['type']))
			$formVars['typeName'] = $row['type'];

		if(isset($row['year']))
			$formVars['yearNo'] = $row['year'];

		if(isset($row['publication']))
			$formVars['publicationName'] = $row['publication'];

		if(isset($row['abbrev_journal']))
			$formVars['abbrevJournalName'] = $row['abbrev_journal'];

		if(isset($row['volume']))
			$formVars['volumeNo'] = $row['volume'];

		if(isset($row['issue']))
			$formVars['issueNo'] = $row['issue'];

		if(isset($row['pages']))
			$formVars['pagesNo'] = $row['pages'];

		if(isset($row['corporate_author']))
			$formVars['corporateAuthorName'] = $row['corporate_author'];

		if(isset($row['thesis']))
			$formVars['thesisName'] = $row['thesis'];

		if(isset($row['address']))
			$formVars['addressName'] = $row['address'];

		if(isset($row['keywords']))
			$formVars['keywordsName'] = $row['keywords'];

		if(isset($row['abstract']))
			$formVars['abstractName'] = $row['abstract'];

		if(isset($row['publisher']))
			$formVars['publisherName'] = $row['publisher'];

		if(isset($row['place']))
			$formVars['placeName'] = $row['place'];

		if(isset($row['editor']))
			$formVars['editorName'] = $row['editor'];

		if(isset($row['language']))
			$formVars['languageName'] = $row['language'];

		if(isset($row['summary_language']))
			$formVars['summaryLanguageName'] = $row['summary_language'];

		if(isset($row['orig_title']))
			$formVars['origTitleName'] = $row['orig_title'];

		if(isset($row['series_editor']))
			$formVars['seriesEditorName'] = $row['series_editor'];

		if(isset($row['series_title']))
			$formVars['seriesTitleName'] = $row['series_title'];

		if(isset($row['abbrev_series_title']))
			$formVars['abbrevSeriesTitleName'] = $row['abbrev_series_title'];

		if(isset($row['series_volume']))
			$formVars['seriesVolumeNo'] = $row['series_volume'];

		if(isset($row['series_issue']))
			$formVars['seriesIssueNo'] = $row['series_issue'];

		if(isset($row['edition']))
			$formVars['editionNo'] = $row['edition'];

		if(isset($row['issn']))
			$formVars['issnName'] = $row['issn'];

		if(isset($row['isbn']))
			$formVars['isbnName'] = $row['isbn'];

		if(isset($row['medium']))
			$formVars['mediumName'] = $row['medium'];

		if(isset($row['area']))
			$formVars['areaName'] = $row['area'];

		if(isset($row['expedition']))
			$formVars['expeditionName'] = $row['expedition'];

		if(isset($row['conference']))
			$formVars['conferenceName'] = $row['conference'];

		if(isset($row['notes']))
			$formVars['notesName'] = $row['notes'];

		if(isset($row['approved']))
			$formVars['approvedRadio'] = $row['approved'];

		if(isset($row['location']))
			$formVars['locationName'] = $row['location'];

		if(isset($row['call_number']))
			$formVars['callNumberName'] = $row['call_number'];

		if(isset($row['serial']))
			$formVars['serialNo'] = $row['serial'];

		if(isset($row['online_publication']))
			$formVars['onlinePublicationCheckBox'] = $row['online_publication'];

		if(isset($row['online_citation']))
			$formVars['onlineCitationName'] = $row['online_citation'];

		if(isset($row['marked']))
			$formVars['markedRadio'] = $row['marked'];

		if(isset($row['copy']))
			$formVars['copyName'] = $row['copy'];

		if(isset($row['selected']))
			$formVars['selectedRadio'] = $row['selected'];

		if(isset($row['user_keys']))
			$formVars['userKeysName'] = $row['user_keys'];

		if(isset($row['user_notes']))
			$formVars['userNotesName'] = $row['user_notes'];

		if(isset($row['user_file']))
			$formVars['userFileName'] = $row['user_file'];

		if(isset($row['user_groups']))
			$formVars['userGroupsName'] = $row['user_groups'];

		if(isset($row['cite_key']))
			$formVars['citeKeyName'] = $row['cite_key'];

		if(isset($row['related']))
			$formVars['relatedName'] = $row['related'];

		if(isset($row['orig_record']))
			$formVars['origRecord'] = $row['orig_record'];

		if(isset($row['file']))
			$formVars['fileName'] = $row['file'];

		if(isset($row['url']))
			$formVars['urlName'] = $row['url'];

		if(isset($row['doi']))
			$formVars['doiName'] = $row['doi'];

		return $formVars;
	}

	// --------------------------------------------------------------------

	// Build properly formatted <option> tag elements from items listed within an array or string (and which -- in the case of strings -- are delimited by '$splitDelim').
	// The string given in '$prefix' will be used to prefix each of the <option> tags (e.g., use '\t\t' to indent each of the tags by 2 tabs)
	function buildSelectMenuOptions($sourceData, $splitDelim, $prefix, $useArrayKeysAsValues)
	{
		if (is_string($sourceData)) // split the string on the specified delimiter (which is interpreted as regular expression!):
			$sourceData = split($splitDelim, $sourceData);

		if ($useArrayKeysAsValues)
		{
			$optionTags = ""; // initialize variable

			// copy each item as option tag element to the end of the '$optionTags' variable:
			foreach ($sourceData as $itemID => $item)
			{
				if (!empty($item))
					$optionTags .= "\n$prefix<option value=\"$itemID\">$item</option>";
				else // empty items will also get an empty value:
					$optionTags .= "\n$prefix<option value=\"\"></option>";
			}
		}
		else
			$optionTags = "\n$prefix<option>" . implode("</option>\n$prefix<option>", $sourceData) . "</option>";

		return $optionTags;
	}

	// --------------------------------------------------------------------

	// Produce a <select> list with unique items from the specified field
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
	// 17. The type of the output format that shall be returned ("ARRAY", "HTML SELECT", "HTML UL" or "JSON")
	// 18. The POSIX-PATTERN that matches those substrings from the field's contents that shall be included as search suggestions
	// 19. Boolean that specifies whether search suggestions shall be wrapped into an enclosing HTML (or JSON) structure (yes if 'true')
	function selectDistinct($connection, // 1.
	                        $refsTableName, // 2.
	                        $refsTablePrimaryKey, // 3.
	                        $userDataTableName, // 4.
	                        $userDataTablePrimaryKey, // 5.
	                        $userDataTableUserID, // 6.
	                        $userDataTableUserIDvalue, // 7.
	                        $columnName, // 8.
	                        $pulldownName, // 9.
	                        $additionalOptionDisplay, // 10.
	                        $additionalOption, // 11.
	                        $defaultValue, // 12.
	                        $RestrictToField, // 13.
	                        $RestrictToFieldContents, // 14.
	                        $SplitValues, // 15.
	                        $SplitPattern, // 16.
	                        $outputFormat = "HTML SELECT", // 17.
	                        $searchSuggestionsPattern = "", // 18.
	                        $wrapSearchSuggestions = true) // 19.
	{
		$defaultWithinResultSet = FALSE;

		// Query to find distinct values of '$columnName' in '$refsTableName':
		if (isset($_SESSION['loginEmail'])) // if a user is logged in
		{
			if ($RestrictToField == "")
				 $distinctQuery = "SELECT DISTINCT $columnName FROM $refsTableName LEFT JOIN $userDataTableName ON $refsTablePrimaryKey = $userDataTablePrimaryKey AND $userDataTableUserID = $userDataTableUserIDvalue ORDER BY $columnName";
			else
				 $distinctQuery = "SELECT DISTINCT $columnName FROM $refsTableName LEFT JOIN $userDataTableName ON $refsTablePrimaryKey = $userDataTablePrimaryKey AND $userDataTableUserID = $userDataTableUserIDvalue WHERE $RestrictToField RLIKE $RestrictToFieldContents ORDER BY $columnName";
		}
		else // if NO user is logged in
		{
			if ($RestrictToField == "")
				 $distinctQuery = "SELECT DISTINCT $columnName FROM $refsTableName ORDER BY $columnName";
			else
				 $distinctQuery = "SELECT DISTINCT $columnName FROM $refsTableName WHERE $RestrictToField RLIKE $RestrictToFieldContents ORDER BY $columnName";
		}

		// Run the distinctQuery on the database through the connection:
		$resultId = queryMySQLDatabase($distinctQuery);

		// Retrieve all distinct values:
		$i = 0;
		$resultBuffer = array();

		while ($row = @ mysql_fetch_array($resultId))
		{
			if ($SplitValues) // if desired, split field contents into substrings
			{
				// split field data on the pattern specified in '$SplitPattern':
				$splittedFieldData = preg_split("#" . $SplitPattern . "#", $row[$columnName]);
				// ... copy all array elements to end of '$resultBuffer':
				foreach($splittedFieldData as $element)
				{
					$element = trim($element);
					// NOTE: in case of OpenSearch search suggestions, we only include those substrings
					//       that match the regular expression given in '$searchSuggestionsPattern'
					if (empty($searchSuggestionsPattern) OR (!empty($searchSuggestionsPattern) AND !empty($element) AND preg_match("/" . $searchSuggestionsPattern . "/i", $element)))
						$resultBuffer[$i++] = $element;
				}
			}
			else // copy field data (as is) to end of '$resultBuffer':
			{
				$element = trim($row[$columnName]);
				if (empty($searchSuggestionsPattern) OR (!empty($searchSuggestionsPattern) AND !empty($element)))
					$resultBuffer[$i++] = $element;
			}
		}

		if ($SplitValues) // (otherwise, data are already DISTINCT and ORDERed BY!)
		{
			if (!empty($resultBuffer))
			{
				// remove duplicate values from array:
				$resultBuffer = array_unique($resultBuffer);
				// sort in ascending order:
				sort($resultBuffer);
			}
		}

		if ($outputFormat == "ARRAY") // return data as a PHP array:
		{
			return $resultBuffer;
		}
		else // return data as HTML or JSON:
		{
			$outputData = "";

			if ($outputFormat == "HTML SELECT") // output data in an HTML select widget:
			{
				// Start the HTML select widget:
				if ($wrapSearchSuggestions)
					$outputData = "\n\t\t<select name=\"$pulldownName\">";

				$optionTags = ""; // initialize variable

				// Add any additional option element:
				if (!empty($additionalOptionDisplay) AND !empty($additionalOption))
					$optionTags .= "\n\t\t\t<option value=\"$additionalOption\">$additionalOptionDisplay</option>";

				// Build correct option tags from the provided database values:
				$optionTags .= buildSelectMenuOptions($resultBuffer, "", "\t\t\t", false);

				$outputData .= ereg_replace("<option([^>]*)>($defaultValue)</option>", "<option\\1 selected>\\2</option>", $optionTags); // add 'selected' attribute

				if ($wrapSearchSuggestions)
					$outputData .= "\n\t\t</select>";
			}

			elseif (($outputFormat == "HTML UL") AND !empty($resultBuffer)) // output data in an unordered HTML list:
			{
				$outputData = "<li>" . implode("</li><li>", $resultBuffer) . "</li>";

				if ($wrapSearchSuggestions)
					$outputData = "<ul>" . $outputData . "</ul>";
			}

			elseif (($outputFormat == "JSON") AND !empty($resultBuffer)) // output data in JSON format:
			{
				$outputData = '"' . implode('", "', $resultBuffer) . '"'; // for PHP 5 >= 5.2.0 and UTF-8 data, function 'json_encode()' could be used instead

				if ($wrapSearchSuggestions)
					$outputData = "[" . $outputData . "]";
			}

			return $outputData;
		}
	}

	// --------------------------------------------------------------------

	// Returns values from the given field & table:
	function getFieldContents($tableName, $columnName, $userID = "", $queryWhereClause = "", $orderBy = "", $getDistinctValues = true)
	{
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		connectToMySQLDatabase();

		if ($getDistinctValues)
			$distinct = "DISTINCT ";
		else
			$distinct = "";

		// CONSTRUCT SQL QUERY:
		$query = "SELECT " . $distinct . $columnName
		       . " FROM " . $tableName;

		if (($tableName == $tableRefs) AND isset($_SESSION['loginEmail']) AND !empty($userID)) // when querying table 'refs', and if a user is logged in...
			$query .= " LEFT JOIN " . $tableUserData . " ON serial = record_id AND user_id = " . quote_smart($userID);

		if (!empty($queryWhereClause))
			$query .= " WHERE " . $queryWhereClause;

		if (!empty($orderBy))
			$query .= " ORDER BY " . $orderBy;

		$result = queryMySQLDatabase($query); // RUN the query on the database through the connection

		$fieldContentsArray = array(); // initialize array variable

		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows found ...
		{
			while ($row = @ mysql_fetch_array($result)) // for all rows found
				$fieldContentsArray[] = $row[$columnName]; // append this row's field value to the array of extracted field values
		}

		return $fieldContentsArray;
	}

	// --------------------------------------------------------------------

	// Remove a text pattern from the beginning and/or end of a string:
	// This function is used to remove leading and/or trailing delimiters from a string.
	// Notes:  - '$removePattern' must be specified as perl-style regular expression!
	//         - set both variables '$trimLeft' & '$trimRight' to 'true' if you want your text pattern to get removed from BOTH sides of the source string;
	//           if you only want to trim the LEFT side of your source string: set '$trimLeft = true' & '$trimRight = false';
	//           if you only want to trim the RIGHT side of your source string: set '$trimLeft = false' & '$trimRight = true';
	// Example:  if '$removePattern' = ' *; *' and both, '$trimLeft' and '$trimRight', are set to 'true',
	//           the string '; red; green; yellow; ' would be transformed to 'red; green; yellow'.
	function trimTextPattern($sourceString, $removePattern, $trimLeft, $trimRight)
	{
		if ($trimLeft)
			$sourceString = preg_replace("/^" . $removePattern . "/", "", $sourceString); // remove text pattern from beginning of source string

		if ($trimRight)
			$sourceString = preg_replace("/" . $removePattern . "$/", "", $sourceString); // remove text pattern from end of source string

		return $sourceString; // return the trimmed source string
	}

	// --------------------------------------------------------------------

	// Quote variable to make safe (and escape special characters in a string for use in a SQL statement):
	function quote_smart($value)
	{
		// Remove slashes from value if 'magic_quotes_gpc = On':
		$value = stripSlashesIfMagicQuotes($value);

		// Remove any leading or trailing whitespace:
		$value = trim($value);

		// Quote & escape special chars if not a number or a numeric string:
		if (!is_numeric($value))
		{
			$value = "\"" . escapeSQL($value) . "\"";
		}
		// Quote numbers with leading zeros (which would otherwise get stripped):
		elseif (preg_match("/^0+\d+$/", $value))
		{
			$value = "\"" . $value . "\"";
		}

		return $value;
	}

	// --------------------------------------------------------------------

	// Get the path to the currently executing script, relative to the document root:
	function scriptURL()
	{
		if (isset($_SERVER['SCRIPT_NAME']))
		{
			$pathToScript = $_SERVER['SCRIPT_NAME'];
		}
		else
		{
			$pathToScript = $_SERVER['PHP_SELF'];

			// Sanitize PHP_SELF:
			if (preg_match('#\.php.+#', $pathToScript))
			{
				// Remove anything after the PHP file extension:
				$pathToScript = preg_replace('#(?<=\.php).+#', '', $pathToScript);
			}
		}

		// NOTE: When a 'show.php' URL is called from within another script via function 'fetchDataFromURL()'
		//       (as is the case for 'index.php'), '$_SERVER['SCRIPT_NAME']' and '$_SERVER['PHP_SELF']' do seem
		//       to return double slashes for path separators (e.g. "/refs//search.php"). I don't know why this
		//       happens. The line below fixes this:
		$pathToScript = preg_replace('#//+#', '/', $pathToScript);

		return $pathToScript;
	}

	// --------------------------------------------------------------------

	// Removes slashes from the input string if 'magic_quotes_gpc = On':
	function stripSlashesIfMagicQuotes($sourceString)
	{
		$magicQuotes = ini_get("magic_quotes_gpc"); // check the value of the 'magic_quotes_gpc' directive in 'php.ini'

		if ($magicQuotes) // magic_quotes_gpc = On
			$sourceString = convertSlashes($sourceString);

		return $sourceString;
	}

	// --------------------------------------------------------------------

	// Fix escape sequences within a string (i.e., remove 'unwanted' slashes):
	function convertSlashes($sourceString)
	{
		// $sourceString = stripslashes($sourceString);

		// Note that function 'stripslashes()' cannot be used here since it may remove too many slashes!
		// As an example, assume a user input in 'show.php' like this:
		// 
		//   <my cite_key> ... <is within list> ... Mock++1997Bacteria
		// 
		// 'Mock++1997Bacteria' gets preg_quote()d in 'show.php' to 'Mock\+\+1997Bacteria'. This
		// is necessary to escape any potential grep metacharacters inside the user's cite keys.
		// 
		// So, for an input of '^(Mock\+\+1997Bacteria)$', following scenario will occur with 'magic_quotes_gpc = On':
		// 
		// Case 1 ('convertSlashes()' uses 'stripslashes()'):
		//   'show.php' -> 'quote_smart()' -> 'stripSlashesIfMagicQuotes()' -> 'convertSlashes()':        ^(Mock++1997Bacteria)$   -> this step incorrectly strips the slashes!
		//   'show.php' -> 'quote_smart()' -> 'escapeSQL()':                                              ^(Mock++1997Bacteria)$
		//   'show.php' -> 'quote_smart()':                                                              "^(Mock++1997Bacteria)$"
		//   'search.php' receives:                                                                     \"^(Mock++1997Bacteria)$\"
		//   'search.php' -> 'verifySQLQuery()' -> 'stripSlashesIfMagicQuotes()' -> 'convertSlashes()':  "^(Mock++1997Bacteria)$"
		// 
		// Case 2 ('convertSlashes()' uses 'str_replace'):
		//   'show.php' -> 'quote_smart()' -> 'stripSlashesIfMagicQuotes()' -> 'convertSlashes()':        ^(Mock\+\+1997Bacteria)$
		//   'show.php' -> 'quote_smart()' -> 'escapeSQL()':                                              ^(Mock\\+\\+1997Bacteria)$
		//   'show.php' -> 'quote_smart()':                                                              "^(Mock\\+\\+1997Bacteria)$"
		//   'search.php' receives:                                                                     \"^(Mock\\\\+\\\\+1997Bacteria)$\"
		//   'search.php' -> 'verifySQLQuery()' -> 'stripSlashesIfMagicQuotes()' -> 'convertSlashes()':  "^(Mock\\+\\+1997Bacteria)$"
		// 
		// This means that 'stripslashes()' fails while the code below seems to work:

		$sourceString = str_replace('\"', '"', $sourceString); // replace any \" with "
		$sourceString = str_replace("\\'", "'", $sourceString); // replace any \' with '
		$sourceString = str_replace("\\\\", "\\", $sourceString);
		// $sourceString = eregi_replace('(\\\\)+', '\\\\', $sourceString); // instead of the previous line, this would kinda work if SQL strings aren't quote_smart()ed

		return $sourceString;
	}

	// --------------------------------------------------------------------

	// Perform search & replace actions on the given text input:
	// ('$includesSearchPatternDelimiters' must be a boolean value that specifies whether the leading and trailing slashes
	//  are included within the search pattern ['true'] or not ['false'])
	function searchReplaceText($searchReplaceActionsArray, $sourceString, $includesSearchPatternDelimiters)
	{
		// apply the search & replace actions defined in '$searchReplaceActionsArray' to the text passed in '$sourceString':
		foreach ($searchReplaceActionsArray as $searchString => $replaceString)
		{
			if (!$includesSearchPatternDelimiters)
				$searchString = "/" . $searchString . "/"; // add search pattern delimiters

			if (preg_match($searchString, $sourceString))
				$sourceString = preg_replace($searchString, $replaceString, $sourceString);
		}

		return $sourceString;
	}

	// --------------------------------------------------------------------

	// Perform case transformations on the given text input:
	// ('$transformation' must be either 'lower', 'upper', 'title' or 'heading')
	// 
	// NOTE: For UTF-8, the PHP functions 'strtolower()' and 'strtoupper()' will only work correctly
	//       if the server has locales installed which support UTF-8! More info is available at:
	//       <http://www.phpwact.org/php/i18n/charsets>
	//       <http://www.phpwact.org/php/i18n/utf-8>
	// 
	// TODO: Implement function 'changeCase()' so that it always works for UTF-8
	//       See e.g. functions 'utf8_strtolower()' and 'utf8_strtoupper()' at
	//       <http://dev.splitbrain.org/view/darcs/dokuwiki/inc/utf8.php>
	function changeCase($transformation, $sourceString)
	{
		if (eregi("lower", $transformation)) // change source text to lower case
			$sourceString = strtolower($sourceString);

		elseif (eregi("upper", $transformation)) // change source text to upper case
			$sourceString = strtoupper($sourceString);

		elseif (eregi("title", $transformation)) // change source text to title case
			$sourceString = preg_replace("/\b(\w)(\w+)/e", "strtoupper('\\1').strtolower('\\2')", $sourceString); // the 'e' modifier allows to execute PHP code within the replacement pattern

		elseif (eregi("heading", $transformation)) // change source text to heading case (opposed to 'title', we only touch words with more than 3 chars, and we only change the case of the first letter but not any subsequent ones)
			$sourceString = preg_replace("/\b(\w)(\w{3,})/e", "strtoupper('\\1').'\\2'", $sourceString); // the 'e' modifier allows to execute PHP code within the replacement pattern

		return $sourceString;
	}

	// --------------------------------------------------------------------

	// Sets the system's locale information:
	// On *NIX systems, use "locale -a" on the command line to display all locales
	// supported on your system. See <http://www.php.net/setlocale> for more information.
	function setSystemLocale($charSet = "", $systemLocales = "NONE")
	{
		global $contentTypeCharset; // these variables are defined in 'ini.inc.php'
		global $convertExportDataToUTF8;

		if (empty($charSet))
			$charSet = $contentTypeCharset;

		if ($systemLocales == "NONE") {
			if ($charSet == "UTF-8")
				$systemLocales = array('en_US.UTF-8', 'en_GB.UTF-8', 'en_CA.UTF-8', 'en_AU.UTF-8', 'en_NZ.UTF-8', 'de_DE.UTF-8', 'fr_FR.UTF-8', 'es_ES.UTF-8');
			else // we assume "ISO-8859-1" by default
				$systemLocales = array('en_US.ISO8859-1', 'en_GB.ISO8859-1', 'en_CA.ISO8859-1', 'en_AU.ISO8859-1', 'en_NZ.ISO8859-1', 'de_DE.ISO8859-1', 'fr_FR.ISO8859-1', 'es_ES.ISO8859-1');
		}

		setlocale(LC_COLLATE, $systemLocales); // set locale for string comparison (including pattern matching)
		setlocale(LC_CTYPE, $systemLocales); // set locale for character classification and conversion, for example 'strtoupper()'

		// get the current settings without affecting them:
		$systemLocaleCollate = setlocale(LC_COLLATE, "0");
		$systemLocaleCType = setlocale(LC_CTYPE, "0");

		return array($systemLocaleCollate, $systemLocaleCType);
	}

	// --------------------------------------------------------------------

	// Sets the mimetype & character encoding in the header:
	function setHeaderContentType($contentType, $contentTypeCharset)
	{
		header('Content-type: ' . $contentType . '; charset=' . $contentTypeCharset);
	}

	// --------------------------------------------------------------------

	// Set HTTP status response

	// From user 'Ciantic' found at <http://www.php.net/header> (24-Dec-2005 03:07)
	// This contains all HTTP status responses defined in RFC2616 section 6.1.1
	// See <http://www.w3.org/Protocols/rfc2616/rfc2616-sec6.html#sec6.1.1>
	function setHTTPStatus($statusCode)
	{
		// HTTP Protocol defined status codes:
		static $http = array(100 => "HTTP/1.1 100 Continue",
		                     101 => "HTTP/1.1 101 Switching Protocols",
		                     200 => "HTTP/1.1 200 OK",
		                     201 => "HTTP/1.1 201 Created",
		                     202 => "HTTP/1.1 202 Accepted",
		                     203 => "HTTP/1.1 203 Non-Authoritative Information",
		                     204 => "HTTP/1.1 204 No Content",
		                     205 => "HTTP/1.1 205 Reset Content",
		                     206 => "HTTP/1.1 206 Partial Content",
		                     300 => "HTTP/1.1 300 Multiple Choices",
		                     301 => "HTTP/1.1 301 Moved Permanently",
		                     302 => "HTTP/1.1 302 Found",
		                     303 => "HTTP/1.1 303 See Other",
		                     304 => "HTTP/1.1 304 Not Modified",
		                     305 => "HTTP/1.1 305 Use Proxy",
		                     307 => "HTTP/1.1 307 Temporary Redirect",
		                     400 => "HTTP/1.1 400 Bad Request",
		                     401 => "HTTP/1.1 401 Unauthorized",
		                     402 => "HTTP/1.1 402 Payment Required",
		                     403 => "HTTP/1.1 403 Forbidden",
		                     404 => "HTTP/1.1 404 Not Found",
		                     405 => "HTTP/1.1 405 Method Not Allowed",
		                     406 => "HTTP/1.1 406 Not Acceptable",
		                     407 => "HTTP/1.1 407 Proxy Authentication Required",
		                     408 => "HTTP/1.1 408 Request Time-out",
		                     409 => "HTTP/1.1 409 Conflict",
		                     410 => "HTTP/1.1 410 Gone",
		                     411 => "HTTP/1.1 411 Length Required",
		                     412 => "HTTP/1.1 412 Precondition Failed",
		                     413 => "HTTP/1.1 413 Request Entity Too Large",
		                     414 => "HTTP/1.1 414 Request-URI Too Large",
		                     415 => "HTTP/1.1 415 Unsupported Media Type",
		                     416 => "HTTP/1.1 416 Requested range not satisfiable",
		                     417 => "HTTP/1.1 417 Expectation Failed",
		                     500 => "HTTP/1.1 500 Internal Server Error",
		                     501 => "HTTP/1.1 501 Not Implemented",
		                     502 => "HTTP/1.1 502 Bad Gateway",
		                     503 => "HTTP/1.1 503 Service Unavailable",
		                     504 => "HTTP/1.1 504 Gateway Time-out");

		header($http[$statusCode]);
	}

	// --------------------------------------------------------------------

	// This function takes the URL given in '$sourceURL' and retrieves the returned data:
	function fetchDataFromURL($sourceURL)
	{
		global $errors;

		$handle = fopen($sourceURL, "r"); // fetch data from URL in read mode

		$sourceData = "";

		if ($handle)
		{
			while (!feof($handle))
			{
				$sourceData .= fread($handle, 4096); // read data in chunks
			}
			fclose($handle);
		}
		else
		{
			$errorMessage = "Error occurred: Failed to open " . $sourceURL; // network error

			if (!isset($errors["sourceText"]))
				$errors["sourceText"] = $errorMessage;
			else
				$errors["sourceText"] = $errors["sourceText"] . "<br>" . $errorMessage;
		}

		return $sourceData;
	}

	// --------------------------------------------------------------------

	// Send '$dataString' as POST request (using the 'application/x-www-form-urlencoded'
	// content type) to the given '$host'/'$path':
	function sendPostRequest($host, $path, $referer, $dataString)
	{
		$port = 80; // server port to be used with the connection
		$timeout = 600; // connection time out in seconds
		$result = "";

		// build header:
		$header = "POST " . $path . " HTTP/1.0\r\n" // "HTTP/1.1" would return data with "Transfer-Encoding: chunked"
		        . "Host: " . $host . "\r\n"
		        . "Referer: " . $referer . "\r\n"
		        . "Content-Type: application/x-www-form-urlencoded\r\n"
		        . "Content-Length: ". strlen($dataString) ."\r\n"
		        . "\r\n";

		// open connection:
		// see <http://www.php.net/fsockopen>
		$fp = fsockopen($host, $port, $errorNo, $errorMsg, $timeout);

		if (!$fp)
		{
			$result = "Error $errorNo : $errorMsg";
		}
		else
		{
			// POST data:
			fputs($fp, $header . $dataString);

			// read result:
			while (!feof($fp))
				$result .= fgets($fp, 1024);

			// close connection:
			fclose($fp);
		}

		return $result;
	}

	// --------------------------------------------------------------------

	// Detect character encoding:
	// NOTE: - Currently, this function only distinguishes between ISO-8859-1 and UTF-8!
	function detectCharacterEncoding($sourceString, $detectOrder = "")
	{
		// Method A:
		// Function 'mb_detect_encoding()' requires PHP with multi-byte support (i.e., PHP must
		// be compiled with the '--enable-mbstring' configure option).
		// (see: <http://php.net/manual/en/function.mb-detect-encoding.php>)

//		$charSet = "";

//		if (empty($detectOrder))
			// Set the default character encoding detection order:
			// (see: <http://www.php.net/mb-detect-order>)
		//	$detectOrder = implode(", ", mb_detect_order()); // on an English system this may be e.g. "ASCII, UTF-8" which wouldn't be useful in our case
//			$detectOrder = "UTF-8, ISO-8859-1"; // in case of refbase, we currently hardcode the detection order

		// Detect the character encoding of the given '$sourceString' with the given '$detectOrder':
//		$charSet = mb_detect_encoding($sourceString . "a", $detectOrder); // an ASCII char is appended to avoid a bug, see comment by <hoermann dot j at gmail dot com> at <http://php.net/manual/en/function.mb-detect-encoding.php>


		// Method B:
		// Based on function 'detectUTF8()' by user <chris at w3style dot co dot uk>
		// at <http://php.net/manual/en/function.mb-detect-encoding.php>
		// (see also: <http://w3.org/International/questions/qa-forms-utf-8.html>)

		// Check if a string contains UTF-8 characters:
		// NOTE: This regex pattern only looks for non-ASCII multibyte sequences in
		//       the UTF-8 range and stops once it finds at least one multibytes string.
		if (preg_match('%(?:
		                      [\xC2-\xDF][\x80-\xBF]            # non-overlong 2-byte
		                    | \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
		                    | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
		                    | \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
		                    | \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
		                    | [\xF1-\xF3][\x80-\xBF]{3}         # planes 4-15
		                    | \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
		                 )+%xs', $sourceString))
			$charSet = "UTF-8"; // found at least one multibyte UTF-8 character
		else
			$charSet = "ISO-8859-1";

		return $charSet;
	}

	// --------------------------------------------------------------------

	// Convert to character encoding:
	// This function converts text that's represented in '$sourceCharset' into the character encoding
	// given in '$targetCharset'. If '$sourceCharset' isn't given, we default to the refbase database
	// encoding (which is indicated in '$contentTypeCharset'). '$transliteration' must be either
	// "TRANSLIT" or "IGNORE" causing characters which are unrecognized by the target charset to get
	// either transliterated or ignored, respectively.
	function convertToCharacterEncoding($targetCharset, $transliteration, $sourceString, $sourceCharset = "")
	{
		global $contentTypeCharset; // defined in 'ini.inc.php'
		global $transtab_latin1_ascii; // defined in 'transtab_latin1_ascii.inc.php'
		global $transtab_unicode_ascii; // defined in 'transtab_unicode_ascii.inc.php'
		global $transtab_unicode_latin1; // defined in 'transtab_unicode_latin1.inc.php'
		global $transtab_unicode_refbase; // defined in 'transtab_unicode_refbase.inc.php'

		if (empty($sourceCharset))
			$sourceCharset = $contentTypeCharset;

		// In case of ISO-8859-1/UTF-8 to ASCII conversion we attempt to transliterate non-ASCII chars,
		// comparable to the fallback notations that people use commonly in email and on typewriters to
		// represent unavailable characters:
		if (($targetCharset == "ASCII") AND ($transliteration == "TRANSLIT"))
		{
			if ($sourceCharset == "UTF-8")
				$convertedString = searchReplaceText($transtab_unicode_ascii, $sourceString, false);
			else // we assume "ISO-8859-1" by default
				$convertedString = searchReplaceText($transtab_latin1_ascii, $sourceString, false);

			// Strip any additional non-ASCII characters which we weren't able to transliterate:
			$convertedString = iconv($sourceCharset, "ASCII//IGNORE", $convertedString);

			// Notes from <http://www.php.net/manual/en/function.iconv.php> regarding "TRANSLIT" and "IGNORE":
			// - If you append the string //TRANSLIT to out_charset transliteration is activated.
			//   This means that when a character can't be represented in the target charset, it can
			//   be approximated through one or several similarly looking characters. If you append
			//   the string //IGNORE, characters that cannot be represented in the target charset
			//   are silently discarded. Otherwise, str is cut from the first illegal character.
		}

		// Similar to the ISO-8859-1/UTF-8 to ASCII conversion we attempt to transliterate non-latin1 chars when
		// converting from UTF-8 to ISO-8859-1.
		// NOTE: we don't use 'iconv("UTF-8", "ISO-8859-1//TRANSLIT", $sourceString)' here, since this seems to
		//       abort the conversion with an error ("Detected an illegal character in input string") if e.g. a
		//       greek delta character is encountered.
		elseif (($targetCharset == "ISO-8859-1") AND ($transliteration == "TRANSLIT") AND ($sourceCharset == "UTF-8"))
		{
			// Convert Unicode entities to refbase markup (if possible):
			$convertedString = searchReplaceText($transtab_unicode_refbase, $sourceString, true);

			// Attempt to transliterate any remaining non-latin1 characters:
			$convertedString = searchReplaceText($transtab_unicode_latin1, $convertedString, false);

			// Strip any additional non-latin1 characters which we weren't able to transliterate:
			$convertedString = iconv($sourceCharset, "ISO-8859-1//IGNORE", $convertedString);
		}

		else
			$convertedString = iconv($sourceCharset, "$targetCharset//$transliteration", $sourceString);

		return $convertedString;
	}

	// --------------------------------------------------------------------

	// Encode HTML entities:
	// (this custom function is provided so that it'll be easier to change the way how entities are HTML encoded later on)
	function encodeHTML($sourceString)
	{
		global $contentTypeCharset; // defined in 'ini.inc.php'

		// Note: Using PHP 5.0.4, I couldn't get 'htmlentities()' to work properly with UTF-8. Apparently, versions before
		// PHP 4.3.11 and PHP 5.0.4 had a partially incorrect utf8 to htmlentities mapping (see <http://bugs.php.net/28067>),
		// however, PHP 5.0.4 still seems buggy to me. Therefore, in case of UTF-8, we'll use 'mb_convert_encoding()' instead.
		// IMPORTANT: this requires multi-byte support enabled on your PHP server!
		//            (i.e., PHP must be compiled with the '--enable-mbstring' configure option)
		if ($contentTypeCharset == "UTF-8")
		{
			// encode HTML special chars
			$sourceString = encodeHTMLspecialchars($sourceString);
			// converts from 'UTF-8' to 'HTML-ENTITIES' (see: <http://php.net/manual/en/function.mb-convert-encoding.php>)
			$encodedString = mb_convert_encoding($sourceString, 'HTML-ENTITIES', "$contentTypeCharset");
		}
		else
			$encodedString = htmlentities($sourceString, ENT_COMPAT, "$contentTypeCharset");
			// Notes from <http://www.php.net/htmlentities>:
			//
			//     - The optional second parameter lets you define what will be done with 'single' and "double" quotes.
			//       It takes on one of three constants with the default being ENT_COMPAT:
			//       ENT_COMPAT:   Will convert double-quotes and leave single-quotes alone.
			//       ENT_QUOTES:   Will convert both double and single quotes.
			//       ENT_NOQUOTES: Will leave both double and single quotes unconverted.
			//
			//     - The optional third argument defines the character set used in conversion. Support for this argument
			//       was added in PHP 4.1.0. Presently, the ISO-8859-1 character set is used as the default.

		return $encodedString;
	}

	// --------------------------------------------------------------------

	// Encode HTML special chars:
	// As opposed to the 'encodeHTML()' function this function will only convert the characters supported by the
	// 'htmlspecialchars()' function:
	// - '&' (ampersand) becomes '&amp;'
	// - '"' (double quote) becomes '&quot;' when ENT_NOQUOTES is not set
	// - ''' (single quote) becomes '&#039;' only when ENT_QUOTES is set
	// - '<' (less than) becomes '&lt;'
	// - '>' (greater than) becomes '&gt;'
	// Note that these (and only these!) entities are also supported by XML (which is why we use this function within the XML
	// generating functions 'generateRSS()', 'modsRecord()' & 'atomEntry()' and leave all other higher ASCII chars unencoded)
	function encodeHTMLspecialchars($sourceString)
	{
		global $contentTypeCharset; // defined in 'ini.inc.php'

		$encodedString = htmlspecialchars($sourceString, ENT_COMPAT, "$contentTypeCharset");
		// Notes from <http://www.php.net/htmlspecialchars>:
		//
		//     - The optional second parameter lets you define what will be done with 'single' and "double" quotes.
		//       It takes on one of three constants with the default being ENT_COMPAT:
		//       ENT_COMPAT:   Will convert double-quotes and leave single-quotes alone.
		//       ENT_QUOTES:   Will convert both double and single quotes.
		//       ENT_NOQUOTES: Will leave both double and single quotes unconverted.
		//
		//     - The optional third argument defines the character set used in conversion. Support for this argument
		//       was added in PHP 4.1.0. Presently, the ISO-8859-1 character set is used as the default.

		return $encodedString;
	}

	// --------------------------------------------------------------------

	// Decode HTML entities:
	// This function converts HTML entities in '$sourceString' to the character encoding given in '$targetCharset'.
	// It is intended to work similar to function 'html_entity_decode()' but should also support conversion of numeric
	// entities as well as UTF-8 on PHP 4. In case of refbase, '$targetCharset' should be either "UTF-8" or "ISO-8859-1".
	function decodeHTML($targetCharset, $sourceString)
	{
		global $contentTypeCharset; // defined in 'ini.inc.php'

		static $transtab_HTML;

		// Method A:
		// Function 'html_entity_decode()' is available since PHP 4.3.0, but UTF-8 support was only added with PHP 5?
		// (see <http://www.php.net/html-entity-decode>)
		// NOTE: This function doesn't convert numeric entities, so, if used, it should be combined with the code block
		//       underneath "Replace numeric entities" below.
//		$convertedString = html_entity_decode($sourceString, ENT_QUOTES, "$targetCharset");
		// W.r.t. the second parameter, see notes underneath the call to 'htmlentities()' in function 'encodeHTML()'


		// Method B:
		// Function 'mb_convert_encoding()' requires PHP with multi-byte support (i.e., PHP must be compiled with the
		// '--enable-mbstring' configure option). Converts from 'HTML-ENTITIES' to '$targetCharset'.
		// (see: <http://php.net/manual/en/function.mb-convert-encoding.php>)
		// NOTE: Compared to methods A + C, this seems to yield different results! ?:-/
//		$convertedString = mb_convert_encoding($sourceString, "$targetCharset", 'HTML-ENTITIES');


		// Method C:
		// Assembled from user contributions at <http://www.php.net/html-entity-decode>

		// - Replace numeric entities:
		$convertedString = preg_replace('/&#x0*([0-9a-f]+);/ei', "charNumToCharString('$targetCharset', hexdec('\\1'))", $sourceString); // hex notation
		$convertedString = preg_replace('/&#0*([0-9]+);/e', "charNumToCharString('$targetCharset', '\\1')", $convertedString); // decimal notation

		// - Replace literal entities:
		if (!isset($transtab_HTML))
		{
			// Get the translation table that's used by function 'htmlspecialchars()':
			$transtab_HTML = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
			$transtab_HTML = array_flip($transtab_HTML);

			// Change the translation table from latin1 to UTF-8 (if necessary):
			if ($targetCharset == "UTF-8")
				foreach ($transtab_HTML as $key => $value)
					$transtab_HTML[$key] = utf8_encode($value); // encode ISO-8859-1 char as UTF-8
		}

		$convertedString = strtr($convertedString, $transtab_HTML);

		return $convertedString;
	}

	// --------------------------------------------------------------------

	// Decode HTML special chars:
	// As opposed to the 'decodeHTML()' function this function will only decode the characters supported by the
	// 'htmlspecialchars()' function:
	// - '&amp;' (ampersand) becomes '&'
	// - '&quot;' (double quote) becomes '"' when ENT_NOQUOTES is not set
	// - '&#039;' (single quote) becomes ''' only when ENT_QUOTES is set
	// - '&lt;' (less than) becomes '<'
	// - '&gt;' (greater than) becomes '>'
	function decodeHTMLspecialchars($sourceString)
	{
		static $transtab_HTMLspecialchars;

		// Method A:
		// Function 'htmlspecialchars_decode()' seems to be available since PHP 5.1.0.
		// (see <http://www.php.net/htmlspecialchars-decode>)
//		$decodedString = htmlspecialchars_decode($sourceString, ENT_QUOTES);
		// W.r.t. the second parameter, see notes underneath the call to 'htmlspecialchars()' in function 'encodeHTMLspecialchars()'


		// Method B:
		// Assembled from user contributions at <http://www.php.net/htmlspecialchars-decode>
		if (!isset($transtab_HTMLspecialchars))
		{
			// Get the translation table that's used by function 'htmlspecialchars()':
			$transtab_HTMLspecialchars = get_html_translation_table(HTML_SPECIALCHARS, ENT_QUOTES);
			$transtab_HTMLspecialchars = array_flip($transtab_HTMLspecialchars);

			if (!isset($transtab_HTMLspecialchars['&#039;'])) // we need to add '&#039;' since the above call to 'get_html_translation_table()' returns just '&#39;'
				$transtab_HTMLspecialchars['&#039;'] = "'";
		}

		$decodedString = strtr($sourceString, $transtab_HTMLspecialchars);

		return $decodedString;
	}

	// --------------------------------------------------------------------

	// Returns the character string that corresponds to the given character code value:
	// (modified after user contributions by <akniep at rayo dot info>, <aurynas dot butkus at gmail dot com>
	//  and <romans at void dot lv> at <http://www.php.net/html-entity-decode>)
	// NOTE: - In case of refbase, '$targetCharset' should be either "UTF-8" or "ISO-8859-1"
	//       - For a latin1-based database, we'll convert any Unicode-only entities into the
	//         corresponding refbase markup (if possible), and any remaining UTF-8 characters
	//         will be converted to their ASCII equivalents.
	function charNumToCharString($targetCharset, $num)
	{
		global $transtab_unicode_ascii; // defined in 'transtab_unicode_ascii.inc.php'
		global $transtab_unicode_refbase; // defined in 'transtab_unicode_refbase.inc.php'

		// Generates a UTF-8 string that corresponds to the given Unicode value:
		if ($num < 0)
			$utfChar = '';
		elseif ($num < 128)
			$utfChar = chr($num);
		elseif ($num < 2048)
			$utfChar = chr(($num >> 6) + 192) . chr(($num & 63) + 128);
		elseif ($num < 65536)
			$utfChar = chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
		elseif ($num < 2097152)
			$utfChar = chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);

		if (!empty($utfChar) AND $targetCharset == "ISO-8859-1")
		{
			// Convert Unicode entities to refbase markup (if possible):
			$utfChar = searchReplaceText($transtab_unicode_refbase, $utfChar, true);

			// Convert any remaining UTF-8 characters to their ASCII equivalents:
			// TODO: Should we use iconv (function 'convertToCharacterEncoding()') instead?
			$utfChar = searchReplaceText($transtab_unicode_ascii, $utfChar, false);
		}

		return $utfChar;
	}

	// --------------------------------------------------------------------

	// Strip HTML and PHP tags from input string:
	// See <http://www.php.net/strip_tags>
	function stripTags($sourceString, $allowedTags = "")
	{
		$cleanedString = strip_tags($sourceString, $allowedTags);

		return $cleanedString;
	}

	// --------------------------------------------------------------------

	// Verify the SQL query specified by the user and modify it if security concerns are encountered:
	// (this function does add/remove user-specific query code as required and will fix problems with escape sequences within the SQL query)
	function verifySQLQuery($sqlQuery, $referer, $displayType, $showLinks)
	{
		global $loginEmail;
		global $loginUserID;
		global $fileVisibility; // these variables are specified in 'ini.inc.php'
		global $librarySearchPattern;
		global $showAdditionalFieldsDetailsViewDefault;
		global $showUserSpecificFieldsDetailsViewDefault;
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		global $loc; // '$loc' is made globally available in 'core.php'

		// note that, if several errors occur, only the last error message will be displayed

		// disallow display/querying of the 'file' field if NONE of the following conditions are met:
		// - the variable '$fileVisibility' (defined in 'ini.inc.php') is set to 'everyone'
		// - the variable '$fileVisibility' is set to 'login' AND the user is logged in
		// - the variable '$fileVisibility' is set to 'user-specific' AND the 'user_permissions' session variable contains 'allow_download'
		if (!($fileVisibility == "everyone" OR ($fileVisibility == "login" AND isset($_SESSION['loginEmail'])) OR ($fileVisibility == "user-specific" AND (isset($_SESSION['user_permissions']) AND ereg("allow_download", $_SESSION['user_permissions'])))))
		{
			// remove 'file' field from SQL query:
			$sqlQuery = stripFieldFromSQLQuery($sqlQuery, "file", true);
		}


		// disallow display/querying of the 'location' field if the user is NOT logged in:
		// (this is mostly done to shield user email addresses from exposure to search engines and/or email harvesting robots)
		if (!isset($_SESSION['loginEmail']))
		{
			// remove 'location' field from SQL query:
			$sqlQuery = stripFieldFromSQLQuery($sqlQuery, "location", true);
		}

		// supply generic 'WHERE' clause if it didn't exist in the SELECT query:
		if (eregi("^SELECT", $sqlQuery) AND !eregi(" FROM " . $tableRefs . ".* WHERE ", $sqlQuery))
			$sqlQuery = preg_replace("/(?= ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$)/i", " WHERE serial RLIKE \".+\"", $sqlQuery, 1);

		// supply generic 'ORDER BY' clause if it didn't exist in the SELECT query:
		// TODO: - add a suitable 'ORDER BY' clause for Browse view and if '$citeOrder != "author"'
		if (eregi("^SELECT", $sqlQuery) AND !eregi(" FROM " . $tableRefs . ".* ORDER BY ", $sqlQuery) AND ($displayType != "Browse"))
			$sqlQuery = preg_replace("/(?= LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$)/i", " ORDER BY author, year DESC, publication", $sqlQuery, 1);

		// handle the display & querying of user-specific fields:
		if (!isset($_SESSION['loginEmail'])) // if NO user is logged in...
		{
			// ... and any user-specific fields are part of the SELECT or ORDER BY statement...
			if ((empty($referer) OR eregi(".+search\.php",$referer)) AND (eregi("(SELECT |ORDER BY |, *)(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)",$sqlQuery))) // if the calling script ends with 'search.php' (i.e., is NOT 'show.php' or 'sru.php', see note below!) AND any user-specific fields are part of the SELECT or ORDER BY clause
			{
				// if the 'SELECT' clause contains any user-specific fields:
				if (preg_match("/SELECT(.(?!FROM))+?(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)/i",$sqlQuery))
				{
					// return an appropriate error message:
					// note: we don't write out any error message if the user-specific fields do only occur within the 'ORDER' clause (but not within the 'SELECT' clause)
					$HeaderString = returnMsg($loc["Warning_DisplayUserSpecificFieldsOmitted"] . "!", "warning", "strong", "HeaderString");
				}

				$sqlQuery = eregi_replace("(SELECT|ORDER BY) (marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)( DESC)?", "\\1 ", $sqlQuery); // ...delete any user-specific fields from beginning of 'SELECT' or 'ORDER BY' clause
				$sqlQuery = eregi_replace(", *(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)( DESC)?", "", $sqlQuery); // ...delete any remaining user-specific fields from 'SELECT' or 'ORDER BY' clause
				$sqlQuery = eregi_replace("(SELECT|ORDER BY) *, *", "\\1 ", $sqlQuery); // ...remove any field delimiters that directly follow the 'SELECT' or 'ORDER BY' terms

				$sqlQuery = preg_replace("/SELECT *(?=FROM)/i", buildSELECTclause("", "", "", false, false) . " ", $sqlQuery); // ...supply generic 'SELECT' clause if it did ONLY contain user-specific fields
				$sqlQuery = preg_replace("/ORDER BY *(?=LIMIT|GROUP BY|HAVING|PROCEDURE|FOR UPDATE|LOCK IN|$)/i", "ORDER BY author, year DESC, publication", $sqlQuery); // ...supply generic 'ORDER BY' clause if it did ONLY contain user-specific fields
			}

			// ... and the 'LEFT JOIN...' statement is part of the 'FROM' clause...
			if ((eregi(".+search\.php",$referer)) AND (eregi("LEFT JOIN $tableUserData",$sqlQuery))) // if the calling script ends with 'search.php' (i.e., is NOT 'show.php' or 'sru.php', see note below!) AND the 'LEFT JOIN...' statement is part of the 'FROM' clause...
				$sqlQuery = eregi_replace("FROM $tableRefs LEFT JOIN.+WHERE","FROM $tableRefs WHERE",$sqlQuery); // ...delete 'LEFT JOIN...' part from 'FROM' clause

			// ... and any user-specific fields are part of the WHERE clause...
			if ((eregi(".+search\.php",$referer) OR eregi("^RSS$",$displayType)) AND (eregi("WHERE.+(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)",$sqlQuery))) // if a user who's NOT logged in tries to query user-specific fields (by use of 'sql_search.php')...
			// Note that the script 'show.php' may query the user-specific field 'selected' (e.g., by URLs of the form: 'show.php?author=...&userID=...&only=selected')
			// but since (in that case) the '$referer' variable is either empty or does not end with 'search.php' this if clause will not apply (which is ok since we want to allow 'show.php' to query the 'selected' field).
			// The same applies in the case of 'sru.php' which may query the user-specific field 'cite_key' (e.g., by URLs like: 'sru.php?version=1.1&query=bib.citekey=...&x-info-2-auth1.0-authenticationToken=email=...')
			// Note that this also implies that a user who's not logged in might perform a query such as: 'http://localhost/refs/show.php?cite_key=...&userID=...'
			{
				// Note: in the patterns below we'll attempt to account for parentheses but this won't catch all cases!
				$sqlQuery = preg_replace("/WHERE( *\( *?)* *(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related).+?(?= (AND|OR)\b| ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$)/i","WHERE\\1",$sqlQuery); // ...delete any user-specific fields from 'WHERE' clause
				$sqlQuery = preg_replace("/( *\( *?)*( *(AND|OR)\b)? *(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related).+?(?=( *\) *?)* +((AND|OR)\b|ORDER BY|LIMIT|GROUP BY|HAVING|PROCEDURE|FOR UPDATE|LOCK IN|$))/i","\\1",$sqlQuery); // ...delete any user-specific fields from 'WHERE' clause
				$sqlQuery = preg_replace("/WHERE( *\( *?)* *(AND|OR)\b/i","WHERE\\1",$sqlQuery); // ...delete any superfluous 'AND' or 'OR' that wasn't removed properly by the two regex patterns above
				$sqlQuery = preg_replace("/WHERE( *\( *?)*(?= ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$)/i","WHERE serial RLIKE \".+\"",$sqlQuery); // ...supply generic 'WHERE' clause if it did ONLY contain user-specific fields

				// return an appropriate error message:
				$HeaderString = returnMsg($loc["Warning_QueryUserSpecificFieldsOmitted"] . "!", "warning", "strong", "HeaderString");
			}
		}

		else // if a user is logged in...
		{
			if (eregi("LEFT JOIN $tableUserData",$sqlQuery)) // if the 'LEFT JOIN...' statement is part of the 'FROM' clause...
			{
				// ...and any user-specific fields other(!) than the 'selected' or 'cite_key' field are part of the 'SELECT' or 'WHERE' clause...
				// Note that we exclude the 'selected' field here (although it is user-specific). By that we allow the 'selected' field to be queried by every user who's logged in.
				// This is done to support the 'show.php' script which may query the user-specific field 'selected' (e.g., by URLs of the form: 'show.php?author=...&userID=...&only=selected')
				// Similarly, we exclude 'cite_key' here to allow every user to query other user's 'cite_key' fields using 'sru.php' (e.g., by URLs like: 'sru.php?version=1.1&query=bib.citekey=...&x-info-2-auth1.0-authenticationToken=email=...')
				if (eregi(", (marked|copy|user_keys|user_notes|user_file|user_groups|related)",$sqlQuery) OR eregi("WHERE.+(marked|copy|user_keys|user_notes|user_file|user_groups|related)",$sqlQuery))
				{
					$sqlQuery = eregi_replace("user_id *= *[0-9]+","user_id = $loginUserID",$sqlQuery); // ...replace any other user ID with the ID of the currently logged in user

					if (!empty($librarySearchPattern) AND !(eregi("^location$",$librarySearchPattern[0]) AND preg_match("/location RLIKE " . quote_smart($librarySearchPattern[1]) . "/i",$sqlQuery))) // don't replace the 'location' part of the WHERE clause if it stems from variable '$librarySearchPattern' in 'ini.inc.php' (NOTE: this is quite hacky! :-/)
						$sqlQuery = preg_replace("/location RLIKE .+?(?= (AND|OR)\b| ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$)/i","location RLIKE " . quote_smart($loginEmail),$sqlQuery); // ...replace any other user email address with the login email address of the currently logged in user
				}
			}

			// if we're going to display record details for a logged in user, we have to ensure the display of the 'location' field as well as the user-specific fields (which may have been deleted from a query due to a previous logout action);
			// in 'Display Details' view, the 'call_number' and 'serial' fields are the last generic fields before any user-specific fields:
			if (((eregi("^Display$",$displayType) AND ($showUserSpecificFieldsDetailsViewDefault == "yes")) OR (eregi("^Export$",$displayType))) AND (eregi(", call_number, serial FROM $tableRefs",$sqlQuery))) // if the user-specific fields are missing from the SELECT statement...
				$sqlQuery = eregi_replace(", call_number, serial FROM $tableRefs",", call_number, serial, marked, copy, selected, user_keys, user_notes, user_file, user_groups, cite_key, related FROM $tableRefs",$sqlQuery); // ...add all user-specific fields to the 'SELECT' clause

			// in 'Display Details' view, the 'location' field should occur within the SELECT statement before the 'call_number' and 'serial' fields:
//			if (((eregi("^Display$",$displayType) AND ($showAdditionalFieldsDetailsViewDefault == "yes")) OR (eregi("^Export$",$displayType))) AND (preg_match("/(?<!location,) call_number, serial(?=(, marked, copy, selected, user_keys, user_notes, user_file, user_groups, cite_key, related)? FROM $tableRefs)/i",$sqlQuery))) // if the 'location' field is missing from the SELECT statement...
//				$sqlQuery = preg_replace("/(?<!location), call_number, serial(?=(, marked, copy, selected, user_keys, user_notes, user_file, user_groups, cite_key, related)? FROM $tableRefs)/i",", location, call_number, serial",$sqlQuery); // ...add the 'location' field to the 'SELECT' clause
			// NOTE: I've commented the above code block for now, since, for '$showAdditionalFieldsDetailsViewDefault=yes' with additional fields being hidden, it causes the 'location' field to appear when clicking any of the sort/browse/view links
			//       The drawback is that the 'location' field isn't added to the SQL query now when a record in Details view is reloaded after an anonymous user did view the record in Details view and then decided to log in

			if ((eregi("^(Cite|Display|Export|RSS)$",$displayType)) AND (!eregi("LEFT JOIN $tableUserData",$sqlQuery))) // if the 'LEFT JOIN...' statement isn't already part of the 'FROM' clause...
				$sqlQuery = eregi_replace(" FROM $tableRefs"," FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = $loginUserID",$sqlQuery); // ...add the 'LEFT JOIN...' part to the 'FROM' clause
		}


		// restrict adding of columns to SELECT queries (so that 'DELETE FROM refs ...' statements won't get modified as well);
		// we'll also exclude the Browse view since these links aren't needed (and would cause problems) in this view
		if (eregi("^SELECT",$sqlQuery) AND ($displayType != "Browse"))
		{
			$sqlQuery = eregi_replace(" FROM $tableRefs",", orig_record FROM $tableRefs",$sqlQuery); // add 'orig_record' column (which is required in order to present visual feedback on duplicate records)
			$sqlQuery = eregi_replace(" FROM $tableRefs",", serial FROM $tableRefs",$sqlQuery); // add 'serial' column (which is required in order to obtain unique checkbox names)

			if ($showLinks == "1")
				$sqlQuery = eregi_replace(" FROM $tableRefs",", file, url, doi, isbn, type FROM $tableRefs",$sqlQuery); // add 'file', 'url', 'doi', 'isbn' & 'type' columns
		}

		// fix escape sequences within the SQL query:
		$query = stripSlashesIfMagicQuotes($sqlQuery);

		return $query;
	}

	// --------------------------------------------------------------------

	// Removes the field given in '$field' from the SQL query and
	// issues a warning if '$issueWarning == true':
	// TODO: I18n
	function stripFieldFromSQLQuery($sqlQuery, $field, $issueWarning = true)
	{
		// note that, upon multiple warnings, only the last warning message will be displayed

		// if the given '$field' is part of the SELECT or ORDER BY statement...
		if (eregi("(SELECT |ORDER BY |, *)" . $field, $sqlQuery))
		{
			// if the 'SELECT' clause contains '$field':
			if ($issueWarning AND (preg_match("/SELECT(.(?!FROM))+?" . $field . "/i", $sqlQuery)))
			{
				// return an appropriate error message:
				// note: we don't write out any error message if the given '$field' does only occur within the 'ORDER' clause (but not within the 'SELECT' clause)
				$HeaderString = returnMsg("Display of '" . $field . "' field was omitted!", "warning", "strong", "HeaderString");
			}

			$sqlQuery = eregi_replace("(SELECT|ORDER BY) " . $field . "( DESC)?", "\\1 ", $sqlQuery); // ...delete '$field' from beginning of 'SELECT' or 'ORDER BY' clause
			$sqlQuery = eregi_replace(", *" . $field . "( DESC)?", "", $sqlQuery); // ...delete any other occurrences of '$field' from 'SELECT' or 'ORDER BY' clause
			$sqlQuery = eregi_replace("(SELECT|ORDER BY) *, *", "\\1 ", $sqlQuery); // ...remove any field delimiters that directly follow the 'SELECT' or 'ORDER BY' terms

			$sqlQuery = preg_replace("/SELECT *(?=FROM)/i", buildSELECTclause("", "", "", false, false) . " ", $sqlQuery); // ...supply generic 'SELECT' clause if it did ONLY contain the given '$field'
			$sqlQuery = preg_replace("/ORDER BY *(?=LIMIT|GROUP BY|HAVING|PROCEDURE|FOR UPDATE|LOCK IN|$)/i", "ORDER BY author, year DESC, publication", $sqlQuery); // ...supply generic 'ORDER BY' clause if it did ONLY contain the given '$field'
		}

		// if the given '$field' is part of the WHERE clause...
		if (eregi("WHERE.+" . $field, $sqlQuery)) // this simple pattern works since we have already stripped any instance(s) of the given '$field' from the ORDER BY clause
		{
			// Note: in the patterns below we'll attempt to account for parentheses but this won't catch all cases!
			$sqlQuery = preg_replace("/WHERE( *\( *?)* *" . $field . ".+?(?= (AND|OR)\b| ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$)/i", "WHERE\\1", $sqlQuery); // ...delete '$field' from 'WHERE' clause
			$sqlQuery = preg_replace("/( *\( *?)*( *(AND|OR)\b)? *" . $field . ".+?(?=( *\) *?)* +((AND|OR)\b|ORDER BY|LIMIT|GROUP BY|HAVING|PROCEDURE|FOR UPDATE|LOCK IN|$))/i", "\\1", $sqlQuery); // ...delete '$field' from 'WHERE' clause
			$sqlQuery = preg_replace("/WHERE( *\( *?)* *(AND|OR)\b/i","WHERE\\1",$sqlQuery); // ...delete any superfluous 'AND' that wasn't removed properly by the two regex patterns above
			$sqlQuery = preg_replace("/WHERE( *\( *?)*(?= ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$)/i", "WHERE serial RLIKE \".+\"", $sqlQuery); // ...supply generic 'WHERE' clause if it did ONLY contain the given '$field'

			if ($issueWarning)
			{
				// return an appropriate error message:
				$HeaderString = returnMsg("Querying of '" . $field . "' field was omitted!", "warning", "strong", "HeaderString");
			}
		}

		return $sqlQuery;
	}

	// --------------------------------------------------------------------

	// this function uses 'mysql_real_escape_string()' to:
	// - prepend backslashes to \, ', "
	// - replace the characters \x00, \n, \r, and \x1a with a MySQL acceptable representation
	//   for queries (e.g., the newline character is replaced with the litteral string '\n')
	function escapeSQL($sourceString)
	{
		$sourceString = mysql_real_escape_string($sourceString);

		return $sourceString;
	}

	// --------------------------------------------------------------------

	// generate a UNIX date/time stamp (integer) from a MySQL-formatted date (YYYY-MM-DD)
	// and time (HH:MM:SS) (or the current date/time if no specific date/time was given):
	function generateUNIXTimeStamp($date = "", $time = "")
	{
		if (!empty($date))
			$dateArray = split("-", $date); // split MySQL-formatted date string (e.g. "2004-09-27") into its pieces (year, month, day)
		else
			$dateArray = array(date('Y'), date('m'), date('d')); // use current year, month & day

		if (!empty($time))
			$timeArray = split(":", $time); // split MySQL-formatted time string (e.g. "23:58:23") into its pieces (hours, minutes, seconds)
		else
			$timeArray = array(date('H'), date('i'), date('s')); // use current hour, minute & second

		// return the Unix timestamp corresponding to the arguments given; the timestamp is a long integer
		// containing the number of seconds between the Unix Epoch (January 1 1970 00:00:00 GMT) and the time specified:
		$timeStamp = mktime($timeArray[0], $timeArray[1], $timeArray[2], $dateArray[1], $dateArray[2], $dateArray[0]);

		return $timeStamp;
	}

	// --------------------------------------------------------------------

	// generate an ISO date/time stamp (string) according to ISO-8601,
	// the international standard for date and time representations:
	// (ISO-8601 date/time example: "2008-01-11T18:30:21+0100";
	//  more info: <http://en.wikipedia.org/wiki/ISO_8601>
	//             <http://www.cl.cam.ac.uk/~mgk25/iso-time.html>)
	function generateISO8601TimeStamp($date = "", $time = "")
	{
		$timeStamp = generateUNIXTimeStamp($date, $time);

		$iso8601date = date('Y-m-d\TH:i:s', $timeStamp); // PHP 4+5
		// for PHP4 support, we manually insert a colon in the TZ designation:
		$timezone = date("O", $timeStamp); // get timezone
		$iso8601date .= substr($timezone, 0, -2) . ":" . substr($timezone, -2, 2); // append timezone

//		$iso8601date = date('c', $timeStamp); // PHP 5

		return $iso8601date;
	}

	// --------------------------------------------------------------------

	// generate a RFC-2822 formatted date/time stamp (string):
	// (RFC-2822 date/time example: "Fri, 11 Jan 2008 18:30:21 +0100")
	function generateRFC2822TimeStamp($date = "", $time = "")
	{
		$timeStamp = generateUNIXTimeStamp($date, $time);

		$rfc2822date = date('r', $timeStamp);

		return $rfc2822date;
	}

	// --------------------------------------------------------------------

	// generate an email address from MySQL 'created_by' fields that conforms
	// to the RFC-2822 specifications (<http://www.faqs.org/rfcs/rfc2822.html>):
	function generateRFC2822EmailAddress($createdBy)
	{
		// Note that the following patterns don't attempt to do fancy parsing of email addresses but simply assume the string format
		// of the 'created_by' field (table 'refs'). If you change the string format, you must modify these patterns as well!
		$authorName = preg_replace("/(.+?)\([^)]+\)/", "\\1", $createdBy);
		$authorEmail = preg_replace("/.+?\(([^)]+)\)/", "\\1", $createdBy);

		$rfc2822address = encodeHTMLspecialchars($authorName . "<" . $authorEmail . ">");

		return $rfc2822address;
	}

	// --------------------------------------------------------------------

	// Takes a SQL query and tries to describe it in natural language:
	// (Note that, currently, this function doesn't attempt to cover all kinds of SQL queries [which would be a task by its own!]
	//  but rather sticks to what is needed in the context of refbase: I.e., right now, only the 'WHERE' clause will be translated)
	function explainSQLQuery($sourceSQLQuery)
	{
		// fix escape sequences within the SQL query:
		$translatedSQL = stripSlashesIfMagicQuotes($sourceSQLQuery);
//		$translatedSQL = str_replace('\"','"',$sourceSQLQuery); // replace any \" with "
//		$translatedSQL = ereg_replace('(\\\\)+','\\\\',$translatedSQL);

		// define an array of search & replace actions:
		// (Note that the order of array elements IS important since it defines when a search/replace action gets executed)
		$sqlSearchReplacePatterns = array(" != "                           =>  " is not equal to ",
		                                  " = "                            =>  " is equal to ",
		                                  " > "                            =>  " is greater than ",
		                                  " >= "                           =>  " is equal to or greater than ",
		                                  " < "                            =>  " is less than ",
		                                  " <= "                           =>  " is equal to or less than ",
		                                  "NOT RLIKE \"\\^([^\"]+?)\\$\""  =>  "is not equal to '\\1'",
		                                  "NOT RLIKE \"\\^"                =>  "does not start with '",
		                                  "NOT RLIKE \"([^\"]+?)\\$\""     =>  "does not end with '\\1'",
		                                  "NOT RLIKE"                      =>  "does not contain",
		                                  "RLIKE \"\\^([^\"]+?)\\$\""      =>  "is equal to '\\1'",
		                                  "RLIKE \"\\^"                    =>  "starts with '",
		                                  "RLIKE \"([^\"]+?)\\$\""         =>  "ends with '\\1'",
		                                  "RLIKE"                          =>  "contains",
		                                  "AND"                            =>  "and");

		// Perform search & replace actions on the SQL query:
		$translatedSQL = searchReplaceText($sqlSearchReplacePatterns, $translatedSQL, false);

		$translatedSQL = str_replace('"',"'",$translatedSQL); // replace any remaining " with '

		return $translatedSQL;
	}

	// --------------------------------------------------------------------

	// Extract the 'SELECT' clause from an SQL query:
	function extractSELECTclause($query)
	{
		$querySELECTclause = preg_replace("/^.*?SELECT (.+?) FROM .*?$/i", "\\1", $query);

		return $querySELECTclause;
	}

	// --------------------------------------------------------------------

	// Extract the 'WHERE' clause from an SQL query:
	function extractWHEREclause($query)
	{
		// Note: we include the SQL commands SELECT/INSERT/UPDATE/DELETE/CREATE/ALTER/DROP/FILE in an attempt to sanitize a given WHERE clause from SQL injection attacks
		$queryWHEREclause = preg_replace("/^.*? WHERE (.+?)(?= ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|[ ;]+(SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|FILE)\b|$).*?$/i", "\\1", $query);

		return $queryWHEREclause;
	}

	// --------------------------------------------------------------------

	// Extract the 'ORDER BY' clause from an SQL query:
	function extractORDERBYclause($query)
	{
		// Note: we include the SQL commands SELECT/INSERT/UPDATE/DELETE/CREATE/ALTER/DROP/FILE in an attempt to sanitize a given ORDER BY clause from SQL injection attacks
		$queryORDERBYclause = preg_replace("/^.*? ORDER BY (.+?)(?= LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|[ ;]+(SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|FILE)\b|$).*?$/i", "\\1", $query);

		return $queryORDERBYclause;
	}

	// --------------------------------------------------------------------

	// This function walks a '$searchArray' and appends its items to the WHERE clause:
	// (the array hierarchy will be maintained, i.e. if the '_query' item is itself
	//  an array of query items these sub-items will get properly nested in parentheses)
	// Example '$searchArray':
	//   Array
	//   (
	//       [0] => Array
	//           (
	//               [_boolean] => 
	//               [_query] => location RLIKE "user@refbase.net"
	//           )
	//       [1] => Array
	//           (
	//               [_boolean] => AND
	//               [_query] => Array
	//                        (
	//                            [0] => Array
	//                                (
	//                                    [_boolean] => OR
	//                                    [_query] => author RLIKE "steffens"
	//                                )
	//                            [1] => Array
	//                                (
	//                                    [_boolean] => OR
	//                                    [_query] => title RLIKE "refbase"
	//                                )
	//                            [2] => Array
	//                                (
	//                                    [_boolean] => OR
	//                                    [_query] => keywords RLIKE "refbase"
	//                                )
	//                        )
	//           )
	//   )
	function appendToWhereClause($searchArray)
	{
		global $query;

		foreach ($searchArray as $searchArrayItem)
		{
			if (!ereg("\($", $query)) // add whitespace & any given boolean search operator if this item isn't the first one within a sub-array of query items
			{
				$query .= " ";

				if (!empty($searchArrayItem["_boolean"]))
					$query .= $searchArrayItem["_boolean"] . " ";
			}

			if (is_array($searchArrayItem["_query"])) // recursively parse any sub-arrays of query items and nest them in parentheses 
			{
				$query .= "("; // NOTE: the parentheses must be on their own code lines to allow for correct recursion
				$query .= appendToWhereClause($searchArrayItem["_query"]);
				$query .= ")";
			}
			else
			{
				$query .= $searchArrayItem["_query"];
			}
		}
	}

	// -------------------------------------------------------------------------------------------------------------------

	// Generate an URL pointing to a RSS feed or any of the supported export/citation formats for the given query:
	// '$urlType' must be one of these: - RSS XML
	//                                  - export formats:   ADS, BibTeX, Endnote, ISI, RIS, Atom XML, MODS XML, OAI_DC XML, ODF XML, SRW_DC XML, SRW_MODS XML, Word XML
	//                                  - citation formats: RTF, PDF, LaTeX, Markdown, ASCII, LaTeX .bbl
	//                                  - default format:   html (session variable 'userDefaultView' specifies the default display type)
	function generateURL($baseURL, $urlType, $queryParametersArray, $encodeAmpersands = false, $showRows = 0, $rowOffset = 0, $citeStyle = "", $citeOrder = "")
	{
		global $defaultCiteStyle; // defined in 'ini.inc.php'

		// NOTE: This code block is a hack that fixes an inconsistency in the refbase API, where "RSS XML" is generated by 'rss.php'
		//       while all other formats are available via 'show.php'. Eventually, "RSS XML" should be also made available as proper
		//       export format so that it can be generated via 'show.php' URLs.
		if (($baseURL == "show.php") AND ($urlType == "RSS XML"))
			$baseURL = "rss.php";

		if (empty($urlType))
			$urlType = "html";

		// NOTE: The record offset ('$rowOffset') as well as the number of records to be returned ('$showRows') will only work for "html"
		//       output, any of the citation formats or the export formats "Atom XML", "SRW_DC XML" and "SRW_MODS XML" - the other export formats will
		//       currently always export the entire result set. Also, 'rss.php' supports '$showRows', but not '$rowOffset', '$citeStyle' or '$citeOrder'.
		if (!empty($rowOffset))
		{
			if (eregi("^((opensearch|sru|show|rss)\.php)$", $baseURL))
				$queryParametersArray["startRecord"] = ($rowOffset + 1);
			else
				$queryParametersArray["rowOffset"] = $rowOffset;
		}

		if (!empty($showRows))
		{
			if (eregi("^((opensearch|sru)\.php)$", $baseURL))
				$queryParametersArray["maximumRecords"] = $showRows;
			else
				$queryParametersArray["showRows"] = $showRows;
		}

		// Add parameters required by 'search.php' or the 'show.php' API:
		if (eregi("^((search|show)\.php)$", $baseURL))
		{
			// - all formats:
			if (!empty($citeOrder) AND ($citeOrder != "author")) // 'citeOrder=author' is the default sort order
				$queryParametersArray["citeOrder"] = $citeOrder;

			// - all formats that (may) contain formatted citations:
			if (eregi("^(html|Atom XML|OAI_DC XML|SRW_DC XML|RTF|PDF|LaTeX|Markdown|ASCII|LaTeX \.bbl)$", $urlType))
			{
				if (!empty($citeStyle) AND ($citeStyle != $defaultCiteStyle))
					$queryParametersArray["citeStyle"] = $citeStyle;
			}

			// - export formats:
			if (eregi("^(ADS|BibTeX|Endnote|RIS|ISI|Atom XML|MODS XML|OAI_DC XML|ODF XML|SRW_DC XML|SRW_MODS XML|Word XML)$", $urlType))
			{
				if (!isset($queryParametersArray["exportType"]))
				{
					if (eregi("XML", $urlType))
						$queryParametersArray["exportType"] = "xml";
					else
						$queryParametersArray["exportType"] = "file";
				}

				$queryParametersArray["submit"] = "Export";

				$queryParametersArray["exportFormat"] = $urlType;
			}

			// - citation formats:
			elseif (eregi("^(RTF|PDF|LaTeX|Markdown|ASCII|LaTeX \.bbl)$", $urlType))
			{
				$queryParametersArray["submit"] = "Cite";

				$queryParametersArray["citeType"] = $urlType;
			}
		}

		// Add parameters required by 'opensearch.php':
		elseif ($baseURL == "opensearch.php")
		{
			$queryParametersArray["recordSchema"] = $urlType;
		}

		// Build query URL:
		$queryURL = "";

		if ($encodeAmpersands)
			$ampersandChar = "&amp;"; // we need to encode the ampersand character (that delimits 'param=value' pairs) if the generated URL is to be included in HTML or XML output
		else
			$ampersandChar = "&";

		foreach ($queryParametersArray as $varname => $value)
			$queryURL .= $ampersandChar . $varname . "=" . rawurlencode($value);

		$queryURL = trimTextPattern($queryURL, $ampersandChar, true, false); // remove again ampersand character from beginning of query URL


		return $baseURL . "?" . $queryURL;
	}

	// --------------------------------------------------------------------

	// Generate RSS XML data from a particular result set (upto the limit given in '$showRows'):
	// 
	// TODO: include OpenSearch elements in RSS output
	//       (see examples at <http://www.opensearch.org/Specifications/OpenSearch/1.1#OpenSearch_response_elements>)
	function generateRSS($result, $showRows, $rssChannelDescription)
	{
		global $officialDatabaseName; // these variables are defined in 'ini.inc.php'
		global $databaseBaseURL;
		global $feedbackEmail;
		global $defaultCiteStyle;
		global $contentTypeCharset;
		global $logoImageURL;

		global $transtab_refbase_html; // defined in 'transtab_refbase_html.inc.php'

		// Note that we only convert those entities that are supported by XML (by use of the 'encodeHTMLspecialchars()' function).
		// All other higher ASCII chars are left unencoded and valid feed output is only possible if the '$contentTypeCharset' variable is set correctly in 'ini.inc.php'.
		// (The only exception is the item description which will contain HTML tags & entities that were defined by '$transtab_refbase_html' or by the 'reArrangeAuthorContents()' function)

		// Define inline text markup to be used by the 'citeRecord()' function:
		$markupPatternsArray = array("bold-prefix"      => "<b>",
		                             "bold-suffix"      => "</b>",
		                             "italic-prefix"    => "<i>",
		                             "italic-suffix"    => "</i>",
		                             "underline-prefix" => "<u>",
		                             "underline-suffix" => "</u>",
		                             "endash"           => "&#8211;",
		                             "emdash"           => "&#8212;",
		                             "ampersand"        => "&", // ampersands in author contents get encoded in function 'reArrangeAuthorContents()' (since the last param in the 'citeRecord()' function call below is set to 'true')
		                             "double-quote"     => "&quot;",
		                             "single-quote"     => "'",
		                             "less-than"        => "&lt;",
		                             "greater-than"     => "&gt;",
		                             "newline"          => "\n<br>\n"
		                            );

		$currentDateTimeStamp = generateRFC2822TimeStamp(); // get the current date & time (in UNIX/RFC-2822 time stamp format => "date('r')" or "date('D, j M Y H:i:s O')")

		// write RSS header:
		$rssData = "<?xml version=\"1.0\" encoding=\"" . $contentTypeCharset . "\"?>"
		         . "\n<rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">";

		// write channel info:
		$rssData .= "\n\t<channel>"
		          . "\n\t\t<title>" . encodeHTMLspecialchars($officialDatabaseName) . "</title>"
		          . "\n\t\t<link>" . $databaseBaseURL . "</link>"
		          . "\n\t\t<description>" . encodeHTMLspecialchars($rssChannelDescription) . "</description>"
		          . "\n\t\t<language>en</language>"
		          . "\n\t\t<pubDate>" . $currentDateTimeStamp . "</pubDate>"
		          . "\n\t\t<lastBuildDate>" . $currentDateTimeStamp . "</lastBuildDate>"
		          . "\n\t\t<webMaster>" . $feedbackEmail . "</webMaster>";

		// write image data:
		$rssData .=  "\n\n\t\t<image>"
		          . "\n\t\t\t<url>" . $databaseBaseURL . $logoImageURL . "</url>"
		          . "\n\t\t\t<title>" . encodeHTMLspecialchars($officialDatabaseName) . "</title>"
		          . "\n\t\t\t<link>" . $databaseBaseURL . "</link>"
		          . "\n\t\t</image>";

		// fetch results: upto the limit specified in '$showRows', fetch a row into the '$row' array and write out a RSS item:
		for ($rowCounter=0; (($rowCounter < $showRows) && ($row = @ mysql_fetch_array($result))); $rowCounter++)
		{
			$origTitle = $row['title']; // save the original title contents before applying any search & replace actions

			// Perform search & replace actions on the text of the 'title' field:
			// (the array '$transtab_refbase_html' in 'transtab_refbase_html.inc.php' defines which search & replace actions will be employed)
			$row['title'] = searchReplaceText($transtab_refbase_html, $row['title'], true);
			// this will provide for correct rendering of italic, super/sub-script and greek letters in item descriptions (which are enclosed by '<![CDATA[...]]>' to ensure well-formed XML);
			// item titles are still served in raw format, though, since the use of HTML in item titles breaks many news readers

			$citeStyleFile = getStyleFile($defaultCiteStyle); // fetch the name of the citation style file that's associated with the style given in '$defaultCiteStyle' (which, in turn, is defined in 'ini.inc.php')

			// include the found citation style file *once*:
			include_once "cite/" . $citeStyleFile; // instead of 'include_once' we could also use: 'if ($rowCounter == 0) { include "cite/" . $citeStyleFile; }'

			// Generate a proper citation for this record, ordering attributes according to the chosen output style & record type:
			$record = citeRecord($row, $defaultCiteStyle, "", $markupPatternsArray, true); // function 'citeRecord()' is defined in the citation style file given in '$citeStyleFile' (which, in turn, must reside in the 'styles' directory of the refbase root directory)

			// To avoid advertising email adresses in public RSS output, we remove the email address from contents of the 'modified_by' field which
			// get displayed in item descriptions. However, note that email adresses are NOT stripped from contents of the 'created_by' field
			// since a valid RSS feed must include an email address in the '<author>' element.
			// The following pattern does not attempt to do fancy parsing of email addresses but simply assumes the string format
			// of the 'modified_by' field (table 'refs'). If you change the string format, you must modify this pattern as well!
			$editorName = preg_replace("/(.+?) \([^)]+\)/", "\\1", $row['modified_by']);

			// append a RSS item for the current record:
			$rssData .= "\n\n\t\t<item>"

			          . "\n\t\t\t<title>" . encodeHTMLspecialchars($origTitle) . "</title>" // we avoid embedding HTML in the item title and use the raw title instead

			          . "\n\t\t\t<link>" . $databaseBaseURL . "show.php?record=" . $row['serial'] . "</link>"

			          . "\n\t\t\t<description><![CDATA[" . $record

			          . "\n\t\t\t<br><br>Edited by " . encodeHTMLspecialchars($editorName) . " on " . generateRFC2822TimeStamp($row['modified_date'], $row['modified_time']) . ".]]></description>"

			          . "\n\t\t\t<guid isPermaLink=\"true\">" . $databaseBaseURL . "show.php?record=" . $row['serial'] . "</guid>"

			          . "\n\t\t\t<pubDate>" . generateRFC2822TimeStamp($row['created_date'], $row['created_time']) . "</pubDate>"

			          . "\n\t\t\t<author>" . generateRFC2822EmailAddress($row['created_by']) . "</author>"

			          . "\n\t\t</item>";
		}

		// finish RSS data:
		$rssData .= "\n\n\t</channel>"
		          . "\n</rss>\n";

		return $rssData;
	}

	// --------------------------------------------------------------------

	// Create new table with parsed table data
	// this function will create a new table with separate rows for all sub-items (which are
	// delimited by '$delim') from the given '$field' (from table 'refs' or 'user_data').
	// This is done to support the Browse view feature for fields that contain a string of
	// multiple values separated by a delimiter.
	// (for each of the multi-item fields this function is executed only once by 'update.php',
	// thereafter 'modify.php' will keep these 'ref_...' tables in sync with the main tables)
	function createNewTableWithParsedTableData($fieldName, $delim)
	{
		global $loginUserID; // saved as session variable on login
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		if (ereg("^(user_keys|user_notes|user_file|user_groups)$", $fieldName)) // for all user-specific fields that can contain multiple items (we ignore the 'related' field here since it won't get used for Browse view)
		{
			$query = "SELECT $fieldName, record_id, user_id FROM $tableUserData"; // WHERE user_id = " . $loginUserID
			$userIDTableSpec = "ref_user_id MEDIUMINT UNSIGNED NOT NULL, ";
		}
		else
		{
			$query = "SELECT $fieldName, serial FROM $tableRefs";
			$userIDTableSpec = "";
		}

		$result = queryMySQLDatabase($query);

		$fieldValuesArray = array(); // initialize array variable which will hold the splitted sub-items

		// split field values on the given delimiter:
		for ($i=0; $row = @ mysql_fetch_array($result); $i++)
		{
			$fieldSubValuesArray = split($delim, $row[$fieldName]); // split field contents on '$delim'
			foreach ($fieldSubValuesArray as $fieldSubValue)
			{
//				// NOTE: we include empty values so that any Browse view query will also display the number of records where the given field is empty
//				if (!empty($fieldSubValue))
//				{
					$fieldSubValue = trim($fieldSubValue);

					if ($fieldName == "author")
						$fieldSubValue = trimTextPattern($fieldSubValue, " *\(eds?\)", false, true); // remove any existing editor info from the 'author' string, i.e., kill any trailing " (ed)" or " (eds)"

					// copy the individual item (as string, ready for database insertion) to the array:
					if (ereg("^(user_keys|user_notes|user_file|user_groups)$", $fieldName))
						$fieldValuesArray[] = "(NULL, \"". addslashes($fieldSubValue) . "\", $row[record_id], $row[user_id])";
					else
						$fieldValuesArray[] = "(NULL, \"". addslashes($fieldSubValue) . "\", $row[serial])";
//				}
			}
		}

		// build correct 'ref_...' table and field names:
		list($tableName, $fieldName) = buildRefTableAndFieldNames($fieldName);

		// NOTE: the below query will only work if the current MySQL user is allowed to CREATE tables ('Create_priv = Y')
		//       therefore, the CREATE statements should be moved to 'update.sql'!
		$queryArray[] = "CREATE TABLE " . $tableName . " ("
		              . $fieldName . "_id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, "
		              . $fieldName . " VARCHAR(255), "
		              . "ref_id MEDIUMINT UNSIGNED NOT NULL, "
		              . $userIDTableSpec
		              . "INDEX (" . $fieldName . "_id, " . $fieldName . ", ref_id))";

		// TODO: Sanitize with quote_smart
		foreach ($fieldValuesArray as $fieldValue)
			$queryArray[] = "INSERT INTO " . $tableName . " VALUES " . $fieldValue;

// inserting all values at once may cause 'URL too long' server errors:
//		$fieldValuesString = implode(", ", $fieldValuesArray); // merge array
//		$queryArray[] = "INSERT INTO " . $tableName . " VALUES " . $fieldValuesString;

		// RUN the queries on the database through the connection:
		foreach($queryArray as $query)
			$result = queryMySQLDatabase($query);

		return $tableName;
	}

	// --------------------------------------------------------------------

	// Build correct 'ref_...' table and field names:
	function buildRefTableAndFieldNames($fieldName)
	{
		if ($fieldName == "address")
		{
			$tableName = "ref_addresses";
			$fieldName = "ref_address";
		}
		elseif (!eregi("s$", $fieldName)) // field name does NOT end with an 's' (such as in 'author')
		{
			$tableName = "ref_" . $fieldName . "s"; // e.g. 'ref_authors'
			$fieldName = "ref_" . $fieldName; // e.g. 'ref_author'
		}
		else // field name ends with an 's' (such as in 'keywords')
		{
			$tableName = "ref_" . $fieldName; // e.g. 'ref_keywords'
			$fieldName = "ref_" . preg_replace("/^(\w+)s$/i", "\\1", $fieldName); // strip 's' from end of field name -> produces e.g. 'ref_keyword'
		}

		return array($tableName, $fieldName);
	}

	// --------------------------------------------------------------------
?>
