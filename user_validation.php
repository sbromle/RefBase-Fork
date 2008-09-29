<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./user_validation.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    16-Apr-02, 10:54
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This script validates user data entered into the form that is provided by 'user_details.php'.
	// If validation succeeds, it INSERTs or UPDATEs a user and redirects to a receipt page;
	// if it fails, it creates error messages and these are later displayed by 'user_details.php'.
	// TODO: I18n


	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
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

	// Clear any errors that might have been found previously:
	$errors = array();

	// Write the (POST) form variables into an array:
	foreach($_POST as $varname => $value)
		$formVars[$varname] = $value;
//		$formVars[$varname] = trim(clean($value, 50)); // the use of the clean function would be more secure!

	// --------------------------------------------------------------------

	// First of all, check if this script was called by something else than 'user_details.php':
	if (!eregi("/user_details\.php", $referer)) // variable '$referer' is globally defined in function 'start_session()' in 'include.inc.php'
	{
		// return an appropriate error message:
		$HeaderString = returnMsg($loc["Warning_InvalidCallToScript"] . " '" . scriptURL() . "'!", "warning", "strong", "HeaderString"); // functions 'returnMsg()' and 'scriptURL()' are defined in 'include.inc.php'

		header("Location: " . $referer); // redirect to calling page

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// Validate the First Name
	if (empty($formVars["firstName"]))
		// First name cannot be a null string
		$errors["firstName"] = "The first name field cannot be blank:";

//	elseif (ereg("\(" . $adminLoginEmail . "\)$", empty($formVars["firstName"]))

//	elseif (!eregi("^[a-z'-]*$", $formVars["firstName"]))
//		// First name cannot contain white space
//		$errors["firstName"] = "The first name can only contain alphabetic characters or \"-\" or \"'\":";

	elseif (strlen($formVars["firstName"]) > 50)
		$errors["firstName"] = "The first name can be no longer than 50 characters:";


	// Validate the Last Name
	if (empty($formVars["lastName"]))
		// the user's last name cannot be a null string
		$errors["lastName"] = "The last name field cannot be blank:";

	elseif (strlen($formVars["lastName"]) > 50)
		$errors["lastName"] = "The last name can be no longer than 50 characters:";


	// Validate the Institution
	if (strlen($formVars["institution"]) > 255)
		$errors["institution"] = "The institution name can be no longer than 255 characters:";


	// Validate the Institutional Abbreviation
	if (empty($formVars["abbrevInstitution"]))
		// the institutional abbreviation cannot be a null string
		$errors["abbrevInstitution"] = "The institutional abbreviation field cannot be blank:";

	elseif (strlen($formVars["abbrevInstitution"]) > 25)
		$errors["abbrevInstitution"] = "The institutional abbreviation can be no longer than 25 characters:";


	// Validate the Corporate Institution
	if (strlen($formVars["corporateInstitution"]) > 255)
		$errors["corporateInstitution"] = "The corporate institution name can be no longer than 255 characters:";


	// Validate the Address
//	if (empty($formVars["address1"]) && empty($formVars["address2"]) && empty($formVars["address3"]))
//		// all the fields of the address cannot be null
//		$errors["address"] = "You must supply at least one address line:";
//	else
//	{
		if (strlen($formVars["address1"]) > 50)
			$errors["address1"] = "The address line 1 can be no longer than 50 characters:";
		if (strlen($formVars["address2"]) > 50)
			$errors["address2"] = "The address line 2 can be no longer than 50 characters:";
		if (strlen($formVars["address3"]) > 50)
			$errors["address3"] = "The address line 3 can be no longer than 50 characters:";
//	}


	// Validate the City
//	if (empty($formVars["city"]))
//		// the user's city cannot be a null string
//		$errors["city"] = "You must supply a city:";
	if (strlen($formVars["city"]) > 40)
		$errors["city"] = "The city can be no longer than 40 characters:";


	// Validate State - any string less than 51 characters
	if (strlen($formVars["state"]) > 50)
		$errors["state"] = "The state can be no longer than 50 characters:";


	// Validate Zip code
//	if (!ereg("^([0-9]{4,5})$", $formVars["zipCode"]))
//		$errors["zipCode"] = "The zip code must be 4 or 5 digits in length:";
	if (strlen($formVars["zipCode"]) > 25)
		$errors["zipCode"] = "The zip code can be no longer than 25 characters:";


	// Validate Country
	if (strlen($formVars["country"]) > 40)
		$errors["country"] = "The country can be no longer than 40 characters:";


	// Validate Phone
	if (strlen($formVars["phone"]) > 50)
		$errors["phone"] = "The phone number can be no longer than 50 characters:";

	elseif (!empty($formVars["phone"]) && !eregi("^[0-9 /+-]+$", $formVars["phone"])) // '+49 431/600-1233' would be a valid format
		// The phone must match the above regular expression (i.e., it should only consist out of digits, the characters '/+-' and a space)
		$errors["phone"] = "The phone number must consist out of digits plus the optional characters '+/- ',\n\t\t<br>\n\t\te.g., '+49 431/600-1233' would be a valid format:";

//	// Phone is optional, but if it is entered it must have correct format
//	$validPhoneExpr = "^([0-9]{2,3}[ ]?)?[0-9]{4}[ ]?[0-9]{4}$";

//	if (!empty($formVars["phone"]) && !ereg($validPhoneExpr, $formVars["phone"]))
//		$errors["phone"] = "The phone number must be 8 digits in length, with an optional 2 or 3 digit area code:";


	// Validate URL
	if (strlen($formVars["url"]) > 255)
		$errors["url"] = "The URL can be no longer than 255 characters:";


	// Only validate email if this is an INSERT:
	// Validation is triggered for NEW USERS (visitors who aren't logged in) as well as the ADMIN
	// (the email field isn't shown to logged in non-admin-users anyhow)
	if (!isset($_SESSION['loginEmail']) | (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail) && ($_REQUEST['userID'] == "")))
	{
		// Check syntax
		$validEmailExpr = "^[0-9a-z~!#$%&_-]([.]?[0-9a-z~!#$%&_-])*@[0-9a-z~!#$%&_-]([.]?[0-9a-z~!#$%&_-])*$";

		if (empty($formVars["email"]))
			// the user's email cannot be a null string
			$errors["email"] = "You must supply an email address:";

		elseif (!eregi($validEmailExpr, $formVars["email"]))
			// The email must match the above regular expression
			$errors["email"] = "The email address must be in the name@domain format:";

		elseif (strlen($formVars["email"]) > 50)
			// The length cannot exceed 50 characters
			$errors["email"] = "The email address can be no longer than 50 characters:";

//		elseif (!(getmxrr(substr(strstr($formVars["email"], '@'), 1), $temp)) || checkdnsrr(gethostbyname(substr(strstr($formVars["email"], '@'), 1)), "ANY"))
//			// There must be a Domain Name Server (DNS) record for the domain name
//			$errors["email"] = "The domain does not exist:";

		else // Check if the email address is already in use in the database:
		{
			$query = "SELECT * FROM $tableAuth WHERE email = " . quote_smart($formVars["email"]); // CONSTRUCT SQL QUERY

			// (3) RUN the query on the database through the connection:
			$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

			if (mysql_num_rows($result) == 1) // (4) Interpret query result: Is it taken?
				$errors["email"] = "A user already exists with this email address as login name.\n\t\t<br>\n\t\tPlease enter a different one:";
		}
	}

	// If this was an INSERT, we do not allow the password field to be blank:
	// Validation is triggered for NEW USERS (visitors who aren't logged in) as well as the ADMIN
	if (!isset($_SESSION['loginEmail']) | (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail) && ($_REQUEST['userID'] == "")))
		if (empty($formVars["loginPassword"]))
			// Password cannot be a null string
			$errors["loginPassword"] = "The password field cannot be blank:";

	if ($formVars["loginPassword"] != $formVars["loginPasswordRetyped"])
		$errors["loginPassword"] = "You typed <em>two</em> different passwords! Please make sure\n\t\t<br>\n\t\tthat you type your password correctly:";

	elseif (strlen($formVars["loginPassword"]) > 15)
		$errors["loginPassword"] = "The password can be no longer than 15 characters:";

	// alternatively, only validate password if it's length is between 6 and 8 characters
//	elseif (!isset($_SESSION['loginEmail']) && (strlen($formVars["loginPassword"]) < 6 || strlen($formVars["loginPassword"] > 8)))
//		$errors["loginPassword"] = "The password must be between 6 and 8 characters in length:";

	// --------------------------------------------------------------------

	// Now the script has finished the validation, check if there were any errors:
	if (count($errors) > 0)
	{
		// Write back session variables:
		saveSessionVariable("errors", $errors); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		saveSessionVariable("formVars", $formVars);

		// There are errors. Relocate back to the client form:
		header("Location: user_details.php?userID=" . $_REQUEST['userID']); // 'userID' got included as hidden form tag by 'user_details.php' (for new users 'userID' will be empty but will get ignored by 'INSERT...' anyhow)

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// If we made it here, then the data is valid!

	// CONSTRUCT SQL QUERY:
	// First, setup some required variables:
	// Get the current date (e.g. '2003-12-31'), time (e.g. '23:59:49') and user name & email address (e.g. 'Matthias Steffens (refbase@extracts.de)'):
	list ($currentDate, $currentTime, $currentUser) = getCurrentDateTimeUser(); // function 'getCurrentDateTimeUser()' is defined in 'include.inc.php'

	// If a user is logged in and has submitted 'user_details.php' with a 'userID' parameter:
	// (while the admin has no restrictions, a normal user can only submit 'user_details.php' with his own 'userID' as parameter!)
	if (isset($_SESSION['loginEmail']) && ($_REQUEST['userID'] != "")) // -> perform an update:
	{
		if ($loginEmail != $adminLoginEmail) // if not admin logged in
			$userID = getUserID($loginEmail); // Get the 'user_id' using 'loginEmail' (function 'getUserID()' is defined in 'include.inc.php')
		else // if the admin is logged in he should be able to make any changes to account data of _other_ users...
			$userID = $_REQUEST['userID']; // ...in this case we accept 'userID' from the GET/POST request (it got included as hidden form tag by 'user_details.php')

		// UPDATE - construct a query to update the relevant record
		$query = "UPDATE $tableUsers SET "
				. "first_name = " . quote_smart($formVars["firstName"])
				. ", last_name = " . quote_smart($formVars["lastName"])
				. ", title = " . quote_smart($formVars["title"])
				. ", institution = " . quote_smart($formVars["institution"])
				. ", abbrev_institution = " . quote_smart($formVars["abbrevInstitution"])
				. ", corporate_institution = " . quote_smart($formVars["corporateInstitution"])
				. ", address_line_1 = " . quote_smart($formVars["address1"])
				. ", address_line_2 = " . quote_smart($formVars["address2"])
				. ", address_line_3 = " . quote_smart($formVars["address3"])
				. ", zip_code = " . quote_smart($formVars["zipCode"])
				. ", city = " . quote_smart($formVars["city"])
				. ", state = " . quote_smart($formVars["state"])
				. ", country = " . quote_smart($formVars["country"])
				. ", phone = " . quote_smart($formVars["phone"])
				. ", url = " . quote_smart($formVars["url"]);

		if (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail))
		{
			$query .= ", keywords = " . quote_smart($formVars["keywords"])
					. ", notes = " . quote_smart($formVars["notes"])
					. ", marked = " . quote_smart($formVars["marked"]);
		}

		if (isset($_SESSION['loginEmail']))
			$query .= ", modified_by = " . quote_smart($currentUser);

		$query .= ", modified_date = " . quote_smart($currentDate)
				. ", modified_time = " . quote_smart($currentTime);

		$query .= " WHERE user_id = " . quote_smart($userID);
	}
	// If an authorized user uses 'user_details.php' to add a new user (-> 'userID' is empty!):
	// INSERTs are allowed to:
	//         1. EVERYONE who's not logged in (but ONLY if variable '$addNewUsers' in 'ini.inc.php' is set to "everyone"!)
	//            (Note that this feature is actually only meant to add the very first user to the users table.
	//             After you've done so, it is highly recommended to change the value of '$addNewUsers' to 'admin'!)
	//   -or-  2. the ADMIN only (if variable '$addNewUsers' in 'ini.inc.php' is set to "admin")
	elseif ((!isset($_SESSION['loginEmail']) && ($addNewUsers == "everyone") && ($_REQUEST['userID'] == "")) | (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail) && ($_REQUEST['userID'] == ""))) // -> perform an insert:
	{
		// INSERT - construct a query to add data as new record
		$query = "INSERT INTO $tableUsers SET "
				. "first_name = " . quote_smart($formVars["firstName"])
				. ", last_name = " . quote_smart($formVars["lastName"])
				. ", title = " . quote_smart($formVars["title"])
				. ", institution = " . quote_smart($formVars["institution"])
				. ", abbrev_institution = " . quote_smart($formVars["abbrevInstitution"])
				. ", corporate_institution = " . quote_smart($formVars["corporateInstitution"])
				. ", address_line_1 = " . quote_smart($formVars["address1"])
				. ", address_line_2 = " . quote_smart($formVars["address2"])
				. ", address_line_3 = " . quote_smart($formVars["address3"])
				. ", zip_code = " . quote_smart($formVars["zipCode"])
				. ", city = " . quote_smart($formVars["city"])
				. ", state = " . quote_smart($formVars["state"])
				. ", country = " . quote_smart($formVars["country"])
				. ", phone = " . quote_smart($formVars["phone"])
				. ", url = " . quote_smart($formVars["url"]);

		if (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail))
		{
			$query .= ", keywords = " . quote_smart($formVars["keywords"])
					. ", notes = " . quote_smart($formVars["notes"])
					. ", marked = " . quote_smart($formVars["marked"]);
		}

		$query .= ", email = " . quote_smart($formVars["email"]);

		if (isset($_SESSION['loginEmail']))
			$query .= ", created_by = " . quote_smart($currentUser);

		$query .= ", created_date = " . quote_smart($currentDate)
				. ", created_time = " . quote_smart($currentTime);

		if (isset($_SESSION['loginEmail']))
			$query .= ", modified_by = " . quote_smart($currentUser);

		$query .= ", modified_date = " . quote_smart($currentDate)
				. ", modified_time = " . quote_smart($currentTime);

		$query .= ", language = \"" . $defaultLanguage . "\"" // '$defaultLanguage' is defined in 'ini.inc.php' (the language setting can be changed by the user in 'user_options.php')
				. ", last_login = NOW()" // set 'last_login' field to the current date & time in 'DATETIME' format (which is 'YYYY-MM-DD HH:MM:SS', e.g.: '2003-12-31 23:45:59')
				. ", logins = 1 "; // set the number of logins to 1 (so that any subsequent login attempt can be counted correctly)
	}
	// if '$addNewUsers' is set to 'admin': MAIL feedback to new user & send data to admin for approval:
	// no user is logged in (since 'user_details.php' cannot be called w/o a 'userID' by a logged in user,
	// 'user_details.php' must have been submitted by a NEW user!)
	elseif ($addNewUsers == "admin" && ($_REQUEST['userID'] == ""))
	{
		// First, we have to query for the proper admin name, so that we can include this name within the emails:
		$query = "SELECT first_name, last_name FROM $tableUsers WHERE email = " . quote_smart($adminLoginEmail); // CONSTRUCT SQL QUERY ('$adminLoginEmail' is specified in 'ini.inc.php')

		// (3a) RUN the query on the database through the connection:
		$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		$row = mysql_fetch_array($result); // (3b) EXTRACT results: fetch the current row into the array $row

		// 1) Mail feedback to user, i.e., send the person who wants to be added as new user a notification email:
		$emailRecipient = $formVars["firstName"] . " " . $formVars["lastName"] . " <" . $formVars["email"] . ">";
		$emailSubject = "Your request to participate at the " . $officialDatabaseName; // ('$officialDatabaseName' is specified in 'ini.inc.php')
		$emailBody = "Dear " . $formVars["firstName"] . " " . $formVars["lastName"] . ","
					. "\n\nthanks for your interest in the " . $officialDatabaseName . "!"
					. "\nThe data you provided have been sent to our database admin."
					. "\nWe'll process your request and mail back to you as soon as we can."
					. "\n\n--"
					. "\n" . $databaseBaseURL . "index.php"; // ('$databaseBaseURL' is specified in 'ini.inc.php')

		sendEmail($emailRecipient, $emailSubject, $emailBody);

		// 2) Send user data to admin for approval:
		$emailRecipient = $row["first_name"] . " " . $row["last_name"] . " <" . $adminLoginEmail . ">"; // ('$adminLoginEmail' is specified in 'ini.inc.php')
		$emailSubject = "User request to participate at the " . $officialDatabaseName; // ('$officialDatabaseName' is specified in 'ini.inc.php')
		$emailBody = "Dear " . $row["first_name"] . " " . $row["last_name"] . ","
					. "\n\nsomebody wants to join the " . $officialDatabaseName . ":"
					. "\n\n" . $formVars["firstName"] . " " . $formVars["lastName"] . " (" . $formVars["abbrevInstitution"] . ") submitted the form at"
					. "\n\n  <" . $databaseBaseURL . "user_details.php>"
					. "\n\nwith the data below:"
					. "\n\n  first name:                  " . $formVars["firstName"]
					. "\n  last name:                   " . $formVars["lastName"]
					. "\n  institution:                 " . $formVars["institution"]
					. "\n  institutional abbreviation:  " . $formVars["abbrevInstitution"]
					. "\n  corporate institution:       " . $formVars["corporateInstitution"]
					. "\n  address line 1:              " . $formVars["address1"]
					. "\n  address line 2:              " . $formVars["address2"]
					. "\n  address line 3:              " . $formVars["address3"]
					. "\n  zip code:                    " . $formVars["zipCode"]
					. "\n  city:                        " . $formVars["city"]
					. "\n  state:                       " . $formVars["state"]
					. "\n  country:                     " . $formVars["country"]
					. "\n  phone:                       " . $formVars["phone"]
					. "\n  url:                         " . $formVars["url"]
					. "\n  email:                       " . $formVars["email"]
					. "\n  password:                    " . $formVars["loginPassword"]
					. "\n\nPlease contact " . $formVars["firstName"] . " " . $formVars["lastName"] . " to approve the request."
					. "\n\n--"
					. "\n" . $databaseBaseURL . "index.php"; // ('$databaseBaseURL' is specified in 'ini.inc.php')

		sendEmail($emailRecipient, $emailSubject, $emailBody);

		header("Location: user_receipt.php?userID=-1"); // Note: we use the non-existing user ID '-1' as trigger to show the email notification receipt page (instead of the standard receipt page)
		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// (3) RUN the query on the database through the connection:
	$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

	// ----------------------------------------------

	// If this was an UPDATE - we save possible name changes to the session file (so that this new user name can be displayed by the 'showLogin()' function):
	if (isset($_SESSION['loginEmail']) && ($_REQUEST['userID'] != ""))
	{
		// We only save name changes if a normal user is logged in -OR- the admin is logged in AND the updated user data are his own!
		// (We have to account for that the admin is allowed to view and edit account data from other users)
		if (($loginEmail != $adminLoginEmail) | (($loginEmail == $adminLoginEmail) && ($userID == getUserID($loginEmail))))
		{
			$loginFirstName = $formVars["firstName"];
			$loginLastName = $formVars["lastName"];
		}

		// If the user provided a new password, we need to UPDATE also the 'auth' table (which contains the login credentials for each user):
		if ($formVars["loginPassword"] != "") // a new password was provided by the user...
		{
			// Use the first two characters of the email as a salt for the password
			// Note: The user's email is NOT included as a regular form field for UPDATEs. To make it available as 'salt'
			//       the user's email gets included as a hidden form tag by 'user_details.php'!
			$salt = substr($formVars["email"], 0, 2);

			// Create the encrypted password
			$stored_password = crypt($formVars["loginPassword"], $salt);

			// Update the user's password within the auth table
			$query = "UPDATE $tableAuth SET "
					. "password = " . quote_smart($stored_password)
					. " WHERE user_id = " . quote_smart($userID);

			$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'
		}
	}

	// If this was an INSERT, we'll also need to INSERT into the 'auth' table (which contains the login credentials for each user) as well as into some 'user_*' tables:
	// INSERTs are allowed to:
	//         1. EVERYONE who's not logged in (but ONLY if variable '$addNewUsers' in 'ini.inc.php' is set to "everyone"!)
	//            (Note that this feature is actually only meant to add the very first user to the users table.
	//             After you've done so, it is highly recommended to change the value of '$addNewUsers' to 'admin'!)
	//   -or-  2. the ADMIN only (if variable '$addNewUsers' in 'ini.inc.php' is set to "admin")
	elseif ((!isset($_SESSION['loginEmail']) && ($addNewUsers == "everyone") && ($_REQUEST['userID'] == "")) | (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail) && ($_REQUEST['userID'] == ""))) // -> perform an insert:
	{
		// Get the user id that was created
		$userID = @ mysql_insert_id($connection);

		// Use the first two characters of the email as a salt for the password
		$salt = substr($formVars["email"], 0, 2);

		// Create the encrypted password
		$stored_password = crypt($formVars["loginPassword"], $salt);

		// Insert a new user into the auth table
		$queryArray[] = "INSERT INTO $tableAuth SET "
						. "user_id = " . quote_smart($userID) . ", "
						. "email = " . quote_smart($formVars["email"]) . ", "
						. "password = " . quote_smart($stored_password);


		// Insert a row for this new user into the 'user_permissions' table:
		$defaultUserPermissionsString = implode("\", \"", $defaultUserPermissions); // '$defaultUserPermissions' is defined in 'ini.inc.php'
		// TODO: quote_smart()
		$queryArray[] = "INSERT INTO $tableUserPermissions VALUES (NULL, " . $userID . ", \"" . $defaultUserPermissionsString . "\")";


		// Note: Refbase lets you define default formats/styles/types in 'ini.inc.php' by their name (and not by ID numbers) which means that
		//       the format/style/type names within the 'formats/styles/types' table must be unique!

		foreach($defaultUserExportFormats as $defaultUserExportFormat)
		{
			// get the 'format_id' for the record entry in table 'formats' whose 'format_name' matches that in '$defaultUserExportFormats' (defined in 'ini.inc.php'):
			$query = "SELECT format_id FROM $tableFormats WHERE format_name = " . quote_smart($defaultUserExportFormat) . " AND format_type = 'export'";
			$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'
			$row = mysql_fetch_array($result);

			// Insert a row with the found format ID for this new user into the 'user_formats' table:
			$queryArray[] = "INSERT INTO $tableUserFormats VALUES (NULL, " . quote_smart($row["format_id"]) . ", " . quote_smart($userID) . ", \"true\")";
		}

		foreach($defaultUserCiteFormats as $defaultUserCiteFormat)
		{
			// get the 'format_id' for the record entry in table 'formats' whose 'format_name' matches that in '$defaultUserCiteFormats' (defined in 'ini.inc.php'):
			$query = "SELECT format_id FROM $tableFormats WHERE format_name = " . quote_smart($defaultUserCiteFormat) . " AND format_type = 'cite'";
			$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'
			$row = mysql_fetch_array($result);

			// Insert a row with the found format ID for this new user into the 'user_formats' table:
			$queryArray[] = "INSERT INTO $tableUserFormats VALUES (NULL, " . quote_smart($row["format_id"]) . ", " . quote_smart($userID) . ", \"true\")";
		}

		foreach($defaultUserStyles as $defaultUserStyle)
		{
			// get the 'style_id' for the record entry in table 'styles' whose 'style_name' matches that in '$defaultUserStyles' (defined in 'ini.inc.php'):
			$query = "SELECT style_id FROM $tableStyles WHERE style_name = " . quote_smart($defaultUserStyle);
			$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'
			$row = mysql_fetch_array($result);

			// Insert a row with the found style ID for this new user into the 'user_styles' table:
			$queryArray[] = "INSERT INTO $tableUserStyles VALUES (NULL, " . quote_smart($row["style_id"]) . ", " . quote_smart($userID) . ", \"true\")";
		}

		foreach($defaultUserTypes as $defaultUserType)
		{
			// get the 'type_id' for the record entry in table 'types' whose 'type_name' matches that in '$defaultUserTypes' (defined in 'ini.inc.php'):
			$query = "SELECT type_id FROM $tableTypes WHERE type_name = " . quote_smart($defaultUserType);
			$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'
			$row = mysql_fetch_array($result);

			// Insert a row with the found type ID for this new user into the 'user_types' table:
			$queryArray[] = "INSERT INTO $tableUserTypes VALUES (NULL, " . quote_smart($row["type_id"]) . ", " . quote_smart($userID) . ", \"true\")";
		}


		// Insert a row for this new user into the 'user_options' table:
		$defaultUserOptionsString = implode("\", \"", $defaultUserOptions); // '$defaultUserOptions' is defined in 'ini.inc.php'
		$defaultUserOptionsString = ereg_replace("\"\"", "NULL", $defaultUserOptionsString); // replace empty string with NULL
		// TODO: quote_smart()
		$queryArray[] = "INSERT INTO $tableUserOptions VALUES (NULL, " . $userID . ", \"" . $defaultUserOptionsString . "\")";


		// RUN the queries on the database through the connection:
		foreach($queryArray as $query)
			$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		// if EVERYONE who's not logged in is able to add a new user (which is the case if the variable '$addNewUsers' in 'ini.inc.php'
		// is set to "everyone", see note above!), then we have to make sure that this visitor gets logged into his new
		// account - otherwise the following receipt page ('users_receipt.php') will generate an error:
		if (!isset($_SESSION['loginEmail']) && ($addNewUsers == "everyone") && ($_REQUEST['userID'] == ""))
		{
			// Log the user into his new account by assigning his information to
			// those variables that will be written to the '$_SESSION' variable below:
			$loginEmail = $formVars["email"];
			$loginUserID = $userID;
			$loginFirstName = $formVars["firstName"];
			$loginLastName = $formVars["lastName"];
			$abbrevInstitution = $formVars["abbrevInstitution"];
			$lastLogin = date('Y-m-d H:i:s'); // use the current date & time

			// Get the user permissions for the newly created user
			// and save all allowed user actions as semicolon-delimited string to the session variable 'user_permissions':
			getPermissions($userID, "user", true); // function 'getPermissions()' is defined in 'include.inc.php'
		}
	}

	// Write back session variables:
	saveSessionVariable("loginEmail", $loginEmail); // function 'saveSessionVariable()' is defined in 'include.inc.php'
	saveSessionVariable("loginUserID", $loginUserID);
	saveSessionVariable("loginFirstName", $loginFirstName);
	saveSessionVariable("loginLastName", $loginLastName);
	saveSessionVariable("abbrevInstitution", $abbrevInstitution);
	saveSessionVariable("lastLogin", $lastLogin);

	// If an authorized user uses 'user_details.php' to add a new user (-> 'userID' is empty!):
	if ((!isset($_SESSION['loginEmail']) && ($addNewUsers == "everyone") && ($_REQUEST['userID'] == "")) | (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail) && ($_REQUEST['userID'] == "")))
	{
		saveSessionVariable("userLanguage", $defaultLanguage); // '$defaultLanguage' is defined in 'ini.inc.php'
		saveSessionVariable("userRecordsPerPage", $defaultUserOptions['records_per_page']); // '$defaultUserOptions' is defined in 'ini.inc.php'
		saveSessionVariable("userMainFields", $defaultUserOptions['main_fields']);
	}

	// Get all user groups specified by the current user
	// and (if some groups were found) save them as semicolon-delimited string to the session variable 'userGroups':
	getUserGroups($tableUserData, $loginUserID); // function 'getUserGroups()' is defined in 'include.inc.php'

	if ($loginEmail == $adminLoginEmail) // ('$adminLoginEmail' is specified in 'ini.inc.php')
		// Get all user groups specified by the admin
		// and (if some groups were found) save them as semicolon-delimited string to the session variable 'adminUserGroups':
		getUserGroups($tableUsers, $loginUserID); // function 'getUserGroups()' is defined in 'include.inc.php'

	// Similarly, get all queries that were saved previously by the current user
	// and (if some queries were found) save them as semicolon-delimited string to the session variable 'userQueries':
	getUserQueries($loginUserID); // function 'getUserQueries()' is defined in 'include.inc.php'

	// Clear the 'errors' and 'formVars' session variables so a future <form> is blank:
	deleteSessionVariable("errors"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	deleteSessionVariable("formVars");

	// ----------------------------------------------

	// (4) Now show the user RECEIPT:
	header("Location: user_receipt.php?userID=$userID");

	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase(); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------
?>
