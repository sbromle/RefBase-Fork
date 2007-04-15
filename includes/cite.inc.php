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
													"Book Chapter"       => "BookContributions",
													"Book Whole"         => "Monographs",
													"Conference Article" => "ConferenceArticles",
													"Conference Volume"  => "ConferenceVolumes",
													"Journal"            => "Journals",
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

		if (!eregi("^html$", $citeType)) // for citation formats other than HTML:
			// apply dirty hack that reverses the HTML encoding of locales (which were HTML encoded globally in 'core.inc.php');
			// note that function 'html_entity_decode' doesn't support multibyte character sets (such as UTF-8) in PHP versions < 5
			// (see <http://www.php.net/manual/en/function.html-entity-decode.php>)
			$typeTitle = html_entity_decode($typeTitle, ENT_QUOTES, $contentTypeCharset);

		return $typeTitle;
	}
?>
