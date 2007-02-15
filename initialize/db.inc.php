<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./initialize/db.inc.php
	// Created:    15-Oct-02, 19:11
	// Modified:   29-Oct-05, 16:56

	// This file holds crucial
	// database access information.
	// Please read the notes below!

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/
	
	// NOTE: Edit the variables '$databaseName', '$username' and '$password' to suit your setup!
	//       (Although you'll be able to use the refbase package without modifying these variables,
	//        we highly recommend not to use the default values!)
	// CAUTION: To avoid security risks you must not permit any remote user to view this file!
	//          E.g., this can be done by adjusting the config file of your server ("httpd.conf"
	//          in case of the Apache web server) to disallow viewing of "*\.inc.php" files
	//          ("Deny from all"). Please see your web server documentation for further information.

	// --------------------------------------------------------------------

	// The host name of your MySQL installation:
	$hostName = "localhost"; // e.g.: "localhost"

	// The name of the MySQL database that you're planning to use with the
	// refbase package:
	// Note: - if there's no existing database with the specified name,
	//         the 'install.php' script will create it for you
	$databaseName = "literature"; // e.g.: "literature"

	// The name of the MySQL user that's going to be used with your MySQL
	// literature database:
	// Note: - this user should be a separate MySQL user (different from the
	//         user that has full administrative privileges like the root user!)
	//       - if there's no existing MySQL user with the specified name,
	//         the 'install.php' script will create this user for you
	$username = "litwww"; // e.g.: "litwww"

	// The password by which the above MySQL user will be granted access to
	// your MySQL literature database:
	$password = "%l1t3ratur3?"; // e.g.: "%l1t3ratur3?"

	// --------------------------------------------------------------------

	// NOTE: You shouldn't change anything below this line unless you know what you're doing!

	// The names of the MySQL database tables used by refbase:
	// If you need to put the refbase tables into an existing MySQL database, you may want to
	// rename the refbase tables to avoid any name conflicts. If you do so, you need to adopt
	// the table names here accordingly.
	//                      ->  in code comments referred to as table:
	$tableAuth = "auth"; // ->  'auth'
	$tableDeleted = "deleted"; // -> 'deleted'
	$tableDepends = "depends"; // -> 'depends'
	$tableFormats = "formats"; // -> 'formats'
	$tableLanguages = "languages"; // -> 'languages'
	$tableQueries = "queries"; // -> 'queries'
	$tableRefs = "refs"; // -> 'refs'
	$tableStyles = "styles"; // -> 'styles'
	$tableTypes = "types"; // -> 'types'
	$tableUserData = "user_data"; // -> 'user_data'
	$tableUserFormats = "user_formats"; // -> 'user_formats'
	$tableUserOptions = "user_options"; // -> 'user_options'
	$tableUserPermissions = "user_permissions"; // -> 'user_permissions'
	$tableUserStyles = "user_styles"; // -> 'user_styles'
	$tableUserTypes = "user_types"; // -> 'user_types'
	$tableUsers = "users"; // -> 'users'
?>
