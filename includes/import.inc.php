<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/import.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    13-Jan-06, 21:00
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This file contains functions
	// that are used when importing
	// records into the database.
	// TODO: I18n


	// Import the ActiveLink Packages
	require_once("classes/include.php");
	import("org.active-link.xml.XML");
	import("org.active-link.xml.XMLDocument");

	include 'includes/transtab_bibtex_refbase.inc.php'; // include BibTeX markup -> refbase search & replace patterns
	include 'includes/transtab_endnotexml_refbase.inc.php'; // include Endnote XML text style markup -> refbase search & replace patterns

	if ($contentTypeCharset == "UTF-8") // variable '$contentTypeCharset' is defined in 'ini.inc.php'
		include_once 'includes/transtab_latex_unicode.inc.php'; // include LaTeX -> Unicode translation table
	else // we assume "ISO-8859-1" by default
		include_once 'includes/transtab_latex_latin1.inc.php'; // include LaTeX -> Latin1 translation table

	include_once('includes/classes/org/simplepie/simplepie.inc'); // include the SimplePie library

	// --------------------------------------------------------------------

	// ISI TO CSA
	// This function converts records from "ISI Web of Science" format to "CSA" format
	// in order to enable import of ISI WoS records via the 'csaToRefbase()' function.
	// ISI WoS records must contain at least the tags "PT" and "SO" and end with "\nER\n".
	// 
	// Authors: this function was originally written by Joachim Almergren <joachim.almergren@umb.no>
	//          and was re-written by Matthias Steffens <mailto:refbase@extracts.de> to enable batch import
	function isiToCsa($isiSourceData)
	{
		global $alnum, $alpha, $cntrl, $digit, $graph, $lower, $print, $punct, $space, $upper, $word, $patternModifiers; // defined in 'transtab_unicode_charset.inc.php' and 'transtab_latin1_charset.inc.php'

		// Function preferences:
		$extractAllAddresses = false; // if set to 'true', all addresses will be extracted from the ISI "C1" field;
									// set to 'false' if you only want to extract the first address given in the ISI "C1" field

		$extractEmail = true; // if set to 'true', the first email address will be extracted from the ISI "EM" field and appended to the first address in "AF: Affiliation";
							 // set to 'false' if you don't want to extract the email address

		// Generate an array which lists all the CSA tags that are recognized by the 'csaToRefbase()' function
		// and match them with their corresponding ISI tags ("CSA tag" => "ISI tag"):
		$isiToCsaTagsArray = array(
									"PT: Publication Type"     => "PT",
									"AU: Author"               => "AU",
									"TI: Title"                => "TI",
									"SO: Source"               => "SO",
									"PY: Publication Year"     => "PY",
		//							"JN: Journal Name"         => "", // the 'csaToRefbase()' function will generate the full journal name from "SO: Source"
									"JA: Abbrev Journal Name"  => "JI",
		//							"MT: Monograph Title"      => "", // the ISI WoS database does only contain journal article (AFAIK)
									"JV: Journal Volume"       => "VL",
									"JI: Journal Issue"        => "IS",
		//							"JP: Journal Pages"        => "", // ISI WoS contains separate tags for start page ("BP") and end page ("EP"), we'll compute a "JP: Journal Pages" from these fields below
									"AF: Affiliation"          => "C1",
		//							"CA: Corporate Author"     => "",
									"DE: Descriptors"          => "DE",
									"AB: Abstract"             => "AB",
									"PB: Publisher"            => "PU",
		//							""                         => "PI", // AFAIK, CSA offers no field for the place of publisher (though it would be nice to import this info as well)
		//							"ED: Editor"               => "",
									"LA: Language"             => "LA",
		//							"SL: Summary Language"     => "",
		//							"OT: Original Title"       => "",
									"IS: ISSN"                 => "SN",
		//							"IB: ISBN"                 => "",
		//							"ER: Environmental Regime" => "",
		//							"CF: Conference"           => "",
									"NT: Notes"                => "UT", // we'll import the ISI record ID to the notes field
		//							"DO: DOI"                  => "DI", // Bibutils apparently recognizes "DI" to extract the DOI from ISI records, however, my tests returned only ISI records where the "DI" field contained some other identifier ?:-/
								);

		// ----------------------------------------------------------------

		// SPLIT INPUT text on the "ER" (= end of record) tag that terminates every ISI record:
		$isiRecordsArray = preg_split("/\s*[\r\n]ER *[\r\n]\s*/", $isiSourceData, -1, PREG_SPLIT_NO_EMPTY); // (the 'PREG_SPLIT_NO_EMPTY' flag causes only non-empty pieces to be returned)
		$recordsCount = count($isiRecordsArray); // count how many records are available

		$csaRecordsArray = array(); // initialize array variable which will hold all records that were converted to CSA format

		// ----------------------------------------------------------------

		// LOOP OVER EACH RECORD:
		for ($i=0; $i<$recordsCount; $i++) // for each record...
		{
			// we'll only process an array element if it's text does contain the "PT" tag as well as the "SO" tag:
			if ((preg_match("/^PT /m", $isiRecordsArray[$i])) AND (preg_match("/^SO /m", $isiRecordsArray[$i]))) // ...process this record:
			{
				$csaRecordFieldsArray = array(); // initialize array variable which will hold all fields that we've converted to CSA format

				// extract first email address from ISI "EM" field:
				if (preg_match("/^EM [^ \r\n]+/m", $isiRecordsArray[$i]))
					$emailAddress = preg_replace("/.*[\r\n]EM ([^ \r\n]+).*/s", "\\1", $isiRecordsArray[$i]);
				else
					$emailAddress = "";

				// extract start page (ISI "BP" field) and end page (ISI "EP" field):
				$pages = array();

				if (preg_match("/^BP [^ \r\n]+/m", $isiRecordsArray[$i]))
					$pages[] = preg_replace("/.*[\r\n]BP ([^\r\n]+).*/s", "\\1", $isiRecordsArray[$i]);

				if (preg_match("/^EP [^ \r\n]+/m", $isiRecordsArray[$i]))
					$pages[] = preg_replace("/.*[\r\n]EP ([^\r\n]+).*/s", "\\1", $isiRecordsArray[$i]);

				if (!empty($pages))
					$pageRange = implode("-", $pages);
				// if no start or end page is given, we'll try the ISI "PG" field that indicates the total number of pages:
				elseif (preg_match("/^PG [^ \r\n]+/m", $isiRecordsArray[$i]))
					$pageRange = preg_replace("/.*[\r\n]PG ([^\r\n]+).*/s", "\\1 pp", $isiRecordsArray[$i]);
				else
					$pageRange = "";

				// split each record into its individual fields:
				$isiRecordFieldsArray = preg_split("/[\r\n]+(?=\w\w )/", $isiRecordsArray[$i]);

				// LOOP OVER EACH FIELD:
				foreach ($isiRecordFieldsArray as $recordField)
				{
					// we'll only process an array element if it starts with two letters followed by a space
					if (preg_match("/^\w\w /", $recordField))
					{
						// split each field into its tag and its field data:
						list($recordFieldTag, $recordFieldData) = preg_split("/(?<=^\w\w) /", $recordField);

						foreach ($isiToCsaTagsArray as $csaTag => $isiTag) // for each ISI field that we'd like to convert...
						{
							if ($recordFieldTag == $isiTag)
							{
								// replace found ISI field identifier tag with the corresponding CSA tag:
								$recordFieldTag = $csaTag;

								// add a space to the beginning of any data line that starts with only three spaces (instead of four):
								$recordFieldData = preg_replace("/^   (?! )/m", "    ", $recordFieldData);

								// convert ISI publication type "J" into CSA format ("Journal Article"):
								if (($recordFieldTag == "PT: Publication Type") AND ($recordFieldData == "J"))
									$recordFieldData = "Journal Article";

								// merge multiple authors (that are printed on separate lines) with a semicolon (';') and a space:
								elseif ($recordFieldTag == "AU: Author")
									$recordFieldData = preg_replace("/\s*[\r\n]\s*/", "; ", $recordFieldData);

								// process address info:
								elseif ($recordFieldTag == "AF: Affiliation")
								{
									// remove any trailing punctuation from end of string:
									$recordFieldData = preg_replace("/[$punct]+$/$patternModifiers", "", $recordFieldData);

									$recordFieldDataArray = array(); // initialize array variable

									// if the address data string contains multiple addresses (which are given as one address per line):
									if (preg_match("/[\r\n]/", $recordFieldData))
										// split address data string into individual addresses:
										$recordFieldDataArray = preg_split("/[$punct\s]*[\r\n]\s*/$patternModifiers", $recordFieldData);
									else
										// use the single address as given:
										$recordFieldDataArray[] = $recordFieldData;

									// append the first email address from ISI "EM" field to the first address in "AF: Affiliation":
									if (($extractEmail) AND (!empty($emailAddress)))
										$recordFieldDataArray[0] .= ", Email: " . $emailAddress;

									if ($extractAllAddresses)
										// merge multiple addresses with a semicolon (';') and a space:
										$recordFieldData = implode("; ", $recordFieldDataArray);
									else
										// use only the first address in "AF: Affiliation":
										$recordFieldData = $recordFieldDataArray[0];
								}

								// if a comma (',') is used as keyword delimiter, we'll convert it into a semicolon (';'):
								elseif (($recordFieldTag == "DE: Descriptors") AND (!ereg(";", $recordFieldData)))
									$recordFieldData = preg_replace("/ *, */", "; ", $recordFieldData);

								// if all of the record data is in uppercase letters, we attempt to convert the string to something more readable:
								if ((preg_match("/^[$upper\W\d]+$/$patternModifiers", $recordFieldData)) AND ($isiTag != "UT")) // we exclude the ISI record ID from the ISI "UT" field
									// convert upper case to title case (converts e.g. "ELSEVIER SCIENCE BV" into "Elsevier Science Bv"):
									// (note that this case transformation won't do the right thing for author initials and abbreviations,
									//  but the result is better than the whole string being upper case, IMHO)
									$recordFieldData = changeCase('title', $recordFieldData); // function 'changeCase()' is defined in 'include.inc.php'

								// merge again field tag and data:
								$recordField = $recordFieldTag . "\n    " . $recordFieldData;

								// append this field to array of CSA fields:
								$csaRecordFieldsArray[] = $recordField;

								// process next ISI field in '$isiRecordFieldsArray':
								continue;
							}
						}
					}
				}

				// append "JP: Journal Pages" field with generated page range to array of CSA fields:
				if (!empty($pageRange))
					$csaRecordFieldsArray[] = "JP: Journal Pages\n    " . $pageRange;

				// merge CSA fields into a string and prefix it with a CSA record identifier:
				$csaRecord = "Record " . ($i + 1) . " of " . $recordsCount . "\n\n" . implode("\n", $csaRecordFieldsArray);

				// append this record to array of CSA records:
				$csaRecordsArray[] = $csaRecord;
			}
		}

		// return all CSA records merged into a string:
		return implode("\n\n", $csaRecordsArray);
	}

	// --------------------------------------------------------------------

	// CROSSREF TO REFBASE
	// This function converts records from CrossRef's "unixref" XML format into the standard "refbase"
	// array format which can be then imported by the 'addRecords()' function in 'include.inc.php'.
	//
	// NOTES: - So far, CrossRef seems to be the only provider of this data format & they do not yet
	//          use it to return more than one result. However, since function 'fetchDataFromCrossRef()'
	//          can fetch data for multiple DOIs and appends found records to '$sourceText', this
	//          function does allow for batch import (though we might need to tweak '$recordDelimiter'
	//          if multiple results would be returned directly by CrossRef).
	//
	//        - This is our first and only native XML import format, so we do not use functions
	//          'validateRecords()' or 'parseRecords()'.
	// 
	// TODO:
	// - one of these, in order of preference:
	//   - change functions 'validateRecords()' & 'parseRecords()' to accept non-tagged, XML references
	//   - add new XML validation/parsing functions
	//   - transform XML to a tagged format
	// 
	// Authors: Richard Karnesky <mailto:karnesky@gmail.com> and
	//          Matthias Steffens <mailto:refbase@extracts.de>
	function crossrefToRefbase($sourceText, $importRecordsRadio, $importRecordNumbersArray)
	{
		global $alnum, $alpha, $cntrl, $digit, $graph, $lower, $print, $punct, $space, $upper, $word, $patternModifiers; // defined in 'transtab_unicode_charset.inc.php' and 'transtab_latin1_charset.inc.php'

		global $contentTypeCharset; // defined in 'ini.inc.php'

		global $errors;
		global $showSource;

		// Pattern by which the input text will be split into individual records:
		$recordDelimiter = "(\s*<doi_records[^<>\r\n]*>)?\s*(?=<doi_record[^<>\r\n]*>)" // splits before '<doi_record>'
		                 . "|"
		                 . "(?<=<\/doi_record>)\s*(<\/doi_records>\s*)?"; // splits after '</doi_record>'

		// Pattern by which multiple persons are separated within the author, editor or series editor fields of the source data:
		// (Note: name standardization occurs after multiple author fields have been merged by '; ')
		$personDelimiter = " *; *";

		// Pattern by which a person's family name is separated from the given name (or initials):
		$familyNameGivenNameDelimiter = " *, *";

		// Specifies whether the person's family name comes first within a person's name
		// ('true' means that the family name is followed by the given name (or initials), 'false' means that the person's family name comes *after* the given name (or initials))
		$familyNameFirst = true;

		// Specifies whether a person's full given name(s) shall be shortened to initial(s):
		// (Notes: - if set to 'true', given names will be abbreviated and initials will get normalized (meaning removal of extra whitespace, adding of dots between initials, etc)
		//         - if set to 'false', given names (and any initials) are taken as is
		//         - in your database, you should stick to either fully written given names OR initials; if you mix these, records won't get sorted correctly on citation output)
		$shortenGivenNames = true;

		// Specifies whether fields whose contents are entirely in upper case shall be transformed to title case ('true') or not ('false'):
		$transformCase = true;

		// Postprocessor actions:
		// Defines search & replace 'actions' that will be applied to all those refbase fields that are listed in the corresponding 'fields' element:
		// (If you don't want to perform any search and replace actions, specify an empty array, like: '$postprocessorActionsArray = array();'.
		//  Note that, in this case, the search patterns MUST include the leading & trailing slashes -- which is done to allow for mode modifiers such as 'imsxU'.)
		//                              "/Search Pattern/" => "Replace Pattern"
		$postprocessorActionsArray = array(
		                                   array(
		                                         'fields'  => array("title"),
		                                         'actions' => array(
		                                                            "/[,.;:!] *$/" =>  "" // remove any punctuation (except for question marks) from end of field contents
		                                                           )
		                                        ),
											array(
													'fields'  => array("author", "title", "publication", "abbrev_journal"), // convert HTML font attributes (which some publishers include in their CrossRef data, see e.g. doi:10.1515/BOT.2001.002)
													'actions' => array(
																		"/<sup>(.+?)<\/sup>/i" =>  "[super:\\1]", // replace '<sup>...</sup>' with refbase markup ('[super:...]')
																		"/<sub>(.+?)<\/sub>/i" =>  "[sub:\\1]", // replace '<sub>...</sub>' with refbase markup ('[sub:...]')
																		"/<i>(.+?)<\/i>/i"     =>  "_\\1_", // replace '<i>...</i>' with refbase markup ('_..._')
																		"/<b>(.+?)<\/b>/i"     =>  "**\\1**" // replace '<b>...</b>' with refbase markup ('**...**')
																	)
												),
		                                   array(
		                                         'fields'  => array("author", "title", "year", "publication", "abbrev_journal", "volume", "issue", "pages", "issn", "url", "doi"), // not sure whether we need to do this for all fields, it occurs e.g. for doi:10.1007/BF00391383 in the 'url' field
		                                         'actions' => array(
		                                                            "/<!\[CDATA\[(.+?)\]\]>/" =>  "\\1" // remove any '<![CDATA[...]]>' wrapper
		                                                           )
		                                        )
		                                   );

		// -----------------------------------------

		// PRE-PROCESS SOURCE TEXT:

		// Split input text into individual records:
		$recordArray = splitSourceText($sourceText, $recordDelimiter, false);

		// PROCESS SOURCE DATA:

		// Initialize array variables:
		$parsedRecordsArray = array(); // initialize array variable which will hold parsed data of all records that shall be imported

		// NOTE: We do NOT validate records yet, i.e. we assume that they are perfect and attempt to import all of them:
		$importRecordNumbersRecognizedFormatArray = array(); // initialize array variable which will hold all record numbers of those records that shall be imported AND which were of a recognized format
		$importRecordNumbersNotRecognizedFormatArray = array(); // same for all records that shall be imported BUT which had an UNrecognized format

		$recordsCount = count($recordArray); // count how many records are available

		// -----------------------------------------

		// LOOP OVER EACH RECORD:
		for ($i=0; $i<$recordsCount; $i++) // for each record...
		{
			$fieldParametersArray = array(); // setup an empty array (it will hold all fields that were extracted for a given record)

			// Parse record XML:
			$XML = new XML($recordArray[$i]);

			// Check for any errors:
			$errorXML = $XML->getBranches("doi_record/crossref","error");

			if (!empty($errorXML[0]))
			{
				$importRecordNumbersNotRecognizedFormatArray[] = $i + 1; // append this record number to the list of numbers whose record format is NOT recognized

				$crossRefError = $errorXML[0]->getTagContent("error"); // e.g. "DOI not found in CrossRef"

				// Prepare an appropriate error message:
				$errorMessage = "Record " . ($i + 1) . ": " . $crossRefError . "!";

				if (!isset($errors["sourceText"]))
					$errors["sourceText"] = $errorMessage;
				else
					$errors["sourceText"] = $errors["sourceText"] . "<br>" . $errorMessage;
			}
			else // a DOI record was found
			{
				// NOTE: We do NOT yet validate any found records, i.e. for now, we'll just assume that they are ok:
				$importRecordNumbersRecognizedFormatArray[] = $i + 1; // append this record number to the list of numbers whose record format IS recognized ('$i' starts with 0 so we have to add 1 to point to the correct record number)

				$fieldParametersArray['type'] = 'Journal Article'; // MOST CrossRef entitites are journal articles. TODO: find what isn't & fix the type

				// Parse main XML branches:
				$metadataXML     = $XML->getBranches("doi_record/crossref/journal","journal_metadata");
				$issueXML        = $XML->getBranches("doi_record/crossref/journal","journal_issue");
				$articleXML      = $XML->getBranches("doi_record/crossref/journal","journal_article");
				$contributorsXML = $XML->getBranches("doi_record/crossref/journal/journal_article/contributors","person_name");

				// Process '$metadataXML':
				// TODO:
				// - Put CODEN and/or publisher-specific article IDs ('<publisher_item>') into the 'notes' field (?)
				$fieldParametersArray['publication']    = $metadataXML[0]->getTagContent("journal_metadata/full_title");
				$fieldParametersArray['abbrev_journal'] = $metadataXML[0]->getTagContent("journal_metadata/abbrev_title");

				// Get print ISSN ('media_type="print"')
				$issnXML = $XML->getBranches("doi_record/crossref/journal/journal_metadata","issn","media_type","print");

				if (!empty($issnXML[0]))
					$issn = $issnXML[0]->getTagContent("issn");
				else // if there's no ISSN tag with attribute 'media_type="print"', we fall back to the first given ISSN tag (if any)
				{
					$issnXML = $XML->getBranches("doi_record/crossref/journal/journal_metadata","issn");

					if (!empty($issnXML[0]))
						$issn = $issnXML[0]->getTagContent("issn");
					else
						$issn = "";
				}

				if (!empty($issn))
					$fieldParametersArray['issn'] = $issn;

				// Process '$issueXML':
				$fieldParametersArray['year']   = $issueXML[0]->getTagContent("journal_issue/publication_date/year");
				$fieldParametersArray['volume'] = $issueXML[0]->getTagContent("journal_issue/journal_volume/volume");
				$fieldParametersArray['issue']  = $issueXML[0]->getTagContent("journal_issue/issue");

				// Proccess '$articleXML':
				$fieldParametersArray['title'] = $articleXML[0]->getTagContent("journal_article/titles/title");

				// - Append any subtitle to the main title:
				$subTitleXML = $articleXML[0]->getBranches("journal_article/titles","subtitle");
				if (!empty($subTitleXML[0]))
					$fieldParametersArray['title'] .= ": " . $subTitleXML[0]->getTagContent("subtitle");

				$fieldParametersArray['doi']   = $articleXML[0]->getTagContent("journal_article/doi_data/doi");
				$fieldParametersArray['url']   = $articleXML[0]->getTagContent("journal_article/doi_data/resource");

				$fieldParametersArray['startPage'] = $articleXML[0]->getTagContent("journal_article/pages/first_page");
				$fieldParametersArray['endPage']   = $articleXML[0]->getTagContent("journal_article/pages/last_page");

				// Process '$contributorsXML':
				// TODO:
				// - Differentiate authors from other types of contributors
				$author = "";
				foreach ($contributorsXML as $contributor)
				{
					$givenName  = $contributor->getTagContent("person_name/given_name");
					$familyName = $contributor->getTagContent("person_name/surname");

					// If the author's family (or given) name is entirely in uppercase letters, we attempt to convert the string to something more readable:
					if ($transformCase)
					{
						if (preg_match("/^[$upper\W\d]+$/$patternModifiers", $familyName))
							// Convert upper case to title case (converts e.g. "STEFFENS" into "Steffens"):
							$familyName = changeCase('title', $familyName); // function 'changeCase()' is defined in 'include.inc.php'

						if (preg_match("/^[$upper\W\d]+$/$patternModifiers", $givenName))
							// Convert upper case to title case (converts e.g. "MATTHIAS" into "Matthias"):
							$givenName = changeCase('title', $givenName);
					}

					// Append any name suffix to the surname:
					$nameSuffixXML = $contributor->getBranches("person_name","suffix");
					if (!empty($nameSuffixXML[0]))
						$familyName .= " " . $nameSuffixXML[0]->getTagContent("suffix");

					$author .= $familyName . ", " . $givenName . "; ";
				}
				$author = trim($author, "; "); 
				$fieldParametersArray['author'] = $author;

				// Standardize field data contained in '$fieldParametersArray':
				foreach ($fieldParametersArray as $fieldKey => $fieldData)
				{
					// Decode HTML special chars:
					if (($fieldKey != "url") AND ereg('&(amp|quot|#0?39|lt|gt);', $fieldData))
						$fieldParametersArray[$fieldKey] = decodeHTMLspecialchars($fieldData); // function 'decodeHTMLspecialchars()' is defined in 'include.inc.php'

					elseif (($fieldKey == "url") AND ereg('&amp;', $fieldData)) // in case of the 'url' field, we just decode any ampersand characters
						$fieldParametersArray[$fieldKey] = str_replace('&amp;', '&', $fieldData);
				}

				// Function 'standardizeFieldData()' e.g. performs case transformation, standardizes thesis names, normalizes page ranges, and reformats person names according to preference:
				$fieldParametersArray = standardizeFieldData($fieldParametersArray, "CrossRef XML", $personDelimiter, $familyNameGivenNameDelimiter, $familyNameFirst, $shortenGivenNames, $transformCase, $postprocessorActionsArray);

				// Append the array of extracted field data to the main data array which holds all records to import:
				$parsedRecordsArray[] = $fieldParametersArray;
			}
		}

		// -----------------------------------------

		// Build refbase import array:
		$importDataArray = buildImportArray("refbase", // 'type' - the array format of the 'records' element
		                                    "1.0", // 'version' - the version of the given array structure
		                                    "http://refbase.net/import/crossref/", // 'creator' - the name of the script/importer (preferably given as unique URI)
		                                    "Richard Karnesky", // 'author' - author/contact name of the person who's responsible for this script/importer
		                                    "karnesky@users.sourceforge.net", // 'contact' - author's email/contact address
		                                    array('prefix_call_number' => "true"), // 'options' - array with settings that control the behaviour of the 'addRecords()' function
		                                    $parsedRecordsArray); // 'records' - array of record(s) (with each record being a sub-array of fields)


		return array($importDataArray, $recordsCount, $importRecordNumbersRecognizedFormatArray, $importRecordNumbersNotRecognizedFormatArray, $errors);
	}

	// --------------------------------------------------------------------

	// ARXIV TO REFBASE
	// This function converts records from arXiv.org's Atom XML Opensearch format into the standard "refbase"
	// array format which can be then imported by the 'addRecords()' function in 'include.inc.php'.
	// Info on the arXiv API (including sample code) is given at:
	// <http://export.arxiv.org/api_help/>
	// <http://export.arxiv.org/api_help/docs/user-manual.html>
	// <http://export.arxiv.org/api_help/docs/examples/php_arXiv_parsing_example.txt>
	// 
	// Requires the SimplePie library (by Ryan Parman and Geoffrey Sneddon), which is
	// available under the BSD license from: <http://simplepie.org>
	//
	// '$feed' must contain the list of Atom feed items given as a SimplePie object
	function arxivToRefbase(&$feed, $importRecordsRadio, $importRecordNumbersArray)
	{
		global $alnum, $alpha, $cntrl, $digit, $graph, $lower, $print, $punct, $space, $upper, $word, $patternModifiers; // defined in 'transtab_unicode_charset.inc.php' and 'transtab_latin1_charset.inc.php'

		global $contentTypeCharset; // defined in 'ini.inc.php'

		global $errors;
		global $showSource;

		// Pattern by which multiple persons are separated within the author, editor or series editor fields of the source data:
		// (Note: name standardization occurs after multiple author fields have been merged by '; ')
		$personDelimiter = " *; *";

		// Pattern by which a person's family name is separated from the given name (or initials):
		// 
		// NOTE: If function 'reArrangeAuthorContents()' would use 'preg_split()' (instead of just 'split()') to extract author name
		//       & initials into separate list items, we could use following settings to transform the formatting of author names to
		//       the one used by refbase:
		//         $familyNameGivenNameDelimiter = "/ (?=([$upper]+[-$alpha]+)( *;|$))/$patternModifiers";
		//         $familyNameFirst = false;
		//         $shortenGivenNames = true;
		//       This would e.g. convert "Steven M. Kahn" into "Kahn, S.M.". But since function 'reArrangeAuthorContents()' currently
		//       just uses 'split()', we'll pre-process each author string below (so that it is formatted like "Kahn, Steven M.").
		$familyNameGivenNameDelimiter = " *, *";

		// Specifies whether the person's family name comes first within a person's name
		// ('true' means that the family name is followed by the given name (or initials), 'false' means that the person's family name comes *after* the given name (or initials))
		$familyNameFirst = true;

		// Specifies whether a person's full given name(s) shall be shortened to initial(s):
		// (Notes: - if set to 'true', given names will be abbreviated and initials will get normalized (meaning removal of extra whitespace, adding of dots between initials, etc)
		//         - if set to 'false', given names (and any initials) are taken as is
		//         - in your database, you should stick to either fully written given names OR initials; if you mix these, records won't get sorted correctly on citation output)
		$shortenGivenNames = true;

		// Specifies whether fields whose contents are entirely in upper case shall be transformed to title case ('true') or not ('false'):
		$transformCase = true;

		// Postprocessor actions:
		// Defines search & replace 'actions' that will be applied to all those refbase fields that are listed in the corresponding 'fields' element:
		// (If you don't want to perform any search and replace actions, specify an empty array, like: '$postprocessorActionsArray = array();'.
		//  Note that, in this case, the search patterns MUST include the leading & trailing slashes -- which is done to allow for mode modifiers such as 'imsxU'.)
		//                              "/Search Pattern/" => "Replace Pattern"
		$postprocessorActionsArray = array(
		                                   array(
		                                         'fields'  => array("title", "abstract", "notes"),
		                                         'actions' => array(
		                                                            "/ *[\n\r]+ */" =>  " " // transform whitespace: replace any run of whitespace that includes newline/return character(s) with a space
		                                                           )
		                                        ),
		                                   array(
		                                         'fields'  => array("title"),
		                                         'actions' => array(
		                                                            "/[,.;:!] *$/" =>  "" // remove any punctuation (except for question marks) from end of field contents
		                                                           )
		                                        )
		                                   );

		// -----------------------------------------

		// PROCESS SOURCE DATA:

		// Initialize array variables:
		$parsedRecordsArray = array(); // initialize array variable which will hold parsed data of all records that shall be imported

		// NOTE: We do NOT validate records yet, i.e. we assume that they are perfect and attempt to import all of them:
		$importRecordNumbersRecognizedFormatArray = array(); // initialize array variable which will hold all record numbers of those records that shall be imported AND which were of a recognized format
		$importRecordNumbersNotRecognizedFormatArray = array(); // same for all records that shall be imported BUT which had an UNrecognized format

		// Use these namespaces to retrieve tags:
		$atomNamespace = 'http://www.w3.org/2005/Atom';
		$opensearchNamespace = 'http://a9.com/-/spec/opensearch/1.1/';
		$arxivNamespace = 'http://arxiv.org/schemas/atom';

		// Get feed data:
		$recordArray = $feed->get_items(); // fetch all feed items into an array
		$recordsCount = count($recordArray); // count how many records are available

		// -----------------------------------------

		// LOOP OVER EACH RECORD:
		for ($i=0; $i<$recordsCount; $i++) // for each record...
		{
			$fieldParametersArray = array(); // setup an empty array (it will hold all fields that were extracted for a given record)
			$record = $recordArray[$i]; // this will make things a bit more readable

			// Check for any errors:
			if ($record->get_title() == "Error")
			{
				$importRecordNumbersNotRecognizedFormatArray[] = $i + 1; // append this record number to the list of numbers whose record format is NOT recognized

				$arXivError = $record->get_description(); // e.g. "incorrect id format for 1234.12345"

				// Prepare an appropriate error message:
				$errorMessage = "Record " . ($i + 1) . ": " . $arXivError . "!";

				if (!isset($errors["sourceText"]))
					$errors["sourceText"] = $errorMessage;
				else
					$errors["sourceText"] = $errors["sourceText"] . "<br>" . $errorMessage;
			}
			elseif (!($record->get_permalink())) // empty record (which has no matching arXiv ID); ATM, this occurs e.g. when querying for "arXiv:1234.9999"
			{
				$importRecordNumbersNotRecognizedFormatArray[] = $i + 1; // append this record number to the list of numbers whose record format is NOT recognized

				// Prepare an appropriate error message:
				$errorMessage = "Record " . ($i + 1) . ": nothing found!";

				if (!isset($errors["sourceText"]))
					$errors["sourceText"] = $errorMessage;
				else
					$errors["sourceText"] = $errors["sourceText"] . "<br>" . $errorMessage;
			}
			else // an arXiv record was found
			{
				// NOTE: We do NOT yet validate any found records, i.e. for now, we'll just assume that they are ok:
				$importRecordNumbersRecognizedFormatArray[] = $i + 1; // append this record number to the list of numbers whose record format IS recognized ('$i' starts with 0 so we have to add 1 to point to the correct record number)

				// Extract elements of the current Atom XML entry:
				// - type:
				$fieldParametersArray['type'] = 'Journal Article'; // NOTE: Are all arXiv records journal articles? TODO: find what isn't & fix the type

				// - id:
				$fieldParametersArray['notes'] = str_replace("http://arxiv.org/abs/", "arXiv:", $record->get_permalink()); // extract the arXiv ID from the abstract URL in the 'id' element & prefix it with "arXiv:"

				// - title:
				$fieldParametersArray['title'] = $record->get_title();

				// - summary:
				if ($abstract = $record->get_description())
					$fieldParametersArray['abstract'] = $abstract;

				// - author:
				// NOTE: If we didn't want to extract author affiliation info, we could just use standard SimplePie functions ('get_authors()' and 'get_name()')
				$authorsArray = array();
				$addressArray = array();

				$authors = $record->get_item_tags($atomNamespace, 'author');

				foreach ($authors as $author)
				{
					$authorName = "";
					$authorLastName = "";
					$authorAddressArray = "";

					if (isset($author['child'][$atomNamespace]['name']) AND ($authorName = $author['child'][$atomNamespace]['name'][0]['data']))
					{
						// -- name:
						// In case of a latin1-based database, attempt to convert UTF-8 data to refbase markup & latin1:
						// NOTE: For authors, we need to perform charset conversion up here (and not further down below, as is done for all the other fields),
						//       since otherwise the below '$upper' and '$alpha' character class elements would fail to match!
						if (($contentTypeCharset == "ISO-8859-1") AND (detectCharacterEncoding($authorName) == "UTF-8")) // function 'detectCharacterEncoding()' is defined in 'include.inc.php'
							$authorName = convertToCharacterEncoding("ISO-8859-1", "TRANSLIT", $authorName, "UTF-8"); // function 'convertToCharacterEncoding()' is defined in 'include.inc.php'

						// Change the formatting of author names to the one used by refbase, i.e. the family name comes first, and a comma separates family name & initials:
						// (further standardisation of person names is done in function 'standardizeFieldData()'; see also note for '$familyNameGivenNameDelimiter' above)
						$authorName = preg_replace("/^(.+?) +([$upper]+[-$alpha]+)$/$patternModifiers", "\\2, \\1", $authorName);
						$authorsArray[] = $authorName;

						// -- arxiv:affiliation:
						if (isset($author['child'][$arxivNamespace]) AND ($authorAffiliations = $author['child'][$arxivNamespace]['affiliation']))
						{
							foreach ($authorAffiliations as $authorAffiliation)
								$authorAddressArray[] = $authorAffiliation['data'];

							$authorAddresses = implode(", ", $authorAddressArray);
							// In case of a latin1-based database, attempt to convert UTF-8 data to refbase markup & latin1:
							if (($contentTypeCharset == "ISO-8859-1") AND (detectCharacterEncoding($authorAddresses) == "UTF-8"))
								$authorAddresses = convertToCharacterEncoding("ISO-8859-1", "TRANSLIT", $authorAddresses, "UTF-8");

							$authorLastName = preg_replace("/^([$upper]+[-$alpha]+).+$/$patternModifiers", "\\1", $authorName); // extract authors last name
							$addressArray[] = $authorLastName . ": " . $authorAddresses;
						}
					}
				}

				if (!empty($authorsArray))
					$fieldParametersArray['author'] = implode("; ", $authorsArray); // merge multiple authors

				if (!empty($addressArray))
					$fieldParametersArray['address'] = implode("; ", $addressArray); // merge multiple author affiliations

				// - links:
				// 
				// TODO: Currently, we just copy a link to the PDF to the 'file' field. It might be desirable to fetch the actual PDF and store it on the refbase server.
				// 
				// NOTE: - In order to extract any links, we access the raw SimplePie object here; This is done since, in SimplePie v1.1.1, the standard SimplePie functions
				//         'get_link()' and 'get_links()' only support checking for the 'rel' attribute, but don't allow to filter on the 'type' or 'title' attribute. However,
				//         we need to check the 'type' & 'title' attributes in order to assign PDF & DOI links to the 'file' & 'doi' fields, respectively. Alternatively, we
				//         could also get this information from the URL itself, but that may fail if arXiv changes its URL pattern.
				//       - More info on how to grab custom tags or attributes: <http://simplepie.org/wiki/tutorial/grab_custom_tags_or_attributes>
				$links = $record->get_item_tags($atomNamespace, 'link');

				foreach ($links as $link)
				{
					if (isset($link['attribs']['']['href']))
					{
						// -- file:
						if (!isset($fieldParametersArray['file']) AND isset($link['attribs']['']['title']) AND ($link['attribs']['']['title'] == "pdf")) // we could also check for 'type="application/pdf"'
							$fieldParametersArray['file'] = $link['attribs']['']['href'];

						// -- url:
						// NOTE: the 'id' element (see above) also contains the URL to the abstract page for the current article
						elseif (!isset($fieldParametersArray['url']) AND isset($link['attribs']['']['type']) AND ($link['attribs']['']['type'] == "text/html")) // we could also check for 'title' being unset
							$fieldParametersArray['url'] = $link['attribs']['']['href'];

						// -- doi:
						// NOTE: the 'arxiv:doi' element also contains the DOI for the current article
						elseif (!isset($fieldParametersArray['doi']) AND isset($link['attribs']['']['title']) AND ($link['attribs']['']['title'] == "doi"))
							$fieldParametersArray['doi'] = str_replace("http://dx.doi.org/", "", $link['attribs']['']['href']);
					}
				}

				// - arxiv:comment:
				if ($comment = $record->get_item_tags($arxivNamespace, 'comment'))
					$fieldParametersArray['notes'] .= "; " . $comment[0]['data']; // TODO: if arXiv records can include multiple comments, we'd need to loop over all of them

				// - arxiv:primary_category:
				// TODO: Should we copy the term given in the 'arxiv:primary_category' element to the 'area' field?

				// - arxiv:category:
				$categoriesArray = array();

				foreach ($record->get_categories() as $category)
					$categoriesArray[] = $category->get_label();

				if (!empty($categoriesArray))
					$fieldParametersArray['keywords'] = implode("; ", $categoriesArray); // merge multiple categories

				// - arxiv:journal_ref:
				if ($journalRef = $record->get_item_tags($arxivNamespace, 'journal_ref'))
				{
					// We extract the full 'journal_ref' string into its own variable since we're going to mess with it:
					$journalRefData = preg_replace("/ *[\n\r]+ */", " ", $journalRef[0]['data']); // transform whitespace: replace any run of whitespace that includes newline/return character(s) with a space

					// NOTE: The formatting of the 'journal_ref' string can vary heavily, so
					//       the below parsing efforts may fail. Therefore, we'll also copy the
					//       original 'journal_ref' string to the 'notes' field, and display it
					//       in the header message when importing single records.
					$fieldParametersArray['source'] = $journalRefData;
					$fieldParametersArray['notes'] .= "; Journal Ref: " . $journalRefData;

					// Extract source info from the 'journal_ref' string into the different fields:
					// NOTE: We try to use reasonably liberal (and thus rather ugly!) regex patterns
					//       which should catch most of the commonly used formatting styles. However,
					//       as noted above, due to the varying formatting of the 'journal_ref' string,
					//       this may not be always entirely successful.
					// TODO: Extract ISSN from the 'journal_ref' string (see e.g. 'arXiv:cond-mat/0506611v1')
					// -- journal:
					$journalName = preg_replace("/^(.+?)(?= *(\(?\d+|[,;]|(v(ol)?\.?|volume) *\d+|$)).*/i", "\\1", $journalRefData); // extract journal name
					$journalRefData = preg_replace("/^(.+?)(?= *(\(?\d+|[,;]|(v(ol)?\.?|volume) *\d+|$))[,; ]*/i", "", $journalRefData); // remove journal name from 'journal_ref' string
					if (ereg("\.", $journalName))
						$fieldParametersArray['abbrev_journal'] = preg_replace("/(?<=\.)(?![ )]|$)/", " ", $journalName); // ensure that any dots are followed with a space
					else
						$fieldParametersArray['publication'] = $journalName;

					// -- volume:
					// NOTE: The volume is assumed to be the first number that follows the journal name, and
					//       which is followed by another four-digit number (which is asssumed to be the year).
					if (preg_match("/^(?:(?:v(?:ol)?\.?|volume) *)?(\w*\d+\w*)(?= *.*?\d{4})/i", $journalRefData))
					{
						$fieldParametersArray['volume'] = preg_replace("/^(?:(?:v(?:ol)?\.?|volume) *)?(\w*\d+\w*)(?= *.*?\d{4}).*/i", "\\1", $journalRefData); // extract volume
						$journalRefData = preg_replace("/^(?:(?:v(?:ol)?\.?|volume) *)?(\w*\d+\w*)(?= *.*?\d{4})[,; ]*/i", "", $journalRefData); // remove volume from 'journal_ref' string
					}

					// -- year (take 1):
					// NOTE: For the first take, we assume the year to be the first occurrence of a four-digit number
					//       that's wrapped in parentheses.
					if (preg_match("/\(\d{4}\)/i", $journalRefData))
					{
						$fieldParametersArray['year'] = preg_replace("/^.*?\((\d{4})\).*?$/i", "\\1", $journalRefData); // extract year
						$journalRefData = preg_replace("/[,; ]*\(\d{4}\)[,; ]*/i", " ", $journalRefData); // remove year from 'journal_ref' string
					}

					// -- issue:
					// NOTE: The issue is only recognized if it is preceded with a "n/no/number" prefix, or if it is a
					//       number with less than four digits that is enclosed in parentheses (we can check for the latter
					//       case since four-digit years that are wrapped in parens have already been removed). The regex
					//       patterns below also try to account for some non-digit characters in issue numbers.
					// TODO: Support issue extraction from "Journal Vol:No ..." format (see e.g. 'arXiv:cond-mat/0703452v2')
					if (preg_match("/(?:(?:n\.|no\.?|number) *)(\w*[\d\/-]+\w*)|\((\w*(?:\d{1,3}|\d{1,2}[\/-]+\d{1,2})\w*)\)/i", $journalRefData)) // matches e.g. "no. 2", "Number 2" or "(1/2)"
					{
						$fieldParametersArray['issue'] = preg_replace("/^.*?(?:(?:(?:n\.|no\.?|number) *)(\w*[\d\/-]+\w*)|\((\w*(?:\d{1,3}|\d{1,2}[\/-]+\d{1,2})\w*)\)).*?$/i", "\\1\\2", $journalRefData); // extract issue
						$journalRefData = preg_replace("/[,; ]*(?:(?:(?:n\.|no\.?|number) *)(\w*[\d\/-]+\w*)|\((\w*(?:\d{1,3}|\d{1,2}[\/-]+\d{1,2})\w*)\))[,; ]*/i", "", $journalRefData); // remove issue from 'journal_ref' string
					}

					// -- pages (take 1):
					// NOTE: For the first take, we assume the pages to be either preceded with a "p/pp" prefix, or to
					//       be a page range.
					if (preg_match("/(?:p(?:p)?\.? *)(\w*\d+\w*)(?: *-+ *(\w*\d+\w*))?|(?:p(?:p)?\.? *)?(\w*\d+\w*) *-+ *(\w*\d+\w*)/i", $journalRefData)) // matches e.g. "p. 167-188", "pp.361--364" or "197-209"
					{
						$fieldParametersArray['startPage'] = preg_replace("/^.*?(?:(?:p(?:p)?\.? *)(\w*\d+\w*)(?: *-+ *(\w*\d+\w*))?|(?:p(?:p)?\.? *)?(\w*\d+\w*) *-+ *(\w*\d+\w*)).*?$/i", "\\1\\3", $journalRefData); // extract starting page
						$fieldParametersArray['endPage'] = preg_replace("/^.*?(?:(?:p(?:p)?\.? *)(\w*\d+\w*)(?: *-+ *(\w*\d+\w*))?|(?:p(?:p)?\.? *)?(\w*\d+\w*) *-+ *(\w*\d+\w*)).*?$/i", "\\2\\4", $journalRefData); // extract ending page
						$journalRefData = preg_replace("/[,; ]*(?:(?:p(?:p)?\.? *)(\w*\d+\w*)(?: *-+ *(\w*\d+\w*))?|(?:p(?:p)?\.? *)?(\w*\d+\w*) *-+ *(\w*\d+\w*))[,; ]*/i", "", $journalRefData); // remove page info from 'journal_ref' string
					}

					// -- year (take 2):
					// NOTE: For the second take, we assume the year to be the first occurrence of any four-digit number
					//       in the remaining 'journal_ref' string.
					if (!isset($fieldParametersArray['year']) AND preg_match("/\b\d{4}\b/i", $journalRefData))
					{
						$fieldParametersArray['year'] = preg_replace("/^.*?\b(\d{4})\b.*?$/i", "\\1", $journalRefData); // extract year
						$journalRefData = preg_replace("/[,; ]*\b\d{4}\b[,; ]*/i", " ", $journalRefData); // remove year from 'journal_ref' string
					}

					// -- pages (take 2):
					// NOTE: For the second take, we assume the page info to be any number that is at the beginning of
					//       the remaining 'journal_ref' string.
					if (!isset($fieldParametersArray['startPage']) AND preg_match("/^[,; ]*\w*\d+\w*/i", $journalRefData))
					{
						$fieldParametersArray['startPage'] = preg_replace("/^[,; ]*(\w*\d+\w*).*?$/i", "\\1", $journalRefData); // extract page info
					}
				}

				// Standardize field data contained in '$fieldParametersArray':
				foreach ($fieldParametersArray as $fieldKey => $fieldData)
				{
					// In case of a latin1-based database, attempt to convert UTF-8 data to refbase markup & latin1:
					// (we exclude the 'author' and 'address' fields here since they have already been dealt with above)
					if ((!ereg("^(author|address)$", $fieldKey)) AND ($contentTypeCharset == "ISO-8859-1") AND (detectCharacterEncoding($fieldData) == "UTF-8"))
						$fieldData = convertToCharacterEncoding("ISO-8859-1", "TRANSLIT", $fieldData, "UTF-8");

					// Decode HTML special chars:
					if (($fieldKey != "url") AND ereg('&(amp|quot|#0?39|lt|gt);', $fieldData))
						$fieldParametersArray[$fieldKey] = decodeHTMLspecialchars($fieldData); // function 'decodeHTMLspecialchars()' is defined in 'include.inc.php'

					elseif (($fieldKey == "url") AND ereg('&amp;', $fieldData)) // in case of the 'url' field, we just decode any ampersand characters
						$fieldParametersArray[$fieldKey] = str_replace('&amp;', '&', $fieldData);
				}

				// Function 'standardizeFieldData()' e.g. performs case transformation, standardizes thesis names, normalizes page ranges, and reformats person names according to preference:
				$fieldParametersArray = standardizeFieldData($fieldParametersArray, "arXiv XML", $personDelimiter, $familyNameGivenNameDelimiter, $familyNameFirst, $shortenGivenNames, $transformCase, $postprocessorActionsArray);

				// Append the array of extracted field data to the main data array which holds all records to import:
				$parsedRecordsArray[] = $fieldParametersArray;
			}
		}

		// -----------------------------------------

		// Build refbase import array:
		$importDataArray = buildImportArray("refbase", // 'type' - the array format of the 'records' element
		                                    "1.0", // 'version' - the version of the given array structure
		                                    "http://refbase.net/import/arxiv/", // 'creator' - the name of the script/importer (preferably given as unique URI)
		                                    "Matthias Steffens", // 'author' - author/contact name of the person who's responsible for this script/importer
		                                    "refbase@extracts.de", // 'contact' - author's email/contact address
		                                    array('prefix_call_number' => "true"), // 'options' - array with settings that control the behaviour of the 'addRecords()' function
		                                    $parsedRecordsArray); // 'records' - array of record(s) (with each record being a sub-array of fields)


		return array($importDataArray, $recordsCount, $importRecordNumbersRecognizedFormatArray, $importRecordNumbersNotRecognizedFormatArray, $errors);
	}

	// --------------------------------------------------------------------

	// RIS TO REFBASE
	// This function converts records from Reference Manager (RIS) format into the standard "refbase"
	// array format which can be then imported by the 'addRecords()' function in 'include.inc.php'.
	function risToRefbase($sourceText, $importRecordsRadio, $importRecordNumbersArray)
	{
		global $contentTypeCharset; // defined in 'ini.inc.php'

		global $errors;
		global $showSource;

		// Define regular expression patterns that will facilitate parsing of RIS data:
		// (patterns must be specified as perl-style regular expression, without the leading & trailing slashes, if not stated otherwise)

		// Pattern by which the input text will be split into individual records:
		$recordDelimiter = "\s*[\r\n]ER  - *[\r\n]*\s*";

		// Pattern by which records will be split into individual fields:
		$fieldDelimiter = "[\r\n]+(?=\w\w  - )";

		// Pattern by which fields will be split into their field label (tag) and field data:
		$dataDelimiter = "(?<=^\w\w)  - ";

		// Pattern by which multiple persons are separated within the author, editor or series editor fields of the source data:
		// (Note: name standardization occurs after multiple author fields have been merged by '; ')
		$personDelimiter = " *; *";

		// Pattern by which a person's family name is separated from the given name (or initials):
		$familyNameGivenNameDelimiter = " *, *";

		// Specifies whether the person's family name comes first within a person's name
		// ('true' means that the family name is followed by the given name (or initials), 'false' means that the person's family name comes *after* the given name (or initials))
		$familyNameFirst = true;

		// Specifies whether a person's full given name(s) shall be shortened to initial(s):
		// (Notes: - if set to 'true', given names will be abbreviated and initials will get normalized (meaning removal of extra whitespace, adding of dots between initials, etc)
		//         - if set to 'false', given names (and any initials) are taken as is
		//         - in your database, you should stick to either fully written given names OR initials; if you mix these, records won't get sorted correctly on citation output)
		$shortenGivenNames = true;

		// Specifies whether fields whose contents are entirely in upper case shall be transformed to title case ('true') or not ('false'):
		$transformCase = true;

		// Preprocessor actions:
		// Defines search & replace 'actions' that will be applied to each record's raw source data if the pattern in the corresponding 'match' element is matched:
		// (If you don't want to perform any preprocessor actions, specify an empty array, like: '$preprocessorActionsArray = array();'.
		//  Note that, in this case, the search patterns MUST include the leading & trailing slashes -- which is done to allow for mode modifiers such as 'imsxU'.)
		// 								  "/Search Pattern/"  =>  "Replace Pattern"
		$preprocessorActionsArray = array(
											array(
													'match'   => "/&#?\w+;/", // if HTML encoded text (such as "&auml;", "&#xF6;" or "&#233;") occurs in the source data
													'actions' => array(
																		"/(&#?\w+;)/e"  =>  "html_entity_decode('\\1', ENT_QUOTES, '$contentTypeCharset')" // HTML decode source data (see <http://www.php.net/manual/en/function.html-entity-decode.php>)
																	)
												)
										);

		// Postprocessor actions:
		// Defines search & replace 'actions' that will be applied to all those refbase fields that are listed in the corresponding 'fields' element:
		// (If you don't want to perform any search and replace actions, specify an empty array, like: '$postprocessorActionsArray = array();'.
		//  Note that, in this case, the search patterns MUST include the leading & trailing slashes -- which is done to allow for mode modifiers such as 'imsxU'.)
		// 								  "/Search Pattern/"  =>  "Replace Pattern"
		$postprocessorActionsArray = array(
											array(
													'fields'  => array("year"),
													'actions' => array(
																		"/^.*?(\d{4}).*/" =>  "\\1" // for the 'year' field, extract any four-digit number (and discard everything else)
																	)
												),
											array(
													'fields'  => array("title"),
													'actions' => array(
																		"/[,.;:!] *$/" =>  "" // remove any punctuation (except for question marks) from end of field contents
																	)
												),
											array(
													'fields'  => array("notes"),
													'actions' => array(
																		"/exported from refbase \(http[^ ]+ last updated.+?\d{2}:\d{2}:\d{2} [+-]\d{4}/" =>  "" // remove refbase attribution string (such as "exported from refbase (http://localhost/refs/show.php?record=12345), last updated on Sat, 15 Jul 2006 22:24:16 +0200")
																	)
												),
											array(
													'fields'  => array("url"),
													'actions' => array(
																		"/^PM:(\d+)$/i" =>  "http://www.ncbi.nlm.nih.gov/pubmed/\\1" // convert "PM:17302433" into a resolvable PubMed URL; Bibutils 'xml2ris' (<= v3.40) converts "<identifier type="pubmed">17302433</identifier>" to "UR  - PM:17302433"
																	)
												),
											array(
													'fields'  => array("title", "address", "keywords", "abstract", "orig_title", "series_title", "abbrev_series_title", "notes"), // convert font attributes (which some publishers include in RIS records that are available on their web pages)
													'actions' => array(
																		"/<sup>(.+?)<\/sup>/i" =>  "[super:\\1]", // replace '<sup>...</sup>' with refbase markup ('[super:...]')
																		"/<sub>(.+?)<\/sub>/i" =>  "[sub:\\1]", // replace '<sub>...</sub>' with refbase markup ('[sub:...]')
																		"/<i>(.+?)<\/i>/i"     =>  "_\\1_", // replace '<i>...</i>' with refbase markup ('_..._')
																		"/<b>(.+?)<\/b>/i"     =>  "**\\1**", // replace '<b>...</b>' with refbase markup ('**...**')
																		"/\\x10(.+?)\\x11/"    =>  "_\\1_" // replace '<ASCII#10>...<ASCII#11>' (which is used by Reference Manager to indicate italic strings) with refbase markup ('_..._')
																	)
												),
											array(
													'fields'  => array("title", "abstract", "orig_title", "series_title", "abbrev_series_title", "notes"), // convert RefWorks font attributes (which RefWorks supports in title fields, notes, abstracts and user 1 - 5 fields)
													'actions' => array(
																		"/0RW1S34RfeSDcfkexd09rT3(.+?)1RW1S34RfeSDcfkexd09rT3/"  =>  "[super:\\1]", // replace RefWorks indicators for superscript text with refbase markup ('[super:...]')
																		"/0RW1S34RfeSDcfkexd09rT4(.+?)1RW1S34RfeSDcfkexd09rT4/"  =>  "[sub:\\1]", // replace RefWorks indicators for subscript text with refbase markup ('[sub:...]')
																		"/0RW1S34RfeSDcfkexd09rT2(.+?)1RW1S34RfeSDcfkexd09rT2/"  =>  "_\\1_", // replace RefWorks indicators for italic text with refbase markup ('_..._')
																		"/0RW1S34RfeSDcfkexd09rT0(.+?)1RW1S34RfeSDcfkexd09rT0/"  =>  "**\\1**", // replace RefWorks indicators for bold text with refbase markup ('**...**')
																		"/0RW1S34RfeSDcfkexd09rT1(.+?)1RW1S34RfeSDcfkexd09rT1/"  =>  "__\\1__" // replace RefWorks indicators for underline text with refbase markup ('__...__')
																	)
												)
										);


		// This array lists patterns which match all RIS tags that must occur within a record to be recognized as valid RIS record:
		// (Array keys must contain the tag name as it should be displayed to the user; as is the case with search & replace actions,
		//  the search patterns MUST include the leading & trailing slashes.)
		// 				"tag display name"  =>  "tag search pattern"
		$requiredTagsArray = array(
									"TY"    =>  "/^TY  - /m"
								);

		// This array matches RIS tags with their corresponding refbase fields:
		// (fields that are unsupported in either RIS or refbase are commented out)
		// 								"RIS tag" => "refbase field" // RIS tag name (comment)
		$tagsToRefbaseFieldsArray = array(
											"TY"  =>  "type", // Type of reference (IMPORTANT: the array element that maps to 'type' must be listed as the first element!)

											"AU"  =>  "author", // Author Primary
											"A1"  =>  "author", // Author Primary
											"A2"  =>  "editor", // Author Secondary (see note for 'corporate_author' below)
											"ED"  =>  "editor", // Author Secondary
											"A3"  =>  "series_editor", // Author Series
											"AD"  =>  "address", // Address
		//									""    =>  "corporate_author", // note that bibutils uses the RIS 'A2' tag to indicate corporate authors ('<name type="corporate">'), e.g., when importing contents of the BibTeX 'organization' field

											"TI"  =>  "title", // Title Primary
											"T1"  =>  "title", // Title Primary
											"CT"  =>  "title", // Title Primary
		//									""    =>  "orig_title",

											"PY"  =>  "year", // Date Primary (date must be in the following format: "YYYY/MM/DD/other_info"; the year, month and day fields are all numeric; the other info field can be any string of letters, spaces and hyphens; note that each specific date information is optional, however the slashes ("/") are not)
											"Y1"  =>  "year", // Date Primary (same syntax rules as for "PY")
		//									"Y2"  =>  "", // Date Secondary (same syntax rules as for "PY")

											"BT"  =>  array("BOOK" => "series_title", "STD" => "series_title", "THES" => "series_title", "Other" => "publication"), // according to <http://www.refman.com/support/risformat_tags_01.asp> this would be: array("BOOK" => "title", "Other" => "publication"), // Book Whole: Title Primary; Other reference types: Title Secondary
											"JF"  =>  "publication", // Periodical name: full format
											"JO"  =>  "publication", // Periodical name: full format
											"JA"  =>  "abbrev_journal", // Periodical name: standard abbreviation
											"J1"  =>  "abbrev_journal", // Periodical name: user abbreviation 1
											"J2"  =>  "abbrev_journal", // Periodical name: user abbreviation 2
											"T2"  =>  array("JOUR" => "abbrev_journal", "CHAP" => "publication", "Other" => "abbrev_series_title"), // Title Secondary (## "T2" is used by bibutils (instead of "JA") for abbreviated journal names! ##)
											"T3"  =>  "series_title", // Title Series (in case of "TY=CONF", "T3" appears to be used for conference title)

											"VL"  =>  "volume", // Volume number
											"IS"  =>  "issue", // Issue
											"SP"  =>  "startPage", // Start page number (contents of the special fields 'startPage' and 'endPage' will be merged into a range and copied to the refbase 'pages' field)
											"EP"  =>  "endPage", // Ending page number
											"LP"  =>  "endPage", // Ending page number ('LP' is actually not part of the RIS specification but gets used in the wild such as in RIS exports of the American Physical Society, <http://aps.org/>)

		//									""    =>  "series_volume", // (for 'series_volume' and 'series_issue', some magic will be applied within the 'parseRecords()' function)
		//									""    =>  "series_issue",

											"PB"  =>  "publisher", // Publisher
											"CY"  =>  "place", // City of publication
											"CP"  =>  "place", // City of publication

		//									""    =>  "edition",
		//									""    =>  "medium",
											"SN"  =>  array("BOOK" => "isbn", "CHAP" => "isbn", "STD" => "isbn", "THES" => "isbn", "Other" => "issn"), // Book Whole, Book Chapter, Generic and Thesis: ISBN; Other reference types: ISSN (note that this will fail for a thesis that was published within a series with an ISSN number)

		//									""    =>  "language",
		//									""    =>  "summary_language",

											"KW"  =>  "keywords", // Keywords
											"AB"  =>  "abstract", // Abstract
											"N2"  =>  "abstract", // Abstract

		//									""    =>  "area",
		//									""    =>  "expedition",
		//									""    =>  "conference",

		//									""    =>  "doi",
											"UR"  =>  "url", // URL (URL addresses can be entered individually, one per tag or multiple addresses can be entered on one line using a semi-colon as a separator)
											"L1"  =>  "file", // Link to PDF (same syntax rules as for "UR")
		//									"L2"  =>  "", // Link to Full-text (same syntax rules as for "UR")
		//									"L3"  =>  "related", // Related Records (NOTE: import into user-specific fields is NOT supported yet!)
		//									"L4"  =>  "", // Image(s)

											"N1"  =>  "notes", // Notes
											"ID"  =>  "call_number", // Reference ID (NOTE: alternatively, we could map 'ID' to the user-specific 'cite_key' field which would copy the contents of the 'ID' field to the 'cite_key' field of the currently logged-in user)

		//									"M1"  =>  "", // Miscellaneous 1
		//									"M2"  =>  "", // Miscellaneous 2
		//									"M3"  =>  "", // Miscellaneous 3
											"U1"  =>  "thesis", // User definable 1 ('U1' is used by Bibutils to indicate the type of thesis, e.g. "Masters thesis" or "Ph.D. thesis"; function 'parseRecords()' will further tweak the contents of the refbase 'thesis' field)
											"U2"  =>  "user_notes", // User definable 2
		//									"U3"  =>  "", // User definable 3
		//									"U4"  =>  "", // User definable 4
		//									"U5"  =>  "", // User definable 5

		//									""    =>  "contribution_id",
		//									""    =>  "online_publication",
		//									""    =>  "online_citation",
		//									""    =>  "approved",
		//									""    =>  "orig_record",

		//									"RP"  =>  "copy", // Reprint status (valid values: "IN FILE", "NOT IN FILE", "ON REQUEST (MM/DD/YY)") (NOTE: import into user-specific fields is NOT supported yet!)
		//									"AV"  =>  "", // Availability
										);

		// This array lists all RIS tags that may occur multiple times:
		$tagsMultipleArray = array(
									"AU",
									"A1",
									"A2",
									"ED",
									"A3",
									"KW",
									"UR", // currently, refbase does only support one URL per record (however, we allow 'UR' to occur multiple times to extract any DOI given as URL, otherwise only the first URL will be taken)
		//							"L1", // currently, refbase does only support one file per record
									"N1"
								);


		// This array matches RIS reference types with their corresponding refbase types:
		// (RIS types that are currently not supported in refbase will be taken as is but will get
		//  prefixed with an "Unsupported: " label; '#fallback#' in comments indicates a type mapping that
		//  is not a perfect match but as close as currently possible)
		// 										"RIS type"  =>  "refbase type" // name of RIS reference type (comment)
		$referenceTypesToRefbaseTypesArray = array(
													"ABST"       =>  "Abstract", // Abstract
													"ADVS"       =>  "Unsupported: Audiovisual Material", // Audiovisual material
													"ART"        =>  "Unsupported: Art Work", // Art Work
													"BILL"       =>  "Unsupported: Bill/Resolution", // Bill/Resolution
													"BOOK"       =>  "Book Whole", // Book, Whole
													"CASE"       =>  "Unsupported: Case", // Case
													"CHAP(TER)?" =>  "Book Chapter", // Book chapter (the incorrect CHAPTER type gets used by SpringerLink, see e.g. RIS output at <http://www.springerlink.com/content/57w5dd51eh0h8a25>)
													"COMP"       =>  "Software", // Computer program
													"CONF"       =>  "Conference Article", // Conference proceeding
													"CTLG"       =>  "Book Whole", // Catalog (#fallback#)
													"DATA"       =>  "Unsupported: Data File", // Data file
													"ELEC"       =>  "Unsupported: Electronic Citation", // Electronic Citation
													"GEN"        =>  "Miscellaneous", // Generic
													"HEAR"       =>  "Unsupported: Hearing", // Hearing
													"ICOMM"      =>  "Unsupported: Internet Communication", // Internet Communication
													"INPR"       =>  "Unsupported: In Press", // In Press
													"JFULL"      =>  "Journal", // Journal (full)
													"JOUR"       =>  "Journal Article", // Journal
													"MAP"        =>  "Map", // Map
													"MGZN"       =>  "Magazine Article", // Magazine article
													"MPCT"       =>  "Unsupported: Motion Picture", // Motion picture
													"MUSIC"      =>  "Unsupported: Music Score", // Music score
													"NEWS"       =>  "Newspaper Article", // Newspaper
													"PAMP"       =>  "Unsupported: Pamphlet", // Pamphlet
													"PAT"        =>  "Patent", // Patent
													"PCOMM"      =>  "Unsupported: Personal Communication", // Personal communication
													"RPRT"       =>  "Report", // Report
													"SER"        =>  "Unsupported: Serial (Book, Monograph)", // Serial (Book, Monograph)
													"SLIDE"      =>  "Unsupported: Slide", // Slide
													"SOUND"      =>  "Unsupported: Sound Recording", // Sound recording
													"STAT"       =>  "Unsupported: Statute", // Statute
													"STD"        =>  "Miscellaneous", // Generic (note that 'STD' is used by bibutils although it is NOT listed as a recognized reference type at <http://www.refman.com/support/risformat_reftypes.asp>)
													"THES"       =>  "Thesis", // Thesis/Dissertation (function 'parseRecords()' will set the special type 'Thesis' back to 'Book Whole' and adopt the refbase 'thesis' field)
													"UNBILL"     =>  "Unsupported: Unenacted Bill/Resolution", // Unenacted bill/resolution
													"UNPB"       =>  "Manuscript", // Unpublished work (#fallback#)
													"VIDEO"      =>  "Unsupported: Video Recording" // Video recording
												);

		// -----------------------------------------

		// Split input text into individual records:
		$recordArray = splitSourceText($sourceText, $recordDelimiter, false); // split on the "ER" (= end of record) tag that terminates every RIS record

		// Validate all records that shall be imported:
		list($errors, $importRecordNumbersRecognizedFormatArray, $importRecordNumbersNotRecognizedFormatArray) = validateRecords($recordArray, $requiredTagsArray, $importRecordsRadio, $importRecordNumbersArray, $errors);

		// Parse all records that shall be imported:
		list($parsedRecordsArray, $recordsCount) = parseRecords($recordArray, "RIS", $importRecordNumbersRecognizedFormatArray, $tagsToRefbaseFieldsArray, $tagsMultipleArray, $referenceTypesToRefbaseTypesArray, $fieldDelimiter, $dataDelimiter, $personDelimiter, $familyNameGivenNameDelimiter, $familyNameFirst, $shortenGivenNames, $transformCase, $postprocessorActionsArray, $preprocessorActionsArray);

		// Build refbase import array:
		$importDataArray = buildImportArray("refbase", // 'type' - the array format of the 'records' element
											"1.0", // 'version' - the version of the given array structure
											"http://refbase.net/import/ris/", // 'creator' - the name of the script/importer (preferably given as unique URI)
											"Matthias Steffens", // 'author' - author/contact name of the person who's responsible for this script/importer
											"refbase@extracts.de", // 'contact' - author's email/contact address
											array('prefix_call_number' => "true"), // 'options' - array with settings that control the behaviour of the 'addRecords()' function
											$parsedRecordsArray); // 'records' - array of record(s) (with each record being a sub-array of fields)


		return array($importDataArray, $recordsCount, $importRecordNumbersRecognizedFormatArray, $importRecordNumbersNotRecognizedFormatArray, $errors);
	}

	// --------------------------------------------------------------------

	// MEDLINE TO REFBASE
	// This function converts records from Pubmed MEDLINE format into the standard "refbase"
	// array format which can be then imported by the 'addRecords()' function in 'include.inc.php'.
	function medlineToRefbase($sourceText, $importRecordsRadio, $importRecordNumbersArray)
	{
		global $alnum, $alpha, $cntrl, $digit, $graph, $lower, $print, $punct, $space, $upper, $word, $patternModifiers; // defined in 'transtab_unicode_charset.inc.php' and 'transtab_latin1_charset.inc.php'

		global $errors;
		global $showSource;

		// Define regular expression patterns that will facilitate parsing of MEDLINE data:
		// (patterns must be specified as perl-style regular expression, without the leading & trailing slashes, if not stated otherwise)

		// Pattern by which the input text will be split into individual records:
		$recordDelimiter = "\s*[\r\n](?=PMID- |<html>)"; // PubMed error messages are wrapped into HTML (errors may occur e.g. when fetching MEDLINE data directly via their PubMed ID)

		// Pattern by which records will be split into individual fields:
		$fieldDelimiter = "[\r\n]+(?=\w{2,4} *- )";

		// Pattern by which fields will be split into their field label (tag) and field data:
		$dataDelimiter = "(?<=^\w{2})  - |(?<=^\w{3}) - |(?<=^\w{4})- ";

		// Pattern by which multiple persons are separated within the author, editor or series editor fields of the source data:
		// (Note: name standardization occurs after multiple author fields have been merged by '; ')
		$personDelimiter = " *; *";

		// Pattern by which a person's family name is separated from the given name (or initials):
		$familyNameGivenNameDelimiter = " *, *";

		// Specifies whether the person's family name comes first within a person's name
		// ('true' means that the family name is followed by the given name (or initials), 'false' means that the person's family name comes *after* the given name (or initials))
		$familyNameFirst = true;

		// Specifies whether a person's full given name(s) shall be shortened to initial(s):
		// (Notes: - if set to 'true', given names will be abbreviated and initials will get normalized (meaning removal of extra whitespace, adding of dots between initials, etc)
		//         - if set to 'false', given names (and any initials) are taken as is
		//         - in your database, you should stick to either fully written given names OR initials; if you mix these, records won't get sorted correctly on citation output)
		$shortenGivenNames = true;

		// Specifies whether fields whose contents are entirely in upper case shall be transformed to title case ('true') or not ('false'):
		$transformCase = true;

		// Preprocessor actions:
		// Defines search & replace 'actions' that will be applied to each record's raw source data if the pattern in the corresponding 'match' element is matched:
		// (If you don't want to perform any preprocessor actions, specify an empty array, like: '$preprocessorActionsArray = array();'.
		//  Note that, in this case, the search patterns MUST include the leading & trailing slashes -- which is done to allow for mode modifiers such as 'imsxU'.)
		// 								  "/Search Pattern/"  =>  "Replace Pattern"
		$preprocessorActionsArray = array(
											array(
													'match'   => "/^FAU - .+?[\r\n]AU  - /m", // if author info is available via both 'FAU' *AND* 'AU' field(s)
													'actions' => array(
																		"/^AU  - .+?[\r\n]+/m"  =>  "" // discard any 'AU' field(s) (which otherwise would confuse the 'parseRecords()' function)
																	)
												),
											array(
													'match'   => "/^AU  - /m",
													'actions' => array(
																		"/(?<=^AU  - )([$alpha -]+) +([$upper]+)/m$patternModifiers"  =>  "\\1, \\2" // change the string formatting in 'AU' field(s) to the one used by refbase (i.e. insert a comma between family name & initials)
																	)
												)
										);

		// Postprocessor actions:
		// Defines search & replace 'actions' that will be applied to all those refbase fields that are listed in the corresponding 'fields' element:
		// (If you don't want to perform any search and replace actions, specify an empty array, like: '$postprocessorActionsArray = array();'.
		//  Note that, in this case, the search patterns MUST include the leading & trailing slashes -- which is done to allow for mode modifiers such as 'imsxU'.)
		// 								  "/Search Pattern/"  =>  "Replace Pattern"
		$postprocessorActionsArray = array(
											array(
													'fields'  => array("year"),
													'actions' => array(
																		"/^.*?(\d{4}).*/"  =>  "\\1" // for the 'year' field, extract any four-digit number (and discard everything else)
																	)
												),
											array(
													'fields'  => array("title", "orig_title", "publication", "address"),
													'actions' => array(
																		"/[,.;:!] *$/"  =>  "" // remove any punctuation (except for question marks) from end of field contents
																	)
												),
											array(
													'fields'  => array("publication", "abbrev_journal"), // NOTE: this replacement action will probably be only beneficial for records of type "Journal Article" (if possible, this should rather be a preprocessor action to distinguish articles from books or other resource types)
													'actions' => array(
																		"/\b([$lower])([$alpha]{3,})/e$patternModifiers"  =>  "strtoupper('\\1').'\\2'" // make sure that all journal title words (with >3 characters) start with an upper case letter (the 'e' modifier allows to execute PHP code within the replacement pattern)
																	)
												),
											array(
													'fields'  => array("issn"),
													'actions' => array(
																		"/^.*?(\w{4}-?\w{4}).*/"  =>  "\\1" // remove any text except the actual ISSN number
																	)
												),
											array(
													'fields'  => array("notes"),
													'actions' => array(
																		"/^(\d+)/"  =>  "PMID:\\1" // insert a "PMID:" prefix in front of any number that's at the beginning of the notes field
																	)
												),
											array(
													'fields'  => array("doi"),
													'actions' => array(
																		"/^.*?([^ ]+) *\[doi\].*/"  =>  "\\1", // if a DOI number is given, extract the DOI and discard everything else
																		"/^.*?\[[^]]+\].*/"  =>  "" // if no DOI number was given but some other ID info is still present, remove everything from the field
																	)
												),
											array(
													'fields'  => array("language", "summary_language"),
													'actions' => array(
																		"/^afr$/i"  =>  "Afrikaans", // map abbreviated language names to full names (taken from <http://www.nlm.nih.gov/bsd/language_table.html>)
																		"/^alb$/i"  =>  "Albanian",
																		"/^amh$/i"  =>  "Amharic",
																		"/^ara$/i"  =>  "Arabic",
																		"/^arm$/i"  =>  "Armenian",
																		"/^aze$/i"  =>  "Azerbaijani",
																		"/^ben$/i"  =>  "Bengali",
																		"/^bos$/i"  =>  "Bosnian",
																		"/^bul$/i"  =>  "Bulgarian",
																		"/^cat$/i"  =>  "Catalan",
																		"/^chi$/i"  =>  "Chinese",
																		"/^cze$/i"  =>  "Czech",
																		"/^dan$/i"  =>  "Danish",
																		"/^dut$/i"  =>  "Dutch",
																		"/^eng$/i"  =>  "English",
																		"/^epo$/i"  =>  "Esperanto",
																		"/^est$/i"  =>  "Estonian",
																		"/^fin$/i"  =>  "Finnish",
																		"/^fre$/i"  =>  "French",
																		"/^geo$/i"  =>  "Georgian",
																		"/^ger$/i"  =>  "German",
																		"/^gla$/i"  =>  "Scottish Gaelic",
																		"/^gre$/i"  =>  "Greek, Modern",
																		"/^heb$/i"  =>  "Hebrew",
																		"/^hin$/i"  =>  "Hindi",
																		"/^hun$/i"  =>  "Hungarian",
																		"/^ice$/i"  =>  "Icelandic",
																		"/^ind$/i"  =>  "Indonesian",
																		"/^ita$/i"  =>  "Italian",
																		"/^jpn$/i"  =>  "Japanese",
																		"/^kin$/i"  =>  "Kinyarwanda",
																		"/^kor$/i"  =>  "Korean",
																		"/^lat$/i"  =>  "Latin",
																		"/^lav$/i"  =>  "Latvian",
																		"/^lit$/i"  =>  "Lithuanian",
																		"/^mac$/i"  =>  "Macedonian",
																		"/^mal$/i"  =>  "Malayalam",
																		"/^mao$/i"  =>  "Maori",
																		"/^may$/i"  =>  "Malay",
																		"/^mul$/i"  =>  "Multiple languages",
																		"/^nor$/i"  =>  "Norwegian",
																		"/^per$/i"  =>  "Persian",
																		"/^pol$/i"  =>  "Polish",
																		"/^por$/i"  =>  "Portuguese",
																		"/^pus$/i"  =>  "Pushto",
																		"/^rum$/i"  =>  "Romanian, Rumanian",
																		"/^rus$/i"  =>  "Russian",
																		"/^san$/i"  =>  "Sanskrit",
																		"/^scc$/i"  =>  "Serbian",
																		"/^scr$/i"  =>  "Croatian",
																		"/^slo$/i"  =>  "Slovak",
																		"/^slv$/i"  =>  "Slovenian",
																		"/^spa$/i"  =>  "Spanish",
																		"/^swe$/i"  =>  "Swedish",
																		"/^tha$/i"  =>  "Thai",
																		"/^tur$/i"  =>  "Turkish",
																		"/^ukr$/i"  =>  "Ukrainian",
																		"/^und$/i"  =>  "Undetermined",
																		"/^urd$/i"  =>  "Urdu",
																		"/^vie$/i"  =>  "Vietnamese",
																		"/^wel$/i"  =>  "Welsh"
																	)
												)
										);


		// This array lists patterns which match all MEDLINE tags that must occur within a record to be recognized as valid MEDLINE record:
		// (Array keys must contain the tag name as it should be displayed to the user; as is the case with search & replace actions,
		//  the search patterns MUST include the leading & trailing slashes.)
		// 				"tag display name"  =>  "tag search pattern"
		$requiredTagsArray = array(
									"PMID"  =>  "/^PMID- /m",
									"PT"    =>  "/^PT  - /m"
								);

		// This array matches MEDLINE tags with their corresponding refbase fields:
		// (MEDLINE fields taken from <http://www.ncbi.nlm.nih.gov/books/bv.fcgi?rid=helppubmed.table.pubmedhelp.T43>;
		//  fields that are unsupported in either MEDLINE or refbase are commented out)
		// 							"MEDLINE tag"   =>  "refbase field" // MEDLINE tag name [Description] (comment)
		$tagsToRefbaseFieldsArray = array(
											"PT"    =>  "type", // Publication Type [The type of material the article represents] (IMPORTANT: the array element that maps to 'type' must be listed as the first element!)

											"AU"    =>  "author", // Author [Authors] (the contents of the 'AU' field will be used if the 'FAU' field is not available; note that for records that contain both 'AU' *AND* 'FAU' fields, this only works if a suitable preprocessor action is defined, see above)
											"FAU"   =>  "author", // Full Author Name [Full Author Names] (by default, we use this author format since family name and initials are uniquely separated by a comma)
		//									""      =>  "editor",
		//									""      =>  "series_editor",
											"AD"    =>  "address", // Affiliation [Institutional affiliation and address of the first author]
											"CN"    =>  "corporate_author", // Corporate Author [Corporate author or group names with authorship responsibility]

											"TI"    =>  "title", // Title [The title of the article]
											"TT"    =>  "orig_title", // Transliterated Title [Title of the article originally published in a non-English language, in that language]

											"DP"    =>  "year", // Publication Date [The date the article was published]
		//									"DEP"   =>  "", // Date of Electronic Publication [Electronic publication date]

											"JT"    =>  "publication", // Full Journal Title [Full journal title from NLM's cataloging data]
											"TA"    =>  "abbrev_journal", // Journal Title Abbreviation [Standard journal title abbreviation]
		//									""      =>  "series_title",

											"VI"    =>  "volume", // Volume [Volume number of the journal]
											"IP"    =>  "issue", // Issue [The number of the issue, part, or supplement of the journal in which the article was published]
											"PG"    =>  "pages", // Pagination [The full pagination of the article]

		//									""      =>  "series_volume",
		//									""      =>  "series_issue",

		//									""      =>  "edition",
		//									""      =>  "medium",
		//									""      =>  "isbn",
											"IS"    =>  "issn", // ISSN [International Standard Serial Number of the journal]

		//									""      =>  "publisher",
		//									"PL"    =>  "place", // Place of Publication [Journal's country of publication] (the "PL" field lists the *country* of publication but the *city* of publication should go into the "place" field)

											"LA"    =>  "language", // Language [The language in which the article was published]
		//									""      =>  "summary_language",

											"MH"    =>  "keywords", // MeSH Terms [NLM's Medical Subject Headings (MeSH) controlled vocabulary]
											"OT"    =>  "keywords", // Other Term [Non-MeSH subject terms (keywords) assigned by an organization identified by the Other Term Owner]
											"OAB"   =>  "abstract", // Other Abstract [Abstract supplied by an NLM collaborating organization] (since "AB" is defined later and neither "OAB" nor "AB" is listed in '$tagsMultipleArray', any content in "AB" will overwrite contents of "OAB")
											"AB"    =>  "abstract", // Abstract [English language abstract taken directly from the published article]

		//									""      =>  "area",
		//									""      =>  "expedition",
		//									""      =>  "conference",

											"AID"   =>  "doi", // Article Identifier [Article ID values supplied by the publisher may include the pii (controlled publisher identifier) or doi (Digital Object Identifier)] (using a search & replace action, we'll extract only the doi bit)
		//									""      =>  "url",
		//									""      =>  "file",

											"GN"    =>  "notes", // General Note [Supplemental or descriptive information related to the document]
											"PMID"  =>  "notes", // PubMed Unique Identifier [Unique number assigned to each PubMed citation]
		//									""      =>  "call_number",

		//									""      =>  "contribution_id",
		//									""      =>  "online_publication",
		//									""      =>  "online_citation",
		//									""      =>  "approved",
		//									""      =>  "orig_record",

		//#									"PUBM"  =>  "online_publication", // Publishing Model [Article's model of print or electronic publishing]

											"SO"    =>  "source", // Source [Composite field containing bibliographic information] (the contents of this special field may be presented within the header message of 'record.php' for easy comparison with the extracted data)

		//									"CI"    =>  "", // Copyright Information [Copyright statement provided by the publisher]
		//									"CIN"   =>  "", // Comment In [Reference containing a comment about the article]
		//									"CON"   =>  "", // Comment On [Reference upon which the article comments]
		//									"CRF"   =>  "", // Corrected and republished from [Final, correct version of an article]
		//									"CRI"   =>  "", // Corrected and republished in [Original article that was republished in corrected form]
		//									"DA"    =>  "", // Date Created [Used for internal processing at NLM]
		//									"DCOM"  =>  "", // Date Completed [Used for internal processing at NLM]
		//									"EDAT"  =>  "", // Entrez Date [The date the citation was added to PubMed]
		//									"EFR"   =>  "", // Erratum For [Cites the original article needing the correction]
		//									"EIN"   =>  "", // Erratum In [Reference containing a published erratum to the article]
		//									"FIR"   =>  "", // Full Investigator [Full investigator name]
		//									"FPS"   =>  "", // Full Personal Name as Subject [Full Personal Name of the subject of the article]
		//									"GR"    =>  "", // Grant Number [Research grant numbers, contract numbers, or both that designate financial support by any agency of the US PHS or Wellcome Trust]
		//									"GS"    =>  "", // Gene Symbol [Abbreviated gene names (used 1991 through 1996)]
		//									"IR"    =>  "", // Investigator [NASA-funded principal investigator]
		//									"IRAD"  =>  "", // Investigator Affiliation [Affiliation of NASA-funded principal investigator]
		//									"JID"   =>  "", // NLM Unique ID [Unique journal ID in NLM's catalog of books, journals, and audiovisuals]
		//									"LR"    =>  "", // Last Revision Date [The date a change was made to the record]
		//									"MHDA"  =>  "", // MeSH Date [The date MeSH terms were added to the citation. The MeSH date is the same as the Entrez date until MeSH are added]
		//									"OCI"   =>  "", // Other Copyright Information [Copyright owner]
		//									"OID"   =>  "", // Other ID [Identification numbers provided by organizations supplying citation data]
		//									"ORI"   =>  "", // Original Report In [Cites the original article associated with the patient summary]
		//									"OTO"   =>  "", // Other Term Owner [Organization that provided the Other Term data]
		//									"OWN"   =>  "", // Owner [Organization acronym that supplied citation data]
		//									"PHST"  =>  "", // Publication History Status Date [Publisher supplied dates regarding the article publishing process]
		//									"PS"    =>  "", // Personal Name as Subject [Individual is the subject of the article]
		//									"PST"   =>  "", // Publication Status [Publication status]
		//									"RF"    =>  "", // Number of References [Number of bibliographic references for Review articles]
		//									"RIN"   =>  "", // Retraction In [Retraction of the article]
		//									"RN"    =>  "", // EC/RN Number [Number assigned by the Enzyme Commission to designate a particular enzyme or by the Chemical Abstracts Service for Registry Numbers]
		//									"ROF"   =>  "", // Retraction Of [Article being retracted]
		//									"RPF"   =>  "", // Republished From [Original article]
		//									"RPI"   =>  "", // Republished In [Corrected and republished article]
		//									"SB"    =>  "", // Subset [Journal or citation subset values representing specialized topics]
		//									"SFM"   =>  "", // Space Flight Mission [NASA-supplied data space flight/mission name and/or number]
		//									"SI"    =>  "", // Secondary Source Identifier [Identifies secondary source databanks and accession numbers of molecular sequences discussed in articles]
		//									"SPIN"  =>  "", // Summary For Patients In [Cites a patient summary article]
		//									"STAT"  =>  "", // Status Tag [Used for internal processing at NLM]
		//									"UIN"   =>  "", // Update In [Update to the article]
		//									"UOF"   =>  "", // Update Of [The article being updated]
										);

		// This array lists all MEDLINE tags that may occur multiple times:
		$tagsMultipleArray = array(
									"AU", // see above note for 'AU' at '$tagsToRefbaseFieldsArray'
									"FAU",
									"MH",
									"OT",
									"AID",
									"PMID", // by allowing "PMID" and "GN" to occur multiple times we can merge contents of both of these fields into the 'notes' field
									"GN"
								);


		// This array matches MEDLINE reference types with their corresponding refbase types:
		// (MEDLINE types that are currently not supported in refbase will be taken as is but will get
		//  prefixed with an "Unsupported: " label; '#fallback#' in comments indicates a type mapping that
		//  is not a perfect match but as close as currently possible)
		// 												                                              "MEDLINE type" =>  "refbase type"
		$referenceTypesToRefbaseTypesArray = array(
		//											"Journal Article"                                                =>  "Journal Article", // NOTE: PubMed has *many* more types which should be dealt with (see e.g. <http://www.nlm.nih.gov/mesh/pubtypes2006.html> and <http://www.nlm.nih.gov/mesh/pubtypesg2003.html>) 
													"JOURNAL ARTICLE"                                                =>  "Journal Article",
													"REVIEW|Review"                                                  =>  "Journal Article", // in some records, "PT" may occur multiple times (e.g. as in "PT  - Journal Article\nPT  - Review"), and refbase currently uses the contents of the last "PT" as type
													"Monograph|Account Books|Guidebooks|Handbooks|Textbooks"         =>  "Book Whole",
													"Congresses|Meeting Abstracts"                                   =>  "Conference Article",
													"Consensus Development Conference(, NIH)?"                       =>  "Conference Article",
													"Newspaper Article"                                              =>  "Newspaper Article",
													"(Annual|Case|Technical) Reports?"                               =>  "Report",
													"Manuscripts|Unpublished Works"                                  =>  "Manuscript",
													"Patents"                                                        =>  "Patent",
													"Maps"                                                           =>  "Map",
													"Editorial"                                                      =>  "Journal Article",
													"Letter"                                                         =>  "Journal Article", // #fallback#
													"Validation Studies"                                             =>  "Journal Article",
													"Research Support, N\.I\.H\., (Ex|In)tramural *"                 =>  "Journal Article",
													"Research Support, (Non-)?U\.S\. Gov\'t(, (Non-)?P\.H\.S\.)? *"  =>  "Journal Article"
												);

		// -----------------------------------------

		// Split input text into individual records:
		$recordArray = splitSourceText($sourceText, $recordDelimiter, false); // split on the "ER" (= end of record) tag that terminates every MEDLINE record

		// Validate all records that shall be imported:
		list($errors, $importRecordNumbersRecognizedFormatArray, $importRecordNumbersNotRecognizedFormatArray) = validateRecords($recordArray, $requiredTagsArray, $importRecordsRadio, $importRecordNumbersArray, $errors);

		// Parse all records that shall be imported:
		list($parsedRecordsArray, $recordsCount) = parseRecords($recordArray, "MEDLINE", $importRecordNumbersRecognizedFormatArray, $tagsToRefbaseFieldsArray, $tagsMultipleArray, $referenceTypesToRefbaseTypesArray, $fieldDelimiter, $dataDelimiter, $personDelimiter, $familyNameGivenNameDelimiter, $familyNameFirst, $shortenGivenNames, $transformCase, $postprocessorActionsArray, $preprocessorActionsArray);

		// Build refbase import array:
		$importDataArray = buildImportArray("refbase", // 'type' - the array format of the 'records' element
											"1.0", // 'version' - the version of the given array structure
											"http://refbase.net/import/medline/", // 'creator' - the name of the script/importer (preferably given as unique URI)
											"Matthias Steffens", // 'author' - author/contact name of the person who's responsible for this script/importer
											"refbase@extracts.de", // 'contact' - author's email/contact address
											array('prefix_call_number' => "true"), // 'options' - array with settings that control the behaviour of the 'addRecords()' function
											$parsedRecordsArray); // 'records' - array of record(s) (with each record being a sub-array of fields)


		return array($importDataArray, $recordsCount, $importRecordNumbersRecognizedFormatArray, $importRecordNumbersNotRecognizedFormatArray, $errors);
	}

	// --------------------------------------------------------------------

	// REFWORKS TO REFBASE
	// This function converts records from RefWorks Tagged Format into the standard "refbase"
	// array format which can be then imported by the 'addRecords()' function in 'include.inc.php'.
	// More info on the RefWorks Tagged Format: <http://refworks.scholarsportal.info/Refworks/help/RefWorks_Tagged_Format.htm>
	function refworksToRefbase($sourceText, $importRecordsRadio, $importRecordNumbersArray)
	{
		global $errors;
		global $showSource;

		// Define regular expression patterns that will facilitate parsing of RefWorks data:
		// (patterns must be specified as perl-style regular expression, without the leading & trailing slashes, if not stated otherwise)

		// Pattern by which the input text will be split into individual records:
		$recordDelimiter = "\s*[\r\n][\r\n][\r\n]+\s*";

		// Pattern by which records will be split into individual fields:
		$fieldDelimiter = "[\r\n]+(?=\w\w )";

		// Pattern by which fields will be split into their field label (tag) and field data:
		$dataDelimiter = "(?<=^\w\w) ";

		// Pattern by which multiple persons are separated within the author, editor or series editor fields of the source data:
		// (Note: name standardization occurs after multiple author fields have been merged by '; ')
		$personDelimiter = " *; *";

		// Pattern by which a person's family name is separated from the given name (or initials):
		$familyNameGivenNameDelimiter = " *, *";

		// Specifies whether the person's family name comes first within a person's name
		// ('true' means that the family name is followed by the given name (or initials), 'false' means that the person's family name comes *after* the given name (or initials))
		$familyNameFirst = true;

		// Specifies whether a person's full given name(s) shall be shortened to initial(s):
		// (Notes: - if set to 'true', given names will be abbreviated and initials will get normalized (meaning removal of extra whitespace, adding of dots between initials, etc)
		//         - if set to 'false', given names (and any initials) are taken as is
		//         - in your database, you should stick to either fully written given names OR initials; if you mix these, records won't get sorted correctly on citation output)
		$shortenGivenNames = true;

		// Specifies whether fields whose contents are entirely in upper case shall be transformed to title case ('true') or not ('false'):
		$transformCase = true;

		// Preprocessor actions:
		// Defines search & replace 'actions' that will be applied to each record's raw source data if the pattern in the corresponding 'match' element is matched:
		// (If you don't want to perform any preprocessor actions, specify an empty array, like: '$preprocessorActionsArray = array();'.
		//  Note that, in this case, the search patterns MUST include the leading & trailing slashes -- which is done to allow for mode modifiers such as 'imsxU'.)
		// 								  "/Search Pattern/"  =>  "Replace Pattern"
		$preprocessorActionsArray = array();

		// Postprocessor actions:
		// Defines search & replace 'actions' that will be applied to all those refbase fields that are listed in the corresponding 'fields' element:
		// (If you don't want to perform any search and replace actions, specify an empty array, like: '$postprocessorActionsArray = array();'.
		//  Note that, in this case, the search patterns MUST include the leading & trailing slashes -- which is done to allow for mode modifiers such as 'imsxU'.)
		// 								  "/Search Pattern/"  =>  "Replace Pattern"
		$postprocessorActionsArray = array(
											array(
													'fields'  => array("year"),
													'actions' => array(
																		"/^.*?(\d{4}).*/" =>  "\\1" // for the 'year' field, extract any four-digit number (and discard everything else)
																	)
												),
											array(
													'fields'  => array("title"),
													'actions' => array(
																		"/[,.;:!] *$/" =>  "" // remove any punctuation (except for question marks) from end of field contents
																	)
												),
											array(
													'fields'  => array("title", "abstract", "orig_title", "series_title", "abbrev_series_title", "notes"), // convert RefWorks font attributes (which RefWorks supports in title fields, notes, abstracts and user 1 - 5 fields)
													'actions' => array(
																		"/0RW1S34RfeSDcfkexd09rT3(.+?)1RW1S34RfeSDcfkexd09rT3/"  =>  "[super:\\1]", // replace RefWorks indicators for superscript text with refbase markup ('[super:...]')
																		"/0RW1S34RfeSDcfkexd09rT4(.+?)1RW1S34RfeSDcfkexd09rT4/"  =>  "[sub:\\1]", // replace RefWorks indicators for subscript text with refbase markup ('[sub:...]')
																		"/0RW1S34RfeSDcfkexd09rT2(.+?)1RW1S34RfeSDcfkexd09rT2/"  =>  "_\\1_", // replace RefWorks indicators for italic text with refbase markup ('_..._')
																		"/0RW1S34RfeSDcfkexd09rT0(.+?)1RW1S34RfeSDcfkexd09rT0/"  =>  "**\\1**", // replace RefWorks indicators for bold text with refbase markup ('**...**')
																		"/0RW1S34RfeSDcfkexd09rT1(.+?)1RW1S34RfeSDcfkexd09rT1/"  =>  "__\\1__" // replace RefWorks indicators for underline text with refbase markup ('__...__')
																	)
												)
										);


		// This array lists patterns which match all RefWorks tags that must occur within a record to be recognized as valid RefWorks record:
		// (Array keys must contain the tag name as it should be displayed to the user; as is the case with search & replace actions,
		//  the search patterns MUST include the leading & trailing slashes.)
		// 				"tag display name"  =>  "tag search pattern"
		$requiredTagsArray = array(
									"RT"    =>  "/^RT /m"
								);

		// This array matches RefWorks tags with their corresponding refbase fields:
		// (fields that are unsupported in either RefWorks or refbase are commented out)
		// 						   "RefWorks tag" => "refbase field" // RefWorks tag name (comment)
		$tagsToRefbaseFieldsArray = array(
											"RT"  =>  "type", // Reference Type (IMPORTANT: the array element that maps to 'type' must be listed as the first element!)
		//									""    =>  "thesis",

											"A1"  =>  "author", // Primary Authors
											"A2"  =>  "editor", // Secondary Authors (Editors)
											"A3"  =>  "series_editor", // Tertiary Authors (Series Editors)
		//									"A4"  =>  "", // Quaternary Authors (Translators)
		//									"A5"  =>  "", // Quinary Authors (Compliers)
		//									"A6"  =>  "", // Website Editors
											"AD"  =>  "address", // Author Address
		//									""    =>  "corporate_author",

											"T1"  =>  "title", // Primary Title
											"OT"  =>  "orig_title", // Original Foreign Title
		//									"ST"  =>  "", // Shortened Title
		//									"WT"  =>  "", // Website Title

		//									"FD"  =>  "", // Publication Data, Free Form (this field is used for date information such as a season or month and day; year data is solely placed in the year field, i.e., "YR 2003")
											"YR"  =>  "year", // Year
		//									"RD"  =>  "", // Retrieved Date
		//									"WV"  =>  "", // Website Version
		//									"WP"  =>  "", // Date of Electronic Publication

											"JF"  =>  "publication", // Periodical name: full format
											"JO"  =>  "abbrev_journal", // Periodical name: standard abbreviation
											"T2"  =>  array("Book, Section" => "publication", "Other" => "series_title"), // Secondary Title
											"T3"  =>  "abbrev_series_title", // Tertiary Title

											"VO"  =>  "volume", // Volume
											"IS"  =>  "issue", // Issue
											"SP"  =>  "startPage", // Start Page (contents of the special fields 'startPage' and 'endPage' will be merged into a range and copied to the refbase 'pages' field)
											"OP"  =>  "endPage", // Other Pages ('SP' is the tag for the starting page and should only contain this information; the 'OP' tag is used for any additional pages or page information)

		//									""    =>  "series_volume", // (for 'series_volume' and 'series_issue', some magic will be applied within the 'parseRecords()' function)
		//									""    =>  "series_issue",

											"PB"  =>  "publisher", // Publisher
											"PP"  =>  "place", // Place of Publication

											"ED"  =>  "edition", // Edition
		//									""    =>  "medium",
											"SN"  =>  array("Book, Section" => "isbn", "Book, Edited" => "isbn", "Book, Whole" => "isbn", "Dissertation" => "isbn", "Dissertation/Thesis" => "isbn", "Other" => "issn"), // Book Whole & Book Chapter: ISBN; Other reference types: ISSN

											"LA"  =>  "language", // Language
		//									""    =>  "summary_language",

											"K1"  =>  "keywords", // Keywords
											"AB"  =>  "abstract", // Abstract

		//									""    =>  "area",
		//									""    =>  "expedition",
		//									""    =>  "conference",

											"DO"  =>  "doi", // Digital Object Identifier
											"LK"  =>  "url", // Links
											"UL"  =>  "url", // URL
		//									""    =>  "file", // Link to PDF
		//									""    =>  "related", // Related Records (NOTE: import into user-specific fields is NOT supported yet!)

											"NO"  =>  "notes", // Notes
											"ID"  =>  "call_number", // Reference Identifier
											"CN"  =>  "notes", // Call Number (if 'ID' would be mapped to 'cite_key', contents of this field could go into the 'call_number' field)
											"IP"  =>  "notes", // Identifying Phrase (NOTE: contents of this field should probably better go into 'cite_key' but import into user-specific fields is NOT supported yet!)

		//									"U1"  =>  "", // User definable 1
		//									"U2"  =>  "", // User definable 2
		//									"U3"  =>  "", // User definable 3
		//									"U4"  =>  "", // User definable 4
		//									"U5"  =>  "", // User definable 5

		//									""    =>  "contribution_id",
		//									""    =>  "online_publication",
		//									""    =>  "online_citation",
		//									""    =>  "approved",
		//									""    =>  "orig_record",

		//									""    =>  "copy", // Reprint status (valid values: "IN FILE", "NOT IN FILE", "ON REQUEST (MM/DD/YY)") (NOTE: import into user-specific fields is NOT supported yet!)
											"AV"  =>  "notes", // Availability

		//									"AN"  =>  "", // Accession Number
		//									"CL"  =>  "", // Classification
		//									"SF"  =>  "", // Subfile/Database
		//									"DB"  =>  "", // Database
		//									"DS"  =>  "", // Data Source
		//									"SL"  =>  "", // Sponsoring Library
		//									"LL"  =>  "", // Sponsoring Library Location
		//									"CR"  =>  "", // Cited References
										);

		// This array lists all RefWorks tags that may occur multiple times:
		$tagsMultipleArray = array(
									"A1",
									"A2",
									"A3",
		//							"A4",
		//							"A5",
		//							"A6",
									"K1",
		//							"LK", // currently, refbase does only support one link per record
		//							"UL", // currently, refbase does only support one URL per record
									"ID",
									"CN",
									"IP",
									"NO",
									"AV"
								);


		// This array matches RefWorks reference types with their corresponding refbase types:
		// (RefWorks types that are currently not supported in refbase will be taken as is but will get
		//  prefixed with an "Unsupported: " label; '#fallback#' in comments indicates a type mapping that
		//  is not a perfect match but as close as currently possible)
		// 																  "RefWorks type"  =>  "refbase type" // name of RefWorks reference type (comment)
		$referenceTypesToRefbaseTypesArray = array(
													"Abstract"                             =>  "Abstract", // Abstract
													"Artwork"                              =>  "Unsupported: Artwork", // Artwork
													"Bills\/Resolutions"                   =>  "Unsupported: Bills/Resolutions", // Bills/Resolutions
													"Book,? (Section|Chapter)"             =>  "Book Chapter", // Book, Section
													"Book, Edited"                         =>  "Book Whole", // Book, Edited (#fallback#)
													"Book, Whole"                          =>  "Book Whole", // Book, Whole
													"Case\/Court Decisions"                =>  "Unsupported: Case/Court Decisions", // Case/Court Decisions
													"Computer Program"                     =>  "Software", // Computer Program
													"Conference Proceeding"                =>  "Conference Article", // Conference Proceeding
													"Dissertation(\/Thesis)?"              =>  "Thesis", // Dissertation/Thesis (function 'parseRecords()' will set the special type 'Thesis' back to 'Book Whole' and adopt the refbase 'thesis' field)
													"Dissertation(\/Thesis)?, Unpublished" =>  "Thesis", // Dissertation/Thesis, Unpublished (#fallback#) (function 'parseRecords()' will set the special type 'Thesis' back to 'Book Whole' and adopt the refbase 'thesis' field)
													"Generic"                              =>  "Miscellaneous", // Generic
													"Grant"                                =>  "Unsupported: Grant", // Grant
													"Hearing"                              =>  "Unsupported: Hearing", // Hearing
													"Journal"                              =>  "Journal Article", // Journal
													"Journal, Electronic"                  =>  "Journal Article", // Journal, Electronic (#fallback#) (function 'parseRecords()' should set the 'online_publication' field accordingly)
													"Laws\/Statutes"                       =>  "Unsupported: Laws/Statutes", // Laws/Statutes
													"Magazine Article"                     =>  "Magazine Article", // Magazine Article
													"Map"                                  =>  "Map", // Map
													"Monograph"                            =>  "Book Whole", // Monograph (#fallback#)
													"Motion Picture"                       =>  "Unsupported: Motion Picture", // Motion Picture
													"Music Score"                          =>  "Unsupported: Music Score", // Music Score
													"Newspaper Article"                    =>  "Newspaper Article", // Newspaper Article
													"Online Discussion Forum"              =>  "Unsupported: Online Discussion Forum", // Online Discussion Forum
													"Patent"                               =>  "Patent", // Patent
													"Personal Communication"               =>  "Unsupported: Personal Communication", // Personal Communication
													"Report"                               =>  "Report", // Report
													"Sound Recording"                      =>  "Unsupported: Sound Recording", // Sound Recording
													"Thesis(\/Dissertation)?"              =>  "Thesis", // Dissertation/Thesis (function 'parseRecords()' will set the special type 'Thesis' back to 'Book Whole' and adopt the refbase 'thesis' field)
													"Unpublished Material"                 =>  "Manuscript", // Unpublished Material (#fallback#)
													"Video\/DVD"                           =>  "Unsupported: Video/DVD", // Video/DVD
													"Web Page"                             =>  "Unsupported: Web Page" // Web Page
												);

		// -----------------------------------------

		// Split input text into individual records:
		$recordArray = splitSourceText($sourceText, $recordDelimiter, false); // split on the "ER" (= end of record) tag that terminates every RefWorks record

		// Validate all records that shall be imported:
		list($errors, $importRecordNumbersRecognizedFormatArray, $importRecordNumbersNotRecognizedFormatArray) = validateRecords($recordArray, $requiredTagsArray, $importRecordsRadio, $importRecordNumbersArray, $errors);

		// Parse all records that shall be imported:
		list($parsedRecordsArray, $recordsCount) = parseRecords($recordArray, "RefWorks", $importRecordNumbersRecognizedFormatArray, $tagsToRefbaseFieldsArray, $tagsMultipleArray, $referenceTypesToRefbaseTypesArray, $fieldDelimiter, $dataDelimiter, $personDelimiter, $familyNameGivenNameDelimiter, $familyNameFirst, $shortenGivenNames, $transformCase, $postprocessorActionsArray, $preprocessorActionsArray);

		// Build refbase import array:
		$importDataArray = buildImportArray("refbase", // 'type' - the array format of the 'records' element
											"1.0", // 'version' - the version of the given array structure
											"http://refbase.net/import/refworks/", // 'creator' - the name of the script/importer (preferably given as unique URI)
											"Matthias Steffens", // 'author' - author/contact name of the person who's responsible for this script/importer
											"refbase@extracts.de", // 'contact' - author's email/contact address
											array('prefix_call_number' => "true"), // 'options' - array with settings that control the behaviour of the 'addRecords()' function
											$parsedRecordsArray); // 'records' - array of record(s) (with each record being a sub-array of fields)


		return array($importDataArray, $recordsCount, $importRecordNumbersRecognizedFormatArray, $importRecordNumbersNotRecognizedFormatArray, $errors);
	}

	// --------------------------------------------------------------------

	// SCIFINDER TO REFBASE
	// This function converts records from SciFinder (<http://www.cas.org/SCIFINDER/>) Tagged Format
	// into the standard "refbase" array format which can be then imported by the 'addRecords()' function
	// in 'include.inc.php'.
	function scifinderToRefbase($sourceText, $importRecordsRadio, $importRecordNumbersArray)
	{
		global $alnum, $alpha, $cntrl, $digit, $graph, $lower, $print, $punct, $space, $upper, $word, $patternModifiers; // defined in 'transtab_unicode_charset.inc.php' and 'transtab_latin1_charset.inc.php'

		global $errors;
		global $showSource;

		// The SciFinder format uses variable-length field label names, which makes it
		// impossible to match field labels using regular expressions with perl-style
		// look-behinds (such as '(?<=...)'). This poses a problem when specifying an
		// appropriate regex pattern for variable '$dataDelimiter'. Therefore, we'll
		// preprocess the '$sourceText' so that delimiters between field labels and
		// field data can be easily matched.
		$sourceText = preg_replace("/^(FIELD [^:\r\n]+):/m", "\\1__dataDelimiter__", $sourceText); // replace the first colon (":"), which separates a field label from its data, with a custom string ("__dataDelimiter__")

		// Define regular expression patterns that will facilitate parsing of SciFinder data:
		// (patterns must be specified as perl-style regular expression, without the leading & trailing slashes, if not stated otherwise)

		// Pattern by which the input text will be split into individual records:
		$recordDelimiter = "\s*(START_RECORD[\r\n]+|[\r\n]+END_RECORD)\s*";

		// Pattern by which records will be split into individual fields:
		$fieldDelimiter = "[\r\n]+FIELD *";

		// Pattern by which fields will be split into their field label (tag) and field data:
		$dataDelimiter = " *__dataDelimiter__ *";

		// Pattern by which multiple persons are separated within the author, editor or series editor fields of the source data:
		// (Note: name standardization occurs after multiple author fields have been merged by '; ')
		$personDelimiter = " *; *";

		// Pattern by which a person's family name is separated from the given name (or initials):
		$familyNameGivenNameDelimiter = " *, *";

		// Specifies whether the person's family name comes first within a person's name
		// ('true' means that the family name is followed by the given name (or initials), 'false' means that the person's family name comes *after* the given name (or initials))
		$familyNameFirst = true;

		// Specifies whether a person's full given name(s) shall be shortened to initial(s):
		// (Notes: - if set to 'true', given names will be abbreviated and initials will get normalized (meaning removal of extra whitespace, adding of dots between initials, etc)
		//         - if set to 'false', given names (and any initials) are taken as is
		//         - in your database, you should stick to either fully written given names OR initials; if you mix these, records won't get sorted correctly on citation output)
		$shortenGivenNames = true;

		// Specifies whether fields whose contents are entirely in upper case shall be transformed to title case ('true') or not ('false'):
		$transformCase = true;

		// Preprocessor actions:
		// Defines search & replace 'actions' that will be applied to each record's raw source data if the pattern in the corresponding 'match' element is matched:
		// (If you don't want to perform any preprocessor actions, specify an empty array, like: '$preprocessorActionsArray = array();'.
		//  Note that, in this case, the search patterns MUST include the leading & trailing slashes -- which is done to allow for mode modifiers such as 'imsxU'.)
		// 								  "/Search Pattern/"  =>  "Replace Pattern"
		$preprocessorActionsArray = array();

		// Postprocessor actions:
		// Defines search & replace 'actions' that will be applied to all those refbase fields that are listed in the corresponding 'fields' element:
		// (If you don't want to perform any search and replace actions, specify an empty array, like: '$postprocessorActionsArray = array();'.
		//  Note that, in this case, the search patterns MUST include the leading & trailing slashes -- which is done to allow for mode modifiers such as 'imsxU'.)
		// 								  "/Search Pattern/"  =>  "Replace Pattern"
		$postprocessorActionsArray = array(
											array(
													'fields'  => array("year"),
													'actions' => array(
																		"/^.*?(\d{4}).*/" =>  "\\1", // for the 'year' field, extract any four-digit number (and discard everything else)
																		"/^\D+$/" =>  "" // clear the 'year' field if it doesn't contain any number
																	)
												),
											array(
													'fields'  => array("pages"),
													'actions' => array(
																		"/(\d+ *pp?)\./" =>  "\\1" // strip any trailing dots from "xx pp." or "xx p." in the 'pages' field
																	)
												),
											array(
													'fields'  => array("title", "address"),
													'actions' => array(
																		"/[,.;:!] *$/" =>  "", // remove any punctuation (except for question marks) from end of field contents
																		"/,(?! )/" =>  ", " // add a space after a comma if missing (this mainly regards the 'Corporate Source' -> 'address' field)
																	)
												),
											array(
													'fields'  => array("abstract"),
													'actions' => array(
																		'/\\\\"/' =>  '"', // convert escaped quotes (\") into unescaped quotes (")
																		"/ *\[on SciFinder \(R\)\]$/" =>  "" // remove attribution string " [on SciFinder (R)]" from end of field contents
																	)
												),
											array(
													'fields'  => array("language"),
													'actions' => array(
																		"/^[$lower$punct ]+(?=[$upper][$lower]+)/$patternModifiers" =>  "", // remove any all-lowercase prefix string (so that field contents such as "written in English." get reduced to "English.")
																		"/language unavailable/" =>  "", // remove "language unavailable" string
																		"/[$punct] *$/$patternModifiers" =>  "" // remove any punctuation from end of field contents
																	)
												),
											array(
													'fields'  => array("notes"),
													'actions' => array(
																		"/^Can (\d+)/"  =>  "CAN:\\1", // convert any existing "CAN " prefix in front of any number that's at the beginning of the 'notes' field (which originated from the SciFinder 'Chemical Abstracts Number(CAN)' field)
																		"/^(\d+)/"  =>  "CAN:\\1" // insert a "CAN:" prefix in front of any number that's at the beginning of the 'notes' field (we map the SciFinder 'Chemical Abstracts Number(CAN)' field to the 'notes' field)
																	)
												)
										);


		// This array lists patterns which match all SciFinder tags that must occur within a record to be recognized as valid SciFinder record:
		// (Array keys must contain the tag name as it should be displayed to the user; as is the case with search & replace actions,
		//  the search patterns MUST include the leading & trailing slashes.)
		// 				"tag display name"  =>  "tag search pattern"
		$requiredTagsArray = array(
									"Document Type"  =>  "/^FIELD Document Type/m"
								);

		// This array matches SciFinder tags with their corresponding refbase fields:
		// (fields that are unsupported in either SciFinder or refbase are commented out)
		// 													  "SciFinder tag" => "refbase field" // SciFinder tag name (comment)
		$tagsToRefbaseFieldsArray = array(
											"Document Type"                   =>  "type", // Document Type (IMPORTANT: the array element that maps to 'type' must be listed as the first element!)
		//									""                                =>  "thesis",

											"Author"                          =>  "author", // Primary Authors
		//									""                                =>  "editor", // Secondary Authors (Editors)
		//									""                                =>  "series_editor", // Tertiary Authors (Series Editors)
											"Corporate Source"                =>  "address", // Corporate Source
		//									""                                =>  "corporate_author", // Corporate Author

											"Title"                           =>  "title", // Primary Title
		//									""                                =>  "orig_title", // Original Foreign Title

											"Publication Year"                =>  "year", // Publication Year
											"Publication Date"                =>  "year", // Publication Date

											"Journal Title"                   =>  "publication", // Periodical name: full format
		//									""                                =>  "abbrev_journal", // Periodical name: standard abbreviation
		//									""                                =>  array("Book, Section" => "publication", "Other" => "series_title"), // Secondary Title
		//									""                                =>  "abbrev_series_title", // Tertiary Title

											"Volume"                          =>  "volume", // Volume
											"Issue"                           =>  "issue", // Issue
											"Page"                            =>  "pages", // Page

		//									""                                =>  "series_volume", // (for 'series_volume' and 'series_issue', some magic will be applied within the 'parseRecords()' function)
		//									""                                =>  "series_issue",

		//									""                                =>  "publisher", // Publisher
		//									""                                =>  "place", // Place of Publication

		//									""                                =>  "edition", // Edition
		//									""                                =>  "medium", // Medium
											"Internat.Standard Doc. Number"   =>  array("Book, Section" => "isbn", "Book, Edited" => "isbn", "Book" => "isbn", "Dissertation" => "isbn", "Dissertation/Thesis" => "isbn", "Other" => "issn"), // Book Whole & Book Chapter: ISBN; Other reference types: ISSN

											"Language"                        =>  "language", // Language
		//									""                                =>  "summary_language", // Summary Language

											"Index Terms"                     =>  "keywords", // Index Terms
		//									"Index Terms(2)"                  =>  "keywords", // Index Terms(2)
											"Abstract"                        =>  "abstract", // Abstract

		//									""                                =>  "area",
		//									""                                =>  "expedition",
		//									""                                =>  "conference",

		//									""                                =>  "doi", // Digital Object Identifier
											"URL"                             =>  "url", // URL
		//									""                                =>  "file", // Link to PDF
		//									""                                =>  "related", // Related Records (NOTE: import into user-specific fields is NOT supported yet!)

		//									""                                =>  "call_number", // Call Number
											"Chemical Abstracts Number(CAN)"  =>  "notes", // Chemical Abstracts Number(CAN)

		//									""                                =>  "contribution_id",
		//									""                                =>  "online_publication",
		//									""                                =>  "online_citation",
		//									""                                =>  "approved",
		//									""                                =>  "orig_record",

		//									""                                =>  "copy", // Reprint status (NOTE: import into user-specific fields is NOT supported yet!)

		//									"Copyright"                       =>  "", // Copyright
		//									"Database"                        =>  "", // Database
		//									"Accession Number"                =>  "", // Accession Number
		//									"Section Code"                    =>  "", // Section Code
		//									"Section Title"                   =>  "", // Section Title
		//									"CA Section Cross-references"     =>  "", // CA Section Cross-references
		//									"CODEN"                           =>  "", // CODEN
		//									"CAS Registry Numbers"            =>  "", // CAS Registry Numbers
		//									"Supplementary Terms"             =>  "", // Supplementary Terms
		//									"PCT Designated States"           =>  "", // PCT Designated States
		//									"PCT Reg. Des. States"            =>  "", // PCT Reg. Des. States
		//									"Reg.Pat.Tr.Des.States"           =>  "", // Reg.Pat.Tr.Des.States
		//									"Main IPC"                        =>  "", // Main IPC
		//									"IPC"                             =>  "", // IPC
		//									"Secondary IPC"                   =>  "", // Secondary IPC
		//									"Additional IPC"                  =>  "", // Additional IPC
		//									"Index IPC"                       =>  "", // Index IPC
		//									"Inventor Name"                   =>  "", // Inventor Name
		//									"National Patent Classification"  =>  "", // National Patent Classification
		//									"Patent Application Country"      =>  "", // Patent Application Country
		//									"Patent Application Date"         =>  "", // Patent Application Date
		//									"Patent Application Number"       =>  "", // Patent Application Number
		//									"Patent Assignee"                 =>  "", // Patent Assignee
		//									"Patent Country"                  =>  "", // Patent Country
		//									"Patent Kind Code"                =>  "", // Patent Kind Code
		//									"Patent Number"                   =>  "", // Patent Number
		//									"Priority Application Country"    =>  "", // Priority Application Country
		//									"Priority Application Number"     =>  "", // Priority Application Number
		//									"Priority Application Date"       =>  "", // Priority Application Date
		//									"Citations"                       =>  "", // Citations
										);

		// This array lists all SciFinder tags that may occur multiple times:
		$tagsMultipleArray = array(
		//							"Chemical Abstracts Number(CAN)",
		//							"Index Terms", // by allowing "Index Terms" and "Index Terms(2)" to occur multiple times we can merge contents of both of these fields into the 'keywords' field
		//							"Index Terms(2)",
									"Publication Year", // by allowing "Publication Year" and "Publication Date" to occur multiple times we can merge contents of both of these fields into the 'year' field (then, we'll extract the first four-digit number from it)
									"Publication Date"
								);


		// This array matches SciFinder reference types with their corresponding refbase types:
		// (SciFinder types that are currently not supported in refbase will be taken as is but will get
		//  prefixed with an "Unsupported: " label; '#fallback#' in comments indicates a type mapping that
		//  is not a perfect match but as close as currently possible)
		// (NOTE: the commented reference types are NOT from SciFinder but are remains from the 'refworksToRefbase()' function!)
		// 																 "SciFinder type"  =>  "refbase type" // name of SciFinder reference type (comment)
		$referenceTypesToRefbaseTypesArray = array(
		//											"Abstract"                             =>  "Abstract", // Abstract
		//											"Artwork"                              =>  "Unsupported: Artwork", // Artwork
		//											"Bills\/Resolutions"                   =>  "Unsupported: Bills/Resolutions", // Bills/Resolutions
		//											"Book,? (Section|Chapter)"             =>  "Book Chapter", // Book, Section
		//											"Book, Edited"                         =>  "Book Whole", // Book, Edited (#fallback#)
													"Book(;.*)?"                           =>  "Book Whole", // Book
		//											"Case\/Court Decisions"                =>  "Unsupported: Case/Court Decisions", // Case/Court Decisions
		//											"Computer Program"                     =>  "Software", // Computer Program
		//											"Conference Proceeding"                =>  "Conference Article", // Conference Proceeding
		//											"Dissertation(\/Thesis)?"              =>  "Thesis", // Dissertation/Thesis (function 'parseRecords()' will set the special type 'Thesis' back to 'Book Whole' and adopt the refbase 'thesis' field)
		//											"Dissertation(\/Thesis)?, Unpublished" =>  "Thesis", // Dissertation/Thesis, Unpublished (#fallback#) (function 'parseRecords()' will set the special type 'Thesis' back to 'Book Whole' and adopt the refbase 'thesis' field)
		//											"Generic"                              =>  "Miscellaneous", // Generic
		//											"Grant"                                =>  "Unsupported: Grant", // Grant
		//											"Hearing"                              =>  "Unsupported: Hearing", // Hearing
													"Journal(;.*)?"                        =>  "Journal Article", // Journal
		//											"Journal, Electronic"                  =>  "Journal Article", // Journal, Electronic (#fallback#) (function 'parseRecords()' should set the 'online_publication' field accordingly)
		//											"Laws\/Statutes"                       =>  "Unsupported: Laws/Statutes", // Laws/Statutes
		//											"Magazine Article"                     =>  "Magazine Article", // Magazine Article
		//											"Map"                                  =>  "Map", // Map
		//											"Monograph"                            =>  "Book Whole", // Monograph (#fallback#)
		//											"Motion Picture"                       =>  "Unsupported: Motion Picture", // Motion Picture
		//											"Music Score"                          =>  "Unsupported: Music Score", // Music Score
		//											"Newspaper Article"                    =>  "Newspaper Article", // Newspaper Article
		//											"Online Discussion Forum"              =>  "Unsupported: Online Discussion Forum", // Online Discussion Forum
		//											"Patent"                               =>  "Patent", // Patent
		//											"Personal Communication"               =>  "Unsupported: Personal Communication", // Personal Communication
													"Report(;.*)?"                         =>  "Report", // Report
		//											"Sound Recording"                      =>  "Unsupported: Sound Recording", // Sound Recording
		//											"Thesis(\/Dissertation)?"              =>  "Thesis", // Dissertation/Thesis (function 'parseRecords()' will set the special type 'Thesis' back to 'Book Whole' and adopt the refbase 'thesis' field)
													"Preprint"                             =>  "Manuscript", // Preprint (#fallback#)
		//											"Video\/DVD"                           =>  "Unsupported: Video/DVD", // Video/DVD
		//											"Web Page"                             =>  "Unsupported: Web Page" // Web Page
												);

		// Other SciFinder Document Types which I've encountered so far:

		//											"General Review"                       =>  "" // General Review
		//											"Online Computer File"                 =>  "" // Online Computer File

		// -----------------------------------------

		// Split input text into individual records:
		$recordArray = splitSourceText($sourceText, $recordDelimiter, false); // split on the "START_RECORD"/"END_RECORD" tags that delimit every SciFinder record

		// Validate all records that shall be imported:
		list($errors, $importRecordNumbersRecognizedFormatArray, $importRecordNumbersNotRecognizedFormatArray) = validateRecords($recordArray, $requiredTagsArray, $importRecordsRadio, $importRecordNumbersArray, $errors);

		// Parse all records that shall be imported:
		list($parsedRecordsArray, $recordsCount) = parseRecords($recordArray, "SciFinder", $importRecordNumbersRecognizedFormatArray, $tagsToRefbaseFieldsArray, $tagsMultipleArray, $referenceTypesToRefbaseTypesArray, $fieldDelimiter, $dataDelimiter, $personDelimiter, $familyNameGivenNameDelimiter, $familyNameFirst, $shortenGivenNames, $transformCase, $postprocessorActionsArray, $preprocessorActionsArray);

		// Build refbase import array:
		$importDataArray = buildImportArray("refbase", // 'type' - the array format of the 'records' element
											"1.0", // 'version' - the version of the given array structure
											"http://refbase.net/import/scifinder/", // 'creator' - the name of the script/importer (preferably given as unique URI)
											"Matthias Steffens", // 'author' - author/contact name of the person who's responsible for this script/importer
											"refbase@extracts.de", // 'contact' - author's email/contact address
											array('prefix_call_number' => "true"), // 'options' - array with settings that control the behaviour of the 'addRecords()' function
											$parsedRecordsArray); // 'records' - array of record(s) (with each record being a sub-array of fields)


		return array($importDataArray, $recordsCount, $importRecordNumbersRecognizedFormatArray, $importRecordNumbersNotRecognizedFormatArray, $errors);
	}

	// --------------------------------------------------------------------

	// IDENTIFY SOURCE FORMAT
	// This function tries to identify the format of the input text:
	function identifySourceFormat($sourceText)
	{
		$sourceFormat = "";

		// CSA format:
		if (preg_match("/^Record \d+ of \d+/m", $sourceText) AND preg_match("/^SO: Source *[\r\n]+ {4,4}/m", $sourceText)) // CSA records must at least start with a record identifier ("Record x of xx") and contain the "SO: Source" tag
			$sourceFormat = "CSA";

		// PubMed MEDLINE format:
		elseif (preg_match("/^PMID- /m", $sourceText) AND preg_match("/^PT  - /m", $sourceText)) // PubMed MEDLINE records must at least contain the "PMID" and "PT" tags
			$sourceFormat = "Pubmed Medline";

		// PubMed XML format:
		elseif (preg_match("/<PubmedArticle[^<>\r\n]*>/i", $sourceText) AND preg_match("/<\/PubmedArticle>/", $sourceText)) // PubMed XML records must at least contain the "<PubmedArticle>...</PubmedArticle>" root element
			$sourceFormat = "Pubmed XML";

		// ISI Web of Science format:
		elseif (preg_match("/^PT /m", $sourceText) AND preg_match("/^SO /m", $sourceText) AND preg_match("/^ER *[\r\n]/m", $sourceText)) // ISI records must at least contain the "PT" and "SO" tags and end with an "ER" tag
			$sourceFormat = "ISI";

		// RIS format:
		elseif (preg_match("/^TY  - /m", $sourceText) AND preg_match("/^ER  -/m", $sourceText)) // RIS records must at least start with the "TY" tag and end with an "ER" tag (we'll only check for their presence, though)
			$sourceFormat = "RIS";

		// RefWorks format:
		elseif (preg_match("/^RT /m", $sourceText)) // RefWorks records must at least start with the "RT" tag (we'll only check for its presence, though)
			$sourceFormat = "RefWorks";

		// SciFinder format:
		elseif (preg_match("/^START_RECORD/m", $sourceText) AND preg_match("/^END_RECORD/m", $sourceText)) // SciFinder records must at least start with the "START_RECORD" tag and end with an "END_RECORD" tag (we'll only check for their presence, though)
			$sourceFormat = "SciFinder";

		// Copac format:
		elseif (preg_match("/^TI- /m", $sourceText) AND preg_match("/^HL- /m", $sourceText)) // Copac records must at least contain the "TI" and "HL" tags
			$sourceFormat = "Copac";

		// Endnote format:
		elseif (preg_match("/^%0 /m", $sourceText) AND preg_match("/^%T /m", $sourceText)) // Endnote records must at least start with the "%0" tag (we'll only check for presence, though) and contain a "%T" tag
			$sourceFormat = "Endnote";

		// MODS XML format:
		elseif (preg_match("/<mods[^<>\r\n]*>/i", $sourceText) AND preg_match("/<\/mods>/", $sourceText)) // MODS XML records must at least contain the "<mods>...</mods>" root element
			$sourceFormat = "MODS XML";

		// Endnote XML format:
		elseif (preg_match("/<xml>[^<>]*?<records>[^<>]*?<record>/mi", $sourceText)) // Endnote XML records must at least contain the elements "<xml>...<records>...<record>"
			$sourceFormat = "Endnote XML";

		// BibTeX format:
		elseif (preg_match("/^@\w+\{[^ ,\r\n]* *, *[\r\n]/m", $sourceText)) // BibTeX records must start with the "@" sign, followed by a type specifier and an optional cite key (such as in '@article{steffens1988,')
			$sourceFormat = "BibTeX";

		// CrossRef "unixref" XML format:
		// TODO: improve match
		elseif (preg_match("/<doi_records[^<>\r\n]*>/i", $sourceText) AND preg_match("/<\/doi_records>/", $sourceText)) // CrossRef XML records must at least contain the "<doi_records>...</doi_records>" root element
			$sourceFormat = "CrossRef XML";

		// arXiv.org Atom XML OpenSearch format:
		// TODO: add regex pattern that matches arXiv.org Atom feeds

		return $sourceFormat;
	}

	// --------------------------------------------------------------------

	// IDENTIFY SOURCE ID
	// This function tries to identify the type of the IDs contained in the input string:
	// TODO:
	// - modify the code so that '$sourceIDs' can contain a mixture of any supported IDs
	// - after splitting on whitespace, verify ALL items and check whether they match one of the recognized ID patterns
	// - better identification/verification of OpenURLs
	// - to support OpenURL context objects from COinS or Atom XML, we need to decode ampersand characters ('&amp;' -> '&'),
	//   and allow for OpenURLs that don't start with '?' or '&'
	function identifySourceID($sourceIDs)
	{
		$idFormat = "";

		// DOIs:
		if (preg_match("#(?<=^|\s)(doi:|http://dx\.doi\.org/)?10\.\d{4}/\S+?(?=$|\s)#i", $sourceIDs))
			$idFormat = "CrossRef XML";

		// OpenURLs:
		elseif (preg_match("#(?<=^|\s)(openurl:|http://.+?(?=\?))?.*?(?<=[?&])ctx_ver=Z39\.88-2004(?=&|$).*?(?=$|\s)#i", $sourceIDs)) // OpenURLs must contain the 'ctx_ver=Z39.88-2004' key/value pair
			$idFormat = "CrossRef XML";

		// arXiv IDs:
		elseif (preg_match("#(?<=^|\s)(arXiv:|http://arxiv\.org/abs/)?([\w.-]+/\d{7}|\d{4}\.\d{4,})(v\d+)?(?=$|\s)#i", $sourceIDs))
			$idFormat = "arXiv XML";

		// PubMed IDs:
		elseif (preg_match("/(?<=^|\s)\d+(?=$|\s)/", $sourceIDs))
			$idFormat = "Pubmed Medline";

		return $idFormat;
	}

	// --------------------------------------------------------------------

	// SPLIT SOURCE TEXT
	// This function splits the input text at the specified delimiter and returns an array of records:
	function splitSourceText($sourceText, $splitPattern, $returnEmptyElements)
	{
		if ($returnEmptyElements) // include empty elements:
			$recordArray = preg_split("/" . $splitPattern . "/", $sourceText);
		else // omit empty elements:
			$recordArray = preg_split("/" . $splitPattern . "/", $sourceText, -1, PREG_SPLIT_NO_EMPTY); // the 'PREG_SPLIT_NO_EMPTY' flag causes only non-empty pieces to be returned

		return $recordArray;
	}

	// --------------------------------------------------------------------

	// VALIDATE RECORDS
	// This function takes an array of records containing the source data (as tagged text) and
	// checks for each record if any of the required fields (given in '$requiredTagsArray') are missing:
	function validateRecords($recordArray, $requiredTagsArray, $importRecordsRadio, $importRecordNumbersArray, $errors)
	{
		// count how many records are available:
		$recordsCount = count($recordArray);

		$importRecordNumbersRecognizedFormatArray = array(); // initialize array variable which will hold all record numbers of those records that shall be imported AND which were of a recognized format
		$importRecordNumbersNotRecognizedFormatArray = array(); // same for all records that shall be imported BUT which had an UNrecognized format

		for ($i=0; $i<$recordsCount; $i++) // for each record...
		{
			if (($importRecordsRadio == "only") AND (!in_array(($i+1), $importRecordNumbersArray))) // if we're NOT supposed to import this record... ('$i' starts with 0 so we have to add 1 to point to the correct record number)
			{
				continue; // process next record (if any)
			}
			else // ...validate the format of the current record:
			{
				$missingTagsArray = array();

				// check for required fields:
				if (!empty($recordArray[$i]))
					foreach ($requiredTagsArray as $requiredTagName => $requiredTagPattern)
						if (!preg_match($requiredTagPattern, $recordArray[$i])) // if required field is missing
							$missingTagsArray[] = $requiredTagName;

				// we assume a single record as valid if the '$recordArray[$i]' variable is not empty
				// and if all tag search patterns in '$requiredTagsArray' were matched:
				if (!empty($recordArray[$i]) AND empty($missingTagsArray))
				{
					$importRecordNumbersRecognizedFormatArray[] = $i + 1; // append this record number to the list of numbers whose record format IS recognized ('$i' starts with 0 so we have to add 1 to point to the correct record number)
				}
				else // unrecognized record format
				{
					$importRecordNumbersNotRecognizedFormatArray[] = $i + 1; // append this record number to the list of numbers whose record format is NOT recognized

					// prepare an appropriate error message:
					$errorMessage = "Record " . ($i + 1) . ":";

					// Handle PubMed Medline errors:
					// TODO: - improve identification of Medline errors
					//       - handle PubMed XML
					if (eregi("^\s*<html>", $recordArray[$i]) AND ereg("Error occurred:", $recordArray[$i])) // a PubMed error occurred, probably because an unrecognized PubMed ID was given
						$errorMessage .= preg_replace("/.*Error occurred: *([^<>]+).*/s", " PubMed error: \\1.", $recordArray[$i]); // attempt to extract PubMed error message
					else
					{
						$errorMessage .= " Unrecognized data format!";

						if (!empty($missingTagsArray)) // some required fields were missing
						{
							if (count($missingTagsArray) == 1) // one field missing
								$errorMessage .= " Required field missing: " . $missingTagsArray[0];
							else // several fields missing
								$errorMessage .= " Required fields missing: " . implode(', ', $missingTagsArray);
						}
					}

					if (!isset($errors["sourceText"]))
						$errors["sourceText"] = $errorMessage;
					else
						$errors["sourceText"] = $errors["sourceText"] . "<br>" . $errorMessage;
				}
			}
		}

		return array($errors, $importRecordNumbersRecognizedFormatArray, $importRecordNumbersNotRecognizedFormatArray);
	}

	// --------------------------------------------------------------------

	// PARSE RECORDS
	// This function processes an array of records containing the source data (as tagged text) and
	// returns an array of records where each record contains an array of extracted field data:
	function parseRecords($recordArray, $recordFormat, $importRecordNumbersRecognizedFormatArray, $tagsToRefbaseFieldsArray, $tagsMultipleArray, $referenceTypesToRefbaseTypesArray, $fieldDelimiter, $dataDelimiter, $personDelimiter, $familyNameGivenNameDelimiter, $familyNameFirst, $shortenGivenNames, $transformCase, $postprocessorActionsArray, $preprocessorActionsArray)
	{
		global $alnum, $alpha, $cntrl, $digit, $graph, $lower, $print, $punct, $space, $upper, $word, $patternModifiers; // defined in 'transtab_unicode_charset.inc.php' and 'transtab_latin1_charset.inc.php'

		global $showSource;

		$parsedRecordsArray = array(); // initialize array variable which will hold parsed data of all records that shall be imported

		$recordsCount = count($recordArray); // count how many records are available

		// LOOP OVER EACH RECORD:
		for ($i=0; $i<$recordsCount; $i++) // for each record...
		{
			// if we're NOT supposed to import this record (because it was either not selected by the user -OR- because it did contain an unrecognized data format)
			if (!in_array(($i+1), $importRecordNumbersRecognizedFormatArray)) // '$i' starts with 0 so we have to add 1 to point to the correct record number
			{
				continue; // process next record (if any)
			}
			else // ...import the current record:
			{
				// PRE-PROCESS FIELD DATA:
				// apply search & replace 'actions' to each record's raw source data:
				foreach ($preprocessorActionsArray as $thisMatchActionsArray)
					if (preg_match($thisMatchActionsArray['match'], $recordArray[$i]))
						$recordArray[$i] = searchReplaceText($thisMatchActionsArray['actions'], $recordArray[$i], true); // function 'searchReplaceText()' is defined in 'include.inc.php'

				// split each record into its fields:
				$fieldArray = preg_split("/" . $fieldDelimiter . "/", $recordArray[$i]);

				// initialize some variables:
				$fieldParametersArray = array(); // setup an empty array (it will hold all fields that were extracted for a given record)
				$tagContentsMultipleArray = array(); // this array will hold individual items of tags that can occur multiple times


				// LOOP OVER EACH FIELD:
				foreach ($fieldArray as $singleField) // for each field within the current record...
				{
					// split each field into its tag and its field data:
					list($fieldLabel, $fieldData) = preg_split("/" . $dataDelimiter . "/", $singleField);

					if (isset($tagsToRefbaseFieldsArray[$fieldLabel])) // if the current tag is one we'd like to import
					{
						$fieldData = preg_replace("/\s{2,}/", " ", $fieldData); // remove any hard returns and extra spaces within the data string
						$fieldData = trim($fieldData); // remove any preceeding and trailing whitespace from the field data

						// if all of the field data is in uppercase letters, we attempt to convert the string to something more readable:
						// NOTE: while case transformation is also done in function 'standardizeFieldData()', we cannot omit it here
						//       since tags that can occur multiple times must be treated individually (i.e. before merging them)
						if ($transformCase AND ($tagsToRefbaseFieldsArray[$fieldLabel] != "type")) // we exclude reference types from any case transformations
							// TODO: we should probably only use Unicode-aware expressions here (i.e. something like "/^([$upper$digit]|[^$word])+$/$patternModifiers")
							if (preg_match("/^[$upper\W\d]+$/$patternModifiers", $fieldData))
								// convert upper case to title case (converts e.g. "ELSEVIER SCIENCE BV" into "Elsevier Science Bv"):
								// (note that this case transformation won't do the right thing for author initials and abbreviations,
								//  but the result is better than the whole string being upper case, IMHO)
								$fieldData = changeCase('title', $fieldData); // function 'changeCase()' is defined in 'include.inc.php'

						// extract individual items of tags that can occur multiple times:
						foreach ($tagsMultipleArray as $tagMultiple)
						{
							if (eregi("^" . $tagMultiple . "$", $fieldLabel))
								$tagContentsMultipleArray[$tagsToRefbaseFieldsArray[$fieldLabel]][] = $fieldData;
						}

						// copy field data to array of field parameters (using the corresponding refbase field name as element key):
						if(!is_array($tagsToRefbaseFieldsArray[$fieldLabel]))
						{
							$fieldParametersArray[$tagsToRefbaseFieldsArray[$fieldLabel]] = $fieldData;
						}
						else // if the current tag's value in '$tagsToRefbaseFieldsArray' is an array...
						{
							// ...we'll copy field data to different refbase fields depending on the current records reference type:
							// (note that this will only work if the array element that maps to 'type' has been already parsed,
							//  which is why '$tagsToRefbaseFieldsArray' should contain this as the first element!)
							$useDefault = true;

							foreach ($tagsToRefbaseFieldsArray[$fieldLabel] as $referenceType => $refbaseField)
								if ($fieldParametersArray['type'] == $referenceType)
								{
									$fieldParametersArray[$refbaseField] = $fieldData;
									$useDefault = false;
									break;
								}

							if ($useDefault AND isset($tagsToRefbaseFieldsArray[$fieldLabel]['Other']))
								$fieldParametersArray[$tagsToRefbaseFieldsArray[$fieldLabel]['Other']] = $fieldData;
						}
					}
				}
				// (END LOOP OVER EACH FIELD)


				// POST-PROCESS FIELD DATA:

				if (empty($showSource) AND isset($fieldParametersArray['source'])) // if we're NOT supposed to display the original source data
					unset($fieldParametersArray['source']); // remove the special 'source' field from the array of fields

				// convert format-specific reference types into refbase format:
				// (e.g. for the RIS format, convert "JOUR" into "Journal Article", etc)
				if (isset($fieldParametersArray['type']))
					$fieldParametersArray['type'] = searchReplaceText($referenceTypesToRefbaseTypesArray, $fieldParametersArray['type'], false); // function 'searchReplaceText()' is defined in 'include.inc.php'

				// merge individual items of fields that can occur multiple times:
				foreach ($tagsMultipleArray as $tagMultiple)
				{
					if (isset($tagContentsMultipleArray[$tagsToRefbaseFieldsArray[$tagMultiple]]))
						$fieldParametersArray[$tagsToRefbaseFieldsArray[$tagMultiple]] = implode("; ", $tagContentsMultipleArray[$tagsToRefbaseFieldsArray[$tagMultiple]]);
				}

				// standardize field data contained in '$fieldParametersArray':
				// (function 'standardizeFieldData()' e.g. performs case transformation, standardizes thesis names, normalizes page ranges, and reformats person names according to preference)
				$fieldParametersArray = standardizeFieldData($fieldParametersArray, $recordFormat, $personDelimiter, $familyNameGivenNameDelimiter, $familyNameFirst, $shortenGivenNames, $transformCase, $postprocessorActionsArray);

				// append the array of extracted field data to the main data array which holds all records to import:
				$parsedRecordsArray[] = $fieldParametersArray;
			}
		}
		// (END LOOP OVER EACH RECORD)

		return array($parsedRecordsArray, $recordsCount);
	}

	// --------------------------------------------------------------------

	// STANDARDIZE FIELD DATA
	// This function standardizes field data contained in '$fieldParametersArray':
	// (e.g. performs case transformation, standardizes thesis names, normalizes page ranges, and reformats person names according to preference)
	function standardizeFieldData($fieldParametersArray, $recordFormat, $personDelimiter, $familyNameGivenNameDelimiter, $familyNameFirst, $shortenGivenNames, $transformCase, $postprocessorActionsArray)
	{
		global $alnum, $alpha, $cntrl, $digit, $graph, $lower, $print, $punct, $space, $upper, $word, $patternModifiers; // defined in 'transtab_unicode_charset.inc.php' and 'transtab_latin1_charset.inc.php'

		if (!empty($fieldParametersArray))
		{
			// perform case transformation:
			// NOTE: this case transformation is kinda redundant if the record data were passed thru function 'parseRecords()' before,
			//       but we include it here since this function is also called individually (e.g. by function 'crossrefToRefbase()')
			foreach ($fieldParametersArray as $fieldKey => $fieldData) // for each field within the current record...
			{
				// if all of the field data is in uppercase letters, we attempt to convert the string to something more readable:
				if ($transformCase AND (!ereg("^(type|issn|url|doi)$", $fieldKey))) // we exclude ISSN & DOI numbers, as well as URLs and reference types from any case transformations
					// TODO: as above, we should probably only use Unicode-aware expressions here (i.e. something like "/^([$upper$digit]|[^$word])+$/$patternModifiers")
					if (preg_match("/^[$upper\W\d]+$/$patternModifiers", $fieldData))
						// convert upper case to title case (converts e.g. "ELSEVIER SCIENCE BV" into "Elsevier Science Bv"):
						// (note that this case transformation won't do the right thing for author initials and abbreviations,
						//  but the result is better than the whole string being upper case, IMHO)
						$fieldParametersArray[$fieldKey] = changeCase('title', $fieldData); // function 'changeCase()' is defined in 'include.inc.php'
			}

			if (ereg("Thesis", $fieldParametersArray['type']))
			{
				$fieldParametersArray['type'] = "Book Whole";

				// standardize thesis names:
				if (isset($fieldParametersArray['thesis']))
				{
					if (eregi("^Master'?s thesis$", $fieldParametersArray['thesis']))
						$fieldParametersArray['thesis'] = "Master's thesis";
					elseif (eregi("^Bachelor'?s thesis$", $fieldParametersArray['thesis']))
						$fieldParametersArray['thesis'] = "Bachelor's thesis";
					elseif (eregi("^(Diploma thesis|Diplom(arbeit)?)$", $fieldParametersArray['thesis']))
						$fieldParametersArray['thesis'] = "Diploma thesis";
					elseif (eregi("^(Doctoral thesis|Dissertation|Doktor(arbeit)?)$", $fieldParametersArray['thesis']))
						$fieldParametersArray['thesis'] = "Doctoral thesis";
					elseif (eregi("^Habilitation( thesis)?$", $fieldParametersArray['thesis']))
						$fieldParametersArray['thesis'] = "Habilitation thesis";
					else // if an unknown thesis name was given
						$fieldParametersArray['thesis'] = "Ph.D. thesis"; // NOTE: this fallback may actually be not correct!
				}
				else // if no thesis info was given
					$fieldParametersArray['thesis'] = "Ph.D. thesis"; // NOTE: this fallback may actually be not correct!
			}

			// merge contents of the special fields 'startPage' and 'endPage' into a range and copy it to the 'pages' field:
			// (these special fields will be then removed again from the '$fieldParametersArray' since they aren't valid refbase field names)
			if (isset($fieldParametersArray['startPage']) OR isset($fieldParametersArray['endPage']))
			{
				$pages = array();

				if (isset($fieldParametersArray['startPage']))
				{
					if (!empty($fieldParametersArray['startPage']))
						$pages[] = $fieldParametersArray['startPage'];

					unset($fieldParametersArray['startPage']);
				}

				if (isset($fieldParametersArray['endPage']))
				{
					if (!empty($fieldParametersArray['endPage']))
						$pages[] = $fieldParametersArray['endPage'];

					unset($fieldParametersArray['endPage']);
				}

				if (!empty($pages))
					$fieldParametersArray['pages'] = implode("-", $pages);

				if (ereg("Book Whole", $fieldParametersArray['type']) AND preg_match("/^\d+$/", $fieldParametersArray['pages']))
					$fieldParametersArray['pages'] = $fieldParametersArray['pages'] . " pp"; // append "pp" identifier for whole books where the pages field contains a single number
			}

			// if the 'pages' field contains a page range, verify that the end page is actually greater than the start page:
			if (isset($fieldParametersArray['pages']) AND preg_match("/^\d+\D*-\D*\d+$/", $fieldParametersArray['pages']))
			{
				list($startPage, $endPage) = preg_split("/\D*-\D*/", $fieldParametersArray['pages']);

				$countStartPage = strlen($startPage);
				$countEndPage = strlen($endPage);

				if(($countStartPage > $countEndPage) AND ($startPage > $endPage))
				{
					$startPagePart = preg_replace("/^.*?(\d{" . $countEndPage . "})$/", "\\1", $startPage);
					if ($startPagePart < $endPage)
						$fieldParametersArray['pages'] = $startPage . "-" . ($startPage + ($endPage - $startPagePart)); // convert page ranges such as '673-6' or '673-85' to '673-676' or '673-685', respectively
				}
			}

			// standardize contents of the 'author', 'editor' and 'series_editor' fields:
			if (!empty($fieldParametersArray['author']) OR !empty($fieldParametersArray['editor']) OR !empty($fieldParametersArray['series_editor']))
			{
				$namesArray = array();

				if (!empty($fieldParametersArray['author']))
					$namesArray['author'] = $fieldParametersArray['author'];

				if (!empty($fieldParametersArray['editor']))
					$namesArray['editor'] = $fieldParametersArray['editor'];

				if (!empty($fieldParametersArray['series_editor']))
					$namesArray['series_editor'] = $fieldParametersArray['series_editor'];

				if (!empty($namesArray))
					foreach ($namesArray as $nameKey => $nameString)
						$fieldParametersArray[$nameKey] = standardizePersonNames($nameString, $familyNameFirst, $personDelimiter, $familyNameGivenNameDelimiter, $shortenGivenNames);
			}

			// if the 'author' field is empty BUT the 'editor' field is not empty AND the record type is either a container item or a self-contained/independent item (such as 'Book Whole', 'Journal', 'Manuscript' or 'Map'):
			if (empty($fieldParametersArray['author']) AND !empty($fieldParametersArray['editor']) AND ereg("^(Book Whole|Conference Volume|Journal|Manual|Manuscript|Map|Miscellaneous|Patent|Report|Software)$", $fieldParametersArray['type']))
			{
				$fieldParametersArray['author'] = $fieldParametersArray['editor']; // duplicate field contents from 'editor' to 'author' field

				if (!ereg(";", $fieldParametersArray['author'])) // if the 'author' field does NOT contain a ';' (which would delimit multiple authors) => single author
					$fieldParametersArray['author'] .= " (ed)"; // append " (ed)" to the end of the 'author' string
				else // the 'author' field does contain at least one ';' => multiple authors
					$fieldParametersArray['author'] .= " (eds)"; // append " (eds)" to the end of the 'author' string
			}

			// if some (full or abbreviated) series title was given, we assume that the information given in 'volume'/'issue' is actually the 'series_volume'/'series_issue':
			if (!empty($fieldParametersArray['series_title']) OR !empty($fieldParametersArray['abbrev_series_title']))
			{
				if (!empty($fieldParametersArray['volume']) AND empty($fieldParametersArray['series_volume'])) // move 'volume' to 'series_volume'
				{
					$fieldParametersArray['series_volume'] = $fieldParametersArray['volume'];
					unset($fieldParametersArray['volume']);
				}

				if (!empty($fieldParametersArray['issue']) AND empty($fieldParametersArray['series_issue'])) // move 'issue' to 'series_issue'
				{
					$fieldParametersArray['series_issue'] = $fieldParametersArray['issue'];
					unset($fieldParametersArray['issue']);
				}
			}

			// if the 'url' field actually contains a DOI prefixed with "http://dx.doi.org/" (AND the 'doi' field is empty), we'll extract the DOI and move it to the 'doi' field:
			if (!empty($fieldParametersArray['url']) AND empty($fieldParametersArray['doi']) AND preg_match("#(?<=^|; )http://dx\.doi\.org/10\.\d{4}/\S+?(?=$|; )#", $fieldParametersArray['url']))
			{
				$fieldParametersArray['doi'] = preg_replace("#(?:.+?; )?http://dx\.doi\.org/(10\.\d{4}/\S+?)(?=$|; ).*#", "\\1", $fieldParametersArray['url']); // extract DOI to 'doi' field
				$fieldParametersArray['url'] = preg_replace("#^http://dx\.doi\.org/10\.\d{4}/\S+?(?=$|; )(; )?#", "", $fieldParametersArray['url']); // remove DOI URL from beginning of 'url' field
				$fieldParametersArray['url'] = preg_replace("#(; )?http://dx\.doi\.org/10\.\d{4}/\S+?(?=$|; )#", "", $fieldParametersArray['url']); // remove DOI URL from middle (or end) of 'url' field

				if (empty($fieldParametersArray['url'])) // the DOI URL was the only URL given
					unset($fieldParametersArray['url']);
			}

			if (!empty($fieldParametersArray['url'])) // besides any DOI URL, some other URL(s) were given
				$fieldParametersArray['url'] = preg_replace("/^([^ ]+?)(?=$|; ).*/", "\\1", $fieldParametersArray['url']); // remove everything but the first URL from the 'url' field (currently, refbase does only support one URL per record)

			// standardize format of ISSN number:
			if (!empty($fieldParametersArray['issn']) AND preg_match("/^ *\d{4}\D*\d{4} *$/", $fieldParametersArray['issn']))
			{
				$fieldParametersArray['issn'] = preg_replace("/^ *(\d{4})\D*(\d{4}) *$/", "\\1-\\2", $fieldParametersArray['issn']);
			}

			// apply search & replace 'actions' to all fields that are listed in the 'fields' element of the arrays contained in '$postprocessorActionsArray':
			foreach ($postprocessorActionsArray as $fieldActionsArray)
				foreach ($fieldParametersArray as $fieldName => $fieldValue)
					if (in_array($fieldName, $fieldActionsArray['fields']))
						$fieldParametersArray[$fieldName] = searchReplaceText($fieldActionsArray['actions'], $fieldValue, true); // function 'searchReplaceText()' is defined in 'include.inc.php'

			// if (except for a DOI) no other URL(s) are given AND the 'notes' field contains a PubMed ID, we extract the
			// PubMed ID and copy a resolvable URL (that points to the PubMed article's abstract page) to the 'url' field:
			if (!isset($fieldParametersArray['url']) AND preg_match("/PMID *: *\d+/i", $fieldParametersArray['notes']))
				$fieldParametersArray['url'] = "http://www.ncbi.nlm.nih.gov/pubmed/" . preg_replace("/.*?PMID *: *(\d+).*/i", "\\1", $fieldParametersArray['notes']);
		}

		return $fieldParametersArray;
	}

	// --------------------------------------------------------------------

	// STANDARDIZE PERSON NAMES
	// This function is currently a wrapper for the 'reArrangeAuthorContents()' function that is used by several import routines.
	// The function standardizes the contents of the 'author', 'editor' and 'series_editor' fields and features removal of
	// extra whitespace, re-arranging of family and given names, abbreviation of given names, adding of dots between initials, etc.
	function standardizePersonNames($nameString, $familyNameFirst, $personDelimiter, $familyNameGivenNameDelimiter, $shortenGivenNames)
	{
		// Call the 'reArrangeAuthorContents()' function (defined in 'include.inc.php') in order to re-order contents of the 'author', 'editor' or 'series_editor' field. Required Parameters:
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
		$reorderedNameString = reArrangeAuthorContents($nameString, // 1.
														$familyNameFirst, // 2.
														$personDelimiter, // 3.
														"; ", // 4.
														"; ", // 5.
														$familyNameGivenNameDelimiter, // 6.
														", ", // 7.
														", ", // 8.
														".", // 9.
														false, // 10.
														false, // 11.
														$shortenGivenNames, // 12.
														"", // 13.
														"", // 14.
														"", // 15.
														false); // 16.

		return $reorderedNameString;
	}

	// --------------------------------------------------------------------

	// BUILD IMPORT ARRAY
	// This function builds an array structure that can be passed to the 'addRecords()' function for import:
	// (for a more detailed explanation of the required array structure, see the comments above the
	//  'addRecords()' function in 'include.inc.php')
	function buildImportArray($type, $version, $creator, $author, $contact, $options, $parsedRecordsArray)
	{
		$importDataArray = array();
		$importDataArray['type'] = $type; // the array format of the 'records' element
		$importDataArray['version'] = $version; // the version of the given array structure
		$importDataArray['creator'] = $creator; // the name of the script/importer (preferably given as unique URI)
		$importDataArray['author'] = $author; // author/contact name of the person who's responsible for this script/importer
		$importDataArray['contact'] = $contact; // author's email/contact address
		$importDataArray['options'] = $options; // array with settings that control the behaviour of the 'addRecords()' function
		$importDataArray['records'] = $parsedRecordsArray; // array of record(s) (with each record being a sub-array of fields)

		// NOTES:
		//   - the 'addRecords()' function will take care of the calculation fields ('first_author', 'author_count', 'first_page',
		//     'volume_numeric' and 'series_volume_numeric')
		//
		//   - similarly, the *date/*time/*by fields ('created_date', 'created_time', 'created_by', 'modified_date', 'modified_time' and
		//     'modified_by') will be filled automatically if no custom values (in correct date ['YYYY-MM-DD'] and time ['HH:MM:SS'] format)
		//     are given in the '$importDataArray'
		//
		//   - we could pass any custom info for the 'location' field with the '$importDataArray', omitting it here
		//     causes the 'addRecords()' function to insert name & email address of the currently logged-in user
		//     (e.g. 'Matthias Steffens (refbase@extracts.de)')
		//
		//   - if the 'prefix_call_number' element of the 'options' array is set to "true", any 'call_number' string will be prefixed with
		//     the correct call number prefix of the currently logged-in user (e.g. 'IPÖ @ msteffens @ ')
		//
		//   - the serial number(s) will be assigned automatically and returned by the 'addRecords()' function in form of an array
		//
		//   - currently, it is not possible to add anything to the 'user_data' table

		return $importDataArray;
	}

	// --------------------------------------------------------------------

	// This function takes a BibTeX source and converts any contained
	// LaTeX/BibTeX markup into proper refbase markup:
	function standardizeBibtexInput($bibtexSourceText)
	{
		global $contentTypeCharset; // defined in 'ini.inc.php'

		// The array '$transtab_bibtex_refbase' contains search & replace patterns for conversion from LaTeX/BibTeX markup & entities to refbase markup.
		// Converts LaTeX fontshape markup (italic, bold) into appropriate refbase commands, super- and subscript as well as greek letters in math mode
		// get converted into the respective refbase commands. You may need to adopt the LaTeX markup to suit your individual needs.
		global $transtab_bibtex_refbase; // defined in 'transtab_bibtex_refbase.inc.php'

		// The arrays '$transtab_latex_latin1' and '$transtab_latex_unicode' provide translation tables for best-effort conversion of higher ASCII
		// characters from LaTeX markup to ISO-8859-1 entities (or Unicode, respectively).
		global $transtab_latex_latin1; // defined in 'transtab_latex_latin1.inc.php'
		global $transtab_latex_unicode; // defined in 'transtab_latex_unicode.inc.php'

		// Perform search & replace actions on the given BibTeX text:
		$bibtexSourceText = searchReplaceText($transtab_bibtex_refbase, $bibtexSourceText, true); // function 'searchReplaceText()' is defined in 'include.inc.php'

		// Attempt to convert LaTeX markup for higher ASCII chars to their corresponding ISO-8859-1/Unicode entities:
		if ($contentTypeCharset == "UTF-8")
			$bibtexSourceText = searchReplaceText($transtab_latex_unicode, $bibtexSourceText, false);
		else
			$bibtexSourceText = searchReplaceText($transtab_latex_latin1, $bibtexSourceText, false);

		return $bibtexSourceText;
	}

	// --------------------------------------------------------------------

	// This function takes an Endnote XML source and converts any contained
	// text style markup into proper refbase markup:
	function standardizeEndnoteXMLInput($endxSourceText)
	{
		// The array '$transtab_endnotexml_refbase' contains search & replace patterns for conversion from Endnote XML text style markup to refbase markup.
		// It attempts to convert fontshape markup (italic, bold)  as well as super- and subscript into appropriate refbase markup.
		global $transtab_endnotexml_refbase; // defined in 'transtab_endnotexml_refbase.inc.php'

		// Perform search & replace actions on the given Endnote XML source text:
		$endxSourceText = searchReplaceText($transtab_endnotexml_refbase, $endxSourceText, true); // function 'searchReplaceText()' is defined in 'include.inc.php'

		return $endxSourceText;
	}

	// --------------------------------------------------------------------

	// This function fetches source data from PubMed.gov for all PubMed IDs
	// given in '$pmidArray':
	// ('$sourceFormat' must be either "Pubmed Medline" or "Pubmed XML";
	//  more info on the Entrez Programming Utilities:
	//  <http://eutils.ncbi.nlm.nih.gov/entrez/query/static/eutils_help.html>)
	function fetchDataFromPubMed($pmidArray, $sourceFormat = "Pubmed Medline")
	{
		global $errors;

		$sourceText = "";

		if (!empty($pmidArray))
		{
			// Remove any duplicate PubMed IDs:
			$pmidArray = array_unique($pmidArray);

			// Define response format:
			if (eregi("^Pubmed XML$", $sourceFormat))
				$fetchType = "xml";
			else // by default, we'll use the "Pubmed Medline" format
				$fetchType = "text";

			// NOTE:
			// When querying PubMed for multiple PubMed IDs *at once*, errors are not
			// returned inline on a per-record basis. If one or more of the given
			// PubMed IDs are invalid, PubMed returns a single error message, if the
			// first given PubMed ID is invalid, otherwise it returns records until
			// the first invalid ID is encountered. In any case, the remaining records
			// seem to get omitted from the PubMed response.
			// To work around this, we'll query PubMed for each given PubMed ID
			// *individually* (similar to function 'fetchDataFromCrossRef()'), and we
			// then perform the record validation (i.e. error checking) in function
			// 'validateRecords()'.
			// See below for alternative code that fetches PubMed records via a single
			// HTTP request.
			foreach ($pmidArray as $pmid)
			{
				// Build query URL:
				$sourceURL = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi"
				           . "?db=pubmed"
				           . "&retmode=" . $fetchType
				           . "&rettype=medline"
				           . "&tool=refbase"
				           . "&email=" . rawurlencode("info@refbase.net")
				           . "&id=" . $pmid;

				// Perform query:
				$sourceText .= fetchDataFromURL($sourceURL);
			}

			// Alternative code that fetches PubMed records via a single HTTP request:
			// (while this may be more efficient, it prevents us from checking errors
			//  on a per-record level -- see note above)

//			// Merge PubMed IDs with commas:
//			$sourceIDs = implode(",", $pmidArray);
//
//			// Build query URL:
//			$sourceURL = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi"
//			           . "?db=pubmed"
//			           . "&retmode=" . $fetchType
//			           . "&rettype=medline"
//			           . "&tool=refbase"
//			           . "&email=" . rawurlencode("info@refbase.net")
//			           . "&id=" . $sourceIDs;
//
//			// Perform query:
//			$sourceText = fetchDataFromURL($sourceURL);
//
//			// Handle errors:
//			if (!preg_match("/^PMID- /m", $sourceText) AND ereg("Error occurred:", $sourceText)) // a PubMed error occurred, probably because only unrecognized PubMed IDs were given; TODO: handle PubMed XML
//				$errors["sourceText"] = preg_replace("/.*Error occurred: *([^<>]+).*/s", "PubMed error: \\1", $sourceText); // attempt to extract PubMed error message
		}

		return array($errors, $sourceText);
	}

	// --------------------------------------------------------------------

	// This function fetches record metadata from arXiv.org for all arXiv IDs
	// given in '$itemArray':
	// (for '$sourceFormat', only "arXiv XML", i.e. the arXiv.org Atom XML OpenSearch format,
	//  is currently supported; more info on the arXiv API:
	//  <http://export.arxiv.org/api_help/>
	//  <http://export.arxiv.org/api_help/docs/user-manual.html>
	// 
	// Requires the SimplePie library (by Ryan Parman and Geoffrey Sneddon), which is
	// available under the BSD license from: <http://simplepie.org>
	function fetchDataFromArXiv($itemArray, $sourceFormat = "arXiv XML")
	{
		global $errors; // NOTE: ATM, error checking is done in function 'arxivToRefbase()'

		$sourceURLArray = array();

		if (!empty($itemArray))
		{
			// Remove any duplicate IDs:
			$itemArray = array_unique($itemArray);

			// NOTE:
			// When querying arXiv.org for multiple arXiv IDs *at once*, errors are not
			// returned inline on a per-record basis. If one or more of the given
			// arXiv IDs are invalid, arXiv.org returns a *single* error message, and
			// any other requested records seem to get omitted from the arXiv response.
			// To work around this, we'll query arXiv.org for each given arXiv ID
			// *individually*, and we then perform the record validation (i.e. error
			// checking) in function 'arxivToRefbase()'.
			foreach ($itemArray as $item)
			{
//				if (preg_match("#(arXiv:|http://arxiv\.org/abs/)?([\w.-]+/\d{7}|\d{4}\.\d{4,})(v\d+)?#i", $item)) // '$item' is an arXiv ID
//				{
					// Build query URL:
					$sourceURLArray[] = "http://export.arxiv.org/api/query"
					                  . "?id_list=" . rawurlencode($item);
//				}
			}

			// Perform query:
			$feed = new SimplePie(); // setup new SimplePie constructor
			$feed->set_feed_url($sourceURLArray); // setup multi-feed request
			$feed->set_input_encoding('UTF-8'); // force UTF-8 as input encoding
			$feed->enable_cache(false); // disable caching
			$feed->enable_order_by_date(false); // disable automatic sorting of entries by date
			$feed->init(); // process options, fetch feeds, cache, parse, merge, etc
		}

		return array($errors, $feed);
	}

	// --------------------------------------------------------------------

	// This function tries to fetch record metadata from CrossRef.org for all DOIs or
	// OpenURLs given in '$itemArray':
	// (for '$sourceFormat', only "CrossRef XML", i.e. the CrossRef "unixref XML" format,
	//  is currently supported; more info on the CrossRef OpenURL resolver/metadata server:
	//  <http://www.crossref.org/openurl>
	//  see also: <http://hublog.hubmed.org/archives/001624.html>)
	function fetchDataFromCrossRef($itemArray, $sourceFormat = "CrossRef XML")
	{
		global $errors;

		//  Notes: - the CrossRef resolver requires users to have an account and to supply their login
		//           credentials in the 'pid' or 'req_dat' parameters; for more info please see:
		//           <http://www.crossref.org/openurl_info.html>
		//         - to request an account, complete the form at: <http://www.crossref.org/requestaccount/>
		$crossRefReqDat = ""; // please enter your 'pid'/'req_dat' ("username:password") here // TODO: move to 'ini.inc.php'

		$sourceText = "";

		if (!empty($itemArray))
		{
			// Remove any duplicate IDs:
			$itemArray = array_unique($itemArray);

			// Define response format:
//			if (eregi("^CrossRef XML$", $sourceFormat))
//				$fetchType = "unixref";
//			else // by default, we'll use the "unixref XML" format
				$fetchType = "unixref";

			foreach ($itemArray as $item)
			{
				// Build query URL:
				$sourceURL = "http://www.crossref.org/openurl/"
						   . "?noredirect=true"
						   . "&format=" . $fetchType;

				if (!empty($crossRefReqDat))
					$sourceURL .= "&pid=" . $crossRefReqDat;

				if (preg_match("#^10\.\d{4}/\S+$#", $item)) // '$item' is a DOI
					$sourceURL .= "&id=" . rawurlencode("doi:" . $item);
				else // otherwise we assume a full OpenURL context object // TODO: verify OpenURL!?
					$sourceURL .= "&" . $item;

				// Perform query:
				$sourceText .= fetchDataFromURL($sourceURL);
			}
		}

		return array($errors, $sourceText);
	}

	// --------------------------------------------------------------------

	// This function takes the URL given in '$sourceURL' and retrieves the returned data:
	function fetchDataFromURL($sourceURL)
	{
		$handle = fopen($sourceURL, "r"); // fetch data from URL in read mode

		$sourceData = "";

		if ($handle)
		{
			while (!feof($handle))
			{
				$sourceData .= fread($handle, 4096); // read data in chunks
			}
			fclose($handle);
		}
		else
			$sourceData = "Error occurred: Failed to open " . $sourceURL; // network error

		return $sourceData;
	}

	// --------------------------------------------------------------------

	// CSA TO REFBASE
	// This function converts records from Cambridge Scientific Abstracts (CSA) into the standard "refbase"
	// array format which can be then imported by the 'addRecords()' function in 'include.inc.php'.
	function csaToRefbase($sourceText, $importRecordsRadio, $importRecordNumbersArray)
	{
		global $alnum, $alpha, $cntrl, $digit, $graph, $lower, $print, $punct, $space, $upper, $word, $patternModifiers; // defined in 'transtab_unicode_charset.inc.php' and 'transtab_latin1_charset.inc.php'

		global $errors;
		global $showSource;

		// Defines the pattern by which the input text will be split into individual records:
		$recordDelimiter = "\s*Record \d+ of \d+\s*";

		// PRE-PROCESS SOURCE TEXT:

		// Split input text into individual records:
		$recordArray = splitSourceText($sourceText, $recordDelimiter, false); // split input text on the header text preceeding each CSA record (e.g. "\nRecord 4 of 52\n")

		// Count how many records are available:
		$recordsCount = count($recordArray);

		// ----------------------------------------------------------------

		// VALIDATE INDIVIDUAL RECORDS:

		// Note that source data must begin with "\nRecord x of xx\n" and that (opposed to the handling in 'import_csa_modify.php') any text preceeding the source data isn't removed but treated as the first record!

		// This array lists patterns which match all CSA tags that must occur within a record to be recognized as valid CSA record:
		// (Array keys must contain the tag name as it should be displayed to the user; as is the case with search & replace actions,
		//  the search patterns MUST include the leading & trailing slashes.)
		//	 							"tag display name" =>  "tag search pattern"
		$requiredTagsArray = array(
									"title"                =>  "/^TI: Title *[\r\n]+ {4,4}/m",
									"author (or editor)"   =>  "/^(AU: Author|ED: Editor) *[\r\n]+ {4,4}/m",
									"source"               =>  "/^SO: Source *[\r\n]+ {4,4}/m" // since the "SO: Source" is also specified as format requirement in function 'identifySourceFormat()' records without "SO: Source" won't be recognized anyhow
								);

		// Validate all records that shall be imported:
		list($errors, $importRecordNumbersRecognizedFormatArray, $importRecordNumbersNotRecognizedFormatArray) = validateRecords($recordArray, $requiredTagsArray, $importRecordsRadio, $importRecordNumbersArray, $errors);

		// ----------------------------------------------------------------

		// PROCESS SOURCE DATA:

		$parsedRecordsArray = array(); // initialize array variable which will hold parsed data of all records that shall be imported

		// LOOP OVER EACH RECORD:
		for ($i=0; $i<$recordsCount; $i++) // for each record...
		{
			// if we're NOT supposed to import this record (because it was either not selected by the user -OR- because it did contain an unrecognized data format)
			if (!in_array(($i+1), $importRecordNumbersRecognizedFormatArray)) // '$i' starts with 0 so we have to add 1 to point to the correct record number
			{
				continue; // process next record (if any)
			}
			else // ...import the current record:
			{
				$singleRecord = $recordArray[$i];

				// if the "AU: Author" field is missing BUT the "ED: Editor" is present (which is allowed for book monographs):
				// we replace the "ED: Editor" field identifier with "AU: Author" (this will keep any " (ed)" and " (eds)" tags in place which, in turn, will cause the "is Editor" checkbox in 'record.php' to get marked)
				if (!preg_match("/^AU: Author *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/^ED: Editor *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord))
					$singleRecord = preg_replace("/^ED: Editor(?= *[\r\n]+ {4,4})/m", "AU: Author", $singleRecord);

				// split each record into its fields:
				$fieldArray = preg_split("/[\r\n]+(?=\w\w: )/", $singleRecord);

				// initialize some variables:
				$fieldParametersArray = array(); // setup an empty array (it will hold all fields that were extracted for a given record)
				$additionalDocumentTypeInfo = ""; // will be used with the "PT: Publication Type" field
				$environmentalRegime = ""; // will be used with the "ER: Environmental Regime" field


				// GENERATE EXTRA FIELDS:
				// check if the fields "MT: Monograph Title", "JN: Journal Name", "JV: Journal Volume", "JI: Journal Issue" and "JP: Journal Pages" are present,
				// if not, we attempt to generate them from the "SO: Source" field:
				$sourceField = preg_replace("/.*SO: Source *[\r\n]+ {4,4}(.+?)(?=([\r\n]+\w\w: |\s*\z)).*/ms", "\\1", $singleRecord); // first, we need to extract the "SO: Source" field data from the record text
				$sourceField = preg_replace("/\s{2,}/", " ", $sourceField); // remove any hard returns and extra spaces within the source field data string

				// if the current record is of type "Book Monograph" but the field "MT: Monograph Title" is missing:
				if (preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord) AND !preg_match("/^MT: Monograph Title *[\r\n]+ {4,4}/m", $singleRecord))
				{
					$extractedSourceFieldData = preg_replace("/^([^.[]+).*/", "\\1", $sourceField); // attempt to extract the full monograph title from the source field

					if (preg_match("/^[$upper\W\d]+$/$patternModifiers", $extractedSourceFieldData)) // if all of the words within the monograph title are uppercase, we attempt to convert the string to something more readable:
						// perform case transformation (e.g. convert "BIOLOGY AND ECOLOGY OF GLACIAL RELICT CRUSTACEA" into "Biology And Ecology Of Glacial Relict Crustacea")
						$extractedSourceFieldData = changeCase('title', $extractedSourceFieldData); // function 'changeCase()' is defined in 'include.inc.php'

					$fieldArray[] = "MT: Monograph Title\r\n    " . $extractedSourceFieldData; // add field "MT: Monograph Title" to the array of fields
				}
				// else if the current record is of type "Journal Article", "Report", etc (or wasn't specified) but the field "JN: Journal Name" is missing:
				elseif (!preg_match("/^JN: Journal Name *[\r\n]+ {4,4}/m", $singleRecord)) // preg_match("/^(PT: Publication Type\s+(Journal Article|Report)|DT: Document Type\s+(J|R))/m", $singleRecord)
				{
					if (ereg("\[", $sourceField)) // if the source field data contain a square bracket we assume a format like: "Journal of Phycology [J. Phycol.]. Vol. 37, no. s3, pp. 18-18. Jun 2001."
						$extractedSourceFieldData = preg_replace("/^([^.[]+).*/", "\\1", $sourceField); // attempt to extract the full journal name from the source field
					else // source field format might be something like: "Phycologia, vol. 34, no. 2, pp. 135-144, 1995"
						$extractedSourceFieldData = preg_replace("/^([^.,]+).*/", "\\1", $sourceField); // attempt to extract the full journal name from the source field

					if (preg_match("/^[$upper\W\d]+$/$patternModifiers", $extractedSourceFieldData)) // if all of the words within the journal name are uppercase, we attempt to convert the string to something more readable:
						// perform case transformation (e.g. convert "POLAR BIOLOGY" into "Polar Biology")
						$extractedSourceFieldData = changeCase('title', $extractedSourceFieldData);

					$fieldArray[] = "JN: Journal Name\r\n    " . $extractedSourceFieldData; // add field "JN: Journal Name" to the array of fields
				}

				// if the "JV: Journal Volume" is missing BUT the "SO: Source" field contains a volume specification:
				if (!preg_match("/^JV: Journal Volume *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/(?<=\W)vol[. ]+[\w\/-]+/i", $sourceField))
				{
					$extractedSourceFieldData = preg_replace("/.*(?<=\W)vol[. ]+([\w\/-]+).*/i", "\\1", $sourceField); // attempt to extract the journal volume from the source field

					$fieldArray[] = "JV: Journal Volume\r\n    " . $extractedSourceFieldData; // add field "JV: Journal Volume" to the array of fields
				}

				// if the "JI: Journal Issue" is missing BUT the "SO: Source" field contains an issue specification:
				if (!preg_match("/^JI: Journal Issue *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/(?<=\W)no[. ]+[\w\/-]+/i", $sourceField))
				{
					$extractedSourceFieldData = preg_replace("/.*(?<=\W)no[. ]+([\w\/-]+).*/i", "\\1", $sourceField); // attempt to extract the journal issue from the source field

					$fieldArray[] = "JI: Journal Issue\r\n    " . $extractedSourceFieldData; // add field "JI: Journal Issue" to the array of fields
				}

				// if the "JP: Journal Pages" is missing BUT the "SO: Source" field contains a pages specification:
				if (!preg_match("/^JP: Journal Pages *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/((?<=\W)pp?[. ]+[\w\/,-]+|[\d,]+ *pp\b)/i", $sourceField))
				{
					if (preg_match("/(?<=\W)pp?[. ]+[\w\/,-]+/i", $sourceField)) // e.g. "pp. 212-217" or "p. 216" etc
						$extractedSourceFieldData = preg_replace("/.*(?<=\W)pp?[. ]+([\w\/,-]+).*/i", "\\1", $sourceField); // attempt to extract the journal pages from the source field
					elseif (preg_match("/[\d,]+ *pp\b/", $sourceField)) // e.g. "452 pp"
						$extractedSourceFieldData = preg_replace("/.*?([\d,]+ *pp)\b.*/i", "\\1", $sourceField); // attempt to extract the journal pages from the source field

					$extractedSourceFieldData = preg_replace("/,/", "", $extractedSourceFieldData); // remove any thousands separators from journal pages

					$fieldArray[] = "JP: Journal Pages\r\n    " . $extractedSourceFieldData; // add field "JP: Journal Pages" to the array of fields
				}


				// Additionally, we extract the abbreviated journal name from the "SO: Source" field (if available):
				if (ereg("\[", $sourceField)) // if the source field data contain a square bracket we assume a format like: "Journal of Phycology [J. Phycol.]. Vol. 37, no. s3, pp. 18-18. Jun 2001."
				{
					$extractedSourceFieldData = preg_replace("/.*\[(.+?)\].*/", "\\1", $sourceField); // attempt to extract the abbreviated journal name from the source field
					$extractedSourceFieldData = preg_replace("/\./", "", $extractedSourceFieldData); // remove any dots from the abbreviated journal name

					if (preg_match("/^[$upper\W\d]+$/$patternModifiers", $extractedSourceFieldData)) // if all of the words within the abbreviated journal name are uppercase, we attempt to convert the string to something more readable:
						// perform case transformation (e.g. convert "BALT SEA ENVIRON PROC" into "Balt Sea Environ Proc")
						$extractedSourceFieldData = changeCase('title', $extractedSourceFieldData);

					$fieldArray[] = "JA: Abbrev Journal Name\r\n    " . $extractedSourceFieldData; // add field "JA: Abbrev Journal Name" to the array of fields (note that this field normally does NOT occur within the CSA full record format!)
				}
				// (END GENERATE EXTRA FIELDS)


				// LOOP OVER EACH FIELD:
				foreach ($fieldArray as $singleField) // for each field within the current record...
				{
					$singleField = preg_replace("/^(\w\w: [^\r\n]+)[\r\n]+ {4,4}/", "\\1___LabelDataSplitter___", $singleField); // insert a unique text string between the field identifier and the field data
					$fieldLabelPlusDataArray = preg_split("/___LabelDataSplitter___/", $singleField); // split each field into a 2-element array containing [0] the field identifier and [1] the field data

					$fieldLabel = $fieldLabelPlusDataArray[0];
					$fieldData = $fieldLabelPlusDataArray[1];

					$fieldData = preg_replace("/\s{2,}/", " ", $fieldData); // remove any hard returns and extra spaces within the data string
					$fieldData = trim($fieldData); // remove any preceeding and trailing whitespace from the field data

					if (ereg("AU: Author", $fieldLabel))
					{
						$fieldData = preg_replace("/\*/", "", $fieldData); // remove any asterisk ("*")
						$fieldData = standardizePersonNames($fieldData, true, " *; *", " *, *", true); // standardize person names
					}

					elseif (ereg("ED: Editor", $fieldLabel))
					{
						$fieldData = preg_replace("/ \(eds?\)(?= *$| *;)/", "", $fieldData); // remove " (ed)" and/or " (eds)"
						$fieldData = standardizePersonNames($fieldData, true, " *; *", " *, *", true); // standardize person names
					}

					elseif (ereg("TI: Title|AB: Abstract", $fieldLabel))
					{
						if (ereg("TI: Title", $fieldLabel))
						{
							$fieldData = preg_replace("/--/", "-", $fieldData); // remove en-dash markup
							$fieldData = preg_replace("/ *\. *$/", "", $fieldData); // remove any dot from end of title
						}

						if (preg_match("/ su(b|per)\(.+?\)/", $fieldData))
							$fieldData = preg_replace("/ (su(?:b|per))\((.+?)\)/", "[\\1:\\2]", $fieldData); // transform " sub(...)" & " super(...)" markup into "[sub:...]" & "[super:...]" markup
						if (preg_match("/(?<= )mu /", $fieldData))
							$fieldData = preg_replace("/(?<= )mu /", "µ", $fieldData); // transform "mu " markup into "µ" markup
					}


					// BUILD FIELD PARAMETERS:
					// build an array of key/value pairs:

					// "AU: Author":
					if (ereg("AU: Author", $fieldLabel))
						$fieldParametersArray['author'] = $fieldData;

					// "TI: Title":
					elseif (ereg("TI: Title", $fieldLabel))
						$fieldParametersArray['title'] = $fieldData;

					// "PT: Publication Type":
					elseif (ereg("PT: Publication Type", $fieldLabel)) // could also check for "DT: Document Type" (but DT was added only recently)
					{
						if (ereg("[;:,.]", $fieldData)) // if the "PT: Publication Type" field contains a delimiter (e.g. like: "Journal Article; Conference")
						{
							$correctDocumentType = preg_replace("/(.+?)\s*[;:,.]\s*.*/", "\\1", $fieldData); // extract everything before this delimiter
							$additionalDocumentTypeInfo = preg_replace("/.*?\s*[;:,.]\s*(.+)/", "\\1", $fieldData); // extract everything after this delimiter
							$additionalDocumentTypeInfo = $additionalDocumentTypeInfo; // this info will be appended to any notes field data (see below)
						}
						else // we take the "PT: Publication Type" field contents as they are
							$correctDocumentType = $fieldData;

						// Note that for books the "PT: Publication Type" field will always start with "Book Monograph", no matter whether the referenced
						// publication is a whole book or just a book chapter within that book! This is a design flaw within the CSA full record format.
						// So we can only apply some "good guessing" whether the current record actually references a complete book or just a book chapter:
						if (preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord)) // if the current record is of type "Book Monograph"
						{
							// and if the source field contains some page specification like "213 pp." (AND NOT something like "pp. 76-82" or "p. 216")...
							if (preg_match("/[\d,]+ *pp\b/i", $sourceField) AND !preg_match("/(?<=\W)pp?[. ]+[\w\/,-]+/i", $sourceField))
								$correctDocumentType = "Book Whole"; // ...we assume its a whole book
							else
								$correctDocumentType = "Book Chapter"; // ...otherwise we assume its a book chapter (which may NOT always be correct!)
						}

						$fieldParametersArray['type'] = $correctDocumentType;
					}

					// "PY: Publication Year":
					elseif (ereg("PY: Publication Year", $fieldLabel))
						$fieldParametersArray['year'] = $fieldData;

					// "JN: Journal Name":
					elseif (ereg("JN: Journal Name", $fieldLabel))
					{
						// if the current record is of type "Book Monograph" AND the field "JN: Journal Name" was given within the *original* record data (i.e., before adding stuff to it):
						if (preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord) AND preg_match("/^JN: Journal Name *[\r\n]+ {4,4}/m", $singleRecord))
							// for book monographs the publication title is given in "MT: Monograph Title"; if a "JN: Journal Name" was originally provided as well, we assume, it's the series title:
							$fieldParametersArray['series_title'] = $fieldData;
						else
							$fieldParametersArray['publication'] = $fieldData;
					}

					// "JA: Abbrev Journal Name":
					elseif (ereg("JA: Abbrev Journal Name", $fieldLabel))
					{
						if (preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord)) // if the current record is of type "Book Monograph"
							// for book monographs the publication title is given in "MT: Monograph Title"; if a "JA: Abbrev Journal Name" is provided as well, we assume, it's the abbreviated series title:
							$fieldParametersArray['abbrev_series_title'] = $fieldData;
						else
							$fieldParametersArray['abbrev_journal'] = $fieldData;
					}

					// "MT: Monograph Title":
					elseif (ereg("MT: Monograph Title", $fieldLabel))
					{
						// if the source field contains some page specification like "213 pp." (AND NOT something like "pp. 76-82" or "p. 216")...
						if (preg_match("/[\d,]+ *pp\b/i", $sourceField) AND !preg_match("/(?<=\W)pp?[. ]+[\w\/,-]+/i", $sourceField))
							// ...we assume its a whole book (see above comment), in which case we assign the monograph title to the series title field:
							$fieldParametersArray['series_title'] = $fieldData;
						else
							$fieldParametersArray['publication'] = $fieldData;
					}

					// "JV: Journal Volume":
					elseif (ereg("JV: Journal Volume", $fieldLabel))
					{
						if (preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord)) // if the current record is of type "Book Monograph"
							// for book monographs, if there's a volume given, we assume, it's the series volume:
							$fieldParametersArray['series_volume'] = $fieldData;
						else
							$fieldParametersArray['volume'] = $fieldData;
					}

					// "JI: Journal Issue":
					elseif (ereg("JI: Journal Issue", $fieldLabel))
					{
						if (preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord)) // if the current record is of type "Book Monograph"
							// for book monographs, if there's an issue given, we assume, it's the series issue:
							$fieldParametersArray['series_issue'] = $fieldData;
						else
							$fieldParametersArray['issue'] = $fieldData;
					}

					// "JP: Journal Pages":
					elseif (ereg("JP: Journal Pages", $fieldLabel))
						$fieldParametersArray['pages'] = $fieldData;

					// "AF: Affiliation" & "AF: Author Affilition":
					elseif (ereg("AF: (Author )?Affilia?tion", $fieldLabel))
						$fieldParametersArray['address'] = $fieldData;

					// "CA: Corporate Author":
					elseif (ereg("CA: Corporate Author", $fieldLabel))
						$fieldParametersArray['corporate_author'] = $fieldData;

					// "DE: Descriptors":
					elseif (ereg("DE: Descriptors", $fieldLabel)) // currently, the fields "KW: Keywords" and "ID: Identifiers" are ignored!
						$fieldParametersArray['keywords'] = $fieldData;

					// "AB: Abstract":
					elseif (ereg("AB: Abstract", $fieldLabel))
						$fieldParametersArray['abstract'] = $fieldData;

					// "PB: Publisher":
					elseif (ereg("PB: Publisher", $fieldLabel))
					{
						if (preg_match("/^[$upper\W\d]+$/$patternModifiers", $fieldData)) // if all of the words within the publisher name are uppercase, we attempt to convert the string to something more readable:
							// perform case transformation (e.g. convert "ELSEVIER SCIENCE B.V." into "Elsevier Science B.V.")
							$fieldData = changeCase('title', $fieldData);

						$fieldParametersArray['publisher'] = $fieldData;
					}

					// "ED: Editor":
					elseif (ereg("ED: Editor", $fieldLabel))
						$fieldParametersArray['editor'] = $fieldData;

					// "LA: Language":
					elseif (ereg("LA: Language", $fieldLabel))
						$fieldParametersArray['language'] = $fieldData;

					// "SL: Summary Language":
					elseif (ereg("SL: Summary Language", $fieldLabel))
						$fieldParametersArray['summary_language'] = $fieldData;

					// "OT: Original Title":
					elseif (ereg("OT: Original Title", $fieldLabel))
						$fieldParametersArray['orig_title'] = $fieldData;

					// "IS: ISSN":
					elseif (ereg("IS: ISSN", $fieldLabel))
						$fieldParametersArray['issn'] = $fieldData;

					// "IB: ISBN":
					elseif (ereg("IB: ISBN", $fieldLabel))
						$fieldParametersArray['isbn'] = $fieldData;

					// "ER: Environmental Regime":
					elseif (ereg("ER: Environmental Regime", $fieldLabel))
						$environmentalRegime = $fieldData; // this info will be appended to any notes field data (see below)

					// "CF: Conference":
					elseif (ereg("CF: Conference", $fieldLabel))
						$fieldParametersArray['conference'] = $fieldData;

					// "NT: Notes":
					elseif (ereg("NT: Notes", $fieldLabel))
						$fieldParametersArray['notes'] = $fieldData;

					// "DO: DOI":
					elseif (ereg("DO: DOI", $fieldLabel))
						$fieldParametersArray['doi'] = $fieldData;
				}
				// (END LOOP OVER EACH FIELD)


				if (!empty($showSource)) // if we're supposed to display the original source data
					// append original source field data (they will be presented within the header message of 'record.php' for easy comparison with the extracted data):
					$fieldParametersArray['source'] = $sourceField;

				// we'll hack the "notes" element in order to append additional info:
				// (this cannot be done earlier above since we don't know about the presence & order of fields within the source text!)
				if (!empty($additionalDocumentTypeInfo)) // if the "PT: Publication Type" field contains some additional info
				{
					if (isset($fieldParametersArray['notes'])) // and if the notes element is present
						$fieldParametersArray['notes'] = $fieldParametersArray['notes'] . "; " . $additionalDocumentTypeInfo; // append additional info from "PT: Publication Type" field
					else // the notes parameter wasn't specified yet
						$fieldParametersArray['notes'] = $additionalDocumentTypeInfo; // add notes element with additional info from "PT: Publication Type" field
				}

				if (!empty($environmentalRegime)) // if the "ER: Environmental Regime" field contains some data
				{
					if (isset($fieldParametersArray['notes'])) // and if the notes element is present
						$fieldParametersArray['notes'] = $fieldParametersArray['notes'] . "; " . $environmentalRegime; // append "ER: Environmental Regime" field data
					else // the notes parameter wasn't specified yet
						$fieldParametersArray['notes'] = $environmentalRegime; // add notes element with "ER: Environmental Regime" field data
				}

				// Append the array of extracted field data to the main data array which holds all records to import:
				$parsedRecordsArray[] = $fieldParametersArray;
			}
		}
		// (END LOOP OVER EACH RECORD)

		// ----------------------------------------------------------------

		// BUILD REFBASE IMPORT ARRAY:
		$importDataArray = buildImportArray("refbase", // 'type' - the array format of the 'records' element
											"1.0", // 'version' - the version of the given array structure
											"http://refbase.net/import/csa/", // 'creator' - the name of the script/importer (preferably given as unique URI)
											"Matthias Steffens", // 'author' - author/contact name of the person who's responsible for this script/importer
											"refbase@extracts.de", // 'contact' - author's email/contact address
											array('prefix_call_number' => "true"), // 'options' - array with settings that control the behaviour of the 'addRecords()' function
											$parsedRecordsArray); // 'records' - array of record(s) (with each record being a sub-array of fields)


		return array($importDataArray, $recordsCount, $importRecordNumbersRecognizedFormatArray, $importRecordNumbersNotRecognizedFormatArray, $errors);
	}

	// --------------------------------------------------------------------

/*

	// NOTE: by default, this function is currently disabled, since it uses DOM which is part of PHP 5 but must
	// be installed as a separate PEAR extension for PHP 4. In order to provide widest compatibility with PHP 4,
	// this function should be modified so that it makes use of ActiveLink's XML package instead:
	// <http://www.active-link.com/software/>

	// PUBMED TO CSA
	// This function takes a PubMed ID and fetches corresponding PubMed XML record data from the PubMed server.
	// Record data will be converted to CSA format which can be imported via 'import_csa_modify.php'.
	// 
	// Authors: this function was originally written in Python by Andreas Hildebrandt <anhi@bioinf.uni-sb.de>
	//          and was ported to PHP by Marc Sturm <sturm@informatik.uni-tuebingen.de>
	function pubmedToCsa($pubmedID)
	{
		global $contentTypeCharset;

		$months     = array('Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04', 'May' => '05', 'Jun' => '06', 
							'Jul' => '07', 'Aug' => '08', 'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12');
		$use_proxy=false; 
		function proxy_url($proxy_url)
		{
		   $proxy_name = 'www-cache.informatik.uni-tuebingen.de';
		   $proxy_port = 3128;
		   $proxy_user = '';
		   $proxy_pass = '';
		   $proxy_cont = '';	
		   $proxy_fp = fsockopen($proxy_name, $proxy_port);
		   if (!$proxy_fp) {return false;}
		   fputs($proxy_fp, "GET $proxy_url HTTP/1.0\r\nHost: $proxy_name\r\n");
		   fputs($proxy_fp, "Proxy-Authorization: Basic " . base64_encode("$proxy_user:$proxy_pass") . "\r\n\r\n");
		   while(!feof($proxy_fp)) { $proxy_cont .= fread($proxy_fp,4096); }
		   fclose($proxy_fp);
		   $proxy_cont = substr($proxy_cont, strpos($proxy_cont,"\r\n\r\n")+4);
		   return $proxy_cont;
		}

		if ($use_proxy) 
			$file = proxy_url("http://www.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&id=".escapeshellcmd($pubmedID)."&retmode=xml");
		else
			$file = file_get_contents("http://www.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&id=".escapeshellcmd($pubmedID)."&retmode=xml");

		$doc = DOMDocument::loadXML($file);
		$doc->preserveWhiteSpace = false;
		$xpath = new DOMXPath($doc);

		//-------------------------------------------------------------------------
		//  This parses the XML data:
		//   1) Find the article (assume only one at this point...)
		//   2) Do we need to add "et.al" to Authors?
		//   3) Only one affiliation...
		//-------------------------------------------------------------------------

		$result = "";

		$articles = $doc->getElementsByTagName('PubmedArticle');
		foreach ($articles as $ref) 
		{
			$med = $ref->getElementsByTagName('MedlineCitation')->item(0);
			$article = $med->getElementsByTagName('Article')->item(0);
			$title = $xpath->query("ArticleTitle/text()", $article)->item(0)->nodeValue;
			$result .= "TI: Title\n    $title\n";	
			$author_list = $article->getElementsByTagName('AuthorList')->item(0);
			if ($author_list->attributes->getNamedItem('CompleteYN')->value == 'N')
				$add_et_al = true;
			else
				$add_et_al = false;

			$authors = $author_list->getElementsByTagName('Author');

			$author_line = "";

			foreach ($authors as $author)
			{
				$author_line .= $xpath->query("LastName/text()", $author)->item(0)->nodeValue;
				$author_line .= ", ";
				$forename = $xpath->query("ForeName/text()", $author);
				if ($forename->length == 0)
					$forename = $xpath->query("Initials/text()", $author);
				if ($forename->length > 0)	
					$author_line .= $forename->item(0)->nodeValue;
				$author_line .= "; ";
			}
			if ($add_et_al)
				$author_line = substr($author_line,0,-2) . " et al.";
			else
				$author_line = substr($author_line,0,-2);

			$result .= "AU: Author\n    $author_line\n";	

			$affiliation = $xpath->query("Affiliation/text()", $article);
			if ($affiliation->length > 0)
				$result .= "AF: Affiliation\n    ".$affiliation->item(0)->nodeValue."\n";

			if ($ref->getElementsByTagName('MedlineJournalInfo')->length == 0) {
				print "No useable source information given!";
				exit(1);
			}

			$source = $xpath->query("MedlineJournalInfo/MedlineTA/text()", $med)->item(0)->nodeValue.". ";
			if ($xpath->query("Journal/JournalIssue/Volume/text()", $article)->length > 0)
					$source .= "Vol. " . $xpath->query("Journal/JournalIssue/Volume/text()", $article)->item(0)->nodeValue;
			if ($xpath->query("Journal/JournalIssue/Issue/text()", $article)->length > 0)
					$source .= " no. " . $xpath->query("Journal/JournalIssue/Issue/text()", $article)->item(0)->nodeValue;
			if ($xpath->query("Pagination/MedlinePgn/text()", $article)->length > 0)
				$source .= ", pp. " . $xpath->query("Pagination/MedlinePgn/text()", $article)->item(0)->nodeValue;
			if ($xpath->query("Journal/JournalIssue/PubDate/Year", $article)->length > 0)
				$source .= ". " . $xpath->query("Journal/JournalIssue/PubDate/Year/text()", $article)->item(0)->nodeValue . ".";
			if ($source != "")
				$result .=  "SO: Source\n    " . $source . "\n";

			if ($xpath->query("Journal/ISSN", $article)->length > 0)
				$result .=  "IS: ISSN\n    " . $xpath->query("Journal/ISSN/text()", $article)->item(0)->nodeValue . "\n";
			if ($xpath->query("Abstract/AbstractText", $article)->length > 0)
				$result .=  "AB: Abstract\n    " . $xpath->query("Abstract/AbstractText/text()", $article)->item(0)->nodeValue . "\n";
			if ($xpath->query("Language", $article)->length > 0)
				$result .=  "LA: Language\n    " . $xpath->query("Language/text()", $article)->item(0)->nodeValue . "\n";

		$pubdate = "";
		if ($xpath->query("Journal/JournalIssue/PubDate", $article)->length > 0) 
		{
			$year = $xpath->query("Journal/JournalIssue/PubDate/Year/text()", $article);
			if ($year > 0)
			{
				$pubdate = $year->item(0)->nodeValue;
				$month = $xpath->query("Journal/JournalIssue/PubDate/Month/text()", $article);
				if ($month > 0)
				{
					$pubdate .= $months[$month->item(0)->nodeValue];
					$day = $xpath->query("Journal/JournalIssue/PubDate/Day/text()", $article);
					if ($day->length > 0)
						$pubdate .= $day->item(0)->nodeValue;
					else
						$pubdate .= "00";
				}else{
					$pubdate = $pubdate . "00";
				}
			}
			$result .=  "PD: Publication Date\n    " . $pubdate . "\n";
		}

		$ptl = $article->getElementsByTagName('PublicationTypeList');
		$publication_type = "";
		if ($ptl->length > 0)
		{
			$pts = $xpath->query("PublicationTypeList/PublicationType/text()", $article);
			for ($i=0; $i<$pts->length ; ++$i)
			//{
				$publication_type .= $pts->item($i)->nodeValue . "; ";
			//}
		}
		if ($publication_type != "")
			$result .=  "PT: Publication Type\n    " . substr($publication_type,0,-2) . "\n";

		// collect all MeshHeadings and put them as descriptors.
		// this currently ignores all other types of keywords
		$descs = $xpath->query("MeshHeadingList/MeshHeading/DescriptorName/text()", $med);
		$desc_line = "";

		for ($i=0; $i<$descs->length ; ++$i)
			$desc_line .= $descs->item($i)->nodeValue . "; ";			

		if ($desc_line != "")
			$result .=  "DE: Descriptors\n    " . substr($desc_line,0,-2) . "\n";

		$year = $xpath->query("Journal/JournalIssue/PubDate/Year/text()", $article)	;
		if ($year > 0)
			$result .=  "PY: Publication Year\n    " . $year->item(0)->nodeValue . "\n";
		}

		if ($contentTypeCharset == "ISO-8859-1")
			$result = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $result); // convert text from Unicode UTF-8 encoding to ISO Latin 1

		return $result;
	}

*/

	// --------------------------------------------------------------------
?>
