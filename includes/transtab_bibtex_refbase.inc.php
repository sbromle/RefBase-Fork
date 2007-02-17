<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/transtab_bibtex_refbase.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    10-Aug-06, 21:00
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// Search & replace patterns for conversion from LaTeX/BibTeX markup & entities to refbase markup. Converts LaTeX fontshape markup (italic, bold) into
	// appropriate refbase commands, super- and subscript as well as greek letters in math mode get converted into the respective refbase commands.
	// You may need to adopt the LaTeX markup to suit your individual needs.
	// Notes: - search & replace patterns must be specified as perl-style regular expression and search patterns must include the leading & trailing slashes

	$transtab_bibtex_refbase = array(

		'/\\\\(?:text)?it\\{(.+?)\\}/i'                            => "_\\1_",
		'/\\\\(?:text)?bf\\{(.+?)\\}/i'                            => "**\\1**",
		'/((\\$\\^\\{.+?\\}\\$|\\\\textsuperscript\\{.+?\\}|\\{\\\\text(one|two|three)superior\\})+)/ie' => "bibtexSuperScriptToRefbase('\\1')", // function 'bibtexSuperScriptToRefbase()' will convert LaTeX/BibTeX superscript markup to appropriate refbase markup
		'/((\\$\\_\\{.+?\\}\\$|\\\\textsubscript\\{.+?\\})+)/ie'   => "bibtexSubScriptToRefbase('\\1')", // function 'bibtexSubScriptToRefbase()' will convert LaTeX/BibTeX subscript markup to appropriate refbase markup
		'/\\{\\\\textperthousand\\}/'                              => "[permil]",
		'/\\$\\\\infty\\$/'                                        => "[infinity]",
		'/\\$\\\\alpha\\$/'                                        => "[alpha]",
		'/\\$\\\\beta\\$/'                                         => "[beta]",
		'/\\$\\\\gamma\\$/'                                        => "[gamma]",
		'/\\$\\\\delta\\$/'                                        => "[delta]",
		'/\\$\\\\epsilon\\$/'                                      => "[epsilon]",
		'/\\$\\\\zeta\\$/'                                         => "[zeta]",
		'/\\$\\\\eta\\$/'                                          => "[eta]",
		'/\\$\\\\theta\\$/'                                        => "[theta]",
		'/\\$\\\\iota\\$/'                                         => "[iota]",
		'/\\$\\\\kappa\\$/'                                        => "[kappa]",
		'/\\$\\\\lambda\\$/'                                       => "[lambda]",
		'/\\$\\\\mu\\$/'                                           => "[mu]",
		'/\\$\\\\nu\\$/'                                           => "[nu]",
		'/\\$\\\\xi\\$/'                                           => "[xi]",
		'/\\$o\\$/'                                                => "[omicron]",
		'/\\$\\\\pi\\$/'                                           => "[pi]",
		'/\\$\\\\rho\\$/'                                          => "[rho]",
		'/\\$\\\\varsigma\\$/'                                     => "[sigmaf]",
		'/\\$\\\\sigma\\$/'                                        => "[sigma]",
		'/\\$\\\\tau\\$/'                                          => "[tau]",
		'/\\$\\\\upsilon\\$/'                                      => "[upsilon]",
		'/\\$\\\\phi\\$/'                                          => "[phi]",
		'/\\$\\\\chi\\$/'                                          => "[chi]",
		'/\\$\\\\psi\\$/'                                          => "[psi]",
		'/\\$\\\\omega\\$/'                                        => "[omega]",
		'/\\$A\\$/'                                                => "[Alpha]",
		'/\\$B\\$/'                                                => "[Beta]",
		'/\\$\\\\Gamma\\$/'                                        => "[Gamma]",
		'/\\$\\\\Delta\\$/'                                        => "[Delta]",
		'/\\$E\\$/'                                                => "[Epsilon]",
		'/\\$Z\\$/'                                                => "[Zeta]",
		'/\\$H\\$/'                                                => "[Eta]",
		'/\\$\\\\Theta\\$/'                                        => "[Theta]",
		'/\\$I\\$/'                                                => "[Iota]",
		'/\\$K\\$/'                                                => "[Kappa]",
		'/\\$\\\\Lambda\\$/'                                       => "[Lambda]",
		'/\\$M\\$/'                                                => "[Mu]",
		'/\\$N\\$/'                                                => "[Nu]",
		'/\\$\\\\Xi\\$/'                                           => "[Xi]",
		'/\\$O\\$/'                                                => "[Omicron]",
		'/\\$\\\\Pi\\$/'                                           => "[Pi]",
		'/\\$R\\$/'                                                => "[Rho]",
		'/\\$\\\\Sigma\\$/'                                        => "[Sigma]",
		'/\\$T\\$/'                                                => "[Tau]",
		'/\\$\\\\Upsilon\\$/'                                      => "[Upsilon]",
		'/\\$\\\\Phi\\$/'                                          => "[Phi]",
		'/\\$X\\$/'                                                => "[Chi]",
		'/\\$\\\\Psi\\$/'                                          => "[Psi]",
		'/\\$\\\\Omega\\$/'                                        => "[Omega]",
		'/^opt(?=(URL|LOCATION|NOTE|KEYWORDS)=)/mi'                => ""

	);


	$bibtexSuperScriptSearchReplaceActionsArray = array(

		"/\\$\\^\\{(.+?)\\}\\$/"              =>  '\\1',
		"/\\\\textsuperscript\\{(.+?)\\}/i"   =>  '\\1',
		"/\\{\\\\textonesuperior\\}/i"        =>  '1', // (superscript one)
		"/\\{\\\\texttwosuperior\\}/i"        =>  '2', // (superscript two)
		"/\\{\\\\textthreesuperior\\}/i"      =>  '3' // (superscript three)

	);


	$bibtexSubScriptSearchReplaceActionsArray = array(

		"/\\$\\_\\{(.+?)\\}\\$/"              =>  '\\1',
		"/\\\\textsubscript\\{(.+?)\\}/i"     =>  '\\1'

	);

	// --------------------------------------------------------------------

	// Converts LaTeX/BibTeX superscript markup to appropriate refbase markup:
	function bibtexSuperScriptToRefbase($sourceString)
	{
		global $bibtexSuperScriptSearchReplaceActionsArray;

		$sourceString = searchReplaceText($bibtexSuperScriptSearchReplaceActionsArray, $sourceString, true); // function 'searchReplaceText()' is defined in 'include.inc.php'

		return "[super:" . $sourceString . "]";
	}

	// --------------------------------------------------------------------

	// Converts LaTeX/BibTeX subscript markup to appropriate refbase markup:
	function bibtexSubScriptToRefbase($sourceString)
	{
		global $bibtexSubScriptSearchReplaceActionsArray;

		$sourceString = searchReplaceText($bibtexSubScriptSearchReplaceActionsArray, $sourceString, true); // function 'searchReplaceText()' is defined in 'include.inc.php'

		return "[sub:" . $sourceString . "]";
	}
?>
