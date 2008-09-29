<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/webservice.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    04-Feb-06, 22:02
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This include file contains functions that are used in conjunction with the refbase webservices.
	// Requires ActiveLink PHP XML Package, which is available under the GPL from:
	// <http://www.active-link.com/software/>. See 'sru.php' and 'opensearch.php' for more info.


	// Import the ActiveLink Packages
	require_once("classes/include.php");
	import("org.active-link.xml.XML");
	import("org.active-link.xml.XMLDocument");

	// --------------------------------------------------------------------

	// Add a new XML branch, optionally with an attribute and tag content:
	// 
	// TODO: this function should also accept arrays to add multiple content tags
	function addNewBranch(&$thisBranch, $elementName, $elementAttributeArray, $elementContent)
	{
		$newBranch = new XMLBranch($elementName);

		if (!empty($elementAttributeArray))
			foreach ($elementAttributeArray as $elementAttributeKey => $elementAttributeValue)
				$newBranch->setTagAttribute($elementAttributeKey, $elementAttributeValue);

		if (!empty($elementContent))
			$newBranch->setTagContent($elementContent);

		$thisBranch->addXMLBranch($newBranch);
	}

	// -------------------------------------------------------------------------------------------------------------------

	// Parse CQL query:
	// This function parses a CQL query into its elements (context set, index, relation and search term(s)),
	// builds appropriate SQL search terms and returns a hierarchical array containing the converted search terms
	// (this array, in turn, gets merged into a full SQL WHERE clause by function 'appendToWhereClause()' in
	// 'include.inc.php')
	// 
	// NOTE: we don't provide a full CQL parser here but will (for now) concentrate on a rather limited feature
	//       set that makes sense in conjunction with refbase. However, future versions should employ far better
	//       CQL parsing logic.
	function parseCQL($sruVersion, $sruQuery, $operation = "")
	{
		// map CQL indexes to refbase field names:
		$indexNamesArray = mapCQLIndexes();

		$searchArray = array(); // intialize array that will hold information about context set, index name, relation and search value
		$searchSubArray1 = array();

		// --------------------------------

		if (!empty($sruQuery))
		{
			// check for presence of context set/index name and any of the main relations:
			if (!preg_match('/^[^\" <>=]+( +(all|any|exact|within) +| *(<>|<=|>=|<|>|=) *)/', $sruQuery))
				$sruQuery = "cql.serverChoice all " . $sruQuery; // if no context set/index name and relation was given we'll use 'cql.serverChoice all ' by default

			// extract the context set:
			if (preg_match('/^([^\" <>=.]+)\./', $sruQuery))
				$contextSet = preg_replace('/^([^\" <>=.]+)\..*/', '\\1', $sruQuery);
			else
				$contextSet = ""; // use the default context set

			// extract the index:
			$indexName = preg_replace('/^(?:[^\" <>=.]+\.)?([^\" <>=.]+).*/', '\\1', $sruQuery);

			// ----------------

			// return a fatal diagnostic if the CQL query does contain an unrecognized 'set.index' identifier:
			// (a) verify that the given context set (if any) is recognized:
			if (!empty($contextSet))
			{
				$contextSetIndexConnector = ".";
				$contextSetLabel = "context set '" . $contextSet . "'";

				if (!ereg("^(dc|bath|rec|bib|cql)$", $contextSet))
				{
					returnDiagnostic(15, $contextSet); // unsupported context set (function 'returnDiagnostic()' is defined in 'opensearch.php' and 'sru.php')
					exit;
				}
			}
			else
			{
				$contextSetIndexConnector = "";
				$contextSetLabel = "empty context set";
			}

			// (b) verify that the given 'set.index' term is recognized:
			if (!isset($indexNamesArray[$contextSet . $contextSetIndexConnector . $indexName]))
			{
				if (isset($indexNamesArray[$indexName]) OR isset($indexNamesArray["dc." . $indexName]) OR isset($indexNamesArray["bath." . $indexName]) OR isset($indexNamesArray["rec." . $indexName]) OR isset($indexNamesArray["bib." . $indexName]) OR isset($indexNamesArray["cql." . $indexName])) // this may be clumsy but I don't know any better, right now
				{
					returnDiagnostic(10, "Unsupported combination of " . $contextSetLabel . " with index '" . $indexName . "'"); // unsupported combination of context set & index
				}
				else
				{
					returnDiagnostic(16, $indexName); // unsupported index
				}
				exit;
			}

			// ----------------

			// extract the main relation (relation modifiers aren't supported yet!):
			$mainRelation = preg_replace('/^[^\" <>=]+( +(all|any|exact|within) +| *(<>|<=|>=|<|>|=) *).*/', '\\1', $sruQuery);
			// remove any runs of leading or trailing whitespace:
			$mainRelation = trim($mainRelation);

			// ----------------

			// extract the search term:
			$searchTerm = preg_replace('/^[^\" <>=]+(?: +(?:all|any|exact|within) +| *(?:<>|<=|>=|<|>|=) *)(.*)/', '\\1', $sruQuery);

			// remove slashes from search term if 'magic_quotes_gpc = On':
			$searchTerm = stripSlashesIfMagicQuotes($searchTerm); // function 'stripSlashesIfMagicQuotes()' is defined in 'include.inc.php'

			// remove any leading or trailing quotes from the search term:
			// (note that multiple query parts connected with boolean operators aren't supported yet!)
			$searchTerm = preg_replace('/^\"/', '', $searchTerm);
			$searchTerm = preg_replace('/\"$/', '', $searchTerm);

			// OpenSearch search suggestions ('$operation=suggest'): since CQL matches full words (not sub-strings),
			// we need to make sure that every search term ends with the '*' masking character:
			if (eregi("^suggest$", $operation) AND ($mainRelation != "exact"))
				$searchTerm = preg_replace('/(\w+)\b(?![?*^])/', '\\1*', $searchTerm);

			// escape meta characters (including '/' that is used as delimiter for the PCRE replace functions below and which gets passed as second argument):
			$searchTerm = preg_quote($searchTerm, "/"); // escape special regular expression characters: . \ + * ? [ ^ ] $ ( ) { } = ! < > | :

			// account for CQL anchoring ('^') and masking ('*' and '?') characters:
			// NOTE: in the code block above we quote everything to escape possible meta characters,
			//       so all special chars in the block below have to be matched in their escaped form!
			//       (The expression '\\\\' in the patterns below describes only *one* backslash! -> '\'.
			//        The reason for this is that before the regex engine can interpret the \\ into \, PHP interprets it.
			//        Thus, you have to escape your backslashes twice: once for PHP, and once for the regex engine.)
			// 
			// more info about masking characters in CQL:  <http://zing.z3950.org/cql/intro.html#6>
			// more info about word anchoring in CQL:      <http://zing.z3950.org/cql/intro.html#6.1>

			// recognize any anchor at the beginning of a search term (like '^foo'):
			// (in CQL, a word beginning with ^ must be the first in its field)
			$searchTerm = preg_replace('/(^| )\\\\\^/', '\\1^', $searchTerm);

			// convert any anchor at the end of a search term (like 'foo^') to the correct MySQL variant ('foo$'):
			// (in CQL, a word ending with ^ must be the last in its field)
			$searchTerm = preg_replace('/\\\\\^( |$)/', '$\\1', $searchTerm);

			// recognize any masking ('*' and '?') characters:
			// Note: by "character" we do refer to *word* characters here, i.e., any character that is not a space or punctuation character (see below);
			//       however, I'm not sure if the masking characters '*' and '?' should also include non-word characters!
			$searchTerm = preg_replace('/(?<!\\\\)\\\\\*/', '[^[:space:][:punct:]]*', $searchTerm); // a single asterisk ('*') is used to mask zero or more characters
			$searchTerm = preg_replace('/(?<!\\\\)\\\\\?/', '[^[:space:][:punct:]]', $searchTerm); // a single question mark ('?') is used to mask a single character, thus N consecutive question-marks means mask N characters

			// ----------------

			// construct the WHERE clause:
			$whereClausePart = $indexNamesArray[$contextSet . $contextSetIndexConnector . $indexName]; // start WHERE clause with field name

			if ($mainRelation == "all") // matches full words (not sub-strings); 'all' means "all of these words"
			{
				if (ereg(" ", $searchTerm))
				{
					$searchTermArray = split(" +", $searchTerm);

					foreach ($searchTermArray as $searchTermItem)
						$whereClauseSubPartsArray[] = " RLIKE " . quote_smart("(^|[[:space:][:punct:]])" . $searchTermItem . "([[:space:][:punct:]]|$)");

					// NOTE: For word-matching relations (like 'all', 'any' or '=') we could also use word boundaries which would be more (too?) restrictive:
					// 
					// [[:<:]] , [[:>:]]
					// 
					// They match the beginning and end of words, respectively. A word is a sequence of word characters that is not preceded by or
					// followed by word characters. A word character is an alphanumeric character in the alnum class or an underscore (_).

					$whereClausePart .= implode(" AND " . $indexNamesArray[$contextSet . $contextSetIndexConnector . $indexName], $whereClauseSubPartsArray);
				}
				else
					$whereClausePart .= " RLIKE " . quote_smart("(^|[[:space:][:punct:]])" . $searchTerm . "([[:space:][:punct:]]|$)");
			}

			elseif ($mainRelation == "any") // matches full words (not sub-strings); 'any' means "any of these words"
			{
				$searchTerm = splitAndMerge(" +", "|", $searchTerm); // function 'splitAndMerge()' is defined in 'include.inc.php'
				$whereClausePart .= " RLIKE " . quote_smart("(^|[[:space:][:punct:]])(" . $searchTerm . ")([[:space:][:punct:]]|$)");
			}

			elseif ($mainRelation == "exact") // 'exact' is used for exact string matching, i.e., it matches field contents exactly
				$whereClausePart .= " = " . quote_smart($searchTerm);

			elseif ($mainRelation == "within") // matches a range (i.e. requires two space-separated dimensions)
			{
				if (preg_match("/[^ ]+ [^ ]+/", $searchTerm))
				{
					$searchTermArray = split(" +", $searchTerm);

					$whereClausePart .= " >= " . quote_smart($searchTermArray[0]) . " AND " . $indexNamesArray[$contextSet . $contextSetIndexConnector . $indexName] . " <= " . quote_smart($searchTermArray[1]);
				}
				else
				{
					returnDiagnostic(36, "Search term requires two space-separated dimensions. Example: dc.date within \"2004 2005\"");
					exit;
				}
			}

			elseif ($mainRelation == "=") // matches full words (not sub-strings); '=' is used for word adjacency, the words appear in that order with no others intervening
				$whereClausePart .= " RLIKE " . quote_smart("(^|[[:space:][:punct:]])" . $searchTerm . "([[:space:][:punct:]]|$)");

			elseif ($mainRelation == "<>") // does this also match full words (and not sub-strings) ?:-/
				$whereClausePart .= " NOT RLIKE " . quote_smart("(^|[[:space:][:punct:]])" . $searchTerm . "([[:space:][:punct:]]|$)");

			elseif ($mainRelation == "<")
				$whereClausePart .= " < " . quote_smart($searchTerm);

			elseif ($mainRelation == "<=")
				$whereClausePart .= " <= " . quote_smart($searchTerm);

			elseif ($mainRelation == ">")
				$whereClausePart .= " > " . quote_smart($searchTerm);

			elseif ($mainRelation == ">=")
				$whereClausePart .= " >= " . quote_smart($searchTerm);

			$searchSubArray1[] = array("_boolean" => "",
			                           "_query"   => $whereClausePart);
		}

		// --------------------------------

		else // '$sruQuery' was empty -> return all records:
		{
				$searchSubArray1[] = array("_boolean" => "",
				                           "_query"   => "serial RLIKE " . quote_smart(".+"));
		}

		// --------------------------------

		if (!empty($searchSubArray1))
			$searchArray[] = array("_boolean" => "",
			                       "_query"   => $searchSubArray1);


		return $searchArray;
	}

	// -------------------------------------------------------------------------------------------------------------------

	// Add a metadata element to the given object:
	// As an example, the function call 'addMetaElement($object, "dc", "title", array("lang" => "en"), "this is a title")'
	// would add '<dc:title lang="en">this is a title</dc:title>' as a new branch to the given '$object'.
	// 
	// TODO: expand function so that it can be also used for formats other than XML (e.g. HTML)
	function addMetaElement(&$object, $namespace, $elementName, $elementAttributeArray, $elementContent, $elementType = "", $format = "xml")
	{
		$addStatus = false;

		if (!empty($elementName) AND !empty($elementContent))
		{
			// Preprocess element contents (if necessary):

			// - 'creator', 'contributor':
			if (ereg("^(creator|contributor)$", $elementName))
				$elementContent = getPersons($elementContent); // get an array of all creators (i.e. authors) or contributors (e.g. editors)

			// - 'identifier':
			//   NOTE: should we support any other identifiers from the "info" URI scheme?
			//         see <http://info-uri.info/registry/OAIHandler?verb=ListRecords&metadataPrefix=oai_dc>

			//   - DOI:
			elseif ($elementName == "identifier" AND $elementType == "doi")
				$elementContent = "info:doi/" . $elementContent;

			//   - PMID:
			elseif ($elementName == "identifier" AND $elementType == "pmid")
			{
				// extract any PubMed ID from the given '$elementContent':
				// NOTE: should this better be done in the calling function?
				$pubmedID = preg_replace("/.*?PMID *: *(\d+).*/i", "\\1", $elementContent);
				$elementContent = "info:pmid/" . $pubmedID;
			}

			//   - arXiv:
			elseif ($elementName == "identifier" AND $elementType == "arxiv")
			{
				// extract any arXiv ID from the given '$elementContent':
				// NOTE: see note for PMID
				$arxivID = preg_replace("/.*?arXiv *: *([^ ;]+).*/i", "\\1", $elementContent);
				$elementContent = "info:arxiv/" . $arxivID;
			}

			//   - ISBN:
			//     NOTE: we could also output the ISBN or ISSN as a value URI within a
			//           'dcterms:isPartOf' relation property, e.g.:
			//           '<dcterms:isPartOf>urn:ISSN:0740-8188</dcterms:isPartOf>'
			elseif ($elementName == "identifier" AND $elementType == "isbn")
				$elementContent = "urn:ISBN:" . $elementContent;

			//   - ISSN:
			//     NOTE: see note for ISBN above
			elseif ($elementName == "identifier" AND $elementType == "issn")
				$elementContent = "urn:ISSN:" . $elementContent;

			//   - OpenURL:
			elseif ($elementName == "identifier" AND $elementType == "openurl")
			{
				if (!ereg("^openurl:", $elementContent))
					$elementContent = "openurl:" . $elementContent; // use "openurl:" prefix if doesn't already exist in the given OpenURL
			}

			//   - URL:
			//     NOTE: the 'url:' prefix is non-standard, is there a better way to
			//           include a permanent URL for a record in Simple Dublin Core XML output?
			elseif ($elementName == "identifier" AND $elementType == "url")
				$elementContent = "url:" . $elementContent;

			//   - Cite key:
			//     NOTE: the 'citekey:' prefix is non-standard, is there a better way to
			//           include the cite key in Simple Dublin Core XML output?
			elseif ($elementName == "identifier" AND $elementType == "citekey")
				$elementContent = "citekey:" . $elementContent;

			//   - Bibliographic citation:
			//     NOTE: the 'citation:' prefix is non-standard, is there a better way to
			//           include the bibliographic citation in Simple Dublin Core XML output?
			elseif ($elementName == "identifier" AND $elementType == "citation")
				$elementContent = "citation:" . $elementContent;

			// - 'source':

			//   - Series:
			//     NOTE: the 'series:' prefix is non-standard, is there a better way to
			//           include series information in Simple Dublin Core XML output?
			elseif ($elementName == "source" AND $elementType == "series")
				$elementContent = "series:" . $elementContent;

			//   - ISSN:
			//     NOTE: see note for ISBN above
			elseif ($elementName == "source" AND $elementType == "issn")
				$elementContent = "urn:ISSN:" . $elementContent;

			// - 'relation':

			//   - URL:
			//     NOTE: the 'url:' prefix is non-standard, is there a better way to
			//           include a permanent URL for a record in Simple Dublin Core XML output?
			elseif ($elementName == "relation" AND $elementType == "url")
				$elementContent = "url:" . $elementContent;

			//   - FILE:
			//     NOTE: the 'file:' prefix is non-standard, is there a better way to
			//           include an URL to a file representing this record in Simple Dublin Core XML output?
			elseif ($elementName == "relation" AND $elementType == "file")
				$elementContent = "file:" . $elementContent;

			// - 'type':
			elseif ($elementName == "type")
			{
				if (eregi("^((Simple|oai)?[- _]?(dc|Dublin[- _]?Core)[- _]?(terms)?)$", $namespace))
				{
					// Map refbase types to the corresponding eprint/resource types suggested for Simple
					// Dublin Core (<http://eprints-uk.rdn.ac.uk/project/docs/simpledc-guidelines/#type>):
					$dcTypesArray = mapDCTypes();

					// NOTE: for '$elementName="type"', variable '$elementType' is supposed to contain the
					//       thesis type from the refbase 'thesis' field (e.g. "Ph.D. thesis")
					if (isset($dcTypesArray[$elementContent]) AND empty($elementType))
						$elementContent = $dcTypesArray[$elementContent];
					elseif (!empty($elementType))
						$elementContent = $dcTypesArray["Thesis"];
				}
			}

			// - 'subject':
			if ($elementName == "subject")
				$elementContent = preg_split("/\s*;\s*/", $elementContent, -1, PREG_SPLIT_NO_EMPTY); // get an array of all keywords

			// - 'language':
			//   TODO: convert to ISO notation (i.e. "en" instead of "English", etc)
			//         see <http://www.loc.gov/standards/iso639-2/php/code_list.php>
			if ($elementName == "language")
				$elementContent = preg_split("/\s*[;,]\s*/", $elementContent, -1, PREG_SPLIT_NO_EMPTY); // get an array of all languages


			// Prefix element name with given namespace:
			if (!empty($namespace))
				$elementName = $namespace . ":" . $elementName;

			// Add metadata element(s) to the given object:
			if (is_array($elementContent)) // add each array item as a new element:
			{
				foreach ($elementContent as $singleElement)
					addNewBranch($object, $elementName, $elementAttributeArray, $singleElement);
			}
			else // add string in '$elementContent' as a new element:
				addNewBranch($object, $elementName, $elementAttributeArray, $elementContent);


			$addStatus = true;
		}


		return $addStatus;
	}

	// --------------------------------------------------------------------

	// Split a string of person names (authors/editors) into an array:
	function getPersons($personString, $standardizePersonNames = true, $betweenNamesDelim = " *; *", $nameGivenDelim = " *, *", $newBetweenGivensDelim = ".")
	{
		if ($standardizePersonNames)
		{
			// NOTE: We standardize person names (e.g. add dots between initials if missing) in an attempt to adhere to
			//       the recommendations given at <http://eprints-uk.rdn.ac.uk/project/docs/simpledc-guidelines/#creator>
			//
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
			$personString = reArrangeAuthorContents($personString, // 1.
													true, // 2.
													$betweenNamesDelim, // 3.
													"; ", // 4.
													"; ", // 5.
													$nameGivenDelim, // 6.
													", ", // 7.
													", ", // 8.
													$newBetweenGivensDelim, // 9.
													false, // 10.
													false, // 11.
													true, // 12.
													"", // 13.
													"", // 14.
													"", // 15.
													false); // 16.

			$betweenNamesDelim = "/\s*;\s*/";
		}

		$nameArray = array();

		if (!ereg("^/.*/$", $betweenNamesDelim))
			$betweenNamesDelim = "/" . $betweenNamesDelim . "/"; // add search pattern delimiters

		$nameArray = preg_split($betweenNamesDelim, $personString, -1, PREG_SPLIT_NO_EMPTY); // get a list of all authors/editors


		return $nameArray;
	}

	// -------------------------------------------------------------------------------------------------------------------

	// Map CQL indexes to refbase field names:
	function mapCQLIndexes()
	{
		// TODO: - add support for the OAI indexes 'oai.identifier' and 'oai.datestamp'
		//       - the CQL indexes 'creationDate' and 'lastModificationDate'
		//         contain both date & time info so this needs to be parsed into two
		//         refbase fields (which isn't done yet!)
		//       - if no context set & index name are given in the query, we should search
		//         the user's preferred list of "main fields" by default! (cql.serverChoice)
		$indexNamesArray = array("dc.creator"                    => "author", // "CQL context_set.index_name"  =>  "refbase field name"
		                         "dc.title"                      => "title",
		                         "dc.date"                       => "year",
		                         "dc.language"                   => "language",
		                         "dc.description"                => "abstract",
		                         "dc.contributor"                => "editor",
		                         "dc.subject"                    => "keywords",
		                         "dc.format"                     => "medium",
		                         "dc.type"                       => "type",
		                         "dc.publisher"                  => "publisher",
		                         "dc.coverage"                   => "area",

//		                         "bath.name"                     => "author",
//		                         "bath.topicalSubject"           => "keywords",
		                         "bath.isbn"                     => "isbn",
		                         "bath.issn"                     => "issn",
		                         "bath.corporateName"            => "corporate_author",
		                         "bath.conferenceName"           => "conference",
		                         "bath.notes"                    => "notes",

		                         "rec.identifier"                => "serial",
		                         "rec.creationDate"              => "created_date-created_time", // see TODO note above
		                         "rec.creationAgentName"         => "created_by",
		                         "rec.lastModificationDate"      => "modified_date-modified_time", // see TODO note above
		                         "rec.lastModificationAgentName" => "modified_by",

		                         "bib.citekey"                   => "cite_key",

		                         "oai.identifier"                => "serial",
//		                         "oai.datestamp"                 => "modified_date-modified_time", // see TODO note above (same as 'rec.lastModificationDate')

		                         "cql.serverChoice"              => "keywords", // TODO: the special index 'main_fields' should resolve to 'cql.serverChoice', and that, in turn, should resolve to the user's preferred list of "main fields"

		                         "author"                        => "author", // for indexes that have no public context set we simply accept refbase field names
		                         "title"                         => "title",
		                         "year"                          => "year",
		                         "publication"                   => "publication",
		                         "abbrev_journal"                => "abbrev_journal",
		                         "volume"                        => "volume",
		                         "issue"                         => "issue",
		                         "pages"                         => "pages",

		                         "address"                       => "address",
		                         "corporate_author"              => "corporate_author",
		                         "keywords"                      => "keywords",
		                         "abstract"                      => "abstract",
		                         "publisher"                     => "publisher",
		                         "place"                         => "place",
		                         "editor"                        => "editor",
		                         "language"                      => "language",
		                         "summary_language"              => "summary_language",
		                         "orig_title"                    => "orig_title",

		                         "series_editor"                 => "series_editor",
		                         "series_title"                  => "series_title",
		                         "abbrev_series_title"           => "abbrev_series_title",
		                         "series_volume"                 => "series_volume",
		                         "series_issue"                  => "series_issue",
		                         "edition"                       => "edition",

		                         "issn"                          => "issn",
		                         "isbn"                          => "isbn",
		                         "medium"                        => "medium",
		                         "area"                          => "area",
		                         "expedition"                    => "expedition",
		                         "conference"                    => "conference",
		                         "notes"                         => "notes",
		                         "approved"                      => "approved",

		                         "location"                      => "location",
		                         "call_number"                   => "call_number",
		                         "serial"                        => "serial",
		                         "type"                          => "type",
		                         "thesis"                        => "thesis",

		                         "file"                          => "file",
		                         "url"                           => "url",
		                         "doi"                           => "doi",
		                         "contribution_id"               => "contribution_id",
		                         "online_publication"            => "online_publication",
		                         "online_citation"               => "online_citation",

		                         "created_date-created_time"     => "created_date-created_time", // see TODO note above
		                         "created_by"                    => "created_by",
		                         "modified_date-modified_time"   => "modified_date-modified_time", // see TODO note above
		                         "modified_by"                   => "modified_by",

		                         "orig_record"                   => "orig_record",

		                         "marked"                        => "marked", // in case of 'sru.php', querying for user-specific fields requires that the 'x-...authenticationToken' is given in the SRU query
		                         "copy"                          => "copy",// for 'opensearch.php', querying of user-specific fields will only work with a user being logged in
		                         "selected"                      => "selected",
		                         "user_keys"                     => "user_keys",
		                         "user_notes"                    => "user_notes",
		                         "user_file"                     => "user_file",
		                         "user_groups"                   => "user_groups",
		                         "related"                       => "related",
		                         "cite_key"                      => "cite_key" // currently, only the user-specific 'cite_key' field can be queried by every user using 'sru.php'
		                        );


		return $indexNamesArray;
	}

	// -------------------------------------------------------------------------------------------------------------------

	// Map SRU/W diagnostic numbers to their corresponding messages:
	// Spec: <http://www.loc.gov/standards/sru/specs/diagnostics.html>,
	//       <http://www.loc.gov/standards/sru/resources/diagnostics-list.html>
	function mapSRWDiagnostics()
	{
		$diagMessagesArray = array(1 => "General system error", // Details: Debugging information (traceback)
		                           2 => "System temporarily unavailable",
		                           3 => "Authentication error",
		                           4 => "Unsupported operation",
		                           5 => "Unsupported version", // Details: Highest version supported
		                           6 => "Unsupported parameter value", // Details: Name of parameter
		                           7 => "Mandatory parameter not supplied", // Details: Name of missing parameter
		                           8 => "Unsupported Parameter", // Details: Name of the unsupported parameter

		                           10 => "Query syntax error",
		                           15 => "Unsupported context set", // Details: URI or short name of context set
		                           16 => "Unsupported index", // Details: Name of index
		                           24 => "Unsupported combination of relation and term",
		                           36 => "Term in invalid format for index or relation",
		                           39 => "Proximity not supported",

		                           50 => "Result sets not supported",

		                           61 => "First record position out of range",
		                           64 => "Record temporarily unavailable",
		                           65 => "Record does not exist",
		                           66 => "Unknown schema for retrieval", // Details: Schema URI or short name (of the unsupported one)
		                           67 => "Record not available in this schema", // Details: Schema URI or short name
		                           68 => "Not authorised to send record",
		                           69 => "Not authorised to send record in this schema",
		                           70 => "Record too large to send", // Details: Maximum record size
		                           71 => "Unsupported record packing",
		                           72 => "XPath retrieval unsupported",

		                           80 => "Sort not supported",

		                           110 => "Stylesheets not supported"
		                          );

		return $diagMessagesArray;
	}

	// -------------------------------------------------------------------------------------------------------------------

	// Map refbase types to the corresponding eprint/resource types suggested for Simple Dublin Core[1]:
	// [1]: <http://eprints-uk.rdn.ac.uk/project/docs/simpledc-guidelines/#type>
	// for mappings marked with (*), the above article doesn't offer a type that sufficiently matches the refbase type
	function mapDCTypes()
	{
		$dcTypesArray = array("Journal Article"    => "JournalArticle",
		                      "Abstract"           => "Abstract", // (*)
		                      "Book Chapter"       => "BookChapter",
		                      "Book Whole"         => "Book",
		                      "Conference Article" => "ConferencePaper",
		                      "Conference Volume"  => "ConferenceProceedings",
		                      "Journal"            => "Journal", // (*)
		                      "Magazine Article"   => "MagazineArticle", // (*)
		                      "Manual"             => "Manual", // (*)
		                      "Manuscript"         => "Preprint",
		                      "Map"                => "Map", // (*)
		                      "Miscellaneous"      => "Other",
		                      "Newspaper Article"  => "NewsArticle",
		                      "Patent"             => "Patent", // (*)
		                      "Report"             => "TechnicalReport",
		                      "Software"           => "Software", // (*)
		//                    ""                   => "ConferencePoster",
		//                    ""                   => "InCollection",
		//                    ""                   => "OnlineJournalArticle",
		                      "Thesis"             => "Thesis" // since refbase currently doesn't use a 'Thesis' type, this has to be dealt with in the calling function
		                     );

		return $dcTypesArray;
	}

	// --------------------------------------------------------------------
?>
