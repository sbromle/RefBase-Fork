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
	// (NOTE: this function should also accept arrays to add multiple content tags)
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
	// (this array, in turn, gets merged into a full sql WHERE clause by function 'appendToWhereClause()')
	// NOTE: we don't provide a full CQL parser here but will (for now) concentrate on a rather limited feature
	//       set that makes sense in conjunction with refbase. However, future versions should employ far better
	//       CQL parsing logic.
	function parseCQL($sruVersion, $sruQuery)
	{
		// map CQL indexes to refbase field names:
		$indexNamesArray = mapCQLIndexes();

		$searchArray = array(); // intialize array that will hold information about context set, index name, relation and search value
		$searchSubArray1 = array();

		// check for presence of context set/index name and any of the main relations:
		if (preg_match('/^[^\" <>=]+( +(all|any|exact|within) +| *(<>|<=|>=|<|>|=) *)/', $sruQuery))
		{
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

				if (!ereg("^(dc|bath|rec|bib)$", $contextSet))
				{
					returnDiagnostic(15, $contextSet); // unsupported context set
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
				if (isset($indexNamesArray[$indexName]) OR isset($indexNamesArray["dc." . $indexName]) OR isset($indexNamesArray["bath." . $indexName]) OR isset($indexNamesArray["rec." . $indexName]) OR isset($indexNamesArray["bib." . $indexName])) // this may be clumsy but I don't know any better, right now
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

			// escape meta characters (including '/' that is used as delimiter for the PCRE replace functions below and which gets passed as second argument):
			$searchTerm = preg_quote($searchTerm, "/"); // escape special regular expression characters: . \ + * ? [ ^ ] $ ( ) { } = ! < > | :

			// account for CQL anchoring ('^') and masking ('*' and '?') characters:
			// NOTE: in the code block above we quote everything to escape possible meta characters,
			//       so all special chars in the block below have to be matched in their escaped form!
			//       (The expression '\\\\' in the patterns below describes only *one* backslash! -> '\'.
			//        The reason for this is that before the regex engine can interpret the \\ into \, PHP interprets it.
			//        Thus, you have to escape your backslashes twice: once for PHP, and once for the regex engine.)

			// recognize any anchor at the beginning of a search term (like '^foo'):
			$searchTerm = preg_replace('/(^| )\\\\\^/', '\\1^', $searchTerm);

			// convert any anchor at the end of a search term (like 'foo^') to the correct MySQL variant ('foo$'):
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
										"_query" => $whereClausePart);
		}

		else // no context set/index name and relation was given -> search the 'serial' field by default:
		{
			// NOTE: the following code block does not conform to CQL syntax rules!

			// replace any non-digit chars with "|":
			// (in doing so we'll ignore any 'and/or/not' booleans that were
			//  present in the search term and assume an 'or' operator instead)
			$serialsString = preg_replace("/\D+/", "|", $sruQuery);
			// strip "|" from beginning/end of string (if any):
			$serialsString = preg_replace("/^\|?(.*?)\|?$/", "\\1", $serialsString);

			if (!empty($serialsString))
				$searchSubArray1[] = array("_boolean" => "",
											"_query" => "serial RLIKE " . quote_smart("^(" . $serialsString . ")$"));
		}


		if (!empty($searchSubArray1))
			$searchArray[] = array("_boolean" => "",
									"_query" => $searchSubArray1);


		return $searchArray;
	}

	// -------------------------------------------------------------------------------------------------------------------

	// This function walks a '$searchArray' and appends its items to the WHERE clause:
	// (the array hierarchy will be maintained, i.e. if the '_query' item is itself
	//  an array of query items these sub-items will get properly nested in parentheses)
	function appendToWhereClause($searchArray)
	{
		global $query;

		foreach ($searchArray as $searchArrayItem)
		{
			if (is_array($searchArrayItem["_query"]))
			{
				$query .= " " . $searchArrayItem["_boolean"] . " (";
				$query .= appendToWhereClause($searchArrayItem["_query"]);
				$query .= " )";
			}
			else
			{
				$query .= " " . $searchArrayItem["_boolean"] . " " . $searchArrayItem["_query"];
			}
		}
	}

	// -------------------------------------------------------------------------------------------------------------------

	// Map CQL indexes to refbase field names:
	function mapCQLIndexes()
	{
		// NOTE: the CQL indexes 'creationDate' and 'lastModificationDate'
		// contain both date & time info so this needs to be parsed into two
		// refbase fields (which isn't done yet!).
		$indexNamesArray = array("dc.creator" => "author", // "CQL context_set.index_name"  =>  "refbase field name"
								"dc.title" => "title",
								"dc.date" => "year",
								"dc.language" => "language",
								"dc.description" => "abstract",
								"dc.subject" => "keywords",
								"dc.format" => "medium",
								"dc.publisher" => "publisher",
								"dc.coverage" => "area",
	
//								"bath.name" => "author",
//								"bath.topicalSubject" => "keywords",
								"bath.issn" => "issn",
								"bath.corporateName" => "corporate_author",
								"bath.conferenceName" => "conference",
	
								"rec.identifier" => "serial",
								"rec.creationDate" => "created_date-created_time",
								"rec.creationAgentName" => "created_by",
								"rec.lastModificationDate" => "modified_date-modified_time",
								"rec.lastModificationAgentName" => "modified_by",
	
								"bib.citekey" => "cite_key",
	
								"author" => "author", // for indexes that have no public context set we simply accept refbase field names
								"title" => "title",
								"year" => "year",
								"publication" => "publication",
								"abbrev_journal" => "abbrev_journal",
								"volume" => "volume",
								"issue" => "issue",
								"pages" => "pages",
	
								"address" => "address",
								"corporate_author" => "corporate_author",
								"keywords" => "keywords",
								"abstract" => "abstract",
								"publisher" => "publisher",
								"place" => "place",
								"editor" => "editor",
								"language" => "language",
								"summary_language" => "summary_language",
								"orig_title" => "orig_title",
	
								"series_editor" => "series_editor",
								"series_title" => "series_title",
								"abbrev_series_title" => "abbrev_series_title",
								"series_volume" => "series_volume",
								"series_issue" => "series_issue",
								"edition" => "edition",
	
								"issn" => "issn",
								"isbn" => "isbn",
								"medium" => "medium",
								"area" => "area",
								"expedition" => "expedition",
								"conference" => "conference",
								"notes" => "notes",
								"approved" => "approved",
	
								"location" => "location",
								"call_number" => "call_number",
								"serial" => "serial",
								"type" => "type",
								"thesis" => "thesis",
	
								"file" => "file",
								"url" => "url",
								"doi" => "doi",
								"contribution_id" => "contribution_id",
								"online_publication" => "online_publication",
								"online_citation" => "online_citation",
	
								"created_date-created_time" => "created_date-created_time",
								"created_by" => "created_by",
								"modified_date-modified_time" => "modified_date-modified_time",
								"modified_by" => "modified_by",
	
								"orig_record" => "orig_record",
	
//								"marked" => "marked", // querying for user-specific fields requires that the 'x-...authenticationToken' is given in the SRU query
//								"copy" => "copy",
//								"selected" => "selected",
//								"user_keys" => "user_keys",
//								"user_notes" => "user_notes",
//								"user_file" => "user_file",
//								"user_groups" => "user_groups",
//								"related" => "related",
								"cite_key" => "cite_key"); // currently, only the user-specific 'cite_key' field can be queried by every user using 'sru.php'


		return $indexNamesArray;
	}

	// --------------------------------------------------------------------
?>
