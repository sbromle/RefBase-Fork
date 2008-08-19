<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./cite/styles/cite_Vancouver.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    04-Aug-08, 12:00
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is a citation style file (which must reside within the 'cite/styles/' sub-directory of your refbase root directory). It contains a
	// version of the 'citeRecord()' function that outputs a reference list from selected records according to the citation style used by
	// the Vancouver referencing system (also known as "Uniform Requirements for Manuscripts Submitted to Biomedical Journals")

	// This Vancouver style was modeled after these resources:
	// <http://www.library.uq.edu.au/training/citation/vancouv.pdf>
	// <http://library.curtin.edu.au/research_and_information_skills/referencing/vancouver.pdf>
	// <http://www.icmje.org/index.html> citing: <http://www.nlm.nih.gov/citingmedicine/>
	// <http://www.nlm.nih.gov/bsd/uniform_requirements.html>
	// <http://library.sun.ac.za//eng/help/infolit2002/bibvancouver.htm>

	// based on 'cite_AMA.php'

	// NOTES: - In the Vancouver style, the reference list is arranged numerically in the order in which references are cited in the text.
	//          This isn't currently handled by this style (it needs to be implemented in the 'cite/formats/cite_*.php' files).
	//        - For conference proceedings, you'll currently need to add the place & date of the conference in the proceedings title field
	//          (e.g. "Proceedings of the 5th Germ Cell Tumour Conference; 2001 Sep 13-15; Leeds, UK").

	// TODO: - abstracts, newspaper/magazine articles, patents & reports?
	//       - arrange references numerically
	//       - for newspaper articles, only the beginning page number of an article should be included (see: <http://www.ncbi.nlm.nih.gov/books/bv.fcgi?rid=citmed.section.41496#41607>)
	//       - where to put (and how to format) editors of whole books that also have an author?
	//       - see also inline comments labeled with TODO (and NOTE)

	// --------------------------------------------------------------------

	// --- BEGIN CITATION STYLE ---

	function citeRecord($row, $citeStyle, $citeType, $markupPatternsArray, $encodeHTML)
	{
		global $alnum, $alpha, $cntrl, $dash, $digit, $graph, $lower, $print, $punct, $space, $upper, $word, $patternModifiers; // defined in 'transtab_unicode_charset.inc.php' and 'transtab_latin1_charset.inc.php'

		static $uspsStateAbbreviations;

		// Official USPS state abbreviations:
		// see <http://www.usps.com/ncsc/lookups/usps_abbreviations.htm>
		$uspsStateAbbreviations = "AL|AK|AS|AZ|AR|CA|CO|CT|DE|DC|FM|FL|GA|GU|HI|ID|IL|IN|IA|KS|KY|LA|ME|MH|MD|MA|MI|MN|MS|MO|MT|"
		                        . "NE|NV|NH|NJ|NM|NY|NC|ND|MP|OH|OK|OR|PW|PA|PR|RI|SC|SD|TN|TX|UT|VT|VI|VA|WA|WV|WI|WY";

		$record = ""; // make sure that our buffer variable is empty

		// --- BEGIN TYPE = JOURNAL ARTICLE / MAGAZINE ARTICLE / NEWSPAPER ARTICLE --------------------------------------------------------------

		if (ereg("^(Journal Article|Magazine Article|Newspaper Article)$", $row['type']))
		{
			if (!empty($row['author']))      // author
			{
				// Call the 'reArrangeAuthorContents()' function (defined in 'include.inc.php') in order to re-order contents of the author field. Required Parameters:
				//   1. input:  contents of the author field
				//   2. input:  boolean value that specifies whether the author's family name comes first (within one author) in the source string
				//              ('true' means that the family name is followed by the given name (or initials), 'false' if it's the other way around)
				//
				//   3. input:  pattern describing old delimiter that separates different authors
				//   4. output: for all authors except the last author: new delimiter that separates different authors
				//   5. output: for the last author: new delimiter that separates the last author from all other authors
				//
				//   6. input:  pattern describing old delimiter that separates author name & initials (within one author)
				//   7. output: for the first author: new delimiter that separates author name & initials (within one author)
				//   8. output: for all authors except the first author: new delimiter that separates author name & initials (within one author)
				//   9. output: new delimiter that separates multiple initials (within one author)
				//  10. output: for the first author: boolean value that specifies if initials go *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
				//  11. output: for all authors except the first author: boolean value that specifies if initials go *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
				//  12. output: boolean value that specifies whether an author's full given name(s) shall be shortened to initial(s)
				//
				//  13. output: if the total number of authors is greater than the given number (integer >= 1), only the number of authors given in (14) will be included in the citation along with the string given in (15); keep empty if all authors shall be returned
				//  14. output: number of authors (integer >= 1) that is included in the citation if the total number of authors is greater than the number given in (13); keep empty if not applicable
				//  15. output: string that's appended to the number of authors given in (14) if the total number of authors is greater than the number given in (13); the actual number of authors can be printed by including '__NUMBER_OF_AUTHORS__' (without quotes) within the string
				//
				//  16. output: boolean value that specifies whether the re-ordered string shall be returned with higher ASCII chars HTML encoded
				$author = reArrangeAuthorContents($row['author'], // 1.
				                                  true, // 2.
				                                  " *; *", // 3.
				                                  ", ", // 4.
				                                  ", ", // 5.
				                                  " *, *", // 6.
				                                  " ", // 7.
				                                  " ", // 8.
				                                  "", // 9.
				                                  false, // 10.
				                                  false, // 11.
				                                  true, // 12.
				                                  "6", // 13.
				                                  "6", // 14.
				                                  ", et al.", // 15.
				                                  $encodeHTML // 16.
				                                 );

				if (!ereg("\. *$", $author))
					$record .= $author . ".";
				else
					$record .= $author;
			}

			if (!empty($row['title']))      // title
			{
				if (!empty($row['author']))
					$record .= " ";

				$record .= $row['title'];
			}

			// From here on we'll assume that at least either the 'author' or the 'title' field did contain some contents
			// if this is not the case, the output string will begin with a space. However, any preceding/trailing whitespace will be removed at the cleanup stage (see below)

			if (!ereg("[?!.] *$", $record))
				$record .= ".";

			if (!empty($row['abbrev_journal']))      // abbreviated journal name
				$record .= " " . preg_replace("/\./", "", $row['abbrev_journal']); // no punctuation marks are used in the abbreviated journal name, just spaces (TODO: smarten regex pattern)

			// if there's no abbreviated journal name, we'll use the full journal name instead:
			elseif (!empty($row['publication']))      // publication (= journal) name
				$record .= " " . $row['publication'];

			if ($row['online_publication'] == "yes") // this record refers to an online publication
				$record .= " [Internet]"; // NOTE: some of the above mentioned resources use "[serial online]", "[serial on the Internet]" or just "[online]" instead

			// NOTE: the formatting of year/volume/issue is meant for journal articles (TODO: newspaper/magazine articles)
			if (!empty($row['year']))      // year
				$record .= ". " . $row['year'];

			if ($row['online_publication'] == "yes") // append the current date if this record refers to an online publication
				$record .= " [cited " . date("Y M j") . "]";

			if (!empty($row['volume']) || !empty($row['issue']))
				$record .= ";";

			if (!empty($row['volume']))      // volume (=month)
				$record .= $row['volume'];

			if (!empty($row['issue']))      // issue (=day)
				$record .=  "(" . $row['issue'] . ")";

			if (!empty($row['pages']))      // pages
			{
				if (!empty($row['year']) || !empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication'])) // only add ": " if either year, volume, issue, abbrev_journal or publication isn't empty
					$record .= ":";

				$record .= formatPageInfo($row['pages'], $markupPatternsArray["endash"], "", "", "", "", "", "", true); // function 'formatPageInfo()' is defined in 'cite.inc.php'
			}

			if ($row['online_publication'] == "yes") // this record refers to an online publication
			{
				// append an optional string (given in 'online_citation') plus the DOI (or URL):

				if (!empty($row['online_citation']))      // online_citation
				{
					if (!empty($row['year']) || !empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication'])) // only add ":" or "," if either year, volume, issue, abbrev_journal or publication isn't empty
					{
						if (empty($row['pages']))
							$record .= ":"; // print instead of pages
						else
							$record .= ";"; // append to pages (TODO: not sure whether this is correct)
					}

					$record .= $row['online_citation'];
				}

				if (!empty($row['doi']) || !empty($row['url']))      // doi OR url
				{
					if (!empty($row['online_citation']) OR (empty($row['online_citation']) AND (!empty($row['year']) || !empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication'])))) // only add "." if online_citation isn't empty, or else if either year, volume, issue, abbrev_journal or publication isn't empty
						$record .= ".";

					$record .= " Available from: " . $markupPatternsArray["underline-prefix"]; // NOTE: some of the above mentioned resources use "Available from: URL:http://..." instead

					if (!empty($row['doi']))      // doi
						$uri = "http://dx.doi.org/" . $row['doi'];
					else      // url
						$uri = $row['url'];

					if ($encodeHTML)
						$record .= encodeHTML($uri);
					else
						$record .= $uri;

					$record .= $markupPatternsArray["underline-suffix"];
				}
			}

			if (!ereg("\. *$", $record) AND ($row['online_publication'] != "yes"))
				$record .= "."; // NOTE: the examples in the above mentioned resources differ wildly w.r.t. whether the closing period should be omitted for online publications
		}

		// --- BEGIN TYPE = ABSTRACT / BOOK CHAPTER / CONFERENCE ARTICLE ------------------------------------------------------------------------

		elseif (ereg("^(Abstract|Book Chapter|Conference Article)$", $row['type']))
		{
			if (!empty($row['author']))      // author
			{
				// Call the 'reArrangeAuthorContents()' function (defined in 'include.inc.php') in order to re-order contents of the author field. Required Parameters:
				//   1. input:  contents of the author field
				//   2. input:  boolean value that specifies whether the author's family name comes first (within one author) in the source string
				//              ('true' means that the family name is followed by the given name (or initials), 'false' if it's the other way around)
				//
				//   3. input:  pattern describing old delimiter that separates different authors
				//   4. output: for all authors except the last author: new delimiter that separates different authors
				//   5. output: for the last author: new delimiter that separates the last author from all other authors
				//
				//   6. input:  pattern describing old delimiter that separates author name & initials (within one author)
				//   7. output: for the first author: new delimiter that separates author name & initials (within one author)
				//   8. output: for all authors except the first author: new delimiter that separates author name & initials (within one author)
				//   9. output: new delimiter that separates multiple initials (within one author)
				//  10. output: for the first author: boolean value that specifies if initials go *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
				//  11. output: for all authors except the first author: boolean value that specifies if initials go *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
				//  12. output: boolean value that specifies whether an author's full given name(s) shall be shortened to initial(s)
				//
				//  13. output: if the total number of authors is greater than the given number (integer >= 1), only the number of authors given in (14) will be included in the citation along with the string given in (15); keep empty if all authors shall be returned
				//  14. output: number of authors (integer >= 1) that is included in the citation if the total number of authors is greater than the number given in (13); keep empty if not applicable
				//  15. output: string that's appended to the number of authors given in (14) if the total number of authors is greater than the number given in (13); the actual number of authors can be printed by including '__NUMBER_OF_AUTHORS__' (without quotes) within the string
				//
				//  16. output: boolean value that specifies whether the re-ordered string shall be returned with higher ASCII chars HTML encoded
				$author = reArrangeAuthorContents($row['author'], // 1.
				                                  true, // 2.
				                                  " *; *", // 3.
				                                  ", ", // 4.
				                                  ", ", // 5.
				                                  " *, *", // 6.
				                                  " ", // 7.
				                                  " ", // 8.
				                                  "", // 9.
				                                  false, // 10.
				                                  false, // 11.
				                                  true, // 12.
				                                  "6", // 13.
				                                  "6", // 14.
				                                  ", et al.", // 15.
				                                  $encodeHTML // 16.
				                                 );

				if (!ereg("\. *$", $author))
					$record .= $author . ".";
				else
					$record .= $author;
			}

			if (!empty($row['title']))      // title
			{
				if (!empty($row['author']))
					$record .= " ";

				$record .= $row['title'];
			}

			if ($row['type'] == "Abstract") // for abstracts, add "[abstract]" label
				$record .= " [abstract]";


			// From here on we'll assume that at least either the 'author' or the 'title' field did contain some contents
			// if this is not the case, the output string will begin with a space. However, any preceding/trailing whitespace will be removed at the cleanup stage (see below)

			if (!empty($row['editor']))      // editor
			{
				// Call the 'reArrangeAuthorContents()' function (defined in 'include.inc.php') in order to re-order contents of the author field. Required Parameters:
				//   1. input:  contents of the author field
				//   2. input:  boolean value that specifies whether the author's family name comes first (within one author) in the source string
				//              ('true' means that the family name is followed by the given name (or initials), 'false' if it's the other way around)
				//
				//   3. input:  pattern describing old delimiter that separates different authors
				//   4. output: for all authors except the last author: new delimiter that separates different authors
				//   5. output: for the last author: new delimiter that separates the last author from all other authors
				//
				//   6. input:  pattern describing old delimiter that separates author name & initials (within one author)
				//   7. output: for the first author: new delimiter that separates author name & initials (within one author)
				//   8. output: for all authors except the first author: new delimiter that separates author name & initials (within one author)
				//   9. output: new delimiter that separates multiple initials (within one author)
				//  10. output: for the first author: boolean value that specifies if initials go *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
				//  11. output: for all authors except the first author: boolean value that specifies if initials go *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
				//  12. output: boolean value that specifies whether an author's full given name(s) shall be shortened to initial(s)
				//
				//  13. output: if the total number of authors is greater than the given number (integer >= 1), only the number of authors given in (14) will be included in the citation along with the string given in (15); keep empty if all authors shall be returned
				//  14. output: number of authors (integer >= 1) that is included in the citation if the total number of authors is greater than the number given in (13); keep empty if not applicable
				//  15. output: string that's appended to the number of authors given in (14) if the total number of authors is greater than the number given in (13); the actual number of authors can be printed by including '__NUMBER_OF_AUTHORS__' (without quotes) within the string
				//
				//  16. output: boolean value that specifies whether the re-ordered string shall be returned with higher ASCII chars HTML encoded
				$editor = reArrangeAuthorContents($row['editor'], // 1.
				                                  true, // 2.
				                                  " *; *", // 3.
				                                  ", ", // 4.
				                                  ", ", // 5.
				                                  " *, *", // 6.
				                                  " ", // 7.
				                                  " ", // 8.
				                                  "", // 9.
				                                  false, // 10.
				                                  false, // 11.
				                                  true, // 12.
				                                  "6", // 13.
				                                  "6", // 14.
				                                  ", et al.", // 15.
				                                  $encodeHTML // 16.
				                                 );

				if (!ereg("[?!.] *$", $record))
					$record .= ".";

				$record .= " In: " . $editor . ", ";
				if (ereg("^[^;\r\n]+(;[^;\r\n]+)+$", $row['editor'])) // there are at least two editors (separated by ';')
					$record .= "editors";
				else // there's only one editor (or the editor field is malformed with multiple editors but missing ';' separator[s])
					$record .= "editor";
			}

			$publication = ereg_replace("[ \r\n]*\(Eds?:[^\)\r\n]*\)", "", $row['publication']);
			if (!empty($publication))      // publication
			{
				if (!ereg("[?!.] *$", $record))
					$record .= ".";

				if (empty($row['editor']))
					$record .= " In:";

				$record .= " " . $publication;
			}

			if (!empty($row['volume']))      // volume
			{
				if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
					$record .= ".";

				$record .= " Vol " . $row['volume']; // TODO: not sure whether this is correct
			}

			if (!empty($row['edition']) && !preg_match("/^(1|1st|first|one)( ed\.?| edition)?$/i", $row['edition']))      // edition
			{
				if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
					$record .= ".";

				if (preg_match("/^\d{1,3}$/", $row['edition'])) // if the edition field contains a number of up to three digits, we assume it's an edition number (such as "2nd ed.")
				{
					if ($row['edition'] == "2")
						$editionSuffix = "nd";
					elseif ($row['edition'] == "3")
						$editionSuffix = "rd";
					else
						$editionSuffix = "th";
				}
				else
					$editionSuffix = "";

				if (!preg_match("/( ed\.?| edition)$/i", $row['edition']))
					$editionSuffix .= " ed.";

				$record .= " " . $row['edition'] . $editionSuffix;
			}

			if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
				$record .= ".";

			if (!empty($row['place']))      // place
			{
				// for places in the USA, format any two-letter postal code for the state (i.e. ensure upper case & wrap in parens, eg. "Boca Raton (FL)"):
				if (preg_match("/(.+?)[$punct$space]+($uspsStateAbbreviations)[$punct$space]*$/i$patternModifiers", $row['place']))
					$record .= " " . preg_replace("/(.+?)[$punct$space]+($uspsStateAbbreviations)[$punct$space]*$/ie$patternModifiers", "'\\1 ('.strtoupper('\\2').')'", $row['place']);
				else
					$record .= " " . $row['place'];
			}

			if (!empty($row['publisher']))      // publisher
			{
				if (!empty($row['place']))
					$record .= ":";

				$record .= " " . $row['publisher'];
			}

			if (!empty($row['year']))      // year
				$record .= "; " . $row['year'];

			if (!empty($row['pages']))      // pages
				$record .= ". " . formatPageInfo($row['pages'], $markupPatternsArray["endash"], "p. ", "p. ", "", "", "", "", true); // function 'formatPageInfo()' is defined in 'cite.inc.php'

			if (!empty($row['abbrev_series_title']) OR !empty($row['series_title'])) // if there's either a full or an abbreviated series title
			{
				if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
					$record .= ".";

				$record .= " (";

				if (!empty($row['abbrev_series_title']))      // abbreviated series title
					$record .= preg_replace("/\./", "", $row['abbrev_series_title']); // no punctuation marks are used in the abbreviated series title, just spaces (TODO: smarten regex pattern)

				// if there's no abbreviated series title, we'll use the full series title instead:
				elseif (!empty($row['series_title']))      // full series title
					$record .= $row['series_title'];

				if (!empty($row['series_volume'])||!empty($row['series_issue']))
					$record .= "; ";

				if (!empty($row['series_volume']))      // series volume
					$record .= "vol " . $row['series_volume'];

				if (!empty($row['series_volume']) && !empty($row['series_issue']))
					$record .= "; "; // TODO: not sure whether this is correct

				if (!empty($row['series_issue']))      // series issue (I'm not really sure if -- for this cite style -- the series issue should be rather omitted here)
					$record .= "no " . $row['series_issue']; // since a series volume should be prefixed with "vol", is it correct to prefix series issues with "no"?

				$record .= ")";
			}

			if (!ereg("\. *$", $record))
				$record .= ".";
		}

		// --- BEGIN TYPE = BOOK WHOLE / CONFERENCE VOLUME / JOURNAL / MANUAL / MANUSCRIPT / MAP / MISCELLANEOUS / PATENT / REPORT / SOFTWARE ---

		else // if (ereg("Book Whole|Conference Volume|Journal|Manual|Manuscript|Map|Miscellaneous|Patent|Report|Software", $row['type']))
			// note that this also serves as a fallback: unrecognized resource types will be formatted similar to whole books
		{
			if (!empty($row['author']))      // author
			{
				$author = ereg_replace("[ \r\n]*\(eds?\)", "", $row['author']);

				// Call the 'reArrangeAuthorContents()' function (defined in 'include.inc.php') in order to re-order contents of the author field. Required Parameters:
				//   1. input:  contents of the author field
				//   2. input:  boolean value that specifies whether the author's family name comes first (within one author) in the source string
				//              ('true' means that the family name is followed by the given name (or initials), 'false' if it's the other way around)
				//
				//   3. input:  pattern describing old delimiter that separates different authors
				//   4. output: for all authors except the last author: new delimiter that separates different authors
				//   5. output: for the last author: new delimiter that separates the last author from all other authors
				//
				//   6. input:  pattern describing old delimiter that separates author name & initials (within one author)
				//   7. output: for the first author: new delimiter that separates author name & initials (within one author)
				//   8. output: for all authors except the first author: new delimiter that separates author name & initials (within one author)
				//   9. output: new delimiter that separates multiple initials (within one author)
				//  10. output: for the first author: boolean value that specifies if initials go *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
				//  11. output: for all authors except the first author: boolean value that specifies if initials go *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
				//  12. output: boolean value that specifies whether an author's full given name(s) shall be shortened to initial(s)
				//
				//  13. output: if the total number of authors is greater than the given number (integer >= 1), only the number of authors given in (14) will be included in the citation along with the string given in (15); keep empty if all authors shall be returned
				//  14. output: number of authors (integer >= 1) that is included in the citation if the total number of authors is greater than the number given in (13); keep empty if not applicable
				//  15. output: string that's appended to the number of authors given in (14) if the total number of authors is greater than the number given in (13); the actual number of authors can be printed by including '__NUMBER_OF_AUTHORS__' (without quotes) within the string
				//
				//  16. output: boolean value that specifies whether the re-ordered string shall be returned with higher ASCII chars HTML encoded
				$author = reArrangeAuthorContents($author, // 1.
				                                  true, // 2.
				                                  " *; *", // 3.
				                                  ", ", // 4.
				                                  ", ", // 5.
				                                  " *, *", // 6.
				                                  " ", // 7.
				                                  " ", // 8.
				                                  "", // 9.
				                                  false, // 10.
				                                  false, // 11.
				                                  true, // 12.
				                                  "6", // 13.
				                                  "6", // 14.
				                                  ", et al.", // 15.
				                                  $encodeHTML // 16.
				                                 );

				// if the author is actually the editor of the resource we'll append ', ed' (or ', eds') to the author string:
				// [to distinguish editors from authors in the 'author' field, the 'modify.php' script does append ' (ed)' or ' (eds)' if appropriate,
				//  so we're just checking for these identifier strings here. Alternatively, we could check whether the editor field matches the author field]
				if (ereg("[ \r\n]*\(ed\)", $row['author'])) // single editor
					$author = $author . ", editor";
				elseif (ereg("[ \r\n]*\(eds\)", $row['author'])) // multiple editors
					$author = $author . ", editors";

				if (!ereg("\. *$", $author))
					$record .= $author . ".";
				else
					$record .= $author;
			}

			if (!empty($row['title']))      // title
			{
				if (!empty($row['author']))
					$record .= " ";

					$record .= $row['title'];
			}

			if ($row['type'] == "Software") // for software, add software label
				$record .= " [computer program]";

			if (($row['online_publication'] == "yes") AND empty($row['thesis'])) // this record refers to an online publication (online theses will be handled further down below)
				$record .= " [Internet]"; // NOTE: some of the above mentioned resources use "[monograph online]", "[monograph on the Internet]" or just "[online]" instead

			if (!empty($row['volume']) AND ($row['type'] != "Software"))      // volume
			{
				if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
					$record .= ".";

				$record .= " Vol " . $row['volume']; // TODO: not sure whether this is correct
			}

			if (!empty($row['edition']))      // edition
			{
				if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
					$record .= ".";

				if ($row['type'] == "Software")      // software edition (=version)
				{
					$record .= " Version " . $row['edition'];
				}
				elseif (!preg_match("/^(1|1st|first|one)( ed\.?| edition)?$/i", $row['edition']))      // edition
				{
					if (preg_match("/^\d{1,3}$/", $row['edition'])) // if the edition field contains a number of up to three digits, we assume it's an edition number (such as "2nd ed.")
					{
						if ($row['edition'] == "2")
							$editionSuffix = "nd";
						elseif ($row['edition'] == "3")
							$editionSuffix = "rd";
						else
							$editionSuffix = "th";
					}
					else
						$editionSuffix = "";

					if (!preg_match("/( ed\.?| edition)$/i", $row['edition']))
						$editionSuffix .= " ed.";

					$record .= " " . $row['edition'] . $editionSuffix;
				}
			}

			if (!empty($row['editor']) && !ereg("[ \r\n]*\(eds?\)", $row['author']))      // editor (if different from author, see note above regarding the check for ' (ed)' or ' (eds)')
			{
				// Call the 'reArrangeAuthorContents()' function (defined in 'include.inc.php') in order to re-order contents of the author field. Required Parameters:
				//   1. input:  contents of the author field
				//   2. input:  boolean value that specifies whether the author's family name comes first (within one author) in the source string
				//              ('true' means that the family name is followed by the given name (or initials), 'false' if it's the other way around)
				//
				//   3. input:  pattern describing old delimiter that separates different authors
				//   4. output: for all authors except the last author: new delimiter that separates different authors
				//   5. output: for the last author: new delimiter that separates the last author from all other authors
				//
				//   6. input:  pattern describing old delimiter that separates author name & initials (within one author)
				//   7. output: for the first author: new delimiter that separates author name & initials (within one author)
				//   8. output: for all authors except the first author: new delimiter that separates author name & initials (within one author)
				//   9. output: new delimiter that separates multiple initials (within one author)
				//  10. output: for the first author: boolean value that specifies if initials go *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
				//  11. output: for all authors except the first author: boolean value that specifies if initials go *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
				//  12. output: boolean value that specifies whether an author's full given name(s) shall be shortened to initial(s)
				//
				//  13. output: if the total number of authors is greater than the given number (integer >= 1), only the number of authors given in (14) will be included in the citation along with the string given in (15); keep empty if all authors shall be returned
				//  14. output: number of authors (integer >= 1) that is included in the citation if the total number of authors is greater than the number given in (13); keep empty if not applicable
				//  15. output: string that's appended to the number of authors given in (14) if the total number of authors is greater than the number given in (13); the actual number of authors can be printed by including '__NUMBER_OF_AUTHORS__' (without quotes) within the string
				//
				//  16. output: boolean value that specifies whether the re-ordered string shall be returned with higher ASCII chars HTML encoded
				$editor = reArrangeAuthorContents($row['editor'], // 1.
				                                  true, // 2.
				                                  " *; *", // 3.
				                                  ", ", // 4.
				                                  ", ", // 5.
				                                  " *, *", // 6.
				                                  " ", // 7.
				                                  " ", // 8.
				                                  "", // 9.
				                                  false, // 10.
				                                  false, // 11.
				                                  true, // 12.
				                                  "6", // 13.
				                                  "6", // 14.
				                                  ", et al.", // 15.
				                                  $encodeHTML // 16.
				                                 );

				if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
					$record .= ".";

				$record .= " " . $editor;
				if (ereg("^[^;\r\n]+(;[^;\r\n]+)+$", $row['editor'])) // there are at least two editors (separated by ';')
					$record .= ", editors";
				else // there's only one editor (or the editor field is malformed with multiple editors but missing ';' separator[s])
					$record .= ", editor";
			}

			if (!empty($row['thesis']))      // thesis
			{
				// TODO: do we need to use the term "[dissertation]" instead of "[Ph.D. thesis]", etc? What about other thesis types then?
				$record .= " [" . $row['thesis'];

				if ($row['online_publication'] == "yes") // this record refers to an online thesis
					$record .= " on the Internet]";
				else
					$record .= "]";
			}

			if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
				$record .= ".";

			if (!empty($row['place']))      // place
			{
				// for places in the USA, format any two-letter postal code for the state (i.e. ensure upper case & wrap in parentheses, eg. "Boca Raton (FL)"):
				if (preg_match("/(.+?)[$punct$space]+($uspsStateAbbreviations)[$punct$space]*$/i$patternModifiers", $row['place']))
					$record .= " " . preg_replace("/(.+?)[$punct$space]+($uspsStateAbbreviations)[$punct$space]*$/ie$patternModifiers", "'\\1 ('.strtoupper('\\2').')'", $row['place']);
				else
					$record .= " " . $row['place'];
			}

			if (!empty($row['publisher']))      // publisher
			{
				if (!empty($row['place']))
					$record .= ":";

				$record .= " " . $row['publisher'];
			}

			$record .= ";";

			if (!empty($row['year']))      // year
				$record .= " " . $row['year'];

			if ($row['type'] == "Software")      // for software, volume (=month) and issue (=day) information is printed after the year (TODO: not sure whether this is correct)
			{
				if (!empty($row['volume']))      // volume (=month)
					$record .= " " . $row['volume'];

				if (!empty($row['issue']))      // issue (=day)
					$record .= " " . $row['issue'];
			}

			if ($row['online_publication'] == "yes") // append the current date if this record refers to an online publication
				$record .= " [cited " . date("Y M j") . "]";

			if (!empty($row['abbrev_series_title']) OR !empty($row['series_title'])) // if there's either a full or an abbreviated series title
			{
				if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
					$record .= ".";

				$record .= " (";

				if (!empty($row['abbrev_series_title']))      // abbreviated series title
					$record .= preg_replace("/\./", "", $row['abbrev_series_title']); // no punctuation marks are used in the abbreviated series title, just spaces (TODO: smarten regex pattern)

				// if there's no abbreviated series title, we'll use the full series title instead:
				elseif (!empty($row['series_title']))      // full series title
					$record .= $row['series_title'];

				if (!empty($row['series_volume'])||!empty($row['series_issue']))
					$record .= "; ";

				if (!empty($row['series_volume']))      // series volume
					$record .= "vol " . $row['series_volume'];

				if (!empty($row['series_volume']) && !empty($row['series_issue']))
					$record .= "; "; // TODO: not sure whether this is correct

				if (!empty($row['series_issue']))      // series issue (I'm not really sure if -- for this cite style -- the series issue should be rather omitted here)
					$record .= "no " . $row['series_issue']; // since a series volume should be prefixed with "vol", is it correct to prefix series issues with "no"?

				$record .= ")";
			}

			if ($row['online_publication'] == "yes" || $row['type'] == "Software") // this record refers to an online publication, or a computer program/software
			{
				// append an optional string (given in 'online_citation') plus the DOI (or URL):

				if (!empty($row['online_citation']))      // online_citation
				{
					if (!ereg("\. *$", $record))
						$record .= ".";

					$record .= $row['online_citation'];
				}

				if (!empty($row['doi']) || !empty($row['url']))      // doi OR url
				{
					if (!ereg("\. *$", $record))
						$record .= ".";

					$record .= " Available from: " . $markupPatternsArray["underline-prefix"]; // NOTE: some of the above mentioned resources use "Available from: URL:http://..." instead

					if (!empty($row['doi']))      // doi
						$uri = "http://dx.doi.org/" . $row['doi'];
					else      // url
						$uri = $row['url'];

					if ($encodeHTML)
						$record .= encodeHTML($uri);
					else
						$record .= $uri;

					$record .= $markupPatternsArray["underline-suffix"];
				}
			}

			if (!ereg("\. *$", $record) AND ($row['online_publication'] != "yes") AND ($row['type'] != "Software"))
				$record .= "."; // NOTE: the examples in the above mentioned resources differ wildly w.r.t. whether the closing period should be omitted for online publications
		}

		// --- BEGIN POST-PROCESSING -----------------------------------------------------------------------------------------------------------

		// do some further cleanup:
		$record = trim($record); // remove any preceding or trailing whitespace


		return $record;
	}

	// --- END CITATION STYLE ---
?>
