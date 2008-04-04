<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./export/export_atomxml.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    08-Jan-08, 22:00
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This exports OpenSearch Atom XML. This file must reside in the 'export' directory of the refbase root directory.
	// It uses functions from include files 'opensearch.inc.php' and 'atomxml.inc.php' that require the ActiveLink PHP XML Package,
	// which is available under the GPL from: <http://www.active-link.com/software/>

	// --------------------------------------------------------------------

	// --- BEGIN EXPORT FORMAT ---

	// Export found records as OpenSearch Atom XML:
	function exportRecords($result, $rowOffset, $showRows, $exportStylesheet, $displayType)
	{
		global $rowsFound;

		if ($rowsFound > 0 && ($rowOffset + 1) > $rowsFound) // Invalid offset for current MySQL result set, error with an appropriate diagnostics response:
		{
			if ($rowsFound == 1)
				$recordString = "record";
			else
				$recordString = "records";

			$recordCollection = openSearchDiagnostics(61, "Record offset " . ($rowOffset + 1) . " is invalid for current result set (" . $rowsFound . " " . $recordString . " found)", $exportStylesheet); // function 'openSearchDiagnostics()' is defined in 'opensearch.inc.php'
		}
		else // Generate and serve an OpenSearch Atom XML file of ALL records:
		{
			$recordCollection = atomCollection($result, $rowOffset, $showRows, $exportStylesheet, $displayType); // function 'atomCollection()' is defined in 'atomxml.inc.php'
		}

		return $recordCollection;
	}

	// --- END EXPORT FORMAT ---

	// --------------------------------------------------------------------
?>
