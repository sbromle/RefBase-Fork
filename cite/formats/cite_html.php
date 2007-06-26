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

	// --------------------------------------------------------------------

	// --- BEGIN CITATION FORMAT ---

	function citeRecords($result, $rowsFound, $query, $oldQuery, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $wrapResults, $citeStyle, $citeOrder, $citeType, $orderBy, $headerMsg, $userID, $viewType)
	{
		global $searchReplaceActionsArray; // defined in 'ini.inc.php'
		global $showLinkTypesInCitationView;

		global $client;

		$htmlData = ""; // make sure that our buffer variables are empty
		$recordTableData = "";

		// First, initialize some variables that we'll need later on
		// Calculate the number of all visible columns (which is needed as colspan value inside some TD tags)
		if ($showLinks == "1" && $citeOrder == "year") // in citation layout, we simply set it to a fixed value (either '1' or '2', depending on the values of '$showLinks' and '$citeOrder')
			$NoColumns = 2; // first column: literature citation, second column: 'display details' link
		else
			$NoColumns = 1;

		// Initialize array variables:
		$yearsArray = array();
		$typeTitlesArray = array();

		// Define inline text markup to be used by the 'citeRecord()' function:
		$markupPatternsArray = array("bold-prefix"     => "<b>",
									"bold-suffix"      => "</b>",
									"italic-prefix"    => "<i>",
									"italic-suffix"    => "</i>",
									"underline-prefix" => "<u>",
									"underline-suffix" => "</u>",
									"endash"           => "&#8211;",
									"emdash"           => "&#8212;");


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
					$headingPrefix = "\n<tr>"
									. "\n\t<td valign=\"top\" colspan=\"$NoColumns\">";

					$headingSuffix = "</td>"
									. "\n</tr>";

					list($yearsArray, $typeTitlesArray, $sectionHeading) = generateSectionHeading($yearsArray, $typeTitlesArray, $row, $citeOrder, $headingPrefix, $headingSuffix, "<h4>", "</h4>", "<h5>", "</h5>"); // function 'generateSectionHeading()' is defined in 'cite.inc.php'

					$recordTableData .= $sectionHeading;
				}

				// Print out the record:
				$recordTableData .= "\n<tr>"
									. "\n\t<td valign=\"top\">" . $record . "</td>";

				if ($showLinks == "1") // display links:
				{
					$recordTableData .= "\n\t<td valign=\"top\" width=\"38\">";

					// Print out available links:
					// for Citation view, we'll use the '$showLinkTypesInCitationView' array that's defined in 'ini.inc.php'
					// to specify which links shall be displayed (if available and if 'showLinks == 1')
					// (for links of type DOI/URL/ISBN/XREF, only one link will be printed; order of preference: DOI, URL, ISBN, XREF)
					$recordTableData .= printLinks($showLinkTypesInCitationView, $row, $showQuery, $showLinks, $wrapResults, $userID, $viewType, $orderBy); // function 'printLinks()' is defined in 'search.php'

					$recordTableData .= "\n\t</td>";
				}

				$recordTableData .= "\n</tr>";
			}
		}


		// OUTPUT RESULTS:
		// Note: currently, we omit the 'Search Within Results' form in citation layout! (compare with 'displayColumns()' function in 'search.php')

		if ($wrapResults != "0")
		{
			// Build a TABLE with links for "previous" & "next" browsing, as well as links to intermediate pages
			// call the 'buildBrowseLinks()' function (defined in 'include.inc.php'):
			$BrowseLinks = buildBrowseLinks("search.php", $query, $oldQuery, $NoColumns, $rowsFound, $showQuery, $showLinks, $showRows, $rowOffset, $previousOffset, $nextOffset, "25", "sqlSearch", "Cite", $citeStyle, $citeOrder, $orderBy, $headerMsg, $viewType);
			$htmlData .= $BrowseLinks;
		}

		// Output query results as TABLE:
		$htmlData .= "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds the database results for your query\">"
					. $recordTableData
					. "\n</table>";

		// Append the footer:
		// Note: we omit the results footer in print view ('viewType=Print') and for include mechanisms!
		if (($viewType != "Print") AND (!eregi("^inc", $client)) AND ($wrapResults != "0"))
		{
			// Again, insert the (already constructed) BROWSE LINKS
			// (i.e., a TABLE with links for "previous" & "next" browsing, as well as links to intermediate pages)
			$htmlData .= $BrowseLinks;
		}

		return $htmlData;
	}

	// --- END CITATION FORMAT ---
?>
