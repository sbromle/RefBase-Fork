<?php
	// Copyright:  Richard Karnesky <mailto:karnesky@gmail.com>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./includes/header.inc.php

	// --------------------------------------------------------------------

	// Inserts the HTML <head>...</head> block as well as the initial <body> tag:
	function displayHTMLhead($pageTitle, $metaRobots, $metaDescription, $additionalMeta, $includeJavaScript, $includeJavaScriptFile, $viewType, $rssURLArray)
	{
		global $contentTypeCharset; // these variables are specified in 'ini.inc.php' 
		global $defaultStyleSheet;
		global $printStyleSheet;
		global $mobileStyleSheet;
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

		if (eregi("^Print$", $viewType))
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
		function checkall(val, formpart) {
			var x = 0;
			while(document.queryResults.elements[x]) {
				if(document.queryResults.elements[x].name == formpart) {
					document.queryResults.elements[x].checked = val;
				}
				x++;
			}
			toggleRadio('allRecs', 'selRecs', val);
		}
		function toggleVisibility(id, imgid, txtid, txt) {
			var e = document.getElementById(id);
			var i = document.getElementById(imgid);
			var t = document.getElementById(txtid);
			if(e.style.display == 'block') {
				e.style.display = 'none';
				i.src = 'img/closed.gif';
				t.innerHTML = txt;
			}
			else {
				e.style.display = 'block';
				i.src = 'img/open.gif';
				t.innerHTML = '';
			}
		}
		function toggleRadio(id1, id2, val) {
			document.getElementById(id1).checked = !(val);
			document.getElementById(id2).checked = val;
		}
		function updateAllRecs() {
			var val=!eval("document.getElementById('allRecs').checked");
			var x=0;
			var checked=0;
			while(document.queryResults.elements[x]) {
				if(document.queryResults.elements[x].name == "marked[]") {
					if (eval("document.queryResults.elements[x].checked")) {
						checked++;
					}
				}
				x++;
			}
			if (checked>0) {
				val=true;
			} else {
				val=false;
			}
			toggleRadio('allRecs', 'selRecs', val);
		}
	</script><?php
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
