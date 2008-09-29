<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/header.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    28-Jul-02, 11:21
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is the header include file.
	// It contains functions that provide the HTML header
	// as well as the visible header that gets displayed on every page.

	// --------------------------------------------------------------------

	// Inserts the HTML <head>...</head> block as well as the initial <body> tag:
	// 
	// TODO: include OpenSearch elements in HTML header
	//       (see examples at <http://www.opensearch.org/Specifications/OpenSearch/1.1#Response_metadata_in_HTML.2FXHTML>)
	function displayHTMLhead($pageTitle, $metaRobots, $metaDescription, $additionalMeta, $includeJavaScript, $includeJavaScriptFile, $viewType, $rssURLArray)
	{
		global $officialDatabaseName; // these variables are defined in 'ini.inc.php'
		global $contentTypeCharset;
		global $defaultStyleSheet;
		global $printStyleSheet;
		global $mobileStyleSheet;
		global $autoCompleteUserInput;
		global $useVisualEffects;
		global $databaseBaseURL;
		global $databaseKeywords;
		global $defaultFeedFormat;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
		"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head profile="http://a9.com/-/spec/opensearch/1.1/">
	<title><?php echo $pageTitle; ?></title>
	<meta name="date" content="<?php echo date('d-M-y'); ?>">
	<meta name="robots" content="<?php echo $metaRobots; ?>">
	<meta name="description" lang="en" content="<?php echo $metaDescription; ?>">
	<meta name="keywords" lang="en" content="<?php echo $databaseKeywords; ?>"><?php

		if (!empty($additionalMeta))
			echo $additionalMeta;
?>

	<meta http-equiv="content-language" content="<?php echo getUserLanguage(); ?>">
	<meta http-equiv="content-type" content="text/html; charset=<?php echo $contentTypeCharset; ?>">
	<meta http-equiv="Content-Style-Type" content="text/css"><?php

		if (eregi("^Print$", $viewType))
		{
?>

	<link rel="stylesheet" href="<?php echo $printStyleSheet; ?>" type="text/css" title="CSS Definition"><?php
		}
		elseif (eregi("^Mobile$", $viewType))
		{
?>

	<link rel="stylesheet" href="<?php echo $mobileStyleSheet; ?>" type="text/css" title="CSS Definition"><?php
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
				if ($defaultFeedFormat == "Atom XML")
					$feedContentType = "application/atom+xml";
				else // RSS XML
					$feedContentType = "application/rss+xml";

			// ...include a link tag pointing to a dynamic RSS feed for the current query:
?>

	<link rel="alternate" type="<?php echo $feedContentType; ?>" href="<?php echo $databaseBaseURL . $rssURL['href']; ?>" title="<?php echo $rssURL['title']; ?>"><?php
			}
		}
?>

	<link rel="unapi-server" type="application/xml" title="unAPI" href="<?php echo $databaseBaseURL; ?>unapi.php">
	<link rel="search" type="application/opensearchdescription+xml" title="<?php echo encodeHTML($officialDatabaseName); ?>" href="<?php echo $databaseBaseURL; ?>opensearch.php?operation=explain"><?php

		if ($includeJavaScript OR ($autoCompleteUserInput == "yes") OR ($useVisualEffects == "yes"))
		{
			// ...include common refbase JavaScript functions:
?>

	<script language="JavaScript" type="text/javascript" src="javascript/common.js"></script><?php
		}

		if (($autoCompleteUserInput == "yes") OR ($useVisualEffects == "yes"))
		{
			// ...include the Prototype & script.aculo.us JavaScript frameworks:
?>

	<script language="JavaScript" type="text/javascript" src="javascript/prototype.js"></script>
	<script language="JavaScript" type="text/javascript" src="javascript/scriptaculous.js?load=effects,controls"></script><?php
		}

		if (!empty($includeJavaScriptFile))
		{
			// ...include additional JavaScript functions:
?>

	<script language="JavaScript" type="text/javascript" src="<?php echo $includeJavaScriptFile; ?>"></script><?php
		}
?>

</head>
<body><?php
	}

	// --------------------------------------------------------------------

	// Displays the visible header:
	function showPageHeader($HeaderString)
	{
		global $officialDatabaseName; // these variables are defined in 'ini.inc.php'
		global $hostInstitutionAbbrevName;
		global $hostInstitutionName;
		global $hostInstitutionURL;
		global $helpResourcesURL;
		global $logoImageURL;
		global $logoImageWidth;
		global $logoImageHeight;

		global $loginWelcomeMsg; // these variables are globally defined in function 'showLogin()' in 'include.inc.php'
		global $loginStatus;
		global $loginLinks;

		global $displayType;
		global $query;
		global $queryURL;
		global $showQuery;
		global $showLinks;
		global $showRows;

		global $citeStyle;
		global $citeOrder;

		global $loc; // '$loc' is made globally available in 'core.php'
?>

<table class="pageheader" align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This holds the title logo and info">
<tr>
	<td valign="bottom" rowspan="2" align="left" width="<?php echo $logoImageWidth + 26; ?>"><a href="<?php echo $hostInstitutionURL; ?>"><img src="<?php echo $logoImageURL; ?>" border="0" alt="<?php echo encodeHTML($hostInstitutionAbbrevName); ?> Home" title="<?php echo encodeHTML($hostInstitutionName); ?>" width="<?php echo $logoImageWidth; ?>" height="<?php echo $logoImageHeight; ?>"></a></td>
	<td>
		<h2><?php echo encodeHTML($officialDatabaseName); ?></h2>
		<span class="smallup">
			<a href="index.php"<?php echo addAccessKey("attribute", "home"); ?> title="<?php echo $loc["LinkTitle_Home"] . addAccessKey("title", "home"); ?>"><?php echo $loc["Home"]; ?></a>&nbsp;|&nbsp;
			<a href="show.php?records=all"<?php echo addAccessKey("attribute", "show_all"); ?> title="<?php echo $loc["LinkTitle_ShowAll"] . addAccessKey("title", "show_all"); ?>"><?php echo $loc["ShowAll"]; ?></a>&nbsp;|&nbsp;
			<a href="simple_search.php"<?php echo addAccessKey("attribute", "search"); ?> title="<?php echo $loc["LinkTitle_SimpleSearch"] . addAccessKey("title", "search"); ?>"><?php echo $loc["SimpleSearch"]; ?></a>&nbsp;|&nbsp;
			<a href="advanced_search.php"<?php echo addAccessKey("attribute", "adv_search"); ?> title="<?php echo $loc["LinkTitle_AdvancedSearch"] . addAccessKey("title", "adv_search"); ?>"><?php echo $loc["AdvancedSearch"]; ?></a><?php

		// -------------------------------------------------------
		if (isset($_SESSION['user_permissions']) AND ereg("allow_add", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_add'...
		{
		// ... include a link to 'record.php?recordAction=add...':
?>

			&nbsp;|&nbsp;<a href="record.php?recordAction=add"<?php echo addAccessKey("attribute", "add"); ?> title="<?php echo $loc["LinkTitle_AddRecord"] . addAccessKey("title", "add"); ?>"><?php echo $loc["AddRecord"]; ?></a><?php
		}

		// -------------------------------------------------------
		if (isset($_SESSION['user_permissions']) AND ereg("(allow_import|allow_batch_import)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains either 'allow_import' or 'allow_batch_import'...
		{
		// ... include a link to 'import.php':
?>

			&nbsp;|&nbsp;<a href="import.php"<?php echo addAccessKey("attribute", "import"); ?> title="<?php echo $loc["LinkTitle_Import"] . addAccessKey("title", "import"); ?>"><?php echo $loc["Import"]; ?></a><?php
		}

		// -------------------------------------------------------
?>

			<!--&nbsp;|&nbsp;<a href="help.php" title="display help">Help</a>-->
		</span>
	</td>
	<td class="small" valign="bottom" rowspan="2" align="right">
		<div id="loginInfo">
			<div id="loginStatus"><?php echo $loginStatus; ?></div>
			<div id="loginName"><?php echo $loginWelcomeMsg; ?></div>
			<div id="loginLinks"><?php echo $loginLinks; ?></div>
		</div>
		<div id="queryrefs">
<?php echo buildQuickSearchElements($query, $queryURL, $showQuery, $showLinks, $showRows, $citeStyle, $citeOrder, $displayType); ?>
		</div>
	</td>
</tr>
<tr>
	<td><?php echo $HeaderString; ?></td>
</tr>
</table>
<hr class="pageheader" align="center" width="95%"><?php
	}

	// --------------------------------------------------------------------
?>
