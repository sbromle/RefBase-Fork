<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./export/bibutils/export_xml2end.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    28-Sep-04, 22:14
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is an export format file (which must reside within the 'export/' sub-directory of your refbase root directory). It contains a version of the
	// 'exportRecords()' function that outputs records according to the export format used by the commercial bibliographic package 'Endnote' (http://www.endnote.com).
	// This function is basically a wrapper for the bibutils 'xml2end' command line tool (http://www.scripps.edu/~cdputnam/software/bibutils/bibutils.html).

	// --------------------------------------------------------------------

	// --- BEGIN EXPORT FORMAT ---

	// Export found records in 'Endnote' format:

	// Requires the following packages (available under the GPL):
	//    - bibutils <http://www.scripps.edu/~cdputnam/software/bibutils/bibutils.html>
	//    - ActiveLink PHP XML Package <http://www.active-link.com/software/>

	function exportRecords($result, $rowOffset, $showRows, $exportStylesheet, $displayType)
	{
		// function 'exportBibutils()' is defined in 'execute.inc.php'
		return exportBibutils($result,"xml2end");
	}

	// --- END EXPORT FORMAT ---

	// --------------------------------------------------------------------
?>
