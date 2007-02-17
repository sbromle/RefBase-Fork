<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/transtab_refbase_markdown.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    28-May-06, 18:24
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// Search & replace patterns for conversion from refbase markup to "Markdown" markup & entities. Markdown is a plain text formatting syntax
	// as well as a software tool that converts the plain text formatting back to HTML; see <http://daringfireball.net/projects/markdown/> for more info.
	// refbase fontshape markup (italic, bold) does not need any conversion since it's identical to (and was in fact modeled after) the Markdown syntax.
	// Super- and subscript gets converted into HTML commands while greek letters get converted into the respective HTML entity codes.
	// Search & replace patterns must be specified as perl-style regular expression and search patterns must include the leading & trailing slashes.

	$transtab_refbase_markdown = array(

	//	"/_(.+?)_/"            =>  "_\\1_",
	//	"/\\*\\*(.+?)\\*\\*/"  =>  "**\\1**",
		"/\\[super:(.+?)\\]/i" =>  "<sup>\\1</sup>",
		"/\\[sub:(.+?)\\]/i"   =>  "<sub>\\1</sub>",
		"/\\[permil\\]/"       =>  "&permil;",
		"/\\[infinity\\]/"     =>  "&infin;",
		"/\\[alpha\\]/"        =>  "&alpha;",
		"/\\[beta\\]/"         =>  "&beta;",
		"/\\[gamma\\]/"        =>  "&gamma;",
		"/\\[delta\\]/"        =>  "&delta;",
		"/\\[epsilon\\]/"      =>  "&epsilon;",
		"/\\[zeta\\]/"         =>  "&zeta;",
		"/\\[eta\\]/"          =>  "&eta;",
		"/\\[theta\\]/"        =>  "&theta;",
		"/\\[iota\\]/"         =>  "&iota;",
		"/\\[kappa\\]/"        =>  "&kappa;",
		"/\\[lambda\\]/"       =>  "&lambda;",
		"/\\[mu\\]/"           =>  "&mu;",
		"/\\[nu\\]/"           =>  "&nu;",
		"/\\[xi\\]/"           =>  "&xi;",
		"/\\[omicron\\]/"      =>  "&omicron;",
		"/\\[pi\\]/"           =>  "&pi;",
		"/\\[rho\\]/"          =>  "&rho;",
		"/\\[sigmaf\\]/"       =>  "&sigmaf;",
		"/\\[sigma\\]/"        =>  "&sigma;",
		"/\\[tau\\]/"          =>  "&tau;",
		"/\\[upsilon\\]/"      =>  "&upsilon;",
		"/\\[phi\\]/"          =>  "&phi;",
		"/\\[chi\\]/"          =>  "&chi;",
		"/\\[psi\\]/"          =>  "&psi;",
		"/\\[omega\\]/"        =>  "&omega;",
		"/\\[Alpha\\]/"        =>  "&Alpha;",
		"/\\[Beta\\]/"         =>  "&Beta;",
		"/\\[Gamma\\]/"        =>  "&Gamma;",
		"/\\[Delta\\]/"        =>  "&Delta;",
		"/\\[Epsilon\\]/"      =>  "&Epsilon;",
		"/\\[Zeta\\]/"         =>  "&Zeta;",
		"/\\[Eta\\]/"          =>  "&Eta;",
		"/\\[Theta\\]/"        =>  "&Theta;",
		"/\\[Iota\\]/"         =>  "&Iota;",
		"/\\[Kappa\\]/"        =>  "&Kappa;",
		"/\\[Lambda\\]/"       =>  "&Lambda;",
		"/\\[Mu\\]/"           =>  "&Mu;",
		"/\\[Nu\\]/"           =>  "&Nu;",
		"/\\[Xi\\]/"           =>  "&Xi;",
		"/\\[Omicron\\]/"      =>  "&Omicron;",
		"/\\[Pi\\]/"           =>  "&Pi;",
		"/\\[Rho\\]/"          =>  "&Rho;",
		"/\\[Sigma\\]/"        =>  "&Sigma;",
		"/\\[Tau\\]/"          =>  "&Tau;",
		"/\\[Upsilon\\]/"      =>  "&Upsilon;",
		"/\\[Phi\\]/"          =>  "&Phi;",
		"/\\[Chi\\]/"          =>  "&Chi;",
		"/\\[Psi\\]/"          =>  "&Psi;",
		"/\\[Omega\\]/"        =>  "&Omega;",
		"/ +- +/"              =>  " &ndash; ",
		"/–/"                  =>  "&ndash;"

	);

?>
