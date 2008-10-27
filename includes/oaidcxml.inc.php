<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/oaidcxml.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    05-Mar-08, 21:52
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This include file contains functions that'll export records to OAI_DC XML.
	// Requires ActiveLink PHP XML Package, which is available under the GPL from:
	// <http://www.active-link.com/software/>
	// TODO: I18n


	// Incorporate some include files:
	include_once 'includes/webservice.inc.php'; // include functions that are commonly used with the refbase webservices

	// Import the ActiveLink Packages
	require_once("classes/include.php");
	import("org.active-link.xml.XML");
	import("org.active-link.xml.XMLDocument");

	// --------------------------------------------------------------------

	// Return records as OAI_DC (i.e. simple/unqualified Dublin Core) XML as required
	// by the Open Archives Initiative Protocol for Metadata Harvesting (OAI-PMH):
	// 
	// Spec:   <http://www.openarchives.org/OAI/openarchivesprotocol.html>
	// Guides: <http://www.oaforum.org/tutorial/english/page5.htm>
	//         <http://dublincore.org/documents/dc-xml-guidelines/>
	function oaidcCollection($result)
	{
		global $contentTypeCharset; // these variables are defined in 'ini.inc.php'
		global $convertExportDataToUTF8;

		global $citeKeysArray; // '$citeKeysArray' is made globally available from
		                       // within this function

		// Individual records are objects and collections of records are strings

		$oaidcCollectionDoc = new XMLDocument();

		if (($convertExportDataToUTF8 == "yes") AND ($contentTypeCharset != "UTF-8"))
			$oaidcCollectionDoc->setEncoding("UTF-8");
		else
			$oaidcCollectionDoc->setEncoding($contentTypeCharset);

		$oaidcCollection = new XML("dcCollection");
		$oaidcCollection->setTagAttribute("xmlns:oai_dc", "http://www.openarchives.org/OAI/2.0/oai_dc/");
		$oaidcCollection->setTagAttribute("xmlns:dc", "http://purl.org/dc/elements/1.1/");
		$oaidcCollection->setTagAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
		$oaidcCollection->setTagAttribute("xsi:schemaLocation", "http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd");

		// ----------------------------------------------------------

		// Add OAI_DC XML entries:

		$exportArray = array(); // array for individually exported records
		$citeKeysArray = array(); // array of cite keys (used to ensure uniqueness of cite keys among all exported records)

		// Generate the export for each record and push them onto an array:
		while ($row = @ mysql_fetch_array($result))
		{
			// Export the current record as OAI_DC XML:
			$record = oaidcRecord($row, "oai_dc");

			if (!empty($record)) // unless the record buffer is empty...
				array_push($exportArray, $record); // ...add it to an array of exports
		}

		// for each of the OAI_DC XML entries in the result set...
		foreach ($exportArray as $oaidc)
			$oaidcCollection->addXMLasBranch($oaidc);

		$oaidcCollectionDoc->setXML($oaidcCollection);
		$oaidcCollectionString = $oaidcCollectionDoc->getXMLString();

		return $oaidcCollectionString;
	}

	// --------------------------------------------------------------------

	// Generate an OAI_DC (i.e. simple/unqualified Dublin Core) XML record:
	// (returns an XML object (oaidc) of a single record)
	// 
	// TODO: - see inline comments labeled with "TODO"
	function oaidcRecord($row, $metadataPrefix = "oai_dc", $addNameSpaceInfo = true)
	{
		global $databaseBaseURL; // these variables are defined in 'ini.inc.php'
		global $contentTypeCharset;
		global $fileVisibility;
		global $fileVisibilityException;
		global $filesBaseURL;
		global $convertExportDataToUTF8;
		global $defaultCiteStyle;

		global $citeStyle;

		global $alnum, $alpha, $cntrl, $dash, $digit, $graph, $lower, $print, $punct, $space, $upper, $word, $patternModifiers; // defined in 'transtab_unicode_charset.inc.php' and 'transtab_latin1_charset.inc.php'

		// The array '$transtab_refbase_unicode' contains search & replace patterns for conversion from refbase markup to Unicode entities.
		global $transtab_refbase_unicode; // defined in 'transtab_refbase_unicode.inc.php'

		// The array '$transtab_refbase_ascii' contains search & replace patterns for conversion from refbase markup to plain text.
		global $transtab_refbase_ascii; // defined in 'transtab_refbase_ascii.inc.php'

		// Define inline text markup to generate a plain text citation string:
		// (to be included within a 'dcterms:bibliographicCitation' element)
		$markupPatternsArrayPlain = array("bold-prefix"        => "", // NOTE: should we rather keep refbase font-shape markup (like _italic_ and **bold**) for plain text output?
		                                  "bold-suffix"        => "",
		                                  "italic-prefix"      => "",
		                                  "italic-suffix"      => "",
		                                  "underline-prefix"   => "",
		                                  "underline-suffix"   => "",
		                                  "endash"             => "-",
		                                  "emdash"             => "-",
		                                  "ampersand"          => "&",
		                                  "double-quote"       => '"',
		                                  "double-quote-left"  => '"',
		                                  "double-quote-right" => '"',
		                                  "single-quote"       => "'",
		                                  "single-quote-left"  => "'",
		                                  "single-quote-right" => "'",
		                                  "less-than"          => "<",
		                                  "greater-than"       => ">",
		                                  "newline"            => "\n"
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

		// Fetch the name of the citation style file that's associated with the style given in '$citeStyle':
		$citeStyleFile = getStyleFile($citeStyle); // function 'getStyleFile()' is defined in 'include.inc.php'

		if (empty($citeStyleFile))
		{
			$citeStyle = $defaultCiteStyle; // if the given cite style could not be found, we'll use the default cite style which is defined by the '$defaultCiteStyle' variable in 'ini.inc.php'
			$citeStyleFile = getStyleFile($citeStyle);
		}

		// Include the found citation style file *once*:
		include_once "cite/" . $citeStyleFile;

		// Generate a proper citation for this record, ordering attributes according to the chosen output style & record type:
		// - Plain text version of citation string:
		$recordCitationPlain = citeRecord($row, $citeStyle, "", $markupPatternsArrayPlain, false); // function 'citeRecord()' is defined in the citation style file given in '$citeStyleFile' (which, in turn, must reside in the 'styles' directory of the refbase root directory)

		//   Convert any refbase markup that remains in the citation string (such as _italic_ or **bold**) to plain text:
		$recordCitationPlain = searchReplaceText($transtab_refbase_ascii, $recordCitationPlain, true);

		// Convert any remaining refbase markup in the 'title', 'keywords' & 'abstract' fields to plain text:
		$row['title'] = searchReplaceText($transtab_refbase_ascii, $row['title'], true);
		$row['keywords'] = searchReplaceText($transtab_refbase_ascii, $row['keywords'], true);
		$row['abstract'] = searchReplaceText($transtab_refbase_ascii, $row['abstract'], true);

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
		// TODO: - the URL-generating code should be made into a dedicated function (since it's shared with 'modsxml.inc.php' and 'atomxml.inc.php')
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

		// Start OAI_DC XML record:

		if (!empty($metadataPrefix))
			$recordPrefix = $metadataPrefix . ":";

		$record = new XML($recordPrefix . "dc"); // create an XML object for a single record

		if ($addNameSpaceInfo)
		{
			if ($metadataPrefix == "oai_dc")
				$record->setTagAttribute("xmlns:oai_dc", "http://www.openarchives.org/OAI/2.0/oai_dc/");
			elseif ($metadataPrefix == "srw_dc")
				$record->setTagAttribute("xmlns:srw_dc", "info:srw/schema/1/dc-v1.1");

			$record->setTagAttribute("xmlns:dc", "http://purl.org/dc/elements/1.1/");

			if ($metadataPrefix == "oai_dc") // NOTE: should we include these for 'srw_dc:dc' output as well?
			{
				$record->setTagAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
				$record->setTagAttribute("xsi:schemaLocation", "http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd");
			}
			elseif ($metadataPrefix == "srw_dc")
				$record->setTagAttribute("xmlns:prism", "http://prismstandard.org/namespaces/1.2/basic/");
		}


		// Add Dublin Core elements:
		// NOTE: With a few exceptions, we try to adhere to the guidelines given at
		//       "Using simple Dublin Core to describe eprints" by Andy Powell et al.
		//       See: <http://eprints-uk.rdn.ac.uk/project/docs/simpledc-guidelines/>

		// - 'dc:title':
		if (!empty($row['title']))
			addMetaElement($record, "dc", "title", array(), $row['title']); // function 'addMetaElement()' is defined in 'webservice.inc.php'

		// - 'dc:creator':
		if (!empty($row['author']) AND ($row['author'] != $row['editor']))
			addMetaElement($record, "dc", "creator", array(), $row['author']);

		// - 'dc:creator':
		//   TODO: add refbase corporate author(s) as 'dc:creator'

		// - 'dc:contributor':
		if (!empty($row['editor']))
			addMetaElement($record, "dc", "contributor", array(), $row['editor']);

		// - 'dc:description':
		if (!empty($row['abstract']))
			addMetaElement($record, "dc", "description", array(), $row['abstract']);

		// - 'dc:identifier':

		//   - DOI:
		if (!empty($row['doi']))
			addMetaElement($record, "dc", "identifier", array(), $row['doi'], "doi");

		//   - PMID:
		if (!empty($row['notes']) AND preg_match("/PMID *: *\d+/i", $row['notes']))
			addMetaElement($record, "dc", "identifier", array(), $row['notes'], "pmid");

		//   - arXiv:
		if (!empty($row['notes']) AND preg_match("/arXiv *: *[^ ;]+/i", $row['notes']))
			addMetaElement($record, "dc", "identifier", array(), $row['notes'], "arxiv");

		//   - ISBN:
		if (!empty($row['isbn']))
			addMetaElement($record, "dc", "identifier", array(), $row['isbn'], "isbn");

		//   - OpenURL:
		addMetaElement($record, "dc", "identifier", array(), $openURL, "openurl");

		//   - refbase ID:
		addMetaElement($record, "dc", "identifier", array(), $databaseBaseURL . generateURL("show.php", "html", array("record" => $row['serial']), true), "url");

		//   - Cite key:
		addMetaElement($record, "dc", "identifier", array(), $citeKey, "citekey");

		//   - Bibliographic citation:
		//     NOTE: In 'atomxml.inc.php', the bibliographic citation is put into a
		//           'dcterms:bibliographicCitation' element so that it can be uniquely
		//           identified and extracted easily. However, in case of simple Dublin
		//           Core output, we just put it into a 'dc:identifier' element and
		//           use a "citation:" prefix.
		addMetaElement($record, "dc", "identifier", array(), encodeHTMLspecialchars($recordCitationPlain), "citation");

		// - 'dc:source':
		//   NOTE: - In <http://eprints-uk.rdn.ac.uk/project/docs/simpledc-guidelines/>,
		//           Andy Powell et al. recommend that this element should NOT be used!
		//           However, we use 'dc:source' elements for publication & series info
		//           (publication/series title plus volume & issue) to provide a dedicated
		//           source string that's easily readable and parsable.
		//           Example: <dc:source>Polar Biology, Vol. 25, No. 10</dc:source>
		//         - While we could also append the page info to the publication
		//           'dc:source' element, this info is more pertinent to the article
		//           itself and is thus not included. For 'srw_dc:dc' output, page info is
		//           included in PRISM elements (see below).
		//         - All metadata (including the page info) are also provided as a machine
		//           parsable citation in form of an OpenURL ContextObject (see above).

		//   - Publication info:
		//     NOTE: We only include the 'dc:source' element for 'oai_dc:dc' output. In case of 'srw_dc:dc'
		//           output, we use the more fine-grained PRISM elements instead (see below)
		if (($metadataPrefix == "oai_dc") AND (!empty($row['publication']) OR !empty($row['abbrev_journal'])))
		{
			if (!empty($row['publication']))
				$source = $row['publication'];
			elseif (!empty($row['abbrev_journal']))
				$source = $row['abbrev_journal'];

			if (!empty($row['volume']))
				$source .= ", Vol. " . $row['volume'];

			if (!empty($row['issue']))
				$source .= ", No. " . $row['issue'];

			if (!empty($source))
				addMetaElement($record, "dc", "source", array(), $source);
		}

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
				addMetaElement($record, "dc", "source", array(), $series);
				// NOTE: To distinguish between regular publication & series info,
				//       should we better use a "series:" prefix here? If so, use:
//				addMetaElement($record, "dc", "source", array(), $series, "series");
		}

		//   - ISSN:
		//     NOTE: for 'srw_dc:dc' output, we put the ISSN into the 'prism:issn' element
		if (($metadataPrefix == "oai_dc") AND !empty($row['issn']))
			addMetaElement($record, "dc", "source", array(), $row['issn'], "issn");

		// - 'dc:date':
		if (!empty($row['year']))
			addMetaElement($record, "dc", "date", array(), $row['year']);

		// - 'dc:type':
		if (!empty($row['type']))
			addMetaElement($record, "dc", "type", array(), $row['type'], $row['thesis']);

		//   In case of a thesis, we add another 'dc:type' element with the actual thesis type:
		if (!empty($row['thesis']))
			addMetaElement($record, "dc", "type", array(), $row['thesis']);

		// - 'dc:format':
		//   TODO: ideally, we should parse the content of the refbase 'medium' field and map it
		//         to a media-type term from <http://www.iana.org/assignments/media-types/>
		if (!empty($row['medium']))
			$mediaType = $row['medium'];
		else
			$mediaType = "text";

		addMetaElement($record, "dc", "format", array(), $mediaType);

		// - 'dc:subject':
		//   TODO: add user-specific keywords (from field 'user_keys') if the user is logged in
		if (!empty($row['keywords']))
			addMetaElement($record, "dc", "subject", array(), $row['keywords']);

		// - 'dc:coverage':
		//   TODO: should we add contents from the refbase 'area' field as 'dc:coverage' element(s)?

		// - 'dc:relation':

		//   - Related URL:
		if (!empty($row['url']))
			addMetaElement($record, "dc", "relation", array(), $row['url'], "url");

		//   - Related FILE:
		if ($printURL)
			addMetaElement($record, "dc", "relation", array(), $URLprefix . $row['file'], "file");

		// - 'dc:publisher':
		if (!empty($row['publisher']))
			addMetaElement($record, "dc", "publisher", array(), $row['publisher']);

		// - 'dc:language':
		//   TODO: convert to ISO notation (i.e. "en" instead of "English", etc)
		if (!empty($row['language']))
			addMetaElement($record, "dc", "language", array(), $row['language']);


		// ----------------------------------------------------------

		// Add PRISM elements:
		// NOTE: When using the 'srw_dc' namespace (i.e. 'info:srw/schema/1/dc-v1.1' as detailed at
		//       <http://www.loc.gov/standards/sru/resources/dc-schema.html>), I don't think it's allowed
		//       to include anything but the fifteen elements from simple Dublin Core. Is this correct?
		//       If so, then:
		// 
		// TODO: Do we need to put the PRISM elements in <extraRecordData> instead? Or can we put them within
		//       a separate branch outside of (and next to) the '<srw_dc:dc>' element? Or shall we better omit
		//       them entirely?
		//       More info on SRU Extra Data>: <http://www.loc.gov/standards/sru/specs/extra-data.html>
		// 
		//       See also "Mixing DC metadata with other metadata schemas" in "Guidelines for implementing
		//       Dublin Core in XML" <http://dublincore.org/documents/dc-xml-guidelines/>

		if ($metadataPrefix == "srw_dc") // we only include PRISM elements for 'srw_dc:dc' output
		{
			// - 'prism:issn':
			if (!empty($row['issn']))
				addMetaElement($record, "prism", "issn", array(), $row['issn']);

			// - 'prism:publicationName':
			if (!empty($row['publication']))
				addMetaElement($record, "prism", "publicationName", array(), $row['publication']);
			elseif (!empty($row['abbrev_journal']))
				addMetaElement($record, "prism", "publicationName", array(), $row['abbrev_journal']);

			// - 'prism:publicationDate':
			if (!empty($row['year']))
				addMetaElement($record, "prism", "publicationDate", array(), $row['year']);

			// - 'prism:volume':
			if (!empty($row['volume']))
				addMetaElement($record, "prism", "volume", array(), $row['volume']);

			// - 'prism:number':
			if (!empty($row['issue']))
				addMetaElement($record, "prism", "number", array(), $row['issue']);

			// - 'prism:startingPage', 'prism:endingPage':
			//   TODO: Similar code is used in 'include.in.php', 'modsxml.inc.php' and 'openurl.inc.php',
			//         so this should be made into a dedicated function!
			if (!empty($row['pages']) AND preg_match("/\d+/i", $row['pages'])) // if the 'pages' field contains a number
			{
				$pages = preg_replace("/^\D*(\d+)( *[$dash]+ *\d+)?.*/i$patternModifiers", "\\1\\2", $row['pages']); // extract page range (if there's any), otherwise just the first number
				$startPage = preg_replace("/^\D*(\d+).*/i", "\\1", $row['pages']); // extract starting page
				$endPage = extractDetailsFromField("pages", $pages, "/\D+/", "[-1]"); // extract ending page (function 'extractDetailsFromField()' is defined in 'include.inc.php')
				// NOTE: To extract the ending page, we'll use function 'extractDetailsFromField()'
				//       instead of just grabbing a matched regex pattern since it'll also work
				//       when just a number but no range is given (e.g. when startPage = endPage)

				// - 'prism:startingPage':
				if (preg_match("/\d+ *[$dash]+ *\d+/i$patternModifiers", $row['pages'])) // if there's a page range
					addMetaElement($record, "prism", "startingPage", array(), $startPage);

				// - 'prism:endingPage':
				addMetaElement($record, "prism", "endingPage", array(), $endPage);
			}
		}


		return $record;
	}

	// --------------------------------------------------------------------
?>
