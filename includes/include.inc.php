<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./includes/include.inc.php
	// Created:    16-Apr-02, 10:54
	// Modified:   20-Nov-06, 20:15

	// This file contains important
	// functions that are shared
	// between all scripts.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'initialize/ini.inc.php'; // include common variables

	// include transliteration tables:
	include 'includes/transtab_unicode_ascii.inc.php'; // include unicode -> ascii transliteration table
	include 'includes/transtab_latin1_ascii.inc.php'; // include latin1 -> ascii transliteration table

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
//		global $referer;

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
		}

//		if (isset($_SESSION['referer']))
//			$referer = $_SESSION['referer'];
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
	function connectToMySQLDatabase($oldQuery)
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
					showErrorMsg("The following error occurred while trying to connect to the host:", $oldQuery);

			// (2) Set the connection character set (if connected to MySQL 4.1.x or greater):
			//     more info at <http://dev.mysql.com/doc/refman/5.1/en/charset-connection.html>
			if (isset($_SESSION['mysqlVersion']) AND ereg("^(4\.1|5)", $_SESSION['mysqlVersion']))
			{
				if ($contentTypeCharset == "UTF-8")
					queryMySQLDatabase("SET NAMES utf8", ""); // set the character set for this connection to 'utf8'
				else
					queryMySQLDatabase("SET NAMES latin1", ""); // by default, we establish a 'latin1' connection
			}

			// (3) SELECT the database:
			//      (variables are set by include file 'db.inc.php'!)
			if (!(mysql_select_db($databaseName, $connection)))
				if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
					showErrorMsg("The following error occurred while trying to connect to the database:", $oldQuery);
		}
	}

	// --------------------------------------------------------------------

	// Query the MySQL database:
	function queryMySQLDatabase($query, $oldQuery)
	{
		global $connection;
		global $client;

		// (3) RUN the query on the database through the connection:
		if (!($result = @ mysql_query ($query, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			{
				if (eregi("^cli", $client)) // if the query originated from a command line client such as the "refbase" CLI client ("cli-refbase-1.0")
					showErrorMsg("Your query:\n\n" . $query . "\n\ncaused the following error:", $oldQuery);
				else
					showErrorMsg("Your query:\n<br>\n<br>\n<code>" . $query . "</code>\n<br>\n<br>\n caused the following error:", $oldQuery);
			}

		return $result;
	}

	// --------------------------------------------------------------------

	// Disconnect from the MySQL database:
	function disconnectFromMySQLDatabase($oldQuery)
	{
		global $connection;

		if (isset($connection))
			// (5) CLOSE the database connection:
			if (!(mysql_close($connection)))
				if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
					showErrorMsg("The following error occurred while trying to disconnect from the database:", $oldQuery);
	}

	// --------------------------------------------------------------------

	// Get MySQL version:
	function getMySQLversion()
	{
		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		$query = "SELECT VERSION()";

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection

		$row = mysql_fetch_row($result); // fetch the current row into the array $row (it'll be always *one* row, but anyhow)
		$mysqlVersionString = $row[0]; // extract the contents of the first (and only) row (returned version string will be something like "4.0.20-standard" etc.)
		$mysqlVersion = preg_replace("/^(\d+\.\d+).+/", "\\1", $mysqlVersionString); // extract main version number (e.g. "4.0") from version string

		return $mysqlVersion;
	}

	// --------------------------------------------------------------------

	// Find out how many rows are available and (if there were rows found) seek to the current offset:
	// Note that this function will also (re-)assign values to the variables '$rowOffset', '$showRows',
	// '$rowsFound', '$previousOffset', '$nextOffset' and '$showMaxRow'.
	function seekInMySQLResultsToOffset($result, $rowOffset, $showRows, $displayType, $citeType)
	{
		global $defaultNumberOfRecords; // defined in 'ini.inc.php'

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
				$showRows = $defaultNumberOfRecords;

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
	function showErrorMsg($headerMsg, $oldQuery)
	{
		global $client;

		$errorNo = mysql_errno();
		$errorMsg = mysql_error();

		if (eregi("^cli", $client)) // if the query originated from a command line client such as the "refbase" CLI client ("cli-refbase-1.0")
			echo $headerMsg . "\n\nError " . $errorNo . ": " . $errorMsg . "\n\n";
		else
			header("Location: error.php?errorNo=" . $errorNo . "&errorMsg=" . rawurlencode($errorMsg) . "&headerMsg=" . rawurlencode($headerMsg) . "&oldQuery=" . rawurlencode($oldQuery));

		exit;
	}

	// --------------------------------------------------------------------

	// Show whether the user is logged in or not:
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


		// Read session variables:
		if (isset($_SESSION['loginUserID']))
			$loginUserID = $_SESSION['loginUserID'];


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
		global $oldQuery;

		global $errorNo;
		global $errorMsg;

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
		if (ereg(".*(index|install|update|simple_search|advanced_search|sql_search|library_search|extract|users|user_details|user_receipt)\.php", $_SERVER["SCRIPT_NAME"]))
			$referer = $_SERVER["SCRIPT_NAME"]; // we don't need to provide any parameters if the user clicked login/logout on the main page, the install/update page or any of the search pages (we just need
												// to re-locate back to these pages after successful login/logout). Logout on 'install.php', 'users.php', 'user_details.php' or 'user_receipt.php' will redirect to 'index.php'.

		elseif (ereg(".*(record|receipt)\.php", $_SERVER["SCRIPT_NAME"]))
			$referer = $_SERVER["SCRIPT_NAME"] . "?" . "recordAction=" . $recordAction . "&serialNo=" . $serialNo . "&headerMsg=" . rawurlencode($headerMsg) . "&oldQuery=" . rawurlencode($oldQuery);

		elseif (ereg(".*error\.php", $_SERVER["SCRIPT_NAME"]))
			$referer = $_SERVER["SCRIPT_NAME"] . "?" . "errorNo=" . $errorNo . "&errorMsg=" . rawurlencode($errorMsg) . "&headerMsg=" . rawurlencode($headerMsg) . "&oldQuery=" . rawurlencode($oldQuery);

		else
			$referer = $_SERVER["SCRIPT_NAME"] . "?" . "formType=" . "sqlSearch" . "&submit=" . $displayType . "&headerMsg=" . rawurlencode($headerMsg) . "&sqlQuery=" . $queryURL . "&showQuery=" . $showQuery . "&showLinks=" . $showLinks . "&showRows=" . $showRows . "&rowOffset=" . $rowOffset . $recordSerialsString . "&citeStyleSelector=" . rawurlencode($citeStyle) . "&citeOrder=" . $citeOrder . "&orderBy=" . rawurlencode($orderBy) . "&oldQuery=" . rawurlencode($oldQuery);
		// --- END WORKAROUND -----

		// Is the user logged in?
		if (isset($_SESSION['loginEmail']))
			{
				$loginWelcomeMsg = "Welcome<br><em>" . encodeHTML($loginFirstName) . " " . encodeHTML($loginLastName) . "</em>!";

				if ($loginEmail == $adminLoginEmail)
					$loginStatus = "You're logged in as<br><span class=\"warning\">Admin</span> (<em>" . $loginEmail . "</em>)";
				else
					$loginStatus = "You're logged in as<br><em>" . $loginEmail . "</em>";

				$loginLinks = "";
				if ($loginEmail == $adminLoginEmail) // if the admin is logged in, add the 'Add User' & 'Manage Users' links:
				{
					$loginLinks .= "<a href=\"user_details.php\" title=\"add a user to the database\">Add User</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
					$loginLinks .= "<a href=\"users.php\" title=\"manage user data\">Manage Users</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
				}
				else // if a normal user is logged in, we add the 'My Refs' and 'Options' links instead:
				{
					$loginLinks .= "<a href=\"search.php?formType=myRefsSearch&amp;showQuery=0&amp;showLinks=1&amp;myRefsRadio=1\" title=\"display all of your records\">My Refs</a>&nbsp;&nbsp;|&nbsp;&nbsp;";

					if (isset($_SESSION['user_permissions']) AND ereg("allow_modify_options", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_modify_options'...
					// ... include a link to 'user_receipt.php':
						$loginLinks .= "<a href=\"user_receipt.php?userID=" . $loginUserID . "\" title=\"view and modify your account details and options\">Options</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
				}
				$loginLinks .= "<a href=\"user_logout.php?referer=" . rawurlencode($referer) . "\" title=\"logout from the database\">Logout</a>";
			}
		else
			{
				$loginWelcomeMsg = "";

				if (ereg(".*(record|import[^.]*)\.php", $_SERVER["SCRIPT_NAME"]))
					$loginStatus = "<span class=\"warning\">You must be logged in<br>to submit this form!</span>";
				else
					$loginStatus = "";

				$loginLinks = "<a href=\"user_login.php?referer=" . rawurlencode($referer) . "\" title=\"login to the database\">Login</a>";
			}

		// Write back session variables:
		saveSessionVariable("loginUserID", $loginUserID);
		saveSessionVariable("loginStatus", $loginStatus);
		saveSessionVariable("loginLinks", $loginLinks);

		// Although the '$referer' variable gets included as GET parameter above, we'll also save the variable as session variable:
		// (this should help re-directing to the correct page if a user called 'user_login/logout.php' manually, i.e., without parameters)
		saveSessionVariable("referer", $referer);
	}

	// --------------------------------------------------------------------

	// Get the 'user_id' for the record entry in table 'auth' whose email matches that in '$emailAddress':
	function getUserID($emailAddress)
	{
		global $tableAuth; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		$query = "SELECT user_id FROM $tableAuth WHERE email = " . quote_smart($emailAddress);

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection
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
	//                                         [prefix_call_number] => "true" // if "true", any 'call_number' string will be prefixed with the correct call number prefix of the currently logged-in user (e.g. 'IP÷ @ msteffens @ ')
	//                                       )
	//               )
	function addRecords($importDataArray)
	{
		global $oldQuery;
		global $tableRefs; // defined in 'db.inc.php'

		global $connection;

		if (!isset($oldQuery))
			$oldQuery = "";

		connectToMySQLDatabase($oldQuery);

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

					// the 'location' field is handled below

					if (!empty($recordData['call_number']))
						$queryRefs .= "call_number = " . quote_smart($recordData['call_number']) . ", ";

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
						// for the 'location' field, we accept input from the '$recordData',
						// but if no data were given, we'll add the currently logged-in user to the 'location' field:
						if (!empty($recordData['location']))
							$queryRefs .= "location = " . quote_smart($recordData['location']) . ", ";
						elseif (isset($_SESSION['loginEmail']))
							$queryRefs .= "location = " . quote_smart($currentUser) . ", ";

						// if the 'prefix_call_number' option is set to "true", any 'call_number' string will be prefixed with
						// the correct call number prefix of the currently logged-in user (e.g. 'IP÷ @ msteffens @ '):
						//
						// TODO: Sanitize this using quote_smart
						if ((isset($_SESSION['loginEmail'])) AND (isset($importDataArray['options']['prefix_call_number'])) AND ($importDataArray['options']['prefix_call_number'] == "true"))
						{
							if (empty($recordData['call_number'])) // similar to the GUI behaviour, we'll also add a call number prefix if the 'call_number' field is empty
								$queryRefs .= "call_number = \"\", ";

							$callNumberPrefix = getCallNumberPrefix(); // build a correct call number prefix for the currently logged-in user (e.g. 'IP÷ @ msteffens')

							$queryRefs = preg_replace("/(call_number = \")/", "\\1" . $callNumberPrefix . " @ ", $queryRefs); // add call number prefix to 'call_number' field
						}

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
						$result = queryMySQLDatabase($queryRefs, $oldQuery);

						// Get the record id that was created
						$serialNo = @ mysql_insert_id($connection); // find out the unique ID number of the newly created record (Note: this function should be called immediately after the
																	// SQL INSERT statement! After any subsequent query it won't be possible to retrieve the auto_increment identifier value for THIS record!)

						$serialNumbersArray[] = $serialNo; // append this record's serial number to the array of imported record serials
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
			//  13. output: if the number of authors is greater than the given number (integer >= 1), only the first author will be included along with the string given in (14); keep empty if all authors shall be returned
			//  14. output: string that's appended to the first author if number of authors is greater than the number given in (13); the actual number of authors can be printed by including '__NUMBER_OF_AUTHORS__' (without quotes) within the string
			//
			//  15. output: boolean value that specifies whether the re-ordered string shall be returned with higher ASCII chars HTML encoded
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
												false); // 15.

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

	// BUILD FIELD NAME LINKS
	// (i.e., build clickable column headers for each available column)
	function buildFieldNameLinks($href, $query, $oldQuery, $newORDER, $result, $i, $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, $formType, $submitType, $linkName, $orig_fieldname, $viewType)
	{
		global $loc; // '$loc' is made globally available in 'core.php'
		global $fieldNamesArray; // '$fieldNamesArray' is made globally available from within this function

		if (!isset($fieldNamesArray))
			// map MySQL field names to localized column names:
			$fieldNamesArray = array (
										"author"                => $loc["Author"],
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
										"user_groups"           => $loc["UserGroups"],
										"cite_key"              => $loc["CiteKey"],
										"related"               => $loc["Related"]
			);

		if (empty($orig_fieldname)) // if there's no fixed original fieldname specified (as is the case for all fields but the 'Links' column)
		{
			// Get the meta-data for the attribute
			$info = mysql_fetch_field ($result, $i);
			// Get the attribute name:
			$orig_fieldname = $info->name;
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
			if ($info->numeric == "1") // Check if the field's data type is numeric (if so we'll append " DESC" to the ORDER clause)
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

		// figure out if clicking on the current field name will sort in ascending or descending order:
		// (note that for 1st-level sort attributes, this value will be modified again below)
		if (eregi("ORDER BY [^ ]+ DESC", $newORDER)) // if 1st-level sort is in descending order...
			$linkTitleSortOrder = " (descending order)"; // ...sorting will be conducted in DESCending order
		else
			$linkTitleSortOrder = " (ascending order)"; // ...sorting will be conducted in ASCending order

		// toggle sort order for the 1st-level sort attribute:
		if (preg_match("/ORDER BY $orig_fieldname(?! DESC)/i", $query)) // if 1st-level sort is by this attribute (in ASCending order)...
		{
			$queryURLNewOrder = preg_replace("/(ORDER%20BY%20$orig_fieldname)(?!%20DESC)/i", "\\1%20DESC", $queryURLNewOrder); // ...change sort order to DESCending
			$linkTitleSortOrder = " (descending order)"; // adjust the link title attribute's sort info accordingly
		}
		elseif (preg_match("/ORDER BY $orig_fieldname DESC/i", $query)) // if 1st-level sort is by this attribute (in DESCending order)...
		{
			$queryURLNewOrder = preg_replace("/(ORDER%20BY%20$orig_fieldname)%20DESC/i", "\\1", $queryURLNewOrder); // ...change sort order to ASCending
			$linkTitleSortOrder = " (ascending order)"; // adjust the link title attribute's sort info accordingly
		}

		// build an informative string that get's displayed when a user mouses over a link:
		$linkTitle = "\"sort by field '" . $orig_fieldname . "'" . $linkTitleSortOrder . "\"";

		// start the table header tag & print the attribute name as link:
		$tableHeaderLink = "$HTMLbeforeLink<a href=\"$href?sqlQuery=$queryURLNewOrder&amp;showQuery=$showQuery&amp;showLinks=$showLinks&amp;formType=$formType&amp;showRows=$showRows&amp;rowOffset=$rowOffset&amp;submit=$submitType&amp;orderBy=" . rawurlencode($orderBy) . "&amp;oldQuery=" . rawurlencode($oldQuery) . "&amp;viewType=$viewType\" title=$linkTitle>$linkName</a>";

		// append sort indicator after the 1st-level sort attribute:
		if (preg_match("/ORDER BY $orig_fieldname(?! DESC)(?=,| LIMIT|$)/i", $query)) // if 1st-level sort is by this attribute (in ASCending order)...
			$tableHeaderLink .= "&nbsp;<img src=\"img/sort_asc.gif\" alt=\"(up)\" title=\"sorted by field '" . $orig_fieldname . "' (ascending order)\" width=\"8\" height=\"10\" hspace=\"0\" border=\"0\">"; // ...append an upward arrow image
		elseif (preg_match("/ORDER BY $orig_fieldname DESC/i", $query)) // if 1st-level sort is by this attribute (in DESCending order)...
			$tableHeaderLink .= "&nbsp;<img src=\"img/sort_desc.gif\" alt=\"(down)\" title=\"sorted by field '" . $orig_fieldname . "' (descending order)\" width=\"8\" height=\"10\" hspace=\"0\" border=\"0\">"; // ...append a downward arrow image

		$tableHeaderLink .=  $HTMLafterLink; // append any necessary HTML

		return $tableHeaderLink;
	}

	// --------------------------------------------------------------------

	//	REPLACE ORDER CLAUSE IN SQL QUERY
	function newORDERclause($newORDER, $query)
	{
		// replace any existing ORDER BY clause with the new one given in '$newORDER':
		$queryNewOrder = preg_replace("/ORDER BY .+?(?=LIMIT.*|GROUP BY.*|HAVING.*|PROCEDURE.*|FOR UPDATE.*|LOCK IN.*|$)/i", $newORDER, $query);
		$queryURLNewOrder = rawurlencode($queryNewOrder); // URL encode query

		return $queryURLNewOrder;
	}

	// --------------------------------------------------------------------

	//	BUILD BROWSE LINKS
	// (i.e., build a TABLE row with links for "previous" & "next" browsing, as well as links to intermediate pages)
	function buildBrowseLinks($href, $query, $oldQuery, $NoColumns, $rowsFound, $showQuery, $showLinks, $showRows, $rowOffset, $previousOffset, $nextOffset, $maxPageNo, $formType, $displayType, $citeStyle, $citeOrder, $orderBy, $headerMsg, $viewType)
	{
		global $loc; // '$loc' is made globally available in 'core.php'

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

		// Start a <TABLE>:
		$BrowseLinks = "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds browse links that link to the results pages of your query\">";

		// Start a <TABLE> row:
		$BrowseLinks .= "\n<tr>";

		if ($viewType == "Print")
			$BrowseLinks .= "\n\t<td align=\"left\" valign=\"bottom\" width=\"187\"><a href=\"index.php\" title=\"" . $loc["LinkTitle_Home"] . "\">" . $loc["Home"] . "</a></td>";
		elseif (($href != "help.php" AND $displayType != "Cite") OR ($href == "help.php" AND $displayType == "List"))
		{
			$BrowseLinks .= "\n\t<td align=\"left\" valign=\"bottom\" width=\"187\" class=\"small\">"
							. "\n\t\t<a href=\"JavaScript:checkall(true,'marked%5B%5D')\" title=\"select all records on this page\">" . $loc["SelectAll"] . "</a>&nbsp;&nbsp;&nbsp;"
							. "\n\t\t<a href=\"JavaScript:checkall(false,'marked%5B%5D')\" title=\"deselect all records on this page\">" . $loc["DeselectAll"] . "</a>"
							. "\n\t</td>";
		}
		// we don't show the select/deselect links in citation layout (since there aren't any checkboxes anyhow);
		// similarly, we omit these links on 'help.php' in 'Display' mode:
		else // citation layout
			$BrowseLinks .= "\n\t<td align=\"left\" valign=\"bottom\" width=\"187\">&nbsp;</td>";


		$BrowseLinks .= "\n\t<td align=\"center\" valign=\"bottom\">";

		// a) If there's a page range below the one currently shown,
		// create a "[xx-xx]" link (linking directly to the previous range of pages):
		if ($pageOffset > "0")
			{
				$previousRangeFirstPage = ($pageOffset - $maxPageNo + 1); // calculate the first page of the next page range

				$previousRangeLastPage = ($previousRangeFirstPage + $maxPageNo - 1); // calculate the last page of the next page range

				$BrowseLinks .= "\n\t\t<a href=\"" . $href
					. "?sqlQuery=" . rawurlencode($query)
					. "&amp;submit=$displayType"
					. "&amp;citeStyleSelector=" . rawurlencode($citeStyle)
					. "&amp;citeOrder=$citeOrder"
					. "&amp;orderBy=" . rawurlencode($orderBy)
					. "&amp;headerMsg=" . rawurlencode($headerMsg)
					. "&amp;showQuery=$showQuery"
					. "&amp;showLinks=$showLinks"
					. "&amp;formType=$formType"
					. "&amp;showRows=$showRows"
					. "&amp;rowOffset=" . (($pageOffset - $maxPageNo) * $showRows)
					. "&amp;oldQuery=" . rawurlencode($oldQuery)
					. "&amp;viewType=$viewType"
					. "\" title=\"display results page " . $previousRangeFirstPage . " and links to pages " . $previousRangeFirstPage . "&#8211;" . $previousRangeLastPage . "\">[" . $previousRangeFirstPage . "&#8211;" . $previousRangeLastPage . "] </a>";
			}

		// b) Are there any previous pages?
		if ($rowOffset > 0)
			// Yes, so create a previous link
			$BrowseLinks .= "\n\t\t<a href=\"" . $href
				. "?sqlQuery=" . rawurlencode($query)
				. "&amp;submit=$displayType"
				. "&amp;citeStyleSelector=" . rawurlencode($citeStyle)
				. "&amp;citeOrder=$citeOrder"
				. "&amp;orderBy=" . rawurlencode($orderBy)
				. "&amp;headerMsg=" . rawurlencode($headerMsg)
				. "&amp;showQuery=$showQuery"
				. "&amp;showLinks=$showLinks"
				. "&amp;formType=$formType"
				. "&amp;showRows=$showRows"
				. "&amp;rowOffset=$previousOffset"
				. "&amp;oldQuery=" . rawurlencode($oldQuery)
				. "&amp;viewType=$viewType"
				. "\" title=\"display previous results page\">&lt;&lt;</a>";
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
					$BrowseLinks .= " \n\t\t<a href=\"" . $href
						. "?sqlQuery=" . rawurlencode($query)
						. "&amp;submit=$displayType"
						. "&amp;citeStyleSelector=" . rawurlencode($citeStyle)
						. "&amp;citeOrder=$citeOrder"
						. "&amp;orderBy=" . rawurlencode($orderBy)
						. "&amp;headerMsg=" . rawurlencode($headerMsg)
						. "&amp;showQuery=$showQuery"
						. "&amp;showLinks=$showLinks"
						. "&amp;formType=$formType"
						. "&amp;showRows=$showRows"
						. "&amp;rowOffset=$x"
						. "&amp;oldQuery=" . rawurlencode($oldQuery)
						. "&amp;viewType=$viewType"
						. "\" title=\"display results page $page\">$page</a>";
				else
					// Yes, so don't print a link
					$BrowseLinks .= " \n\t\t<b>$page</b>"; // current page is set in <b>BOLD</b>

		$BrowseLinks .= " ";

		// d) Are there any Next pages?
		if ($rowsFound > $nextOffset)
			// Yes, so create a next link
			$BrowseLinks .= "\n\t\t<a href=\"" . $href
				. "?sqlQuery=" . rawurlencode($query)
				. "&amp;submit=$displayType"
				. "&amp;citeStyleSelector=" . rawurlencode($citeStyle)
				. "&amp;citeOrder=$citeOrder"
				. "&amp;orderBy=" . rawurlencode($orderBy)
				. "&amp;headerMsg=" . rawurlencode($headerMsg)
				. "&amp;showQuery=$showQuery"
				. "&amp;showLinks=$showLinks"
				. "&amp;formType=$formType"
				. "&amp;showRows=$showRows"
				. "&amp;rowOffset=$nextOffset"
				. "&amp;oldQuery=" . rawurlencode($oldQuery)
				. "&amp;viewType=$viewType"
				. "\" title=\"display next results page\">&gt;&gt;</a>";
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

				$BrowseLinks .= "\n\t\t<a href=\"" . $href
					. "?sqlQuery=" . rawurlencode($query)
					. "&amp;submit=$displayType"
					. "&amp;citeStyleSelector=" . rawurlencode($citeStyle)
					. "&amp;citeOrder=$citeOrder"
					. "&amp;orderBy=" . rawurlencode($orderBy)
					. "&amp;headerMsg=" . rawurlencode($headerMsg)
					. "&amp;showQuery=$showQuery"
					. "&amp;showLinks=$showLinks"
					. "&amp;formType=$formType"
					. "&amp;showRows=$showRows"
					. "&amp;rowOffset=" . (($pageOffset + $maxPageNo) * $showRows)
					. "&amp;oldQuery=" . rawurlencode($oldQuery)
					. "&amp;viewType=$viewType"
					. "\" title=\"display results page " . $nextRangeFirstPage . " and links to pages " . $nextRangeFirstPage . "&#8211;" . $nextRangeLastPage . "\"> [" . $nextRangeFirstPage . "&#8211;" . $nextRangeLastPage . "]</a>";
			}

		$BrowseLinks .= "\n\t</td>";

		$BrowseLinks .= "\n\t<td align=\"right\" valign=\"bottom\" width=\"187\">";

		if ($viewType == "Print")
			// f) create a 'Web View' link that will show the currently displayed result set in web view:
			$BrowseLinks .= "\n\t\t<a href=\"" . $href
				. "?sqlQuery=" . rawurlencode($query)
				. "&amp;submit=$displayType"
				. "&amp;citeStyleSelector=" . rawurlencode($citeStyle)
				. "&amp;citeOrder=$citeOrder"
				. "&amp;orderBy=" . rawurlencode($orderBy)
				. "&amp;headerMsg=" . rawurlencode($headerMsg)
				. "&amp;showQuery=$showQuery"
				. "&amp;showLinks=1"
				. "&amp;formType=$formType"
				. "&amp;showRows=$showRows"
				. "&amp;rowOffset=$rowOffset"
				. "&amp;oldQuery=" . rawurlencode($oldQuery)
				. "&amp;viewType=Web"
				. "\"><img src=\"img/web.gif\" alt=\"web\" title=\"back to web view\" width=\"16\" height=\"16\" hspace=\"0\" border=\"0\"></a>";
		else
		{
			if (isset($_SESSION['user_permissions']) AND ereg("allow_print_view", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_print_view'...
				// f) create a 'Print View' link that will show the currently displayed result set in print view:
				$BrowseLinks .= "\n\t\t<a href=\"" . $href
					. "?sqlQuery=" . rawurlencode($query)
					. "&amp;submit=$displayType"
					. "&amp;citeStyleSelector=" . rawurlencode($citeStyle)
					. "&amp;citeOrder=$citeOrder"
					. "&amp;orderBy=" . rawurlencode($orderBy)
					. "&amp;headerMsg=" . rawurlencode($headerMsg)
					. "&amp;showQuery=$showQuery"
					. "&amp;showLinks=0"
					. "&amp;formType=$formType"
					. "&amp;showRows=$showRows"
					. "&amp;rowOffset=$rowOffset"
					. "&amp;oldQuery=" . rawurlencode($oldQuery)
					. "&amp;viewType=Print"
					. "\"><img src=\"img/print.gif\" alt=\"print\" title=\"display print view\" width=\"17\" height=\"18\" hspace=\"0\" border=\"0\"></a>";
		}

		$BrowseLinks .= "\n\t</td>"
						. "\n</tr>"
						. "\n</table>";

		return $BrowseLinks;
	}

	// --------------------------------------------------------------------

	// prepare the previous query stored in '$oldQuery' so that it can be used as active query again:
	function reactivateOldQuery($oldQuery)
	{
		// we'll have to URL encode the sqlQuery part within '$oldQuery' while maintaining the rest unencoded(!):
		$oldQuerySQLPart = preg_replace("/sqlQuery=(.+?)&amp;.+/", "\\1", $oldQuery); // extract the sqlQuery part within '$oldQuery'
		$oldQueryOtherPart = preg_replace("/sqlQuery=.+?(&amp;.+)/", "\\1", $oldQuery); // extract the remaining part after the sqlQuery
		$oldQuerySQLPart = rawurlencode($oldQuerySQLPart); // URL encode sqlQuery part within '$oldQuery'
		$oldQueryPartlyEncoded = "sqlQuery=" . $oldQuerySQLPart . $oldQueryOtherPart; // Finally, we merge everything again

		return $oldQueryPartlyEncoded;
	}

	// --------------------------------------------------------------------

	//	BUILD REFINE SEARCH ELEMENTS
	// (i.e., provide options to refine the search results)
	function buildRefineSearchElements($href, $queryURL, $showQuery, $showLinks, $showRows, $refineSearchSelectorElements1, $refineSearchSelectorElements2, $refineSearchSelectorElementSelected, $displayType)
	{
		// adjust button spacing according to the calling script (which is either 'search.php' or 'users.php')
		if ($href == "users.php")
			$spaceBeforeButton = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"; // I know this is ugly (it's just a quick workaround which should get fixed in the future...)
		else // if ($href == "search.php")
			$spaceBeforeButton = "&nbsp;&nbsp;";

		$refineSearchForm = <<<EOF
		<form action="$href" method="POST" name="refineSearch">
			<input type="hidden" name="formType" value="refineSearch">
			<input type="hidden" name="submit" value="Search">
			<input type="hidden" name="originalDisplayType" value="$displayType">
			<input type="hidden" name="sqlQuery" value="$queryURL">
			<input type="hidden" name="showQuery" value="$showQuery">
			<input type="hidden" name="showLinks" value="$showLinks">
			<input type="hidden" name="showRows" value="$showRows">
			<table align="center" border="0" cellpadding="0" cellspacing="5" summary="This table holds a search form that enables you to refine the previous search result">
				<tr>
					<td valign="top">
						Search within Results:
					</td>
				</tr>
				<tr>
					<td valign="top">
						<select name="refineSearchSelector" title="choose the field you want to search">
EOF;

		$optionTags = buildSelectMenuOptions($refineSearchSelectorElements1, " *, *", "\t\t\t\t\t\t\t", false); // build correct option tags from the column items provided

		if (isset($_SESSION['loginEmail']) AND !empty($refineSearchSelectorElements2)) // if a user is logged in -AND- there were any additional elements specified...
			// ...add these additional elements to the popup menu:
			$optionTags .= buildSelectMenuOptions($refineSearchSelectorElements2, " *, *", "\t\t\t\t\t\t\t", false); // build correct option tags from the column items provided

		$optionTags = ereg_replace("<option>$refineSearchSelectorElementSelected", "<option selected>$refineSearchSelectorElementSelected", $optionTags); // add 'selected' attribute:

		$refineSearchForm .= $optionTags;

		$refineSearchForm .= <<<EOF

						</select>&nbsp;&nbsp;
						<input type="text" name="refineSearchName" size="11" title="enter your search string here">
					</td>
				</tr>
				<tr>
					<td valign="top">
						<input type="checkbox" name="refineSearchExclude" value="1" title="mark this checkbox to exclude all records from the current result set that match the above search criterion">&nbsp;Exclude matches$spaceBeforeButton
						<input type="submit" name="submit" value="Search" title="search within the current result set">
					</td>
				</tr>
			</table>
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
	function buildGroupSearchElements($href, $queryURL, $query, $showQuery, $showLinks, $showRows, $displayType)
	{
		if (preg_match("/.+user_groups RLIKE \"[()|^.;* ]+[^;]+?[()|$.;* ]+\"/i", $query)) // if the query does contain a 'WHERE' clause that searches for a particular user group
			$currentGroup = preg_replace("/.+user_groups RLIKE \"[()|^.;* ]+([^;]+?)[()|$.;* ]+\".*/i", "\\1", $query); // extract the particular group name
		else
			$currentGroup = "none";

		// show the 'Show My Groups' form:
		// - if the admin is logged in and calls 'users.php' (since only the admin will be allowed to call 'users.php', checking '$href' is sufficient here) -OR-
		// - if a user is logged in AND the 'user_permissions' session variable contains 'allow_user_groups'
		if (($href == "users.php") OR (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_user_groups", $_SESSION['user_permissions']))))
		{
			if (($href == "search.php" AND isset($_SESSION['userGroups'])) OR ($href == "users.php" AND isset($_SESSION['adminUserGroups']))) // if the appropriate session variable is set
			{
				$groupSearchDisabled = "";
				$groupSearchSelectorTitle = "choose the group that you want to display";
				$groupSearchButtonTitle = "show all records that belong to the specified group";
			}
			else
			{
				$groupSearchDisabled = " disabled"; // disable the 'Show My Groups' form if the session variable holding the user's groups isnt't available
				$groupSearchSelectorTitle = "(to setup a new group with all selected records, enter a group name at the bottom of this page, then click the 'Add' button)";
				$groupSearchButtonTitle = "(not available since you haven't specified any groups yet)";
			}

			// adjust the form title according to the calling script (which is either 'search.php' or 'users.php')
			if ($href == "search.php")
				$formTitleAddon = " My";
			elseif ($href == "users.php")
				$formTitleAddon = " User";
			else
				$formTitleAddon = ""; // currently, '$href' will be either 'search.php' or 'users.php', but anyhow

			$groupSearchForm = <<<EOF
		<form action="$href" method="POST" name="groupSearch">
			<input type="hidden" name="formType" value="groupSearch">
			<input type="hidden" name="originalDisplayType" value="$displayType">
			<input type="hidden" name="sqlQuery" value="$queryURL">
			<input type="hidden" name="showQuery" value="$showQuery">
			<input type="hidden" name="showLinks" value="$showLinks">
			<input type="hidden" name="showRows" value="$showRows">
			<table align="left" border="0" cellpadding="0" cellspacing="5" summary="This table holds a search form that gives you access to your groups">
				<tr>
					<td valign="top">
						Show$formTitleAddon Group:
					</td>
				</tr>
				<tr>
					<td valign="top">
						<select name="groupSearchSelector" title="$groupSearchSelectorTitle"$groupSearchDisabled>
EOF;

			if (($href == "search.php" AND isset($_SESSION['userGroups'])) OR ($href == "users.php" AND isset($_SESSION['adminUserGroups']))) // if the appropriate session variable is set
			{
				 // build properly formatted <option> tag elements from the items listed in the appropriate session variable:
				if ($href == "search.php")
					$optionTags = buildSelectMenuOptions($_SESSION['userGroups'], " *; *", "\t\t\t\t\t\t\t", false);
				elseif ($href == "users.php")
					$optionTags = buildSelectMenuOptions($_SESSION['adminUserGroups'], " *; *", "\t\t\t\t\t\t\t", false);

				if (!empty($currentGroup)) // if the current SQL query contains a 'WHERE' clause that searches for a particular user group
					$optionTags = ereg_replace("<option>$currentGroup</option>", "<option selected>$currentGroup</option>", $optionTags); // we select that group by adding the 'selected' parameter to the apropriate <option> tag

				$groupSearchForm .= $optionTags;
			}
			else
				$groupSearchForm .= "<option>(no groups available)</option>";

			$groupSearchForm .= <<<EOF

						</select>
					</td>
				</tr>
				<tr>
					<td valign="top">
						<input type="submit" value="Show" title="$groupSearchButtonTitle"$groupSearchDisabled>
					</td>
				</tr>
			</table>
		</form>

EOF;
		}
		else
			$groupSearchForm = "\t\t&nbsp;\n";

		return $groupSearchForm;
	}

	// --------------------------------------------------------------------

	//	BUILD DISPLAY OPTIONS FORM ELEMENTS
	// (i.e., provide options to show/hide columns or change the number of records displayed per page)
	function buildDisplayOptionsElements($href, $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $displayOptionsSelectorElements1, $displayOptionsSelectorElements2, $displayOptionsSelectorElementSelected, $fieldsToDisplay, $displayType)
	{
		if ($displayType == "Browse")
		{
			$submitValue = "Browse";
			$recordsOrItems = "items";
		}
		else
		{
			$submitValue = "Show";
			$recordsOrItems = "records";
		}

		$displayOptionsForm = <<<EOF
		<form action="$href" method="POST" name="displayOptions">
			<input type="hidden" name="formType" value="displayOptions">
			<input type="hidden" name="submit" value="$submitValue">
			<input type="hidden" name="originalDisplayType" value="$displayType">
			<input type="hidden" name="sqlQuery" value="$queryURL">
			<input type="hidden" name="showQuery" value="$showQuery">
			<input type="hidden" name="showLinks" value="$showLinks">
			<input type="hidden" name="rowOffset" value="$rowOffset">
			<input type="hidden" name="showRows" value="$showRows">
			<table align="right" border="0" cellpadding="0" cellspacing="5" summary="This table holds a form that enables you to modify the display of columns and records">
				<tr>
					<td valign="top">
						Display Options:
					</td>
				</tr>
				<tr>
					<td valign="top">
EOF;

		if ($displayType == "Browse")
			$displayOptionsForm .= "\n\t\t\t\t\t\t<select name=\"displayOptionsSelector\" title=\"choose the field you want to browse\">";
		else
			$displayOptionsForm .= "\n\t\t\t\t\t\t<select name=\"displayOptionsSelector\" title=\"choose the field you want to show or hide\">";

		// NOTE: we embed the current value of '$rowOffset' as hidden tag within the 'displayOptions' form. By this, the current row offset can be re-applied after the user pressed the 'Show'/'Hide' button within the 'displayOptions' form.
		//       To avoid that browse links don't behave as expected, the actual value of '$rowOffset' will be adjusted in function 'seekInMySQLResultsToOffset()' to an exact multiple of '$showRows'!

		$optionTags = buildSelectMenuOptions($displayOptionsSelectorElements1, " *, *", "\t\t\t\t\t\t\t", false); // build correct option tags from the column items provided

		if (isset($_SESSION['loginEmail']) AND !empty($displayOptionsSelectorElements2)) // if a user is logged in -AND- there were any additional elements specified...
			// ...add these additional elements to the popup menu:
			$optionTags .= buildSelectMenuOptions($displayOptionsSelectorElements2, " *, *", "\t\t\t\t\t\t\t", false); // build correct option tags from the column items provided

		$optionTags = ereg_replace("<option>$displayOptionsSelectorElementSelected", "<option selected>$displayOptionsSelectorElementSelected", $optionTags); // add 'selected' attribute:

		$displayOptionsForm .= $optionTags;

		if ($fieldsToDisplay < 2)
		{
			$hideButtonDisabled = " disabled"; // disable the 'Hide' button if there's currently only one field being displayed (except the links column)
			$hideButtonTitle = "(only available with two or more fields being displayed!)";
		}
		else
		{
			$hideButtonDisabled = "";
			$hideButtonTitle = "hide the specified field";
		}

			$displayOptionsForm .= "\n\t\t\t\t\t\t</select>&nbsp;";

		if ($displayType == "Browse")
		{
			$displayOptionsForm .= "\n\t\t\t\t\t\t<input type=\"submit\" name=\"submit\" value=\"Browse\" title=\"browse the current result set by the specified field\">&nbsp;";
		}
		else
		{
			$displayOptionsForm .= "\n\t\t\t\t\t\t<input type=\"submit\" name=\"submit\" value=\"Show\" title=\"show the specified field\">&nbsp;";
			$displayOptionsForm .= "\n\t\t\t\t\t\t<input type=\"submit\" name=\"submit\" value=\"Hide\" title=\"$hideButtonTitle\"$hideButtonDisabled>";
		}

		$displayOptionsForm .= <<<EOF

					</td>
				</tr>
				<tr>
					<td valign="top">
						<input type="text" name="showRows" value="$showRows" size="4" title="specify how many $recordsOrItems shall be displayed per page">&nbsp;&nbsp;$recordsOrItems per page
					</td>
				</tr>
			</table>
		</form>

EOF;

		return $displayOptionsForm;
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the "Search within Results" or "Display Options" forms above the query results list (which, in turn, was returned by 'search.php' or 'users.php', respectively):
	function extractFormElementsRefineDisplay($queryTable, $displayType, $originalDisplayType, $query, $showLinks, $userID)
	{
		global $tableRefs, $tableUserData, $tableUsers; // defined in 'db.inc.php'

		// extract form variables:
		if ($displayType == "Search") // the user clicked the 'Search' button of the "Search within Results" form
		{
			$fieldSelector = $_POST['refineSearchSelector']; // extract field name chosen by the user
			$refineSearchName = $_POST['refineSearchName']; // extract search text entered by the user

			if (isset($_POST['refineSearchExclude'])) // extract user option whether matched records should be included or excluded
				$refineSearchActionCheckbox = $_POST['refineSearchExclude']; // the user marked the checkbox next to "Exclude matches"
			else
				$refineSearchActionCheckbox = "0"; // the user did NOT mark the checkbox next to "Exclude matches"
		}

		elseif (ereg("^(Show|Hide|Browse)$", $displayType)) // the user clicked either the 'Browse' or 'Show'/'Hide' buttons of the "Display Options" form
		// (hitting <enter> within the 'ShowRows' text entry field of the "Display Options" form will act as if the user clicked the 'Browse'/'Show' button)
		{
			$fieldSelector = $_POST['displayOptionsSelector']; // extract field name chosen by the user
		}


		if ($displayType == "Search")
		{
			if ($refineSearchName != "") // if the user typed a search string into the text entry field...
			{
				// Depending on the chosen output action, construct an appropriate SQL query:
				if ($refineSearchActionCheckbox == "0") // if the user did NOT mark the checkbox next to "Exclude matches"
					{
						// for the fields 'marked=no', 'copy=false' and 'selected=no', force NULL values to be matched:
						if (($fieldSelector == "marked" AND $refineSearchName == "no") OR ($fieldSelector == "copy" AND $refineSearchName == "false") OR ($fieldSelector == "selected" AND $refineSearchName == "no"))
							$query = eregi_replace("WHERE","WHERE ($fieldSelector RLIKE " . quote_smart($refineSearchName) . " OR $fieldSelector IS NULL) AND",$query); // ...add search field name & value to the sql query
						else // add default 'WHERE' clause:
							$query = eregi_replace("WHERE","WHERE $fieldSelector RLIKE " . quote_smart($refineSearchName) . " AND",$query); // ...add search field name & value to the sql query
					}
				else // $refineSearchActionCheckbox == "1" // if the user marked the checkbox next to "Exclude matches"
					{
						$query = eregi_replace("WHERE","WHERE ($fieldSelector NOT RLIKE " . quote_smart($refineSearchName) . " OR $fieldSelector IS NULL) AND",$query); // ...add search field name & value to the sql query
					}
				$query = eregi_replace(' AND serial RLIKE "\.\+"','',$query); // remove any 'AND serial RLIKE ".+"' which isn't required anymore
			}
			// else, if the user did NOT type a search string into the text entry field, we simply keep the old WHERE clause...
		}


		elseif ($displayType == "Show" OR $displayType == "Hide")
		{
			if ($displayType == "Show") // if the user clicked the 'Show' button...
				{
					if (!preg_match("/SELECT.*\W$fieldSelector\W.*FROM $queryTable/i", $query)) // ...and the field is *not* already displayed...
						$query = eregi_replace(" FROM $queryTable",", $fieldSelector FROM $queryTable",$query); // ...then SHOW the field that was used for refining the search results
				}
			elseif ($displayType == "Hide") // if the user clicked the 'Hide' button...
				{
					if (preg_match("/SELECT.*\W$fieldSelector\W.*FROM $queryTable/i", $query)) // ...and the field *is* currently displayed...
					{
						// for all columns except the first:
						$query = preg_replace("/(SELECT.+?), $fieldSelector( .*FROM $queryTable)/i","\\1\\2",$query); // ...then HIDE the field that was used for refining the search results
						// for all columns except the last:
						$query = preg_replace("/(SELECT.*? )$fieldSelector, (.+FROM $queryTable)/i","\\1\\2",$query); // ...then HIDE the field that was used for refining the search results
					}
				}
		}

		elseif ($displayType == "Browse") // if the user clicked the 'Browse' button within the "Display Options" form...
		{
			$previousField = preg_replace("/^SELECT (\w+).+/i","\\1",$query); // extract the field that was previously used in Browse view

			if (!eregi("^" . $fieldSelector . "$", $previousField)) // if the user did choose another field in Browse view...
			{
				// ...modify the SQL query to show a summary for the new field that was chosen by the user:
				// (NOTE: these replace patterns aren't 100% safe and may fail if the user has modified the query using 'sql_search.php'!)
				$query = preg_replace("/^SELECT $previousField/i","SELECT $fieldSelector",$query); // use the field that was chosen by the user for Browse view
				$query = preg_replace("/GROUP BY $previousField/i","GROUP BY $fieldSelector",$query); // group data by the field that was chosen by the user
				$query = preg_replace("/ORDER BY( records( DESC)?,)? $previousField/i","ORDER BY\\1 $fieldSelector",$query); // order data by the field that was chosen by the user
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
			if (eregi("^(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)$",$fieldSelector))
				if (!eregi("LEFT JOIN $tableUserData", $query)) // ...and if the 'LEFT JOIN...' statement isn't already part of the 'FROM' clause...
					$query = eregi_replace(" FROM $tableRefs"," FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = $userID",$query); // ...add the 'LEFT JOIN...' part to the 'FROM' clause

			if ($displayType != "Browse")
			{
				$query = eregi_replace(" FROM $tableRefs",", orig_record FROM $tableRefs",$query); // add 'orig_record' column (although it won't be visible the 'orig_record' column gets included in every search query)
																						// (which is required in order to present visual feedback on duplicate records)

				$query = eregi_replace(" FROM $tableRefs",", serial FROM $tableRefs",$query); // add 'serial' column (although it won't be visible the 'serial' column gets included in every search query)
																				// (which is required in order to obtain unique checkbox names)

				if ($showLinks == "1")
					$query = eregi_replace(" FROM $tableRefs",", file, url, doi, isbn, type FROM $tableRefs",$query); // add 'file', 'url', 'doi', 'isbn' & 'type' columns
			}
		}
		elseif ($queryTable == $tableUsers) // 'users.php':
		{
			$query = eregi_replace(" FROM $tableUsers",", user_id FROM $tableUsers",$query); // add 'user_id' column (although it won't be visible the 'user_id' column gets included in every search query)
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
		// split the string on the specified delimiter (which is interpreted as regular expression!):
		$piecesArray = split($splitDelim, $sourceString);

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
	function reArrangeAuthorContents($authorContents, $familyNameFirst, $oldBetweenAuthorsDelim, $newBetweenAuthorsDelimStandard, $newBetweenAuthorsDelimLastAuthor, $oldAuthorsInitialsDelim, $newAuthorsInitialsDelimFirstAuthor, $newAuthorsInitialsDelimStandard, $betweenInitialsDelim, $initialsBeforeAuthorFirstAuthor, $initialsBeforeAuthorStandard, $shortenGivenNames, $includeNumberOfAuthors, $customStringAfterFirstAuthor, $encodeHTML)
	{
		// Note: I haven't figured out how to *successfully* enable locale support, so that e.g. '[[:upper:]]' would also match 'ÿ' etc.
		//       Therefore, as a workaround, high ascii chars are specified literally below
		//       (in order to have this work, the character encoding of 'search.php' must be set to 'Western (Iso Latin 1)' aka 'ISO-8859-1'!)
		//       high ascii chars upper case = "ƒ≈¡¿¬√«…» À—÷ÿ”“‘’‹⁄Ÿ€ÕÃŒœ∆"
		//       high ascii chars lower case = "‰Â·‡‚„ÁÈËÍÎÒˆ¯ÛÚÙı¸˙˘˚ÌÏÓÔÊˇﬂ"
		// setlocale(LC_COLLATE, 'la_LN.ISO-8859-1'); // use the ISO 8859-1 Latin-1 character set for pattern matching

		$authorsArray = split($oldBetweenAuthorsDelim, $authorContents); // get a list of all authors for this record

		$authorCount = count($authorsArray); // check how many authors we have to deal with
		$newAuthorContents = ""; // this variable will hold the final author string
		$includeStringAfterFirstAuthor = false;
		if (empty($includeNumberOfAuthors))
			$includeNumberOfAuthors = $authorCount;

		for ($i=0; $i < $authorCount; $i++)
		{
			$singleAuthorArray = split($oldAuthorsInitialsDelim, $authorsArray[$i]); // for each author, extract author name & initials to separate list items

			if (!$familyNameFirst) // if the family name comes *after* the given name (or initials) in the source string, put array elements in reverse order:
				$singleAuthorArray = array_reverse($singleAuthorArray); // (Note: this only works, if the array has only *two* elements, i.e., one containing the author's name and one holding the initials!)

			if ($shortenGivenNames) // if we're supposed to abbreviate given names
			{
				// within initials, reduce all full first names (-> defined by a starting uppercase character, followed by one ore more lowercase characters)
				// to initials, i.e., only retain their first character
				$singleAuthorArray[1] = preg_replace("/([[:upper:]ƒ≈¡¿¬√«…» À—÷ÿ”“‘’‹⁄Ÿ€ÕÃŒœ∆])[[:lower:]‰Â·‡‚„ÁÈËÍÎÒˆ¯ÛÚÙı¸˙˘˚ÌÏÓÔÊˇﬂ]+/", "\\1", $singleAuthorArray[1]);

				// within initials, remove any dots:
				$singleAuthorArray[1] = preg_replace("/([[:upper:]ƒ≈¡¿¬√«…» À—÷ÿ”“‘’‹⁄Ÿ€ÕÃŒœ∆])\.+/", "\\1", $singleAuthorArray[1]);

				// within initials, remove any spaces *between* initials:
				$singleAuthorArray[1] = preg_replace("/(?<=[-[:upper:]ƒ≈¡¿¬√«…» À—÷ÿ”“‘’‹⁄Ÿ€ÕÃŒœ∆]) +(?=[-[:upper:]ƒ≈¡¿¬√«…» À—÷ÿ”“‘’‹⁄Ÿ€ÕÃŒœ∆])/", "", $singleAuthorArray[1]);

				// within initials, add a space after a hyphen, but only if ...
				if (ereg(" $", $betweenInitialsDelim)) // ... the delimiter that separates initials ends with a space
					$singleAuthorArray[1] = preg_replace("/-(?=[[:upper:]ƒ≈¡¿¬√«…» À—÷ÿ”“‘’‹⁄Ÿ€ÕÃŒœ∆])/", "- ", $singleAuthorArray[1]);

				// then, separate initials with the specified delimiter:
				$singleAuthorArray[1] = preg_replace("/([[:upper:]ƒ≈¡¿¬√«…» À—÷ÿ”“‘’‹⁄Ÿ€ÕÃŒœ∆])/", "\\1$betweenInitialsDelim", $singleAuthorArray[1]);
			}


			if ((($i == 0) AND $initialsBeforeAuthorFirstAuthor) OR (($i > 0) AND $initialsBeforeAuthorStandard)) // put array elements in reverse order:
				$singleAuthorArray = array_reverse($singleAuthorArray); // (Note: this only works, if the array has only *two* elements, i.e., one containing the author's name and one holding the initials!)

			// re-join author name & initials, using the specified delimiter, and copy the string to the end of an array:
			if ($i == 0) // -> first author
				$singleAuthorString = implode($newAuthorsInitialsDelimFirstAuthor, $singleAuthorArray);
			else // $i > 0 // -> all authors except the first one
				$singleAuthorString = implode($newAuthorsInitialsDelimStandard, $singleAuthorArray);

			// append this author to the final author string:
			if ($i == 0) // -> first author
			{
				$newAuthorContents .= $singleAuthorString;

				// we'll append the string in '$customStringAfterFirstAuthor' to the first author if number of authors is greater than the number given in '$includeNumberOfAuthors':
				if (($includeNumberOfAuthors>0) AND ($authorCount > $includeNumberOfAuthors))
				{
					if (ereg("__NUMBER_OF_AUTHORS__", $customStringAfterFirstAuthor))
						$customStringAfterFirstAuthor = preg_replace("/__NUMBER_OF_AUTHORS__/", ($authorCount -1), $customStringAfterFirstAuthor); // resolve placeholder

					$includeStringAfterFirstAuthor = true;
					break;
				}
			}
			elseif (($includeNumberOfAuthors<0) AND ($i == -$includeNumberOfAuthors)) { // -> last author
				if (ereg("__NUMBER_OF_AUTHORS__", $customStringAfterFirstAuthor))
					$customStringAfterFirstAuthor = preg_replace("/__NUMBER_OF_AUTHORS__/", ($authorCount + $includeNumberOfAuthors), $customStringAfterFirstAuthor); // resolve placeholder

				$includeStringAfterFirstAuthor = true;
				break;
			}
			elseif (($authorCount > 1) AND (($i + 1) == $authorCount)) // -> last author
				$newAuthorContents .= $newBetweenAuthorsDelimLastAuthor . $singleAuthorString;
			else // -> all authors except the first or the last one
				$newAuthorContents .= $newBetweenAuthorsDelimStandard . $singleAuthorString;
		}

		// do some final clean up:
		$newAuthorContents = preg_replace("/  +/", " ", $newAuthorContents); // remove double spaces (which occur e.g., when both, $betweenInitialsDelim & $newAuthorsInitialsDelim..., end with a space)
		$newAuthorContents = preg_replace("/ +([,.;:?!])/", "\\1", $newAuthorContents); // remove spaces before [,.;:?!]

		if ($encodeHTML)
			$newAuthorContents = encodeHTML($newAuthorContents); // HTML encode higher ASCII characters within the newly arranged author contents

		if ($includeStringAfterFirstAuthor)
			$newAuthorContents .= $customStringAfterFirstAuthor; // the custom string won't get HTML encoded so that it's possible to include HTML tags (such as '<i>') within the string

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
	// this function takes the contents of the author field and will extract the given name of a particular author (specified by position)
	// (e.g., setting '$authorPosition' to "1" will return the 1st author's given name)
	//  Required Parameters:
	//        1. pattern describing delimiter that separates different authors
	//        2. pattern describing delimiter that separates author name & initials (within one author)
	//        3. position of the author whose last name shall be extracted (e.g., "1" will return the 1st author's last name)
	//        4. contents of the author field
	function extractAuthorsGivenName($oldBetweenAuthorsDelim, $oldAuthorsInitialsDelim, $authorPosition, $authorContents)
	{
		$authorsArray = split($oldBetweenAuthorsDelim, $authorContents); // get a list of all authors for this record

		$authorPosition = ($authorPosition-1); // php array elements start with "0", so we decrease the authors position by 1
		$singleAuthor = $authorsArray[$authorPosition]; // for the author in question, extract the full author name (last name & initials)
		$singleAuthorArray = split($oldAuthorsInitialsDelim, $singleAuthor); // then, extract author name & initials to separate list items
		$singleAuthorsGivenName = $singleAuthorArray[1]; // extract this author's last name into a new variable

		return $singleAuthorsGivenName;
	}

	// --------------------------------------------------------------------

	// PARSE PLACEHOLDER STRING
	// this function will parse a given placeholder string into its indiviual placeholders and replaces
	// them with content from the given record
	function parsePlaceholderString($formVars, $placeholderString, $fallbackPlaceholderString)
	{
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
							$title .= extractDetailsFromField("title", $formVars['titleName'], " +", $options);
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
							$publication .= extractDetailsFromField("publication", $formVars['publicationName'], " +", $options);
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
							$abbrevJournal .= extractDetailsFromField("abbrev_journal", $formVars['abbrevJournalName'], "\.? +|\.", $options);
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
							$startPage .= preg_replace("/^\D*(\d+).*/i", "\\1", $formVars['pagesNo']);
							$startPage .= $suffix;
							$convertedPlaceholderArray[] = $startPage;
						}
					}

					// '<:endPage:>' placeholder:
					elseif (preg_match("/:endPage:/i", $placeholderPart))
					{
						if (!empty($formVars['pagesNo']) AND preg_match("/\d+/i", $formVars['pagesNo'])) // if the 'pages' field contains a number
						{
							$pages = preg_replace("/^\D*(\d+)( *[-ñ]+ *\d+)?.*/i", "\\1\\2", $formVars['pagesNo']);
							$endPage = $prefix;
							$endPage .= extractDetailsFromField("pages", $pages, "[^0-9]+", "[-1]"); // we'll use this function instead of just grabbing a matched regex pattern since it'll also work when just a number but no range is given (e.g. when startPage = endPage)
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
							$keywords .= extractDetailsFromField("keywords", $formVars['keywordsName'], " *[;,] *", $options);
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
							$area .= extractDetailsFromField("area", $formVars['areaName'], " *[;,] *", $options);
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
							$notes .= extractDetailsFromField("notes", $formVars['notesName'], " +", $options);
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
							$userKeys .= extractDetailsFromField("user_keys", $formVars['userKeysName'], " *[;,] *", $options);
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

		if (!($returnRawSourceString) AND ereg($splitDelim, $sourceString))
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
	//                (the 'error' element was added with PHP 4.2.0. Error code explanation: <http://www.php.net/manual/en/features.file-upload.errors.php>)
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
		$relatedQuery = "SELECT author, title, year, publication, volume, pages";

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
	function modifyUserGroups($queryTable, $displayType, $recordSerialsArray, $recordSerialsString, $userID, $userGroup, $userGroupActionRadio)
	{
		global $oldQuery;
		global $tableUserData, $tableUsers; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

// 		// CAUTION: this commented code is highly experimental and exposes probably too much power and/or possible side effects!
// 		// Check whether the contents of the '$userGroup' variable shall be interpreted as regular expression:
// 		// Note: We assume the variable contents to be a (perl-style!) regular expression if the following conditions are true:
// 		//       - the user checked the radio button next to the group text entry field ('userGroupName')
// 		//       - the entered string starts with 'REGEXP:'
// 		if (($userGroupActionRadio == "0") AND (ereg("^REGEXP:", $userGroup))) // don't escape possible meta characters
// 		{
// 			$userGroup = preg_replace("/REGEXP:(.+)/", "(\\1)", $userGroup); // remove 'REGEXP:' tage & enclose the following pattern in brackets
// 			// The enclosing brackets ensure that a pipe '|' which is used in the grep pattern doesn't cause any harm.
// 			// E.g., without enclosing brackets, the pattern 'mygroup|.+' would be (among others) resolved to ' *; *mygroup|.+ *' (see below).
// 			// This, in turn, would cause the pattern to match beyond the group delimiter (semicolon), causing severe damage to the user's
// 			// other group names!
//
// 			// to assure that the regular pattern specified by the user doesn't match beyond our group delimiter ';' (semicolon),
// 			// we'll need to convert any greedy regex quantifiers to non-greedy ones:
// 			$userGroup = preg_replace("/(?<![?+*]|[\d,]})([?+*]|\{\d+(, *\d*)?\})(?!\?)/", "\\1?", $userGroup);
// 		}

		// otherwise we escape any possible meta characters:
//		else // if the user checked the radio button next to the group popup menu ($userGroupActionRadio == "1") -OR-
			// the radio button next to the group text entry field was selected BUT the string does NOT start with an opening bracket and end with a closing bracket...
			$userGroup = preg_quote($userGroup, "/"); // escape meta characters (including '/' that is used as delimiter for the PCRE replace functions below and which gets passed as second argument)


		if ($queryTable == $tableUserData) // for the current user, get all entries within the 'user_data' table that refer to the selected records (listed in '$recordSerialsString'):
			$query = "SELECT record_id, user_groups FROM $tableUserData WHERE record_id RLIKE " . quote_smart("^(" . $recordSerialsString . ")$") . " AND user_id = " . quote_smart($userID);
		elseif ($queryTable == $tableUsers) // for the admin, get all entries within the 'users' table that refer to the selected records (listed in '$recordSerialsString'):
			$query = "SELECT user_id as record_id, user_groups FROM $tableUsers WHERE user_id RLIKE " . quote_smart("^(" . $recordSerialsString . ")$");
			// (note that by using 'user_id as record_id' we can use the term 'record_id' as identifier of the primary key for both tables)


		$result = queryMySQLDatabase($query, $oldQuery); // RUN the query on the database through the connection

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
				if ($displayType == "Add" AND !ereg("(^|.*;) *$userGroup *(;.*|$)", $recordUserGroups)) // if the specified group isn't listed already within the 'user_groups' field:
				{
					if (empty($recordUserGroups)) // and if the 'user_groups' field is completely empty
						$recordUserGroups = ereg_replace("^.*$", "$userGroup", $recordUserGroups); // add the specified user group to the 'user_groups' field
					else // if the 'user_groups' field does already contain some user content:
						$recordUserGroups = ereg_replace("^(.+)$", "\\1; $userGroup", $recordUserGroups); // append the specified user group to the 'user_groups' field
				}

				// REMOVE the specified user group from the 'user_groups' field:
				elseif ($displayType == "Remove") // remove the specified group from the 'user_groups' field:
				{
					$recordUserGroups = preg_replace("/^ *$userGroup *(?=;|$)/", "", $recordUserGroups); // the specified group is listed at the very beginning of the 'user_groups' field
					$recordUserGroups = preg_replace("/ *; *$userGroup *(?=;|$)/", "", $recordUserGroups); // the specified group occurs after some other group name within the 'user_groups' field
					$recordUserGroups = ereg_replace("^ *; *", "", $recordUserGroups); // remove any remaining group delimiters at the beginning of the 'user_groups' field
				}

				if ($queryTable == $tableUserData) // for the current record & user ID, update the matching entry within the 'user_data' table:
					$queryUserData = "UPDATE $tableUserData SET user_groups = " . quote_smart($recordUserGroups) . " WHERE record_id = " . quote_smart($recordID) . " AND user_id = " . quote_smart($userID);
				elseif ($queryTable == $tableUsers) // for the current user ID, update the matching entry within the 'users' table:
					$queryUserData = "UPDATE $tableUsers SET user_groups = " . quote_smart($recordUserGroups) . " WHERE user_id = " . quote_smart($recordID);


				$resultUserData = queryMySQLDatabase($queryUserData, $oldQuery); // RUN the query on the database through the connection
			}
		}

		if ($queryTable == $tableUserData)
		{
			// for all selected records that have no entries in the 'user_data' table (for this user), we'll need to add a new entry containing the specified group:
			$leftoverSerialsArray = array_diff($recordSerialsArray, $foundSerialsArray); // get all unique array elements of '$recordSerialsArray' which are not in '$foundSerialsArray'

			foreach ($leftoverSerialsArray as $leftoverRecordID) // for each record that we haven't processed yet (since it doesn't have an entry in the 'user_data' table for this user)
			{
				// for the current record & user ID, add a new entry (containing the specified group) to the 'user_data' table:
				$queryUserData = "INSERT INTO $tableUserData SET "
								. "user_groups = " . quote_smart($userGroup) . ", "
								. "record_id = " . quote_smart($leftoverRecordID) . ", "
								. "user_id = " . quote_smart($userID) . ", "
								. "data_id = NULL"; // inserting 'NULL' into an auto_increment PRIMARY KEY attribute allocates the next available key value

				$resultUserData = queryMySQLDatabase($queryUserData, $oldQuery); // RUN the query on the database through the connection
			}
		}

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

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		// Note: 'user_groups RLIKE ".+"' will cause the database to only return user data entries where the 'user_groups' field
		//       is neither NULL (=> 'user_groups IS NOT NULL') nor the empty string (=> 'user_groups NOT RLIKE "^$"')
		if ($queryTable == $tableUserData)
			// Find all unique 'user_groups' entries in the 'user_data' table belonging to the current user:
			$query = "SELECT DISTINCT user_groups FROM $tableUserData WHERE user_id = " . quote_smart($userID) . " AND user_groups RLIKE \".+\"";
		elseif ($queryTable == $tableUsers)
			// Find all unique 'user_groups' entries in the 'users' table:
			$query = "SELECT DISTINCT user_groups FROM $tableUsers WHERE user_groups RLIKE \".+\"";

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection

		$userGroupsArray = array(); // initialize array variable

		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows found ...
		{
			while ($row = @ mysql_fetch_array($result)) // for all rows found
			{
				// remove any meaningless delimiter(s) from the beginning or end of a field string:
				$rowUserGroupsString = trimTextPattern($row["user_groups"], "( *; *)+", true, true); // function 'trimTextPattern()' is defined in 'include.inc.php'

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

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		// Find all unique query entries in the 'queries' table belonging to the current user:
		// (query names should be unique anyhow, so the DISTINCT parameter wouldn't be really necessary)
		$query = "SELECT DISTINCT query_name FROM $tableQueries WHERE user_id = " . quote_smart($userID) . " ORDER BY last_execution DESC";
		// Note: we sort (in descending order) by the 'last_execution' field to get the last used query entries first;
		//       by that, the last used query will be always at the top of the popup menu within the 'Recall My Query' form

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection

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

	// Get all available formats/styles/types:
	function getAvailableFormatsStylesTypes($dataType, $formatType) // '$dataType' must be one of the following: 'format', 'style', 'type'; '$formatType' must be either '', 'export', 'import' or 'cite'
	{
		global $tableDepends, $tableFormats, $tableStyles, $tableTypes; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		if ($dataType == "format")
			$query = "SELECT format_name, format_id FROM $tableFormats LEFT JOIN $tableDepends ON $tableFormats.depends_id = $tableDepends.depends_id WHERE format_type = " . quote_smart($formatType) . " AND format_enabled = 'true' AND depends_enabled = 'true' ORDER BY order_by, format_name";

		elseif ($dataType == "style")
			$query = "SELECT style_name, style_id FROM $tableStyles LEFT JOIN $tableDepends ON $tableStyles.depends_id = $tableDepends.depends_id WHERE style_enabled = 'true' AND depends_enabled = 'true' ORDER BY order_by, style_name";

		elseif ($dataType == "type")
			$query = "SELECT type_name, type_id FROM $tableTypes WHERE type_enabled = 'true' ORDER BY order_by, type_name";

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection

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

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		if ($dataType == "format")
			$query = "SELECT $tableFormats.format_name, $tableFormats.format_id FROM $tableFormats LEFT JOIN $tableUserFormats on $tableFormats.format_id = $tableUserFormats.format_id LEFT JOIN $tableDepends ON $tableFormats.depends_id = $tableDepends.depends_id WHERE format_type = " . quote_smart($formatType) . " AND format_enabled = 'true' AND depends_enabled = 'true' AND user_id = " . quote_smart($userID) . " ORDER BY $tableFormats.order_by, $tableFormats.format_name";

		elseif ($dataType == "style")
			$query = "SELECT $tableStyles.style_name, $tableStyles.style_id FROM $tableStyles LEFT JOIN $tableUserStyles on $tableStyles.style_id = $tableUserStyles.style_id LEFT JOIN $tableDepends ON $tableStyles.depends_id = $tableDepends.depends_id WHERE style_enabled = 'true' AND depends_enabled = 'true' AND user_id = " . quote_smart($userID) . " ORDER BY $tableStyles.order_by, $tableStyles.style_name";

		elseif ($dataType == "type")
			$query = "SELECT $tableTypes.type_name, $tableTypes.type_id FROM $tableTypes LEFT JOIN $tableUserTypes USING (type_id) WHERE type_enabled = 'true' AND user_id = " . quote_smart($userID) . " ORDER BY $tableTypes.order_by, $tableTypes.type_name";

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection

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

	// Get all user formats/styles/types that are available and enabled for the current user (by admins choice) AND which this user has choosen to be visible:
	// and (if some formats/styles/types were found) save them each as semicolon-delimited string to the session variables 'user_export_formats', 'user_cite_formats', 'user_styles' or 'user_types', respectively:
	function getVisibleUserFormatsStylesTypes($userID, $dataType, $formatType) // '$dataType' must be one of the following: 'format', 'style', 'type'; '$formatType' must be either '', 'export', 'import' or 'cite'
	{
		global $loginEmail;
		global $adminLoginEmail; // ('$adminLoginEmail' is specified in 'ini.inc.php')
		global $tableDepends, $tableFormats, $tableStyles, $tableTypes, $tableUserFormats, $tableUserStyles, $tableUserTypes; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

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

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection

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
	//     (with those items being selected which were choosen to be _visible_ by the current user)
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

			$selectedFormatsStylesTypesArray = getVisibleUserFormatsStylesTypes($userID, $dataType, $formatType); // get all formats/styles/types that were choosen to be visible for the current user
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

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		// get the 'style_spec' for the record entry in table 'styles' whose 'style_name' matches that in '$citeStyle':
		$query = "SELECT style_spec FROM $tableStyles WHERE style_name = " . quote_smart($citeStyle);

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection
		$row = mysql_fetch_array($result);

		return($row["style_spec"]);
	}

	// --------------------------------------------------------------------

	// Fetch the path/name of the format file that's associated with the format given in '$formatName'
	function getFormatFile($formatName, $formatType) // '$formatType' must be either 'export', 'import' or 'cite'
	{
		global $tableFormats; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		// get the 'format_spec' for the record entry in table 'formats' whose 'format_name' matches that in '$formatName':
		$query = "SELECT format_spec FROM $tableFormats WHERE format_name = " . quote_smart($formatName) . " AND format_type = " . quote_smart($formatType);

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection
		$row = mysql_fetch_array($result);

		return($row["format_spec"]);
	}

	// --------------------------------------------------------------------

	// Fetch the path of the external utility that's required for a particular import/export format
	function getExternalUtilityPath($externalUtilityName)
	{
		global $tableDepends; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		// get the path for the record entry in table 'depends' whose field 'depends_external' matches that in '$externalUtilityName':
		$query = "SELECT depends_path FROM $tableDepends WHERE depends_external = " . quote_smart($externalUtilityName);

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection
		$row = mysql_fetch_array($result);

		return($row["depends_path"]);
	}

	// --------------------------------------------------------------------

	// Get the user (or group) permissions for the current user
	// and (optionally) save all allowed user actions as semicolon-delimited string to the session variable 'user_permissions':
	function getPermissions($user_OR_groupID, $permissionType, $savePermissionsToSessionVariable) // '$permissionType' must be either 'user' or 'group'; '$savePermissionsToSessionVariable' must be either 'true' or 'false'
	{
		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		// Fetch all permission settings from the 'user_permissions' (or 'group_permissions') table for the current user:
		$query = "SELECT allow_add, allow_edit, allow_delete, allow_download, allow_upload, allow_details_view, allow_print_view, allow_browse_view, allow_sql_search, allow_user_groups, allow_user_queries, allow_rss_feeds, allow_import, allow_export, allow_cite, allow_batch_import, allow_batch_export, allow_modify_options FROM " . $permissionType . "_permissions WHERE " . $permissionType . "_id = " . quote_smart($user_OR_groupID);

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection

		if (mysql_num_rows($result) == 1) // interpret query result: Do we have exactly one row?
		{
			$userPermissionsArray = array(); // initialize array variables
			$userPermissionsFieldNameArray = array();

			$row = mysql_fetch_array($result); // fetch the one row into the array '$row'

			$fieldsFound = mysql_num_fields($result); // count the number of fields

			for ($i=0; $i<$fieldsFound; $i++)
			{
				$fieldInfo = mysql_fetch_field($result, $i); // get the meta-data for the attribute
				$fieldName = $fieldInfo->name; // get the current attribute name

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

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		if (empty($userID))
			// Find all unique language entries in the 'languages' table that are enabled:
			// (language names should be unique anyhow, so the DISTINCT parameter wouldn't be really necessary)
			$query = "SELECT DISTINCT language_name FROM $tableLanguages WHERE language_enabled = 'true' ORDER BY order_by";
		else
			// Get the preferred language for the user with the user ID given in '$userID':
			$query = "SELECT language AS language_name FROM $tableUsers WHERE user_id = " . quote_smart($userID);


		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection

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

	// Get all user options for the current user:
	function getUserOptions($userID)
	{
		global $tableUserOptions; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

		if (empty($userID))
			$userID = 0;

		// CONSTRUCT SQL QUERY:
		// Fetch all options from table 'user_options' for the user with the user ID given in '$userID':
		$query = "SELECT * FROM $tableUserOptions WHERE user_id = " . quote_smart($userID);


		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection

		$userOptionsArray = array(); // initialize array variable

		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound == 1) // Interpret query result: Do we have exactly one row?
			$userOptionsArray = @ mysql_fetch_array($result); // fetch the one row into the array '$userOptionsArray'

		return $userOptionsArray;
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

	// Build a correct call number prefix for the currently logged-in user (e.g. 'IP÷ @ msteffens'):
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

	// Returns the total number of records in the database:
	function getNumberOfRecords()
	{
		global $tableRefs; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		$query = "SELECT COUNT(serial) FROM $tableRefs"; // query the total number of records

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection

		$row = mysql_fetch_row($result); // fetch the current row into the array $row (it'll be always *one* row, but anyhow)
		$numberOfRecords = $row[0]; // extract the contents of the first (and only) row

		return $numberOfRecords;
	}

	// --------------------------------------------------------------------

	// Returns the date/time information (in format 'YYYY-MM-DD hh-mm-ss') when the database was last modified:
	function getLastModifiedDateTime()
	{
		global $tableRefs; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		$query = "SELECT modified_date, modified_time FROM $tableRefs ORDER BY modified_date DESC, modified_time DESC, created_date DESC, created_time DESC LIMIT 1"; // get date/time info for the record that was added/edited most recently

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection

		$row = mysql_fetch_row($result); // fetch the current row into the array $row (it'll be always *one* row, but anyhow)
		$lastModifiedDateTime = $row[0] . " " . $row[1];

		return $lastModifiedDateTime;
	}

	// --------------------------------------------------------------------

	// Update the specified user permissions for the selected user(s):
	function updateUserPermissions($recordSerialsString, $userPermissionsArray) // '$userPermissionsArray' must contain one or more key/value elements of the form array('allow_add' => 'yes', 'allow_delete' => 'no') where key is a particular 'allow_*' field name from table 'user_permissions' and value is either 'yes' or 'no'
	{
		connectToMySQLDatabase("");

		$permissionQueryArray = array();

		// CONSTRUCT SQL QUERY:
		// prepare the 'SET' part of the SQL query string:
		foreach($userPermissionsArray as $permissionKey => $permissionValue)
			$permissionQueryArray[] = $permissionKey . " = " . quote_smart($permissionValue);

		if (!empty($permissionQueryArray))
		{
			$permissionQueryString = implode(", ", $permissionQueryArray);

			// Update all specified permission settings in the 'user_permissions' table for the selected user(s):
			$query = "UPDATE user_permissions SET " . $permissionQueryString . " WHERE user_id RLIKE " . quote_smart("^(" . $recordSerialsString . ")$");

			$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection

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
		global $userOptionsArray; // '$userOptionsArray' is made globally available by functions 'generateExport()' and 'generateCitations()' in 'search.php'
		global $citeKeysArray; // '$citeKeysArray' is made globally available by functions 'modsCollection()' in 'modsxml.inc.php' or 'odfSpreadsheet()' in 'odfxml.inc.php', respectively

		// by default, we use any record-specific cite key that was entered manually by the user:
		if (isset($formVars['citeKeyName']))
			$citeKey = $formVars['citeKeyName'];
		else
			$citeKey = "";


		// check if the user's options for auto-generation of cite keys command us to replace the manually entered cite key:
		if (!empty($userOptionsArray))
		{
			if ($userOptionsArray['export_cite_keys'] == "yes") // if this user wants to include cite keys on export
			{
				if ($userOptionsArray['autogenerate_cite_keys'] == "yes") // if cite keys shall be auto-generated on export
				{
					if (empty($citeKey) OR ($userOptionsArray['prefer_autogenerated_cite_keys'] == "yes")) // if there's no manually entered cite key -OR- if the auto-generated cite key shall overwrite contents from the 'cite_key' field on export
					{
						if ($userOptionsArray['use_custom_cite_key_format'] == "yes") // if the user wants to use a custom cite key format
							$citeKeyFormat = $userOptionsArray['cite_key_format'];

						else // use the default cite key format that was specified by the admin in 'ini.inc.php'
							$citeKeyFormat = $defaultCiteKeyFormat;

						// auto-generate a cite key according to the given naming scheme:
						$citeKey = parsePlaceholderString($formVars, $citeKeyFormat, "<:authors:><:year:>"); // function 'parsePlaceholderString()' is defined in 'include.inc.php'
					}
				}
			}
			else
				$citeKey = ""; // by omitting a cite key bibutils will take care of generation of cite keys for its export formats (BibTeX, Endnote, RIS)
		}


		// check how to handle non-ASCII characters:
		if (!empty($userOptionsArray) AND !empty($userOptionsArray['nonascii_chars_in_cite_keys'])) // use the user's own setting
			$handleNonASCIIChars = $userOptionsArray['nonascii_chars_in_cite_keys'];
		else
			$handleNonASCIIChars = $handleNonASCIICharsInCiteKeysDefault; // use the default setting that was specified by the admin in 'ini.inc.php'

		if (!empty($citeKey))
			$citeKey = handleNonASCIIAndUnwantedCharacters($citeKey, "\S", $handleNonASCIIChars); // in addition to the handling of non-ASCII chars (given in '$handleNonASCIIChars') we'll only strip whitespace from the generated cite keys


		// ensure that each cite key is unique:
		if (!empty($citeKey) AND !empty($userOptionsArray) AND ($userOptionsArray['export_cite_keys'] == "yes") AND ($userOptionsArray['uniquify_duplicate_cite_keys'] == "yes"))
		{
			if (!isset($citeKeysArray[$citeKey])) // this cite key has not been seen so far
				$citeKeysArray[$citeKey] = 1; // append the current cite key (together with its number of occurrence) to the array of known cite keys

			else // we've encountered the current site key already before
			{
				$citeKeyOccurrence = $citeKeysArray[$citeKey] + 1; // increment the number of occurrence for the current cite key
				$citeKeysArray[$citeKey] = $citeKeyOccurrence; // update the array of known cite keys accordingly
				$citeKey = $citeKey . "_" . $citeKeyOccurrence; // append the current number of occurrence to this cite key
			}
		}

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
	function buildSelectMenuOptions($sourceStringOrArray, $splitDelim, $prefix, $useArrayKeysAsValues)
	{
		if (is_string($sourceStringOrArray)) // split the string on the specified delimiter (which is interpreted as regular expression!):
			$itemArray = split($splitDelim, $sourceStringOrArray);
		else // source data are already provided as array:
			$itemArray = $sourceStringOrArray;

		$optionTags = ""; // initialize variable

		// copy each item as option tag element to the end of the '$optionTags' variable:
		if ($useArrayKeysAsValues)
		{
			foreach ($itemArray as $itemID => $item)
			{
				if (!empty($item))
					$optionTags .= "\n$prefix<option value=\"$itemID\">$item</option>";
				else // empty items will also get an empty value:
					$optionTags .= "\n$prefix<option value=\"\"></option>";
			}
		}
		else
		{
			foreach ($itemArray as $item)
				$optionTags .= "\n$prefix<option>$item</option>";
		}

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
	function selectDistinct($connection,
							$refsTableName,
							$refsTablePrimaryKey,
							$userDataTableName,
							$userDataTablePrimaryKey,
							$userDataTableUserID,
							$userDataTableUserIDvalue,
							$columnName,
							$pulldownName,
							$additionalOptionDisplay,
							$additionalOption,
							$defaultValue,
							$RestrictToField,
							$RestrictToFieldContents,
							$SplitValues,
							$SplitPattern)
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
		$resultId = queryMySQLDatabase($distinctQuery, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		// Retrieve all distinct values:
		$i = 0;
		$resultBuffer = array();

		while ($row = @ mysql_fetch_array($resultId))
		{
			if ($SplitValues) // if desired, split field contents into substrings
			{
				// split field data on the pattern specified in '$SplitPattern':
				$splittedFieldData = split($SplitPattern, $row[$columnName]);
				// ... copy all array elements to end of '$resultBuffer':
				foreach($splittedFieldData as $element)
					$resultBuffer[$i++] = $element;
			}
			else // copy field data (as is) to end of '$resultBuffer':
				$resultBuffer[$i++] = $row[$columnName];
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

		// Start the select widget:
		echo "\n\t\t<select name=\"$pulldownName\">";

		// Is there an additional option?
		if (isset($additionalOptionDisplay))
		{
			// yes, but is it the default option?
			if ($defaultValue == $additionalOptionDisplay) // show the additional option as selected
				echo "\n\t\t\t<option value=\"$additionalOption\" selected>$additionalOptionDisplay</option>";
			else // just show the additional option
				echo "\n\t\t\t<option value=\"$additionalOption\">$additionalOptionDisplay</option>";
		}

		// Check for a default value:
		if (isset($defaultValue))
		{
			// check if the defaultValue is in the database values
			foreach ($resultBuffer as $result)
			{
				if ($result == $defaultValue) // yes, show as selected
					echo "\n\t\t\t<option selected>$result</option>";
				else // no, just show as an option
					echo "\n\t\t\t<option>$result</option>";
			}
		}
		else // no default value
		{
			foreach ($resultBuffer as $result) // show database values as options
				echo "\n\t\t\t<option>$result</option>";
		}
		echo "\n\t\t</select>";
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
	// ('$transformation' must be either 'lower' or 'upper')
	function changeCase($transformation, $sourceString)
	{
		if (eregi("lower", $transformation)) // change source text to lower case
			$sourceString = strtolower($sourceString);

		elseif (eregi("upper", $transformation)) // change source text to upper case
			$sourceString = strtoupper($sourceString);

		return $sourceString;
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
		static $http = array (
								100 => "HTTP/1.1 100 Continue",
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
								504 => "HTTP/1.1 504 Gateway Time-out"
		);

		header($http[$statusCode]);
	}

	// --------------------------------------------------------------------

	// Convert to character encoding:
	// This function converts text that's represented in the refbase database encoding
	// (which is indicated in '$contentTypeCharset') into the character encoding given
	// in '$targetCharset'. '$transliteration' must be either "TRANSLIT" or "IGNORE"
	// causing characters which are unrecognized by the target charset to get either
	// transliterated or ignored, respectively.
	function convertToCharacterEncoding($targetCharset, $transliteration, $sourceString)
	{
		global $contentTypeCharset; // defined in 'ini.inc.php'
		global $transtab_latin1_ascii; // defined in 'transtab_latin1_ascii.inc.php'
		global $transtab_unicode_ascii; // defined in 'transtab_unicode_ascii.inc.php'

		// in case of ISO-8859-1/UTF-8 to ASCII conversion we attempt to transliterate non-ASCII chars,
		// comparable to the fallback notations that people use commonly in email and on typewriters to
		// represent unavailable characters:
		if (($targetCharset == "ASCII") AND ($transliteration == "TRANSLIT"))
		{
			if ($contentTypeCharset == "UTF-8")
				$convertedString = searchReplaceText($transtab_unicode_ascii, $sourceString, false);
			else // we assume "ISO-8859-1" by default
				$convertedString = searchReplaceText($transtab_latin1_ascii, $sourceString, false);

			// strip any additional non-ASCII characters which we weren't able to transliterate:
			$convertedString = iconv($contentTypeCharset, "ASCII//IGNORE", $convertedString);

			// Notes from <http://www.php.net/manual/en/function.iconv.php> regarding "TRANSLIT" and "IGNORE":
			// - If you append the string //TRANSLIT to out_charset transliteration is activated.
			//   This means that when a character can't be represented in the target charset, it can
			//   be approximated through one or several similarly looking characters. If you append
			//   the string //IGNORE, characters that cannot be represented in the target charset
			//   are silently discarded. Otherwise, str is cut from the first illegal character.
		}

		else
			$convertedString = iconv($contentTypeCharset, "$targetCharset//$transliteration", $sourceString);

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
			// Notes from <http://www.php.net/manual/en/function.htmlentities.php>:
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
	// As opposed to the 'encodeHTML()' function this function will only convert the characters supported by the 'htmlspecialchars()' function:
	// - '&' (ampersand) becomes '&amp;'
	// - '"' (double quote) becomes '&quot;' when ENT_NOQUOTES  is not set
	// - ''' (single quote) becomes '&#039;' only when  ENT_QUOTES is set
	// - '<' (less than) becomes '&lt;'
	// - '>' (greater than) becomes '&gt;'
	// Note that these (and only these!) entities are also supported by XML (which is why we use this function within the XML generating functions
	// 'generateRSS()' & 'modsRecord()' and leave all other higher ASCII chars unencoded)
	function encodeHTMLspecialchars($sourceString)
	{
		global $contentTypeCharset; // defined in 'ini.inc.php'

		$encodedString = htmlspecialchars($sourceString, ENT_COMPAT, "$contentTypeCharset");
		// Notes from <http://www.php.net/manual/en/function.htmlspecialchars.php>:
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

	// Verify the SQL query specified by the user and modify it if security concerns are encountered:
	// (this function does add/remove user-specific query code as required and will fix problems with escape sequences within the SQL query)
	function verifySQLQuery($sqlQuery, $referer, $displayType, $showLinks)
	{
		global $loginEmail;
		global $loginUserID;
		global $fileVisibility; // defined in 'ini.inc.php'
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

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


		// handle the display & querying of user-specific fields:
		if (!isset($_SESSION['loginEmail'])) // if NO user is logged in...
		{
			// ... and any user-specific fields are part of the SELECT or ORDER BY statement...
			if ((empty($referer) OR eregi(".+search.php",$referer)) AND (eregi("(SELECT |ORDER BY |, *)(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)",$sqlQuery))) // if the calling script ends with 'search.php' (i.e., is NOT 'show.php' or 'sru.php', see note below!) AND any user-specific fields are part of the SELECT or ORDER BY clause
			{
				// if the 'SELECT' clause contains any user-specific fields:
				if (preg_match("/SELECT(.(?!FROM))+?(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)/i",$sqlQuery))
				{
					// save an appropriate error message:
					$HeaderString = "<b><span class=\"warning\">Display of user-specific fields was omitted!</span></b>";
					// note: we don't write out any error message if the user-specific fields do only occur within the 'ORDER' clause (but not within the 'SELECT' clause)

					// Write back session variable:
					saveSessionVariable("HeaderString", $HeaderString);
				}

				$sqlQuery = eregi_replace("(SELECT|ORDER BY) (marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)( DESC)?", "\\1 ", $sqlQuery); // ...delete any user-specific fields from beginning of 'SELECT' or 'ORDER BY' clause
				$sqlQuery = eregi_replace(", *(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)( DESC)?", "", $sqlQuery); // ...delete any remaining user-specific fields from 'SELECT' or 'ORDER BY' clause
				$sqlQuery = eregi_replace("(SELECT|ORDER BY) *, *", "\\1 ", $sqlQuery); // ...remove any field delimiters that directly follow the 'SELECT' or 'ORDER BY' terms

				$sqlQuery = preg_replace("/SELECT *(?=FROM)/i", "SELECT author, title, year, publication, volume, pages ", $sqlQuery); // ...supply generic 'SELECT' clause if it did ONLY contain user-specific fields
				$sqlQuery = preg_replace("/ORDER BY *(?=LIMIT|GROUP BY|HAVING|PROCEDURE|FOR UPDATE|LOCK IN|$)/i", "ORDER BY author, year DESC, publication", $sqlQuery); // ...supply generic 'ORDER BY' clause if it did ONLY contain user-specific fields
			}

			// ... and the 'LEFT JOIN...' statement is part of the 'FROM' clause...
			if ((eregi(".+search.php",$referer)) AND (eregi("LEFT JOIN $tableUserData",$sqlQuery))) // if the calling script ends with 'search.php' (i.e., is NOT 'show.php' or 'sru.php', see note below!) AND the 'LEFT JOIN...' statement is part of the 'FROM' clause...
				$sqlQuery = eregi_replace("FROM $tableRefs LEFT JOIN.+WHERE","FROM $tableRefs WHERE",$sqlQuery); // ...delete 'LEFT JOIN...' part from 'FROM' clause

			// ... and any user-specific fields are part of the WHERE clause...
			if ((eregi(".+search.php",$referer) OR eregi("^RSS$",$displayType)) AND (eregi("WHERE.+(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)",$sqlQuery))) // if a user who's NOT logged in tries to query user-specific fields (by use of 'sql_search.php')...
			// Note that the script 'show.php' may query the user-specific field 'selected' (e.g., by URLs of the form: 'show.php?author=...&userID=...&only=selected')
			// but since (in that case) the '$referer' variable is either empty or does not end with 'search.php' this if clause will not apply (which is ok since we want to allow 'show.php' to query the 'selected' field).
			// The same applies in the case of 'sru.php' which may query the user-specific field 'cite_key' (e.g., by URLs like: 'sru.php?version=1.1&query=bib.citekey=...&x-info-2-auth1.0-authenticationToken=email=...')
			// Note that this also implies that a user who's not logged in might perform a query such as: 'http://localhost/refs/show.php?cite_key=...&userID=...'
			{
				// Note: in the patterns below we'll attempt to account for parentheses but this won't catch all cases!
				$sqlQuery = preg_replace("/WHERE( *\( *?)* *(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related).+?(?= AND| ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$)/i","WHERE\\1",$sqlQuery); // ...delete any user-specific fields from 'WHERE' clause
				$sqlQuery = preg_replace("/( *\( *?)*( *AND)? *(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related).+?(?=( *\) *?)* +(AND|ORDER BY|LIMIT|GROUP BY|HAVING|PROCEDURE|FOR UPDATE|LOCK IN|$))/i","\\1",$sqlQuery); // ...delete any user-specific fields from 'WHERE' clause
				$sqlQuery = preg_replace("/WHERE( *\( *?)* *AND/i","WHERE\\1",$sqlQuery); // ...delete any superfluous 'AND' that wasn't removed properly by the two regex patterns above
				$sqlQuery = preg_replace("/WHERE( *\( *?)*(?= ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$)/i","WHERE serial RLIKE \".+\"",$sqlQuery); // ...supply generic 'WHERE' clause if it did ONLY contain user-specific fields

				// save an appropriate error message:
				$HeaderString = "<b><span class=\"warning\">Querying of user-specific fields was omitted!</span></b>"; // save an appropriate error message

				// Write back session variable:
				saveSessionVariable("HeaderString", $HeaderString);
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
					$sqlQuery = eregi_replace("location RLIKE [^ ]+","location RLIKE " . quote_smart($loginEmail),$sqlQuery); // ...replace any other user email address with the login email address of the currently logged in user
				}
			}

			// if we're going to display record details for a logged in user, we have to ensure the display of the 'location' field as well as the user-specific fields (which may have been deleted from a query due to a previous logout action);
			// in 'Display Details' view, the 'call_number' and 'serial' fields are the last generic fields before any user-specific fields:
			if ((eregi("^(Display|Export)$",$displayType)) AND (eregi(", call_number, serial FROM $tableRefs",$sqlQuery))) // if the user-specific fields are missing from the SELECT statement...
				$sqlQuery = eregi_replace(", call_number, serial FROM $tableRefs",", call_number, serial, marked, copy, selected, user_keys, user_notes, user_file, user_groups, cite_key, related FROM $tableRefs",$sqlQuery); // ...add all user-specific fields to the 'SELECT' clause

			// in 'Display Details' view, the 'location' field should occur within the SELECT statement before the 'call_number' and 'serial' fields:
			if ((eregi("^(Display|Export)$",$displayType)) AND (preg_match("/(?<!location,) call_number, serial(?=(, marked, copy, selected, user_keys, user_notes, user_file, user_groups, cite_key, related)? FROM $tableRefs)/i",$sqlQuery))) // if the 'location' field is missing from the SELECT statement...
				$sqlQuery = preg_replace("/(?<!location), call_number, serial(?=(, marked, copy, selected, user_keys, user_notes, user_file, user_groups, cite_key, related)? FROM $tableRefs)/i",", location, call_number, serial",$sqlQuery); // ...add the 'location' field to the 'SELECT' clause

			if ((eregi("^(Display|Export|RSS)$",$displayType)) AND (!eregi("LEFT JOIN $tableUserData",$sqlQuery))) // if the 'LEFT JOIN...' statement isn't already part of the 'FROM' clause...
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
	function stripFieldFromSQLQuery($sqlQuery, $field, $issueWarning = true)
	{
		// note that, upon multiple warnings, only the last warning message will be displayed

		// if the given '$field' is part of the SELECT or ORDER BY statement...
		if (eregi("(SELECT |ORDER BY |, *)" . $field, $sqlQuery))
		{
			// if the 'SELECT' clause contains '$field':
			if ($issueWarning AND (preg_match("/SELECT(.(?!FROM))+?" . $field . "/i", $sqlQuery)))
			{
				// save an appropriate error message:
				$HeaderString = "<b><span class=\"warning\">Display of " . $field . " field was omitted!</span></b>";
				// note: we don't write out any error message if the given '$field' does only occur within the 'ORDER' clause (but not within the 'SELECT' clause)

				// Write back session variable:
				saveSessionVariable("HeaderString", $HeaderString);
			}

			$sqlQuery = eregi_replace("(SELECT|ORDER BY) " . $field . "( DESC)?", "\\1 ", $sqlQuery); // ...delete '$field' from beginning of 'SELECT' or 'ORDER BY' clause
			$sqlQuery = eregi_replace(", *" . $field . "( DESC)?", "", $sqlQuery); // ...delete any other occurrences of '$field' from 'SELECT' or 'ORDER BY' clause
			$sqlQuery = eregi_replace("(SELECT|ORDER BY) *, *", "\\1 ", $sqlQuery); // ...remove any field delimiters that directly follow the 'SELECT' or 'ORDER BY' terms

			$sqlQuery = preg_replace("/SELECT *(?=FROM)/i", "SELECT author, title, year, publication, volume, pages ", $sqlQuery); // ...supply generic 'SELECT' clause if it did ONLY contain the given '$field'
			$sqlQuery = preg_replace("/ORDER BY *(?=LIMIT|GROUP BY|HAVING|PROCEDURE|FOR UPDATE|LOCK IN|$)/i", "ORDER BY author, year DESC, publication", $sqlQuery); // ...supply generic 'ORDER BY' clause if it did ONLY contain the given '$field'
		}

		// if the given '$field' is part of the WHERE clause...
		if (eregi("WHERE.+" . $field, $sqlQuery)) // this simple pattern works since we have already stripped any instance(s) of the given '$field' from the ORDER BY clause
		{
			// Note: in the patterns below we'll attempt to account for parentheses but this won't catch all cases!
			$sqlQuery = preg_replace("/WHERE( *\( *?)* *" . $field . ".+?(?= AND| ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$)/i", "WHERE\\1", $sqlQuery); // ...delete '$field' from 'WHERE' clause
			$sqlQuery = preg_replace("/( *\( *?)*( *AND)? *" . $field . ".+?(?=( *\) *?)* +(AND|ORDER BY|LIMIT|GROUP BY|HAVING|PROCEDURE|FOR UPDATE|LOCK IN|$))/i", "\\1", $sqlQuery); // ...delete '$field' from 'WHERE' clause
			$sqlQuery = preg_replace("/WHERE( *\( *?)* *AND/i","WHERE\\1",$sqlQuery); // ...delete any superfluous 'AND' that wasn't removed properly by the two regex patterns above
			$sqlQuery = preg_replace("/WHERE( *\( *?)*(?= ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$)/i", "WHERE serial RLIKE \".+\"", $sqlQuery); // ...supply generic 'WHERE' clause if it did ONLY contain the given '$field'

			if ($issueWarning)
			{
				// save an appropriate error message:
				$HeaderString = "<b><span class=\"warning\">Querying of " . $field . " field was omitted!</span></b>"; // save an appropriate error message

				// Write back session variable:
				saveSessionVariable("HeaderString", $HeaderString);
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

	// generate a RFC-2822 formatted date from MySQL date & time fields:
	function generateUNIXTimeStamp($mysqlDate, $mysqlTime)
	{
		$dateArray = split("-", $mysqlDate); // split MySQL-formatted date string (e.g. "2004-09-27") into its pieces (year, month, day)

		$timeArray = split(":", $mysqlTime); // split MySQL-formatted time string (e.g. "23:58:23") into its pieces (hours, minutes, seconds)

		$timeStamp = mktime($timeArray[0], $timeArray[1], $timeArray[2], $dateArray[1], $dateArray[2], $dateArray[0]);

		$rfc2822date = date('r', $timeStamp);

		return $rfc2822date;
	}

	// --------------------------------------------------------------------

	// generate an email address from MySQL 'created_by' fields that conforms
	// to the RFC-2822 specifications (<http://www.faqs.org/rfcs/rfc2822.html>):
	function generateRFC2822EmailAddress($createdBy)
	{
		// Note that the following patterns don't attempt to do fancy parsing of email addresses but simply assumes the string format
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
		$sqlSearchReplacePatterns = array(" != "                         =>  " is not equal to ",
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
		$translatedSQL = searchReplaceText($sqlSearchReplacePatterns, $translatedSQL, false); // function 'searchReplaceText()' is defined in 'include.inc.php'

		$translatedSQL = str_replace('"',"'",$translatedSQL); // replace any remaining " with '

		return $translatedSQL;
	}

	// --------------------------------------------------------------------

	// Extract the 'WHERE' clause from an SQL query:
	function extractWhereClause($query)
	{
		// Note: we include the SQL commands SELECT/INSERT/UPDATE/DELETE in an attempt to sanitize a given WHERE clause from SQL injection attacks
		$queryWhereClause = preg_replace("/^.+?WHERE (.+?)(?= ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|[ ;]SELECT|[ ;]INSERT|[ ;]UPDATE|[ ;]DELETE|$).*?$/i","\\1",$query);

		return $queryWhereClause;
	}

	// --------------------------------------------------------------------

	// Generate an URL pointing to a RSS feed:
	function generateRSSURL($queryWhereClause, $showRows)
	{
		$rssURL = "rss.php?where=" . rawurlencode($queryWhereClause) . "&amp;showRows=" . $showRows;

		return $rssURL;
	}

	// --------------------------------------------------------------------

	// Generate RSS XML data from a particular result set (upto the limit given in '$showRows'):
	function generateRSS($result, $showRows, $rssChannelDescription)
	{
		global $officialDatabaseName; // these variables are defined in 'ini.inc.php'
		global $databaseBaseURL;
		global $feedbackEmail;
		global $defaultCiteStyle;
		global $contentTypeCharset;

		global $transtab_refbase_html; // defined in 'transtab_refbase_html.inc.php'

		// Note that we only convert those entities that are supported by XML (by use of the 'encodeHTMLspecialchars()' function).
		// All other higher ASCII chars are left unencoded and valid feed output is only possible if the '$contentTypeCharset' variable is set correctly in 'ini.inc.php'.
		// (The only exception is the item description which will contain HTML tags & entities that were defined by '$transtab_refbase_html' or by the 'reArrangeAuthorContents()' function)

		// Define inline text markup to be used by the 'citeRecord()' function:
		$markupPatternsArray = array("bold-prefix"     => "<b>",
									"bold-suffix"      => "</b>",
									"italic-prefix"    => "<i>",
									"italic-suffix"    => "</i>",
									"underline-prefix" => "<u>",
									"underline-suffix" => "</u>",
									"endash"           => "&#8211;",
									"emdash"           => "&#8212;");

		$currentDateTimeStamp = date('r'); // get the current date & time (in UNIX time stamp format => "date('D, j M Y H:i:s O')")

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
					. "\n\t\t\t<url>" . $databaseBaseURL . "img/logo.gif</url>"
					. "\n\t\t\t<title>" . encodeHTMLspecialchars($officialDatabaseName) . "</title>"
					. "\n\t\t\t<link>" . $databaseBaseURL . "</link>"
					. "\n\t\t</image>";

		// fetch results: upto the limit specified in '$showRows', fetch a row into the '$row' array and write out a RSS item:
		for ($rowCounter=0; (($rowCounter < $showRows) && ($row = @ mysql_fetch_array($result))); $rowCounter++)
		{
			$origTitle = $row['title']; // save the original title contents before applying any search & replace actions

			// Perform search & replace actions on the text of the 'title' field:
			// (the array '$transtab_refbase_html' in 'transtab_refbase_html.inc.php' defines which search & replace actions will be employed)
			$row['title'] = searchReplaceText($transtab_refbase_html, $row['title'], true); // function 'searchReplaceText()' is defined in 'include.inc.php'
			// this will provide for correct rendering of italic, super/sub-script and greek letters in item descriptions (which are enclosed by '<![CDATA[...]]>' to ensure well-formed XML);
			// item titles are still served in raw format, though, since the use of HTML in item titles breaks many news readers

			$citeStyleFile = getStyleFile($defaultCiteStyle); // fetch the name of the citation style file that's associated with the style given in '$defaultCiteStyle' (which, in turn, is defined in 'ini.inc.php')

			// include the found citation style file *once*:
			include_once "cite/" . $citeStyleFile; // instead of 'include_once' we could also use: 'if ($rowCounter == 0) { include "cite/" . $citeStyleFile; }'

			// Generate a proper citation for this record, ordering attributes according to the chosen output style & record type:
			$record = citeRecord($row, $defaultCiteStyle, "", $markupPatternsArray, true); // function 'citeRecord()' is defined in the citation style file given in '$citeStyleFile' (which, in turn, must reside in the 'styles' directory of the refbase root directory)

			// append a RSS item for the current record:
			$rssData .= "\n\n\t\t<item>"

						. "\n\t\t\t<title>" . encodeHTMLspecialchars($origTitle) . "</title>" // we avoid embedding HTML in the item title and use the raw title instead

						. "\n\t\t\t<link>" . $databaseBaseURL . "show.php?record=" . $row['serial'] . "</link>"

						. "\n\t\t\t<description><![CDATA[" . $record

						. "\n\t\t\t<br><br>Edited by " . encodeHTMLspecialchars($row['modified_by']) . " on " . generateUNIXTimeStamp($row['modified_date'], $row['modified_time']) . ".]]></description>"

						. "\n\t\t\t<guid isPermaLink=\"true\">" . $databaseBaseURL . "show.php?record=" . $row['serial'] . "</guid>"

						. "\n\t\t\t<pubDate>" . generateUNIXTimeStamp($row['created_date'], $row['created_time']) . "</pubDate>"

						. "\n\t\t\t<author>" . generateRFC2822EmailAddress($row['created_by']) . "</author>"

						. "\n\t\t</item>";
		}

		// finish RSS data:
		$rssData .=  "\n\n\t</channel>"
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

		$result = queryMySQLDatabase($query, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

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
			$result = queryMySQLDatabase($query, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

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
