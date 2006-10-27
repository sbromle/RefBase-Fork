<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./index.php
	// Created:    29-Jul-02, 16:45
	// Modified:   01-Sep-06, 23:14

	// This script builds the main page.
	// It provides login and quick search forms
	// as well as links to various search forms.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/
	
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

	// If there's no stored message available:
	if (!isset($_SESSION['HeaderString']))
		$HeaderString = $loc["Default Welcome Message"]; // Provide the default welcome message
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

	// Setup an array of arrays holding URL and title information for all RSS feeds available on this page:
	// (appropriate <link...> tags will be included in the HTML header for every URL specified)
	$rssURLArray = array();

	if (isset($_SESSION['user_permissions']) AND ereg("allow_rss_feeds", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_rss_feeds'...
	{
		$rssURLArray[] = array("href" => "rss.php?where=serial%20RLIKE%20%22.%2B%22&amp;showRows=" . $defaultNumberOfRecords, // '$defaultNumberOfRecords' is defined in 'ini.inc.php'
								"title" => "records added most recently");

		$rssURLArray[] = array("href" => "rss.php?where=created_date%20%3D%20CURDATE%28%29&amp;showRows=" . $defaultNumberOfRecords,
								"title" => "records added today");

		$rssURLArray[] = array("href" => "rss.php?where=modified_date%20%3D%20CURDATE%28%29&amp;showRows=" . $defaultNumberOfRecords,
								"title" => "records edited today");
	}

	// --------------------------------------------------------------------

	// Get the total number of records:
	$recordCount = getNumberOfRecords(); // function 'getNumberOfRecords()' is defined in 'include.inc.php'

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (4) DISPLAY header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(encodeHTML($officialDatabaseName) . " -- " . $loc["Home"], "index,follow", "Search the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, $rssURLArray);
	showPageHeader($HeaderString, "");

	// Define variable holding common drop-down elements, i.e. build properly formatted <option> tag elements:
	$dropDownFieldNameArray = array("author" => $loc["DropDownFieldName_Author"],
									"title" => $loc["DropDownFieldName_Title"],
									"year" => $loc["DropDownFieldName_Year"],
									"keywords" => $loc["DropDownFieldName_Keywords"],
									"abstract" => $loc["DropDownFieldName_Abstract"]);

	$dropDownItems = buildSelectMenuOptions($dropDownFieldNameArray, "", "\t\t\t\t\t", true); // function 'buildSelectMenuOptions()' is defined in 'include.inc.php'

	$dropDownFieldNameArray2 = array("author" => $loc["DropDownFieldName_Author"],
									"year" => $loc["DropDownFieldName_Year"],
									"publication" => $loc["DropDownFieldName_Publication"],
									"keywords" => $loc["DropDownFieldName_Keywords"],
									"user_keys" => $loc["DropDownFieldName_UserKeys"]);

	$dropDownItems2 = buildSelectMenuOptions($dropDownFieldNameArray2, "", "\t\t\t\t\t", true); // function 'buildSelectMenuOptions()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------
?>

<table align="center" border="0" cellpadding="2" cellspacing="5" width="90%" summary="This table explains features, goals and usage of the <?php echo encodeHTML($officialDatabaseName); ?>">
	<tr>
		<td colspan="2"><h3><?php echo $loc["Goals"]; ?> &amp; <?php echo $loc["Features"]; ?></h3></td>
		<td width="182" valign="bottom"><?php
if (!isset($_SESSION['loginEmail']))
	{
?><div class="header"><b><?php echo $loc["Login"]; ?>:</b></div><?php
	}
else
	{
?><div class="header"><b><?php echo $loc["ShowMyRefs"]; ?>:</b></div><?php
	}
?></td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td><?php echo $loc["ThisDatabaseAttempts"]; ?>

			<br>
			<br>
			<?php echo $loc["ThisDatabase"] . " " . $loc["provides"] . ":"; ?>

			<ul type="circle">
				<li><?php echo $loc["Features_ComprehensiveDataset"]; 
					// report the total number of records:
					echo ", ". $loc["currently featuring"]; ?><a href="show.php?records=all" title="<?php echo $loc["LinkTitle_ShowAll"]; ?>"><?php echo $recordCount . " " . $loc["records"]; ?></a></li>
				<li><?php echo $loc["Features_StandardizedInterface"]; ?></li>
				<li><?php echo $loc["Features_SearchOptions"]; ?></li>
				<li><?php echo $loc["Features_DisplayCiteExportOptions"]; ?></li>
				<li><?php

				// -------------------------------------------------------
				if (isset($_SESSION['user_permissions']) AND ereg("(allow_import|allow_batch_import)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains either 'allow_import' or 'allow_batch_import'...
				{
				// ... include a link to 'import.php':
					echo "<a href=\"import.php\">". $loc["Import"] ."</a>";
				}
				else
				{
					echo $loc["Import"];
				}

				// -------------------------------------------------------

				echo $loc["ImportLinkText"]; ?></li>
			</ul>
		</td>
		<td width="182" valign="top">
<?php
if (!isset($_SESSION['loginEmail']))
	{
?>
			<form action="user_login.php" method="POST">
				<?php echo $loc["EmailAddress"]; ?>:
				<br>
				<input type="text" name="loginEmail" size="12">
				<br>
				<?php echo $loc["Password"]; ?>:
				<br>
				<input type="password" name="loginPassword" size="12">
				<br>
				<input type="submit" value="<?php echo $loc["ButtonTitle_Login"]; ?>">
			</form><?php
	}
else
	{
?>
			<form action="search.php" method="GET">
				<input type="hidden" name="formType" value="myRefsSearch">
				<input type="hidden" name="showQuery" value="0">
				<input type="hidden" name="showLinks" value="1">
				<input type="radio" name="myRefsRadio" value="1" checked>&nbsp;<?php echo $loc["All"]; ?>

				<br>
				<input type="radio" name="myRefsRadio" value="0">&nbsp;<?php echo $loc["Only"]; ?>:
				<br>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="findMarked" value="1">
				<select name="markedSelector">
					<option value="marked"><?php echo $loc["marked"]; ?></option>
					<option value="not marked"><?php echo $loc["not"]." ". $loc["marked"]; ?></option>
				</select>
				<br>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="findSelected" value="1">
				<select name="selectedSelector">
					<option value="selected"><?php echo $loc["selected"]; ?></option>
					<option value="not selected"><?php echo $loc["not"]." ". $loc["selected"]; ?></option>
				</select>
				<br>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="findCopy" value="1">&nbsp;<?php echo $loc["copy"]; ?>:
				<select name="copySelector">
					<option value="true"><?php echo $loc["true"]; ?></option>
					<option value="fetch"><?php echo $loc["fetch"]; ?></option>
					<option value="ordered"><?php echo $loc["ordered"]; ?></option>
					<option value="false"><?php echo $loc["false"]; ?></option>
				</select>
				<br>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="findUserKeys" value="1">&nbsp;<?php echo $loc["key"]; ?>:&nbsp;&nbsp;
				<input type="text" name="userKeysName" size="7">
				<br>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="findUserNotes" value="1">&nbsp;<?php echo $loc["note"]; ?>:&nbsp;
				<input type="text" name="userNotesName" size="7">
				<br>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="findUserFile" value="1">&nbsp;<?php echo $loc["file"]; ?>:&nbsp;&nbsp;&nbsp;
				<input type="text" name="userFileName" size="7">
				<br>
				<input type="submit" value="<?php echo $loc["ButtonTitle_Show"]; ?>">
			</form><?php
	}
?>

		</td>
	</tr>
	<tr>
		<td colspan="2"><h3><?php echo $loc["Search"]; ?></h3></td>
		<td width="182" valign="bottom"><div class="header"><b><?php echo $loc["QuickSearch"]; ?>:</b></div></td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td><?php echo $loc["SearchDB"]; ?>:
			<ul type="circle">
				<li><a href="simple_search.php"><?php echo $loc["Simple"]; ?> <?php echo $loc["Search"]; ?></a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;<?php echo $loc["search"]." ".$loc["SearchMain"]; ?></li>
				<li><a href="advanced_search.php"><?php echo $loc["Advanced"]; ?> <?php echo $loc["Search"]; ?></a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;<?php echo $loc["search"]." ".$loc["SearchAll"]; ?></li><?php

		// -------------------------------------------------------
		if (isset($_SESSION['user_permissions']) AND ereg("allow_sql_search", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_sql_search'...
		{
		// ... include a link to 'sql_search.php':
?>

				<li><a href="sql_search.php">SQL <?php echo $loc["Search"]; ?></a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;<?php echo $loc["search"]." ".$loc["SearchSQL"]; ?></li><?php
		}

		// -------------------------------------------------------
?>

				<li><a href="library_search.php"><?php echo $loc["Library"]; ?> <?php echo $loc["Search"]; ?></a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;<?php echo $loc["search"]." ".$loc["SearchExt"]; ?> <?php echo encodeHTML($hostInstitutionName); ?></li>
			</ul>
		</td>
		<td width="182" valign="top">
			<form action="search.php" method="GET">
				<input type="hidden" name="formType" value="quickSearch">
				<input type="hidden" name="showQuery" value="0">
				<input type="hidden" name="showLinks" value="1">
				<select name="quickSearchSelector"><?php

$quickSearchDropDownItems = ereg_replace("<option([^>]*)>" . $loc["DropDownFieldName_Author"], "<option\\1 selected>" . $loc["DropDownFieldName_Author"], $dropDownItems); // select the 'author' menu entry ...
echo $quickSearchDropDownItems;
?>

				</select>
				<br>
				<input type="text" name="quickSearchName" size="12">
				<br>
				<input type="submit" value="<?php echo $loc["ButtonTitle_Search"]; ?>">
			</form>
		</td>
	</tr><?php
if (isset($_SESSION['user_permissions']) AND ereg("allow_browse_view", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_browse_view'...
	{
?>

	<tr>
		<td width="15">&nbsp;</td>
		<td>
			<?php echo $loc["browse all"]; ?>:
		</td>
		<td width="182" valign="top">
<?php
if (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_browse_view", $_SESSION['user_permissions'])))
	{
?>
			<div class="header"><b><?php echo $loc["BrowseMyRefs"]; ?>:</b></div><?php
	}
else
	{
?>
			&nbsp;<?php
	}
?>

		</td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td>
			<ul type="circle" class="moveup">
				<li><a href="show.php?submit=Browse&amp;by=author"><?php echo $loc["author"]; ?></a> | <a href="show.php?submit=Browse&amp;by=year"><?php echo $loc["year"]; ?></a> | <a href="show.php?submit=Browse&amp;by=publication"><?php echo $loc["publication"]; ?></a> | <a href="show.php?submit=Browse&amp;by=keywords"><?php echo $loc["keywords"]; ?></a></li>
				<li><a href="show.php?submit=Browse&amp;by=location"><?php echo $loc["location"]; ?></a> | <a href="show.php?submit=Browse&amp;by=area"><?php echo $loc["area"]; ?></a> | <a href="show.php?submit=Browse&amp;by=language"><?php echo $loc["language"]; ?></a> | <a href="show.php?submit=Browse&amp;by=type"><?php echo $loc["type"]; ?></a></li>
			</ul>
		</td>
		<td width="182" valign="top">
<?php
if (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_browse_view", $_SESSION['user_permissions']))) // if a user is logged in AND the 'user_permissions' session variable contains 'allow_browse_view', show the 'Browse My Refs' form:
	{
?>
			<form action="search.php" method="GET">
				<input type="hidden" name="formType" value="myRefsBrowse">
				<input type="hidden" name="submit" value="Browse">
				<input type="hidden" name="showQuery" value="0">
				<input type="hidden" name="showLinks" value="1">
				<input type="hidden" name="showRows" value="10">
				<select name="browseFieldSelector"><?php

$browseMyRefsDropDownItems = ereg_replace("<option([^>]*)>" . $loc["DropDownFieldName_Author"], "<option\\1 selected>" . $loc["DropDownFieldName_Author"], $dropDownItems2); // select the 'author' menu entry ...
echo $browseMyRefsDropDownItems;
?>

				</select>
				<br>
				<input type="submit" value="<?php echo $loc["ButtonTitle_Browse"]; ?>">
			</form><?php
	}
else
	{
?>
			&nbsp;<?php
	}
?>

		</td>
	</tr><?php
	}
?>

	<tr>
		<td width="15">&nbsp;</td>
		<td>
			<?php echo $loc["view all"]; ?>:
		</td>
		<td width="182" valign="top">
<?php
if (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_user_groups", $_SESSION['user_permissions'])))
	{
?>
			<div class="header"><b><?php echo $loc["ShowMyGroup"]; ?>:</b></div><?php
	}
else
	{
?>
			&nbsp;<?php
	}
?>

		</td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td>
<?php
	// Get the current year & date in order to include them into query URLs:
	$CurrentYear = date('Y');
	$CurrentDate = date('Y-m-d');
	// We'll also need yesterday's date for inclusion into query URLs:
	$TimeStampYesterday = mktime(0, 0, 0, date('m'), (date('d') - 1), date('Y'));
	$DateYesterday = date('Y-m-d', $TimeStampYesterday);
	// Plus, we'll calculate the date that's a week ago (again, for inclusion into query URLs):
	$TimeStampLastWeek = mktime(0, 0, 0, date('m'), (date('d') - 7), date('Y'));
	$DateLastWeek = date('Y-m-d', $TimeStampLastWeek);

	if (isset($_SESSION['loginEmail'])) // if a user is logged in
	{
		// Get the date & time of the last login for the current user:
		if (!empty($lastLogin)) // '$lastLogin' is provided as session variable
		{
			$lastLoginDate = date('Y-m-d', strtotime($lastLogin));
			$lastLoginTime = date('H:i:s', strtotime($lastLogin));
		}
		else
		{
			$lastLoginDate = "";
			$lastLoginTime = "";
		}
	}
?>
			<ul type="circle" class="moveup">
				<li><?php echo $loc["added"]; ?>: <a href="show.php?date=<?php echo $CurrentDate; ?>"><?php echo $loc["today"]; ?></a> | <a href="show.php?date=<?php echo $DateYesterday; ?>"><?php echo $loc["yesterday"]; ?></a> | <a href="show.php?date=<?php echo $DateLastWeek; ?>&amp;range=after"><?php echo $loc["last 7 days"]; ?></a><?php if (isset($_SESSION['loginEmail']) AND !empty($lastLoginDate) AND !empty($lastLoginTime)) { ?> | <a href="show.php?date=<?php echo $lastLoginDate; ?>&amp;time=<?php echo $lastLoginTime; ?>&amp;range=equal_or_after"><?php echo $loc["since last login"]; ?></a><?php } ?></li>
				<li><?php echo $loc["edited"]; ?>: <a href="show.php?date=<?php echo $CurrentDate; ?>&amp;when=edited"><?php echo $loc["today"]; ?></a> | <a href="show.php?date=<?php echo $DateYesterday; ?>&amp;when=edited"><?php echo $loc["yesterday"]; ?></a> | <a href="show.php?date=<?php echo $DateLastWeek; ?>&amp;when=edited&amp;range=after"><?php echo $loc["last 7 days"]; ?></a><?php if (isset($_SESSION['loginEmail']) AND !empty($lastLoginDate) AND !empty($lastLoginTime)) { ?> | <a href="show.php?date=<?php echo $lastLoginDate; ?>&amp;time=<?php echo $lastLoginTime; ?>&amp;when=edited&amp;range=equal_or_after"><?php echo $loc["since last login"]; ?></a><?php } ?></li>
				<li><?php echo $loc["published in"]; ?>: <a href="show.php?year=<?php echo $CurrentYear; ?>"><?php echo $CurrentYear; ?></a> | <a href="show.php?year=<?php echo ($CurrentYear - 1); ?>"><?php echo ($CurrentYear - 1); ?></a> | <a href="show.php?year=<?php echo ($CurrentYear - 2); ?>"><?php echo ($CurrentYear - 2); ?></a> | <a href="show.php?year=<?php echo ($CurrentYear - 3); ?>"><?php echo ($CurrentYear - 3); ?></a></li>
			</ul>
		</td>
		<td width="182" valign="top">
<?php
if (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_user_groups", $_SESSION['user_permissions']))) // if a user is logged in AND the 'user_permissions' session variable contains 'allow_user_groups', show the 'Show My Groups' form:
	{
		if (!isset($_SESSION['userGroups']))
			$groupSearchDisabled = " disabled"; // disable the 'Show My Groups' form if the session variable holding the user's groups isnt't available
		else
			$groupSearchDisabled = "";
?>
			<form action="search.php" method="GET">
				<input type="hidden" name="formType" value="groupSearch">
				<input type="hidden" name="showQuery" value="0">
				<input type="hidden" name="showLinks" value="1">
				<select name="groupSearchSelector"<?php echo $groupSearchDisabled; ?>><?php

				if (isset($_SESSION['userGroups']))
				{
					$optionTags = buildSelectMenuOptions($_SESSION['userGroups'], " *; *", "\t\t\t\t\t", false); // build properly formatted <option> tag elements from the items listed in the 'userGroups' session variable
					echo $optionTags;
				}
				else
				{
?>

					<option>(<?php echo $loc["NoGroupsAvl"]; ?>)</option><?php
				}
?>

				</select>
				<br>
				<input type="submit" value="<?php echo $loc["ButtonTitle_Show"]; ?>"<?php echo $groupSearchDisabled; ?>>
			</form><?php
	}
else
	{
?>
			&nbsp;<?php
	}
?>

		</td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td>
<?php
if (isset($_SESSION['user_permissions']) AND ereg("(allow_details_view|allow_cite)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable either contains 'allow_details_view' or 'allow_cite'...
	{
?>
			<?php echo $loc["Tools serial nums"]; ?>:<?php
	}
else
	{
?>
			&nbsp;<?php
	}
?>

		</td>
		<td width="182" valign="top">
<?php
if (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_user_queries", $_SESSION['user_permissions'])))
	{
?>
			<div class="header"><b><?php echo $loc["RecallMyQuery"]; ?>:</b></div><?php
	}
else
	{
?>
			&nbsp;<?php
	}
?>

		</td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td>
			<ul type="circle" class="moveup"><?php
if (isset($_SESSION['user_permissions']) AND ereg("allow_details_view", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_details_view'...
	{
?>

				<li><a href="show.php"><?php echo $loc["SearchSerial"]; ?></a><?php echo $loc["SearchSerialLinkText"]; ?></li><?php
	}

if (isset($_SESSION['user_permissions']) AND ereg("allow_cite", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_cite'...
	{
?>

				<li><a href="extract.php"><?php echo $loc["extractCitations"]; ?></a><?php echo $loc["ExtractCitationsLinkText"]; ?></li><?php
	}
?>

			</ul>
		</td>
		<td width="182" valign="top">
<?php
if (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_user_queries", $_SESSION['user_permissions']))) // if a user is logged in AND the 'user_permissions' session variable contains 'allow_user_queries', show the 'Recall My Query' form:
	{
		if (!isset($_SESSION['userQueries']))
			$querySearchDisabled = " disabled"; // disable the 'Recall My Query' form if the session variable holding the user's queries isn't available
		else
			$querySearchDisabled = "";
?>
			<form action="queries.php" method="GET">
				<input type="hidden" name="formType" value="querySearch">
				<input type="hidden" name="showQuery" value="0">
				<input type="hidden" name="showLinks" value="1">
				<select name="querySearchSelector"<?php echo $querySearchDisabled; ?>><?php

				if (isset($_SESSION['userQueries']))
				{
					$optionTags = buildSelectMenuOptions($_SESSION['userQueries'], " *; *", "\t\t\t\t\t", false); // build properly formatted <option> tag elements from the items listed in the 'userQueries' session variable
					echo $optionTags;
				}
				else
				{
?>

					<option>(<?php echo $loc["NoQueriesAvl"]; ?>)</option><?php
				}
?>

				</select>
				<br>
				<input type="submit" name="submit" value="<?php echo $loc["ButtonTitle_Go"]; ?>"<?php echo $querySearchDisabled; ?>>&nbsp;<input type="submit" name="submit" value="<?php echo $loc["ButtonTitle_Edit"]; ?>"<?php echo $querySearchDisabled; ?>>
			</form><?php
	}
else
	{
?>
			&nbsp;<?php
	}
?>

		</td>
	</tr>
	<tr>
		<td colspan="3"><h3><?php echo $loc["about"]; ?></h3></td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td><?php echo $loc["ThisDatabaseIsMaintained"]; ?> <a href="<?php echo $hostInstitutionURL; ?>"><?php echo encodeHTML($hostInstitutionName); ?></a> (<?php echo encodeHTML($hostInstitutionAbbrevName); ?>). <?php echo $loc["You are welcome to send"]; ?> <a href="mailto:<?php echo $feedbackEmail; ?>"><?php echo $loc["feedback address"]; ?></a>. <?php echo $loc["refbaseDesc"]; ?></td>
		<td width="182" valign="top"><a href="http://www.refbase.net/"><img src="img/refbase_credit.gif" alt="powered by refbase" width="80" height="44" hspace="0" border="0"></a></td>
	</tr>
</table><?php

	// --------------------------------------------------------------------

	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase(""); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
	showPageFooter($HeaderString, "");

	displayHTMLfoot();

	// --------------------------------------------------------------------
?>
