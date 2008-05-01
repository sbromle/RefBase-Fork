<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./record.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    29-Jul-02, 16:39
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// Form that offers to add
	// records or edit/delete
	// existing ones.


	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/header.inc.php'; // include header
	include 'includes/footer.inc.php'; // include footer
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

	// Extract session variables:
	if (isset($_SESSION['errors']))
	{
		$errors = $_SESSION['errors']; // read session variable (only necessary if register globals is OFF!)

		// Note: though we clear the session variable, the current error message is still available to this script via '$errors':
		deleteSessionVariable("errors"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	}
	else
		$errors = array(); // initialize the '$errors' variable in order to prevent 'Undefined variable...' messages

	if (isset($_SESSION['formVars']))
	{
		$formVars = $_SESSION['formVars']; // read session variable (only necessary if register globals is OFF!)

		// Note: though we clear the session variable, the current form variables are still available to this script via '$formVars':
		deleteSessionVariable("formVars"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	}
	else
		$formVars = array();

	// --------------------------------------------------------------------

	if (isset($_REQUEST['recordAction']))
		$recordAction = $_REQUEST['recordAction']; // check whether the user wants to *add* a record or *edit* an existing one
	else
		$recordAction = ""; // if the 'recordAction' parameter wasn't set we set the '$recordAction' variable to the empty string ("") to prevent 'Undefined index: recordAction...' notification messages

	if (isset($_REQUEST['mode']))
		$mode = $_REQUEST['mode']; // check whether the user wants to add a record by use of an *import* form (e.g., the parameter "mode=import" will be set by 'import_modify.php' and 'import_csa_modify.php')
	else
		$mode = ""; // if the 'mode' parameter wasn't set we set the '$mode' variable to the empty string ("") to prevent 'Undefined index: mode...' notification messages

	if (isset($_REQUEST['importSource']))
		$importSource = $_REQUEST['importSource']; // get the source from which the imported data originate (e.g., if data have been imported via 'import_csa.php', the 'importSource' value will be 'csa')
	else
		$importSource = ""; // if the 'importSource' parameter wasn't set we set the '$importSource' variable to the empty string ("") to prevent 'Undefined index: importSource...' notification messages

	if (isset($_REQUEST['serialNo']))
		$serialNo = $_REQUEST['serialNo']; // fetch the serial number of the record to edit
	else
		$serialNo = ""; // this is actually unneccessary, but we do it for clarity reasons here

	// Setup some required variables:

	// If there's no stored message available:
	if (!isset($_SESSION['HeaderString'])) // if there's no stored message available
	{
		if (empty($errors)) // provide one of the default messages:
		{
			$errors = array(); // re-assign an empty array (in order to prevent 'Undefined variable "errors"...' messages when calling the 'fieldError' function later on)
			if ($recordAction == "edit") // *edit* record
				$HeaderString = $loc["EditRecordHeaderText"] . ":";
			else // *add* record will be the default action if no parameter is given
			{
				$HeaderString = $loc["AddRecordHeaderText"];
				if (isset($_REQUEST['source'])) // when importing data, we display the original source data if the 'source' parameter is present:
					$HeaderString .= ". Original source data:\n<br>\n<br>\n<code>" . encodeHTML($_REQUEST['source']) . "</code>"; // the 'source' parameter gets passed by 'import.php' or 'import_csa.php'
				else
					$HeaderString .= ":";
			}
		}
		else // -> there were errors validating the data entered by the user
			$HeaderString = "<b><span class=\"warning\">". $loc["WarningInputDataError"]."</span></b>";
	}
	else // there is already a stored message available
	{
		$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

		// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
		deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	}

	// Extract the view type requested by the user (either 'Mobile', 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";

	// if the user isn't logged in -OR- any normal user is logged in (not the admin)...
	if ((!isset($loginEmail)) OR ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail)))
	{
		$fieldLock = " readonly"; // ... lock the 'location' & 'file' fields
		$fieldLockLabel = " (" . $loc["readonly"] . ")"; // ... append a " (readonly)" indicator to the field description of the 'location' & 'file' fields
	}
	else // if the admin is logged in...
	{
		$fieldLock = ""; // ...the 'location' & 'file' fields won't be locked (since the admin should be able to freely add or edit any records)
		$fieldLockLabel = "";
	}

	if ($recordAction == "edit") // *edit* record
	{
		$pageTitle = $loc["EditRecord"]; // set the correct page title
		$addEditButtonTitle = $loc["ButtonTitle_EditRecord"]; // set the button name of the (default) submit button ('Edit Record')
	}
	else
	{
		$recordAction = "add"; // *add* record will be the default action if no parameter is given
		$pageTitle = $loc["AddRecord"]; // set the correct page title
		$addEditButtonTitle = $loc["ButtonTitle_AddRecord"]; // set the button name of the (default) submit button ('Add Record')
		$serialNo = $loc["not assigned yet"];

		// if the user isn't logged in -OR- any normal user is logged in (not the admin)...
		if ((!isset($loginEmail)) OR ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail)))
			// ...provide a generic info string within the (locked) 'location' field that informs the user about the automatic fill in of his user name & email address
			// (IMPORTANT: if you change this information string you must also edit the corresponding 'ereg(...)' pattern in 'modify.php'!)
			$locationName = $loc["your name & email address will be filled in automatically"];
		else // if the admin is logged in...
			$locationName = ""; // ...keep the 'location' field empty
	}

	if (isset($loginEmail)) // if a user is logged in
	{
		// build a correct call number prefix for the currently logged-in user (e.g. 'IPÖ @ msteffens'):
		$callNumberPrefix = getCallNumberPrefix(); // function 'getCallNumberPrefix()' is defined in 'include.inc.php'
	}

	// --------------------------------------------------------------------

	// CONSTRUCT SQL QUERY:
	// if the script was called with parameters (like: 'record.php?recordAction=edit&serialNo=...')
	if ($recordAction == "edit")
	{
		// for the selected record, select *all* available fields:
		$query = buildSELECTclause("Edit", "1"); // function 'buildSELECTclause()' is defined in 'include.inc.php'

		if (isset($_SESSION['loginEmail'])) // if a user is logged in, show user specific fields:
			$query .= " FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id =" . quote_smart($loginUserID) . " WHERE serial RLIKE " . quote_smart("^(" . $serialNo . ")$"); // since we'll only fetch one record, the ORDER BY clause is obsolete here
		else // if NO user logged in, don't display any user specific fields:
			$query .= " FROM $tableRefs WHERE serial RLIKE " . quote_smart("^(" . $serialNo . ")$"); // since we'll only fetch one record, the ORDER BY clause is obsolete here
	}

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// Initialize some variables (to prevent "Undefined variable..." messages):
	$isEditorCheckBox = "";
	$contributionIDCheckBox = "";
	$locationSelectorName = "";

	if ($recordAction == "edit" AND empty($errors))
		{
			// (3a) RUN the query on the database through the connection:
			$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

			if (@ mysql_num_rows($result) == 1) // this condition is added here to avoid the case that clicking on a search result item which got deleted in the meantime invokes a seemingly correct but empty 'edit record' search form
			{
				// (3b) EXTRACT results:
				$row = mysql_fetch_array($result); //fetch the current row into the array $row (it'll be always *one* row, but anyhow)
				
				// fetch attributes of the current record into variables:
				$authorName = encodeHTML($row['author']);
				$titleName = encodeHTML($row['title']);
				$yearNo = encodeHTML($row['year']);
				$publicationName = encodeHTML($row['publication']);
				$abbrevJournalName = encodeHTML($row['abbrev_journal']);
				$volumeNo = encodeHTML($row['volume']);
				$issueNo = encodeHTML($row['issue']);
				$pagesNo = encodeHTML($row['pages']);
				$addressName = encodeHTML($row['address']);
				$corporateAuthorName = encodeHTML($row['corporate_author']);
				$keywordsName = encodeHTML($row['keywords']);
				$abstractName = encodeHTML($row['abstract']);
				$publisherName = encodeHTML($row['publisher']);
				$placeName = encodeHTML($row['place']);
				$editorName = encodeHTML($row['editor']);
				$languageName = encodeHTML($row['language']);
				$summaryLanguageName = encodeHTML($row['summary_language']);
				$origTitleName = encodeHTML($row['orig_title']);
				$seriesEditorName = encodeHTML($row['series_editor']);
				$seriesTitleName = encodeHTML($row['series_title']);
				$abbrevSeriesTitleName = encodeHTML($row['abbrev_series_title']);
				$seriesVolumeNo = encodeHTML($row['series_volume']);
				$seriesIssueNo = encodeHTML($row['series_issue']);
				$editionNo = encodeHTML($row['edition']);
				$issnName = encodeHTML($row['issn']);
				$isbnName = encodeHTML($row['isbn']);
				$mediumName = encodeHTML($row['medium']);
				$areaName = encodeHTML($row['area']);
				$expeditionName = encodeHTML($row['expedition']);
				$conferenceName = encodeHTML($row['conference']);
				$notesName = encodeHTML($row['notes']);
				$approvedRadio = encodeHTML($row['approved']);

				// we only show the contents of the 'location' field if the user is logged in:
				// (this is mostly done to shield user email addresses from exposure to search engines and/or email harvesting robots)
				if (isset($loginEmail))
				{
					$locationName = encodeHTML($row['location']);
					$rawLocationName = $row['location']; // we'll save the unencoded location string to a separate variable since it will be needed when generating the delete button
				}
				else
				{
					$locationName = "";
					$rawLocationName = "";
				}

				$callNumberName = $row['call_number']; // contents of the 'call_number' field will get encoded depending on who's logged in (normal user vs. admin)
													// (for normal users being logged in, the field's contents won't get HTML encoded at all, since the data will
													//  get *rawurlencoded* when including them within a hidden form tag; for the admin being logged in, the data
													//  will get HTML encoded below)

				// if a normal user is logged in, we'll only display the user's *own* call number within the 'call_number' field:
				if ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail))
				{
					if (ereg("(^|.*;) *$callNumberPrefix *@ +([^@;]+)", $callNumberName)) // if the user's call number prefix occurs within the contents of the 'call_number' field
					{
						$callNumberNameUserOnly = ereg_replace("(^|.*;) *$callNumberPrefix *@ +([^@;]+).*", "\\2", $callNumberName); // extract the user's *own* call number from the full contents of the 'call_number' field
						$callNumberNameUserOnly = encodeHTML($callNumberNameUserOnly);
					}
					else
						$callNumberNameUserOnly = "";
				}
				elseif ((isset($loginEmail)) AND ($loginEmail == $adminLoginEmail)) // admin logged in
				{
					$callNumberNameUserOnly = ""; // the 'call_number' field will be empty if no user is logged in (note that '$callNumberNameUserOnly' won't be used at all, if the admin is logged in)
					$callNumberName = encodeHTML($callNumberName); // if the admin is logged in we display the full contents of the 'call_number' field, so we'll need to HTML encode the data
				}
				else // nobody logged in
				{
					$callNumberNameUserOnly = ""; // the 'call_number' field will be empty if no user is logged in (note that '$callNumberNameUserOnly' won't be used at all, if the admin is logged in)
					// note that, as for normal users being logged in, the call number field contents won't get HTML encoded here, since the data will get *rawurlencoded* when including them within a hidden form tag
				}

				$serialNo = encodeHTML($row['serial']);
				$typeName = encodeHTML($row['type']);
				$thesisName = encodeHTML($row['thesis']);

				if (isset($row['marked'])) // 'marked' field is only provided if a user is logged in
					$markedRadio = encodeHTML($row['marked']);
				else
					$markedRadio = "";

				if (isset($row['copy'])) // 'copy' field is only provided if a user is logged in
					$copyName = encodeHTML($row['copy']);
				else
					$copyName = "";

				if (isset($row['selected'])) // 'selected' field is only provided if a user is logged in
					$selectedRadio = encodeHTML($row['selected']);
				else
					$selectedRadio = "";

				if (isset($row['user_keys'])) // 'user_keys' field is only provided if a user is logged in
					$userKeysName = encodeHTML($row['user_keys']);
				else
					$userKeysName = "";

				if (isset($row['user_notes'])) // 'user_notes' field is only provided if a user is logged in
					$userNotesName = encodeHTML($row['user_notes']);
				else
					$userNotesName = "";

				if (isset($row['user_file'])) // 'user_file' field is only provided if a user is logged in
					$userFileName = encodeHTML($row['user_file']);
				else
					$userFileName = "";

				if (isset($row['user_groups'])) // 'user_groups' field is only provided if a user is logged in
					$userGroupsName = encodeHTML($row['user_groups']);
				else
					$userGroupsName = "";

				if (isset($row['cite_key'])) // 'cite_key' field is only provided if a user is logged in
					$citeKeyName = encodeHTML($row['cite_key']);
				else
					$citeKeyName = "";

				if (isset($row['related'])) // 'related' field is only provided if a user is logged in
					$relatedName = encodeHTML($row['related']);
				else
					$relatedName = "";

				// show the contents of the 'file' field if one of the following conditions is met:
				// - the variable '$fileVisibility' (defined in 'ini.inc.php') is set to 'everyone'
				// - the variable '$fileVisibility' is set to 'login' AND the user is logged in
				// - the variable '$fileVisibility' is set to 'user-specific' AND the 'user_permissions' session variable contains 'allow_download'
				if ($fileVisibility == "everyone" OR ($fileVisibility == "login" AND isset($_SESSION['loginEmail'])) OR ($fileVisibility == "user-specific" AND (isset($_SESSION['user_permissions']) AND ereg("allow_download", $_SESSION['user_permissions']))))
					$fileName = encodeHTML($row['file']);
				else // if the user has no permission to download (and hence view) any files, 'modify.php' will take care that the empty form value won't overwrite any existing contents of the 'file' field
					$fileName = "";

				$urlName = encodeHTML($row['url']);
				$doiName = encodeHTML($row['doi']);
				$contributionID = $row['contribution_id'];
				$onlinePublication = $row['online_publication'];
				$onlineCitationName = $row['online_citation'];
				$createdDate = $row['created_date'];
				$createdTime = $row['created_time'];
				$createdBy = encodeHTML($row['created_by']);
				$modifiedDate = $row['modified_date'];
				$modifiedTime = $row['modified_time'];
				$modifiedBy = encodeHTML($row['modified_by']);
				$origRecord = $row['orig_record'];
			}
			else
				showErrorMsg($loc["The Query"].":\n<br>\n<br>\n<code>" . encodeHTML($query) . "</code>\n<br>\n<br>\n ". $loc["caused an error"].":", "");

		}
	else // if ($recordAction == "add") -OR- ($recordAction == "edit" but there were some errors on submit)
		{
			if ($recordAction == "add" AND $mode == "import" AND empty($errors)) // if the user wants to import record data by use of an import form (like 'import.php' or 'import_csa.php')
			{

				foreach($_REQUEST as $varname => $value)
					// remove slashes from parameter values if 'magic_quotes_gpc = On':
					$_REQUEST[$varname] = stripSlashesIfMagicQuotes($value); // function 'stripSlashesIfMagicQuotes()' is defined in 'include.inc.php'

				// read field data from a GET/POST request:
				if (isset($_REQUEST['author']))
					$authorName = encodeHTML($_REQUEST['author']);
				else
					$authorName = "";

				if (isset($_REQUEST['title']))
					$titleName = encodeHTML($_REQUEST['title']);
				else
					$titleName = "";

				if (isset($_REQUEST['year']))
					$yearNo = encodeHTML($_REQUEST['year']);
				else
					$yearNo = "";

				if (isset($_REQUEST['publication']))
					$publicationName = encodeHTML($_REQUEST['publication']);
				else
					$publicationName = "";

				if (isset($_REQUEST['abbrev_journal']))
					$abbrevJournalName = encodeHTML($_REQUEST['abbrev_journal']);
				else
					$abbrevJournalName = "";

				if (isset($_REQUEST['volume']))
					$volumeNo = encodeHTML($_REQUEST['volume']);
				else
					$volumeNo = "";

				if (isset($_REQUEST['issue']))
					$issueNo = encodeHTML($_REQUEST['issue']);
				else
					$issueNo = "";

				if (isset($_REQUEST['pages']))
					$pagesNo = encodeHTML($_REQUEST['pages']);
				else
					$pagesNo = "";

				if (isset($_REQUEST['address']))
					$addressName = encodeHTML($_REQUEST['address']);
				else
					$addressName = "";

				if (isset($_REQUEST['corporate_author']))
					$corporateAuthorName = encodeHTML($_REQUEST['corporate_author']);
				else
					$corporateAuthorName = "";

				if (isset($_REQUEST['keywords']))
					$keywordsName = encodeHTML($_REQUEST['keywords']);
				else
					$keywordsName = "";

				if (isset($_REQUEST['abstract']))
					$abstractName = encodeHTML($_REQUEST['abstract']);
				else
					$abstractName = "";

				if (isset($_REQUEST['publisher']))
					$publisherName = encodeHTML($_REQUEST['publisher']);
				else
					$publisherName = "";

				if (isset($_REQUEST['place']))
					$placeName = encodeHTML($_REQUEST['place']);
				else
					$placeName = "";

				if (isset($_REQUEST['editor']))
					$editorName = encodeHTML($_REQUEST['editor']);
				else
					$editorName = "";

				if (isset($_REQUEST['language']))
					$languageName = encodeHTML($_REQUEST['language']);
				else
					$languageName = "";

				if (isset($_REQUEST['summary_language']))
					$summaryLanguageName = encodeHTML($_REQUEST['summary_language']);
				else
					$summaryLanguageName = "";

				if (isset($_REQUEST['orig_title']))
					$origTitleName = encodeHTML($_REQUEST['orig_title']);
				else
					$origTitleName = "";

				if (isset($_REQUEST['series_editor']))
					$seriesEditorName = encodeHTML($_REQUEST['series_editor']);
				else
					$seriesEditorName = "";

				if (isset($_REQUEST['series_title']))
					$seriesTitleName = encodeHTML($_REQUEST['series_title']);
				else
					$seriesTitleName = "";

				if (isset($_REQUEST['abbrev_series_title']))
					$abbrevSeriesTitleName = encodeHTML($_REQUEST['abbrev_series_title']);
				else
					$abbrevSeriesTitleName = "";

				if (isset($_REQUEST['series_volume']))
					$seriesVolumeNo = encodeHTML($_REQUEST['series_volume']);
				else
					$seriesVolumeNo = "";

				if (isset($_REQUEST['series_issue']))
					$seriesIssueNo = encodeHTML($_REQUEST['series_issue']);
				else
					$seriesIssueNo = "";

				if (isset($_REQUEST['edition']))
					$editionNo = encodeHTML($_REQUEST['edition']);
				else
					$editionNo = "";

				if (isset($_REQUEST['issn']))
					$issnName = encodeHTML($_REQUEST['issn']);
				else
					$issnName = "";

				if (isset($_REQUEST['isbn']))
					$isbnName = encodeHTML($_REQUEST['isbn']);
				else
					$isbnName = "";

				$mediumName = "";

				if (isset($_REQUEST['area']))
					$areaName = encodeHTML($_REQUEST['area']);
				else
					$areaName = "";

				$expeditionName = "";

				if (isset($_REQUEST['conference']))
					$conferenceName = encodeHTML($_REQUEST['conference']);
				else
					$conferenceName = "";

				if (isset($_REQUEST['notes']))
					$notesName = encodeHTML($_REQUEST['notes']);
				else
					$notesName = "";

				$approvedRadio = "";
				$locationName = $locationName; // supply some generic info: "(...will be filled in automatically)" [as defined at the top of this script]
				$rawLocationName = "";

				if (isset($_REQUEST['call_number']))
				{
					// if the data did originate from an import form -AND- (if the user isn't logged in -OR- any normal user is logged in (not the admin))...
					if ($recordAction == "add" AND $mode == "import" AND ((!isset($loginEmail)) OR ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail))))
					{
						$callNumberName = "";
						$callNumberNameUserOnly = encodeHTML($_REQUEST['call_number']); // for import, we assume that the contents of the call number field fully belong to the current user
					}
					else // if the data didn't originate from an import form or if the admin is logged in...
					{
						$callNumberName = encodeHTML($_REQUEST['call_number']);
						$callNumberNameUserOnly = "";
					}
				}
				else
				{
					$callNumberName = "";
					$callNumberNameUserOnly = "";
				}

				$serialNo = $serialNo; // supply some generic info: "(not assigned yet)" [as defined at the top of this script]

				if (isset($_REQUEST['type']))
					$typeName = encodeHTML($_REQUEST['type']);
				else
					$typeName = "";

				if (isset($_REQUEST['thesis']))
					$thesisName = encodeHTML($_REQUEST['thesis']);
				else
					$thesisName = "";

				$markedRadio = "";
				$copyName = "";
				$selectedRadio = "";
				$userKeysName = "";
				$userNotesName = "";
				$userFileName = "";
				$userGroupsName = "";
				$citeKeyName = "";
				$relatedName = "";
				$fileName = "";

				if (isset($_REQUEST['url']))
					$urlName = encodeHTML($_REQUEST['url']);
				else
					$urlName = "";

				if (isset($_REQUEST['doi']))
					$doiName = encodeHTML($_REQUEST['doi']);
				else
					$doiName = "";

				$contributionID = "";
				$onlinePublication = "";
				$onlineCitationName = "";
				$createdDate = ""; // for INSERTs, 'created_...' and 'modified_...' variables will get fresh values in 'modify.php' anyhow 
				$createdTime = "";
				$createdBy = "";
				$modifiedDate = "";
				$modifiedTime = "";
				$modifiedBy = "";
				$origRecord = "";
			}
			else // the user tried to add or edit a record but...
			{
				if (!empty($errors)) // ...there were some errors on submit. -> Re-load the data that were submitted by the user:
				{
					foreach($formVars as $varname => $value)
						// remove slashes from parameter values if 'magic_quotes_gpc = On':
						$formVars[$varname] = stripSlashesIfMagicQuotes($value); // function 'stripSlashesIfMagicQuotes()' is defined in 'include.inc.php'

					$authorName = $formVars['authorName'];

					if (isset($formVars['isEditorCheckBox'])) // the user did mark the "is Editor" checkbox
						$isEditorCheckBox = $formVars['isEditorCheckBox'];

					$titleName = $formVars['titleName'];
					$yearNo = $formVars['yearNo'];
					$publicationName = $formVars['publicationName'];
					$abbrevJournalName = $formVars['abbrevJournalName'];
					$volumeNo = $formVars['volumeNo'];
					$issueNo = $formVars['issueNo'];
					$pagesNo = $formVars['pagesNo'];
					$addressName = $formVars['addressName'];
					$corporateAuthorName = $formVars['corporateAuthorName'];
					$keywordsName = $formVars['keywordsName'];
					$abstractName = $formVars['abstractName'];
					$publisherName = $formVars['publisherName'];
					$placeName = $formVars['placeName'];
					$editorName = $formVars['editorName'];
					$languageName = $formVars['languageName'];
					$summaryLanguageName = $formVars['summaryLanguageName'];
					$origTitleName = $formVars['origTitleName'];
					$seriesEditorName = $formVars['seriesEditorName'];
					$seriesTitleName = $formVars['seriesTitleName'];
					$abbrevSeriesTitleName = $formVars['abbrevSeriesTitleName'];
					$seriesVolumeNo = $formVars['seriesVolumeNo'];
					$seriesIssueNo = $formVars['seriesIssueNo'];
					$editionNo = $formVars['editionNo'];
					$issnName = $formVars['issnName'];
					$isbnName = $formVars['isbnName'];
					$mediumName = $formVars['mediumName'];
					$areaName = $formVars['areaName'];
					$expeditionName = $formVars['expeditionName'];
					$conferenceName = $formVars['conferenceName'];
					$notesName = $formVars['notesName'];
					$approvedRadio = $formVars['approvedRadio'];

					if ($recordAction == "edit")
					{
						$locationName = $formVars['locationName'];
						$rawLocationName = $formVars['locationName'];
					}
					else
					{
						$locationName = $locationName; // supply some generic info: "(...will be filled in automatically)" [as defined at the top of this script]
						$rawLocationName = "";
					}

					$callNumberName = $formVars['callNumberName'];
					if (ereg("%40", $callNumberName)) // if '$callNumberName' still contains URL encoded data... ('%40' is the URL encoded form of the character '@', see note below!)
						$callNumberName = rawurldecode($callNumberName); // ...URL decode 'callNumberName' variable contents (it was URL encoded before incorporation into a hidden tag of the 'record' form to avoid any HTML syntax errors)
																		// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_POST'!
																		//       But, opposed to that, URL encoded data that are included within a form by means of a *hidden form tag* will NOT get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!

					$callNumberNameUserOnly = $formVars['callNumberNameUserOnly'];

					if ($recordAction == "edit")
						$serialNo = $formVars['serialNo'];
					else
						$serialNo = $serialNo; // supply some generic info: "(not assigned yet)" [as defined at the top of this script]

					$typeName = $formVars['typeName'];
					$thesisName = $formVars['thesisName'];
					$markedRadio = $formVars['markedRadio'];
					$copyName = $formVars['copyName'];
					$selectedRadio = $formVars['selectedRadio'];
					$userKeysName = $formVars['userKeysName'];
					$userNotesName = $formVars['userNotesName'];
					$userFileName = $formVars['userFileName'];
					$userGroupsName = $formVars['userGroupsName'];
					$citeKeyName = $formVars['citeKeyName'];
					$relatedName = $formVars['relatedName'];
					$fileName = $formVars['fileName'];
					$urlName = $formVars['urlName'];
					$doiName = $formVars['doiName'];

					$contributionID = $formVars['contributionIDName'];
					$contributionID = rawurldecode($contributionID); // URL decode 'contributionID' variable contents (it was URL encoded before incorporation into a hidden tag of the 'record' form to avoid any HTML syntax errors) [see above!]

					// check if we need to set the checkbox in front of "This is a ... publication.":
					if (isset($formVars['contributionIDCheckBox'])) // the user did mark the contribution ID checkbox
						$contributionIDCheckBox = $formVars['contributionIDCheckBox'];

					if (isset($formVars['locationSelectorName']))
						$locationSelectorName = $formVars['locationSelectorName'];
					else
						$locationSelectorName = "";

					// check if we need to set the "Online publication" checkbox:
					if (isset($formVars['onlinePublicationCheckBox'])) // the user did mark the "Online publication" checkbox
						$onlinePublication = "yes";
					else
						$onlinePublication = "no";

					$onlineCitationName = $formVars['onlineCitationName'];
					$createdDate = ""; // for INSERTs, 'created_...' and 'modified_...' variables will get fresh values in 'modify.php' anyhow 
					$createdTime = "";
					$createdBy = "";
					$modifiedDate = "";
					$modifiedTime = "";
					$modifiedBy = "";
					$origRecord = $formVars['origRecord'];
				}
				else // add a new record -> display an empty form (i.e., set all variables to an empty string [""] or their default values, respectively):
				{
					$authorName = "";
					$titleName = "";
					$yearNo = "";
					$publicationName = "";
					$abbrevJournalName = "";
					$volumeNo = "";
					$issueNo = "";
					$pagesNo = "";
					$addressName = "";
					$corporateAuthorName = "";
					$keywordsName = "";
					$abstractName = "";
					$publisherName = "";
					$placeName = "";
					$editorName = "";
					$languageName = "";
					$summaryLanguageName = "";
					$origTitleName = "";
					$seriesEditorName = "";
					$seriesTitleName = "";
					$abbrevSeriesTitleName = "";
					$seriesVolumeNo = "";
					$seriesIssueNo = "";
					$editionNo = "";
					$issnName = "";
					$isbnName = "";
					$mediumName = "";
					$areaName = "";
					$expeditionName = "";
					$conferenceName = "";
					$notesName = "";
					$approvedRadio = "";
					$locationName = $locationName; // supply some generic info: "(...will be filled in automatically)" [as defined at the top of this script]
					$rawLocationName = "";
					$callNumberName = "";
					$callNumberNameUserOnly = "";
					$serialNo = $serialNo; // supply some generic info: "(not assigned yet)" [as defined at the top of this script]
					$typeName = "Journal Article";
					$thesisName = "";
					$markedRadio = "";
					$copyName = "true";
					$selectedRadio = "";
					$userKeysName = "";
					$userNotesName = "";
					$userFileName = "";
					$userGroupsName = "";
					$citeKeyName = "";
					$relatedName = "";
					$fileName = "";
					$urlName = "";
					$doiName = "";
					$contributionID = "";
					$onlinePublication = "";
					$onlineCitationName = "";
					$createdDate = ""; // for INSERTs, 'created_...' and 'modified_...' variables will get fresh values in 'modify.php' anyhow 
					$createdTime = "";
					$createdBy = "";
					$modifiedDate = "";
					$modifiedTime = "";
					$modifiedBy = "";
					$origRecord = "";
				}
			}
		}

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (4a) DISPLAY header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(encodeHTML($officialDatabaseName) . " -- " . $pageTitle, "index,follow", "Add, edit or delete a record in the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
	showPageHeader($HeaderString);

	// (4b) DISPLAY results:
	// Start <form> and <table> holding the form elements:
	echo "\n<form enctype=\"multipart/form-data\" action=\"modify.php?proc=1\" method=\"POST\" accept-charset=\"" . $contentTypeCharset . "\">"; // '$contentTypeCharset' is defined in 'ini.inc.php'
	echo "\n<input type=\"hidden\" name=\"formType\" value=\"record\">";
	echo "\n<input type=\"hidden\" name=\"submit\" value=\"" . $addEditButtonTitle . "\">"; // provide a default value for the 'submit' form tag (then, hitting <enter> within a text entry field will act as if the user clicked the 'Add/Edit Record' button)
	echo "\n<input type=\"hidden\" name=\"recordAction\" value=\"" . $recordAction . "\">";
	echo "\n<input type=\"hidden\" name=\"contributionIDName\" value=\"" . rawurlencode($contributionID) . "\">";
	echo "\n<input type=\"hidden\" name=\"origRecord\" value=\"" . $origRecord . "\">";

	if ($recordAction == "edit")
	{
		// the following hidden form tags are included in order to have their values available when a record is moved to the 'deleted' table:
		echo "\n<input type=\"hidden\" name=\"createdDate\" value=\"" . $createdDate . "\">";
		echo "\n<input type=\"hidden\" name=\"createdTime\" value=\"" . $createdTime . "\">";
		echo "\n<input type=\"hidden\" name=\"createdBy\" value=\"" . $createdBy . "\">";
		echo "\n<input type=\"hidden\" name=\"modifiedDate\" value=\"" . $modifiedDate . "\">";
		echo "\n<input type=\"hidden\" name=\"modifiedTime\" value=\"" . $modifiedTime . "\">";
		echo "\n<input type=\"hidden\" name=\"modifiedBy\" value=\"" . $modifiedBy . "\">";
	}

	// include a hidden tag that indicates the login status *at the time this page was loaded*:
	// Background: We use the session variable "$loginEmail" to control whether a user is logged in or not. However, if a user is working in different browser windows/tabs
	//             the state/contents of a particular window might have changed due to any login/logout actions performed by the user. As an example, a user (who's currently NOT logged in!)
	//             could open several records in edit view to *different* browser windows. Then he realizes that he forgot to login and logs in on the last browser window. He submits that
	//             window and displays the next of his windows (where he still appears to be logged out). He doesn't notice the obsolete login status and goes on editing/submitting this window.
	//             Since the session variable is global, it WILL be possible to submit the form in that window! This proceedure will cause the following problems:
	// Problems:   1. For normal users, the user's *own* call number will get removed from the 'call_number' field contents! The user's call number prefix will remain, though.
	//                (the user's call number gets deleted, since the call number form field is left blank if a user isn't logged in)
	//             2. For normal users as well as for admins, any contribution ID that exists within the "contribution_id" field will be removed
	//                (this is, since the contribution ID checkbox isn't shown when the user isn't logged in)
	// Solution:   Since the above problems can't be circumvented easily with the current design, we simply include a hidden form tag, that indicates the user's login status on a
	//             *per page* basis. Then, 'modify.php' will only allow submitting of forms where "pageLoginStatus=logged in". If a user is already logged in, but the "pageLoginStatus" of the currently
	//             displayed page still states "logged out", he'll need to reload the page or click on the login link to update the "pageLoginStatus" first. This will avoid the problems outlined above.
	if (isset($loginEmail)) // if a user is logged in...
		echo "\n<input type=\"hidden\" name=\"pageLoginStatus\" value=\"logged in\">"; // ...the user was logged IN when loading this page
	else // if no user is logged in...
		echo "\n<input type=\"hidden\" name=\"pageLoginStatus\" value=\"logged out\">"; // ...the user was logged OUT when loading this page

	// if the user isn't logged in -OR- any normal user is logged in (not the admin)...
	if ((!isset($loginEmail)) OR ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail)))
		// except the admin, no user will be presented with the complete contents of the 'call_number' field! This is to prevent normal users
		// to mess with other user's personal call numbers. Instead, normal users will always only see their own id number within the 'call_number' field.
		// This should also avoid confusion how this field should/must be edited properly. Of course, the full contents of the 'call_number' field must be
		// preserved, therefore we include them within a hidden form tag:
		echo "\n<input type=\"hidden\" name=\"callNumberName\" value=\"" . rawurlencode($callNumberName) . "\">"; // ...include the *full* contents of the 'call_number' field

	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\" width=\"600\" summary=\"This table holds a form that offers to add records or edit existing ones\">"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>". $loc["Author"]."</b></td>"
			. "\n\t<td colspan=\"4\" class=\"mainfieldsbg\">" . fieldError("authorName", $errors) . "<input type=\"text\" name=\"authorName\" value=\"$authorName\" size=\"63\" title=\"". $loc["DescriptionAuthor"]."\"></td>";

	if ($isEditorCheckBox == "1" OR ereg(" *\(eds?\)$", $authorName)) // if the '$isEditorCheckBox' variable is set to 1 -OR- if 'author' field ends with either " (ed)" or " (eds)"
		$isEditorCheckBoxIsChecked = " checked"; // mark the 'is Editor' checkbox
	else
		$isEditorCheckBoxIsChecked = ""; // don't mark the 'is Editor' checkbox

	echo "\n\t<td align=\"right\" class=\"mainfieldsbg\"><input type=\"checkbox\" name=\"isEditorCheckBox\" value=\"1\"$isEditorCheckBoxIsChecked title=\"". $loc["DescriptionEditorCheckBox"]."\">&nbsp;&nbsp;<b>". $loc["isEditor"]."</b></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>". $loc["Title"]."</b></td>"
			. "\n\t<td colspan=\"3\" class=\"mainfieldsbg\">" . fieldError("titleName", $errors) . "<input type=\"text\" name=\"titleName\" value=\"$titleName\" size=\"48\" title=\"". $loc["DescriptionTitle"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>". $loc["Type"]."</b></td>";

	if (!isset($_SESSION['user_types']))
		$documentTypeDisabled = " disabled"; // disable the type popup if the session variable holding the user's types isn't available
	else
		$documentTypeDisabled = "";

	$recordType = "\n\t<td align=\"right\" class=\"mainfieldsbg\">"
				. "\n\t\t<select name=\"typeName\" title=\"". $loc["DescriptionType"]."\" $documentTypeDisabled>";
	
	if (isset($_SESSION['user_types']))
	{
		$optionTags = buildSelectMenuOptions($_SESSION['user_types'], " *; *", "\t\t\t", false); // build properly formatted <option> tag elements from the items listed in the 'user_types' session variable
		$recordType .= $optionTags;

		if ($recordAction == "edit" || $mode == "import") // for the edit (or import) record form, the current type is added to the drop down if it isn't one of the user's types
		{
			$userTypes = split(" *; *", $_SESSION['user_types']);
			$optionPresent = false;
			foreach ($userTypes as $userType)
			{
				if ($userType == $typeName)
				{
					$optionPresent = true;
				}
			}
			if ($optionPresent != true)
			{
				$recordType .= "\t\t\t<option>$typeName</option>";
			}
		}
	}
	else
		$recordType .= "<option>(no types available)</option>";

	$recordType .= "\n\t\t</select>"
				. "\n\t</td>";

	if (!empty($typeName))
		$recordType = ereg_replace("<option>$typeName", "<option selected>$typeName", $recordType);
	
	echo "$recordType"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>". $loc["Year"]."</b></td>"
			. "\n\t<td class=\"mainfieldsbg\">" . fieldError("yearNo", $errors) . "<input type=\"text\" name=\"yearNo\" value=\"$yearNo\" size=\"14\" title=\"". $loc["DescriptionYear"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>". $loc["Publication"]."</b></td>"
			. "\n\t<td class=\"mainfieldsbg\">" . fieldError("publicationName", $errors) . "<input type=\"text\" name=\"publicationName\" value=\"$publicationName\" size=\"14\" title=\"". $loc["DescriptionPublicationName"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>". $loc["JournalAbbr"]."</b></td>"
			. "\n\t<td align=\"right\" class=\"mainfieldsbg\">" . fieldError("abbrevJournalName", $errors) . "<input type=\"text\" name=\"abbrevJournalName\" value=\"$abbrevJournalName\" size=\"14\" title=\"". $loc["DescriptionJournalAbbr"]."\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>". $loc["Volume"]."</b></td>"
			. "\n\t<td class=\"mainfieldsbg\">" . fieldError("volumeNo", $errors) . "<input type=\"text\" name=\"volumeNo\" value=\"$volumeNo\" size=\"14\" title=\"". $loc["DescriptionVolume"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>". $loc["Issue"]."</b></td>"
			. "\n\t<td class=\"mainfieldsbg\"><input type=\"text\" name=\"issueNo\" value=\"$issueNo\" size=\"14\" title=\"". $loc["DescriptionIssue"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>". $loc["Pages"]."</b></td>"
			. "\n\t<td align=\"right\" class=\"mainfieldsbg\">" . fieldError("pagesNo", $errors) . "<input type=\"text\" name=\"pagesNo\" value=\"$pagesNo\" size=\"14\" title=\"". $loc["DescriptionPages"]."\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["Keywords"]."</b></td>"
			. "\n\t<td colspan=\"5\" class=\"otherfieldsbg\"><input type=\"text\" name=\"keywordsName\" value=\"$keywordsName\" size=\"85\" title=\"". $loc["DescriptionKeywords"]."\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["Abstract"]."</b></td>"
			. "\n\t<td colspan=\"5\" class=\"otherfieldsbg\"><textarea name=\"abstractName\" rows=\"6\" cols=\"83\" title=\"". $loc["DescriptionAbstract"]."\">$abstractName</textarea></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["Address"]."</b></td>"
			. "\n\t<td colspan=\"5\" class=\"otherfieldsbg\"><input type=\"text\" name=\"addressName\" value=\"$addressName\" size=\"85\" title=\"". $loc["DescriptionAdress"]."\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["CorporateAuthor"]."</b></td>"
			. "\n\t<td colspan=\"3\" class=\"otherfieldsbg\"><input type=\"text\" name=\"corporateAuthorName\" value=\"$corporateAuthorName\" size=\"48\" title=\"". $loc["DescriptionCorporate"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["Thesis"]."</b></td>";

	$thesisType = "\n\t<td align=\"right\" class=\"otherfieldsbg\">\n\t\t<select name=\"thesisName\" title=\"". $loc["DescriptionThesis"]."\">\n\t\t\t<option></option>\n\t\t\t<option value=\"Bachelor's thesis\">" . $loc["Bachelor's thesis"] . "</option>\n\t\t\t<option value=\"Master's thesis\">" . $loc["Master's thesis"] . "</option>\n\t\t\t<option value=\"Ph.D. thesis\">" . $loc["Ph.D. thesis"] . "</option>\n\t\t\t<option value=\"Diploma thesis\">" . $loc["Diploma thesis"] . "</option>\n\t\t\t<option value=\"Doctoral thesis\">" . $loc["Doctoral thesis"] . "</option>\n\t\t\t<option value=\"Habilitation thesis\">" . $loc["Habilitation thesis"] . "</option>\n\t\t</select>\n\t</td>";
	if (!empty($thesisName))
		$thesisType = preg_replace("/<option (value=\"" . $thesisName . "\")>/", "<option \\1 selected>", $thesisType);

	echo "$thesisType"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["Publisher"]."</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"publisherName\" value=\"$publisherName\" size=\"14\" title=\"". $loc["DescriptionPublisher"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["PublisherPlace"]."</b></td>"
			. "\n\t<td class=\"otherfieldsbg\" class=\"otherfieldsbg\"><input type=\"text\" name=\"placeName\" value=\"$placeName\" size=\"14\" title=\"". $loc["DescriptionPublisherPlace"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["Editor"]."</b></td>"
			. "\n\t<td align=\"right\" class=\"otherfieldsbg\"><input type=\"text\" name=\"editorName\" value=\"$editorName\" size=\"14\" title=\"". $loc["DescriptionEditor"]."\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["Language"]."</b></td>"
			. "\n\t<td class=\"otherfieldsbg\">" . fieldError("languageName", $errors) . "<input type=\"text\" name=\"languageName\" value=\"$languageName\" size=\"14\" title=\"". $loc["DescriptionLanguage"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["LanguageSummary"]."</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"summaryLanguageName\" value=\"$summaryLanguageName\" size=\"14\" title=\"". $loc["DescriptionLanguageSummary"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["TitleOriginal"]."</b></td>"
			. "\n\t<td align=\"right\" class=\"otherfieldsbg\"><input type=\"text\" name=\"origTitleName\" value=\"$origTitleName\" size=\"14\" title=\"". $loc["DescriptionTitleOriginal"]."\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["SeriesEditor"]."</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"seriesEditorName\" value=\"$seriesEditorName\" size=\"14\" title=\"". $loc["DescriptionSeriesEditor"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["TitleSeries"]."</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"seriesTitleName\" value=\"$seriesTitleName\" size=\"14\" title=\"". $loc["DescriptionTitleSeries"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["TitleSeriesAbbr"]."</b></td>"
			. "\n\t<td align=\"right\" class=\"otherfieldsbg\"><input type=\"text\" name=\"abbrevSeriesTitleName\" value=\"$abbrevSeriesTitleName\" size=\"14\" title=\"". $loc["DescriptionTitleSeriesAbbr"]."\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["SeriesVolume"]."</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"seriesVolumeNo\" value=\"$seriesVolumeNo\" size=\"14\" title=\"". $loc["DescriptionSeriesVolume"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["SeriesIssue"]."</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"seriesIssueNo\" value=\"$seriesIssueNo\" size=\"14\" title=\"". $loc["DescriptionSeriesIssue"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["Edition"]."</b></td>"
			. "\n\t<td align=\"right\" class=\"otherfieldsbg\"><input type=\"text\" name=\"editionNo\" value=\"$editionNo\" size=\"14\" title=\"". $loc["DescriptionEdition"]."\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["ISSN"]."</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"issnName\" value=\"$issnName\" size=\"14\" title=\"". $loc["DescriptionISSN"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["ISBN"]."</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"isbnName\" value=\"$isbnName\" size=\"14\" title=\"". $loc["DescriptionISBN"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["Medium"]."</b></td>"
			. "\n\t<td align=\"right\" class=\"otherfieldsbg\"><input type=\"text\" name=\"mediumName\" value=\"$mediumName\" size=\"14\" title=\"". $loc["DescriptionMedium"]."\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["Area"]."</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"areaName\" value=\"$areaName\" size=\"14\" title=\"". $loc["DescriptionArea"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["Expedition"]."</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"expeditionName\" value=\"$expeditionName\" size=\"14\" title=\"". $loc["DescriptionExpedition"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["Conference"]."</b></td>"
			. "\n\t<td align=\"right\" class=\"otherfieldsbg\"><input type=\"text\" name=\"conferenceName\" value=\"$conferenceName\" size=\"14\" title=\"". $loc["DescriptionConference"]."\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["Notes"]."</b></td>"
			. "\n\t<td colspan=\"3\" class=\"otherfieldsbg\"><input type=\"text\" name=\"notesName\" value=\"$notesName\" size=\"48\" title=\"". $loc["DescriptionNotes"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["Approved"]."</b></td>";

	$approved = "\n\t<td align=\"right\" class=\"otherfieldsbg\"><input type=\"radio\" name=\"approvedRadio\" value=\"yes\" title=\"". $loc["DescriptionApproved"]."\">&nbsp;&nbsp;". $loc["yes"]."&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"approvedRadio\" value=\"no\" title=\"". $loc["DescriptionApproved"]."\">&nbsp;&nbsp;". $loc["no"]."</td>";
	if ($approvedRadio == "yes")
		$approved = ereg_replace("name=\"approvedRadio\" value=\"yes\"", "name=\"approvedRadio\" value=\"yes\" checked", $approved);
	else // ($approvedRadio == "no")
		$approved = ereg_replace("name=\"approvedRadio\" value=\"no\"", "name=\"approvedRadio\" value=\"no\" checked", $approved);

	echo "$approved"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["Location"]."</b></td>"
			. "\n\t<td colspan=\"5\" class=\"otherfieldsbg\"><input type=\"text\" name=\"locationName\" value=\"$locationName\" size=\"85\" title=\"". $loc["DescriptionLocation"]."$fieldLockLabel\"$fieldLock></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>". $loc["CallNumber"]."</b></td>";

	// if the user isn't logged in -OR- any normal user is logged in (not the admin)...
	if ((!isset($loginEmail)) OR ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail)))
		// ...we just show the user's own call number (if any):
		echo "\n\t<td colspan=\"3\" class=\"mainfieldsbg\">" . fieldError("callNumberNameUserOnly", $errors) . "<input type=\"text\" name=\"callNumberNameUserOnly\" value=\"$callNumberNameUserOnly\" size=\"48\" title=\"". $loc["DescriptionCallNumber"]."\"></td>";
	else // if the admin is logged in...
		// ...we display the full contents of the 'call_number' field:
		echo "\n\t<td colspan=\"3\" class=\"mainfieldsbg\"><input type=\"text\" name=\"callNumberName\" value=\"$callNumberName\" size=\"48\" title=\"". $loc["DescriptionCallNumberFull"]."\"></td>";

	echo "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>". $loc["Serial"]."</b></td>"
			. "\n\t<td align=\"right\" class=\"mainfieldsbg\"><input type=\"text\" name=\"serialNo\" value=\"$serialNo\" size=\"14\" title=\"". $loc["DescriptionSerial"]."\" readonly></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"userfieldsbg\"><b>". $loc["Marked"]."</b></td>";

	$marked = "\n\t<td class=\"userfieldsbg\"><input type=\"radio\" name=\"markedRadio\" value=\"yes\" title=\"". $loc["DescriptionMarked"]."\">&nbsp;&nbsp;". $loc["yes"]."&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"markedRadio\" value=\"no\" title=\"". $loc["DescriptionMarked"]."\">&nbsp;&nbsp;". $loc["no"]."</td>";
	if ($markedRadio == "yes")
		$marked = ereg_replace("name=\"markedRadio\" value=\"yes\"", "name=\"markedRadio\" value=\"yes\" checked", $marked);
	else // ($markedRadio == "no")
		$marked = ereg_replace("name=\"markedRadio\" value=\"no\"", "name=\"markedRadio\" value=\"no\" checked", $marked);

	echo "$marked"
			. "\n\t<td width=\"74\" class=\"userfieldsbg\"><b>". $loc["Copy"]."</b></td>";
	
	$copy = "\n\t<td class=\"userfieldsbg\">\n\t\t<select name=\"copyName\" title=\"". $loc["DescriptionCopy"]."\">\n\t\t\t<option value=\"true\">". $loc["true"]."</option>\n\t\t\t<option value=\"fetch\">". $loc["fetch"]."</option>\n\t\t\t<option value=\"ordered\">". $loc["ordered"]."</option>\n\t\t\t<option value=\"false\">". $loc["false"]."</option>\n\t\t</select>\n\t</td>";
	if (!empty($copyName))
		$copy = preg_replace("/<option(.*?)>" . $loc[$copyName] . "/", "<option\\1 selected>" . $loc[$copyName], $copy);
	
	echo "$copy"
			. "\n\t<td width=\"74\" class=\"userfieldsbg\"><b>". $loc["Selected"]."</b></td>";

	$selected = "\n\t<td align=\"right\" class=\"userfieldsbg\"><input type=\"radio\" name=\"selectedRadio\" value=\"yes\" title=\"". $loc["DescriptionSelected"]."\">&nbsp;&nbsp;". $loc["yes"]."&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"selectedRadio\" value=\"no\" title=\"". $loc["DescriptionSelected"]."\">&nbsp;&nbsp;". $loc["no"]."</td>";
	if ($selectedRadio == "yes")
		$selected = ereg_replace("name=\"selectedRadio\" value=\"yes\"", "name=\"selectedRadio\" value=\"yes\" checked", $selected);
	else // ($selectedRadio == "no")
		$selected = ereg_replace("name=\"selectedRadio\" value=\"no\"", "name=\"selectedRadio\" value=\"no\" checked", $selected);

	echo "$selected"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"userfieldsbg\"><b>". $loc["UserKeys"]."</b></td>"
			. "\n\t<td colspan=\"5\" class=\"userfieldsbg\"><input type=\"text\" name=\"userKeysName\" value=\"$userKeysName\" size=\"85\" title=\"". $loc["DescriptionUserKeys"]."\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"userfieldsbg\"><b>". $loc["UserNotes"]."</b></td>"
			. "\n\t<td colspan=\"3\" class=\"userfieldsbg\"><input type=\"text\" name=\"userNotesName\" value=\"$userNotesName\" size=\"48\" title=\"". $loc["DescriptionUserNotes"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"userfieldsbg\"><b>". $loc["UserFile"]."</b></td>"
			. "\n\t<td align=\"right\" class=\"userfieldsbg\"><input type=\"text\" name=\"userFileName\" value=\"$userFileName\" size=\"14\" title=\"". $loc["DescriptionUserFile"]."\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"userfieldsbg\"><b>". $loc["UserGroups"]."</b></td>";

	if (isset($_SESSION['user_permissions']) AND ereg("allow_user_groups", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_user_groups'...
	// adjust the title string for the user groups text entry field:
	{
		$userGroupsFieldLock = "";
		$userGroupsTitle = $loc["DescriptionUserGroups"];
	}
	else
	{
		$userGroupsFieldLock = " disabled"; // it would be more consistent to remove the user groups field completely from the form if the user has no permission to use the user groups feature; but since this would complicate the processing quite a bit, we just disable the field (for now)
		$userGroupsTitle = $loc["NoPermission"] . $loc["NoPermission_ForUserGroups"];
	}

	echo "\n\t<td colspan=\"3\" class=\"userfieldsbg\"><input type=\"text\" name=\"userGroupsName\" value=\"$userGroupsName\" size=\"48\"$userGroupsFieldLock title=\"$userGroupsTitle\"></td>"
			. "\n\t<td width=\"74\" class=\"userfieldsbg\"><b>". $loc["CiteKey"]."</b></td>"
			. "\n\t<td align=\"right\" class=\"userfieldsbg\"><input type=\"text\" name=\"citeKeyName\" value=\"$citeKeyName\" size=\"14\" title=\"". $loc["DescriptionCiteKey"]."\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"userfieldsbg\"><b>". $loc["Related"]."</b></td>"
			. "\n\t<td colspan=\"5\" class=\"userfieldsbg\"><input type=\"text\" name=\"relatedName\" value=\"$relatedName\" size=\"85\" title=\"". $loc["DescriptionRelated"]."\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["File"]."</b></td>"
			. "\n\t<td colspan=\"3\" class=\"otherfieldsbg\"><input type=\"text\" name=\"fileName\" value=\"$fileName\" size=\"48\" title=\"". $loc["DescriptionFile"]."\"$fieldLock></td>";

	if (isset($_SESSION['user_permissions']) AND ereg("allow_upload", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_upload'...
	// adjust the title string for the upload button:
	{
		$uploadButtonLock = "";
		$uploadTitle = $loc["DescriptionFileUpload"];
	}
	else
	{
		$uploadButtonLock = " disabled"; // disabling of the upload button doesn't seem to work in all browsers (e.g., it doesn't work in Safari on MacOSX Panther, but does work with Mozilla & Camino) ?:-/
		$uploadTitle = $loc["NoPermission"] . $loc["NoPermission_ForFileUpload"]; // similarily, not all browsers will show title strings for disabled buttons (Safari does, Mozilla & Camino do not)
	}

	echo "\n\t<td valign=\"bottom\" colspan=\"2\" class=\"otherfieldsbg\">" . fieldError("uploadFile", $errors) . "<input type=\"file\" name=\"uploadFile\" size=\"17\"$uploadButtonLock title=\"$uploadTitle\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["URL"]."</b></td>"
			. "\n\t<td colspan=\"3\" class=\"otherfieldsbg\"><input type=\"text\" name=\"urlName\" value=\"$urlName\" size=\"48\" title=\"". $loc["DescriptionURL"]."\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>". $loc["DOI"]."</b></td>"
			. "\n\t<td align=\"right\" class=\"otherfieldsbg\"><input type=\"text\" name=\"doiName\" value=\"$doiName\" size=\"14\" title=\"". $loc["DescriptionDOI"]."\"></td>"
			. "\n</tr>";

	if ($onlinePublication == "yes") // if the 'online_publication' field value is "yes"
		$onlinePublicationCheckBoxIsChecked = " checked"; // mark the 'Online publication' checkbox
	else
		$onlinePublicationCheckBoxIsChecked = ""; // don't mark the 'Online publication' checkbox

	echo "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\">&nbsp;</td>"
			. "\n\t<td colspan=\"3\" class=\"otherfieldsbg\">\n\t\t<input type=\"checkbox\" name=\"onlinePublicationCheckBox\" value=\"1\"$onlinePublicationCheckBoxIsChecked title=\"". $loc["DescriptionOnlinePublicationCheckbox"]."\">&nbsp;"
			. "\n\t\t". $loc["Online publication. Cite with this text:"]."&nbsp;<input type=\"text\" name=\"onlineCitationName\" value=\"$onlineCitationName\" size=\"9\" title=\"". $loc["DescriptionOnlinePublicationCitation"]."\">\n\t</td>";

	if (isset($loginEmail)) // if a user is logged in...
	{
		// ...we'll show a checkbox where the user can state that the current publication stems form his own institution
		if ($contributionIDCheckBox == "1" OR ereg("$abbrevInstitution", $contributionID)) // if the '$contributionIDCheckBox' variable is set to 1 -OR- if the currrent user's abbreviated institution name is listed within the 'contribution_id' field
			$contributionIDCheckBoxIsChecked = " checked";
		else
			$contributionIDCheckBoxIsChecked = "";

		if ($origRecord > 0) // if the current record has been identified as duplicate entry...
			$contributionIDCheckBoxLock = " disabled"; // ...we lock the check box (since the original entry, and not the dup entry, should be marked instead)
		else
			$contributionIDCheckBoxLock = "";

		echo "\n\t<td colspan=\"2\" class=\"otherfieldsbg\">\n\t\t<input type=\"checkbox\" name=\"contributionIDCheckBox\" value=\"1\"$contributionIDCheckBoxIsChecked title=\"". $loc["DescriptionOwnPublication"]."\"$contributionIDCheckBoxLock>&nbsp;"
				. "\n\t\t". encodeHTML($abbrevInstitution) . " " . $loc["publication"] . "\n\t</td>"; // we make use of the session variable '$abbrevInstitution' here
	}
	else
	{
		echo "\n\t<td colspan=\"2\" class=\"otherfieldsbg\">&nbsp;</td>";
	}

	echo "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\">&nbsp;</td>"
			. "\n\t<td colspan=\"5\">&nbsp;</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td width=\"74\">". $loc["Location Field"].":</td>";

	$locationSelector = "\n\t<td colspan=\"3\">\n\t\t<select name=\"locationSelectorName\" title=\"". $loc["DescriptionLocationSelector"]."\">\n\t\t\t<option value=\"don't touch\">". $loc["don't touch"]."</option>\n\t\t\t<option value=\"add\">". $loc["add"]."</option>\n\t\t\t<option value=\"remove\">". $loc["remove"]."</option>\n\t\t</select>&nbsp;&nbsp;\n\t\t". $loc["my name & email address"]."\n\t</td>";
	if ($recordAction == "edit" AND !empty($locationSelectorName))
		$locationSelector = preg_replace("/<option(.*?)>" . $loc[$locationSelectorName] . "/", "<option\\1 selected>" . $loc[$locationSelectorName], $locationSelector);
	elseif ($recordAction == "add")
	{
		$locationSelector = preg_replace("/<option(.*?)>" . $loc["add"] . "/", "<option\\1 selected>" . $loc["add"], $locationSelector); // select the appropriate menu entry ...
		if ((!isset($loginEmail)) OR ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail))) // ... and if the user isn't logged in -OR- any normal user is logged in (not the admin) ...
			$locationSelector = ereg_replace("<select", "<select disabled", $locationSelector); // ... disable the popup menu. This is, since the current user & email address will be always written to the location field when adding new records. An orphaned record would be produced if the user could chose anything other than 'add'! (Note that the admin is permitted to override this behaviour)
	}

	echo "$locationSelector"
			. "\n\t<td align=\"right\" colspan=\"2\">";

	// Note that, normally, we don't show interface items which the user isn't allowed to use (see the delete button). But, in the case of the add/edit button we make an exception here and just grey the button out.
	// This is, since otherwise the form would have no submit button at all, which would be pretty odd. The title string of the button explains why it is disabled.
	if ($recordAction == "edit") // adjust the title string for the edit button
	{
		if (isset($_SESSION['user_permissions']) AND ereg("allow_edit", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_edit'...
		{
			$addEditButtonLock = "";
			$addEditTitle = $loc["DescriptionEditButton"];
		}
		else
		{
			$addEditButtonLock = " disabled";
			$addEditTitle = $loc["NoPermission"] . $loc["NoPermission_ForEditRecords"];
		}
	}
	else // if ($recordAction == "add") // adjust the title string for the add button
	{
		if (isset($_SESSION['user_permissions']) AND ereg("allow_add", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_add'...
		{
			$addEditButtonLock = "";
			$addEditTitle = $loc["DescriptionAddButton"];
		}
		else
		{
			$addEditButtonLock = " disabled";
			$addEditTitle = $loc["NoPermission"] . $loc["NoPermission_ForAddRecords"];
		}
	}

	// display an ADD/EDIT button:
	echo "<input type=\"submit\" name=\"submit\" value=\"$addEditButtonTitle\"$addEditButtonLock title=\"$addEditTitle\">";

	if (isset($_SESSION['user_permissions']) AND ereg("allow_delete", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_delete'...
	// ... display a delete button:
	{
		if ($recordAction == "edit") // add a DELETE button (CAUTION: the delete button must be displayed *AFTER* the edit button, otherwise DELETE will be the default action if the user hits return!!)
									// (this is since the first displayed submit button represents the default submit action in several browsers!! [like OmniWeb or Mozilla])
		{
			if (!isset($loginEmail) OR ((!ereg($loginEmail,$locationName) OR ereg(";",$rawLocationName)) AND ($loginEmail != $adminLoginEmail))) // if the user isn't logged in -OR- any normal user is logged in & the 'location' field doesn't list her email address -OR- if the 'location' field contains more than one user (which is indicated by a semicolon character)...
				// Note that we use '$rawLocationName' instead of the '$locationName' variable for those tests that check for the existence of a semicolon since for '$locationName' high ASCII characters were converted into HTML entities.
				// E.g., the german umlaut 'ü' would be presented as '&uuml;', thus containing a semicolon character *within* the user's name!
			{
				// build an informative title string:
				if (!isset($loginEmail)) // if the user isn't logged in
					$deleteTitle = $loc["DescriptionDeleteButtonDisabled"] . $loc["DescriptionDeleteButtonDisabledNotLoggedIn"];

				elseif (!ereg($loginEmail, $locationName)) // if any normal user is logged in & the 'location' field doesn't list her email address
					$deleteTitle = $loc["DescriptionDeleteButtonDisabled"] . $loc["DescriptionDeleteButtonDisabledNotYours"];

				elseif (ereg(";", $rawLocationName)) // if the 'location' field contains more than one user (which is indicated by a semicolon character)
				{
					// if we made it here, the current user is listed within the 'location' field of this record
					if (ereg("^[^;]+;[^;]+$", $rawLocationName)) // the 'location' field does contain exactly one ';' => two authors, i.e., there's only one "other user" listed within the 'location' field
						$deleteTitle = $loc["DescriptionDeleteButtonDisabled"] . $loc["DescriptionDeleteButtonDisabledOtherUser"];
					elseif (ereg("^[^;]+;[^;]+;[^;]+", $rawLocationName)) // the 'location' field does contain at least two ';' => more than two authors, i.e., there are two or more "other users" listed within the 'location' field
						$deleteTitle = $loc["DescriptionDeleteButtonDisabled"] . $loc["DescriptionDeleteButtonDisabledOtherUsers"];
				}
	
				$deleteButtonLock = " disabled"; // ...we lock the delete button (since a normal user shouldn't be allowed to delete records that belong to other users)
			}
			else
			{
				$deleteTitle = $loc["DescriptionDeleteButton"];
				$deleteButtonLock = "";
			}
	
			echo "&nbsp;&nbsp;&nbsp;<input type=\"submit\" name=\"submit\" value=\"" . $loc["ButtonTitle_DeleteRecord"] . "\"$deleteButtonLock title=\"$deleteTitle\">";
		}
	}

	echo "</td>"
			. "\n</tr>"
			. "\n</table>"
			. "\n</form>";
	
	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase(); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// SHOW ERROR IN RED:
	function fieldError($fieldName, $errors)
	{
		if (isset($errors[$fieldName]))
			return "<b><span class=\"warning2\">" . $errors[$fieldName] . "</span></b><br>";
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
	showPageFooter($HeaderString);

	displayHTMLfoot();

	// --------------------------------------------------------------------
?>
