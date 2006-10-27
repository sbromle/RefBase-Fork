<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./import/import_refworks2refbase.php
	// Created:    25-Aug-06, 13:10
	// Modified:   25-Aug-06, 17:44

	// This is an import format file (which must reside within the 'import/' sub-directory of your refbase root directory). It contains a version of the
	// 'importRecords()' function that imports records from 'RefWorks'-formatted data, i.e. data formatted according to the tagged text format used
	// by the commercial bibliographic web application 'RefWorks' (http://www.refworks.com).

	// --------------------------------------------------------------------

	// --- BEGIN IMPORT FORMAT ---

	// Import records from RefWorks-formatted source data:

	function importRecords($sourceText, $importRecordsRadio, $importRecordNumbersArray)
	{
		// parse RefWorks format:
		return refworksToRefbase($sourceText, $importRecordsRadio, $importRecordNumbersArray); // function 'refworksToRefbase()' is defined in 'import.inc.php'
	}

	// --- END IMPORT FORMAT ---

	// --------------------------------------------------------------------
?>
