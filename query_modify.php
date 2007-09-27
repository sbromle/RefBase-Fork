<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./query_modify.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    23-May-04, 20:42
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This php script will perform adding, editing & deleting of user queries.
	// It then relocates back to the main page ('index.php') so that the user
	// can verify the changes.
	// TODO: I18n


	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
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

	// Clear any errors that might have been found previously:
	$errors = array();

	// Write the (POST) form variables into an array:
	foreach($_POST as $varname => $value)
		$formVars[$varname] = trim($value); // remove any leading or trailing whitespace from the field's contents & copy the trimmed string to the '$formVars' array
//		$formVars[$varname] = trim(clean($value, 50)); // the use of the clean function would be more secure!

	// --------------------------------------------------------------------

	// Extract form variables sent through POST:
	// Note: Although we could use the '$formVars' array directly below (e.g.: $formVars['pageLoginStatus'] etc., like in 'user_validation.php'), we'll read out
	//       all variables individually again. This is done to enhance readability. (A smarter way of doing so seems be the use of the 'extract()' function, but that
	//       may expose yet another security hole...)

	// First of all, check if this script was called by something else than 'query_manager.php':
	if (!ereg(".+/query_manager.php", $_SERVER['HTTP_REFERER']))
	{
		// return an appropriate error message:
		$HeaderString = returnMsg($loc["Warning_InvalidCallToScript"] . " '" . scriptURL() . "'!", "warning", "strong", "HeaderString"); // functions 'returnMsg()' and 'scriptURL()' are defined in 'include.inc.php'

		if (!empty($_SERVER['HTTP_REFERER'])) // if the referer variable isn't empty
			header("Location: " . $_SERVER['HTTP_REFERER']); // redirect to calling page
		else
			header("Location: index.php"); // redirect to main page ('index.php')

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// Extract the form used by the user:
	$formType = $formVars['formType'];

	// Extract the type of action requested by the user (either 'add', 'edit', 'delet', or ''):
	// ('' will be treated equal to 'add')
	if (isset($formVars['queryAction']) AND !empty($formVars['queryAction']))
		$queryAction = $formVars['queryAction'];
	else
		$queryAction = "add";

	// Determine the button that was hit by the user (either 'Add Query', 'Edit Query', 'Delete Query' or ''):
	// '$submitAction' is only used to determine any 'delet' action! (where '$submitAction' = 'Delete Query')
	// (otherwise, only '$queryAction' controls how to proceed)
	$submitAction = $formVars['submit'];
	if ($submitAction == "Delete Query") // *delete* Query
		$queryAction = "delet";

	// get the query ID (if any) which is required when editing queries:
	if (isset($formVars['queryID']))
		$queryID = $formVars['queryID'];
	else
		$queryID = "";

	// Extract the type of display requested by the user (normally, either 'Display', 'Cite' or ''):
	// ('' will produce the default columnar output style)
	// Note: In contrast to other scripts, the 'displayType' parameter is not passed via the submit button but via a hidden form tag 'displayType'
	if (isset($formVars['displayType']))
		$displayType = $formVars['displayType'];
	else
		$displayType = "";

	// For a given display type, extract the view type requested by the user (either 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($formVars['queryViewType']))
		$queryViewType = $formVars['queryViewType'];
	else
		$queryViewType = "";

	// Extract other form values provided by 'query_manager.php':
	if (isset($formVars['queryName']))
		$queryName = $formVars['queryName'];
	else
		$queryName = "";

	if (isset($formVars['sqlQuery']))
		$sqlQuery = $formVars['sqlQuery'];
	else
		$sqlQuery = "";

	if (isset($formVars['showQuery']))
		$showQuery = $formVars['showQuery'];
	else
		$showQuery = "";

	if (isset($formVars['showLinks']))
		$showLinks = $formVars['showLinks'];
	else
		$showLinks = "";

	if (isset($formVars['showRows']) AND ereg("^[1-9]+[0-9]*$", $formVars['showRows'])) // NOTE: we silently adjust the 'showRows' parameter if anything other than a positive integer was given
		$showRows = $formVars['showRows'];
	else
		$showRows = $_SESSION['userRecordsPerPage']; // get the default number of records per page preferred by the current user

	if (isset($formVars['citeStyleSelector']))
		$citeStyle = $formVars['citeStyleSelector']; // get the cite style chosen by the user
	else
		$citeStyle = "";
	if (ereg("%20", $citeStyle)) // if '$citeStyle' still contains URL encoded data... ('%20' is the URL encoded form of a space, see note below!)
		$citeStyle = rawurldecode($citeStyle); // ...URL decode 'citeStyle' statement (it was URL encoded before incorporation into a hidden tag of the 'sqlSearch' form to avoid any HTML syntax errors)
													// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_REQUEST'!
													//       But, opposed to that, URL encoded data that are included within a form by means of a *hidden form tag* will NOT get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!

	if (isset($formVars['citeOrder']))
		$citeOrder = $formVars['citeOrder']; // get information how the data should be sorted. If this param is set to 'Year', records will be listed in blocks sorted by year.
	else
		$citeOrder = "";

	if (isset($formVars['oldQuery']))
	{
		$oldQuery = $formVars['oldQuery']; // fetch the query URL of the formerly displayed results page so that its's available on the subsequent receipt page that follows any add/edit/delete action!
		if (ereg('sqlQuery%3D', $oldQuery)) // if '$oldQuery' still contains URL encoded data... ('%3D' is the URL encoded form of '=', see note below!)
			$oldQuery = rawurldecode($oldQuery); // ...URL decode old query URL (it was URL encoded before incorporation into a hidden tag of the 'record' form to avoid any HTML syntax errors)
											// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_POST'!
											//       But, opposed to that, URL encoded data that are included within a form by means of a *hidden form tag* will NOT get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!
		$oldQuery = stripSlashesIfMagicQuotes($oldQuery); // function 'stripSlashesIfMagicQuotes()' is defined in 'include.inc.php'
//		$oldQuery = str_replace('\"','"',$oldQuery); // replace any \" with "
	}
	else
		$oldQuery = "";


	if (isset($formVars['origQueryName']))
		$origQueryName = rawurldecode($formVars['origQueryName']); // get the original query name that was included within a hidden form tag (and since it got URL encoded, we'll need to decode it again)
	else
		$origQueryName = "";

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase($oldQuery); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// VALIDATE data fields:

	// NOTE: for all fields that are validated here must exist error parsing code (of the form: " . fieldError("languageName", $errors) . ")
	//       in front of the respective <input> form field in 'query_manager.php'! Otherwise the generated error won't be displayed!

	// Validate the 'Query Name' field:
	if (empty($queryName))
		$errors["queryName"] = "You must specify a name for your query:"; // 'queryName' must not be empty

	elseif (ereg(";", $queryName))
		$errors["queryName"] = "Your query name cannot contain a semicolon (';')<br>since this character is used as delimiter:"; // the user's query name cannot contain a semicolon (';') since this character is used as delimiter between query names within the 'userQueries' session variable (see function 'getUserQueries()' in 'include.inc.php')

	if (($queryAction == "add") OR (($queryAction == "edit") AND ($queryName != $origQueryName))) // if the user did modify the query name, check if the new query name does already exist for this user:
	{
		$query = "SELECT query_id, query_name FROM $tableQueries WHERE user_id = $loginUserID AND query_name = '$queryName'"; // the global variable '$loginUserID' gets set in function 'start_session()' within 'include.inc.php'

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection (function 'queryMySQLDatabase()' is defined in 'include.inc.php')

		if (@ mysql_num_rows($result) > 0) // if there's already a saved query (belonging to this user) with exactly the same name
			$errors["queryName"] = "You've got already a query with that name!<br>Please choose a different name:"; // the user's query name must be unique (since the query popup of the 'Recall My Query' form on the main page uses the query's name to recall a particular query)
			// note that we could allow for duplicate query names if the query popup on the main page would work with query IDs instead. However, from an interface design perspective, duplicate query names shouldn't be allowed anyhow. So we simply don't permit them.
	}

	// Validate the 'SQL Query' field:
	if (empty($sqlQuery))
		$errors["sqlQuery"] = "You must specify a query string:"; // 'sqlQuery' must not be empty

	elseif (!eregi("^SELECT", $sqlQuery))
		$errors["sqlQuery"] = "You can only save SELECT queries:"; // currently, the user is only allowed to save SELECT queries

	// --------------------------------------------------------------------

	// Now the script has finished the validation, check if there were any errors:
	if (count($errors) > 0)
	{
		// Write back session variables:
		saveSessionVariable("errors", $errors); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		saveSessionVariable("formVars", $formVars);

		// There are errors. Relocate back to the 'Add/Edit Query' form (script 'query_manager.php'):
		header("Location: " . $_SERVER['HTTP_REFERER']);

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// If we made it here, then the data is considered valid!

	// CONSTRUCT SQL QUERY:

	// Is this an update?
	if ($queryAction == "edit")
	{
			// UPDATE - update the relevant query
			$query = "UPDATE $tableQueries SET "
					. "query_name = " . quote_smart($queryName)
					. ", display_type = " . quote_smart($displayType)
					. ", view_type = " . quote_smart($queryViewType)
					. ", query = " . quote_smart($sqlQuery)
					. ", show_query = " . quote_smart($showQuery)
					. ", show_links = " . quote_smart($showLinks)
					. ", show_rows = " . quote_smart($showRows)
					. ", cite_style_selector = " . quote_smart($citeStyle)
					. ", cite_order = " . quote_smart($citeOrder)
					. " WHERE query_id = " . quote_smart($queryID);
	}

	elseif ($queryAction == "delet")
	{
			// DELETE - delete existing query
			$query = "DELETE FROM $tableQueries WHERE query_id = " . quote_smart($queryID);
	}

	else // add the data:
	{
			// INSERT - add new query
			$query = "INSERT INTO $tableQueries SET "
					. "user_id = " . quote_smart($loginUserID) // the global variable '$loginUserID' gets set in function 'start_session()' within 'include.inc.php'
					. ", query_name = " . quote_smart($queryName)
					. ", display_type = " . quote_smart($displayType)
					. ", view_type = " . quote_smart($queryViewType)
					. ", query = " . quote_smart($sqlQuery)
					. ", show_query = " . quote_smart($showQuery)
					. ", show_links = " . quote_smart($showLinks)
					. ", show_rows = " . quote_smart($showRows)
					. ", cite_style_selector = " . quote_smart($citeStyle)
					. ", cite_order = " . quote_smart($citeOrder)
					. ", last_execution = NOW()" // set 'last_execution' field to the current date & time in 'DATETIME' format (which is 'YYYY-MM-DD HH:MM:SS', e.g.: '2003-12-31 23:45:59')
					. ", query_id = NULL"; // inserting 'NULL' into an auto_increment PRIMARY KEY attribute allocates the next available key value
	}

	// --------------------------------------------------------------------

	// (3) RUN QUERY, (4) DISPLAY HEADER & RESULTS

	// (3) RUN the query on the database through the connection:
	$result = queryMySQLDatabase($query, $oldQuery); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

	if (ereg("^(edit|delet)$", $queryAction))
	{
		$affectedRows = ($result ? mysql_affected_rows ($connection) : 0); // get the number of rows that were modified (or return 0 if an error occurred)

		if ($affectedRows == 0) // no rows were affected by the update, i.e., the query must have been deleted in the meantime!
		// NOTE: MySQL does also return 0 if nothing was changed since identical form data were submitted!
		//       So, if '$affectedRows=0', it would be better to check for the existence of the record and adopt the error message accordingly.
		{
			// return an appropriate error message:
			$HeaderString = returnMsg($loc["Warning_SavedQueryDoesNotExistAnymore"] . "!", "warning", "strong", "HeaderString"); // function 'returnMsg()' is defined in 'include.inc.php'

			// update the 'userQueries' session variable:
			getUserQueries($loginUserID); // function 'getUserQueries()' is defined in 'include.inc.php'

			header("Location: index.php"); // redirect to main page ('index.php')

			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}
	}

	elseif ($queryAction == "add") // Get the query id that was created:
		$queryID = @ mysql_insert_id($connection); // find out the unique ID number of the newly created query (Note: this function should be called immediately after the
													// SQL INSERT statement! After any subsequent query it won't be possible to retrieve the auto_increment identifier value for THIS record!)

	// update the 'userQueries' session variable:
	getUserQueries($loginUserID); // function 'getUserQueries()' is defined in 'include.inc.php'

	// Build correct header message:
	if ($queryAction == "add")
		$HeaderString = $loc["SavedQueryAdded"]; // before I18n, we did use: "The query no. " . $queryID . " has been successfully " . $queryAction . "ed."
	elseif ($queryAction == "edit")
		$HeaderString = $loc["SavedQueryEdited"];
	elseif ($queryAction == "delet")
		$HeaderString = $loc["SavedQueryDeleted"];

	$HeaderString = returnMsg($HeaderString, "", "", "HeaderString"); // function 'returnMsg()' is defined in 'include.inc.php'


	// (4) Call 'index.php' which will display the header message
	//     (routing feedback output to a different script page will avoid any reload problems effectively!)
	header("Location: index.php");

	// --------------------------------------------------------------------

	// (5) CLOSE CONNECTION
	disconnectFromMySQLDatabase($oldQuery); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------
?>
