<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/transtab_refbase_unicode.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    02-Jun-06, 01:41
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// Search & replace patterns and functions for conversion from refbase markup to Unicode entities.
	// Search & replace patterns must be specified as perl-style regular expression and search patterns must include the leading & trailing slashes.

	global $patternModifiers; // defined in 'transtab_unicode_charset.inc.php' and 'transtab_latin1_charset.inc.php'

	$transtab_refbase_unicode = array(

	//	"/__(?!_)(.+?)__/"      =>  '\\1', // the pattern for underline (__...__) must come before the one for italic (_..._)
	//	"/_(.+?)_/"             =>  '\\1', // fontshape markup is currently NOT converted (uncomment to strip fontshape markup from exported text)
	//	"/\\*\\*(.+?)\\*\\*/"   =>  '\\1',
		"/\\[super:(.+?)\\]/ie" =>  "superScriptToUnicode('\\1')", // function 'superScriptToUnicode()' will convert superscript text to appropriate Unicode entities
		"/\\[sub:(.+?)\\]/ie"   =>  "subScriptToUnicode('\\1')", // function 'subScriptToUnicode()' will convert subscript text to appropriate Unicode entities
		"/\\[permil\\]/"        =>  '‰', // <U2030> (per mille sign)
		"/\\[infinity\\]/"      =>  '∞', // <U221E> (infinity)
		"/\\[alpha\\]/"         =>  'α',
		"/\\[beta\\]/"          =>  'β',
		"/\\[gamma\\]/"         =>  'γ',
		"/\\[delta\\]/"         =>  'δ',
		"/\\[epsilon\\]/"       =>  'ε',
		"/\\[zeta\\]/"          =>  'ζ',
		"/\\[eta\\]/"           =>  'η',
		"/\\[theta\\]/"         =>  'θ',
		"/\\[iota\\]/"          =>  'ι',
		"/\\[kappa\\]/"         =>  'κ',
		"/\\[lambda\\]/"        =>  'λ',
		"/\\[mu\\]/"            =>  'μ',
		"/\\[nu\\]/"            =>  'ν',
		"/\\[xi\\]/"            =>  'ξ',
		"/\\[omicron\\]/"       =>  'ο',
		"/\\[pi\\]/"            =>  'π',
		"/\\[rho\\]/"           =>  'ρ',
		"/\\[sigmaf\\]/"        =>  'ς',
		"/\\[sigma\\]/"         =>  'σ',
		"/\\[tau\\]/"           =>  'τ',
		"/\\[upsilon\\]/"       =>  'υ',
		"/\\[phi\\]/"           =>  'φ',
		"/\\[chi\\]/"           =>  'χ',
		"/\\[psi\\]/"           =>  'ψ',
		"/\\[omega\\]/"         =>  'ω',
		"/\\[Alpha\\]/"         =>  'Α',
		"/\\[Beta\\]/"          =>  'Β',
		"/\\[Gamma\\]/"         =>  'Γ',
		"/\\[Delta\\]/"         =>  'Δ',
		"/\\[Epsilon\\]/"       =>  'Ε',
		"/\\[Zeta\\]/"          =>  'Ζ',
		"/\\[Eta\\]/"           =>  'Η',
		"/\\[Theta\\]/"         =>  'Θ',
		"/\\[Iota\\]/"          =>  'Ι',
		"/\\[Kappa\\]/"         =>  'Κ',
		"/\\[Lambda\\]/"        =>  'Λ',
		"/\\[Mu\\]/"            =>  'Μ',
		"/\\[Nu\\]/"            =>  'Ν',
		"/\\[Xi\\]/"            =>  'Ξ',
		"/\\[Omicron\\]/"       =>  'Ο',
		"/\\[Pi\\]/"            =>  'Π',
		"/\\[Rho\\]/"           =>  'Ρ',
		"/\\[Sigma\\]/"         =>  'Σ',
		"/\\[Tau\\]/"           =>  'Τ',
		"/\\[Upsilon\\]/"       =>  'Υ',
		"/\\[Phi\\]/"           =>  'Φ',
		"/\\[Chi\\]/"           =>  'Χ',
		"/\\[Psi\\]/"           =>  'Ψ',
		"/\\[Omega\\]/"         =>  'Ω',
		"/\"(.+?)\"/"           =>  '“\\1”', // <U201C>...<U201D> (left and right double quotation marks)
		"/ +- +/"               =>  ' – ', // <U2013> (endash)
		"//$patternModifiers"  =>  '–' // <U2013> (endash)
		// Note that for UTF-8 based systems, '$patternModifiers' contains the "u" (PCRE_UTF8) pattern modifier which causes PHP/PCRE
		// to treat pattern strings as UTF-8 (otherwise this conversion pattern would garble UTF-8 characters such as "Ö")

	);


	$unicodeSuperScriptSearchReplaceActionsArray = array(

		"/1/"                   =>  '¹', // <U00B9> (superscript one)
		"/2/"                   =>  '²', // <U00B2> (superscript two)
		"/3/"                   =>  '³', // <U00B3> (superscript three)
		"/4/"                   =>  '⁴', // <U2074> (superscript four)
		"/5/"                   =>  '⁵', // <U2075> (superscript five)
		"/6/"                   =>  '⁶', // <U2076> (superscript six)
		"/7/"                   =>  '⁷', // <U2077> (superscript seven)
		"/8/"                   =>  '⁸', // <U2078> (superscript eight)
		"/9/"                   =>  '⁹', // <U2079> (superscript nine)
		"/0/"                   =>  '⁰', // <U2070> (superscript zero)
		"/\\+/"                 =>  '⁺', // <U207A> (superscript plus sign)
		"/-/"                   =>  '⁻', // <U207B> (superscript minus)
		"/=/"                   =>  '⁼', // <U207C> (superscript equals sign)
		"/\\(/"                 =>  '⁽', // <U207D> (superscript left parenthesis)
		"/\\)/"                 =>  '⁾', // <U207E> (superscript right parenthesis)
		"/n/"                   =>  'ⁿ', // <U207F> (superscript latin small letter n)
		"/([^¹²³⁴⁵⁶⁷⁸⁹⁰⁺⁻⁼⁽⁾ⁿ]+)/"   =>  '[super:\\1]' // keep superscript markup in place for any text that has no matching superscript entity in Unicode

	);


	$unicodeSubScriptSearchReplaceActionsArray = array(

		"/1/"                   =>  '₁', // <U2081> (subscript one)
		"/2/"                   =>  '₂', // <U2082> (subscript two)
		"/3/"                   =>  '₃', // <U2083> (subscript three)
		"/4/"                   =>  '₄', // <U2084> (subscript four)
		"/5/"                   =>  '₅', // <U2085> (subscript five)
		"/6/"                   =>  '₆', // <U2086> (subscript six)
		"/7/"                   =>  '₇', // <U2087> (subscript seven)
		"/8/"                   =>  '₈', // <U2088> (subscript eight)
		"/9/"                   =>  '₉', // <U2089> (subscript nine)
		"/0/"                   =>  '₀', // <U2080> (subscript zero)
		"/\\+/"                 =>  '₊', // <U208A> (subscript plus sign)
		"/-/"                   =>  '₋', // <U208B> (subscript minus)
		"/=/"                   =>  '₌', // <U208C> (subscript equals sign)
		"/\\(/"                 =>  '₍', // <U208D> (subscript left parenthesis)
		"/\\)/"                 =>  '₎', // <U208E> (subscript right parenthesis)
		"/([^₁₂₃₄₅₆₇₈₉₀₊₋₌₍₎]+)/"    =>  '[sub:\\1]' // keep subscript markup in place for any text that has no matching subscript entity in Unicode

	);

	// --------------------------------------------------------------------

	// Converts superscript text to appropriate Unicode entities:
	function superScriptToUnicode($sourceString)
	{
		global $unicodeSuperScriptSearchReplaceActionsArray;

		$sourceString = searchReplaceText($unicodeSuperScriptSearchReplaceActionsArray, $sourceString, true); // function 'searchReplaceText()' is defined in 'include.inc.php'

		return $sourceString;
	}

	// --------------------------------------------------------------------

	// Converts subscript text to appropriate Unicode entities:
	function subScriptToUnicode($sourceString)
	{
		global $unicodeSubScriptSearchReplaceActionsArray;

		$sourceString = searchReplaceText($unicodeSubScriptSearchReplaceActionsArray, $sourceString, true); // function 'searchReplaceText()' is defined in 'include.inc.php'

		return $sourceString;
	}
?>
