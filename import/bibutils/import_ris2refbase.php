<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./import/bibutils/import_ris2refbase.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    23-Feb-06, 02:35
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is an import format file (which must reside within the 'import/' sub-directory of your refbase root directory). It contains a version of the
	// 'importRecords()' function that imports records from 'RIS'-formatted data, i.e. data formatted according to the standard export format used e.g.
	// by commercial bibliographic packages like 'Reference Manager' (http://www.refman.com).

	// --------------------------------------------------------------------

	// --- BEGIN IMPORT FORMAT ---

	// Import records from RIS-formatted source data:

	// Requires the following packages (available under the GPL):
	//    - bibutils <http://www.scripps.edu/~cdputnam/software/bibutils/bibutils.html>

	function importRecords($sourceText, $importRecordsRadio, $importRecordNumbersArray)
	{
		// By passing RIS thru bibutils we receive a standard RIS format that may be easier to import than the original one.
		// You can use './import/import_ris2refbase.php' to import RIS records directly.

		// convert RIS format to MODS XML format:
		$sourceText = importBibutils($sourceText,"ris2xml"); // function 'importBibutils()' is defined in 'execute.inc.php'

		// convert MODS XML format to RIS format:
		$sourceText = importBibutils($sourceText,"xml2ris"); // function 'importBibutils()' is defined in 'execute.inc.php'

		// parse RIS format:
		return risToRefbase($sourceText, $importRecordsRadio, $importRecordNumbersArray); // function 'risToRefbase()' is defined in 'import.inc.php'
	}

	// --- END IMPORT FORMAT ---

	// --------------------------------------------------------------------
?>
