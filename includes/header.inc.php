<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./includes/header.inc.php
	// Created:    28-Jul-02, 11:21
	// Modified:   10-Sep-06, 01:18

	// This is the header include file.
	// It contains functions that provide the HTML header
	// as well as the visible header that gets displayed on every page.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// --------------------------------------------------------------------

	// Inserts the HTML <head>...</head> block as well as the initial <body> tag:
	function displayHTMLhead($pageTitle, $metaRobots, $metaDescription, $additionalMeta, $includeJavaScript, $includeJavaScriptFile, $viewType, $rssURLArray)
	{
		global $contentTypeCharset; // these variables are specified in 'ini.inc.php' 
		global $defaultStyleSheet;
		global $printStyleSheet;
		global $databaseBaseURL;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
		"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title><?php echo $pageTitle; ?></title>
	<meta name="date" content="<?php echo date('d-M-y'); ?>">
	<meta name="robots" content="<?php echo $metaRobots; ?>">
	<meta name="description" lang="en" content="<?php echo $metaDescription; ?>">
	<meta name="keywords" lang="en" content="science academic literature scientific references search citation web database mysql php"><?php

		if (!empty($additionalMeta))
			echo $additionalMeta;
?>

	<meta http-equiv="content-language" content="en">
	<meta http-equiv="content-type" content="text/html; charset=<?php echo $contentTypeCharset; ?>">
	<meta http-equiv="Content-Style-Type" content="text/css"><?php

		if ($viewType == "Print")
		{
?>

	<link rel="stylesheet" href="<?php echo $printStyleSheet; ?>" type="text/css" title="CSS Definition"><?php
		}
		else
		{
?>

	<link rel="stylesheet" href="<?php echo $defaultStyleSheet; ?>" type="text/css" title="CSS Definition"><?php
		}

		if (!empty($rssURLArray) AND isset($_SESSION['user_permissions']) AND ereg("allow_rss_feeds", $_SESSION['user_permissions'])) // if some RSS URLs were specified AND the 'user_permissions' session variable contains 'allow_rss_feeds'...
		{
			foreach ($rssURLArray as $rssURL)
			{
			// ...include a link tag pointing to a dynamic RSS feed for the current query:
?>

	<link rel="alternate" type="application/rss+xml" href="<?php echo $rssURL['href']; ?>" title="<?php echo $rssURL['title']; ?>"><?php
			}
		}
?>

	<link rel="unapi-server" type="application/xml" title="unAPI" href="<?php echo $databaseBaseURL; ?>unapi.php"><?php

		if (!empty($includeJavaScriptFile))
		{
?>

	<script language="JavaScript" type="text/javascript" src="<?php echo $includeJavaScriptFile; ?>">
		</script><?php
		}

		if ($includeJavaScript)
		{
?>

	<script language="JavaScript" type="text/javascript">
		function checkall(val,formpart){
			x=0;
			while(document.queryResults.elements[x]){
				if(document.queryResults.elements[x].name==formpart){
					document.queryResults.elements[x].checked=val;
				}
				x++;
			}
		}
	</script><?php
		}
?>

</head>
<body><?php
	}

	// --------------------------------------------------------------------

	// Displays the visible header:
	function showPageHeader($HeaderString, $oldQuery)
	{
		global $officialDatabaseName; // these variables are defined in 'ini.inc.php'
		global $hostInstitutionAbbrevName;
		global $hostInstitutionName;
		global $hostInstitutionURL;
		global $helpResourcesURL;

		global $loginWelcomeMsg; // these variables are globally defined in function 'showLogin()' in 'include.inc.php'
		global $loginStatus;
		global $loginLinks;

		global $loc; // '$loc' is made globally available in 'core.php'
?>

<table align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This holds the title logo and info">
<tr>
	<td valign="middle" rowspan="2" align="left" width="170"><a href="<?php echo $hostInstitutionURL; ?>"><img src="img/logo.gif" border="0" alt="<?php echo encodeHTML($hostInstitutionAbbrevName); ?> Home" title="<?php echo encodeHTML($hostInstitutionName); ?>" width="143" height="107"></a></td>
	<td>
		<h2><?php echo encodeHTML($officialDatabaseName); ?></h2>
		<span class="smallup">
			<a href="index.php" title="<?php echo $loc["LinkTitle_Home"]; ?>"><?php echo $loc["Home"]; ?></a>&nbsp;|&nbsp;
			<a href="show.php?records=all" title="<?php echo $loc["LinkTitle_ShowAll"]; ?>"><?php echo $loc["ShowAll"]; ?></a>&nbsp;|&nbsp;
			<a href="simple_search.php" title="<?php echo $loc["LinkTitle_SimpleSearch"]; ?>"><?php echo $loc["Simple"]; ?> <?php echo $loc["Search"]; ?></a>&nbsp;|&nbsp;
			<a href="advanced_search.php" title="<?php echo $loc["LinkTitle_AdvancedSearch"]; ?>"><?php echo $loc["Advanced"]; ?> <?php echo $loc["Search"]; ?></a><?php

		// -------------------------------------------------------
		if (isset($_SESSION['user_permissions']) AND ereg("allow_add", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_add'...
		{
		// ... include a link to 'record.php?recordAction=add...':
?>

			&nbsp;|&nbsp;<a href="record.php?recordAction=add&amp;oldQuery=<?php echo rawurlencode($oldQuery); ?>" title="<?php echo $loc["LinkTitle_AddRecord"]; ?>"><?php echo $loc["AddRecord"]; ?></a><?php
		}

		// -------------------------------------------------------
		if (isset($_SESSION['user_permissions']) AND ereg("(allow_import|allow_batch_import)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains either 'allow_import' or 'allow_batch_import'...
		{
		// ... include a link to 'import.php':
?>

			&nbsp;|&nbsp;<a href="import.php" title="<?php echo $loc["LinkTitle_Import"]; ?>"><?php echo $loc["Import"]; ?></a><?php
		}

		// -------------------------------------------------------
?>

			<!--&nbsp;|&nbsp;<a href="help.php" title="display help">Help</a>-->
		</span>
	</td>
	<td class="small" align="right" valign="middle"><?php echo $loginWelcomeMsg; ?><br><?php echo $loginStatus; ?></td>
</tr>
<tr>
	<td><?php echo $HeaderString; ?></td>
	<td class="small" align="right" valign="middle"><?php echo $loginLinks; ?></td>
</tr>
</table>
<hr align="center" width="95%"><?php
	}

	// --------------------------------------------------------------------
?>
