<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/opensearch.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    09-Jan-08, 00:30
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This include file contains functions that'll return an OpenSearch response.
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

	// Return an OpenSearch description document if the OpenSearch client issued:
	// - .../opensearch.php?operation=explain
	// 
	// Spec:     <http://www.opensearch.org/Specifications/OpenSearch/1.1#OpenSearch_description_document>
	// See also: <http://developer.mozilla.org/en/docs/Creating_OpenSearch_plugins_for_Firefox>
	function openSearchDescription($exportStylesheet)
	{
		global $contentTypeCharset; // these variables are specified in 'ini.inc.php'
		global $convertExportDataToUTF8;
		global $officialDatabaseName;
		global $hostInstitutionName;
		global $hostInstitutionAbbrevName;
		global $feedbackEmail;
		global $databaseBaseURL;
		global $databaseKeywords;
		global $logoSmallImageURL;
		global $logoSmallImageWidth;
		global $logoSmallImageHeight;
		global $logoSmallImageType;
		global $faviconImageURL;

		global $loc; // defined in 'locales/core.php'

		$openSearchCollectionDoc = new XMLDocument();
		$openSearchCollectionDoc->setEncoding($contentTypeCharset);

		$openSearchCollection = openSearchGenerateBaseTags("Description");


		// --- begin search engine info -----------------------------

		// (note that we don't enforce any character length limits)

		// The 'ShortName' element contains a brief human-readable title that identifies this search engine:
		// (the value must contain 16 or fewer characters of plain text)
		addNewBranch($openSearchCollection, "ShortName", array(), "refbase (" . $hostInstitutionAbbrevName . ")"); // function 'addNewBranch()' is defined in 'webservice.inc.php'

		// The 'LongName' element contains an extended human-readable title that identifies this search engine:
		// (the value must contain 48 or fewer characters of plain text)
		addNewBranch($openSearchCollection, "LongName", array(), $officialDatabaseName);

		// The 'Description' element contains a human-readable text description of the search engine:
		// (the value must contain 1024 or fewer characters of plain text)
		addNewBranch($openSearchCollection,
		             "Description",
		             array(),
		             $officialDatabaseName . ": " . $loc["SearchMain"] . ". " . $loc["ThisDatabaseIsMaintained"] . " " . $hostInstitutionName . " (" . $hostInstitutionAbbrevName . ")."
		            );

		// The 'Tags' element contains a set of words that are used as keywords to identify and categorize the search content:
		// (the value must contain 256 or fewer characters of plain text; tags must be a single word and are delimited by the space character)
		addNewBranch($openSearchCollection, "Tags", array(), $databaseKeywords);

		// The 'Contact' element contains an email address at which the maintainer of the description document can be reached:
		// (the value must conform to the requirements of Section 3.4.1 "Addr-spec specification" in RFC 2822 <http://tools.ietf.org/html/rfc2822>)
		addNewBranch($openSearchCollection, "Contact", array(), $feedbackEmail);

		// The 'Attribution' element contains a list of all sources or entities that should be credited for the content contained in the search feed:
		// (the value must contain 256 or fewer characters of plain text)
//		addNewBranch($openSearchCollection, "Attribution", array(), "Search data copyright ..."); // uncomment and edit copyright statement if desired

		// The 'SyndicationRight' element contains a value that indicates the degree to which the search results provided by this search engine can be queried, displayed, and redistributed:
		// (possible values: "open", "limited", "private", "closed"; see <http://www.opensearch.org/Specifications/OpenSearch/1.1#The_.22SyndicationRight.22_element>)
		addNewBranch($openSearchCollection, "SyndicationRight", array(), "open");

		// The 'AdultContent' element contains a boolean value that should be set to true if the search results may contain material intended only for adults:
		// (possible values: "true", "false")
		addNewBranch($openSearchCollection, "AdultContent", array(), "false");

		// The 'Language' element contains a string that indicates that the search engine supports search results in the specified language:
		// (the value must conform to the XML 1.0 Language Identification, as specified by RFC 3066 in <http://tools.ietf.org/html/rfc3066>;
		//  in addition, a value of "*" signifies that the search engine does not restrict search results to any particular language)
		addNewBranch($openSearchCollection, "Language", array(), "*");

		// The 'InputEncoding' element contains a string that indicates that the search engine supports search requests encoded with the specified character encoding:
		// (the value must conform to the XML 1.0 Character Encodings, as specified by the IANA Character Set Assignments: <http://www.iana.org/assignments/character-sets>)
		addNewBranch($openSearchCollection, "InputEncoding", array(), $contentTypeCharset);

		// The 'OutputEncoding' element contains a string that indicates that the search engine supports search responses encoded with the specified character encoding:
		// (the value must conform to the XML 1.0 Character Encodings, as specified by the IANA Character Set Assignments: <http://www.iana.org/assignments/character-sets>)
		if (($convertExportDataToUTF8 == "yes") AND ($contentTypeCharset != "UTF-8"))
			addNewBranch($openSearchCollection, "OutputEncoding", array(), "UTF-8");
		else
			addNewBranch($openSearchCollection, "OutputEncoding", array(), $contentTypeCharset);

		// The 'Developer' element contains the human-readable name or identifier of the creator or maintainer of the description document:
		// (the value must contain 64 or fewer characters of plain text)
		addNewBranch($openSearchCollection, "Developer", array(), "Web Reference Database (http://refbase.sourceforge.net)");

		// The 'Query' element defines a search query that can be performed by search clients:
		// (Spec: <http://www.opensearch.org/Specifications/OpenSearch/1.1#OpenSearch_Query_element>)
		addNewBranch($openSearchCollection,
		             "Query",
		             array("role"        => "example",
		                   "title"       => "Sample search",
		                   "searchTerms" => "Miller", // search term example (we could also use ".+" but an ubiquitous author name such as "Miller" seems more intuitive and almost always results in some hits)
		                   "startIndex"  => "1", // index number of the first search result, starting with one
		                   "count"       => $_SESSION['userRecordsPerPage'] // default number of records per page preferred by the current user
		                  ),
		             ""
		            );

		// The 'Image' element contains a URL that identifies the location of an image that can be used in association with the search content:
		// (images with square aspect ratios are recommended, e.g. a 16x16 image of type ".ico" and a 64x64 image of type ".jpeg" or ".png")
		// - favicon image (16x16):
		addNewBranch($openSearchCollection,
		             "Image",
		             array("type"   => "image/x-icon", // MIME type of this image
		                   "height" => "16", // image height, in pixels
		                   "width"  => "16" // image width, in pixels
		                  ),
		             $databaseBaseURL . $faviconImageURL
		            );

		// - small logo image (e.g. 64x64):
		addNewBranch($openSearchCollection,
		             "Image",
		             array("type"   => $logoSmallImageType,
		                   "height" => $logoSmallImageHeight,
		                   "width"  => $logoSmallImageWidth
		                  ),
		             $databaseBaseURL . $logoSmallImageURL
		            );

		// --- end search engine info -------------------------------


		// --- begin URL templates ----------------------------------

		// The 'Url' element describes an interface by which a search client can make search requests of the search engine:
		// - URL template for output of OpenSearch Atom XML (which is the default):
		addNewBranch($openSearchCollection,
		             "Url",
		             array("type"        => "application/atom+xml", // MIME type of the search result format
		                   "template"    => $databaseBaseURL . "opensearch.php?query={searchTerms}&amp;startRecord={startIndex?}&amp;maximumRecords={count?}&amp;recordSchema=atom", // search URL template to be processed according to the OpenSearch URL template syntax
		                   "indexOffset" => "1", // index number of the first search result, starting with one
		//                 "pageOffset" => "1" // page number of the first set of search results (NOTE: currently, page-based searches are not supported by refbase)
		                  ),
		             ""
		            );

		// - URL template for output of RSS XML:
		addNewBranch($openSearchCollection,
		             "Url",
		             array("type"        => "application/rss+xml",
		                   "template"    => $databaseBaseURL . "opensearch.php?query={searchTerms}&amp;startRecord={startIndex?}&amp;maximumRecords={count?}&amp;recordSchema=rss",
		                   "indexOffset" => "1"
		                  ),
		             ""
		            );

		// - URL template for output of SRW_DC XML:
		addNewBranch($openSearchCollection,
		             "Url",
		             array("type"        => "application/xml",
		                   "template"    => $databaseBaseURL . "opensearch.php?query={searchTerms}&amp;startRecord={startIndex?}&amp;maximumRecords={count?}&amp;recordSchema=srw_dc",
		                   "indexOffset" => "1"
		                  ),
		             ""
		            );

		// - URL template for output of SRW_MODS XML:
		addNewBranch($openSearchCollection,
		             "Url",
		             array("type"        => "application/xml",
		                   "template"    => $databaseBaseURL . "opensearch.php?query={searchTerms}&amp;startRecord={startIndex?}&amp;maximumRecords={count?}&amp;recordSchema=srw_mods",
		                   "indexOffset" => "1"
		                  ),
		             ""
		            );

		// - URL template for output of HTML:
		addNewBranch($openSearchCollection,
		             "Url",
		             array("type"        => "text/html",
		                   "template"    => $databaseBaseURL . "opensearch.php?query={searchTerms}&amp;startRecord={startIndex?}&amp;maximumRecords={count?}&amp;recordSchema=html",
		                   "indexOffset" => "1"
		                  ),
		             ""
		            );

		// - URL template for output of JSON-formatted search suggestions:
		// 
		//   NOTE: An URL template with 'type="application/x-suggestions+json"' is used by Firefox
		//         to specify the URL to use for fetching search suggestions in JSON format
		//         See also: <http://developer.mozilla.org/en/Creating_OpenSearch_plugins_for_Firefox>
		//                   <http://developer.mozilla.org/en/Supporting_search_suggestions_in_search_plugins>
		//                   <http://www.opensearch.org/Specifications/OpenSearch/Extensions/Suggestions/1.0>
		//                   <http://hublog.hubmed.org/archives/001681.html>
		addNewBranch($openSearchCollection,
		             "Url",
		             array("type"        => "application/x-suggestions+json",
		                   "template"    => $databaseBaseURL . "opensearch.php?query={searchTerms}&amp;startRecord={startIndex?}&amp;maximumRecords={count?}&amp;recordSchema=json&amp;operation=suggest&amp;client=sug-refbase_suggest-1.0",
		                   "indexOffset" => "1"
		                  ),
		             ""
		            );

		// --- end URL templates ------------------------------------


		// --- begin Mozilla-specific elements ----------------------

		// The 'SearchForm' element contains the URL to go to to open up the search page at the site for which the plugin is designed to search:
		// (this provides a way for Firefox to let the user visit the web site directly)
		addNewBranch($openSearchCollection, "mozilla:SearchForm", array(), $databaseBaseURL); // this will show the main refbase page with the Quick Search form (to link to other search pages, append '. "simple_search.php"' etc)

		// --- end Mozilla-specific elements ------------------------


		$openSearchCollectionDoc->setXML($openSearchCollection);
		$openSearchCollectionString = $openSearchCollectionDoc->getXMLString();

		// Add the XML Stylesheet definition:
		// Note that this is just a hack (that should get fixed) since I don't know how to do it properly using the ActiveLink PHP XML Package ?:-/
		if (!empty($exportStylesheet))
			$openSearchCollectionString = preg_replace("/(?=\<OpenSearchDescription)/i","<?xml-stylesheet type=\"text/xsl\" href=\"" . $exportStylesheet . "\"?>\n",$openSearchCollectionString);

		return $openSearchCollectionString;
	}

	// --------------------------------------------------------------------

	// Return OpenSearch diagnostics (i.e. OpenSearch error information) wrapped into OpenSearch Atom XML:
	function openSearchDiagnostics($diagCode, $diagDetails, $exportStylesheet)
	{
		global $contentTypeCharset; // defined in 'ini.inc.php'

		// Map SRU/W diagnostic numbers to their corresponding messages:
		// (i.e., for OpenSearch diagnostics, we simply re-use the SRU/W diagnostics)
		$diagMessages = mapSRWDiagnostics(); // function 'mapSRWDiagnostics()' is defined in 'webservice.inc.php'

		if (isset($diagMessages[$diagCode]))
			$diagMessage = $diagMessages[$diagCode];
		else
			$diagMessage = "Unknown error";

		$atomCollectionDoc = new XMLDocument();
		$atomCollectionDoc->setEncoding($contentTypeCharset);

		$atomCollection = openSearchGenerateBaseTags("Error");

		// add feed-level tags:

		// - 'id':
		addNewBranch($atomCollection, "id", array(), "info:srw/diagnostic/1/"); // could something else be used as diagnostics feed ID instead?

		// - OpenSearch elements:
		addNewBranch($atomCollection, "opensearch:totalResults", array(), "1");
		addNewBranch($atomCollection, "openSearch:startIndex", array(), "1");
		addNewBranch($atomCollection, "openSearch:itemsPerPage", array(), "1");

		$diagnosticsBranch = new XMLBranch("entry");

		// add entry-level tags:
		addNewBranch($diagnosticsBranch, "title", array(), $diagMessage);
//		addNewBranch($atomCollection, "link", array("href" => ""), ""); // TODO (what could be used as link for a diagnostics entry?)
		addNewBranch($diagnosticsBranch, "updated", array(), generateISO8601TimeStamp()); // function 'generateISO8601TimeStamp()' is defined in 'include.inc.php'
		addNewBranch($diagnosticsBranch, "id", array(), "info:srw/diagnostic/1/" . $diagCode);

		$diagContent = $diagMessage;
		if (!empty($diagDetails))
			$diagContent .= ": " . $diagDetails;

		addNewBranch($diagnosticsBranch, "content", array("type" => "text"), "Error " . $diagCode . ": " . $diagContent); // TODO: I18n

		$atomCollection->addXMLBranch($diagnosticsBranch);

		$atomCollectionDoc->setXML($atomCollection);
		$atomCollectionString = $atomCollectionDoc->getXMLString();

		return $atomCollectionString;
	}

	// --------------------------------------------------------------------

	// Generate the basic OpenSearch XML tree required for a query response:
	function openSearchGenerateBaseTags($openSearchOperation)
	{
		if ($openSearchOperation == "Error") // OpenSearch Atom XML is used for diagnostics
		 	$atomCollection = atomGenerateBaseTags($openSearchOperation); // function 'atomGenerateBaseTags()' is defined in 'atomxml.inc.php'

		elseif ($openSearchOperation == "Description") // OpenSearch Description XML
		{
			$atomCollection = new XML("OpenSearchDescription");

			$atomCollection->setTagAttribute("xmlns", "http://a9.com/-/spec/opensearch/1.1/");
			$atomCollection->setTagAttribute("xmlns:opensearch", "http://a9.com/-/spec/opensearch/1.1/");
			$atomCollection->setTagAttribute("xmlns:mozilla", "http://www.mozilla.org/2006/browser/search/");
		}

		return $atomCollection;
	}

	// --------------------------------------------------------------------
?>
