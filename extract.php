<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./extract.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    29-Jul-02, 16:39
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// Search form that offers to extract
	// literature cited within a text and build
	// an appropriate reference list from that.


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

	// If there's no stored message available:
	if (!isset($_SESSION['HeaderString']))
		$HeaderString = "Extract citations from a text and build an appropriate reference list:"; // Provide the default message
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

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (2a) Display header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(encodeHTML($officialDatabaseName) . " -- Extract Citations", "index,follow", "Search the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
	showPageHeader($HeaderString);

	// (2b) Start <form> and <table> holding the form elements:
	echo "\n<form action=\"search.php\" method=\"POST\">";

	echo "\n<input type=\"hidden\" name=\"formType\" value=\"extractSearch\">"
		. "\n<input type=\"hidden\" name=\"submit\" value=\"Cite\">"; // provide a default value for the 'submit' form tag. Otherwise, some browsers may not recognize the correct output format when a user hits <enter> within a form field (instead of clicking the "Cite" button)

	if (!isset($_SESSION['user_styles']))
		$citeStyleDisabled = " disabled"; // disable the style popup if the session variable holding the user's styles isn't available
	else
		$citeStyleDisabled = "";

	if (!isset($_SESSION['user_cite_formats']))
		$citeFormatDisabled = " disabled"; // disable the cite format popup if the session variable holding the user's cite formats isn't available
	else
		$citeFormatDisabled = "";

	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds the search form\">"
			. "\n<tr>\n\t<td width=\"58\" valign=\"top\"><b>Extract Citations From:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td><textarea name=\"sourceText\" rows=\"6\" cols=\"60\">Paste your text here...</textarea></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\" rowspan=\"2\"><b>Serial Delimiters:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\">Specify the character(s) that enclose record serial numbers:</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\">Start Delimiter:&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"startDelim\" value=\"{\" size=\"4\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;End Delimiter:&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"endDelim\" value=\"}\" size=\"4\"></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\" rowspan=\"2\"><b>Display Options:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\"><input type=\"checkbox\" name=\"showLinks\" value=\"1\" checked>&nbsp;&nbsp;&nbsp;Display Links"
			. "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Show&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"showRows\" value=\"100\" size=\"4\" title=\"" .  $loc["DescriptionShowRecordsPerPage"] . "\">&nbsp;&nbsp;&nbsp;records per page</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\">View type:&nbsp;&nbsp;"
			. "\n\t\t<select name=\"viewType\">"
			. "\n\t\t\t<option>Web</option>"
			. "\n\t\t\t<option>Print</option>"
			. "\n\t\t\t<option>Mobile</option>"
			. "\n\t\t</select>"
			. "\n\t</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>";

	if (isset($_SESSION['user_permissions']) AND ereg("allow_cite", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_cite'...
	// adjust the title string for the show cite button
	{
		$citeButtonLock = "";
		$citeTitle = "build a reference list for all citations contained within the entered text";
	}
	else // Note, that disabling the submit button is just a cosmetic thing -- the user can still submit the form by pressing enter or by building the correct URL from scratch! (however, there's some code in 'search.php' that will prevent query execution)
	{
		$citeButtonLock = " disabled";
		$citeTitle = "not available since you have no permission to use the cite feature";
	}

	echo "\n\t<td>\n\t\t<br><input type=\"submit\" name=\"submit\" value=\"Cite\"$citeButtonLock title=\"$citeTitle\">&nbsp;&nbsp;&nbsp;"
			. "\n\t\tusing style:&nbsp;&nbsp;"
			. "\n\t\t<select name=\"citeStyle\" title=\"choose the output style for your reference list\"$citeStyleDisabled>";

	if (isset($_SESSION['user_styles']))
	{
		$optionTags = buildSelectMenuOptions($_SESSION['user_styles'], " *; *", "\t\t\t", false); // build properly formatted <option> tag elements from the items listed in the 'user_styles' session variable
		echo $optionTags;
	}
	else
		echo "\n\t\t\t<option>" . $defaultCiteStyle . "</option>"; // defined in 'ini.inc.php'

	echo "\n\t\t</select>&nbsp;&nbsp;&nbsp;"
			. "\n\t\tsort by:&nbsp;&nbsp;"
			. "\n\t\t<select name=\"citeOrder\" title=\"choose the primary sort order for your reference list\">"
			. "\n\t\t\t<option value=\"author\">author</option>"
			. "\n\t\t\t<option value=\"year\">year</option>"
			. "\n\t\t\t<option value=\"type\">type</option>"
			. "\n\t\t\t<option value=\"type-year\">type, year</option>"
			. "\n\t\t</select>&nbsp;&nbsp;&nbsp;"
			. "\n\t\treturn as:&nbsp;&nbsp;"
			. "\n\t\t<select name=\"citeType\" title=\"choose how your reference list shall be returned\"$citeFormatDisabled>";

	if (isset($_SESSION['user_cite_formats']))
	{
		$optionTags = buildSelectMenuOptions($_SESSION['user_cite_formats'], " *; *", "\t\t\t", false); // build properly formatted <option> tag elements from the items listed in the 'user_cite_formats' session variable
		echo $optionTags;
	}
	else
		echo "\n\t\t\t<option>(no formats available)</option>";

	echo "\n\t\t</select>\n\t</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td align=\"center\" colspan=\"3\">&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\"><b>Help:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\">This form enables you to extract all citations from your text and build an appropriate reference list. To have this work simply include the serial numbers of your cited records within your text (as shown below) and enclose the serials by some preferrably unique characters. These delimiters must be specified in the text fields above.</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\"><b>Example:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\"><code>Results of the german south polar expedition were published by Hennings (1906) {1141} as well as several other authors (e.g.: Wille 1924 {1785}; Heiden &amp; Kolbe 1928 {1127}).</code></td>"
			. "\n</tr>"
			. "\n</table>"
			. "\n</form>";
	
	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
	showPageFooter($HeaderString);

	displayHTMLfoot();

	// --------------------------------------------------------------------
?>
