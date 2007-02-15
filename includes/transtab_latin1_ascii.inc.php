<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./includes/transtab_latin1_ascii.inc.php
	// Created:    24-Aug-05, 20:11
	// Modified:   25-Aug-05, 17:06

	// This is a transliteration table for a best-effort conversion from ISO-8859-1 to ASCII. It contains a list of substitution strings for 'ISO-8859-1 West European' characters,
	// comparable to the fallback notations that people use commonly in email and on typewriters to represent unavailable characters. Adopted from 'transtab' by Markus Kuhn
	// (transtab.utf v1.8 2000-10-12 11:01:28+01 mgk25 Exp); see <http://www.cl.cam.ac.uk/~mgk25/unicode.html> for more info about Unicode and transtab.

	$transtab_latin1_ascii = array(

		// APOSTROPHE
		"'" => "'",
		// <U0027> <U2019>

		// GRAVE ACCENT
		"`" => "'",
		// <U0060> <U201B>;<U2018>

		// NO-BREAK SPACE
		" " => " ",
		// <U00A0> <U0020>

		// INVERTED EXCLAMATION MARK
		"¡" => "!",
		// <U00A1> <U0021>

		// CENT SIGN
		"¢" => "c",
		// <U00A2> <U0063>

		// POUND SIGN
		"£" => "GBP",
		// <U00A3> "<U0047><U0042><U0050>"

		// YEN SIGN
		"¥" => "Y",
		// <U00A5> <U0059>

		// BROKEN BAR
		"¦" => "|",
		// <U00A6> <U007C>

		// SECTION SIGN
		"§" => "S",
		// <U00A7> <U0053>

		// DIAERESIS
		"¨" => "\"",
		// <U00A8> <U0022>

		// COPYRIGHT SIGN
		"©" => "(c)", // "c"
		// <U00A9> "<U0028><U0063><U0029>";<U0063>

		// FEMININE ORDINAL INDICATOR
		"ª" => "a",
		// <U00AA> <U0061>

		// LEFT-POINTING DOUBLE ANGLE QUOTATION MARK
		"«" => "<<",
		// <U00AB> "<U003C><U003C>"

		// NOT SIGN
		"¬" => "-",
		// <U00AC> <U002D>

		// SOFT HYPHEN
		"­" => "-",
		// <U00AD> <U002D>

		// REGISTERED SIGN
		"®" => "(R)",
		// <U00AE> "<U0028><U0052><U0029>"

		// MACRON
		"¯" => "-",
		// <U00AF> <U002D>

		// DEGREE SIGN
		"°" => " ",
		// <U00B0> <U0020>

		// PLUS-MINUS SIGN
		"±" => "+/-",
		// <U00B1> "<U002B><U002F><U002D>"

		// SUPERSCRIPT TWO
		"²" => "^2", // "2"
		// <U00B2> "<U005E><U0032>";<U0032>

		// SUPERSCRIPT THREE
		"³" => "^3", // "3"
		// <U00B3> "<U005E><U0033>";<U0033>

		// ACUTE ACCENT
		"´" => "'",
		// <U00B4> <U0027>

		// MICRO SIGN
		"µ" => "u",
		// <U00B5> <U03BC>;<U0075>

		// PILCROW SIGN
		"¶" => "P",
		// <U00B6> <U0050>

		// MIDDLE DOT
		"·" => ".",
		// <U00B7> <U002E>

		// CEDILLA
		"¸" => ",",
		// <U00B8> <U002C>

		// SUPERSCRIPT ONE
		"¹" => "^1", // "1"
		// <U00B9> "<U005E><U0031>";<U0031>

		// MASCULINE ORDINAL INDICATOR
		"º" => "o",
		// <U00BA> <U006F>

		// RIGHT-POINTING DOUBLE ANGLE QUOTATION MARK
		"»" => ">>",
		// <U00BB> "<U003E><U003E>"

		// VULGAR FRACTION ONE QUARTER
		"¼" => " 1/4",
		// <U00BC> "<U0020><U0031><U002F><U0034>"

		// VULGAR FRACTION ONE HALF
		"½" => " 1/2",
		// <U00BD> "<U0020><U0031><U002F><U0032>"

		// VULGAR FRACTION THREE QUARTERS
		"¾" => " 3/4",
		// <U00BE> "<U0020><U0033><U002F><U0034>"

		// INVERTED QUESTION MARK
		"¿" => "?",
		// <U00BF> <U003F>

		// LATIN CAPITAL LETTER A WITH GRAVE
		"À" => "A",
		// <U00C0> <U0041>

		// LATIN CAPITAL LETTER A WITH ACUTE
		"Á" => "A",
		// <U00C1> <U0041>

		// LATIN CAPITAL LETTER A WITH CIRCUMFLEX
		"Â" => "A",
		// <U00C2> <U0041>

		// LATIN CAPITAL LETTER A WITH TILDE
		"Ã" => "A",
		// <U00C3> <U0041>

		// LATIN CAPITAL LETTER A WITH DIAERESIS
		"Ä" => "Ae", // "A"
		// <U00C4> "<U0041><U0065>";<U0041>

		// LATIN CAPITAL LETTER A WITH RING ABOVE
		"Å" => "Aa", // "A"
		// <U00C5> "<U0041><U0061>";<U0041>

		// LATIN CAPITAL LETTER AE
		"Æ" => "AE", // "A"
		// <U00C6> "<U0041><U0045>";<U0041>

		// LATIN CAPITAL LETTER C WITH CEDILLA
		"Ç" => "C",
		// <U00C7> <U0043>

		// LATIN CAPITAL LETTER E WITH GRAVE
		"È" => "E",
		// <U00C8> <U0045>

		// LATIN CAPITAL LETTER E WITH ACUTE
		"É" => "E",
		// <U00C9> <U0045>

		// LATIN CAPITAL LETTER E WITH CIRCUMFLEX
		"Ê" => "E",
		// <U00CA> <U0045>

		// LATIN CAPITAL LETTER E WITH DIAERESIS
		"Ë" => "E",
		// <U00CB> <U0045>

		// LATIN CAPITAL LETTER I WITH GRAVE
		"Ì" => "I",
		// <U00CC> <U0049>

		// LATIN CAPITAL LETTER I WITH ACUTE
		"Í" => "I",
		// <U00CD> <U0049>

		// LATIN CAPITAL LETTER I WITH CIRCUMFLEX
		"Î" => "I",
		// <U00CE> <U0049>

		// LATIN CAPITAL LETTER I WITH DIAERESIS
		"Ï" => "I",
		// <U00CF> <U0049>

		// LATIN CAPITAL LETTER ETH
		"Ð" => "D",
		// <U00D0> <U0044>

		// LATIN CAPITAL LETTER N WITH TILDE
		"Ñ" => "N",
		// <U00D1> <U004E>

		// LATIN CAPITAL LETTER O WITH GRAVE
		"Ò" => "O",
		// <U00D2> <U004F>

		// LATIN CAPITAL LETTER O WITH ACUTE
		"Ó" => "O",
		// <U00D3> <U004F>

		// LATIN CAPITAL LETTER O WITH CIRCUMFLEX
		"Ô" => "O",
		// <U00D4> <U004F>

		// LATIN CAPITAL LETTER O WITH TILDE
		"Õ" => "O",
		// <U00D5> <U004F>

		// LATIN CAPITAL LETTER O WITH DIAERESIS
		"Ö" => "Oe", // "O"
		// <U00D6> "<U004F><U0065>";<U004F>

		// MULTIPLICATION SIGN
		"×" => "x",
		// <U00D7> <U0078>

		// LATIN CAPITAL LETTER O WITH STROKE
		"Ø" => "O",
		// <U00D8> <U004F>

		// LATIN CAPITAL LETTER U WITH GRAVE
		"Ù" => "U",
		// <U00D9> <U0055>

		// LATIN CAPITAL LETTER U WITH ACUTE
		"Ú" => "U",
		// <U00DA> <U0055>

		// LATIN CAPITAL LETTER U WITH CIRCUMFLEX
		"Û" => "U",
		// <U00DB> <U0055>

		// LATIN CAPITAL LETTER U WITH DIAERESIS
		"Ü" => "Ue", // "U"
		// <U00DC> "<U0055><U0065>";<U0055>

		// LATIN CAPITAL LETTER Y WITH ACUTE
		"Ý" => "Y",
		// <U00DD> <U0059>

		// LATIN CAPITAL LETTER THORN
		"Þ" => "Th",
		// <U00DE> "<U0054><U0068>"

		// LATIN SMALL LETTER SHARP S
		"ß" => "ss",
		// <U00DF> "<U0073><U0073>";<U03B2>

		// LATIN SMALL LETTER A WITH GRAVE
		"à" => "a",
		// <U00E0> <U0061>

		// LATIN SMALL LETTER A WITH ACUTE
		"á" => "a",
		// <U00E1> <U0061>

		// LATIN SMALL LETTER A WITH CIRCUMFLEX
		"â" => "a",
		// <U00E2> <U0061>

		// LATIN SMALL LETTER A WITH TILDE
		"ã" => "a",
		// <U00E3> <U0061>

		// LATIN SMALL LETTER A WITH DIAERESIS
		"ä" => "ae", // "a"
		// <U00E4> "<U0061><U0065>";<U0061>

		// LATIN SMALL LETTER A WITH RING ABOVE
		"å" => "aa", // "a"
		// <U00E5> "<U0061><U0061>";<U0061>

		// LATIN SMALL LETTER AE
		"æ" => "ae", // "a"
		// <U00E6> "<U0061><U0065>";<U0061>

		// LATIN SMALL LETTER C WITH CEDILLA
		"ç" => "c",
		// <U00E7> <U0063>

		// LATIN SMALL LETTER E WITH GRAVE
		"è" => "e",
		// <U00E8> <U0065>

		// LATIN SMALL LETTER E WITH ACUTE
		"é" => "e",
		// <U00E9> <U0065>

		// LATIN SMALL LETTER E WITH CIRCUMFLEX
		"ê" => "e",
		// <U00EA> <U0065>

		// LATIN SMALL LETTER E WITH DIAERESIS
		"ë" => "e",
		// <U00EB> <U0065>

		// LATIN SMALL LETTER I WITH GRAVE
		"ì" => "i",
		// <U00EC> <U0069>

		// LATIN SMALL LETTER I WITH ACUTE
		"í" => "i",
		// <U00ED> <U0069>

		// LATIN SMALL LETTER I WITH CIRCUMFLEX
		"î" => "i",
		// <U00EE> <U0069>

		// LATIN SMALL LETTER I WITH DIAERESIS
		"ï" => "i",
		// <U00EF> <U0069>

		// LATIN SMALL LETTER ETH
		"ð" => "d",
		// <U00F0> <U0064>

		// LATIN SMALL LETTER N WITH TILDE
		"ñ" => "n",
		// <U00F1> <U006E>

		// LATIN SMALL LETTER O WITH GRAVE
		"ò" => "o",
		// <U00F2> <U006F>

		// LATIN SMALL LETTER O WITH ACUTE
		"ó" => "o",
		// <U00F3> <U006F>

		// LATIN SMALL LETTER O WITH CIRCUMFLEX
		"ô" => "o",
		// <U00F4> <U006F>

		// LATIN SMALL LETTER O WITH TILDE
		"õ" => "o",
		// <U00F5> <U006F>

		// LATIN SMALL LETTER O WITH DIAERESIS
		"ö" => "oe", // "o"
		// <U00F6> "<U006F><U0065>";<U006F>

		// DIVISION SIGN
		"÷" => ":",
		// <U00F7> <U003A>

		// LATIN SMALL LETTER O WITH STROKE
		"ø" => "o",
		// <U00F8> <U006F>

		// LATIN SMALL LETTER U WITH GRAVE
		"ù" => "u",
		// <U00F9> <U0075>

		// LATIN SMALL LETTER U WITH ACUTE
		"ú" => "u",
		// <U00FA> <U0075>

		// LATIN SMALL LETTER U WITH CIRCUMFLEX
		"û" => "u",
		// <U00FB> <U0075>

		// LATIN SMALL LETTER U WITH DIAERESIS
		"ü" => "ue", // "u"
		// <U00FC> "<U0075><U0065>";<U0075>

		// LATIN SMALL LETTER Y WITH ACUTE
		"ý" => "y",
		// <U00FD> <U0079>

		// LATIN SMALL LETTER THORN
		"þ" => "th",
		// <U00FE> "<U0074><U0068>"

		// LATIN SMALL LETTER Y WITH DIAERESIS
		"ÿ" => "y"
		// <U00FF> <U0079>

	);

?>
