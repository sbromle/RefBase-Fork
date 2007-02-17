<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/locales.inc.php
	// Repository: $HeadURL$
	// Author(s):  Jochen Wendebaum <mailto:wendebaum@users.sourceforge.net>
	//
	// Created:    12-Oct-04, 12:00
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is the locales include file.
	// It will read the locales depending on the personal settings of the currently
	// logged in user or the default language, if no personal information can be found.


	if (isset($_SESSION['loginEmail'])) // if a user is logged in
	{
		// get the preferred language for the current user:
		$userLanguage = getLanguages($loginUserID); // function 'getLanguages()' is defined in 'include.inc.php' and '$loginUserID' is provided as session variable
		$locale = $userLanguage[0];
	}
	else // NO user logged in
		$locale = $defaultLanguage; // use the default language (defined in 'ini.inc.php')

	include 'locales/core.php'; // include the locales
?>