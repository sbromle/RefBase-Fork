<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./user_options.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    24-Oct-04, 19:31
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This script provides options which are individual for each user.
	// 
	// TODO: I18n, more encodeHTML fixes?


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

	// Extract session variables (only necessary if register globals is OFF!):
	if (isset($_SESSION['errors']))
		$errors = $_SESSION['errors'];
	else
		$errors = array(); // initialize variable (in order to prevent 'Undefined index/variable...' messages)

	if (isset($_SESSION['formVars']))
		$formVars = $_SESSION['formVars'];
	else
		$formVars = array(); // initialize variable (in order to prevent 'Undefined index/variable...' messages)

	// The current values of the session variables 'errors' and 'formVars' get stored in '$errors' or '$formVars', respectively. (either automatically if
	// register globals is ON, or explicitly if register globals is OFF).
	// We need to clear these session variables here, since they would otherwise be there even if 'user_options.php' gets called with a different userID!
	// Note: though we clear the session variables, the current error message (or form variables) is still available to this script via '$errors' (or '$formVars', respectively).
	deleteSessionVariable("errors"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	deleteSessionVariable("formVars");

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(""); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// A user must be logged in in order to call 'user_options.php':
	if (!isset($_SESSION['loginEmail']))
	{
		// save an error message:
		$HeaderString = "<b><span class=\"warning\">You must login to view your user account options!</span></b>";

		// save the URL of the currently displayed page:
		$referer = $_SERVER['HTTP_REFERER'];

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		saveSessionVariable("referer", $referer);

		header("Location: user_login.php");
		exit;
	}

	// --------------------------------------------------------------------

	// Set the '$userID' variable:
	if (isset($_REQUEST['userID'])) // for normal users NOT being logged in -OR- for the admin:
		$userID = $_REQUEST['userID'];
	else
		$userID = NULL; // '$userID = ""' wouldn't be correct here, since then any later 'isset($userID)' statement would resolve to true!

	if (isset($_SESSION['loginEmail']) && ($loginEmail != $adminLoginEmail)) // a normal user IS logged in ('$adminLoginEmail' is specified in 'ini.inc.php')
		// Check this user matches the userID (viewing and modifying other user's account options is only allowed to the admin)
		if ($userID != getUserID($loginEmail)) // (function 'getUserID()' is defined in 'include.inc.php')
		{
			// save an error message:
			$HeaderString = "<b><span class=\"warning\">You can only edit your own user data!</span></b>";

			// Write back session variables:
			saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

			$userID = getUserID($loginEmail); // re-establish the user's correct user_id
		}

	// --------------------------------------------------------------------

	// Check the correct parameters have been passed
	if ($userID == "") // note that we can't use 'empty($userID)' here, since 'userID=0' must be allowed so that the admin can edit options for the default user (= no user logged in)
	{
		// save an error message:
		$HeaderString = "<b><span class=\"warning\">Missing parameters for script 'user_options.php'!</span></b>";

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

		// Redirect the browser back to the calling page
		header("Location: index.php"); // Note: if 'header("Location: " . $_SERVER['HTTP_REFERER'])' is used, the error message won't get displayed! ?:-/
		exit;
	}

	// --------------------------------------------------------------------

	// Set header message:
	if (!isset($_SESSION['HeaderString'])) // if there's no stored message available
	{
		if (empty($errors)) // provide the default messages:
			$HeaderString = "Modify your account options:";
		else // -> there were errors validating the user's options
			$HeaderString = "<b><span class=\"warning\">There were validation errors regarding the options you selected. Please check the comments above the respective fields:</span></b>";
	}
	else
	{
		$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

		// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
		deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	}

	// Extract the view type requested by the user (either 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";


	// CONSTRUCT SQL QUERY:
	$query = "SELECT first_name, last_name, email, language FROM $tableUsers WHERE user_id = " . quote_smart($userID);

	// (3a) RUN the query on the database through the connection:
	$result = queryMySQLDatabase($query, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

	// (3b) EXTRACT results:
	$row = mysql_fetch_array($result); // fetch the current row into the array $row

	// If the admin is logged in AND the displayed user data are NOT his own, we overwrite the default header message:
	// (Since the admin is allowed to view and edit account data from other users, we have to provide a dynamic header message in that case)
	if (($loginEmail == $adminLoginEmail) && (!empty($userID)) && ($userID != getUserID($loginEmail))) // ('$adminLoginEmail' is specified in 'ini.inc.php')
		$HeaderString = "Edit account options for <b>" . encodeHTML($row["first_name"]) . " " . encodeHTML($row["last_name"]) . " (" . $row["email"] . ")</b>:";
	elseif (empty($userID))
		$HeaderString = "Edit account options for anyone who isn't logged in:";

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (4) DISPLAY header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(encodeHTML($officialDatabaseName) . " -- User Options", "noindex,nofollow", "User options offered by the " . encodeHTML($officialDatabaseName), "\n\t<meta http-equiv=\"expires\" content=\"0\">", true, "", $viewType, array());
	showPageHeader($HeaderString, "");

	// --------------------------------------------------------------------

	if (empty($errors))
	{
		// Reset the '$formVars' variable (since we're loading from the user tables):
		$formVars = array();

		// Reset the '$errors' variable:
		$errors = array();

		// Load all the form variables with user data & options:
		$formVars["language"] = $row["language"];
	}


	// Initialize variables which will set form elements according to the current user's options:


	// Get all user options for the current user:
	$userOptionsArray = getUserOptions($userID); // function 'getUserOptions()' is defined in 'include.inc.php'


	// Display Options:
	if (!empty($userID))
	{
		// Get all languages that were setup and enabled by the admin:
		$languagesArray = getLanguages(""); // function 'getLanguages()' is defined in 'include.inc.php'
		$languagePopupDisabled = "";
	}
	else // if '$userID == 0' which indicates a user not being logged in
	{
		$languagesArray = array($defaultLanguage); // for a user who's not logged in, we fall back to the default language (defined in 'ini.inc.php')
		$languagePopupDisabled = " disabled"; // disable the language popup if the user isn't logged in
	}

	$languageOptionTags = buildSelectMenuOptions($languagesArray, " *; *", "\t\t\t", false); // build properly formatted <option> tag elements from language items returned by function 'getLanguages()'
	$userLanguage = getLanguages($userID); // get the preferred language for the current user
	$languageOptionTags = ereg_replace("<option>$userLanguage[0]", "<option selected>$userLanguage[0]", $languageOptionTags); // select the user's preferred language

	// Get the default number of records per page preferred by the current user:
	// 'records_per_page' option:
	$recordsPerPage = getDefaultNumberOfRecords($userID); // function 'getDefaultNumberOfRecords()' is defined in 'include.inc.php'

	// Get all reference types that are available (admin logged in) or which were enabled for the current user (normal user logged in):
	$typeOptionTags = returnFormatsStylesTypesAsOptionTags($userID, "type", ""); // function 'returnFormatsStylesTypesAsOptionTags()' is defined in 'include.inc.php'

	// Get all citation styles that are available (admin logged in) or which were enabled for the current user (normal user logged in):
	$styleOptionTags = returnFormatsStylesTypesAsOptionTags($userID, "style", "");

	// Get all citation formats that are available (admin logged in) or which were enabled for the current user (normal user logged in):
	$citeFormatOptionTags = returnFormatsStylesTypesAsOptionTags($userID, "format", "cite");

	// Get all export formats that are available (admin logged in) or which were enabled for the current user (normal user logged in):
	$exportFormatOptionTags = returnFormatsStylesTypesAsOptionTags($userID, "format", "export");

	if ($loginEmail == $adminLoginEmail) // if the admin is logged in
		$selectListIdentifier = "Enabled";
	else // if ($loginEmail != $adminLoginEmail) // if a normal user is logged in
		$selectListIdentifier = "Show";

	// Define fields that can be designated as "main fields":
	$mainFieldsArray = array("author"              => $loc["DropDownFieldName_Author"],
//	                         "address"             => $loc["DropDownFieldName_Address"],
//	                         "corporate_author"    => $loc["DropDownFieldName_CorporateAuthor"],
//	                         "thesis"              => $loc["DropDownFieldName_Thesis"],
	                         "title"               => $loc["DropDownFieldName_Title"],
//	                         "orig_title"          => $loc["DropDownFieldName_OrigTitle"],
	                         "year"                => $loc["DropDownFieldName_Year"],
	                         "publication"         => $loc["DropDownFieldName_Publication"],
	                         "abbrev_journal"      => $loc["DropDownFieldName_AbbrevJournal"],
	                         "editor"              => $loc["DropDownFieldName_Editor"],
//	                         "volume"              => $loc["DropDownFieldName_Volume"],
//	                         "issue"               => $loc["DropDownFieldName_Issue"],
//	                         "pages"               => $loc["DropDownFieldName_Pages"],
	                         "series_title"        => $loc["DropDownFieldName_SeriesTitle"],
	                         "abbrev_series_title" => $loc["DropDownFieldName_AbbrevSeriesTitle"],
//	                         "series_editor"       => $loc["DropDownFieldName_SeriesEditor"],
//	                         "series_volume"       => $loc["DropDownFieldName_SeriesVolume"],
//	                         "series_issue"        => $loc["DropDownFieldName_SeriesIssue"],
//	                         "publisher"           => $loc["DropDownFieldName_Publisher"],
//	                         "place"               => $loc["DropDownFieldName_Place"],
//	                         "edition"             => $loc["DropDownFieldName_Edition"],
//	                         "medium"              => $loc["DropDownFieldName_Medium"],
//	                         "issn"                => $loc["DropDownFieldName_Issn"],
//	                         "isbn"                => $loc["DropDownFieldName_Isbn"],
//	                         "language"            => $loc["DropDownFieldName_Language"],
//	                         "summary_language"    => $loc["DropDownFieldName_SummaryLanguage"],
	                         "keywords"            => $loc["DropDownFieldName_Keywords"],
	                         "abstract"            => $loc["DropDownFieldName_Abstract"],
	                         "area"                => $loc["DropDownFieldName_Area"],
//	                         "expedition"          => $loc["DropDownFieldName_Expedition"],
//	                         "conference"          => $loc["DropDownFieldName_Conference"],
//	                         "doi"                 => $loc["DropDownFieldName_Doi"],
//	                         "url"                 => $loc["DropDownFieldName_Url"],
//	                         "file"                => $loc["DropDownFieldName_File"],
	                         "notes"               => $loc["DropDownFieldName_Notes"],
//	                         "location"            => $loc["DropDownFieldName_Location"],
	                         "call_number"         => $loc["DropDownFieldName_CallNumber"],
	                         "serial"              => $loc["DropDownFieldName_Serial"],
//	                         "type"                => $loc["DropDownFieldName_Type"],
//	                         "approved"            => $loc["DropDownFieldName_Approved"],
//	                         "created_date"        => $loc["DropDownFieldName_CreatedDate"],
//	                         "created_time"        => $loc["DropDownFieldName_CreatedTime"],
//	                         "created_by"          => $loc["DropDownFieldName_CreatedBy"],
//	                         "modified_date"       => $loc["DropDownFieldName_ModifiedDate"],
//	                         "modified_time"       => $loc["DropDownFieldName_ModifiedTime"],
//	                         "modified_by"         => $loc["DropDownFieldName_ModifiedBy"],
//	                         "marked"              => $loc["DropDownFieldName_Marked"],
//	                         "copy"                => $loc["DropDownFieldName_Copy"],
//	                         "selected"            => $loc["DropDownFieldName_Selected"],
//	                         "user_keys"           => $loc["DropDownFieldName_UserKeys"],
//	                         "user_notes"          => $loc["DropDownFieldName_UserNotes"],
//	                         "user_file"           => $loc["DropDownFieldName_UserFile"],
//	                         "user_groups"         => $loc["DropDownFieldName_UserGroups"],
	                         "cite_key"            => $loc["DropDownFieldName_CiteKey"],
	                        );

	// Build properly formatted <option> tag elements from array items given in '$mainFieldsArray':
	$mainFieldsOptionTags = buildSelectMenuOptions($mainFieldsArray, "", "\t\t\t", true); // function 'buildSelectMenuOptions()' is defined in 'include.inc.php'

	// Get the list of "main fields" preferred by the current user:
	// 'main_fields' option:
	$userMainFieldsArray = getMainFields($userID);

	// select all fields that shall be searched when the "main fields" search option is chosen:
	// (these fields will also be included as separate entries in the "Quick Search drop-down menu)
	foreach($userMainFieldsArray as $userMainField)
		$mainFieldsOptionTags = ereg_replace("<option([^>]*)>" . $mainFieldsArray[$userMainField] . "</option>", "<option\\1 selected>" . $mainFieldsArray[$userMainField] . "</option>", $mainFieldsOptionTags);


	// Cite Options:
	// 'use_custom_text_citation_format' option:
	if (!empty($userOptionsArray) AND ($userOptionsArray['use_custom_text_citation_format'] == "yes"))
		$useCustomTextCitationFormatChecked = " checked";
	else
		$useCustomTextCitationFormatChecked = "";

	// 'text_citation_format' option:
	if (!empty($userOptionsArray['text_citation_format']))
		$textCitationFormat = $userOptionsArray['text_citation_format'];
	else
		$textCitationFormat = "";


	// Export Options:
	// 'export_cite_keys' option:
	if (!empty($userOptionsArray) AND ($userOptionsArray['export_cite_keys'] == "yes"))
		$exportCiteKeysChecked = " checked";
	else
		$exportCiteKeysChecked = "";

	// 'autogenerate_cite_keys' option:
	if (!empty($userOptionsArray) AND ($userOptionsArray['autogenerate_cite_keys'] == "yes"))
		$autogenerateCiteKeysChecked = " checked";
	else
		$autogenerateCiteKeysChecked = "";

	// 'prefer_autogenerated_cite_keys' option:
	if (!empty($userOptionsArray) AND ($userOptionsArray['prefer_autogenerated_cite_keys'] == "yes"))
	{
		$preferAutogeneratedCiteKeysChecked = " checked";
		$dontPreferAutogeneratedCiteKeysChecked = "";
	}
	else
	{
		$preferAutogeneratedCiteKeysChecked = "";
		$dontPreferAutogeneratedCiteKeysChecked = " checked";
	}

	// 'use_custom_cite_key_format' option:
	if (!empty($userOptionsArray) AND ($userOptionsArray['use_custom_cite_key_format'] == "yes"))
		$useCustomCiteKeyFormatChecked = " checked";
	else
		$useCustomCiteKeyFormatChecked = "";

	// 'cite_key_format' option:
	if (!empty($userOptionsArray['cite_key_format']))
		$citeKeyFormat = $userOptionsArray['cite_key_format'];
	else
		$citeKeyFormat = "";

	// 'uniquify_duplicate_cite_keys' option:
	if (!empty($userOptionsArray) AND ($userOptionsArray['uniquify_duplicate_cite_keys'] == "yes"))
		$uniquifyDuplicateCiteKeysChecked = " checked";
	else
		$uniquifyDuplicateCiteKeysChecked = "";

	// define variable holding drop-down elements:
	$dropDownItemArray = array("transliterate" => "transliterate",
	                           "strip"         => "strip",
	                           "keep"          => "keep");

	// build properly formatted <option> tag elements from array items given in '$dropDownItemArray':
	$nonASCIICharsInCiteKeysOptionTags = buildSelectMenuOptions($dropDownItemArray, "", "\t\t\t", true); // function 'buildSelectMenuOptions()' is defined in 'include.inc.php'

	// 'nonascii_chars_in_cite_keys' option:
	if (!empty($userOptionsArray['nonascii_chars_in_cite_keys']))
	{
		$useCustomHandlingOfNonASCIICharsInCiteKeysChecked = " checked";

		// select the drop down option chosen by the current user:
		$nonASCIICharsInCiteKeysOptionTags = ereg_replace("<option([^>]*)>" . $userOptionsArray['nonascii_chars_in_cite_keys'], "<option\\1 selected>" . $userOptionsArray['nonascii_chars_in_cite_keys'], $nonASCIICharsInCiteKeysOptionTags);
	}
	else
		$useCustomHandlingOfNonASCIICharsInCiteKeysChecked = "";


	// Start <form> and <table> holding all the form elements:
?>

<form method="POST" action="user_options_modify.php" name="userOptions">
<input type="hidden" name="userID" value="<?php echo encodeHTML($userID) ?>">
<table align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds a form with user options">
<tr>
	<td align="left" width="169"><b><a id="display">Display Options:</a></b></td>
	<td align="left" width="169">Use language:</td>
	<td><?php echo fieldError("languageName", $errors); ?>

		<select name="languageName"<?php echo $languagePopupDisabled; ?>><?php echo $languageOptionTags; ?>

		</select>
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td align="left">Show records per page:</td>
	<td><?php echo fieldError("recordsPerPageNo", $errors); ?>

		<input type="text" name="recordsPerPageNo" value="<?php echo encodeHTML($recordsPerPage); ?>" size="5">
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td align="left" valign="top"><?php echo $selectListIdentifier; ?> reference types:</td>
	<td valign="top"><?php echo fieldError("referenceTypeSelector", $errors); ?>

		<select name="referenceTypeSelector[]" multiple><?php echo $typeOptionTags; ?>

		</select>
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td align="left" valign="top"><?php echo $selectListIdentifier; ?> citation styles:</td>
	<td valign="top"><?php echo fieldError("citationStyleSelector", $errors); ?>

		<select name="citationStyleSelector[]" multiple><?php echo $styleOptionTags; ?>

		</select>
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td align="left" valign="top"><?php echo $selectListIdentifier; ?> citation formats:</td>
	<td valign="top"><?php echo fieldError("citationFormatSelector", $errors); ?>

		<select name="citationFormatSelector[]" multiple><?php echo $citeFormatOptionTags; ?>

		</select>
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td align="left" valign="top"><?php echo $selectListIdentifier; ?> export formats:</td>
	<td valign="top"><?php echo fieldError("exportFormatSelector", $errors); ?>

		<select name="exportFormatSelector[]" multiple><?php echo $exportFormatOptionTags; ?>

		</select>
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td align="left" valign="top">"Main fields" searches:</td>
	<td valign="top"><?php echo fieldError("mainFieldsSelector", $errors); ?>

		<select name="mainFieldsSelector[]" multiple><?php echo $mainFieldsOptionTags; ?>

		</select>
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2">
		<input type="submit" value="Submit">
	</td>
</tr>
<tr>
	<td align="left" height="15"></td>
	<td colspan="2"></td>
</tr>
<tr>
	<td align="left"><b><a id="cite">Cite Options:</a></b></td>
	<td colspan="2">
		<input type="checkbox" name="use_custom_text_citation_format" value="yes"<?php echo $useCustomTextCitationFormatChecked; ?>>&nbsp;&nbsp;Use custom text citation format:
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2">
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" name="text_citation_format" value="<?php echo encodeHTML($textCitationFormat); ?>" size="46">
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2"></td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2">
		<input type="submit" value="Submit">
	</td>
</tr>
<tr>
	<td align="left" height="15"></td>
	<td colspan="2"></td>
</tr>
<tr>
	<td align="left"><b><a id="export">Export Options:</a></b></td>
	<td colspan="2">
		<input type="checkbox" name="export_cite_keys" value="yes"<?php echo $exportCiteKeysChecked; ?>>&nbsp;&nbsp;Include cite keys on export
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2">
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="autogenerate_cite_keys" value="yes"<?php echo $autogenerateCiteKeysChecked; ?>>&nbsp;&nbsp;Auto-generate cite keys on export for:
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2">
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="prefer_autogenerated_cite_keys" value="yes"<?php echo $preferAutogeneratedCiteKeysChecked; ?>>&nbsp;&nbsp;all records
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2">
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="prefer_autogenerated_cite_keys" value="no"<?php echo $dontPreferAutogeneratedCiteKeysChecked; ?>>&nbsp;&nbsp;records with empty 'Cite Key' field
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2">
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="use_custom_cite_key_format" value="yes"<?php echo $useCustomCiteKeyFormatChecked; ?>>&nbsp;&nbsp;Use custom format for auto-generated cite keys:
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2">
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" name="cite_key_format" value="<?php echo encodeHTML($citeKeyFormat); ?>" size="46">
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2">
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="use_custom_handling_of_nonascii_chars_in_cite_keys" value="yes"<?php echo $useCustomHandlingOfNonASCIICharsInCiteKeysChecked; ?>>&nbsp;&nbsp;Use custom handling of non-ASCII characters in cite keys:
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2">
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<select name="nonascii_chars_in_cite_keys"><?php echo $nonASCIICharsInCiteKeysOptionTags; ?>

		</select>
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2">
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="uniquify_duplicate_cite_keys" value="yes"<?php echo $uniquifyDuplicateCiteKeysChecked; ?>>&nbsp;&nbsp;Append incrementing numbers to duplicate cite keys
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2"></td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2">
		<input type="submit" value="Submit">
	</td>
</tr><?php
	if ($loginEmail == $adminLoginEmail) // if the admin is logged in, add form elements to set the user's permissions:
	{
		// Get the user permissions for the current user:
		$userPermissionsArray = getPermissions($userID, "user", false); // function 'getPermissions()' is defined in 'include.inc.php'

		// Setup variables to mark the checkboxes according to the user's permissions:
		if ($userPermissionsArray['allow_add'] == 'yes')
			$allowAddChecked = " checked";
		else
			$allowAddChecked = "";

		if ($userPermissionsArray['allow_edit'] == 'yes')
			$allowEditChecked = " checked";
		else
			$allowEditChecked = "";

		if ($userPermissionsArray['allow_delete'] == 'yes')
			$allowDeleteChecked = " checked";
		else
			$allowDeleteChecked = "";

		if ($userPermissionsArray['allow_download'] == 'yes')
			$allowDownloadChecked = " checked";
		else
			$allowDownloadChecked = "";

		if ($userPermissionsArray['allow_upload'] == 'yes')
			$allowUploadChecked = " checked";
		else
			$allowUploadChecked = "";

		if ($userPermissionsArray['allow_details_view'] == 'yes')
			$allowDetailsViewChecked = " checked";
		else
			$allowDetailsViewChecked = "";

		if ($userPermissionsArray['allow_print_view'] == 'yes')
			$allowPrintViewChecked = " checked";
		else
			$allowPrintViewChecked = "";

		if ($userPermissionsArray['allow_browse_view'] == 'yes') // 'Browse view' isn't presented as visible option yet
			$allowBrowseViewChecked = " checked";
		else
			$allowBrowseViewChecked = "";

		if ($userPermissionsArray['allow_sql_search'] == 'yes')
			$allowSQLSearchChecked = " checked";
		else
			$allowSQLSearchChecked = "";

		if ($userPermissionsArray['allow_user_groups'] == 'yes')
			$allowUserGroupsChecked = " checked";
		else
			$allowUserGroupsChecked = "";

		if ($userPermissionsArray['allow_user_queries'] == 'yes')
			$allowUserQueriesChecked = " checked";
		else
			$allowUserQueriesChecked = "";

		if ($userPermissionsArray['allow_rss_feeds'] == 'yes')
			$allowRSSFeedsChecked = " checked";
		else
			$allowRSSFeedsChecked = "";

		if ($userPermissionsArray['allow_import'] == 'yes')
			$allowImportChecked = " checked";
		else
			$allowImportChecked = "";

		if ($userPermissionsArray['allow_batch_import'] == 'yes')
			$allowBatchImportChecked = " checked";
		else
			$allowBatchImportChecked = "";

		if ($userPermissionsArray['allow_export'] == 'yes')
			$allowExportChecked = " checked";
		else
			$allowExportChecked = "";

		if ($userPermissionsArray['allow_batch_export'] == 'yes')
			$allowBatchExportChecked = " checked";
		else
			$allowBatchExportChecked = "";

		if ($userPermissionsArray['allow_cite'] == 'yes')
			$allowCiteChecked = " checked";
		else
			$allowCiteChecked = "";

		if ($userPermissionsArray['allow_modify_options'] == 'yes')
			$allowChangePersonInfoChecked = " checked";
		else
			$allowChangePersonInfoChecked = "";
?>

<tr>
	<td align="left" height="15"></td>
	<td colspan="2"></td>
</tr>
<tr>
	<td align="left"><b><a id="permissions">User Permissions:</a></b></td>
	<td>
		<input type="checkbox" name="allow_add" value="yes"<?php echo $allowAddChecked; ?>>&nbsp;&nbsp;Add records
	</td>
	<td>
		<input type="checkbox" name="allow_download" value="yes"<?php echo $allowDownloadChecked; ?>>&nbsp;&nbsp;File download
	</td>
</tr>
<tr>
	<td align="left" class="small">
		<!--<a href="JavaScript:checkall(true,'allow*')" title="select all permission options">Select All</a>&nbsp;&nbsp;&nbsp;-->
		<!--<a href="JavaScript:checkall(false,'allow*')" title="deselect all permission options">Deselect All</a>-->
	</td>
	<td>
		<input type="checkbox" name="allow_edit" value="yes"<?php echo $allowEditChecked; ?>>&nbsp;&nbsp;Edit records
	</td>
	<td>
		<input type="checkbox" name="allow_upload" value="yes"<?php echo $allowUploadChecked; ?>>&nbsp;&nbsp;File upload
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td>
		<input type="checkbox" name="allow_delete" value="yes"<?php echo $allowDeleteChecked; ?>>&nbsp;&nbsp;Delete records
	</td>
	<td></td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2"></td>
</tr>
<tr>
	<td align="left"></td>
	<td>
		<input type="checkbox" name="allow_details_view" value="yes"<?php echo $allowDetailsViewChecked; ?>>&nbsp;&nbsp;Details view
	</td>
	<td>
		<input type="checkbox" name="allow_sql_search" value="yes"<?php echo $allowSQLSearchChecked; ?>>&nbsp;&nbsp;SQL search
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td>
		<input type="checkbox" name="allow_print_view" value="yes"<?php echo $allowPrintViewChecked; ?>>&nbsp;&nbsp;Print view
	</td>
	<td></td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2"></td>
</tr>
<tr>
	<td align="left"></td>
	<td>
		<input type="checkbox" name="allow_user_groups" value="yes"<?php echo $allowUserGroupsChecked; ?>>&nbsp;&nbsp;User groups
	</td>
	<td>
		<input type="checkbox" name="allow_rss_feeds" value="yes"<?php echo $allowRSSFeedsChecked; ?>>&nbsp;&nbsp;RSS feeds
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td>
		<input type="checkbox" name="allow_user_queries" value="yes"<?php echo $allowUserQueriesChecked; ?>>&nbsp;&nbsp;User queries
	</td>
	<td></td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2"></td>
</tr>
<tr>
	<td align="left"></td>
	<td>
		<input type="checkbox" name="allow_import" value="yes"<?php echo $allowImportChecked; ?>>&nbsp;&nbsp;Import
	</td>
	<td>
		<input type="checkbox" name="allow_batch_import" value="yes"<?php echo $allowBatchImportChecked; ?>>&nbsp;&nbsp;Batch import
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td>
		<input type="checkbox" name="allow_export" value="yes"<?php echo $allowExportChecked; ?>>&nbsp;&nbsp;Export
	</td>
	<td>
		<input type="checkbox" name="allow_batch_export" value="yes"<?php echo $allowBatchExportChecked; ?>>&nbsp;&nbsp;Batch export
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td>
		<input type="checkbox" name="allow_cite" value="yes"<?php echo $allowCiteChecked; ?>>&nbsp;&nbsp;Cite
	</td>
	<td></td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2"></td>
</tr>
<tr>
	<td align="left"></td>
	<td>
		<input type="checkbox" name="allow_modify_options" value="yes"<?php echo $allowChangePersonInfoChecked; ?>>&nbsp;&nbsp;Modify options
	</td>
	<td></td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2"></td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2">
		<input type="submit" value="Submit">
	</td>
</tr><?php
	}
?>

</table>
</form><?php

	// --------------------------------------------------------------------

	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase(""); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// SHOW ERROR IN RED:
	function fieldError($fieldName, $errors)
	{
		if (isset($errors[$fieldName]))
			echo "\n\t\t<b><span class=\"warning\">" . $errors[$fieldName] . "</span></b>\n\t\t<br>";
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
	showPageFooter($HeaderString, "");

	displayHTMLfoot();

	// --------------------------------------------------------------------
?>
