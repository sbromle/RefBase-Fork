<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./import/import_arxiv2refbase.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    09-Jun-08, 16:00
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is an import format file (which must reside within the 'import/' sub-directory of your refbase root directory). It contains a version of the
	// 'importRecords()' function that imports records from arXiv.org's Atom XML OpenSearch format.
	// More info on the arXiv API and its Atom-based response format is available at <http://export.arxiv.org/api_help/>.

	// --------------------------------------------------------------------

	// --- BEGIN IMPORT FORMAT ---

	// Import records from arXiv source data:

	function importRecords($sourceObject, $importRecordsRadio, $importRecordNumbersArray)
	{
		// parse arXiv Atom XML format:
		return arxivToRefbase($sourceObject, $importRecordsRadio, $importRecordNumbersArray); // function 'arxivToRefbase()' is defined in 'import.inc.php'
	}

	// --- END IMPORT FORMAT ---

	// --------------------------------------------------------------------
?>
