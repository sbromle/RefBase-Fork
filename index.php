<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./index.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    29-Jul-02, 16:45
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This script builds the main page.
	// It provides login and quick search forms
	// as well as links to various search forms.


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

	// Extract the view type requested by the user (either 'Mobile', 'Print', 'Web' or ''):
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
		$showRows = $_SESSION['userRecordsPerPage']; // get the default number of records per page preferred by the current user

		$rssURLArray[] = array("href"  => generateURL("show.php", $defaultFeedFormat, array("where" => 'serial RLIKE ".+"'), true, $showRows), // function 'generateURL()' is defined in 'include.inc.php', variable '$defaultFeedFormat' is defined in 'ini.inc.php'
		                       "title" => "records added most recently");

		$rssURLArray[] = array("href"  => generateURL("show.php", $defaultFeedFormat, array("where" => 'created_date = CURDATE()'), true, $showRows),
		                       "title" => "records added today");

		$rssURLArray[] = array("href"  => generateURL("show.php", $defaultFeedFormat, array("where" => 'modified_date = CURDATE()'), true, $showRows),
		                       "title" => "records edited today");
	}

	// --------------------------------------------------------------------

	// Adjust the width of the right-hand column according to the calling user agent:
	// NOTE: strictly, this isn't really necessary but it helps to achieve a similar appearance of the login form on Firefox/Gecko & Safari/WebKit browsers (with all supported GUI languages)
	// TODO: figure out a better way (which isn't based on user agent sniffing); the problem could also be avoided by simply stacking <input> fields & their labels on top of each other
	if (isset($_SERVER['HTTP_USER_AGENT']) AND eregi("AppleWebKit", $_SERVER['HTTP_USER_AGENT']))
		$rightColumnWidth = "215";
	else
		$rightColumnWidth = "225";

	// Get the total number of records:
	$recordCount = getTotalNumberOfRecords(); // function 'getTotalNumberOfRecords()' is defined in 'include.inc.php'

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (4) DISPLAY header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(encodeHTML($officialDatabaseName) . " -- " . $loc["Home"], "index,follow", "Search the " . encodeHTML($officialDatabaseName), "", true, "", $viewType, $rssURLArray);
	showPageHeader($HeaderString);

	// Define variables holding common drop-down elements, i.e. build properly formatted <option> tag elements:
	// - "Browse My Refs" form:
	$dropDownFieldNameArray2 = array("author"      => $loc["DropDownFieldName_Author"],
	                                 "year"        => $loc["DropDownFieldName_Year"],
	                                 "publication" => $loc["DropDownFieldName_Publication"],
	                                 "keywords"    => $loc["DropDownFieldName_Keywords"],
	                                 "user_keys"   => $loc["DropDownFieldName_UserKeys"]);

	$dropDownItems2 = buildSelectMenuOptions($dropDownFieldNameArray2, "", "\t\t\t\t\t", true); // function 'buildSelectMenuOptions()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------
?>

<table align="center" border="0" cellpadding="2" cellspacing="5" width="90%" summary="This table explains features, goals and usage of the <?php echo encodeHTML($officialDatabaseName); ?>">
	<tr>
		<td colspan="2"><h3><?php echo $loc["RecentChanges"]; ?></h3></td>
		<td width="<?php echo $rightColumnWidth; ?>" valign="bottom" rowspan="2">
<?php
if (!isset($_SESSION['loginEmail']))
	{
?>
			<div id="userlogin" class="box">
				<div class="boxHead">
					<h3><?php echo $loc["Login"]; ?>:</h3>
				</div>
				<div class="boxBody">
					<form action="user_login.php" method="POST" name="login">
						<fieldset>
							<legend><?php echo $loc["Login"]; ?>:</legend>
							<input type="hidden" name="referer" value="index.php">
							<div id="loginUser">
								<div id="loginUserLabel">
									<label for="loginEmail"><?php echo $loc["Email"]; ?>:</label>
								</div>
								<div id="loginUserInput">
									<input type="text" id="loginEmail" name="loginEmail" size="12">
								</div>
							</div>
							<div id="loginPwd">
								<div id="loginPwdLabel">
									<label for="loginPassword"><?php echo $loc["Password"]; ?>:</label>
								</div>
								<div id="loginPwdInput">
									<input type="password" id="loginPassword" name="loginPassword" size="12">
								</div>
							</div>
							<div id="loginSubmit">
								<input type="submit" value="<?php echo $loc["ButtonTitle_Login"]; ?>">
							</div>
						</fieldset>
					</form>
				</div>
			</div><?php
	}
elseif (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_user_groups", $_SESSION['user_permissions']))) // if a user is logged in AND the 'user_permissions' session variable contains 'allow_user_groups', show the 'Show My Groups' form:
	{
		if (!isset($_SESSION['userGroups']))
			$groupSearchDisabled = " disabled"; // disable the 'Show My Groups' form if the session variable holding the user's groups isnt't available
		else
			$groupSearchDisabled = "";
?>
			<div id="showgroupmain" class="box">
				<div class="boxHead">
					<h3><?php echo $loc["ShowMyGroup"]; ?>:</h3>
				</div>
				<div class="boxBody">
					<form action="search.php" method="GET" name="groupSearch">
						<fieldset>
							<legend><?php echo $loc["ShowMyGroup"]; ?>:</legend>
							<input type="hidden" name="formType" value="groupSearch">
							<input type="hidden" name="showQuery" value="0">
							<input type="hidden" name="showLinks" value="1">
							<div id="groupSelect">
								<label for="groupSearchSelector"><?php echo $loc["My"]; ?>:</label>
								<select name="groupSearchSelector"<?php echo $groupSearchDisabled; ?>><?php

								if (isset($_SESSION['userGroups']))
								{
									$optionTags = buildSelectMenuOptions($_SESSION['userGroups'], " *; *", "\t\t\t\t\t\t\t\t\t", false); // build properly formatted <option> tag elements from the items listed in the 'userGroups' session variable
									echo $optionTags;
								}
								else
								{
?>

									<option>(<?php echo $loc["NoGroupsAvl"]; ?>)</option><?php
								}
?>

								</select>
							</div>
							<div id="groupSubmit">
								<input type="submit" value="<?php echo $loc["ButtonTitle_Show"]; ?>"<?php echo $groupSearchDisabled; ?>>
							</div>
						</fieldset>
					</form>
				</div>
			</div><?php
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
			<div id="recentlinks">
				<ul type="circle" class="moveup">
					<li><?php echo $loc["added"]; ?>: <a href="show.php?date=<?php echo $CurrentDate; ?>"><?php echo $loc["today"]; ?></a> | <a href="show.php?date=<?php echo $DateYesterday; ?>"><?php echo $loc["yesterday"]; ?></a> | <a href="show.php?date=<?php echo $DateLastWeek; ?>&amp;range=after"><?php echo $loc["last 7 days"]; ?></a><?php if (isset($_SESSION['loginEmail']) AND !empty($lastLoginDate) AND !empty($lastLoginTime)) { ?> | <a href="show.php?date=<?php echo $lastLoginDate; ?>&amp;time=<?php echo $lastLoginTime; ?>&amp;range=equal_or_after"><?php echo $loc["since last login"]; ?></a><?php } ?></li>
					<li><?php echo $loc["edited"]; ?>: <a href="show.php?date=<?php echo $CurrentDate; ?>&amp;when=edited"><?php echo $loc["today"]; ?></a> | <a href="show.php?date=<?php echo $DateYesterday; ?>&amp;when=edited"><?php echo $loc["yesterday"]; ?></a> | <a href="show.php?date=<?php echo $DateLastWeek; ?>&amp;when=edited&amp;range=after"><?php echo $loc["last 7 days"]; ?></a><?php if (isset($_SESSION['loginEmail']) AND !empty($lastLoginDate) AND !empty($lastLoginTime)) { ?> | <a href="show.php?date=<?php echo $lastLoginDate; ?>&amp;time=<?php echo $lastLoginTime; ?>&amp;when=edited&amp;range=equal_or_after"><?php echo $loc["since last login"]; ?></a><?php } ?></li>
					<li><?php echo $loc["published in"]; ?>: <a href="show.php?year=<?php echo $CurrentYear; ?>"><?php echo $CurrentYear; ?></a> | <a href="show.php?year=<?php echo ($CurrentYear - 1); ?>"><?php echo ($CurrentYear - 1); ?></a> | <a href="show.php?year=<?php echo ($CurrentYear - 2); ?>"><?php echo ($CurrentYear - 2); ?></a> | <a href="show.php?year=<?php echo ($CurrentYear - 3); ?>"><?php echo ($CurrentYear - 3); ?></a></li>
				</ul>
			</div>
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
		<td width="<?php echo $rightColumnWidth; ?>" valign="top">
<?php
if (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_browse_view", $_SESSION['user_permissions'])))
	{
?>
			<h5><?php echo $loc["BrowseMyRefs"]; ?>:</h5><?php
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
		<td width="<?php echo $rightColumnWidth; ?>" valign="top">
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
			<?php echo $loc["MostRecentPublications"]; ?>:
		</td>
		<td width="<?php echo $rightColumnWidth; ?>" valign="top" rowspan="2">
<?php
if (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_user_queries", $_SESSION['user_permissions']))) // if a user is logged in AND the 'user_permissions' session variable contains 'allow_user_queries', show the 'Recall My Query' form:
	{
		if (!isset($_SESSION['userQueries']))
			$querySearchDisabled = " disabled"; // disable the 'Recall My Query' form if the session variable holding the user's queries isn't available
		else
			$querySearchDisabled = "";
?>
			<div id="recallquerymain" class="box">
				<div class="boxHead">
					<h3><?php echo $loc["RecallMyQuery"]; ?>:</h3>
				</div>
				<div class="boxBody">
					<form action="queries.php" method="GET" name="querySearch">
						<fieldset>
							<legend><?php echo $loc["RecallMyQuery"]; ?>:</legend>
							<input type="hidden" name="formType" value="querySearch">
							<input type="hidden" name="showQuery" value="0">
							<input type="hidden" name="showLinks" value="1">
							<div id="recallSelect">
								<label for="querySearchSelector"><?php echo $loc["Query"]; ?>:</label>
								<select name="querySearchSelector"<?php echo $querySearchDisabled; ?>><?php

								if (isset($_SESSION['userQueries']))
								{
									$optionTags = buildSelectMenuOptions($_SESSION['userQueries'], " *; *", "\t\t\t\t\t\t\t\t\t", false); // build properly formatted <option> tag elements from the items listed in the 'userQueries' session variable
									echo $optionTags;
								}
								else
								{
?>

									<option>(<?php echo $loc["NoQueriesAvl"]; ?>)</option><?php
								}
?>

								</select>
							</div>
							<div id="recallSubmit">
								<input type="submit" name="submit" value="<?php echo $loc["ButtonTitle_Go"]; ?>"<?php echo $querySearchDisabled; ?>>
								<input type="submit" name="submit" value="<?php echo $loc["ButtonTitle_Edit"]; ?>"<?php echo $querySearchDisabled; ?>>
							</div>
						</fieldset>
					</form>
				</div>
			</div><?php
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
			<div id="includerefs"><?php

			// Fetch the most recently added publications (as formatted citations), or link to them:

			$recentAdditionsResultTable = "";

			// Get all user permissions for the anonymous user (userID = 0):
			// NOTE: since function 'fetchDataFromURL()' retrieves citations anonymously (i.e. the
			//       current user's session is not maintained, see note below), we need to check the
			//       permissions for the *anonymous* user (userID = 0) here
			$anonymousUserPermissionsArray = getPermissions(0, "user", false); // function 'getPermissions()' is defined in 'include.inc.php'

			if (isset($_SESSION['user_permissions']) AND ($anonymousUserPermissionsArray["allow_cite"] == "yes")) // if the anonymous user is allowed to output records as citations
			{
				// NOTE: - as an alternative to the below code block, we could also fetch citations via an AJAX event and let the JavaScript functions in file 'javascript/show.js' ' write the results into the '<div id="includerefs">' section;
				//         to do so:
				//           1. pass the JavaScript file 'javascript/show.js' as the 6th parameter to the 'displayHTMLhead' function (see above)
				//           2. call JavaScript function 'showRefs()' via an 'onload' event in the body tag of function 'displayHTMLhead()' in 'includes/header.inc.php':  onload="showRefs('records=all&amp;showRows=5&amp;citeOrder=creation-date')"
				//              TODO: function 'displayHTMLhead()' should get modified so that it only calls the 'onload' event if necessary/requested
				// 
				//       - the above alternative works within the user's current session, i.e. the links section will contain any edit or file links (if the user has appropriate permissions);
				//         however, the below method (which uses function 'fetchDataFromURL()') does NOT maintain the user's current session (and adding the user's current PHPSESSID doesn't seem to work ?:-/)

				// Prepare a query that will fetch a HTML table with the most recently added publications (as formatted citations):
				$recentAdditionsQueryURL = $databaseBaseURL . "show.php?records=all&submit=Cite&showRows=5&citeOrder=creation-date&client=inc-refbase-1.0&wrapResults=0"; // variable '$databaseBaseURL' is defined in 'ini.inc.php'

				$recentAdditionsResultTable = fetchDataFromURL($recentAdditionsQueryURL); // function 'fetchDataFromURL()' is defined in 'include.inc.php'
			}

			if (!empty($recentAdditionsResultTable))
			{
				echo $recentAdditionsResultTable;
			}
			else
			{
?>

				<a href="show.php?records=all&amp;citeOrder=creation-date"><?php echo $loc["ShowAll"]; ?></a><?php
			}
?>

			</div>
		</td>
	</tr>
	<tr>
		<td colspan="3"><h3><?php echo $loc["about"]; ?></h3></td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td><?php echo $loc["ThisDatabaseIsMaintained"]; ?> <a href="<?php echo $hostInstitutionURL; ?>"><?php echo encodeHTML($hostInstitutionName); ?></a> (<?php echo encodeHTML($hostInstitutionAbbrevName); ?>). <?php echo $loc["You are welcome to send"]; ?> <a href="mailto:<?php echo $feedbackEmail; ?>"><?php echo $loc["feedback address"]; ?></a>. <?php echo $loc["refbaseDesc"]; ?></td>
		<td width="<?php echo $rightColumnWidth; ?>" valign="top" align="center"><a href="http://www.refbase.net/"><img src="img/refbase_credit.gif" alt="powered by refbase" width="142" height="51" hspace="0" border="0"></a></td>
	</tr>
</table><?php

	// --------------------------------------------------------------------

	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase(); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
	showPageFooter($HeaderString);

	displayHTMLfoot();

	// --------------------------------------------------------------------
?>
