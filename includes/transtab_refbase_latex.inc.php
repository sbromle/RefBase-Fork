<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/transtab_refbase_latex.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    28-May-06, 17:01
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// Search & replace patterns for conversion from refbase markup to LaTeX markup & entities. Converts refbase fontshape markup (italic, bold) into
	// LaTeX commands of the 'textcomp' package, super- and subscript as well as greek letters get converted into the respective commands in math mode.
	// You may need to adopt the LaTeX markup to suit your individual needs.
	// Search & replace patterns must be specified as perl-style regular expression and search patterns must include the leading & trailing slashes.

	$transtab_refbase_latex = array(

		"/_(.+?)_/"            =>  '\\textit{\\1}', // or use '\\it{\\1}'
		"/\\*\\*(.+?)\\*\\*/"  =>  '\\textbf{\\1}', // or use '\\bf{\\1}'
		"/\\[super:(.+?)\\]/i" =>  '$^{\\1}$', // or use '\\textsuperscript{\\1}'
		"/\\[sub:(.+?)\\]/i"   =>  '$_{\\1}$', // or use '\\textsubscript{\\1}' if defined in your package
		"/\\[permil\\]/"       =>  '{\\textperthousand}',
		"/\\[infinity\\]/"     =>  '$\\infty$',
		"/\\[alpha\\]/"        =>  '$\\alpha$',
		"/\\[beta\\]/"         =>  '$\\beta$',
		"/\\[gamma\\]/"        =>  '$\\gamma$',
		"/\\[delta\\]/"        =>  '$\\delta$',
		"/\\[epsilon\\]/"      =>  '$\\epsilon$',
		"/\\[zeta\\]/"         =>  '$\\zeta$',
		"/\\[eta\\]/"          =>  '$\\eta$',
		"/\\[theta\\]/"        =>  '$\\theta$',
		"/\\[iota\\]/"         =>  '$\\iota$',
		"/\\[kappa\\]/"        =>  '$\\kappa$',
		"/\\[lambda\\]/"       =>  '$\\lambda$',
		"/\\[mu\\]/"           =>  '$\\mu$',
		"/\\[nu\\]/"           =>  '$\\nu$',
		"/\\[xi\\]/"           =>  '$\\xi$',
		"/\\[omicron\\]/"      =>  '$o$',
		"/\\[pi\\]/"           =>  '$\\pi$',
		"/\\[rho\\]/"          =>  '$\\rho$',
		"/\\[sigmaf\\]/"       =>  '$\\varsigma$',
		"/\\[sigma\\]/"        =>  '$\\sigma$',
		"/\\[tau\\]/"          =>  '$\\tau$',
		"/\\[upsilon\\]/"      =>  '$\\upsilon$',
		"/\\[phi\\]/"          =>  '$\\phi$',
		"/\\[chi\\]/"          =>  '$\\chi$',
		"/\\[psi\\]/"          =>  '$\\psi$',
		"/\\[omega\\]/"        =>  '$\\omega$',
		"/\\[Alpha\\]/"        =>  '$A$',
		"/\\[Beta\\]/"         =>  '$B$',
		"/\\[Gamma\\]/"        =>  '$\\Gamma$',
		"/\\[Delta\\]/"        =>  '$\\Delta$',
		"/\\[Epsilon\\]/"      =>  '$E$',
		"/\\[Zeta\\]/"         =>  '$Z$',
		"/\\[Eta\\]/"          =>  '$H$',
		"/\\[Theta\\]/"        =>  '$\\Theta$',
		"/\\[Iota\\]/"         =>  '$I$',
		"/\\[Kappa\\]/"        =>  '$K$',
		"/\\[Lambda\\]/"       =>  '$\\Lambda$',
		"/\\[Mu\\]/"           =>  '$M$',
		"/\\[Nu\\]/"           =>  '$N$',
		"/\\[Xi\\]/"           =>  '$\\Xi$',
		"/\\[Omicron\\]/"      =>  '$O$',
		"/\\[Pi\\]/"           =>  '$\\Pi$',
		"/\\[Rho\\]/"          =>  '$R$',
		"/\\[Sigma\\]/"        =>  '$\\Sigma$',
		"/\\[Tau\\]/"          =>  '$T$',
		"/\\[Upsilon\\]/"      =>  '$\\Upsilon$',
		"/\\[Phi\\]/"          =>  '$\\Phi$',
		"/\\[Chi\\]/"          =>  '$X$',
		"/\\[Psi\\]/"          =>  '$\\Psi$',
		"/\\[Omega\\]/"        =>  '$\\Omega$',
		"/([{}])/"             =>  '\\\\\\1', // escaping of curly brackets has to be done here (and not in 'transtab_*' files) so that conversion is only applied to field contents and doesn't mess with the generated LaTeX code
		"/\"(.+?)\"/"          =>  '{\\textquotedblleft}\\1{\\textquotedblright}',
		"/ +- +/"              =>  " -- ",
		"/–/"                  =>  '--'

	);

?>
