<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./import_csa.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    21-Nov-03, 22:05
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// Import form that offers to import records from the "Cambridge Scientific Abstracts" (CSA)
	// Internet Database Service (<http://www.csa1.co.uk/csa/index.html>). This import form requires
	// the "full record" format offered by the CSA Internet Database Service.


	// Incorporate some include files:
	include 'includes/header.inc.php'; // include header
	include 'includes/footer.inc.php'; // include footer
	include 'includes/include.inc.php'; // include common functions
	include 'initialize/ini.inc.php'; // include common variables
	
	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

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
				$HeaderString = "Import records from Cambridge Scientific Abstracts:"; // Provide the default message
			else
				$HeaderString = "Import a record from Cambridge Scientific Abstracts:"; // Provide the default message
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
		$pageTitle = " -- Import CSA Record"; // adopt page title
		$textEntryFormLabel = "Import CSA Full Record"; // adopt the label for the text entry form
		$rowSpan = ""; // adopt table row span parameter
	}
	else
	{
		$pageTitle = " -- Import CSA Records";
		$textEntryFormLabel = "Import CSA Full Records";
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
		if (isset($formVars['sourceText'])) // '$formVars['sourceText']' may be non-existent in the (unlikely but possible) event that a user calls 'import_csa_modify.php' directly
			$sourceText = $formVars['sourceText'];
		else
			$sourceText = "";

		// check if we need to set the checkbox in front of "Display original source data":
		if (isset($formVars['showSource'])) // the user did mark the 'showSource' checkbox
			$showSource = $formVars['showSource'];
		else
			$showSource = "";

		if (isset($formVars['importRecordsRadio'])) // 'importRecordsRadio' is only set if user has 'batch_import' permission
			$importRecordsRadio = $formVars['importRecordsRadio'];
		else
			$importRecordsRadio = "";

		if (isset($formVars['importRecords'])) // 'importRecords' is only set if user has 'batch_import' permission
			$importRecords = $formVars['importRecords'];
		else
			$importRecords = "";

		// check whether the user marked the checkbox to skip records with unrecognized data format:
		if (isset($formVars['skipBadRecords']))
			$skipBadRecords = $formVars['skipBadRecords'];
		else
			$skipBadRecords = "";
	}
	else // display an empty form (i.e., set all variables to an empty string [""] or their default values, respectively):
	{
		$sourceText = "";
		$showSource = "1";
		$importRecordsRadio = "only";
		$importRecords = "1";
		$skipBadRecords = "";
	}

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (2a) Display header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(encodeHTML($officialDatabaseName) . $pageTitle, "index,follow", "Search the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
	showPageHeader($HeaderString);

	// (2b) Start <form> and <table> holding the form elements:
	echo "\n<form action=\"import_csa_modify.php\" method=\"POST\">";
	echo "\n<input type=\"hidden\" name=\"formType\" value=\"importCSA\">"
		. "\n<input type=\"hidden\" name=\"submit\" value=\"Import\">" // provide a default value for the 'submit' form tag. Otherwise, some browsers may not recognize the correct output format when a user hits <enter> within a form field (instead of clicking the "Import" button)
		. "\n<input type=\"hidden\" name=\"showLinks\" value=\"1\">"; // embed '$showLinks=1' so that links get displayed on any 'display details' page

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

	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds the CSA import form\">"
			. "\n<tr>\n\t<td width=\"58\" valign=\"top\"><b>" . $textEntryFormLabel . ":</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td colspan=\"3\">" . fieldError("sourceText", $errors) . $skipBadRecordsInput . "<textarea name=\"sourceText\" rows=\"6\" cols=\"60\" title=\"paste your CSA records here (requires 'full record' format!)\">$sourceText</textarea></td>"
			. "\n</tr>";

	if (!empty($showSource))
		$showSourceCheckBoxIsChecked = " checked"; // mark the 'Display original source data' checkbox
	else
		$showSourceCheckBoxIsChecked = "";

	echo "\n<tr>\n\t<td valign=\"top\"" . $rowSpan . "><b>Options:</b></td>\n\t<td" . $rowSpan . ">&nbsp;</td>"
		. "\n\t<td width=\"215\" valign=\"top\"" . $rowSpan . "><input type=\"checkbox\" name=\"showSource\" value=\"1\"$showSourceCheckBoxIsChecked title=\"mark this checkbox if original source data shall be displayed alongside the parsed data for easy comparison\">&nbsp;&nbsp;Display original source data</td>";

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
				. "\n\t<td valign=\"top\"><input type=\"radio\" name=\"importRecordsRadio\" value=\"all\"$importRecordsRadioAllChecked title=\"choose 'All' if you want to import all pasted records at once\">&nbsp;All</td>"
				. "\n</tr>"
				. "\n<tr>"
				. "\n\t<td valign=\"top\">" . fieldError("importRecords", $errors) . "<input type=\"radio\" name=\"importRecordsRadio\" value=\"only\"$importRecordsRadioOnlyChecked title=\"choose 'Only' if you just want to import particular records from the pasted source data\">&nbsp;Only:&nbsp;&nbsp;<input type=\"text\" name=\"importRecords\" value=\"$importRecords\" size=\"5\" title=\"enter the record number(s) here: e.g. enter '1-5' to import the first five records; or enter '1 3-5 7' to import records 1, 3, 4, 5 and 7\"></td>";
	}
	else
	{
		echo "\n\t<td colspan=\"2\">&nbsp;</td>";
	}

	echo "\n</tr>";

	echo "\n<tr>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>";

	if (isset($_SESSION['user_permissions']) AND ereg("(allow_import|allow_batch_import)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains either 'allow_import' or 'allow_batch_import'...
	// adjust the title string for the import button
	{
		$importButtonLock = "";
		$importTitle = "press this button to import the given source data";
	}
	else // Note, that disabling the submit button is just a cosmetic thing -- the user can still submit the form by pressing enter or by building the correct URL from scratch!
	{
		$importButtonLock = " disabled";
		$importTitle = "not available since you have no permission to import any records";
	}

	echo "\n\t<td colspan=\"3\">\n\t\t<input type=\"submit\" name=\"submit\" value=\"Import\"$importButtonLock title=\"$importTitle\">\n\t</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td align=\"center\" colspan=\"5\">&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\"><b>Help:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\" colspan=\"3\">This form enables you to import records from the <a href=\"" . $importCSArecordsURL . "\" target=\"top\">" // '$importCSArecordsURL' is defined in 'ini.inc.php'
			. "Cambridge Scientific Abstracts</a> (CSA) Internet Database Service. The form requires the 'full record' format offered by the CSA Internet Database Service. An example of a valid CSA 'full record' format is given below:</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\"><b>Example:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\" colspan=\"3\"><pre>Record 1 of 52

DN: Database Name
    ASFA: Aquatic Sciences and Fisheries Abstracts
TI: Title
    Diet composition influences the growth of the pelagic mysid
    shrimp, Mysis mixta (Mysidacea)
AU: Author
    Lehtiniemi, M; Vitasalo, M; Kuosa, H
AF: Affiliation
    Finnish Institute of Marine Research, P.O. Box 33, FIN-00931
    Helsinki, Finland
SO: Source
    Boreal Environment Research [Boreal Environ. Res.]. Vol. 7, no. 2,
    pp. 121-128. 2002.
IS: ISSN
    1239-6095
AB: Abstract
    We studied the growth, feeding, and elemental composition of Mysis
    mixta from June to September 1997 in the northern Baltic Sea. In
    June the juvenile population had a unimodal size distribution
    (mean length similar to 6 mm), but in July-August, the population
    was divided into two cohorts. A stomach content analysis showed
    that the mysids in the larger and faster growing cohort fed
    significantly more on crustacean zooplankton and pelagic material
    than the smaller one: the mean ratios of zooplankton:phytoplankton
    and pelagic:benthic particles in July-August were respectively
    0.27 and 0.11 for the small cohort, and 0.54 and 0.36 for the
    large cohort. This suggests that food quality and its energy
    content are important in influencing the growth of pelagic mysids
    in the northern Baltic. The C:N ratio of the two cohorts did not
    vary much, which shows that ingestion of food items with varying
    elemental content is not necessarily reflected in the elemental
    composition of consumers.
LA: Language
    English
SL: Summary Language
    English
PY: Publication Year
    2002
PD: Publication Date
    20020000
PT: Publication Type
    Journal Article
DE: Descriptors
    Growth; Feeding; Diets; Chemical composition; Seasonal variations;
    Mysis mixta; ANE, Baltic Sea
TR: ASFA Input Center Number
    CS0309883
CL: Classification
    Q1 01424 Age and growth; O 1070 Ecology/Community Studies
UD: Update
    200305
SF: Subfile
    ASFA 1: Biological Sciences &amp; Living Resources; Oceanic Abstracts
AN: Accession Number
    5449614
F1: Fulltext Info
    1239-6095,7,2,121-128,2002
A1: Alert Info
    20030606
JN: Journal Name
    Boreal Environment Research
JP: Journal Pages
    121-128
JV: Journal Volume
    7
JI: Journal Issue
    2
DT: Document Type
    J
BL: Bibliographic Level
    AS
</pre></td>"
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
