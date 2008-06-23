<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./duplicate_search.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    28-Jan-07, 09:17
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This script provides a form that lets you search for duplicate records.
	// 
	// 


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
	// register globals is ON, or explicitly if register globals is OFF [by uncommenting the code above]).
	// We need to clear these session variables here, since they would otherwise be still there on a subsequent call of 'duplicate_search.php'!
	// Note: though we clear the session variables, the current error message (or form variables) is still available to this script via '$errors' (or '$formVars', respectively).
	deleteSessionVariable("errors"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	deleteSessionVariable("formVars");

	// --------------------------------------------------------------------

	// Extract the view type requested by the user (either 'Mobile', 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";


	// Check if the script was called with parameters (like: 'duplicate_search.php?customQuery=1&sqlQuery=...')
	// If so, the parameter 'customQuery=1' will be set:
	if (isset($_REQUEST['customQuery']) AND ($_REQUEST['customQuery'] == "1"))
		$customQuery = "1"; // accept any previous SQL queries
	else
		$customQuery = "0";


	// get the referring URL (if any):
	if (isset($_SERVER['HTTP_REFERER']))
		$referer = $_SERVER['HTTP_REFERER'];
	else
		$referer = ""; // if there's no HTTP referer available we provide the empty string here


	// Setup some required variables:

	// If there's no stored message available:
	if (!isset($_SESSION['HeaderString']))
	{
		if (empty($errors)) // provide one of the default messages:
		{
			$errors = array(); // re-assign an empty array (in order to prevent 'Undefined variable "errors"...' messages when calling the 'fieldError' function later on)
			if ($customQuery == "1") // the script was called with parameters
				$HeaderString = "Find duplicates that match your current query:"; // Provide the default message
			else // the script was called without any custom SQL query
				$HeaderString = "Find duplicates:"; // Provide the default message
		}
		else // -> there were errors validating the data entered by the user
			$HeaderString = "<b><span class=\"warning\">There were validation errors regarding the data you entered:</span></b>";

	}
	else
	{
		$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

		// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
		deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	}

	// --------------------------------------------------------------------

	// Assign correct values to the form variables:

	// a) Provide the default query and options:

	// list of fields that are selected by default:
	$selectedFieldsArray = array("author", "title", "year", "publication", "volume", "pages");

	// default SQL query:
	// TODO: build the complete SQL query using functions 'buildFROMclause()' and 'buildORDERclause()'
	$sqlQuery = buildSELECTclause("", "", "", false, false); // function 'buildSELECTclause()' is defined in 'include.inc.php'

	if (isset($_SESSION['loginEmail']))
		$sqlQuery .= " FROM $tableRefs WHERE location RLIKE \"" . $loginEmail . "\" ORDER BY year DESC, author"; // '$loginEmail' is defined in function 'start_session()' (in 'include.inc.php')
	else
		$sqlQuery .= " FROM $tableRefs WHERE serial RLIKE \".+\" ORDER BY year DESC, author";

	// default search options:
	$ignoreWhitespace = "1";
	$ignorePunctuation = "1";
	$ignoreCharacterCase = "1";
	$ignoreAuthorInitials = "1";
	$nonASCIICharsSelected = "strip";

	// default display options:
	$displayType = $_SESSION['userDefaultView']; // get the default view for the current user
	$showLinks = "1";
	$showRows = $_SESSION['userRecordsPerPage']; // get the default number of records per page preferred by the current user
	$citeStyle = $defaultCiteStyle; // defined in 'ini.inc.php'
	$citeOrder = "";

	// b) The default query and options are overwritten if the script was called with parameters or if there were some errors on submit:

	if ($customQuery == "1") // the script was called with parameters
	{
		// extract selected fields:
		if (isset($_REQUEST['matchFieldsSelector']))
		{
			if (is_string($_REQUEST['matchFieldsSelector'])) // we accept a string containing a (e.g. comma delimited) list of field names
				$selectedFieldsArray = preg_split("/[^a-z_]+/", $_REQUEST['matchFieldsSelector'], -1, PREG_SPLIT_NO_EMPTY); // (the 'PREG_SPLIT_NO_EMPTY' flag causes only non-empty pieces to be returned)
			else // the field list is already provided as array:
				$selectedFieldsArray = $_REQUEST['matchFieldsSelector'];
		}

		// extract SQL query:
		if (isset($_REQUEST['sqlQuery']))
		{
			$sqlQuery = $_REQUEST['sqlQuery']; // accept any previous SQL queries
			$sqlQuery = stripSlashesIfMagicQuotes($sqlQuery); // function 'stripSlashesIfMagicQuotes()' is defined in 'include.inc.php'
		}

		// extract search options:
		if (isset($_REQUEST['ignoreWhitespace']) AND ($_REQUEST['ignoreWhitespace'] == "0"))
			$ignoreWhitespace = "0";

		if (isset($_REQUEST['ignorePunctuation']) AND ($_REQUEST['ignorePunctuation'] == "0"))
			$ignorePunctuation = "0";

		if (isset($_REQUEST['ignoreCharacterCase']) AND ($_REQUEST['ignoreCharacterCase'] == "0"))
			$ignoreCharacterCase = "0";

		if (isset($_REQUEST['ignoreAuthorInitials']) AND ($_REQUEST['ignoreAuthorInitials'] == "0"))
			$ignoreAuthorInitials = "0";

		if (isset($_REQUEST['nonASCIIChars']))
			$nonASCIICharsSelected = $_REQUEST['nonASCIIChars'];

		// extract display options:
		if (isset($_REQUEST['originalDisplayType']))
			$displayType = $_REQUEST['originalDisplayType']; // extract the type of display requested by the user (either 'Display', 'Cite', 'List' or '')

		if (isset($_REQUEST['showLinks']))
			$showLinks = $_REQUEST['showLinks'];

		if (isset($_REQUEST['showRows']))
			$showRows = $_REQUEST['showRows'];

		if (isset($_REQUEST['citeStyle']))
			$citeStyle = $_REQUEST['citeStyle'];

		if (isset($_REQUEST['citeOrder']))
			$citeOrder = $_REQUEST['citeOrder'];
	}

	elseif (!empty($errors)) // there were some errors on submit
	{
		// load selected fields:
		if (isset($formVars['matchFieldsSelector']))
			$selectedFieldsArray = $formVars['matchFieldsSelector'];

		// load the form data that were entered by the user:
		if (isset($formVars['sqlQuery']))
		{
			$sqlQuery = $formVars['sqlQuery'];
			$sqlQuery = stripSlashesIfMagicQuotes($sqlQuery);
		}

		// load search options:
		if (isset($formVars['ignoreWhitespace']))
			$ignoreWhitespace = $formVars['ignoreWhitespace'];

		if (isset($formVars['ignorePunctuation']))
			$ignorePunctuation = $formVars['ignorePunctuation'];

		if (isset($formVars['ignoreCharacterCase']))
			$ignoreCharacterCase = $formVars['ignoreCharacterCase'];

		if (isset($formVars['ignoreAuthorInitials']))
			$ignoreAuthorInitials = $formVars['ignoreAuthorInitials'];

		if (isset($formVars['nonASCIIChars']))
			$nonASCIICharsSelected = $formVars['nonASCIIChars'];

		// load display options:
		if (isset($formVars['originalDisplayType']))
			$displayType = $formVars['originalDisplayType'];

		if (isset($formVars['showLinks']))
			$showLinks = $formVars['showLinks'];

		if (isset($formVars['showRows']))
			$showRows = $formVars['showRows'];

		if (isset($formVars['citeStyle']))
			$citeStyle = $formVars['citeStyle'];

		if (isset($formVars['citeOrder']))
			$citeOrder = $formVars['citeOrder'];
	}


	// Set search and display options according to the fetched attribute values:

	// 'ignoreWhitespace' option:
	if ($ignoreWhitespace == "1")
		$ignoreWhitespaceChecked = " checked";
	else
		$ignoreWhitespaceChecked = "";

	// 'ignorePunctuation' option:
	if ($ignorePunctuation == "1")
		$ignorePunctuationChecked = " checked";
	else
		$ignorePunctuationChecked = "";

	// 'ignoreCharacterCase' option:
	if ($ignoreCharacterCase == "1")
		$ignoreCharacterCaseChecked = " checked";
	else
		$ignoreCharacterCaseChecked = "";

	// 'ignoreAuthorInitials' option:
	if ($ignoreAuthorInitials == "1")
		$ignoreAuthorInitialsChecked = " checked";
	else
		$ignoreAuthorInitialsChecked = "";

	// 'showLinks' option:
	if ($showLinks == "1")
		$checkLinks = " checked";
	else
		$checkLinks = "";


	// Initialize variables used for the multi-select & drop-down form elements:

	// specify which fields should be available in the multi-select box:
	// (the list of fields is similar to that of the "Search within Results" form; while only
	// some fields make sense with regard to duplicate identification, the other fields may be
	// useful in finding non-duplicate records with matching field contents)
	$dropDownItemArray1 = array("author"         => "author",
	                            "title"          => "title",
//	                            "type"           => "type",
	                            "year"           => "year",
	                            "publication"    => "publication",
	                            "abbrev_journal" => "abbrev_journal",
	                            "volume"         => "volume",
	                            "issue"          => "issue",
	                            "pages"          => "pages",
//	                            "thesis"         => "thesis",
//	                            "keywords"       => "keywords",
//	                            "abstract"       => "abstract",
//	                            "publisher"      => "publisher",
//	                            "place"          => "place",
//	                            "editor"         => "editor",
//	                            "language"       => "language",
//	                            "series_title"   => "series_title",
//	                            "area"           => "area",
	                            "notes"          => "notes");

//	if (isset($_SESSION['loginEmail']))
//		$dropDownItemArray1["location"] = "location"; // we only add the 'location' field if the user is logged in

//	if (isset($_SESSION['loginEmail'])) // add user-specific fields if a user is logged in
//	{
//		$dropDownItemArray1["marked"] = "marked";
//		$dropDownItemArray1["copy"] = "copy";
//		$dropDownItemArray1["selected"] = "selected";
//		$dropDownItemArray1["user_keys"] = "user_keys";
//		$dropDownItemArray1["user_notes"] = "user_notes";
//		$dropDownItemArray1["user_file"] = "user_file";
//		$dropDownItemArray1["user_groups"] = "user_groups";
//		$dropDownItemArray1["cite_key"] = "cite_key";
//	}

	// build properly formatted <option> tag elements from array items given in '$dropDownItemArray1':
	$matchFieldsOptionTags = buildSelectMenuOptions($dropDownItemArray1, "", "\t\t\t\t", true); // function 'buildSelectMenuOptions()' is defined in 'include.inc.php'

	// by default we select all fields that are listed within '$selectedFieldsArray':
	$selectedFields = implode("|", $selectedFieldsArray); // merge array of fields that shall be selected

	$matchFieldsOptionTags = ereg_replace("<option([^>]*)>($selectedFields)</option>", "<option\\1 selected>\\2</option>", $matchFieldsOptionTags);


	// define variable holding the 'nonASCIIChars' drop-down elements:
	$dropDownItemArray2 = array("strip"         => "strip",
	                            "transliterate" => "transliterate",
	                            "keep"          => "don't change");

	// build properly formatted <option> tag elements from array items given in '$dropDownItemArray2':
	$nonASCIICharsOptionTags = buildSelectMenuOptions($dropDownItemArray2, "", "\t\t\t\t", true);

	// add 'selected' attribute:
	$nonASCIICharsOptionTags = ereg_replace("<option([^>]*)>($dropDownItemArray2[$nonASCIICharsSelected])</option>", "<option\\1 selected>\\2</option>", $nonASCIICharsOptionTags);

	// --------------------------------------------------------------------

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (2a) Display header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(encodeHTML($officialDatabaseName) . " -- " . "Find Duplicates", "index,follow", "Search for duplicates within the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
	showPageHeader($HeaderString);

	// (2b) Start <form> and <table> holding the form elements:
	// note: we provide a default value for the 'submit' form tag so that hitting <enter> within a text entry field will act as if the user clicked the 'Add/Edit Query' button
?>

<form action="search.php" method="GET">
<input type="hidden" name="formType" value="duplicateSearch">
<input type="hidden" name="originalDisplayType" value="<?php echo $displayType; ?>">
<input type="hidden" name="submit" value="Find Duplicates">
<input type="hidden" name="citeStyle" value="<?php echo rawurlencode($citeStyle); ?>">
<input type="hidden" name="citeOrder" value="<?php echo $citeOrder; ?>">
<table align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds a form that lets you search for duplicate records">
	<tr>
		<td width="58" valign="top"><b>Match Fields:</b></td>
		<td width="10">&nbsp;</td>
		<td valign="top"><?php echo fieldError("matchFieldsSelector", $errors); ?>

			<select name="matchFieldsSelector[]" multiple><?php echo $matchFieldsOptionTags; ?>

			</select>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="submit" value="Find Duplicates">
		</td>
	</tr>
	<tr>
		<td align="center" colspan="3">&nbsp;</td>
	</tr>
	<tr>
		<td valign="top" rowspan="5"><b>Search Options:</b></td>
		<td rowspan="5">&nbsp;</td>
		<td valign="top"><input type="checkbox" name="ignoreWhitespace" value="1"<?php echo $ignoreWhitespaceChecked; ?>>&nbsp;&nbsp;&nbsp;Ignore whitespace</td>
	</tr>
	<tr>
		<td valign="top"><input type="checkbox" name="ignorePunctuation" value="1"<?php echo $ignorePunctuationChecked; ?>>&nbsp;&nbsp;&nbsp;Ignore punctuation</td>
	</tr>
	<tr>
		<td valign="top"><input type="checkbox" name="ignoreCharacterCase" value="1"<?php echo $ignoreCharacterCaseChecked; ?>>&nbsp;&nbsp;&nbsp;Ignore character case</td>
	</tr>
	<tr>
		<td valign="top"><input type="checkbox" name="ignoreAuthorInitials" value="1"<?php echo $ignoreAuthorInitialsChecked; ?>>&nbsp;&nbsp;&nbsp;Ignore initials in author names</td>
	</tr>
	<tr>
		<td valign="top">
			<select name="nonASCIIChars"><?php echo $nonASCIICharsOptionTags; ?>

			</select>
			&nbsp;&nbsp;&nbsp;non-ASCII characters before comparison
		</td>

	</tr>
	<tr>
		<td align="center" colspan="3">&nbsp;</td>
	</tr>
	<tr>
		<td width="58" valign="top"><b>SQL Query:</b></td>
		<td width="10">&nbsp;</td>
		<td><?php echo fieldError("sqlQuery", $errors); ?><textarea name="sqlQuery" rows="6" cols="60"><?php echo $sqlQuery; ?></textarea></td>
	</tr>
	<tr>
		<td valign="top"><b>Display Options:</b></td>
		<td>&nbsp;</td>
		<td valign="top"><input type="checkbox" name="showLinks" value="1"<?php echo $checkLinks; ?>>&nbsp;&nbsp;&nbsp;Display links&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Show&nbsp;&nbsp;&nbsp;<input type="text" name="showRows" value="<?php echo $showRows; ?>" size="4">&nbsp;&nbsp;&nbsp;records per page</td>
	</tr>
	<tr>
		<td align="center" colspan="3">&nbsp;</td>
	</tr>
	<tr>
		<td valign="top"><b>Help:</b></td>
		<td>&nbsp;</td>
		<td>Modify the elements of this form as needed and click the <em>Find Duplicates</em> button. You can use the field selector to specify which fields shall be considered when matching records. The search options allow you to normalize field contents before comparison. The SQL query string defines the scope of the duplicate search and (in case of List view) specifies the columns that will be displayed in the results list.</td>
	</tr>
	<tr>
		<td valign="top">&nbsp;</td>
		<td>&nbsp;</td>
		<td>The <a href="http://www.mysql.com/documentation/index.html">MySQL online manual</a> has a <a href="http://dev.mysql.com/doc/mysql/en/tutorial.html">tutorial introduction</a> on using MySQL and provides a detailed description of the <a href="http://www.mysql.com/doc/S/E/SELECT.html"><code>SELECT</code> syntax</a>.</td>
	</tr>
</table>
</form><?php

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
