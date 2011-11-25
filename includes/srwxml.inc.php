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

	// Return DC XML or MODS XML records wrapped into SRW XML ('searchRetrieveResponse'):
	function srwCollection($result, $rowOffset, $showRows, $exportStylesheet, $displayType)
	{
		global $contentTypeCharset; // these variables are defined in 'ini.inc.php'
		global $convertExportDataToUTF8;

		global $exportFormat; // this is needed so that we can distinguish between "SRW_DC XML" and "SRW_MODS XML" record formats

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
				if (preg_match("/DC/i", $exportFormat)) // export the current record as DC XML (i.e. simple Dublin Core):
					$record = oaidcRecord($row, "srw_dc"); // function 'oaidcRecord()' is defined in 'oaidcxml.inc.php'
				else  // by default, we export the current record as MODS XML:
					$record = modsRecord($row); // function 'modsRecord()' is defined in 'modsxml.inc.php'

				// TODO: build 'extraRecordData' for OAI-PMH (see below) using:
				//       $row['serial'], $row['modified_date'], $row['modified_time']

				if (!empty($record)) // unless the record buffer is empty...
					array_push($exportArray, $record); // ...add it to an array of exports
			}

			$i = $rowOffset; // initialize counter

			// for each of the DC/MODS records in the result set...
			foreach ($exportArray as $record)
			{
				++$i; // increment $i by one, then return $i

				$srwRecordBranch = new XMLBranch("srw:record");

				if (preg_match("/DC/i", $exportFormat))
					srwGeneratePackingSchema($srwRecordBranch, "xml", "dc");
				else
					srwGeneratePackingSchema($srwRecordBranch, "xml", "mods");

				$srwRecordDataBranch = new XMLBranch("srw:recordData");

				if (preg_match("/MODS/i", $exportFormat))
				{
					// NOTE: converting the MODS object into a string to perform search & replace actions
					//       may be very clumsy but I don't know any better... ?:-/
					$recordString = $record->getXMLString();
					$recordString = preg_replace('/<mods/i','<mods xmlns="http://www.loc.gov/mods/v3"',$recordString);
					// alternatively to the above line we could add a 'mods:' identifier to all MODS XML tags:
	//				$recordString = preg_replace("#<(/)?#","<\\1mods:",$recordString);
					$record->removeAllBranches();
					$record->parseFromString($recordString);
				}

				$srwRecordDataBranch->addXMLasBranch($record);
				$srwRecordBranch->addXMLBranch($srwRecordDataBranch);

				// TODO: add 'extraRecordData' for OAI-PMH as explained in <http://www.dlib.org/dlib/february05/sanderson/02sanderson.html>
				//       Example:
				//                <extraRecordData>
				//                    <oai:header xmlns:oai="http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
				//                        <oai:identifier>...</oai:identifier>
				//                        <oai:datestamp>...</oai:datestamp>
				//                        <oai:setSpec>...</oai:setSpec>
				//                    </oai:header>
				//                </extraRecordData>
				//
				//       Then add to the SRW 'Explain' response:
				//          1.  an oai.identifier index containing a unique identifier for each record in the database
				//          2.  an oai.datestamp index containing the date/time the record was added or changed in the database
				//          3.  an optional oai.set index, browsable via the scan operation, to support selective harvesting of records

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
		global $defaultFeedFormat;

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
		$srwDatabaseImplementationBranch->setTagAttribute("version", "0.9.5");
		$srwDatabaseImplementationBranch->setTagAttribute("identifier", "refbase");
		$srwDatabaseImplementationBranch->setTagContent("Web Reference Database (http://refbase.sourceforge.net)", "implementation/title");
		$srwDatabaseInfoBranch->addXMLBranch($srwDatabaseImplementationBranch);

		$srwDatabaseLinksBranch = new XMLBranch("links");

		addNewBranch($srwDatabaseLinksBranch, "link", array("type" => "www"), $databaseBaseURL);
		addNewBranch($srwDatabaseLinksBranch, "link", array("type" => "sru"), $databaseBaseURL . "sru.php");
		addNewBranch($srwDatabaseLinksBranch, "link", array("type" => "rss"), $databaseBaseURL . generateURL("show.php", $defaultFeedFormat, array("where" => 'serial RLIKE ".+"'), true, $showRows)); // function 'generateURL()' is defined in 'include.inc.php'
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

// TODO: The index info of the refbase explain response should also list the original refbase field names,
//       similar to how the COPAC SRU gateway does it (<http://tweed.lib.ed.ac.uk:8080/elf/search/copac>).
//       Example:
//			<index search="true" scan="false" sort="false">
//				<title>Author</title>
 
//				<map>
//					<name>
//						author
//					</name>
//				</map>
//				<map>
//					<name set="dc">
//						creator
//					</name>
//				</map>
//			</index>

		$indexArray = array(); // TODO: '$indexArray' should be an array of arrays so that it can hold multiple mappings

		$indexArray["dc.creator"] = array("_set"          => "dc",
		                                  "_index"        => "creator",
		                                  "_title"        => "author(s) of the resource",
		                                  "_refbaseIndex" => "refbase-author");

		$indexArray["dc.title"] = array("_set"          => "dc",
		                                "_index"        => "title",
		                                "_title"        => "publication title of the resource",
		                                "_refbaseIndex" => "refbase-title");

		$indexArray["dc.date"] = array("_set"          => "dc",
		                               "_index"        => "date",
		                               "_title"        => "year of publication of the resource",
		                               "_refbaseIndex" => "refbase-year");

		$indexArray["dc.language"] = array("_set"          => "dc",
		                                   "_index"        => "language",
		                                   "_title"        => "language of the resource",
		                                   "_refbaseIndex" => "refbase-language");

		$indexArray["dc.description"] = array("_set"          => "dc",
		                                      "_index"        => "description",
		                                      "_title"        => "abstract or summary of the resource",
		                                      "_refbaseIndex" => "refbase-abstract");

		$indexArray["dc.contributor"] = array("_set"          => "dc",
		                                      "_index"        => "contributor",
		                                      "_title"        => "editor(s) of the resource",
		                                      "_refbaseIndex" => "refbase-editor"); // the mapping dc.contributor <-> refbase-editor might be suboptimal, but probably as best as we can do for now

		$indexArray["dc.subject"] = array("_set"          => "dc",
		                                  "_index"        => "subject",
		                                  "_title"        => "topic of the resource",
		                                  "_refbaseIndex" => "refbase-keywords");

		$indexArray["dc.format"] = array("_set"          => "dc",
		                                 "_index"        => "format",
		                                 "_title"        => "physical or digital manifestation of the resource",
		                                 "_refbaseIndex" => "refbase-medium");

		// Note: Currently, we simply expose the contents of the refbase 'type' field as 'dc.type'.
		//       This may not be ideal since it differs from the approved terms that should be used as values for the 'dc.type' element: <http://dublincore.org/documents/dcmi-type-vocabulary/>.
		//       However, the document "Using simple Dublin Core to describe eprints" (<http://eprints-uk.rdn.ac.uk/project/docs/simpledc-guidelines/#type>)
		//       recommends type values that are much closer (but still not identical) to our own type values.
		$indexArray["dc.type"] = array("_set"          => "dc",
		                               "_index"        => "type",
		                               "_title"        => "nature or genre of the resource",
		                               "_refbaseIndex" => "refbase-type");

		$indexArray["dc.publisher"] = array("_set"          => "dc",
		                                    "_index"        => "publisher",
		                                    "_title"        => "publisher",
		                                    "_refbaseIndex" => "refbase-publisher");

		$indexArray["dc.coverage"] = array("_set"          => "dc",
		                                   "_index"        => "coverage",
		                                   "_title"        => "geographic or topographic area of research",
		                                   "_refbaseIndex" => "refbase-area");

// Note: I'm note sure, if 'bath.name' (or maybe better: 'bath.personalName') can be also used to describe the author/creator ('dc.creator') of a publication
//      "'Name Search -- Keyword' searches for complete word in headings (or references) for people, corporate bodies, conferences, and geographic names."
//		$indexArray["bath.name"] = array("_set"          => "bath",
//		                                 "_index"        => "name",
//		                                 "_title"        => "author",
//		                                 "_refbaseIndex" => "refbase-author");

// Note: Not sure again whether 'bath.topicalSubject' can be offered as synonym for 'dc.subject'
//       "'Topical Subject Search -- Keyword' searches for complete word in a topical subject heading or reference."
//		$indexArray["bath.topicalSubject"] = array("_set"          => "bath",
//		                                           "_index"        => "topicalSubject",
//		                                           "_title"        => "keywords",
//		                                           "_refbaseIndex" => "refbase-keywords");

		// NOTE: I'm not sure if 'isbn' is a valid name for the Bath Context Set? At least, it's not listed at <http://zing.z3950.org/srw/bath/2.0/#2>.
		//       However, 'bath.isbn' is used e.g. by <http://z3950.loc.gov:7090/voyager?operation=explain&version=1.1> and other SRU servers.
		$indexArray["bath.isbn"] = array("_set"          => "bath",
		                                 "_index"        => "isbn",
		                                 "_title"        => "international standard book number",
		                                 "_refbaseIndex" => "refbase-isbn");

		$indexArray["bath.issn"] = array("_set"          => "bath",
		                                 "_index"        => "issn",
		                                 "_title"        => "international standard serial number",
		                                 "_refbaseIndex" => "refbase-issn");

		$indexArray["bath.corporateName"] = array("_set"          => "bath",
		                                          "_index"        => "corporateName",
		                                          "_title"        => "corporate author of this publication",
		                                          "_refbaseIndex" => "refbase-corporate_author");

		$indexArray["bath.conferenceName"] = array("_set"          => "bath",
		                                           "_index"        => "conferenceName",
		                                           "_title"        => "conference this publication was presented at",
		                                           "_refbaseIndex" => "refbase-conference");

		// NOTE: I'm not sure if 'notes' is a valid name for the Bath Context Set? 
		//       'bath.notes' is mentioned at <http://www.loc.gov/z3950/lcserver.html> and <http://zing.z3950.org/srw/bath/2.0/#3>.
		$indexArray["bath.notes"] = array("_set"          => "bath",
		                                  "_index"        => "notes",
		                                  "_title"        => "notes about the resource",
		                                  "_refbaseIndex" => "refbase-notes");

		$indexArray["rec.identifier"] = array("_set"          => "rec",
		                                      "_index"        => "identifier",
		                                      "_title"        => "database record number",
		                                      "_refbaseIndex" => "refbase-serial");

		$indexArray["rec.creationDate"] = array("_set"          => "rec",
		                                        "_index"        => "creationDate",
		                                        "_title"        => "date/time at which the record was created",
		                                        "_refbaseIndex" => "refbase-created_date-created_time"); // 'sru.php': CQL search term should get splitted into date & time information!

		$indexArray["rec.creationAgentName"] = array("_set"          => "rec",
		                                             "_index"        => "creationAgentName",
		                                             "_title"        => "name of the agent responsible for creation of the record",
		                                             "_refbaseIndex" => "refbase-created_by");

		$indexArray["rec.lastModificationDate"] = array("_set"          => "rec",
		                                                "_index"        => "lastModificationDate",
		                                                "_title"        => "date/time at which the record was last modified",
		                                                "_refbaseIndex" => "refbase-modified_date-modified_time"); // 'sru.php': CQL search term should get splitted into date & time information!

		$indexArray["rec.lastModificationAgentName"] = array("_set"          => "rec",
		                                                     "_index"        => "lastModificationAgentName",
		                                                     "_title"        => "name of the agent responsible for last modifying the record",
		                                                     "_refbaseIndex" => "refbase-modified_by");

		$indexArray["bib.citekey"] = array("_set"          => "bib",
		                                   "_index"        => "citekey",
		                                   "_title"        => "user-specific cite key for the record",
		                                   "_refbaseIndex" => "refbase-cite_key");

// Not sure how these fields can be mapped:
// 		"publication" => "Book title or journal name",
// 		"abbrev_journal" => "Abbreviated journal name",
// 		"volume" => "Publication volume",
// 		"issue" => "Publication issue",
// 		"pages" => "Range or total number of pages",
// 		"place" => "Place of publication",
// 		"series_title" => "Series title",                     // -> could 'bath.seriesTitle' be used? compare with <http://www.loc.gov/z3950/lcserver.html> and <http://copac.ac.uk/interfaces/srw/>
// 		"abbrev_series_title" => "Abbreviated series title",
// 		"series_volume" => "Series volume",
// 		"series_issue" => "Series issue",
// 		"thesis" => "Thesis",
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

		// MODS:
		$modsSchemaBranch = new XMLBranch("schema");
		$modsSchemaBranch->setTagAttribute("identifier", "http://www.loc.gov/mods/v3"); // or should 'info:srw/schema/1/mods-v3.2' be used?
		$modsSchemaBranch->setTagAttribute("location", "http://www.loc.gov/standards/mods/v3/mods-3-0.xsd");
		$modsSchemaBranch->setTagAttribute("sort", "false");
		$modsSchemaBranch->setTagAttribute("retrieve", "true");
		$modsSchemaBranch->setTagAttribute("name", "mods");

		addNewBranch($modsSchemaBranch, "title", array("lang" => "en"), "Metadata Object Description Schema (MODS) v3");

		$srwSchemaInfoBranch->addXMLBranch($modsSchemaBranch);

		// Simple Dublin Core (DC):
		$dcSchemaBranch = new XMLBranch("schema");
		$dcSchemaBranch->setTagAttribute("identifier", "http://purl.org/dc/elements/1.1/"); // or should 'info:srw/schema/1/dc-v1.1' be used?
		$dcSchemaBranch->setTagAttribute("location", "http://dublincore.org/schemas/xmls/simpledc20021212.xsd");
		$dcSchemaBranch->setTagAttribute("sort", "false");
		$dcSchemaBranch->setTagAttribute("retrieve", "true");
		$dcSchemaBranch->setTagAttribute("name", "dc");

		addNewBranch($dcSchemaBranch, "title", array("lang" => "en"), "Simple Dublin Core (DC) v1.1");

		$srwSchemaInfoBranch->addXMLBranch($dcSchemaBranch);

		// Simple Dublin Core (OAI_DC):
		// See recommendations for use of simple Dublin Core metadata to describe eprints in eprint archives: <http://eprints-uk.rdn.ac.uk/project/docs/simpledc-guidelines/>
		// Example SRW+DC output from LoC: <http://z3950.loc.gov:7090/voyager?query=dc.creator+%3D+%22miller%22&version=1.1&operation=searchRetrieve&recordSchema=dc&startRecord=1&maximumRecords=10>
//		$oaidcSchemaBranch = new XMLBranch("schema");
//		$oaidcSchemaBranch->setTagAttribute("identifier", "http://www.openarchives.org/OAI/2.0/oai_dc/");
//		$oaidcSchemaBranch->setTagAttribute("location", "http://www.openarchives.org/OAI/2.0/oai_dc.xsd");
//		$oaidcSchemaBranch->setTagAttribute("sort", "false");
//		$oaidcSchemaBranch->setTagAttribute("retrieve", "true");
//		$oaidcSchemaBranch->setTagAttribute("name", "oai_dc");
//
//		addNewBranch($oaidcSchemaBranch, "title", array("lang" => "en"), "Simple Dublin Core for OAI-PMH (OAI_DC)");
//
//		$srwSchemaInfoBranch->addXMLBranch($oaidcSchemaBranch);

		$srwExplainBranch->addXMLBranch($srwSchemaInfoBranch);
		// --- end schema info ---------------------------------------


		// --- begin config info -------------------------------------
		$srwConfigInfoBranch = new XMLBranch("configInfo");

		// default:
		addNewBranch($srwConfigInfoBranch, "default", array("type" => "retrieveSchema"), "mods");
		addNewBranch($srwConfigInfoBranch, "default", array("type" => "numberOfRecords"), $showRows);
		addNewBranch($srwConfigInfoBranch, "default", array("type" => "stylesheet"), $databaseBaseURL . "srwmods2html.xsl");
		addNewBranch($srwConfigInfoBranch, "default", array("type" => "contextSet"), "cql");
		addNewBranch($srwConfigInfoBranch, "default", array("type" => "index"), "cql.serverChoice");
		addNewBranch($srwConfigInfoBranch, "default", array("type" => "relation"), "all");

		// setting:
		addNewBranch($srwConfigInfoBranch, "setting", array("type" => "sortSchema"), "identifier");
		addNewBranch($srwConfigInfoBranch, "setting", array("type" => "recordPacking"), "xml");

		// supports:
		addNewBranch($srwConfigInfoBranch, "supports", array("type" => "proximity"), "false");
		addNewBranch($srwConfigInfoBranch, "supports", array("type" => "resultSets"), "false");
		addNewBranch($srwConfigInfoBranch, "supports", array("type" => "relationModifier"), "false");
		addNewBranch($srwConfigInfoBranch, "supports", array("type" => "booleanModifier"), "false"); // TODO: set to 'true' when Rob's CQL-PHP has been implemented successfully
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

		// Map SRU/W diagnostic numbers to their corresponding messages:
		$diagMessages = mapSRWDiagnostics(); // function 'mapSRWDiagnostics()' is defined in 'webservice.inc.php'

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

		// Add the XML Stylesheet definition:
		// Note that this is just a hack (that should get fixed) since I don't know how to do it properly using the ActiveLink PHP XML Package ?:-/
		if (!empty($exportStylesheet))
			$srwCollectionString = preg_replace("/(?=\<srw:searchRetrieveResponse)/i","<?xml-stylesheet type=\"text/xsl\" href=\"" . $exportStylesheet . "\"?>\n",$srwCollectionString);

		return $srwCollectionString;
	}

	// --------------------------------------------------------------------

	// Generate the basic SRW XML tree required for a 'searchRetrieveResponse' or 'explainResponse':
	function srwGenerateBaseTags($srwOperation)
	{
		global $exportFormat; // this is needed so that we can distinguish between "SRW_DC XML" and "SRW_MODS XML" record formats

		$srwCollection = new XML("srw:" . $srwOperation);
		$srwCollection->setTagAttribute("xmlns:srw", "http://www.loc.gov/zing/srw/");

		if ($srwOperation == "searchRetrieveResponse")
		{
			$srwCollection->setTagAttribute("xmlns:diag", "http://www.loc.gov/zing/srw/diagnostic/");
			$srwCollection->setTagAttribute("xmlns:xcql", "http://www.loc.gov/zing/cql/xcql/");

			if (preg_match("/DC/i", $exportFormat)) // add namespace declarations for "SRW_DC XML":
			{
				$srwCollection->setTagAttribute("xmlns:srw_dc", "info:srw/schema/1/dc-v1.1");
				$srwCollection->setTagAttribute("xmlns:dc", "http://purl.org/dc/elements/1.1/");
				$srwCollection->setTagAttribute("xmlns:prism", "http://prismstandard.org/namespaces/1.2/basic/");
			}
			else // add namespace declarations for "SRW_MODS XML":
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
		$srwSchemas = array("dc"            => "info:srw/schema/1/dc-v1.1", // or should <http://purl.org/dc/elements/1.1/> be used?
		//                  "dcterms"       => "http://purl.org/dc/terms/",
		                    "diag"          => "info:srw/schema/1/diagnostic-v1.1", // it says 'info:srw/schema/1/diagnostics-v1.1' at <http://www.loc.gov/standards/sru/diagnostics.html> ?:-/
		                    "zeerex"        => "http://explain.z3950.org/dtd/2.0/",
		                    "mods"          => "info:srw/schema/1/mods-v3.2",
		                    "onix"          => "info:srw/schema/1/onix-v2.0",
		                    "marcxml"       => "info:srw/schema/1/marcxml-v1.1",
		                    "ead"           => "info:srw/schema/1/ead-2002",
		                    "zthes"         => "http://zthes.z3950.org/xml/0.5/",
		                    "ccg"           => "http://srw.cheshire3.org/schemas/ccg/1.0/",
		                    "rec"           => "info:srw/schema/2/rec-1.0",
		                    "server-choice" => "info:srw/schema/1/server-choice",
		                    "xpath"         => "info:srw/schema/1/xpath-1.0");

		addNewBranch($thisObject, "srw:recordPacking", array(), $srwPacking); // function 'addNewBranch()' is defined in 'webservice.inc.php'
		addNewBranch($thisObject, "srw:recordSchema", array(), $srwSchemas[$srwSchema]);
	}

	// --------------------------------------------------------------------
?>
