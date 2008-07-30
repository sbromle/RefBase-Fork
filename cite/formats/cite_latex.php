<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./cite/formats/cite_latex.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    10-Jun-06, 02:32
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is a citation format file (which must reside within the 'cite/formats/' sub-directory of your refbase root directory). It contains a
	// version of the 'citeRecords()' function that outputs a reference list from selected records in LaTeX format.
	// 

	// --------------------------------------------------------------------

	// --- BEGIN CITATION FORMAT ---

	function citeRecords($result, $rowsFound, $query, $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $wrapResults, $citeStyle, $citeOrder, $citeType, $orderBy, $headerMsg, $userID, $viewType)
	{
		global $contentTypeCharset; // defined in 'ini.inc.php'

		global $client;

		// The array '$transtab_refbase_latex' contains search & replace patterns for conversion from refbase markup to LaTeX markup & entities.
		// Converts refbase fontshape markup (italic, bold) into LaTeX commands of the 'textcomp' package, super- and subscript as well as greek
		// symbols get converted into the respective commands in math mode. You may need to adopt the LaTeX markup to suit your individual needs.
		global $transtab_refbase_latex; // defined in 'transtab_refbase_latex.inc.php'

		// The arrays '$transtab_latin1_latex' and '$transtab_unicode_latex' provide translation tables for best-effort conversion of higher ASCII
		// characters from ISO-8859-1 (or Unicode, respectively) to LaTeX entities.
		global $transtab_latin1_latex; // defined in 'transtab_latin1_latex.inc.php'
		global $transtab_unicode_latex; // defined in 'transtab_unicode_latex.inc.php'

		// Initialize array variables:
		$yearsArray = array();
		$typeTitlesArray = array();

		// Define inline text markup to be used by the 'citeRecord()' function:
		$markupPatternsArray = array("bold-prefix"      => "\\textbf{",
		                             "bold-suffix"      => "}",
		                             "italic-prefix"    => "\\textit{",
		                             "italic-suffix"    => "}",
		//                           "underline-prefix" => "\\ul{", // the '\ul' command requires '\usepackage{soul}'
		//                           "underline-suffix" => "}",
		                             "endash"           => "--", // or use '{\\textendash}'
		                             "emdash"           => "---", // or use '{\\textemdash}'
		                             "ampersand"        => "&", // conversion of ampersands is done below, after the citation has been generated
		                             "double-quote"     => '"',
		                             "single-quote"     => "'", // same as for ampersands
		                             "less-than"        => "<",
		                             "greater-than"     => ">",
		                             "newline"          => "\n\n"
		                            );

		// Defines search & replace 'actions' that will be applied upon LaTeX output to all those refbase fields that are listed
		// in the corresponding 'fields' element:
		$latexSearchReplaceActionsArray = array(
		                                        array('fields'  => array("title", "publication", "abbrev_journal", "address", "keywords", "abstract", "orig_title", "series_title", "abbrev_series_title", "notes"),
		                                              'actions' => $transtab_refbase_latex
		                                             )
		                                       );

		// For CLI queries, we'll allow paging thru the result set, i.e. we honour the values of the CLI options '-S|--start' ('$rowOffset')
		// and '-R|--rows' ('$showRows') ('$rowOffset' and '$showRows' are re-assigned in function 'seekInMySQLResultsToOffset()' in 'include.inc.php')
		if (eregi("^cli", $client)) // if the query originated from a command line client such as the "refbase" CLI client ("cli-refbase-1.0")
			$showMaxRows = $showRows; // show only rows up to the value given in '$showRows'
		else
			$showMaxRows = $rowsFound; // otherwise show all rows


		// Setup the basic LaTeX document structure:
		$latexData = "%&LaTeX\n"
		           . "\\documentclass{article}\n\n";

		if ($contentTypeCharset == "UTF-8")
			$latexData .= "\\usepackage[utf8]{inputenc}\n";
		else
			$latexData .= "\\usepackage[latin1]{inputenc}\n";

		$latexData .= "\\usepackage[T1]{fontenc}\n"
		            . "\\usepackage{textcomp}\n\n";

		$latexData .= "\\begin{document}\n\n";

		// Header:
		if (!empty($headerMsg))
		{
			// Remove any colon (":") from end of header message:
			$headerMsg = trimTextPattern($headerMsg, ":", false, true); // function 'trimTextPattern()' is defined in 'include.inc.php'

			// Convert refbase markup in the header message into appropriate LaTeX markup & entities:
			$headerMsg = searchReplaceText($transtab_refbase_latex, $headerMsg, true); // function 'searchReplaceText()' is defined in 'include.inc.php'

			// Attempt to convert higher ASCII chars (i.e., characters with an ASCII value of >= 128) in the header message to their corresponding LaTeX entities:
			if ($contentTypeCharset == "UTF-8")
				$headerMsg = searchReplaceText($transtab_unicode_latex, $headerMsg, false);
			else
				$headerMsg = searchReplaceText($transtab_latin1_latex, $headerMsg, false);

			$latexData .= "\\title{" . $headerMsg . "}\n\n"
			            . "\\maketitle\n\n";
		}

		if (!eregi("type|year", $citeOrder))
			$latexData .= "\\begin{thebibliography}{" . $showMaxRows . "}\n\n";


		// LOOP OVER EACH RECORD:
		// Fetch one page of results (or less if on the last page)
		// (i.e., upto the limit specified in $showMaxRows) fetch a row into the $row array and ...
		for ($rowCounter=0; (($rowCounter < $showMaxRows) && ($row = @ mysql_fetch_array($result))); $rowCounter++)
		{
			foreach ($row as $rowFieldName => $rowFieldValue)
				// Apply search & replace 'actions' to all fields that are listed in the 'fields' element of the arrays contained in '$latexSearchReplaceActionsArray':
				foreach ($latexSearchReplaceActionsArray as $fieldActionsArray)
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
					list($yearsArray, $typeTitlesArray, $sectionHeading) = generateSectionHeading($yearsArray, $typeTitlesArray, $row, $citeOrder, "", "", "\\section*{", "}\n\n", "\\subsection*{", "}\n\n"); // function 'generateSectionHeading()' is defined in 'cite.inc.php'

					$latexData .= $sectionHeading;
				}

				// Attempt to convert higher ASCII chars (i.e., characters with an ASCII value of >= 128) to their corresponding LaTeX entities:
				if ($contentTypeCharset == "UTF-8")
					$recordEncoded = searchReplaceText($transtab_unicode_latex, $record, false); // function 'searchReplaceText()' is defined in 'include.inc.php'
				else
					$recordEncoded = searchReplaceText($transtab_latin1_latex, $record, false);

				// Write LaTeX paragraph:
				if (!eregi("type|year", $citeOrder))
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

					if (!empty($citeKey))
						// Use the custom cite key that's been build according to the user's individual export options:
						$latexData .= "\\bibitem{" . $citeKey . "} ";
					else
						// The '\bibitem' command requires a cite key, which is why we'll include the record's serial number
						// even when the user's export options specify 'export_cite_keys=no' or 'autogenerate_cite_keys=no':
						$latexData .= "\\bibitem{" . $row['serial'] . "} ";
				}

				$latexData .= $recordEncoded . "\n\n"; // create paragraph with encoded record text
			}
		}

		if (!eregi("type|year", $citeOrder))
			$latexData .= "\\end{thebibliography}\n\n";

		$latexData .= "\\end{document}\n\n";

		return $latexData;
	}

	// --- END CITATION FORMAT ---
?>
