<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./unapi.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    15-Jul-06, 11:59
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This script serves as a (faceless) routing page which takes a query to the unAPI server
	// and converts the query into a native refbase query which is then passed to 'show.php'.
	// More info is given at <http://unapi.refbase.net/>.

	// Examples for recognized unAPI queries:
	// (URL encoding left out for better readibility, and your server URL will be different,
	//  of course)
	//
	// - ask the unAPI server to list all of its supported formats:
	//     unapi.php
	//
	// - ask the unAPI server to list all supported formats for a record with serial number 1:
	//     unapi.php?id=http://polaris.ipoe.uni-kiel.de/refs/show.php?record=1
	//
	// - return record with serial number 1 in various export formats:
	//     unapi.php?id=http://polaris.ipoe.uni-kiel.de/refs/show.php?record=1&format=bibtex
	//     unapi.php?id=http://polaris.ipoe.uni-kiel.de/refs/show.php?record=1&format=endnote
	//     unapi.php?id=http://polaris.ipoe.uni-kiel.de/refs/show.php?record=1&format=ris
	//     unapi.php?id=http://polaris.ipoe.uni-kiel.de/refs/show.php?record=1&format=atom
	//     unapi.php?id=http://polaris.ipoe.uni-kiel.de/refs/show.php?record=1&format=mods
	//     unapi.php?id=http://polaris.ipoe.uni-kiel.de/refs/show.php?record=1&format=oai_dc
	//     unapi.php?id=http://polaris.ipoe.uni-kiel.de/refs/show.php?record=1&format=srw_dc
	//     unapi.php?id=http://polaris.ipoe.uni-kiel.de/refs/show.php?record=1&format=srw_mods
	//
	// - return record with serial number 1 in various citation formats:
	//     unapi.php?id=http://polaris.ipoe.uni-kiel.de/refs/show.php?record=1&format=rtf
	//     unapi.php?id=http://polaris.ipoe.uni-kiel.de/refs/show.php?record=1&format=pdf
	//     unapi.php?id=http://polaris.ipoe.uni-kiel.de/refs/show.php?record=1&format=latex
	//     unapi.php?id=http://polaris.ipoe.uni-kiel.de/refs/show.php?record=1&format=markdown
	//     unapi.php?id=http://polaris.ipoe.uni-kiel.de/refs/show.php?record=1&format=text
	//
	// - the unAPI server does also accept multiple record IDs, the given example will return
	//   records with serial numbers 1, 12, 459 and 600 as citations in RTF format:
	//     unapi.php?id=http://polaris.ipoe.uni-kiel.de/refs/show.php?records=1,12,459,600&format=rtf

	// For more on unAPI, see:
	//   <http://unapi.info/specs/>

	// TODO: - return appropriate HTML status codes
	//       - improve error handling


	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/include.inc.php'; // include common functions
	include 'initialize/ini.inc.php'; // include common variables
	include 'includes/unapi.inc.php'; // include functions that deal with unAPI response XML
	include_once 'includes/webservice.inc.php'; // include functions that are commonly used with the refbase webservices

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// --------------------------------------------------------------------

	// Initialize preferred display language:
	// (note that 'locales.inc.php' has to be included *after* the call to the 'start_session()' function)
	include 'includes/locales.inc.php'; // include the locales

	// --------------------------------------------------------------------

	// Extract parameters passed to the script:

	if (isset($_REQUEST['id']) AND !empty($_REQUEST['id']))
		$unapiID = $_REQUEST['id']; // the value must be a permanent URL pointer to a single refbase record (e.g.: 'http://polaris.ipoe.uni-kiel.de/refs/show.php?record=1')
	else
		$unapiID = "";

	// get information how record data shall be returned:
	// - 'bibtex' => return as BibTeX data with mime type 'text/plain'
	// - 'endnote' => return as Endnote data with mime type 'text/plain'
	// - 'ris' => return as RIS data with mime type 'text/plain'
	// - 'atom' => return as Atom XML data with mime type 'application/atom+xml'
	// - 'mods' => return as MODS XML data with mime type 'application/xml'
	// - 'oai_dc' => return as OAI_DC XML data with mime type 'application/xml'
	// - 'srw_dc' => return as SRW_DC XML data with mime type 'application/xml'
	// - 'srw_mods' => return as SRW_MODS XML data with mime type 'application/xml'
	// - 'html' => return as HTML with mime type 'text/html'
	// - 'rtf' => return as RTF data with mime type 'application/rtf'
	// - 'pdf' => return as PDF data with mime type 'application/pdf'
	// - 'latex' => return as LaTeX data with mime type 'application/x-latex'
	// - 'markdown' => return as Markdown TEXT data with mime type 'text/plain'
	// - 'text' or 'ascii' => return as ASCII TEXT data with mime type 'text/plain'
	// TODO: add 'ADS', 'ISI', 'ODF XML' and 'Word XML'
	if (isset($_REQUEST['format']) AND preg_match("/^(BibTeX|Endnote|RIS|Atom( XML)?|MODS( XML)?|(OAI_)?DC( XML)?|SRW( XML|_MODS|_DC)?|html|RTF|PDF|LaTeX|Markdown|ASCII|TEXT)$/i", $_REQUEST['format']))
		$unapiFormat = $_REQUEST['format'];
	else
		$unapiFormat = "";

	// Set some required parameters based on the requested format:

	if (preg_match("/^(BibTeX|Endnote|RIS|Atom( XML)?|MODS( XML)?|(OAI_)?DC( XML)?|SRW( XML|_MODS|_DC)?)$/i", $unapiFormat))
	{
		$displayType = "Export";

		// Standardize XML export format names:
		// NOTE: the below regex patterns are potentially too lax and might cause misbehaviour in case any custom export formats have been added
		// TODO: add 'ODF XML' and 'Word XML' when they're supported by 'unapi.php'
		if (preg_match("/^Atom/i", $unapiFormat))
			$exportFormat = "Atom XML";
		elseif (preg_match("/^MODS/i", $unapiFormat))
			$exportFormat = "MODS XML";
		elseif (preg_match("/^(OAI_)?DC/i", $unapiFormat))
			$exportFormat = "OAI_DC XML";
		elseif (preg_match("/^SRW_DC/i", $unapiFormat))
			$exportFormat = "SRW_DC XML";
		elseif (preg_match("/^SRW/i", $unapiFormat))
			$exportFormat = "SRW_MODS XML";
		else
			$exportFormat = $unapiFormat;

		$citeType = "html";
	}
	elseif (preg_match("/^(html|RTF|PDF|LaTeX|Markdown|ASCII|TEXT)$/i", $unapiFormat))
	{
		$displayType = "Cite";
		$exportFormat = "";

		if (preg_match("/^TEXT/i", $unapiFormat))
			$citeType = "ASCII";
		else
			$citeType = $unapiFormat;
	}
	else // unrecognized format
	{
		$displayType = ""; // if the 'submit' parameter is empty, this will produce the default view
		$exportFormat = ""; // if no export format was given, 'show.php' will use the default export format which is defined by the '$defaultExportFormat' variable in 'ini.inc.php'
		$citeType = "html";
	}

	// For the context of 'unapi.php' we set some parameters explicitly:

	$exportType = "file";
	$citeOrder = "author";
	$citeStyle = ""; // if no cite style was given, 'show.php' will use the default cite style which is defined by the '$defaultCiteStyle' variable in 'ini.inc.php'

	$exportContentType = "application/xml"; // this will be used for unAPI XML response output

	// -------------------------------------------------------------------------------------------------------------------

	// Check if the correct parameters have been passed:
	if (empty($unapiID) OR (!empty($unapiID) AND !isset($_REQUEST['format'])))
	{
		// if 'unapi.php' was called without the 'format' parameter, we'll return an appropriate unAPI XML response:

		// Set the appropriate mimetype & set the character encoding to the one given
		// in '$contentTypeCharset' (which is defined in 'ini.inc.php'):
		setHeaderContentType($exportContentType, $contentTypeCharset); // function 'setHeaderContentType()' is defined in 'include.inc.php'

		echo unapiExplainResponse($unapiID); // function 'unapiExplainResponse()' is defined in 'unapi.inc.php'
	}

	// Note: error handling should be improved:
	//       - the script should return "404 Not Found" if the requested identifier is NOT available on the server: header("HTTP/1.1 404 Not Found");
	//         (currently, an empty file is returned)
	//       - the script should return "406 Not Acceptable" if the requested identifier is available on the server but is NOT available in the requested format
	//         (currently, the requested record is displayed in HTML Details view if an unrecognized format was given)

	// -------------------------------------------------------------------------------------------------------------------

	else // the script was called with the parameters 'id' and 'format'
	{
		// Build the correct query URL:
		// (we skip unnecessary parameters here since 'show.php' will use it's default values for them)
		$queryURL = "&submit=" . $displayType . "&exportFormat=" . rawurlencode($exportFormat) . "&exportType=" . $exportType  . "&citeOrder=" . $citeOrder . "&citeStyle=" . rawurlencode($citeStyle) . "&citeType=" . $citeType;

		// call 'show.php' with the correct query URL in order to output record details in the requested format:
		header("Location: $unapiID$queryURL");
	}

	// -------------------------------------------------------------------------------------------------------------------
?>
