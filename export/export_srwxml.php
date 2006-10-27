<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./export/export_srwxml.php
	// Created:    17-May-05, 16:31
	// Modified:   29-Oct-05, 17:16

	// This exports SRW XML. This file must reside in the 'export' directory of the refbase root directory.
	// It uses functions from include files 'srwxml.inc.php' and 'modsxml.inc.php' that require the ActiveLink PHP XML Package,
	// which is available under the GPL from: <http://www.active-link.com/software/>
	
	// --------------------------------------------------------------------

	// --- BEGIN EXPORT FORMAT ---

	// Export found records as SRW XML:
	function exportRecords($result, $rowOffset, $showRows, $exportStylesheet, $displayType)
	{
		// Find out how many rows are available:
		$rowsFound = @ mysql_num_rows($result);

		if ($rowsFound > 0 && ($rowOffset + 1) > $rowsFound) // Invalid offset for current MySQL result set, error with an appropriate diagnostics response:
		{
			if ($rowsFound == 1)
				$recordString = "record";
			else
				$recordString = "records";

			$recordCollection = srwDiagnostics(61, "Record offset " . ($rowOffset + 1) . " is invalid for current result set: " . $rowsFound . " " . $recordString . " found", $exportStylesheet); // function 'srwDiagnostics()' is defined in 'srwxml.inc.php'
		}
		else // Generate and serve a SRW XML file of ALL records:
		{
			$recordCollection = srwCollection($result, $rowOffset, $showRows, $exportStylesheet, $displayType); // function 'srwCollection()' is defined in 'srwxml.inc.php'
		}
	
		return $recordCollection;
	}

	// --- END EXPORT FORMAT ---

	// --------------------------------------------------------------------
?>
