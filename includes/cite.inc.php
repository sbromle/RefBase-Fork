<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/cite.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    25-May-06, 15:19
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This file contains functions
	// that are used when outputting
	// references as citations.


	// Include common transliteration/translation tables and search & replace patterns
	include 'includes/transtab_refbase_rtf.inc.php'; // include refbase markup -> RTF search & replace patterns
	include 'includes/transtab_refbase_pdf.inc.php'; // include refbase markup -> PDF search & replace patterns
	include 'includes/transtab_refbase_latex.inc.php'; // include refbase markup -> LaTeX search & replace patterns
	include 'includes/transtab_refbase_markdown.inc.php'; // include refbase markup -> Markdown search & replace patterns
	include 'includes/transtab_refbase_ascii.inc.php'; // include refbase markup -> plain text search & replace patterns

	if ($contentTypeCharset == "UTF-8") // variable '$contentTypeCharset' is defined in 'ini.inc.php'
		include_once 'includes/transtab_unicode_latex.inc.php'; // include Unicode -> LaTeX translation table
	else // we assume "ISO-8859-1" by default
		include_once 'includes/transtab_latin1_latex.inc.php'; // include Latin1 -> LaTeX translation table

	// --------------------------------------------------------------------

	// Print any section heading(s):
	function generateSectionHeading($yearsArray, $typeTitlesArray, $row, $citeOrder, $headingPrefix, $headingSuffix, $sectionMarkupPrefix, $sectionMarkupSuffix, $subSectionMarkupPrefix, $subSectionMarkupSuffix)
	{
		global $loc;

		$sectionHeading = "";

		if (!empty($row['year']))
			$yearHeading = $row['year'];
		else
			$yearHeading = $loc["notSpecified"];

		// List records in blocks sorted by record type:
		if (($citeOrder == "type") OR ($citeOrder == "type-year"))
		{
			$typeTitle = generateTypeTitle($row['type'], $row['thesis']); // assign an appropriate title to this record type

			if (!in_array($typeTitle, $typeTitlesArray)) // if this record's type title hasn't occurred already
			{
				$typeTitlesArray[$row['type']] = $typeTitle; // add this title to the array of type titles
				$sectionHeading .= $headingPrefix . $sectionMarkupPrefix . $typeTitle . $sectionMarkupSuffix . $headingSuffix; // print out a the current record type
			}

			// List records in sub-blocks sorted by year:
			if ($citeOrder == "type-year")
			{
				if (!isset($yearsArray[$typeTitle]) OR !in_array($yearHeading, $yearsArray[$typeTitle])) // if this record's year hasn't occurred already for this record's type
				{
					$yearsArray[$typeTitle][] = $yearHeading; // add it to the record-specific array of years
					$sectionHeading .= $headingPrefix . $subSectionMarkupPrefix . $yearHeading . $subSectionMarkupSuffix . $headingSuffix; // print out a the current year
				}
			}
		}

		// List records in blocks sorted by year:
		elseif ($citeOrder == "year")
		{
			if (!in_array($yearHeading, $yearsArray)) // if this record's year hasn't occurred already
			{
				$yearsArray[] = $yearHeading; // add it to the array of years
				$sectionHeading .= $headingPrefix . $sectionMarkupPrefix . $yearHeading . $sectionMarkupSuffix . $headingSuffix; // print out a the current year
			}
		}

		return array($yearsArray, $typeTitlesArray, $sectionHeading);
	}

	// --------------------------------------------------------------------

	// Assign an appropriate title to a given record or thesis type:
	function generateTypeTitle($recordType, $thesis)
	{
		global $contentTypeCharset; // defined in 'ini.inc.php'

		global $citeType;
		global $loc;

		global $availableTypeTitlesArray; // these variables are made globally available from within this function
		global $availableThesisTitlesArray;

		if (empty($thesis))
		{
			if (!isset($availableTypeTitlesArray))
				// Map record types with items of the global localization array ('$loc'):
				$availableTypeTitlesArray = array(
				                                   "Journal Article"    => "JournalArticles",
				                                   "Abstract"           => "Abstracts",
				                                   "Book Chapter"       => "BookContributions",
				                                   "Book Whole"         => "Monographs",
				                                   "Conference Article" => "ConferenceArticles",
				                                   "Conference Volume"  => "ConferenceVolumes",
				                                   "Journal"            => "Journals",
				                                   "Magazine Article"   => "MagazineArticles",
				                                   "Manual"             => "Manuals",
				                                   "Manuscript"         => "Manuscripts",
				                                   "Map"                => "Maps",
				                                   "Miscellaneous"      => "Miscellaneous",
				                                   "Newspaper Article"  => "NewspaperArticles",
				                                   "Patent"             => "Patents",
				                                   "Report"             => "Reports",
				                                   "Software"           => "Software"
				                                 );

			if (isset($recordType, $availableTypeTitlesArray))
				$typeTitle = $loc[$availableTypeTitlesArray[$recordType]];
			else
				$typeTitle = $loc["OtherPublications"];
		}
		else
		{
			if (!isset($availableThesisTitlesArray))
				// Map thesis types with items of the global localization array ('$loc'):
				$availableThesisTitlesArray = array(
				                                     "Bachelor's thesis"   => "Theses_Bachelor",
				                                     "Master's thesis"     => "Theses_Master",
				                                     "Ph.D. thesis"        => "Theses_PhD",
				                                     "Diploma thesis"      => "Theses_Diploma",
				                                     "Doctoral thesis"     => "Theses_Doctoral",
				                                     "Habilitation thesis" => "Theses_Habilitation"
				                                   );

			if (isset($thesis, $availableThesisTitlesArray))
				$typeTitle = $loc[$availableThesisTitlesArray[$thesis]];
			else
				$typeTitle = $loc["Theses_Other"];
		}

		if (!preg_match("/^html$/i", $citeType)) // for citation formats other than HTML:
			// apply dirty hack that reverses the HTML encoding of locales (which were HTML encoded globally in 'core.inc.php');
			// note that function 'html_entity_decode' doesn't support multibyte character sets (such as UTF-8) in PHP versions < 5
			// (see <http://www.php.net/manual/en/function.html-entity-decode.php>)
			$typeTitle = html_entity_decode($typeTitle, ENT_QUOTES, $contentTypeCharset);

		return $typeTitle;
	}

	// --------------------------------------------------------------------

	// Format page information:
	// 
	// NOTES: - this function (and refbase in general) assumes following rules for the original formatting of page information in '$origPageInfo':
	//          - single-page items are given as a page range with identical start & end numbers (e.g. "127-127")
	//          - multi-page items are given as a page range where the end number is greater than the start number (e.g. "127-132")
	//          - for multi-page items where only the start page is known, a hyphen is appended to the start page (e.g. "127-")
	//          - total number of pages are given with a "pp" suffix (e.g. "498 pp"), see TODO
	//          - the given page info is left as is if it does not match any of the above rules (e.g. a single page number is ambiguous since it
	//            could mean a single page or the total number of pages)
	//        - the function attempts to deal with page locators that contain letters (e.g. "A1 - A3" or "4a-4c") but, ATM, locator parts (e.g. "A1")
	//          must contain at least one digit character & must not contain any whitespace
	// 
	// TODO:  - should we only use Unicode-aware regex expressions (i.e. always use '$space', '$digit' or '$word' instead of ' ', '\d' or '\w', etc)?
	//        - recognize & process total number of pages
	//        - for '$shortenPageRangeEnd=true', add support for page locators that contain letters (e.g. "A1 - A3" or "4a-4c")
	function formatPageInfo($origPageInfo, $pageRangeDelim = "-", $singlePagePrefix = "", $pageRangePrefix = "", $totalPagesPrefix = "", $singlePageSuffix = "", $pageRangeSuffix = "", $totalPagesSuffix = "", $shortenPageRangeEnd = false)
	{
		global $alnum, $alpha, $cntrl, $dash, $digit, $graph, $lower, $print, $punct, $space, $upper, $word, $patternModifiers; // defined in 'transtab_unicode_charset.inc.php' and 'transtab_latin1_charset.inc.php'

		// Check original page info for any recognized page locators, and process them appropriately:
		if (preg_match("/\w*\d+\w* *[$dash]+ *(?:\w*\d+\w*)?/$patternModifiers", $origPageInfo)) // the original page info contains a page range (like: "127-127", "127-132", "A1 - A3", "4a-4c", or "127-" if only start page given)
		{
			// Remove any whitespace around dashes or hyphens that indicate a page range:
			$origPageInfo = preg_replace("/(\w*\d+\w*) *([$dash]+) *(\w*\d+\w*)?(?=[^\w\d]|$)/$patternModifiers", "\\1\\2\\3", $origPageInfo);

			// Split original page info into its functional parts:
			// NOTE: ATM, we simply split on any whitespace characters, then process all parts with page ranges
			//       (this will also reduce runs of whitespace to a single space)
			$partsArray = preg_split("/ +/", $origPageInfo);
			$partsCount = count($partsArray);

			for ($i=0; $i < $partsCount; $i++)
			{
				// Format parts with page ranges:
				// - single-page item:
				if (preg_match("/(\w*\d+\w*)[$dash]+\\1(?=[^\w\d]|$)/$patternModifiers", $partsArray[$i])) // this part contains a page range with identical start & end numbers (like: "127-127")
					$partsArray[$i] = preg_replace("/(\w*\d+\w*)[$dash]+\\1(?=[^\w\d]|$)/$patternModifiers", $singlePagePrefix . "\\1" . $singlePageSuffix, $partsArray[$i]);

				// - multi-page item:
				elseif (preg_match("/\w*\d+\w*[$dash]+(?:\w*\d+\w*)?(?=[^\w\d]|$)/$patternModifiers", $partsArray[$i])) // this part contains a page range (like: "127-132", or "127-" if only start page given)
				{
					// In case of '$shortenPageRangeEnd=true', we abbreviate ending page numbers so that digits aren't repeated unnecessarily:
					if ($shortenPageRangeEnd AND preg_match("/\d+[$dash]+\d+/$patternModifiers", $partsArray[$i])) // ATM, only digit-only page locators (like: "127-132") are supported
					{
						// NOTE: the logic of this 'if' clause doesn't work if the original page info contains something like "173-190; 195-195" (where, for the first page range, '$endPage' would be "190;" and not "190")
						list($startPage, $endPage) = preg_split("/[$dash]+/$patternModifiers", $partsArray[$i]);

						$countStartPage = strlen($startPage);
						$countEndPage = strlen($endPage);

						if(($countStartPage == $countEndPage) AND ($startPage < $endPage))
						{
							for ($j=0; $j < $countStartPage; $j++)
							{
								if (preg_match("/^" . substr($startPage, $j, 1) . "/", $endPage)) // if the ending page number has a digit that's identical to the starting page number (at the same digit offset)
									$endPage = substr($endPage, 1); // remove the first digit from the remaining ending page number
								else
									break;
							}
						}

						$partsArray[$i] = $pageRangePrefix . $startPage . $pageRangeDelim . $endPage . $pageRangeSuffix;
					}
					else // don't abbreviate ending page numbers:
						$partsArray[$i] = preg_replace("/(\w*\d+\w*)[$dash]+(\w*\d+\w*)?(?=[^\w\d]|$)/$patternModifiers", $pageRangePrefix . "\\1" . $pageRangeDelim . "\\2" . $pageRangeSuffix, $partsArray[$i]);
				}
			}
				
			$newPageInfo = join(" ", $partsArray); // merge again all parts
		}
		else
			$newPageInfo = $origPageInfo; // page info is ambiguous, so we don't mess with it

		return $newPageInfo;
	}
?>
