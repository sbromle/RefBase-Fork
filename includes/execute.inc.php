<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/execute.inc.php
	// Repository: $HeadURL$
	// Author(s):  Richard Karnesky <mailto:karnesky@gmail.com> and
	//             Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    16-Dec-05, 18:00
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This file contains functions that deal with execution of shell commands and provides
	// fixes for 'exec()' on certain win32 systems (based on rivera at spamjoy dot unr dot edu's
	// 'wind_exec()' function <http://php.net/function.exec>).

	// Note: Since the 'exec()' function is used, some things may not work if
	//'safe_mode' is set to 'On' in your 'php.ini' file. If you need or want to
	// keep 'safe_mode=ON' then you'll need to put the programs within the
	// directory that's specified in 'safe_mode_exec_dir'.

	// --------------------------------------------------------------------

	// Import records using the bibutils program given in '$program'
	function importBibutils($sourceText, $program)
	{
		global $contentTypeCharset; // defined in 'ini.inc.php'

		// Get the absolute path for the bibutils package:
		// (function 'getExternalUtilityPath()' is defined in 'include.inc.php')
		$bibutilsPath = getExternalUtilityPath("bibutils");

		// Write the source data to a temporary file:
		$tempFile = writeToTempFile($sourceText);

		// Set input and output encoding:
		if ($contentTypeCharset != "UTF-8")
		{
			$inputEncodingArg = " -i iso8859_1";
			$outputEncodingArg = " -o iso8859_1";
		}
		else
		{
			$inputEncodingArg = " -i utf8";
			$outputEncodingArg = " -o utf8";
		}

		// Pass this temp file to the bibutils utility for conversion:
		$outputFile = convertBibutils($bibutilsPath, $tempFile, $program, $inputEncodingArg, $outputEncodingArg);
		unlink($tempFile);

		// Read the resulting output file and return the converted data:
		$resultString = readFromFile($outputFile);
		unlink($outputFile);

		return $resultString;
	}

	// --------------------------------------------------------------------

	// Export records using the bibutils program given in '$program'
	function exportBibutils($result, $program)
	{
		global $contentTypeCharset; // these variables are defined in 'ini.inc.php'
		global $convertExportDataToUTF8;

		// Get the absolute path for the bibutils package:
		// (function 'getExternalUtilityPath()' is defined in 'include.inc.php')
		$bibutilsPath = getExternalUtilityPath("bibutils");

		// Generate and serve a MODS XML file of ALL records:
		// (function 'modsCollection()' is defined in 'modsxml.inc.php')
		$recordCollection = modsCollection($result);

		// Write the MODS XML data to a temporary file:
		$tempFile = writeToTempFile($recordCollection);

		// Set input and output encoding:
		if (($convertExportDataToUTF8 == "no") AND ($contentTypeCharset != "UTF-8"))
		{
			$inputEncodingArg = " -i iso8859_1";
			$outputEncodingArg = " -o iso8859_1";
		}
		else
		{
			$inputEncodingArg = " -i utf8";
			$outputEncodingArg = " -o utf8";
		}

		// Pass this temp file to the bibutils utility for conversion:
		$outputFile = convertBibutils($bibutilsPath, $tempFile, $program, $inputEncodingArg, $outputEncodingArg);
		unlink($tempFile);

		// Read the resulting output file and return the converted data:
		$resultString = readFromFile($outputFile);
		unlink($outputFile);

		return $resultString;
	}

	// --------------------------------------------------------------------

	// Convert file contents using the bibutils program given in '$program'
	function convertBibutils($bibutilsPath, $tempFile, $program, $inputEncodingArg, $outputEncodingArg)
	{
		global $sessionTempDir; // defined in 'ini.inc.php'

		$outputFile = tempnam($sessionTempDir, "refbase-");
		$cmd = $bibutilsPath . $program . $inputEncodingArg . $outputEncodingArg . " " . $tempFile;
		execute($cmd, $outputFile);

		return $outputFile;
	}

	// --------------------------------------------------------------------

	// Execute shell command
	function execute($cmd, $outputFile)
	{
		if (getenv("OS") == "Windows_NT")
			executeWin32($cmd . " > " . $outputFile);
		else
		{
			exec($cmd, $output);
			arrayToFile($output, $outputFile);
		}
	}

	// --------------------------------------------------------------------

	// Execute shell command on win32 systems
	function executeWin32($cmd)
	{
		$cmdline = "cmd /C ". $cmd;

		// Make a new instance of the COM object
		$WshShell = new COM("WScript.Shell");

		// Make the command window but dont show it
		$oExec = $WshShell->Run($cmdline, 0, true);
	}

	// --------------------------------------------------------------------

	// Write data to a temporary file
	function writeToTempFile($sourceText)
	{
		global $sessionTempDir; // defined in 'ini.inc.php'

		$tempFile = tempnam($sessionTempDir, "refbase-");
		$tempFileHandle = fopen($tempFile, "w"); // open temp file with write permission
		fwrite($tempFileHandle, $sourceText); // save data to temp file
		fclose($tempFileHandle); // close temp file

		return $tempFile;
	}

	// --------------------------------------------------------------------

	// Get file contents
	function readFromFile($file)
	{
		$fileContents = file_get_contents($file);

		return $fileContents;
	}

	// --------------------------------------------------------------------

	// Write an array (as from $return argument in exec) to a file
	function arrayToFile($array, $outputFile)
	{
		return (stringToFile(implode("\n", $array), $outputFile));
	}

	function stringToFile($string, $outputFile)
	{
		$rc = false;
		do
		{
			if (!($f = fopen($outputFile, "wa+")))
			{
				$rc = 1;
				break;
			}
			if (!fwrite($f, $string))
			{
				$rc = 2;
				break;
			}
			$rc = true;
		}
		while (0);

		if ($f)
			fclose($f);

		return ($rc);
	}

	// --------------------------------------------------------------------

?>
