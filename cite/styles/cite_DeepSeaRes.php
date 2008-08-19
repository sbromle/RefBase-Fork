<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./cite/styles/cite_DeepSeaRes.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    28-Sep-04, 23:36
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is a citation style file (which must reside within the 'cite/styles/' sub-directory of your refbase root directory). It contains a
	// version of the 'citeRecord()' function that outputs a reference list from selected records according to the citation style used by
	// the journal "Deep Sea Research".

	// --------------------------------------------------------------------

	// --- BEGIN CITATION STYLE ---

	function citeRecord($row, $citeStyle, $citeType, $markupPatternsArray, $encodeHTML)
	{
		global $alnum, $alpha, $cntrl, $dash, $digit, $graph, $lower, $print, $punct, $space, $upper, $word, $patternModifiers; // defined in 'transtab_unicode_charset.inc.php' and 'transtab_latin1_charset.inc.php'

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
						                                  ", ", // 7.
						                                  ", ", // 8.
						                                  ".", // 9.
						                                  false, // 10.
						                                  false, // 11.
						                                  true, // 12.
						                                  "", // 13.
						                                  "", // 14.
						                                  " " . $markupPatternsArray["italic-prefix"] . "and __NUMBER_OF_AUTHORS__ others" . $markupPatternsArray["italic-suffix"], // 15.
						                                  $encodeHTML); // 16.

						$record .= $author . ", ";
					}

				if (!empty($row['year']))      // year
					$record .= $row['year'] . ". ";

				if (!empty($row['title']))      // title
					{
						$record .= $row['title'];
						if (!ereg("[?!.]$", $row['title']))
							$record .= ".";
						$record .= " ";
					}

				if (!empty($row['publication']))      // publication
					$record .= $row['publication'] . " ";

				if (!empty($row['volume']))      // volume
					$record .= $row['volume'];

				if (!empty($row['issue']))      // issue
					$record .= " (" . $row['issue'] . ")";

				if ($row['online_publication'] == "yes") // this record refers to an online article
				{
					// instead of any pages info (which normally doesn't exist for online publications) we append
					// an optional string (given in 'online_citation') plus the DOI:

					if (!empty($row['online_citation']))      // online_citation
					{
						if (!empty($row['publication'])||!empty($row['volume'])||!empty($row['issue'])) // only add "," if either publication, volume or issue isn't empty
							$record .= ",";

						$record .= " " . $row['online_citation'];
					}

					if (!empty($row['doi']))      // doi
					{
						if (!empty($row['publication'])||!empty($row['volume'])||!empty($row['issue'])) // only add "," if either publication, volume or issue isn't empty
							$record .= ",";

						$record .= " doi:" . $row['doi'];
					}
				}
				else // $row['online_publication'] == "no" -> this record refers to a printed article, so we append any pages info instead:
				{
					if (!empty($row['pages']))      // pages
					{
						if (!empty($row['volume'])||!empty($row['issue'])) // only add "," if either volume or issue isn't empty
							$record .= ", ";

						$record .= formatPageInfo($row['pages'], $markupPatternsArray["endash"], "", "", "pp"); // function 'formatPageInfo()' is defined in 'cite.inc.php'
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
						                                  ", ", // 5.
						                                  " *, *", // 6.
						                                  ", ", // 7.
						                                  ", ", // 8.
						                                  ".", // 9.
						                                  false, // 10.
						                                  false, // 11.
						                                  true, // 12.
						                                  "", // 13.
						                                  "", // 14.
						                                  " " . $markupPatternsArray["italic-prefix"] . "and __NUMBER_OF_AUTHORS__ others" . $markupPatternsArray["italic-suffix"], // 15.
						                                  $encodeHTML); // 16.

						$record .= $author . ", ";
					}

				if (!empty($row['year']))      // year
					$record .= $row['year'] . ". ";

				if (!empty($row['title']))      // title
					{
						$record .= $row['title'];
						if (!ereg("[?!.]$", $row['title']))
							$record .= ".";
						$record .= " ";
					}

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
						                                  ", ", // 7.
						                                  ", ", // 8.
						                                  ".", // 9.
						                                  false, // 10.
						                                  false, // 11.
						                                  true, // 12.
						                                  "", // 13.
						                                  "", // 14.
						                                  " " . $markupPatternsArray["italic-prefix"] . "and __NUMBER_OF_AUTHORS__ others" . $markupPatternsArray["italic-suffix"], // 15.
						                                  $encodeHTML); // 16.

						$record .= "In: " . $editor;
						if (ereg("^[^;\r\n]+(;[^;\r\n]+)+$", $row['editor'])) // there are at least two editors (separated by ';')
							$record .= " (Eds.)";
						else // there's only one editor (or the editor field is malformed with multiple editors but missing ';' separator[s])
							$record .= " (Ed.)";
					}

				$publication = ereg_replace("[ \r\n]*\(Eds?:[^\)\r\n]*\)", "", $row['publication']);
				if (!empty($publication))      // publication
					$record .= ", " . $publication . ". ";
				else
					if (!empty($row['editor']))
						$record .= ". ";

				if (!empty($row['series_title'])) // if there's a series title, series information will replace the publisher & place information
					{
						$record .= $row['series_title'];      // series title

						if (!empty($row['series_volume'])||!empty($row['series_issue']))
							$record .= " ";

						if (!empty($row['series_volume']))      // series volume
							$record .= $row['series_volume'];

						if (!empty($row['series_issue']))      // series issue
							$record .= "(" . $row['series_issue'] . ")";

						if (!empty($row['pages']))
							$record .= ", ";

					}
				else // if there's NO series title available, we'll insert the publisher & place instead:
					{
						if (!empty($row['publisher']))      // publisher
							{
								$record .= $row['publisher'];
								if (!empty($row['place']))
									$record .= ", ";
								else
								{
									if (!ereg(",$", $row['publisher']))
										$record .= ",";
									$record .= " ";
								}
							}

						if (!empty($row['place']))      // place
							{
								$record .= $row['place'];
								if (!empty($row['pages']))
									{
										if (!ereg(",$", $row['place']))
											$record .= ",";
										$record .= " ";
									}
							}
					}

				if (!empty($row['pages']))      // pages
					$record .= formatPageInfo($row['pages'], $markupPatternsArray["endash"], "p. ", "pp. ", "pp"); // function 'formatPageInfo()' is defined in 'cite.inc.php'

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
						                                  ", ", // 7.
						                                  ", ", // 8.
						                                  ".", // 9.
						                                  false, // 10.
						                                  false, // 11.
						                                  true, // 12.
						                                  "", // 13.
						                                  "", // 14.
						                                  " " . $markupPatternsArray["italic-prefix"] . "and __NUMBER_OF_AUTHORS__ others" . $markupPatternsArray["italic-suffix"], // 15.
						                                  $encodeHTML); // 16.

						$record .= $author . ", ";
					}

				if (!empty($row['year']))      // year
					$record .= $row['year'] . ". ";

				if (!empty($row['title']))      // title
					{
						$record .= $row['title'];
						if (!ereg("[?!.]$", $row['title']))
							$record .= ".";
						$record .= " ";
					}

				if (!empty($row['thesis']))      // thesis
					$record .= $row['thesis'] . ". ";

				if (!empty($row['publisher']))      // publisher
					{
						$record .= $row['publisher'];
						if (!empty($row['place']))
							$record .= ", ";
						else
						{
							if (!ereg("[?!.]$", $row['publisher']))
								$record .= ". ";
							else
								$record .= " ";
						}
					}

				if (!empty($row['place']))      // place
					{
						$record .= $row['place'];
						if (!empty($row['series_title']) || !empty($row['pages']))
							{
								if (!ereg(",$", $row['place']))
									$record .= ",";
								$record .= " ";
							}
					}

				if (!empty($row['series_title']))      // series title
					{
						$record .= $row['series_title'];

						if (!empty($row['series_volume']))      // series volume (will get appended only if there's also a series title!)
						{
							$record .= " ";
							$record .= $row['series_volume'];
						}

						if (!empty($row['pages']))
							{
								if (!ereg(",$", $row['series_volume']))
									$record .= ",";
								$record .= " ";
							}
					}

				if (!empty($row['pages']))      // pages
					{
						// TODO: use function 'formatPageInfo()' when it can recognize & process total number of pages
//						$record .= formatPageInfo($row['pages'], $markupPatternsArray["endash"], "p. ", "pp. ", "pp"); // function 'formatPageInfo()' is defined in 'cite.inc.php'

						if (preg_match("/\d *[$dash] *\d/$patternModifiers", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
							// Note that we'll check for page ranges here although for whole books the 'pages' field should NOT contain a page range but the total number of pages! (like: "623 pp")
							$pagesDisplay = (preg_replace("/(\d+) *[$dash] *(\d+)/$patternModifiers", "\\1" . $markupPatternsArray["endash"] . "\\2", $row['pages']));
						else
							$pagesDisplay = $row['pages'];
						$record .= $pagesDisplay;
					}

				if (!ereg("\. *$", $record))
					$record .= ".";
			}

		// --- BEGIN POST-PROCESSING -----------------------------------------------------------------------------------------------------------

		// do some further cleanup:
		$record = ereg_replace("[ \r\n]*$", "", $record); // remove whitespace at end of line
		$record = ereg_replace("([0-9]+) *pp\.$", "\\1pp.", $record); // remove space between (book whole) page numbers & "pp"


		return $record;
	}

	// --- END CITATION STYLE ---
?>
