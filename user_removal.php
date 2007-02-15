<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./user_removal.php
	// Created:    16-Apr-02, 10:54
	// Modified:   26-Sep-06, 08:35

	// This script deletes a user from the 'users' and 'auth' tables.
	// The script can be only called by the admin. If the removal succeeds, it redirects to 'users.php'.
	// Note that there's no further verification! If you clicked 'Delete User' on 'user_receipt.php' the user will be killed immediately.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/include.inc.php'; // include common functions
	include 'initialize/ini.inc.php'; // include common variables

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// Extract the 'userID' parameter from the request:
	if (isset($_REQUEST['userID']))
		$userID = $_REQUEST['userID'];
	else
		$userID = "";

	// Check if the admin is logged in
	if (!(isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail))) // ('$adminLoginEmail' is specified in 'ini.inc.php')
	{
		// save an error message:
		$HeaderString = "<b><span class=\"warning\">You must be logged in as admin to remove any users!</span></b>";

		// save the URL of the currently displayed page:
		$referer = $_SERVER['HTTP_REFERER'];

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		saveSessionVariable("referer", $referer);

		header("Location: index.php");
		exit;
	}

	// Check the correct parameters have been passed
	if (empty($userID))
	{
		// save an error message:
		$HeaderString = "<b><span class=\"warning\">Incorrect parameters to script 'user_removal.php'!</span></b>";

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

		// Redirect the browser back to the calling page
		header("Location: index.php"); // Note: if 'header("Location: " . $_SERVER['HTTP_REFERER'])' is used, the error message won't get displayed! ?:-/
		exit;
	}

	// --------------------------------------------------------------------

	// CONSTRUCT SQL QUERY:
	// If the admin is logged in:
	if (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail)) // -> perform a delete action:
	{
		// DELETE - construct queries to delete the relevant record(s)
		// ... from the users table:
		$queryArray[] = "DELETE FROM $tableUsers WHERE user_id = " . quote_smart($userID);

		// ... from the auth table:
		$queryArray[] = "DELETE FROM $tableAuth WHERE user_id = " . quote_smart($userID);

		// ... from the user_permissions table:
		$queryArray[] = "DELETE FROM $tableUserPermissions WHERE user_id =" . quote_smart($userID);

		// ... from the user_formats table:
		$queryArray[] = "DELETE FROM $tableUserFormats WHERE user_id =" . quote_smart($userID);

		// ... from the user_styles table:
		$queryArray[] = "DELETE FROM $tableUserStyles WHERE user_id =" . quote_smart($userID);

		// ... from the user_types table:
		$queryArray[] = "DELETE FROM $tableUserTypes WHERE user_id =" . quote_smart($userID);

		// ... from the user_options table:
		$queryArray[] = "DELETE FROM $tableUserOptions WHERE user_id =" . quote_smart($userID);
	}

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(""); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// (3) RUN the queries on the database through the connection:
	foreach($queryArray as $query)
		$result = queryMySQLDatabase($query, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

	// ----------------------------------------------

	// (4) File a message and go back to the list of users:
	// save an informative message:
	$HeaderString = "User was deleted successfully!";

	// Write back session variables:
	saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

	header("Location: users.php"); // re-direct to the list of users

	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase(""); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------
?>
