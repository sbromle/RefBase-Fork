<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./sitemap.php
	// Repository: $HeadURL: https://refbase.svn.sourceforge.net/svnroot/refbase/branches/bleeding-edge/sitemap.php $
	// Author(s):  Richard Karnesky <mailto:rkarnesky@gmail.com>
	//
	// Created:    17-May-08, 15:50
	// Modified:   $Date: 2008-05-17 15:36:57 -0500 (Sat, 17 May 2008) $
	//             $Author: karnesky $
	//             $Revision: 1136 $

	// Create a sitemap for better indexing by search engines.
	//   <http://www.sitemaps.org/>

	// TODO: - Include PDF links that are public
	//       - Include modified time...but how to handle timezone?
	//       - Possibly come up with smart ways to specify changefreq and priority
	//       - Do we wish to include links other than full record views and PDFs?
	//       - Handle cases where there are >50,000 references
	//       - Switch to XML generation library (?)
	//       - GZIP (?)
	//       - make/update a static file (?)


	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/include.inc.php'; // include common functions
	include 'initialize/ini.inc.php'; // include common variables

	global $tableRefs;
	global $databaseBaseURL;

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	$query = "SELECT serial,modified_date FROM ".$tableRefs .' WHERE serial RLIKE ".+"';

	header('Content-Type: application/xml');
	echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

	// (3) RUN QUERY, (4) DISPLAY EXPORT FILE OR HEADER & RESULTS

	// (3) RUN the query on the database through the connection:
	$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		echo "  <url>\n";
		echo "    <loc>".$databaseBaseURL."show.php?record=".$row['serial']."</loc>\n";
		echo "    <lastmod>".$row['modified_date']."</lastmod>\n";
		echo "  </url>\n";
	}
	echo "</urlset>";

	disconnectFromMySQLDatabase(); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'
	exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<

	// --------------------------------------------------------------------
?>
