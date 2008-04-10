<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./duplicate_manager.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    27-Jan-07, 21:18
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This script enables you to manually manage duplicate records by entering their database serial numbers
	// into the provided form. The form lets you flag (i.e. identify) an "original" record and its related
	// duplicate entries. The script will then update the 'orig_record' field in table 'refs' accordingly.
	// TODO: I18n


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
	// We need to clear these session variables here, since they would otherwise be still there on a subsequent call of 'duplicate_manager.php'!
	// Note: though we clear the session variables, the current error message (or form variables) is still available to this script via '$errors' (or '$formVars', respectively).
	deleteSessionVariable("errors"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	deleteSessionVariable("formVars");

	// --------------------------------------------------------------------

	// TODO: enable checking for 'allow_flag_duplicates' permission
	// CAUTION: Since there's not a 'allow_flag_duplicates' permission setting (yet), we currently just check whether a user is logged in!
	if (!isset($_SESSION['loginEmail'])) // if a user isn't logged in...

	// In order to flag any records as duplicates, a user must be logged in AND must be allowed to flag duplicates in the database:
//	if (!(isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_flag_duplicates", $_SESSION['user_permissions'])))) // if a user isn't logged in OR if the 'user_permissions' session variable does NOT contain 'allow_flag_duplicates'...
	{
		// return an appropriate error message:
		$HeaderString = returnMsg($loc["NoPermission"] . $loc["NoPermission_ForFlagDups"] . "!", "warning", "strong", "HeaderString"); // function 'returnMsg()' is defined in 'include.inc.php'

		// save the URL of the currently displayed page:
		$referer = $_SERVER['HTTP_REFERER'];

		// Write back session variables:
		saveSessionVariable("referer", $referer); // function 'saveSessionVariable()' is defined in 'include.inc.php'

		header("Location: index.php");
		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// Extract the view type requested by the user (either 'Mobile', 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";


	// Setup some required variables:

	// If there's no stored message available:
	if (!isset($_SESSION['HeaderString']))
	{
		if (empty($errors)) // provide one of the default messages:
		{
			$errors = array(); // re-assign an empty array (in order to prevent 'Undefined variable "errors"...' messages when calling the 'fieldError' function later on)
			$HeaderString = "Flag records as original or duplicate entries:"; // Provide the default message
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
	if (!empty($errors)) // if there were some errors on submit
	{
		// load the form data that were entered by the user:
		$origRecord = $formVars['origRecord'];
		$dupRecords = $formVars['dupRecords'];
	}
	else
	{
		$origRecord = "";
		$dupRecords = "";
	}

	// --------------------------------------------------------------------

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (2a) Display header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(encodeHTML($officialDatabaseName) . " -- " . "Manage Duplicates", "index,follow", "Manage duplicate records in the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
	showPageHeader($HeaderString);

	// (2b) Start <form> and <table> holding the form elements:
	// note: we provide a default value for the 'submit' form tag so that hitting <enter> within a text entry field will act as if the user clicked the 'Flag Duplicates' button
?>

<form action="duplicate_modify.php" method="POST">
<input type="hidden" name="formType" value="flagDuplicates">
<input type="hidden" name="viewType" value="<?php echo $viewType; ?>">
<input type="hidden" name="submit" value="Flag Duplicates">
<table align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds forms that enable you to manage any duplicate database entries">
	<tr>
		<td width="58"><b>Original:</b></td>
		<td width="10">&nbsp;</td>
		<td><?php echo fieldError("origRecord", $errors); ?><input type="text" name="origRecord" value="<?php echo encodeHTML($origRecord); ?>" size="10" title="enter the serial number of the original (master) record"></td>
	</tr>
	<tr>
		<td width="58"><b>Duplicates:</b></td>
		<td width="10">&nbsp;</td>
		<td><?php echo fieldError("dupRecords", $errors); ?><input type="text" name="dupRecords" value="<?php echo encodeHTML($dupRecords); ?>" size="50" title="enter the serial number(s) of all records that are duplicate entries of the above specified record; separate multiple serials with any non-digit characters"></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td><input type="submit" name="submit" value="Flag Duplicates" title="mark the given records as original/duplicate entries"></td>
	</tr>
	<tr>
		<td align="center" colspan="3">&nbsp;</td>
	</tr>
	<tr>
		<td valign="top"><b>Help:</b></td>
		<td>&nbsp;</td>
		<td>This form allows you to manually flag records as original or duplicate entries. If the database contains multiple entries of the same bibliographic resource, enter the serial number of the "original" record (i.e. the master record) in the upper text entry field. Then enter the serial number(s) of its duplicate record entries (delimited by any non-digit characters) in the lower text entry field and press the <em>Flag Duplicates</em> button.</td>
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
