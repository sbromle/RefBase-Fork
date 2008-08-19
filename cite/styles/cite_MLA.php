<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./cite/styles/cite_MLA.php
	// Repository: $HeadURL$
	// Author(s):  Richard Karnesky <mailto:karnesky@gmail.com> and
	//             Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    13-Nov-06, 15:00
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is a citation style file (which must reside within the 'cite/styles/' sub-directory of your refbase root directory). It contains a
	// version of the 'citeRecord()' function that outputs a reference list from selected records according to the citation style used by
	// the Modern Language Association (MLA)

	// based on 'cite_APA.php'

	// TODO: - newspaper & magazine articles, conference proceedings, manuals, patents, reports, software, published dissertation
	//       - use dashes for subsequent entries when citing two or more books by the same author -> see e.g. example at: <http://web.csustan.edu/english/reuben/pal/append/AXI.HTML>
	//       - don't add a dot if the abbreviated journal (or series title) ends with a dot!

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
						                                  "3", // 13.
						                                  "1", // 14.
						                                  ", et al.", // 15.
						                                  $encodeHTML); // 16.

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

				if (!empty($row['abbrev_journal']))      // abbreviated journal name
					$record .= " " . $markupPatternsArray["italic-prefix"] . $row['abbrev_journal'] . $markupPatternsArray["italic-suffix"];

				// if there's no abbreviated journal name, we'll use the full journal name
				elseif (!empty($row['publication']))      // publication (= journal) name
					$record .= " " . $markupPatternsArray["italic-prefix"] . $row['publication'] . $markupPatternsArray["italic-suffix"];

				if (!empty($row['volume']))      // volume
					{
						if (!empty($row['abbrev_journal']) || !empty($row['publication']))
							$record .= ".";

						$record .= " " . $row['volume'];
					}

				if (!empty($row['issue']))      // issue
					$record .=  "." . $row['issue'];

				if (!empty($row['year']))      // year
					{
						$record .= " (".$row['year'] . ")";
					}


				if ($row['online_publication'] == "yes") // this record refers to an online article
				{
					// instead of any pages info (which normally doesn't exist for online publications) we append
					// an optional string (given in 'online_citation') plus the current date and the DOI (or URL):

					$today = date("j M. Y");

					if (!empty($row['online_citation']))      // online_citation
					{
						if (!empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication'])) // only add ":" if either volume, issue, abbrev_journal or publication isn't empty
							$record .= ":";

						$record .= " " . $row['online_citation'];
					}

					if (!empty($row['doi']))      // doi
					{
						if (!empty($row['online_citation']) OR (empty($row['online_citation']) AND (!empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication'])))) // only add "." if online_citation isn't empty, or else if either volume, issue, abbrev_journal or publication isn't empty
							$record .= ".";

						if ($encodeHTML)
							$record .= " " . $today . encodeHTML(" <http://dx.doi.org/" . $row['doi'] . ">");
						else
							$record .= " " . $today . " <http://dx.doi.org/" . $row['doi'] . ">";
					}
					elseif (!empty($row['url']))      // url
					{
						if (!empty($row['online_citation']) OR (empty($row['online_citation']) AND (!empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication'])))) // only add "." if online_citation isn't empty, or else if either volume, issue, abbrev_journal or publication isn't empty
							$record .= ".";

						if ($encodeHTML)
							$record .= " " . $today . encodeHTML(" <" . $row['url'] . ">");
						else
							$record .= " " . $today . " <" . $row['url'] . ">";
					}

				}
				else // $row['online_publication'] == "no" -> this record refers to a printed article, so we append any pages info instead:
				{
					if (!empty($row['pages']))      // pages
					{
						if (!empty($row['year']) || !empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication'])) // only add ": " if either volume, issue, abbrev_journal or publication isn't empty
							$record .= ": ";

						$record .= formatPageInfo($row['pages'], $markupPatternsArray["endash"]); // function 'formatPageInfo()' is defined in 'cite.inc.php'
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
						                                  "3", // 13.
						                                  "1", // 14.
						                                  ", et al.", // 15.
						                                  $encodeHTML); // 16.

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
					$record .= " " . $markupPatternsArray["italic-prefix"] . $publication . $markupPatternsArray["italic-suffix"];


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
						                                  ", and ", // 5.
						                                  " *, *", // 6.
						                                  " ", // 7.
						                                  " ", // 8.
						                                  ". ", // 9.
						                                  true, // 10.
						                                  true, // 11.
						                                  false, // 12.
						                                  "3", // 13.
						                                  "1", // 14.
						                                  ", et al.", // 15.
						                                  $encodeHTML); // 16.

						if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
							$record .= ".";

						if (ereg("^[^;\r\n]+(;[^;\r\n]+)+$", $row['editor'])) // there are at least two editors (separated by ';')
							$record .= " Eds. " . $editor;
						else // there's only one editor (or the editor field is malformed with multiple editors but missing ';' separator[s])
							$record .= " Ed. " . $editor;
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

					if (preg_match("/^(Rev\.?|Revised)( ed\.?| edition)?$/i", $row['edition']))
						$row['edition'] = "Rev.";

					elseif (preg_match("/^(Abr\.?|Abridged)( ed\.?| edition)?$/i", $row['edition']))
						$row['edition'] = "Abr.";

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
						$record .= ", ";

					if (!empty($row['series_volume']))      // series volume
						$record .= $row['series_volume'];

					if (!empty($row['series_issue']))      // series issue (I'm not really sure if -- for this cite style -- the series issue should be rather omitted here)
						$record .= "." . $row['series_issue']; // is it correct to format series issues similar to journal article issues?
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
					{
						$record .= ", " . $row['year'];
					}

				if (!empty($row['pages']))      // pages
					$record .= ". " . formatPageInfo($row['pages'], $markupPatternsArray["endash"]); // function 'formatPageInfo()' is defined in 'cite.inc.php'

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
						                                  "3", // 13.
						                                  "1", // 14.
						                                  ", et al.", // 15.
						                                  $encodeHTML); // 16.

						// if the author is actually the editor of the resource we'll append ', ed' (or ', eds') to the author string:
						// [to distinguish editors from authors in the 'author' field, the 'modify.php' script does append ' (ed)' or ' (eds)' if appropriate,
						//  so we're just checking for these identifier strings here. Alternatively, we could check whether the editor field matches the author field]
						if (ereg("[ \r\n]*\(ed\)", $row['author'])) // single editor
							$author = $author . ", " . "ed";
						elseif (ereg("[ \r\n]*\(eds\)", $row['author'])) // multiple editors
							$author = $author . ", " . "eds";

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
						                                  "3", // 13.
						                                  "1", // 14.
						                                  ", et al.", // 15.
						                                  $encodeHTML); // 16.

						if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
							$record .= ".";

						if (ereg("^[^;\r\n]+(;[^;\r\n]+)+$", $row['editor'])) // there are at least two editors (separated by ';')
							$record .= " Eds. " . $editor;
						else // there's only one editor (or the editor field is malformed with multiple editors but missing ';' separator[s])
							$record .= " Ed. " . $editor;
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

					if (preg_match("/^(Rev\.?|Revised)( ed\.?| edition)?$/i", $row['edition']))
						$row['edition'] = "Rev.";

					elseif (preg_match("/^(Abr\.?|Abridged)( ed\.?| edition)?$/i", $row['edition']))
						$row['edition'] = "Abr.";

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
						$record .= ", ";

					if (!empty($row['series_volume']))      // series volume
						$record .= $row['series_volume'];

					if (!empty($row['series_issue']))      // series issue (I'm not really sure if -- for this cite style -- the series issue should be rather omitted here)
						$record .= "." . $row['series_issue']; // is it correct to format series issues similar to journal article issues?
				}

				if (!empty($row['thesis']))      // thesis (unpublished dissertation)
				{
					// TODO: a published dissertation needs to be formatted differently!
					//       see e.g. example at: <http://web.csustan.edu/english/reuben/pal/append/AXI.HTML>

					if (!ereg("[?!.][ \"" . $markupPatternsArray["italic-suffix"] . "]*$", $record))
						$record .= ".";

					// TODO: I've also seen MLA examples that separate thesis name, name of institution and year by dots. ?:-|
					//       Also, do we need to use the abbreviation "Diss." instead of "Ph.D. thesis"? What about other thesis types then?
					//       see e.g. <http://www.english.uiuc.edu/cws/wworkshop/writer_resources/citation_styles/mla/unpublished_diss.htm>
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
					$today = date("j M. Y");

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
							$record .= " " . $today . encodeHTML(" <http://dx.doi.org/" . $row['doi'] . ">");
						else
							$record .= " " . $today . " <http://dx.doi.org/" . $row['doi'] . ">";
					}
					elseif (!empty($row['url']))      // url
					{
						if (!ereg("\. *$", $record))
							$record .= ".";

						if ($encodeHTML)
							$record .= " " . $today . encodeHTML(" <" . $row['url'] . ">");
						else
							$record .= " " . $today . " <" . $row['url'] . ">";
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
