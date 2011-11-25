<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./contrib/skins/mediawiki-monobook/header.inc.php
	// Repository: $HeadURL$
	// Author(s):  Richard Karnesky <mailto:karnesky@gmail.com>
	//
	// Created:    31-Oct-06, 00:28
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

		if (preg_match("/^Print$/i", $viewType))
		{
?>

	<link rel="stylesheet" href="<?php echo $printStyleSheet; ?>" type="text/css" title="CSS Definition"><?php
		}
		elseif (preg_match("/^Mobile$/i", $viewType))
		{
?>

	<link rel="stylesheet" href="<?php echo $mobileStyleSheet; ?>" type="text/css" title="CSS Definition"><?php
		}
		else
		{
?>

	<link rel="stylesheet" href="<?php echo $defaultStyleSheet; ?>" type="text/css" title="CSS Definition"><?php
		}

		if (!empty($rssURLArray) AND isset($_SESSION['user_permissions']) AND preg_match("/allow_rss_feeds/", $_SESSION['user_permissions'])) // if some RSS URLs were specified AND the 'user_permissions' session variable contains 'allow_rss_feeds'...
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

		if ($includeJavaScript OR (isset($_SESSION['userAutoCompletions']) AND ($_SESSION['userAutoCompletions'] == "yes")) OR ($useVisualEffects == "yes"))
		{
			// ...include common refbase JavaScript functions:
?>

	<script language="JavaScript" type="text/javascript" src="javascript/common.js"></script><?php
		}

		if ((isset($_SESSION['userAutoCompletions']) AND ($_SESSION['userAutoCompletions'] == "yes")) OR ($useVisualEffects == "yes"))
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

		global $loginWelcomeMsg; // these variables are globally defined in function 'showLogin()' in 'include.inc.php'
		global $loginStatus;
		global $loginLinks;

		global $loc; // '$loc' is made globally available in 'core.php'
?>

<div id="globalWrapper">

      <div id="column-content">
	<div id="content">
	  <a name="top" id="contentTop"></a>
	  	  <h1 class="firstHeading"><? echo encodeHTML($officialDatabaseName); ?></h1>
	  <div id="bodyContent">
	    <h3 id="siteSub">From NUCAPT</h3>

	    <div id="contentSub"></div>
	    	    	    <!-- start content -->
            <h2><? echo $HeaderString; ?></h2>


<?php
	}

	// --------------------------------------------------------------------
?>
