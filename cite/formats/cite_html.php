<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./cite/formats/cite_html.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    10-Jun-06, 02:30
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is a citation format file (which must reside within the 'cite/formats/' sub-directory of your refbase root directory). It contains a
	// version of the 'citeRecords()' function that outputs a reference list from selected records in HTML format.
	// 
	// TODO: use divs + CSS styling (instead of a table-based layout) for _all_ output (not only for 'viewType=Mobile')

	// --------------------------------------------------------------------

	// --- BEGIN CITATION FORMAT ---

	function citeRecords($result, $rowsFound, $query, $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $wrapResults, $citeStyle, $citeOrder, $citeType, $orderBy, $headerMsg, $userID, $viewType)
	{
		global $searchReplaceActionsArray; // defined in 'ini.inc.php'
		global $databaseBaseURL;
		global $displayResultsFooterDefault;
		global $showLinkTypesInCitationView;
		global $maximumBrowseLinks;

		global $loc; // '$loc' is made globally available in 'core.php'

		global $client;
		global $displayType;

		$htmlData = ""; // make sure that our buffer variables are empty
		$recordData = "";

		// First, initialize some variables that we'll need later on
		// Calculate the number of all visible columns (which is needed as colspan value inside some TD tags)
		if ($showLinks == "1" && eregi("^(type|type-year|year)$", $citeOrder)) // in citation layout, we simply set it to a fixed value (either '1' or '2', depending on the values of '$showLinks' and '$citeOrder')
			$NoColumns = 2; // first column: literature citation, second column: 'display details' link
		else
			$NoColumns = 1;

		// Initialize array variables:
		$yearsArray = array();
		$typeTitlesArray = array();

		// Define inline text markup to be used by the 'citeRecord()' function:
		$markupPatternsArray = array("bold-prefix"      => "<b>",
		                             "bold-suffix"      => "</b>",
		                             "italic-prefix"    => "<i>",
		                             "italic-suffix"    => "</i>",
		                             "underline-prefix" => "<u>",
		                             "underline-suffix" => "</u>",
		                             "endash"           => "&#8211;",
		                             "emdash"           => "&#8212;",
		                             "ampersand"        => "&",
		                             "double-quote"     => '"',
		                             "single-quote"     => "'",
		                             "less-than"        => "<",
		                             "greater-than"     => ">",
		                             "newline"          => "\n<br>\n"
		                            );


		// LOOP OVER EACH RECORD:
		// Fetch one page of results (or less if on the last page)
		// (i.e., upto the limit specified in $showRows) fetch a row into the $row array and ...
		for ($rowCounter=0; (($rowCounter < $showRows) && ($row = @ mysql_fetch_array($result))); $rowCounter++)
		{
			foreach ($row as $rowFieldName => $rowFieldValue)
			{
				if (!ereg($rowFieldName, "^(author|editor)$")) // we HTML encode higher ASCII chars for all but the author & editor fields. The author & editor fields are excluded here
					// since these fields must be passed *without* HTML entities to the 'reArrangeAuthorContents()' function (which will then handle the HTML encoding by itself)
					$row[$rowFieldName] = encodeHTML($row[$rowFieldName]); // HTML encode higher ASCII characters within each of the fields

				// Apply search & replace 'actions' to all fields that are listed in the 'fields' element of the arrays contained in '$searchReplaceActionsArray' (which is defined in 'ini.inc.php'):
				foreach ($searchReplaceActionsArray as $fieldActionsArray)
					if (in_array($rowFieldName, $fieldActionsArray['fields']))
						$row[$rowFieldName] = searchReplaceText($fieldActionsArray['actions'], $row[$rowFieldName], true); // function 'searchReplaceText()' is defined in 'include.inc.php'
			}


			// Order attributes according to the chosen output style & record type:
			$record = citeRecord($row, $citeStyle, $citeType, $markupPatternsArray, true); // function 'citeRecord()' is defined in the citation style file given in '$citeStyleFile' (which, in turn, must reside in the 'cite' directory of the refbase root directory), see function 'generateCitations()'


			// Print out the current record:
			if (!empty($record)) // unless the record buffer is empty...
			{
				// Print any section heading(s):
				if (eregi("year|type", $citeOrder))
				{
					if (eregi("^Mobile$", $viewType))
					{
						$headingPrefix = "\n<div class=\"sect\">";

						$headingSuffix = "</div>";
					}
					else
					{
						$headingPrefix = "\n<tr>"
						               . "\n\t<td valign=\"top\" colspan=\"$NoColumns\">";

						$headingSuffix = "</td>"
						               . "\n</tr>";
					}

					list($yearsArray, $typeTitlesArray, $sectionHeading) = generateSectionHeading($yearsArray, $typeTitlesArray, $row, $citeOrder, $headingPrefix, $headingSuffix, "<h4>", "</h4>", "<h5>", "</h5>"); // function 'generateSectionHeading()' is defined in 'cite.inc.php'

					$recordData .= $sectionHeading;
				}

				// Print out the record:
				if (is_integer($rowCounter / 2)) // if we currently are at an even number of rows
					$rowClass = "even";
				else
					$rowClass = "odd";

				if (eregi("^Mobile$", $viewType))
				{
					$recordData .= "\n<div class=\"" . $rowClass . "\">"
					                  . "\n\t<div class=\"citation\">" . $record . "</div>";
				}
				else
				{
					$recordData .= "\n<tr class=\"" . $rowClass . "\">";

					// Print a column with a checkbox:
					// Note: we omit the results footer in print/mobile view ('viewType=Print' or 'viewType=Mobile') and for include mechanisms!
					if ((!eregi("^Print$", $viewType)) AND (!eregi("^inc", $client)) AND ($wrapResults != "0") AND (!isset($displayResultsFooterDefault[$displayType]) OR (isset($displayResultsFooterDefault[$displayType]) AND ($displayResultsFooterDefault[$displayType] != "hidden"))))
					{
						$recordData .= "\n\t<td align=\"center\" valign=\"top\" width=\"10\">";

						// print a checkbox form element:
						if (!isset($displayResultsFooterDefault[$displayType]) OR (isset($displayResultsFooterDefault[$displayType]) AND ($displayResultsFooterDefault[$displayType] != "hidden")))
							$recordData .= "\n\t\t<input type=\"checkbox\" name=\"marked[]\" value=\"" . $row["serial"] . "\" title=\"select this record\">";

						if (!empty($row["orig_record"]))
						{
							if (!isset($displayResultsFooterDefault[$displayType]) OR (isset($displayResultsFooterDefault[$displayType]) AND ($displayResultsFooterDefault[$displayType] != "hidden")))
								$recordData .= "\n\t\t<br>";

							if ($row["orig_record"] < 0)
								$recordData .= "\n\t\t<img src=\"img/ok.gif\" alt=\"(original)\" title=\"original record\" width=\"14\" height=\"16\" hspace=\"0\" border=\"0\">";
							else // $row["orig_record"] > 0
								$recordData .= "\n\t\t<img src=\"img/caution.gif\" alt=\"(duplicate)\" title=\"duplicate record\" width=\"5\" height=\"16\" hspace=\"0\" border=\"0\">";
						}

						// add <abbr> block which works as a microformat that allows applications to identify objects on web pages; see <http://unapi.info/specs/> for more info
						$recordData .= "\n\t\t<div class=\"unapi\"><abbr class=\"unapi-id\" title=\"" . $databaseBaseURL . "show.php?record=" . $row["serial"] . "\"></abbr></div>";

						$recordData .= "\n\t</td>";
					}

					// Print record data as a citation:
					$recordData .= "\n\t<td class=\"citation\" valign=\"top\">" . $record . "</td>";
				}

				if ($showLinks == "1") // display links:
				{
					if (eregi("^Mobile$", $viewType))
						$recordData .= "\n\t<div class=\"links\">";
					else
						$recordData .= "\n\t<td class=\"links\" valign=\"top\" width=\"38\">";

					// Print out available links:
					// for Citation view, we'll use the '$showLinkTypesInCitationView' array that's defined in 'ini.inc.php'
					// to specify which links shall be displayed (if available and if 'showLinks == 1')
					// (for links of type DOI/URL/ISBN/XREF, only one link will be printed; order of preference: DOI, URL, ISBN, XREF)
					$recordData .= printLinks($showLinkTypesInCitationView, $row, $showQuery, $showLinks, $wrapResults, $userID, $viewType, $orderBy); // function 'printLinks()' is defined in 'search.php'

					if (eregi("^Mobile$", $viewType))
						$recordData .= "\n\t</div>";
					else
						$recordData .= "\n\t</td>";
				}

				if (eregi("^Mobile$", $viewType))
					$recordData .= "\n</div>";
				else
					$recordData .= "\n</tr>";
			}
		}


		// OUTPUT RESULTS:

		if ($wrapResults != "0")
		{
			// Build a TABLE with links for "previous" & "next" browsing, as well as links to intermediate pages
			// call the 'buildBrowseLinks()' function (defined in 'include.inc.php'):
			$BrowseLinks = buildBrowseLinks("search.php", $query, $NoColumns, $rowsFound, $showQuery, $showLinks, $showRows, $rowOffset, $previousOffset, $nextOffset, $maximumBrowseLinks, "sqlSearch", "Cite", $citeStyle, $citeOrder, $orderBy, $headerMsg, $viewType);
			$htmlData .= $BrowseLinks;
		}

		if (eregi("^Mobile$", $viewType))
		{
			// Extract the original OpenSearch/CQL query that was saved by 'opensearch.php' as a session variable:
			if (isset($_SESSION['cqlQuery']))
				$cqlQuery = $_SESSION['cqlQuery'];
			else
				$cqlQuery = "";

			// Include an OpenSearch-style (CQL) query form:
			$htmlData .= "\n<div id=\"queryform\">"
			           . "\n\t<form action=\"opensearch.php\" method=\"GET\" name=\"openSearch\">"
			           . "\n\t\t<input type=\"hidden\" name=\"formType\" value=\"openSearch\">"
			           . "\n\t\t<input type=\"hidden\" name=\"submit\" value=\"" . $loc["ButtonTitle_Search"] . "\">"
			           . "\n\t\t<input type=\"hidden\" name=\"viewType\" value=\"" . $viewType . "\">"
			           . "\n\t\t<input type=\"hidden\" name=\"startRecord\" value=\"1\">"
			           . "\n\t\t<input type=\"hidden\" name=\"maximumRecords\" value=\"" . $showRows . "\">"
			           . "\n\t\t<input type=\"hidden\" name=\"recordSchema\" value=\"html\">"
			           . "\n\t\t<input type=\"text\" name=\"query\" value=\"" . $cqlQuery . "\" size=\"25\" title=\"" . $loc["DescriptionEnterSearchString"] . "\">"
			           . "\n\t\t<input type=\"submit\" name=\"submit\" value=\"" . $loc["ButtonTitle_Search"] . "\" title=\"" . $loc["SearchDB"] . "\">"
			           . "\n\t</form>"
			           . "\n</div>";
		}
		elseif ((!eregi("^Print$", $viewType)) AND (!eregi("^inc", $client)) AND ($wrapResults != "0") AND (!isset($displayResultsFooterDefault[$displayType]) OR (isset($displayResultsFooterDefault[$displayType]) AND ($displayResultsFooterDefault[$displayType] != "hidden"))))
		{
			// Include the 'queryResults' form:
			$htmlData .= "\n<form action=\"search.php\" method=\"GET\" name=\"queryResults\">"
			           . "\n<input type=\"hidden\" name=\"formType\" value=\"queryResults\">"
			           . "\n<input type=\"hidden\" name=\"submit\" value=\"Cite\">" // provide a default value for the 'submit' form tag (then, if any form element is selected, hitting <enter> will act as if the user clicked the 'Cite' button)
			           . "\n<input type=\"hidden\" name=\"orderBy\" value=\"" . rawurlencode($orderBy) . "\">" // embed the current ORDER BY parameter so that it can be re-applied when displaying details
			           . "\n<input type=\"hidden\" name=\"showQuery\" value=\"" . $showQuery . "\">" // embed the current value of '$showQuery' so that it's available on 'display details' (batch display) & 'cite'
			           . "\n<input type=\"hidden\" name=\"showLinks\" value=\"" . $showLinks . "\">" // embed the current value of '$showLinks' so that it's available on 'display details' (batch display) & 'cite'
			           . "\n<input type=\"hidden\" name=\"showRows\" value=\"" . $showRows . "\">" // embed the current value of '$showRows' so that it's available on 'display details' (batch display) & 'cite'
			           . "\n<input type=\"hidden\" name=\"rowOffset\" value=\"" . $rowOffset . "\">" // embed the current value of '$rowOffset' so that it can be re-applied after the user pressed either of the 'Add', 'Remove', 'Remember' or 'Forget' buttons within the 'queryResults' form
			           // Note: the inclusion of '$rowOffset' here is only meant to support reloading of the same results page again after a user clicked the 'Add', 'Remove', 'Remember' or 'Forget' buttons
			           //       However, '$rowOffset' MUST NOT be set if the user clicked the 'Display' or 'Cite' button! Therefore we'll trap for this case at the top of the script.
			           . "\n<input type=\"hidden\" name=\"sqlQuery\" value=\"" . $queryURL . "\">"; // embed the current sqlQuery so that it can be re-applied after the user pressed either of the 'Add', 'Remove', 'Remember' or 'Forget' buttons within the 'queryResults' form
		}

		// Output query results:
		if (eregi("^Mobile$", $viewType))
		{
			$htmlData .= "\n<div id=\"citations\" class=\"results\">"
			           . $recordData
			           . "\n</div>";
		}
		else
		{
			$htmlData .= "\n<table id=\"citations\" class=\"results\" align=\"center\" border=\"0\" cellpadding=\"9\" cellspacing=\"0\" width=\"95%\" summary=\"This table holds the database results for your query\">"
			           . $recordData
			           . "\n</table>";
		}

		// Append the footer:
		// Note: we omit the results footer in print/mobile view ('viewType=Print' or 'viewType=Mobile') and for include mechanisms!
		if ((!eregi("^(Print|Mobile)$", $viewType)) AND (!eregi("^inc", $client)) AND ($wrapResults != "0"))
		{
			// Again, insert the (already constructed) BROWSE LINKS
			// (i.e., a TABLE with links for "previous" & "next" browsing, as well as links to intermediate pages)
			$htmlData .= $BrowseLinks;

			// Build a results footer with form elements to cite, group or export all/selected records:
			if (!isset($displayResultsFooterDefault[$displayType]) OR (isset($displayResultsFooterDefault[$displayType]) AND ($displayResultsFooterDefault[$displayType] != "hidden")))
			{
				if (isset($_SESSION['user_permissions']) AND ((isset($_SESSION['loginEmail']) AND ereg("(allow_cite|allow_user_groups|allow_export|allow_batch_export)", $_SESSION['user_permissions'])) OR (!isset($_SESSION['loginEmail']) AND ereg("allow_cite", $_SESSION['user_permissions'])))) // if the 'user_permissions' session variable does contain any of the following: 'allow_cite' -AND- if logged in, aditionally: 'allow_user_groups', 'allow_export', 'allow_batch_export'...
					// ...Insert a divider line (which separates the results data from the forms in the footer):
					$htmlData .= "\n<hr class=\"resultsfooter\" align=\"center\">";

				// Call the 'buildResultsFooter()' function (which does the actual work):
				$htmlData .= buildResultsFooter($NoColumns, $showRows, $citeStyle, $displayType);
			}
		}

		if ((!eregi("^(Print|Mobile)$", $viewType)) AND (!eregi("^inc", $client)) AND ($wrapResults != "0") AND (!isset($displayResultsFooterDefault[$displayType]) OR (isset($displayResultsFooterDefault[$displayType]) AND ($displayResultsFooterDefault[$displayType] != "hidden"))))
		{
			// Finish the form:
			$htmlData .= "\n</form>";
		}

		return $htmlData;
	}

	// --- END CITATION FORMAT ---
?>
