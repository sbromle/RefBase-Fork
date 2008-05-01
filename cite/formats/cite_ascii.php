<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./cite/formats/cite_ascii.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    10-Jun-06, 02:54
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is a citation format file (which must reside within the 'cite/formats/' sub-directory of your refbase root directory). It contains a
	// version of the 'citeRecords()' function that outputs a reference list from selected records in plain text format. Plain text output is
	// mainly meant for command line interfaces such as the refbase command line client (<http://cli.refbase.net/>).

	// --------------------------------------------------------------------

	// --- BEGIN CITATION FORMAT ---

	function citeRecords($result, $rowsFound, $query, $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $wrapResults, $citeStyle, $citeOrder, $citeType, $orderBy, $headerMsg, $userID, $viewType)
	{
		global $officialDatabaseName; // these variables are defined in 'ini.inc.php'
		global $databaseBaseURL;
		global $contentTypeCharset;

		global $client;

		// The array '$transtab_refbase_ascii' contains search & replace patterns for conversion from refbase markup to plain text
		global $transtab_refbase_ascii; // defined in 'transtab_refbase_ascii.inc.php'

		$plainTextData = ""; // make sure that our buffer variable is empty

		// Header:
		if (!empty($headerMsg))
		{
			// Convert refbase markup in the header message into plain text:
			$headerMsg = searchReplaceText($transtab_refbase_ascii, $headerMsg, true); // function 'searchReplaceText()' is defined in 'include.inc.php'

			$plainTextData .= "$headerMsg\n\n"; // prefix any passed header message
		}

		// Initialize array variables:
		$yearsArray = array();
		$typeTitlesArray = array();

		// Define inline text markup to be used by the 'citeRecord()' function:
		$markupPatternsArray = array("bold-prefix"      => "", // for plain text output, we'll omit any font-shape markup
		                             "bold-suffix"      => "",
		                             "italic-prefix"    => "",
		                             "italic-suffix"    => "",
		                             "underline-prefix" => "",
		                             "underline-suffix" => "",
		                             "endash"           => "-",
		                             "emdash"           => "-",
		                             "ampersand"        => "&",
		                             "double-quote"     => '"',
		                             "single-quote"     => "'",
		                             "less-than"        => "<",
		                             "greater-than"     => ">",
		                             "newline"          => "\n"
		                            );

		// Defines search & replace 'actions' that will be applied upon TEXT output to all those refbase fields that are listed
		// in the corresponding 'fields' element:
		$plainTextSearchReplaceActionsArray = array(
		                                            array('fields'  => array("title", "address", "keywords", "abstract", "orig_title", "series_title", "abbrev_series_title", "notes", "publication"),
		                                                  'actions' => $transtab_refbase_ascii
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
				// Apply search & replace 'actions' to all fields that are listed in the 'fields' element of the arrays contained in '$plainTextSearchReplaceActionsArray':
				foreach ($plainTextSearchReplaceActionsArray as $fieldActionsArray)
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
					list($yearsArray, $typeTitlesArray, $sectionHeading) = generateSectionHeading($yearsArray, $typeTitlesArray, $row, $citeOrder, "", "", "", "\n\n", "", "\n\n"); // function 'generateSectionHeading()' is defined in 'cite.inc.php'

					$plainTextData .= $sectionHeading;
				}

				// Write plain TEXT paragraph:
				if (eregi("^cli", $client)) // when outputting results to a command line client, we'll prefix the record with it's serial number (and it's user-specific cite key, if available)
				{
					// This is a stupid hack that maps the names of the '$row' array keys to those used
					// by the '$formVars' array (which is required by function 'generateCiteKey()')
					// (eventually, the '$formVars' array should use the MySQL field names as names for its array keys)
					$formVars = buildFormVarsArray($row); // function 'buildFormVarsArray()' is defined in 'include.inc.php'

					// Generate or extract the cite key for this record:
					// NOTE: currently, the following placeholders are not available for citation output:
					//       <:keywords:>, <:issn:>, <:area:>, <:notes:>, <:userKeys:>
					//       if the cite key specification uses one of these placeholders, it will get ignored
					$citeKey = generateCiteKey($formVars); // function 'generateCiteKey()' is defined in 'include.inc.php'

					$plainTextData .= "[" . $row['serial'] . "] ";

					if (!empty($citeKey))
						// Use the custom cite key that's been build according to the user's individual export options:
						$plainTextData .= "{" . $citeKey . "} ";
				}

				$plainTextData .= $record . "\n\n"; // create paragraph with encoded record text
			}
		}

		if (eregi("^cli", $client)) // when outputting results to a command line client, we'll append some info about the number of rows displayed/found, the database name/URL and optionally display the SQL query
		{
			// Calculate the maximum result number on each page:
			if (($rowOffset + $showRows) < $rowsFound)
				$showMaxRow = ($rowOffset + $showRows); // maximum result number on each page
			else
				$showMaxRow = $rowsFound; // for the last results page, correct the maximum result number if necessary

			if ($rowsFound == 1)
				$footerInfoPart = " record found";
			else
				$footerInfoPart = " records found";

			$rowsFoundInfo = ($rowOffset + 1) . "-" . $showMaxRow . " of " . $rowsFound . $footerInfoPart; // prints e.g. "1-5 of 23 records found"

			$rowsFoundDelimiter = ereg_replace(".", "-", $rowsFoundInfo); // generate a line of hyphens which has the same length as the string in '$rowsFoundInfo' (e.g. "-----------------------")

			$plainTextData .= $rowsFoundDelimiter . "\n" . $rowsFoundInfo . "\n\n"; // append info about rows displayed/found

			$plainTextData .= $officialDatabaseName . "\n" . $databaseBaseURL . "\n\n"; // append database name and URL (comment this line if you don't like that)

			if ($showQuery == "1") // display SQL query:
				$plainTextData .= "Query: " . $query . "\n\n";
		}


		return $plainTextData;
	}

	// --- END CITATION FORMAT ---
?>
