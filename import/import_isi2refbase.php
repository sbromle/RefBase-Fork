<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./import/import_isi2refbase.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    24-Feb-06, 01:38
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is an import format file (which must reside within the 'import/' sub-directory of your refbase root directory). It contains a version of the
	// 'importRecords()' function that imports records from 'ISI'-formatted data, i.e. data that were exported from the ISI Web of Science Internet
	// Database Service (http://scientific.thomson.com/products/wos/).

	// --------------------------------------------------------------------

	// --- BEGIN IMPORT FORMAT ---

	// Import records from ISI-formatted source data:

	function importRecords($sourceText, $importRecordsRadio, $importRecordNumbersArray)
	{
		// convert ISI WoS format to CSA format:
		$sourceText = isiToCsa($sourceText); // function 'isiToCsa()' is defined in 'import.inc.php'

		// parse CSA format:
		return csaToRefbase($sourceText, $importRecordsRadio, $importRecordNumbersArray); // function 'csaToRefbase()' is defined in 'import.inc.php'
	}

	// --- END IMPORT FORMAT ---

	// --------------------------------------------------------------------
?>
