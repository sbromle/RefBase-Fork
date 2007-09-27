<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/srwxml.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de> and
	//             Richard Karnesky <mailto:karnesky@gmail.com>
	//
	// Created:    17-May-05, 16:38
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This include file contains functions that'll export records to SRW XML.
	// Requires ActiveLink PHP XML Package, which is available under the GPL from:
	// <http://www.active-link.com/software/>. See 'sru.php' for more info.


	// Incorporate some include files:
	include_once 'includes/webservice.inc.php'; // include functions that are commonly used with the refbase webservices

	// Import the ActiveLink Packages
	require_once("classes/include.php");
	import("org.active-link.xml.XML");
	import("org.active-link.xml.XMLDocument");

	// --------------------------------------------------------------------

	// Return MODS XML records wrapped into SRW XML ('searchRetrieveResponse'):
	function srwCollection($result, $rowOffset, $showRows, $exportStylesheet, $displayType)
	{
		global $contentTypeCharset; // these variables are defined in 'ini.inc.php'
		global $convertExportDataToUTF8;

		// Individual records are objects and collections of records are strings

		$srwCollectionDoc = new XMLDocument();

		if (($convertExportDataToUTF8 == "yes") AND ($contentTypeCharset != "UTF-8"))
			$srwCollectionDoc->setEncoding("UTF-8");
		else
			$srwCollectionDoc->setEncoding($contentTypeCharset);

		$srwCollection = srwGenerateBaseTags("searchRetrieveResponse");

		$showRowsOriginal = $showRows; // save original value of '$showRows' (which may get modified by the 'seekInMySQLResultsToOffset()' function below)

		// Find out how many rows are available and (if there were rows found) seek to the current offset:
		// function 'seekInMySQLResultsToOffset()' is defined in 'include.inc.php'
		list($result, $rowOffset, $showRows, $rowsFound, $previousOffset, $nextOffset, $showMaxRow) = seekInMySQLResultsToOffset($result, $rowOffset, $showRows, $displayType, "");

		addNewBranch($srwCollection, "srw:numberOfRecords", array(), $rowsFound); // function 'addNewBranch()' is defined in 'webservice.inc.php'

		// <srw:resultSetId> not supported
		// <srw:resultSetIdleTime> not supported

		$srwRecordsBranch = new XMLBranch("srw:records");

		if ($showRowsOriginal != 0) // we omit the records list in the response if the SRU query did contain 'maximumRecords=0'
		{
			$exportArray = array(); // Array for individually exported records

			// Generate the export for each record and push them onto an array:
			for ($rowCounter=0; (($rowCounter < $showRows) && ($row = @ mysql_fetch_array($result))); $rowCounter++)
			{
				// Export the current record as MODS XML:
				$record = modsRecord($row); // function 'modsRecord()' is defined in 'modsxml.inc.php'

				if (!empty($record)) // unless the record buffer is empty...
					array_push($exportArray, $record); // ...add it to an array of exports
			}

			$i = $rowOffset; // initialize counter

			// for each of the MODS records in the result set...
			foreach ($exportArray as $mods)
			{
				++$i; // increment $i by one, then return $i

				$srwRecordBranch = new XMLBranch("srw:record");

				srwGeneratePackingSchema($srwRecordBranch, "xml", "mods");

				$srwRecordDataBranch = new XMLBranch("srw:recordData");

				// NOTE: converting the MODS object into a string to perform search & replace actions
				//       may be very clumsy but I don't know any better... ?:-/
				$modsString = $mods->getXMLString();
				$modsString = preg_replace('/<mods/i','<mods xmlns="http://www.loc.gov/mods/v3"',$modsString);
				// alternatively to the above line we could add a 'mods:' identifier to all MODS XML tags:
//				$modsString = preg_replace("#<(/)?#","<\\1mods:",$modsString);
				$mods->removeAllBranches();
				$mods->parseFromString($modsString);

				$srwRecordDataBranch->addXMLasBranch($mods);
				$srwRecordBranch->addXMLBranch($srwRecordDataBranch);

				addNewBranch($srwRecordBranch, "srw:recordPosition", array(), $i);

				$srwRecordsBranch->addXMLBranch($srwRecordBranch);
			}
		}

		$srwCollection->addXMLBranch($srwRecordsBranch);

		if (($showRowsOriginal != 0) && ($showMaxRow < $rowsFound)) // show 'nextRecordPosition' if the SRU query did not contain 'maximumRecords=0' and if there are any remaining records to be displayed
			addNewBranch($srwCollection, "srw:nextRecordPosition", array(), ($showMaxRow + 1));

		$srwCollectionDoc->setXML($srwCollection);
		$srwCollectionString = $srwCollectionDoc->getXMLString();

		// Add the XML Stylesheet definition:
		// Note that this is just a hack (that should get fixed) since I don't know how to do it properly using the ActiveLink PHP XML Package ?:-/
		if (!empty($exportStylesheet))
			$srwCollectionString = preg_replace("/(?=\<srw:searchRetrieveResponse)/i","<?xml-stylesheet type=\"text/xsl\" href=\"" . $exportStylesheet . "\"?>\n",$srwCollectionString);

		return $srwCollectionString;
	}

	// --------------------------------------------------------------------

	// return an SRW 'explainResponse' if the SRW/U client issued either of the following:
	// - http://.../refs/sru.php?operation=explain
	// - http://.../refs/sru.php?
	// - http://.../refs/sru.php
	function srwExplainResponse($exportStylesheet)
	{
		global $contentTypeCharset; // these variables are specified in 'ini.inc.php'
		global $databaseBaseURL;
		global $officialDatabaseName;
		global $hostInstitutionName;
		global $feedbackEmail;
		global $logoImageURL;
		global $defaultLanguage;

		global $loc; // defined in 'locales/core.php'

		$srwCollectionDoc = new XMLDocument();
		$srwCollectionDoc->setEncoding($contentTypeCharset);

		$srwCollection = srwGenerateBaseTags("explainResponse");

		$srwRecordBranch = new XMLBranch("srw:record");

		srwGeneratePackingSchema($srwRecordBranch, "xml", "zeerex");

		$srwRecordDataBranch = new XMLBranch("srw:recordData");

		$srwExplainBranch = new XMLBranch("explain");
		$srwExplainBranch->setTagAttribute("xmlns", "http://explain.z3950.org/dtd/2.0/");
		$srwExplainBranch->setTagAttribute("xmlns:refb", "http://refbase.net/");


		// extract the protocol from the base URL:
		if (preg_match("#^([^:]+)://.*#",$databaseBaseURL))
			$databaseProtocol = preg_replace("#^([^:]+)://.*#","\\1",$databaseBaseURL);
		else
			$databaseProtocol = "";

		// extract the host from the base URL:
		if (preg_match("#^[^:]+://(?:www\.)?[^/]+.*#",$databaseBaseURL))
			$databaseHost = preg_replace("#^[^:]+://(?:www\.)?([^/]+).*#","\\1",$databaseBaseURL);
		else
			$databaseHost = $databaseBaseURL;

		// extract the path on server from the base URL:
		if (preg_match("#^[^:]+://(?:www\.)?[^/]+/.+#",$databaseBaseURL))
			$databasePathOnServer = preg_replace("#^[^:]+://(?:www\.)?[^/]+/(.+)#","\\1",$databaseBaseURL);
		else
			$databasePathOnServer = "";

		// get the total number of records in the database:
		$recordCount = getTotalNumberOfRecords(); // function 'getTotalNumberOfRecords()' is defined in 'include.inc.php'

		// get the default number of records per page preferred by the current user:
		$showRows = $_SESSION['userRecordsPerPage'];

		// get date/time information when the database was last modified:
		$lastModified = getLastModifiedDateTime(); // function 'getLastModifiedDateTime()' is defined in 'include.inc.php'


		// --- begin server info ------------------------------------
		$srwServerInfoBranch = new XMLBranch("serverInfo");
		$srwServerInfoBranch->setTagAttribute("protocol", "SRU");
		$srwServerInfoBranch->setTagAttribute("version", "1.1");
		if (!empty($databaseProtocol))
			$srwServerInfoBranch->setTagAttribute("transport", $databaseProtocol);

		$srwServerInfoBranch->setTagContent($databaseHost, "serverInfo/host");
		$srwServerInfoBranch->setTagContent("80", "serverInfo/port"); // NOTE: this should really be a variable in 'ini.inc.php' or such

		addNewBranch($srwServerInfoBranch, "database", array("numRecs" => $recordCount, "lastUpdate" => $lastModified), $databasePathOnServer . "sru.php"); // function 'addNewBranch()' is defined in 'webservice.inc.php'

		// IMPORTANT: if you want to allow remote users who are NOT logged in (userID=0) to query the refbase database
		//            via 'sru.php' then either the 'Export' or the 'Batch export' user permission needs to be
		//            enabled at 'user_options.php?userID=0'. This will allow export of XML records via 'sru.php'
		//            but won't allow a user who isn't logged in to export records via the web interface. However, you
		//            should be aware that a direct GET query like 'show.php?author=miller&submit=Export&exportFormat=MODS%20XML'
		//            will be also allowed then!

		// As an alternative, you can provide explicit login information within the 'serverInfo/authentication' tag
		// below. But, obviously, the provided login information should be only given for an account that has the
		// 'Export' permission bit enabled but has otherwise limited access rights!

		// If the 'authentication' element is present, but empty, then it implies that authentication is required
		// to connect to the server, however there is no publically available login. If it contains a string, then
		// this is the token to give in order to authenticate. Otherwise it may contain three elements:
		// 1. user: The username to supply.
		// 2. group: The group to supply.
		// 3. password: The password to supply.
//		$srwServerInfoAuthenticationBranch = new XMLBranch("authentication");
//		$srwServerInfoAuthenticationBranch->setTagContent("LOGINEMAIL", "authentication/user");
//		$srwServerInfoAuthenticationBranch->setTagContent("PASSWORD", "authentication/password");
//		$srwServerInfoBranch->addXMLBranch($srwServerInfoAuthenticationBranch);

		$srwExplainBranch->addXMLBranch($srwServerInfoBranch);
		// --- end server info --------------------------------------


		// --- begin database info ----------------------------------
		$srwDatabaseInfoBranch = new XMLBranch("databaseInfo");

		addNewBranch($srwDatabaseInfoBranch, "title", array("lang" => $defaultLanguage, "primary" => "true"), $officialDatabaseName);

		addNewBranch($srwDatabaseInfoBranch, "description", array("lang" => $defaultLanguage, "primary" => "true"), encodeHTMLspecialchars($loc["ThisDatabaseAttempts"]));

		$srwDatabaseInfoBranch->setTagContent(encodeHTMLspecialchars($hostInstitutionName), "databaseInfo/author");

		$srwDatabaseInfoBranch->setTagContent(encodeHTMLspecialchars($hostInstitutionName) . " (" . $feedbackEmail . ")", "databaseInfo/contact");

		$srwDatabaseImplementationBranch = new XMLBranch("implementation");
//		$srwDatabaseImplementationBranch->setTagAttribute("version", "0.9.1");
		$srwDatabaseImplementationBranch->setTagAttribute("identifier", "refbase");
		$srwDatabaseImplementationBranch->setTagContent("Web Reference Database (http://refbase.sourceforge.net)", "implementation/title");
		$srwDatabaseInfoBranch->addXMLBranch($srwDatabaseImplementationBranch);

		$srwDatabaseLinksBranch = new XMLBranch("links");

		addNewBranch($srwDatabaseLinksBranch, "link", array("type" => "www"), $databaseBaseURL);
		addNewBranch($srwDatabaseLinksBranch, "link", array("type" => "sru"), $databaseBaseURL . "sru.php");
		addNewBranch($srwDatabaseLinksBranch, "link", array("type" => "rss"), $databaseBaseURL . "rss.php?where=serial%20RLIKE%20%22.%2B%22&amp;showRows=" . $showRows);
		addNewBranch($srwDatabaseLinksBranch, "link", array("type" => "icon"), $databaseBaseURL . $logoImageURL);

		$srwDatabaseInfoBranch->addXMLBranch($srwDatabaseLinksBranch);

		$srwExplainBranch->addXMLBranch($srwDatabaseInfoBranch);
		// --- end database info ------------------------------------


		// --- begin index info -------------------------------------
		$srwIndexInfoBranch = new XMLBranch("indexInfo");

		addNewBranch($srwIndexInfoBranch, "set", array("identifier" => "info:srw/cql-context-set/1/cql-v1.1", "name" => "cql"), "");
		addNewBranch($srwIndexInfoBranch, "set", array("identifier" => "info:srw/cql-context-set/1/dc-v1.1", "name" => "dc"), "");
		addNewBranch($srwIndexInfoBranch, "set", array("identifier" => "http://zing.z3950.org/cql/bath/2.0/", "name" => "bath"), "");
		addNewBranch($srwIndexInfoBranch, "set", array("identifier" => "info:srw/cql-context-set/2/rec-1.1", "name" => "rec"), "");

		$indexArray = array();

		$indexArray["dc.creator"] = array("_set" => "dc",
										"_index" => "creator",
										"_title" => "Author",
										"_refbaseIndex" => "refbase-author");

		$indexArray["dc.title"] = array("_set" => "dc",
										"_index" => "title",
										"_title" => "Publication title",
										"_refbaseIndex" => "refbase-title");

		$indexArray["dc.date"] = array("_set" => "dc",
									"_index" => "date",
									"_title" => "Year of publication",
									"_refbaseIndex" => "refbase-year");

		$indexArray["dc.language"] = array("_set" => "dc",
										"_index" => "language",
										"_title" => "Language",
										"_refbaseIndex" => "refbase-language");

		$indexArray["dc.description"] = array("_set" => "dc",
											"_index" => "description",
											"_title" => "Abstract",
											"_refbaseIndex" => "refbase-abstract");

		$indexArray["dc.subject"] = array("_set" => "dc",
										"_index" => "subject",
										"_title" => "Keywords",
										"_refbaseIndex" => "refbase-keywords");

		$indexArray["dc.format"] = array("_set" => "dc",
										"_index" => "format",
										"_title" => "Format/Type of Material",
										"_refbaseIndex" => "refbase-medium");

		$indexArray["dc.publisher"] = array("_set" => "dc",
										"_index" => "publisher",
										"_title" => "Publisher",
										"_refbaseIndex" => "refbase-publisher");

		$indexArray["dc.coverage"] = array("_set" => "dc",
											"_index" => "coverage",
											"_title" => "Geographic or topographic area of research",
											"_refbaseIndex" => "refbase-area");

// Note: I'm note sure, if 'bath.name' can be also used to describe the author/creator ('dc.creator') of a publication
//		$indexArray["bath.name"] = array("_set" => "bath",
//										"_index" => "name",
//										"_title" => "Author",
//										"_refbaseIndex" => "refbase-author");

// Note: Not sure again whether 'bath.topicalSubject' can be offered as synonym for 'dc.subject'
//		$indexArray["bath.topicalSubject"] = array("_set" => "bath",
//												"_index" => "topicalSubject",
//												"_title" => "Keywords",
//												"_refbaseIndex" => "refbase-keywords");

		$indexArray["bath.issn"] = array("_set" => "bath",
										"_index" => "issn",
										"_title" => "International standard serial number",
										"_refbaseIndex" => "refbase-issn");

		$indexArray["bath.corporateName"] = array("_set" => "bath",
												"_index" => "corporateName",
												"_title" => "Corporate Author",
												"_refbaseIndex" => "refbase-corporate_author");

		$indexArray["bath.conferenceName"] = array("_set" => "bath",
												"_index" => "conferenceName",
												"_title" => "Conference",
												"_refbaseIndex" => "refbase-conference");

		$indexArray["rec.identifier"] = array("_set" => "rec",
										"_index" => "identifier",
										"_title" => "Database record number",
										"_refbaseIndex" => "refbase-serial");

		$indexArray["rec.creationDate"] = array("_set" => "rec",
												"_index" => "creationDate",
												"_title" => "Date/Time at which the record was created",
												"_refbaseIndex" => "refbase-created_date-created_time"); // 'sru.php': CQL search term should get splitted into date & time information!

		$indexArray["rec.creationAgentName"] = array("_set" => "rec",
													"_index" => "creationAgentName",
													"_title" => "Name of the agent responsible for creation of the record",
													"_refbaseIndex" => "refbase-created_by");

		$indexArray["rec.lastModificationDate"] = array("_set" => "rec",
													"_index" => "lastModificationDate",
													"_title" => "Date/Time at which the record was last modified",
													"_refbaseIndex" => "refbase-modified_date-modified_time"); // 'sru.php': CQL search term should get splitted into date & time information!

		$indexArray["rec.lastModificationAgentName"] = array("_set" => "rec",
														"_index" => "lastModificationAgentName",
														"_title" => "Name of the agent responsible for last modifying the record",
														"_refbaseIndex" => "refbase-modified_by");

		$indexArray["bib.citekey"] = array("_set" => "bib",
													"_index" => "citekey",
													"_title" => "User-specific cite key for the record",
													"_refbaseIndex" => "refbase-cite_key");

// Not sure how these fields can be mapped:
// 		"publication" => "Book title or journal name",
// 		"abbrev_journal" => "Abbreviated journal name",
// 		"volume" => "Publication volume",
// 		"issue" => "Publication issue",
// 		"pages" => "Range or total number of pages",
// 		"editor" => "Editor",
// 		"place" => "Place of publication",
// 		"series_title" => "Series title",
// 		"abbrev_series_title" => "Abbreviated series title",
// 		"series_volume" => "Series volume",
// 		"series_issue" => "Series issue",
// 		"notes" => "Notes",
// 		"thesis" => "Thesis",
// 		"isbn" => "International standard book number",
// 		"doi" => "Digital object identifier",
// 		"url" => "Uniform resource locator",

		foreach ($indexArray as $indexKey => $index)
		{
			$srwIndexBranch = new XMLBranch("index");
			$srwIndexBranch->setTagAttribute("search", "true");
			$srwIndexBranch->setTagAttribute("scan", "false");
			$srwIndexBranch->setTagAttribute("sort", "false");
			$srwIndexBranch->setTagAttribute("refb:index", $index["_refbaseIndex"]);

			addNewBranch($srwIndexBranch, "title", array("lang" => "en"), $index["_title"]);

			$srwIndexMapBranch = new XMLBranch("map");

			addNewBranch($srwIndexMapBranch, "name", array("set" => $index["_set"]), $index["_index"]);

			$srwIndexBranch->addXMLBranch($srwIndexMapBranch);

			$srwIndexInfoBranch->addXMLBranch($srwIndexBranch);
		}

		$srwExplainBranch->addXMLBranch($srwIndexInfoBranch);
		// --- end index info ---------------------------------------


		// --- begin schema info -------------------------------------
		$srwSchemaInfoBranch = new XMLBranch("schemaInfo");

		$srwSchemaBranch = new XMLBranch("schema");
		$srwSchemaBranch->setTagAttribute("identifier", "http://www.loc.gov/mods/v3");
		$srwSchemaBranch->setTagAttribute("location", "http://www.loc.gov/standards/mods/v3/mods-3-0.xsd");
		$srwSchemaBranch->setTagAttribute("sort", "false");
		$srwSchemaBranch->setTagAttribute("retrieve", "true");
		$srwSchemaBranch->setTagAttribute("name", "mods");

		addNewBranch($srwSchemaBranch, "title", array("lang" => "en"), "Metadata Object Description Schema (MODS) v3");

		$srwSchemaInfoBranch->addXMLBranch($srwSchemaBranch);

		$srwExplainBranch->addXMLBranch($srwSchemaInfoBranch);
		// --- end schema info ---------------------------------------


		// --- begin config info -------------------------------------
		$srwConfigInfoBranch = new XMLBranch("configInfo");

		// default:
		addNewBranch($srwConfigInfoBranch, "default", array("type" => "numberOfRecords"), $showRows);
		addNewBranch($srwConfigInfoBranch, "default", array("type" => "stylesheet"), $databaseBaseURL . "srwmods2html.xsl");
		addNewBranch($srwConfigInfoBranch, "default", array("type" => "contextSet"), "cql");
		addNewBranch($srwConfigInfoBranch, "default", array("type" => "index"), "identifier");
		addNewBranch($srwConfigInfoBranch, "default", array("type" => "relation"), "any");

		// setting:
		addNewBranch($srwConfigInfoBranch, "setting", array("type" => "sortSchema"), "identifier");
		addNewBranch($srwConfigInfoBranch, "setting", array("type" => "retrieveSchema"), "mods");
		addNewBranch($srwConfigInfoBranch, "setting", array("type" => "recordPacking"), "xml");

		// supports:
		addNewBranch($srwConfigInfoBranch, "supports", array("type" => "proximity"), "false");
		addNewBranch($srwConfigInfoBranch, "supports", array("type" => "resultSets"), "false");
		addNewBranch($srwConfigInfoBranch, "supports", array("type" => "relationModifier"), "false");
		addNewBranch($srwConfigInfoBranch, "supports", array("type" => "booleanModifier"), "false");
		addNewBranch($srwConfigInfoBranch, "supports", array("type" => "sort"), "false");
		addNewBranch($srwConfigInfoBranch, "supports", array("type" => "maskingCharacter"), "true");
		addNewBranch($srwConfigInfoBranch, "supports", array("type" => "anchoring"), "true");
		addNewBranch($srwConfigInfoBranch, "supports", array("type" => "emptyTerm"), "false");
		addNewBranch($srwConfigInfoBranch, "supports", array("type" => "recordXPath"), "false");
		addNewBranch($srwConfigInfoBranch, "supports", array("type" => "scan"), "false");

		$srwExplainBranch->addXMLBranch($srwConfigInfoBranch);
		// --- end config info ---------------------------------------


		$srwRecordDataBranch->addXMLBranch($srwExplainBranch);

		$srwRecordBranch->addXMLBranch($srwRecordDataBranch);

		$srwCollection->addXMLBranch($srwRecordBranch);

		$srwCollectionDoc->setXML($srwCollection);
		$srwCollectionString = $srwCollectionDoc->getXMLString();

		// Add the XML Stylesheet definition:
		// Note that this is just a hack (that should get fixed) since I don't know how to do it properly using the ActiveLink PHP XML Package ?:-/
		if (!empty($exportStylesheet))
			$srwCollectionString = preg_replace("/(?=\<srw:explainResponse)/i","<?xml-stylesheet type=\"text/xsl\" href=\"" . $exportStylesheet . "\"?>\n",$srwCollectionString);

		return $srwCollectionString;
	}

	// --------------------------------------------------------------------

	// Return SRW diagnostics (i.e. SRW error information) wrapped into SRW XML ('searchRetrieveResponse'):
	function srwDiagnostics($diagCode, $diagDetails, $exportStylesheet)
	{
		global $contentTypeCharset; // defined in 'ini.inc.php'

		$diagMessages = array(1 => "General system error", // Details: Debugging information (traceback)
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

								110 => "Stylesheets not supported");

		if (isset($diagMessages[$diagCode]))
			$diagMessage = $diagMessages[$diagCode];
		else
			$diagMessage = "Unknown error";

		$srwCollectionDoc = new XMLDocument();
		$srwCollectionDoc->setEncoding($contentTypeCharset);

		$srwCollection = srwGenerateBaseTags("searchRetrieveResponse");

		$diagnosticsBranch = new XMLBranch("srw:diagnostics");

		// since we've defined the 'diag' namespace in the <searchRetrieveResponse> element (see function 'srwGenerateBaseTags()'),
		// we can simply use '<diag:diagnostic>' below; otherwise we should use '<diagnostic xmlns="http://www.loc.gov/zing/srw/diagnostic/">':
		// addNewBranch($diagnosticsBranch, "diagnostic", array("xmlns" => "http://www.loc.gov/zing/srw/diagnostic/"), "");

		$diagnosticsBranch->setTagContent("info:srw/diagnostic/1/" . $diagCode, "srw:diagnostics/diag:diagnostic/uri");
		$diagnosticsBranch->setTagContent($diagMessage, "srw:diagnostics/diag:diagnostic/message");
		if (!empty($diagDetails))
			$diagnosticsBranch->setTagContent(encodeHTMLspecialchars($diagDetails), "srw:diagnostics/diag:diagnostic/details");

		$srwCollection->addXMLBranch($diagnosticsBranch);

		$srwCollectionDoc->setXML($srwCollection);
		$srwCollectionString = $srwCollectionDoc->getXMLString();

		return $srwCollectionString;
	}

	// --------------------------------------------------------------------

	// Generate the basic SRW XML tree required for a 'searchRetrieveResponse' or 'explainResponse':
	function srwGenerateBaseTags($srwOperation)
	{
		$srwCollection = new XML("srw:" . $srwOperation);
		$srwCollection->setTagAttribute("xmlns:srw", "http://www.loc.gov/zing/srw/");

		if ($srwOperation == "searchRetrieveResponse")
		{
			$srwCollection->setTagAttribute("xmlns:diag", "http://www.loc.gov/zing/srw/diagnostic/");
			$srwCollection->setTagAttribute("xmlns:xcql", "http://www.loc.gov/zing/cql/xcql/");
			$srwCollection->setTagAttribute("xmlns:mods", "http://www.loc.gov/mods/v3");
		}
	//	elseif ($srwOperation == "explainResponse")
	//	{
	//		$srwCollection->setTagAttribute("xmlns:zr", "http://explain.z3950.org/dtd/2.0/");
	//	}

		addNewBranch($srwCollection, "srw:version", array(), "1.1"); // function 'addNewBranch()' is defined in 'webservice.inc.php'

		return $srwCollection;
	}

	// --------------------------------------------------------------------

	// Generate the basic SRW XML elements 'recordPacking' and 'recordSchema':
	function srwGeneratePackingSchema(&$thisObject, $srwPacking, $srwSchema)
	{
		// available schemas taken from <http://www.loc.gov/z3950/agency/zing/srw/record-schemas.html>
		$srwSchemas = array("dc" => "info:srw/schema/1/dc-v1.1",
							"diag" => "info:srw/schema/1/diagnostic-v1.1", // it says 'info:srw/schema/1/diagnostics-v1.1' at <http://www.loc.gov/standards/sru/diagnostics.html> ?:-/
							"zeerex" => "http://explain.z3950.org/dtd/2.0/",
							"mods" => "info:srw/schema/1/mods-v3.0",
							"onix" => "info:srw/schema/1/onix-v2.0",
							"marcxml" => "info:srw/schema/1/marcxml-v1.1",
							"ead" => "info:srw/schema/1/ead-2002",
							"zthes" => "http://zthes.z3950.org/xml/0.5/",
							"ccg" => "http://srw.cheshire3.org/schemas/ccg/1.0/",
							"rec" => "info:srw/schema/2/rec-1.0",
							"server-choice" => "info:srw/schema/1/server-choice",
							"xpath" => "info:srw/schema/1/xpath-1.0");

		addNewBranch($thisObject, "srw:recordPacking", array(), $srwPacking); // function 'addNewBranch()' is defined in 'webservice.inc.php'
		addNewBranch($thisObject, "srw:recordSchema", array(), $srwSchemas[$srwSchema]);
	}

	// --------------------------------------------------------------------
?>
