<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/install.inc.php
	// Repository: $HeadURL$
	// Author(s):  Richard Karnesky <mailto:karnesky@gmail.com> and
	//             Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    16-Aug-06, 18:00
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This file contains functions
	// that are used when installing
	// or updating a refbase database.

	// --------------------------------------------------------------------

	// This function attempts to find a file (or program) on disk. It searches directories
	// given in '$fileLocations' for existing file/program names given in '$fileNames'.
	// Note that, currently, this function won't look into subdirectories.
	// 
	// Authors: Richard Karnesky <mailto:karnesky@gmail.com> and
	//          Matthias Steffens <mailto:refbase@extracts.de>
	function locateFile($fileLocations, $fileNames, $returnParentDirOnly)
	{
		$filePath = "";

		foreach ($fileLocations as $location)
		{
			foreach ($fileNames as $name)
			{
				if (file_exists("$location/$name"))
				{
					if ($returnParentDirOnly)
						$filePath = realpath($location) . "/";
					else
						$filePath = realpath("$location/$name");

					break 2;
				}
			}
		}

		return $filePath;
	}

	// --------------------------------------------------------------------

	// Connect to the MySQL database with admin permissions:
	// TODO: I18n
	function connectToMySQLDatabaseAsAdmin($adminUserName, $adminPassword)
	{
		global $hostName; // these variables are specified in 'db.inc.php'
		global $databaseName;

		global $connection;

		// Establish a *new* connection that has admin permissions
		// (1) OPEN the database connection:
		if (!($connection = @ mysql_connect($hostName, $adminUserName, $adminPassword)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to connect to the host:", "");

		// (2) SELECT the database:
		if (!(mysql_select_db($databaseName, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to connect to the database:", "");
	}

	// --------------------------------------------------------------------

	// Check for the presence of a value in a table,
	// and if it doesn't exist, add the given row to that same table:
	// 
	// Authors: Richard Karnesky <mailto:karnesky@gmail.com> and
	//          Matthias Steffens <mailto:refbase@extracts.de>
	function insertIfNotExists($keysArray, $table, $values, $userID = "")
	{
		global $connection;

		$selectClauseArray = array();
		$whereClauseArray = array();

		foreach ($keysArray as $keyColumn => $keyValue)
		{
			$selectClauseArray[] = $keyColumn;
			$whereClauseArray[] = $keyColumn . " = " . quote_smart($keyValue);
		}

		$query = "SELECT " . implode(", ", $selectClauseArray)
				. " FROM " . $table
				. " WHERE " . implode(" AND ", $whereClauseArray);

		if ($userID != "") // note that 'if (!empty($userID))' doesn't work here since '$userID = 0' would incorrectly be treated as 'empty'
			$query .= " AND user_id = " . $userID;

		$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'
	
		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound == 0)
		{
			$query = "INSERT INTO " . $table . " VALUES " . $values;
			$result = queryMySQLDatabase($query);

			return "true";
		}
		else
		{
			return "false";
		}
	}

	// --------------------------------------------------------------------

	// Check for the presence of a column in a table,
	// and if it doesn't exist, add the given column to that same table:
	// 
	// Author: Richard Karnesky <mailto:karnesky@gmail.com>
	function addColumnIfNotExists($column, $table, $properties)
	{
		global $connection;

		$present = false;

		$queryFields = "SHOW FIELDS FROM " . $table;
		$result = queryMySQLDatabase($queryFields); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		while ($row = @ mysql_fetch_array($result)) // for all fields found, check if any of their names matches the field name that we want to add
			if ($row["Field"] == $column)
				$present = true;

		if (!$present)
		{
			$query = "ALTER TABLE " . $table . " ADD COLUMN " . $column . " " . $properties;
			$result = queryMySQLDatabase($query);

			return "true";
		}
		else
		{
			return "false";
		}
	}

	// --------------------------------------------------------------------

	// Check for the presence of a table in the currently selected database,
	// and if it doesn't exist, add the given table to that same database:
	// This is similar to "CREATE TABLE IF NOT EXISTS ..." but allows us
	// to return appropriate feedback
	function addTableIfNotExists($table, $properties)
	{
		global $connection;

		$present = false;

		$queryFields = "SHOW TABLES";
		$result = queryMySQLDatabase($queryFields); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		while ($row = @ mysql_fetch_array($result)) // for all tables found, check if any of their names matches the table name that we want to add
			if ($row[0] == $table)
				$present = true;

		if (!$present)
		{
			$query = "CREATE TABLE " . $table . " " . $properties;
			$result = queryMySQLDatabase($query);

			return "true";
		}
		else
		{
			return "false";
		}
	}

	// --------------------------------------------------------------------

	// Show error in red:
	function fieldError($fieldName, $errors)
	{
		if (isset($errors[$fieldName]))
			echo returnMsg($errors[$fieldName], "warning", "strong", "", "\n\t\t\t", "\n\t\t\t<br>"); // function 'returnMsg()' is defined in 'include.inc.php'
	}

	// --------------------------------------------------------------------
?>
