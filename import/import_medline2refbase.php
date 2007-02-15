<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./import/import_medline2refbase.php
	// Created:    24-Feb-06, 02:07
	// Modified:   26-Feb-06, 21:49

	// This is an import format file (which must reside within the 'import/' sub-directory of your refbase root directory). It contains a version of the
	// 'importRecords()' function that imports records from 'Pubmed'-formatted data, i.e. data that were exported from the Pubmed Internet Database
	// Service (http://www.pubmed.gov/) in 'MEDLINE' format.

	// --------------------------------------------------------------------

	// --- BEGIN IMPORT FORMAT ---

	// Import records from Pubmed-formatted source data:

	function importRecords($sourceText, $importRecordsRadio, $importRecordNumbersArray)
	{
		// parse Pubmed MEDLINE format:
		return medlineToRefbase($sourceText, $importRecordsRadio, $importRecordNumbersArray); // function 'medlineToRefbase()' is defined in 'import.inc.php'
	}

	// --- END IMPORT FORMAT ---

	// --------------------------------------------------------------------
?>
