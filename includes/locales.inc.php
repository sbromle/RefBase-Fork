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


	$locale = getUserLanguage(); // function 'getUserLanguage()' is defined in 'include.inc.php'

	include 'locales/core.php'; // include the locales
?>