<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/transtab_refbase_pdf.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    11-Jun-06, 01:13
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// Search & replace patterns for conversion from refbase markup to PDF markup & entities. Note that there's currently no conversion of emdashes
	// or markup for greek letters and super-/subscript (since I don't know how to print chars by code number or how to print Unicode chars directly).
	// Search & replace patterns must be specified as perl-style regular expression and search patterns must include the leading & trailing slashes.

	$transtab_refbase_pdf = array(

		"/__(?!_)(.+?)__/"      =>  "<u>\\1</u>", // the pattern for underline (__...__) must come before the one for italic (_..._)
		"/_(.+?)_/"             =>  "<i>\\1</i>", // html-style fontshape markup is recognized and converted by the pdf-php package
		"/\\*\\*(.+?)\\*\\*/"   =>  "<b>\\1</b>",
		"/\\[super:(.+?)\\]/ie" =>  "superScriptToLatin1('\\1')", // function 'superScriptToLatin1()' will convert superscript letters '1', '2' and '3' to appropriate latin1 entities
		"/\\[sub:(.+?)\\]/i"    =>  "\\1", // we remove markup which we cannot successfully convert to latin1 entities and replace it with an ASCII representation
		"/\\[permil\\]/"        =>  "per mille",
		"/\\[infinity\\]/"      =>  "infinity",
		"/\\[alpha\\]/"         =>  "alpha",
		"/\\[beta\\]/"          =>  "beta",
		"/\\[gamma\\]/"         =>  "gamma",
		"/\\[delta\\]/"         =>  "delta",
		"/\\[epsilon\\]/"       =>  "epsilon",
		"/\\[zeta\\]/"          =>  "zeta",
		"/\\[eta\\]/"           =>  "eta",
		"/\\[theta\\]/"         =>  "theta",
		"/\\[iota\\]/"          =>  "iota",
		"/\\[kappa\\]/"         =>  "kappa",
		"/\\[lambda\\]/"        =>  "lambda",
		"/\\[mu\\]/"            =>  "mu",
		"/\\[nu\\]/"            =>  "nu",
		"/\\[xi\\]/"            =>  "xi",
		"/\\[omicron\\]/"       =>  "omicron",
		"/\\[pi\\]/"            =>  "pi",
		"/\\[rho\\]/"           =>  "rho",
		"/\\[sigmaf\\]/"        =>  "sigmaf",
		"/\\[sigma\\]/"         =>  "sigma",
		"/\\[tau\\]/"           =>  "tau",
		"/\\[upsilon\\]/"       =>  "upsilon",
		"/\\[phi\\]/"           =>  "phi",
		"/\\[chi\\]/"           =>  "chi",
		"/\\[psi\\]/"           =>  "psi",
		"/\\[omega\\]/"         =>  "omega",
		"/\\[Alpha\\]/"         =>  "Alpha",
		"/\\[Beta\\]/"          =>  "Beta",
		"/\\[Gamma\\]/"         =>  "Gamma",
		"/\\[Delta\\]/"         =>  "Delta",
		"/\\[Epsilon\\]/"       =>  "Epsilon",
		"/\\[Zeta\\]/"          =>  "Zeta",
		"/\\[Eta\\]/"           =>  "Eta",
		"/\\[Theta\\]/"         =>  "Theta",
		"/\\[Iota\\]/"          =>  "Iota",
		"/\\[Kappa\\]/"         =>  "Kappa",
		"/\\[Lambda\\]/"        =>  "Lambda",
		"/\\[Mu\\]/"            =>  "Mu",
		"/\\[Nu\\]/"            =>  "Nu",
		"/\\[Xi\\]/"            =>  "Xi",
		"/\\[Omicron\\]/"       =>  "Omicron",
		"/\\[Pi\\]/"            =>  "Pi",
		"/\\[Rho\\]/"           =>  "Rho",
		"/\\[Sigma\\]/"         =>  "Sigma",
		"/\\[Tau\\]/"           =>  "Tau",
		"/\\[Upsilon\\]/"       =>  "Upsilon",
		"/\\[Phi\\]/"           =>  "Phi",
		"/\\[Chi\\]/"           =>  "Chi",
		"/\\[Psi\\]/"           =>  "Psi",
		"/\\[Omega\\]/"         =>  "Omega",
//		"/\"(.+?)\"/"           =>  "/quotedblleft\\1/quotedblright",
		"/ +- +/"               =>  " – " // endash

	);


	$latin1SuperScriptSearchReplaceActionsArray = array(

		"/1/"                   =>  '¹', // <U00B9> (superscript one)
		"/2/"                   =>  '²', // <U00B2> (superscript two)
		"/3/"                   =>  '³' // <U00B3> (superscript three)
//		"/([^¹²³]+)/"           =>  '[super:\\1]' // keep superscript markup in place for any text that has no matching superscript entity in Unicode

	);

	// --------------------------------------------------------------------

	// Converts superscript text to appropriate Unicode entities:
	function superScriptToLatin1($sourceString)
	{
		global $latin1SuperScriptSearchReplaceActionsArray;

		$sourceString = searchReplaceText($latin1SuperScriptSearchReplaceActionsArray, $sourceString, true); // function 'searchReplaceText()' is defined in 'include.inc.php'

		return $sourceString;
	}
?>
