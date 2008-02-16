<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./import/import_crossref2refbase.php
	// Repository: $HeadURL$
	// Author(s):  Richard Karnesky <mailto:karneskygmail.com>
	//
	// Created:    15-Feb-08, 16:45
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is an import format file (which must reside within the 'import/' sub-directory of your refbase root directory). It contains a version of the
  // 'importRecords()' function that imports records from 'CrossRef "unixref XML"'-formatted data
  // i.e. that from their OpenURL server:
  // http://www.crossref.org/openurl/?noredirect=true&format=unixref&id=doi%3A10.1103%2FPhysRev.47.777

	// --------------------------------------------------------------------

	// --- BEGIN IMPORT FORMAT ---

	// Import records from CrossRef-formatted source data:

	function importRecords($sourceText, $importRecordsRadio, $importRecordNumbersArray)
	{
		// parse CrossRef format:
		return crossrefToRefbase($sourceText, $importRecordsRadio, $importRecordNumbersArray); // function 'crossrefToRefbase()' is defined in 'import.inc.php'
	}

	// --- END IMPORT FORMAT ---

	// --------------------------------------------------------------------
?>
