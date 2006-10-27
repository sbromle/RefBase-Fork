<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./import/import_ris2refbase.php
	// Created:    23-Feb-06, 02:35
	// Modified:   24-Feb-06, 01:49

	// This is an import format file (which must reside within the 'import/' sub-directory of your refbase root directory). It contains a version of the
	// 'importRecords()' function that imports records from 'RIS'-formatted data, i.e. data formatted according to the standard export format used e.g.
	// by commercial bibliographic packages like 'Reference Manager' (http://www.refman.com).

	// --------------------------------------------------------------------

	// --- BEGIN IMPORT FORMAT ---

	// Import records from RIS-formatted source data:

	function importRecords($sourceText, $importRecordsRadio, $importRecordNumbersArray)
	{
		// parse RIS format:
		return risToRefbase($sourceText, $importRecordsRadio, $importRecordNumbersArray); // function 'risToRefbase()' is defined in 'import.inc.php'
	}

	// --- END IMPORT FORMAT ---

	// --------------------------------------------------------------------
?>
