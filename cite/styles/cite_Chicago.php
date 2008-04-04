<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./cite/styles/cite_Chicago.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de> and
	//             Richard Karnesky <mailto:karnesky@gmail.com>
	//
	// Created:    25-Feb-08, 20:05
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is a citation style file (which must reside within the 'cite/styles/' sub-directory of your refbase root directory). It contains a
	// version of the 'citeRecord()' function that outputs a reference list from selected records according to the citation style documented
	// in the "Chicago Manual of Style" (2003), and Kate Turabian's "Manual for Writer's of Term Papers, Theses, and Dissertations" (1996)

	// based on 'cite_MLA.php'

	// TODO: - newspaper & magazine articles, conference proceedings, manuals, patents, reports, software -> see e.g. examples at <http://www.library.hbs.edu/guides/citationguide.pdf>
	//       - " and " vs. ", and "?

	// --------------------------------------------------------------------

	// --- BEGIN CITATION STYLE ---

	function citeRecord($row, $citeStyle, $citeType, $markupPatternsArray, $encodeHTML)
	{
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
				                                  ", and ", // 5.
				                                  " *, *", // 6.
				                                  ", ", // 7.
				                                  " ", // 8.
				                                  ". ", // 9.
				                                  false, // 10.
				                                  true, // 11.
				                                  false, // 12.
				                                  "10", // 13.
				                                  "10", // 14.
				                                  " et al.", // 15.
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

				$record .= '"' . $row['title'];
				if (!ereg("[?!.]$", $row['title']))
					$record .= ".";
				$record .= '"';
			}

			// From here on we'll assume that at least either the 'author' or the 'title' field did contain some contents
			// if this is not the case, the output string will begin with a space. However, any preceding/trailing whitespace will be removed at the cleanup stage (see below)

			if (!empty($row['publication']))      // publication (= journal) name
				$record .= " " . $markupPatternsArray["italic-prefix"] . $row['publication'] . $markupPatternsArray["italic-suffix"];

			// if there's no full journal name, we'll use the abbreviated journal name
			elseif (!empty($row['abbrev_journal']))      // abbreviated journal name
				$record .= " " . $markupPatternsArray["italic-prefix"] . $row['abbrev_journal'] . $markupPatternsArray["italic-suffix"];

			if (!empty($row['volume']))      // volume
				$record .= " " . $row['volume'];

			if (!empty($row['issue']))      // issue
				$record .=  ", no. " . $row['issue'];

			if (!empty($row['year']))      // year
				$record .= " (" . $row['year'] . ")";

			if (!empty($row['pages']))      // pages
			{
				if (!empty($row['year']) || !empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication'])) // only add ": " if either year, volume, issue, abbrev_journal or publication isn't empty
					$record .= ": ";

				if (preg_match("/(?<=^|[^0-9])([0-9]+) *[-–] *\\1/", $row['pages'])) // if the 'pages' field contains a page range with identical start & end numbers (like: "127-127") -> single-page item
					$record .= (ereg_replace("([0-9]+) *[-–] *[0-9]+", "\\1", $row['pages'])); // reformat as "XX"

				elseif (ereg("[0-9]+ *[-–] *[0-9]*", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132", or "127-" if only start page given) -> multi-page item
					$record .= (ereg_replace("([0-9]+) *[-–] *([0-9]*)", "\\1" . $markupPatternsArray["endash"] . "\\2", $row['pages'])); // reformat as "XX-XX" (replace hyphen with em dash)

				else
					$record .= $row['pages']; // page info is ambiguous, so we don't mess with it
			}

			if ($row['online_publication'] == "yes") // this record refers to an online article
			{
				// append an optional string (given in 'online_citation') plus the current date and the DOI (or URL):

				$today = date("F j, Y");

				if (!empty($row['online_citation']))      // online_citation
				{
					if (!empty($row['year']) || !empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication'])) // only add "," if either year, volume, issue, abbrev_journal or publication isn't empty
					{
						if (empty($row['pages']))
							$record .= ":"; // print instead of pages
						else
							$record .= ","; // append to pages
					}

					$record .= " " . $row['online_citation'];
				}

				if (!empty($row['doi']))      // doi
				{
					if (!empty($row['online_citation']) OR (empty($row['online_citation']) AND (!empty($row['year']) || !empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication'])))) // only add "." if online_citation isn't empty, or else if either year, volume, issue, abbrev_journal or publication isn't empty
						$record .= "."; // NOTE: some Chicago examples (e.g. <http://www.lib.berkeley.edu/instruct/guides/chicago-turabianstyle.pdf>) use a comma here (not sure what's correct)

					if ($encodeHTML)
						$record .= " " . encodeHTML("http://dx.doi.org/" . $row['doi']) . " (accessed " . $today . ")";
					else
						$record .= " " . "http://dx.doi.org/" . $row['doi'] . " (accessed " . $today . ")";
				}
				elseif (!empty($row['url']))      // url
				{
					if (!empty($row['online_citation']) OR (empty($row['online_citation']) AND (!empty($row['year']) || !empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication'])))) // only add "." if online_citation isn't empty, or else if either year, volume, issue, abbrev_journal or publication isn't empty
						$record .= "."; // see note for doi

					if ($encodeHTML)
						$record .= " " . encodeHTML($row['url']) . " (accessed " . $today . ")";
					else
						$record .= " " . $row['url'] . " (accessed " . $today . ")";
				}

			}

			if (!ereg("\. *$", $record))
				$record .= ".";
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
				                                  ", and ", // 5.
				                                  " *, *", // 6.
				                                  ", ", // 7.
				                                  " ", // 8.
				                                  ". ", // 9.
				                                  false, // 10.
				                                  true, // 11.
				                                  false, // 12.
				                                  "10", // 13.
				                                  "10", // 14.
				                                  " et al.", // 15.
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

				$record .= '"' . $row['title'];
				if (!ereg("[?!.]$", $row['title']))
					$record .= ".";
				$record .= '"';
			}

			$publication = ereg_replace("[ \r\n]*\(Eds?:[^\)\r\n]*\)", "", $row['publication']);
			if (!empty($publication))      // publication
				$record .= " In " . $markupPatternsArray["italic-prefix"] . $publication . $markupPatternsArray["italic-suffix"];


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
				                                  " and ", // 5.
				                                  " *, *", // 6.
				                                  " ", // 7.
				                                  " ", // 8.
				                                  ". ", // 9.
				                                  true, // 10.
				                                  true, // 11.
				                                  false, // 12.
				                                  "10", // 13.
				                                  "10", // 14.
				                                  " et al.", // 15.
				                                  $encodeHTML // 16.
				                                 );

				$record .= ", edited by " . $editor;
			}

			if (!empty($row['pages']))      // pages
			{
				$record .= ", ";

				if (preg_match("/(?<=^|[^0-9])([0-9]+) *[-–] *\\1/", $row['pages'])) // if the 'pages' field contains a page range with identical start & end numbers (like: "127-127") -> single-page item
					$record .= (ereg_replace("([0-9]+) *[-–] *[0-9]+", "\\1", $row['pages'])); // reformat as "XX"

				elseif (ereg("[0-9]+ *[-–] *[0-9]*", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132", or "127-" if only start page given) -> multi-page item
				{
					if (ereg("[0-9]+ *[-–] *[0-9]* +[^ ]+", $row['pages'])) // if the 'pages' field contains some trailing text that's separated from the page range by a space
						$record .= (ereg_replace("([0-9]+) *[-–] *([0-9]*) +([^ ]+)", "\\1" . $markupPatternsArray["endash"] . "\\2 \\3", $row['pages'])); // replace hyphen with em dash, and keep trailing text separated by a space
					else
						$record .= (ereg_replace("([0-9]+) *[-–] *([0-9]*)", "\\1" . $markupPatternsArray["endash"] . "\\2", $row['pages'])); // reformat as "XX-XX" (replace hyphen with em dash)
				}

				else
					$record .= $row['pages']; // page info is ambiguous, so we don't mess with it
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

			if (!empty($row['volume']))      // volume
			{
				if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
					$record .= ".";

				$record .= " Vol. " . $row['volume'];
			}

			if (!empty($row['abbrev_series_title']) OR !empty($row['series_title'])) // if there's either a full or an abbreviated series title
			{
				if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
					$record .= ".";

				$record .= " ";

				if (!empty($row['abbrev_series_title']))
					$record .= $row['abbrev_series_title'];      // abbreviated series title

				// if there's no abbreviated series title, we'll use the full series title instead:
				elseif (!empty($row['series_title']))
					$record .= $row['series_title'];      // full series title

				if (!empty($row['series_volume'])||!empty($row['series_issue']))
					$record .= " ";

				if (!empty($row['series_volume']))      // series volume
					$record .= $row['series_volume'];

				if (!empty($row['series_issue']))      // series issue (I'm not really sure if -- for this cite style -- the series issue should be rather omitted here)
					$record .= ", no. " . $row['series_issue']; // is it correct to format series issues similar to journal article issues?
			}

			if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
				$record .= ".";

			if (!empty($row['place']))      // place
				$record .= " " . $row['place'];

			if (!empty($row['publisher']))      // publisher
			{
				if (!empty($row['place']))
					$record .= ":";

				$record .= " " . $row['publisher'];
			}

			if (!empty($row['year']))      // year
				$record .= ", " . $row['year'];

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
				                                  ", and ", // 5.
				                                  " *, *", // 6.
				                                  ", ", // 7.
				                                  " ", // 8.
				                                  ". ", // 9.
				                                  false, // 10.
				                                  true, // 11.
				                                  false, // 12.
				                                  "10", // 13.
				                                  "10", // 14.
				                                  " et al.", // 15.
				                                  $encodeHTML // 16.
				                                 );

				// if the author is actually the editor of the resource we'll append ', ed' (or ', eds') to the author string:
				// [to distinguish editors from authors in the 'author' field, the 'modify.php' script does append ' (ed)' or ' (eds)' if appropriate,
				//  so we're just checking for these identifier strings here. Alternatively, we could check whether the editor field matches the author field]
				if (ereg("[ \r\n]*\(ed\)", $row['author'])) // single editor
					$author = $author . ", ed";
				elseif (ereg("[ \r\n]*\(eds\)", $row['author'])) // multiple editors
					$author = $author . ", eds";

				if (!ereg("\. *$", $author))
					$record .= $author . ".";
				else
					$record .= $author;
			}

			if (!empty($row['title']))      // title
			{
				if (!empty($row['author']))
					$record .= " ";

				if (!empty($row['thesis']))      // thesis
				{
					$record .= '"' . $row['title'];
					if (!ereg("[?!.]$", $row['title']))
						$record .= ".";
					$record .= '"';
				}
				else // not a thesis
					$record .= $markupPatternsArray["italic-prefix"] . $row['title'] . $markupPatternsArray["italic-suffix"];
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
				                                  ", and ", // 5.
				                                  " *, *", // 6.
				                                  " ", // 7.
				                                  " ", // 8.
				                                  ". ", // 9.
				                                  true, // 10.
				                                  true, // 11.
				                                  false, // 12.
				                                  "10", // 13.
				                                  "10", // 14.
				                                  " et al.", // 15.
				                                  $encodeHTML // 16.
				                                 );

				if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
					$record .= ".";

				$record .= " Edited by " . $editor;
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

			if (!empty($row['volume']))      // volume
			{
				if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
					$record .= ".";

				$record .= " Vol. " . $row['volume'];
			}

			if (!empty($row['abbrev_series_title']) OR !empty($row['series_title'])) // if there's either a full or an abbreviated series title
			{
				if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
					$record .= ".";

				$record .= " ";

				if (!empty($row['abbrev_series_title']))
					$record .= $row['abbrev_series_title'];      // abbreviated series title

				// if there's no abbreviated series title, we'll use the full series title instead:
				elseif (!empty($row['series_title']))
					$record .= $row['series_title'];      // full series title

				if (!empty($row['series_volume'])||!empty($row['series_issue']))
					$record .= " ";

				if (!empty($row['series_volume']))      // series volume
					$record .= $row['series_volume'];

				if (!empty($row['series_issue']))      // series issue (I'm not really sure if -- for this cite style -- the series issue should be rather omitted here)
					$record .= ", no. " . $row['series_issue']; // is it correct to format series issues similar to journal article issues?
			}

			if (!empty($row['thesis']))      // thesis
			{
				if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
					$record .= ".";

				$record .= " " . $row['thesis'];
				$record .= ", " . $row['publisher'];
			}
			else // not a thesis
			{
				if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
					$record .= ".";

				if (!empty($row['place']))      // place
					$record .= " " . $row['place'];

				if (!empty($row['publisher']))      // publisher
				{
					if (!empty($row['place']))
						$record .= ":";

					$record .= " " . $row['publisher'];
				}
			}

			if (!empty($row['year']))      // year
				$record .= ", ".$row['year'];

			if ($row['online_publication'] == "yes") // this record refers to an online article
			{
				// append an optional string (given in 'online_citation') plus the current date and the DOI (or URL):

				$today = date("F j, Y");

				if (!empty($row['online_citation']))      // online_citation
				{
					if (!ereg("\. *$", $record))
						$record .= ".";

					$record .= " " . $row['online_citation'];
				}

				if (!empty($row['doi']))      // doi
				{
					if (!ereg("\. *$", $record))
						$record .= ".";

					if ($encodeHTML)
						$record .= " " . encodeHTML("http://dx.doi.org/" . $row['doi']) . " (accessed " . $today . ")";
					else
						$record .= " " . "http://dx.doi.org/" . $row['doi'] . " (accessed " . $today . ")";
				}
				elseif (!empty($row['url']))      // url
				{
					if (!ereg("\. *$", $record))
						$record .= ".";

					if ($encodeHTML)
						$record .= " " . encodeHTML($row['url']) . " (accessed " . $today . ")";
					else
						$record .= " " . $row['url'] . " (accessed " . $today . ")";
				}

			}

			if (!ereg("\. *$", $record))
				$record .= ".";
		}

		// --- BEGIN POST-PROCESSING -----------------------------------------------------------------------------------------------------------

		// do some further cleanup:
		$record = trim($record); // remove any preceding or trailing whitespace


		return $record;
	}

	// --- END CITATION STYLE ---
?>
