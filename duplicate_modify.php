<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./duplicate_modify.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    27-Jan-07, 23:22
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This php script will flag records as original and duplicate records.
	// It then displays the affected records using 'search.php' so that the user
	// can verify the changes.
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
	
	// Write the form variables into an array:
	foreach($_REQUEST as $varname => $value)
		$formVars[$varname] = trim($value); // remove any leading or trailing whitespace from the field's contents & copy the trimmed string to the '$formVars' array
//		$formVars[$varname] = trim(clean($value, 50)); // the use of the clean function would be more secure!

	// --------------------------------------------------------------------

	// Extract form variables:
	// Note: Although we could use the '$formVars' array directly below (e.g.: $formVars['origRecord'] etc., like in 'user_validation.php'), we'll read out
	//       all variables individually again. This is done to enhance readability. (A smarter way of doing so seems be the use of the 'extract()' function, but that
	//       may expose yet another security hole...)

	// First of all, check if this script was called by something else than 'duplicate_manager.php':
	if (!ereg(".+/duplicate_manager.php", $_SERVER['HTTP_REFERER']))
	{
		// return an appropriate error message:
		$HeaderString = returnMsg($loc["Warning_InvalidCallToScript"] . " '" . scriptURL() . "'!", "warning", "strong", "HeaderString"); // functions 'returnMsg()' and 'scriptURL()' are defined in 'include.inc.php'
		
		if (!empty($_SERVER['HTTP_REFERER'])) // if the referer variable isn't empty
			header("Location: " . $_SERVER['HTTP_REFERER']); // redirect to calling page
		else
			header("Location: index.php"); // redirect to main page ('index.php')

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// Extract the form used by the user:
	$formType = $formVars['formType'];

	// Extract the view type requested by the user (either 'Mobile', 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($formVars['viewType']))
		$viewType = $formVars['viewType'];
	else
		$viewType = "";

	// Extract other form values provided by 'duplicate_manager.php':
	if (isset($formVars['origRecord']))
		$origRecord = $formVars['origRecord'];
	else
		$origRecord = "";

	if (isset($formVars['dupRecords']))
		$dupRecords = $formVars['dupRecords'];
	else
		$dupRecords = "";

	// Extract serial numbers (i.e. discard any non-digit characters from the original user input):
	$origRecordSerial = preg_replace("/\D*(\d+).*/", "\\1", $origRecord); // extract the first number given
	$dupRecordSerialsArray = preg_split("/\D+/", $dupRecords, -1, PREG_SPLIT_NO_EMPTY); // extract all given serial numbers (the 'PREG_SPLIT_NO_EMPTY' flag causes only non-empty pieces to be returned)

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// VALIDATE data fields:

	// NOTE: for all fields that are validated here must exist error parsing code (of the form: " . fieldError("origRecord", $errors) . ")
	//       in front of the respective <input> form field in 'duplicate_manager.php'! Otherwise the generated error won't be displayed!

	// Validate the 'Original' field:
	if (empty($origRecord) OR !preg_match("/\d/", $origRecord))
		$errors["origRecord"] = "You must specify a serial number for the original record:"; // 'origRecord' must not be empty and must contain a number

	elseif (preg_match("/\d\D+\d/", $origRecord))
		$errors["origRecord"] = "You can only specify a single record as original entry:"; // only one serial number must be given
	
	elseif (in_array($origRecordSerial, $dupRecordSerialsArray))
		$errors["origRecord"] = "The original record cannot be one of the duplicate records:"; // the serial number of the original record must not be given within the list of duplicate serial numbers
	
	// Validate the 'Duplicates' field:
	if (empty($dupRecords) OR !preg_match("/\d/", $dupRecords))
		$errors["dupRecords"] = "You must specify at least one serial number that identifies a duplicate record:"; // 'dupRecords' must not be empty and at least one serial number must be given
	
	// --------------------------------------------------------------------

	// Now the script has finished the validation, check if there were any errors:
	if (count($errors) > 0)
	{
		// Write back session variables:
		saveSessionVariable("errors", $errors); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		saveSessionVariable("formVars", $formVars);

		// There are errors. Relocate back to the 'Flag Duplicates' form (script 'duplicate_manager.php'):
		header("Location: " . $_SERVER['HTTP_REFERER']);

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// If we made it here, then the data is considered valid!

	// CONSTRUCT SQL QUERY:

	// UPDATE field 'orig_record' in table 'refs':
	// original record:
	$queryArray[] = "UPDATE $tableRefs SET "
					. "orig_record = -" . $origRecordSerial
					. " WHERE serial = " . $origRecordSerial;

	// duplicate record(s):
	$queryArray[] = "UPDATE $tableRefs SET "
					. "orig_record = " . $origRecordSerial
					. " WHERE serial RLIKE \"^(" . implode("|", $dupRecordSerialsArray) . ")$\"";

	// --------------------------------------------------------------------

	// (3) RUN QUERY, (4) DISPLAY HEADER & RESULTS

	// (3) RUN the queries on the database through the connection:
	foreach($queryArray as $query)
		$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

	$affectedRows = ($result ? mysql_affected_rows ($connection) : 0); // get the number of rows that were modified (or return 0 if an error occurred)

	if ($affectedRows == 0) // no rows were affected by the update
	{
		// we'll file this additional error element here so that the 'errors' session variable isn't empty causing 'duplicate_manager.php' to re-load the form data that were submitted by the user
		$errors["ignoredRecords"] = "all";

		// return an appropriate error message:
		$HeaderString = returnMsg("Nothing was changed by your query!", "warning", "strong", "HeaderString"); // function 'returnMsg()' is defined in 'include.inc.php'

		// Write back session variables:
		saveSessionVariable("errors", $errors); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		saveSessionVariable("formVars", $formVars);
		
		// Relocate back to the 'Flag Duplicates' form (script 'duplicate_manager.php'):
		header("Location: " . $_SERVER['HTTP_REFERER']);

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// Build correct header message:
	$HeaderString = returnMsg("The records below have been successfully flagged as original/duplicate records:", "", "", "HeaderString"); // function 'returnMsg()' is defined in 'include.inc.php'


	// Merge all given record serial numbers:
	$allRecordSerialsString = $origRecordSerial . "," . implode(",", $dupRecordSerialsArray);

	// (4) Call 'show.php' which will display all affected records along with the header message
	//     (routing feedback output to a different script page will avoid any reload problems effectively!)
	header("Location: show.php?records=" . $allRecordSerialsString);

	// --------------------------------------------------------------------

	// (5) CLOSE CONNECTION
	disconnectFromMySQLDatabase(); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------
?>
