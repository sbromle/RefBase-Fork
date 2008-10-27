<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./cite/styles/cite_Harvard_3.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    01-Aug-08, 02:00
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is a citation style file (which must reside within the 'cite/styles/' sub-directory of your refbase root directory). It contains a
	// version of the 'citeRecord()' function that outputs a reference list from selected records according to the citation style used by
	// the Harvard referencing system

	// This is a variant of the Harvard author/date style, modeled after these resources:
	// <http://library.curtin.edu.au/referencing/harvard.html> (not available anymore?)
	// <http://www.usq.edu.au/library/help/ehelp/ref_guides/harvard.htm>
	// <http://www.library.uwa.edu.au/education_training_and_support/guides/harvard_citation_style>
	// <http://www.lib.unimelb.edu.au/cite/harvard_dis/>

	// based on 'cite_APA.php'

	// TODO: - patents & reports?
	//       - should we shorten ending page numbers if necessary (e.g. "pp. 10-8" or "pp. 51-5", but "pp. 19-26"), or only if numbers are >=3 digits?
	//       - where to put (and how to format) series info & editors of whole books that also have an author?

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
						                                  " " . $markupPatternsArray["ampersand"] . " ", // 5.
						                                  " *, *", // 6.
						                                  ", ", // 7.
						                                  ", ", // 8.
						                                  "", // 9.
						                                  false, // 10.
						                                  false, // 11.
						                                  true, // 12.
						                                  "", // 13.
						                                  "", // 14.
						                                  " et al", // 15.
						                                  $encodeHTML); // 16.

						$record .= $author;
					}

				if (!empty($row['year']))      // year
				{
					if (!empty($row['author']))
						$record .= " ";

					if (!empty($row['year']))
						$record .= $row['year'];
				}

				if (!empty($row['title']))      // title
				{
					if (!empty($row['author']) || !empty($row['year']))
						$record .= ", ";

					$record .= $markupPatternsArray["single-quote-left"] . $row['title'] . $markupPatternsArray["single-quote-right"];
					$record .= ",";
				}

				// From here on we'll assume that at least one of the fields 'author', 'year' or 'title' did contain some contents

				if (!empty($row['publication']))      // publication (= journal) name
					$record .= " " . $markupPatternsArray["italic-prefix"] . $row['publication'] . $markupPatternsArray["italic-suffix"];

				// if there's no full journal name, we'll use the abbreviated journal name instead:
				elseif (!empty($row['abbrev_journal']))      // abbreviated journal name
					$record .= " " . $markupPatternsArray["italic-prefix"] . $row['abbrev_journal'] . $markupPatternsArray["italic-suffix"];

				if ((!empty($row['abbrev_journal']) || !empty($row['publication'])) && (!empty($row['volume']) || !empty($row['issue'])))
					$record .= ",";

				if ($row['type'] == "Journal Article") // for journal articles, volume (or issue) information is printed with a "vol." (or "no.") prefix
				{
					if (!empty($row['volume']))      // volume
						$record .= " vol. " . $row['volume'];

					if (!empty($row['issue']))      // issue
					{
						if (!empty($row['volume']))
							$record .= ",";

						$record .= " no. " . $row['issue'];
					}
				}

				elseif (ereg("^(Newspaper Article|Magazine Article)$", $row['type'])) // for newspaper and magazine articles, volume (=month) and issue (=day) information is printed without prefix
				{
					if (!empty($row['issue']))      // issue (=day)
						$record .= " " . $row['issue'];

					if (!empty($row['volume']))      // volume (=month)
						$record .= " " . $row['volume'];
				}

				if (!empty($row['pages']))      // pages
				{
					if (!empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication'])) // only add ", " if either volume, issue, abbrev_journal or publication isn't empty
						$record .= ", ";

					$record .= formatPageInfo($row['pages'], $markupPatternsArray["endash"], "p. ", "pp. "); // function 'formatPageInfo()' is defined in 'cite.inc.php'
				}

				if ($row['online_publication'] == "yes") // this record refers to an online article
				{
					// append an optional string (given in 'online_citation') plus the current date and the DOI (or URL):

					if (!empty($row['online_citation']))      // online_citation
					{
						if (!empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication'])) // only add "," if either volume, issue, abbrev_journal or publication isn't empty
							$record .= ",";

						$record .= " " . $row['online_citation'];
					}

					if (!empty($row['doi']) || !empty($row['url']))      // doi OR url
					{
						if (!empty($row['online_citation']) OR (empty($row['online_citation']) AND (!empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication'])))) // only add "." if online_citation isn't empty, or else if either volume, issue, abbrev_journal or publication isn't empty
							$record .= ".";

						$today = date("j F Y");

						$record .= " Available from: "; // NOTE: some of the above mentioned resources use ", accessed 26 February 2004, from <http://...>." (or ", viewed on ...") instead

						if (!empty($row['doi']))      // doi
							$uri = "http://dx.doi.org/" . $row['doi'];
						else      // url
							$uri = $row['url'];

						if ($encodeHTML)
							$record .= encodeHTML($markupPatternsArray["less-than"] . $uri . $markupPatternsArray["greater-than"]);
						else
							$record .= $markupPatternsArray["less-than"] . $uri . $markupPatternsArray["greater-than"];

						$record .= " [" . $today . "]";
					}
				}

				if (!ereg("\. *$", $record)) // if the string doesn't end with a period
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
						                                  " " . $markupPatternsArray["ampersand"] . " ", // 5.
						                                  " *, *", // 6.
						                                  ", ", // 7.
						                                  ", ", // 8.
						                                  "", // 9.
						                                  false, // 10.
						                                  false, // 11.
						                                  true, // 12.
						                                  "", // 13.
						                                  "", // 14.
						                                  " et al", // 15.
						                                  $encodeHTML); // 16.

						$record .= $author;
					}

				if (!empty($row['year']))      // year
				{
					if (!empty($row['author']))
						$record .= " ";

					$record .= $row['year'];
				}

				if (!empty($row['title']))      // title
				{
					if (!empty($row['author']) || !empty($row['year']))
						$record .= ", ";

					$record .= $markupPatternsArray["single-quote-left"] . $row['title'] . $markupPatternsArray["single-quote-right"];
					$record .= ",";
				}

				// From here on we'll assume that at least one of the fields 'author', 'year' or 'title' did contain some contents
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
						                                  " " . $markupPatternsArray["ampersand"] . " ", // 5.
						                                  " *, *", // 6.
						                                  " ", // 7.
						                                  " ", // 8.
						                                  "", // 9.
						                                  true, // 10.
						                                  true, // 11.
						                                  true, // 12.
						                                  "", // 13.
						                                  "", // 14.
						                                  " et al", // 15.
						                                  $encodeHTML); // 16.

						$record .= " in " . $editor . " (";
						if (ereg("^[^;\r\n]+(;[^;\r\n]+)+$", $row['editor'])) // there are at least two editors (separated by ';')
							$record .= "eds.";
						else // there's only one editor (or the editor field is malformed with multiple editors but missing ';' separator[s])
							$record .= "ed.";
						$record .= "),";
					}

				$publication = ereg_replace("[ \r\n]*\(Eds?:[^\)\r\n]*\)", "", $row['publication']);
				if (!empty($publication))      // publication
				{
					if (empty($row['editor']))
						$record .= " in";

					$record .= " " . $markupPatternsArray["italic-prefix"] . $publication . $markupPatternsArray["italic-suffix"];
				}

				if (!empty($row['edition']) && !preg_match("/^(1|1st|first|one)( ed\.?| edition)?$/i", $row['edition']) || !empty($row['volume']))
				{
					$record .= ", ";

					if (!empty($row['edition']) && !preg_match("/^(1|1st|first|one)( ed\.?| edition)?$/i", $row['edition']))      // edition
					{
						if (preg_match("/^\d{1,3}$/", $row['edition'])) // if the edition field contains a number of up to three digits, we assume it's an edition number (such as "2nd edn")
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

						if (!empty($row['edition']) && !preg_match("/( ed\.?| edition)$/i", $row['edition']))
							$editionSuffix .= " edn";

						$record .= $row['edition'] . $editionSuffix;
					}

					if (!empty($row['volume']))      // volume
					{
						if (!empty($row['edition']) && !preg_match("/^(1|1st|first|one)( ed\.?| edition)?$/i", $row['edition']))
							$record .= ", ";

						$record .= "vol. " . $row['volume'];
					}
				}

				if (!empty($row['abbrev_series_title']) OR !empty($row['series_title'])) // if there's either a full or an abbreviated series title
				{
					$record .= ", ";

					if (!empty($row['series_title']))
						$record .= $row['series_title'];      // full series title

					// if there's no full series title, we'll use the abbreviated series title instead:
					elseif (!empty($row['abbrev_series_title']))
						$record .= $row['abbrev_series_title'];      // abbreviated series title

					if (!empty($row['series_volume'])||!empty($row['series_issue']))
						$record .= ", ";

					if (!empty($row['series_volume']))      // series volume (I'm not really sure if -- for this cite style -- the series volume & issue should be rather omitted here)
						$record .= "vol. " . $row['series_volume'];

					if (!empty($row['series_issue']))      // series issue (see note for series volume)
					{
						if (!empty($row['series_volume']))
							$record .= ", ";

						$record .= "no. " . $row['series_issue'];
					}
				}

				$record .= ",";

				if (!empty($row['publisher']))      // publisher
					$record .= " " . $row['publisher'];

				if (!empty($row['place']))      // place
				{
					if (!empty($row['publisher']))
						$record .= ",";

					$record .= " " . $row['place'];
				}

				if (!empty($row['pages']))      // pages
				{
					if (!empty($row['publisher']) || !empty($row['place']))
						$record .= ", ";

					$record .= formatPageInfo($row['pages'], $markupPatternsArray["endash"], "p. ", "pp. "); // function 'formatPageInfo()' is defined in 'cite.inc.php'
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
						                                  " " . $markupPatternsArray["ampersand"] . " ", // 5.
						                                  " *, *", // 6.
						                                  ", ", // 7.
						                                  ", ", // 8.
						                                  "", // 9.
						                                  false, // 10.
						                                  false, // 11.
						                                  true, // 12.
						                                  "", // 13.
						                                  "", // 14.
						                                  " et al", // 15.
						                                  $encodeHTML); // 16.

						// if the author is actually the editor of the resource we'll append ', ed' (or ', eds') to the author string:
						// [to distinguish editors from authors in the 'author' field, the 'modify.php' script does append ' (ed)' or ' (eds)' if appropriate,
						//  so we're just checking for these identifier strings here. Alternatively, we could check whether the editor field matches the author field]
						if (ereg("[ \r\n]*\(ed\)", $row['author'])) // single editor
							$author = $author . " (ed.)";
						elseif (ereg("[ \r\n]*\(eds\)", $row['author'])) // multiple editors
							$author = $author . " (eds.)";

						$record .= $author;
					}

				if (!empty($row['year']))      // year
				{
					if (!empty($row['author']))
						$record .= " ";

					$record .= $row['year'];
				}

				if (!empty($row['title']))      // title
				{
					if (!empty($row['author']) || !empty($row['year']))
						$record .= ", ";

					if (!empty($row['thesis'])) // for theses, the title is printed in roman type and in quotation marks
						$record .= $markupPatternsArray["single-quote-left"] . $row['title'] . $markupPatternsArray["single-quote-right"];
					else // otherwise, the title is printed in italics
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
						                                  " " . $markupPatternsArray["ampersand"] . " ", // 5.
						                                  " *, *", // 6.
						                                  " ", // 7.
						                                  " ", // 8.
						                                  "", // 9.
						                                  true, // 10.
						                                  true, // 11.
						                                  true, // 12.
						                                  "", // 13.
						                                  "", // 14.
						                                  " et al", // 15.
						                                  $encodeHTML); // 16.

						if (!empty($row['author']) || !empty($row['year']) || !empty($row['title']))
							$record .= " ";

						$record .= " (" . $editor . ", ";
						if (ereg("^[^;\r\n]+(;[^;\r\n]+)+$", $row['editor'])) // there are at least two editors (separated by ';')
							$record .= "eds.";
						else // there's only one editor (or the editor field is malformed with multiple editors but missing ';' separator[s])
							$record .= "ed.";
						$record .= ")";
					}

				if (!empty($row['edition']) || !empty($row['volume']))
				{
					if (!empty($row['author']) || !empty($row['year']) || !empty($row['title']) || (!empty($row['editor']) && !ereg("[ \r\n]*\(eds?\)", $row['author'])))
						$record .= ", ";

					if ($row['type'] == "Software")      // software edition (=version)
					{
						if (!empty($row['edition']))
						{
							$record .= "version " . $row['edition'];

							if (!empty($row['volume']) || !empty($row['issue']))
								$record .= ", ";
						}

						if (!empty($row['issue']))      // issue (=day)
							$record .= " " . $row['issue'];

						if (!empty($row['volume']))      // volume (=month)
							$record .= " " . $row['volume'];
					}
					elseif (!preg_match("/^(1|1st|first|one)( ed\.?| edition)?$/i", $row['edition']))      // regular edition (other than the first)
					{
						if (preg_match("/^\d{1,3}$/", $row['edition'])) // if the edition field contains a number of up to three digits, we assume it's an edition number (such as "2nd edn")
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

						if (!empty($row['edition']) && !preg_match("/( ed\.?| edition)$/i", $row['edition']))
							$editionSuffix .= " edn";

						$record .= $row['edition'] . $editionSuffix;

						if (!empty($row['volume']))      // volume
						{
							if (!empty($row['edition']) && !preg_match("/^(1|1st|first|one)( ed\.?| edition)?$/i", $row['edition']))
								$record .= ", ";

							$record .= "vol. " . $row['volume'];
						}
					}
				}

				if ($row['type'] == "Software") // for software, add software label
				{
					$record .= ", computer software";
				}
				else // add series info
				{
					if (!empty($row['abbrev_series_title']) OR !empty($row['series_title'])) // if there's either a full or an abbreviated series title
					{
						if ((!empty($row['title']) && !ereg("[?!.]$", $row['title'])) || (!empty($row['editor']) && !ereg("[ \r\n]*\(eds?\)", $row['author'])) || !empty($row['edition']) || !empty($row['volume']))
							$record .= ",";

						$record .= " ";

						if (!empty($row['series_title']))
							$record .= $row['series_title'];      // full series title

						// if there's no full series title, we'll use the abbreviated series title instead:
						elseif (!empty($row['abbrev_series_title']))
							$record .= $row['abbrev_series_title'];      // abbreviated series title

						if (!empty($row['series_volume'])||!empty($row['series_issue']))
							$record .= ", ";

						if (!empty($row['series_volume']))      // series volume (I'm not really sure if -- for this cite style -- the series volume & issue should be rather omitted here)
							$record .= "vol. " . $row['series_volume'];

						if (!empty($row['series_issue']))      // series issue (see note for series volume)
						{
							if (!empty($row['series_volume']))
								$record .= ", ";
	
							$record .= "no. " . $row['series_issue'];
						}
					}
				}

				if (!empty($row['thesis']))      // thesis
				{
					$record .= ", " . $row['thesis'];
					$record .= ", " . $row['publisher'];
					$record .= ", " . $row['place']; // NOTE: should we omit the place of publication for theses?
				}
				else // not a thesis
				{
					if (!empty($row['publisher']))      // publisher
						$record .= ", " . $row['publisher'];

					if (!empty($row['place']))      // place
					{
						if (!empty($row['publisher']))
							$record .= ",";

						$record .= " " . $row['place'];
					}
				}

				if ($row['online_publication'] == "yes" || $row['type'] == "Software") // this record refers to an online article, or a computer program/software
				{
					if (!empty($row['online_citation']))      // online_citation
					{
						if (!ereg("\. *$", $record))
							$record .= ".";

						$record .= " " . $row['online_citation'];
					}

					if (!empty($row['doi']) || !empty($row['url']))      // doi OR url
					{
						if (!ereg("\. *$", $record))
							$record .= ".";

						$today = date("j F Y");

						$record .= " Available from: "; // NOTE: some of the above mentioned resources use ", accessed 26 February 2004, from <http://...>." (or ", viewed on ...") instead

						if (!empty($row['doi']))      // doi
							$uri = "http://dx.doi.org/" . $row['doi'];
						else      // url
							$uri = $row['url'];

						if ($encodeHTML)
							$record .= encodeHTML($markupPatternsArray["less-than"] . $uri . $markupPatternsArray["greater-than"]);
						else
							$record .= $markupPatternsArray["less-than"] . $uri . $markupPatternsArray["greater-than"];

						$record .= " [" . $today . "]";
					}
				}

				if (!ereg("\. *$", $record)) // if the string doesn't end with a period
					$record .= ".";
			}

		// --- BEGIN POST-PROCESSING -----------------------------------------------------------------------------------------------------------

		// do some further cleanup:
		$record = trim($record); // remove any preceding or trailing whitespace


		return $record;
	}

	// --- END CITATION STYLE ---
?>
