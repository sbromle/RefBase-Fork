<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./cite/formats/cite_markdown.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    10-Jun-06, 02:58
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is a citation format file (which must reside within the 'cite/formats/' sub-directory of your refbase root directory). It contains a
	// version of the 'citeRecords()' function that outputs a reference list from selected records in Markdown format. Markdown is a plain text
	// formatting syntax as well as a software tool that converts the plain text formatting back to HTML (<http://daringfireball.net/projects/markdown/>)

	// --------------------------------------------------------------------

	// --- BEGIN CITATION FORMAT ---

	function citeRecords($result, $rowsFound, $query, $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $wrapResults, $citeStyle, $citeOrder, $citeType, $orderBy, $headerMsg, $userID, $viewType)
	{
		global $contentTypeCharset; // defined in 'ini.inc.php'

		global $client;

		// The array '$transtab_refbase_markdown' contains search & replace patterns for conversion from refbase markup to Markdown markup & entities
		global $transtab_refbase_markdown; // defined in 'transtab_refbase_markdown.inc.php'

		$markdownData = ""; // make sure that our buffer variable is empty

		// Header:
		if (!empty($headerMsg))
		{
			// Decode any HTML entities:
			// (these may occur in the header message e.g. if the user's preferred display language is not English but German or French, etc)
			$headerMsg = decodeHTML($contentTypeCharset, $headerMsg); // function 'decodeHTML()' is defined in 'include.inc.php', and '$contentTypeCharset' is defined in 'ini.inc.php'

			// Convert refbase markup in the header message into appropriate Markdown markup & entities:
			$headerMsg = searchReplaceText($transtab_refbase_markdown, $headerMsg, true); // function 'searchReplaceText()' is defined in 'include.inc.php'

			$markdownData .= "# $headerMsg #\n\n";
		}

		// Initialize array variables:
		$yearsArray = array();
		$typeTitlesArray = array();

		// Define inline text markup to be used by the 'citeRecord()' function:
		$markupPatternsArray = array("bold-prefix"        => "**",
		                             "bold-suffix"        => "**",
		                             "italic-prefix"      => "_",
		                             "italic-suffix"      => "_",
		                             "underline-prefix"   => "<u>",
		                             "underline-suffix"   => "</u>",
		                             "endash"             => "&ndash;",
		                             "emdash"             => "&mdash;",
		                             "ampersand"          => "&",
		                             "double-quote"       => '"',
		                             "double-quote-left"  => "&ldquo;",
		                             "double-quote-right" => "&rdquo;",
		                             "single-quote"       => "'",
		                             "single-quote-left"  => "&lsquo;",
		                             "single-quote-right" => "&rsquo;",
		                             "less-than"          => "<",
		                             "greater-than"       => ">",
		                             "newline"            => "  \n"
		                            );

		// Defines search & replace 'actions' that will be applied upon Markdown output to all those refbase fields that are listed
		// in the corresponding 'fields' element:
		$markdownSearchReplaceActionsArray = array(
		                                           array('fields'  => array("title", "publication", "abbrev_journal", "address", "keywords", "abstract", "orig_title", "series_title", "abbrev_series_title", "notes"),
		                                                 'actions' => $transtab_refbase_markdown
		                                                )
		                                          );

		// For CLI queries, we'll allow paging thru the result set, i.e. we honour the values of the CLI options '-S|--start' ('$rowOffset')
		// and '-R|--rows' ('$showRows') ('$rowOffset' and '$showRows' are re-assigned in function 'seekInMySQLResultsToOffset()' in 'include.inc.php')
		if (eregi("^cli", $client)) // if the query originated from a command line client such as the "refbase" CLI client ("cli-refbase-1.0")
			$showMaxRows = $showRows; // show only rows up to the value given in '$showRows'
		else
			$showMaxRows = $rowsFound; // otherwise show all rows


		// LOOP OVER EACH RECORD:
		// Fetch one page of results (or less if on the last page)
		// (i.e., upto the limit specified in $showMaxRows) fetch a row into the $row array and ...
		for ($rowCounter=0; (($rowCounter < $showMaxRows) && ($row = @ mysql_fetch_array($result))); $rowCounter++)
		{
			foreach ($row as $rowFieldName => $rowFieldValue)
				// Apply search & replace 'actions' to all fields that are listed in the 'fields' element of the arrays contained in '$markdownSearchReplaceActionsArray':
				foreach ($markdownSearchReplaceActionsArray as $fieldActionsArray)
					if (in_array($rowFieldName, $fieldActionsArray['fields']))
						$row[$rowFieldName] = searchReplaceText($fieldActionsArray['actions'], $row[$rowFieldName], true); // function 'searchReplaceText()' is defined in 'include.inc.php'


			// Order attributes according to the chosen output style & record type:
			$record = citeRecord($row, $citeStyle, $citeType, $markupPatternsArray, false); // function 'citeRecord()' is defined in the citation style file given in '$citeStyleFile' (which, in turn, must reside in the 'cite' directory of the refbase root directory), see function 'generateCitations()'


			// Print out the current record:
			if (!empty($record)) // unless the record buffer is empty...
			{
				// Print any section heading(s):
				if (eregi("year|type", $citeOrder))
				{
					$headingPrefix = "";
					$headingSuffix = "";

					if (!empty($headerMsg)) // if there's a custom header message available, we decrease the heading level of sections & subsections by one (since the header message has level 1)
					{
						$sectionMarkupPrefix = "## ";
						$sectionMarkupSuffix = " ##\n\n";
						$subSectionMarkupPrefix = "### ";
						$subSectionMarkupSuffix = " ###\n\n";
					}
					else // no custom header message given
					{
						$sectionMarkupPrefix = "# ";
						$sectionMarkupSuffix = " #\n\n";
						$subSectionMarkupPrefix = "## ";
						$subSectionMarkupSuffix = " ##\n\n";
					}

					list($yearsArray, $typeTitlesArray, $sectionHeading) = generateSectionHeading($yearsArray, $typeTitlesArray, $row, $citeOrder, $headingPrefix, $headingSuffix, $sectionMarkupPrefix, $sectionMarkupSuffix, $subSectionMarkupPrefix, $subSectionMarkupSuffix); // function 'generateSectionHeading()' is defined in 'cite.inc.php'

					$markdownData .= $sectionHeading;
				}

				// Write plain TEXT paragraph:
				$markdownData .= $record . "\n\n"; // create paragraph with encoded record text
			}
		}


		return $markdownData;
	}

	// --- END CITATION FORMAT ---
?>
