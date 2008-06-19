<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/transtab_unicode_refbase.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    11-Jun-08, 13:00
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// Search & replace patterns and functions for conversion from Unicode entities to refbase markup.
	// Search & replace patterns must be specified as perl-style regular expression and search patterns must include the leading & trailing slashes.

	$transtab_unicode_refbase = array(

		'/‰|/'                =>  "[permil]", // ‰: <U2030> (per mille sign); : ?
		'/∞/'                  =>  "[infinity]", // <U221E> (infinity)
		'/α/'                  =>  "[alpha]",
		'/β/'                  =>  "[beta]",
		'/γ/'                  =>  "[gamma]",
		'/δ/'                  =>  "[delta]",
		'/ε/'                  =>  "[epsilon]",
		'/ζ/'                  =>  "[zeta]",
		'/η/'                  =>  "[eta]",
		'/θ/'                  =>  "[theta]",
		'/ι/'                  =>  "[iota]",
		'/κ/'                  =>  "[kappa]",
		'/λ/'                  =>  "[lambda]",
		'/μ/'                  =>  "[mu]",
		'/ν/'                  =>  "[nu]",
		'/ξ/'                  =>  "[xi]",
		'/ο/'                  =>  "[omicron]",
		'/π/'                  =>  "[pi]",
		'/ρ/'                  =>  "[rho]",
		'/ς/'                  =>  "[sigmaf]",
		'/σ/'                  =>  "[sigma]",
		'/τ/'                  =>  "[tau]",
		'/υ/'                  =>  "[upsilon]",
		'/φ/'                  =>  "[phi]",
		'/χ/'                  =>  "[chi]",
		'/ψ/'                  =>  "[psi]",
		'/ω/'                  =>  "[omega]",
		'/Α/'                  =>  "[Alpha]",
		'/Β/'                  =>  "[Beta]",
		'/Γ/'                  =>  "[Gamma]",
		'/Δ/'                  =>  "[Delta]",
		'/Ε/'                  =>  "[Epsilon]",
		'/Ζ/'                  =>  "[Zeta]",
		'/Η/'                  =>  "[Eta]",
		'/Θ/'                  =>  "[Theta]",
		'/Ι/'                  =>  "[Iota]",
		'/Κ/'                  =>  "[Kappa]",
		'/Λ/'                  =>  "[Lambda]",
		'/Μ/'                  =>  "[Mu]",
		'/Ν/'                  =>  "[Nu]",
		'/Ξ/'                  =>  "[Xi]",
		'/Ο/'                  =>  "[Omicron]",
		'/Π/'                  =>  "[Pi]",
		'/Ρ/'                  =>  "[Rho]",
		'/Σ/'                  =>  "[Sigma]",
		'/Τ/'                  =>  "[Tau]",
		'/Υ/'                  =>  "[Upsilon]",
		'/Φ/'                  =>  "[Phi]",
		'/Χ/'                  =>  "[Chi]",
		'/Ψ/'                  =>  "[Psi]",
		'/Ω/'                  =>  "[Omega]",
		"/((?:¹|²|³|⁴|⁵|⁶|⁷|⁸|⁹|⁰|⁺|⁻|⁼|⁽|⁾|ⁿ)+)/ie" =>  "unicodeSuperScriptToRefbase('\\1')", // function 'unicodeSuperScriptToRefbase()' will convert Unicode superscript entities to appropriate refbase superscript markup
		"/((?:₁|₂|₃|₄|₅|₆|₇|₈|₉|₀|₊|₋|₌|₍|₎)+)/ie"   =>  "unicodeSubScriptToRefbase('\\1')", // function 'unicodeSubScriptToRefbase()' will convert Unicode subscript entities to appropriate refbase subscript markup
		// Note that, when matching superscript or subscript Unicode characters, we cannot use the double-byte characters within character classes
		// (like [¹²³⁴⁵⁶⁷⁸⁹⁰⁺⁻⁼⁽⁾ⁿ] or ([₁₂₃₄₅₆₇₈₉₀₊₋₌₍₎]) since this may cause the single-byte parts of these characters to be matched and replaced as well!

	);


	$unicodeSuperScriptSearchReplaceActionsArray = array(

		'/¹/'                  =>  "1", // <U00B9> (superscript one)
		'/²/'                  =>  "2", // <U00B2> (superscript two)
		'/³/'                  =>  "3", // <U00B3> (superscript three)
		'/⁴/'                  =>  "4", // <U2074> (superscript four)
		'/⁵/'                  =>  "5", // <U2075> (superscript five)
		'/⁶/'                  =>  "6", // <U2076> (superscript six)
		'/⁷/'                  =>  "7", // <U2077> (superscript seven)
		'/⁸/'                  =>  "8", // <U2078> (superscript eight)
		'/⁹/'                  =>  "9", // <U2079> (superscript nine)
		'/⁰/'                  =>  "0", // <U2070> (superscript zero)
		'/⁺/'                  =>  "+", // <U207A> (superscript plus sign)
		'/⁻/'                  =>  "-", // <U207B> (superscript minus)
		'/⁼/'                  =>  "=", // <U207C> (superscript equals sign)
		'/⁽/'                  =>  "(", // <U207D> (superscript left parenthesis)
		'/⁾/'                  =>  ")", // <U207E> (superscript right parenthesis)
		'/ⁿ/'                  =>  "n", // <U207F> (superscript latin small letter n)

	);


	$unicodeSubScriptSearchReplaceActionsArray = array(

		'/₁/'                  =>  "1", // <U2081> (subscript one)
		'/₂/'                  =>  "2", // <U2082> (subscript two)
		'/₃/'                  =>  "3", // <U2083> (subscript three)
		'/₄/'                  =>  "4", // <U2084> (subscript four)
		'/₅/'                  =>  "5", // <U2085> (subscript five)
		'/₆/'                  =>  "6", // <U2086> (subscript six)
		'/₇/'                  =>  "7", // <U2087> (subscript seven)
		'/₈/'                  =>  "8", // <U2088> (subscript eight)
		'/₉/'                  =>  "9", // <U2089> (subscript nine)
		'/₀/'                  =>  "0", // <U2080> (subscript zero)
		'/₊/'                  =>  "+", // <U208A> (subscript plus sign)
		'/₋/'                  =>  "-", // <U208B> (subscript minus)
		'/₌/'                  =>  "=", // <U208C> (subscript equals sign)
		'/₍/'                  =>  "(", // <U208D> (subscript left parenthesis)
		'/₎/'                  =>  ")", // <U208E> (subscript right parenthesis)

	);

	// --------------------------------------------------------------------

	// Converts Unicode superscript entities to appropriate refbase superscript markup:
	function unicodeSuperScriptToRefbase($sourceString)
	{
		global $unicodeSuperScriptSearchReplaceActionsArray;

		$sourceString = searchReplaceText($unicodeSuperScriptSearchReplaceActionsArray, $sourceString, true); // function 'searchReplaceText()' is defined in 'include.inc.php'

		return "[super:" . $sourceString . "]";
	}

	// --------------------------------------------------------------------

	// Converts Unicode subscript entities to appropriate refbase subscript markup:
	function unicodeSubScriptToRefbase($sourceString)
	{
		global $unicodeSubScriptSearchReplaceActionsArray;

		$sourceString = searchReplaceText($unicodeSubScriptSearchReplaceActionsArray, $sourceString, true); // function 'searchReplaceText()' is defined in 'include.inc.php'

		return "[sub:" . $sourceString . "]";
	}
?>
