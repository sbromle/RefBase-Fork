<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./advanced_search.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    29-Jul-02, 16:39
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// Search form providing access to all fields of the database.
	// It offers some output options (like how many records to display per page)
	// and let's you specify the output sort order (up to three levels deep).


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

	if (!isset($_SESSION['loginEmail'])) // if NO user is logged in
		$loginUserID = ""; // set '$loginUserID' to "" so that 'selectDistinct()' function can be executed without problems

	// --------------------------------------------------------------------

	// (1) Open the database connection and use the literature database:
	connectToMySQLDatabase(); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// If there's no stored message available:
	if (!isset($_SESSION['HeaderString']))
		$HeaderString = $loc["SearchAll"].":"; // Provide the default message
	else
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

	// Get the default number of records per page preferred by the current user:
	$showRows = $_SESSION['userRecordsPerPage'];

	// Get the user's preference for displaying auto-completions:
	$showAutoCompletions = $_SESSION['userAutoCompletions'];

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (2a) Display header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(encodeHTML($officialDatabaseName) . " -- " . $loc["AdvancedSearch"], "index,follow", "Search the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
	showPageHeader($HeaderString);

	// Define variables holding common drop-down elements, i.e. build properly formatted <option> tag elements:
	$dropDownConditionals1Array = array("contains"         => $loc["contains"],
	                                    "does not contain" => $loc["contains not"],
	                                    "is equal to"      => $loc["equal to"],
	                                    "is not equal to"  => $loc["equal to not"],
	                                    "starts with"      => $loc["starts with"],
	                                    "ends with"        => $loc["ends with"]);

	$dropDownItems1 = buildSelectMenuOptions($dropDownConditionals1Array, "", "\t\t\t", true); // function 'buildSelectMenuOptions()' is defined in 'include.inc.php'


	$dropDownConditionals2Array = array("is greater than" => $loc["is greater than"],
	                                    "is less than"    => $loc["is less than"],
	                                    "is within range" => $loc["is within range"],
	                                    "is within list"  => $loc["is within list"]);

	$dropDownItems2 = buildSelectMenuOptions($dropDownConditionals2Array, "", "\t\t\t", true); // function 'buildSelectMenuOptions()' is defined in 'include.inc.php'


	// TODO: if possible, we should use function 'mapFieldNames()' here (see also below)
	$dropDownFieldNames1Array = array("author"                => $loc["DropDownFieldName_Author"],
	                                  "address"               => $loc["DropDownFieldName_Address"],
	                                  "corporate_author"      => $loc["DropDownFieldName_CorporateAuthor"],
	                                  "thesis"                => $loc["DropDownFieldName_Thesis"],
	                                  "", // empty array elements function as spacers between groups of drop-down menu items
	                                  "title"                 => $loc["DropDownFieldName_Title"],
	                                  "orig_title"            => $loc["DropDownFieldName_OrigTitle"],
	                                  "",
	                                  "year"                  => $loc["DropDownFieldName_Year"],
	                                  "publication"           => $loc["DropDownFieldName_Publication"],
	                                  "abbrev_journal"        => $loc["DropDownFieldName_AbbrevJournal"],
	                                  "editor"                => $loc["DropDownFieldName_Editor"],
	                                  "",
	                                  "volume_numeric"        => $loc["DropDownFieldName_Volume"], // 'volume_numeric' is used instead of 'volume' in the sort dropdown menus
	                                  "issue"                 => $loc["DropDownFieldName_Issue"],
	                                  "pages"                 => $loc["DropDownFieldName_Pages"],
	                                  "",
	                                  "series_title"          => $loc["DropDownFieldName_SeriesTitle"],
	                                  "abbrev_series_title"   => $loc["DropDownFieldName_AbbrevSeriesTitle"],
	                                  "series_editor"         => $loc["DropDownFieldName_SeriesEditor"],
	                                  "series_volume_numeric" => $loc["DropDownFieldName_SeriesVolume"], // 'series_volume_numeric' is used instead of 'series_volume' in the sort dropdown menus
	                                  "series_issue"          => $loc["DropDownFieldName_SeriesIssue"],
	                                  "",
	                                  "publisher"             => $loc["DropDownFieldName_Publisher"],
	                                  "place"                 => $loc["DropDownFieldName_Place"],
	                                  "",
	                                  "edition"               => $loc["DropDownFieldName_Edition"],
	                                  "medium"                => $loc["DropDownFieldName_Medium"],
	                                  "issn"                  => $loc["DropDownFieldName_Issn"],
	                                  "isbn"                  => $loc["DropDownFieldName_Isbn"],
	                                  "",
	                                  "language"              => $loc["DropDownFieldName_Language"],
	                                  "summary_language"      => $loc["DropDownFieldName_SummaryLanguage"],
	                                  "",
	                                  "keywords"              => $loc["DropDownFieldName_Keywords"],
	                                  "abstract"              => $loc["DropDownFieldName_Abstract"],
	                                  "",
	                                  "area"                  => $loc["DropDownFieldName_Area"],
	                                  "expedition"            => $loc["DropDownFieldName_Expedition"],
	                                  "conference"            => $loc["DropDownFieldName_Conference"],
	                                  "",
	                                  "doi"                   => $loc["DropDownFieldName_Doi"],
	                                  "url"                   => $loc["DropDownFieldName_Url"]);

	if (isset($_SESSION['loginEmail'])) // we only include the 'file' field if the user is logged in
		$dropDownFieldNames1Array["file"] = $loc["DropDownFieldName_File"];

	$dropDownFieldNames1Array[] = "";
	$dropDownFieldNames1Array["notes"] = $loc["DropDownFieldName_Notes"];

	if (isset($_SESSION['loginEmail'])) // we only include the 'location' field if the user is logged in
		$dropDownFieldNames1Array["location"] = $loc["DropDownFieldName_Location"];

	$dropDownFieldNames2Array = array("call_number"  => $loc["DropDownFieldName_CallNumber"],
	                                  "",
	                                  "serial"       => $loc["DropDownFieldName_Serial"],
	                                  "type"         => $loc["DropDownFieldName_Type"],
	                                  "approved"     => $loc["DropDownFieldName_Approved"],
	                                  "",
	                                  "created_date" => $loc["DropDownFieldName_CreatedDate"],
	                                  "created_time" => $loc["DropDownFieldName_CreatedTime"]);

	if (isset($_SESSION['loginEmail'])) // we only include the 'created_by' field if the user is logged in
		$dropDownFieldNames2Array["created_by"] = $loc["DropDownFieldName_CreatedBy"];

	$dropDownFieldNames2Array[] = "";
	$dropDownFieldNames2Array["modified_date"] = $loc["DropDownFieldName_ModifiedDate"];
	$dropDownFieldNames2Array["modified_time"] = $loc["DropDownFieldName_ModifiedTime"];

	if (isset($_SESSION['loginEmail'])) // we only include the 'modified_by' field if the user is logged in
		$dropDownFieldNames2Array["modified_by"] = $loc["DropDownFieldName_ModifiedBy"];

	$dropDownItems3 = buildSelectMenuOptions(array_merge($dropDownFieldNames1Array,$dropDownFieldNames2Array), "", "\t\t\t", true); // function 'buildSelectMenuOptions()' is defined in 'include.inc.php'

	// (2b) Start <form> and <table> holding the form elements:

	// NOTE: Internet Explorer (at least XP IE v7.0.5730.11) chokes on the length of the GET request,
	//       so (unless we apply some browser agent sniffing) the request method should remain POST 
?>

<form action="search.php" method="POST" name="queryForm">
<input type="hidden" name="formType" value="advancedSearch">
<input type="hidden" name="showQuery" value="0">
<table align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds the search form">
<tr>
	<th align="left"><?php echo $loc["Show"]; ?></th>
	<th align="left"><?php echo $loc["Field"]; ?></th>
	<th align="left">&nbsp;</th>
	<th align="left"><?php echo $loc["That..."]; ?></th>
	<th align="left"><?php echo $loc["Searchstring"]; ?></th>
</tr>
<tr>
	<td width="20" valign="middle"><input type="checkbox" name="showAuthor" value="1" checked></td>
	<td width="40"><b><?php echo $loc["Author"]; ?>:</b></td>
	<td width="10">&nbsp;</td>
	<td width="125">
		<select name="authorSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="authorName" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showAddress" value="1"></td>
	<td><b><?php echo $loc["Address"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="addressSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="addressName" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showCorporateAuthor" value="1"></td>
	<td><b><?php echo $loc["CorporateAuthor"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="corporateAuthorSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="corporateAuthorName" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showThesis" value="1"></td>
	<td><b><?php echo $loc["Thesis"]; ?>:</b></td>
	<td align="center"><input type="radio" name="thesisRadio" value="1" checked></td>
	<td>
		<select name="thesisSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><?php

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. The field name of the table's primary key
	// 4. Table name of the user data table
	// 5. The field name within the user data table that corresponds to the field in 3.
	// 6. The field name of the user ID field within the user data table
	// 7. The user ID of the currently logged in user (which must be provided as a session variable)
	// 8. Attribute that contains values
	// 9. <SELECT> element name
	// 10. An additional non-database value (display string)
	// 11. String that gets submitted instead of the display string given in 10.
	// 12. Optional <OPTION SELECTED>
	// 13. Restrict query to field... (keep empty if no restriction wanted)
	// 14. ...where field contents are...
	// 15. Split field contents into substrings? (yes = true, no = false)
	// 16. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	echo selectDistinct($connection,
	                    $tableRefs,
	                    "serial",
	                    $tableUserData,
	                    "record_id",
	                    "user_id",
	                    $loginUserID,
	                    "thesis",
	                    "thesisName",
	                    $loc["All"],
	                    "All",
	                    $loc["All"],
	                    "",
	                    "",
	                    false,
	                    "");
?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="right"><?php echo $loc["or"]; ?>:</td>
	<td align="center"><input type="radio" name="thesisRadio" value="0"></td>
	<td>
		<select name="thesisSelector2"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="thesisName2" size="42"></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showTitle" value="1" checked></td>
	<td><b><?php echo $loc["Title"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="titleSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="titleName" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showOrigTitle" value="1"></td>
	<td><b><?php echo $loc["TitleOriginal"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="origTitleSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="origTitleName" size="42"></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showYear" value="1" checked></td>
	<td><b><?php echo $loc["Year"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="yearSelector"><?php echo $dropDownItems1 . $dropDownItems2; ?>

		</select>
	</td>
	<td><input type="text" name="yearNo" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showPublication" value="1" checked></td>
	<td><b><?php echo $loc["Publication"]; ?>:</b></td>
	<td align="center"><input type="radio" name="publicationRadio" value="1" checked></td>
	<td>
		<select name="publicationSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><?php

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. The field name of the table's primary key
	// 4. Table name of the user data table
	// 5. The field name within the user data table that corresponds to the field in 3.
	// 6. The field name of the user ID field within the user data table
	// 7. The user ID of the currently logged in user (which must be provided as a session variable)
	// 8. Attribute that contains values
	// 9. <SELECT> element name
	// 10. An additional non-database value (display string)
	// 11. String that gets submitted instead of the display string given in 10.
	// 12. Optional <OPTION SELECTED>
	// 13. Restrict query to field... (keep empty if no restriction wanted)
	// 14. ...where field contents are...
	// 15. Split field contents into substrings? (yes = true, no = false)
	// 16. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	echo selectDistinct($connection,
	                    $tableRefs,
	                    "serial",
	                    $tableUserData,
	                    "record_id",
	                    "user_id",
	                    $loginUserID,
	                    "publication",
	                    "publicationName",
	                    $loc["All"],
	                    "All",
	                    $loc["All"],
	                    "type",
	                    "\"journal\"",
	                    false,
	                    "");
?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="right"><?php echo $loc["or"]; ?>:</td>
	<td align="center"><input type="radio" name="publicationRadio" value="0"></td>
	<td>
		<select name="publicationSelector2"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="publicationName2" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showAbbrevJournal" value="1"></td>
	<td><b><?php echo $loc["JournalAbbr"]; ?>:</b></td>
	<td align="center"><input type="radio" name="abbrevJournalRadio" value="1" checked></td>
	<td>
		<select name="abbrevJournalSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><?php

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. The field name of the table's primary key
	// 4. Table name of the user data table
	// 5. The field name within the user data table that corresponds to the field in 3.
	// 6. The field name of the user ID field within the user data table
	// 7. The user ID of the currently logged in user (which must be provided as a session variable)
	// 8. Attribute that contains values
	// 9. <SELECT> element name
	// 10. An additional non-database value (display string)
	// 11. String that gets submitted instead of the display string given in 10.
	// 12. Optional <OPTION SELECTED>
	// 13. Restrict query to field... (keep empty if no restriction wanted)
	// 14. ...where field contents are...
	// 15. Split field contents into substrings? (yes = true, no = false)
	// 16. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	echo selectDistinct($connection,
	                    $tableRefs,
	                    "serial",
	                    $tableUserData,
	                    "record_id",
	                    "user_id",
	                    $loginUserID,
	                    "abbrev_journal",
	                    "abbrevJournalName",
	                    $loc["All"],
	                    "All",
	                    $loc["All"],
	                    "type",
	                    "\"journal\"",
	                    false,
	                    "");
?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="right"><?php echo $loc["or"]; ?>:</td>
	<td align="center"><input type="radio" name="abbrevJournalRadio" value="0"></td>
	<td>
		<select name="abbrevJournalSelector2"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="abbrevJournalName2" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showEditor" value="1"></td>
	<td><b><?php echo $loc["Editor"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="editorSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="editorName" size="42"></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showVolume" value="1" checked></td>
	<td><b><?php echo $loc["Volume"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="volumeSelector"><?php echo $dropDownItems1 . $dropDownItems2; ?>

		</select>
	</td>
	<td><input type="text" name="volumeNo" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showIssue" value="1"></td>
	<td><b><?php echo $loc["Issue"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="issueSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="issueNo" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showPages" value="1" checked></td>
	<td><b><?php echo $loc["Pages"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="pagesSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="pagesNo" size="42"></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showSeriesTitle" value="1"></td>
	<td><b><?php echo $loc["TitleSeries"]; ?>:</b></td>
	<td align="center"><input type="radio" name="seriesTitleRadio" value="1" checked></td>
	<td>
		<select name="seriesTitleSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><?php

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. The field name of the table's primary key
	// 4. Table name of the user data table
	// 5. The field name within the user data table that corresponds to the field in 3.
	// 6. The field name of the user ID field within the user data table
	// 7. The user ID of the currently logged in user (which must be provided as a session variable)
	// 8. Attribute that contains values
	// 9. <SELECT> element name
	// 10. An additional non-database value (display string)
	// 11. String that gets submitted instead of the display string given in 10.
	// 12. Optional <OPTION SELECTED>
	// 13. Restrict query to field... (keep empty if no restriction wanted)
	// 14. ...where field contents are...
	// 15. Split field contents into substrings? (yes = true, no = false)
	// 16. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	echo selectDistinct($connection,
	                    $tableRefs,
	                    "serial",
	                    $tableUserData,
	                    "record_id",
	                    "user_id",
	                    $loginUserID,
	                    "series_title",
	                    "seriesTitleName",
	                    $loc["All"],
	                    "All",
	                    $loc["All"],
	                    "",
	                    "",
	                    false,
	                    "");
?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="right"><?php echo $loc["or"]; ?>:</td>
	<td align="center"><input type="radio" name="seriesTitleRadio" value="0"></td>
	<td>
		<select name="seriesTitleSelector2"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="seriesTitleName2" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showAbbrevSeriesTitle" value="1"></td>
	<td><b><?php echo $loc["TitleSeriesAbbr"]; ?>:</b></td>
	<td align="center"><input type="radio" name="abbrevSeriesTitleRadio" value="1" checked></td>
	<td>
		<select name="abbrevSeriesTitleSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><?php

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. The field name of the table's primary key
	// 4. Table name of the user data table
	// 5. The field name within the user data table that corresponds to the field in 3.
	// 6. The field name of the user ID field within the user data table
	// 7. The user ID of the currently logged in user (which must be provided as a session variable)
	// 8. Attribute that contains values
	// 9. <SELECT> element name
	// 10. An additional non-database value (display string)
	// 11. String that gets submitted instead of the display string given in 10.
	// 12. Optional <OPTION SELECTED>
	// 13. Restrict query to field... (keep empty if no restriction wanted)
	// 14. ...where field contents are...
	// 15. Split field contents into substrings? (yes = true, no = false)
	// 16. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	echo selectDistinct($connection,
	                    $tableRefs,
	                    "serial",
	                    $tableUserData,
	                    "record_id",
	                    "user_id",
	                    $loginUserID,
	                    "abbrev_series_title",
	                    "abbrevSeriesTitleName",
	                    $loc["All"],
	                    "All",
	                    $loc["All"],
	                    "",
	                    "",
	                    false,
	                    "");
?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="right"><?php echo $loc["or"]; ?>:</td>
	<td align="center"><input type="radio" name="abbrevSeriesTitleRadio" value="0"></td>
	<td>
		<select name="abbrevSeriesTitleSelector2"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="abbrevSeriesTitleName2" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showSeriesEditor" value="1"></td>
	<td><b><?php echo $loc["SeriesEditor"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="seriesEditorSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="seriesEditorName" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showSeriesVolume" value="1"></td>
	<td><b><?php echo $loc["SeriesVolume"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="seriesVolumeSelector"><?php echo $dropDownItems1 . $dropDownItems2; ?>

		</select>
	</td>
	<td><input type="text" name="seriesVolumeNo" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showSeriesIssue" value="1"></td>
	<td><b><?php echo $loc["SeriesIssue"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="seriesIssueSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="seriesIssueNo" size="42"></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showPublisher" value="1"></td>
	<td><b><?php echo $loc["Publisher"]; ?>:</b></td>
	<td align="center"><input type="radio" name="publisherRadio" value="1" checked></td>
	<td>
		<select name="publisherSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><?php

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. The field name of the table's primary key
	// 4. Table name of the user data table
	// 5. The field name within the user data table that corresponds to the field in 3.
	// 6. The field name of the user ID field within the user data table
	// 7. The user ID of the currently logged in user (which must be provided as a session variable)
	// 8. Attribute that contains values
	// 9. <SELECT> element name
	// 10. An additional non-database value (display string)
	// 11. String that gets submitted instead of the display string given in 10.
	// 12. Optional <OPTION SELECTED>
	// 13. Restrict query to field... (keep empty if no restriction wanted)
	// 14. ...where field contents are...
	// 15. Split field contents into substrings? (yes = true, no = false)
	// 16. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	echo selectDistinct($connection,
	                    $tableRefs,
	                    "serial",
	                    $tableUserData,
	                    "record_id",
	                    "user_id",
	                    $loginUserID,
	                    "publisher",
	                    "publisherName",
	                    $loc["All"],
	                    "All",
	                    $loc["All"],
	                    "",
	                    "",
	                    false,
	                    "");
?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="right"><?php echo $loc["or"]; ?>:</td>
	<td align="center"><input type="radio" name="publisherRadio" value="0"></td>
	<td>
		<select name="publisherSelector2"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="publisherName2" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showPlace" value="1"></td>
	<td><b><?php echo $loc["PublisherPlace"]; ?>:</b></td>
	<td align="center"><input type="radio" name="placeRadio" value="1" checked></td>
	<td>
		<select name="placeSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><?php

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. The field name of the table's primary key
	// 4. Table name of the user data table
	// 5. The field name within the user data table that corresponds to the field in 3.
	// 6. The field name of the user ID field within the user data table
	// 7. The user ID of the currently logged in user (which must be provided as a session variable)
	// 8. Attribute that contains values
	// 9. <SELECT> element name
	// 10. An additional non-database value (display string)
	// 11. String that gets submitted instead of the display string given in 10.
	// 12. Optional <OPTION SELECTED>
	// 13. Restrict query to field... (keep empty if no restriction wanted)
	// 14. ...where field contents are...
	// 15. Split field contents into substrings? (yes = true, no = false)
	// 16. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	echo selectDistinct($connection,
	                    $tableRefs,
	                    "serial",
	                    $tableUserData,
	                    "record_id",
	                    "user_id",
	                    $loginUserID,
	                    "place",
	                    "placeName",
	                    $loc["All"],
	                    "All",
	                    $loc["All"],
	                    "",
	                    "",
	                    true,
	                    " *[,;()] *");
?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="right"><?php echo $loc["or"]; ?>:</td>
	<td align="center"><input type="radio" name="placeRadio" value="0"></td>
	<td>
		<select name="placeSelector2"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="placeName2" size="42"></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showEdition" value="1"></td>
	<td><b><?php echo $loc["Edition"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="editionSelector"><?php echo $dropDownItems1 . $dropDownItems2; ?>

		</select>
	</td>
	<td><input type="text" name="editionNo" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showMedium" value="1"></td>
	<td><b><?php echo $loc["Medium"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="mediumSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="mediumName" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showISSN" value="1"></td>
	<td><b><?php echo $loc["ISSN"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="issnSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="issnName" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showISBN" value="1"></td>
	<td><b><?php echo $loc["ISBN"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="isbnSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="isbnName" size="42"></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showLanguage" value="1"></td>
	<td><b><?php echo $loc["Language"]; ?>:</b></td>
	<td align="center"><input type="radio" name="languageRadio" value="1" checked></td>
	<td>
		<select name="languageSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><?php

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. The field name of the table's primary key
	// 4. Table name of the user data table
	// 5. The field name within the user data table that corresponds to the field in 3.
	// 6. The field name of the user ID field within the user data table
	// 7. The user ID of the currently logged in user (which must be provided as a session variable)
	// 8. Attribute that contains values
	// 9. <SELECT> element name
	// 10. An additional non-database value (display string)
	// 11. String that gets submitted instead of the display string given in 10.
	// 12. Optional <OPTION SELECTED>
	// 13. Restrict query to field... (keep empty if no restriction wanted)
	// 14. ...where field contents are...
	// 15. Split field contents into substrings? (yes = true, no = false)
	// 16. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	echo selectDistinct($connection,
	                    $tableRefs,
	                    "serial",
	                    $tableUserData,
	                    "record_id",
	                    "user_id",
	                    $loginUserID,
	                    "language",
	                    "languageName",
	                    $loc["All"],
	                    "All",
	                    $loc["All"],
	                    "",
	                    "",
	                    true,
	                    " *[,;()] *");
?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="right"><?php echo $loc["or"]; ?>:</td>
	<td align="center"><input type="radio" name="languageRadio" value="0"></td>
	<td>
		<select name="languageSelector2"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="languageName2" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showSummaryLanguage" value="1"></td>
	<td><b><?php echo $loc["LanguageSummary"]; ?>:</b></td>
	<td align="center"><input type="radio" name="summaryLanguageRadio" value="1" checked></td>
	<td>
		<select name="summaryLanguageSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><?php

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. The field name of the table's primary key
	// 4. Table name of the user data table
	// 5. The field name within the user data table that corresponds to the field in 3.
	// 6. The field name of the user ID field within the user data table
	// 7. The user ID of the currently logged in user (which must be provided as a session variable)
	// 8. Attribute that contains values
	// 9. <SELECT> element name
	// 10. An additional non-database value (display string)
	// 11. String that gets submitted instead of the display string given in 10.
	// 12. Optional <OPTION SELECTED>
	// 13. Restrict query to field... (keep empty if no restriction wanted)
	// 14. ...where field contents are...
	// 15. Split field contents into substrings? (yes = true, no = false)
	// 16. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	echo selectDistinct($connection,
	                    $tableRefs,
	                    "serial",
	                    $tableUserData,
	                    "record_id",
	                    "user_id",
	                    $loginUserID,
	                    "summary_language",
	                    "summaryLanguageName",
	                    $loc["All"],
	                    "All",
	                    $loc["All"],
	                    "",
	                    "",
	                    true,
	                    " *[,;()] *");
?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="right"><?php echo $loc["or"]; ?>:</td>
	<td align="center"><input type="radio" name="summaryLanguageRadio" value="0"></td>
	<td>
		<select name="summaryLanguageSelector2"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="summaryLanguageName2" size="42"></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showKeywords" value="1"></td>
	<td><b><?php echo $loc["Keywords"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="keywordsSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="keywordsName" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showAbstract" value="1"></td>
	<td><b><?php echo $loc["Abstract"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="abstractSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="abstractName" size="42"></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showArea" value="1"></td>
	<td><b><?php echo $loc["Area"]; ?>:</b></td>
	<td align="center"><input type="radio" name="areaRadio" value="1" checked></td>
	<td>
		<select name="areaSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><?php

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. The field name of the table's primary key
	// 4. Table name of the user data table
	// 5. The field name within the user data table that corresponds to the field in 3.
	// 6. The field name of the user ID field within the user data table
	// 7. The user ID of the currently logged in user (which must be provided as a session variable)
	// 8. Attribute that contains values
	// 9. <SELECT> element name
	// 10. An additional non-database value (display string)
	// 11. String that gets submitted instead of the display string given in 10.
	// 12. Optional <OPTION SELECTED>
	// 13. Restrict query to field... (keep empty if no restriction wanted)
	// 14. ...where field contents are...
	// 15. Split field contents into substrings? (yes = true, no = false)
	// 16. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	echo selectDistinct($connection,
	                    $tableRefs,
	                    "serial",
	                    $tableUserData,
	                    "record_id",
	                    "user_id",
	                    $loginUserID,
	                    "area",
	                    "areaName",
	                    $loc["All"],
	                    "All",
	                    $loc["All"],
	                    "",
	                    "",
	                    true,
	                    " *[,;()] *");
?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="right"><?php echo $loc["or"]; ?>:</td>
	<td align="center"><input type="radio" name="areaRadio" value="0"></td>
	<td>
		<select name="areaSelector2"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="areaName2" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showExpedition" value="1"></td>
	<td><b><?php echo $loc["Expedition"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="expeditionSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="expeditionName" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showConference" value="1"></td>
	<td><b><?php echo $loc["Conference"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="conferenceSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="conferenceName" size="42"></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showDOI" value="1"></td>
	<td><b><?php echo $loc["DOI"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="doiSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="doiName" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showURL" value="1"></td>
	<td><b><?php echo $loc["URL"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="urlSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="urlName" size="42"></td>
</tr><?php

	// show a text entry form to search for any files if one of the following conditions is met:
	// - the variable '$fileVisibility' (defined in 'ini.inc.php') is set to 'everyone'
	// - the variable '$fileVisibility' is set to 'login' AND the user is logged in
	// - the variable '$fileVisibility' is set to 'user-specific' AND the 'user_permissions' session variable contains 'allow_download'
	if ($fileVisibility == "everyone" OR ($fileVisibility == "login" AND isset($_SESSION['loginEmail'])) OR ($fileVisibility == "user-specific" AND (isset($_SESSION['user_permissions']) AND ereg("allow_download", $_SESSION['user_permissions']))))
	{
?>

<tr>
	<td valign="middle"><input type="checkbox" name="showFile" value="1"></td>
	<td><b><?php echo $loc["File"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="fileSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="fileName" size="42"></td>
</tr><?php
	}
?>

<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showNotes" value="1"></td>
	<td><b><?php echo $loc["Notes"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="notesSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="notesName" size="42"></td>
</tr><?php

	// we only show a text entry form for the 'location' field if the user is logged in:
	if (isset($_SESSION['loginEmail']))
	{
?>

<tr>
	<td valign="middle"><input type="checkbox" name="showLocation" value="1"></td>
	<td><b><?php echo $loc["Location"]; ?>:</b></td>
	<td align="center"><input type="radio" name="locationRadio" value="1" checked></td>
	<td>
		<select name="locationSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><?php

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. The field name of the table's primary key
	// 4. Table name of the user data table
	// 5. The field name within the user data table that corresponds to the field in 3.
	// 6. The field name of the user ID field within the user data table
	// 7. The user ID of the currently logged in user (which must be provided as a session variable)
	// 8. Attribute that contains values
	// 9. <SELECT> element name
	// 10. An additional non-database value (display string)
	// 11. String that gets submitted instead of the display string given in 10.
	// 12. Optional <OPTION SELECTED>
	// 13. Restrict query to field... (keep empty if no restriction wanted)
	// 14. ...where field contents are...
	// 15. Split field contents into substrings? (yes = true, no = false)
	// 16. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	echo selectDistinct($connection,
	                    $tableRefs,
	                    "serial",
	                    $tableUserData,
	                    "record_id",
	                    "user_id",
	                    $loginUserID,
	                    "location",
	                    "locationName",
	                    $loc["All"],
	                    "All",
	                    $loc["All"],
	                    "",
	                    "",
	                    true,
	                    " *[,;()] *");
?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="right"><?php echo $loc["or"]; ?>:</td>
	<td align="center"><input type="radio" name="locationRadio" value="0"></td>
	<td>
		<select name="locationSelector2"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="locationName2" size="42"></td>
</tr><?php
	}
?>

<tr>
	<td valign="middle"><input type="checkbox" name="showCallNumber" value="1"></td>
	<td><b><?php echo $loc["CallNumber"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="callNumberSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="callNumberName" size="42"></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showSerial" value="1"></td>
	<td><b><?php echo $loc["Serial"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="serialSelector"><?php echo $dropDownItems1 . $dropDownItems2; ?>

		</select>
	</td>
	<td><input type="text" name="serialNo" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showType" value="1"></td>
	<td><b><?php echo $loc["Type"]; ?>:</b></td>
	<td align="center"><input type="radio" name="typeRadio" value="1" checked></td>
	<td>
		<select name="typeSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><?php

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. The field name of the table's primary key
	// 4. Table name of the user data table
	// 5. The field name within the user data table that corresponds to the field in 3.
	// 6. The field name of the user ID field within the user data table
	// 7. The user ID of the currently logged in user (which must be provided as a session variable)
	// 8. Attribute that contains values
	// 9. <SELECT> element name
	// 10. An additional non-database value (display string)
	// 11. String that gets submitted instead of the display string given in 10.
	// 12. Optional <OPTION SELECTED>
	// 13. Restrict query to field... (keep empty if no restriction wanted)
	// 14. ...where field contents are...
	// 15. Split field contents into substrings? (yes = true, no = false)
	// 16. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	echo selectDistinct($connection,
	                    $tableRefs,
	                    "serial",
	                    $tableUserData,
	                    "record_id",
	                    "user_id",
	                    $loginUserID,
	                    "type",
	                    "typeName",
	                    $loc["All"],
	                    "All",
	                    $loc["All"],
	                    "",
	                    "",
	                    false,
	                    "");
?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="right"><?php echo $loc["or"]; ?>:</td>
	<td align="center"><input type="radio" name="typeRadio" value="0"></td>
	<td>
		<select name="typeSelector2"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="typeName2" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showApproved" value="1"></td>
	<td><b><?php echo $loc["Approved"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td><input type="radio" name="approvedRadio" value="1">&nbsp;&nbsp;<?php echo $loc["Yes"]; ?>&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="approvedRadio" value="0">&nbsp;&nbsp;<?php echo $loc["No"]; ?></td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showCreatedDate" value="1"></td>
	<td><b><?php echo $loc["CreationDate"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="createdDateSelector"><?php echo $dropDownItems1 . $dropDownItems2; ?>

		</select>
	</td>
	<td><input type="text" name="createdDateNo" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showCreatedTime" value="1"></td>
	<td><b><?php echo $loc["CreationTime"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="createdTimeSelector"><?php echo $dropDownItems1 . $dropDownItems2; ?>

		</select>
	</td>
	<td><input type="text" name="createdTimeNo" size="42"></td>
</tr><?php

	// we only show a text entry form for the 'created_by' field if the user is logged in:
	if (isset($_SESSION['loginEmail']))
	{
?>

<tr>
	<td valign="middle"><input type="checkbox" name="showCreatedBy" value="1"></td>
	<td><b><?php echo $loc["Creator"]; ?>:</b></td>
	<td align="center"><input type="radio" name="createdByRadio" value="1" checked></td>
	<td>
		<select name="createdBySelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><?php

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. The field name of the table's primary key
	// 4. Table name of the user data table
	// 5. The field name within the user data table that corresponds to the field in 3.
	// 6. The field name of the user ID field within the user data table
	// 7. The user ID of the currently logged in user (which must be provided as a session variable)
	// 8. Attribute that contains values
	// 9. <SELECT> element name
	// 10. An additional non-database value (display string)
	// 11. String that gets submitted instead of the display string given in 10.
	// 12. Optional <OPTION SELECTED>
	// 13. Restrict query to field... (keep empty if no restriction wanted)
	// 14. ...where field contents are...
	// 15. Split field contents into substrings? (yes = true, no = false)
	// 16. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	echo selectDistinct($connection,
	                    $tableRefs,
	                    "serial",
	                    $tableUserData,
	                    "record_id",
	                    "user_id",
	                    $loginUserID,
	                    "created_by",
	                    "createdByName",
	                    $loc["All"],
	                    "All",
	                    $loc["All"],
	                    "",
	                    "",
	                    true,
	                    " *[,;()] *");
?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="right"><?php echo $loc["or"]; ?>:</td>
	<td align="center"><input type="radio" name="createdByRadio" value="0"></td>
	<td>
		<select name="createdBySelector2"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="createdByName2" size="42"></td>
</tr><?php
	}
?>

<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showModifiedDate" value="1"></td>
	<td><b><?php echo $loc["ModifiedDate"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="modifiedDateSelector"><?php echo $dropDownItems1 . $dropDownItems2; ?>

		</select>
	</td>
	<td><input type="text" name="modifiedDateNo" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showModifiedTime" value="1"></td>
	<td><b><?php echo $loc["ModifiedTime"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="modifiedTimeSelector"><?php echo $dropDownItems1 . $dropDownItems2; ?>

		</select>
	</td>
	<td><input type="text" name="modifiedTimeNo" size="42"></td>
</tr><?php

	// we only show a text entry form for the 'modified_by' field if the user is logged in:
	if (isset($_SESSION['loginEmail']))
	{
?>

<tr>
	<td valign="middle"><input type="checkbox" name="showModifiedBy" value="1"></td>
	<td><b><?php echo $loc["Modifier"]; ?>:</b></td>
	<td align="center"><input type="radio" name="modifiedByRadio" value="1" checked></td>
	<td>
		<select name="modifiedBySelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><?php

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. The field name of the table's primary key
	// 4. Table name of the user data table
	// 5. The field name within the user data table that corresponds to the field in 3.
	// 6. The field name of the user ID field within the user data table
	// 7. The user ID of the currently logged in user (which must be provided as a session variable)
	// 8. Attribute that contains values
	// 9. <SELECT> element name
	// 10. An additional non-database value (display string)
	// 11. String that gets submitted instead of the display string given in 10.
	// 12. Optional <OPTION SELECTED>
	// 13. Restrict query to field... (keep empty if no restriction wanted)
	// 14. ...where field contents are...
	// 15. Split field contents into substrings? (yes = true, no = false)
	// 16. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	echo selectDistinct($connection,
	                    $tableRefs,
	                    "serial",
	                    $tableUserData,
	                    "record_id",
	                    "user_id",
	                    $loginUserID,
	                    "modified_by",
	                    "modifiedByName",
	                    $loc["All"],
	                    "All",
	                    $loc["All"],
	                    "",
	                    "",
	                    true,
	                    " *[,;()] *");
?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="right"><?php echo $loc["or"]; ?>:</td>
	<td align="center"><input type="radio" name="modifiedByRadio" value="0"></td>
	<td>
		<select name="modifiedBySelector2"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="modifiedByName2" size="42"></td>
</tr><?php
	}

	// --------------------------------------------------------------------

	if (isset($_SESSION['loginEmail'])) // if a user is logged in, display user specific fields:
	{
?>

<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showMarked" value="1"></td>
	<td><b><?php echo $loc["Marked"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td><input type="radio" name="markedRadio" value="1">&nbsp;&nbsp;<?php echo $loc["Yes"]; ?>&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="markedRadio" value="0">&nbsp;&nbsp;<?php echo $loc["No"]; ?></td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showCopy" value="1"></td>
	<td><b><?php echo $loc["Copy"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="copySelector">
			<option value="is equal to" selected><?php echo $loc["equal to"]; ?></option>
			<option value="is not equal to"><?php echo $loc["equal to not"]; ?></option>
		</select>
	</td>
	<td>
		<select name="copyName">
			<option value="All" selected><?php echo $loc["All"]; ?></option>
			<option value="true"><?php echo $loc["true"]; ?></option>
			<option value="fetch"><?php echo $loc["fetch"]; ?></option>
			<option value="ordered"><?php echo $loc["ordered"]; ?></option>
			<option value="false"><?php echo $loc["false"]; ?></option>
		</select>
	</td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showSelected" value="1"></td>
	<td><b><?php echo $loc["Selected"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td><input type="radio" name="selectedRadio" value="1">&nbsp;&nbsp;<?php echo $loc["Yes"]; ?>&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="selectedRadio" value="0">&nbsp;&nbsp;<?php echo $loc["No"]; ?></td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showUserKeys" value="1"></td>
	<td><b><?php echo $loc["UserKeys"]; ?>:</b></td>
	<td align="center"><input type="radio" name="userKeysRadio" value="1" checked></td>
	<td>
		<select name="userKeysSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><?php

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. The field name of the table's primary key
	// 4. Table name of the user data table
	// 5. The field name within the user data table that corresponds to the field in 3.
	// 6. The field name of the user ID field within the user data table
	// 7. The user ID of the currently logged in user (which must be provided as a session variable)
	// 8. Attribute that contains values
	// 9. <SELECT> element name
	// 10. An additional non-database value (display string)
	// 11. String that gets submitted instead of the display string given in 10.
	// 12. Optional <OPTION SELECTED>
	// 13. Restrict query to field... (keep empty if no restriction wanted)
	// 14. ...where field contents are...
	// 15. Split field contents into substrings? (yes = true, no = false)
	// 16. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	echo selectDistinct($connection,
	                    $tableRefs,
	                    "serial",
	                    $tableUserData,
	                    "record_id",
	                    "user_id",
	                    $loginUserID,
	                    "user_keys",
	                    "userKeysName",
	                    $loc["All"],
	                    "All",
	                    $loc["All"],
	                    "",
	                    "",
	                    true,
	                    " *[,;()] *");
?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="right"><?php echo $loc["or"]; ?>:</td>
	<td align="center"><input type="radio" name="userKeysRadio" value="0"></td>
	<td>
		<select name="userKeysSelector2"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="userKeysName2" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showUserNotes" value="1"></td>
	<td><b><?php echo $loc["UserNotes"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="userNotesSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="userNotesName" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showUserFile" value="1"></td>
	<td><b><?php echo $loc["UserFile"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="userFileSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="userFileName" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showUserGroups" value="1"></td>
	<td><b><?php echo $loc["UserGroups"]; ?>:</b></td>
	<td align="center"><input type="radio" name="userGroupsRadio" value="1" checked></td>
	<td>
		<select name="userGroupsSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><?php

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. The field name of the table's primary key
	// 4. Table name of the user data table
	// 5. The field name within the user data table that corresponds to the field in 3.
	// 6. The field name of the user ID field within the user data table
	// 7. The user ID of the currently logged in user (which must be provided as a session variable)
	// 8. Attribute that contains values
	// 9. <SELECT> element name
	// 10. An additional non-database value (display string)
	// 11. String that gets submitted instead of the display string given in 10.
	// 12. Optional <OPTION SELECTED>
	// 13. Restrict query to field... (keep empty if no restriction wanted)
	// 14. ...where field contents are...
	// 15. Split field contents into substrings? (yes = true, no = false)
	// 16. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	echo selectDistinct($connection,
	                    $tableRefs,
	                    "serial",
	                    $tableUserData,
	                    "record_id",
	                    "user_id",
	                    $loginUserID,
	                    "user_groups",
	                    "userGroupsName",
	                    $loc["All"],
	                    "All",
	                    $loc["All"],
	                    "",
	                    "",
	                    true,
	                    " *[,;()] *");
?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="right"><?php echo $loc["or"]; ?>:</td>
	<td align="center"><input type="radio" name="userGroupsRadio" value="0"></td>
	<td>
		<select name="userGroupsSelector2"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="userGroupsName2" size="42"></td>
</tr>
<tr>
	<td valign="middle"><input type="checkbox" name="showCiteKey" value="1"></td>
	<td><b><?php echo $loc["CiteKey"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td>
		<select name="citeKeySelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td><input type="text" name="citeKeyName" size="42"></td>
</tr><?php

	} // end if (a user is logged in, display user specific fields)

	// --------------------------------------------------------------------

?>

<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td valign="top"><b><?php echo $loc["DisplayOptions"]; ?>:</b></td>
	<td>&nbsp;</td>
	<td valign="middle"><input type="checkbox" name="showLinks" value="1" checked>&nbsp;&nbsp;&nbsp;<?php echo $loc["ShowLinks"]; ?></td>
	<td valign="middle"><?php echo $loc["ShowRecordsPerPage_Prefix"]; ?>&nbsp;&nbsp;&nbsp;<input type="text" name="showRows" value="<?php echo $showRows; ?>" size="4" title="<?php echo $loc["DescriptionShowRecordsPerPage"]; ?>">&nbsp;&nbsp;&nbsp;<?php echo $loc["ShowRecordsPerPage_Suffix"]; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="<?php echo $loc["ButtonTitle_Search"]; ?>"></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr><?php

	if (isset($_SESSION['loginEmail'])) // if a user is logged in, add user specific fields to the sort menus:
	{
		// TODO: if possible, we should use function 'mapFieldNames()' here (see also above)
		$userSpecificSortFieldsNameArray = array("",
		                                         "marked"      => $loc["DropDownFieldName_Marked"],
		                                         "copy"        => $loc["DropDownFieldName_Copy"],
		                                         "selected"    => $loc["DropDownFieldName_Selected"],
		                                         "user_keys"   => $loc["DropDownFieldName_UserKeys"],
		                                         "user_notes"  => $loc["DropDownFieldName_UserNotes"],
		                                         "user_file"   => $loc["DropDownFieldName_UserFile"],
		                                         "user_groups" => $loc["DropDownFieldName_UserGroups"],
		                                         "cite_key"    => $loc["DropDownFieldName_CiteKey"]);

		$dropDownItems4 = buildSelectMenuOptions($userSpecificSortFieldsNameArray, "", "\t\t\t", true); // function 'buildSelectMenuOptions()' is defined in 'include.inc.php'
	}
	else
	{
		$dropDownItems4 = "";
	}
?>

<tr>
	<td>&nbsp;</td>
	<td>1.&nbsp;<?php echo $loc["sort by"]; ?>:</td>
	<td>&nbsp;</td>
	<td>
		<select name="sortSelector1"><?php

$sortSelector1DropDownItems = ereg_replace("<option([^>]*)>" . $loc["DropDownFieldName_Author"], "<option\\1 selected>" . $loc["DropDownFieldName_Author"], $dropDownItems3); // select the 'author' menu entry ...
echo $sortSelector1DropDownItems . $dropDownItems4;
?>

		</select>
	</td>
	<td>
		<input type="radio" name="sortRadio1" value="0" checked>&nbsp;&nbsp;&nbsp;<?php echo $loc["ascending"]; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" name="sortRadio1" value="1">&nbsp;&nbsp;&nbsp;<?php echo $loc["descending"]; ?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>2.&nbsp;<?php echo $loc["sort by"]; ?>:</td>
	<td>&nbsp;</td>
	<td>
		<select name="sortSelector2"><?php

$sortSelector2DropDownItems = ereg_replace("<option([^>]*)>" . $loc["DropDownFieldName_Year"], "<option\\1 selected>" . $loc["DropDownFieldName_Year"], $dropDownItems3); // select the 'year' menu entry ...
echo $sortSelector2DropDownItems . $dropDownItems4;
?>

		</select>
	</td>
	<td>
		<input type="radio" name="sortRadio2" value="0">&nbsp;&nbsp;&nbsp;<?php echo $loc["ascending"]; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" name="sortRadio2" value="1" checked>&nbsp;&nbsp;&nbsp;<?php echo $loc["descending"]; ?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>3.&nbsp;<?php echo $loc["sort by"]; ?>:</td>
	<td>&nbsp;</td>
	<td>
		<select name="sortSelector3"><?php

$sortSelector3DropDownItems = ereg_replace("<option([^>]*)>" . $loc["DropDownFieldName_Publication"], "<option\\1 selected>" . $loc["DropDownFieldName_Publication"], $dropDownItems3); // select the 'publication' menu entry ...
echo $sortSelector3DropDownItems . $dropDownItems4;
?>

		</select>
	</td>
	<td>
		<input type="radio" name="sortRadio3" value="0" checked>&nbsp;&nbsp;&nbsp;<?php echo $loc["ascending"]; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" name="sortRadio3" value="1">&nbsp;&nbsp;&nbsp;<?php echo $loc["descending"]; ?>

	</td>
</tr>
</table>
</form><?php

	// (5) Close the database connection:
	disconnectFromMySQLDatabase(); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
	showPageFooter($HeaderString);

	displayHTMLfoot();

	// --------------------------------------------------------------------
?>
