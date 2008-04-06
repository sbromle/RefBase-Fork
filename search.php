<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./search.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    30-Jul-02, 17:40
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is the main script that handles the search query and displays the query results.
	// Supports three different output styles: 1) List view, with fully configurable columns -> displayColumns() function
	// 2) Details view, shows all fields -> displayDetails() function; 3) Citation view -> generateCitations() function

	// TODO: - Refactor so that query builder will use a few common functions
	//       - I18n


	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/header.inc.php'; // include header
	include 'includes/results_header.inc.php'; // include results header
	include 'includes/footer.inc.php'; // include footer
	include 'includes/include.inc.php'; // include common functions
	include 'includes/cite.inc.php'; // include citation functions
	include 'includes/export.inc.php'; // include export functions
	include 'includes/execute.inc.php'; // include functions that deal with execution of shell commands
	include 'includes/atomxml.inc.php'; // include functions that deal with Atom XML
	include 'includes/modsxml.inc.php'; // include functions that deal with MODS XML
	include 'includes/oaidcxml.inc.php'; // include functions that deal with OAI_DC XML
	include 'includes/odfxml.inc.php'; // include functions that deal with ODF XML
	include 'includes/opensearch.inc.php'; // include functions that return an OpenSearch response
	include 'includes/openurl.inc.php';
	include 'includes/srwxml.inc.php'; // include functions that deal with SRW XML
	include 'initialize/ini.inc.php'; // include common variables

	// --------------------------------------------------------------------

	// Extract the ID of the client from which the query originated:
	// this identifier is used to identify queries that originated from the refbase command line clients ("cli-refbase-1.1", "cli-refbase_import-1.0") or from a bookmarklet (e.g., "jsb-refbase-1.0")
	// (note that 'client' parameter has to be extracted *before* the call to the 'start_session()' function, since it's value is required by this function)
	if (isset($_REQUEST['client']))
		$client = $_REQUEST['client'];
	else
		$client = "";

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// Read out POST data that were saved as a session variable:
	// NOTE: this is done by 'show.php' if the original request was a POST (as is the case for the refbase command line client)
	//       in order to retain large param/value strings (that would exceed the maximum string limit for GET requests)
	if (isset($_SESSION['postData']))
	{
		foreach ($_SESSION['postData'] as $varname => $value)
		{
			$_POST[$varname] = $value;
			$_REQUEST[$varname] = $value;
		}

		deleteSessionVariable("postData"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	}

	// --------------------------------------------------------------------

	// Initialize preferred display language:
	// (note that 'locales.inc.php' has to be included *after* the call to the 'start_session()' function)
	include 'includes/locales.inc.php'; // include the locales

	// --------------------------------------------------------------------

	// EXTRACT FORM VARIABLES

	// [ Extract form variables sent through POST/GET by use of the '$_REQUEST' variable ]
	// [ !! NOTE !!: for details see <http://www.php.net/release_4_2_1.php> & <http://www.php.net/manual/en/language.variables.predefined.php> ]

	// Extract the form used for searching:
	$formType = $_REQUEST['formType'];

	// Extract the type of display requested by the user. Normally, this will be one of the following:
	//  - '' => if the 'submit' parameter is empty, this will produce the default view
	//  - 'List' => display records using the columnar output style ('displayColumns()' function)
	//  - 'Display' => display details for all found records ('displayDetails()' function)
	//  - 'Cite' => build a proper citation for all found records ('generateCitations()' function)
	//  - 'Browse' => browse unique values from a given database field ('displayColumns()' function)
	// Note that the 'submit' parameter can be also one of the following:
	//   - 'Export' => generate and return selected records in the bibliographic format specified by the user ('generateExport()' function)
	//   - 'RSS' => these value gets included within the 'RSS' link (in the page header) and will cause 'search.php' to return results as RSS feed
	//   - 'Search', 'Show' or 'Hide' => these values change/refine the search results or their appearance on screen (how many entries & which columns get displayed)
	//   - 'Add', 'Remove', 'Remember' or 'Forget' => these values will trigger actions that act on the selected records (NOTE: 'Remember' or 'Forget' are currently disabled!)
	if (isset($_REQUEST['submit']) AND !empty($_REQUEST['submit']))
		$displayType = $_REQUEST['submit'];
	else
		$displayType = $defaultView; // defined in 'ini.inc.php'

	// extract the original value of the '$displayType' variable:
	// (which was included as a hidden form tag within the 'groupSearch' form of a search results page, the 'queryResults' form in Details view, and the 'duplicateSearch' form)
	if (isset($_REQUEST['originalDisplayType']))
		$originalDisplayType = $_REQUEST['originalDisplayType'];
	else
		$originalDisplayType = "";

	// get the referring URL (if any):
	if (isset($_SERVER['HTTP_REFERER']))
		$referer = $_SERVER['HTTP_REFERER'];
	else // as an example, 'HTTP_REFERER' won't be set if a user clicked on a URL of type '.../show.php?record=12345' within an email announcement
		$referer = ""; // if there's no HTTP referer available we provide the empty string here


	// we need to check if the user is allowed to view records with the specified display type:
	if ($displayType == "List")
	{
		if (isset($_SESSION['user_permissions']) AND !ereg("allow_list_view", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does NOT contain 'allow_list_view'...
		{
			// return an appropriate error message:
			$HeaderString = returnMsg($loc["NoPermission"] . $loc["NoPermission_ForDisplayColumns"] . "!", "warning", "strong", "HeaderString"); // function 'returnMsg()' is defined in 'include.inc.php'

			if (!eregi("^cli", $client))
				header("Location: index.php"); // redirect to main page ('index.php')

			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}
	}
	elseif ($displayType == "Display")
	{
		if (isset($_SESSION['user_permissions']) AND !ereg("allow_details_view", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does NOT contain 'allow_details_view'...
		{
			// return an appropriate error message:
			$HeaderString = returnMsg($loc["NoPermission"] . $loc["NoPermission_ForDisplayDetails"] . "!", "warning", "strong", "HeaderString"); // function 'returnMsg()' is defined in 'include.inc.php'

			if (!eregi("^cli", $client))
				header("Location: index.php"); // redirect to main page ('index.php')

			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}
	}
	elseif ($displayType == "Cite")
	{
		if (isset($_SESSION['user_permissions']) AND !ereg("allow_cite", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does NOT contain 'allow_cite'...
		{
			// return an appropriate error message:
			$HeaderString = returnMsg($loc["NoPermission"] . $loc["NoPermission_ForCite"] . "!", "warning", "strong", "HeaderString"); // function 'returnMsg()' is defined in 'include.inc.php'

			if (!eregi("^cli", $client))
			{
				if (ereg(".+extract.php", $referer)) // if the query was submitted by 'extract.php'
					header("Location: " . $referer); // redirect to calling page
				else
					header("Location: index.php"); // redirect to main page ('index.php')
			}

			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}
	}
	elseif ($displayType == "Export")
	{
		if (isset($_SESSION['user_permissions']) AND !ereg("(allow_export|allow_batch_export)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does NOT contain either 'allow_export' or 'allow_batch_export'...
		{
			// return an appropriate error message:
			$HeaderString = returnMsg($loc["NoPermission"] . $loc["NoPermission_ForExport"] . "!", "warning", "strong", "HeaderString"); // function 'returnMsg()' is defined in 'include.inc.php'

			if (!eregi("^cli", $client))
			{
				if (ereg(".+extract.php", $referer)) // if the query was submitted by 'extract.php'
					header("Location: " . $referer); // redirect to calling page
				else
					header("Location: index.php"); // redirect to main page ('index.php')
			}

			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}
	}
	elseif ((empty($displayType) OR ($displayType == "List")) AND ereg(".+[/_]search.php", $referer))
	{
		// by restricting this if clause to scripts that end with '/search.php' or '_search.php', we exclude 'opensearch.php' and 'show.php' to allow for SQL queries like : 'show.php?date=...&when=...&range=...' and 'show.php?year=...'
		// (and if the referer variable is empty this if clause won't apply either)

		if (isset($_SESSION['user_permissions']) AND !ereg("allow_sql_search", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does NOT contain 'allow_sql_search'...
		{
			if ($formType == "sqlSearch" AND !ereg(".+/search.php", $referer)) // if the calling URL contained 'formType=sqlSearch' but wasn't sent by 'search.php' (but, e.g., by 'sql_search.php')
			{
				// return an appropriate error message:
				$HeaderString = returnMsg($loc["NoPermission"] . $loc["NoPermission_ForSQL"] . "!", "warning", "strong", "HeaderString"); // function 'returnMsg()' is defined in 'include.inc.php'

				if (!eregi("^cli", $client))
				{
					if (ereg(".+sql_search.php", $referer)) // if the sql query was entered in the form provided by 'sql_search.php'
						header("Location: " . $referer); // redirect to calling page
					else
						header("Location: index.php"); // redirect to main page ('index.php')
				}

				exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
			}
		}
	}

	// For a given display type, extract the view type requested by the user (either 'Mobile', 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = ucfirst(strtolower($_REQUEST['viewType'])); // we normalize the case of passed values
	else
		$viewType = "";

	// Extract other variables from the request:
	if (isset($_REQUEST['sqlQuery']))
		$sqlQuery = $_REQUEST['sqlQuery'];
	else
		$sqlQuery = "";
	if (ereg("%20", $sqlQuery)) // if '$sqlQuery' still contains URL encoded data... ('%20' is the URL encoded form of a space, see note below!)
		$sqlQuery = rawurldecode($sqlQuery); // URL decode SQL query (it was URL encoded before incorporation into hidden tags of the 'groupSearch', 'refineSearch', 'displayOptions' and 'queryResults' forms to avoid any HTML syntax errors)
											// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_REQUEST'!
											//       But, opposed to that, URL encoded data that are included within a form by means of a hidden form tag will *NOT* get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!

	if (isset($_REQUEST['showQuery']))
		$showQuery = $_REQUEST['showQuery'];
	else
		$showQuery = "";

	if (isset($_REQUEST['showLinks']))
		$showLinks = $_REQUEST['showLinks'];
	else
		$showLinks = "";

	if (isset($_REQUEST['showRows']) AND ereg("^[0-9]+$", $_REQUEST['showRows'])) // NOTE: we cannot use "^[1-9]+[0-9]*$" here since 'maximumRecords=0' is used in 'opensearch.php' and 'sru.php' queries to return just the number of found records (and not the full record data)
		$showRows = $_REQUEST['showRows'];
	else
		$showRows = $_SESSION['userRecordsPerPage']; // get the default number of records per page preferred by the current user

	if (isset($_REQUEST['rowOffset']))
	{
		// Note: Besides passing the current value of '$rowOffset' within GET queries, this parameter was also included as a hidden tag into the 'queryResults' form.
		//       This was done, so that the correct offset could be re-applied after the user pressed either of the 'Add', 'Remove', 'Remember' or 'Forget' buttons.
		//       However, '$rowOffset' MUST NOT be set if the user clicked the 'Display' or 'Cite' button within the 'queryResults' form!
		//       Therefore, we'll trap this case here:
		if (($formType != "queryResults") OR ($formType == "queryResults" AND !ereg("^(Display|Cite)$", $displayType)))
			$rowOffset = $_REQUEST['rowOffset'];
		else // if ($formType == "queryResults" AND ereg("^(Display|Cite)$", $displayType))
			$rowOffset = 0;
	}
	else
		$rowOffset = 0;

	if (isset($_REQUEST['wrapResults']) AND ($_REQUEST['wrapResults'] == "0"))
		$wrapResults = $_REQUEST['wrapResults']; // for citation output, 'wrapResults=0' causes refbase to output only a partial document structure containing solely the search results (e.g. for HTML, everything is omitted except for the <table> block containing the search results)
	else
		$wrapResults = "1"; // we'll output a full document (HTML, RTF, LaTeX, etc) structure unless the 'wrapResults' parameter is set explicitly to "0"

	// In order to generalize routines we have to query further variables here:
	if (isset($_REQUEST['citeStyle']) AND !empty($_REQUEST['citeStyle']))
		$citeStyle = $_REQUEST['citeStyle']; // get the cite style chosen by the user (only occurs in 'extract.php' form and in query result lists)
	else
		$citeStyle = $defaultCiteStyle; // if no cite style was given, we'll use the default cite style which is defined by the '$defaultCiteStyle' variable in 'ini.inc.php'
	if (ereg("%20", $citeStyle)) // if '$citeStyle' still contains URL encoded data... ('%20' is the URL encoded form of a space, see note below!)
		$citeStyle = rawurldecode($citeStyle); // ...URL decode 'citeStyle' statement (it was URL encoded before incorporation into a hidden tag of the 'sqlSearch' form to avoid any HTML syntax errors)
													// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_REQUEST'!
													//       But, opposed to that, URL encoded data that are included within a form by means of a *hidden form tag* will NOT get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!

	if (isset($_REQUEST['exportFormat']) AND !empty($_REQUEST['exportFormat']))
		$exportFormat = $_REQUEST['exportFormat']; // get the export format style chosen by the user (only occurs in 'extract.php' form and in query result lists)
	else
		$exportFormat = $defaultExportFormat; // if no export format was given, we'll use the default export format which is defined by the '$defaultExportFormat' variable in 'ini.inc.php'
	if (ereg("%20", $exportFormat)) // if '$exportFormat' still contains URL encoded data... ('%20' is the URL encoded form of a space, see note below!)
		$exportFormat = rawurldecode($exportFormat); // ...URL decode 'exportFormat' statement (it was URL encoded before incorporation into a hidden tag of the 'sqlSearch' form to avoid any HTML syntax errors)
													// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_REQUEST'!
													//       But, opposed to that, URL encoded data that are included within a form by means of a *hidden form tag* will NOT get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!
	// Standardize XML export format names:
	// NOTE: the below regex patterns are potentially too lax and might cause misbehaviour in case any custom export formats have been added
	if (eregi("^Atom", $exportFormat))
		$exportFormat = "Atom XML";
	elseif (eregi("^MODS", $exportFormat))
		$exportFormat = "MODS XML";
	elseif (eregi("^(OAI_)?DC", $exportFormat))
		$exportFormat = "OAI_DC XML";
	elseif (eregi("^ODF", $exportFormat))
		$exportFormat = "ODF XML";
	elseif (eregi("^SRW_DC", $exportFormat))
		$exportFormat = "SRW_DC XML";
	elseif (eregi("^SRW", $exportFormat))
		$exportFormat = "SRW_MODS XML";
	elseif (eregi("^Word", $exportFormat))
		$exportFormat = "Word XML";

	if (isset($_REQUEST['citeOrder']))
		$citeOrder = $_REQUEST['citeOrder']; // get information how the data should be sorted (only occurs in 'extract.php'/'sql_search' forms and in query result lists). If this param is set to 'year', records will be listed in blocks sorted by year.
	else
		$citeOrder = "";

	// get information how citation data shall be returned:
	// - 'html' => return citations as HTML with mime type 'text/html'
	// - 'RTF' => return citations as RTF data with mime type 'application/rtf'
	// - 'PDF' => return citations as PDF data with mime type 'application/pdf'
	// - 'LaTeX' => return citations as LaTeX data with mime type 'application/x-latex'
	// - 'Markdown' => return citations as Markdown TEXT data with mime type 'text/plain'
	// - 'ASCII' => return citations as TEXT data with mime type 'text/plain'
	// - 'LaTeX .bbl' => return citations as LaTeX .bbl file (for use with LaTeX/BibTeX) with mime type 'application/x-latex'
	if (isset($_REQUEST['citeType']) AND eregi("^(html|RTF|PDF|LaTeX|Markdown|ASCII|LaTeX \.bbl)$", $_REQUEST['citeType']))
		$citeType = $_REQUEST['citeType'];
	else
		$citeType = "html";

	// get information how exported data shall be returned:
	// - 'text' => return data with mime type 'text/plain'
	// - 'html' => return data with mime type 'text/html'
	// - 'xml' => return data with mime type 'application/xml'
	// - 'rss' => return data with mime type 'application/rss+xml'
	// - 'file' => return data as downloadable file
	// - 'email' => send data as email (to the user's login email address)
	if (isset($_REQUEST['exportType']) AND eregi("^(text|html|xml|rss|file|email)$", $_REQUEST['exportType']))
		$exportType = $_REQUEST['exportType'];
	else
		$exportType = "html";

	if (isset($_REQUEST['exportStylesheet']))
		$exportStylesheet = $_REQUEST['exportStylesheet']; // extract any stylesheet information that has been specified for XML export formats
	else
		$exportStylesheet = "";

	if (isset($_REQUEST['orderBy']))
		$orderBy = $_REQUEST['orderBy']; // extract the current ORDER BY parameter so that it can be re-applied when displaying details (only occurs in query result lists)
	else
		$orderBy = "";
	if (ereg("%20", $orderBy)) // if '$orderBy' still contains URL encoded data... ('%20' is the URL encoded form of a space, see note below!)
		$orderBy = rawurldecode($orderBy); // ...URL decode 'orderBy' statement (it was URL encoded before incorporation into a hidden tag of the 'queryResults' form to avoid any HTML syntax errors)
										// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_REQUEST'!
										//       But, opposed to that, URL encoded data that are included within a form by means of a *hidden form tag* will NOT get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!

	if ($orderBy == '') // if there's no ORDER BY parameter...
		$orderBy = "author, year DESC, publication"; // ...use the default ORDER BY clause

	if (isset($_REQUEST['headerMsg']))
		$headerMsg = stripTags($_REQUEST['headerMsg']); // get any custom header message but strip HTML tags from the custom header message to prevent cross-site scripting (XSS) attacks (function 'stripTags()' is defined in 'include.inc.php')
						// Note: this feature is provided in 'search.php' so that it's possible to include an information string within a link. This info string could
						//       e.g. describe who's publications are being displayed (e.g.: "Publications of Matthias Steffens:"). I.e., a link pointing to a persons own
						//       publications can include the appropriate owner information (it will show up as header message)
	else
		$headerMsg = "";

	if (isset($_SESSION['oldQuery']))
		$oldQuery = $_SESSION['oldQuery']; // get the query URL of the formerly displayed results page
	else
		$oldQuery = "";

	// Extract checkbox variable values from the request:
	if (isset($_REQUEST['marked']))
		$recordSerialsArray = $_REQUEST['marked']; // extract the values of all checked checkboxes (i.e., the serials of all selected records)
	else
		$recordSerialsArray = array();

	// check if the user did mark any checkboxes (and set up variables accordingly, they will be used within the 'displayDetails()', 'generateCitations()' and 'modifyUserGroups()' functions)
	if (ereg(".+[/_]search.php", $referer) AND empty($recordSerialsArray)) // no checkboxes were marked
		$nothingChecked = true;
	else // some checkboxes were marked -OR- the query resulted from another script like 'opensearch.php', 'show.php' or 'rss.php' (which has no checkboxes to mark!)
		$nothingChecked = false;



	// --------------------------------------------------------------------

	// VERIFY SQL QUERY:
	// Note that for user-generated SQL queries, further verification is done in function 'verifySQLQuery()'

	$notPermitted = false;

	// Prevent cross-site scripting (XSS) attacks:
	// Note that this is just a rough measure, everything that slips thru will get HTML encoded before output
	$htmlTagsArray = array("a", "applet", "base", "basefont", "bgsound", "blink", "body", "br", "div", "embed", "head", "html", "frame", "frameset", "ilayer", "iframe", "img", "input", "layer", "ilayer", "link", "meta", "script", "span", "style", "object", "table", "title", "xml");

	if (!empty($sqlQuery) AND preg_match("/(<|&lt;?|&#0*60;?|&#x0*3C;?|%3C|\\\\x3c|\\\\u003c)\/*(" . join("|", $htmlTagsArray) . ")/i", $sqlQuery)) // if the SQL query contains any unwanted HTML tags
	{
		$sqlQuery = preg_replace("/(<|&lt;?|&#0*60;?|&#x0*3C;?|%3C|\\\\x3c|\\\\u003c)\/*(" . join("|", $htmlTagsArray) . ").*?(>|&gt;?|&#0*62;?|&#x0*3E;?|%3E|\\\\x3e|\\\\u003e)*/i", "", $sqlQuery);

		$notPermitted = true;
		$HeaderString = $loc["NoPermission"] . $loc["NoPermission_ForThisQuery"] . "!";
	}

	// For a normal user we only allow the use of SELECT queries (the admin is allowed to do everything that is allowed by his GRANT privileges):
	// NOTE: This does only provide for minimal security!
	//		 To avoid further security risks you should grant the MySQL user (who's specified in 'db.inc.php') only those
	//		 permissions that are required to access the literature database. This can be done by use of a GRANT statement:
	//		 GRANT SELECT,INSERT,UPDATE,DELETE ON MYSQL_DATABASE_NAME_GOES_HERE.* TO MYSQL_USER_NAME_GOES_HERE@localhost IDENTIFIED BY 'MYSQL_PASSWORD_GOES_HERE';

	// if the SQL query isn't build from scratch but is accepted from user input (which is the case for the forms 'sqlSearch', 'duplicateSearch' and 'refineSearch'):
	if (!empty($sqlQuery) AND eregi("(sql|duplicate|refine)Search", $formType)) // the user used 'sql_search.php', 'duplicate_search.php' -OR- the "Search within Results" form above the query results list (that was produced by 'search.php')
	{
		if ((!isset($loginEmail)) OR ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail))) // if the user isn't logged in -OR- any normal user is logged in...
		{
			$tablesArray = array($tableAuth, $tableDeleted, $tableDepends, $tableFormats, $tableLanguages, $tableQueries, $tableRefs, $tableStyles, $tableTypes, $tableUserData, $tableUserFormats, $tableUserOptions, $tableUserPermissions, $tableUserStyles, $tableUserTypes, $tableUsers);
			$forbiddenSQLCommandsArray = array("DROP DATABASE", "DROP TABLE"); // the refbase MySQL user shouldn't have permissions for these commands anyhow, but by listing & checking for them here, we can return a more appropriate error message

			// ...and the user did use anything other than a SELECT query:
			if (!eregi("^SELECT", $sqlQuery) OR eregi(join("|", $forbiddenSQLCommandsArray), $sqlQuery))
			{
				$notPermitted = true;
				$HeaderString = $loc["NoPermission_ForSQLOtherThanSELECT"] . "!";
			}
			// ...or the user tries to hack the SQL query (by providing e.g. the string "FROM refs" within the SELECT statement) -OR- if the user attempts to query anything other than the 'refs' or 'user_data' table:
			elseif ((preg_match("/FROM .*(" . join("|", $tablesArray) . ").+ FROM /i", $sqlQuery)) OR (!preg_match("/FROM $tableRefs( LEFT JOIN $tableUserData ON serial ?= ?record_id AND user_id ?= ?\d*)?(?= WHERE| ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$)/i", $sqlQuery)))
			{
				$notPermitted = true;
				$HeaderString = $loc["NoPermission"] . $loc["NoPermission_ForThisQuery"] . "!";
			}
		}
		// note that besides the above validation, in case of 'duplicate_search.php' the SQL query will be further restricted so that generally only SELECT queries can be executed (this is handled by function 'findDuplicates()')
	}

	if ($notPermitted)
	{
		// return an appropriate error message:
		$HeaderString = returnMsg($HeaderString, "warning", "strong", "HeaderString"); // function 'returnMsg()' is defined in 'include.inc.php'

		if (!eregi("^cli", $client))
		{
			if (eregi(".+(sql|duplicate)_search.php", $referer)) // if the sql query was entered in the form provided by 'sql_search.php' or 'duplicate_search.php'
				header("Location: $referer"); // relocate back to the calling page
			else // if the user didn't come from 'sql_search.php' or 'duplicate_search.php' (e.g., if he attempted to hack parameters of a GET query directly)
				header("Location: index.php"); // relocate back to the main page
		}

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	if (isset($_REQUEST["loginEmail"]))
		$loginEmail = $_REQUEST["loginEmail"]; // extract the email address of the currently logged in user

	if (isset($_SESSION['loginEmail'])) // if a user is logged in...
		$userID = getUserID($loginEmail); // ...get the user's 'user_id' using his/her 'loginEmail' (function 'getUserID()' is defined in 'include.inc.php')
	else
		$userID = 0; // set variable to zero (a user with '$userID = 0' definitely doesn't exist) in order to prevent 'Undefined variable...' messages

	// --------------------------------------------------------------------

	// CONSTRUCT SQL QUERY from user input provided by any of the search forms:

	// --- Form 'sql_search.php': ------------------
	if ($formType == "sqlSearch") // the user either used the 'sql_search.php' form for searching -OR- used scripts like 'show.php' or 'rss.php' (which also use 'formType=sqlSearch')...
		{
			// verify the SQL query specified by the user and modify it if security concerns are encountered:
			// (this function does add/remove user-specific query code as required and will fix problems with escape sequences within the SQL query)
			$query = verifySQLQuery($sqlQuery, $referer, $displayType, $showLinks); // function 'verifySQLQuery()' is defined in 'include.inc.php' (since it's also used by 'rss.php')
		}

	// --- Form 'duplicate_search.php': ---------------
	elseif ($formType == "duplicateSearch") // the user used the 'duplicate_search.php' form for searching...
		{
			// find duplicate records within results of the given SQL query (using settings extracted from the 'duplicateSearch' form
			// in 'duplicate_search.php') and return a modified database query that only matches these duplicate entries:
			list($sqlQuery, $displayType) = findDuplicates($sqlQuery, $originalDisplayType);

			// by passing the generated SQL query thru the 'verifySQLQuery()' function we ensure that necessary fields are added as needed:
			// (this function does add/remove user-specific query code as required and will fix problems with escape sequences within the SQL query)
			$query = verifySQLQuery($sqlQuery, $referer, $displayType, $showLinks); // function 'verifySQLQuery()' is defined in 'include.inc.php' (since it's also used by 'rss.php')
		}

	// --- Form 'simple_search.php': ---------------
	elseif ($formType == "simpleSearch") // the user used the 'simple_search.php' form for searching...
		{
			$query = extractFormElementsSimple($showLinks, $userID);
		}

	// --- Form 'library_search.php': --------------
	elseif ($formType == "librarySearch") // the user used the 'library_search.php' form for searching...
		{
			$query = extractFormElementsLibrary($showLinks, $userID);
		}

	// --- Form 'advanced_search.php': -------------
	elseif ($formType == "advancedSearch") // the user used the 'advanced_search.php' form for searching...
		{
			$query = extractFormElementsAdvanced($showLinks, $loginEmail, $userID);
		}

	// --- Form within 'search.php': ---------------
	elseif ($formType == "refineSearch" OR $formType == "displayOptions") // the user used the "Search within Results" (or "Display Options") form above the query results list (that was produced by 'search.php')
		{
			list($query, $displayType) = extractFormElementsRefineDisplay($tableRefs, $displayType, $originalDisplayType, $sqlQuery, $showLinks, $userID); // function 'extractFormElementsRefineDisplay()' is defined in 'include.inc.php' since it's also used by 'users.php'
		}

	// --- Form within 'search.php': ---------------
	elseif ($formType == "queryResults") // the user clicked one of the buttons under the query results list (that was produced by 'search.php')
		{
			list($query, $displayType) = extractFormElementsQueryResults($displayType, $originalDisplayType, $showLinks, $citeOrder, $orderBy, $userID, $sqlQuery, $referer, $recordSerialsArray);
		}

	// --- Form 'extract.php': ---------------------
	elseif ($formType == "extractSearch") // the user used the 'extract.php' form for searching...
		{
			$query = extractFormElementsExtract($showLinks, $citeOrder, $userID);
		}

	// --- My Refs Search Form within 'index.php': -------------------
	elseif ($formType == "myRefsSearch") // the user used the 'Show My Refs' search form on the main page ('index.php') for searching...
		{
			$query = extractFormElementsMyRefs($showLinks, $loginEmail, $userID);
		}

	// --- Quick Search Form within 'index.php': ---------------------
	elseif ($formType == "quickSearch") // the user used the 'Quick Search' form on the main page ('index.php') for searching...
		{
			$query = extractFormElementsQuick($showLinks, $userID, $displayType);
		}

	// --- Browse My Refs Form within 'index.php': -------------------
	elseif ($formType == "myRefsBrowse") // the user used the 'Browse My Refs' form on the main page ('index.php') for searching...
		{
			$query = extractFormElementsBrowseMyRefs($showLinks, $loginEmail, $userID);
		}

	// --- My Groups Search Form within 'index.php': ---------------------
	elseif ($formType == "groupSearch") // the user used the 'Show My Group' form on the main page ('index.php') or above the query results list (that was produced by 'search.php')
		{
			list($query, $displayType) = extractFormElementsGroup($sqlQuery, $showLinks, $userID, $displayType, $originalDisplayType);
		}

	// --------------------------------------------------------------------

	// this is to support the '$fileVisibilityException' feature from 'ini.inc.php':
	if (eregi("^SELECT", $query) AND ($displayType != "Browse") AND !empty($fileVisibilityException) AND !preg_match("/SELECT.+$fileVisibilityException[0].+FROM/i", $query)) // restrict adding of columns to SELECT queries (so that 'DELETE FROM refs ...' statements won't get modified as well);
	{
		$query = eregi_replace("(, orig_record)?(, serial)?(, file, url, doi, isbn, type)? FROM $tableRefs", ", $fileVisibilityException[0]\\1\\2\\3 FROM $tableRefs",$query); // add column that's given in '$fileVisibilityException'
		$addCounterMax = 1; // this will ensure that the added column won't get displayed within the 'displayColumns()' function
	}
	else
		$addCounterMax = 0;


	// (3) RUN QUERY, (4) DISPLAY EXPORT FILE OR HEADER & RESULTS

	// (3) RUN the query on the database through the connection:
	$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'


	// (4) If the display type is 'Export', display the exported file...
	if (($displayType == "Export") && (empty($headerMsg)))
	{
		// Find out how many rows are available:
		$rowsFound = @ mysql_num_rows($result); // for all other display types, the '$rowsFound' variable is set within function 'seekInMySQLResultsToOffset()' (see below)
		if ($rowsFound > 0) // If there were rows found ...
		{
			generateExport($result, $rowOffset, $showRows, $exportFormat, $exportType, $exportStylesheet, $displayType, $viewType, $userID); // export records using the export format specified in '$exportFormat'

			// For export, we disconnect from the database and exit this php file:
			disconnectFromMySQLDatabase(); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'
			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}
		// else, if nothing was found, we proceed & return the "No records selected..." feedback (thru the 'displayColumns()' function)
	}


	// ...else, display HTML:

	// (4a) DISPLAY header:
	// First, build the appropriate SQL query in order to embed it into the 'your query' URL:
	if ($showLinks == "1")
		$query = eregi_replace(", file, url, doi, isbn, type FROM $tableRefs"," FROM $tableRefs",$query); // strip 'file', 'url', 'doi', 'isbn' & 'type columns from SQL query

	$query = eregi_replace(", serial FROM $tableRefs"," FROM $tableRefs",$query); // strip 'serial' column from SQL query

	$query = eregi_replace(", orig_record FROM $tableRefs"," FROM $tableRefs",$query); // strip 'orig_record' column from SQL query

	if (!empty($fileVisibilityException))
		$query = eregi_replace(", $fileVisibilityException[0] FROM $tableRefs"," FROM $tableRefs",$query); // strip column that's given in '$fileVisibilityException' (defined in 'ini.inc.php')

	if (ereg("(simple|advanced|library|quick)Search", $formType)) // if $formType is "simpleSearch", "advancedSearch", "librarySearch" or "quickSearch" and there is more than one WHERE clause (indicated by '...AND...'):
		$query = eregi_replace('WHERE serial RLIKE "\.\+" AND','WHERE',$query); // strip first WHERE clause (which was added only due to an internal workaround)

	$queryURL = rawurlencode($query); // URL encode SQL query

	if (!eregi("^SELECT", $query)) // for queries other than SELECT queries (e.g. UPDATE, DELETE or INSERT queries that were executed by the admin via use of 'sql_search.php')
		$affectedRows = ($result ? mysql_affected_rows ($connection) : 0); // get the number of rows that were modified (or return 0 if an error occurred)

	// Second, save the generated query URL to a session variable:
	$queryParametersArray = array("sqlQuery"         => $query,
	                              "client"           => $client,
	                              "formType"         => "sqlSearch",
	                              "submit"           => $displayType,
	                              "viewType"         => $viewType,
	                              "showQuery"        => $showQuery,
	                              "showLinks"        => $showLinks,
	                              "showRows"         => $showRows,
	                              "rowOffset"        => $rowOffset,
	                              "wrapResults"      => $wrapResults,
	                              "citeOrder"        => $citeOrder,
	                              "citeStyle"        => $citeStyle,
	                              "exportFormat"     => $exportFormat,
	                              "exportType"       => $exportType,
	                              "exportStylesheet" => $exportStylesheet,
	                              "citeType"         => $citeType,
	                              "headerMsg"        => $headerMsg
	                             );

	saveSessionVariable("oldQuery", $queryParametersArray);

	// Third, find out how many rows are available and (if there were rows found) seek to the current offset:
	// Note that the 'seekInMySQLResultsToOffset()' function will also (re-)assign values to the variables
	// '$rowOffset', '$showRows', '$rowsFound', '$previousOffset', '$nextOffset' and '$showMaxRow'.
	list($result, $rowOffset, $showRows, $rowsFound, $previousOffset, $nextOffset, $showMaxRow) = seekInMySQLResultsToOffset($result, $rowOffset, $showRows, $displayType, $citeType); // function 'seekInMySQLResultsToOffset()' is defined in 'include.inc.php'

	// If the current result set contains multiple records, we save the generated query URL to yet another session variable:
	// (after a record has been successfully added/edited/deleted, this query will be included as a link ["Display previous search results"] in the feedback header message
	//  if the SQL query in 'oldQuery' is different from that one stored in 'oldMultiRecordQuery', i.e. if 'oldQuery' points to a single record)
	if ($rowsFound > 1)
		saveSessionVariable("oldMultiRecordQuery", $queryParametersArray);

	// Fourth, setup an array of arrays holding URL and title information for all RSS/Atom feeds available on this page:
	// (appropriate <link...> tags will be included in the HTML header for every URL specified)
	$rssURLArray = array();

	if (isset($_SESSION['user_permissions']) AND ereg("allow_rss_feeds", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_rss_feeds'...
	{
		// ...extract the 'WHERE' clause from the SQL query to include it within the feed URL:
		$queryWhereClause = extractWHEREclause($query); // function 'extractWHEREclause()' is defined in 'include.inc.php'

		// generate an URL pointing to the RSS/Atom feed that matches the current query:
		$rssURL = generateURL("show.php", $defaultFeedFormat, array("where" => $queryWhereClause), true, $showRows); // function 'generateURL()' is defined in 'include.inc.php', variable '$defaultFeedFormat' is defined in 'ini.inc.php'

		// build a title string that matches the current query:
		// (alternatively we could always use: "records matching current query")
		$rssTitle = "records where " . encodeHTML(explainSQLQuery($queryWhereClause)); // functions 'encodeHTML()' and 'explainSQLQuery()' are defined in 'include.inc.php'

		$rssURLArray[] = array("href"  => $rssURL,
		                       "title" => $rssTitle);
	}

	// Finally, build the appropriate header string (which is required as parameter to the 'showPageHeader()' function):
	if (!isset($_SESSION['HeaderString'])) // if there's no stored message available
	{
		if (!empty($headerMsg)) // if there's a custom header message available, e.g. one that describes who's literature is being displayed...
		{
			// ...we use that string as header message ('$headerMsg' could contain something like: "Literature of **Matthias Steffens**:"):

			// Perform search & replace actions on the provided header message (which will e.g. convert '**...**' to '<b>...</b>' etc):
			// (the array '$transtab_refbase_html' in 'transtab_refbase_html.inc.php' defines which search & replace actions will be employed)
			$HeaderString = searchReplaceText($transtab_refbase_html, encodeHTML($headerMsg), true); // functions 'searchReplaceText()' and 'encodeHTML()' are defined in 'include.inc.php'
		}
		else // provide the default message:
		{
			if (eregi("^SELECT", $query)) // for SELECT queries:
			{
				if ($rowsFound == 1)
				{
					if ($displayType == "Browse")
						$HeaderStringPart = " item ";
					else
						$HeaderStringPart = " record ";
				}
				else
				{
					if ($displayType == "Browse")
						$HeaderStringPart = " items ";
					else
						$HeaderStringPart = " records ";
				}

				$HeaderStringPart .= "found matching ";

				if (isset($_SESSION['user_permissions']) AND ereg("allow_sql_search", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_sql_search'...
					// ...generate a link to 'sql_search.php' with a custom SQL query that matches the current result set & display options:
					$HeaderString = $HeaderStringPart
					              . "<a href=\"sql_search.php?customQuery=1"
					              . "&amp;sqlQuery=" . $queryURL
					              . "&amp;showQuery=" . $showQuery
					              . "&amp;showLinks=" . $showLinks
					              . "&amp;showRows=" . $showRows
					              . "&amp;submit=" . $displayType
					              . "&amp;citeStyle=" . rawurlencode($citeStyle)
					              . "&amp;citeOrder=" . $citeOrder
					              . "\" title=\"modify your current query\">your query</a>";
				else // use of 'sql_search.php' isn't allowed for this user
					$HeaderString = $HeaderStringPart . "your query"; // so we omit the link

				if (isset($_SESSION['user_permissions']) AND ((isset($_SESSION['loginEmail']) AND ereg("(allow_user_queries|allow_rss_feeds)", $_SESSION['user_permissions'])) OR (!isset($_SESSION['loginEmail']) AND ereg("allow_rss_feeds", $_SESSION['user_permissions'])))) // if the 'user_permissions' session variable contains 'allow_rss_feeds' -OR- if logged in, aditionally: 'allow_user_queries':
					$HeaderString .= " (";

				if (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_user_queries", $_SESSION['user_permissions']))) // if a user is logged in AND the 'user_permissions' session variable contains 'allow_user_queries'...
				{
					// ...we'll show a link to save the current query:
					$HeaderString .= "<a href=\"query_manager.php?customQuery=1"
					               . "&amp;sqlQuery=" . $queryURL
					               . "&amp;showQuery=" . $showQuery
					               . "&amp;showLinks=" . $showLinks
					               . "&amp;showRows=" . $showRows
					               . "&amp;displayType=" . $displayType
					               . "&amp;citeStyle=" . rawurlencode($citeStyle)
					               . "&amp;citeOrder=" . $citeOrder
					               . "&amp;viewType=" . $viewType
					               . "\" title=\"save your current query\">save</a>";

					if (isset($_SESSION['user_permissions']) AND ereg("allow_rss_feeds", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_rss_feeds', we'll insert a pipe between the 'save' and 'RSS' links...
						$HeaderString .= " | ";
				}

				if (isset($_SESSION['user_permissions']) AND ereg("allow_rss_feeds", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_rss_feeds'...
				{
					// ...we'll display a link that will generate a dynamic RSS feed for the current query:
					$HeaderString .= "<a href=\"" . $rssURL . "\" title=\"track newly added records matching your current query by subscribing to this RSS feed\">RSS</a>";

					if (isset($_SESSION['loginEmail'])) // if a user is logged in, we'll insert a pipe between the 'RSS' and 'dups' links...
						$HeaderString .= " | ";
				}

				if (isset($_SESSION['loginEmail'])) // if a user is logged in...
					// ...we'll show a link to find any duplicates within the current query results:
					$HeaderString .= "<a href=\"duplicate_search.php?customQuery=1"
					               . "&amp;sqlQuery=" . $queryURL
					               . "&amp;showLinks=" . $showLinks
					               . "&amp;showRows=" . $showRows
					               . "&amp;originalDisplayType=" . $displayType
					               . "\" title=\"find duplicates that match your current query\">dups</a>";

				if (isset($_SESSION['user_permissions']) AND ((isset($_SESSION['loginEmail']) AND ereg("(allow_user_queries|allow_rss_feeds)", $_SESSION['user_permissions'])) OR (!isset($_SESSION['loginEmail']) AND ereg("allow_rss_feeds", $_SESSION['user_permissions'])))) // if the 'user_permissions' session variable contains 'allow_rss_feeds' -OR- if logged in, aditionally: 'allow_user_queries':
					$HeaderString .= ")";

				if ($showQuery == "1")
					$HeaderString .= ":\n<br>\n<br>\n<code>" . encodeHTML($query) . "</code>"; // function 'encodeHTML()' is defined in 'include.inc.php'
				else // $showQuery == "0" or wasn't specified
					$HeaderString .= ":";

				if ($rowsFound > 0)
					$HeaderString = ($rowOffset + 1) . "&#8211;" . $showMaxRow . " of " . $rowsFound . $HeaderString;
				elseif ($rowsFound == 0)
					$HeaderString = $rowsFound . $HeaderString;
				else
					$HeaderString = $HeaderString; // well, this is actually bad coding but I do it for clearity reasons...
			}
			else // for queries other than SELECT queries (e.g. UPDATE, DELETE or INSERT queries that were executed by the admin via use of 'sql_search.php') display the number of rows that were modified:
			{
				if ($affectedRows == 1)
					$HeaderStringPart = " record was ";
				else
					$HeaderStringPart = " records were ";

				$HeaderString = $affectedRows . $HeaderStringPart . "affected by "
				              . "<a href=\"sql_search.php?customQuery=1"
				              . "&amp;sqlQuery=" . $queryURL
				              . "&amp;showQuery=" . $showQuery
				              . "&amp;showLinks=" . $showLinks
				              . "&amp;showRows=" . $showRows
				              . "&amp;submit=" . $displayType
				              . "&amp;citeStyle=" . rawurlencode($citeStyle)
				              . "&amp;citeOrder=" . $citeOrder
				              . "\">your query</a>:";

				if ($showQuery == "1")
					$HeaderString .= "\n<br>\n<br>\n<code>" . encodeHTML($query) . "</code>";
			}
		}
	}
	else
	{
		$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

		// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
		deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	}


	// Now, show the login status:
	showLogin(); // function 'showLogin()' is defined in 'include.inc.php'

	if (!eregi("^cli", $client) AND ($wrapResults != "0") AND (!(($displayType == "Cite") AND (!eregi("^html$", $citeType))) OR ($rowsFound == 0))) // we exclude the HTML page header for citation formats other than HTML if something was found
	{
		// Then, call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
		displayHTMLhead(encodeHTML($officialDatabaseName) . " -- Query Results", "index,follow", "Results from the " . encodeHTML($officialDatabaseName), "", true, "", $viewType, $rssURLArray);
		if ((!eregi("^(Print|Mobile)$", $viewType)) AND (!eregi("^inc", $client))) // Note: we omit the visible header in print/mobile view ('viewType=Print' or 'viewType=Mobile') and for include mechanisms!
			showPageHeader($HeaderString);
	}


	// (4b) DISPLAY results:
	if ($displayType == "Display") // display details for each of the selected records
		displayDetails($result, $rowsFound, $query, $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $wrapResults, $nothingChecked, $citeStyle, $citeOrder, $orderBy, $showMaxRow, $headerMsg, $userID, $displayType, $viewType, $formType);

	elseif ($displayType == "Cite") // build a proper citation for each of the selected records
		generateCitations($result, $rowsFound, $query, $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $wrapResults, $nothingChecked, $citeStyle, $citeOrder, $citeType, $orderBy, $headerMsg, $userID, $viewType);

	else // show all records in columnar style
		displayColumns($result, $rowsFound, $query, $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $wrapResults, $nothingChecked, $citeStyle, $citeOrder, $headerMsg, $userID, $displayType, $viewType, $addCounterMax, $formType);

	// --------------------------------------------------------------------

	// (5) CLOSE CONNECTION
	disconnectFromMySQLDatabase(); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// SHOW THE RESULTS IN AN HTML <TABLE> (columnar layout)
	function displayColumns($result, $rowsFound, $query, $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $wrapResults, $nothingChecked, $citeStyle, $citeOrder, $headerMsg, $userID, $displayType, $viewType, $addCounterMax, $formType)
	{
		global $searchReplaceActionsArray; // these variables are defined in 'ini.inc.php'
		global $databaseBaseURL;
		global $defaultDropDownFieldsEveryone;
		global $defaultDropDownFieldsLogin;
		global $displayResultsFooterDefault;
		global $showLinkTypesInListView;
		global $maximumBrowseLinks;
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		global $loc; // '$loc' is made globally available in 'core.php'

		if (eregi(".+LIMIT *[0-9]+",$query)) // query does contain the 'LIMIT' parameter
			$orderBy = eregi_replace(".+ORDER BY (.+) LIMIT.+","\\1",$query); // extract 'ORDER BY'... parameter (without including any 'LIMIT' parameter)
		else // query does not contain the 'LIMIT' parameter
			$orderBy = eregi_replace(".+ORDER BY (.+)","\\1",$query); // extract 'ORDER BY'... parameter

		if (($formType != "queryResults") OR (($formType == "queryResults") AND !($nothingChecked))) // some checkboxes were marked within the 'queryResults' form (or the request stems from a different script without checkboxes)
		{
			// If the query has results ...
			if ($rowsFound > 0)
			{
				// BEGIN RESULTS HEADER --------------------
				// 1) First, initialize some variables that we'll need later on
				if ($showLinks == "1" AND $displayType != "Browse") // we exclude the Browse view since it has a special type of 'Links' column and the 'file', 'url', 'doi', 'isbn' & 'type columns weren't included in the query
					$CounterMax = 5; // When displaying a 'Links' column truncate the last five columns (i.e., hide the 'file', 'url', 'doi', 'isbn' & 'type columns)
				else
					$CounterMax = 0; // Otherwise don't hide any columns

				// count the number of fields
				$fieldsFound = mysql_num_fields($result);
				if ($displayType != "Browse")
				{
					// hide those last columns that were added by the script and not by the user
					$fieldsToDisplay = $fieldsFound-(2+$CounterMax+$addCounterMax); // (2+$CounterMax) -> $CounterMax is increased by 2 in order to hide the 'orig_record' & 'serial' columns (which were added to make checkboxes & dup warning work)
																					// $addCounterMax is set to 1 when the field given in '$fileVisibilityException[0]' (defined in 'ini.inc.php') was added to the query, otherwise '$addCounterMax = 0'
				}
				else // for Browse view the 'orig_record' & 'serial' columns weren't included in the query
					$fieldsToDisplay = $fieldsFound;

				// Calculate the number of all visible columns (which is needed as colspan value inside some TD tags)
				if ($showLinks == "1")
					$NoColumns = (1+$fieldsToDisplay+1); // add checkbox & Links column
				else
					$NoColumns = (1+$fieldsToDisplay); // add checkbox column

				// Save the current List view query to a session variable:
				saveSessionVariable("lastListViewQuery", $query);


				// Note: we omit the 'Search Within Results' form in print/mobile view! ('viewType=Print' or 'viewType=Mobile')
				if (!eregi("^(Print|Mobile)$", $viewType))
				{
					if ($displayType == "Browse")
						$selectedField = preg_replace("/^SELECT (\w+).*/i","\\1",$query); // extract the field that's currently used in Browse view (so that we can re-select it in the drop-downs of the 'refineSearch' and 'displayOptions' forms)
					else
						$selectedField = "author"; // otherwise we'll always selected the 'author' field by default

					// Map MySQL field names to localized column names:
					$fieldNamesArray = mapFieldNames(true); // function 'mapFieldNames()' is defined in 'include.inc.php'
					$localizedDropDownFieldsArray = array();

					if (isset($_SESSION['loginEmail']) AND !empty($defaultDropDownFieldsLogin)) // if a user is logged in -AND- there were any additional fields specified...
						$dropDownFieldsArray = array_merge($defaultDropDownFieldsEveryone, $defaultDropDownFieldsLogin); // ...add these additional fields to the list of fields visible in the dropdown menus of the results header
					else
						$dropDownFieldsArray = $defaultDropDownFieldsEveryone;

					foreach ($dropDownFieldsArray as $field)
					{
						if (isset($fieldNamesArray[$field]))
							$localizedDropDownFieldsArray[$field] = $fieldNamesArray[$field];
						else // no localized field name exists, so we use the original field name
							$localizedDropDownFieldsArray[$field] = $field;
					}

					// 2) Build a TABLE with forms containing options to show the user's groups, refine the search results or change the displayed columns:
					//    TODO for 2b+2c: should we allow users to choose via the web interface which columns are included in the popup menus?

					//    2a) Build a FORM with a popup containing the user's groups:
					$formElementsGroup = buildGroupSearchElements("search.php", $queryURL, $query, $showQuery, $showLinks, $showRows, $displayType); // function 'buildGroupSearchElements()' is defined in 'include.inc.php'

					//    2b) Build a FORM containing options to refine the search results:
					//        Call the 'buildRefineSearchElements()' function (defined in 'include.inc.php') which does the actual work:
					$formElementsRefine = buildRefineSearchElements("search.php", $queryURL, $showQuery, $showLinks, $showRows, $localizedDropDownFieldsArray, $selectedField, $displayType);

					//    2c) Build a FORM containing display options (show/hide columns or change the number of records displayed per page):
					//        Call the 'buildDisplayOptionsElements()' function (defined in 'include.inc.php') which does the actual work:
					$formElementsDisplayOptions = buildDisplayOptionsElements("search.php", $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $localizedDropDownFieldsArray, $selectedField, $fieldsToDisplay, $displayType);

					echo displayResultsHeader("search.php", $formElementsGroup, $formElementsRefine, $formElementsDisplayOptions); // function 'displayResultsHeader()' is defined in 'results_header.inc.php'
				}


				//    and insert a divider line (which separates the 'Search Within Results' form from the browse links & results data below):
				if (!eregi("^(Print|Mobile)$", $viewType)) // Note: we omit the divider line in print/mobile view! ('viewType=Print' or 'viewType=Mobile')
					echo "\n<hr class=\"resultsheader\" align=\"center\" width=\"93%\">";

				// 3) Build a TABLE with links for "previous" & "next" browsing, as well as links to intermediate pages
				//    call the 'buildBrowseLinks()' function (defined in 'include.inc.php'):
				$BrowseLinks = buildBrowseLinks("search.php", $query, $NoColumns, $rowsFound, $showQuery, $showLinks, $showRows, $rowOffset, $previousOffset, $nextOffset, $maximumBrowseLinks, "sqlSearch", $displayType, $citeStyle, $citeOrder, $orderBy, $headerMsg, $viewType);
				echo $BrowseLinks;


				// 4) Start a FORM
				echo "\n<form action=\"search.php\" method=\"GET\" name=\"queryResults\">"
				   . "\n<input type=\"hidden\" name=\"formType\" value=\"queryResults\">"
				   . "\n<input type=\"hidden\" name=\"submit\" value=\"Cite\">" // provide a default value for the 'submit' form tag (then, if any form element is selected, hitting <enter> will act as if the user clicked the 'Cite' button)
				   . "\n<input type=\"hidden\" name=\"orderBy\" value=\"" . rawurlencode($orderBy) . "\">" // embed the current ORDER BY parameter so that it can be re-applied when displaying details
				   . "\n<input type=\"hidden\" name=\"showQuery\" value=\"$showQuery\">" // embed the current value of '$showQuery' so that it's available on 'display details' (batch display) & 'cite'
				   . "\n<input type=\"hidden\" name=\"showLinks\" value=\"$showLinks\">" // embed the current value of '$showLinks' so that it's available on 'display details' (batch display) & 'cite'
				   . "\n<input type=\"hidden\" name=\"showRows\" value=\"$showRows\">" // embed the current value of '$showRows' so that it's available on 'display details' (batch display) & 'cite'
				   . "\n<input type=\"hidden\" name=\"rowOffset\" value=\"$rowOffset\">" // embed the current value of '$rowOffset' so that it can be re-applied after the user pressed either of the 'Add', 'Remove', 'Remember' or 'Forget' buttons within the 'queryResults' form
				   // Note: the inclusion of '$rowOffset' here is only meant to support reloading of the same results page again after a user clicked the 'Add', 'Remove', 'Remember' or 'Forget' buttons
				   //       However, '$rowOffset' MUST NOT be set if the user clicked the 'Display' or 'Cite' button! Therefore we'll trap for this case at the top of the script.
				   . "\n<input type=\"hidden\" name=\"sqlQuery\" value=\"$queryURL\">"; // embed the current sqlQuery so that it can be re-applied after the user pressed either of the 'Add', 'Remove', 'Remember' or 'Forget' buttons within the 'queryResults' form


				// 5) And start a TABLE, with column headers
				echo "\n<table id=\"columns\" class=\"results\" align=\"center\" border=\"0\" cellpadding=\"9\" cellspacing=\"0\" width=\"95%\" summary=\"This table holds the database results for your query\">";

				//    for the column headers, start a TABLE ROW ...
				echo "\n<tr>";

				// ... print a marker ('x') column (which will hold the checkboxes within the results part)
				if (!eregi("^(Print|Mobile)$", $viewType)) // Note: we omit the marker column in print/mobile view! ('viewType=Print' or 'viewType=Mobile')
					echo "\n\t<th align=\"left\" valign=\"top\">&nbsp;</th>";

				// for each of the attributes in the result set...
				for ($i=0; $i<$fieldsToDisplay; $i++)
				{
					// ... and print out each of the attribute names
					// in that row as a separate TH (Table Header)...
					$HTMLbeforeLink = "\n\t<th align=\"left\" valign=\"top\">"; // start the table header tag
					$HTMLafterLink = "</th>"; // close the table header tag
					// call the 'buildFieldNameLinks()' function (defined in 'include.inc.php'), which will return a properly formatted table header tag holding the current field's name
					// as well as the URL encoded query with the appropriate ORDER clause:
					$tableHeaderLink = buildFieldNameLinks("search.php", $query, "", $result, $i, $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, "sqlSearch", $displayType, "", "", $viewType);
					echo $tableHeaderLink; // print the attribute name as link
				 }

				if (($showLinks == "1") AND ($displayType != "Browse"))
				{
					$newORDER = ("ORDER BY url DESC, doi DESC"); // Build the appropriate ORDER BY clause to facilitate sorting by Links column

					$HTMLbeforeLink = "\n\t<th align=\"left\" valign=\"top\">"; // start the table header tag
					$HTMLafterLink = "</th>"; // close the table header tag
					// call the 'buildFieldNameLinks()' function (defined in 'include.inc.php'), which will return a properly formatted table header tag holding the current field's name
					// as well as the URL encoded query with the appropriate ORDER clause:
					$tableHeaderLink = buildFieldNameLinks("search.php", $query, $newORDER, $result, $i, $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, "sqlSearch", $displayType, $loc["Links"], "url", $viewType);
					echo $tableHeaderLink; // print the attribute name as link
				}
				elseif (($showLinks == "1") AND ($displayType == "Browse"))
				{
					echo "\n\t<th align=\"left\" valign=\"top\">" // start the table header tag
						. "Show" // in Browse view we simply provide a static column header
						. "</th>"; // close the table header tag
				}

				// Finish the row
				echo "\n</tr>";
				// END RESULTS HEADER ----------------------


				// BEGIN RESULTS DATA COLUMNS --------------
				// Fetch one page of results (or less if on the last page)
				// (i.e., upto the limit specified in $showRows) fetch a row into the $row array and ...
				for ($rowCounter=0; (($rowCounter < $showRows) && ($row = @ mysql_fetch_array($result))); $rowCounter++)
				{
					if (is_integer($rowCounter / 2)) // if we currently are at an even number of rows
						$rowClass = "even";
					else
						$rowClass = "odd";

					// ... start a TABLE ROW ...
					echo "\n<tr class=\"" . $rowClass . "\">";

					// ... print a column with a checkbox
					if (!eregi("^(Print|Mobile)$", $viewType)) // Note: we omit the marker column in print/mobile view! ('viewType=Print' or 'viewType=Mobile')
					{
						echo "\n\t<td align=\"center\" valign=\"top\" width=\"10\">";

						// print a checkbox form element:
						if (!isset($displayResultsFooterDefault[$displayType]) OR (isset($displayResultsFooterDefault[$displayType]) AND ($displayResultsFooterDefault[$displayType] != "hidden")))
						{
							echo "\n\t\t<input type=\"checkbox\" name=\"marked[]\" value=\"";
							if ($displayType == "Browse")
								echo $row[0];
							else
								echo $row["serial"];
							echo "\" title=\"select this record\">";
						}

						if (!empty($row["orig_record"]))
						{
							if (!isset($displayResultsFooterDefault[$displayType]) OR (isset($displayResultsFooterDefault[$displayType]) AND ($displayResultsFooterDefault[$displayType] != "hidden")))
								echo "\n\t\t<br>";

							if ($row["orig_record"] < 0)
								echo "\n\t\t<img src=\"img/ok.gif\" alt=\"(original)\" title=\"original record\" width=\"14\" height=\"16\" hspace=\"0\" border=\"0\">";
							else // $row["orig_record"] > 0
								echo "\n\t\t<img src=\"img/caution.gif\" alt=\"(duplicate)\" title=\"duplicate record\" width=\"5\" height=\"16\" hspace=\"0\" border=\"0\">";
						}

						if ($displayType != "Browse")
						{
							// add <abbr> block which works as a microformat that allows applications to identify objects on web pages; see <http://unapi.info/specs/> for more info
							echo "\n\t\t<div class=\"unapi\"><abbr class=\"unapi-id\" title=\"" . $databaseBaseURL . "show.php?record=" . $row["serial"] . "\"></abbr></div>";
						}

						echo "\n\t</td>";
					}

					// ... and print out each of the attributes
					// in that row as a separate TD (Table Data)
					for ($i=0; $i<$fieldsToDisplay; $i++)
					{
						// fetch the current attribute name:
						$orig_fieldname = getMySQLFieldInfo($result, $i, "name"); // function 'getMySQLFieldInfo()' is defined in 'include.inc.php'

						if (!empty($row[$i]))
						{
							if (ereg("^(thesis|approved|marked|copy|selected)$", $orig_fieldname)) // for the fields 'thesis', 'approved', 'marked', 'copy' and 'selected', we'll use localized field values (e.g., in case of german we display 'ja' instead of 'yes', etc)
								$encodedRowAttribute = ereg_replace(".+", $loc[$row[$i]], $row[$i]); // note that the locales in '$loc' are already HTML encoded
							else
								$encodedRowAttribute = encodeHTML($row[$i]); // HTML encode higher ASCII characters (we write the data into a new variable since we still need unencoded data when including them into a link for Browse view)
						}
						else
							$encodedRowAttribute = "";

						if (($displayType == "Browse") AND ($i == 0)) // in Browse view we save the first field name to yet another variable (since it'll be needed when generating correct queries in the Links column)
							$browseFieldName = $orig_fieldname;

						// apply search & replace 'actions' to all fields that are listed in the 'fields' element of the arrays contained in '$searchReplaceActionsArray' (which is defined in 'ini.inc.php'):
						foreach ($searchReplaceActionsArray as $fieldActionsArray)
							if (in_array($orig_fieldname, $fieldActionsArray['fields']))
								$encodedRowAttribute = searchReplaceText($fieldActionsArray['actions'], $encodedRowAttribute, true); // function 'searchReplaceText()' is defined in 'include.inc.php'

						echo "\n\t<td valign=\"top\">" . $encodedRowAttribute . "</td>";
					}

					// embed appropriate links (if available):
					if (($showLinks == "1") AND ($displayType != "Browse")) // we exclude Browse view since it will need a different type of link query (see below)
					{
						echo "\n\t<td valign=\"top\">";

						// print out available links:
						// for List view, we'll use the '$showLinkTypesInListView' array that's defined in 'ini.inc.php'
						// to specify which links shall be displayed (if available and if 'showLinks == 1')
						// (for links of type DOI/URL/ISBN/XREF, only one link will be printed; order of preference: DOI, URL, ISBN, XREF)
						echo printLinks($showLinkTypesInListView, $row, $showQuery, $showLinks, $wrapResults, $userID, $viewType, $orderBy);

						echo "\n\t</td>";
					}

					// for Browse view we'll incorporate links that will show all records whose field (given in '$orig_fieldname') matches the current value (given in '$row[0]'):
					elseif (($showLinks == "1") AND ($displayType == "Browse"))
					{
						// ...extract the 'WHERE' clause from the SQL query to include it within the link URL:
						$queryWhereClause = extractWHEREclause($query); // function 'extractWHEREclause()' is defined in 'include.inc.php'
						$queryWhereClause = eregi_replace('^serial RLIKE "\.\+"','',$queryWhereClause); // strip generic WHERE clause if present

						// Construct the SQL query:
						// TODO: build the complete SQL query first (using functions 'buildFROMclause()' and 'buildORDERclause()'), then rawurlencode and add to link
						$browseViewShowRecordsQuery = buildSELECTclause("List", $showLinks, "", false, false); // function 'buildSELECTclause()' is defined in 'include.inc.php'

						echo "\n\t<td valign=\"top\">";

						echo "\n\t\t<a href=\"search.php?sqlQuery=" . rawurlencode($browseViewShowRecordsQuery);

						if (isset($_SESSION['loginEmail']) AND eregi("^(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)$", $browseFieldName)) // if a user is logged in and a user specific field is used in Browse view, we add the 'LEFT JOIN...' part to the 'FROM' clause:
							echo "%20FROM%20" . $tableRefs . "%20LEFT%20JOIN%20" . $tableUserData . "%20ON%20serial%20%3D%20record_id%20AND%20user_id%20%3D%20" . $userID . "%20";
						else
							echo "%20FROM%20" . $tableRefs . "%20";

						echo "WHERE%20";

						if (!empty($queryWhereClause))
							echo rawurlencode($queryWhereClause) . "%20AND%20";

						echo $browseFieldName . "%20";

						if (!empty($row[0]))
							echo "=%20%22" . rawurlencode($row[0]) . "%22%20";
						else
							echo "IS%20NULL%20";

						echo "ORDER%20BY%20author%2C%20year%20DESC%2C%20publication" // use the default ORDER BY clause
						   . "&amp;formType=sqlSearch"
						   . "&amp;showQuery=" . $showQuery
						   . "&amp;showLinks=" . $showLinks
						   . "&amp;showRows=" . $showRows
						   . "&amp;submit="
						   . "&amp;viewType=" . $viewType
						   . "\"><img src=\"img/details.gif\" alt=\"records\" title=\"show records\" width=\"9\" height=\"17\" hspace=\"0\" border=\"0\"></a>&nbsp;&nbsp;";

						echo "\n\t</td>";
					}

					// Finish the row
					echo "\n</tr>";
				}
				// Finish the table
				echo "\n</table>";
				// END RESULTS DATA COLUMNS ----------------

				// BEGIN RESULTS FOOTER --------------------
				// Note: we omit the results footer in print/mobile view! ('viewType=Print' or 'viewType=Mobile')
				if (!eregi("^(Print|Mobile)$", $viewType))
				{
					// Again, insert the (already constructed) BROWSE LINKS
					// (i.e., a TABLE with links for "previous" & "next" browsing, as well as links to intermediate pages)
					echo $BrowseLinks;

					// Build a results footer with form elements to cite, group or export all/selected records:
					if (!isset($displayResultsFooterDefault[$displayType]) OR (isset($displayResultsFooterDefault[$displayType]) AND ($displayResultsFooterDefault[$displayType] != "hidden")))
					{
						if (isset($_SESSION['user_permissions']) AND ((isset($_SESSION['loginEmail']) AND ereg("(allow_cite|allow_user_groups|allow_export|allow_batch_export)", $_SESSION['user_permissions'])) OR (!isset($_SESSION['loginEmail']) AND ereg("allow_cite|allow_export|allow_batch_export", $_SESSION['user_permissions'])))) // if the 'user_permissions' session variable does contain any of the following: 'allow_cite' -AND- if logged in, aditionally: 'allow_user_groups', 'allow_export', 'allow_batch_export'...
							// ...Insert a divider line (which separates the results data from the forms in the footer):
							echo "\n<hr class=\"resultsfooter\" align=\"center\">";

						// Call the 'buildResultsFooter()' function (which does the actual work):
						$ResultsFooter = buildResultsFooter($NoColumns, $showRows, $citeStyle, $displayType);
						echo $ResultsFooter;
					}
				}
				// END RESULTS FOOTER ----------------------

				// Finally, finish the form
				echo "\n</form>";
			}
			else
			{
				// Report that nothing was found:
				$nothingFoundFeedback = nothingFound(false); // This is a clumsy workaround: by pretending that there were some records marked by the user ($nothingChecked = false) we force the 'nothingFound()' function to output "Sorry, but your query didn't produce any results!" instead of "No records selected..."
				echo $nothingFoundFeedback;
			}// end if $rowsFound body
		}
		else // if the user clicked either the 'Add' or the 'Remove' button on a search results page but did not mark some checkboxes in front of the records, we display a "No records selected..." warning:
		{
			// Report that nothing was selected:
			$nothingFoundFeedback = nothingFound($nothingChecked);
			echo $nothingFoundFeedback;
		}
	}

	// --------------------------------------------------------------------

	// SHOW THE RESULTS IN AN HTML <TABLE> (horizontal layout)
	function displayDetails($result, $rowsFound, $query, $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $wrapResults, $nothingChecked, $citeStyle, $citeOrder, $orderBy, $showMaxRow, $headerMsg, $userID, $displayType, $viewType, $formType)
	{
		global $filesBaseURL; // these variables are defined in 'ini.inc.php'
		global $searchReplaceActionsArray;
		global $databaseBaseURL;
		global $fileVisibility;
		global $fileVisibilityException;
		global $displayResultsFooterDefault;
		global $maximumBrowseLinks;
		global $openURLResolver;
		global $isbnURLFormat;

		global $loc; // '$loc' is made globally available in 'core.php'

		if (($formType != "queryResults") OR (($formType == "queryResults") AND !($nothingChecked))) // some checkboxes were marked within the 'queryResults' form (or the request stems from a different script without checkboxes)
		{
			// If the query has results ...
			if ($rowsFound > 0)
			{
				// BEGIN RESULTS HEADER --------------------
				// 1) First, initialize some variables that we'll need later on
				if ($showLinks == "1")
					$CounterMax = 5; // When displaying a 'Links' column truncate the last five columns (i.e., hide the 'file', 'url', 'doi', 'isbn' & 'type columns)
				else
					$CounterMax = 0; // Otherwise don't hide any columns

				if (isset($_SESSION['loginEmail'])) // if a user is logged in...
					$CounterMax = ($CounterMax + 1); // ...we'll also need to hide the 'related' column (which isn't displayed in Details view but is only used to generate a link to related records)

				// count the number of fields
				$fieldsFound = mysql_num_fields($result);
				// hide those last columns that were added by the script and not by the user
				$fieldsToDisplay = $fieldsFound-(2+$CounterMax); // (2+$CounterMax) -> $CounterMax is increased by 2 in order to hide the 'orig_record' & 'serial' columns (which were added to make checkboxes & dup warning work)
				// In summary, when displaying a 'Links' column and with a user being logged in, we hide the following fields: 'related, orig_record, serial, file, url, doi, isbn, type' (i.e., truncate the last eight columns)

				// Calculate the number of all visible columns (which is needed as colspan value inside some TD tags)
				if ($showLinks == "1") // in 'display details' layout, we simply set it to a fixed no of columns:
					$NoColumns = 8; // 8 columns: checkbox, 3 x (field name + field contents), links
				else
					$NoColumns = 7; // 7 columns: checkbox, field name, field contents

				// Save the current Details view query to a session variable:
//				saveSessionVariable("lastDetailsViewQuery", $query);


				// 2) Note: we omit the 'Search Within Results' form when displaying details! (compare with 'displayColumns()' function)


				// 3) Build a TABLE with links for "previous" & "next" browsing, as well as links to intermediate pages
				//    call the 'buildBrowseLinks()' function (defined in 'include.inc.php'):
				$BrowseLinks = buildBrowseLinks("search.php", $query, $NoColumns, $rowsFound, $showQuery, $showLinks, $showRows, $rowOffset, $previousOffset, $nextOffset, $maximumBrowseLinks, "sqlSearch", "Display", $citeStyle, $citeOrder, $orderBy, $headerMsg, $viewType);
				echo $BrowseLinks;


				// 4) Start a FORM
				echo "\n<form action=\"search.php\" method=\"GET\" name=\"queryResults\">"
				   . "\n<input type=\"hidden\" name=\"formType\" value=\"queryResults\">"
				   . "\n<input type=\"hidden\" name=\"submit\" value=\"Cite\">" // provide a default value for the 'submit' form tag (then, if any form element is selected, hitting <enter> will act as if the user clicked the 'Cite' button)
				   . "\n<input type=\"hidden\" name=\"originalDisplayType\" value=\"$displayType\">" // embed the original value of the '$displayType' variable
				   . "\n<input type=\"hidden\" name=\"orderBy\" value=\"" . rawurlencode($orderBy) . "\">" // embed the current ORDER BY parameter so that it can be re-applied when displaying details
				   . "\n<input type=\"hidden\" name=\"showQuery\" value=\"$showQuery\">" // embed the current value of '$showQuery' so that it's available on 'display details' (batch display) & 'cite'
				   . "\n<input type=\"hidden\" name=\"showLinks\" value=\"$showLinks\">" // embed the current value of '$showLinks' so that it's available on 'display details' (batch display) & 'cite'
				   . "\n<input type=\"hidden\" name=\"showRows\" value=\"$showRows\">" // embed the current value of '$showRows' so that it's available on 'display details' (batch display) & 'cite'
				   . "\n<input type=\"hidden\" name=\"rowOffset\" value=\"$rowOffset\">" // embed the current value of '$rowOffset' so that it can be re-applied after the user pressed either of the 'Add', 'Remove', 'Remember' or 'Forget' buttons within the 'queryResults' form
				   // Note: the inclusion of '$rowOffset' here is only meant to support reloading of the same results page again after a user clicked the 'Add', 'Remove', 'Remember' or 'Forget' buttons
				   //       However, '$rowOffset' MUST NOT be set if the user clicked the 'Display' or 'Cite' button! Therefore we'll trap for this case at the top of the script.
				   . "\n<input type=\"hidden\" name=\"sqlQuery\" value=\"$queryURL\">"; // embed the current sqlQuery so that it can be re-applied after the user pressed either of the 'Add', 'Remove', 'Remember' or 'Forget' buttons within the 'queryResults' form


				// 5) And start a TABLE, with column headers
				echo "\n<table id=\"details\" class=\"results\" align=\"center\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\" width=\"95%\" summary=\"This table holds the database results for your query\">";

				//    for the column headers, start a TABLE ROW ...
				echo "\n<tr>";

				// ... print a marker ('x') column (which will hold the checkboxes within the results part)
				if (!eregi("^(Print|Mobile)$", $viewType)) // Note: we omit the marker column in print/mobile view! ('viewType=Print' or 'viewType=Mobile')
					echo "\n\t<th align=\"left\" valign=\"top\">&nbsp;</th>";

				// ... print a record header
				if (($showMaxRow-$rowOffset) == "1") // '$showMaxRow-$rowOffset' gives the number of displayed records for a particular page) // '($rowsFound == "1" || $showRows == "1")' wouldn't trap the case of a single record on the last of multiple results pages!
						$recordHeader = $loc["Record"]; // use singular form if there's only one record to display
				else
						$recordHeader = $loc["Records"]; // use plural form if there are multiple records to display
				echo "\n\t<th align=\"left\" valign=\"top\" colspan=\"6\">$recordHeader</th>";

				if ($showLinks == "1")
					{
						$newORDER = ("ORDER BY url DESC, doi DESC"); // Build the appropriate ORDER BY clause to facilitate sorting by Links column

						$HTMLbeforeLink = "\n\t<th align=\"left\" valign=\"top\">"; // start the table header tag
						$HTMLafterLink = "</th>"; // close the table header tag
						// call the 'buildFieldNameLinks()' function (defined in 'include.inc.php'), which will return a properly formatted table header tag holding the current field's name
						// as well as the URL encoded query with the appropriate ORDER clause:
						$tableHeaderLink = buildFieldNameLinks("search.php", $query, $newORDER, $result, "", $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, "sqlSearch", "Display", $loc["Links"], "url", $viewType);
						echo $tableHeaderLink; // print the attribute name as link
					}

				// Finish the row
				echo "\n</tr>";
				// END RESULTS HEADER ----------------------

				// BEGIN RESULTS DATA COLUMNS --------------
				// Fetch one page of results (or less if on the last page)
				// (i.e., upto the limit specified in $showRows) fetch a row into the $row array and ...
				for ($rowCounter=0; (($rowCounter < $showRows) && ($row = @ mysql_fetch_array($result))); $rowCounter++)
				{
					// ... print out each of the attributes
					// in that row as a separate TR (Table Row)
					$recordData = ""; // make sure that buffer variable is empty

					for ($i=0; $i<$fieldsToDisplay; $i++)
						{
							// fetch the current attribute name:
							$orig_fieldname = getMySQLFieldInfo($result, $i, "name"); // function 'getMySQLFieldInfo()' is defined in 'include.inc.php'

							// for all the fields specified (-> all fields to the left):
							if (ereg("^(author|title|year|volume|corporate_author|address|keywords|abstract|publisher|language|series_editor|series_volume|issn|area|notes|location|call_number|marked|user_keys|user_notes|user_groups|created_date|modified_date)$", $orig_fieldname))
								{
									$recordData .= "\n<tr>"; // ...start a new TABLE row

									if (!eregi("^(Print|Mobile)$", $viewType)) // Note: we omit the marker column in print/mobile view! ('viewType=Print' or 'viewType=Mobile')
									{
										if ($i == 0) // ... print a column with a checkbox if it's the first row of attribute data:
											$recordData .= "\n\t<td align=\"left\" valign=\"top\" width=\"10\"><input type=\"checkbox\" name=\"marked[]\" value=\"" . $row["serial"] . "\" title=\"select this record\"></td>";
										else // ... otherwise simply print an empty TD tag:
											$recordData .= "\n\t<td valign=\"top\" width=\"10\">&nbsp;</td>";
									}
								}

							// ... and print out each of the ATTRIBUTE NAMES:
							// in that row as a bold link...
							if (ereg("^(author|title|type|year|publication|abbrev_journal|volume|issue|pages|call_number|serial)$", $orig_fieldname)) // print a colored background (grey, by default)
								{
									$HTMLbeforeLink = "\n\t<td valign=\"top\" width=\"75\" class=\"mainfieldsbg\"><b>"; // start the (bold) TD tag
									$HTMLafterLink = "</b></td>"; // close the (bold) TD tag
								}
							elseif (ereg("^(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key)$", $orig_fieldname)) // print a colored background (light orange, by default) for all the user specific fields
								{
									$HTMLbeforeLink = "\n\t<td valign=\"top\" width=\"75\" class=\"userfieldsbg\"><b>"; // start the (bold) TD tag
									$HTMLafterLink = "</b></td>"; // close the (bold) TD tag
								}
							else // no colored background (by default)
								{
									$HTMLbeforeLink = "\n\t<td valign=\"top\" width=\"75\" class=\"otherfieldsbg\"><b>"; // start the (bold) TD tag
									$HTMLafterLink = "</b></td>"; // close the (bold) TD tag
								}
							// call the 'buildFieldNameLinks()' function (defined in 'include.inc.php'), which will return a properly formatted table data tag holding the current field's name
							// as well as the URL encoded query with the appropriate ORDER clause:
							$recordData .= buildFieldNameLinks("search.php", $query, "", $result, $i, $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, "sqlSearch", "Display", "", "", $viewType);

							// print the ATTRIBUTE DATA:
							// first, calculate the correct colspan value for all the fields specified:
							if (ereg("^(author|address|keywords|abstract|location|user_keys)$", $orig_fieldname))
								$ColspanFields = 5; // supply an appropriate colspan value
							elseif (ereg("^(title|corporate_author|notes|call_number|user_notes|user_groups)$", $orig_fieldname))
								$ColspanFields = 3; // supply an appropriate colspan value

							// then, start the TD tag, for all the fields specified:
							if (ereg("^(author|title|corporate_author|address|keywords|abstract|notes|location|call_number|user_keys|user_notes|user_groups)$", $orig_fieldname)) // WITH colspan attribute:
								if (ereg("^(author|title|call_number)$", $orig_fieldname)) // print a colored background (grey, by default)
									$recordData .= "\n\t<td valign=\"top\" colspan=\"$ColspanFields\" class=\"mainfieldsbg\">"; // ...with colspan attribute & appropriate value
								elseif (ereg("^(user_keys|user_notes|user_file|user_groups)$", $orig_fieldname)) // print a colored background (light orange, by default) for all the user specific fields
									$recordData .= "\n\t<td valign=\"top\" colspan=\"$ColspanFields\" class=\"userfieldsbg\">"; // ...with colspan attribute & appropriate value
								else // no colored background (by default)
									$recordData .= "\n\t<td valign=\"top\" colspan=\"$ColspanFields\" class=\"otherfieldsbg\">"; // ...with colspan attribute & appropriate value

							else // for all other fields WITHOUT colspan attribute:
								if (ereg("^(type|year|publication|abbrev_journal|volume|issue|pages|serial)$", $orig_fieldname)) // print a colored background (grey, by default)
									$recordData .= "\n\t<td valign=\"top\" class=\"mainfieldsbg\">"; // ...without colspan attribute
								elseif (ereg("^(marked|copy|selected|user_file|cite_key)$", $orig_fieldname)) // print a colored background (light orange, by default) for all the user specific fields
									$recordData .= "\n\t<td valign=\"top\" class=\"userfieldsbg\">"; // ...without colspan attribute
								else // no colored background (by default)
									$recordData .= "\n\t<td valign=\"top\" class=\"otherfieldsbg\">"; // ...without colspan attribute

							if (ereg("^(author|title|year)$", $orig_fieldname)) // print author, title & year fields in bold
								$recordData .= "<b>";

							if (!empty($row[$i]))
							{
								if (ereg("^(thesis|approved|marked|copy|selected)$", $orig_fieldname)) // for the fields 'thesis', 'approved', 'marked', 'copy' and 'selected', we'll use localized field values (e.g., in case of german we display 'ja' instead of 'yes', etc)
									$row[$i] = ereg_replace(".+", $loc[$row[$i]], $row[$i]); // note that the locales in '$loc' are already HTML encoded
								else
									$row[$i] = encodeHTML($row[$i]); // HTML encode higher ASCII characters
							}

							if (ereg("^abstract$", $orig_fieldname)) // for the 'abstract' field, transform newline ('\n') characters into <br> tags
								$row[$i] = ereg_replace("\n", "<br>", $row[$i]);

							// apply search & replace 'actions' to all fields that are listed in the 'fields' element of the arrays contained in '$searchReplaceActionsArray' (which is defined in 'ini.inc.php'):
							foreach ($searchReplaceActionsArray as $fieldActionsArray)
								if (in_array($orig_fieldname, $fieldActionsArray['fields']))
									$row[$i] = searchReplaceText($fieldActionsArray['actions'], $row[$i], true); // function 'searchReplaceText()' is defined in 'include.inc.php'

							$recordData .= $row[$i]; // print the attribute data

							if (ereg("^(author|title|year)$", $orig_fieldname))
								$recordData .= "</b>";

							$recordData .= "</td>"; // finish the TD tag

							// for all the fields specified (-> all fields to the right):
							if (ereg("^(author|type|abbrev_journal|pages|thesis|address|keywords|abstract|editor|orig_title|abbrev_series_title|edition|medium|conference|approved|location|serial|selected|user_keys|user_file|cite_key|created_by|modified_by)$", $orig_fieldname))
								{
									if ($showLinks == "1")
										{
											// ...embed appropriate links (if available):
											if ($i == 0) // ... print a column with links if it's the first row of attribute data:
											{
												$recordData .= "\n\t<td valign=\"top\" width=\"50\" rowspan=\"2\">"; // note that this table cell spans the next row!

												$linkArray = array(); // initialize array variable that will hold all available links

												if (isset($_SESSION['user_permissions']) AND ereg("allow_edit", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_edit'...
													// ... display a link that opens the edit form for this record:
													$linkArray[] = "\n\t\t<a href=\"record.php"
													             . "?serialNo=" . $row["serial"]
													             . "&amp;recordAction=edit"
													             . "\"><img src=\"img/edit.gif\" alt=\"edit\" title=\"edit record\" width=\"11\" height=\"17\" hspace=\"0\" border=\"0\"></a>";

												// show a link to any corresponding FILE if one of the following conditions is met:
												// - the variable '$fileVisibility' (defined in 'ini.inc.php') is set to 'everyone'
												// - the variable '$fileVisibility' is set to 'login' AND the user is logged in
												// - the variable '$fileVisibility' is set to 'user-specific' AND the 'user_permissions' session variable contains 'allow_download'
												// - the array variable '$fileVisibilityException' (defined in 'ini.inc.php') contains a pattern (in array element 1) that matches the contents of the field given (in array element 0)
												if ($fileVisibility == "everyone" OR ($fileVisibility == "login" AND isset($_SESSION['loginEmail'])) OR ($fileVisibility == "user-specific" AND (isset($_SESSION['user_permissions']) AND ereg("allow_download", $_SESSION['user_permissions']))) OR (!empty($fileVisibilityException) AND preg_match($fileVisibilityException[1], $row[$fileVisibilityException[0]])))
												{
													if (!empty($row["file"]))// if the 'file' field is NOT empty
													{
														if (isset($_SESSION['user_permissions']) AND ereg("allow_edit", $_SESSION['user_permissions']))
															$prefix = "&nbsp;";
														else
															$prefix = "";

														if (ereg("^(https?|ftp|file)://", $row["file"])) // if the 'file' field contains a full URL (starting with "http://", "https://", "ftp://" or "file://")
															$URLprefix = ""; // we don't alter the URL given in the 'file' field
														else // if the 'file' field contains only a partial path (like 'polarbiol/10240001.pdf') or just a file name (like '10240001.pdf')
															$URLprefix = $filesBaseURL; // use the base URL of the standard files directory as prefix ('$filesBaseURL' is defined in 'ini.inc.php')

														if (eregi("\.pdf$", $row["file"])) // if the 'file' field contains a link to a PDF file
															$linkArray[] = $prefix . "\n\t\t<a href=\"" . $URLprefix . $row["file"] . "\"><img src=\"img/file_PDF.gif\" alt=\"pdf\" title=\"download PDF file\" width=\"17\" height=\"17\" hspace=\"0\" border=\"0\"></a>"; // display a PDF file icon as download link
														else
															$linkArray[] = $prefix . "\n\t\t<a href=\"" . $URLprefix . $row["file"] . "\"><img src=\"img/file.gif\" alt=\"file\" title=\"download file\" width=\"11\" height=\"15\" hspace=\"0\" border=\"0\"></a>"; // display a generic file icon as download link
													}
												}

												// generate a link from the URL field:
												if (!empty($row["url"])) // 'htmlentities()' is used to convert any '&' into '&amp;'
													$linkArray[] = "\n\t\t<a href=\"" . encodeHTML($row["url"]) . "\"><img src=\"img/www.gif\" alt=\"url\" title=\"goto web page\" width=\"17\" height=\"20\" hspace=\"0\" border=\"0\"></a>";

												// generate a link from the DOI field:
												if (!empty($row["doi"]))
													$linkArray[] = "\n\t\t<a href=\"http://dx.doi.org/" . $row["doi"] . "\"><img src=\"img/doi.gif\" alt=\"doi\" title=\"goto web page (via DOI)\" width=\"17\" height=\"20\" hspace=\"0\" border=\"0\"></a>";

												// generate a link from the RELATED field:
												if (isset($_SESSION['loginEmail'])) // if a user is logged in, show a link to any related records (if available):
												{
													if (!empty($row["related"]))
													{
														$relatedRecordsLink = buildRelatedRecordsLink($row["related"], $userID);

														$linkArray[] = "\n\t\t<a href=\"" . $relatedRecordsLink . "\"><img src=\"img/related.gif\" alt=\"related\" title=\"display related records\" width=\"19\" height=\"16\" hspace=\"0\" border=\"0\"></a>";
													}
												}

												// if an ISBN number exists for the current record, provide a link to an ISBN resolver:
												if (!empty($isbnURLFormat) AND !empty($row["isbn"]))
												{
													// this is a stupid hack that maps the names of the '$row' array keys to those used
													// by the '$formVars' array (which is required by function 'parsePlaceholderString()')
													// (eventually, the '$formVars' array should use the MySQL field names as names for its array keys)
													$formVars = buildFormVarsArray($row); // function 'buildFormVarsArray()' is defined in 'include.inc.php'

													// auto-generate an ISBN link according to the naming scheme given in '$isbnURLFormat' (in 'ini.inc.php'):
													$isbnURL = parsePlaceholderString($formVars, $isbnURLFormat, ""); // function 'parsePlaceholderString()' is defined in 'include.inc.php'

													$encodedURL = encodeHTML($isbnURL); // 'htmlentities()' is used to convert higher ASCII chars into its entities and any '&' into '&amp;'
													$encodedURL = str_replace(" ", "%20", $encodedURL); // ensure that any spaces are also properly urlencoded

													if (!empty($isbnURL))
														$linkArray[] = "\n\t\t<a href=\"" . $encodedURL . "\"><img src=\"img/isbn.gif\" alt=\"isbn\" title=\"find book details (via ISBN)\" width=\"17\" height=\"20\" hspace=\"0\" border=\"0\"></a>";
												}

												// provide a link to an OpenURL resolver:
												if (!empty($openURLResolver))
												{
													$openURL = openURL($row); // function 'openURL()' is defined in 'openurl.inc.php'
													$linkArray[] = "\n\t\t<a href=\"" . $openURL . "\"><img src=\"img/xref.gif\" alt=\"openurl\" title=\"find record details (via OpenURL)\" width=\"18\" height=\"20\" hspace=\"0\" border=\"0\"></a>";
												}

												// insert COinS (ContextObjects in Spans):
												$linkArray[] = "\n\t\t" . coins($row); // function 'coins()' is defined in 'openurl.inc.php'

												// merge links with delimiters appropriate for display in the Links column:
												$recordData .=  mergeLinks($linkArray);

												$recordData .= "\n\t</td>";
											}

											// ... for the second row (which consists of the second and third field), we don't print any table column tag at all since the links (printed in the first row) span this second row!
											elseif ($i > 3) // ... for the third row up to the last row, simply print an empty TD tag:
												$recordData .= "\n\t<td valign=\"top\" width=\"50\">&nbsp;</td>";
										}

									$recordData .= "\n</tr>"; // ...and finish the row
								}
						}

					if (!eregi("^(Print|Mobile)$", $viewType)) // supply an appropriate colspan value
						$ColspanFields = $NoColumns;
					else // print view (i.e., no marker column)
						$ColspanFields = ($NoColumns - 1);

					// Print out an URL that links directly to this record:
					$recordData .= "\n<tr>" // start a new TR (Table Row)
					             . "\n\t<td colspan=\"$ColspanFields\" align=\"center\" class=\"smaller\"><a href=\"" . $databaseBaseURL . "show.php?record=" . $row["serial"] . "\" title=\"copy this URL to directly link to this record\">Permanent link to this record</a>"
					             . "<div class=\"unapi\"><abbr class=\"unapi-id\" title=\"" . $databaseBaseURL . "show.php?record=" . $row["serial"] . "\"></abbr></div></td>" // re <abbr> tag see <http://unapi.info/specs/>
					             . "\n</tr>";

					// Append a divider line if it's not the last (or only) record on the page:
					if ((($rowCounter+1) < $showRows) && (($rowCounter+1) < $rowsFound))
						if (!(($showMaxRow == $rowsFound) && (($rowCounter+1) == ($showMaxRow-$rowOffset)))) // if we're NOT on the *last* page processing the *last* record... ('$showMaxRow-$rowOffset' gives the number of displayed records for a particular page)
							$recordData .= "\n<tr>"
							             . "\n\t<td colspan=\"$ColspanFields\">&nbsp;</td>"
							             . "\n</tr>"
							             . "\n<tr>"
							             . "\n\t<td colspan=\"$ColspanFields\"><hr class=\"results\" align=\"left\" width=\"100%\"></td>"
							             . "\n</tr>"
							             . "\n<tr>"
							             . "\n\t<td colspan=\"$ColspanFields\">&nbsp;</td>"
							             . "\n</tr>";

					echo $recordData;
				}
				// Finish the table
				echo "\n</table>";
				// END RESULTS DATA COLUMNS ----------------

				// BEGIN RESULTS FOOTER --------------------
				// Note: we omit the results footer in print/mobile view! ('viewType=Print' or 'viewType=Mobile')
				if (!eregi("^(Print|Mobile)$", $viewType))
				{
					// Again, insert the (already constructed) BROWSE LINKS
					// (i.e., a TABLE with links for "previous" & "next" browsing, as well as links to intermediate pages)
					echo $BrowseLinks;

					// Build a results footer with form elements to cite, group or export all/selected records:
					if (!isset($displayResultsFooterDefault[$displayType]) OR (isset($displayResultsFooterDefault[$displayType]) AND ($displayResultsFooterDefault[$displayType] != "hidden")))
					{
						// Call the 'buildResultsFooter()' function (which does the actual work):
						$ResultsFooter = buildResultsFooter($NoColumns, $showRows, $citeStyle, $displayType);
						echo $ResultsFooter;
					}
				}
				// END RESULTS FOOTER ----------------------

				// Finally, finish the form
				echo "\n</form>";
			}
			else
			{
				// Report that nothing was found:
				$nothingFoundFeedback = nothingFound(false); // This is a clumsy workaround: by pretending that there were some records marked by the user ($nothingChecked = false) we force the 'nothingFound()' function to output "Sorry, but your query didn't produce any results!" instead of "No records selected..."
				echo $nothingFoundFeedback;
			}// end if $rowsFound body
		}
		else // if the user clicked one of the buttons in the 'queryResults' form on a search results page but did not mark some checkboxes in front of the records, we display a "No records selected..." warning:
		{
			// Report that nothing was selected:
			$nothingFoundFeedback = nothingFound($nothingChecked);
			echo $nothingFoundFeedback;
		}
	}

	// --------------------------------------------------------------------

	// EXPORT RECORDS using the specified export format
	function generateExport($result, $rowOffset, $showRows, $exportFormat, $exportType, $exportStylesheet, $displayType, $viewType, $userID)
	{
		global $officialDatabaseName; // these variables are defined in 'ini.inc.php'
		global $contentTypeCharset;
		global $convertExportDataToUTF8;
		global $defaultExportFormat;

		global $userOptionsArray;

		// get all user options for the current user:
		// (note that '$userOptionsArray' is made globally available)
		$userOptionsArray = getUserOptions($userID); // function 'getUserOptions()' is defined in 'include.inc.php'

		// fetch the path/name of the export format file that's associated with the export format given in '$exportFormat':
		$exportFormatFile = getFormatFile($exportFormat, "export"); // function 'getFormatFile()' is defined in 'include.inc.php()'

		if (empty($exportFormatFile))
		{
			$exportFormat = $defaultExportFormat; // if the given export format could not be found, we'll use the default export format which is defined by the '$defaultExportFormat' variable in 'ini.inc.php'
			$exportFormatFile = getFormatFile($exportFormat, "export");
		}

		// include the found export format file *once*:
		include_once "export/" . $exportFormatFile; // instead of 'include_once' we could also use: 'if ($rowCounter == 0) { include "export/" . $exportFormatFile; }'

		// export found records using the specified export format:
		$exportText = exportRecords($result, $rowOffset, $showRows, $exportStylesheet, $displayType); // function 'exportRecords()' is defined in the export format file given in '$exportFormatFile' (which, in turn, must reside in the 'export' directory of the refbase root directory)

		// adjust the mime type and return exported data based on the key given in '$exportType':
		if (eregi("text", $exportType))
			$exportContentType = "text/plain";

		elseif (eregi("^(html|email)$", $exportType))
			$exportContentType = "text/html";

		elseif (eregi("xml", $exportType))
		{
			// NOTE: Firefox >=2.x, Safari >=2.x and IE >=7.x break client-side XSL for RSS and Atom feeds!
			//       See e.g.: <http://decafbad.com/blog/2006/11/02/firefox-20-breaks-client-side-xsl-for-rss-and-atom-feeds>
			// TODO: Re-evaluate: As a consequence, we apply a VERY dirty hack in 'atomxml.inc.php' that prevents the feed sniffing
			//       and subsequent browser applied default XSLT stylesheet that has been implemented by FireFox 2, Safari 2
			//       and Internet Explorer 7. To prevent the feed sniffing we insert a comment before the feed
			//       element that is larger than 512 bytes. See: <http://feedme.mind-it.info/pivot/entry.php?id=9>
			// 
			//       For some browsers (such as the Camino browser <http://caminobrowser.org/>) it's possible to set the content type
			//       to 'application/xml' which (while incorrect for Atom/RSS) will cause the browser to trigger their XML+XSLT renderer
			//       if the Atom/RSS feed was requested together with a stylesheet.
			// 
			//       If the content type is set to 'application/atom+xml', Firefox 2 and Safari 2 will always apply their own default
			//       XSLT stylesheet and ignore any client-side XSL transformation!

			if (eregi("Atom", $exportFormat) AND empty($exportStylesheet))
				$exportContentType = "application/atom+xml"; // NOTE: using Safari 3 on OS X 10.4, this seems to cause misbehavior; Firefox seems to work fine, though
			else
				$exportContentType = "application/xml";
		}

		elseif (eregi("rss", $exportType))
			$exportContentType = "application/rss+xml";

		elseif (eregi("file", $exportType)) // attempt to set mime type & download file name according to the chosen export format:
		{
			$exportContentType = "text/plain"; // set the default mime type

			// Note that we do some "quick'n dirty" guessing for some export formats here (e.g., we assume/require that an XML export format name
			// contains 'XML' within its name!). This is in NO way fool proof and should be handled in a better way!
			if (eregi("XML", $exportFormat)) // if the export format name contains 'XML'
			{
				if (eregi("Atom", $exportFormat)) // if the export format name contains 'Atom'
					$exportContentType = "application/atom+xml"; // see note above
				else
					$exportContentType = "application/xml";

				if (eregi("Atom", $exportFormat)) // if the export format name contains 'Atom'
					$exportFileName = "atom_export.xml";

				elseif (eregi("^MODS", $exportFormat)) // if the export format name starts with 'MODS' (NOTE: the regex pattern must not match "SRW_MODS XML")
					$exportFileName = "mods_export.xml";

				elseif (eregi("(OAI_)?DC", $exportFormat)) // if the export format name contains 'OAI_DC' or 'DC'
					$exportFileName = "oaidc_export.xml";

				elseif (eregi("ODF|OpenDocument", $exportFormat)) // if the export format name contains 'ODF' or 'OpenDocument'
				{
					if (eregi("file", $exportType)) {
						$exportContentType="application/vnd.oasis.opendocument.spreadsheet";
						$exportFileName="odf_export.ods";
					}
					else {
						$exportFileName = "content.xml";
					}
				}

				elseif (eregi("SRW", $exportFormat)) // if the export format name contains 'SRW'
					$exportFileName = "srw_export.xml";

				elseif (eregi("Word", $exportFormat)) // if the export format name contains 'Word'
					$exportFileName = "msword_export.xml";

				else
					$exportFileName = "export.xml";
			}

			elseif (eregi("BibTeX|Endnote|RIS|ISI", $exportFormat)) // if the export format name contains either 'BibTeX', 'Endnote', 'RIS' or 'ISI'
			{
				if (eregi("Endnote", $exportFormat))
					$exportFileName = "endnote_export.enw";

				elseif (eregi("BibTeX", $exportFormat))
					$exportFileName = "bibtex_export.bib";

				elseif (eregi("RIS", $exportFormat))
					$exportFileName = "ris_export.ris";

				elseif (eregi("ISI", $exportFormat))
					$exportFileName = "isi_export.txt";
			}

			else
				$exportFileName = "exported_records.txt"; // set the default download file name
		}

		// if variable '$convertExportDataToUTF8' is set to "yes" in 'ini.inc.php', we'll convert latin1 data to UTF-8
		// when exporting to XML; therefore, we'll need to temporarily set the value of the global '$contentTypeCharset'
		// variable to UTF-8 which will ensure proper HTML output
		if (($convertExportDataToUTF8 == "yes") AND ($contentTypeCharset != "UTF-8"))
		{
			$oldContentTypeCharset = $contentTypeCharset; // remember the actual database charset
			$oldOfficialDatabaseName = $officialDatabaseName; // remember the database name as originally encoded

			// if the database charset is not "UTF-8" then we'll also need to temporarily convert any higher ASCII chars in variables which get included within the HTML output
			$officialDatabaseName = convertToCharacterEncoding("UTF-8", "IGNORE", $officialDatabaseName); // function 'convertToCharacterEncoding()' is defined in 'include.inc.php'
			$contentTypeCharset = "UTF-8"; // for XML output we'll temporarily set the value of '$contentTypeCharset' to "UTF-8"
		}

		// set the appropriate mimetype & set the character encoding to the one given in '$contentTypeCharset':
		setHeaderContentType($exportContentType, $contentTypeCharset); // function 'setHeaderContentType()' is defined in 'include.inc.php'

		if (eregi("file", $exportType)) // instruct the browser to download the resulting XML file:
			header('Content-Disposition: attachment; filename="' . $exportFileName . '"'); // Note that this doesn't seem to work with all browsers (notably not with Safari & OmniWeb on MacOSX Panther, but it does work with Mozilla & Camino as well as Safari on Tiger)


		elseif (eregi("^(html|email)$", $exportType)) // output data as HTML, wrapped into <pre>...</pre> tags:
		{
			if (eregi("email", $exportType)) // send exported data to the user's login email address:
			{
				$emailRecipient = $_SESSION['loginEmail'];
				$emailSubject = "Your records from the " . $officialDatabaseName . " (exported to " . $exportFormat . " format)";
				$emailBody = $exportText;

				sendEmail($emailRecipient, $emailSubject, $emailBody); // function 'sendEmail()' is defined in 'include.inc.php'
			}

			// call the 'displayHTMLhead()' function (defined in 'header.inc.php'):
			displayHTMLhead(encodeHTML($officialDatabaseName) . " -- Exported Data", "index,follow", "Data exported from the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());

			$exportText = "\n\t<pre>\n" . encodeHTML($exportText) . "\n\t</pre>\n</body>\n</html>\n";

			if ($exportType == "email")
				$exportText = "\n\t<p>"
							. "\n\t\t<a href=\"javascript:history.back()\" title=\"go back to results\">Go Back</a>"
							. "\n\t</p>"
							. "\n\t<p>"
							. "\n\t\t<b>The data below have been sent to <a href=\"mailto:" . $_SESSION['loginEmail'] . "\">" . $_SESSION['loginEmail'] . "</a>:</b>"
							. "\n\t</p>"
							. $exportText;
		}

		if (($convertExportDataToUTF8 == "yes") AND ($contentTypeCharset != "UTF-8"))
		{
			$contentTypeCharset = $oldContentTypeCharset; // restore the actual database charset
			$officialDatabaseName = $oldOfficialDatabaseName; // restore the database name as originally encoded
		}

		if ( (eregi("ODF|OpenDocument", $exportFormat)) && (eregi("file", $exportType)) ) {
			// This is a dirty hack to zip and return an ODF file.
			// It may be desired to retun other non-textual formats in the future & to return these as attachments by email in the future.
			// If this becomes needed, we should refactor the output.
			$zipfile = zipODF($exportText); // function 'zipODF()' is defined in 'odfxml.inc.php'
			echo $zipfile -> file();   
		}
		else {
			// we'll present the output within the _same_ browser window:
			// (note that we don't use a popup window here, since this may be blocked by particular browsers)
			echo $exportText;
		}
	}

	// --------------------------------------------------------------------

	// CITE RECORDS using the specified citation style and format
	function generateCitations($result, $rowsFound, $query, $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $wrapResults, $nothingChecked, $citeStyle, $citeOrder, $citeType, $orderBy, $headerMsg, $userID, $viewType)
	{
		global $contentTypeCharset; // these variables are defined in 'ini.inc.php'
		global $defaultCiteStyle;

		global $client;

		global $userOptionsArray;

		// get all user options for the current user:
		// (note that '$userOptionsArray' is made globally available)
		$userOptionsArray = getUserOptions($userID); // function 'getUserOptions()' is defined in 'include.inc.php'

		// if the query has results ...
		if ($rowsFound > 0)
		{
			// Save the current Citation view query to a session variable:
//			saveSessionVariable("lastCitationViewQuery", $query);

			// fetch the name of the citation style file that's associated with the style given in '$citeStyle':
			$citeStyleFile = getStyleFile($citeStyle); // function 'getStyleFile()' is defined in 'include.inc.php'

			If (empty($citeStyleFile))
			{
				$citeStyle = $defaultCiteStyle; // if the given cite style could not be found, we'll use the default cite style which is defined by the '$defaultCiteStyle' variable in 'ini.inc.php'
				$citeStyleFile = getStyleFile($citeStyle);
			}

			// include the found citation style file *once*:
			include_once "cite/" . $citeStyleFile;


			// fetch the name of the citation format file that's associated with the format given in '$citeType':
			$citeFormatFile = getFormatFile($citeType, "cite"); // function 'getFormatFile()' is defined in 'include.inc.php()'

			If (empty($citeFormatFile))
			{
				if (eregi("^cli", $client)) // if the query originated from a command line client such as the refbase CLI clients ("cli-refbase-1.1", "cli-refbase_import-1.0")
					$citeType = "ASCII";
				else
					$citeType = "html";

				$citeFormatFile = getFormatFile($citeType, "cite");
			}

			// include the found citation format file *once*:
			include_once "cite/" . $citeFormatFile;


			$citationData = citeRecords($result, $rowsFound, $query, $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $wrapResults, $citeStyle, $citeOrder, $citeType, $orderBy, $headerMsg, $userID, $viewType);


			if (eregi("^RTF$", $citeType)) // output references as RTF file
			{
				$citeContentType = "application/rtf";
				$citeFileName = "citations.rtf";
			}
			elseif (eregi("^PDF$", $citeType)) // output references as PDF file
			{
				$citeContentType = "application/pdf";
				$citeFileName = "citations.pdf";
			}
			elseif (eregi("^LaTeX$", $citeType)) // output references as LaTeX file
			{
				$citeContentType = "application/x-latex";
				$citeFileName = "citations.tex";
			}
			elseif (eregi("^LaTeX \.bbl$", $citeType)) // output references as LaTeX .bbl file (for use with LaTeX/BibTeX)
			{
				$citeContentType = "application/x-latex";
				$citeFileName = "citations.bbl";
			}
			elseif (eregi("^Markdown$", $citeType)) // output references as Markdown TEXT (a plain text formatting syntax)
			{
				$citeContentType = "text/plain";
				$citeFileName = "citations.txt";
			}
			elseif (eregi("^ASCII$", $citeType)) // output references as plain TEXT
			{
				$citeContentType = "text/plain";
				$citeFileName = "citations.txt";
			}
			else // by default, we'll output references in HTML format
			{
				$citeContentType = "text/html";
				$citeFileName = "citations.html";
			}

			if (!eregi("^html$", $citeType))
				// set the appropriate mimetype & set the character encoding to the one given in '$contentTypeCharset' (which is defined in 'ini.inc.php'):
				setHeaderContentType($citeContentType, $contentTypeCharset); // function 'setHeaderContentType()' is defined in 'include.inc.php'

			if (eregi("^application", $citeContentType))
				// instruct the browser to download the resulting output as file:
				header('Content-Disposition: attachment; filename="' . $citeFileName . '"'); // Note that this doesn't seem to work with all browsers (notably not with Safari & OmniWeb on MacOSX Panther, but it does work with Mozilla & Camino as well as Safari on Tiger)

			echo $citationData;
		}
		else
		{
			$nothingFoundFeedback = nothingFound($nothingChecked);
			echo $nothingFoundFeedback;
		}
	}

	// --------------------------------------------------------------------

	//	BUILD RESULTS FOOTER
	function buildResultsFooter($NoColumns, $showRows, $citeStyle, $displayType)
	{
		global $allowAnonymousGUIExport; // these variables are defined in 'ini.inc.php'
		global $displayResultsFooterDefault;

		global $loc; // defined in 'locales/core.php'

		if (isset($_SESSION['user_permissions']) AND ((isset($_SESSION['loginEmail']) AND ereg("(allow_cite|allow_user_groups|allow_export|allow_batch_export)", $_SESSION['user_permissions'])) OR (!isset($_SESSION['loginEmail']) AND ereg("allow_cite|allow_export|allow_batch_export", $_SESSION['user_permissions'])))) // only the results footer if the 'user_permissions' session variable does contain any of the following: 'allow_cite' -AND- if logged in, aditionally: 'allow_user_groups', 'allow_export', 'allow_batch_export'...
		{
			$resultsFooterToggleText = "";

			if (ereg("allow_cite", $_SESSION['user_permissions']))
				$resultsFooterToggleText .= "Cite";

			if (ereg("allow_user_groups", $_SESSION['user_permissions']))
			{
				if (ereg("allow_cite", $_SESSION['user_permissions']))
				{
					if (ereg("(allow_export|allow_batch_export)", $_SESSION['user_permissions']))
						$resultsFooterToggleText .= ", ";
					else
						$resultsFooterToggleText .= " &amp; ";
				}

				$resultsFooterToggleText .= "Group";
			}

			if (!isset($_SESSION['loginEmail']) AND ($allowAnonymousGUIExport == "yes") OR (isset($_SESSION['loginEmail']) AND ereg("(allow_export|allow_batch_export)", $_SESSION['user_permissions'])))
			{
				if (ereg("(allow_cite|allow_user_groups)", $_SESSION['user_permissions']))
					$resultsFooterToggleText .= " &amp; ";

				$resultsFooterToggleText .= "Export";
			}

			$resultsFooterToggleText .= " Options";

			if (isset($displayResultsFooterDefault[$displayType]) AND ($displayResultsFooterDefault[$displayType] == "open"))
			{
				$resultsFooterDisplayStyle = "block";
				$resultsFooterToggleImage = "img/open.gif";
				$resultsFooterInitialToggleText = "";
			}
			else
			{
				$resultsFooterDisplayStyle = "none";
				$resultsFooterToggleImage = "img/closed.gif";
				$resultsFooterInitialToggleText = $resultsFooterToggleText;
			}
			
			$ResultsFooterRow = "\n<div class=\"resultsfooter\">";

			$ResultsFooterRow .= "\n<div class=\"showhide\">"
			                   . "\n\t<a href=\"#resultactions\" onclick=\"toggleVisibility('resultactions','resultsFooterToggleimg','resultsFooterToggletxt','" . $resultsFooterToggleText . "')\" title=\"toggle visibility\">"
			                   . "\n\t\t<img id=\"resultsFooterToggleimg\" class=\"toggleimg\" src=\"" . $resultsFooterToggleImage . "\" alt=\"" . $loc["LinkTitle_ToggleVisibility"] . "\" width=\"9\" height=\"9\" hspace=\"0\" border=\"0\">"
			                   . "\n\t</a>"
			                   . "\n\t<div id=\"resultsFooterToggletxt\" class=\"toggletxt\">" . $resultsFooterInitialToggleText . "</div>"
			                   . "\n</div>";


			$ResultsFooterRow .= "\n<div id=\"resultactions\" style=\"display: " . $resultsFooterDisplayStyle . ";\">";

			$ResultsFooterRow .= "\n\t<div id=\"selectresults\">"
			                   . "\n\t\t<input type=\"radio\" id=\"allRecs\" name=\"recordsSelectionRadio\" value=\"1\" onfocus=\"checkall(false,'marked%5B%5D')\" title=\"cite/group/export all records of the current result set\" checked>"
			                   . "\n\t\t<label for=\"allRecs\">All Found Records</label>"
			                   . "\n\t\t<input type=\"radio\" id=\"selRecs\" name=\"recordsSelectionRadio\" value=\"0\" onfocus=\"checkall(true,'marked%5B%5D')\" title=\"cite/group/export only those records which you've selected on this page\">"
			                   . "\n\t\t<label for=\"selRecs\">Selected Records:</label>"
			                   . "\n\t</div>";


			// Cite functionality:
			if (isset($_SESSION['user_permissions']) AND ereg("allow_cite", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_cite', show form elements to build a reference list for the chosen records:
			{
				if (!isset($_SESSION['user_styles']))
					$citeStyleDisabled = " disabled"; // disable the style popup (and other form elements) if the session variable holding the user's styles isn't available
				else
					$citeStyleDisabled = "";

				if (!isset($_SESSION['user_cite_formats']))
					$citeFormatDisabled = " disabled"; // disable the cite format popup if the session variable holding the user's cite formats isn't available
				else
					$citeFormatDisabled = "";

				$ResultsFooterRow .= "\n\t<fieldset id=\"citerefs\">"
				                   . "\n\t\t<legend>Save Citations:</legend>"
				                   . "\n\t\t<label for=\"citeType\">Format:</label>"
				                   . "\n\t\t<select id=\"citeType\" name=\"citeType\" title=\"choose how your reference list shall be returned\"$citeStyleDisabled$citeFormatDisabled>";

				if (isset($_SESSION['user_cite_formats']))
				{
					$optionTags = buildSelectMenuOptions($_SESSION['user_cite_formats'], " *; *", "\t\t\t", false); // build properly formatted <option> tag elements from the items listed in the 'user_cite_formats' session variable
					$ResultsFooterRow .= $optionTags;
				}
				else
					$ResultsFooterRow .= "\n\t\t\t<option>(no formats available)</option>";

				$ResultsFooterRow .= "\n\t\t</select>"
				                   . "\n\t\t<input type=\"submit\" name=\"submit\" value=\"Cite\" title=\"build a list of references for all chosen records\"$citeStyleDisabled>"
				                   . "\n\t</fieldset>";

				// Assign the 'selected' param to one of the main non-HTML citation output options (RTF, PDF, LaTeX):
				if (eregi("<option>RTF</option>", $ResultsFooterRow))
					$ResultsFooterRow = ereg_replace("<option>RTF</option>", "<option selected>RTF</option>", $ResultsFooterRow);
				elseif (eregi("<option>PDF</option>", $ResultsFooterRow))
					$ResultsFooterRow = ereg_replace("<option>PDF</option>", "<option selected>PDF</option>", $ResultsFooterRow);
				elseif (eregi("<option>LaTeX</option>", $ResultsFooterRow))
					$ResultsFooterRow = ereg_replace("<option>LaTeX</option>", "<option selected>LaTeX</option>", $ResultsFooterRow);
			}


			// User groups functionality:
			if (isset($_SESSION['loginEmail']) AND isset($_SESSION['user_permissions']) AND ereg("allow_user_groups", $_SESSION['user_permissions'])) // if a user is logged in AND the 'user_permissions' session variable contains 'allow_user_groups', show form elements to add/remove the chosen records to/from a user's group:
			{
				if (!isset($_SESSION['userGroups']))
				{
					$groupSearchDisabled = " disabled"; // disable the (part of the) 'Add to/Remove from group' form elements if the session variable holding the user's groups isnt't available
					$groupSearchPopupMenuChecked = "";
					$groupSearchTextInputChecked = " checked";
					$groupSearchSelectorTitle = "(to setup a new group with all chosen records, enter a group name to the right, then click the 'Add' button)";
					$groupSearchTextInputTitle = "specify a new group name here, then click the 'Add' button";
				}
				else
				{
					$groupSearchDisabled = "";
					$groupSearchPopupMenuChecked = " checked";
					$groupSearchTextInputChecked = "";
					$groupSearchSelectorTitle = "choose the group to (from) which the chosen records shall be added (removed)";
					$groupSearchTextInputTitle = "specify a new group name here, then click the 'Add' button";
				}

				$ResultsFooterRow .= "\n\t<fieldset id=\"grouprefs\">"
				                   . "\n\t\t<legend>Add to (Remove from) Group:</legend>"
				                   . "\n\t\t<div id=\"myGroup\">"
				                   . "\n\t\t\t<input type=\"radio\" id=\"myGroupRadio\" name=\"userGroupActionRadio\" value=\"1\" title=\"add (remove) the chosen records to (from) an existing group\"$groupSearchDisabled$groupSearchPopupMenuChecked>"
				                   . "\n\t\t\t<label for=\"userGroupSelector\">My:</label>"
				                   . "\n\t\t\t<select id=\"userGroupSelector\" name=\"userGroupSelector\" onfocus=\"toggleRadio('myGroupRadio', 'newGroupRadio', false)\" title=\"$groupSearchSelectorTitle\"$groupSearchDisabled>";

				if (isset($_SESSION['userGroups']))
				{
					$optionTags = buildSelectMenuOptions($_SESSION['userGroups'], " *; *", "\t\t\t\t", false); // build properly formatted <option> tag elements from the items listed in the 'userGroups' session variable
					$ResultsFooterRow .= $optionTags;
				}
				else
				{
					$ResultsFooterRow .= "\n\t\t\t\t<option>(no groups available)</option>";
				}

				$ResultsFooterRow .= "\n\t\t\t</select>"
				                   . "\n\t\t</div>"
				                   . "\n\t\t<div id=\"newGroup\">"
				                   . "\n\t\t\t<input type=\"radio\" id=\"newGroupRadio\" name=\"userGroupActionRadio\" value=\"0\" title=\"setup a new group with the chosen records\"$groupSearchTextInputChecked>"
				                   . "\n\t\t\t<label for=\"userGroupName\">New:</label>"
				                   . "\n\t\t\t<input type=\"text\" id=\"userGroupName\" name=\"userGroupName\" value=\"\" size=\"12\" onfocus=\"toggleRadio('myGroupRadio', 'newGroupRadio', true)\" title=\"$groupSearchTextInputTitle\">"
				                   . "\n\t\t</div>"
				                   . "\n\t\t<div id=\"addRemoveGroup\">"
				                   . "\n\t\t\t<input type=\"submit\" name=\"submit\" value=\"Add\" title=\"add the chosen records to the specified group\">"
				                   . "\n\t\t\t<input type=\"submit\" name=\"submit\" value=\"Remove\" title=\"remove the chosen records from the specified group\"$groupSearchDisabled>"
				                   . "\n\t\t</div>"
				                   . "\n\t</fieldset>";
			}


			// Export functionality:
			if ((!isset($_SESSION['loginEmail']) AND ($allowAnonymousGUIExport == "yes")) OR (isset($_SESSION['loginEmail']) AND isset($_SESSION['user_permissions']) AND ereg("(allow_export|allow_batch_export)", $_SESSION['user_permissions']))) // if a user is logged in AND the 'user_permissions' session variable contains either 'allow_export' or 'allow_batch_export', show form elements to export the chosen records:
			{
				if (!isset($_SESSION['user_export_formats']))
					$exportFormatDisabled = " disabled"; // disable the format popup if the session variable holding the user's export formats isn't available
				else
					$exportFormatDisabled = "";

				$ResultsFooterRow .= "\n\t<fieldset id=\"exportrefs\">"
				                   . "\n\t\t<legend>Export Records:</legend>"
				                   . "\n\t\t<label for=\"exportFormat\">Format:</label>"
				                   . "\n\t\t<select id=\"exportFormat\" name=\"exportFormat\" title=\"choose the export format for your references\"$exportFormatDisabled>";

				if (isset($_SESSION['user_export_formats']))
				{
					$optionTags = buildSelectMenuOptions($_SESSION['user_export_formats'], " *; *", "\t\t\t", false); // build properly formatted <option> tag elements from the items listed in the 'user_export_formats' session variable
					$ResultsFooterRow .= $optionTags;
				}
				else
					$ResultsFooterRow .= "\n\t\t\t<option>(no formats available)</option>";

				$ResultsFooterRow .= "\n\t\t</select>"
				                   . "\n\t\t<input type=\"hidden\" name=\"exportType\" value=\"file\">"
				                   . "\n\t\t<input type=\"submit\" name=\"submit\" value=\"Export\" title=\"export all chosen records\"$exportFormatDisabled>"
				                   . "\n\t</fieldset>";
			}


			$ResultsFooterRow .= "\n</div>"
			                   . "\n</div>";
		}
		else
			$ResultsFooterRow = ""; // return an empty string if the 'user_permissions' session variable does NOT contain any of the following: 'allow_cite', 'allow_user_groups', 'allow_export', 'allow_batch_export'


		return $ResultsFooterRow;
	}

	// --------------------------------------------------------------------

	// EXTRACT FORM VARIABLES SENT THROUGH GET OR POST
	// (!! NOTE !!: for details see <http://www.php.net/release_4_2_1.php> & <http://www.php.net/manual/en/language.variables.predefined.php>)

	// Find duplicate records within results of the given SQL query (using settings extracted from the 'duplicateSearch' form
	// in 'duplicate_search.php') and return a modified database query that only matches these duplicate entries:
	function findDuplicates($sqlQuery, $originalDisplayType)
	{
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		// re-assign the correct display type (i.e. the view that was active when the user clicked the 'dups' link in the header):
		if (!empty($originalDisplayType))
			$displayType = $originalDisplayType;

		// Extract form variables provided by the 'duplicateSearch' form in 'duplicate_search.php':
		if (isset($_REQUEST['matchFieldsSelector']))
		{
			if (is_string($_REQUEST['matchFieldsSelector'])) // we accept a string containing a (e.g. comma delimited) list of field names
				$selectedFieldsArray = preg_split("/[^a-z_]+/", $_REQUEST['matchFieldsSelector'], -1, PREG_SPLIT_NO_EMPTY); // (the 'PREG_SPLIT_NO_EMPTY' flag causes only non-empty pieces to be returned)
			else // the field list is already provided as array:
				$selectedFieldsArray = $_REQUEST['matchFieldsSelector'];
		}
		else
			$selectedFieldsArray = array();

		if (isset($_REQUEST['ignoreWhitespace']) AND ($_REQUEST['ignoreWhitespace'] == "1"))
			$ignoreWhitespace = "1";
		else
			$ignoreWhitespace = "0";

		if (isset($_REQUEST['ignorePunctuation']) AND ($_REQUEST['ignorePunctuation'] == "1"))
			$ignorePunctuation = "1";
		else
			$ignorePunctuation = "0";

		if (isset($_REQUEST['ignoreCharacterCase']) AND ($_REQUEST['ignoreCharacterCase'] == "1"))
			$ignoreCharacterCase = "1";
		else
			$ignoreCharacterCase = "0";

		if (isset($_REQUEST['ignoreAuthorInitials']) AND ($_REQUEST['ignoreAuthorInitials'] == "1"))
			$ignoreAuthorInitials = "1";
		else
			$ignoreAuthorInitials = "0";

		if (isset($_REQUEST['nonASCIIChars']))
			$nonASCIIChars = $_REQUEST['nonASCIIChars'];
		else
			$nonASCIIChars = "keep";


		// VALIDATE FORM DATA:
		$errors = array();

		// Validate the field selector:
		if (empty($selectedFieldsArray))
			$errors["matchFieldsSelector"] = "You must select at least one field:";

		// Validate the 'SQL Query' field:
		if (empty($sqlQuery))
			$errors["sqlQuery"] = "You must specify a query string:"; // 'sqlQuery' must not be empty

		elseif (!eregi("^SELECT", $sqlQuery))
			$errors["sqlQuery"] = "You can only execute SELECT queries:";

		// Check if there were any errors:
		if (count($errors) > 0)
		{
			// In case of an error, we write all form variables back to the '$formVars' array
			// (which 'duplicate_search.php' requires to reload form values):
			foreach($_REQUEST as $varname => $value)
				$formVars[$varname] = $value;

			// Since checkbox form fields do only get included in the '$_REQUEST' array if they were marked,
			// we have to add appropriate array elements for all checkboxes that weren't set:
			if (!isset($formVars["ignoreWhitespace"]))
				$formVars["ignoreWhitespace"] = "0";

			if (!isset($formVars["ignorePunctuation"]))
				$formVars["ignorePunctuation"] = "0";

			if (!isset($formVars["ignoreCharacterCase"]))
				$formVars["ignoreCharacterCase"] = "0";

			if (!isset($formVars["ignoreAuthorInitials"]))
				$formVars["ignoreAuthorInitials"] = "0";

			if (!isset($formVars["showLinks"]))
				$formVars["showLinks"] = "0";

			// Write back session variables:
			saveSessionVariable("errors", $errors); // function 'saveSessionVariable()' is defined in 'include.inc.php'
			saveSessionVariable("formVars", $formVars);

			// There are errors. Relocate back to 'duplicate_search.php':
			header("Location: duplicate_search.php");

			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}


		// CONSTRUCT SQL QUERY (1. DUPLICATE SEARCH):
		// To identify any duplicates within the results of the original query, we build a new query based on the original SQL query:
		$query = $sqlQuery;

		// Replace SELECT list of columns with those from '$selectedFieldsArray' (plus the 'serial' column):
		$selectedFieldsString = implode(", ", $selectedFieldsArray);
		$query = newSELECTclause("SELECT " . $selectedFieldsString . ", serial", $query, false); // function 'newSELECTclause()' is defined in 'include.inc.php'

		// Replace any existing ORDER BY clause with the list of columns given in '$selectedFieldsArray':
		$query = newORDERclause("ORDER BY " . $selectedFieldsString, $query, false); // function 'newORDERclause()' is defined in 'include.inc.php'

		// Fix escape sequences within the SQL query:
		$query = stripSlashesIfMagicQuotes($query);

		// RUN the query on the database through the connection:
		$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'


		// PROCESS RESULTS:
		$recordSerialsArray = array();
		$duplicateRecordSerialsArray = array();

		$rowsFound = @ mysql_num_rows($result);

		// Identify any records with matching field data:
		if ($rowsFound > 0) // if there were rows found ...
		{
			// Count the number of fields:
			$fieldsFound = mysql_num_fields($result);

			// Loop over each row in the result set:
			for ($rowCounter=0; $row = @ mysql_fetch_array($result); $rowCounter++)
			{
				$recordIdentifier = ""; // make sure our buffer variable is empty

				// For each row, loop over each field (except for the last one which is the 'serial' field):
				for ($i=0; $i < ($fieldsFound - 1); $i++)
				{
					// fetch the current attribute name:
					$fieldName = getMySQLFieldInfo($result, $i, "name"); // function 'getMySQLFieldInfo()' is defined in 'include.inc.php'

					// normalize author names:
					if (($fieldName == "author") AND ($ignoreAuthorInitials == "1"))
					{
						// this is a stupid hack that maps the names of the '$row' array keys to those used
						// by the '$formVars' array (which is required by function 'parsePlaceholderString()')
						// (eventually, the '$formVars' array should use the MySQL field names as names for its array keys)
						$formVars = buildFormVarsArray($row); // function 'buildFormVarsArray()' is defined in 'include.inc.php'

						// ignore initials in author names:
						$row[$i] = parsePlaceholderString($formVars, "<:authors[0||]:>", ""); // function 'parsePlaceholderString()' is defined in 'include.inc.php'
					}

					$recordIdentifier .= $row[$i]; // merge all field values to form a unique record identifier string
				}

				// Normalize record identifier string:
				if ($ignoreWhitespace == "1") // ignore whitespace
					$recordIdentifier = preg_replace("/\s+/", "", $recordIdentifier);

				if ($ignorePunctuation == "1") // ignore punctuation
					$recordIdentifier = preg_replace("/[[:punct:]]+/", "", $recordIdentifier);

				if ($ignoreCharacterCase == "1") // ignore character case
					$recordIdentifier = strtolower($recordIdentifier);

				if ($nonASCIIChars == "strip") // strip non-ASCII characters
					$recordIdentifier = handleNonASCIIAndUnwantedCharacters($recordIdentifier, "\S\s", "strip"); // function 'parsePlaceholderString()' is defined in 'include.inc.php'

				elseif ($nonASCIIChars == "transliterate") // transliterate non-ASCII characters
					$recordIdentifier = handleNonASCIIAndUnwantedCharacters($recordIdentifier, "\S\s", "transliterate");

				// Check whether the record identifier string has occurred already:
				if (isset($recordSerialsArray[$recordIdentifier])) // this record identifier string has already been seen
					$recordSerialsArray[$recordIdentifier][] = $row["serial"]; // add this record's serial number to the array of record serials which share the same record identifier string
				else // new record identifier string
					$recordSerialsArray[$recordIdentifier] = array($row["serial"]); // add a new array element for this record's identifier string (and store its serial number as value within a sub-array)
			}

			// Collect all array elements from '$recordSerialsArray' where their sub-array contains more than one serial number:
			foreach($recordSerialsArray as $recordSerials)
			{
				if (count($recordSerials) > 1)
					foreach($recordSerials as $recordSerial)
						$duplicateRecordSerialsArray[] = $recordSerial; // add this record's serial number to the array of duplicate record serials
			}
		}
		else // nothing found!
		{
			// TODO!
		}

		if (empty($duplicateRecordSerialsArray))
			$duplicateRecordSerialsArray[] = "0"; // if no duplicate records were found, the non-existing serial number '0' will result in a "nothing found" feedback


		// CONSTRUCT SQL QUERY (2. DUPLICATES DISPLAY):
		// To display any duplicates that were found within the results of the original query, we build again a new query based on the original SQL query:
		$query = $sqlQuery;

		// Replace WHERE clause:
		// TODO: maybe make this into a generic function? (compare with function 'extractWHEREclause()' in 'include.inc.php')
		$duplicateRecordSerialsString = implode("|", $duplicateRecordSerialsArray);
		$query = preg_replace("/(?<=WHERE )(.+?)(?= ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|[ ;]+(SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|FILE)|$)/i", "serial RLIKE \"^(" . $duplicateRecordSerialsString . ")$\"", $query);

		// Replace any existing ORDER BY clause with the list of columns given in '$selectedFieldsArray':
		$query = newORDERclause("ORDER BY " . $selectedFieldsString, $query, false);


		return array($query, $displayType);
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the 'simple_search.php' form:
	// TODO: build the complete SQL query using functions 'buildFROMclause()', 'buildWHEREclause()' and 'buildORDERclause()'
	function extractFormElementsSimple($showLinks, $userID)
	{
		global $defaultView; // defined in 'ini.inc.php'
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		// Build SELECT clause:
		if ($defaultView == "List") // honour the user's selection of fields to be displayed in List View
		{
			// Defines a list of all checkbox names that are available in "Simple Search"
			// and their corresponding column names from MySQL table 'refs':
			$columnsArray = array("showAuthor"      => "author",
								  "showTitle"       => "title",
								  "showYear"        => "year",
								  "showPublication" => "publication",
								  "showVolume"      => "volume",
								  "showPages"       => "pages"
								 );

			// Add columns given in '$columnsArray' to the list of fields available in the
			// List View SELECT clause if they were marked in the search form interface:
			$selectClauseColumnsArray = addToSelectClause($columnsArray);

			$query = buildSELECTclause($defaultView, $showLinks, "", false, true, implode(", ", $selectClauseColumnsArray));
		}
		else
			$query = buildSELECTclause($defaultView, $showLinks);


		// Build FROM clause:
		if (isset($_SESSION['loginEmail'])) // if a user is logged in...
			$query .= " FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = " . $userID;
		else // NO user logged in
			$query .= " FROM $tableRefs";


		// Build WHERE clause:
		$query .= " WHERE serial RLIKE \".+\""; // add initial WHERE clause

		// ... if the user has specified an author, add the value of '$authorName' as an AND clause:
		$authorName = $_REQUEST['authorName'];
		if ($authorName != "")
			{
				$authorSelector = $_REQUEST['authorSelector'];
				if ($authorSelector == "contains")
					$query .= " AND author RLIKE " . quote_smart($authorName);
				elseif ($authorSelector == "does not contain")
					$query .= " AND author NOT RLIKE " . quote_smart($authorName);
				elseif ($authorSelector == "is equal to")
					$query .= " AND author = " . quote_smart($authorName);
				elseif ($authorSelector == "is not equal to")
					$query .= " AND author != " . quote_smart($authorName);
				elseif ($authorSelector == "starts with")
					$query .= " AND author RLIKE " . quote_smart("^" . $authorName);
				elseif ($authorSelector == "ends with")
					$query .= " AND author RLIKE " . quote_smart($authorName . "$");
			}

		// ... if the user has specified a title, add the value of '$titleName' as an AND clause:
		$titleName = $_REQUEST['titleName'];
		if ($titleName != "")
			{
				$titleSelector = $_REQUEST['titleSelector'];
				if ($titleSelector == "contains")
					$query .= " AND title RLIKE " . quote_smart($titleName);
				elseif ($titleSelector == "does not contain")
					$query .= " AND title NOT RLIKE " . quote_smart($titleName);
				elseif ($titleSelector == "is equal to")
					$query .= " AND title = " . quote_smart($titleName);
				elseif ($titleSelector == "is not equal to")
					$query .= " AND title != " . quote_smart($titleName);
				elseif ($titleSelector == "starts with")
					$query .= " AND title RLIKE " . quote_smart("^" . $titleName);
				elseif ($titleSelector == "ends with")
					$query .= " AND title RLIKE " . quote_smart($titleName . "$");
			}

		// ... if the user has specified a year, add the value of '$yearNo' as an AND clause:
		$yearNo = $_REQUEST['yearNo'];
		if ($yearNo != "")
			{
				$yearSelector = $_REQUEST['yearSelector'];
				if ($yearSelector == "contains")
					$query .= " AND year RLIKE " . quote_smart($yearNo);
				elseif ($yearSelector == "does not contain")
					$query .= " AND year NOT RLIKE " . quote_smart($yearNo);
				elseif ($yearSelector == "is equal to")
					$query .= " AND year = " . quote_smart($yearNo);
				elseif ($yearSelector == "is not equal to")
					$query .= " AND year != " . quote_smart($yearNo);
				elseif ($yearSelector == "starts with")
					$query .= " AND year RLIKE " . quote_smart("^" . $yearNo);
				elseif ($yearSelector == "ends with")
					$query .= " AND year RLIKE " . quote_smart($yearNo . "$");
				elseif ($yearSelector == "is greater than")
					$query .= " AND year > " . quote_smart($yearNo);
				elseif ($yearSelector == "is less than")
					$query .= " AND year < " . quote_smart($yearNo);
				elseif ($yearSelector == "is within range")
					{
						if (preg_match("/\d+/", $yearNo)) // if '$yearNo' does contain at least one number
						{
							// extract first number:
							$yearNoStart = preg_replace("/^\D*(\d+).*/", "\\1", $yearNo);
							$query .= " AND year >= " . quote_smart($yearNoStart);

							if (preg_match("/^\D*\d+\D+\d+/", $yearNo)) // if '$yearNo' does contain at least two numbers (which are separated by one or more non-digit characters)
							{
								// extract the second number:
								$yearNoEnd = preg_replace("/^\D*\d+\D+(\d+).*/", "\\1", $yearNo);
								$query .= " AND year <= " . quote_smart($yearNoEnd);
							}
						}
						else // fallback if no number is given:
							$query .= " AND year RLIKE " . quote_smart($yearNo);
					}
				elseif ($yearSelector == "is within list")
					{
						// replace any non-digit chars with "|":
						$yearNo = preg_replace("/\D+/", "|", $yearNo);
						// strip "|" from beginning/end of string (if any):
						$yearNo = preg_replace("/^\|?(.+?)\|?$/", "\\1", $yearNo);
						$query .= " AND year RLIKE " . quote_smart("^(" . $yearNo . ")$");
					}
			}

		// ... if the user has specified a publication, add the value of '$publicationName' as an AND clause:
		$publicationRadio = $_REQUEST['publicationRadio'];
		if ($publicationRadio == "1")
		{
			$publicationName = $_REQUEST['publicationName'];
			if ($publicationName != "All" && $publicationName != "")
				{
					$publicationSelector = $_REQUEST['publicationSelector'];
					if ($publicationSelector == "contains")
						$query .= " AND publication RLIKE " . quote_smart($publicationName);
					elseif ($publicationSelector == "does not contain")
						$query .= " AND publication NOT RLIKE " . quote_smart($publicationName);
					elseif ($publicationSelector == "is equal to")
						$query .= " AND publication = " . quote_smart($publicationName);
					elseif ($publicationSelector == "is not equal to")
						$query .= " AND publication != " . quote_smart($publicationName);
					elseif ($publicationSelector == "starts with")
						$query .= " AND publication RLIKE " . quote_smart("^" . $publicationName);
					elseif ($publicationSelector == "ends with")
						$query .= " AND publication RLIKE " . quote_smart($publicationName . "$");
				}
		}
		elseif ($publicationRadio == "0")
		{
			$publicationName2 = $_REQUEST['publicationName2'];
			if ($publicationName2 != "")
				{
					$publicationSelector2 = $_REQUEST['publicationSelector2'];
					if ($publicationSelector2 == "contains")
						$query .= " AND publication RLIKE " . quote_smart($publicationName2);
					elseif ($publicationSelector2 == "does not contain")
						$query .= " AND publication NOT RLIKE " . quote_smart($publicationName2);
					elseif ($publicationSelector2 == "is equal to")
						$query .= " AND publication = " . quote_smart($publicationName2);
					elseif ($publicationSelector2 == "is not equal to")
						$query .= " AND publication != " . quote_smart($publicationName2);
					elseif ($publicationSelector2 == "starts with")
						$query .= " AND publication RLIKE " . quote_smart("^" . $publicationName2);
					elseif ($publicationSelector2 == "ends with")
						$query .= " AND publication RLIKE " . quote_smart($publicationName2 . "$");
				}
		}

		// ... if the user has specified a volume, add the value of '$volumeNo' as an AND clause:
		$volumeNo = $_REQUEST['volumeNo'];
		if ($volumeNo != "")
			{
				$volumeSelector = $_REQUEST['volumeSelector'];
				if ($volumeSelector == "contains")
					$query .= " AND volume RLIKE " . quote_smart($volumeNo);
				elseif ($volumeSelector == "does not contain")
					$query .= " AND volume NOT RLIKE " . quote_smart($volumeNo);
				elseif ($volumeSelector == "is equal to")
					$query .= " AND volume = " . quote_smart($volumeNo);
				elseif ($volumeSelector == "is not equal to")
					$query .= " AND volume != " . quote_smart($volumeNo);
				elseif ($volumeSelector == "starts with")
					$query .= " AND volume RLIKE " . quote_smart("^" . $volumeNo);
				elseif ($volumeSelector == "ends with")
					$query .= " AND volume RLIKE " . quote_smart($volumeNo . "$");
				elseif ($volumeSelector == "is greater than")
					$query .= " AND volume_numeric > " . quote_smart($volumeNo);
				elseif ($volumeSelector == "is less than")
					$query .= " AND volume_numeric < " . quote_smart($volumeNo);
				elseif ($volumeSelector == "is within range")
					{
						if (preg_match("/\d+/", $volumeNo)) // if '$volumeNo' does contain at least one number
						{
							// extract first number:
							$volumeNoStart = preg_replace("/^\D*(\d+).*/", "\\1", $volumeNo);
							$query .= " AND volume_numeric >= " . quote_smart($volumeNoStart);

							if (preg_match("/^\D*\d+\D+\d+/", $volumeNo)) // if '$volumeNo' does contain at least two numbers (which are separated by one or more non-digit characters)
							{
								// extract the second number:
								$volumeNoEnd = preg_replace("/^\D*\d+\D+(\d+).*/", "\\1", $volumeNo);
								$query .= " AND volume_numeric <= " . quote_smart($volumeNoEnd);
							}
						}
						else // fallback if no number is given:
							$query .= " AND volume RLIKE " . quote_smart($volumeNo);
					}
				elseif ($volumeSelector == "is within list")
					{
						// replace any non-digit chars with "|":
						$volumeNo = preg_replace("/\D+/", "|", $volumeNo);
						// strip "|" from beginning/end of string (if any):
						$volumeNo = preg_replace("/^\|?(.+?)\|?$/", "\\1", $volumeNo);
						$query .= " AND volume RLIKE " . quote_smart("^(" . $volumeNo . ")$");
					}
			}

		// ... if the user has specified some pages, add the value of '$pagesNo' as an AND clause:
		$pagesNo = $_REQUEST['pagesNo'];
		if ($pagesNo != "")
			{
				$pagesSelector = $_REQUEST['pagesSelector'];
				if ($pagesSelector == "contains")
					$query .= " AND pages RLIKE " . quote_smart($pagesNo);
				elseif ($pagesSelector == "does not contain")
					$query .= " AND pages NOT RLIKE " . quote_smart($pagesNo);
				elseif ($pagesSelector == "is equal to")
					$query .= " AND pages = " . quote_smart($pagesNo);
				elseif ($pagesSelector == "is not equal to")
					$query .= " AND pages != " . quote_smart($pagesNo);
				elseif ($pagesSelector == "starts with")
					$query .= " AND pages RLIKE " . quote_smart("^" . $pagesNo);
				elseif ($pagesSelector == "ends with")
					$query .= " AND pages RLIKE " . quote_smart($pagesNo . "$");
			}


		// Construct the ORDER BY clause:
		// TODO?: quote_smart (haven't yey tested)
		// A) extract first level sort option:
		$sortSelector1 = $_REQUEST['sortSelector1'];
		// when field name = 'pages' then sort by 'first_page' instead:
		$sortSelector1 = str_replace("pages", "first_page", $sortSelector1);

		$sortRadio1 = $_REQUEST['sortRadio1'];
		if ($sortRadio1 == "0") // sort ascending
			$query .= " ORDER BY $sortSelector1";
		else // sort descending
			$query .= " ORDER BY $sortSelector1 DESC";

		// B) extract second level sort option:
		$sortSelector2 = $_REQUEST['sortSelector2'];
		// when field name = 'pages' then sort by 'first_page' instead:
		$sortSelector2 = str_replace("pages", "first_page", $sortSelector2);

		$sortRadio2 = $_REQUEST['sortRadio2'];
		if ($sortRadio2 == "0") // sort ascending
			$query .= ", $sortSelector2";
		else // sort descending
			$query .= ", $sortSelector2 DESC";

		// C) extract third level sort option:
		$sortSelector3 = $_REQUEST['sortSelector3'];
		// when field name = 'pages' then sort by 'first_page' instead:
		$sortSelector3 = str_replace("pages", "first_page", $sortSelector3);

		$sortRadio3 = $_REQUEST['sortRadio3'];
		if ($sortRadio3 == "0") // sort ascending
			$query .= ", $sortSelector3";
		else // sort descending
			$query .= ", $sortSelector3 DESC";


		return $query;
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the 'library_search.php' form:
	// TODO: build the complete SQL query using functions 'buildFROMclause()', 'buildWHEREclause()' and 'buildORDERclause()'
	function extractFormElementsLibrary($showLinks, $userID)
	{
		global $librarySearchPattern; // these variables are specified in 'ini.inc.php'
		global $defaultView;
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		// Build SELECT clause:
		if ($defaultView == "List") // honour the user's selection of fields to be displayed in List View
		{
			// Defines a list of all checkbox names that are available in "Library Search"
			// and their corresponding column names from MySQL table 'refs':
			$columnsArray = array("showAuthor"      => "author",
								  "showTitle"       => "title",
								  "showYear"        => "year",
								  "showEditor"      => "editor",
								  "showSeriesTitle" => "series_title",
								  "showVolume"      => "series_volume",
								  "showPages"       => "pages",
								  "showPublisher"   => "publisher",
								  "showPlace"       => "place",
								  "showCallNumber"  => "call_number",
								  "showKeywords"    => "keywords",
								  "showNotes"       => "notes"
								 );

			// Add columns given in '$columnsArray' to the list of fields available in the
			// List View SELECT clause if they were marked in the search form interface:
			$selectClauseColumnsArray = addToSelectClause($columnsArray);

			$query = buildSELECTclause($defaultView, $showLinks, "", false, true, implode(", ", $selectClauseColumnsArray));
		}
		else
			$query = buildSELECTclause($defaultView, $showLinks);


		// Build FROM clause:
		if (isset($_SESSION['loginEmail'])) // if a user is logged in...
			$query .= " FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = " . $userID;
		else // NO user logged in
			$query .= " FROM $tableRefs";


		// Build WHERE clause:
		// Note: we'll restrict the query to records where the pattern given in array element '$librarySearchPattern[1]' (defined in 'ini.inc.php')
		//       matches the contents of the field given in array element '$librarySearchPattern[0]'
		$query .= " WHERE serial RLIKE \".+\" AND " . $librarySearchPattern[0] . " RLIKE " . quote_smart($librarySearchPattern[1]); // add initial WHERE clause

		// ... if the user has specified an author, add the value of '$authorName' as an AND clause:
		$authorName = $_REQUEST['authorName'];
		if ($authorName != "")
			{
				$authorSelector = $_REQUEST['authorSelector'];
				if ($authorSelector == "contains")
					$query .= " AND author RLIKE " . quote_smart($authorName);
				elseif ($authorSelector == "does not contain")
					$query .= " AND author NOT RLIKE " . quote_smart($authorName);
				elseif ($authorSelector == "is equal to")
					$query .= " AND author = " . quote_smart($authorName);
				elseif ($authorSelector == "is not equal to")
					$query .= " AND author != " . quote_smart($authorName);
				elseif ($authorSelector == "starts with")
					$query .= " AND author RLIKE " . quote_smart("^" . $authorName);
				elseif ($authorSelector == "ends with")
					$query .= " AND author RLIKE " . quote_smart($authorName . "$");
			}

		// ... if the user has specified a title, add the value of '$titleName' as an AND clause:
		$titleName = $_REQUEST['titleName'];
		if ($titleName != "")
			{
				$titleSelector = $_REQUEST['titleSelector'];
				if ($titleSelector == "contains")
					$query .= " AND title RLIKE " . quote_smart($titleName);
				elseif ($titleSelector == "does not contain")
					$query .= " AND title NOT RLIKE " . quote_smart($titleName);
				elseif ($titleSelector == "is equal to")
					$query .= " AND title = " . quote_smart($titleName);
				elseif ($titleSelector == "is not equal to")
					$query .= " AND title != " . quote_smart($titleName);
				elseif ($titleSelector == "starts with")
					$query .= " AND title RLIKE " . quote_smart("^" . $titleName);
				elseif ($titleSelector == "ends with")
					$query .= " AND title RLIKE " . quote_smart($titleName . "$");
			}

		// ... if the user has specified a year, add the value of '$yearNo' as an AND clause:
		$yearNo = $_REQUEST['yearNo'];
		if ($yearNo != "")
			{
				$yearSelector = $_REQUEST['yearSelector'];
				if ($yearSelector == "contains")
					$query .= " AND year RLIKE " . quote_smart($yearNo);
				elseif ($yearSelector == "does not contain")
					$query .= " AND year NOT RLIKE " . quote_smart($yearNo);
				elseif ($yearSelector == "is equal to")
					$query .= " AND year = " . quote_smart($yearNo);
				elseif ($yearSelector == "is not equal to")
					$query .= " AND year != " . quote_smart($yearNo);
				elseif ($yearSelector == "starts with")
					$query .= " AND year RLIKE " .quote_smart("^" . $yearNo);
				elseif ($yearSelector == "ends with")
					$query .= " AND year RLIKE " . quote_smart($yearNo . "$");
				elseif ($yearSelector == "is greater than")
					$query .= " AND year > " . quote_smart($yearNo);
				elseif ($yearSelector == "is less than")
					$query .= " AND year < " . quote_smart($yearNo);
				elseif ($yearSelector == "is within range")
					{
						if (preg_match("/\d+/", $yearNo)) // if '$yearNo' does contain at least one number
						{
							// extract first number:
							$yearNoStart = preg_replace("/^\D*(\d+).*/", "\\1", $yearNo);
							$query .= " AND year >= " . quote_smart($yearNoStart);

							if (preg_match("/^\D*\d+\D+\d+/", $yearNo)) // if '$yearNo' does contain at least two numbers (which are separated by one or more non-digit characters)
							{
								// extract the second number:
								$yearNoEnd = preg_replace("/^\D*\d+\D+(\d+).*/", "\\1", $yearNo);
								$query .= " AND year <= " . quote_smart($yearNoEnd);
							}
						}
						else // fallback if no number is given:
							$query .= " AND year RLIKE " . quote_smart($yearNo);
					}
				elseif ($yearSelector == "is within list")
					{
						// replace any non-digit chars with "|":
						$yearNo = preg_replace("/\D+/", "|", $yearNo);
						// strip "|" from beginning/end of string (if any):
						$yearNo = preg_replace("/^\|?(.+?)\|?$/", "\\1", $yearNo);
						$query .= " AND year RLIKE " . quote_smart("^(" . $yearNo . ")$");
					}
			}

		// ... if the user has specified an editor, add the value of '$editorName' as an AND clause:
		$editorName = $_REQUEST['editorName'];
		if ($editorName != "")
			{
				$editorSelector = $_REQUEST['editorSelector'];
				if ($editorSelector == "contains")
					$query .= " AND editor RLIKE " . quote_smart($editorName);
				elseif ($editorSelector == "does not contain")
					$query .= " AND editor NOT RLIKE " . quote_smart($editorName);
				elseif ($editorSelector == "is equal to")
					$query .= " AND editor = " . quote_smart($editorName);
				elseif ($editorSelector == "is not equal to")
					$query .= " AND editor != " . quote_smart($editorName);
				elseif ($editorSelector == "starts with")
					$query .= " AND editor RLIKE " . quote_smart("^" . $editorName);
				elseif ($editorSelector == "ends with")
					$query .= " AND editor RLIKE " . quote_smart($editorName . "$");
			}

		// ... if the user has specified a series title, add the value of '$seriesTitleName' as an AND clause:
		$seriesTitleRadio = $_REQUEST['seriesTitleRadio'];
		if ($seriesTitleRadio == "1")
		{
			$seriesTitleName = $_REQUEST['seriesTitleName'];
			if ($seriesTitleName != "All" && $seriesTitleName != "")
				{
					$seriesTitleSelector = $_REQUEST['seriesTitleSelector'];
					if ($seriesTitleSelector == "contains")
						$query .= " AND series_title RLIKE " . quote_smart($seriesTitleName);
					elseif ($seriesTitleSelector == "does not contain")
						$query .= " AND series_title NOT RLIKE " . quote_smart($seriesTitleName);
					elseif ($seriesTitleSelector == "is equal to")
						$query .= " AND series_title = " . quote_smart($seriesTitleName);
					elseif ($seriesTitleSelector == "is not equal to")
						$query .= " AND series_title != " . quote_smart($seriesTitleName);
					elseif ($seriesTitleSelector == "starts with")
						$query .= " AND series_title RLIKE " . quote_smart("^" . $seriesTitleName);
					elseif ($seriesTitleSelector == "ends with")
						$query .= " AND series_title RLIKE " . quote_smart($seriesTitleName . "$");
				}
		}
		elseif ($seriesTitleRadio == "0")
		{
			$seriesTitleName2 = $_REQUEST['seriesTitleName2'];
			if ($seriesTitleName2 != "")
				{
					$seriesTitleSelector2 = $_REQUEST['seriesTitleSelector2'];
					if ($seriesTitleSelector2 == "contains")
						$query .= " AND series_title RLIKE " . quote_smart($seriesTitleName2);
					elseif ($seriesTitleSelector2 == "does not contain")
						$query .= " AND series_title NOT RLIKE " . quote_smart($seriesTitleName2);
					elseif ($seriesTitleSelector2 == "is equal to")
						$query .= " AND series_title = " . quote_smart($seriesTitleName2);
					elseif ($seriesTitleSelector2 == "is not equal to")
						$query .= " AND series_title != " . quote_smart($seriesTitleName2);
					elseif ($seriesTitleSelector2 == "starts with")
						$query .= " AND series_title RLIKE " . quote_smart("^" .$seriesTitleName2);
					elseif ($seriesTitleSelector2 == "ends with")
						$query .= " AND series_title RLIKE " . quote_smart($seriesTitleName2 . "$");
				}
		}

		// ... if the user has specified a series volume, add the value of '$volumeNo' as an AND clause:
		$volumeNo = $_REQUEST['volumeNo'];
		if ($volumeNo != "")
			{
				$volumeSelector = $_REQUEST['volumeSelector'];
				if ($volumeSelector == "contains")
					$query .= " AND series_volume RLIKE " . quote_smart($volumeNo);
				elseif ($volumeSelector == "does not contain")
					$query .= " AND series_volume NOT RLIKE " . quote_smart($volumeNo);
				elseif ($volumeSelector == "is equal to")
					$query .= " AND series_volume = " . quote_smart($volumeNo);
				elseif ($volumeSelector == "is not equal to")
					$query .= " AND series_volume != " . quote_smart($volumeNo);
				elseif ($volumeSelector == "starts with")
					$query .= " AND series_volume RLIKE " . quote_smart("^" . $volumeNo);
				elseif ($volumeSelector == "ends with")
					$query .= " AND series_volume RLIKE " . quote_smart($volumeNo . "$");
				elseif ($volumeSelector == "is greater than")
					$query .= " AND series_volume_numeric > " . quote_smart($volumeNo);
				elseif ($volumeSelector == "is less than")
					$query .= " AND series_volume_numeric < " . quote_smart($volumeNo);
				elseif ($volumeSelector == "is within range")
					{
						if (preg_match("/\d+/", $volumeNo)) // if '$volumeNo' does contain at least one number
						{
							// extract first number:
							$volumeNoStart = preg_replace("/^\D*(\d+).*/", "\\1", $volumeNo);
							$query .= " AND series_volume_numeric >= " . quote_smart($volumeNoStart);

							if (preg_match("/^\D*\d+\D+\d+/", $volumeNo)) // if '$volumeNo' does contain at least two numbers (which are separated by one or more non-digit characters)
							{
								// extract the second number:
								$volumeNoEnd = preg_replace("/^\D*\d+\D+(\d+).*/", "\\1", $volumeNo);
								$query .= " AND series_volume_numeric <= " . quote_smart($volumeNoEnd);
							}
						}
						else // fallback if no number is given:
							$query .= " AND series_volume RLIKE " . quote_smart($volumeNo);
					}
				elseif ($volumeSelector == "is within list")
					{
						// replace any non-digit chars with "|":
						$volumeNo = preg_replace("/\D+/", "|", $volumeNo);
						// strip "|" from beginning/end of string (if any):
						$volumeNo = preg_replace("/^\|?(.+?)\|?$/", "\\1", $volumeNo);
						$query .= " AND series_volume RLIKE " . quote_smart("^(" . $volumeNo . ")$");
					}
			}

		// ... if the user has specified some pages, add the value of '$pagesNo' as an AND clause:
		$pagesNo = $_REQUEST['pagesNo'];
		if ($pagesNo != "")
			{
				$pagesSelector = $_REQUEST['pagesSelector'];
				if ($pagesSelector == "contains")
					$query .= " AND pages RLIKE " . quote_smart($pagesNo);
				elseif ($pagesSelector == "does not contain")
					$query .= " AND pages NOT RLIKE " . quote_smart($pagesNo);
				elseif ($pagesSelector == "is equal to")
					$query .= " AND pages = " . quote_smart($pagesNo);
				elseif ($pagesSelector == "is not equal to")
					$query .= " AND pages != " . quote_smart($pagesNo);
				elseif ($pagesSelector == "starts with")
					$query .= " AND pages RLIKE " . quote_smart("^" . $pagesNo);
				elseif ($pagesSelector == "ends with")
					$query .= " AND pages RLIKE " . quote_smart($pagesNo . "$");
			}

		// ... if the user has specified a publisher, add the value of '$publisherName' as an AND clause:
		$publisherName = $_REQUEST['publisherName'];
		if ($publisherName != "")
			{
				$publisherSelector = $_REQUEST['publisherSelector'];
				if ($publisherSelector == "contains")
					$query .= " AND publisher RLIKE " . quote_smart($publisherName);
				elseif ($publisherSelector == "does not contain")
					$query .= " AND publisher NOT RLIKE " . quote_smart($publisherName);
				elseif ($publisherSelector == "is equal to")
					$query .= " AND publisher = " . quote_smart($publisherName);
				elseif ($publisherSelector == "is not equal to")
					$query .= " AND publisher != " . quote_smart($publisherName);
				elseif ($publisherSelector == "starts with")
					$query .= " AND publisher RLIKE " . quote_smart("^" . $publisherName);
				elseif ($publisherSelector == "ends with")
					$query .= " AND publisher RLIKE " . quote_smart($publisherName . "$");
			}

		// ... if the user has specified a place, add the value of '$placeName' as an AND clause:
		$placeName = $_REQUEST['placeName'];
		if ($placeName != "")
			{
				$placeSelector = $_REQUEST['placeSelector'];
				if ($placeSelector == "contains")
					$query .= " AND place RLIKE " . quote_smart($placeName);
				elseif ($placeSelector == "does not contain")
					$query .= " AND place NOT RLIKE " . quote_smart($placeName);
				elseif ($placeSelector == "is equal to")
					$query .= " AND place = " . quote_smart($placeName);
				elseif ($placeSelector == "is not equal to")
					$query .= " AND place != " . quote_smart($placeName);
				elseif ($placeSelector == "starts with")
					$query .= " AND place RLIKE " . quote_smart("^" . $placeName);
				elseif ($placeSelector == "ends with")
					$query .= " AND place RLIKE " . quote_smart($placeName . "$");
			}

		// ... if the user has specified a call number, add the value of '$callNumberName' as an AND clause:
		$callNumberName = $_REQUEST['callNumberName'];
		if ($callNumberName != "")
			{
				$callNumberSelector = $_REQUEST['callNumberSelector'];
				if ($callNumberSelector == "contains")
					$query .= " AND call_number RLIKE " . quote_smart($callNumberName);
				elseif ($callNumberSelector == "does not contain")
					$query .= " AND call_number NOT RLIKE " . quote_smart($callNumberName);
				elseif ($callNumberSelector == "is equal to")
					$query .= " AND call_number = " . quote_smart($callNumberName);
				elseif ($callNumberSelector == "is not equal to")
					$query .= " AND call_number != " . quote_smart($callNumberName);
				elseif ($callNumberSelector == "starts with")
					$query .= " AND call_number RLIKE " . quote_smart("^" . $callNumberName);
				elseif ($callNumberSelector == "ends with")
					$query .= " AND call_number RLIKE " . quote_smart($callNumberName . "$");
			}

		// ... if the user has specified some keywords, add the value of '$keywordsName' as an AND clause:
		$keywordsName = $_REQUEST['keywordsName'];
		if ($keywordsName != "")
			{
				$keywordsSelector = $_REQUEST['keywordsSelector'];
				if ($keywordsSelector == "contains")
					$query .= " AND keywords RLIKE " . quote_smart($keywordsName);
				elseif ($keywordsSelector == "does not contain")
					$query .= " AND keywords NOT RLIKE " . quote_smart($keywordsName);
				elseif ($keywordsSelector == "is equal to")
					$query .= " AND keywords = " . quote_smart($keywordsName);
				elseif ($keywordsSelector == "is not equal to")
					$query .= " AND keywords != " . quote_smart($keywordsName);
				elseif ($keywordsSelector == "starts with")
					$query .= " AND keywords RLIKE " . quote_smart("^" . $keywordsName);
				elseif ($keywordsSelector == "ends with")
					$query .= " AND keywords RLIKE " . quote_smart($keywordsName . "$");
			}

		// ... if the user has specified some notes, add the value of '$notesName' as an AND clause:
		$notesName = $_REQUEST['notesName'];
		if ($notesName != "")
			{
				$notesSelector = $_REQUEST['notesSelector'];
				if ($notesSelector == "contains")
					$query .= " AND notes RLIKE " . quote_smart($notesName);
				elseif ($notesSelector == "does not contain")
					$query .= " AND notes NOT RLIKE " . quote_smart($notesName);
				elseif ($notesSelector == "is equal to")
					$query .= " AND notes = " . quote_smart($notesName);
				elseif ($notesSelector == "is not equal to")
					$query .= " AND notes != " . quote_smart($notesName);
				elseif ($notesSelector == "starts with")
					$query .= " AND notes RLIKE " . quote_smart("^" . $notesName);
				elseif ($notesSelector == "ends with")
					$query .= " AND notes RLIKE " . quote_smart($notesName . "$");
			}


		// Construct the ORDER BY clause:
		// A) extract first level sort option:
		$sortSelector1 = $_REQUEST['sortSelector1'];
		// when field name = 'pages' then sort by 'first_page' instead:
		$sortSelector1 = str_replace("pages", "first_page", $sortSelector1);

		$sortRadio1 = $_REQUEST['sortRadio1'];
		if ($sortRadio1 == "0") // sort ascending
			$query .= " ORDER BY $sortSelector1";
		else // sort descending
			$query .= " ORDER BY $sortSelector1 DESC";

		// B) extract second level sort option:
		$sortSelector2 = $_REQUEST['sortSelector2'];
		// when field name = 'pages' then sort by 'first_page' instead:
		$sortSelector2 = str_replace("pages", "first_page", $sortSelector2);

		$sortRadio2 = $_REQUEST['sortRadio2'];
		if ($sortRadio2 == "0") // sort ascending
			$query .= ", $sortSelector2";
		else // sort descending
			$query .= ", $sortSelector2 DESC";

		// C) extract third level sort option:
		$sortSelector3 = $_REQUEST['sortSelector3'];
		// when field name = 'pages' then sort by 'first_page' instead:
		$sortSelector3 = str_replace("pages", "first_page", $sortSelector3);

		$sortRadio3 = $_REQUEST['sortRadio3'];
		if ($sortRadio3 == "0") // sort ascending
			$query .= ", $sortSelector3";
		else // sort descending
			$query .= ", $sortSelector3 DESC";


		return $query;
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the 'advanced_search.php' form:
	// TODO: build the complete SQL query using functions 'buildFROMclause()', 'buildWHEREclause()' and 'buildORDERclause()'
	function extractFormElementsAdvanced($showLinks, $loginEmail, $userID)
	{
		global $defaultView; // defined in 'ini.inc.php'
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		// Build SELECT clause:
		if ($defaultView == "List") // honour the user's selection of fields to be displayed in List View
		{
			// Defines a list of all checkbox names that are available in "Advanced Search"
			// and their corresponding column names from MySQL tables 'refs' & 'user_data':
			$columnsArray = array("showAuthor"            => "author",
			                      "showAddress"           => "address",
			                      "showCorporateAuthor"   => "corporate_author",
			                      "showThesis"            => "thesis",
			                      "showTitle"             => "title",
			                      "showOrigTitle"         => "orig_title",
			                      "showYear"              => "year",
			                      "showPublication"       => "publication",
			                      "showAbbrevJournal"     => "abbrev_journal",
			                      "showEditor"            => "editor",
			                      "showVolume"            => "volume",
			                      "showIssue"             => "issue",
			                      "showPages"             => "pages",
			                      "showSeriesTitle"       => "series_title",
			                      "showAbbrevSeriesTitle" => "abbrev_series_title",
			                      "showSeriesEditor"      => "series_editor",
			                      "showSeriesVolume"      => "series_volume",
			                      "showSeriesIssue"       => "series_issue",
			                      "showPublisher"         => "publisher",
			                      "showPlace"             => "place",
			                      "showEdition"           => "edition",
			                      "showMedium"            => "medium",
			                      "showISSN"              => "issn",
			                      "showISBN"              => "isbn",
			                      "showLanguage"          => "language",
			                      "showSummaryLanguage"   => "summary_language",
			                      "showKeywords"          => "keywords",
			                      "showAbstract"          => "abstract",
			                      "showArea"              => "area",
			                      "showExpedition"        => "expedition",
			                      "showConference"        => "conference",
			                      "showDOI"               => "doi",
			                      "showURL"               => "url",
			                      "showLocation"          => "location",
			                      "showCallNumber"        => "call_number",
			                      "showFile"              => "file",
			                      "showCopy"              => "copy",
			                      "showNotes"             => "notes",
			                      "showUserKeys"          => "user_keys",
			                      "showUserNotes"         => "user_notes",
			                      "showUserFile"          => "user_file",
			                      "showUserGroups"        => "user_groups",
			                      "showCiteKey"           => "cite_key",
			                      "showSerial"            => "serial",
			                      "showType"              => "type",
			                      "showMarked"            => "marked",
			                      "showSelected"          => "selected",
			                      "showApproved"          => "approved",
			                      "showCreatedDate"       => "created_date",
			                      "showCreatedTime"       => "created_time",
			                      "showCreatedBy"         => "created_by",
			                      "showModifiedDate"      => "modified_date",
			                      "showModifiedTime"      => "modified_time",
			                      "showModifiedBy"        => "modified_by"
			                     );

			// Add columns given in '$columnsArray' to the list of fields available in the
			// List View SELECT clause if they were marked in the search form interface:
			$selectClauseColumnsArray = addToSelectClause($columnsArray);

			$query = buildSELECTclause($defaultView, $showLinks, "", false, true, implode(", ", $selectClauseColumnsArray));
		}
		else
			$query = buildSELECTclause($defaultView, $showLinks);


		// Build FROM clause:
		if (isset($_SESSION['loginEmail'])) // if a user is logged in...
			$query .= " FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = " . $userID;
		else // NO user logged in
			$query .= " FROM $tableRefs";


		// Build WHERE clause:
		$query .= " WHERE serial RLIKE \".+\""; // add initial WHERE clause

		// ... if the user has specified an author, add the value of '$authorName' as an AND clause:
		$authorName = $_REQUEST['authorName'];
		if ($authorName != "")
			{
				$authorSelector = $_REQUEST['authorSelector'];
				if ($authorSelector == "contains")
					$query .= " AND author RLIKE " . quote_smart($authorName);
				elseif ($authorSelector == "does not contain")
					$query .= " AND author NOT RLIKE " . quote_smart($authorName);
				elseif ($authorSelector == "is equal to")
					$query .= " AND author = " . quote_smart($authorName);
				elseif ($authorSelector == "is not equal to")
					$query .= " AND author != " . quote_smart($authorName);
				elseif ($authorSelector == "starts with")
					$query .= " AND author RLIKE " . quote_smart("^" . $authorName);
				elseif ($authorSelector == "ends with")
					$query .= " AND author RLIKE " . quote_smart($authorName . "$");
			}

		// ... if the user has specified an address, add the value of '$addressName' as an AND clause:
		$addressName = $_REQUEST['addressName'];
		if ($addressName != "")
			{
				$addressSelector = $_REQUEST['addressSelector'];
				if ($addressSelector == "contains")
					$query .= " AND address RLIKE " . quote_smart($addressName);
				elseif ($addressSelector == "does not contain")
					$query .= " AND address NOT RLIKE " . quote_smart($addressName);
				elseif ($addressSelector == "is equal to")
					$query .= " AND address = " . quote_smart($addressName);
				elseif ($addressSelector == "is not equal to")
					$query .= " AND address != " . quote_smart($addressName);
				elseif ($addressSelector == "starts with")
					$query .= " AND address RLIKE " . quote_smart("^" . $addressName);
				elseif ($addressSelector == "ends with")
					$query .= " AND address RLIKE " . quote_smart($addressName . "$");
			}

		// ... if the user has specified a corporate author, add the value of '$corporateAuthorName' as an AND clause:
		$corporateAuthorName = $_REQUEST['corporateAuthorName'];
		if ($corporateAuthorName != "")
			{
				$corporateAuthorSelector = $_REQUEST['corporateAuthorSelector'];
				if ($corporateAuthorSelector == "contains")
					$query .= " AND corporate_author RLIKE " . quote_smart($corporateAuthorName);
				elseif ($corporateAuthorSelector == "does not contain")
					$query .= " AND corporate_author NOT RLIKE " . quote_smart($corporateAuthorName);
				elseif ($corporateAuthorSelector == "is equal to")
					$query .= " AND corporate_author = " . quote_smart($corporateAuthorName);
				elseif ($corporateAuthorSelector == "is not equal to")
					$query .= " AND corporate_author != " . quote_smart($corporateAuthorName);
				elseif ($corporateAuthorSelector == "starts with")
					$query .= " AND corporate_author RLIKE " . quote_smart("^" . $corporateAuthorName);
				elseif ($corporateAuthorSelector == "ends with")
					$query .= " AND corporate_author RLIKE " . quote_smart($corporateAuthorName . "$");
			}

		// ... if the user has specified a thesis, add the value of '$thesisName' as an AND clause:
		$thesisRadio = $_REQUEST['thesisRadio'];
		if ($thesisRadio == "1")
		{
			$thesisName = $_REQUEST['thesisName'];
			if ($thesisName != "All" && $thesisName != "")
				{
					$thesisSelector = $_REQUEST['thesisSelector'];
					if ($thesisSelector == "contains")
						$query .= " AND thesis RLIKE " . quote_smart($thesisName);
					elseif ($thesisSelector == "does not contain")
						$query .= " AND thesis NOT RLIKE " . quote_smart($thesisName);
					elseif ($thesisSelector == "is equal to")
						$query .= " AND thesis = " . quote_smart($thesisName);
					elseif ($thesisSelector == "is not equal to")
						$query .= " AND thesis != " . quote_smart($thesisName);
					elseif ($thesisSelector == "starts with")
						$query .= " AND thesis RLIKE " . quote_smart("^" . $thesisName);
					elseif ($thesisSelector == "ends with")
						$query .= " AND thesis RLIKE " . quote_smart($thesisName . "$");
				}
		}
		elseif ($thesisRadio == "0")
		{
			$thesisName2 = $_REQUEST['thesisName2'];
			if ($thesisName2 != "")
				{
					$thesisSelector2 = $_REQUEST['thesisSelector2'];
					if ($thesisSelector2 == "contains")
						$query .= " AND thesis RLIKE " . quote_smart($thesisName2);
					elseif ($thesisSelector2 == "does not contain")
						$query .= " AND thesis NOT RLIKE " . quote_smart($thesisName2);
					elseif ($thesisSelector2 == "is equal to")
						$query .= " AND thesis = " . quote_smart($thesisName2);
					elseif ($thesisSelector2 == "is not equal to")
						$query .= " AND thesis != " . quote_smart($thesisName2);
					elseif ($thesisSelector2 == "starts with")
						$query .= " AND thesis RLIKE " . quote_smart("^" . $thesisName2);
					elseif ($thesisSelector2 == "ends with")
						$query .= " AND thesis RLIKE " . quote_smart($thesisName2 . "$");
				}
		}

		// ... if the user has specified a title, add the value of '$titleName' as an AND clause:
		$titleName = $_REQUEST['titleName'];
		if ($titleName != "")
			{
				$titleSelector = $_REQUEST['titleSelector'];
				if ($titleSelector == "contains")
					$query .= " AND title RLIKE " . quote_smart($titleName);
				elseif ($titleSelector == "does not contain")
					$query .= " AND title NOT RLIKE " . quote_smart($titleName);
				elseif ($titleSelector == "is equal to")
					$query .= " AND title = " . quote_smart($titleName);
				elseif ($titleSelector == "is not equal to")
					$query .= " AND title != " . quote_smart($titleName);
				elseif ($titleSelector == "starts with")
					$query .= " AND title RLIKE " . quote_smart("^" . $titleName);
				elseif ($titleSelector == "ends with")
					$query .= " AND title RLIKE " . quote_smart($titleName . "$");
			}

		// ... if the user has specified an original title, add the value of '$origTitleName' as an AND clause:
		$origTitleName = $_REQUEST['origTitleName'];
		if ($origTitleName != "")
			{
				$origTitleSelector = $_REQUEST['origTitleSelector'];
				if ($origTitleSelector == "contains")
					$query .= " AND orig_title RLIKE " . quote_smart($origTitleName);
				elseif ($origTitleSelector == "does not contain")
					$query .= " AND orig_title NOT RLIKE " . quote_smart($origTitleName);
				elseif ($origTitleSelector == "is equal to")
					$query .= " AND orig_title = " . quote_smart($origTitleName);
				elseif ($origTitleSelector == "is not equal to")
					$query .= " AND orig_title != " . quote_smart($origTitleName);
				elseif ($origTitleSelector == "starts with")
					$query .= " AND orig_title RLIKE " . quote_smart("^" . $origTitleName);
				elseif ($origTitleSelector == "ends with")
					$query .= " AND orig_title RLIKE " . quote_smart($origTitleName . "$");
			}

		// ... if the user has specified a year, add the value of '$yearNo' as an AND clause:
		$yearNo = $_REQUEST['yearNo'];
		if ($yearNo != "")
			{
				$yearSelector = $_REQUEST['yearSelector'];
				if ($yearSelector == "contains")
					$query .= " AND year RLIKE " . quote_smart($yearNo);
				elseif ($yearSelector == "does not contain")
					$query .= " AND year NOT RLIKE " . quote_smart($yearNo);
				elseif ($yearSelector == "is equal to")
					$query .= " AND year = " . quote_smart($yearNo);
				elseif ($yearSelector == "is not equal to")
					$query .= " AND year != " . quote_smart($yearNo);
				elseif ($yearSelector == "starts with")
					$query .= " AND year RLIKE " . quote_smart("^" . $yearNo);
				elseif ($yearSelector == "ends with")
					$query .= " AND year RLIKE " . quote_smart($yearNo . "$");
				elseif ($yearSelector == "is greater than")
					$query .= " AND year > " . quote_smart($yearNo);
				elseif ($yearSelector == "is less than")
					$query .= " AND year < " . quote_smart($yearNo);
				elseif ($yearSelector == "is within range")
					{
						if (preg_match("/\d+/", $yearNo)) // if '$yearNo' does contain at least one number
						{
							// extract first number:
							$yearNoStart = preg_replace("/^\D*(\d+).*/", "\\1", $yearNo);
							$query .= " AND year >= " . quote_smart($yearNoStart);

							if (preg_match("/^\D*\d+\D+\d+/", $yearNo)) // if '$yearNo' does contain at least two numbers (which are separated by one or more non-digit characters)
							{
								// extract the second number:
								$yearNoEnd = preg_replace("/^\D*\d+\D+(\d+).*/", "\\1", $yearNo);
								$query .= " AND year <= " . quote_smart($yearNoEnd);
							}
						}
						else // fallback if no number is given:
							$query .= " AND year RLIKE " . quote_smart($yearNo);
					}
				elseif ($yearSelector == "is within list")
					{
						// replace any non-digit chars with "|":
						$yearNo = preg_replace("/\D+/", "|", $yearNo);
						// strip "|" from beginning/end of string (if any):
						$yearNo = preg_replace("/^\|?(.+?)\|?$/", "\\1", $yearNo);
						$query .= " AND year RLIKE " . quote_smart("^(" . $yearNo . ")$");
					}
			}

		// ... if the user has specified a publication, add the value of '$publicationName' as an AND clause:
		$publicationRadio = $_REQUEST['publicationRadio'];
		if ($publicationRadio == "1")
		{
			$publicationName = $_REQUEST['publicationName'];
			if ($publicationName != "All" && $publicationName != "")
				{
					$publicationSelector = $_REQUEST['publicationSelector'];
					if ($publicationSelector == "contains")
						$query .= " AND publication RLIKE " . quote_smart($publicationName);
					elseif ($publicationSelector == "does not contain")
						$query .= " AND publication NOT RLIKE " . quote_smart($publicationName);
					elseif ($publicationSelector == "is equal to")
						$query .= " AND publication = " . quote_smart($publicationName);
					elseif ($publicationSelector == "is not equal to")
						$query .= " AND publication != " . quote_smart($publicationName);
					elseif ($publicationSelector == "starts with")
						$query .= " AND publication RLIKE " . quote_smart("^" . $publicationName);
					elseif ($publicationSelector == "ends with")
						$query .= " AND publication RLIKE " . quote_smart($publicationName . "$");
				}
		}
		elseif ($publicationRadio == "0")
		{
			$publicationName2 = $_REQUEST['publicationName2'];
			if ($publicationName2 != "")
				{
					$publicationSelector2 = $_REQUEST['publicationSelector2'];
					if ($publicationSelector2 == "contains")
						$query .= " AND publication RLIKE " . quote_smart($publicationName2);
					elseif ($publicationSelector2 == "does not contain")
						$query .= " AND publication NOT RLIKE " . quote_smart($publicationName2);
					elseif ($publicationSelector2 == "is equal to")
						$query .= " AND publication = " . quote_smart($publicationName2);
					elseif ($publicationSelector2 == "is not equal to")
						$query .= " AND publication != " . quote_smart($publicationName2);
					elseif ($publicationSelector2 == "starts with")
						$query .= " AND publication RLIKE " . quote_smart("^" . $publicationName2);
					elseif ($publicationSelector2 == "ends with")
						$query .= " AND publication RLIKE " . quote_smart($publicationName2 . "$");
				}
		}

		// ... if the user has specified an abbreviated journal, add the value of '$abbrevJournalName' as an AND clause:
		$abbrevJournalRadio = $_REQUEST['abbrevJournalRadio'];
		if ($abbrevJournalRadio == "1")
		{
			$abbrevJournalName = $_REQUEST['abbrevJournalName'];
			if ($abbrevJournalName != "All" && $abbrevJournalName != "")
				{
					$abbrevJournalSelector = $_REQUEST['abbrevJournalSelector'];
					if ($abbrevJournalSelector == "contains")
						$query .= " AND abbrev_journal RLIKE " . quote_smart($abbrevJournalName);
					elseif ($abbrevJournalSelector == "does not contain")
						$query .= " AND abbrev_journal NOT RLIKE " . quote_smart($abbrevJournalName);
					elseif ($abbrevJournalSelector == "is equal to")
						$query .= " AND abbrev_journal = " . quote_smart($abbrevJournalName);
					elseif ($abbrevJournalSelector == "is not equal to")
						$query .= " AND abbrev_journal != " . quote_smart($abbrevJournalName);
					elseif ($abbrevJournalSelector == "starts with")
						$query .= " AND abbrev_journal RLIKE " . quote_smart("^" . $abbrevJournalName);
					elseif ($abbrevJournalSelector == "ends with")
						$query .= " AND abbrev_journal RLIKE " . quote_smart($abbrevJournalName . "$");
				}
		}
		elseif ($abbrevJournalRadio == "0")
		{
			$abbrevJournalName2 = $_REQUEST['abbrevJournalName2'];
			if ($abbrevJournalName2 != "")
				{
					$abbrevJournalSelector2 = $_REQUEST['abbrevJournalSelector2'];
					if ($abbrevJournalSelector2 == "contains")
						$query .= " AND abbrev_journal RLIKE " . quote_smart($abbrevJournalName2);
					elseif ($abbrevJournalSelector2 == "does not contain")
						$query .= " AND abbrev_journal NOT RLIKE " . quote_smart($abbrevJournalName2);
					elseif ($abbrevJournalSelector2 == "is equal to")
						$query .= " AND abbrev_journal = " . quote_smart($abbrevJournalName2);
					elseif ($abbrevJournalSelector2 == "is not equal to")
						$query .= " AND abbrev_journal != " . quote_smart($abbrevJournalName2);
					elseif ($abbrevJournalSelector2 == "starts with")
						$query .= " AND abbrev_journal RLIKE " . quote_smart("^" . $abbrevJournalName2);
					elseif ($abbrevJournalSelector2 == "ends with")
						$query .= " AND abbrev_journal RLIKE " . quote_smart($abbrevJournalName2 . "$");
				}
		}

		// ... if the user has specified an editor, add the value of '$editorName' as an AND clause:
		$editorName = $_REQUEST['editorName'];
		if ($editorName != "")
			{
				$editorSelector = $_REQUEST['editorSelector'];
				if ($editorSelector == "contains")
					$query .= " AND editor RLIKE " . quote_smart($editorName);
				elseif ($editorSelector == "does not contain")
					$query .= " AND editor NOT RLIKE " . quote_smart($editorName);
				elseif ($editorSelector == "is equal to")
					$query .= " AND editor = " . quote_smart($editorName);
				elseif ($editorSelector == "is not equal to")
					$query .= " AND editor != " . quote_smart($editorName);
				elseif ($editorSelector == "starts with")
					$query .= " AND editor RLIKE " . quote_smart("^" . $editorName);
				elseif ($editorSelector == "ends with")
					$query .= " AND editor RLIKE " . quote_smart($editorName . "$");
			}

		// ... if the user has specified a volume, add the value of '$volumeNo' as an AND clause:
		$volumeNo = $_REQUEST['volumeNo'];
		if ($volumeNo != "")
			{
				$volumeSelector = $_REQUEST['volumeSelector'];
				if ($volumeSelector == "contains")
					$query .= " AND volume RLIKE " . quote_smart($volumeNo);
				elseif ($volumeSelector == "does not contain")
					$query .= " AND volume NOT RLIKE " . quote_smart($volumeNo);
				elseif ($volumeSelector == "is equal to")
					$query .= " AND volume = " . quote_smart($volumeNo);
				elseif ($volumeSelector == "is not equal to")
					$query .= " AND volume != " . quote_smart($volumeNo);
				elseif ($volumeSelector == "starts with")
					$query .= " AND volume RLIKE " . quote_smart("^" . $volumeNo);
				elseif ($volumeSelector == "ends with")
					$query .= " AND volume RLIKE " . quote_smart($volumeNo . "$");
				elseif ($volumeSelector == "is greater than")
					$query .= " AND volume_numeric > " . quote_smart($volumeNo);
				elseif ($volumeSelector == "is less than")
					$query .= " AND volume_numeric < " . quote_smart($volumeNo);
				elseif ($volumeSelector == "is within range")
					{
						if (preg_match("/\d+/", $volumeNo)) // if '$volumeNo' does contain at least one number
						{
							// extract first number:
							$volumeNoStart = preg_replace("/^\D*(\d+).*/", "\\1", $volumeNo);
							$query .= " AND volume_numeric >= " . quote_smart($volumeNoStart);

							if (preg_match("/^\D*\d+\D+\d+/", $volumeNo)) // if '$volumeNo' does contain at least two numbers (which are separated by one or more non-digit characters)
							{
								// extract the second number:
								$volumeNoEnd = preg_replace("/^\D*\d+\D+(\d+).*/", "\\1", $volumeNo);
								$query .= " AND volume_numeric <= " . quote_smart($volumeNoEnd);
							}
						}
						else // fallback if no number is given:
							$query .= " AND volume RLIKE " . quote_smart($volumeNo);
					}
				elseif ($volumeSelector == "is within list")
					{
						// replace any non-digit chars with "|":
						$volumeNo = preg_replace("/\D+/", "|", $volumeNo);
						// strip "|" from beginning/end of string (if any):
						$volumeNo = preg_replace("/^\|?(.+?)\|?$/", "\\1", $volumeNo);
						$query .= " AND volume RLIKE " . quote_smart("^(" . $volumeNo . ")$");
					}
			}

		// ... if the user has specified an issue, add the value of '$issueNo' as an AND clause:
		$issueNo = $_REQUEST['issueNo'];
		if ($issueNo != "")
			{
				$issueSelector = $_REQUEST['issueSelector'];
				if ($issueSelector == "contains")
					$query .= " AND issue RLIKE " . quote_smart($issueNo);
				elseif ($issueSelector == "does not contain")
					$query .= " AND issue NOT RLIKE " . quote_smart($issueNo);
				elseif ($issueSelector == "is equal to")
					$query .= " AND issue = " . quote_smart($issueNo);
				elseif ($issueSelector == "is not equal to")
					$query .= " AND issue != " . quote_smart($issueNo);
				elseif ($issueSelector == "starts with")
					$query .= " AND issue RLIKE " . quote_smart("^" . $issueNo);
				elseif ($issueSelector == "ends with")
					$query .= " AND issue RLIKE " . quote_smart($issueNo . "$");
			}

		// ... if the user has specified some pages, add the value of '$pagesNo' as an AND clause:
		$pagesNo = $_REQUEST['pagesNo'];
		if ($pagesNo != "")
			{
				$pagesSelector = $_REQUEST['pagesSelector'];
				if ($pagesSelector == "contains")
					$query .= " AND pages RLIKE " . quote_smart($pagesNo);
				elseif ($pagesSelector == "does not contain")
					$query .= " AND pages NOT RLIKE " . quote_smart($pagesNo);
				elseif ($pagesSelector == "is equal to")
					$query .= " AND pages = " . quote_smart($pagesNo);
				elseif ($pagesSelector == "is not equal to")
					$query .= " AND pages != " . quote_smart($pagesNo);
				elseif ($pagesSelector == "starts with")
					$query .= " AND pages RLIKE " . quote_smart("^" . $pagesNo);
				elseif ($pagesSelector == "ends with")
					$query .= " AND pages RLIKE " . quote_smart($pagesNo . "$");
			}


		// ... if the user has specified a series title, add the value of '$seriesTitleName' as an AND clause:
		$seriesTitleRadio = $_REQUEST['seriesTitleRadio'];
		if ($seriesTitleRadio == "1")
		{
			$seriesTitleName = $_REQUEST['seriesTitleName'];
			if ($seriesTitleName != "All" && $seriesTitleName != "")
				{
					$seriesTitleSelector = $_REQUEST['seriesTitleSelector'];
					if ($seriesTitleSelector == "contains")
						$query .= " AND series_title RLIKE " . quote_smart($seriesTitleName);
					elseif ($seriesTitleSelector == "does not contain")
						$query .= " AND series_title NOT RLIKE " . quote_smart($seriesTitleName);
					elseif ($seriesTitleSelector == "is equal to")
						$query .= " AND series_title = " . quote_smart($seriesTitleName);
					elseif ($seriesTitleSelector == "is not equal to")
						$query .= " AND series_title != " . quote_smart($seriesTitleName);
					elseif ($seriesTitleSelector == "starts with")
						$query .= " AND series_title RLIKE " . quote_smart("^" . $seriesTitleName);
					elseif ($seriesTitleSelector == "ends with")
						$query .= " AND series_title RLIKE " . quote_smart($seriesTitleName . "$");
				}
		}
		elseif ($seriesTitleRadio == "0")
		{
			$seriesTitleName2 = $_REQUEST['seriesTitleName2'];
			if ($seriesTitleName2 != "")
				{
					$seriesTitleSelector2 = $_REQUEST['seriesTitleSelector2'];
					if ($seriesTitleSelector2 == "contains")
						$query .= " AND series_title RLIKE " . quote_smart($seriesTitleName2);
					elseif ($seriesTitleSelector2 == "does not contain")
						$query .= " AND series_title NOT RLIKE " . quote_smart($seriesTitleName2);
					elseif ($seriesTitleSelector2 == "is equal to")
						$query .= " AND series_title = " . quote_smart($seriesTitleName2);
					elseif ($seriesTitleSelector2 == "is not equal to")
						$query .= " AND series_title != " . quote_smart($seriesTitleName2);
					elseif ($seriesTitleSelector2 == "starts with")
						$query .= " AND series_title RLIKE " . quote_smart("^" . $seriesTitleName2);
					elseif ($seriesTitleSelector2 == "ends with")
						$query .= " AND series_title RLIKE " . quote_smart($seriesTitleName2 . "$");
				}
		}

		// ... if the user has specified an abbreviated series title, add the value of '$abbrevSeriesTitleName' as an AND clause:
		$abbrevSeriesTitleRadio = $_REQUEST['abbrevSeriesTitleRadio'];
		if ($abbrevSeriesTitleRadio == "1")
		{
			$abbrevSeriesTitleName = $_REQUEST['abbrevSeriesTitleName'];
			if ($abbrevSeriesTitleName != "All" && $abbrevSeriesTitleName != "")
				{
					$abbrevSeriesTitleSelector = $_REQUEST['abbrevSeriesTitleSelector'];
					if ($abbrevSeriesTitleSelector == "contains")
						$query .= " AND abbrev_series_title RLIKE " . quote_smart($abbrevSeriesTitleName);
					elseif ($abbrevSeriesTitleSelector == "does not contain")
						$query .= " AND abbrev_series_title NOT RLIKE " . quote_smart($abbrevSeriesTitleName);
					elseif ($abbrevSeriesTitleSelector == "is equal to")
						$query .= " AND abbrev_series_title = " . quote_smart($abbrevSeriesTitleName);
					elseif ($abbrevSeriesTitleSelector == "is not equal to")
						$query .= " AND abbrev_series_title != " . quote_smart($abbrevSeriesTitleName);
					elseif ($abbrevSeriesTitleSelector == "starts with")
						$query .= " AND abbrev_series_title RLIKE " . quote_smart("^" . $abbrevSeriesTitleName);
					elseif ($abbrevSeriesTitleSelector == "ends with")
						$query .= " AND abbrev_series_title RLIKE " . quote_smart($abbrevSeriesTitleName . "$");
				}
		}
		elseif ($abbrevSeriesTitleRadio == "0")
		{
			$abbrevSeriesTitleName2 = $_REQUEST['abbrevSeriesTitleName2'];
			if ($abbrevSeriesTitleName2 != "")
				{
					$abbrevSeriesTitleSelector2 = $_REQUEST['abbrevSeriesTitleSelector2'];
					if ($abbrevSeriesTitleSelector2 == "contains")
						$query .= " AND abbrev_series_title RLIKE " . quote_smart($abbrevSeriesTitleName2);
					elseif ($abbrevSeriesTitleSelector2 == "does not contain")
						$query .= " AND abbrev_series_title NOT RLIKE " . quote_smart($abbrevSeriesTitleName2);
					elseif ($abbrevSeriesTitleSelector2 == "is equal to")
						$query .= " AND abbrev_series_title = " . quote_smart($abbrevSeriesTitleName2);
					elseif ($abbrevSeriesTitleSelector2 == "is not equal to")
						$query .= " AND abbrev_series_title != " . quote_smart($abbrevSeriesTitleName2);
					elseif ($abbrevSeriesTitleSelector2 == "starts with")
						$query .= " AND abbrev_series_title RLIKE " . quote_smart("^" . $abbrevSeriesTitleName2);
					elseif ($abbrevSeriesTitleSelector2 == "ends with")
						$query .= " AND abbrev_series_title RLIKE " . quote_smart($abbrevSeriesTitleName2 . "$");
				}
		}

		// ... if the user has specified a series editor, add the value of '$seriesEditorName' as an AND clause:
		$seriesEditorName = $_REQUEST['seriesEditorName'];
		if ($seriesEditorName != "")
			{
				$seriesEditorSelector = $_REQUEST['seriesEditorSelector'];
				if ($seriesEditorSelector == "contains")
					$query .= " AND series_editor RLIKE " . quote_smart($seriesEditorName);
				elseif ($seriesEditorSelector == "does not contain")
					$query .= " AND series_editor NOT RLIKE " . quote_smart($seriesEditorName);
				elseif ($seriesEditorSelector == "is equal to")
					$query .= " AND series_editor = " . quote_smart($seriesEditorName);
				elseif ($seriesEditorSelector == "is not equal to")
					$query .= " AND series_editor != " . quote_smart($seriesEditorName);
				elseif ($seriesEditorSelector == "starts with")
					$query .= " AND series_editor RLIKE " . quote_smart("^" . $seriesEditorName);
				elseif ($seriesEditorSelector == "ends with")
					$query .= " AND series_editor RLIKE " . quote_smart($seriesEditorName . "$");
			}


		// ... if the user has specified a series volume, add the value of '$seriesVolumeNo' as an AND clause:
		$seriesVolumeNo = $_REQUEST['seriesVolumeNo'];
		if ($seriesVolumeNo != "")
			{
				$seriesVolumeSelector = $_REQUEST['seriesVolumeSelector'];
				if ($seriesVolumeSelector == "contains")
					$query .= " AND series_volume RLIKE " . quote_smart($seriesVolumeNo);
				elseif ($seriesVolumeSelector == "does not contain")
					$query .= " AND series_volume NOT RLIKE " . quote_smart($seriesVolumeNo);
				elseif ($seriesVolumeSelector == "is equal to")
					$query .= " AND series_volume = " . quote_smart($seriesVolumeNo);
				elseif ($seriesVolumeSelector == "is not equal to")
					$query .= " AND series_volume != " . quote_smart($seriesVolumeNo);
				elseif ($seriesVolumeSelector == "starts with")
					$query .= " AND series_volume RLIKE " . quote_smart("^" . $seriesVolumeNo);
				elseif ($seriesVolumeSelector == "ends with")
					$query .= " AND series_volume RLIKE " . quote_smart($seriesVolumeNo . "$");
				elseif ($seriesVolumeSelector == "is greater than")
					$query .= " AND series_volume_numeric > " . quote_smart($seriesVolumeNo);
				elseif ($seriesVolumeSelector == "is less than")
					$query .= " AND series_volume_numeric < " . quote_smart($seriesVolumeNo);
				elseif ($seriesVolumeSelector == "is within range")
					{
						if (preg_match("/\d+/", $seriesVolumeNo)) // if '$seriesVolumeNo' does contain at least one number
						{
							// extract first number:
							$seriesVolumeNoStart = preg_replace("/^\D*(\d+).*/", "\\1", $seriesVolumeNo);
							$query .= " AND series_volume_numeric >= " . quote_smart($seriesVolumeNoStart);

							if (preg_match("/^\D*\d+\D+\d+/", $seriesVolumeNo)) // if '$seriesVolumeNo' does contain at least two numbers (which are separated by one or more non-digit characters)
							{
								// extract the second number:
								$seriesVolumeNoEnd = preg_replace("/^\D*\d+\D+(\d+).*/", "\\1", $seriesVolumeNo);
								$query .= " AND series_volume_numeric <= " . quote_smart($seriesVolumeNoEnd);
							}
						}
						else // fallback if no number is given:
							$query .= " AND series_volume RLIKE " . quote_smart($seriesVolumeNo);
					}
				elseif ($seriesVolumeSelector == "is within list")
					{
						// replace any non-digit chars with "|":
						$seriesVolumeNo = preg_replace("/\D+/", "|", $seriesVolumeNo);
						// strip "|" from beginning/end of string (if any):
						$seriesVolumeNo = preg_replace("/^\|?(.+?)\|?$/", "\\1", $seriesVolumeNo);
						$query .= " AND series_volume RLIKE " . quote_smart("^(" . $seriesVolumeNo . ")$");
					}
			}

		// ... if the user has specified a series issue, add the value of '$seriesIssueNo' as an AND clause:
		$seriesIssueNo = $_REQUEST['seriesIssueNo'];
		if ($seriesIssueNo != "")
			{
				$seriesIssueSelector = $_REQUEST['seriesIssueSelector'];
				if ($seriesIssueSelector == "contains")
					$query .= " AND series_issue RLIKE " . quote_smart($seriesIssueNo);
				elseif ($seriesIssueSelector == "does not contain")
					$query .= " AND series_issue NOT RLIKE " . quote_smart($seriesIssueNo);
				elseif ($seriesIssueSelector == "is equal to")
					$query .= " AND series_issue = " . quote_smart($seriesIssueNo);
				elseif ($seriesIssueSelector == "is not equal to")
					$query .= " AND series_issue != " . quote_smart($seriesIssueNo);
				elseif ($seriesIssueSelector == "starts with")
					$query .= " AND series_issue RLIKE " . quote_smart("^" . $seriesIssueNo);
				elseif ($seriesIssueSelector == "ends with")
					$query .= " AND series_issue RLIKE " . quote_smart($seriesIssueNo . "$");
			}

		// ... if the user has specified a publisher, add the value of '$publisherName' as an AND clause:
		$publisherRadio = $_REQUEST['publisherRadio'];
		if ($publisherRadio == "1")
		{
			$publisherName = $_REQUEST['publisherName'];
			if ($publisherName != "All" && $publisherName != "")
				{
					$publisherSelector = $_REQUEST['publisherSelector'];
					if ($publisherSelector == "contains")
						$query .= " AND publisher RLIKE " . quote_smart($publisherName);
					elseif ($publisherSelector == "does not contain")
						$query .= " AND publisher NOT RLIKE " . quote_smart($publisherName);
					elseif ($publisherSelector == "is equal to")
						$query .= " AND publisher = " . quote_smart($publisherName);
					elseif ($publisherSelector == "is not equal to")
						$query .= " AND publisher != " . quote_smart($publisherName);
					elseif ($publisherSelector == "starts with")
						$query .= " AND publisher RLIKE " . quote_smart("^" . $publisherName);
					elseif ($publisherSelector == "ends with")
						$query .= " AND publisher RLIKE " . quote_smart($publisherName . "$");
				}
		}
		elseif ($publisherRadio == "0")
		{
			$publisherName2 = $_REQUEST['publisherName2'];
			if ($publisherName2 != "")
				{
					$publisherSelector2 = $_REQUEST['publisherSelector2'];
					if ($publisherSelector2 == "contains")
						$query .= " AND publisher RLIKE " . quote_smart($publisherName2);
					elseif ($publisherSelector2 == "does not contain")
						$query .= " AND publisher NOT RLIKE " . quote_smart($publisherName2);
					elseif ($publisherSelector2 == "is equal to")
						$query .= " AND publisher = " . quote_smart($publisherName2);
					elseif ($publisherSelector2 == "is not equal to")
						$query .= " AND publisher != " . quote_smart($publisherName2);
					elseif ($publisherSelector2 == "starts with")
						$query .= " AND publisher RLIKE " . quote_smart("^" . $publisherName2);
					elseif ($publisherSelector2 == "ends with")
						$query .= " AND publisher RLIKE " . quote_smart($publisherName2 . "$");
				}
		}

		// ... if the user has specified a place, add the value of '$placeName' as an AND clause:
		$placeRadio = $_REQUEST['placeRadio'];
		if ($placeRadio == "1")
		{
			$placeName = $_REQUEST['placeName'];
			if ($placeName != "All" && $placeName != "")
				{
					$placeSelector = $_REQUEST['placeSelector'];
					if ($placeSelector == "contains")
						$query .= " AND place RLIKE " . quote_smart($placeName);
					elseif ($placeSelector == "does not contain")
						$query .= " AND place NOT RLIKE " . quote_smart($placeName);
					elseif ($placeSelector == "is equal to")
						$query .= " AND place = " . quote_smart($placeName);
					elseif ($placeSelector == "is not equal to")
						$query .= " AND place != " . quote_smart($placeName);
					elseif ($placeSelector == "starts with")
						$query .= " AND place RLIKE " . quote_smart("^" . $placeName);
					elseif ($placeSelector == "ends with")
						$query .= " AND place RLIKE " . quote_smart($placeName . "$");
				}
		}
		elseif ($placeRadio == "0")
		{
			$placeName2 = $_REQUEST['placeName2'];
			if ($placeName2 != "")
				{
					$placeSelector2 = $_REQUEST['placeSelector2'];
					if ($placeSelector2 == "contains")
						$query .= " AND place RLIKE " . quote_smart($placeName2);
					elseif ($placeSelector2 == "does not contain")
						$query .= " AND place NOT RLIKE " . quote_smart($placeName2);
					elseif ($placeSelector2 == "is equal to")
						$query .= " AND place = " . quote_smart($placeName2);
					elseif ($placeSelector2 == "is not equal to")
						$query .= " AND place != " . quote_smart($placeName2);
					elseif ($placeSelector2 == "starts with")
						$query .= " AND place RLIKE " . quote_smart("^" . $placeName2);
					elseif ($placeSelector2 == "ends with")
						$query .= " AND place RLIKE " . quote_smart($placeName2 . "$");
				}
		}

		// ... if the user has specified an edition, add the value of '$editionNo' as an AND clause:
		$editionNo = $_REQUEST['editionNo'];
		if ($editionNo != "")
			{
				$editionSelector = $_REQUEST['editionSelector'];
				if ($editionSelector == "contains")
					$query .= " AND edition RLIKE " . quote_smart($editionNo);
				elseif ($editionSelector == "does not contain")
					$query .= " AND edition NOT RLIKE " . quote_smart($editionNo);
				elseif ($editionSelector == "is equal to")
					$query .= " AND edition = " . quote_smart($editionNo);
				elseif ($editionSelector == "is not equal to")
					$query .= " AND edition != " . quote_smart($editionNo);
				elseif ($editionSelector == "starts with")
					$query .= " AND edition RLIKE " . quote_smart("^" . $editionNo);
				elseif ($editionSelector == "ends with")
					$query .= " AND edition RLIKE " . quote_smart($editionNo . "$");
				elseif ($editionSelector == "is greater than")
					$query .= " AND edition > " . quote_smart($editionNo);
				elseif ($editionSelector == "is less than")
					$query .= " AND edition < " . quote_smart($editionNo);
				elseif ($editionSelector == "is within range")
					{
						if (preg_match("/\d+/", $editionNo)) // if '$editionNo' does contain at least one number
						{
							// extract first number:
							$editionNoStart = preg_replace("/^\D*(\d+).*/", "\\1", $editionNo);
							$query .= " AND edition >= " . quote_smart($editionNoStart);

							if (preg_match("/^\D*\d+\D+\d+/", $editionNo)) // if '$editionNo' does contain at least two numbers (which are separated by one or more non-digit characters)
							{
								// extract the second number:
								$editionNoEnd = preg_replace("/^\D*\d+\D+(\d+).*/", "\\1", $editionNo);
								$query .= " AND edition <= " . quote_smart($editionNoEnd);
							}
						}
						else // fallback if no number is given:
							$query .= " AND edition RLIKE " . quote_smart($editionNo);
					}
				elseif ($editionSelector == "is within list")
					{
						// replace any non-digit chars with "|":
						$editionNo = preg_replace("/\D+/", "|", $editionNo);
						// strip "|" from beginning/end of string (if any):
						$editionNo = preg_replace("/^\|?(.+?)\|?$/", "\\1", $editionNo);
						$query .= " AND edition RLIKE " . quote_smart("^(" . $editionNo . ")$");
					}
			}

		// ... if the user has specified a medium, add the value of '$mediumName' as an AND clause:
		$mediumName = $_REQUEST['mediumName'];
		if ($mediumName != "")
			{
				$mediumSelector = $_REQUEST['mediumSelector'];
				if ($mediumSelector == "contains")
					$query .= " AND medium RLIKE " . quote_smart($mediumName);
				elseif ($mediumSelector == "does not contain")
					$query .= " AND medium NOT RLIKE " . quote_smart($mediumName);
				elseif ($mediumSelector == "is equal to")
					$query .= " AND medium = " . quote_smart($mediumName);
				elseif ($mediumSelector == "is not equal to")
					$query .= " AND medium != " . quote_smart($mediumName);
				elseif ($mediumSelector == "starts with")
					$query .= " AND medium RLIKE " . quote_smart("^" . $mediumName);
				elseif ($mediumSelector == "ends with")
					$query .= " AND medium RLIKE " . quote_smart($mediumName . "$");
			}

		// ... if the user has specified an ISSN, add the value of '$issnName' as an AND clause:
		$issnName = $_REQUEST['issnName'];
		if ($issnName != "")
			{
				$issnSelector = $_REQUEST['issnSelector'];
				if ($issnSelector == "contains")
					$query .= " AND issn RLIKE " . quote_smart($issnName);
				elseif ($issnSelector == "does not contain")
					$query .= " AND issn NOT RLIKE " . quote_smart($issnName);
				elseif ($issnSelector == "is equal to")
					$query .= " AND issn = " . quote_smart($issnName);
				elseif ($issnSelector == "is not equal to")
					$query .= " AND issn != " . quote_smart($issnName);
				elseif ($issnSelector == "starts with")
					$query .= " AND issn RLIKE " . quote_smart("^" . $issnName);
				elseif ($issnSelector == "ends with")
					$query .= " AND issn RLIKE " . quote_smart($issnName . "$");
			}

		// ... if the user has specified an ISBN, add the value of '$isbnName' as an AND clause:
		$isbnName = $_REQUEST['isbnName'];
		if ($isbnName != "")
			{
				$isbnSelector = $_REQUEST['isbnSelector'];
				if ($isbnSelector == "contains")
					$query .= " AND isbn RLIKE " . quote_smart($isbnName);
				elseif ($isbnSelector == "does not contain")
					$query .= " AND isbn NOT RLIKE " . quote_smart($isbnName);
				elseif ($isbnSelector == "is equal to")
					$query .= " AND isbn = " . quote_smart($isbnName);
				elseif ($isbnSelector == "is not equal to")
					$query .= " AND isbn != " . quote_smart($isbnName);
				elseif ($isbnSelector == "starts with")
					$query .= " AND isbn RLIKE " . quote_smart("^" . $isbnName);
				elseif ($isbnSelector == "ends with")
					$query .= " AND isbn RLIKE " . quote_smart($isbnName . "$");
			}


		// ... if the user has specified a language, add the value of '$languageName' as an AND clause:
		$languageRadio = $_REQUEST['languageRadio'];
		if ($languageRadio == "1")
		{
			$languageName = $_REQUEST['languageName'];
			if ($languageName != "All" && $languageName != "")
				{
					$languageSelector = $_REQUEST['languageSelector'];
					if ($languageSelector == "contains")
						$query .= " AND language RLIKE " . quote_smart($languageName);
					elseif ($languageSelector == "does not contain")
						$query .= " AND language NOT RLIKE " . quote_smart($languageName);
					elseif ($languageSelector == "is equal to")
						$query .= " AND language = " . quote_smart($languageName);
					elseif ($languageSelector == "is not equal to")
						$query .= " AND language != " . quote_smart($languageName);
					elseif ($languageSelector == "starts with")
						$query .= " AND language RLIKE " . quote_smart("^" . $languageName);
					elseif ($languageSelector == "ends with")
						$query .= " AND language RLIKE " . quote_smart($languageName . "$");
				}
		}
		elseif ($languageRadio == "0")
		{
			$languageName2 = $_REQUEST['languageName2'];
			if ($languageName2 != "")
				{
					$languageSelector2 = $_REQUEST['languageSelector2'];
					if ($languageSelector2 == "contains")
						$query .= " AND language RLIKE " . quote_smart($languageName2);
					elseif ($languageSelector2 == "does not contain")
						$query .= " AND language NOT RLIKE " . quote_smart($languageName2);
					elseif ($languageSelector2 == "is equal to")
						$query .= " AND language = " . quote_smart($languageName2);
					elseif ($languageSelector2 == "is not equal to")
						$query .= " AND language != " . quote_smart($languageName2);
					elseif ($languageSelector2 == "starts with")
						$query .= " AND language RLIKE " . quote_smart("^" . $languageName2);
					elseif ($languageSelector2 == "ends with")
						$query .= " AND language RLIKE " . quote_smart($languageName2 . "$");
				}
		}

		// ... if the user has specified a summary language, add the value of '$summaryLanguageName' as an AND clause:
		$summaryLanguageRadio = $_REQUEST['summaryLanguageRadio'];
		if ($summaryLanguageRadio == "1")
		{
			$summaryLanguageName = $_REQUEST['summaryLanguageName'];
			if ($summaryLanguageName != "All" && $summaryLanguageName != "")
				{
					$summaryLanguageSelector = $_REQUEST['summaryLanguageSelector'];
					if ($summaryLanguageSelector == "contains")
						$query .= " AND summary_language RLIKE " . quote_smart($summaryLanguageName);
					elseif ($summaryLanguageSelector == "does not contain")
						$query .= " AND summary_language NOT RLIKE " . quote_smart($summaryLanguageName);
					elseif ($summaryLanguageSelector == "is equal to")
						$query .= " AND summary_language = " . quote_smart($summaryLanguageName);
					elseif ($summaryLanguageSelector == "is not equal to")
						$query .= " AND summary_language != " . quote_smart($summaryLanguageName);
					elseif ($summaryLanguageSelector == "starts with")
						$query .= " AND summary_language RLIKE " . quote_smart("^" . $summaryLanguageName);
					elseif ($summaryLanguageSelector == "ends with")
						$query .= " AND summary_language RLIKE " . quote_smart($summaryLanguageName . "$");
				}
		}
		elseif ($summaryLanguageRadio == "0")
		{
			$summaryLanguageName2 = $_REQUEST['summaryLanguageName2'];
			if ($summaryLanguageName2 != "")
				{
					$summaryLanguageSelector2 = $_REQUEST['summaryLanguageSelector2'];
					if ($summaryLanguageSelector2 == "contains")
						$query .= " AND summary_language RLIKE " . quote_smart($summaryLanguageName2);
					elseif ($summaryLanguageSelector2 == "does not contain")
						$query .= " AND summary_language NOT RLIKE " . quote_smart($summaryLanguageName2);
					elseif ($summaryLanguageSelector2 == "is equal to")
						$query .= " AND summary_language = " . quote_smart($summaryLanguageName2);
					elseif ($summaryLanguageSelector2 == "is not equal to")
						$query .= " AND summary_language != " . quote_smart($summaryLanguageName2);
					elseif ($summaryLanguageSelector2 == "starts with")
						$query .= " AND summary_language RLIKE " . quote_smart("^" . $summaryLanguageName2);
					elseif ($summaryLanguageSelector2 == "ends with")
						$query .= " AND summary_language RLIKE " . quote_smart($summaryLanguageName2 . "$");
				}
		}

		// ... if the user has specified some keywords, add the value of '$keywordsName' as an AND clause:
		$keywordsName = $_REQUEST['keywordsName'];
		if ($keywordsName != "")
			{
				$keywordsSelector = $_REQUEST['keywordsSelector'];
				if ($keywordsSelector == "contains")
					$query .= " AND keywords RLIKE " . quote_smart($keywordsName);
				elseif ($keywordsSelector == "does not contain")
					$query .= " AND keywords NOT RLIKE " . quote_smart($keywordsName);
				elseif ($keywordsSelector == "is equal to")
					$query .= " AND keywords = " . quote_smart($keywordsName);
				elseif ($keywordsSelector == "is not equal to")
					$query .= " AND keywords != " . quote_smart($keywordsName);
				elseif ($keywordsSelector == "starts with")
					$query .= " AND keywords RLIKE " . quote_smart("^" . $keywordsName);
				elseif ($keywordsSelector == "ends with")
					$query .= " AND keywords RLIKE " . quote_smart($keywordsName . "$");
			}

		// ... if the user has specified an abstract, add the value of '$abstractName' as an AND clause:
		$abstractName = $_REQUEST['abstractName'];
		if ($abstractName != "")
			{
				$abstractSelector = $_REQUEST['abstractSelector'];
				if ($abstractSelector == "contains")
					$query .= " AND abstract RLIKE " . quote_smart($abstractName);
				elseif ($abstractSelector == "does not contain")
					$query .= " AND abstract NOT RLIKE " . quote_smart($abstractName);
				elseif ($abstractSelector == "is equal to")
					$query .= " AND abstract = " . quote_smart($abstractName);
				elseif ($abstractSelector == "is not equal to")
					$query .= " AND abstract != " . quote_smart($abstractName);
				elseif ($abstractSelector == "starts with")
					$query .= " AND abstract RLIKE " . quote_smart("^" . $abstractName);
				elseif ($abstractSelector == "ends with")
					$query .= " AND abstract RLIKE " . quote_smart($abstractName . "$");
			}


		// ... if the user has specified an area, add the value of '$areaName' as an AND clause:
		$areaRadio = $_REQUEST['areaRadio'];
		if ($areaRadio == "1")
		{
			$areaName = $_REQUEST['areaName'];
			if ($areaName != "All" && $areaName != "")
				{
					$areaSelector = $_REQUEST['areaSelector'];
					if ($areaSelector == "contains")
						$query .= " AND area RLIKE " . quote_smart($areaName);
					elseif ($areaSelector == "does not contain")
						$query .= " AND area NOT RLIKE " . quote_smart($areaName);
					elseif ($areaSelector == "is equal to")
						$query .= " AND area = " . quote_smart($areaName);
					elseif ($areaSelector == "is not equal to")
						$query .= " AND area != " . quote_smart($areaName);
					elseif ($areaSelector == "starts with")
						$query .= " AND area RLIKE " . quote_smart("^" . $areaName);
					elseif ($areaSelector == "ends with")
						$query .= " AND area RLIKE " . quote_smart($areaName . "$");
				}
		}
		elseif ($areaRadio == "0")
		{
			$areaName2 = $_REQUEST['areaName2'];
			if ($areaName2 != "")
				{
					$areaSelector2 = $_REQUEST['areaSelector2'];
					if ($areaSelector2 == "contains")
						$query .= " AND area RLIKE " . quote_smart($areaName2);
					elseif ($areaSelector2 == "does not contain")
						$query .= " AND area NOT RLIKE " . quote_smart($areaName2);
					elseif ($areaSelector2 == "is equal to")
						$query .= " AND area = " . quote_smart($areaName2);
					elseif ($areaSelector2 == "is not equal to")
						$query .= " AND area != " . quote_smart($areaName2);
					elseif ($areaSelector2 == "starts with")
						$query .= " AND area RLIKE " . quote_smart("^" . $areaName2);
					elseif ($areaSelector2 == "ends with")
						$query .= " AND area RLIKE " . quote_smart($areaName2 . "$");
				}
		}

		// ... if the user has specified an expedition, add the value of '$expeditionName' as an AND clause:
		$expeditionName = $_REQUEST['expeditionName'];
		if ($expeditionName != "")
			{
				$expeditionSelector = $_REQUEST['expeditionSelector'];
				if ($expeditionSelector == "contains")
					$query .= " AND expedition RLIKE " . quote_smart($expeditionName);
				elseif ($expeditionSelector == "does not contain")
					$query .= " AND expedition NOT RLIKE " . quote_smart($expeditionName);
				elseif ($expeditionSelector == "is equal to")
					$query .= " AND expedition = " . quote_smart($expeditionName);
				elseif ($expeditionSelector == "is not equal to")
					$query .= " AND expedition != " . quote_smart($expeditionName);
				elseif ($expeditionSelector == "starts with")
					$query .= " AND expedition RLIKE " . quote_smart("^" . $expeditionName);
				elseif ($expeditionSelector == "ends with")
					$query .= " AND expedition RLIKE " . quote_smart($expeditionName . "$");
			}

		// ... if the user has specified a conference, add the value of '$conferenceName' as an AND clause:
		$conferenceName = $_REQUEST['conferenceName'];
		if ($conferenceName != "")
			{
				$conferenceSelector = $_REQUEST['conferenceSelector'];
				if ($conferenceSelector == "contains")
					$query .= " AND conference RLIKE " . quote_smart($conferenceName);
				elseif ($conferenceSelector == "does not contain")
					$query .= " AND conference NOT RLIKE " . quote_smart($conferenceName);
				elseif ($conferenceSelector == "is equal to")
					$query .= " AND conference = " . quote_smart($conferenceName);
				elseif ($conferenceSelector == "is not equal to")
					$query .= " AND conference != " . quote_smart($conferenceName);
				elseif ($conferenceSelector == "starts with")
					$query .= " AND conference RLIKE " . quote_smart("^" . $conferenceName);
				elseif ($conferenceSelector == "ends with")
					$query .= " AND conference RLIKE " . quote_smart($conferenceName . "$");
			}

		// ... if the user has specified a DOI, add the value of '$doiName' as an AND clause:
		$doiName = $_REQUEST['doiName'];
		if ($doiName != "")
			{
				$doiSelector = $_REQUEST['doiSelector'];
				if ($doiSelector == "contains")
					$query .= " AND doi RLIKE " . quote_smart($doiName);
				elseif ($doiSelector == "does not contain")
					$query .= " AND doi NOT RLIKE " . quote_smart($doiName);
				elseif ($doiSelector == "is equal to")
					$query .= " AND doi = " . quote_smart($doiName);
				elseif ($doiSelector == "is not equal to")
					$query .= " AND doi != " . quote_smart($doiName);
				elseif ($doiSelector == "starts with")
					$query .= " AND doi RLIKE " . quote_smart("^" . $doiName);
				elseif ($doiSelector == "ends with")
					$query .= " AND doi RLIKE " . quote_smart($doiName . "$");
			}

		// ... if the user has specified an URL, add the value of '$urlName' as an AND clause:
		$urlName = $_REQUEST['urlName'];
		if ($urlName != "")
			{
				$urlSelector = $_REQUEST['urlSelector'];
				if ($urlSelector == "contains")
					$query .= " AND url RLIKE " . quote_smart($urlName);
				elseif ($urlSelector == "does not contain")
					$query .= " AND url NOT RLIKE " . quote_smart($urlName);
				elseif ($urlSelector == "is equal to")
					$query .= " AND url = " . quote_smart($urlName);
				elseif ($urlSelector == "is not equal to")
					$query .= " AND url != " . quote_smart($urlName);
				elseif ($urlSelector == "starts with")
					$query .= " AND url RLIKE " . quote_smart("^" . $urlName);
				elseif ($urlSelector == "ends with")
					$query .= " AND url RLIKE " . quote_smart($urlName . "$");
			}


		// ... if the user has specified a location, add the value of '$locationName' as an AND clause:
		if (isset($_REQUEST['locationRadio'])) // the location text entry form is not available if the user is not logged in
		{
			$locationRadio = $_REQUEST['locationRadio'];
			if ($locationRadio == "1")
			{
				$locationName = $_REQUEST['locationName'];
				if ($locationName != "All" && $locationName != "")
					{
						$locationSelector = $_REQUEST['locationSelector'];
						if ($locationSelector == "contains")
							$query .= " AND location RLIKE " . quote_smart($locationName);
						elseif ($locationSelector == "does not contain")
							$query .= " AND location NOT RLIKE " . quote_smart($locationName);
						elseif ($locationSelector == "is equal to")
							$query .= " AND location = " . quote_smart($locationName);
						elseif ($locationSelector == "is not equal to")
							$query .= " AND location != " . quote_smart($locationName);
						elseif ($locationSelector == "starts with")
							$query .= " AND location RLIKE " . quote_smart("^" . $locationName);
						elseif ($locationSelector == "ends with")
							$query .= " AND location RLIKE " . quote_smart($locationName . "$");
					}
			}
			elseif ($locationRadio == "0")
			{
				$locationName2 = $_REQUEST['locationName2'];
				if ($locationName2 != "")
					{
						$locationSelector2 = $_REQUEST['locationSelector2'];
						if ($locationSelector2 == "contains")
							$query .= " AND location RLIKE " . quote_smart($locationName2);
						elseif ($locationSelector2 == "does not contain")
							$query .= " AND location NOT RLIKE " . quote_smart($locationName2);
						elseif ($locationSelector2 == "is equal to")
							$query .= " AND location = " . quote_smart($locationName2);
						elseif ($locationSelector2 == "is not equal to")
							$query .= " AND location != " . quote_smart($locationName2);
						elseif ($locationSelector2 == "starts with")
							$query .= " AND location RLIKE " . quote_smart("^" . $locationName2);
						elseif ($locationSelector2 == "ends with")
							$query .= " AND location RLIKE " . quote_smart($locationName2 . "$");
					}
			}
		}

		// ... if the user has specified a call number, add the value of '$callNumberName' as an AND clause:
		$callNumberName = $_REQUEST['callNumberName'];
		if ($callNumberName != "")
			{
				$callNumberSelector = $_REQUEST['callNumberSelector'];
				if ($callNumberSelector == "contains")
					$query .= " AND call_number RLIKE " . quote_smart($callNumberName);
				elseif ($callNumberSelector == "does not contain")
					$query .= " AND call_number NOT RLIKE " . quote_smart($callNumberName);
				elseif ($callNumberSelector == "is equal to")
					$query .= " AND call_number = " . quote_smart($callNumberName);
				elseif ($callNumberSelector == "is not equal to")
					$query .= " AND call_number != " . quote_smart($callNumberName);
				elseif ($callNumberSelector == "starts with")
					$query .= " AND call_number RLIKE " . quote_smart("^" . $callNumberName);
				elseif ($callNumberSelector == "ends with")
					$query .= " AND call_number RLIKE " . quote_smart($callNumberName . "$");
			}

		// ... if the user has specified a file, add the value of '$fileName' as an AND clause:
		if (isset($_REQUEST['fileName'])) // the file text entry form may be hidden if the user has no permission to see any files
		{
			$fileName = $_REQUEST['fileName'];
			if ($fileName != "")
				{
					$fileSelector = $_REQUEST['fileSelector'];
					if ($fileSelector == "contains")
						$query .= " AND file RLIKE " . quote_smart($fileName);
					elseif ($fileSelector == "does not contain")
						$query .= " AND file NOT RLIKE " . quote_smart($fileName);
					elseif ($fileSelector == "is equal to")
						$query .= " AND file = " . quote_smart($fileName);
					elseif ($fileSelector == "is not equal to")
						$query .= " AND file != " . quote_smart($fileName);
					elseif ($fileSelector == "starts with")
						$query .= " AND file RLIKE " . quote_smart("^" . $fileName);
					elseif ($fileSelector == "ends with")
						$query .= " AND file RLIKE " . quote_smart($fileName . "$");
				}
		}


		if (isset($loginEmail)) // if a user is logged in and...
		{
			// ... if the user has specified a copy status, add the value of '$copyName' as an AND clause:
			$copyName = $_REQUEST['copyName'];
			if ($copyName != "All" && $copyName != "")
				{
					$copySelector = $_REQUEST['copySelector'];
					if ($copySelector == "is equal to")
						$query .= " AND copy = " . quote_smart($copyName);
					elseif ($copySelector == "is not equal to")
						$query .= " AND copy != " . quote_smart($copyName);
				}
		}

		// ... if the user has specified some notes, add the value of '$notesName' as an AND clause:
		$notesName = $_REQUEST['notesName'];
		if ($notesName != "")
			{
				$notesSelector = $_REQUEST['notesSelector'];
				if ($notesSelector == "contains")
					$query .= " AND notes RLIKE " . quote_smart($notesName);
				elseif ($notesSelector == "does not contain")
					$query .= " AND notes NOT RLIKE " . quote_smart($notesName);
				elseif ($notesSelector == "is equal to")
					$query .= " AND notes = " . quote_smart($notesName);
				elseif ($notesSelector == "is not equal to")
					$query .= " AND notes != " . quote_smart($notesName);
				elseif ($notesSelector == "starts with")
					$query .= " AND notes RLIKE " . quote_smart("^" . $notesName);
				elseif ($notesSelector == "ends with")
					$query .= " AND notes RLIKE " . quote_smart($notesName . "$");
			}

		if (isset($loginEmail)) // if a user is logged in and...
		{
			// ... if the user has specified some user keys, add the value of '$userKeysName' as an AND clause:
			$userKeysRadio = $_REQUEST['userKeysRadio'];
			if ($userKeysRadio == "1")
			{
				$userKeysName = $_REQUEST['userKeysName'];
				if ($userKeysName != "All" && $userKeysName != "")
					{
						$userKeysSelector = $_REQUEST['userKeysSelector'];
						if ($userKeysSelector == "contains")
							$query .= " AND user_keys RLIKE " . quote_smart($userKeysName);
						elseif ($userKeysSelector == "does not contain")
							$query .= " AND user_keys NOT RLIKE " . quote_smart($userKeysName);
						elseif ($userKeysSelector == "is equal to")
							$query .= " AND user_keys = " . quote_smart($userKeysName);
						elseif ($userKeysSelector == "is not equal to")
							$query .= " AND user_keys != " . quote_smart($userKeysName);
						elseif ($userKeysSelector == "starts with")
							$query .= " AND user_keys RLIKE " . quote_smart("^" . $userKeysName);
						elseif ($userKeysSelector == "ends with")
							$query .= " AND user_keys RLIKE " . quote_smart($userKeysName . "$");
					}
			}
			elseif ($userKeysRadio == "0")
			{
				$userKeysName2 = $_REQUEST['userKeysName2'];
				if ($userKeysName2 != "")
					{
						$userKeysSelector2 = $_REQUEST['userKeysSelector2'];
						if ($userKeysSelector2 == "contains")
							$query .= " AND user_keys RLIKE " . quote_smart($userKeysName2);
						elseif ($userKeysSelector2 == "does not contain")
							$query .= " AND user_keys NOT RLIKE " . quote_smart($userKeysName2);
						elseif ($userKeysSelector2 == "is equal to")
							$query .= " AND user_keys = " . quote_smart($userKeysName2);
						elseif ($userKeysSelector2 == "is not equal to")
							$query .= " AND user_keys != " . quote_smart($userKeysName2);
						elseif ($userKeysSelector2 == "starts with")
							$query .= " AND user_keys RLIKE " . quote_smart("^" . $userKeysName2);
						elseif ($userKeysSelector2 == "ends with")
							$query .= " AND user_keys RLIKE " . quote_smart($userKeysName2 . "$");
					}
			}

			// ... if the user has specified some user notes, add the value of '$userNotesName' as an AND clause:
			$userNotesName = $_REQUEST['userNotesName'];
			if ($userNotesName != "")
				{
					$userNotesSelector = $_REQUEST['userNotesSelector'];
					if ($userNotesSelector == "contains")
						$query .= " AND user_notes RLIKE " . quote_smart($userNotesName);
					elseif ($userNotesSelector == "does not contain")
						$query .= " AND user_notes NOT RLIKE " . quote_smart($userNotesName);
					elseif ($userNotesSelector == "is equal to")
						$query .= " AND user_notes = " . quote_smart($userNotesName);
					elseif ($userNotesSelector == "is not equal to")
						$query .= " AND user_notes != " . quote_smart($userNotesName);
					elseif ($userNotesSelector == "starts with")
						$query .= " AND user_notes RLIKE " . quote_smart("^" . $userNotesName);
					elseif ($userNotesSelector == "ends with")
						$query .= " AND user_notes RLIKE " . quote_smart($userNotesName . "$");
				}

			// ... if the user has specified a user file, add the value of '$userFileName' as an AND clause:
			$userFileName = $_REQUEST['userFileName'];
			if ($userFileName != "")
				{
					$userFileSelector = $_REQUEST['userFileSelector'];
					if ($userFileSelector == "contains")
						$query .= " AND user_file RLIKE " . quote_smart($userFileName);
					elseif ($userFileSelector == "does not contain")
						$query .= " AND user_file NOT RLIKE " . quote_smart($userFileName);
					elseif ($userFileSelector == "is equal to")
						$query .= " AND user_file = " . quote_smart($userFileName);
					elseif ($userFileSelector == "is not equal to")
						$query .= " AND user_file != " . quote_smart($userFileName);
					elseif ($userFileSelector == "starts with")
						$query .= " AND user_file RLIKE " . quote_smart("^" . $userFileName);
					elseif ($userFileSelector == "ends with")
						$query .= " AND user_file RLIKE " . quote_smart($userFileName . "$");
				}

			// ... if the user has specified some user groups, add the value of '$userGroupsName' as an AND clause:
			$userGroupsRadio = $_REQUEST['userGroupsRadio'];
			if ($userGroupsRadio == "1")
			{
				$userGroupsName = $_REQUEST['userGroupsName'];
				if ($userGroupsName != "All" && $userGroupsName != "")
					{
						$userGroupsSelector = $_REQUEST['userGroupsSelector'];
						if ($userGroupsSelector == "contains")
							$query .= " AND user_groups RLIKE " . quote_smart($userGroupsName);
						elseif ($userGroupsSelector == "does not contain")
							$query .= " AND user_groups NOT RLIKE " . quote_smart($userGroupsName);
						elseif ($userGroupsSelector == "is equal to")
							$query .= " AND user_groups = " . quote_smart($userGroupsName);
						elseif ($userGroupsSelector == "is not equal to")
							$query .= " AND user_groups != " . quote_smart($userGroupsName);
						elseif ($userGroupsSelector == "starts with")
							$query .= " AND user_groups RLIKE " . quote_smart("^" . $userGroupsName);
						elseif ($userGroupsSelector == "ends with")
							$query .= " AND user_groups RLIKE " . quote_smart($userGroupsName . "$");
					}
			}
			elseif ($userGroupsRadio == "0")
			{
				$userGroupsName2 = $_REQUEST['userGroupsName2'];
				if ($userGroupsName2 != "")
					{
						$userGroupsSelector2 = $_REQUEST['userGroupsSelector2'];
						if ($userGroupsSelector2 == "contains")
							$query .= " AND user_groups RLIKE " . quote_smart($userGroupsName2);
						elseif ($userGroupsSelector2 == "does not contain")
							$query .= " AND user_groups NOT RLIKE " . quote_smart($userGroupsName2);
						elseif ($userGroupsSelector2 == "is equal to")
							$query .= " AND user_groups = " . quote_smart($userGroupsName2);
						elseif ($userGroupsSelector2 == "is not equal to")
							$query .= " AND user_groups != " . quote_smart($userGroupsName2);
						elseif ($userGroupsSelector2 == "starts with")
							$query .= " AND user_groups RLIKE " . quote_smart("^" . $userGroupsName2);
						elseif ($userGroupsSelector2 == "ends with")
							$query .= " AND user_groups RLIKE " . quote_smart($userGroupsName2 . "$");
					}
			}

			// ... if the user has specified a cite key, add the value of '$citeKeyName' as an AND clause:
			$citeKeyName = $_REQUEST['citeKeyName'];
			if ($citeKeyName != "")
				{
					$citeKeySelector = $_REQUEST['citeKeySelector'];
					if ($citeKeySelector == "contains")
						$query .= " AND cite_key RLIKE " . quote_smart($citeKeyName);
					elseif ($citeKeySelector == "does not contain")
						$query .= " AND cite_key NOT RLIKE " . quote_smart($citeKeyName);
					elseif ($citeKeySelector == "is equal to")
						$query .= " AND cite_key = " . quote_smart($citeKeyName);
					elseif ($citeKeySelector == "is not equal to")
						$query .= " AND cite_key != " . quote_smart($citeKeyName);
					elseif ($citeKeySelector == "starts with")
						$query .= " AND cite_key RLIKE " . quote_smart("^" . $citeKeyName);
					elseif ($citeKeySelector == "ends with")
						$query .= " AND cite_key RLIKE " . quote_smart($citeKeyName . "$");
				}
		}

		// ... if the user has specified a serial, add the value of '$serialNo' as an AND clause:
		$serialNo = $_REQUEST['serialNo'];
		if ($serialNo != "")
			{
				$serialSelector = $_REQUEST['serialSelector'];
				if ($serialSelector == "contains")
					$query .= " AND serial RLIKE " . quote_smart($serialNo);
				elseif ($serialSelector == "does not contain")
					$query .= " AND serial NOT RLIKE " . quote_smart($serialNo);
				elseif ($serialSelector == "is equal to")
					$query .= " AND serial = " . quote_smart($serialNo);
				elseif ($serialSelector == "is not equal to")
					$query .= " AND serial != " . quote_smart($serialNo);
				elseif ($serialSelector == "starts with")
					$query .= " AND serial RLIKE " . quote_smart("^" . $serialNo);
				elseif ($serialSelector == "ends with")
					$query .= " AND serial RLIKE " . quote_smart($serialNo . "$");
				elseif ($serialSelector == "is greater than")
					$query .= " AND serial > " . quote_smart($serialNo);
				elseif ($serialSelector == "is less than")
					$query .= " AND serial < " . quote_smart($serialNo);
				elseif ($serialSelector == "is within range")
					{
						if (preg_match("/\d+/", $serialNo)) // if '$serialNo' does contain at least one number
						{
							// extract first number:
							$serialNoStart = preg_replace("/^\D*(\d+).*/", "\\1", $serialNo);
							$query .= " AND serial >= " . quote_smart($serialNoStart);

							if (preg_match("/^\D*\d+\D+\d+/", $serialNo)) // if '$serialNo' does contain at least two numbers (which are separated by one or more non-digit characters)
							{
								// extract the second number:
								$serialNoEnd = preg_replace("/^\D*\d+\D+(\d+).*/", "\\1", $serialNo);
								$query .= " AND serial <= " . quote_smart($serialNoEnd);
							}
						}
						else // fallback if no number is given:
							$query .= " AND serial RLIKE " . quote_smart($serialNo); // this will never produce any results since serial is always numeric but we keep it here for reasons of consistency
					}
				elseif ($serialSelector == "is within list")
					{
						// replace any non-digit chars with "|":
						$serialNo = preg_replace("/\D+/", "|", $serialNo);
						// strip "|" from beginning/end of string (if any):
						$serialNo = preg_replace("/^\|?(.+?)\|?$/", "\\1", $serialNo);
						$query .= " AND serial RLIKE " . quote_smart("^(" . $serialNo . ")$");
					}
			}

		// ... if the user has specified a type, add the value of '$typeName' as an AND clause:
		$typeRadio = $_REQUEST['typeRadio'];
		if ($typeRadio == "1")
		{
			$typeName = $_REQUEST['typeName'];
			if ($typeName != "All" && $typeName != "")
				{
					$typeSelector = $_REQUEST['typeSelector'];
					if ($typeSelector == "contains")
						$query .= " AND type RLIKE " . quote_smart($typeName);
					elseif ($typeSelector == "does not contain")
						$query .= " AND type NOT RLIKE " . quote_smart($typeName);
					elseif ($typeSelector == "is equal to")
						$query .= " AND type = " . quote_smart($typeName);
					elseif ($typeSelector == "is not equal to")
						$query .= " AND type != " . quote_smart($typeName);
					elseif ($typeSelector == "starts with")
						$query .= " AND type RLIKE " . quote_smart("^" . $typeName);
					elseif ($typeSelector == "ends with")
						$query .= " AND type RLIKE " . quote_smart($typeName . "$");
				}
		}
		elseif ($typeRadio == "0")
		{
			$typeName2 = $_REQUEST['typeName2'];
			if ($typeName2 != "")
				{
					$typeSelector2 = $_REQUEST['typeSelector2'];
					if ($typeSelector2 == "contains")
						$query .= " AND type RLIKE " . quote_smart($typeName2);
					elseif ($typeSelector2 == "does not contain")
						$query .= " AND type NOT RLIKE " . quote_smart($typeName2);
					elseif ($typeSelector2 == "is equal to")
						$query .= " AND type = " . quote_smart($typeName2);
					elseif ($typeSelector2 == "is not equal to")
						$query .= " AND type != " . quote_smart($typeName2);
					elseif ($typeSelector2 == "starts with")
						$query .= " AND type RLIKE " . quote_smart("^" . $typeName2);
					elseif ($typeSelector2 == "ends with")
						$query .= " AND type RLIKE " . quote_smart($typeName2 . "$");
				}
		}

		if (isset($loginEmail)) // if a user is logged in and...
		{
			// ... if the user has selected a radio button for 'Marked', add the corresponding value for 'marked' as an AND clause:
			if (isset($_REQUEST['markedRadio']))
			{
				$markedRadio = $_REQUEST['markedRadio'];
				if ($markedRadio == "1")
					$query .= " AND marked = \"yes\"";
				elseif ($markedRadio == "0")
					$query .= " AND marked = \"no\"";
			}

			// ... if the user has selected a radio button for 'Selected', add the corresponding value for 'selected' as an AND clause:
			if (isset($_REQUEST['selectedRadio']))
			{
				$selectedRadio = $_REQUEST['selectedRadio'];
				if ($selectedRadio == "1")
					$query .= " AND selected = \"yes\"";
				elseif ($selectedRadio == "0")
					$query .= " AND selected = \"no\"";
			}
		}

		// ... if the user has selected a radio button for 'Approved', add the corresponding value for 'approved' as an AND clause:
		if (isset($_REQUEST['approvedRadio']))
		{
			$approvedRadio = $_REQUEST['approvedRadio'];
			if ($approvedRadio == "1")
				$query .= " AND approved = \"yes\"";
			elseif ($approvedRadio == "0")
				$query .= " AND approved = \"no\"";
		}

		// ... if the user has specified a created date, add the value of '$createdDateNo' as an AND clause:
		$createdDateNo = $_REQUEST['createdDateNo'];
		if ($createdDateNo != "")
			{
				$createdDateSelector = $_REQUEST['createdDateSelector'];
				if ($createdDateSelector == "contains")
					$query .= " AND created_date RLIKE " . quote_smart($createdDateNo);
				elseif ($createdDateSelector == "does not contain")
					$query .= " AND created_date NOT RLIKE " . quote_smart($createdDateNo);
				elseif ($createdDateSelector == "is equal to")
					$query .= " AND created_date = " . quote_smart($createdDateNo);
				elseif ($createdDateSelector == "is not equal to")
					$query .= " AND created_date != " . quote_smart($createdDateNo);
				elseif ($createdDateSelector == "starts with")
					$query .= " AND created_date RLIKE " . quote_smart("^" . $createdDateNo);
				elseif ($createdDateSelector == "ends with")
					$query .= " AND created_date RLIKE " . quote_smart($createdDateNo . "$");
				elseif ($createdDateSelector == "is greater than")
					$query .= " AND created_date > " . quote_smart($createdDateNo);
				elseif ($createdDateSelector == "is less than")
					$query .= " AND created_date < " . quote_smart($createdDateNo);
				elseif ($createdDateSelector == "is within range")
					{
						if (preg_match("/\d{4}/", $createdDateNo)) // if '$createdDateNo' does contain at least one date spec (which, as a minimum, is defined by a four-digit year)
						{
							// extract first date spec:
							$createdDateNoStart = preg_replace("/^[^\d-]*(\d{4}(?:-\d{2})?(?:-\d{2})?).*/", "\\1", $createdDateNo); // extracts e.g. "2005-10-27", "2005-10" or just "2005" (in that order)
							$query .= " AND created_date >= " . quote_smart($createdDateNoStart);

							if (preg_match("/^[^\d-]*\d{4}(?:-\d{2})?(?:-\d{2})?[^\d-]+\d{4}(?:-\d{2})?(?:-\d{2})?/", $createdDateNo)) // if '$createdDateNo' does contain at least two date specs (which are separated by one or more non-digit/non-hyphen characters)
							{
								// extract the second date spec:
								$createdDateNoEnd = preg_replace("/^[^\d-]*\d{4}(?:-\d{2})?(?:-\d{2})?[^\d-]+(\d{4}(?:-\d{2})?(?:-\d{2})?).*/", "\\1", $createdDateNo);
								$query .= " AND created_date <= " . quote_smart($createdDateNoEnd);
							}
						}
						else // fallback if no recognized date spec is given:
							$query .= " AND created_date RLIKE " . quote_smart($createdDateNo);
					}
				elseif ($createdDateSelector == "is within list")
					{
						// replace any non-digit/non-hyphen chars with "|":
						$createdDateNo = preg_replace("/[^\d-]+/", "|", $createdDateNo);
						// strip "|" from beginning/end of string (if any):
						$createdDateNo = preg_replace("/^\|?(.+?)\|?$/", "\\1", $createdDateNo);
						$query .= " AND created_date RLIKE " . quote_smart("^(" . $createdDateNo . ")$");
					}
			}

		// ... if the user has specified a created time, add the value of '$createdTimeNo' as an AND clause:
		$createdTimeNo = $_REQUEST['createdTimeNo'];
		if ($createdTimeNo != "")
			{
				$createdTimeSelector = $_REQUEST['createdTimeSelector'];
				if ($createdTimeSelector == "contains")
					$query .= " AND created_time RLIKE " . quote_smart($createdTimeNo);
				elseif ($createdTimeSelector == "does not contain")
					$query .= " AND created_time NOT RLIKE " . quote_smart($createdTimeNo);
				elseif ($createdTimeSelector == "is equal to")
					$query .= " AND created_time = " . quote_smart($createdTimeNo);
				elseif ($createdTimeSelector == "is not equal to")
					$query .= " AND created_time != " . quote_smart($createdTimeNo);
				elseif ($createdTimeSelector == "starts with")
					$query .= " AND created_time RLIKE " . quote_smart("^" . $createdTimeNo);
				elseif ($createdTimeSelector == "ends with")
					$query .= " AND created_time RLIKE " . quote_smart($createdTimeNo . "$");
				elseif ($createdTimeSelector == "is greater than")
					$query .= " AND created_time > " . quote_smart($createdTimeNo);
				elseif ($createdTimeSelector == "is less than")
					$query .= " AND created_time < " . quote_smart($createdTimeNo);
				elseif ($createdTimeSelector == "is within range")
					{
						if (preg_match("/\d{2}:\d{2}/", $createdTimeNo)) // if '$createdTimeNo' does contain at least one time spec (which, as a minimum, is defined by a HH:MM)
						{
							// extract first time spec:
							$createdTimeNoStart = preg_replace("/^[^\d:]*(\d{2}:\d{2}(?::\d{2})?).*/", "\\1", $createdTimeNo); // extracts e.g. "23:59:59" or just "23:59" (in that order)
							$query .= " AND created_time >= " . quote_smart($createdTimeNoStart);

							if (preg_match("/^[^\d:]*\d{2}:\d{2}(?::\d{2})?[^\d:]+\d{2}:\d{2}(?::\d{2})?/", $createdTimeNo)) // if '$createdTimeNo' does contain at least two date specs (which are separated by one or more non-digit/non-colon characters)
							{
								// extract the second time spec:
								$createdTimeNoEnd = preg_replace("/^[^\d:]*\d{2}:\d{2}(?::\d{2})?[^\d:]+(\d{2}:\d{2}(?::\d{2})?).*/", "\\1", $createdTimeNo);
								$query .= " AND created_time <= " . quote_smart($createdTimeNoEnd);
							}
						}
						else // fallback if no recognized time spec is given:
							$query .= " AND created_time RLIKE " . quote_smart($createdTimeNo);
					}
				elseif ($createdTimeSelector == "is within list")
					{
						// replace any non-digit/non-colon chars with "|":
						$createdTimeNo = preg_replace("/[^\d:]+/", "|", $createdTimeNo);
						// strip "|" from beginning/end of string (if any):
						$createdTimeNo = preg_replace("/^\|?(.+?)\|?$/", "\\1", $createdTimeNo);
						$query .= " AND created_time RLIKE " . quote_smart("^(" . $createdTimeNo . ")$");
					}
			}

		// ... if the user has specified a created by, add the value of '$createdByName' as an AND clause:
		if (isset($_REQUEST['createdByRadio'])) // the "created by" text entry form is not available if the user is not logged in
		{
			$createdByRadio = $_REQUEST['createdByRadio'];
			if ($createdByRadio == "1")
			{
				$createdByName = $_REQUEST['createdByName'];
				if ($createdByName != "All" && $createdByName != "")
					{
						$createdBySelector = $_REQUEST['createdBySelector'];
						if ($createdBySelector == "contains")
							$query .= " AND created_by RLIKE " . quote_smart($createdByName);
						elseif ($createdBySelector == "does not contain")
							$query .= " AND created_by NOT RLIKE " . quote_smart($createdByName);
						elseif ($createdBySelector == "is equal to")
							$query .= " AND created_by = " . quote_smart($createdByName);
						elseif ($createdBySelector == "is not equal to")
							$query .= " AND created_by != " . quote_smart($createdByName);
						elseif ($createdBySelector == "starts with")
							$query .= " AND created_by RLIKE " . quote_smart("^" . $createdByName);
						elseif ($createdBySelector == "ends with")
							$query .= " AND created_by RLIKE " . quote_smart($createdByName . "$");
					}
			}
			elseif ($createdByRadio == "0")
			{
				$createdByName2 = $_REQUEST['createdByName2'];
				if ($createdByName2 != "")
					{
						$createdBySelector2 = $_REQUEST['createdBySelector2'];
						if ($createdBySelector2 == "contains")
							$query .= " AND created_by RLIKE " . quote_smart($createdByName2);
						elseif ($createdBySelector2 == "does not contain")
							$query .= " AND created_by NOT RLIKE " . quote_smart($createdByName2);
						elseif ($createdBySelector2 == "is equal to")
							$query .= " AND created_by = " . quote_smart($createdByName2);
						elseif ($createdBySelector2 == "is not equal to")
							$query .= " AND created_by != " . quote_smart($createdByName2);
						elseif ($createdBySelector2 == "starts with")
							$query .= " AND created_by RLIKE " . quote_smart("^" . $createdByName2);
						elseif ($createdBySelector2 == "ends with")
							$query .= " AND created_by RLIKE " . quote_smart($createdByName2 . "$");
					}
			}
		}

		// ... if the user has specified a modified date, add the value of '$modifiedDateNo' as an AND clause:
		$modifiedDateNo = $_REQUEST['modifiedDateNo'];
		if ($modifiedDateNo != "")
			{
				$modifiedDateSelector = $_REQUEST['modifiedDateSelector'];
				if ($modifiedDateSelector == "contains")
					$query .= " AND modified_date RLIKE " . quote_smart($modifiedDateNo);
				elseif ($modifiedDateSelector == "does not contain")
					$query .= " AND modified_date NOT RLIKE " . quote_smart($modifiedDateNo);
				elseif ($modifiedDateSelector == "is equal to")
					$query .= " AND modified_date = " . quote_smart($modifiedDateNo);
				elseif ($modifiedDateSelector == "is not equal to")
					$query .= " AND modified_date != " . quote_smart($modifiedDateNo);
				elseif ($modifiedDateSelector == "starts with")
					$query .= " AND modified_date RLIKE " . quote_smart("^" . $modifiedDateNo);
				elseif ($modifiedDateSelector == "ends with")
					$query .= " AND modified_date RLIKE " . quote_smart($modifiedDateNo . "$");
				elseif ($modifiedDateSelector == "is greater than")
					$query .= " AND modified_date > " . quote_smart($modifiedDateNo);
				elseif ($modifiedDateSelector == "is less than")
					$query .= " AND modified_date < " . quote_smart($modifiedDateNo);
				elseif ($modifiedDateSelector == "is within range")
					{
						if (preg_match("/\d{4}/", $modifiedDateNo)) // if '$modifiedDateNo' does contain at least one date spec (which, as a minimum, is defined by a four-digit year)
						{
							// extract first date spec:
							$modifiedDateNoStart = preg_replace("/^[^\d-]*(\d{4}(?:-\d{2})?(?:-\d{2})?).*/", "\\1", $modifiedDateNo); // extracts e.g. "2005-10-27", "2005-10" or just "2005" (in that order)
							$query .= " AND modified_date >= " . quote_smart($modifiedDateNoStart);

							if (preg_match("/^[^\d-]*\d{4}(?:-\d{2})?(?:-\d{2})?[^\d-]+\d{4}(?:-\d{2})?(?:-\d{2})?/", $modifiedDateNo)) // if '$modifiedDateNo' does contain at least two date specs (which are separated by one or more non-digit/non-hyphen characters)
							{
								// extract the second date spec:
								$modifiedDateNoEnd = preg_replace("/^[^\d-]*\d{4}(?:-\d{2})?(?:-\d{2})?[^\d-]+(\d{4}(?:-\d{2})?(?:-\d{2})?).*/", "\\1", $modifiedDateNo);
								$query .= " AND modified_date <= " . quote_smart($modifiedDateNoEnd);
							}
						}
						else // fallback if no recognized date spec is given:
							$query .= " AND modified_date RLIKE " . quote_smart($modifiedDateNo);
					}
				elseif ($modifiedDateSelector == "is within list")
					{
						// replace any non-digit/non-hyphen chars with "|":
						$modifiedDateNo = preg_replace("/[^\d-]+/", "|", $modifiedDateNo);
						// strip "|" from beginning/end of string (if any):
						$modifiedDateNo = preg_replace("/^\|?(.+?)\|?$/", "\\1", $modifiedDateNo);
						$query .= " AND modified_date RLIKE " . quote_smart("^(" . $modifiedDateNo . ")$");
					}
			}

		// ... if the user has specified a modified time, add the value of '$modifiedTimeNo' as an AND clause:
		$modifiedTimeNo = $_REQUEST['modifiedTimeNo'];
		if ($modifiedTimeNo != "")
			{
				$modifiedTimeSelector = $_REQUEST['modifiedTimeSelector'];
				if ($modifiedTimeSelector == "contains")
					$query .= " AND modified_time RLIKE " . quote_smart($modifiedTimeNo);
				elseif ($modifiedTimeSelector == "does not contain")
					$query .= " AND modified_time NOT RLIKE " . quote_smart($modifiedTimeNo);
				elseif ($modifiedTimeSelector == "is equal to")
					$query .= " AND modified_time = " . quote_smart($modifiedTimeNo);
				elseif ($modifiedTimeSelector == "is not equal to")
					$query .= " AND modified_time != " . quote_smart($modifiedTimeNo);
				elseif ($modifiedTimeSelector == "starts with")
					$query .= " AND modified_time RLIKE " . quote_smart("^" . $modifiedTimeNo);
				elseif ($modifiedTimeSelector == "ends with")
					$query .= " AND modified_time RLIKE " . quote_smart($modifiedTimeNo . "$");
				elseif ($modifiedTimeSelector == "is greater than")
					$query .= " AND modified_time > " . quote_smart($modifiedTimeNo);
				elseif ($modifiedTimeSelector == "is less than")
					$query .= " AND modified_time < " . quote_smart($modifiedTimeNo);
				elseif ($modifiedTimeSelector == "is within range")
					{
						if (preg_match("/\d{2}:\d{2}/", $modifiedTimeNo)) // if '$modifiedTimeNo' does contain at least one time spec (which, as a minimum, is defined by a HH:MM)
						{
							// extract first time spec:
							$modifiedTimeNoStart = preg_replace("/^[^\d:]*(\d{2}:\d{2}(?::\d{2})?).*/", "\\1", $modifiedTimeNo); // extracts e.g. "23:59:59" or just "23:59" (in that order)
							$query .= " AND modified_time >= " . quote_smart($modifiedTimeNoStart);

							if (preg_match("/^[^\d:]*\d{2}:\d{2}(?::\d{2})?[^\d:]+\d{2}:\d{2}(?::\d{2})?/", $modifiedTimeNo)) // if '$modifiedTimeNo' does contain at least two date specs (which are separated by one or more non-digit/non-colon characters)
							{
								// extract the second time spec:
								$modifiedTimeNoEnd = preg_replace("/^[^\d:]*\d{2}:\d{2}(?::\d{2})?[^\d:]+(\d{2}:\d{2}(?::\d{2})?).*/", "\\1", $modifiedTimeNo);
								$query .= " AND modified_time <= " . quote_smart($modifiedTimeNoEnd);
							}
						}
						else // fallback if no recognized time spec is given:
							$query .= " AND modified_time RLIKE " . quote_smart($modifiedTimeNo);
					}
				elseif ($modifiedTimeSelector == "is within list")
					{
						// replace any non-digit/non-colon chars with "|":
						$modifiedTimeNo = preg_replace("/[^\d:]+/", "|", $modifiedTimeNo);
						// strip "|" from beginning/end of string (if any):
						$modifiedTimeNo = preg_replace("/^\|?(.+?)\|?$/", "\\1", $modifiedTimeNo);
						$query .= " AND modified_time RLIKE " . quote_smart("^(" . $modifiedTimeNo . ")$");
					}
			}

		// ... if the user has specified a modified by, add the value of '$modifiedByName' as an AND clause:
		if (isset($_REQUEST['modifiedByRadio'])) // the "modified by" text entry form is not available if the user is not logged in
		{
			$modifiedByRadio = $_REQUEST['modifiedByRadio'];
			if ($modifiedByRadio == "1")
			{
				$modifiedByName = $_REQUEST['modifiedByName'];
				if ($modifiedByName != "All" && $modifiedByName != "")
					{
						$modifiedBySelector = $_REQUEST['modifiedBySelector'];
						if ($modifiedBySelector == "contains")
							$query .= " AND modified_by RLIKE " . quote_smart($modifiedByName);
						elseif ($modifiedBySelector == "does not contain")
							$query .= " AND modified_by NOT RLIKE " . quote_smart($modifiedByName);
						elseif ($modifiedBySelector == "is equal to")
							$query .= " AND modified_by = " . quote_smart($modifiedByName);
						elseif ($modifiedBySelector == "is not equal to")
							$query .= " AND modified_by != " . quote_smart($modifiedByName);
						elseif ($modifiedBySelector == "starts with")
							$query .= " AND modified_by RLIKE " . quote_smart("^" . $modifiedByName);
						elseif ($modifiedBySelector == "ends with")
							$query .= " AND modified_by RLIKE " . quote_smart($modifiedByName . "$");
					}
			}
			elseif ($modifiedByRadio == "0")
			{
				$modifiedByName2 = $_REQUEST['modifiedByName2'];
				if ($modifiedByName2 != "")
					{
						$modifiedBySelector2 = $_REQUEST['modifiedBySelector2'];
						if ($modifiedBySelector2 == "contains")
							$query .= " AND modified_by RLIKE " . quote_smart($modifiedByName2);
						elseif ($modifiedBySelector2 == "does not contain")
							$query .= " AND modified_by NOT RLIKE " . quote_smart($modifiedByName2);
						elseif ($modifiedBySelector2 == "is equal to")
							$query .= " AND modified_by = " . quote_smart($modifiedByName2);
						elseif ($modifiedBySelector2 == "is not equal to")
							$query .= " AND modified_by != " . quote_smart($modifiedByName2);
						elseif ($modifiedBySelector2 == "starts with")
							$query .= " AND modified_by RLIKE " . quote_smart("^" . $modifiedByName2);
						elseif ($modifiedBySelector2 == "ends with")
							$query .= " AND modified_by RLIKE " . quote_smart($modifiedByName2 . "$");
					}
			}
		}


		// Construct the ORDER BY clause:
		$query .= " ORDER BY ";

		// A) extract first level sort option:
		$sortSelector1 = $_REQUEST['sortSelector1'];
		if ($sortSelector1 != "")
			{
				// when field name = 'pages' then sort by 'first_page' instead:
				$sortSelector1 = str_replace("pages", "first_page", $sortSelector1);

				$sortRadio1 = $_REQUEST['sortRadio1'];
				if ($sortRadio1 == "0") // sort ascending
					$query .= "$sortSelector1";
				else // sort descending
					$query .= "$sortSelector1 DESC";
			}

		// B) extract second level sort option:
		$sortSelector2 = $_REQUEST['sortSelector2'];
		if ($sortSelector2 != "")
			{
				// when field name = 'pages' then sort by 'first_page' instead:
				$sortSelector2 = str_replace("pages", "first_page", $sortSelector2);

				$sortRadio2 = $_REQUEST['sortRadio2'];
				if ($sortRadio2 == "0") // sort ascending
					$query .= ", $sortSelector2";
				else // sort descending
					$query .= ", $sortSelector2 DESC";
			}

		// C) extract third level sort option:
		$sortSelector3 = $_REQUEST['sortSelector3'];
		if ($sortSelector3 != "")
			{
				// when field name = 'pages' then sort by 'first_page' instead:
				$sortSelector3 = str_replace("pages", "first_page", $sortSelector3);

				$sortRadio3 = $_REQUEST['sortRadio3'];
				if ($sortRadio3 == "0") // sort ascending
					$query .= ", $sortSelector3";
				else // sort descending
					$query .= ", $sortSelector3 DESC";
			}

		// Since the sort popup menus use empty fields as delimiters between groups of fields
		// we'll have to trap the case that the user hasn't chosen any field names for sorting:
		if (eregi("ORDER BY $", $query))
			$query .= "author, year DESC, publication"; // use the default ORDER BY clause

		// Finally, fix the wrong syntax where its says "ORDER BY, author, title, ..." instead of "ORDER BY author, title, ...":
		$query = eregi_replace("ORDER BY , ","ORDER BY ",$query);


		return $query;
	}

	// --------------------------------------------------------------------

	// Note: function 'extractFormElementsRefineDisplay()' is defined in 'include.inc.php' since it's also used by 'users.php'

	// --------------------------------------------------------------------

	// Build the database query from records selected by the user within the query results list (which, in turn, was returned by 'search.php'):
	function extractFormElementsQueryResults($displayType, $originalDisplayType, $showLinks, $citeOrder, $orderBy, $userID, $sqlQuery, $referer, $recordSerialsArray)
	{
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		$recordsSelectionRadio = $_REQUEST['recordsSelectionRadio']; // extract user option whether we're supposed to process ALL records or just the ones that have been SELECTED on the current page

		// Process ALL found records:
		if ($recordsSelectionRadio == "1") // if the user checked the radio button next to the "All Found Records" option [this is the default]
		{
			// extract the 'WHERE' clause from the SQL query:
			$queryWhereClause = extractWHEREclause($sqlQuery); // function 'extractWHEREclause()' is defined in 'include.inc.php'

			$recordSerialsString = "";
		}

		// Process SELECTED records only:
		else // $recordsSelectionRadio == "0" // if the user checked the radio button next to the "Selected Records" option
		{
			// join array elements:
			if (!empty($recordSerialsArray)) // the user did check some checkboxes
				$recordSerialsString = implode("|", $recordSerialsArray); // separate record serials by "|" in order to facilitate regex querying...
			else // the user didn't check any checkboxes
				$recordSerialsString = "0"; // we use '0' which definitely doesn't exist as serial, resulting in a "nothing found" feedback

			$queryWhereClause = "serial RLIKE " . quote_smart("^(" . $recordSerialsString . ")$");
		}

		if (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_user_groups", $_SESSION['user_permissions']))) // if a user is logged in AND the 'user_permissions' session variable contains 'allow_user_groups', extract form elements which add/remove the selected records to/from a user's group:
		{
			$userGroupActionRadio = $_REQUEST['userGroupActionRadio']; // extract user option whether we're supposed to process an existing group name or any custom/new group name that was specified by the user

			// Extract the chosen user group from the request:
			// first, we need to check whether the user did choose an existing group name from the popup menu
			// -OR- if he/she did enter a custom group name in the text entry field:
			if ($userGroupActionRadio == "1") // if the user checked the radio button next to the group popup menu ('userGroupSelector') [this is the default]
			{
				if (isset($_REQUEST['userGroupSelector']))
					$userGroup = $_REQUEST['userGroupSelector']; // extract the value of the 'userGroupSelector' popup menu
				else
					$userGroup = "";
			}
			else // $userGroupActionRadio == "0" // if the user checked the radio button next to the group text entry field ('userGroupName')
			{
				if (isset($_REQUEST['userGroupName']))
					$userGroup = $_REQUEST['userGroupName']; // extract the value of the 'userGroupName' text entry field
				else
					$userGroup = "";
			}
		}

		// Depending on the chosen output format, construct an appropriate SQL query:
		// TODO: build the complete SQL query using functions 'buildFROMclause()' and 'buildORDERclause()'
		if (eregi("^Cite$", $displayType)) // (if any form element is selected, hitting <enter> will act as if the user clicked the 'Cite' button)
			{
				$query = buildSELECTclause($displayType, $showLinks); // function 'buildSELECTclause()' is defined in 'include.inc.php'

				if (isset($_SESSION['loginEmail'])) // if a user is logged in...
					$query .= " FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = " . quote_smart($userID) . " WHERE " . $queryWhereClause;
				else // NO user logged in
					$query .= " FROM $tableRefs WHERE " . $queryWhereClause;

				if ($citeOrder == "year") // sort records first by year (descending), then in the usual way:
					$query .= " ORDER BY year DESC, first_author, author_count, author, title";

				elseif ($citeOrder == "type") // sort records first by record type (and thesis type), then in the usual way:
					$query .= " ORDER BY type DESC, thesis DESC, first_author, author_count, author, year, title";

				elseif ($citeOrder == "type-year") // sort records first by record type (and thesis type), then by year (descending), then in the usual way:
					$query .= " ORDER BY type DESC, thesis DESC, year DESC, first_author, author_count, author, title";

				else // if any other or no '$citeOrder' parameter is specified, we supply the default ORDER BY pattern (which is suitable for citation in a journal etc.):
					$query .= " ORDER BY first_author, author_count, author, year, title";
			}

		elseif (eregi("^(Display|Export)$", $displayType))
			{
				$query = buildSELECTclause($displayType, $showLinks); // function 'buildSELECTclause()' is defined in 'include.inc.php'

				if (isset($_SESSION['loginEmail'])) // if a user is logged in...
					$query .= " FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = " . quote_smart($userID) . " WHERE " . $queryWhereClause . " ORDER BY $orderBy";
				else // NO user logged in
					$query .= " FROM $tableRefs WHERE " . $queryWhereClause . " ORDER BY $orderBy";
			}

		elseif (isset($_SESSION['loginEmail']) AND ereg("^(Remember|Add|Remove)$", $displayType)) // if a user (who's logged in) clicked the 'Remember', 'Add' or 'Remove' button...
			{
				if ($displayType == "Remember") // the user clicked the 'Remember' button
					if (!empty($recordSerialsArray)) // the user did check some checkboxes
						// save the the serials of all selected records to a session variable:
						saveSessionVariable("selectedRecords", $recordSerialsArray); // function 'saveSessionVariable()' is defined in 'include.inc.php'

				if (ereg("^(Add|Remove)$", $displayType) AND !empty($userGroup)) // the user clicked either the 'Add' or the 'Remove' button
					modifyUserGroups($tableUserData, $displayType, $recordSerialsArray, $recordSerialsString, $userID, $userGroup, $userGroupActionRadio); // add (remove) selected records to (from) the specified user group (function 'modifyUserGroups()' is defined in 'include.inc.php')


				// re-apply the current sqlQuery:
				$query = eregi_replace(" FROM $tableRefs",", orig_record FROM $tableRefs", $sqlQuery); // add 'orig_record' column (which is required in order to present visual feedback on duplicate records)
				$query = eregi_replace(" FROM $tableRefs",", serial FROM $tableRefs", $query); // add 'serial' column (which is required in order to obtain unique checkbox names)

				if ($showLinks == "1")
					$query = eregi_replace(" FROM $tableRefs",", file, url, doi, isbn, type FROM $tableRefs", $query); // add 'file', 'url', 'doi', 'isbn' & 'type columns

				// re-assign the correct display type if the user clicked the 'Remember', 'Add' or 'Remove' button of the 'queryResults' form:
				$displayType = $originalDisplayType;
			}


		return array($query, $displayType);
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the 'extract.php' form:
	function extractFormElementsExtract($showLinks, $citeOrder, $userID)
	{
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		global $loc; // '$loc' is made globally available in 'core.php'

		// Extract form elements (that are unique to the 'extract.php' form):
		$sourceText = $_REQUEST['sourceText']; // get the source text that contains the record serial numbers/cite keys
		$startDelim = $_REQUEST['startDelim']; // get the start delimiter that precedes record serial numbers/cite keys
		$endDelim = $_REQUEST['endDelim']; // get the end delimiter that follows record serial numbers/cite keys

		$startDelim = preg_quote($startDelim); // escape any potential meta-characters
		$endDelim = preg_quote($endDelim); // escape any potential meta-characters

		// Extract record serial numbers/cite keys from source text:
		$sourceText = "_" . $sourceText; // Note: by adding a character at the beginning of '$sourceText' we circumvent a problem with the regex pattern below which will strip everything up to the 2nd serial number/cite key if '$sourceText' starts with '$startDelim'
		$recordSerialsKeysString = preg_replace("/^.*?(?=$startDelim.+?$endDelim|$)/s", "", $sourceText); // remove any text preceeding the first serial number/cite key

		$recordSerialsKeysString = preg_replace("/$startDelim(.+?)$endDelim.*?(?=$startDelim.+?$endDelim|$)/s", "\\1_#_§_~_", $recordSerialsKeysString); // replace any text between serial numbers/cite keys (or between a serial number/cite key and the end of the text) with "_#_§_~_"; additionally, remove the delimiters enclosing the serial numbers/cite keys
		// Note: we do a quick'n dirty approach here, by inserting the string "_#_§_~_" as string delimiter between serial numbers/cite keys. Of course, this will only work as long the string "_#_§_~_" doesn't occur within '$sourceText'.
		$recordSerialsKeysString = preg_replace("/(_#_§_~_)?\n?$/s", "", $recordSerialsKeysString); // remove any trailing chars (like \n or "_#_§_~_") at end of line

		$recordSerialsKeysArray = preg_split("/_#_§_~_/", $recordSerialsKeysString, -1, PREG_SPLIT_NO_EMPTY); // split string containing the serial numbers/cite keys on the string delimiter "_#_§_~_" (the 'PREG_SPLIT_NO_EMPTY' flag causes only non-empty pieces to be returned)
		$recordSerialsKeysArray = array_unique($recordSerialsKeysArray); // remove any duplicate serial numbers/cite keys from the list of extracted record identifiers

		$recordSerialsArray = array();
		$escapedRecordKeysArray = array();
		$foundRecordSerialsKeysArray = array();
		$missingRecordSerialsKeysArray = array();

		foreach($recordSerialsKeysArray as $recordSerialKey)
		{
			if (preg_match("/^\d+$/", $recordSerialKey)) // every identifier which only contains digits is treated as a serial number! (In other words: cite keys must contain at least one non-digit character)
				$recordSerialsArray[] = $recordSerialKey;
			elseif (!empty($recordSerialKey)) // identifier is treated as cite key
			{
				$escapedRecordKey = preg_quote($recordSerialKey); // escape any potential meta-characters within cite key
				$escapedRecordKey = str_replace('\\','\\\\', $escapedRecordKey); // escape the escape character (i.e., make each backslash "\" a double backslash "\\")
				$escapedRecordKeysArray[] = $escapedRecordKey;
			}
		}

		$recordSerialsString = implode("|", $recordSerialsArray); // merge array of serial numbers again into a string, using "|" as delimiter
		$escapedRecordKeysString = implode("|", $escapedRecordKeysArray); // merge array of cite keys again into a string, using "|" as delimiter

		// Construct the SQL query:
		// TODO: build the complete SQL query using functions 'buildFROMclause()' and 'buildORDERclause()'

		// for the selected records, select all fields that are visible in Citation view:
		$query = buildSELECTclause("Cite", $showLinks); // function 'buildSELECTclause()' is defined in 'include.inc.php'

		$query .= " FROM $tableRefs"; // add FROM clause

		if (isset($_SESSION['loginEmail'])) // if a user is logged in...
			$query .= " LEFT JOIN $tableUserData ON serial = record_id AND user_id = " . quote_smart($userID); // add LEFT JOIN part to FROM clause

		// add WHERE clause:
		$query .= " WHERE";

		if (!empty($recordSerialsArray) OR (empty($recordSerialsArray) AND empty($escapedRecordKeysArray)) OR (empty($recordSerialsArray) AND !isset($_SESSION['loginEmail']))) // the second condition ensures a valid SQL query if no serial numbers or cite keys were found, same for the third condition if a user isn't logged in and '$sourceText' did only contain cite keys
			$query .= " serial RLIKE " . quote_smart("^(" . $recordSerialsString . ")$"); // add any serial numbers to WHERE clause

		if (!empty($recordSerialsArray) AND (!empty($escapedRecordKeysArray) AND isset($_SESSION['loginEmail'])))
			$query .= " OR";

		if (!empty($escapedRecordKeysArray) AND isset($_SESSION['loginEmail']))
			$query .= " cite_key RLIKE " . quote_smart("^(" . $escapedRecordKeysString . ")$"); // add any cite keys to WHERE clause

		// add ORDER BY clause:
		if ($citeOrder == "year") // sort records first by year (descending), then in the usual way:
			$query .= " ORDER BY year DESC, first_author, author_count, author, title";

		elseif ($citeOrder == "type") // sort records first by record type (and thesis type), then in the usual way:
			$query .= " ORDER BY type DESC, thesis DESC, first_author, author_count, author, year, title";

		elseif ($citeOrder == "type-year") // sort records first by record type (and thesis type), then by year (descending), then in the usual way:
			$query .= " ORDER BY type DESC, thesis DESC, year DESC, first_author, author_count, author, title";

		else // if any other or no '$citeOrder' parameter is specified, we supply the default ORDER BY pattern (which is suitable for citation in a journal etc.):
			$query .= " ORDER BY first_author, author_count, author, year, title";


		// Check whether the extracted serial numbers and cite keys exist in the database:
		$result = queryMySQLDatabase($query); // RUN the query on the database through the connection (function 'queryMySQLDatabase()' is defined in 'include.inc.php')

		if (@ mysql_num_rows($result) > 0) // if there were rows found ...
		{
			// Loop over each row in the result set:
			for ($rowCounter=0; $row = @ mysql_fetch_array($result); $rowCounter++)
			{
				if (!in_array($row["serial"], $foundRecordSerialsKeysArray) OR (!empty($row["cite_key"]) AND !in_array($row["cite_key"], $foundRecordSerialsKeysArray))) // if this record identifier hasn't been seen yet
				{
					// add this record's serial number and cite key to the array of found record serials and cite keys:
					$foundRecordSerialsKeysArray[] = $row["serial"];
					if (!empty($row["cite_key"]))
						$foundRecordSerialsKeysArray[] = $row["cite_key"];
				}
			}
		}

		$missingRecordSerialsKeysArray = array_diff($recordSerialsKeysArray, $foundRecordSerialsKeysArray); // get all unique array elements of '$recordSerialsKeysArray' which are not in '$foundRecordSerialsKeysArray'
		sort($missingRecordSerialsKeysArray);

		if (!empty($escapedRecordKeysArray) AND !isset($_SESSION['loginEmail'])) // a user can only use cite keys as record identifiers when he's logged in
			$messageSuffix = "<br>" . $loc["Warning_LoginToUseCiteKeysAsIdentifiers"] . "!";
		else
			$messageSuffix = "";

		if (!empty($missingRecordSerialsKeysArray) OR (!empty($escapedRecordKeysArray) AND !isset($_SESSION['loginEmail']))) // if some record identifiers could not be found in the database -OR- if a user tries to use cite keys while not being logged in
			// return an appropriate error message:
			$HeaderString = returnMsg("Following record identifiers could not be found: " . implode(", ", $missingRecordSerialsKeysArray), "warning", "strong", "HeaderString", "", $messageSuffix); // function 'returnMsg()' is defined in 'include.inc.php'


		return $query;
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the "Quick Search" form on the main page ('index.php'):
	// TODO: build the complete SQL query using functions 'buildFROMclause()' and 'buildORDERclause()'
	function extractFormElementsQuick($showLinks, $userID, $displayType)
	{
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		global $defaultFieldsListViewMajor; // these variables are specified in 'ini.inc.php'
		global $defaultFieldsListViewMinor;

		global $query;

		$quickSearchSelector = $_REQUEST['quickSearchSelector']; // extract field name chosen by the user
		$quickSearchName = $_REQUEST['quickSearchName']; // extract search text entered by the user

		// Build SELECT clause:
		if (eregi("^(Cite|Display)$", $displayType))
		{
			// Generate a SELECT clause that's appropriate for Citation view (or Details view):
			$query = buildSELECTclause($displayType, $showLinks); // function 'buildSELECTclause()' is defined in 'include.inc.php'
		}
		else // output found records in List view:
		{
			if ($quickSearchSelector == "main fields") // if we're supposed to query all of the "main fields" at once
			{
				$userMainFieldsArray = split(" *, *", $_SESSION['userMainFields']); // get the list of "main fields" preferred by the current user

				$additionalFields = $defaultFieldsListViewMinor; // note that for the "main fields" option, we simply display the default list of columns
			}
			else
			{
				// if the default list of "major" fields (to be displayed in List view) doesn't already contain the chosen field name...
				// (which is e.g. the case for the 'keywords' & 'abstract' fields)
				if (!ereg($quickSearchSelector, $defaultFieldsListViewMajor))
					$additionalFields = $quickSearchSelector; // ...add chosen field to SELECT query
				else
					$additionalFields = $defaultFieldsListViewMinor; // ...otherwise, add further default columns
			}

			$query = buildSELECTclause("", $showLinks, $additionalFields, false, true, $defaultFieldsListViewMajor);
		}


		// Build FROM clause:
		if (isset($_SESSION['loginEmail'])) // if a user is logged in...
			$query .= " FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = " . $userID;
		else // NO user logged in
			$query .= " FROM $tableRefs";


		// Build WHERE clause:
		$query .= " WHERE";

		// we construct a hierarchical '$searchArray' from the given search field name(s) & value;
		// this array then gets merged into a full SQL WHERE clause by function 'appendToWhereClause()'
		$searchArray = array();
		$searchArray[] = array("_boolean" => "",
		                       "_query"   => "serial RLIKE \".+\"");

		if ($quickSearchName != "") // if the user typed a search string into the text entry field...
		{
			if ($quickSearchSelector == "main fields")
			{
				$searchSubArray = array();

				foreach($userMainFieldsArray as $userMainField)
					$searchSubArray[] = array("_boolean" => "OR",
					                          "_query"   => $userMainField . " RLIKE " . quote_smart($quickSearchName));

				$searchArray[] = array("_boolean" => "AND",
				                       "_query"   => $searchSubArray);
			}
			else
				$searchArray[] = array("_boolean" => "AND",
				                       "_query"   => $quickSearchSelector . " RLIKE " . quote_smart($quickSearchName));
		}

		appendToWhereClause($searchArray); // function 'appendToWhereClause()' is defined in 'include.inc.php'

		$query .= " ORDER BY author, year DESC, publication"; // add the default ORDER BY clause


		return $query;
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the "Show My Group" form on the main page ('index.php') or above the query results list (that was produced by 'search.php'):
	// TODO: build the complete SQL query using functions 'buildFROMclause()' and 'buildORDERclause()'
	function extractFormElementsGroup($sqlQuery, $showLinks, $userID, $displayType, $originalDisplayType)
	{
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		$groupSearchSelector = $_REQUEST['groupSearchSelector']; // extract the user group chosen by the user

		// re-assign the correct display type (i.e. the view that was active when the user clicked the 'Show' button of the 'groupSearch' form):
		if (!empty($originalDisplayType))
			$displayType = $originalDisplayType;

		if (eregi("^(Cite|Display)$", $displayType))
		{
			// generate a SELECT clause that's appropriate for Citation view (or Details view):
			$query = buildSELECTclause($displayType, $showLinks); // function 'buildSELECTclause()' is defined in 'include.inc.php'
		}

		// output found records in List view:
		elseif (($displayType != "Browse") AND (!empty($sqlQuery))) // if we're not in Browse view and there's a previous SQL query available (as is the case if the group search originated from a search results page - and not from the main page 'index.php')
		{
			// use the custom set of colums chosen by the user:
			$previousSelectClause = extractSELECTclause($sqlQuery); // function 'extractSELECTclause()' is defined in 'include.inc.php'
			$query = buildSELECTclause("", $showLinks, "", false, true, $previousSelectClause);
		}
		else
		{
			// use the default SELECT statement:
			$query = buildSELECTclause("", $showLinks, "user_groups", false, true);
		}


		if (($displayType != "Browse") AND (!empty($sqlQuery)))
			// use the custom ORDER BY clause chosen by the user:
			$queryOrderBy = extractORDERBYclause($sqlQuery); // function 'extractORDERBYclause()' is defined in 'include.inc.php'
		else
			// add the default ORDER BY clause:
			$queryOrderBy = "author, year DESC, publication";


		$query .= " FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = " . $userID; // add FROM clause

		$query .= " WHERE user_groups RLIKE " . quote_smart("(^|.*;) *" . $groupSearchSelector. " *(;.*|$)"); // add WHERE clause

		$query .= " ORDER BY " . $queryOrderBy; // add ORDER BY clause


		return array($query, $displayType);
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the "Show My Refs" form on the
	// main page ('index.php') which searches the user specific fields from table 'user_data':
	// Note: Although the "Show My Refs" form on 'index.php' is of method="POST" we do accept
	//       GET queries as well in order to allow for the 'My Refs' links provided by the
	//       'showLogin()' function (from 'include.inc.php').
	function extractFormElementsMyRefs($showLinks, $loginEmail, $userID)
	{
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		$myRefsRadio = $_REQUEST['myRefsRadio']; // will be "1" if the user wants to display ALL of his records, otherwise it will be "0"

		// extract form popup 'marked/not marked':
		if (isset($_REQUEST['findMarked']))
			$findMarked = $_REQUEST['findMarked']; // will be "1" if the user wants to search the 'marked' field
		else
			$findMarked = "";

		if (isset($_REQUEST['markedSelector']))
			$markedSelector = $_REQUEST['markedSelector']; // extract 'marked' field value chosen by the user
		else
			$markedSelector = "";

		// extract form popup 'selected/not selected':
		if (isset($_REQUEST['findSelected']))
			$findSelected = $_REQUEST['findSelected']; // will be "1" if the user wants to search the 'selected' field
		else
			$findSelected = "";

		if (isset($_REQUEST['selectedSelector']))
			$selectedSelector = $_REQUEST['selectedSelector']; // extract 'selected' field value chosen by the user
		else
			$selectedSelector = "";

		// extract form popup 'copy = true/fetch/ordered/false':
		if (isset($_REQUEST['findCopy']))
			$findCopy = $_REQUEST['findCopy']; // will be "1" if the user wants to search the 'copy' field
		else
			$findCopy = "";

		if (isset($_REQUEST['copySelector']))
			$copySelector = $_REQUEST['copySelector']; // extract 'copy' field value chosen by the user
		else
			$copySelector = "";

		// extract form text entry field 'key':
		if (isset($_REQUEST['findUserKeys']))
			$findUserKeys = $_REQUEST['findUserKeys']; // will be "1" if the user wants to search the 'user_keys' field
		else
			$findUserKeys = "";

		if (isset($_REQUEST['userKeysName']))
			$userKeysName = $_REQUEST['userKeysName']; // extract user keys entered by the user
		else
			$userKeysName = "";

		// extract form text entry field 'note':
		if (isset($_REQUEST['findUserNotes']))
			$findUserNotes = $_REQUEST['findUserNotes']; // will be "1" if the user wants to search the 'user_notes' field
		else
			$findUserNotes = "";

		if (isset($_REQUEST['userNotesName']))
			$userNotesName = $_REQUEST['userNotesName']; // extract user notes entered by the user
		else
			$userNotesName = "";

		// extract form text entry field 'file':
		if (isset($_REQUEST['findUserFile']))
			$findUserFile = $_REQUEST['findUserFile']; // will be "1" if the user wants to search the 'user_file' field
		else
			$findUserFile = "";

		if (isset($_REQUEST['userFileName']))
			$userFileName = $_REQUEST['userFileName']; // extract file specification entered by the user
		else
			$userFileName = "";

		if ($myRefsRadio == "0") // if the user only wants to display a subset of his records:
		{
			$additionalFieldsArray = array();

			if ($findMarked == "1") // if the user wants to search the 'marked' field...
				$additionalFieldsArray[] = "marked"; // ...add 'marked' field to SELECT query

			if ($findSelected == "1") // if the user wants to search the 'selected' field...
				$additionalFieldsArray[] = "selected"; // ...add 'selected' field to SELECT query

			if ($findCopy == "1") // if the user wants to search the 'copy' field...
				$additionalFieldsArray[] = "copy"; // ...add 'copy' field to SELECT query

			if ($findUserKeys == "1") // if the user wants to search the 'user_keys' field...
				$additionalFieldsArray[] = "user_keys"; // ...add 'user_keys' to SELECT query

			if ($findUserNotes == "1") // if the user wants to search the 'user_notes' field...
				$additionalFieldsArray[] = "user_notes"; // ...add 'user_notes' to SELECT query

			if ($findUserFile == "1") // if the user wants to search the 'user_file' field...
				$additionalFieldsArray[] = "user_file"; // ...add 'user_file' to SELECT query

			$additionalFields = implode(", ", $additionalFieldsArray); // merge array of additional fields into a string, using ", " as delimiter
		}
		else
			$additionalFields = "";
			

		// construct the SQL query:
		// TODO: build the complete SQL query using functions 'buildFROMclause()' and 'buildORDERclause()'

		$query = buildSELECTclause("", $showLinks, $additionalFields, false, true); // function 'buildSELECTclause()' is defined in 'include.inc.php'

		$query .= " FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = " . $userID . " WHERE location RLIKE \"$loginEmail\""; // add FROM & (initial) WHERE clause


		if ($myRefsRadio == "0") // if the user only wants to display a subset of his records:
			{
				if ($findMarked == "1") // if the user wants to search the 'marked' field...
					{
						if ($markedSelector == "marked")
							$query .= " AND marked = \"yes\""; // ...add 'marked' field name & value to the sql query
						else // $markedSelector == "not marked" (i.e., 'marked' is either 'no' -or- NULL)
							$query .= " AND (marked = \"no\" OR marked IS NULL)"; // ...add 'marked' field name & value to the sql query
					}

				if ($findSelected == "1") // if the user wants to search the 'selected' field...
					{
						if ($selectedSelector == "selected")
							$query .= " AND selected = \"yes\""; // ...add 'selected' field name & value to the sql query
						else // $selectedSelector == "not selected" (i.e., 'selected' is either 'no' -or- NULL)
							$query .= " AND (selected = \"no\" OR selected IS NULL)"; // ...add 'selected' field name & value to the sql query
					}

				if ($findCopy == "1") // if the user wants to search the 'copy' field...
					{
						if ($copySelector == "true")
							$query .= " AND copy = \"true\""; // ...add 'copy' field name & value to the sql query
						elseif ($copySelector == "ordered")
							$query .= " AND copy = \"ordered\""; // ...add 'copy' field name & value to the sql query
						elseif ($copySelector == "fetch")
							$query .= " AND copy = \"fetch\""; // ...add 'copy' field name & value to the sql query
						else // 'copy' is either 'false' -or- NULL
							$query .= " AND (copy = \"false\" OR copy IS NULL)"; // ...add 'copy' field name & value to the sql query
					}

				if ($findUserKeys == "1") // if the user wants to search the 'user_keys' field...
					if ($userKeysName != "") // if the user typed a search string into the text entry field...
						$query .= " AND user_keys RLIKE " . quote_smart($userKeysName); // ...add 'user_keys' field name & value to the sql query

				if ($findUserNotes == "1") // if the user wants to search the 'user_notes' field...
					if ($userNotesName != "") // if the user typed a search string into the text entry field...
						$query .= " AND user_notes RLIKE " . quote_smart($userNotesName); // ...add 'user_notes' field name & value to the sql query

				if ($findUserFile == "1") // if the user wants to search the 'user_file' field...
					if ($userFileName != "") // if the user typed a search string into the text entry field...
						$query .= " AND user_file RLIKE " . quote_smart($userFileName); // ...add 'user_file' field name & value to the sql query
			}


		$query .= " ORDER BY author, year DESC, publication"; // add the default ORDER BY clause


		return $query;
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the "Browse My Refs" form on the
	// main page ('index.php') which lets the user browse a particular field:
	function extractFormElementsBrowseMyRefs($showLinks, $loginEmail, $userID)
	{
		// IMPORTANT NOTE: Browse functionality is NOT fully implemented yet!!

		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		$browseFieldSelector = $_REQUEST['browseFieldSelector']; // extract field name chosen by the user

		// construct the SQL query:
		// TODO: build the complete SQL query using functions 'buildFROMclause()' and 'buildORDERclause()'

		// if the chosen field can contain multiple items...
		// TODO: we really should check here if the corresponding 'ref_...' table exists!
		if (eregi("^(author|keywords|editor|language|summary_language|area|location|user_keys|user_groups)$", $browseFieldSelector))
		{
			list($refTableName, $browseFieldName) = buildRefTableAndFieldNames($browseFieldSelector); // get correct table name and field name for the 'ref_...' table that matches the chosen field

			$browseFieldColumnName = " AS " . preg_replace("/^ref_(\w+)$/i", "\\1", $browseFieldName); // strip the 'ref_' prefix for the column name

			$queryRefTableLeftJoinPart = " LEFT JOIN $refTableName ON serial = ref_id"; // ...add the appropriate 'LEFT JOIN...' part to the 'FROM' clause
			if (eregi("^(user_keys|user_groups)$", $browseFieldSelector))
				$queryRefTableLeftJoinPart .= " AND ref_user_id = " . quote_smart($userID); // add the user's user_id as additional condition to this 'LEFT JOIN...' part
		}
		else
		{
			$browseFieldName = $browseFieldSelector;
			$browseFieldColumnName = "";
			$queryRefTableLeftJoinPart = "";
		}

		$query = buildSELECTclause("Browse", $showLinks, "", false, false, "", $browseFieldName . $browseFieldColumnName); // function 'buildSELECTclause()' is defined in 'include.inc.php'

		// if a user specific field was chosen...
		if (eregi("^(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)$", $browseFieldSelector))
			$query .= " FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = " . $userID; // add FROM clause and the appropriate 'LEFT JOIN...' part
		else
			$query .= " FROM $tableRefs"; // add FROM clause

		$query .= $queryRefTableLeftJoinPart; // add additional 'LEFT JOIN...' part (if required)

		$query .= " WHERE location RLIKE " . quote_smart($loginEmail); // add (initial) WHERE clause

		$query .= " GROUP BY $browseFieldName"; // add the GROUP BY clause

		$query .= " ORDER BY records DESC, $browseFieldName"; // add the default ORDER BY clause


		return $query;
	}

	// --------------------------------------------------------------------

	// Add columns given in '$columnsArray' to the list of fields available in the
	// List View SELECT clause if they were marked in the search form interface:
	function addToSelectClause($columnsArray)
	{
		$selectClauseColumnsArray = array();

		foreach ($columnsArray as $checkboxName => $columnName)
		{
			// If the user has checked the checkbox next to this column,
			// add it to the SELECT clause:
			if (isset($_REQUEST[$checkboxName]) AND ($_REQUEST[$checkboxName] == "1"))
				$selectClauseColumnsArray[$columnName] = $columnName;
		}

		// force add 'author' column if the user hasn't checked any of the column checkboxes:
		if (empty($selectClauseColumnsArray))
			$selectClauseColumnsArray['author'] = "author";


		return $selectClauseColumnsArray;
	}

	// --------------------------------------------------------------------

	// NOTHING FOUND
	// informs the user that no results were found for the current query/action
	function nothingFound($nothingChecked)
	{
		global $client;

		if (eregi("^cli", $client)) // if the query originated from a command line client such as the refbase CLI clients ("cli-refbase-1.1", "cli-refbase_import-1.0")
		{
			$nothingFoundFeedback = "Nothing found!\n\n"; // return plain text
		}
		else // return HTML
		{
			$nothingFoundFeedback = "\n<table id=\"error\" class=\"results\" align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds the database results for your query\">";

			if ($nothingChecked)
				// Inform the user that no records were selected:
				$nothingFoundFeedback .= "\n<tr>\n\t<td valign=\"top\">No records selected! Please select one or more records by clicking the appropriate checkboxes.&nbsp;&nbsp;<a href=\"javascript:history.back()\">Go Back</a></td>\n</tr>";
			else // $nothingChecked == false (i.e., the user did check some checkboxes) -OR- the query resulted from another script like 'show.php' (which has no checkboxes to mark!)
			{
				// Report that nothing was found:
				$nothingFoundFeedback .= "\n<tr>\n\t<td valign=\"top\">Sorry, but your query didn't produce any results!";

				if (!eregi("^inc", $client))
					$nothingFoundFeedback .= "&nbsp;&nbsp;<a href=\"javascript:history.back()\">Go Back</a></td>\n</tr>";
			}

			$nothingFoundFeedback .= "\n</table>";
		}


		return $nothingFoundFeedback;
	}

	// --------------------------------------------------------------------

	// PRINT LINKS
	// this function prints out available links in List view and Citation view
	// (for links of type DOI/URL/ISBN/XREF, only one link will be printed; order of preference: DOI, URL, ISBN, XREF)
	function printLinks($showLinkTypes, $row, $showQuery, $showLinks, $wrapResults, $userID, $viewType, $orderBy)
	{
		global $databaseBaseURL; // these variables are defined in 'ini.inc.php'
		global $filesBaseURL;
		global $fileVisibility;
		global $fileVisibilityException;
		global $openURLResolver;
		global $isbnURLFormat;
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		global $client;

		// Note: for proper placement of links within the Links column we don't use the 'mergeLinks()' function here (as is done for Details view),
		//       since spacing before links is handled individually for each link type

		$links = ""; // make sure that our buffer variable is empty

		// count the number of available link elements:
		$linkElementCounterLoggedOut = 0;

		// if the 'user_permissions' session variable contains 'allow_details_view'...
		if (in_array("details", $showLinkTypes) AND isset($_SESSION['user_permissions']) AND ereg("allow_details_view", $_SESSION['user_permissions']))
			$linkElementCounterLoggedOut = ($linkElementCounterLoggedOut + 1);

		// if the 'user_permissions' session variable contains 'allow_edit'...
		if (in_array("edit", $showLinkTypes) AND isset($_SESSION['user_permissions']) AND ereg("allow_edit", $_SESSION['user_permissions']))
			$linkElementCounterLoggedOut = ($linkElementCounterLoggedOut + 1);

		// if either the URL or the DOI field contain something
		if ((in_array("url", $showLinkTypes) AND !empty($row["url"])) OR (in_array("doi", $showLinkTypes) AND !empty($row["doi"])))
			$linkElementCounterLoggedOut = ($linkElementCounterLoggedOut + 1);

		// in case an ISBN number was given
		elseif (in_array("isbn", $showLinkTypes) AND !empty($isbnURLFormat) AND !empty($row["isbn"])) // provide a link to an ISBN resolver
			$linkElementCounterLoggedOut = ($linkElementCounterLoggedOut + 1);

		// if we're supposed to auto-generate an OpenURL link
		elseif (in_array("xref", $showLinkTypes) AND !empty($openURLResolver))
			$linkElementCounterLoggedOut = ($linkElementCounterLoggedOut + 1);

		$linkElementCounterLoggedIn = $linkElementCounterLoggedOut;

		// if a user is logged in and a FILE is associated with the current record
		if (in_array("file", $showLinkTypes) AND ($fileVisibility == "everyone" OR ($fileVisibility == "login" AND isset($_SESSION['loginEmail'])) OR ($fileVisibility == "user-specific" AND (isset($_SESSION['user_permissions']) AND ereg("allow_download", $_SESSION['user_permissions']))) OR (!empty($fileVisibilityException) AND preg_match($fileVisibilityException[1], $row[$fileVisibilityException[0]]))))
			if (!empty($row["file"]))// if the 'file' field is NOT empty
				$linkElementCounterLoggedIn = ($linkElementCounterLoggedIn + 1);


		if (eregi("^inc", $client)) // we open links in a new browser window if refbase data are included somewhere else:
			$target = " target=\"_blank\"";
		else
			$target = "";

		if (eregi("^cli", $client) OR ($wrapResults == "0")) // we use absolute links for CLI clients or when returning only a partial document structure
			$baseURL = $databaseBaseURL;
		else
			$baseURL = "";


		if (in_array("details", $showLinkTypes) AND isset($_SESSION['user_permissions']) AND ereg("allow_details_view", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_details_view'...
		{
			// display a link that opens the Details view for this record:
			// NOTE: we use a 'show.php' URL here since it is much shorter and easier to bookmark as a permanent link; however,
			//       this means one additional redirect; the old code that directly generates a 'search.php' URL is commented out below
			// TODO: verify that the time lag introduced by the redirect action is generally acceptable!
			$queryParametersArray = array("record" => $row["serial"]);

			// we only add further parameters to the 'show.php' URL if their current value differs from the defaults used by 'show.php' or 'search.php':
			if (!empty($viewType) AND $viewType != "Web")
				$queryParametersArray["viewType"] = $viewType;

			if ($showQuery == "1")
				$queryParametersArray["showQuery"] = $showQuery;

			if ($showLinks == "0") // this is kinda superfluous since, for '$showLinks=0', the link isn't shown in the first place
				$queryParametersArray["showLinks"] = $showLinks;

			$links .= "\n\t\t<a href=\"" . $baseURL . generateURL("show.php", "html", $queryParametersArray, true) . "\"" . $target . ">"
			        . "<img src=\"" . $baseURL . "img/details.gif\" alt=\"details\" title=\"show details\" width=\"9\" height=\"17\" hspace=\"0\" border=\"0\"></a>";

			// Old code that directly generates a 'search.php' URL which points to Details view for this record:
//			// Construct the SQL query:
//			// TODO: build the complete SQL query first (using functions 'buildFROMclause()' and 'buildORDERclause()'), then rawurlencode and add to link
//			$showDetailsQuery = buildSELECTclause("Display", $showLinks, "", false, false); // function 'buildSELECTclause()' is defined in 'include.inc.php'
//
//			// ... display a link that opens the Details view for this record:
//			if (isset($_SESSION['loginEmail'])) // if a user is logged in, show user specific fields:
//				$links .= "\n\t\t<a href=\"" . $baseURL . "search.php"
//				        . "?sqlQuery=" . rawurlencode($showDetailsQuery) . "%20FROM%20" . $tableRefs . "%20LEFT%20JOIN%20" . $tableUserData . "%20ON%20serial%20%3D%20record_id%20AND%20user_id%20%3D%20" . $userID . "%20";
//			else // if NO user logged in, don't display any user specific fields and hide the 'location' field:
//				$links .= "\n\t\t<a href=\"" . $baseURL . "search.php"
//				        . "?sqlQuery=" . rawurlencode($showDetailsQuery) . "%20FROM%20" . $tableRefs . "%20";
//
//			$links .= "WHERE%20serial%20RLIKE%20%22%5E%28" . $row["serial"]
//			        . "%29%24%22%20ORDER%20BY%20" . rawurlencode($orderBy)
//			        . "&amp;formType=sqlSearch"
//			        . "&amp;showQuery=" . $showQuery
//			        . "&amp;showLinks=" . $showLinks
//			        . "&amp;submit=Display"
//			        . "&amp;viewType=" . $viewType
//			        . "\"" . $target . ">"
//			        . "<img src=\"" . $baseURL . "img/details.gif\" alt=\"details\" title=\"show details\" width=\"9\" height=\"17\" hspace=\"0\" border=\"0\"></a>";
		}

		if ((($linkElementCounterLoggedOut > 0) OR (isset($_SESSION['loginEmail']) AND $linkElementCounterLoggedIn > 0)) AND (in_array("details", $showLinkTypes) AND isset($_SESSION['user_permissions']) AND ereg("allow_details_view", $_SESSION['user_permissions'])))
			$links .= "&nbsp;&nbsp;";

		if (in_array("edit", $showLinkTypes) AND isset($_SESSION['user_permissions']) AND ereg("allow_edit", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_edit'...
			// ... display a link that opens the edit form for this record:
			$links .= "\n\t\t<a href=\"" . $baseURL . "record.php"
			        . "?serialNo=" . $row["serial"]
			        . "&amp;recordAction=edit"
			        . "\"" . $target . ">"
			        . "<img src=\"" . $baseURL . "img/edit.gif\" alt=\"edit\" title=\"edit record\" width=\"11\" height=\"17\" hspace=\"0\" border=\"0\"></a>";

		if ((($linkElementCounterLoggedOut > 1) OR (isset($_SESSION['loginEmail']) AND $linkElementCounterLoggedIn > 1)) AND (in_array("edit", $showLinkTypes) AND isset($_SESSION['user_permissions']) AND ereg("allow_edit", $_SESSION['user_permissions'])))
		{
			if (in_array("details", $showLinkTypes) AND isset($_SESSION['user_permissions']) AND ereg("allow_details_view", $_SESSION['user_permissions']))
				$links .= "\n\t\t<br>";
			else
				$links .= "&nbsp;&nbsp;";
		}

		// show a link to any corresponding file if one of the following conditions is met:
		// - the variable '$fileVisibility' (defined in 'ini.inc.php') is set to 'everyone'
		// - the variable '$fileVisibility' is set to 'login' AND the user is logged in
		// - the variable '$fileVisibility' is set to 'user-specific' AND the 'user_permissions' session variable contains 'allow_download'
		// - the array variable '$fileVisibilityException' (defined in 'ini.inc.php') contains a pattern (in array element 1) that matches the contents of the field given (in array element 0)
		if (in_array("file", $showLinkTypes) AND ($fileVisibility == "everyone" OR ($fileVisibility == "login" AND isset($_SESSION['loginEmail'])) OR ($fileVisibility == "user-specific" AND (isset($_SESSION['user_permissions']) AND ereg("allow_download", $_SESSION['user_permissions']))) OR (!empty($fileVisibilityException) AND preg_match($fileVisibilityException[1], $row[$fileVisibilityException[0]]))))
		{
			if (!empty($row["file"]))// if the 'file' field is NOT empty
			{
				if (ereg("^(https?|ftp|file)://", $row["file"])) // if the 'file' field contains a full URL (starting with "http://", "https://", "ftp://" or "file://")
					$URLprefix = ""; // we don't alter the URL given in the 'file' field
				else // if the 'file' field contains only a partial path (like 'polarbiol/10240001.pdf') or just a file name (like '10240001.pdf')
				{
					// use the base URL of the standard files directory as prefix:
					if (ereg('^/', $filesBaseURL)) // absolute path -> file dir is located outside of refbase root dir
					{
						if (eregi("^cli", $client) OR ($wrapResults == "0")) // we use absolute links for CLI clients or when returning only a partial document structure
							$URLprefix = 'http://' . $_SERVER['HTTP_HOST'] . $filesBaseURL; // note that '$baseURL' cannot be used here since we need to prefix '$filesBaseURL' only with the host URL (and not the '$databaseBaseURL')
						else
							$URLprefix = $filesBaseURL;
					}
					else // relative path -> file dir is located within refbase root dir
						$URLprefix = $baseURL . $filesBaseURL;
				}

				if (eregi("\.pdf$", $row["file"])) // if the 'file' field contains a link to a PDF file
					$links .= "\n\t\t<a href=\"" . $URLprefix . $row["file"] . "\"" . $target . "><img src=\"" . $baseURL . "img/file_PDF.gif\" alt=\"pdf\" title=\"download PDF file\" width=\"17\" height=\"17\" hspace=\"0\" border=\"0\"></a>"; // display a PDF file icon as download link
				else
					$links .= "\n\t\t<a href=\"" . $URLprefix . $row["file"] . "\"" . $target . "><img src=\"" . $baseURL . "img/file.gif\" alt=\"file\" title=\"download file\" width=\"11\" height=\"15\" hspace=\"0\" border=\"0\"></a>"; // display a generic file icon as download link
			}
		}

		// if a DOI number exists for this record, we'll prefer it as link, otherwise we use the URL (if available):
		// (note, that in List view, we'll use the same icon, no matter if the DOI or the URL is used for the link)
		if (in_array("doi", $showLinkTypes) AND !empty($row["doi"]))
			$links .= "\n\t\t<a href=\"http://dx.doi.org/" . $row["doi"] . "\"" . $target . "><img src=\"" . $baseURL . "img/link.gif\" alt=\"doi\" title=\"goto web page (via DOI)\" width=\"11\" height=\"8\" hspace=\"0\" border=\"0\"></a>";

		elseif (in_array("url", $showLinkTypes) AND !empty($row["url"])) // 'htmlentities()' is used to convert any '&' into '&amp;'
			$links .= "\n\t\t<a href=\"" . encodeHTML($row["url"]) . "\"" . $target . "><img src=\"" . $baseURL . "img/link.gif\" alt=\"url\" title=\"goto web page\" width=\"11\" height=\"8\" hspace=\"0\" border=\"0\"></a>";

		// if an ISBN number exists for the current record, provide a link to an ISBN resolver:
		elseif (in_array("isbn", $showLinkTypes) AND !empty($isbnURLFormat) AND !empty($row["isbn"]))
		{
			// this is a stupid hack that maps the names of the '$row' array keys to those used
			// by the '$formVars' array (which is required by function 'parsePlaceholderString()')
			// (eventually, the '$formVars' array should use the MySQL field names as names for its array keys)
			$formVars = buildFormVarsArray($row); // function 'buildFormVarsArray()' is defined in 'include.inc.php'

			// auto-generate an ISBN link according to the naming scheme given in '$isbnURLFormat' (in 'ini.inc.php'):
			$isbnURL = parsePlaceholderString($formVars, $isbnURLFormat, ""); // function 'parsePlaceholderString()' is defined in 'include.inc.php'

			$encodedURL = encodeHTML($isbnURL); // 'htmlentities()' is used to convert higher ASCII chars into its entities and any '&' into '&amp;'
			$encodedURL = str_replace(" ", "%20", $encodedURL); // ensure that any spaces are also properly urlencoded

			if (!empty($isbnURL))
				$links .= "\n\t\t<a href=\"" . $encodedURL . "\"" . $target . "><img src=\"" . $baseURL . "img/resolve.gif\" alt=\"isbn\" title=\"find book details (via ISBN)\" width=\"11\" height=\"8\" hspace=\"0\" border=\"0\"></a>";
		}

		// if still no link was generated, we'll provide a link to an OpenURL resolver:
		elseif (in_array("xref", $showLinkTypes) AND !empty($openURLResolver))
		{
			$openURL = openURL($row); // function 'openURL()' is defined in 'openurl.inc.php'
			$links .= "\n\t\t<a href=\"" . $openURL . "\"" . $target . "><img src=\"" . $baseURL . "img/resolve.gif\" alt=\"openurl\" title=\"find record details (via OpenURL)\" width=\"11\" height=\"8\" hspace=\"0\" border=\"0\"></a>";
		}

		// insert COinS (ContextObjects in Spans):
		$links .= "\n\t\t" . coins($row); // function 'coins()' is defined in 'openurl.inc.php'

		return $links;
	}

	// --------------------------------------------------------------------

	// MERGE LINKS
	// this function will merge links with delimiters appropriate for display in the Links column
	function mergeLinks($linkArray)
	{
		$totalLinkCount = count($linkArray); // check how many links we're dealing with

		$linkString = "";

		if (!empty($linkArray)) // if some links are present
		{
			if ($totalLinkCount == 1) // only one link
			{
				$linkString = "&nbsp;&nbsp;" . $linkArray[0];
			}
			else // multiple links
			{
				for ($linkCounter=0; $linkCounter < ($totalLinkCount - 1); $linkCounter++) // first array element has offset '0' so we decrement '$totalLinkCount' by 1
				{
					if (is_integer(($linkCounter + 1)/2)) // even number
						$suffix = "<br>"; // a set of two links is printed per row
					else // uneven number
						$suffix = "&nbsp;";

					$linkString .=  $linkArray[$linkCounter] . $suffix;
				}

				$linkString .=  $linkArray[($totalLinkCount - 1)]; // append last link
			}
		}

		return $linkString;
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
	if (!eregi("^cli", $client) AND ($wrapResults != "0") AND (!(($displayType == "Cite") AND (!eregi("^html$", $citeType))) OR ($rowsFound == 0))) // we exclude the HTML page footer for citation formats other than HTML if something was found
	{
		if ((!eregi("^(Print|Mobile)$", $viewType)) AND (!eregi("^inc", $client))) // Note: we omit the visible footer in print/mobile view ('viewType=Print' or 'viewType=Mobile') and for include mechanisms!
			showPageFooter($HeaderString);

		displayHTMLfoot();
	}

	// --------------------------------------------------------------------
?>
