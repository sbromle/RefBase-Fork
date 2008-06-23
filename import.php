<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./import.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    17-Feb-06, 20:57
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// Import form that offers to import records from Reference Manager (RIS), CSA Illumina,
	// RefWorks Tagged Format, SciFinder Tagged Format, ISI Web of Science, PubMed MEDLINE, PubMed XML, MODS XML,
	// Endnote Tagged Text, BibTeX or COPAC. Import of the latter five formats is provided via use of bibutils.


	// Incorporate some include files:
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

		// Remove slashes from parameter values if 'magic_quotes_gpc = On':
		foreach($formVars as $varname => $value)
			$formVars[$varname] = stripSlashesIfMagicQuotes($value); // function 'stripSlashesIfMagicQuotes()' is defined in 'include.inc.php'

		// Note: though we clear the session variable, the current form variables are still available to this script via '$formVars':
		deleteSessionVariable("formVars"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	}
	else
		$formVars = array();

	// --------------------------------------------------------------------

	// Initialize preferred display language:
	// (note that 'locales.inc.php' has to be included *after* the call to the 'start_session()' function)
	include 'includes/locales.inc.php'; // include the locales

	// --------------------------------------------------------------------

	// If there's no stored message available:
	if (!isset($_SESSION['HeaderString']))
	{
		if (empty($errors)) // provide one of the default messages:
		{
			if (isset($_SESSION['user_permissions']) AND ereg("(allow_batch_import)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does contain 'allow_batch_import'...
				$HeaderString = "Import records:"; // Provide the default message
			else
				$HeaderString = "Import a record:"; // Provide the default message
		}
		else // -> there were errors validating the user's data input
			$HeaderString = "<b><span class=\"warning\">There were validation errors regarding the data you entered:</span></b>";
	}
	else // there is already a stored message available
	{
		$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

		// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
		deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	}

	// Adopt the page title & some labels according to the user's permissions:
	if (isset($_SESSION['user_permissions']) AND !ereg("(allow_batch_import)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does NOT contain 'allow_batch_import'...
	{
		$pageTitle = " -- Import Record"; // adopt page title
		$textEntryFormLabel = "Record"; // adopt the label for the text entry form
		$rowSpan = ""; // adopt table row span parameter
	}
	else
	{
		$pageTitle = " -- Import Records";
		$textEntryFormLabel = "Records";
		$rowSpan = " rowspan=\"2\"";
	}

	// Extract the view type requested by the user (either 'Mobile', 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";

	// If there were some errors on submit -> Re-load the data that were submitted by the user:
	if (!empty($errors))
	{
		$formType = $formVars['formType']; // get the form type that was submitted by the user (and which subsequently caused an error)

		// (A) main import form:
		if (isset($formVars['sourceText'])) // '$formVars['sourceText']' may be non-existent in the (unlikely but possible) event that a user calls 'import_modify.php' directly
			$sourceText = $formVars['sourceText'];
		else
			$sourceText = "";

		if (isset($formVars['importRecordsRadio'])) // 'importRecordsRadio' is only set if user has 'batch_import' permission
			$importRecordsRadio = $formVars['importRecordsRadio'];
		else
			$importRecordsRadio = "";

		if (isset($formVars['importRecords'])) // 'importRecords' is only set if user has 'batch_import' permission
			$importRecords = $formVars['importRecords'];
		else
			$importRecords = "1";

		// check whether the user marked the checkbox to skip records with unrecognized data format:
		if (isset($formVars['skipBadRecords']))
			$skipBadRecords = $formVars['skipBadRecords'];
		else
			$skipBadRecords = "";

		// (B) "Import IDs" form (imports records from PubMed ID, arXiv ID, DOI or OpenURL):
		if (isset($formVars['sourceIDs']))
			$sourceIDs = $formVars['sourceIDs'];
		else
			$sourceIDs = "";
	}
	else // display an empty form (i.e., set all variables to an empty string [""] or their default values, respectively):
	{
		$formType = "";

		// (A) main import form:
		$sourceText = "";
		$importRecordsRadio = "all";
		$importRecords = "1";
		$skipBadRecords = "";

		// (B) "Import IDs" form:
		$sourceIDs = "";
	}

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (2a) Display header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(encodeHTML($officialDatabaseName) . $pageTitle, "index,follow", "Import records into the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
	showPageHeader($HeaderString);

	// (2b) Start <form> and <table> holding the form elements of the main import form:
	echo "\n<form enctype=\"multipart/form-data\" action=\"import_modify.php\" method=\"POST\">"
		. "\n<input type=\"hidden\" name=\"formType\" value=\"import\">"
		. "\n<input type=\"hidden\" name=\"submit\" value=\"Import\">" // provide a default value for the 'submit' form tag. Otherwise, some browsers may not recognize the correct output format when a user hits <enter> within a form field (instead of clicking the "Import" button)
		. "\n<input type=\"hidden\" name=\"showLinks\" value=\"1\">" // embed '$showLinks=1' so that links get displayed on any 'display details' page
		. "\n<input type=\"hidden\" name=\"showSource\" value=\"1\">"; // for particular formats (e.g., CSA or MEDLINE) original source data will be displayed alongside the parsed data for easier comparison

	if (isset($errors['badRecords']))
	{
		if ($errors['badRecords'] == "all") // none of the given records had a recognized format
		{
			if (!empty($errors['skipBadRecords']))
				$skipBadRecordsInput = "<br>" . fieldError("skipBadRecords", $errors);
			else
				$skipBadRecordsInput = "";
		}
		elseif ($errors['badRecords'] == "some") // there were at least some records with recognized format but other records could NOT be recognized
		{
			if (!empty($skipBadRecords))
				$skipBadRecordsCheckBoxIsChecked = " checked"; // mark the 'Skip records with unrecognized data format' checkbox
			else
				$skipBadRecordsCheckBoxIsChecked = "";

			// display the 'Skip records with unrecognized data format' checkbox:
			$skipBadRecordsInput = "<br><input type=\"checkbox\" name=\"skipBadRecords\" value=\"1\"$skipBadRecordsCheckBoxIsChecked title=\"mark this checkbox to omit records with unrecognized data format during import\">&nbsp;&nbsp;" . fieldError("skipBadRecords", $errors);
		}
	}
	else // all records did have a valid data format -> supress the 'Skip records with unrecognized data format' checkbox
	{
		$skipBadRecordsInput = "";
	}

	if (!empty($skipBadRecordsInput))
	{
		if ($formType == "importID")
		{
			$skipBadRecordsInputMain = "";
			$skipBadRecordsInputID = $skipBadRecordsInput;
		}
		else // $formType == "import"
		{
			$skipBadRecordsInputMain = $skipBadRecordsInput;
			$skipBadRecordsInputID = "";
		}
	}
	else
	{
		$skipBadRecordsInputMain = "";
		$skipBadRecordsInputID = "";
	}

	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds the main import form\">"
			. "\n<tr>\n\t<td width=\"94\" valign=\"top\"><b>" . $textEntryFormLabel . ":</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td colspan=\"3\">" . fieldError("sourceText", $errors) . $skipBadRecordsInputMain . "<textarea name=\"sourceText\" rows=\"6\" cols=\"63\" title=\"paste your records here\">$sourceText</textarea></td>"
			. "\n</tr>";

	// the code for the next table row is kept a bit more modular than necessary to allow for easy changes in the future
	if (isset($_SESSION['user_permissions']) AND ereg("(allow_batch_import)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does contain 'allow_batch_import'...
		echo "\n<tr>\n\t<td" . $rowSpan . ">&nbsp;</td>\n\t<td" . $rowSpan . ">&nbsp;</td>";

	if (isset($_SESSION['user_permissions']) AND ereg("(allow_batch_import)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does contain 'allow_batch_import'...
	{
		// display a file upload button:
		$uploadButtonLock = "";
		$uploadTitle = $loc["DescriptionFileImport"];

		echo "\n\t<td width=\"215\" valign=\"top\"" . $rowSpan . ">" . fieldError("uploadFile", $errors) . "<input type=\"file\" name=\"uploadFile\" size=\"17\"$uploadButtonLock title=\"$uploadTitle\"></td>";
	}
//	else
//	{
//		// note that we currently simply hide the upload button if the user doesn't have the 'allow_batch_import' permission (i.e., the two lines below are currently without effect):
//		$uploadButtonLock = " disabled"; // disabling of the upload button doesn't seem to work in all browsers (e.g., it doesn't work in Safari on MacOSX Panther, but does work with Mozilla & Camino) ?:-/
//		$uploadTitle = $loc["NoPermission"] . $loc["NoPermission_ForFileImport"]; // similarily, not all browsers will show title strings for disabled buttons (Safari does, Mozilla & Camino do not)
//
//		echo "\n\t<td width=\"215\"" . $rowSpan . ">&nbsp;</td>";
//	}

	if (isset($_SESSION['user_permissions']) AND ereg("(allow_batch_import)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does contain 'allow_batch_import'...
	{
		if ($importRecordsRadio == "all")
		{
			$importRecordsRadioAllChecked = " checked"; // select the 'All' radio button
			$importRecordsRadioOnlyChecked = "";
		}
		else // $importRecordsRadio == "only"
		{
			$importRecordsRadioAllChecked = "";
			$importRecordsRadioOnlyChecked = " checked"; // select the 'Only' radio button
		}

		echo "\n\t<td width=\"98\" valign=\"top\"" . $rowSpan . ">Import records:</td>"
				. "\n\t<td valign=\"top\"><input type=\"radio\" name=\"importRecordsRadio\" value=\"all\"$importRecordsRadioAllChecked title=\"choose 'All' if you want to import all records at once\">&nbsp;All</td>"
				. "\n</tr>"
				. "\n<tr>"
				. "\n\t<td valign=\"top\">" . fieldError("importRecords", $errors) . "<input type=\"radio\" name=\"importRecordsRadio\" value=\"only\"$importRecordsRadioOnlyChecked title=\"choose 'Only' if you just want to import particular records\">&nbsp;Only:&nbsp;&nbsp;<input type=\"text\" name=\"importRecords\" value=\"$importRecords\" size=\"5\" title=\"enter record number(s): e.g. '1-5 7' imports the first five and the seventh\"></td>";
	}
//	else
//	{
//		echo "\n\t<td colspan=\"2\">&nbsp;</td>";
//	}

	if (isset($_SESSION['user_permissions']) AND ereg("(allow_batch_import)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does contain 'allow_batch_import'...
		echo "\n</tr>";

	echo "\n<tr>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>";

	if (isset($_SESSION['user_permissions']) AND ereg("(allow_import|allow_batch_import)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains either 'allow_import' or 'allow_batch_import'...
	// adjust the title string for the import button
	{
		$importButtonLock = "";
		$importTitleMain = "press this button to import the given source data";
		$importTitleID = "press this button to fetch &amp; import source data for the given IDs";
	}
	else // Note, that disabling the submit button is just a cosmetic thing -- the user can still submit the form by pressing enter or by building the correct URL from scratch!
	{
		$importButtonLock = " disabled";
		$importTitleMain = "not available since you have no permission to import any records";
		$importTitleID = "not available since you have no permission to import any records";
	}

	echo "\n\t<td colspan=\"3\">\n\t\t<input type=\"submit\" name=\"submit\" value=\"Import\"$importButtonLock title=\"$importTitleMain\">\n\t</td>"
			. "\n</tr>"
			. "\n</table>"
			. "\n</form>";

	// (2c) Start <form> and <table> holding the form elements of the "Import IDs" form:
	echo "\n<form action=\"import_modify.php\" method=\"POST\">"
			. "\n<input type=\"hidden\" name=\"formType\" value=\"importID\">"
			. "\n<input type=\"hidden\" name=\"submit\" value=\"Import\">" // provide a default value for the 'submit' form tag. Otherwise, some browsers may not recognize the correct output format when a user hits <enter> within a form field (instead of clicking the "Import" button)
			. "\n<input type=\"hidden\" name=\"showSource\" value=\"1\">"; // in case of the MEDLINE format, original source data will be displayed alongside the parsed data for easier comparison

	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds a form to import records via their ID\">"
			. "\n<tr>\n\t<td width=\"94\" valign=\"top\"><b>Import IDs:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td colspan=\"3\">" . fieldError("sourceIDs", $errors) . $skipBadRecordsInputID . "<input type=\"text\" name=\"sourceIDs\" value=\"$sourceIDs\" size=\"66\" title=\"enter PubMed IDs, arXiv IDs, DOIs or OpenURLs, multiple IDs must be delimited by whitespace\"></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td colspan=\"3\">\n\t\t<input type=\"submit\" name=\"submit\" value=\"Import\"$importButtonLock title=\"$importTitleID\">\n\t</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td align=\"center\" colspan=\"5\">&nbsp;</td>"
			. "\n</tr>";

	// (2d) Display a table row with some help text:
	echo "\n<tr>\n\t<td valign=\"top\"><b>Help:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\" colspan=\"3\">The upper form enables you to import records from "
			. "<a href=\"http://www.endnote.com/\" target=\"top\">Endnote</a> (tagged text or XML), "
			. "<a href=\"http://www.refman.com/\" target=\"top\">Reference Manager</a> (RIS), "
			. "<a href=\"http://www.refworks.com/\" target=\"top\">RefWorks</a>, "
			. "<a href=\"http://en.wikipedia.org/wiki/Bibtex\" target=\"top\">BibTeX</a>, "
			. "<a href=\"http://www.loc.gov/standards/mods/\" target=\"top\">MODS XML</a>, "
			. "<a href=\"http://isiknowledge.com/wos/\" target=\"top\">ISI Web of Science</a>, "
			. "<a href=\"http://www.pubmed.gov/\" target=\"top\">PubMed</a> (MEDLINE or XML), "
			. "<a href=\"" . $importCSArecordsURL . "\" target=\"top\">CSA Illumina</a>, " // '$importCSArecordsURL' is defined in 'ini.inc.php'
			. "<a href=\"http://www.cas.org/SCIFINDER/\" target=\"top\">SciFinder</a> "
			. "and <a href=\"http://www.copac.ac.uk/\" target=\"top\">COPAC</a>."
			. " Please see the <a href=\"http://import.refbase.net/\" target=\"top\">refbase online documentation</a> for more information about the supported formats and any requirements in format structure.</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td colspan=\"3\">The lower form allows you to import records via their ID; supported IDs: <a href=\"http://www.pubmed.gov/\" target=\"top\">PubMed</a> <a href=\"http://en.wikipedia.org/wiki/PMID\" target=\"top\">ID (PMID)</a>, <a href=\"http://arxiv.org/\" target=\"top\">arXiv</a> <a href=\"http://arxiv.org/help/arxiv_identifier\" target=\"top\">ID</a>, <a href=\"http://www.doi.org/\" target=\"top\">DOI</a> and <a href=\"http://en.wikipedia.org/wiki/OpenURL\" target=\"top\">OpenURL</a>. Just enter one or more IDs (delimited by whitespace) and press the <em>Import</em> button. Please note that currently you cannot mix different IDs within the same import action, i.e. specify either PubMed IDs or DOIs, etc.</td>"
			. "\n</tr>"
			. "\n</table>"
			. "\n</form>";

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
