<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./user_logout.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    16-Apr-02, 10:54
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This script logs a user out and redirects 
	// to the calling page. If the script is called
	// unexpectedly, an error message is generated.


	// Incorporate some include files:
	include 'includes/include.inc.php'; // include common functions

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// --------------------------------------------------------------------

	if (isset($_REQUEST['referer']))
	{
		$referer = $_REQUEST['referer']; // get the referring URL from the superglobal '$_REQUEST' variable (if any)
	}
	elseif (isset($_SESSION['referer']))
	{
		$referer = $_SESSION['referer']; // get the referring URL from the superglobal '$_SESSION' variable (if any)
	}
	else // if '$referer' is still not set
	{
		if (isset($_SERVER['HTTP_REFERER']))
			$referer = $_SERVER['HTTP_REFERER'];
		else
			$referer = "index.php"; // if all other attempts fail, we'll re-direct to the main page
	}
	
	// Is the user logged in?
	if (isset($_SESSION['loginEmail']))
	{
		// Delete the 'loginEmail' session variable & other session variables we've registered on login:
		// (function 'deleteSessionVariable()' is defined in 'include.inc.php')
		deleteSessionVariable("loginEmail"); // remove the user's email address (as a result the user will be logged out)
		deleteSessionVariable("loginUserID"); // clear the user's user ID
		deleteSessionVariable("loginFirstName"); // clear the user's first name
		deleteSessionVariable("loginLastName"); // clear the user's last name
		deleteSessionVariable("abbrevInstitution"); // clear the user's abbreviated institution name
		deleteSessionVariable("userLanguage"); // clear the user's preferred language
		deleteSessionVariable("lastLogin"); // clear the user's last login date & time
	
		if (isset($_SESSION['userGroups']))
			deleteSessionVariable("userGroups"); // clear the user's user groups (if any)

		if (isset($_SESSION['userQueries']))
			deleteSessionVariable("userQueries"); // clear the user's saved queries (if any)

		if (isset($_SESSION['user_export_formats']))
			deleteSessionVariable("user_export_formats"); // clear the user's export formats (if any)

		if (isset($_SESSION['user_cite_formats']))
			deleteSessionVariable("user_cite_formats"); // clear the user's cite formats (if any)

		if (isset($_SESSION['user_styles']))
			deleteSessionVariable("user_styles"); // clear the user's styles (if any)

		if (isset($_SESSION['user_types']))
			deleteSessionVariable("user_types"); // clear the user's types (if any)

		if (isset($_SESSION['user_permissions']))
			deleteSessionVariable("user_permissions"); // clear any user-specific permissions

		if (isset($_SESSION['HeaderString']))
			deleteSessionVariable("HeaderString"); // clear any previous messages
	}
	else
	{
		// save an error message:
		$HeaderString = "<b><span class=\"warning\">You cannot logout since you are not logged in anymore!</span></b>";

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
	}

	if (!preg_match("/.*user(_details|_options|_receipt|s)\.php.*|.*(error|install|query_manager)\.php.*/", $referer))
		header("Location: $referer"); // redirect the user to the calling page
	else
		header("Location: index.php"); // back to main page
?>
