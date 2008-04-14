<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./export/bibutils/export_xml2word.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    21-Mar-07, 23:17
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is an export format file (which must reside within the 'export/' sub-directory of your refbase root directory). It contains a version of the
	// 'exportRecords()' function that outputs records according to the Word 2007 bibliography XML format (http://office.microsoft.com/word/).
	// This function is basically a wrapper for the bibutils 'xml2wordbib' command line tool (http://www.scripps.edu/~cdputnam/software/bibutils/bibutils.html).

	// --------------------------------------------------------------------

	// --- BEGIN EXPORT FORMAT ---

	// Export found records in Word 2007 bibliography format:

	// Requires the following packages (available under the GPL):
	//    - bibutils <http://www.scripps.edu/~cdputnam/software/bibutils/bibutils.html>
	//    - ActiveLink PHP XML Package <http://www.active-link.com/software/>

	function exportRecords($result, $rowOffset, $showRows, $exportStylesheet, $displayType)
	{
		// function 'exportBibutils()' is defined in 'execute.inc.php'
		// NOTE: if you're using a Bibutils version prior to Bibutils v3.40,
		//       you must rename "xml2wordbib" to "xml2word" in the line below
		return exportBibutils($result,"xml2wordbib");
	}

	// --- END EXPORT FORMAT ---

	// --------------------------------------------------------------------
?>
