<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./cite/styles/cite_APA.php
	// Repository: $HeadURL$
	// Author(s):  Richard Karnesky <mailto:karnesky@gmail.com> and
	//             Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    06-Nov-06, 13:00
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is a citation style file (which must reside within the 'cite/styles/' sub-directory of your refbase root directory). It contains a
	// version of the 'citeRecord()' function that outputs a reference list from selected records according to the citation style used by
	// the APA

	// based on cite_AnnGlaciol_JGlaciol.php

	// --------------------------------------------------------------------

	// --- BEGIN CITATION STYLE ---

	function citeRecord($row, $citeStyle, $citeType, $markupPatternsArray, $encodeHTML)
	{
		$record = ""; // make sure that our buffer variable is empty

		// --- BEGIN TYPE = JOURNAL ARTICLE ----------------------------------------------------------------------------------------------------

		if ($row['type'] == "Journal Article")
			{
				if (!empty($row['author']))			// author
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
						//  13. output: if the number of authors is greater than the given number (integer >= 1), only the first author will be included along with the string given in (14); keep empty if all authors shall be returned
						//  14. output: string that's appended to the first author if number of authors is greater than the number given in (13); the actual number of authors can be printed by including '__NUMBER_OF_AUTHORS__' (without quotes) within the string
						//
						//  15. output: boolean value that specifies whether the re-ordered string shall be returned with higher ASCII chars HTML encoded
						$author = reArrangeAuthorContents($row['author'], // 1.
															true, // 2.
															" *; *", // 3.
															", ", // 4.
															", & ", // 5.
															" *, *", // 6.
															", ", // 7.
															", ", // 8.
															".", // 9.
															false, // 10.
															false, // 11.
															true, // 12.
															"-6", // 13.
															", et al.", // 14.
															$encodeHTML); // 15.

						if (!ereg("\. *$", $author))
							$record .= $author . ".";
						else
							$record .= $author;
					}

				if (!empty($row['year']))				// year
					{
						if (!empty($row['author']))
							$record .= " ";

						$record .= "(".$row['year'] . ").";
					}

				if (!empty($row['title']))			// title
					{
						if (!empty($row['author']) || !empty($row['year']))
							$record .= " ";

						$record .= $row['title'];
						if (!ereg("[?!.]$", $row['title']))
							$record .= ".";
					}

				// From here on we'll assume that at least one of the fields 'author', 'year' or 'title' did contain some contents
				// if this is not the case, the output string will begin with a space. However, any preceding/trailing whitespace will be removed at the cleanup stage (see below)

				if (!empty($row['abbrev_journal']))		// abbreviated journal name
					$record .= " " . $markupPatternsArray["italic-prefix"] . $row['abbrev_journal'] . $markupPatternsArray["italic-suffix"];

				// if there's no abbreviated journal name, we'll use the full journal name
				elseif (!empty($row['publication']))	// publication (= journal) name
					$record .= " " . $markupPatternsArray["italic-prefix"] . $row['publication'] . $markupPatternsArray["italic-suffix"];

				if (!empty($row['volume']))			// volume
					{
						if (!empty($row['abbrev_journal']) || !empty($row['publication']))
							$record .= ",";

						$record .= " " . $markupPatternsArray["italic-prefix"] . $row['volume'] . $markupPatternsArray["italic-suffix"];
					}

				if (!empty($row['issue']))			// issue
					$record .=  $markupPatternsArray["italic-prefix"] . "(" . $row['issue'] . ")" . $markupPatternsArray["italic-suffix"];

				if ($row['online_publication'] == "yes") // this record refers to an online article
				{
					// instead of any pages info (which normally doesn't exist for online publications) we append
					// an optional string (given in 'online_citation') plus the DOI:
					// (NOTE: I'm not really sure how to format an online publication for this cite style)

					$today = date("F j, Y");

					if (!empty($row['online_citation']))			// online_citation
					{
						if (!empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication']))		// only add "," if either volume, issue, abbrev_journal or publication isn't empty
							$record .= ",";

						$record .= " " . $row['online_citation'];
					}

					if (!empty($row['doi']))			// doi
					{
						if (!empty($row['online_citation']) OR (empty($row['online_citation']) AND (!empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication']))))		// only add "." if online_citation isn't empty, or else if either volume, issue, abbrev_journal or publication isn't empty
							$record .= ".";

						$record .= " Retrieved " . $today . ", from: http://dx.doi.org/" . $row['doi'];
					}
					elseif (!empty($row['url']))			// doi
					{
						if (!empty($row['online_citation']) OR (empty($row['online_citation']) AND (!empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication']))))		// only add "." if online_citation isn't empty, or else if either volume, issue, abbrev_journal or publication isn't empty
							$record .= ".";

						$record .= " Retrieved " . $today . ", from: " . $row['url'];
					}

				}
				else // $row['online_publication'] == "no" -> this record refers to a printed article, so we append any pages info instead:
				{
					if (!empty($row['pages']))			// pages
						{
							if (!empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication']))		// only add "," if either volume, issue, abbrev_journal or publication isn't empty
								$record .= ",";

							if (ereg("[0-9] *[-–] *[0-9]", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
								$record .= " " . (ereg_replace("([0-9]+) *[-–] *([0-9]+)", "\\1" . $markupPatternsArray["endash"] . "\\2", $row['pages']));
							else
								$record .= " " . $row['pages'];
						}
				}

				if (!ereg("\. *$", $record))
					$record .= ".";
			}

		// --- BEGIN TYPE = BOOK CHAPTER / CONFERENCE ARTICLE ----------------------------------------------------------------------------------

		elseif (ereg("Book Chapter|Conference Article", $row['type']))
			{
				if (!empty($row['author']))			// author
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
						//  13. output: if the number of authors is greater than the given number (integer >= 1), only the first author will be included along with the string given in (14); keep empty if all authors shall be returned
						//  14. output: string that's appended to the first author if number of authors is greater than the number given in (13); the actual number of authors can be printed by including '__NUMBER_OF_AUTHORS__' (without quotes) within the string
						//
						//  15. output: boolean value that specifies whether the re-ordered string shall be returned with higher ASCII chars HTML encoded
						$author = reArrangeAuthorContents($row['author'], // 1.
															true, // 2.
															" *; *", // 3.
															", ", // 4.
															", & ", // 5.
															" *, *", // 6.
															", ", // 7.
															", ", // 8.
															".", // 9.
															false, // 10.
															false, // 11.
															true, // 12.
															"-6", // 13.
															", et al.", // 14.
															$encodeHTML); // 15.

						if (!ereg("\. *$", $author))
							$record .= $author . ".";
						else
							$record .= $author;
					}

				if (!empty($row['year']))				// year
					{
						if (!empty($row['author']))
							$record .= " ";

						$record .= "(".$row['year'] . ").";
					}

				if (!empty($row['title']))			// title
					{
						if (!empty($row['author']) || !empty($row['year']))
							$record .= " ";

						$record .= $row['title'];
						if (!ereg("[?!.]$", $row['title']))
							$record .= ".";
					}

				// From here on we'll assume that at least one of the fields 'author', 'year' or 'title' did contain some contents
				// if this is not the case, the output string will begin with a space. However, any preceding/trailing whitespace will be removed at the cleanup stage (see below)

				if (!empty($row['editor']))			// editor
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
						//  13. output: if the number of authors is greater than the given number (integer >= 1), only the first author will be included along with the string given in (14); keep empty if all authors shall be returned
						//  14. output: string that's appended to the first author if number of authors is greater than the number given in (13); the actual number of authors can be printed by including '__NUMBER_OF_AUTHORS__' (without quotes) within the string
						//
						//  15. output: boolean value that specifies whether the re-ordered string shall be returned with higher ASCII chars HTML encoded
						$editor = reArrangeAuthorContents($row['editor'], // 1.
															true, // 2.
															" *; *", // 3.
															", ", // 4.
															", & ", // 5.
															" *, *", // 6.
															" ", // 7.
															" ", // 8.
															".", // 9.
															true, // 10.
															true, // 11.
															true, // 12.
															"-6", // 13.
															", et al.", // 14.
															$encodeHTML); // 15.

						$record .= " " . "In " . $editor . " (";
						if (ereg("^[^;\r\n]+(;[^;\r\n]+)+$", $row['editor'])) // there are at least two editors (separated by ';')
							$record .= "Eds";
						else // there's only one editor (or the editor field is malformed with multiple editors but missing ';' separator[s])
							$record .= "Ed.";
						$record .= "),";
					}

				$publication = ereg_replace("[ \r\n]*\(Eds?:[^\)\r\n]*\)", "", $row['publication']);
				if (!empty($publication))			// publication
					$record .= " " . $markupPatternsArray["italic-prefix"] . $publication . $markupPatternsArray["italic-suffix"] . ".";

				if (!empty($row['pages']))			// pages
					{
						if (!empty($publication))
							$record .= ",";

						$record .= " (";

						if (ereg("[0-9] *[-–] *[0-9]", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
							$record .= "pp. " . (ereg_replace("([0-9]+) *[-–] *([0-9]+)", "\\1" . $markupPatternsArray["endash"] . "\\2", $row['pages'])); // replace hyphen with em dash
						else
							$record .= " " . $row['pages'];
						$record .= ").";
					}

				if (!empty($row['place']))			// place
					$record .= " " . $row['place'];

				if (!empty($row['publisher']))		// publisher
					{
						if (!empty($row['place']))
							$record .= ":";

						$record .= " " . $row['publisher'];
					}

				if (!ereg("\. *$", $record))
					$record .= ".";

				if (!empty($row['abbrev_series_title']) OR !empty($row['series_title'])) // if there's either a full or an abbreviated series title
					{
						$record .= " (";

						if (!empty($row['abbrev_series_title']))
							$record .= $row['abbrev_series_title'];	// abbreviated series title

						// if there's no abbreviated series title, we'll use the full series title instead:
						elseif (!empty($row['series_title']))
							$record .= $row['series_title'];	// full series title

						if (!empty($row['series_volume'])||!empty($row['series_issue']))
							$record .= " ";

						if (!empty($row['series_volume']))	// series volume
							$record .= $row['series_volume'];

						if (!empty($row['series_issue']))	// series issue (I'm not really sure if -- for this cite style -- the series issue should be rather omitted here)
							$record .= "(" . $row['series_issue'] . ")";

						$record .= ".)";
					}
			}

		// --- BEGIN TYPE = BOOK WHOLE / CONFERENCE VOLUME / MAP / MANUSCRIPT / JOURNAL --------------------------------------------------------

		elseif (ereg("Book Whole|Conference Volume|Map|Manuscript|Journal", $row['type']))
			{
				if (!empty($row['author']))			// author
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
						//  13. output: if the number of authors is greater than the given number (integer >= 1), only the first author will be included along with the string given in (14); keep empty if all authors shall be returned
						//  14. output: string that's appended to the first author if number of authors is greater than the number given in (13); the actual number of authors can be printed by including '__NUMBER_OF_AUTHORS__' (without quotes) within the string
						//
						//  15. output: boolean value that specifies whether the re-ordered string shall be returned with higher ASCII chars HTML encoded
						$author = reArrangeAuthorContents($author, // 1.
												true, // 2.
															" *; *", // 3.
															", ", // 4.
															", & ", // 5.
															" *, *", // 6.
															", ", // 7.
															", ", // 8.
															".", // 9.
															false, // 10.
															false, // 11.
															true, // 12.
															"-6", // 13.
															", et al.", // 14.
															$encodeHTML); // 15.

						// if the author is actually the editor of the resource we'll append ', ed' (or ', eds') to the author string:
						// [to distinguish editors from authors in the 'author' field, the 'modify.php' script does append ', ed' (or ', eds') if appropriate,
						//  so we're just checking for these identifier strings here. Alternatively, we could check whether the editor field matches the author field]
						if (ereg("[ \r\n]*\(ed\)", $row['author'])) // single editor
							$author = $author . ", " . $markupPatternsArray["italic-prefix"] . "ed" . $markupPatternsArray["italic-suffix"];
						elseif (ereg("[ \r\n]*\(eds\)", $row['author'])) // multiple editors
							$author = $author . ", " . $markupPatternsArray["italic-prefix"] . "eds" . $markupPatternsArray["italic-suffix"];

						if (!ereg("\. *$", $author))
							$record .= $author . ".";
						else
							$record .= $author;
					}

				if (!empty($row['year']))				// year
					{
						if (!empty($row['author']))
							$record .= " ";

						$record .= "(".$row['year'] . ").";
					}

				if (!empty($row['title']))			// title
					{
						if (!empty($row['author']) || !empty($row['year']))
							$record .= " ";

						$record .= $markupPatternsArray["italic-prefix"] . $row['title'] . $markupPatternsArray["italic-suffix"];
						if (!ereg("[?!.]$", $row['title']))
							$record .= ".";
					}

				if (!empty($row['thesis']))			// thesis
					{
						$record .= " (" . $row['thesis'];
						$record .= ", " . $row['publisher'] . ".)";
					}
				else  // not a thesis
					{
						if (!empty($row['place']))			// place
							$record .= " " . $row['place'];

						if (!empty($row['publisher']))		// publisher
							{
								if (!empty($row['place']))
									$record .= ":";

								$record .= " " . $row['publisher'];
							}

//						if (!empty($row['pages']))			// pages
//							{
//								if (!empty($row['place']) || !empty($row['publisher']))
//									$record .= ",";
//		
//								if (ereg("[0-9] *[-–] *[0-9]", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
//									$record .= " " . (ereg_replace("([0-9]+) *[-–] *([0-9]+)", "\\1" . $markupPatternsArray["endash"] . "\\2", $row['pages'])); // replace hyphen with em dash
//								else
//									$record .= " " . $row['pages'];
//							}

						if (!ereg("\. *$", $record))
							$record .= ".";
					}

				if (!empty($row['abbrev_series_title']) OR !empty($row['series_title'])) // if there's either a full or an abbreviated series title
					{
						$record .= " (";

						if (!empty($row['abbrev_series_title']))
							$record .= $row['abbrev_series_title'];	// abbreviated series title

						// if there's no abbreviated series title, we'll use the full series title instead:
						elseif (!empty($row['series_title']))
							$record .= $row['series_title'];	// full series title

						if (!empty($row['series_volume'])||!empty($row['series_issue']))
							$record .= " ";

						if (!empty($row['series_volume']))	// series volume
							$record .= $row['series_volume'];

						if (!empty($row['series_issue']))	// series issue (I'm not really sure if -- for this cite style -- the series issue should be rather omitted here)
							$record .= "(" . $row['series_issue'] . ")";

						$record .= ".)";
					}
			}

		// --- BEGIN POST-PROCESSING -----------------------------------------------------------------------------------------------------------

		// do some further cleanup:
		$record = trim($record); // remove any preceding or trailing whitespace


		return $record;
	}

	// --- END CITATION STYLE ---
?>
