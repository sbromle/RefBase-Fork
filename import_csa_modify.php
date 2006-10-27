<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./import_csa_modify.php
	// Created:    21-Nov-03, 22:46
	// Modified:   23-Jan-06, 21:50

	// This php script accepts input from 'import_csa.php' and will process any CSA full record data. In case of a single
	// record, the script will call 'record.php' with all provided fields pre-filled. The user can then verify the data,
	// add or modify any details as necessary and add the record to the database. Multiple records will be imported directly.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/include.inc.php'; // include common functions
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
		$formVars[$varname] = $value;

	// --------------------------------------------------------------------

	// Get the referring page (or set a default one if no referrer is available):
	if (!empty($_SERVER['HTTP_REFERER'])) // if the referrer variable isn't empty
		$referer = $_SERVER['HTTP_REFERER']; // on error, redirect to calling page
	else
		$referer = "import_csa.php"; // on error, redirect to CSA import form

	// First of all, check if the user is logged in:
	if (!isset($_SESSION['loginEmail'])) // -> if the user isn't logged in
	{
		header("Location: user_login.php?referer=" . rawurlencode($referer)); // ask the user to login first, then he'll get directed back to the calling page (normally, 'import_csa.php')

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

	// Extract form variables sent through POST:
	// Note: Although we could use the '$formVars' array directly below (e.g.: $formVars['sourceText'] etc., like in 'user_validation.php'), we'll read out
	//       all variables individually again. This is done to enhance readability. (A smarter way of doing so seems to be the use of the 'extract()' function, but that
	//       may expose yet another security hole...)

	// Get the form used by the user:
	$formType = $formVars['formType'];

	// Get the source text containing the CSA record(s):
	$sourceText = $formVars['sourceText'];

	// Check if source text originated from a PubMed import form (instead of 'import_csa.php')
	if ($formType == "importPUBMED")
	{
		// Fetch PubMed XML data (by PubMed ID given in '$sourceText') and convert to CSA format:
		// (this allows for import of PubMed records via the import form provided by 'import_pubmed.php')
		$sourceText = PubmedToCsa($sourceText); // function 'PubmedToCsa()' is defined in 'import.inc.php'
	}

	// Check if the format of the pasted source data is in "ISI Web of Science" format (instead of "CSA" format):
	if ((!preg_match("/\s*Record \d+ of \d+\s*/", $sourceText)) and (preg_match("/\s*FN ISI Export Format\s*/", $sourceText)))
	{
		// Convert ISI WoS format to CSA format:
		// (this allows for import of ISI WoS records via the import form provided by 'import_csa.php')
		$sourceText = IsiToCsa($sourceText); // function 'IsiToCsa()' is defined in 'import.inc.php'
	}

	// Check whether we're supposed to display the original source data:
	if (isset($formVars['showSource']))
		$showSource = $formVars['showSource'];
	else
		$showSource = "";

	if (isset($_SESSION['user_permissions']) AND ereg("allow_batch_import", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does contain 'allow_batch_import'...
	{
		// Check whether we're supposed to import all records ('all') or just particular ones ('only'):
		$importRecordsRadio = $formVars['importRecordsRadio'];

		// Get the record numbers of those records that shall be imported:
		// examples of recognized formats: '1-5' imports the first five records; '1 3 7' will import records 1, 3 and 7; '1-3 5-7 9' will import records 1, 2, 3, 5, 6, 7 and 9
		// (note that the first three records could be labelled e.g. as 'Record 12 of 52', 'Record 30 of 112' and 'Record 202 of 533' but they must be referred to as records '1-3'
		//  in the 'importRecords' form)
		$importRecords = $formVars['importRecords'];
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

	// --------------------------------------------------------------------

	// Do some pre-processing of the data input:

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

	// Extract the first record identifier of the input data (e.g. "\nRecord 1 of ...\n"):
	// this will also allow to paste record data that don't start with "\nRecord 1 of ...\n" but with e.g. "\nRecord 3 of ...\n"
	// (note that I couldn't figure out a non-greedy regex pattern to reliably extract the *very first* record identifier
	//  which is why I'm using this 'preg_split()' workaround here)
	//  - split on record identifiers ("\nRecord ... of ...\n") and include the found identifiers (i.e. the delimiters) as additional array elements ('PREG_SPLIT_DELIM_CAPTURE' flag):
	$recordsAndRecordNumbersArray = preg_split("/\s*(Record \d+ of \d+)\s*/", $sourceText, -1, PREG_SPLIT_DELIM_CAPTURE);
	$firstRecordIdentifier = "Record 1 of \d+"; // establish a default search pattern for the regex replace action below (otherwise, if '$firstRecordIdentifier' would be empty, all of the '$sourceText' would be deleted!)
	for ($i=0; $i<(count($recordsAndRecordNumbersArray)); $i++)
	{
		if (preg_match("/^Record \d+ of \d+$/", $recordsAndRecordNumbersArray[$i]))
		{
			// - extract the first record identifier
			$firstRecordIdentifier = $recordsAndRecordNumbersArray[$i];
			break;
		}
	}

	// Remove any text preceeding the actual record data as well as the first record identifier ("\nRecord ... of ...\n"):
	// CAUTION: Note that record identifiers must be unique among the pasted records! If e.g. the record identifier in '$firstRecordIdentifier'
	//          occurs twice, the line below will remove all records up to the *second* occurrence of this identifier!
	$trimmedSourceText = preg_replace("/.*\s*" . $firstRecordIdentifier . "(?=\D)\s*/s", "", $sourceText);

	// Split input text on the header text preceeding each CSA record (e.g. "\nRecord 4 of 52\n"):
	$recordArray = preg_split("/\s*Record \d+ of \d+\s*/", $trimmedSourceText);
	$recordsCount = count($recordArray); // count how many records are available

	// --------------------------------------------------------------------

	// VALIDATE data fields:

	// Verify that some source text was given:
	if (empty($sourceText)) // no source data given
	{
		$errors["sourceText"] = "Source data missing!";
	}

	// Validate the 'importRecords' text entry field...
	if ($importRecordsRadio == "only") // ...if we're supposed to import only particular records
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
		$importRecordNumbersRecognizedFormatArray = array(); // initialize array variable which will hold all record numbers of those records that shall be imported AND which were of a recognized format
		$importRecordNumbersNotRecognizedFormatArray = array(); // same for all records that shall be imported BUT which had an UNrecognized format

		// Verify the record format:
		for ($i=0; $i<$recordsCount; $i++) // for each record...
		{
			$singleRecord = $recordArray[$i];

			if (($importRecordsRadio == "only") AND (!in_array(($i+1), $importRecordNumbersArray))) // if we're NOT supposed to import this record... ('$i' starts with 0 so we have to add 1 to point to the correct record number)
			{
				continue; // process next record (if any)
			}
			else // ...validate the format of the current record:
			{
				// We assume a single record as valid if the '$singleRecord' variable is not empty and if it does contain
				// at least the following three field identifiers: "TI: Title", "SO: Source", "AU: Author" (only exception: for book monographs we accept "ED: Editor" instead of "AU: Author")
				// In addition, each of these field identifiers must be followed by a return and/or newline and four spaces!
				if (!empty($singleRecord) AND preg_match("/^TI: Title *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/^SO: Source *[\r\n]+ {4,4}/m", $singleRecord) AND (preg_match("/^AU: Author *[\r\n]+ {4,4}/m", $singleRecord) OR (preg_match("/^ED: Editor *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord))))
				{
					$importRecordNumbersRecognizedFormatArray[] = $i + 1; // append this record number to the list of numbers whose record format IS recognized ('$i' starts with 0 so we have to add 1 to point to the correct record number)
				}
				else // unrecognized record format
				{
					$importRecordNumbersNotRecognizedFormatArray[] = $i + 1; // append this record number to the list of numbers whose record format is NOT recognized

					// prepare an appropriate error message:
					$errorMessage = "Record " . ($i + 1) . ": Unrecognized data format!";
					$emptyFieldsArray = array();

					// check for required fields:
					if (!preg_match("/^TI: Title *[\r\n]+ {4,4}/m", $singleRecord)) // required field empty: 'title'
						$emptyFieldsArray[] = "title";

					if (!preg_match("/^AU: Author *[\r\n]+ {4,4}/m", $singleRecord) AND !preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord)) // non-books: required field empty: 'author'
						$emptyFieldsArray[] = "author";

					elseif (!preg_match("/^AU: Author *[\r\n]+ {4,4}/m", $singleRecord) AND !preg_match("/^ED: Editor *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord)) // books: required fields empty: 'author' AND 'editor' (for books, either 'author' or 'editor' must be given)
						$emptyFieldsArray[] = "author (or editor)";

					if (!preg_match("/^SO: Source *[\r\n]+ {4,4}/m", $singleRecord)) // required field empty: 'source'
						$emptyFieldsArray[] = "source";

					if (!empty($emptyFieldsArray)) // some required fields were missing
					{
						if (count($emptyFieldsArray) == 1) // one field missing
							$errorMessage .= " Required field missing: " . $emptyFieldsArray[0];
						else // several fields missing
							$errorMessage .= " Required fields missing: " . implode(', ', $emptyFieldsArray);
					}

					if (!isset($errors["sourceText"]))
						$errors["sourceText"] = $errorMessage;
					else
						$errors["sourceText"] = $errors["sourceText"] . "<br>" . $errorMessage;
				}
			}
		}

		if (empty($importRecordNumbersRecognizedFormatArray)) // if none of the records to import had a recognized format
		{
			// we'll file an additional error element here, which will indicate whether the 'Skip records with unrecognized data format' checkbox shall be displayed or not
			$errors["badRecords"] = "all";

			if (count($importRecordNumbersNotRecognizedFormatArray) > 1) // if the user attempted to import more than one record
				$errors["skipBadRecords"] = "Sorry, but all of the specified records were of unrecognized data format!";
			else // user tried to import one single record (will be also triggered if '$importRecords' is empty)
				$errors["skipBadRecords"] = ""; // we insert an empty 'skipBadRecords' element so that 'import_csa.php' does the right thing
		}
		elseif (!empty($importRecordNumbersNotRecognizedFormatArray)) // some records had a recognized format but some were NOT recognized
		{
			$errors["badRecords"] = "some"; // see note above

			$errors["skipBadRecords"] = "Skip records with unrecognized data format";
		}
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
			// ...otherwise we'll redirect back to the CSA import form and present the error message(s):

			// Write back session variables:
			saveSessionVariable("errors", $errors); // function 'saveSessionVariable()' is defined in 'include.inc.php'
			saveSessionVariable("formVars", $formVars);

			// Redirect the browser back to the CSA import form:
			header("Location: " . $referer);
			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}
	}

	// --------------------------------------------------------------------

	// If we made it here, then the data is considered valid!

	// (3) PROCESS SOURCE DATA:

	$parsedRecordsArray = array(); // initialize array variable which will hold parsed data of all records that shall be imported

	// LOOP OVER EACH RECORD:
	for ($i=0; $i<$recordsCount; $i++) // for each record...
	{
		// if we're NOT supposed to import this record (because it was either not selected by the user -OR- because it did contain an unrecognized data format)
		if (!in_array(($i+1), $importRecordNumbersRecognizedFormatArray)) // '$i' starts with 0 so we have to add 1 to point to the correct record number
		{
			continue; // process next record (if any)
		}
		else // ...import the current record:
		{
			$singleRecord = $recordArray[$i];

			// if the "AU: Author" field is missing BUT the "ED: Editor" is present (which is allowed for book monographs, see above):
			// we replace the "ED: Editor" field identifier with "AU: Author" (this will keep any " (ed)" and " (eds)" tags in place which, in turn, will cause the "is Editor" checkbox in 'record.php' to get marked)
			if (!preg_match("/^AU: Author *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/^ED: Editor *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord))
				$singleRecord = preg_replace("/^ED: Editor(?= *[\r\n]+ {4,4})/m", "AU: Author", $singleRecord);

			$fieldArray = preg_split("/[\r\n]+(?=\w\w: )/", $singleRecord); // split each record into its fields

			// initialize some variables:
			$fieldParametersArray = array(); // setup an empty array (it will hold the parameters that get passed to 'record.php')
			$additionalDocumentTypeInfo = ""; // will be used with the "PT: Publication Type" field
			$environmentalRegime = ""; // will be used with the "ER: Environmental Regime" field


			// GENERATE EXTRA FIELDS:
			// check if the fields "MT: Monograph Title", "JN: Journal Name", "JV: Journal Volume", "JI: Journal Issue" and "JP: Journal Pages" are present,
			// if not, we attempt to generate them from the "SO: Source" field:
			$sourceField = preg_replace("/.*SO: Source *[\r\n]+ {4,4}(.+?)(?=([\r\n]+\w\w: |\s*\z)).*/ms", "\\1", $singleRecord); // first, we need to extract the "SO: Source" field data from the record text
			$sourceField = preg_replace("/\s{2,}/", " ", $sourceField); // remove any hard returns and extra spaces within the source field data string

			// if the current record is of type "Book Monograph" but the field "MT: Monograph Title" is missing:
			if (preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord) AND !preg_match("/^MT: Monograph Title *[\r\n]+ {4,4}/m", $singleRecord))
			{
				$extractedSourceFieldData = preg_replace("/^([^.[]+).*/", "\\1", $sourceField); // attempt to extract the full monograph title from the source field

				if (preg_match("/^[[:upper:]\W\d]+$/", $extractedSourceFieldData)) // if all of the words within the monograph title are uppercase, we attempt to convert the string to something more readable:
					$extractedSourceFieldData = ucwords(strtolower($extractedSourceFieldData)); // perform case transformation (e.g. convert "BIOLOGY AND ECOLOGY OF GLACIAL RELICT CRUSTACEA" into "Biology And Ecology Of Glacial Relict Crustacea")

				$fieldArray[] = "MT: Monograph Title\r\n    " . $extractedSourceFieldData; // add field "MT: Monograph Title" to the array of fields
			}
			// else if the current record is of type "Journal Article", "Report", etc (or wasn't specified) but the field "JN: Journal Name" is missing:
			elseif (!preg_match("/^JN: Journal Name *[\r\n]+ {4,4}/m", $singleRecord)) // preg_match("/^(PT: Publication Type\s+(Journal Article|Report)|DT: Document Type\s+(J|R))/m", $singleRecord)
			{
				if (ereg("\[", $sourceField)) // if the source field data contain a square bracket we assume a format like: "Journal of Phycology [J. Phycol.]. Vol. 37, no. s3, pp. 18-18. Jun 2001."
					$extractedSourceFieldData = preg_replace("/^([^.[]+).*/", "\\1", $sourceField); // attempt to extract the full journal name from the source field
				else // source field format might be something like: "Phycologia, vol. 34, no. 2, pp. 135-144, 1995"
					$extractedSourceFieldData = preg_replace("/^([^.,]+).*/", "\\1", $sourceField); // attempt to extract the full journal name from the source field

				if (preg_match("/^[[:upper:]\W\d]+$/", $extractedSourceFieldData)) // if all of the words within the journal name are uppercase, we attempt to convert the string to something more readable:
					$extractedSourceFieldData = ucwords(strtolower($extractedSourceFieldData)); // perform case transformation (e.g. convert "POLAR BIOLOGY" into "Polar Biology")

				$fieldArray[] = "JN: Journal Name\r\n    " . $extractedSourceFieldData; // add field "JN: Journal Name" to the array of fields
			}

			// if the "JV: Journal Volume" is missing BUT the "SO: Source" field contains a volume specification:
			if (!preg_match("/^JV: Journal Volume *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/(?<=\W)vol[. ]+[\w\/-]+/i", $sourceField))
			{
				$extractedSourceFieldData = preg_replace("/.*(?<=\W)vol[. ]+([\w\/-]+).*/i", "\\1", $sourceField); // attempt to extract the journal volume from the source field

				$fieldArray[] = "JV: Journal Volume\r\n    " . $extractedSourceFieldData; // add field "JV: Journal Volume" to the array of fields
			}

			// if the "JI: Journal Issue" is missing BUT the "SO: Source" field contains an issue specification:
			if (!preg_match("/^JI: Journal Issue *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/(?<=\W)no[. ]+[\w\/-]+/i", $sourceField))
			{
				$extractedSourceFieldData = preg_replace("/.*(?<=\W)no[. ]+([\w\/-]+).*/i", "\\1", $sourceField); // attempt to extract the journal issue from the source field

				$fieldArray[] = "JI: Journal Issue\r\n    " . $extractedSourceFieldData; // add field "JI: Journal Issue" to the array of fields
			}

			// if the "JP: Journal Pages" is missing BUT the "SO: Source" field contains a pages specification:
			if (!preg_match("/^JP: Journal Pages *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/((?<=\W)pp?[. ]+[\w\/,-]+|[\d,]+ *pp\b)/i", $sourceField))
			{
				if (preg_match("/(?<=\W)pp?[. ]+[\w\/,-]+/i", $sourceField)) // e.g. "pp. 212-217" or "p. 216" etc
					$extractedSourceFieldData = preg_replace("/.*(?<=\W)pp?[. ]+([\w\/,-]+).*/i", "\\1", $sourceField); // attempt to extract the journal pages from the source field
				elseif (preg_match("/[\d,]+ *pp\b/", $sourceField)) // e.g. "452 pp"
					$extractedSourceFieldData = preg_replace("/.*?([\d,]+ *pp)\b.*/i", "\\1", $sourceField); // attempt to extract the journal pages from the source field

				$extractedSourceFieldData = preg_replace("/,/", "", $extractedSourceFieldData); // remove any thousands separators from journal pages

				$fieldArray[] = "JP: Journal Pages\r\n    " . $extractedSourceFieldData; // add field "JP: Journal Pages" to the array of fields
			}


			// Additionally, we extract the abbreviated journal name from the "SO: Source" field (if available):
			if (ereg("\[", $sourceField)) // if the source field data contain a square bracket we assume a format like: "Journal of Phycology [J. Phycol.]. Vol. 37, no. s3, pp. 18-18. Jun 2001."
			{
				$extractedSourceFieldData = preg_replace("/.*\[(.+?)\].*/", "\\1", $sourceField); // attempt to extract the abbreviated journal name from the source field
				$extractedSourceFieldData = preg_replace("/\./", "", $extractedSourceFieldData); // remove any dots from the abbreviated journal name

				if (preg_match("/^[[:upper:]\W\d]+$/", $extractedSourceFieldData)) // if all of the words within the abbreviated journal name are uppercase, we attempt to convert the string to something more readable:
					$extractedSourceFieldData = ucwords(strtolower($extractedSourceFieldData)); // perform case transformation (e.g. convert "BALT SEA ENVIRON PROC" into "Balt Sea Environ Proc")

				$fieldArray[] = "JA: Abbrev Journal Name\r\n    " . $extractedSourceFieldData; // add field "JA: Abbrev Journal Name" to the array of fields (note that this field normally does NOT occur within the CSA full record format!)
			}
			// (END GENERATE EXTRA FIELDS)


			// LOOP OVER EACH FIELD:
			foreach ($fieldArray as $singleField) // for each field within the current record...
			{
				$singleField = preg_replace("/^(\w\w: [^\r\n]+)[\r\n]+ {4,4}/", "\\1___LabelDataSplitter___", $singleField); // insert a unique text string between the field identifier and the field data
				$fieldLabelPlusDataArray = preg_split("/___LabelDataSplitter___/", $singleField); // split each field into a 2-element array containing [0] the field identifier and [1] the field data

				$fieldLabelPlusDataArray[1] = preg_replace("/\s{2,}/", " ", $fieldLabelPlusDataArray[1]); // remove any hard returns and extra spaces within the data string
				$fieldLabelPlusDataArray[1] = trim($fieldLabelPlusDataArray[1]); // remove any preceeding and trailing whitespace from the field data

				if (ereg("AU: Author", $fieldLabelPlusDataArray[0]))
					$fieldLabelPlusDataArray[1] = preg_replace("/\*/", "", $fieldLabelPlusDataArray[1]); // remove any asterisk ("*")

				elseif (ereg("ED: Editor", $fieldLabelPlusDataArray[0]))
					$fieldLabelPlusDataArray[1] = preg_replace("/ \(eds?\)(?= *$| *;)/", "", $fieldLabelPlusDataArray[1]); // remove " (ed)" and/or " (eds)"

				elseif (ereg("TI: Title|AB: Abstract", $fieldLabelPlusDataArray[0]))
				{
					if (ereg("TI: Title", $fieldLabelPlusDataArray[0]))
					{
						$fieldLabelPlusDataArray[1] = preg_replace("/--/", "-", $fieldLabelPlusDataArray[1]); // remove en-dash markup
						$fieldLabelPlusDataArray[1] = preg_replace("/ *\. *$/", "", $fieldLabelPlusDataArray[1]); // remove any dot from end of title
					}

					if (preg_match("/ su(b|per)\(.+?\)/", $fieldLabelPlusDataArray[1]))
						$fieldLabelPlusDataArray[1] = preg_replace("/ (su(?:b|per))\((.+?)\)/", "[\\1:\\2]", $fieldLabelPlusDataArray[1]); // transform " sub(...)" & " super(...)" markup into "[sub:...]" & "[super:...]" markup
					if (preg_match("/(?<= )mu /", $fieldLabelPlusDataArray[1]))
						$fieldLabelPlusDataArray[1] = preg_replace("/(?<= )mu /", "µ", $fieldLabelPlusDataArray[1]); // transform "mu " markup into "µ" markup
				}


				// BUILD FIELD PARAMETERS:
				// build an array of key/value pairs:

				// "AU: Author":
				if (ereg("AU: Author", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray['author'] = $fieldLabelPlusDataArray[1];

				// "TI: Title":
				elseif (ereg("TI: Title", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray['title'] = $fieldLabelPlusDataArray[1];

				// "PT: Publication Type":
				elseif (ereg("PT: Publication Type", $fieldLabelPlusDataArray[0])) // could also check for "DT: Document Type" (but DT was added only recently)
				{
					if (ereg("[;:,.]", $fieldLabelPlusDataArray[1])) // if the "PT: Publication Type" field contains a delimiter (e.g. like: "Journal Article; Conference")
					{
						$correctDocumentType = preg_replace("/(.+?)\s*[;:,.]\s*.*/", "\\1", $fieldLabelPlusDataArray[1]); // extract everything before this delimiter
						$additionalDocumentTypeInfo = preg_replace("/.*?\s*[;:,.]\s*(.+)/", "\\1", $fieldLabelPlusDataArray[1]); // extract everything after this delimiter
						$additionalDocumentTypeInfo = $additionalDocumentTypeInfo; // this info will be appended to any notes field data (see below)
					}
					else // we take the "PT: Publication Type" field contents as they are
						$correctDocumentType = $fieldLabelPlusDataArray[1];

					// Note that for books the "PT: Publication Type" field will always start with "Book Monograph", no matter whether the referenced
					// publication is a whole book or just a book chapter within that book! This is a design flaw within the CSA full record format.
					// So we can only apply some "good guessing" whether the current record actually references a complete book or just a book chapter:
					if (preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord)) // if the current record is of type "Book Monograph"
					{
						// and if the source field contains some page specification like "213 pp." (AND NOT something like "pp. 76-82" or "p. 216")...
						if (preg_match("/[\d,]+ *pp\b/i", $sourceField) AND !preg_match("/(?<=\W)pp?[. ]+[\w\/,-]+/i", $sourceField))
							$correctDocumentType = "Book Whole"; // ...we assume its a whole book
						else
							$correctDocumentType = "Book Chapter"; // ...otherwise we assume its a book chapter (which may NOT always be correct!)
					}

					$fieldParametersArray['type'] = $correctDocumentType;
				}

				// "PY: Publication Year":
				elseif (ereg("PY: Publication Year", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray['year'] = $fieldLabelPlusDataArray[1];

				// "JN: Journal Name":
				elseif (ereg("JN: Journal Name", $fieldLabelPlusDataArray[0]))
				{
					// if the current record is of type "Book Monograph" AND the field "JN: Journal Name" was given within the *original* record data (i.e., before adding stuff to it):
					if (preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord) AND preg_match("/^JN: Journal Name *[\r\n]+ {4,4}/m", $singleRecord))
						// for book monographs the publication title is given in "MT: Monograph Title"; if a "JN: Journal Name" was originally provided as well, we assume, it's the series title:
						$fieldParametersArray['series_title'] = $fieldLabelPlusDataArray[1];
					else
						$fieldParametersArray['publication'] = $fieldLabelPlusDataArray[1];
				}

				// "JA: Abbrev Journal Name":
				elseif (ereg("JA: Abbrev Journal Name", $fieldLabelPlusDataArray[0]))
				{
					if (preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord)) // if the current record is of type "Book Monograph"
						// for book monographs the publication title is given in "MT: Monograph Title"; if a "JA: Abbrev Journal Name" is provided as well, we assume, it's the abbreviated series title:
						$fieldParametersArray['abbrev_series_title'] = $fieldLabelPlusDataArray[1];
					else
						$fieldParametersArray['abbrev_journal'] = $fieldLabelPlusDataArray[1];
				}

				// "MT: Monograph Title":
				elseif (ereg("MT: Monograph Title", $fieldLabelPlusDataArray[0]))
				{
					// if the source field contains some page specification like "213 pp." (AND NOT something like "pp. 76-82" or "p. 216")...
					if (preg_match("/[\d,]+ *pp\b/i", $sourceField) AND !preg_match("/(?<=\W)pp?[. ]+[\w\/,-]+/i", $sourceField))
						// ...we assume its a whole book (see above comment), in which case we assign the monograph title to the series title field:
						$fieldParametersArray['series_title'] = $fieldLabelPlusDataArray[1];
					else
						$fieldParametersArray['publication'] = $fieldLabelPlusDataArray[1];
				}

				// "JV: Journal Volume":
				elseif (ereg("JV: Journal Volume", $fieldLabelPlusDataArray[0]))
				{
					if (preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord)) // if the current record is of type "Book Monograph"
						// for book monographs, if there's a volume given, we assume, it's the series volume:
						$fieldParametersArray['series_volume'] = $fieldLabelPlusDataArray[1];
					else
						$fieldParametersArray['volume'] = $fieldLabelPlusDataArray[1];
				}

				// "JI: Journal Issue":
				elseif (ereg("JI: Journal Issue", $fieldLabelPlusDataArray[0]))
				{
					if (preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord)) // if the current record is of type "Book Monograph"
						// for book monographs, if there's an issue given, we assume, it's the series issue:
						$fieldParametersArray['series_issue'] = $fieldLabelPlusDataArray[1];
					else
						$fieldParametersArray['issue'] = $fieldLabelPlusDataArray[1];
				}

				// "JP: Journal Pages":
				elseif (ereg("JP: Journal Pages", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray['pages'] = $fieldLabelPlusDataArray[1];

				// "AF: Affiliation" & "AF: Author Affilition":
				elseif (ereg("AF: (Author )?Affilia?tion", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray['address'] = $fieldLabelPlusDataArray[1];

				// "CA: Corporate Author":
				elseif (ereg("CA: Corporate Author", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray['corporate_author'] = $fieldLabelPlusDataArray[1];

				// "DE: Descriptors":
				elseif (ereg("DE: Descriptors", $fieldLabelPlusDataArray[0])) // currently, the fields "KW: Keywords" and "ID: Identifiers" are ignored!
					$fieldParametersArray['keywords'] = $fieldLabelPlusDataArray[1];

				// "AB: Abstract":
				elseif (ereg("AB: Abstract", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray['abstract'] = $fieldLabelPlusDataArray[1];

				// "PB: Publisher":
				elseif (ereg("PB: Publisher", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray['publisher'] = $fieldLabelPlusDataArray[1];

				// "ED: Editor":
				elseif (ereg("ED: Editor", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray['editor'] = $fieldLabelPlusDataArray[1];

				// "LA: Language":
				elseif (ereg("LA: Language", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray['language'] = $fieldLabelPlusDataArray[1];

				// "SL: Summary Language":
				elseif (ereg("SL: Summary Language", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray['summary_language'] = $fieldLabelPlusDataArray[1];

				// "OT: Original Title":
				elseif (ereg("OT: Original Title", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray['orig_title'] = $fieldLabelPlusDataArray[1];

				// "IS: ISSN":
				elseif (ereg("IS: ISSN", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray['issn'] = $fieldLabelPlusDataArray[1];

				// "IB: ISBN":
				elseif (ereg("IB: ISBN", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray['isbn'] = $fieldLabelPlusDataArray[1];

				// "ER: Environmental Regime":
				elseif (ereg("ER: Environmental Regime", $fieldLabelPlusDataArray[0]))
					$environmentalRegime = $fieldLabelPlusDataArray[1]; // this info will be appended to any notes field data (see below)

				// "CF: Conference":
				elseif (ereg("CF: Conference", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray['conference'] = $fieldLabelPlusDataArray[1];

				// "NT: Notes":
				elseif (ereg("NT: Notes", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray['notes'] = $fieldLabelPlusDataArray[1];

				// "DO: DOI":
				elseif (ereg("DO: DOI", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray['doi'] = $fieldLabelPlusDataArray[1];
			}
			// (END LOOP OVER EACH FIELD)


			if (!empty($showSource)) // if we're supposed to display the original source data
				// append original source field data (they will be presented within the header message of 'record.php' for easy comparison with the extracted data):
				$fieldParametersArray['source'] = $sourceField;

			// we'll hack the "notes" element in order to append additional info:
			// (this cannot be done earlier above since we don't know about the presence & order of fields within the source text!)
			if (!empty($additionalDocumentTypeInfo)) // if the "PT: Publication Type" field contains some additional info
			{
				if (isset($fieldParametersArray['notes'])) // and if the notes element is present
					$fieldParametersArray['notes'] = $fieldParametersArray['notes'] . "; " . $additionalDocumentTypeInfo; // append additional info from "PT: Publication Type" field
				else // the notes parameter wasn't specified yet
					$fieldParametersArray['notes'] = $additionalDocumentTypeInfo; // add notes element with additional info from "PT: Publication Type" field
			}

			if (!empty($environmentalRegime)) // if the "ER: Environmental Regime" field contains some data
			{
				if (isset($fieldParametersArray['notes'])) // and if the notes element is present
					$fieldParametersArray['notes'] = $fieldParametersArray['notes'] . "; " . $environmentalRegime; // append "ER: Environmental Regime" field data
				else // the notes parameter wasn't specified yet
					$fieldParametersArray['notes'] = $environmentalRegime; // add notes element with "ER: Environmental Regime" field data
			}

			// Append the array of extracted field data to the main data array which holds all records to import:
			$parsedRecordsArray[] = $fieldParametersArray;
		}
	}
	// (END LOOP OVER EACH RECORD)

	// --------------------------------------------------------------------

	// IMPORT RECORDS:

	if (count($importRecordNumbersRecognizedFormatArray) == 1) // if this is the only record we'll need to import:
	{
		// we can use '$fieldParametersArray' directly here, since it still holds the data of the *one* record that we're supposed to import
		foreach ($fieldParametersArray as $fieldParameterKey => $fieldParameterValue)
			$fieldParametersArray[$fieldParameterKey] = $fieldParameterKey . "=" . rawurlencode($fieldParameterValue); // copy parameter name and equals sign in front of parameter value

		$fieldParameters = implode("&", $fieldParametersArray); // merge list of parameters

		// RELOCATE TO IMPORT PAGE:
		// call 'record.php' and load the form fields with the data of the current record
		header("Location: record.php?recordAction=add&mode=import&importSource=csa&" . $fieldParameters);
		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}
	else // import record(s) directly:
	{
		// Build an array structure suitable for passing to the 'addRecords()' function:
		$importDataArray = array(); // for an explanation of the structure of '$importDataArray', see the comments above the 'addRecords()' function (in 'include.inc.php')
		$importDataArray['type'] = "refbase"; // we use the "refbase" array format
		$importDataArray['version'] = "1.0"; // we use version "1.0" of the array structure
		$importDataArray['creator'] = "http://refbase.net"; // calling script/importer is "refbase"
		$importDataArray['author'] = "Matthias Steffens"; // author/contact name of the person who's responsible for this script/importer
		$importDataArray['contact'] = "refbase@extracts.de"; // author's email/contact address
		$importDataArray['options'] = array('prefix_call_number' => "true"); // if "true", any 'call_number' string will be prefixed with the correct call number prefix of the currently logged-in user (e.g. 'IPÖ @ msteffens @ ')
		$importDataArray['records'] = $parsedRecordsArray; // this array will hold the record(s) (with each record being a sub-array of fields) -> but in this case, we'll import one record at a time

		// NOTES: - due to the nature of the CSA format, this importer doesn't provide input for the following fields:
		//          'place', 'series_editor', 'edition', 'medium', 'area', 'expedition', 'call_number', 'approved', 'file', 'thesis', 'url', 'contribution_id', 'online_publication', 'online_citation', 'orig_record'
		//        - the 'addRecords()' function will take care of the calculation fields ('first_author', 'author_count', 'first_page', 'volume_numeric' and 'series_volume_numeric')
		//        - similarly, the *date/*time/*by fields ('created_date', 'created_time', 'created_by', 'modified_date', 'modified_time', 'modified_by') will be filled automatically
		//          if no custom values (in correct date ['YYYY-MM-DD'] and time ['HH:MM:SS'] format) are given in the '$importDataArray'
		//        - we could pass any custom info for the 'location' field with the '$importDataArray', ommitting it here
		//          causes the 'addRecords()' function to insert name & email address of the currently logged-in user (e.g. 'Matthias Steffens (refbase@extracts.de)')
		//        - the serial number(s) will be assigned automatically (and returned by the 'addRecords()' function in form of an array)
		//        - we don't add anything to the 'user_data' table since the import data don't contain any user-specific data

		// Add all records to the database (i.e., for each record, add a row entry to MySQL table 'refs'):
		// ('$importedRecordsArray' will hold the serial numbers of all newly imported records)
		$importedRecordsArray = addRecords($importDataArray); // function 'addRecords()' is defined in 'include.inc.php'
	}

	// --------------------------------------------------------------------

	// (4) DISPLAY RESULTS

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
		header("Location: show.php?records=" . $recordSerialsQueryString . "&headerMsg=" . rawurlencode($headerMessage));
	}
	else // nothing imported
	{
		// we'll file again this additional error element here so that the 'errors' session variable isn't empty causing 'import_csa.php' to re-load the form data that were submitted by the user
		$errors["badRecords"] = "all";

		// save an appropriate error message:
		$HeaderString = "<b><span class=\"warning\">No records imported!</span></b>";

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		saveSessionVariable("errors", $errors);
		saveSessionVariable("formVars", $formVars);

		header("Location: " . $referer); // redirect to the calling page (normally, 'import_csa.php')
	}

	// --------------------------------------------------------------------
?>
