<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./includes/odfxml.inc.php
	// Created:    01-Jun-06, 12:49
	// Modified:   06-Sep-06, 14:00

	// This include file contains functions that'll export records to ODF XML
	// in spreadsheet format ('.ods').
	// Requires ActiveLink PHP XML Package, which is available under the GPL from:
	// <http://www.active-link.com/software/>


	// Incorporate some include files:
	include_once 'includes/webservice.inc.php'; // include functions that are commonly used with the refbase webservices
	include_once 'includes/transtab_refbase_unicode.inc.php'; // include refbase markup -> Unicode search & replace patterns
	include_once 'includes/zip.inc.php';

	// Import the ActiveLink Packages
	require_once("classes/include.php");
	import("org.active-link.xml.XML");
	import("org.active-link.xml.XMLDocument");


	// --------------------------------------------------------------------

	// Generates an ODF XML document
	function odfDocument($result, $odfBodyContentType)
	{
		global $contentTypeCharset; // these variables are defined in 'ini.inc.php'
		global $convertExportDataToUTF8;

		$odfDocumentDoc = new XMLDocument();

		if (($convertExportDataToUTF8 == "yes") AND ($contentTypeCharset != "UTF-8"))
			$odfDocumentDoc->setEncoding("UTF-8");
		else
			$odfDocumentDoc->setEncoding($contentTypeCharset);

		// Setup root element:
		$odfDocument = new XML("office:document-content");

		$rootAttributesArray = array(
										"xmlns:office"   => "urn:oasis:names:tc:opendocument:xmlns:office:1.0",
										"xmlns:style"    => "urn:oasis:names:tc:opendocument:xmlns:style:1.0",
										"xmlns:text"     => "urn:oasis:names:tc:opendocument:xmlns:text:1.0",
										"xmlns:table"    => "urn:oasis:names:tc:opendocument:xmlns:table:1.0",
										"xmlns:draw"     => "urn:urn:oasis:names:tc:opendocument:xmlns:drawing:1.0",
										"xmlns:fo"       => "urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0",
										"xmlns:xlink"    => "http://www.w3.org/1999/xlink",
										"xmlns:dc"       => "http://purl.org/dc/elements/1.1/",
										"xmlns:meta"     => "urn:oasis:names:tc:opendocument:xmlns:meta:1.0",
										"xmlns:number"   => "urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0",
										"xmlns:svg"      => "urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0",
										"xmlns:chart"    => "urn:oasis:names:tc:opendocument:xmlns:chart:1.0",
										"xmlns:dr3d"     => "urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0",
										"xmlns:math"     => "http://www.w3.org/1998/Math/MathML",
										"xmlns:form"     => "urn:oasis:names:tc:opendocument:xmlns:form:1.0",
										"xmlns:script"   => "urn:oasis:names:tc:opendocument:xmlns:script:1.0",
										"xmlns:ooo"      => "http://openoffice.org/2004/office",
										"xmlns:ooow"     => "http://openoffice.org/2004/writer",
										"xmlns:oooc"     => "http://openoffice.org/2004/calc",
										"xmlns:dom"      => "http://www.w3.org/2001/xml-events",
										"xmlns:xforms"   => "http://www.w3.org/2002/xforms",
										"xmlns:xsd"      => "http://www.w3.org/2001/XMLSchema",
										"xmlns:xsi"      => "http://www.w3.org/2001/XMLSchema-instance",
										"office:version" => "1.0"
									);

		foreach ($rootAttributesArray as $attributeKey => $attributeValue)
			$odfDocument->setTagAttribute($attributeKey, $attributeValue);

		// Add common attributes:
		addNewBranch($odfDocument, "office-document-common-attrs", array(), ""); // function 'addNewBranch()' is defined in 'webservice.inc.php'

		// Add scripts:
		addNewBranch($odfDocument, "office:scripts", array(), "");

		// Add font face declarations:
		$odfDocumentFontFaceDcls = new XMLBranch("office:font-face-decls");
		addNewBranch($odfDocumentFontFaceDcls, "style:font-face", array("style:name" => "Arial1", "svg:font-family" => "Arial", "style:font-pitch" => "variable"), "");
		addNewBranch($odfDocumentFontFaceDcls, "style:font-face", array("style:name" => "Lucidasans", "svg:font-family" => "Lucidasans", "style:font-pitch" => "variable"), "");
		addNewBranch($odfDocumentFontFaceDcls, "style:font-face", array("style:name" => "Arial", "svg:font-family" => "Arial", "style:font-family-generic" => "swiss", "style:font-pitch" => "variable"), "");
		$odfDocument->addXMLBranch($odfDocumentFontFaceDcls);

		// Add automatic styles:
		if ($odfBodyContentType == "spreadsheet") // Define spreadsheet styles:
		{
			$odfDocumentAutoStyles = new XMLBranch("office:automatic-styles");

			// Define table style:
			$odfDocumentStyle = new XMLBranch("style:style");
			$odfDocumentStyle->setTagAttribute("style:name", "ta1");
			$odfDocumentStyle->setTagAttribute("style:family", "table");
			$odfDocumentStyle->setTagAttribute("style:master-page-name", "Default");
			addNewBranch($odfDocumentStyle, "style:table-properties", array("table:display" => "true", "style:writing-mode" => "lr-tb"), "");
			$odfDocumentAutoStyles->addXMLBranch($odfDocumentStyle);

			// Define style for first table row:
			$odfDocumentStyle = new XMLBranch("style:style");
			$odfDocumentStyle->setTagAttribute("style:name", "ro1");
			$odfDocumentStyle->setTagAttribute("style:family", "table-row");
			addNewBranch($odfDocumentStyle, "style:table-row-properties", array("style:row-height" => "0.1681in", "fo:break-before" => "auto", "style:use-optimal-row-height" => "true"), "");
			$odfDocumentAutoStyles->addXMLBranch($odfDocumentStyle);

			// Define style for all other table rows:
			$odfDocumentStyle = new XMLBranch("style:style");
			$odfDocumentStyle->setTagAttribute("style:name", "ro2");
			$odfDocumentStyle->setTagAttribute("style:family", "table-row");
			addNewBranch($odfDocumentStyle, "style:table-row-properties", array("style:row-height" => "0.6425in", "fo:break-before" => "auto", "style:use-optimal-row-height" => "true"), "");
			$odfDocumentAutoStyles->addXMLBranch($odfDocumentStyle);

			$odfDocument->addXMLBranch($odfDocumentAutoStyles);
		}

		// Add body:
		$odfDocumentBody = new XMLBranch("office:body");

		if ($odfBodyContentType == "spreadsheet") // generate ODF spreadsheet data
		{
			$odfSpreadsheet = odfSpreadsheet($result);
			$odfDocumentBody->addXMLasBranch($odfSpreadsheet);
		}

		$odfDocument->addXMLBranch($odfDocumentBody);

		$odfDocumentDoc->setXML($odfDocument);
		$odfDocumentString = $odfDocumentDoc->getXMLString();

		return $odfDocumentString;
	}
 
	// --------------------------------------------------------------------

	// Generates ODF spreadsheet XML
	function odfSpreadsheet($result)
	{
		global $citeKeysArray; // '$citeKeysArray' is made globally available from within this function

		$exportArray = array(); // array for individually exported records
		$citeKeysArray = array(); // array of cite keys (used to ensure uniqueness of cite keys among all exported records)

		// Map ODF indexes to refbase field names, map ODF reference types to refbase types and define search & replace patterns:
		list($universalSearchReplaceActionsArray, $fieldSpecificSearchReplaceActionsArray, $odfIndexesToRefbaseFieldsArray, $referenceTypesToRefbaseTypesArray) = initializeArrays();

		// Generate the export for each record and push them onto an array:
		while ($row = @ mysql_fetch_array($result))
		{
			// Parse the current record into an array of field data that shall be exported to ODF:
			$recordExportArray = parseRecord($row, $odfIndexesToRefbaseFieldsArray, $referenceTypesToRefbaseTypesArray, $universalSearchReplaceActionsArray, $fieldSpecificSearchReplaceActionsArray);

			// Export the current record as ODF XML in a spreadsheet table row:
			$record = odfSpreadsheetTableRow($recordExportArray, "data");

			if (!empty($record)) // unless the record buffer is empty...
				array_push($exportArray, $record); // ...add it to an array of exports
		}

		$odfSpreadsheet = new XML("office:spreadsheet");

		$odfSpreadsheetTable = new XMLBranch("table:table");
		$odfSpreadsheetTable->setTagAttribute("table:name", "biblio");
		$odfSpreadsheetTable->setTagAttribute("table:style-name", "ta1");

		$columnHeadings = odfSpreadsheetTableRow($odfIndexesToRefbaseFieldsArray, "heading"); // export column headings as ODF XML in a spreadsheet table row
		$odfSpreadsheetTable->addXMLasBranch($columnHeadings);

		foreach ($exportArray as $tableRowXML) 
			$odfSpreadsheetTable->addXMLasBranch($tableRowXML);

		$odfSpreadsheet->addXMLBranch($odfSpreadsheetTable);

		return $odfSpreadsheet;
	}

	// --------------------------------------------------------------------

	// Returns an XML table-row object of a single record
	function odfSpreadsheetTableRow($recordExportArray, $rowType)
	{
		// create an XML object for a single record
		$record = new XML("table:table-row");

		if ($rowType == "heading")
		{
			$record->setTagAttribute("table:style-name", "ro1");

			foreach ($recordExportArray as $odfIndex => $indexValue)
			{
				$tableCell = new XMLBranch("table:table-cell");

				$tableCell->setTagAttribute("office:value-type", "string");
				$tableCell->setTagContent($odfIndex, "table:table-cell/text:p");

				$record->addXMLBranch($tableCell);
			}
		}
		else // $rowType = "data"
		{
			$record->setTagAttribute("table:style-name", "ro2");

			foreach ($recordExportArray as $odfIndex => $indexValue)
			{
				$tableCell = new XMLBranch("table:table-cell");

				if (!empty($indexValue))
				{
					$tableCell->setTagAttribute("office:value-type", "string");
					$tableCell->setTagContent($indexValue, "table:table-cell/text:p");
				}

				$record->addXMLBranch($tableCell);
			}
		}

		return $record;
	}

	// --------------------------------------------------------------------

	// Parse a refbase record into an array of field data that shall be exported to ODF:
	function parseRecord($row, $odfIndexesToRefbaseFieldsArray, $referenceTypesToRefbaseTypesArray, $universalSearchReplaceActionsArray, $fieldSpecificSearchReplaceActionsArray)
	{
		global $officialDatabaseName; // these variables are defined in 'ini.inc.php'
		global $databaseBaseURL;
		global $contentTypeCharset;
		global $convertExportDataToUTF8;

		$fieldParametersArray = array();

		// this is a stupid hack that maps the names of the '$row' array keys to those used
		// by the '$formVars' array (which is required by function 'generateCiteKey()')
		// (eventually, the '$formVars' array should use the MySQL field names as names for its array keys)
		$formVars = buildFormVarsArray($row); // function 'buildFormVarsArray()' is defined in 'include.inc.php'

		// generate or extract the cite key for this record
		$citeKey = generateCiteKey($formVars); // function 'generateCiteKey()' is defined in 'include.inc.php'


		// PARSE RECORD:

		// parse the '$odfIndexesToRefbaseFieldsArray' which maps ODF indexes to refbase field names and assign fields accordingly:
		foreach ($odfIndexesToRefbaseFieldsArray as $odfIndex => $refbaseField)
		{
			if (empty($odfIndexesToRefbaseFieldsArray[$odfIndex]))
			{
				$fieldParametersArray[$odfIndex] = ""; // for any unsupported ODF index we'll insert an empty string
			}
			else
			{
				// copy row field data to array of field parameters (using the corresponding ODF index name as element key):
				if(!is_array($odfIndexesToRefbaseFieldsArray[$odfIndex]))
				{
					$fieldParametersArray[$odfIndex] = $row[$refbaseField];
				}
				else // if the current index's value in '$odfIndexesToRefbaseFieldsArray' is an array...
				{
					$useDefault = true;

					// ...we'll extract field data from different refbase fields depending on the current record's reference type:
					foreach ($odfIndexesToRefbaseFieldsArray[$odfIndex] as $referenceType => $refbaseField)
						if (($row['type'] == $referenceType))
						{
							$useDefault = false;

							if (is_array($odfIndexesToRefbaseFieldsArray[$odfIndex][$referenceType]))
							{
								foreach ($odfIndexesToRefbaseFieldsArray[$odfIndex][$referenceType] as $refbaseField)
									if (!empty($refbaseField) AND !empty($row[$refbaseField]))
									{
										$fieldParametersArray[$odfIndex] = $row[$refbaseField];
										break;
									}
							}
							elseif (!empty($refbaseField) AND !empty($row[$refbaseField]))
							{
								$fieldParametersArray[$odfIndex] = $row[$refbaseField];
							}

							break;
						}

					// 'Other' is used as default for all refbase types that were NOT explicitly specified:
					if ($useDefault AND !isset($fieldParametersArray[$odfIndex]) AND isset($odfIndexesToRefbaseFieldsArray[$odfIndex]['Other']))
					{
						if (is_array($odfIndexesToRefbaseFieldsArray[$odfIndex]['Other']))
						{
							foreach ($odfIndexesToRefbaseFieldsArray[$odfIndex]['Other'] as $refbaseField)
								if (!empty($refbaseField) AND !empty($row[$refbaseField]))
								{
									$fieldParametersArray[$odfIndex] = $row[$refbaseField];
									break;
								}
						}
						elseif (!empty($odfIndexesToRefbaseFieldsArray[$odfIndex]['Other']) AND !empty($row[$odfIndexesToRefbaseFieldsArray[$odfIndex]['Other']]))
							$fieldParametersArray[$odfIndex] = $row[$odfIndexesToRefbaseFieldsArray[$odfIndex]['Other']];
					}

					// if this ODF field is still not set, 'Any' is used as default, no matter whether any refbase types were specified explicitly or not:
					if (!isset($fieldParametersArray[$odfIndex]) AND isset($odfIndexesToRefbaseFieldsArray[$odfIndex]['Any']))
					{
						if (is_array($odfIndexesToRefbaseFieldsArray[$odfIndex]['Any']))
						{
							foreach ($odfIndexesToRefbaseFieldsArray[$odfIndex]['Any'] as $refbaseField)
								if (!empty($refbaseField) AND !empty($row[$refbaseField]))
								{
									$fieldParametersArray[$odfIndex] = $row[$refbaseField];
									break;
								}
						}
						elseif (!empty($odfIndexesToRefbaseFieldsArray[$odfIndex]['Any']) AND !empty($row[$odfIndexesToRefbaseFieldsArray[$odfIndex]['Any']]))
							$fieldParametersArray[$odfIndex] = $row[$odfIndexesToRefbaseFieldsArray[$odfIndex]['Any']];
					}

					// if this ODF field isn't set yet, provide an empty string:
					if (!isset($fieldParametersArray[$odfIndex]))
							$fieldParametersArray[$odfIndex] = "";
				}
			}
		}


		// POST-PROCESS FIELD DATA:

		// currently, we'll always overwrite the record serial in the 'Identifier' field with the generated cite key:
		// (this means that NO identifier will be exported if you've unchecked the export option "Include cite keys on export")
		$fieldParametersArray['Identifier'] = $citeKey;

		// convert refbase type names into ODF type numbers:
		$fieldParametersArray['BibliographyType'] = $referenceTypesToRefbaseTypesArray[$fieldParametersArray['BibliographyType']];

		// for theses, set the correct ODF type:
		if (!empty($row['thesis']))
		{
			if (($row['thesis'] == "Ph.D. thesis") OR ($row['thesis'] == "Doctoral thesis"))
				$fieldParametersArray['BibliographyType'] = "11"; // Dissertation
			else
				$fieldParametersArray['BibliographyType'] = "9"; // Thesis

			if (isset($fieldParametersArray['Annote']))
				$fieldParametersArray['Annote'] .= "; " . $row['thesis']; // append type of thesis to ODF 'Annote' field
			else
				$fieldParametersArray['Annote'] = $row['thesis'];
		}

		// if a DOI was copied to the URL field, we'll need to add the DOI resolver:
		if (!empty($row['doi']) AND preg_match("/^\d{2}\.\d{4}\//", $fieldParametersArray['URL']))
			$fieldParametersArray['URL'] = "http://dx.doi.org/" . $fieldParametersArray['URL'];

		// use the series volume as volume if 'series_volume' contains some info, but 'volume' doesn't:
		if (empty($row['volume']) AND !empty($row['series_volume']))
			$fieldParametersArray['Volume'] = $row['series_volume'];

		// set the fourth ODF custom field to a refbase database attribution string and the database URL:
		$fieldParametersArray['Custom4'] = "exported from " . $officialDatabaseName . " (" . $databaseBaseURL . ")";

		// set the fifth ODF custom field to the record's permanent database URL:
		$fieldParametersArray['Custom5'] = $databaseBaseURL . "show.php?record=" . $row['serial'];

		// apply universal search & replace actions, encode special chars and charset conversions to every field that shall be exported:
		foreach ($fieldParametersArray as $fieldName => $fieldValue)
			if (!empty($fieldValue))
			{
				// perform universal search & replace actions:
				if (!empty($universalSearchReplaceActionsArray))
					$fieldParametersArray[$fieldName] = searchReplaceText($universalSearchReplaceActionsArray, $fieldParametersArray[$fieldName], true); // function 'searchReplaceText()' is defined in 'include.inc.php'

				// we only convert those special chars to entities which are supported by XML:
				$fieldParametersArray[$fieldName] = encodeHTMLspecialchars($fieldParametersArray[$fieldName]); // function 'encodeHTMLspecialchars()' is defined in 'include.inc.php'

				// convert field data to UTF-8 (if '$convertExportDataToUTF8' is set to "yes" in 'ini.inc.php' and character encoding is not UTF-8 already):
				// (note that charset conversion can only be done *after* the cite key has been generated, otherwise cite key generation will produce garbled text!)
				if (($convertExportDataToUTF8 == "yes") AND ($contentTypeCharset != "UTF-8"))
					$fieldParametersArray[$fieldName] = convertToCharacterEncoding("UTF-8", "IGNORE", $fieldParametersArray[$fieldName]); // function 'convertToCharacterEncoding()' is defined in 'include.inc.php'
			}

		// apply field-specific search & replace 'actions' to all fields that are listed in the 'fields' element of the arrays contained in '$fieldSpecificSearchReplaceActionsArray':
		foreach ($fieldSpecificSearchReplaceActionsArray as $fieldActionsArray)
			foreach ($fieldParametersArray as $fieldName => $fieldValue)
				if (in_array($fieldName, $fieldActionsArray['fields']))
					$fieldParametersArray[$fieldName] = searchReplaceText($fieldActionsArray['actions'], $fieldValue, true); // function 'searchReplaceText()' is defined in 'include.inc.php'

		return $fieldParametersArray;
	}

	// --------------------------------------------------------------------

	// Map ODF indexes to refbase field names, map ODF reference types to refbase types and define search & replace patterns:
	function initializeArrays()
	{
		global $convertExportDataToUTF8; // defined in 'ini.inc.php'

		// The array '$transtab_refbase_unicode' contains search & replace patterns for conversion from refbase markup to Unicode entities.
		global $transtab_refbase_unicode; // defined in 'transtab_refbase_unicode.inc.php'

		// Defines universal search & replace actions:
		// (Note that the order of array elements IS important since it defines when a search/replace action gets executed)
		// (If you don't want to perform any search and replace actions, specify an empty array, like: '$universalSearchReplaceActionsArray = array();'.
		//  Note that the search patterns MUST include the leading & trailing slashes -- which is done to allow for mode modifiers such as 'imsxU'.)
		//						"/Search Pattern/"  =>  "Replace Pattern"
		$universalSearchReplaceActionsArray = array(); // example: 'array("/&/" => "&amp;", "/</" => "&lt;");'


		// Defines field-specific search & replace 'actions' that will be applied to all those ODF fields that are listed in the corresponding 'fields' element:
		// (If you don't want to perform any search and replace actions, specify an empty array, like: '$fieldSpecificSearchReplaceActionsArray = array();'.
		//  Note that the search patterns MUST include the leading & trailing slashes -- which is done to allow for mode modifiers such as 'imsxU'.)
		// 												"/Search Pattern/"  =>  "Replace Pattern"
		$fieldSpecificSearchReplaceActionsArray = array();

		if ($convertExportDataToUTF8 == "yes")
			$fieldSpecificSearchReplaceActionsArray[] = array(
																'fields'  => array("Title", "Organizations", "Custom1", "Pages"),
																'actions' => $transtab_refbase_unicode
															);


		// Map ODF indexes to refbase field names:
		// Notes: - the special array key "Other" serves as a default for all refbase types that were NOT specifyied explicitly
		//        - the special array key "Any" serves as a default for all refbase types
		//        - instead of specifying a string with a single refbase field name, you can also give a sub-array of multiple refbase field names
		//          where the first non-empty field will be taken as ODF field value
		// 											"ODF index name"   => "refbase field name" // ODF index description (comment)
		$odfIndexesToRefbaseFieldsArray = array(
													"Identifier"       => "serial", // a unique identifier for the bibliographic data (the 'parseRecord()' function will overwrite the record serial with a correct cite key if necessary)
													"BibliographyType" => "type", // the type of the bibliographic reference. It is of the type bibliographydatafield
													"Address"          => "place", // the address of the publisher
													"Annote"           => "notes", // an annotation
													"Author"           => "author", // the name(s) of the author(s)
													"Booktitle"        => array("Book Chapter" => "publication"), // the title of the book
													"Chapter"          => array("Book Chapter" => "volume"), // name or number of the chapter
													"Edition"          => "edition", // the number or name of the edition
													"Editor"           => "editor", // the name(s) of the editor(s)
													"Howpublished"     => "", // a description of the type of the publishing
													"Institution"      => "", // the name of the institution where the publishing was created
													"Journal"          => array("Journal Article" => array("publication", "abbrev_journal")), // the name of the journal
													"Month"            => "", // number or name of the month of the publishing
													"Note"             => "user_notes", // a note
													"Number"           => "issue", // the number of the publishing
													"Organizations"    => "address", // the name of the organizations where the publishing was created
													"Pages"            => "pages", // the number(s) of the page(s) of the reference into a publishing
													"Publisher"        => "publisher", // the name of the publisher
													"School"           => "corporate_author", // the name of the university or school where the publishing was created
													"Series"           => array("Any" => array("series_title", "abbrev_series_title")), // the series of the publishing
													"Title"            => "title", // the title of the publishing
													"ReportType"       => "", // a description of the type of the report
													"Volume"           => "volume", // the volume of the publishing
													"Year"             => "year", // the year when the publishing was created
													"URL"              => array("Any" => array("doi", "url")), // URL of the publishing (in case of a DOI, the 'parseRecord()' function will prefix it with a DOI resolver)
													"ISBN"             => "isbn", // the ISBN data of the publishing
													"Custom1"          => "keywords", // user defined data
													"Custom2"          => "user_keys", // user defined data
													"Custom3"          => "user_groups", // user defined data
													"Custom4"          => "", // user defined data (this field will be set to the database name and URL in function 'parseRecord()')
													"Custom5"          => "" // user defined data (this field will be set to the record's permanent URL in function 'parseRecord()')
												);


		// This array matches ODF reference types with their corresponding refbase types:
		// ODF types which are currently not supported by refbase are commented out;
		// '#fallback#' in comments indicates a type mapping that is not a perfect match but as close as currently possible)
		// 											"refbase type" => "ODF type" // display name of ODF reference type (comment)
		$referenceTypesToRefbaseTypesArray = array(
		//											"Journal Article"  =>  "0", // Article (#fallback#; correct?)
													"Book Whole"       =>  "1", // Book
		//											"Book Whole"       =>  "2", // Brochures (#fallback#)
		//											"Book Whole"       =>  "3", // Conference proceeding (correct? conference?)
		//											"Book Chapter"     =>  "4", // Book excerpt (#fallback#)
													"Book Chapter"     =>  "5", // Book excerpt with title
		//											"Book Chapter"     =>  "6", // Conference proceeding (#fallback#; correct? inproceedings?)
													"Journal Article"  =>  "7", // Journal (AFAIK, 'Journal' means a journal article and not a whole journal)
		//											"Book Whole"       =>  "8", // Tech. Documentation (#fallback#)
		//											"Book Whole"       =>  "9", // Thesis (#fallback#; function 'parseRecord()' will set the ODF type to 'Thesis' if the refbase 'thesis' field isn't empty)
		//											"Book Whole"       => "10", // Miscellaneous (#fallback#)
		//											"Book Whole"       => "11", // Dissertation (#fallback#; function 'parseRecord()' will set the ODF type to 'Dissertation' if the refbase 'thesis' field contains either 'Ph.D. thesis' or 'Doctoral thesis'))
		//											"Book Whole"       => "12", // Conference proceeding (#fallback#; correct? proceedings?)
		//											"Book Whole"       => "13", // Research report (#fallback#)
													"Manuscript"       => "14", // Unpublished (#fallback#)
		//											""                 => "15", // e-mail (unsupported)
		//											""                 => "16", // WWW document (unsupported)
		//											""                 => "17", // User-defined1 (unsupported)
		//											""                 => "18", // User-defined2 (unsupported)
		//											""                 => "19", // User-defined3 (unsupported)
													"Journal"          => "20", // User-defined4 (a whole journal)
													"Map"              => "21"  // User-defined5
												);


		return array($universalSearchReplaceActionsArray, $fieldSpecificSearchReplaceActionsArray, $odfIndexesToRefbaseFieldsArray, $referenceTypesToRefbaseTypesArray);
	}

	function zipODF($content) {
		$zipfile = new zipfile();  
		//$zipfile -> add_dir("META-INF/"); 
		$zipfile -> addFile($content, "content.xml");
		$zipfile -> addFile("", "styles.xml");
		$zipfile -> addFile("application/vnd.oasis.opendocument.spreadsheet","mimetype");
		$zipfile -> addFile("<?xml version=\"1.0\" encoding=\"UTF-8\"?><office:document-meta xmlns:office=\"urn:oasis:names:tc:opendocument:xmlns:office:1.0\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:meta=\"urn:oasis:names:tc:opendocument:xmlns:meta:1.0\" xmlns:ooo=\"http://openoffice.org/2004/office\" office:version=\"1.0\"><office:meta><meta:generator>refbase</meta:generator></office:meta></office:document-meta>","meta.xml");
		$zipfile -> addFile("<?xml version=\"1.0\" encoding=\"UTF-8\"?><manifest:manifest xmlns:manifest=\"urn:oasis:names:tc:opendocument:xmlns:manifest:1.0\"><manifest:file-entry manifest:media-type=\"application/vnd.oasis.opendocument.spreadsheet\" manifest:full-path=\"/\"/><manifest:file-entry manifest:media-type=\"text/xml\" manifest:full-path=\"content.xml\"/><manifest:file-entry manifest:media-type=\"text/xml\" manifest:full-path=\"styles.xml\"/><manifest:file-entry manifest:media-type=\"text/xml\" manifest:full-path=\"meta.xml\"/></manifest:manifest>","META-INF/manifest.xml");	
		return $zipfile;
	}

	// --------------------------------------------------------------------
?>
