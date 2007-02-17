<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/unapi.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    15-Jul-06, 15:25
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This include file contains functions that deal with unAPI response XML.
	// Requires ActiveLink PHP XML Package, which is available under the GPL from:
	// <http://www.active-link.com/software/>. See 'unapi.php' for more info.


	// Incorporate some include files:
	include_once 'includes/webservice.inc.php'; // include functions that are commonly used with the refbase webservices

	// Import the ActiveLink Packages
	require_once("classes/include.php");
	import("org.active-link.xml.XML");
	import("org.active-link.xml.XMLDocument");

	// --------------------------------------------------------------------

	// return an unAPI XML response if the unAPI request issued either of the following:
	// - http://.../refs/unapi.php
	// - http://.../refs/unapi.php?id=http://polaris.ipoe.uni-kiel.de/refs/show.php?record=1
	function unapiExplainResponse($unapiID)
	{
		global $contentTypeCharset; // these variables are specified in 'ini.inc.php'

		$unapiCollectionDoc = new XMLDocument();
		$unapiCollectionDoc->setEncoding($contentTypeCharset);

		$unapiCollection = new XML("formats");

		if (!empty($unapiID))
			$unapiCollection->setTagAttribute("id", $unapiID);

		addNewBranch($unapiCollection, "format", array("name" => "bibtex", "type" => "text/plain", "docs" => "http://en.wikipedia.org/wiki/BibTeX"), ""); // function 'addNewBranch()' is defined in 'webservice.inc.php'
		addNewBranch($unapiCollection, "format", array("name" => "endnote", "type" => "text/plain", "docs" => "http://www.ecst.csuchico.edu/~jacobsd/bib/formats/endnote.html"), "");
		addNewBranch($unapiCollection, "format", array("name" => "ris", "type" => "text/plain", "docs" => "http://www.adeptscience.co.uk/kb/article/A626"), "");
		addNewBranch($unapiCollection, "format", array("name" => "mods", "type" => "application/xml", "docs" => "http://www.loc.gov/standards/mods/"), "");
		addNewBranch($unapiCollection, "format", array("name" => "srw_mods", "type" => "application/xml", "docs" => "http://www.loc.gov/standards/sru/"), "");
		addNewBranch($unapiCollection, "format", array("name" => "html", "type" => "text/html", "docs" => "http://www.w3.org/MarkUp/"), "");
		addNewBranch($unapiCollection, "format", array("name" => "rtf", "type" => "application/rtf", "docs" => "http://en.wikipedia.org/wiki/Rich_Text_Format"), "");
		addNewBranch($unapiCollection, "format", array("name" => "pdf", "type" => "application/pdf", "docs" => "http://partners.adobe.com/public/developer/pdf/index_reference.html"), "");
		addNewBranch($unapiCollection, "format", array("name" => "latex", "type" => "application/x-latex", "docs" => "http://en.wikipedia.org/wiki/LaTeX"), "");
		addNewBranch($unapiCollection, "format", array("name" => "markdown", "type" => "text/plain", "docs" => "http://daringfireball.net/projects/markdown/"), "");
		addNewBranch($unapiCollection, "format", array("name" => "text", "type" => "text/plain"), "");

		$unapiCollectionDoc->setXML($unapiCollection);
		$unapiCollectionString = $unapiCollectionDoc->getXMLString();

		return $unapiCollectionString;
	}

	// --------------------------------------------------------------------
?>
