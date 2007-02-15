<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./import_modify.php
	// Created:    17-Feb-06, 20:57
	// Modified:   02-Oct-06, 19:24

	// This php script accepts input from 'import.php' and will process records exported from Endnote, Reference Manager (RIS), BibTeX, ISI Web of Science,
	// Pubmed, CSA or Copac. In case of a single record, the script will call 'record.php' with all provided fields pre-filled. The user can then verify
	// the data, add or modify any details as necessary and add the record to the database. Multiple records will be imported directly.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/include.inc.php'; // include common functions
	include 'includes/execute.inc.php'; // include functions that deal with execution of shell commands
	include 'includes/import.inc.php'; // include common import functions
	include 'initialize/ini.inc.php'; // include common variables

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// Clear any errors that might have been found previously:
	$errors = array();

	// Write the (POST or GET) form variables into an array:
	foreach($_REQUEST as $varname => $value)
	{
		// remove slashes from parameter values if 'magic_quotes_gpc = On':
		$formVars[$varname] = stripSlashesIfMagicQuotes($value); // function 'stripSlashesIfMagicQuotes()' is defined in 'include.inc.php'
	}

	// --------------------------------------------------------------------

	// Extract the ID of the client from which the query originated:
	// this identifier is used to identify queries that originated from the refbase command line clients ("cli-refbase-1.0.1", "cli-refbase_import-1.0") or from a bookmarklet (e.g., "jsb-refbase-1.0.0")
	if (isset($formVars['client']))
		$client = $formVars['client'];
	else
		$client = "";

	if (eregi("^jsb", $client)) // if data were sent via a bookmarklet, we set some variables directly
	{
		$formVars['formType'] = "import";
		$formVars['importRecordsRadio'] = "all";
		$formVars['importRecords'] = "1";
		$formVars['showSource'] = "1";
	}

	// Get the referring page (or set a default one if no referrer is available or if the data were sent via a bookmarklet):
	if (!empty($_SERVER['HTTP_REFERER']) AND !eregi("^jsb", $client)) // if the referrer variable isn't empty and doesn't originate from a bookmarklet
		$referer = $_SERVER['HTTP_REFERER']; // on error, redirect to calling page
	else
		$referer = "import.php"; // on error, redirect to import form

	// First of all, check if the user is logged in:
	if (!isset($_SESSION['loginEmail'])) // -> if the user isn't logged in
	{
		header("Location: user_login.php?referer=" . rawurlencode($referer)); // ask the user to login first, then he'll get directed back to the calling page (normally, 'import.php')

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// now, check if the (logged in) user is allowed to import any record into the database:
	if (isset($_SESSION['user_permissions']) AND !ereg("(allow_import|allow_batch_import)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does NOT contain either 'allow_import' or 'allow_batch_import'...
	{
		// save an appropriate error message:
		$HeaderString = "<b><span class=\"warning\">You have no permission to import any records!</span></b>";

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

		header("Location: index.php"); // redirect back to main page ('index.php')

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// EXTRACT FORM VARIABLES:
	// Note: Although we could use the '$formVars' array directly below (e.g.: $formVars['sourceText'] etc., like in 'user_validation.php'), we'll read out
	//       all variables individually again. This is done to enhance readability. (A smarter way of doing so seems to be the use of the 'extract()' function, but that
	//       may expose yet another security hole...)

	// Get the form used by the user:
	$formType = $formVars['formType'];

	// In case of the main import form, get the source text containing the bibliographic record(s):
	// Note that data from any successfully uploaded file will override data pasted into the 'sourceText' text entry field
	if (isset($formVars['sourceText']))
		$sourceText = $formVars['sourceText'];
	else
		$sourceText = "";

	// In case of the PubMed import form (import via PubMed ID), get the entered PubMed IDs:
	if (isset($formVars['pubmedIDs']))
		$pubmedIDs = $formVars['pubmedIDs'];
	else
		$pubmedIDs = "";

	// If data were sent via a bookmarklet, get the URL containing the posted data:
	if (isset($formVars['sourceURL']))
		$sourceURL = $formVars['sourceURL'];
	else
		$sourceURL = "";

	// Check whether we're supposed to display the original source data:
	if (isset($formVars['showSource']))
		$showSource = $formVars['showSource'];
	else
		$showSource = "";

	if (isset($_SESSION['user_permissions']) AND ereg("allow_batch_import", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does contain 'allow_batch_import'...
	{
		// Check whether we're supposed to import all records ('all') or just particular ones ('only'):
		if (isset($formVars['importRecordsRadio']))
			$importRecordsRadio = $formVars['importRecordsRadio'];
		else
			$importRecordsRadio = "";

		// Get the record numbers of those records that shall be imported:
		// examples of recognized formats: '1-5' imports the first five records; '1 3 7' will import records 1, 3 and 7; '1-3 5-7 9' will import records 1, 2, 3, 5, 6, 7 and 9
		// (note that the first three records could be labelled e.g. as 'Record 12 of 52', 'Record 30 of 112' and 'Record 202 of 533' but they must be referred to as records '1-3'
		//  in the 'importRecords' form)
		if (isset($formVars['importRecords']))
			$importRecords = $formVars['importRecords'];
		else
			$importRecords = "";
	}
	else // if the user is only allowed to import one record at a time, we'll always import the very first record
	{
		$importRecordsRadio = "only";
		$importRecords = "1";
	}

	// Check whether we're supposed to skip records with unrecognized data format:
	if (isset($formVars['skipBadRecords']))
		$skipBadRecords = $formVars['skipBadRecords'];
	else
		$skipBadRecords = "";

	// Check if a file was uploaded:
	// (note that to have file uploads work, HTTP file uploads must be allowed within your 'php.ini' configuration file
	//  by setting the 'file_uploads' parameter to 'On'!)
	// extract file information into a four (or five) element associative array containing the following information about the file:

	//     name     - original name of file on client
	//     type     - MIME type of file
	//     tmp_name - name of temporary file on server
	//     error    - holds an error number >0 if something went wrong, otherwise 0 (I don't know when this element was added. It may not be present in your PHP version... ?:-/)
	//     size     - size of file in bytes

	// depending what happend on upload, they will contain the following values (PHP 4.1 and above):
	//              no file upload  upload exceeds 'upload_max_filesize'  successful upload
	//              --------------  ------------------------------------  -----------------
	//     name           ""                       [name]                      [name]
	//     type           ""                         ""                        [type]
	//     tmp_name    "" OR "none"                  ""                      [tmp_name]
	//     error          4                          1                           0
	//     size           0                          0                         [size]
	$uploadFile = getUploadInfo("uploadFile"); // function 'getUploadInfo()' is defined in 'include.inc.php'

	$tmpFilePath = "";

	// Validate the 'uploadFile' field:
	// TODO: Move code that validates file uploads into its own function (and merge with related code from 'modify.php')
	// (which must not exceed the 'upload_max_filesize' specified within your 'php.ini' configuration file)
	if (!empty($uploadFile) && !empty($uploadFile["name"])) // if the user attempted to upload a file
	{
		// The 'is_uploaded_file()' function returns 'true' if the file indicated by '$uploadFile["tmp_name"]' was uploaded via HTTP POST. This is useful to help ensure
		// that a malicious user hasn't tried to trick the script into working on files upon which it should not be working - for instance, /etc/passwd.
		if (is_uploaded_file($uploadFile["tmp_name"]))
		{
			if (empty($uploadFile["tmp_name"])) // no tmp file exists => we assume that the maximum upload file size was exceeded!
			// or check via 'error' element instead: "if ($uploadFile["error"] == 1)" (the 'error' element exists since PHP 4.2.0)
			{
				$maxFileSize = ini_get("upload_max_filesize");
				$fileError = "File size must not be greater than " . $maxFileSize . ":";
		
				$errors["uploadFile"] = $fileError; // inform the user that the maximum upload file size was exceeded
			}
			else // a tmp file exists...
			{
				// prevent hackers from gaining access to the systems 'passwd' file (this should be prevented by the 'is_uploaded_file()' function but anyhow):
				if (eregi("^passwd$", $uploadFile["name"])) // file name must not be 'passwd'
					$errors["uploadFile"] = "This file name is not allowed!";
				// check for invalid file name extensions:
				elseif (eregi("\.(exe|com|bat|zip|php|phps|php3|cgi)$", $uploadFile["name"])) // file name has an invalid file name extension (adjust the regex pattern if you want more relaxed file name validation)
					$errors["uploadFile"] = "You cannot upload this type of file!"; // file name must not end with .exe, .com, .bat, .zip, .php, .phps, .php3 or .cgi
				else
					$tmpFilePath = $uploadFile["tmp_name"];
			}
		}
		else
		{
			// I'm not sure if this actually works --RAK
			switch($uploadFile["error"])
			{
				case 0: // no error; possible file attack!
					$errors["uploadFile"] = "There was a problem with your upload.";
				case 1: // uploaded file exceeds the 'upload_max_filesize' directive in 'php.ini'
					$maxFileSize = ini_get("upload_max_filesize");
					$fileError = "File size must not be greater than " . $maxFileSize . ":";
					$errors["uploadFile"] = $fileError;
				case 2: // uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form (Note: refbase doesn't currently specify MAX_FILE_SIZE but anyhow...)
					$errors["uploadFile"] = "The file you are trying to upload is too big.";
				case 3: // uploaded file was only partially uploaded
					$errors["uploadFile"] = "The file you are trying to upload was only partially uploaded.";
				case 4: // no file was uploaded
					$errors["uploadFile"] = "You must select a file for upload.";
				case 6:
					$errors["uploadFile"] = "Missing a temporary folder.";
				default: // a default error, just in case!  :)
					$errors["uploadFile"] = "There was a problem with your upload.";
			}
		}
	}

	if (!empty($uploadFile) && !empty($tmpFilePath)) // if there was a file uploaded successfully
	{
		// Get file contents:
		$fileData = readFromFile($tmpFilePath); // function 'readFromFile()' is defined in 'execute.inc.php'

		if (!empty($fileData))
			// data from any successfully uploaded file will override data pasted into the 'sourceText' text entry field
			$sourceText = $fileData;
	}

	// --------------------------------------------------------------------

	// PRE-PROCESS DATA INPUT:

	// Process record number input:
	$importRecordNumbersArray = array(); // initialize array variable which will hold all the record numbers that shall be imported
	if (!empty($importRecords))
	{
		// split input string on all but digits or the hyphen ("-") character:
		// (the 'PREG_SPLIT_NO_EMPTY' flag causes only non-empty pieces to be returned)
		$importRecordsArray = preg_split("/[^0-9-]+/", $importRecords, -1, PREG_SPLIT_NO_EMPTY); // this keeps only elements such as '1', '3-5', '3-5-9' or '3-' (we'll deal with the last two cases below)

		foreach ($importRecordsArray as $importRecordsElement)
		{
			if (preg_match("/\d+-\d+/", $importRecordsElement)) // if we're dealing with a range of record numbers (such as '1-5')
			{
				$importRecordsElementArray = split("-", $importRecordsElement); // split input string on hyphen ("-") character

				// generate an array that includes all numbers from start number to end number:
				// (in case of incorrect input (such as '3-5-9') we'll only take the first two numbers and ignore anything else)
				$importRecordRangeArray = range($importRecordsElementArray[0], $importRecordsElementArray[1]);

				foreach ($importRecordRangeArray as $importRecordNumber) // append all record numbers within range to array
					$importRecordNumbersArray[] = $importRecordNumber;
			}
			else // this element contains just a single record number
			{
				// append this record number to array:
				$importRecordNumbersArray[] = preg_replace("/(\d+).*/", "\\1", $importRecordsElement); // we account for the case that '$importRecordsElement' contains something like '3-'
			}
		}
	}
	// validation will throw up an error if we're supposed to import only particular records but no record numbers were specified

	// Remove any duplicate record number(s) from the list of extracted record numbers:
	$importRecordNumbersArray = array_unique($importRecordNumbersArray);

	// --------------------------------------------------------------------

	// IDENTIFY SOURCE FORMAT:

	// if the source text originated from the main 'import' form provided by 'import.php':
	if ($formType == "import")
		// attempt to identify the format of the input text:
		$sourceFormat = identifySourceFormat($sourceText); // function 'identifySourceFormat()' is defined in 'import.inc.php'

	// else if source text originated from the PubMed import form (import via PubMed ID):
	elseif ($formType == "importPubMed")
		$sourceFormat = "Pubmed Medline";

	// --------------------------------------------------------------------

	// FETCH DATA FROM URL:

	// in case of import via PubMed ID: fetch source data from PubMed.gov for all given PubMed IDs:
	if (($formType == "importPubMed") AND ereg("[0-9]", $pubmedIDs) AND !empty($sourceFormat))
	{
		// remove any meaningless delimiter(s) from the beginning or end of the ID string:
		$pubmedIDs = trimTextPattern($pubmedIDs, "\D+", true, true); // function 'trimTextPattern()' is defined in 'include.inc.php'
		// split on any text between PubMed IDs and remove any duplicate IDs:
		$pubmedIDArray = array_unique(split("[^0-9]+", $pubmedIDs));
		// merge PubMed IDs again with commas:
		$pubmedIDs = implode(",", $pubmedIDArray);

		$eUtilsServer = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/";
		$eFetchScript = "efetch.fcgi";
		$eFetchTool = "refbase";
		$eFetchEmail = rawurlencode("info@refbase.net");
		$eFetchDatabase = "pubmed";
		$eFetchFormat = "medline";

		if ($sourceFormat == "Pubmed XML")
			$eFetchType = "xml";
		else // by default, we'll use the "Pubmed Medline" format
			$eFetchType = "text";

		$sourceURL = $eUtilsServer . $eFetchScript . "?db=" . $eFetchDatabase . "&retmode=" . $eFetchType . "&rettype=" . $eFetchFormat . "&tool=" . $eFetchTool . "&email=" . $eFetchEmail . "&id=" . $pubmedIDs;

		$sourceText = fetchDataFromURL($sourceURL); // function 'fetchDataFromURL()' is defined in 'import.inc.php'

		if (!preg_match("/^PMID- /m", $sourceText) AND ereg("Error occurred:", $sourceText)) // a PubMed error occurred, probably because only unrecognized PubMed IDs were given
		{
			$errors["pubmedIDs"] = preg_replace("/.*Error occurred: *([^<>]+).*/s", "PubMed error: \\1", $sourceText); // attempt to extract PubMed error message
			$sourceText = "";
		}
	}

	// --------------------------------------------------------------------

	// PARSE SOURCE TEXT:

	if (!empty($sourceText) AND !empty($sourceFormat))
	{
		// fetch the path/name of the import format file that's associated with the import format given in '$sourceFormat':
		$importFormatFile = getFormatFile($sourceFormat, "import"); // function 'getFormatFile()' is defined in 'include.inc.php()'

		if (!empty($importFormatFile))
		{
			// Include the found import format file *once*:
			include_once "import/" . $importFormatFile;

			// Parse records from the specified import format:
			// function 'importRecords()' is defined in the import format file given in '$importFormatFile' (which, in turn, must reside in the 'import' directory of the refbase root directory)
			list($importDataArray, $recordsCount, $importRecordNumbersRecognizedFormatArray, $importRecordNumbersNotRecognizedFormatArray, $errors) = importRecords($sourceText, $importRecordsRadio, $importRecordNumbersArray);
		}
		else
			$errors["sourceText"] = "Sorry, but the $sourceFormat importer is currently not available!";
	}
	else
	{
		$importDataArray = array();
		$recordsCount = 0;
		$importRecordNumbersRecognizedFormatArray = array();
		$importRecordNumbersNotRecognizedFormatArray = array();
	}

	// --------------------------------------------------------------------

	// VALIDATE DATA FIELDS:

	// For each parsed record, function 'validateRecords()' (in 'import.inc.php') will assign errors to '$errors["sourceText"]'.
	// In case of the PubMed import form, we'll redirect these error messages to '$errors["pubmedIDs"]':
	if (($formType == "importPubMed") AND isset($errors["sourceText"])) // some errors occurred
	{
		$errors["pubmedIDs"] = $errors["sourceText"];
		unset($errors["sourceText"]);
	}

	// Verify that some source text was given:
	if (($formType == "import") AND empty($sourceText)) // no source data given
		$errors["sourceText"] = "Source data missing!";

	elseif (($formType == "importPubMed") AND !ereg("[0-9]", $pubmedIDs)) // no PubMed IDs given
		$errors["pubmedIDs"] = "You must specify at least one PubMed ID!";

	// If some source data were given but the source text format wasn't among the recognized formats:
	elseif (empty($sourceFormat))
		$errors["sourceText"] = "Unrecognized data format!";


	// Validate the 'importRecords' text entry field...
	elseif ($importRecordsRadio == "only") // ...if we're supposed to import only particular records
	{
		// ...make sure that some records were specified and that they are actually available in the input data:
		if (empty($importRecords) OR !ereg("[0-9]", $importRecords)) // partial import requested but no record numbers given
		{
			$errors["importRecords"] = "Record number(s) missing!";
		}
		else // if some record numbers were given, check that these numbers are actually available in the input data:
		{
			$availableRecordNumbersArray = range(1, $recordsCount); // construct an array of available record numbers

			// get all record numbers to import which are NOT available in the source data:
			$importRecordNumbersNotAvailableArray = array_diff($importRecordNumbersArray, $availableRecordNumbersArray); // get all unique array elements from '$importRecordNumbersArray' that are not present in '$availableRecordNumbersArray'

			// just FYI, the line below would get all record numbers to import which ARE actually available in the source data:
			// $importRecordNumbersAvailableArray = array_diff($importRecordNumbersArray, $importRecordNumbersNotAvailableArray); // get all unique array elements from '$importRecordNumbersArray' that are not present in '$importRecordNumbersNotAvailableArray'

			if (!empty($importRecordNumbersNotAvailableArray)) // the user did request to import some record(s) that don't exist in the pasted source data
			{
				if ($recordsCount == 1) // one record available
					$errors["importRecords"] = "Only one record available! You can only use record number '1'.";
				else // several records available
					$errors["importRecords"] = "Only " . $recordsCount . " records available! You can only use record numbers '1-" . $recordsCount . "'.";
			}
		}
	}

	// the user did enter some source text and did input some recognized record numbers
	if (!empty($sourceText))
	{
		// NOTE: validation of individual records is done within the import functions and the '$errors' array is modified within these functions if any records of unrecognized format are found

		if (empty($importRecordNumbersRecognizedFormatArray)) // if none of the records to import had a recognized format
		{
			// we'll file an additional error element here, which will indicate whether the 'Skip records with unrecognized data format' checkbox shall be displayed or not
			$errors["badRecords"] = "all";

			if (!empty($sourceFormat) AND (count($importRecordNumbersNotRecognizedFormatArray) > 1)) // if the user attempted to import more than one record
				$errors["skipBadRecords"] = "Sorry, but all of the specified records were of unrecognized data format!";
			else // user tried to import one single record (will be also triggered if '$importRecords' is empty)
				$errors["skipBadRecords"] = ""; // we insert an empty 'skipBadRecords' element so that 'import.php' does the right thing
		}
		elseif (!empty($importRecordNumbersNotRecognizedFormatArray)) // some records had a recognized format but some were NOT recognized
		{
			$errors["badRecords"] = "some"; // see note above

			$errors["skipBadRecords"] = "Skip records with unrecognized data format";
		}
	}
	else
	{
		$errors["badRecords"] = "all";
	}

	// --------------------------------------------------------------------

	// Check if there were any validation errors:
	if (count($errors) > 0)
	{
		// we ignore errors regarding records with unrecognized format if:
		// - at least some of the specified records had a valid data format and
		// - the user did mark the 'Skip records with unrecognized data format' checkbox
		if (!(($errors["badRecords"] == "some") AND ($skipBadRecords == "1")))
		{
			// ...otherwise we'll present the error message(s):

			if (eregi("^cli", $client)) // if the query originated from a command line client such as the refbase CLI clients ("cli-refbase-1.1", "cli-refbase_import-1.0")
			{
				echo "There were validation errors regarding the data you submitted:\n\n";

				if (($errors["badRecords"] == "all") && (!empty($errors["skipBadRecords"])))
					$skipBadInfo = $errors["skipBadRecords"] . "\n\n";
				elseif ($errors["badRecords"] == "some")
					$skipBadInfo = "Use '--skipbad=1' to skip records with unrecognized data format.\n\n";
				else
					$skipBadInfo = "";

				unset($errors["badRecords"]);
				unset($errors["skipBadRecords"]);

				foreach ($errors as $varname => $value)
				{
					$value = ereg_replace("<br>", "\n            ", $value);
					echo $varname . ": " . $value . "\n\n";
				}

				echo $skipBadInfo;
			}
			else
			{
				// Write back session variables:
				saveSessionVariable("errors", $errors); // function 'saveSessionVariable()' is defined in 'include.inc.php'
				saveSessionVariable("formVars", $formVars);
	
				// Redirect the browser back to the import form:
				header("Location: " . $referer);
			}

			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}
	}

	// --------------------------------------------------------------------

	// If we made it here, then the data is considered valid!

	// IMPORT RECORDS:

	$importedRecordsArray = array();

	if ((count($importRecordNumbersRecognizedFormatArray) == 1) AND !eregi("^cli", $client)) // if this is the only record we'll need to import -AND- if the import didn't originate from a refbase command line client:
	{
		foreach ($importDataArray['records'][0] as $fieldParameterKey => $fieldParameterValue)
			$importDataArray['records'][0][$fieldParameterKey] = $fieldParameterKey . "=" . rawurlencode($fieldParameterValue); // copy parameter name and equals sign in front of parameter value

		$fieldParameters = implode("&", $importDataArray['records'][0]); // merge list of parameters

		// RELOCATE TO IMPORT PAGE:
		// call 'record.php' and load the form fields with the data of the current record
		header("Location: record.php?recordAction=add&mode=import&importSource=generic&" . $fieldParameters);
		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}
	else // import record(s) directly:
	{
		// Add all records to the database (i.e., for each record, add a row entry to MySQL table 'refs'):
		// ('$importedRecordsArray' will hold the serial numbers of all newly imported records)
		$importedRecordsArray = addRecords($importDataArray); // function 'addRecords()' is defined in 'include.inc.php'
	}

	// --------------------------------------------------------------------

	// DISPLAY RESULTS

	if (!empty($importedRecordsArray)) // if some records were successfully imported
	{
		$recordSerialsQueryString = implode(",", $importedRecordsArray);

		$importedRecordsCount = count($importedRecordsArray);

		// Send EMAIL announcement:
		if ($sendEmailAnnouncements == "yes")
		{
			// variables '$sendEmailAnnouncements', '$mailingListEmail', '$officialDatabaseName' and '$databaseBaseURL' are specified in 'ini.inc.php';
			// '$loginFirstName' and '$loginLastName' are provided as session variables by the 'start_session()' function in 'include.inc.php'

			// send a notification email to the mailing list email address given in '$mailingListEmail':
			$emailRecipient = "Literature Database Announcement List <" . $mailingListEmail . ">";
	
			if ($importedRecordsCount == 1)
			{
				$emailSubject = "New record added to the " . $officialDatabaseName;
				$emailBodyIntro = "One record has been added to the " . $officialDatabaseName . ":";
				$detailsURL = $databaseBaseURL . "show.php?record=" . $importedRecordsArray[0];
			}
			else // $importedRecordsCount > 1
			{
				$emailSubject = "New records added to the " . $officialDatabaseName;
				$emailBodyIntro = $importedRecordsCount . " records have been added to the " . $officialDatabaseName . ":";
				$detailsURL = $databaseBaseURL . "show.php?records=" . $recordSerialsQueryString;
			}

			$emailBody = $emailBodyIntro
						. "\n\n  added by:     " . $loginFirstName . " " . $loginLastName
						. "\n  details:      " . $detailsURL
						. "\n";

			sendEmail($emailRecipient, $emailSubject, $emailBody); // function 'sendEmail()' is defined in 'include.inc.php'
		}

		if ($importedRecordsCount == 1)
			$headerMessage = $importedRecordsCount . " record has been successfully imported:";
		else // $importedRecordsCount > 1
			$headerMessage = $importedRecordsCount . " records have been successfully imported:";

		// DISPLAY all newly added records:
		header("Location: show.php?records=" . $recordSerialsQueryString . "&headerMsg=" . rawurlencode($headerMessage) . "&client=" . $client);
	}
	else // nothing imported
	{
		if (eregi("^cli", $client)) // if the query originated from a command line client such as the refbase CLI clients ("cli-refbase-1.1", "cli-refbase_import-1.0")
		{
			echo "No records imported!\n\n";
		}
		else
		{
			// we'll file again this additional error element here so that the 'errors' session variable isn't empty causing 'import.php' to re-load the form data that were submitted by the user
			$errors["badRecords"] = "all";

			// save an appropriate error message:
			$HeaderString = "<b><span class=\"warning\">No records imported!</span></b>";

			// Write back session variables:
			saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
			saveSessionVariable("errors", $errors);
			saveSessionVariable("formVars", $formVars);

			header("Location: " . $referer); // redirect to the calling page (normally, 'import.php')
		}
	}

	// --------------------------------------------------------------------
?>
