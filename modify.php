<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./modify.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    18-Dec-02, 23:08
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This php script will perform adding, editing & deleting of records.
	// It then calls 'receipt.php' which displays links to the modified/added record
	// as well as to the previous search results page (if any).
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

	// We check the '$_GET['proc']' variable in addition to '$_POST' in order to catch the case where the POSTed data are
	// greater than the value given in 'post_max_size' (in 'php.ini'). This may happen if the user uploads a large file.
	// Relevant quote from <http://de.php.net/ini.core>:
	// "If the size of post data is greater than post_max_size, the $_POST and $_FILES superglobals are empty.
	//  This can be tracked in various ways, e.g. by passing the $_GET variable to the script processing the data,
	//  i.e. <form action="edit.php?processed=1">, and then checking if $_GET['processed'] is set."
	if (isset($_GET['proc']) AND empty($_POST))
	{
		$maxPostDataSize = ini_get("post_max_size");
		// inform the user that the maximum post data size was exceeded:
		$HeaderString = returnMsg($loc["Warning_PostDataSizeMustNotExceed"] . " " . $maxPostDataSize . "!", "warning", "strong", "HeaderString"); // function 'returnMsg()' is defined in 'include.inc.php'

		header("Location: " . $referer); // redirect to 'record.php' (variable '$referer' is globally defined in function 'start_session()' in 'include.inc.php')

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// Write the (POST) form variables into an array:
	foreach($_POST as $varname => $value)
		$formVars[$varname] = trim($value); // remove any leading or trailing whitespace from the field's contents & copy the trimmed string to the '$formVars' array
//		$formVars[$varname] = trim(clean($value, 50)); // the use of the clean function would be more secure!

	// --------------------------------------------------------------------

	// Extract form variables sent through POST:
	// Note: Although we could use the '$formVars' array directly below (e.g.: $formVars['pageLoginStatus'] etc., like in 'user_validation.php'), we'll read out
	//       all variables individually again. This is done to enhance readability. (A smarter way of doing so seems be the use of the 'extract()' function, but that
	//       may expose yet another security hole...)

	// Extract the page's login status (which indicates the user's login status at the time the page was loaded):
	$pageLoginStatus = $formVars['pageLoginStatus'];

	// First of all, check if this script was called by something else than 'record.php':
	if (!ereg("/record\.php\?.+", $referer))
	{
		// return an appropriate error message:
		$HeaderString = returnMsg($loc["Warning_InvalidCallToScript"] . " '" . scriptURL() . "'!", "warning", "strong", "HeaderString"); // functions 'returnMsg()' and 'scriptURL()' are defined in 'include.inc.php'

		header("Location: " . $referer); // redirect to calling page

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}
	// If the referring page is 'record.php' (i.e., if this script was called by 'record.php'), check if the user is logged in:
	elseif ((!isset($_SESSION['loginEmail'])) OR ($pageLoginStatus != "logged in")) // if the user isn't logged in -OR- the page's login status still does NOT state "logged in" (since the page wasn't reloaded after the user logged in elsewhere)
	{
		// the user is logged in BUT the page's login status still does NOT state "logged in" (since the page wasn't reloaded after the user logged IN elsewhere):
		if ((isset($_SESSION['loginEmail'])) AND ($pageLoginStatus != "logged in"))
		{
			// return an appropriate error message:
			$HeaderString = returnMsg($loc["Warning_PageStatusOutDated"] . "!", "warning", "strong", "HeaderString", "", "<br>" . $loc["Warning_RecordDataReloaded"] . ":"); // function 'returnMsg()' is defined in 'include.inc.php'

			header("Location: " . $referer); // redirect to 'record.php'

			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}

		// the user is NOT logged in BUT the page's login status still states that he's "logged in" (since the page wasn't reloaded after the user logged OUT elsewhere):
		if ((!isset($_SESSION['loginEmail'])) AND ($pageLoginStatus == "logged in"))
		{
			// return an appropriate error message:
			$HeaderString = returnMsg($loc["Warning_NotLoggedInAnymore"] . "!", "warning", "strong", "HeaderString", "", "<br>" . $loc["Warning_TimeOutPleaseLoginAgain"] . ":"); // function 'returnMsg()' is defined in 'include.inc.php'
		}

		// else if the user isn't logged in yet: ((!isset($_SESSION['loginEmail'])) AND ($pageLoginStatus != "logged in"))
		header("Location: user_login.php?referer=" . rawurlencode($referer)); // ask the user to login first, then he'll get directed back to 'record.php'

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// if we made it here, the user is regularly logged in: (isset($_SESSION['loginEmail'] == true) AND ($pageLoginStatus == "logged in")


	// Extract the form used by the user:
	$formType = $formVars['formType'];

	// Extract the type of action requested by the user (either 'add', 'edit' or ''):
	$recordAction = $formVars['recordAction'];

	// $recordAction == '' will be treated equal to 'add':
	if (empty($recordAction))
		$recordAction = "add"; // we set it explicitly here so that we can employ this variable within message strings, etc

	// Determine the button that was hit by the user (either 'Add Record', 'Edit Record', 'Delete Record' or ''):
	// '$submitAction' is only used to determine any 'delet' action! (where '$submitAction' = 'Delete Record')
	// (otherwise, only '$recordAction' controls how to proceed)
	$submitAction = $formVars['submit'];
	if (encodeHTML($submitAction) == $loc["ButtonTitle_DeleteRecord"]) // note that we need to HTML encode '$submitAction' for comparison with the HTML encoded locales
		$recordAction = "delet"; // *delete* record


	// now, check if the (logged in) user is allowed to perform the current record action (i.e., add, edit or delete a record):
	$notPermitted = false;

	// if the (logged in) user...
	if ($recordAction == "edit") // ...wants to edit the current record...
	{
		if (isset($_SESSION['user_permissions']) AND !ereg("allow_edit", $_SESSION['user_permissions'])) // ...BUT the 'user_permissions' session variable does NOT contain 'allow_edit'...
		{
			$notPermitted = true;
			$HeaderString = $loc["NoPermission"] . $loc["NoPermission_ForEditRecord"] . "!";
		}
	}
	elseif ($recordAction == "delet") // ...wants to delete the current record...
	{	
		if (isset($_SESSION['user_permissions']) AND !ereg("allow_delete", $_SESSION['user_permissions'])) // ...BUT the 'user_permissions' session variable does NOT contain 'allow_delete'...
		{
			$notPermitted = true;
			$HeaderString = $loc["NoPermission"] . $loc["NoPermission_ForDeleteRecord"] . "!";
		}
	}
	else // if ($recordAction == "add" OR $recordAction == "") // ...wants to add the current record...
	{	
		if (isset($_SESSION['user_permissions']) AND !ereg("allow_add", $_SESSION['user_permissions'])) // ...BUT the 'user_permissions' session variable does NOT contain 'allow_add'...
		{
			$notPermitted = true;
			$HeaderString = $loc["NoPermission"] . $loc["NoPermission_ForAddRecords"] . "!";
		}
	}

	if ($notPermitted)
	{
		// return an appropriate error message:
		$HeaderString = returnMsg($HeaderString, "warning", "strong", "HeaderString"); // function 'returnMsg()' is defined in 'include.inc.php'

		header("Location: " . $referer); // redirect to 'record.php'

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}


	// if we made it here, we assume that the user is allowed to perform the current record action

	// Get the query URL of the formerly displayed results page:
	if (isset($_SESSION['oldQuery']))
		$oldQuery = $_SESSION['oldQuery'];
	else
		$oldQuery = array();

	// Get the query URL of the last multi-record query:
	if (isset($_SESSION['oldMultiRecordQuery']))
		$oldMultiRecordQuery = $_SESSION['oldMultiRecordQuery'];
	else
		$oldMultiRecordQuery = "";


	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'


	// Extract all form values provided by 'record.php':
	if (isset($formVars['authorName']))
		$authorName = $formVars['authorName'];
	else
		$authorName = "";

	if (isset($formVars['isEditorCheckBox']))
		$isEditorCheckBox = $formVars['isEditorCheckBox'];
	else
		$isEditorCheckBox = "";

	if (isset($formVars['titleName']))
		$titleName = $formVars['titleName'];
	else
		$titleName = "";

	if (isset($formVars['yearNo']))
		$yearNo = $formVars['yearNo'];
	else
		$yearNo = "";

	if (isset($formVars['publicationName']))
		$publicationName = $formVars['publicationName'];
	else
		$publicationName = "";

	if (isset($formVars['abbrevJournalName']))
		$abbrevJournalName = $formVars['abbrevJournalName'];
	else
		$abbrevJournalName = "";

	if (isset($formVars['volumeNo']))
		$volumeNo = $formVars['volumeNo'];
	else
		$volumeNo = "";

	if (isset($formVars['issueNo']))
		$issueNo = $formVars['issueNo'];
	else
		$issueNo = "";

	if (isset($formVars['pagesNo']))
		$pagesNo = $formVars['pagesNo'];
	else
		$pagesNo = "";

	if (isset($formVars['addressName']))
		$addressName = $formVars['addressName'];
	else
		$addressName = "";

	if (isset($formVars['corporateAuthorName']))
		$corporateAuthorName = $formVars['corporateAuthorName'];
	else
		$corporateAuthorName = "";

	if (isset($formVars['keywordsName']))
		$keywordsName = $formVars['keywordsName'];
	else
		$keywordsName = "";

	if (isset($formVars['abstractName']))
		$abstractName = $formVars['abstractName'];
	else
		$abstractName = "";

	if (isset($formVars['publisherName']))
		$publisherName = $formVars['publisherName'];
	else
		$publisherName = "";

	if (isset($formVars['placeName']))
		$placeName = $formVars['placeName'];
	else
		$placeName = "";

	if (isset($formVars['editorName']))
		$editorName = $formVars['editorName'];
	else
		$editorName = "";

	if (isset($formVars['languageName']))
		$languageName = $formVars['languageName'];
	else
		$languageName = "";

	if (isset($formVars['summaryLanguageName']))
		$summaryLanguageName = $formVars['summaryLanguageName'];
	else
		$summaryLanguageName = "";

	if (isset($formVars['origTitleName']))
		$origTitleName = $formVars['origTitleName'];
	else
		$origTitleName = "";

	if (isset($formVars['seriesEditorName']))
		$seriesEditorName = $formVars['seriesEditorName'];
	else
		$seriesEditorName = "";

	if (isset($formVars['seriesTitleName']))
		$seriesTitleName = $formVars['seriesTitleName'];
	else
		$seriesTitleName = "";

	if (isset($formVars['abbrevSeriesTitleName']))
		$abbrevSeriesTitleName = $formVars['abbrevSeriesTitleName'];
	else
		$abbrevSeriesTitleName = "";

	if (isset($formVars['seriesVolumeNo']))
		$seriesVolumeNo = $formVars['seriesVolumeNo'];
	else
		$seriesVolumeNo = "";

	if (isset($formVars['seriesIssueNo']))
		$seriesIssueNo = $formVars['seriesIssueNo'];
	else
		$seriesIssueNo = "";

	if (isset($formVars['editionNo']))
		$editionNo = $formVars['editionNo'];
	else
		$editionNo = "";

	if (isset($formVars['issnName']))
		$issnName = $formVars['issnName'];
	else
		$issnName = "";

	if (isset($formVars['isbnName']))
		$isbnName = $formVars['isbnName'];
	else
		$isbnName = "";

	if (isset($formVars['mediumName']))
		$mediumName = $formVars['mediumName'];
	else
		$mediumName = "";

	if (isset($formVars['areaName']))
		$areaName = $formVars['areaName'];
	else
		$areaName = "";

	if (isset($formVars['expeditionName']))
		$expeditionName = $formVars['expeditionName'];
	else
		$expeditionName = "";

	if (isset($formVars['conferenceName']))
		$conferenceName = $formVars['conferenceName'];
	else
		$conferenceName = "";

	if (isset($formVars['notesName']))
		$notesName = $formVars['notesName'];
	else
		$notesName = "";

	if (isset($formVars['approvedRadio']))
		$approvedRadio = $formVars['approvedRadio'];
	else
		$approvedRadio = "";

	if (isset($formVars['locationName']))
		$locationName = $formVars['locationName'];
	else
		$locationName = "";

	$callNumberName = $formVars['callNumberName'];
	if (ereg("%40|%20", $callNumberName)) // if '$callNumberName' still contains URL encoded data... ('%40' is the URL encoded form of the character '@', '%20' a space, see note below!)
		$callNumberName = rawurldecode($callNumberName); // ...URL decode 'callNumberName' variable contents (it was URL encoded before incorporation into a hidden tag of the 'record' form to avoid any HTML syntax errors)
														// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_POST'!
														//       But, opposed to that, URL encoded data that are included within a form by means of a *hidden form tag* will NOT get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!

	if (isset($formVars['callNumberNameUserOnly']))
		$callNumberNameUserOnly = $formVars['callNumberNameUserOnly'];
	else
		$callNumberNameUserOnly = "";

	if (isset($formVars['serialNo']))
		$serialNo = $formVars['serialNo'];
	else
		$serialNo = "";

	if (isset($formVars['typeName']))
		$typeName = $formVars['typeName'];
	else
		$typeName = "";

	if (isset($formVars['thesisName']))
		$thesisName = $formVars['thesisName'];
	else
		$thesisName = "";

	if (isset($formVars['markedRadio']))
		$markedRadio = $formVars['markedRadio'];
	else
		$markedRadio = "";

	if (isset($formVars['copyName']))
		$copyName = $formVars['copyName'];
	else
		$copyName = "";

	if (isset($formVars['selectedRadio']))
		$selectedRadio = $formVars['selectedRadio'];
	else
		$selectedRadio = "";

	if (isset($formVars['userKeysName']))
		$userKeysName = $formVars['userKeysName'];
	else
		$userKeysName = "";

	if (isset($formVars['userNotesName']))
		$userNotesName = $formVars['userNotesName'];
	else
		$userNotesName = "";

	if (isset($formVars['userFileName']))
		$userFileName = $formVars['userFileName'];
	else
		$userFileName = "";

	if (isset($formVars['userGroupsName']))
		$userGroupsName = $formVars['userGroupsName'];
	else
		$userGroupsName = "";

	if (isset($formVars['citeKeyName']))
		$citeKeyName = $formVars['citeKeyName'];
	else
		$citeKeyName = "";

	if (isset($formVars['relatedName']))
		$relatedName = $formVars['relatedName'];
	else
		$relatedName = "";

	// if the current user has no permission to download (and hence view) any files, 'record.php' does only show an empty string
	// in the 'file' field (no matter if a file exists for the given record or not). Thus, we need to make sure that the empty
	// form value won't overwrite any existing contents of the 'file' field on UPDATE and that the correct field value gets
	// transferred to table 'deleted' on DELETE:
	// Therefore, we re-fetch the contents of the 'file' field if NONE of the following conditions are met:
	// - the variable '$fileVisibility' (defined in 'ini.inc.php') is set to 'everyone'
	// - the variable '$fileVisibility' is set to 'login' AND the user is logged in
	// - the variable '$fileVisibility' is set to 'user-specific' AND the 'user_permissions' session variable contains 'allow_download'
	if (ereg("^(edit|delet)$", $recordAction) AND (!($fileVisibility == "everyone" OR ($fileVisibility == "login" AND isset($_SESSION['loginEmail'])) OR ($fileVisibility == "user-specific" AND (isset($_SESSION['user_permissions']) AND ereg("allow_download", $_SESSION['user_permissions'])))))) // user has NO permission to download (and view) any files
	{
		$queryFile = "SELECT file FROM $tableRefs WHERE serial = " . quote_smart($serialNo);

		$result = queryMySQLDatabase($queryFile); // RUN the query on the database through the connection
		$row = @ mysql_fetch_array($result);

		$fileName = $row["file"];
	}
	else // user has permission to download (and view) any files
		$fileName = $formVars['fileName'];

	if (isset($formVars['urlName']))
		$urlName = $formVars['urlName'];
	else
		$urlName = "";

	if (isset($formVars['doiName']))
		$doiName = $formVars['doiName'];
	else
		$doiName = "";

	if (isset($formVars['contributionIDName']))
		$contributionID = $formVars['contributionIDName'];
	else
		$contributionID = "";

	$contributionID = rawurldecode($contributionID); // URL decode 'contributionID' variable contents (it was URL encoded before incorporation into a hidden tag of the 'record' form to avoid any HTML syntax errors) [see above!]

	if (isset($formVars['contributionIDCheckBox']))
		$contributionIDCheckBox = $formVars['contributionIDCheckBox'];
	else
		$contributionIDCheckBox = "";

	if (isset($formVars['locationSelectorName']))
		$locationSelectorName = $formVars['locationSelectorName'];
	else
		$locationSelectorName = "";

	if (isset($formVars['onlinePublicationCheckBox']))
		$onlinePublicationCheckBox = $formVars['onlinePublicationCheckBox'];
	else
		$onlinePublicationCheckBox = "";

	if (isset($formVars['onlineCitationName']))
		$onlineCitationName = $formVars['onlineCitationName'];
	else
		$onlineCitationName = "";

	if (isset($formVars['createdDate']))
		$createdDate = $formVars['createdDate'];
	else
		$createdDate = "";

	if (isset($formVars['createdTime']))
		$createdTime = $formVars['createdTime'];
	else
		$createdTime = "";

	if (isset($formVars['createdBy']))
		$createdBy = $formVars['createdBy'];
	else
		$createdBy = "";

	if (isset($formVars['modifiedDate']))
		$modifiedDate = $formVars['modifiedDate'];
	else
		$modifiedDate = "";

	if (isset($formVars['modifiedTime']))
		$modifiedTime = $formVars['modifiedTime'];
	else
		$modifiedTime = "";

	if (isset($formVars['modifiedBy']))
		$modifiedBy = $formVars['modifiedBy'];
	else
		$modifiedBy = "";

	if (isset($formVars['origRecord']))
		$origRecord = $formVars['origRecord'];
	else
		$origRecord = "";

	// check if a file was uploaded:
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

	// --------------------------------------------------------------------

	// VALIDATE data fields:

	// NOTE: for all fields that are validated here must exist error parsing code (of the form: " . fieldError("languageName", $errors) . ")
	//       in front of the respective <input> form field in 'record.php'! Otherwise the generated error won't be displayed!

	// Validate fields that MUST not be empty:
	// Validate the 'Call Number' field:
	if (ereg("[@;]", $callNumberNameUserOnly))
		$errors["callNumberNameUserOnly"] = "Your call number cannot contain the characters '@' and ';' (since they function as delimiters):"; // the user's personal reference ID cannot contain the characters '@' and ';' since they are used as delimiters (within or between call numbers)
	elseif ($recordAction == "edit" AND !empty($callNumberNameUserOnly) AND !ereg("$loginEmail", $locationName) AND !ereg("^(add|remove)$", $locationSelectorName)) // if the user specified some reference ID within an 'edit' action -BUT- there's no existing call number for this user within the contents of the 'location' field -AND- the user doesn't want to add it either...
		$errors["callNumberNameUserOnly"] = "You cannot specify a call number unless you add this record to your personal literature set! This can be done by setting the 'Location Field' popup below to 'add'."; // warn the user that he/she has to set the Location Field popup to 'add' if he want's to add this record to his personal literature set

	// Validate the 'uploadFile' field:
	// (whose file name characters must be within [a-zA-Z0-9+_.-] and which must not exceed
	//  the 'upload_max_filesize' specified within your 'php.ini' configuration file)
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
				if (eregi("\.(exe|com|bat|zip|php|phps|php3|cgi)$", $uploadFile["name"])) // file name has an invalid file name extension (adjust the regex pattern if you want more relaxed file name validation)
					$errors["uploadFile"] = "You cannot upload this type of file!"; // file name must not end with .exe, .com, .bat, .zip, .php, .phps, .php3 or .cgi

				if ($renameUploadedFiles != "yes") // if we do NOT rename files according to a standard naming scheme (variable '$renameUploadedFiles' is defined in 'ini.inc.php')
				{
					// check for invalid file name characters:
					if (!preg_match("/^[" . $allowedFileNameCharacters . "]+$/", $uploadFile["name"])) // file name contains invalid characters (variable '$allowedFileNameCharacters' is defined in 'ini.inc.php')
						$errors["uploadFile"] = "File name characters can only be within " . $allowedFileNameCharacters; // characters of file name must be within range given in '$allowedFileNameCharacters'
						// previous error message was a bit more user-friendly: "File name characters can only be alphanumeric ('a-zA-Z0-9'), plus ('+'), minus ('-'), substring ('_') or a dot ('.'):"
				}
			}
		}
		else
		{
			// I'm not sure if this actually works --RAK
			switch($uploadFile["error"])
			{
				case 0: // no error; possible file attack!
					$errors["uploadFile"] = "There was a problem with your upload.";
					break;
				case 1: // uploaded file exceeds the 'upload_max_filesize' directive in 'php.ini'
					$maxFileSize = ini_get("upload_max_filesize");
					$fileError = "File size must not be greater than " . $maxFileSize . ":";
					$errors["uploadFile"] = $fileError;
					break;
				case 2: // uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form (Note: refbase doesn't currently specify MAX_FILE_SIZE but anyhow...)
					$errors["uploadFile"] = "The file you are trying to upload is too big.";
					break;
				case 3: // uploaded file was only partially uploaded
					$errors["uploadFile"] = "The file you are trying to upload was only partially uploaded.";
					break;
				case 4: // no file was uploaded
					$errors["uploadFile"] = "You must select a file for upload.";
					break;
				case 6:
					$errors["uploadFile"] = "Missing a temporary folder.";
					break;
				default: // a default error, just in case!  :)
					$errors["uploadFile"] = "There was a problem with your upload.";
					break;
			}
		}
	}



	// CAUTION: validation of other fields is currently disabled, since, IMHO, there are too many open questions how to implement this properly
	//          and without frustrating the user! Uncomment the commented code below to enable the current validation features:

//	// Validate fields that SHOULD not be empty:
//	// Validate the 'Author' field:
//	if (empty($authorName))
//		$errors["authorName"] = "Is there really no author info for this record? Enter NULL to force empty:"; // Author should not be a null string
//
//	// Validate the 'Title' field:
//	if (empty($titleName))
//		$errors["titleName"] = "Is there really no title info for this record? Enter NULL to force empty:"; // Title should not be a null string
//
//	// Validate the 'Year' field:
//	if (empty($yearNo))
//		$errors["yearNo"] = "Is there really no year info for this record? Enter NULL to force empty:"; // Year should not be a null string
//
//	// Validate the 'Publication' field:
//	if (empty($publicationName))
//		$errors["publicationName"] = "Is there really no publication info for this record? Enter NULL to force empty:"; // Publication should not be a null string
//
//	// Validate the 'Abbrev Journal' field:
//	if (empty($abbrevJournalName))
//		$errors["abbrevJournalName"] = "Is there really no abbreviated journal info for this record? Enter NULL to force empty:"; // Abbrev Journal should not be a null string
//
//	// Validate the 'Volume' field:
//	if (empty($volumeNo))
//		$errors["volumeNo"] = "Is there really no volume info for this record? Enter NULL to force empty:"; // Volume should not be a null string
//
//	// Validate the 'Pages' field:
//	if (empty($pagesNo))
//		$errors["pagesNo"] = "Is there really no pages info for this record? Enter NULL to force empty:"; // Pages should not be a null string
//
//
//	// Validate fields that MUST not be empty:
//	// Validate the 'Language' field:
//	if (empty($languageName))
//		$errors["languageName"] = "The language field cannot be blank:"; // Language cannot be a null string
//
//
//	// Remove 'NULL' values that were entered by the user in order to force empty values for required text fields:
//	// (for the required number fields 'yearNo' & 'volumeNo' inserting 'NULL' will cause '0000' or '0' as value, respectively)
//	if ($authorName == "NULL")
//		$authorName = "";
//
//	if ($titleName == "NULL")
//		$titleName = "";
//
//	if ($publicationName == "NULL")
//		$publicationName = "";
//
//	if ($abbrevJournalName == "NULL")
//		$abbrevJournalName = "";
//
//	if ($pagesNo == "NULL")
//		$pagesNo = "";

	// --------------------------------------------------------------------

	// Now the script has finished the validation, check if there were any errors:
	if (count($errors) > 0)
	{
		// Write back session variables:
		saveSessionVariable("errors", $errors); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		saveSessionVariable("formVars", $formVars);

		// There are errors. Relocate back to the record entry form:
		header("Location: " . $referer);

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// If we made it here, then the data is considered valid!

	// CONSTRUCT SQL QUERY:

	// First, setup some required variables:
	// Get the current date (e.g. '2003-12-31'), time (e.g. '23:59:49') and user name & email address (e.g. 'Matthias Steffens (refbase@extracts.de)'):
	list ($currentDate, $currentTime, $currentUser) = getCurrentDateTimeUser(); // function 'getCurrentDateTimeUser()' is defined in 'include.inc.php'

	// Build a correct call number prefix for the currently logged-in user (e.g. 'IPÖ @ msteffens'):
	$callNumberPrefix = getCallNumberPrefix(); // function 'getCallNumberPrefix()' is defined in 'include.inc.php'


	// provide some magic that figures out what do to depending on the state of the 'is Editor' check box
	// and the content of the 'author', 'editor' and 'type' fields:
	if ($isEditorCheckBox == "1" OR ereg("^(Book Whole|Conference Volume|Journal|Manuscript|Map)$", $typeName)) // if the user did mark the 'is Editor' checkbox -OR- if the record type is either 'Book Whole', 'Conference Volume', 'Journal', 'Map' or 'Manuscript'...
		if (!empty($editorName) AND empty($authorName)) // ...and if the 'Editor' field has some content while the 'Author' field is blank...
		{
			$authorName = $editorName; // duplicate field contents from 'editor' to 'author' field
			$isEditorCheckBox = "1"; // since the user entered something in the 'editor' field (but not the 'author' field), we need to make sure that the 'is Editor' is marked
		}

	if ($isEditorCheckBox == "1" AND ereg("^(Book Whole|Conference Volume|Journal|Manuscript|Map)$", $typeName)) // if the user did mark the 'is Editor' checkbox -AND- the record type is either 'Book Whole', 'Conference Volume', 'Journal', 'Map' or 'Manuscript'...
	{
		$authorName = ereg_replace(" *\(eds?\)$","",$authorName); // ...remove any existing editor info from the 'author' string, i.e., kill any trailing " (ed)" or " (eds)"

		if (!empty($authorName)) // if the 'Author' field has some content...
			$editorName = $authorName; // ...duplicate field contents from 'author' to 'editor' field (CAUTION: this will overwrite any existing contents in the 'editor' field!)

		if (!empty($authorName)) // if 'author' field isn't empty
		{
			if (!ereg(";", $authorName)) // if the 'author' field does NOT contain a ';' (which would delimit multiple authors) => single author
				$authorName .= " (ed)"; // append " (ed)" to the end of the 'author' string
			else // the 'author' field does contain at least one ';' => multiple authors
				$authorName .= " (eds)"; // append " (eds)" to the end of the 'author' string
		}
	}
	else // the 'is Editor' checkbox is NOT checked -OR- the record type is NOT 'Book Whole', 'Conference Volume', 'Journal', 'Map' or 'Manuscript'...
	{
		if (ereg(" *\(eds?\)$", $authorName)) // if 'author' field ends with either " (ed)" or " (eds)"
			$authorName = ereg_replace(" *\(eds?\)$","",$authorName); // remove any existing editor info from the 'author' string, i.e., kill any trailing " (ed)" or " (eds)"

		if ($authorName == $editorName) // if the 'Author' field contents equal the 'Editor' field contents...
			$editorName = ""; // ...clear contents of 'editor' field (that is, we assume that the user did uncheck the 'is Editor' checkbox, which was previously marked)
	}


	// Assign correct values to the calculation fields 'first_author', 'author_count', 'first_page', 'volume_numeric' and 'series_volume_numeric':
	// function 'generateCalculationFieldContent()' is defined in 'include.inc.php'
	// NOTE: this function call won't be necessary anymore when we've also moved database INSERTs and DELETEs to dedicated functions (which would take care of calculation fields then) -> compare with 'addRecords()' function
	list ($firstAuthor, $authorCount, $firstPage, $volumeNumericNo, $seriesVolumeNumericNo) = generateCalculationFieldContent($authorName, $pagesNo, $volumeNo, $seriesVolumeNo);


	// manage 'location' field data:
	if ((($locationSelectorName == "add") OR ($locationSelectorName == "")) AND (!ereg("$loginEmail", $locationName))) // add the current user to the 'location' field (if he/she isn't listed already within the 'location' field):
	// note: if the current user is NOT logged in -OR- if any normal user is logged in, the value for '$locationSelectorName' will be always '' when performing an INSERT,
	//       since the popup is fixed to 'add' and disabled (which, in turn, will result in an empty value to be returned)
	{
		// if the 'location' field is either completely empty -OR- does only contain the information string (that shows up on 'add' for normal users):
		if (ereg("^(" . $loc["your name & email address will be filled in automatically"] . ")?$", encodeHTML($locationName))) // note that we need to HTML encode '$locationName' for comparison with the HTML encoded locales
			$locationName = ereg_replace("^.*$", "$currentUser", $locationName);
		else // if the 'location' field does already contain some user content:
			$locationName = ereg_replace("^(.+)$", "\\1; $currentUser", $locationName);
	}
	elseif ($locationSelectorName == "remove") // remove the current user from the 'location' field:
	{ // the only pattern that's really unique is the users email address, the user's name may change (since it can be modified by the user). This is why we dont use '$currentUser' here:
		$locationName = ereg_replace("^[^;]*\( *$loginEmail *\) *; *", "", $locationName); // the current user is listed at the very beginning of the 'location' field
		$locationName = ereg_replace(" *;[^;]*\( *$loginEmail *\) *", "", $locationName); // the current user occurs after some other user within the 'location' field
		$locationName = ereg_replace("^[^;]*\( *$loginEmail *\) *$", "", $locationName); // the current user is the only one listed within the 'location' field
	}
	// else if '$locationSelectorName' == "don't touch" -OR- if the user is already listed within the 'location' field, we just accept the contents of the 'location' field as entered by the user


	// manage 'call_number' field data:
	if ($loginEmail != $adminLoginEmail) // if any normal user is logged in (not the admin):
	{
		if (ereg("$loginEmail", $locationName)) // we only process the user's call number information if the current user is listed within the 'location' field:
		{
			// Note that, for normal users, we process the user's call number information even if the '$locationSelectorName' is NOT set to 'add'.
			// This is done, since the user should be able to update his/her personal reference ID while the '$locationSelectorName' is set to 'don't touch'.
			// If the '$locationSelectorName' is set to 'remove', then any changes made to the personal reference ID will be discarded anyhow.

			// build a correct call number string for the current user & record:
			if ($callNumberNameUserOnly == "") // if the user didn't enter any personal reference ID for this record...
				$callNumberNameUserOnly = $callNumberPrefix . " @ "; // ...insert the user's call number prefix only
			else // if the user entered (or modified) his/her personal reference ID for this record...
				$callNumberNameUserOnly = $callNumberPrefix . " @ " . $callNumberNameUserOnly; // ...prefix the entered reference ID with the user's call number prefix

			// insert or update the user's call number within the full contents of the 'call_number' field:
			if ($callNumberName == "") // if the 'call_number' field is empty...
				$callNumberName = $callNumberNameUserOnly; // ...insert the user's call number prefix
			elseif (ereg("$callNumberPrefix", $callNumberName)) // if the user's call number prefix occurs within the contents of the 'call_number' field...
				$callNumberName = ereg_replace("$callNumberPrefix *@ *[^@;]*", "$callNumberNameUserOnly", $callNumberName); // ...replace the user's *own* call number within the full contents of the 'call_number' field
			else // if the 'call_number' field does already have some content -BUT- there's no existing call number prefix for the current user...
				$callNumberName = $callNumberName . "; " . $callNumberNameUserOnly; // ...append the user's call number to any existing call numbers
		}
	}
	else // if the admin is logged in:
		if ($locationSelectorName == "add") // we only add the admin's call number information if he/she did set the '$locationSelectorName' to 'add'
		{
			if ($callNumberName == "") // if there's no call number info provided by the admin...
				$callNumberName = $callNumberPrefix . " @ "; // ...insert the admin's call number prefix
			elseif (!ereg("@", $callNumberName)) // if there's a call number provided by the admin that does NOT contain any '@' already...
				$callNumberName = $callNumberPrefix . " @ " . $callNumberName; // ...then we assume the admin entered a personal refernce ID for this record which should be prefixed with his/her call number prefix
			// the contents of the 'call_number' field do contain the '@' character, i.e. we assume one or more full call numbers to be present
			elseif (!ereg("$callNumberPrefix", $callNumberName)) // if the admin's call number prefix does NOT already occur within the contents of the 'call_number' field...
			{
				if (ereg("; *[^ @;][^@;]*$", $callNumberName)) // for the admin we offer autocompletion of the call number prefix if he/she just enters his/her reference ID after the last full call number (separated by '; ')
					// e.g., the string 'IPÖ @ mschmid @ 123; 1778' will be autocompleted to 'IPÖ @ mschmid @ 123; IPÖ @ msteffens @ 1778' (with 'msteffens' being the admin user)
					$callNumberName = ereg_replace("^(.+); *([^@;]+)$", "\\1; $callNumberPrefix @ \\2", $callNumberName); // insert the admin's call number prefix before any reference ID that stand's at the end of the string of call numbers
				else
					$callNumberName = $callNumberName . "; " . $callNumberPrefix . " @ "; // ...append the admin's call number prefix to any existing call numbers
			}
		}
		// otherwise we simply use the information as entered by the admin

	if ($locationSelectorName == "remove") // remove the current user's call number from the 'call_number' field:
	{
		$callNumberName = ereg_replace("^ *$callNumberPrefix *@ *[^@;]*; *", "", $callNumberName); // the user's call number is listed at the very beginning of the 'call_number' field
		$callNumberName = ereg_replace(" *; *$callNumberPrefix *@ *[^@;]*", "", $callNumberName); // the user's call number occurs after some other call number within the 'call_number' field
		$callNumberName = ereg_replace("^ *$callNumberPrefix *@ *[^@;]*$", "", $callNumberName); // the user's call number is the only one listed within the 'call_number' field
	}


	// handle file uploads:
	if (ereg("^(edit|delet)$", $recordAction)) // we exclude '$recordAction = "add"' here, since file name generation needs to be done *after* the record has been created and a serial number is available
	{
		if (!empty($uploadFile) && !empty($uploadFile["tmp_name"])) // if there was a file uploaded successfully
			// process information of any file that was uploaded, auto-generate a file name if required and move the file to the appropriate directory:
			$fileName = handleFileUploads($uploadFile, $formVars);
	}


	// check if we need to set the 'contribution_id' field:
	// (we'll make use of the session variable '$abbrevInstitution' here)
	if ($contributionIDCheckBox == "1") // the user want's to add this record to the list of publications that were published by a member of his institution
	{
		if (!empty($contributionID)) // if the 'contribution_id' field is NOT empty...
		{
			if (!ereg("$abbrevInstitution", $contributionID)) // ...and the current user's 'abbrev_institution' value isn't listed already within the 'contribution_id' field
				$contributionID = $contributionID . "; " . $abbrevInstitution; // append the user's 'abbrev_institution' value to the end of the 'contribution_id' field
		}
		else // the 'contribution_id' field is empty
			$contributionID = $abbrevInstitution; // insert the current user's 'abbrev_institution' value
	}
	else // if present, remove the current user's abbreviated institution name from the 'contribution_id' field:
	{
		if (ereg("$abbrevInstitution", $contributionID)) // if the current user's 'abbrev_institution' value is listed within the 'contribution_id' field, we'll remove it:
		{
			$contributionID = ereg_replace("^ *$abbrevInstitution *[^;]*; *", "", $contributionID); // the user's abbreviated institution name is listed at the very beginning of the 'contribution_id' field
			$contributionID = ereg_replace(" *; *$abbrevInstitution *[^;]*", "", $contributionID); // the user's abbreviated institution name occurs after some other institutional abbreviation within the 'contribution_id' field
			$contributionID = ereg_replace("^ *$abbrevInstitution *[^;]*$", "", $contributionID); // the user's abbreviated institution name is the only one listed within the 'contribution_id' field
		}
	}


	// check if we need to set the 'online_publication' field:
	if ($onlinePublicationCheckBox == "1") // the user did mark the "Online publication" checkbox
		$onlinePublication = "yes";
	else
		$onlinePublication = "no";


	// remove any meaningless delimiter(s) from the beginning or end of a field string:
	// Note:  - this cleanup is only done for fields that may contain sub-elements, which are the fields:
	//          'author', 'keywords', 'place', 'language', 'summary_language', 'area', 'user_keys' and 'user_groups'
	//        - currently, only the semicolon (optionally surrounded by whitespace) is supported as sub-element delimiter
	$authorName = trimTextPattern($authorName, "( *; *)+", true, true); // function 'trimTextPattern()' is defined in 'include.inc.php'
	$keywordsName = trimTextPattern($keywordsName, "( *; *)+", true, true);
	$placeName = trimTextPattern($placeName, "( *; *)+", true, true);
	$languageName = trimTextPattern($languageName, "( *; *)+", true, true);
	$summaryLanguageName = trimTextPattern($summaryLanguageName, "( *; *)+", true, true);
	$areaName = trimTextPattern($areaName, "( *; *)+", true, true);
	$userKeysName = trimTextPattern($userKeysName, "( *; *)+", true, true);
	$userGroupsName = trimTextPattern($userGroupsName, "( *; *)+", true, true);

	$queryDeleted = ""; // initialize the '$queryDeleted' variable in order to prevent 'Undefined variable...' messages

	// Is this an update?
	if ($recordAction == "edit") // alternative method to check for an 'edit' action: if (ereg("^[0-9]+$",$serialNo)) // a valid serial number must be an integer
								// yes, the form already contains a valid serial number, so we'll have to update the relevant record:
	{
			// UPDATE - construct queries to update the relevant record
			$queryRefs = "UPDATE $tableRefs SET "
					. "author = " . quote_smart($authorName) . ", "
					. "first_author = " . quote_smart($firstAuthor) . ", "
					. "author_count = " . quote_smart($authorCount) . ", "
					. "title = " . quote_smart($titleName) . ", "
					. "year = " . quote_smart($yearNo) . ", "
					. "publication = " . quote_smart($publicationName) . ", "
					. "abbrev_journal = " . quote_smart($abbrevJournalName) . ", "
					. "volume = " . quote_smart($volumeNo) . ", "
					. "volume_numeric = " . quote_smart($volumeNumericNo) . ", "
					. "issue = " . quote_smart($issueNo) . ", "
					. "pages = " . quote_smart($pagesNo) . ", "
					. "first_page = " . quote_smart($firstPage) . ", "
					. "address = " . quote_smart($addressName) . ", "
					. "corporate_author = " . quote_smart($corporateAuthorName) . ", "
					. "keywords = " . quote_smart($keywordsName) . ", "
					. "abstract = " . quote_smart($abstractName) . ", "
					. "publisher = " . quote_smart($publisherName) . ", "
					. "place = " . quote_smart($placeName) . ", "
					. "editor = " . quote_smart($editorName) . ", "
					. "language = " . quote_smart($languageName) . ", "
					. "summary_language = " . quote_smart($summaryLanguageName) . ", "
					. "orig_title = " . quote_smart($origTitleName) . ", "
					. "series_editor = " . quote_smart($seriesEditorName) . ", "
					. "series_title = " . quote_smart($seriesTitleName) . ", "
					. "abbrev_series_title = " . quote_smart($abbrevSeriesTitleName) . ", "
					. "series_volume = " . quote_smart($seriesVolumeNo) . ", "
					. "series_volume_numeric = " . quote_smart($seriesVolumeNumericNo) . ", "
					. "series_issue = " . quote_smart($seriesIssueNo) . ", "
					. "edition = " . quote_smart($editionNo) . ", "
					. "issn = " . quote_smart($issnName) . ", "
					. "isbn = " . quote_smart($isbnName) . ", "
					. "medium = " . quote_smart($mediumName) . ", "
					. "area = " . quote_smart($areaName) . ", "
					. "expedition = " . quote_smart($expeditionName) . ", "
					. "conference = " . quote_smart($conferenceName) . ", "
					. "location = " . quote_smart($locationName) . ", "
					. "call_number = " . quote_smart($callNumberName) . ", "
					. "approved = " . quote_smart($approvedRadio) . ", "
					. "file = " . quote_smart($fileName) . ", "
					. "type = " . quote_smart($typeName) . ", "
					. "thesis = " . quote_smart($thesisName) . ", "
					. "notes = " . quote_smart($notesName) . ", "
					. "url = " . quote_smart($urlName) . ", "
					. "doi = " . quote_smart($doiName) . ", "
					. "contribution_id = " . quote_smart($contributionID) . ", "
					. "online_publication = " . quote_smart($onlinePublication) . ", "
					. "online_citation = " . quote_smart($onlineCitationName) . ", "
					. "modified_date = " . quote_smart($currentDate) . ", "
					. "modified_time = " . quote_smart($currentTime) . ", "
					. "modified_by = " . quote_smart($currentUser) . " "
					. "WHERE serial = " . quote_smart($serialNo);


			// first, we need to check if there's already an entry for the current record & user within the 'user_data' table:
			// CONSTRUCT SQL QUERY:
			$query = "SELECT data_id FROM $tableUserData WHERE record_id = " . quote_smart($serialNo) . " AND user_id = " . quote_smart($loginUserID); // '$loginUserID' is provided as session variable

			// (3) RUN the query on the database through the connection:
			$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

			if (mysql_num_rows($result) == 1) // if there's already an existing user_data entry, we perform an UPDATE action:
				$queryUserData = "UPDATE $tableUserData SET "
								. "marked = " . quote_smart($markedRadio) . ", "
								. "copy = " . quote_smart($copyName) . ", "
								. "selected = " . quote_smart($selectedRadio) . ", "
								. "user_keys = " . quote_smart($userKeysName) . ", "
								. "user_notes = " . quote_smart($userNotesName) . ", "
								. "user_file = " . quote_smart($userFileName) . ", "
								. "user_groups = " . quote_smart($userGroupsName) . ", "
								. "cite_key = " . quote_smart($citeKeyName) . ", "
								. "related = " . quote_smart($relatedName) . " "
								. "WHERE record_id = " . quote_smart($serialNo) . " AND user_id = " . quote_smart($loginUserID); // '$loginUserID' is provided as session variable
			else // otherwise we perform an INSERT action:
				$queryUserData = "INSERT INTO $tableUserData SET "
								. "marked = " . quote_smart($markedRadio) . ", "
								. "copy = " . quote_smart($copyName) . ", "
								. "selected = " . quote_smart($selectedRadio) . ", "
								. "user_keys = " . quote_smart($userKeysName) . ", "
								. "user_notes = " . quote_smart($userNotesName) . ", "
								. "user_file = " . quote_smart($userFileName) . ", "
								. "user_groups = " . quote_smart($userGroupsName) . ", "
								. "cite_key = " . quote_smart($citeKeyName) . ", "
								. "related = " . quote_smart($relatedName) . ", "
								. "record_id = " . quote_smart($serialNo) . ", "
								. "user_id = " . quote_smart($loginUserID) . ", " // '$loginUserID' is provided as session variable
								. "data_id = NULL"; // inserting 'NULL' into an auto_increment PRIMARY KEY attribute allocates the next available key value
	}

	elseif ($recordAction == "delet") // (Note that if you delete the mother record within the 'refs' table, the corresponding child entry within the 'user_data' table will remain!)
	{
			// Instead of deleting data, deleted records will be moved to the "deleted" table. Data will be stored within the "deleted" table
			// until they are removed manually. This is to provide the admin with a simple recovery method in case a user did delete some data by accident...

			// INSERT - construct queries to add data as new record
			$queryDeleted = "INSERT INTO $tableDeleted SET "
					. "author = " . quote_smart($authorName) . ", "
					. "first_author = " . quote_smart($firstAuthor) . ", "
					. "author_count = " . quote_smart($authorCount) . ", "
					. "title = " . quote_smart($titleName) . ", "
					. "year = " . quote_smart($yearNo) . ", "
					. "publication = " . quote_smart($publicationName) . ", "
					. "abbrev_journal = " . quote_smart($abbrevJournalName) . ", "
					. "volume = " . quote_smart($volumeNo) . ", "
					. "volume_numeric = " . quote_smart($volumeNumericNo) . ", "
					. "issue = " . quote_smart($issueNo) . ", "
					. "pages = " . quote_smart($pagesNo) . ", "
					. "first_page = " . quote_smart($firstPage) . ", "
					. "address = " . quote_smart($addressName) . ", "
					. "corporate_author = " . quote_smart($corporateAuthorName) . ", "
					. "keywords = " . quote_smart($keywordsName) . ", "
					. "abstract = " . quote_smart($abstractName) . ", "
					. "publisher = " . quote_smart($publisherName) . ", "
					. "place = " . quote_smart($placeName) . ", "
					. "editor = " . quote_smart($editorName) . ", "
					. "language = " . quote_smart($languageName) . ", "
					. "summary_language = " . quote_smart($summaryLanguageName) . ", "
					. "orig_title = " . quote_smart($origTitleName) . ", "
					. "series_editor = " . quote_smart($seriesEditorName) . ", "
					. "series_title = " . quote_smart($seriesTitleName) . ", "
					. "abbrev_series_title = " . quote_smart($abbrevSeriesTitleName) . ", "
					. "series_volume = " . quote_smart($seriesVolumeNo) . ", "
					. "series_volume_numeric = " . quote_smart($seriesVolumeNumericNo) . ", "
					. "series_issue = " . quote_smart($seriesIssueNo) . ", "
					. "edition = " . quote_smart($editionNo) . ", "
					. "issn = " . quote_smart($issnName) . ", "
					. "isbn = " . quote_smart($isbnName) . ", "
					. "medium = " . quote_smart($mediumName) . ", "
					. "area = " . quote_smart($areaName) . ", "
					. "expedition = " . quote_smart($expeditionName) . ", "
					. "conference = " . quote_smart($conferenceName) . ", "
					. "location = " . quote_smart($locationName) . ", "
					. "call_number = " . quote_smart($callNumberName) . ", "
					. "approved = " . quote_smart($approvedRadio) . ", "
					. "file = " . quote_smart($fileName) . ", "
					. "serial = " . quote_smart($serialNo) . ", " // it's important to keep the old PRIMARY KEY (since user specific data may be still associated with this record id)
					. "type = " . quote_smart($typeName) . ", "
					. "thesis = " . quote_smart($thesisName) . ", "
					. "notes = " . quote_smart($notesName) . ", "
					. "url = " . quote_smart($urlName) . ", "
					. "doi = " . quote_smart($doiName) . ", "
					. "contribution_id = " . quote_smart($contributionID) . ", "
					. "online_publication = " . quote_smart($onlinePublication) . ", "
					. "online_citation = " . quote_smart($onlineCitationName) . ", "
					. "created_date = " . quote_smart($createdDate) . ", "
					. "created_time = " . quote_smart($createdTime) . ", "
					. "created_by = " . quote_smart($createdBy) . ", "
					. "modified_date = " . quote_smart($modifiedDate) . ", "
					. "modified_time = " . quote_smart($modifiedTime) . ", "
					. "modified_by = " . quote_smart($modifiedBy) . ", "
					. "orig_record = " . quote_smart($origRecord) . ", "
					. "deleted_date = " . quote_smart($currentDate) . ", " // store information about when and by whom this record was deleted...
					. "deleted_time = " . quote_smart($currentTime) . ", "
					. "deleted_by = " . quote_smart($currentUser);

			// since data have been moved from table 'refs' to table 'deleted', its now safe to delete the data from table 'refs':
			$queryRefs = "DELETE FROM $tableRefs WHERE serial = " . quote_smart($serialNo);
	}

	else // if the form does NOT contain a valid serial number, we'll have to add the data:
	{
			// INSERT - construct queries to add data as new record
			$queryRefs = "INSERT INTO $tableRefs SET "
					. "author = " . quote_smart($authorName) . ", "
					. "first_author = " . quote_smart($firstAuthor) . ", "
					. "author_count = " . quote_smart($authorCount) . ", "
					. "title = " . quote_smart($titleName) . ", "
					. "year = " . quote_smart($yearNo) . ", "
					. "publication = " . quote_smart($publicationName) . ", "
					. "abbrev_journal = " . quote_smart($abbrevJournalName) . ", "
					. "volume = " . quote_smart($volumeNo) . ", "
					. "volume_numeric = " . quote_smart($volumeNumericNo) . ", "
					. "issue = " . quote_smart($issueNo) . ", "
					. "pages = " . quote_smart($pagesNo) . ", "
					. "first_page = " . quote_smart($firstPage) . ", "
					. "address = " . quote_smart($addressName) . ", "
					. "corporate_author = " . quote_smart($corporateAuthorName) . ", "
					. "keywords = " . quote_smart($keywordsName) . ", "
					. "abstract = " . quote_smart($abstractName) . ", "
					. "publisher = " . quote_smart($publisherName) . ", "
					. "place = " . quote_smart($placeName) . ", "
					. "editor = " . quote_smart($editorName) . ", "
					. "language = " . quote_smart($languageName) . ", "
					. "summary_language = " . quote_smart($summaryLanguageName) . ", "
					. "orig_title = " . quote_smart($origTitleName) . ", "
					. "series_editor = " . quote_smart($seriesEditorName) . ", "
					. "series_title = " . quote_smart($seriesTitleName) . ", "
					. "abbrev_series_title = " . quote_smart($abbrevSeriesTitleName) . ", "
					. "series_volume = " . quote_smart($seriesVolumeNo) . ", "
					. "series_volume_numeric = " . quote_smart($seriesVolumeNumericNo) . ", "
					. "series_issue = " . quote_smart($seriesIssueNo) . ", "
					. "edition = " . quote_smart($editionNo) . ", "
					. "issn = " . quote_smart($issnName) . ", "
					. "isbn = " . quote_smart($isbnName) . ", "
					. "medium = " . quote_smart($mediumName) . ", "
					. "area = " . quote_smart($areaName) . ", "
					. "expedition = " . quote_smart($expeditionName) . ", "
					. "conference = " . quote_smart($conferenceName) . ", "
					. "location = " . quote_smart($locationName) . ", "
					. "call_number = " . quote_smart($callNumberName) . ", "
					. "approved = " . quote_smart($approvedRadio) . ", "
					. "file = " . quote_smart($fileName) . ", " // for new records the 'file' field will be updated once more after record creation, since the serial number of the newly created record may be required when generating a file name for any uploaded file
					. "serial = NULL, " // inserting 'NULL' into an auto_increment PRIMARY KEY attribute allocates the next available key value
					. "type = " . quote_smart($typeName) . ", "
					. "thesis = " . quote_smart($thesisName) . ", "
					. "notes = " . quote_smart($notesName) . ", "
					. "url = " . quote_smart($urlName) . ", "
					. "doi = " . quote_smart($doiName) . ", "
					. "contribution_id = " . quote_smart($contributionID) . ", "
					. "online_publication = " . quote_smart($onlinePublication) . ", "
					. "online_citation = " . quote_smart($onlineCitationName) . ", "
					. "created_date = " . quote_smart($currentDate) . ", "
					. "created_time = " . quote_smart($currentTime) . ", "
					. "created_by = " . quote_smart($currentUser) . ", "
					. "modified_date = " . quote_smart($currentDate) . ", "
					. "modified_time = " . quote_smart($currentTime) . ", "
					. "modified_by = " . quote_smart($currentUser);

			// '$queryUserData' will be set up after '$queryRefs' has been conducted (see below), since the serial number of the newly created 'refs' record is required for the '$queryUserData' query
	}

	// Apply some clean-up to the SQL query:
	// - if a field of type=NUMBER is empty, we set it back to NULL (otherwise the empty string would be converted to "0")
	// - if the 'thesis' field is empty, we also set it back to NULL (this ensures correct sorting when outputting citations with '$citeOrder="type"' or '$citeOrder="type-year"')
	if (ereg("(year|volume_numeric|first_page|series_volume_numeric|edition|orig_record|thesis) = [\"']0?[\"']", $queryRefs))
		$queryRefs = preg_replace("/(year|volume_numeric|first_page|series_volume_numeric|edition|orig_record|thesis) = [\"']0?[\"']/", "\\1 = NULL", $queryRefs);

	if (ereg("(year|volume_numeric|first_page|series_volume_numeric|edition|orig_record|thesis) = [\"']0?[\"']", $queryDeleted))
		$queryDeleted = preg_replace("/(year|volume_numeric|first_page|series_volume_numeric|edition|orig_record|thesis) = [\"']0?[\"']/", "\\1 = NULL", $queryDeleted);

	// --------------------------------------------------------------------

	// (3) RUN QUERY, (4) DISPLAY HEADER & RESULTS

	// (3) RUN the query on the database through the connection:
	if ($recordAction == "edit")
	{
		$result = queryMySQLDatabase($queryRefs); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		$result = queryMySQLDatabase($queryUserData); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		getUserGroups($tableUserData, $loginUserID); // update the 'userGroups' session variable (function 'getUserGroups()' is defined in 'include.inc.php')
	}
	elseif ($recordAction == "add")
	{
		$result = queryMySQLDatabase($queryRefs); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		// Get the record id that was created
		$serialNo = @ mysql_insert_id($connection); // find out the unique ID number of the newly created record (Note: this function should be called immediately after the
													// SQL INSERT statement! After any subsequent query it won't be possible to retrieve the auto_increment identifier value for THIS record!)

		$formVars['serialNo'] = $serialNo; // for '$recordAction = "add"' we update the original '$formVars' array element to ensure a correct serial number when generating the file name via the 'parsePlaceholderString()' function

		// handle file uploads:
		// for '$recordAction = "add"' file name generation needs to be done *after* the record has been created and a serial number is available
		if (!empty($uploadFile) && !empty($uploadFile["tmp_name"])) // if there was a file uploaded successfully
		{
			// process information of any file that was uploaded, auto-generate a file name if required and move the file to the appropriate directory:
			$fileName = handleFileUploads($uploadFile, $formVars);

			$queryRefsUpdateFileName = "UPDATE $tableRefs SET file = " . quote_smart($fileName) . " WHERE serial = " . quote_smart($serialNo);

			$result = queryMySQLDatabase($queryRefsUpdateFileName); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'
		}

		$queryUserData = "INSERT INTO $tableUserData SET "
				. "marked = " . quote_smart($markedRadio) . ", "
				. "copy = " . quote_smart($copyName) . ", "
				. "selected = " . quote_smart($selectedRadio) . ", "
				. "user_keys = " . quote_smart($userKeysName) . ", "
				. "user_notes = " . quote_smart($userNotesName) . ", "
				. "user_file = " . quote_smart($userFileName) . ", "
				. "user_groups = " . quote_smart($userGroupsName) . ", "
				. "cite_key = " . quote_smart($citeKeyName) . ", "
				. "related = " . quote_smart($relatedName) . ", "
				. "record_id = " . quote_smart($serialNo) . ", "
				. "user_id = " . quote_smart($loginUserID) . ", " // '$loginUserID' is provided as session variable
				. "data_id = NULL"; // inserting 'NULL' into an auto_increment PRIMARY KEY attribute allocates the next available key value


		$result = queryMySQLDatabase($queryUserData); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		getUserGroups($tableUserData, $loginUserID); // update the 'userGroups' session variable (function 'getUserGroups()' is defined in 'include.inc.php')


		// Send EMAIL announcement:
		if ($sendEmailAnnouncements == "yes") // ('$sendEmailAnnouncements' is specified in 'ini.inc.php')
		{
			// first, build an appropriate author string:
			// Call the 'extractAuthorsLastName()' function (defined in 'include.inc.php') to extract the last name of a particular author (specified by position). Required Parameters:
			//   1. pattern describing delimiter that separates different authors
			//   2. pattern describing delimiter that separates author name & initials (within one author)
			//   3. position of the author whose last name shall be extracted (e.g., "1" will return the 1st author's last name)
			//   4. contents of the author field
			$authorString = extractAuthorsLastName(" *; *", // get last name of first author
												" *, *",
												1,
												$authorName);

			if ($authorCount == "2") // two authors
			{
				$authorString .= " & ";
				$authorString .= extractAuthorsLastName(" *; *", // get last name of second author
													" *, *",
													2,
													$authorName);
			}

			if ($authorCount == "3") // at least three authors
				$authorString .= " et al";		

			// send a notification email to the mailing list email address '$mailingListEmail' (specified in 'ini.inc.php'):
			$emailRecipient = "Literature Database Announcement List <" . $mailingListEmail . ">";

			$emailSubject = "New entry: " . $authorString . " " . $yearNo;
			if (!empty($publicationName))
			{
				$emailSubject .= " (" . $publicationName;
				if (!empty($volumeNo))
					$emailSubject .= " " . $volumeNo . ")";
				else
					$emailSubject .= ")";
			}

			$emailBody = "The following record has been added to the " . $officialDatabaseName . ":"
						. "\n\n  author:       " . $authorName
						. "\n  title:        " . $titleName
						. "\n  year:         " . $yearNo
						. "\n  publication:  " . $publicationName
						. "\n  volume:       " . $volumeNo
						. "\n  issue:        " . $issueNo
						. "\n  pages:        " . $pagesNo
						. "\n\n  added by:     " . $loginFirstName . " " . $loginLastName
						. "\n  details:      " . $databaseBaseURL . "show.php?record=" . $serialNo // ('$databaseBaseURL' is specified in 'ini.inc.php')
						. "\n";

			sendEmail($emailRecipient, $emailSubject, $emailBody);
		}
	}
	else // '$recordAction' is "delet" (Note that if you delete the mother record within the 'refs' table, the corresponding child entry within the 'user_data' table will remain!)
	{
		$result = queryMySQLDatabase($queryDeleted); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		$result = queryMySQLDatabase($queryRefs); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'
	}


	// Build correct header message:
	$headerMsg = "The record no. " . $serialNo . " has been successfully " . $recordAction . "ed.";

	// Append a "Display previous search results" link to the feedback header message if it will be displayed above a single record that was added/edited last:
	if (!empty($oldMultiRecordQuery))
	{
		// Remove any previous 'headerMsg' parameter from the saved query URL:
		unset($oldMultiRecordQuery["headerMsg"]);

		// After a record has been successfully added/edited/deleted, we include a link to the last multi-record query in the feedback header message if:
		// 1) the SQL query in 'oldQuery' is different from that one stored in 'oldMultiRecordQuery', i.e. if 'oldQuery' points to a single record -OR-
		// 2) one or more new records have been added/imported
		if ((!empty($oldQuery) AND ($oldQuery["sqlQuery"] != $oldMultiRecordQuery["sqlQuery"]) AND ($recordAction != "delet")) OR ($recordAction == "add"))
		{
			// Generate a 'search.php' URL that points to the last multi-record query:
			$oldMultiRecordQueryURL = generateURL("search.php", "html", $oldMultiRecordQuery, true); // function 'generateURL()' is defined in 'include.inc.php'

			// Append a link to the previous search results to the feedback header message:
			$headerMsg .= " <a href=\"" . $oldMultiRecordQueryURL . "\">Display previous search results</a>.";
		}
	}

	// Save the header message to a session variable:
	// NOTE: Opposed to single-record feedback (or the 'receipt.php' feedback), we don't include the header message within the 'headerMsg' URL parameter when
	//       displaying the message above the last multi-record query. If we save the header message to a session variable ("HeaderString") this causes the
	//       receiving script ("search.php") to display it just once; if we'd instead include the header message within the 'headerMsg' parameter, it would
	//       be displayed above results of the last multi-record query even when the user browses to another search results page or changes the sort order.
	$HeaderString = returnMsg($headerMsg, "", "", "HeaderString"); // function 'returnMsg()' is defined in 'include.inc.php'


	if ($recordAction == "add")
	{
		// Display the newly added record:
		header("Location: show.php?record=" . $serialNo . "&headerMsg=" . rawurlencode($headerMsg));
	}
	elseif (($recordAction == "delet") AND !empty($oldMultiRecordQuery))
	{
		// Generate a 'search.php' URL that points to the last multi-record query:
		$oldMultiRecordQueryURL = generateURL("search.php", "html", $oldMultiRecordQuery, false);

		// Display the previous search results:
		header("Location: $oldMultiRecordQueryURL");
	}
	elseif (($recordAction != "delet") AND !empty($oldQuery))
	{
		// Remove any previous 'headerMsg' parameter from the saved query URL:
		unset($oldQuery["headerMsg"]);

		// Generate a 'search.php' URL that points to the formerly displayed results page:
		$queryURL = generateURL("search.php", "html", $oldQuery, false);

		// Route back to the previous results display:
		// (i.e., after submission of the edit mask, we now go straight back to the results list that was displayed previously,
		//  no matter what display type it was (List view, Citation view, or Details view))
		header("Location: $queryURL");
	}
	else // old method that uses 'receipt.php' for feedback:
	{
		// (4) Call 'receipt.php' which displays links to the modifyed/added record as well as to the previous search results page (if any)
		//     (routing feedback output to a different script page will avoid any reload problems effectively!)
		header("Location: receipt.php?recordAction=" . $recordAction . "&serialNo=" . $serialNo . "&headerMsg=" . rawurlencode($headerMsg));
	}

	// --------------------------------------------------------------------

	// (5) CLOSE CONNECTION

	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase(); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// Handle file uploads:
	// process information of any file that was uploaded, auto-generate a file name if required
	// and move the file to the appropriate directory
	function handleFileUploads($uploadFile, $formVars)
	{
		global $filesBaseDir; // these variables are defined in 'ini.inc.php'
		global $moveFilesIntoSubDirectories;
		global $dirNamingScheme;
		global $renameUploadedFiles;
		global $fileNamingScheme;
		global $handleNonASCIIChars;
		global $allowedFileNameCharacters;
		global $allowedDirNameCharacters;
		global $changeCaseInFileNames;
		global $changeCaseInDirNames;

		$tmpFilePath = $uploadFile["tmp_name"];

		// Generate file name:
		if ($renameUploadedFiles == "yes") // rename file according to a standard naming scheme
		{
			if (preg_match("/.+\.[^.]+$/i", $uploadFile["name"])) // preserve any existing file name extension
				$fileNameExtension = preg_replace("/.+(\.[^.]+)$/i", "\\1", $uploadFile["name"]);
			else
				$fileNameExtension = "";

			// auto-generate a file name according to the naming scheme given in '$fileNamingScheme':
			$newFileName = parsePlaceholderString($formVars, $fileNamingScheme, "<:serial:>"); // function 'parsePlaceholderString()' is defined in 'include.inc.php'

			// handle non-ASCII and unwanted characters:
			$newFileName = handleNonASCIIAndUnwantedCharacters($newFileName, $allowedFileNameCharacters, $handleNonASCIIChars); // function 'handleNonASCIIAndUnwantedCharacters()' is defined in 'include.inc.php'

			// add original file name extension:
			$newFileName .= $fileNameExtension;
		}
		else // take the file name as given by the user:
			$newFileName = $uploadFile["name"];


		// Generate directory structure:
		if ($moveFilesIntoSubDirectories != "never")
		{
			// remove any slashes (i.e., directory delimiter(s)) from the beginning or end of '$dirNamingScheme':
			$dirNamingScheme = trimTextPattern($dirNamingScheme, "[\/\\\\]+", true, true); // function 'trimTextPattern()' is defined in 'include.inc.php'

			$dirNamingSchemePartsArray = split("[/\\]+", $dirNamingScheme); // split on slashes to separate between multiple sub-directories

			$subDirNamesArray = array(); // initialize array variable which will hold the generated sub-directory names

			// auto-generate a directory name according to the naming scheme given in '$dirNamingScheme'
			// and handle non-ASCII chars plus unwanted characters:
			foreach($dirNamingSchemePartsArray as $dirNamingSchemePart)
			{
				// parse given placeholder string:
				$subDirName = parsePlaceholderString($formVars, $dirNamingSchemePart, ""); // function 'parsePlaceholderString()' is defined in 'include.inc.php'

				// handle non-ASCII and unwanted characters:
				$subDirName = handleNonASCIIAndUnwantedCharacters($subDirName, $allowedDirNameCharacters, $handleNonASCIIChars); // function 'handleNonASCIIAndUnwantedCharacters()' is defined in 'include.inc.php'

				if (!empty($subDirName))
					$subDirNamesArray[] = $subDirName;
			}

			if (!empty($subDirNamesArray))
				$subDirName = implode("/", $subDirNamesArray) . "/"; // merge any individual sub-directory names and append a slash to generate final sub-directory structure
			else
				$subDirName = "";
		}
		else
			$subDirName = "";


		// Perform any case transformations:
		// change case of file name:
		if (eregi("^(lower|upper)$", $changeCaseInFileNames))
			$newFileName = changeCase($changeCaseInFileNames, $newFileName); // function 'changeCase()' is defined in 'include.inc.php'

		// change case of DIR name:
		if (eregi("^(lower|upper)$", $changeCaseInDirNames) && !empty($subDirName))
			$subDirName = changeCase($changeCaseInDirNames, $subDirName);


		// Generate full destination path:
		// - if '$moveFilesIntoSubDirectories = "existing"' and there's an existing sub-directory (within the default files directory '$filesBaseDir')
		//   whose name equals '$subDirName' we'll copy the new file into that sub-directory
		// - if '$moveFilesIntoSubDirectories = "always"' and '$subDirName' isn't empty, we'll generate an appropriately named sub-directory if it
		//   doesn't exist yet
		// - otherwise we just copy the file to the root-level of '$filesBaseDir':
		if (!empty($subDirName) && (($moveFilesIntoSubDirectories == "existing" AND is_dir($filesBaseDir . $subDirName)) OR ($moveFilesIntoSubDirectories == "always")))
		{
			$destFilePath = $filesBaseDir . $subDirName . $newFileName; // new file will be copied into sub-directory within '$filesBaseDir'...

			// copy the new subdir name & file name to the 'file' field variable:
			// Note: if a user uploads a file and there was already a file specified within the 'file' field, the old file will NOT get removed
			//       from the files directory! Automatic file removal is omitted on purpose since it's way more difficult to recover an
			//       inadvertently deleted file than to delete it manually. However, future versions should introduce a smarter way of handling
			//       orphaned files...
			$fileName = $subDirName . $newFileName;

			if ($moveFilesIntoSubDirectories == "always" AND !is_dir($filesBaseDir . $subDirName))
				// make sure the directory we're moving the file to exists before proceeding:
				recursiveMkdir($filesBaseDir . $subDirName);
		}
		else
		{
			$destFilePath = $filesBaseDir . $newFileName; // new file will be copied to root-level of '$filesBaseDir'...
			$fileName = $newFileName; // copy the new file name to the 'file' field variable (see note above!)
		}


		// Copy uploaded file from temporary location to the default file directory specified in '$filesBaseDir':
		// (for more on PHP file uploads see <http://www.php.net/manual/en/features.file-upload.php>)
		move_uploaded_file($tmpFilePath, $destFilePath);

		return $fileName;
	}

	// --------------------------------------------------------------------

	// recursively create directories:
	// this function creates the specified directory using mkdir()
	// (adopted from user-contributed function at <http://de2.php.net/manual/en/function.mkdir.php>)
	function recursiveMkdir($path)
	{
		if (!is_dir($path)) // the directory doesn't exist
		{
			// recurse, passing the parent directory so that it gets created
			// (note that dirname returns the parent directory of the last item of the path
			//  regardless of whether the last item is a directory or a file)
			recursiveMkdir(dirname($path));

			mkdir($path, 0770); // create directory
			// alternatively, if the above line doesn't work for you, you might want to try:
//			$oldumask = umask(0);
//			mkdir($path, 0755); // create directory
//			umask($oldumask);
		}
	}

	// --------------------------------------------------------------------
?>
