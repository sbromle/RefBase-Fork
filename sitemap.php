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
	// Repository: $HeadURL$
	// Author(s):  Richard Karnesky <mailto:rkarnesky@gmail.com>
	//
	// Created:    17-May-08, 15:50
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// Create a sitemap for better indexing by search engines.
	//   <http://www.sitemaps.org/>
	// You can either use this to dynamically generate sitemaps or can make a
	// crontab entry to wget it (saving execution time & allowing you to manually
	// gzip the file (if your webserver doesn't already do this for you).

	// TODO:
	//       - Handle cases where there are >50,000 URLs
	//       - Possibly come up with smart ways to specify changefreq and priority


	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/include.inc.php'; // include common functions
	include 'initialize/ini.inc.php'; // include common variables

	global $tableRefs;
	global $databaseBaseURL;
	global $fileVisibility;
	global $fileVisibilityException;
	global $filesBaseURL;

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	$query = "SELECT *,file FROM ".$tableRefs .' WHERE serial RLIKE ".+"';

	header('Content-Type: application/xml');
	echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
	echo "  <url>\n";
	echo "    <loc>". $databaseBaseURL."index.php</loc>\n";
	echo "    <changefreq>monthly</changefreq>\n";  // liberal, based on nucapt's history (not total records)
	echo "  </url>\n";

	// (3) RUN QUERY, (4) DISPLAY EXPORT FILE OR HEADER & RESULTS

	// (3) RUN the query on the database through the connection:
	$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		echo "  <url>\n";
		echo "    <loc>".$databaseBaseURL."show.php?record=".$row['serial']."</loc>\n";
		if (!empty($row['modified_date'])) {
			$datemod = "    <lastmod>"
			         . generateISO8601TimeStamp($row['modified_date'], $row['modified_time']) // function 'generateISO8601TimeStamp()' is defined in 'include.inc.php'
			         . "</lastmod>\n";
			echo $datemod;
		}
		else
			$datemod = "";
		echo "  </url>\n";
		if ($fileVisibility == "everyone" OR (!empty($fileVisibilityException) AND preg_match($fileVisibilityException[1], $row[$fileVisibilityException[0]]))) {
			if (!empty($row["file"])) { // if the 'file' field is NOT empty 
				if (!ereg("^(https?|ftp|file)://", $row["file"])) { // if the 'file' field does not contain a full URL (starting with "http://", "https://", "ftp://" or "file://")
					echo "  <url>\n";
					echo "    <loc>".$databaseBaseURL.$filesBaseURL.$row["file"]."</loc>\n";
					if (!empty($datemod))
						echo $datemod;
					echo "  </url>\n";
				}
			}
		}
	}
	echo "</urlset>";

	disconnectFromMySQLDatabase(); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'
	exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<

	// --------------------------------------------------------------------
?>
