<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./import_template_base.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    11-Jan-06, 18:36
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// Template for a batch import script.
	// Use this script to develop your own batch importer.
	// See the scripts 'import.php' and 'import_modify.php' for a working example.


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

	// Get the referring page (or set a default one if no referrer is available):
	if (!empty($_SERVER['HTTP_REFERER'])) // if the referrer variable isn't empty
		$referer = $_SERVER['HTTP_REFERER']; // on error, redirect to calling page
	else
		$referer = "import.php"; // on error, redirect to the web import form (if you've got your own import form, insert it's script name here)

	// First of all, check if the user is logged in:
	if (!isset($_SESSION['loginEmail'])) // -> if the user isn't logged in
	{
		header("Location: user_login.php?referer=" . rawurlencode($referer)); // ask the user to login first, then he'll get directed back to the calling page

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// now, check if the (logged in) user is allowed to import any record into the database:
	if (isset($_SESSION['user_permissions']) AND !ereg("(allow_import|allow_batch_import)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does NOT contain either 'allow_import' or 'allow_batch_import'...
	{
		// return an appropriate error message:
		$HeaderString = returnMsg($loc["NoPermission"] . $loc["NoPermission_ForImport"] . "!", "warning", "strong", "HeaderString"); // function 'returnMsg()' is defined in 'include.inc.php'

		header("Location: index.php"); // redirect back to main page ('index.php')

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// PROCESS SOURCE DATA:

	$parsedRecordsArray = array(); // initialize array variable which will hold parsed data of all records that shall be imported

	// >>> insert your own code here that parses your source data into an array of arrays holding the extracted field data <<<

	// ToDo:
	// (1) Obtain your source data via a web form, file upload, direct read-in of a local source file, etc
	//     If your script allows input from the web, make sure to validate your data, see 'import.php' & 'import_modify.php' for an example
	// (2) Split your source data into individual bits representing individual records
	// (3) Loop over each record and extract the record's field data into an array;
	//     For each record you should end up with an array structure similar to the one below ('$recordFieldParametersArray'):

	// NOTES: - you can safely omit unneeded fields from your data array; for any fields that aren't present in the data array, the database will insert its default values
	//        - the 'addRecords()' function will take care of the calculation fields ('first_author', 'author_count', 'first_page', 'volume_numeric' and 'series_volume_numeric')
	//        - similarly, the *date/*time/*by fields ('created_date', 'created_time', 'created_by', 'modified_date', 'modified_time', 'modified_by') will be filled automatically
	//          if no custom values (in correct date ['YYYY-MM-DD'] and time ['HH:MM:SS'] format) are given in your array
	//        - you can pass any custom info for the 'location' field in your array; however, if you omit the 'location' field from the array
	//          the 'addRecords()' function will insert name & email address of the currently logged-in user (e.g. 'Matthias Steffens (refbase@extracts.de)')
	//        - the serial number(s) will be assigned automatically (and returned by the 'addRecords()' function in form of an array)
	//        - this import example will only add to the main MySQL table ('refs'), but not to the 'user_data' table

	// Example record data array. Commented fields will be filled automatically if not present (see notes above).
	// Comments behind field spec give the resulting calculation field values for this example:
	$recordFieldParametersArray = array(
										'author' => "FirstAuthor, Initials; SecondAuthor, Initials", // 'first_author' = "FirstAuthor, Initials"; 'author_count' = "2"
										'address' => "Address",
										'corporate_author' => "Corporate Author",
										'title' => "Title",
										'orig_title' => "Orig Title",
										'publication' => "Publication",
										'abbrev_journal' => "Abbrev Journal",
										'year' => "2005",
										'volume' => "2nd Volume", // 'volume_numeric' = "2"
										'issue' => "Issue",
										'pages' => "5 Pages", // 'first_page' = "5"
										'keywords' => "Keywords",
										'abstract' => "Abstract",
										'edition' => "2",
										'editor' => "Editor",
										'publisher' => "Publisher",
										'place' => "Place",
										'medium' => "Medium",
										'series_editor' => "Series Editor",
										'series_title' => "Series Title",
										'abbrev_series_title' => "Abbrev Series Title",
										'series_volume' => "3rd Series Volume", // 'series_volume_numeric' = "3"
										'series_issue' => "Series Issue",
										'issn' => "ISSN",
										'isbn' => "ISBN",
										'language' => "Language",
										'summary_language' => "Summary Language",
										'area' => "Area",
										'type' => "Type",
										'thesis' => "Diploma thesis",
										'expedition' => "Expedition",
										'doi' => "DOI",
										'conference' => "Conference",
										'url' => "URL",
										'call_number' => "Call Number",
	//									'location' => "Location",
										'contribution_id' => "Contribution Id",
										'online_publication' => "no",
										'online_citation' => "Online Citation",
										'file' => "File",
										'notes' => "Notes",
										'approved' => "no",
	//									'created_date' => "1999-11-30",
	//									'created_time' => "00:00:01",
	//									'created_by' => "Created By",
	//									'modified_date' => "1999-11-31",
	//									'modified_time' => "00:00:02",
	//									'modified_by' => "Modified By",
										'orig_record' => "-123"
										);

	// (4) Append the array of extracted field data to the main data array which holds all records to import:
	$parsedRecordsArray[] = $recordFieldParametersArray; // in this example, we simply import a single record, adopt to your needs

	$recordsCount = count($parsedRecordsArray); // count how many records are available

	// check if the current user has batch import permission:
	if (($recordsCount > 1) AND isset($_SESSION['user_permissions']) AND !ereg("allow_batch_import", $_SESSION['user_permissions'])) // if we're supposed to import several records BUT the 'user_permissions' session variable does NOT contain 'allow_batch_import'...
	{
		// return an appropriate error message:
		// (note that this error message will overwrite any '$headerMessage' that gets specified below)
		$HeaderString = returnMsg($loc["NoPermission"] . $loc["NoPermission_ForBatchImport"] . "!", "warning", "strong", "HeaderString", "", " " . $loc["Warning_OnlyFirstRecordImported"]) . ":"; // function 'returnMsg()' is defined in 'include.inc.php'

		array_splice($parsedRecordsArray, 1); // remove all but the first record from the array of records that shall be imported
	}

	// --------------------------------------------------------------------

	// IMPORT RECORDS:

	// Build an array structure suitable for passing to the 'addRecords()' function:
	$importDataArray = array(); // for an explanation of the structure of '$importDataArray', see the comments above the 'addRecords()' function (in 'include.inc.php')
	$importDataArray['type'] = "refbase"; // we use the "refbase" array format
	$importDataArray['version'] = "1.0"; // we use version "1.0" of the array structure
	$importDataArray['creator'] = "http://refbase.net"; // calling script/importer is "refbase" (insert the unique name of your importer here or give the web address of it's home page)
	$importDataArray['author'] = "Matthias Steffens"; // author/contact name of the person who's responsible for this script/importer (insert your own name here)
	$importDataArray['contact'] = "refbase@extracts.de"; // author's email/contact address (insert your email address here)
	$importDataArray['options'] = array('prefix_call_number' => "true"); // if "true", any 'call_number' string will be prefixed with the correct call number prefix of the currently logged-in user (e.g. 'IPÖ @ msteffens @ ')
	$importDataArray['records'] = $parsedRecordsArray; // this array will hold the record(s) (with each record being a sub-array of fields)

	// Add all records to the database (i.e., for each record, add a row entry to MySQL table 'refs'):
	// ('$importedRecordsArray' will hold the serial numbers of all newly imported records)
	$importedRecordsArray = addRecords($importDataArray); // function 'addRecords()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// DISPLAY RESULTS:

	if (!empty($importedRecordsArray)) // if some records were successfully imported
	{
		$recordSerialsQueryString = "^(" . implode("|", $importedRecordsArray) . ")$";

		$importedRecordsCount = count($importedRecordsArray);

		if ($importedRecordsCount == 1)
			$headerMessage = $importedRecordsCount . " " . $loc["RecordSuccessfullyImported"] . ":";
		else // $importedRecordsCount > 1
			$headerMessage = $importedRecordsCount . " " . $loc["RecordsSuccessfullyImported"] . ":";

		// display all newly added records:
		header("Location: show.php?serial=" . rawurlencode($recordSerialsQueryString) . "&headerMsg=" . rawurlencode($headerMessage));
	}
	else // nothing imported
	{
		// return an appropriate error message:
		$HeaderString = returnMsg($loc["NoRecordsImported"] . "!", "warning", "strong", "HeaderString"); // function 'returnMsg()' is defined in 'include.inc.php'
		
		if (!empty($_SERVER['HTTP_REFERER'])) // if the referer variable isn't empty
			header("Location: " . $_SERVER['HTTP_REFERER']); // redirect to calling page
		else
			header("Location: index.php"); // redirect to main page ('index.php')
	}

	// --------------------------------------------------------------------
?>
