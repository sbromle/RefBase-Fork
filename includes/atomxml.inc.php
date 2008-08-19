<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/atomxml.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    09-Jan-08, 00:30
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This include file contains functions that'll export records to OpenSearch Atom XML.
	// Requires ActiveLink PHP XML Package, which is available under the GPL from:
	// <http://www.active-link.com/software/>. See 'opensearch.php' for more info.
	// TODO: I18n


	// Incorporate some include files:
	include_once 'includes/webservice.inc.php'; // include functions that are commonly used with the refbase webservices

	// Import the ActiveLink Packages
	require_once("classes/include.php");
	import("org.active-link.xml.XML");
	import("org.active-link.xml.XMLDocument");

	// --------------------------------------------------------------------

	// Return records as OpenSearch Atom XML:
	// 
	// Specs: <http://www.opensearch.org/Specifications/OpenSearch/1.1>
	//        <http://www.atomenabled.org/developers/syndication/>
	//        <http://www.atomenabled.org/developers/syndication/atom-format-spec.php>
	// 
	// TODO: - is the feed-level ID valid? (see notes for the 'id' element)
	//       - see inline comments labeled with "TODO"
	function atomCollection($result, $rowOffset, $showRows, $exportStylesheet, $displayType)
	{
		global $databaseBaseURL; // these variables are defined in 'ini.inc.php'
		global $contentTypeCharset;
		global $convertExportDataToUTF8;

		global $query;

		// Individual records are objects and collections of records are strings

		$atomCollectionDoc = new XMLDocument();

		if (($convertExportDataToUTF8 == "yes") AND ($contentTypeCharset != "UTF-8"))
			$atomCollectionDoc->setEncoding("UTF-8");
		else
			$atomCollectionDoc->setEncoding($contentTypeCharset);

		// Generate the basic OpenSearch Atom XML tree required for a query response:
		$atomCollection = atomGenerateBaseTags("Results");

		$showRowsOriginal = $showRows; // save original value of '$showRows' (which may get modified by the 'seekInMySQLResultsToOffset()' function below)

		// Find out how many rows are available and (if there were rows found) seek to the current offset:
		// function 'seekInMySQLResultsToOffset()' is defined in 'include.inc.php'
		list($result, $rowOffset, $showRows, $rowsFound, $previousOffset, $nextOffset, $showMaxRow) = seekInMySQLResultsToOffset($result, $rowOffset, $showRows, $displayType, "");

		// Setup some required variables:
		if (($rowsFound != 0) AND ($showRowsOriginal != 0)) // if some records were found AND the OpenSearch query did not contain 'maximumRecords=0'
		{
			$startIndex = $rowOffset + 1; // for OpenSearch, the index of the first search result is 1 while the first row number in a MySQL result set is 0, so we have to add 1

			if ($showMaxRow < $rowsFound) // if we are not on the last results page
				$itemsPerPage = $showRows;
			else // last results page
				$itemsPerPage = $rowsFound - $rowOffset; // adopt value for '$itemsPerPage' so that it equals the number of records displayed on the last page (which may be less than '$showRows')

			if ($rowsFound > $showRows) // if found results don't fit on a single results page
			{
				// Calculate the maximum number of pages needed:
				$lastPage = ($rowsFound / $showRows);
				// workaround for always rounding upward (since I don't know better! :-/):
				if (ereg("[0-9]+\.[0-9+]", $lastPage)) // if the result number is not an integer
					$lastPage = (int) $lastPage + 1; // we convert the number into an integer and add 1

				// Calculate the offset of the first record that's displayed on the last results page:
				// NOTE: Should the last offset take the current '$rowOffset' into account? I.e., take '$rowOffset' and see
				//       how many full chunks of '$showRows' can be stacked on top of it until '$rowsFound' is reached.
				//       The offset of the first of the remaining records then constitutes the '$lastOffset'.
				$lastOffset = (($lastPage - 1) * $showRows);
			}
			else // there's only one page to be displayed 
				$lastOffset = 0;
		}
		else
		{
			$startIndex = 0; // note that "0" will currently cause an empty element to be returned (instead of the number "0"), should this be changed?
			$itemsPerPage = 0;
			$lastOffset = 0;
		}

		// Extract the 'WHERE' clause from the current SQL query:
		$queryWhereClause = extractWHEREclause($query); // function 'extractWHEREclause()' is defined in 'include.inc.php'

		// Setup base URL and its corresponding query parameter for formats
		// that are supported by both, 'show.php'/'rss.php' AND 'opensearch.php':
		if (!isset($_SESSION['cqlQuery'])) // if there's no stored OpenSearch/CQL query available
		{
			// (while 'opensearch.php' writes the user's OpenSearch/CQL query into a session variable, this
			//  does not happen (and is not possible) if Atom XML is exported via the regular refbase GUI)

			// Generate Atom links using 'show.php' URLs (or 'rss.php' in case of RSS XML):
			$baseURL = "show.php";

			$cqlQuery = "";

			$queryParametersArray["where"] = $queryWhereClause;
		}
		else // if an OpenSearch/CQL query is available, we prefer 'opensearch.php' URLs
		{
			// Generate Atom links using 'opensearch.php' URLs:
			$baseURL = "opensearch.php";

			// Extract the original OpenSearch/CQL query that was saved by 'opensearch.php' as a session variable:
			$cqlQuery = $_SESSION['cqlQuery'];
			$queryParametersArray["query"] = $cqlQuery;

			// Clear the 'cqlQuery' session variable so that subsequent calls of this function won't accidentally use an outdated OpenSearch/CQL query: 
			// Note: Though we clear the session variable, the current message is still available to this script via '$cqlQuery':
			deleteSessionVariable("cqlQuery"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
		}

		// ----------------------------------------------------------

		// Add feed-level tags:

		// - 'link' elements:
		//   NOTE: According to the Atom spec, a feed is limited to ONE 'rel=alternate' link per type and hreflang!

		//   A) Formats supported by both, 'show.php'/'rss.php' AND 'opensearch.php':

		//   - RSS feed for full query results:
		atomLink($atomCollection, $databaseBaseURL . generateURL($baseURL, "RSS XML", $queryParametersArray, true, $showRows), "alternate", "RSS XML", "Current query results as RSS feed"); // function 'generateURL()' is defined in 'include.inc.php'

		//   - HTML output for current results page:
		atomLink($atomCollection, $databaseBaseURL . generateURL($baseURL, "html", $queryParametersArray, true, $showRows, $rowOffset), "alternate", "html", "Current results page as HTML");

		//   - SRW_DC XML data for current results page:
		//     NOTE: A link to SRW_MODS XML is already used with this type!
//		atomLink($atomCollection, $databaseBaseURL . generateURL($baseURL, "SRW_DC XML", $queryParametersArray, true, $showRows, $rowOffset), "alternate", "SRW_DC XML", "Current results page as SRW_DC XML data");

		//   - SRW_MODS XML data for current results page:
		atomLink($atomCollection, $databaseBaseURL . generateURL($baseURL, "SRW_MODS XML", $queryParametersArray, true, $showRows, $rowOffset), "alternate", "SRW_MODS XML", "Current results page as SRW_MODS XML data");

		//   - Atom XML data for current results page:
		atomLink($atomCollection, $databaseBaseURL . generateURL($baseURL, "Atom XML", $queryParametersArray, true, $showRows, $rowOffset), "self", "Atom XML", "Current results page");

		//   - Atom XML data for first results page:
		atomLink($atomCollection, $databaseBaseURL . generateURL($baseURL, "Atom XML", $queryParametersArray, true, $showRows, 0), "first", "Atom XML", "First results page");

		//   - Atom XML data for previous results page:
		if ($startIndex > $showRows) // if there are any previous results pages
			atomLink($atomCollection, $databaseBaseURL . generateURL($baseURL, "Atom XML", $queryParametersArray, true, $showRows, $previousOffset), "previous", "Atom XML", "Previous results page");

		//   - Atom XML data for next results page:
		if ($showMaxRow < $rowsFound) // if we are not on the last results page
			atomLink($atomCollection, $databaseBaseURL . generateURL($baseURL, "Atom XML", $queryParametersArray, true, $showRows, $nextOffset), "next", "Atom XML", "Next results page");

		//   - Atom XML data for last results page:
		atomLink($atomCollection, $databaseBaseURL . generateURL($baseURL, "Atom XML", $queryParametersArray, true, $showRows, $lastOffset), "last", "Atom XML", "Last results page");


		//   B) Other export formats supported by 'show.php':
		//   NOTE: These export formats currently do not support paging of results via '$showRows' and '$rowOffset' and thus always export the entire result set!
		//   TODO: add links for ADS, ISI and Word XML

		//   - BibTeX data for all results:
//		atomLink($atomCollection, $databaseBaseURL . generateURL("show.php", "BibTeX", array("where" => $queryWhereClause), true, $showRows, $rowOffset), "alternate", "BibTeX", "All results as BibTeX data");

		//   - Endnote data for all results:
//		atomLink($atomCollection, $databaseBaseURL . generateURL("show.php", "Endnote", array("where" => $queryWhereClause), true, $showRows, $rowOffset), "alternate", "Endnote", "All results as Endnote data");

		//   - RIS data for all results:
//		atomLink($atomCollection, $databaseBaseURL . generateURL("show.php", "RIS", array("where" => $queryWhereClause), true, $showRows, $rowOffset), "alternate", "RIS", "All results as RIS data");

		//   - MODS XML data for all results:
		//     NOTE: A link to SRW_MODS XML is already used with this type!
//		atomLink($atomCollection, $databaseBaseURL . generateURL("show.php", "MODS XML", array("where" => $queryWhereClause), true, $showRows, $rowOffset), "alternate", "MODS XML", "All results as MODS XML data");

		//   - OAI_DC XML data for all results:
		//     NOTE: A link to SRW_MODS XML is already used with this type!
//		atomLink($atomCollection, $databaseBaseURL . generateURL("show.php", "OAI_DC XML", array("where" => $queryWhereClause), true, $showRows, $rowOffset), "alternate", "OAI_DC XML", "All results as OAI_DC XML data");

		//   - ODF XML data for all results:
//		atomLink($atomCollection, $databaseBaseURL . generateURL("show.php", "ODF XML", array("where" => $queryWhereClause, "exportType" => "file"), true, $showRows, $rowOffset), "alternate", "ODF XML", "All results as ODF XML data");


		//   C) Citation formats supported by 'show.php':
		//   NOTE: Citation formats support paging of results via '$showRows' and '$rowOffset' if the 'client' parameter contains a value that starts with "cli"

		//   - RTF citations for current results page:
		atomLink($atomCollection, $databaseBaseURL . generateURL("show.php", "RTF", array("where" => $queryWhereClause, "client" => "cli-refbase_atom-1.0"), true, $showRows, $rowOffset), "alternate", "RTF", "Current results page as citations in RTF format");

		//   - PDF citations for current results page:
		atomLink($atomCollection, $databaseBaseURL . generateURL("show.php", "PDF", array("where" => $queryWhereClause, "client" => "cli-refbase_atom-1.0"), true, $showRows, $rowOffset), "alternate", "PDF", "Current results page as citations in PDF format");

		//   - LaTeX citations for current results page:
		atomLink($atomCollection, $databaseBaseURL . generateURL("show.php", "LaTeX", array("where" => $queryWhereClause, "client" => "cli-refbase_atom-1.0"), true, $showRows, $rowOffset), "alternate", "LaTeX", "Current results page as citations in LaTeX format");

		//   - LaTeX .bbl citations for current results page:
		//     NOTE: A link to LaTeX citations is already used with this type!
//		atomLink($atomCollection, $databaseBaseURL . generateURL("show.php", "LaTeX .bbl", array("where" => $queryWhereClause, "client" => "cli-refbase_atom-1.0"), true, $showRows, $rowOffset), "alternate", "LaTeX .bbl", "Current results page as citations in LaTeX .bbl format");

		//   - Markdown citations for current results page:
		atomLink($atomCollection, $databaseBaseURL . generateURL("show.php", "Markdown", array("where" => $queryWhereClause, "client" => "cli-refbase_atom-1.0"), true, $showRows, $rowOffset), "alternate", "Markdown", "Current results page as citations in Markdown format");

		//   - ASCII citations for current results page:
		//     (NOTE: A link to Markdown citations is already used with this type!
		//            Additionally, ASCII output with 'client=cli...' causes text output to be meant as shell response)
//		atomLink($atomCollection, $databaseBaseURL . generateURL("show.php", "ASCII", array("where" => $queryWhereClause, "client" => "cli-refbase_atom-1.0"), true, $showRows, $rowOffset), "alternate", "ASCII", "Current results page as citations in ASCII format");


		// - 'id':
		//   NOTE: is this a valid feed ID?
		//   TODO: should we rather use a feed ID that conforms to the Tag URI (RFC 4151)? Spec: <http://tools.ietf.org/html/rfc4151>;
		//         or should we use an ID such as '<id>urn:uuid:60a76c80-d399-11d9-b93C-0003939e0af6</id>' ?
		addNewBranch($atomCollection, "id", array(), $databaseBaseURL . generateURL($baseURL, "Atom XML", $queryParametersArray, true, $showRows, $rowOffset)); // function 'addNewBranch()' is defined in 'webservice.inc.php'


		// - OpenSearch elements:

		//   - 'opensearch:totalResults' contains the number of search results available for the current search:
		//      NOTE: The OpenSearch spec says: "If the totalResults element does not appear on the page then the
		//            search client should consider the current page to be the last page of search results."
		//            So does that, in turn, mean that we should better skip this element on the last results page?
		addNewBranch($atomCollection, "opensearch:totalResults", array(), $rowsFound);

		//   - 'opensearch:startIndex' contains the index of the first search result in the current set of search results:
		addNewBranch($atomCollection, "opensearch:startIndex", array(), $startIndex);

		//   - 'opensearch:itemsPerPage' contains the number of search results returned per page:
		addNewBranch($atomCollection, "opensearch:itemsPerPage", array(), $itemsPerPage);

		//   - 'opensearch:Query' defines a search query that can be performed by search clients:
		if (!empty($cqlQuery))
		{
			// convert query string to UTF-8:
			// (if '$convertExportDataToUTF8' is set to "yes" in 'ini.inc.php' and character encoding is not UTF-8 already)
			if (($convertExportDataToUTF8 == "yes") AND ($contentTypeCharset != "UTF-8"))
				$cqlQuery = convertToCharacterEncoding("UTF-8", "IGNORE", $cqlQuery); // function 'convertToCharacterEncoding()' is defined in 'include.inc.php'

			addNewBranch($atomCollection,
			             "opensearch:Query",
			             array("role"        => "request",
			                   "title"       => "Current query",
			                   "searchTerms" => $cqlQuery,
			                   "startIndex"  => $startIndex,
			                   "count"       => $itemsPerPage,
			                  ),
			             ""
			            );
		}

		// ----------------------------------------------------------

		// Add Atom XML entries:

		if ($showRowsOriginal != 0) // we omit the records list in the response if the OpenSearch query did contain 'maximumRecords=0'
		{
			// Define inline text markup to be used by the 'citeRecord()' function:
			$markupPatternsArray = array("bold-prefix"      => "<b>",
			                             "bold-suffix"      => "</b>",
			                             "italic-prefix"    => "<i>",
			                             "italic-suffix"    => "</i>",
			                             "underline-prefix" => "<u>",
			                             "underline-suffix" => "</u>",
			                             "endash"           => "&#8211;",
			                             "emdash"           => "&#8212;",
			                             "ampersand"        => "&amp;", // this ensures correct encoding of ampersands which are inserted into the author string by citation styles such as APA
			                             "double-quote"     => "&quot;",
			                             "single-quote"     => "'",
			                             "less-than"        => "&lt;",
			                             "greater-than"     => "&gt;",
			                             "newline"          => "\n<br>\n"
			                            );

			$exportArray = array(); // array for individually exported records

			// Generate the export for each record and push them onto an array:
			for ($rowCounter=0; (($rowCounter < $showRows) && ($row = @ mysql_fetch_array($result))); $rowCounter++)
			{
				// Export the current record as Atom XML entry:
				$entry = atomEntry($row, $markupPatternsArray);

				if (!empty($entry)) // unless the record buffer is empty...
					array_push($exportArray, $entry); // ...add it to an array of exports
			}

			// for each of the Atom XML entries in the result set...
			foreach ($exportArray as $atom)
				$atomCollection->addXMLasBranch($atom);
		}

		$atomCollectionDoc->setXML($atomCollection);
		$atomCollectionString = $atomCollectionDoc->getXMLString();

		// Add the XML Stylesheet definition:
		// Note that this is just a hack (that should get fixed) since I don't know how to do it properly using the ActiveLink PHP XML Package ?:-/
		if (!empty($exportStylesheet))
			$atomCollectionString = preg_replace("/(?=\<feed)/i","<?xml-stylesheet type=\"text/xsl\" href=\"" . $exportStylesheet . "\"?>\n",$atomCollectionString);

		// NOTE: Firefox >=2.x, Safari >=2.x and IE >=7.x break client-side XSL for RSS and Atom feeds!
		//       See e.g.: <http://decafbad.com/blog/2006/11/02/firefox-20-breaks-client-side-xsl-for-rss-and-atom-feeds>
		// TODO: Re-evaluate: This is a VERY dirty hack that prevents the feed sniffing and subsequent
		//       browser applied default XSLT stylesheet that has been implemented by FireFox 2, Safari 2
		//       and Internet Explorer 7. To prevent the feed sniffing we insert a comment before the feed
		//       element that is larger than 512 bytes. See: <http://feedme.mind-it.info/pivot/entry.php?id=9>
		if (!empty($exportStylesheet))
			$atomCollectionString = preg_replace("/(?=\<feed)/i","<!-- This is a comment that has been inserted since Internet Explorer 7, FireFox 2 and Safari 3 break client-side XSL for RSS and Atom feeds, i.e. these browsers don't honour a xml stylesheet instruction but instead apply their own default XSLT stylesheet. While this makes sense for reasons of consistency, it's very unfortunate that there's no proper option to circumvent this behaviour since it effectively prevents custom feed-based GUI solutions that were made for other purposes than the ones intended by the browser developers. Luckily the designers of these browsers use very brittle sniffing techniques that can be overridden by consuming the first 512 bytes of an XML file. This comment provides these essential 512 bytes of crud, thus preventing the feed sniffing and subsequent applied default XSLT stylesheet that has been implemented by Internet Explorer 7, FireFox 2 and Safari 2. But, unfortunately, it destroys the nice simplicity and cleanliness of this Atom feed. For more info see e.g. <http://decafbad.com/blog/2006/11/02/firefox-20-breaks-client-side-xsl-for-rss-and-atom-feeds> and <http://feedme.mind-it.info/pivot/entry.php?id=9>. -->\n",$atomCollectionString);

		return $atomCollectionString;
	}

	// --------------------------------------------------------------------

	// Generate an OpenSearch Atom XML entry:
	// (returns an XML object (atom) of a single record)
	function atomEntry($row, $markupPatternsArray)
	{
		global $databaseBaseURL; // these variables are defined in 'ini.inc.php'
		global $contentTypeCharset;
		global $fileVisibility;
		global $fileVisibilityException;
		global $filesBaseURL;
		global $convertExportDataToUTF8;
		global $defaultCiteStyle;

		global $alnum, $alpha, $cntrl, $dash, $digit, $graph, $lower, $print, $punct, $space, $upper, $word, $patternModifiers; // defined in 'transtab_unicode_charset.inc.php' and 'transtab_latin1_charset.inc.php'

		// The array '$transtab_refbase_unicode' contains search & replace patterns for conversion from refbase markup to Unicode entities.
		global $transtab_refbase_unicode; // defined in 'transtab_refbase_unicode.inc.php'

		// The array '$transtab_refbase_ascii' contains search & replace patterns for conversion from refbase markup to plain text.
		global $transtab_refbase_ascii; // defined in 'transtab_refbase_ascii.inc.php'

		// The array '$transtab_refbase_html' contains search & replace patterns for conversion from refbase markup to HTML markup & entities.
		// Note that this will only convert markup which wasn't converted to Unicode entities by '$transtab_refbase_unicode'; this will provide
		// for correct rendering of italic and bold letters in 'content' elements (which is of 'type="xhtml"').
		global $transtab_refbase_html; // defined in 'transtab_refbase_html.inc.php'

		// NOTE: We remove again some search & replace patterns that are present by default in '$transtab_refbase_html' since they cause
		//       problems here; this is surely hacky but I don't know any better. :-/
		unset($transtab_refbase_html['/ +- +/']); // this would incorrectly convert author initials such as "J. - L." to "J. &#8211; L."

		// Define inline text markup to generate a plain text citation string:
		// (to be included within a 'dcterms:bibliographicCitation' element)
		$markupPatternsArrayPlain = array("bold-prefix"      => "", // NOTE: should we rather keep refbase font-shape markup (like _italic_ and **bold**) for plain text output?
		                                  "bold-suffix"      => "",
		                                  "italic-prefix"    => "",
		                                  "italic-suffix"    => "",
		                                  "underline-prefix" => "",
		                                  "underline-suffix" => "",
		                                  "endash"           => "-",
		                                  "emdash"           => "-",
		                                  "ampersand"        => "&",
		                                  "double-quote"     => '"',
		                                  "single-quote"     => "'",
		                                  "less-than"        => "<",
		                                  "greater-than"     => ">",
		                                  "newline"          => "\n"
		                                 );


		// This is a stupid hack that maps the names of the '$row' array keys to those used
		// by the '$formVars' array (which is required by function 'generateCiteKey()')
		// (eventually, the '$formVars' array should use the MySQL field names as names for its array keys)
		$formVars = buildFormVarsArray($row); // function 'buildFormVarsArray()' is defined in 'include.inc.php'

		// Generate or extract the cite key for this record:
		// (to be included within a 'dc:identifier' element)
		$citeKey = generateCiteKey($formVars); // function 'generateCiteKey()' is defined in 'include.inc.php'

		// Generate OpenURL data:
		// (to be included within a 'dc:identifier' element)
		$openURL = openURL($row, "openurl:"); // function 'openURL()' is defined in 'openurl.inc.php'

		// Encode special chars and perform charset conversions:
		foreach ($row as $rowFieldName => $rowFieldValue)
		{
			// We only convert those special chars to entities which are supported by XML:
			// function 'encodeHTMLspecialchars()' is defined in 'include.inc.php'
			$row[$rowFieldName] = encodeHTMLspecialchars($row[$rowFieldName]);

			// Convert field data to UTF-8:
			// (if '$convertExportDataToUTF8' is set to "yes" in 'ini.inc.php' and character encoding is not UTF-8 already)
			// (Note that charset conversion can only be done *after* the cite key has been generated, otherwise cite key
			//  generation will produce garbled text!)
			// function 'convertToCharacterEncoding()' is defined in 'include.inc.php'
			if (($convertExportDataToUTF8 == "yes") AND ($contentTypeCharset != "UTF-8"))
				$row[$rowFieldName] = convertToCharacterEncoding("UTF-8", "IGNORE", $row[$rowFieldName]);
		}

		// Defines field-specific search & replace 'actions' that will be applied to all those refbase fields that are listed in the corresponding 'fields' element:
		// (If you don't want to perform any search and replace actions, specify an empty array, like: '$fieldSpecificSearchReplaceActionsArray = array();'.
		//  Note that the search patterns MUST include the leading & trailing slashes -- which is done to allow for mode modifiers such as 'imsxU'.)
		//                                          "/Search Pattern/"  =>  "Replace Pattern"
		$fieldSpecificSearchReplaceActionsArray = array();

		if ($convertExportDataToUTF8 == "yes")
			$fieldSpecificSearchReplaceActionsArray[] = array('fields'  => array("title", "publication", "abbrev_journal", "address", "keywords", "abstract", "orig_title", "series_title", "abbrev_series_title", "notes"),
			                                                  'actions' => $transtab_refbase_unicode
			                                                 );

		// Apply field-specific search & replace 'actions' to all fields that are listed in the 'fields' element of the arrays contained in '$fieldSpecificSearchReplaceActionsArray':
		foreach ($fieldSpecificSearchReplaceActionsArray as $fieldActionsArray)
			foreach ($row as $rowFieldName => $rowFieldValue)
				if (in_array($rowFieldName, $fieldActionsArray['fields']))
					$row[$rowFieldName] = searchReplaceText($fieldActionsArray['actions'], $rowFieldValue, true); // function 'searchReplaceText()' is defined in 'include.inc.php'

		$citeStyleFile = getStyleFile($defaultCiteStyle); // fetch the name of the citation style file that's associated with the style given in '$defaultCiteStyle' (which, in turn, is defined in 'ini.inc.php')

		// Include the found citation style file *once*:
		include_once "cite/" . $citeStyleFile;

		// Generate a proper citation for this record, ordering attributes according to the chosen output style & record type:
		// - Plain text version of citation string:
		//   (the plain text version of the citation string will be included in the 'dcterms:bibliographicCitation' element which should contain plain text only)
		$recordCitationPlain = citeRecord($row, $defaultCiteStyle, "", $markupPatternsArrayPlain, false); // function 'citeRecord()' is defined in the citation style file given in '$citeStyleFile' (which, in turn, must reside in the 'styles' directory of the refbase root directory)

		//   Convert any refbase markup that remains in the citation string (such as _italic_ or **bold**) to plain text:
		$recordCitationPlain = searchReplaceText($transtab_refbase_ascii, $recordCitationPlain, true);

		// - HTML version of citation string:
		//   (note that, for output of Atom XML, we do NOT HTML encode higher ASCII characters; thus, the last param in the below function call is 'false')
		$recordCitation = citeRecord($row, $defaultCiteStyle, "", $markupPatternsArray, false);

		//   Convert any refbase markup that remains in the citation string (such as _italic_ or **bold**) into HTML markup:
		//   (the HTML version of the citation string will be included in the Atom 'content' element which uses 'type="xhtml"')
		$recordCitation = searchReplaceText($transtab_refbase_html, $recordCitation, true);

		// Save a plain text version of the title to a new variable:
		// (this will be included in the 'dc:title' element which should contain plain text only)
		$titlePlain = searchReplaceText($transtab_refbase_ascii, $row['title'], true);

		// Convert any remaining refbase markup in the abstract & title to HTML markup:
		// (we use 'type="xhtml"' in the Atom 'title' and 'summary' elements)
		$row['title'] = searchReplaceText($transtab_refbase_html, $row['title'], true);
		$row['abstract'] = searchReplaceText($transtab_refbase_html, $row['abstract'], true);

		// Convert keywords to plain text:
		// (keywords will be written to 'dc:subject' elements which should contain plain text only)
		$row['keywords'] = searchReplaceText($transtab_refbase_ascii, $row['keywords'], true);

		// To avoid advertising email adresses in public Atom XML output, we remove the email address from contents of the
		// 'modified_by' and 'created_by' fields which get displayed in the 'author' and 'content' elements.
		// The following pattern does not attempt to do fancy parsing of email addresses but simply assumes the string format of the
		// 'modified_by' and 'created_by' fields (table 'refs'). If you change the string format, you must modify this pattern as well!
		$creatorName = preg_replace("/(.+?) \([^)]+\)/", "\\1", $row['created_by']);
		$editorName = preg_replace("/(.+?) \([^)]+\)/", "\\1", $row['modified_by']);

		// Strip any " (ed)" or " (eds)" suffix from author/editor string:
		if (ereg(" *\(eds?\)$", $row['author']))
			$row['author'] = ereg_replace("[ \r\n]*\(eds?\)", "", $row['author']);

		if (ereg(" *\(eds?\)$", $row['editor']))
			$row['editor'] = ereg_replace("[ \r\n]*\(eds?\)", "", $row['editor']);

		// Include a link to any corresponding file if one of the following conditions is met:
		// - the variable '$fileVisibility' (defined in 'ini.inc.php') is set to 'everyone'
		// - the variable '$fileVisibility' is set to 'login' AND the user is logged in
		// - the variable '$fileVisibility' is set to 'user-specific' AND the 'user_permissions' session variable contains 'allow_download'
		// - the array variable '$fileVisibilityException' (defined in 'ini.inc.php') contains a pattern (in array element 1) that matches the contents of the field given (in array element 0)
		// 
		// TODO: - the URL-generating code should be made into a dedicated function (since it's shared with 'modsxml.inc.php' and 'oaidcxml.inc.php')
		$printURL = false;

		if ($fileVisibility == "everyone" OR ($fileVisibility == "login" AND isset($_SESSION['loginEmail'])) OR ($fileVisibility == "user-specific" AND (isset($_SESSION['user_permissions']) AND ereg("allow_download", $_SESSION['user_permissions']))) OR (!empty($fileVisibilityException) AND preg_match($fileVisibilityException[1], $row[$fileVisibilityException[0]])))
		{
			if (!empty($row['file']))
			{
				if (ereg('^(https?|ftp|file)://', $row['file'])) // if the 'file' field contains a full URL (starting with "http://", "https://",  "ftp://", or "file://")
				{
				  $URLprefix = ""; // we don't alter the URL given in the 'file' field
				}
				else // if the 'file' field contains only a partial path (like 'polarbiol/10240001.pdf') or just a file name (like '10240001.pdf')
				{
					// use the base URL of the standard files directory as prefix:
					if (ereg('^/', $filesBaseURL)) // absolute path -> file dir is located outside of refbase root dir
						$URLprefix = 'http://' . $_SERVER['HTTP_HOST'] . $filesBaseURL;
					else // relative path -> file dir is located within refbase root dir
						$URLprefix = $databaseBaseURL . $filesBaseURL;
				}

				$printURL = true;
			}
		}

		// ----------------------------------------------------------

		// Start Atom XML entry:
		$entry = new XML("entry"); // create an XML object for a single record

		// Add entry-level tags:
		// (not yet used: category, contributor, source, rights)

		// - 'id':
		addNewBranch($entry, "id", array(), $databaseBaseURL . generateURL("show.php", "html", array("record" => $row['serial']), true));

		// - 'title':
		//   TODO: - the 'title' element is required for 'entry', so we may need to insert something else if the record's title is missing
//		addNewBranch($entry, "title", array("type" => "text"), $titlePlain); // plain text version
		addNewBranch($entry,
		             "title",
		             array("type" => "xhtml"),
		             '<div xmlns="http://www.w3.org/1999/xhtml">'
		             . $row['title']
		             . '</div>'
		            );

		// - 'updated':
		addNewBranch($entry, "updated", array(), generateISO8601TimeStamp($row['modified_date'], $row['modified_time'])); // function 'generateISO8601TimeStamp()' is defined in 'include.inc.php'

		// - 'published':
		//   NOTE: we use the 'published' element to indicate the date/time when the record was created in the refbase database,
		//         and not when the record's resource was originally published
		addNewBranch($entry, "published", array(), generateISO8601TimeStamp($row['created_date'], $row['created_time']));

		// - 'link':
		//   NOTE: According to the Atom spec, a feed is limited to ONE 'rel=alternate' link per type and hreflang!

		//   A) Main display formats:

		//   - HTML output for this record:
		//     NOTE: How can we output an 'alternate' link to the HTML citation with the same 'type'?
		//           But, maybe, this isn't necessary since a client GUI (layered over the Atom XML data) would expose
		//           the record citation (from the 'content' or 'bibliographicCitation' element) anyhow... ?
		atomLink($entry, $databaseBaseURL . generateURL("show.php", "html", array("record" => $row['serial']), true), "alternate", "html", "View record in HTML format"); // function 'generateURL()' is defined in 'include.inc.php'


		//   B) Export formats
		//   NOTE: should we rather generate 'unapi.php' and 'opensearch.php' URLs where possible?
		//   TODO: add links for ADS, ISI, RSS XML and Word XML

		//   - BibTeX data for this record:
		atomLink($entry, $databaseBaseURL . generateURL("show.php", "BibTeX", array("record" => $row['serial']), true), "alternate", "BibTeX", "Export record in BibTeX format");

		//   - Endnote data for this record:
		atomLink($entry, $databaseBaseURL . generateURL("show.php", "Endnote", array("record" => $row['serial']), true), "alternate", "Endnote", "Export record in Endnote format");

		//   - RIS data for this record:
		atomLink($entry, $databaseBaseURL . generateURL("show.php", "RIS", array("record" => $row['serial']), true), "alternate", "RIS", "Export record in RIS format");

		//   - Atom XML data for this record:
		atomLink($entry, $databaseBaseURL . generateURL("show.php", "Atom XML", array("record" => $row['serial']), true), "alternate", "Atom XML", "Export record as Atom XML");

		//   - MODS XML data for this record:
		//     NOTE: while we include a link to SRW_MODS XML on feed level, we instead include a link to MODS XML on entry level since the SRW overhead isn't really needed here
		atomLink($entry, $databaseBaseURL . generateURL("show.php", "MODS XML", array("record" => $row['serial']), true), "alternate", "MODS XML", "Export record as MODS XML");

		//   - OAI_DC XML data for this record:
		//     NOTE: A link to MODS XML is already used with this type!
//		atomLink($entry, $databaseBaseURL . generateURL("show.php", "OAI_DC XML", array("record" => $row['serial']), true), "alternate", "OAI_DC XML", "Export record as OAI_DC XML");

		//   - ODF XML data for this record:
		atomLink($entry, $databaseBaseURL . generateURL("show.php", "ODF XML", array("record" => $row['serial'], "exportType" => "file"), true), "alternate", "ODF XML", "Export record as ODF XML");

		//   - SRW_DC XML data for this record:
		//     NOTE: A link to MODS XML is already used with this type!
//		atomLink($entry, $databaseBaseURL . generateURL("show.php", "SRW_DC XML", array("record" => $row['serial']), true), "alternate", "SRW_DC XML", "Export record as SRW_DC XML");

		//   - SRW_MODS XML data for this record:
		//     NOTE: A link to MODS XML is already used with this type!
//		atomLink($entry, $databaseBaseURL . generateURL("show.php", "SRW_MODS XML", array("record" => $row['serial']), true), "alternate", "SRW_MODS XML", "Export record as SRW_MODS XML");


		//   C) Citation formats:

		//   - RTF citations for this record:
		atomLink($entry, $databaseBaseURL . generateURL("show.php", "RTF", array("record" => $row['serial']), true), "alternate", "RTF", "Output record as citation in RTF format");

		//   - PDF citations for this record:
		atomLink($entry, $databaseBaseURL . generateURL("show.php", "PDF", array("record" => $row['serial']), true), "alternate", "PDF", "Output record as citation in PDF format");

		//   - LaTeX citations for this record:
		atomLink($entry, $databaseBaseURL . generateURL("show.php", "LaTeX", array("record" => $row['serial']), true), "alternate", "LaTeX", "Output record as citation in LaTeX format");

		//   - LaTeX .bbl citations for this record:
		//     NOTE: A link to a LaTeX citation is already used with this type!
//		atomLink($entry, $databaseBaseURL . generateURL("show.php", "LaTeX .bbl", array("record" => $row['serial']), true), "alternate", "LaTeX .bbl", "Output record as citation in LaTeX .bbl format");

		//   - Markdown citations for this record:
		atomLink($entry, $databaseBaseURL . generateURL("show.php", "Markdown", array("record" => $row['serial']), true), "alternate", "Markdown", "Output record as citation in Markdown format");

		//   - ASCII citations for this record:
		//     NOTE: A link to a Markdown citation is already used with this type!
//		atomLink($entry, $databaseBaseURL . generateURL("show.php", "ASCII", array("record" => $row['serial']), true), "alternate", "ASCII", "Output record as citation in ASCII format");


		//   D) Related links:

		//   - Related URL:
		//     TODO: - the 'type' (and 'title'?) attributes should get adopted if something other than an URL pointing to a HTML page is given
		if (!empty($row['url']))
			atomLink($entry, $row['url'], "related", "html", "Web page");

		//   - Related FILE:
		//     NOTE: - should we better use the 'enclosure' element instead?
		//     TODO: - the 'type' attribute should get adopted if something other than PDF is given
		if ($printURL)
			atomLink($entry, $URLprefix . $row['file'], "related", "PDF", "Electronic full text");

		// - 'author':
		//   NOTE: The Atom 'author' element contains the database user who created this record,
		//         while the publication authors are contained within 'dc:creator' elements
		$recAuthorBranch = new XMLBranch("author");
		$recAuthorBranch->setTagContent($creatorName, "author/name");
		$entry->addXMLBranch($recAuthorBranch);

		// - 'contributor':
		//   NOTE: The Atom 'contributor' element contains the database user who edited this record last,
		//         while the publication editors are contained within 'dc:contributor' elements
		if ($creatorName != $editorName)
		{
			$recEditorBranch = new XMLBranch("contributor");
			$recEditorBranch->setTagContent($editorName, "contributor/name");
			$entry->addXMLBranch($recEditorBranch);
		}

		// - 'content':
		//   NOTE: According to the Atom spec, all HTML markup must be escaped if 'type="html"' is used. In case of
		//         'type="xhtml"', HTML markup is not entity escaped but must be wrapped in a single XHTML 'div' element.
		//         See: <http://atompub.org/rfc4287.html#element.content>
		//              <http://www.atomenabled.org/developers/syndication/#text>
//		addNewBranch($entry, "content", array("type" => "html"), encodeHTMLspecialchars($recordCitation));
		addNewBranch($entry,
		             "content",
		             array("type" => "xhtml"),
		             '<div xmlns="http://www.w3.org/1999/xhtml">'
		             . '<div class="unapi"><abbr class="unapi-id" title="' . $databaseBaseURL . generateURL("show.php", "html", array("record" => $row['serial']), true) . '"></abbr></div>' // should we omit unAPI IDs? and is it valid to nest divs within the main enclosing divs?
		             . $recordCitation
		//           . "<br /><br />" // last-modified info is already contained in the Atom elements 'contributor' and 'updated'
		//           . "Edited by " . $editorName
		//           . " on " . generateRFC2822TimeStamp($row['modified_date'], $row['modified_time']) . "." // function 'generateRFC2822TimeStamp()' is defined in 'include.inc.php'
		             . '</div>'
		            );

		// - 'summary':
		if (!empty($row['abstract']))
		{
			addNewBranch($entry,
			             "summary",
			             array("type" => "xhtml"),
			             '<div xmlns="http://www.w3.org/1999/xhtml">'
			             . $row['abstract']
			             . '</div>'
			            );
		}

		// ----------------------------------------------------------

		// Add Dublin Core elements:
		// NOTE: With a few exceptions, we try to adhere to the guidelines given at
		//       "Using simple Dublin Core to describe eprints" by Andy Powell et al.
		//       See: <http://eprints-uk.rdn.ac.uk/project/docs/simpledc-guidelines/>

		// - 'dc:title':
		if (!empty($row['title']))
			addMetaElement($entry, "dc", "title", array(), $titlePlain); // function 'addMetaElement()' is defined in 'webservice.inc.php'

		// - 'dc:creator':
		//   NOTE: should we use 'foaf:maker' instead of (or in addition to) 'dc:creator'?
		//   ( xmlns:foaf="http://xmlns.com/foaf/0.1/" )
		// 
		//   <foaf:maker>
		//     <foaf:Person>
		//       <foaf:name> [ Name of author 1 ] </foaf:name>
		//     </foaf:Person>
		//   </foaf:maker>
		if (!empty($row['author']) AND ($row['author'] != $row['editor']))
			addMetaElement($entry, "dc", "creator", array(), $row['author']);

		// - 'dc:creator':
		//   TODO: add refbase corporate author(s) as 'dc:creator'

		// - 'dc:contributor':
		if (!empty($row['editor']))
			addMetaElement($entry, "dc", "contributor", array(), $row['editor']);

		// - 'dc:description':
		//   NOTE: since we already use the Atom-native 'summary' element for the record
		//         abstract/summary, we don't add the abstract again as 'dc:description'

		// - 'dc:identifier':

		//   - DOI:
		if (!empty($row['doi']))
			addMetaElement($entry, "dc", "identifier", array(), $row['doi'], "doi");

		//   - PMID:
		if (!empty($row['notes']) AND preg_match("/PMID *: *\d+/i", $row['notes']))
			addMetaElement($entry, "dc", "identifier", array(), $row['notes'], "pmid");

		//   - arXiv:
		if (!empty($row['notes']) AND preg_match("/arXiv *: *[^ ;]+/i", $row['notes']))
			addMetaElement($entry, "dc", "identifier", array(), $row['notes'], "arxiv");

		//   - ISBN:
		if (!empty($row['isbn']))
			addMetaElement($entry, "dc", "identifier", array(), $row['isbn'], "isbn");

		//   - OpenURL:
		addMetaElement($entry, "dc", "identifier", array(), $openURL, "openurl");

		//   - Cite key:
		addMetaElement($entry, "dc", "identifier", array(), $citeKey, "citekey");

		// - 'dcterms:bibliographicCitation':
		//   NOTE: While Andy Powell (see link above) recommends to put this into a
		//         'dc:identifier' element, we'll put it into a 'dcterms:bibliographicCitation'
		//         element instead, since the citation couldn't be uniquely identified within a
		//         'dc:identifier' element without a 'citation:' prefix (or the like) but that
		//         would be non-standard. Within 'dcterms:bibliographicCitation', the citation
		//         can be uniquely identified and extracted easily.
		//         Compare with 'oaidcxml.inc.php' where, for 'oai_dc:dc' output, we put the
		//         bibliographic citation into a 'dc:identifier' element and use a "citation:"
		//         prefix:
//		addMetaElement($entry, "dc", "identifier", array(), encodeHTMLspecialchars($recordCitationPlain), "citation");
		addMetaElement($entry, "dcterms", "bibliographicCitation", array(), encodeHTMLspecialchars($recordCitationPlain));

		// - 'dc:source':
		//   NOTE: - In <http://eprints-uk.rdn.ac.uk/project/docs/simpledc-guidelines/>,
		//           Andy Powell et al. recommend that this element should NOT be used!
		//           However, for Atom XML output, we do use the 'dc:source' element for series
		//           info (series title plus volume & issue).
		//           Compare with 'oaidcxml.inc.php' where, for 'oai_dc:dc' output, we also
		//           include publication info in a 'dc:source' element.
		//           Example: <dc:source>Polar Biology, Vol. 25, No. 10</dc:source>

		//   - Series info:
		if (!empty($row['series_title']) OR !empty($row['abbrev_series_title']))
		{
			if (!empty($row['series_title']))
				$series = $row['series_title'];
			elseif (!empty($row['abbrev_series_title']))
				$series = $row['abbrev_series_title'];

			if (!empty($row['series_volume']))
				$series .= ", Vol. " . $row['series_volume'];

			if (!empty($row['series_issue']))
				$series .= ", No. " . $row['series_issue'];

			if (!empty($series))
				addMetaElement($entry, "dc", "source", array(), $series);
				// NOTE: To distinguish between regular publication & series info,
				//       should we better use a "series:" prefix here? If so, use:
//				addMetaElement($entry, "dc", "source", array(), $series, "series");
		}

		// - 'dc:date':
		if (!empty($row['year']))
			addMetaElement($entry, "dc", "date", array(), $row['year']);

		// - 'dc:type':
		if (!empty($row['type']))
			addMetaElement($entry, "dc", "type", array(), $row['type'], $row['thesis']);

		//   In case of a thesis, we add another 'dc:type' element with the actual thesis type:
		if (!empty($row['thesis']))
			addMetaElement($entry, "dc", "type", array(), $row['thesis']);

		// - 'dc:format':
		//   TODO: ideally, we should parse the content of the refbase 'medium' field and map it
		//         to a media-type term from <http://www.iana.org/assignments/media-types/>
		if (!empty($row['medium']))
			$mediaType = $row['medium'];
		else
			$mediaType = "text";

		addMetaElement($entry, "dc", "format", array(), $mediaType);

		// - 'dc:subject':
		if (!empty($row['keywords']))
			addMetaElement($entry, "dc", "subject", array(), $row['keywords']);

		// - 'dc:coverage':
		//   TODO: should we add contents from the refbase 'area' field as 'dc:coverage' element(s)?

		// - 'dc:relation':
		//   NOTE: currently, we only add 'related' links (and not 'alternate' links) as 'dc:relation'

		//   - Related URL:
		if (!empty($row['url']))
			addMetaElement($entry, "dc", "relation", array(), $row['url'], "url");

		//   - Related FILE:
		if ($printURL)
			addMetaElement($entry, "dc", "relation", array(), $URLprefix . $row['file'], "file");

		// - 'dc:publisher':
		if (!empty($row['publisher']))
			addMetaElement($entry, "dc", "publisher", array(), $row['publisher']);

		// - 'dc:language':
		//   TODO: convert to ISO notation (i.e. "en" instead of "English", etc)
		if (!empty($row['language']))
			addMetaElement($entry, "dc", "language", array(), $row['language']);


		// ----------------------------------------------------------

		// Add PRISM elements:
		// (not yet used: section)

		// - 'prism:issn':
		//   NOTE: see note for ISBN above
		if (!empty($row['issn']))
			addMetaElement($entry, "prism", "issn", array(), $row['issn']);

		// - 'prism:publicationName':
		if (!empty($row['publication']))
			addMetaElement($entry, "prism", "publicationName", array(), $row['publication']);
		elseif (!empty($row['abbrev_journal']))
			addMetaElement($entry, "prism", "publicationName", array(), $row['abbrev_journal']);

		// - 'prism:publicationDate':
		if (!empty($row['year']))
			addMetaElement($entry, "prism", "publicationDate", array(), $row['year']);

		// - 'prism:volume':
		if (!empty($row['volume']))
			addMetaElement($entry, "prism", "volume", array(), $row['volume']);

		// - 'prism:number':
		if (!empty($row['issue']))
			addMetaElement($entry, "prism", "number", array(), $row['issue']);

		// - 'prism:startingPage', 'prism:endingPage':
		//   TODO: Similar code is used in 'include.in.php', 'modsxml.inc.php' and 'openurl.inc.php',
		//         so this should be made into a dedicated function!
		if (!empty($row['pages']) AND preg_match("/\d+/i", $row['pages'])) // if the 'pages' field contains a number
		{
			$pages = preg_replace("/^\D*(\d+)( *[$dash]+ *\d+)?.*/i$patternModifiers", "\\1\\2", $row['pages']); // extract page range (if there's any), otherwise just the first number
			$startPage = preg_replace("/^\D*(\d+).*/i", "\\1", $row['pages']); // extract starting page
			$endPage = extractDetailsFromField("pages", $pages, "[^0-9]+", "[-1]"); // extract ending page (function 'extractDetailsFromField()' is defined in 'include.inc.php')
			// NOTE: To extract the ending page, we'll use function 'extractDetailsFromField()'
			//       instead of just grabbing a matched regex pattern since it'll also work
			//       when just a number but no range is given (e.g. when startPage = endPage)

			// - 'prism:startingPage':
			if (preg_match("/\d+ *[$dash]+ *\d+/i$patternModifiers", $row['pages'])) // if there's a page range
				addMetaElement($entry, "prism", "startingPage", array(), $startPage);

			// - 'prism:endingPage':
			addMetaElement($entry, "prism", "endingPage", array(), $endPage);
		}


		// See also other potentially useful elements from arXiv Atom feeds:
		// (arXiv example: <http://export.arxiv.org/api/query?search_query=all:immunology&id_list=&start=0&max_results=30>)
		// 
		// <author>
		//   <name>
		//     Margarita Voitikova
		//   </name>
		//   <arxiv:affiliation xmlns:arxiv="http://arxiv.org/schemas/atom">
		//     Institute of Molecular and Atomic Physics, National Academy of Sciences of Belarus
		//   </arxiv:affiliation>
		// </author>
		// 
		// <arxiv:comment xmlns:arxiv="http://arxiv.org/schemas/atom">
		//   6 pages, 3 figures, submitted for publication
		// </arxiv:comment>
		// 
		// <arxiv:journal_ref xmlns:arxiv="http://arxiv.org/schemas/atom">
		//   Theory in Biosciences, 123, 431 (2005)
		// </arxiv:journal_ref>
		// 
		// <link title="doi" href="http://dx.doi.org/10.1016/j.physd.2005.03.004" rel="related" />


		return $entry;
	}

	// --------------------------------------------------------------------

	// Add a link to an Atom XML object:
	// 
	// Specs: <http://www.atomenabled.org/developers/syndication/#link>
	//        <http://www.atomenabled.org/developers/syndication/atom-format-spec.php#element.link>
	function atomLink(&$atom, $url, $linkRelation = "", $linkFormat = "", $linkTitle = "")
	{
		$linkType = "";
		$elementAttributeArray = array();

		// Define media types for the different formats:
		// TODO: add types for ADS, ISI and Word XML
		if (eregi("^HTML$", $linkFormat))
			$linkType = "text/html";

		elseif (eregi("^RTF$", $linkFormat))
			$linkType = "application/rtf";

		elseif (eregi("^BibTeX$", $linkFormat))
			$linkType = "application/x-bibtex";

		elseif (eregi("^Endnote$", $linkFormat))
			$linkType = "application/x-endnote-refer";

		elseif (eregi("^RIS$", $linkFormat))
			$linkType = "application/x-Research-Info-Systems";

		elseif (eregi("^Atom([ _]?XML)?$", $linkFormat))
			$linkType = "application/atom+xml";

		elseif (eregi("^RSS([ _]?XML)?$", $linkFormat))
			$linkType = "application/rss+xml";

		elseif (eregi("^((MODS|(OAI_)?DC)([ _]?XML)?|SRW([ _]?(MODS|DC))?([ _]?XML)?|unAPI)$", $linkFormat))
			$linkType = "application/xml";

		elseif (eregi("^ODF([ _]?XML)?$", $linkFormat))
			$linkType = "application/vnd.oasis.opendocument.spreadsheet";

		elseif (eregi("^OpenSearch$", $linkFormat))
			$linkType = "application/opensearchdescription+xml";

		elseif (eregi("^RTF$", $linkFormat))
			$linkType = "application/rtf";

		elseif (eregi("^PDF$", $linkFormat))
			$linkType = "application/pdf";

		elseif (eregi("^LaTeX([ _]?\.?bbl)?$", $linkFormat))
			$linkType = "application/x-latex";

		elseif (eregi("^(Markdown|ASCII)$", $linkFormat))
			$linkType = "text/plain";


		if (!empty($url))
		{
			// Add 'rel' attribute which contains a single link relationship type:
			// (predefined values: alternate, enclosure, related, self, via)
			if (!empty($linkRelation))
				$elementAttributeArray["rel"] = $linkRelation;

			// Add 'type' attribute which indicates the media type of the resource:
			if (!empty($linkType))
				$elementAttributeArray["type"] = $linkType;

			// Add 'title' attribute which contains human readable information about the link:
			// (typically for display purposes)
			if (!empty($linkTitle))
				$elementAttributeArray["title"] = $linkTitle;

			// Add 'href' attribute (required) which contains the URI of the referenced resource:
			// (typically a Web page)
			$elementAttributeArray["href"] = $url;

			// Add 'link' element as a new XML branch to the '$atom' object:
			addNewBranch($atom, "link", $elementAttributeArray, "");
		}


		return $atom;
	}

	// --------------------------------------------------------------------

	// Generate the basic OpenSearch Atom XML tree required for a query response:
	// ('$atomOperation' can be either "Error" or "Results")
	// 
	// Specs: <http://www.opensearch.org/Specifications/OpenSearch/1.1>
	//        <http://www.atomenabled.org/developers/syndication/>
	//        <http://www.atomenabled.org/developers/syndication/atom-format-spec.php>
	function atomGenerateBaseTags($atomOperation)
	{
		global $officialDatabaseName; // these variables are specified in 'ini.inc.php'
		global $databaseBaseURL;
		global $feedbackEmail;
		global $contentTypeCharset;
		global $convertExportDataToUTF8;
		global $logoImageURL;
		global $faviconImageURL;

		global $query;

		$atomCollection = new XML("feed");

		$atomCollection->setTagAttribute("xmlns", "http://www.w3.org/2005/Atom");
		$atomCollection->setTagAttribute("xmlns:opensearch", "http://a9.com/-/spec/opensearch/1.1/");
		$atomCollection->setTagAttribute("xmlns:unapi", "http://unapi.info/"); // NOTE: is the unAPI namespace ok? Or should we use "http://unapi.info/specs/", or maybe something like "http://purl.org/unapi/ns/" ?
		$atomCollection->setTagAttribute("xmlns:dc", "http://purl.org/dc/elements/1.1/");
		$atomCollection->setTagAttribute("xmlns:dcterms", "http://purl.org/dc/terms/");
		$atomCollection->setTagAttribute("xmlns:prism", "http://prismstandard.org/namespaces/1.2/basic/");

		$officialDatabaseNameConv = encodeHTMLspecialchars($officialDatabaseName); // function 'encodeHTMLspecialchars()' is defined in 'include.inc.php'

		if ($atomOperation != "Error") // for OpenSearch diagnostics (i.e. errors), Atom XML is served with the original database encoding, otherwise:
		{
			// convert database name to UTF-8:
			// (if '$convertExportDataToUTF8' is set to "yes" in 'ini.inc.php' and character encoding is not UTF-8 already)
			if (($convertExportDataToUTF8 == "yes") AND ($contentTypeCharset != "UTF-8"))
				$officialDatabaseNameConv = convertToCharacterEncoding("UTF-8", "IGNORE", $officialDatabaseNameConv); // function 'convertToCharacterEncoding()' is defined in 'include.inc.php'
		}

		// ----------------------------------------------------------

		// Add feed-level tags:
		// (not yet used: category, contributor, rights)

		// - 'title':
		addNewBranch($atomCollection, "title", array("type" => "text"), $officialDatabaseNameConv);

		// - 'subtitle':
		if ($atomOperation == "Error")
			addNewBranch($atomCollection, "subtitle", array(), "Search error!");
		else
		{
			// ...extract the 'WHERE' clause from the SQL query to include a natural-language version (well, sort of) within the 'subtitle' element:
			$queryWhereClause = extractWHEREclause($query); // function 'extractWHEREclause()' is defined in 'include.inc.php'

			// construct a meaningful feed description based on the actual 'WHERE' clause:
			// TODO: For Atom XML, the query string should not get HTML encoded!
			$subTitle = "Displays records where " . encodeHTML(explainSQLQuery($queryWhereClause)); // functions 'encodeHTML()' and 'explainSQLQuery()' are defined in 'include.inc.php'
			addNewBranch($atomCollection, "subtitle", array(), $subTitle);
		}

		// - 'updated':
		//    (TODO: the timestamp in the 'updated' element should really only get updated if any of the matching records was updated, right?)
		addNewBranch($atomCollection, "updated", array(), generateISO8601TimeStamp()); // function 'generateISO8601TimeStamp()' is defined in 'include.inc.php'

		// - 'author':
		$authorBranch = new XMLBranch("author");
		$authorBranch->setTagContent($officialDatabaseNameConv, "author/name");
		$authorBranch->setTagContent($feedbackEmail, "author/email");
		$authorBranch->setTagContent($databaseBaseURL, "author/uri");
		$atomCollection->addXMLBranch($authorBranch);

		// - 'generator', 'icon', 'logo':
		addNewBranch($atomCollection, "generator", array("uri" => "http://www.refbase.net/", "version" => "0.9.1"), "Web Reference Database (http://refbase.sourceforge.net)");
		addNewBranch($atomCollection, "icon", array(), $databaseBaseURL . $faviconImageURL);
		addNewBranch($atomCollection, "logo", array(), $databaseBaseURL . $logoImageURL);

		// - 'link' (more links will be added in function 'atomCollection()'):
		//   - link to OpenSearch Description file:
		atomLink($atomCollection, $databaseBaseURL . "opensearch.php?operation=explain", "search", "OpenSearch", $officialDatabaseNameConv);

		//   - link to unAPI server:
		atomLink($atomCollection, $databaseBaseURL . "unapi.php", "unapi:unapi-server", "unAPI", "unAPI");


		return $atomCollection;
	}

	// --------------------------------------------------------------------
?>
