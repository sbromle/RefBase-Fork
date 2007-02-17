<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/transtab_latex_latin1.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    10-Aug-06, 23:55
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is a translation table for best-effort conversion from LaTeX to ISO-8859-1 (Latin1) entities. It contains a comprehensive list of substitution strings for LaTeX characters,
	// which are used with the 'T1' font encoding. Uses commands from the 'textcomp' package. Latin1 characters that can't be matched uniquely are commented out. LaTeX markup which has
	// no equivalents in the ISO-8859-1 character set will be replaced with its closest ASCII representations. Adopted from 'transtab' by Markus Kuhn
	// (transtab.utf v1.8 2000-10-12 11:01:28+01 mgk25 Exp); see <http://www.cl.cam.ac.uk/~mgk25/unicode.html> for more info about Unicode and transtab.

	$transtab_latex_latin1 = array(

		// NUMBER SIGN
		'\\$\\\\#\\$' => "#",
		// <U0023> <U0023>

		// PERCENT SIGN
		"\\\\%" => "%",
		// <U0025> <U0025>

		// AMPERSAND
		"\\\\&" => "&",
		// <U0026> <U0026>

		// APOSTROPHE
		"\\{\\\\textquoteright\\}" => "'",
		// <U2019> <U0027>

		// GRAVE ACCENT
		"\\{\\\\textquoteleft\\}" => "`",
		// <U201B>;<U2018> <U0060>

		// NO-BREAK SPACE
		"(?<!\\\\)~" => " ",
		// <U007E> <U00A0>

		// INVERTED EXCLAMATION MARK
		"\\{\\\\textexclamdown\\}" => "¡",
		// <U0021> <U00A1>

		// CENT SIGN
		"\\{\\\\textcent\\}" => "¢",
		// <U0063> <U00A2>

		// POUND SIGN
		"\\{\\\\textsterling\\}" => "£",
		// "<U0047><U0042><U0050>" <U00A3>

		// YEN SIGN
		"\\{\\\\textyen\\}" => "¥",
		// <U0059> <U00A5>

		// BROKEN BAR
		"\\{\\\\textbrokenbar\\}" => "¦",
		// <U007C> <U00A6>

		// SECTION SIGN
		"\\{\\\\textsection\\}" => "§",
		// <U0053> <U00A7>

		// DIAERESIS
		"\\{\\\\textasciidieresis\\}" => "¨",
		// <U0022> <U00A8>

		// COPYRIGHT SIGN
		"\\{\\\\textcopyright\\}" => "©",
		// "<U0028><U0063><U0029>";<U0063> <U00A9>

		// FEMININE ORDINAL INDICATOR
		"\\{\\\\textordfeminine\\}" => "ª",
		// <U0061> <U00AA>

		// LEFT-POINTING DOUBLE ANGLE QUOTATION MARK
		"\\{\\\\guillemotleft\\}" => "«",
		// "<U003C><U003C>" <U00AB>

		// NOT SIGN
		"\\{\\\\textlnot\\}" => "¬",
		// <U002D> <U00AC>

		// SOFT HYPHEN
//		"-" => "­", // correct?
		// <U002D> <U00AD>

		// REGISTERED SIGN
		"\\{\\\\textregistered\\}" => "®",
		// "<U0028><U0052><U0029>" <U00AE>

		// MACRON
		"\\{\\\\textasciimacron\\}" => "¯",
		// <U002D> <U00AF>

		// DEGREE SIGN
		"\\{\\\\textdegree\\}" => "°",
		// <U0020> <U00B0>

		// PLUS-MINUS SIGN
		"\\{\\\\textpm\\}" => "±",
		// "<U002B><U002F><U002D>" <U00B1>

		// SUPERSCRIPT TWO
		"\\{\\\\texttwosuperior\\}" => "²", // "[super:2]"
		// "<U005E><U0032>";<U0032> <U00B2>

		// SUPERSCRIPT THREE
		"\\{\\\\textthreesuperior\\}" => "³", // "[super:3]"
		// "<U005E><U0033>";<U0033> <U00B3>

		// ACUTE ACCENT
		"\\{\\\\textasciiacute\\}" => "´",
		// <U0027> <U00B4>

		// MICRO SIGN
		"\\{\\\\textmu\\}" => "µ",
		// <U03BC>;<U0075> <U00B5>

		// PILCROW SIGN
		"\\{\\\\textparagraph\\}" => "¶",
		// <U0050> <U00B6>

		// MIDDLE DOT
		"\\{\\\\textperiodcentered\\}" => "·",
		// <U002E> <U00B7>

		// CEDILLA
		"\\{\\\\c\\\\ \\}" => "¸",
		// <U002C> <U00B8>

		// SUPERSCRIPT ONE
		"\\{\\\\textonesuperior\\}" => "¹", // "[super:1]"
		// "<U005E><U0031>";<U0031> <U00B9>

		// MASCULINE ORDINAL INDICATOR
		"\\{\\\\textordmasculine\\}" => "º",
		// <U006F> <U00BA>

		// RIGHT-POINTING DOUBLE ANGLE QUOTATION MARK
		"\\{\\\\guillemotright\\}" => "»",
		// "<U003E><U003E>" <U00BB>

		// VULGAR FRACTION ONE QUARTER
		"\\{\\\\textonequarter\\}" => "¼",
		// "<U0020><U0031><U002F><U0034>" <U00BC>

		// VULGAR FRACTION ONE HALF
		"\\{\\\\textonehalf\\}" => "½",
		// "<U0020><U0031><U002F><U0032>" <U00BD>

		// VULGAR FRACTION THREE QUARTERS
		"\\{\\\\textthreequarters\\}" => "¾",
		// "<U0020><U0033><U002F><U0034>" <U00BE>

		// INVERTED QUESTION MARK
		"\\{\\\\textquestiondown\\}" => "¿",
		// <U003F> <U00BF>

		// LATIN CAPITAL LETTER A WITH GRAVE
		"\\{\\\\`A\\}" => "À", // \symbol{"C0}
		// <U0041> <U00C0>

		// LATIN CAPITAL LETTER A WITH ACUTE
		"\\{\\\\'A\\}" => "Á", // \symbol{"C1}
		// <U0041> <U00C1>

		// LATIN CAPITAL LETTER A WITH CIRCUMFLEX
		"\\{\\\\\\^A\\}" => "Â", // \symbol{"C2}
		// <U0041> <U00C2>

		// LATIN CAPITAL LETTER A WITH TILDE
		"\\{\\\\~A\\}" => "Ã", // \symbol{"C3}
		// <U0041> <U00C3>

		// LATIN CAPITAL LETTER A WITH DIAERESIS
		"\\{\\\\\"A\\}" => "Ä", // \symbol{"C4}
		// "<U0041><U0065>";<U0041> <U00C4>

		// LATIN CAPITAL LETTER A WITH RING ABOVE
		"\\{\\\\r A\\}" => "Å", // "\\\\AA" // \symbol{"C5}
		// "<U0041><U0061>";<U0041> <U00C5>

		// LATIN CAPITAL LETTER AE
		"\\{\\\\AE\\}" => "Æ", // \symbol{"C6}
		// "<U0041><U0045>";<U0041> <U00C6>

		// LATIN CAPITAL LETTER C WITH CEDILLA
		"\\{\\\\c C\\}" => "Ç", // \symbol{"C7}
		// <U0043> <U00C7>

		// LATIN CAPITAL LETTER E WITH GRAVE
		"\\{\\\\`E\\}" => "È", // \symbol{"C8}
		// <U0045> <U00C8>

		// LATIN CAPITAL LETTER E WITH ACUTE
		"\\{\\\\'E\\}" => "É", // \symbol{"C9}
		// <U0045> <U00C9>

		// LATIN CAPITAL LETTER E WITH CIRCUMFLEX
		"\\{\\\\\\^E\\}" => "Ê", // \symbol{"CA}
		// <U0045> <U00CA>

		// LATIN CAPITAL LETTER E WITH DIAERESIS
		"\\{\\\\\"E\\}" => "Ë", // \symbol{"CB}
		// <U0045> <U00CB>

		// LATIN CAPITAL LETTER I WITH GRAVE
		"\\{\\\\`I\\}" => "Ì", // \symbol{"CC}
		// <U0049> <U00CC>

		// LATIN CAPITAL LETTER I WITH ACUTE
		"\\{\\\\'I\\}" => "Í", // \symbol{"CD}
		// <U0049> <U00CD>

		// LATIN CAPITAL LETTER I WITH CIRCUMFLEX
		"\\{\\\\\\^I\\}" => "Î", // \symbol{"CE}
		// <U0049> <U00CE>

		// LATIN CAPITAL LETTER I WITH DIAERESIS
		"\\{\\\\\"I\\}" => "Ï", // \symbol{"CF}
		// <U0049> <U00CF>

		// LATIN CAPITAL LETTER ETH
		"\\{\\\\DH\\}" => "Ð", // \symbol{"D0}
		// <U0044> <U00D0>

		// LATIN CAPITAL LETTER N WITH TILDE
		"\\{\\\\~N\\}" => "Ñ", // \symbol{"D1}
		// <U004E> <U00D1>

		// LATIN CAPITAL LETTER O WITH GRAVE
		"\\{\\\\`O\\}" => "Ò", // \symbol{"D2}
		// <U004F> <U00D2>

		// LATIN CAPITAL LETTER O WITH ACUTE
		"\\{\\\\'O\\}" => "Ó", // \symbol{"D3}
		// <U004F> <U00D3>

		// LATIN CAPITAL LETTER O WITH CIRCUMFLEX
		"\\{\\\\\\^O\\}" => "Ô", // \symbol{"D4}
		// <U004F> <U00D4>

		// LATIN CAPITAL LETTER O WITH TILDE
		"\\{\\\\~O\\}" => "Õ", // \symbol{"D5}
		// <U004F> <U00D5>

		// LATIN CAPITAL LETTER O WITH DIAERESIS
		"\\{\\\\\"O\\}" => "Ö", // \symbol{"D6}
		// "<U004F><U0065>";<U004F> <U00D6>

		// MULTIPLICATION SIGN
		"\\{\\\\texttimes\\}" => "×", // \symbol{"D7}
		// <U0078> <U00D7>

		// LATIN CAPITAL LETTER O WITH STROKE
		"\\{\\\\O\\}" => "Ø", // \symbol{"D8}
		// <U004F> <U00D8>

		// LATIN CAPITAL LETTER U WITH GRAVE
		"\\{\\\\`U\\}" => "Ù", // \symbol{"D9}
		// <U0055> <U00D9>

		// LATIN CAPITAL LETTER U WITH ACUTE
		"\\{\\\\'U\\}" => "Ú", // \symbol{"DA}
		// <U0055> <U00DA>

		// LATIN CAPITAL LETTER U WITH CIRCUMFLEX
		"\\{\\\\\\^U\\}" => "Û", // \symbol{"DB}
		// <U0055> <U00DB>

		// LATIN CAPITAL LETTER U WITH DIAERESIS
		"\\{\\\\\"U\\}" => "Ü", // \symbol{"DC}
		// "<U0055><U0065>";<U0055> <U00DC>

		// LATIN CAPITAL LETTER Y WITH ACUTE
		"\\{\\\\'Y\\}" => "Ý", // \symbol{"DD}
		// <U0059> <U00DD>

		// LATIN CAPITAL LETTER THORN
		"\\{\\\\TH\\}" => "Þ", // \symbol{"DE}
		// "<U0054><U0068>" <U00DE>

		// LATIN SMALL LETTER SHARP S
		"\\{\\\\ss\\}" => "ß", // \symbol{"DF}
		// "<U0073><U0073>";<U03B2> <U00DF>

		// LATIN SMALL LETTER A WITH GRAVE
		"\\{\\\\`a\\}" => "à", // \symbol{"E0}
		// <U0061> <U00E0>

		// LATIN SMALL LETTER A WITH ACUTE
		"\\{\\\\'a\\}" => "á", // \symbol{"E1}
		// <U0061> <U00E1>

		// LATIN SMALL LETTER A WITH CIRCUMFLEX
		"\\{\\\\\\^a\\}" => "â", // \symbol{"E2}
		// <U0061> <U00E2>

		// LATIN SMALL LETTER A WITH TILDE
		"\\{\\\\~a\\}" => "ã", // \symbol{"E3}
		// <U0061> <U00E3>

		// LATIN SMALL LETTER A WITH DIAERESIS
		"\\{\\\\\"a\\}" => "ä", // \symbol{"E4}
		// "<U0061><U0065>";<U0061> <U00E4>

		// LATIN SMALL LETTER A WITH RING ABOVE
		"\\{\\\\r a\\}" => "å", // "\\\\aa" // \symbol{"E5}
		// "<U0061><U0061>";<U0061> <U00E5>

		// LATIN SMALL LETTER AE
		"\\{\\\\ae\\}" => "æ", // \symbol{"E6}
		// "<U0061><U0065>";<U0061> <U00E6>

		// LATIN SMALL LETTER C WITH CEDILLA
		"\\{\\\\c c\\}" => "ç", // \symbol{"E7}
		// <U0063> <U00E7>

		// LATIN SMALL LETTER E WITH GRAVE
		"\\{\\\\`e\\}" => "è", // \symbol{"E8}
		// <U0065> <U00E8>

		// LATIN SMALL LETTER E WITH ACUTE
		"\\{\\\\'e\\}" => "é", // \symbol{"E9}
		// <U0065> <U00E9>

		// LATIN SMALL LETTER E WITH CIRCUMFLEX
		"\\{\\\\\\^e\\}" => "ê", // \symbol{"EA}
		// <U0065> <U00EA>

		// LATIN SMALL LETTER E WITH DIAERESIS
		"\\{\\\\\"e\\}" => "ë", // \symbol{"EB}
		// <U0065> <U00EB>

		// LATIN SMALL LETTER I WITH GRAVE
		"\\{\\\\`\\\\i\\}" => "ì", // \symbol{"EC}
		// <U0069> <U00EC>

		// LATIN SMALL LETTER I WITH ACUTE
		"\\{\\\\'\\\\i\\}" => "í", // \symbol{"ED}
		// <U0069> <U00ED>

		// LATIN SMALL LETTER I WITH CIRCUMFLEX
		"\\{\\\\\\^\\\\i\\}" => "î", // \symbol{"EE}
		// <U0069> <U00EE>

		// LATIN SMALL LETTER I WITH DIAERESIS
		"\\{\\\\\"\\\\i\\}" => "ï", // \symbol{"EF}
		// <U0069> <U00EF>

		// LATIN SMALL LETTER ETH
		"\\{\\\\dh\\}" => "ð", // \symbol{"F0}
		// <U0064> <U00F0>

		// LATIN SMALL LETTER N WITH TILDE
		"\\{\\\\~n\\}" => "ñ", // \symbol{"F1}
		// <U006E> <U00F1>

		// LATIN SMALL LETTER O WITH GRAVE
		"\\{\\\\`o\\}" => "ò", // \symbol{"F2}
		// <U006F> <U00F2>

		// LATIN SMALL LETTER O WITH ACUTE
		"\\{\\\\'o\\}" => "ó", // \symbol{"F3}
		// <U006F> <U00F3>

		// LATIN SMALL LETTER O WITH CIRCUMFLEX
		"\\{\\\\\\^o\\}" => "ô", // \symbol{"F4}
		// <U006F> <U00F4>

		// LATIN SMALL LETTER O WITH TILDE
		"\\{\\\\~o\\}" => "õ", // \symbol{"F5}
		// <U006F> <U00F5>

		// LATIN SMALL LETTER O WITH DIAERESIS
		"\\{\\\\\"o\\}" => "ö", // \symbol{"F6}
		// "<U006F><U0065>";<U006F> <U00F6>

		// DIVISION SIGN
		"\\{\\\\textdiv\\}" => "÷", // \symbol{"F7}
		// <U003A> <U00F7>

		// LATIN SMALL LETTER O WITH STROKE
		"\\{\\\\o\\}" => "ø", // \symbol{"F8}
		// <U006F> <U00F8>

		// LATIN SMALL LETTER U WITH GRAVE
		"\\{\\\\`u\\}" => "ù", // \symbol{"F9}
		// <U0075> <U00F9>

		// LATIN SMALL LETTER U WITH ACUTE
		"\\{\\\\'u\\}" => "ú", // \symbol{"FA}
		// <U0075> <U00FA>

		// LATIN SMALL LETTER U WITH CIRCUMFLEX
		"\\{\\\\\\^u\\}" => "û", // \symbol{"FB}
		// <U0075> <U00FB>

		// LATIN SMALL LETTER U WITH DIAERESIS
		"\\{\\\\\"u\\}" => "ü", // \symbol{"FC}
		// "<U0075><U0065>";<U0075> <U00FC>

		// LATIN SMALL LETTER Y WITH ACUTE
		"\\{\\\\'y\\}" => "ý", // \symbol{"FD}
		// <U0079> <U00FD>

		// LATIN SMALL LETTER THORN
		"\\{\\\\th\\}" => "þ", // \symbol{"FE}
		// "<U0074><U0068>" <U00FE>

		// LATIN SMALL LETTER Y WITH DIAERESIS
		"\\{\\\\\"y\\}" => "ÿ", // \symbol{"FF}
		// <U0079> <U00FF>

		// Note: AFAIK, the LaTeX markup below has no equivalents in the ISO-8859-1 (Latin1) character set, --------------------------------------------------
		//       therefore we'll replace this LaTeX markup with its closest ASCII representations.

		// LATIN CAPITAL LETTER A WITH BREVE
		"\\{\\\\u A\\}" => "A",
		// <U0041> <U0102>

		// LATIN SMALL LETTER A WITH BREVE
		"\\{\\\\u a\\}" => "a",
		// <U0061> <U0103>

		// LATIN CAPITAL LETTER A WITH OGONEK
		"\\{\\\\k A\\}" => "A",
		// <U0041> <U0104>

		// LATIN SMALL LETTER A WITH OGONEK
		"\\{\\\\k a\\}" => "a",
		// <U0061> <U0105>

		// LATIN CAPITAL LETTER C WITH ACUTE
		"\\{\\\\'C\\}" => "C",
		// <U0043> <U0106>

		// LATIN SMALL LETTER C WITH ACUTE
		"\\{\\\\'c\\}" => "c",
		// <U0063> <U0107>

		// LATIN CAPITAL LETTER C WITH CARON
		"\\{\\\\v C\\}" => "C",
		// <U0043> <U010C>

		// LATIN SMALL LETTER C WITH CARON
		"\\{\\\\v c\\}" => "c",
		// <U0063> <U010D>

		// LATIN CAPITAL LETTER D WITH CARON
		"\\{\\\\v D\\}" => "D",
		// <U0044> <U010E>

		// LATIN SMALL LETTER D WITH CARON
		"\\{\\\\v d\\}" => "d",
		// <U0064> <U010F>

		// LATIN CAPITAL LETTER D WITH STROKE
		"\\{\\\\DJ\\}" => "D",
		// <U0044> <U0110>

		// LATIN SMALL LETTER D WITH STROKE
		"\\{\\\\dj\\}" => "d",
		// <U0064> <U0111>

		// LATIN CAPITAL LETTER E WITH OGONEK
		"\\{\\\\k E\\}" => "E",
		// <U0045> <U0118>

		// LATIN SMALL LETTER E WITH OGONEK
		"\\{\\\\k e\\}" => "e",
		// <U0065> <U0119>

		// LATIN CAPITAL LETTER E WITH CARON
		"\\{\\\\v E\\}" => "E",
		// <U0045> <U011A>

		// LATIN SMALL LETTER E WITH CARON
		"\\{\\\\v e\\}" => "e",
		// <U0065> <U011B>

		// LATIN CAPITAL LETTER G WITH BREVE
		"\\{\\\\u G\\}" => "G",
		// <U0047> <U011E>

		// LATIN SMALL LETTER G WITH BREVE
		"\\{\\\\u g\\}" => "g",
		// <U0067> <U011F>

		// LATIN CAPITAL LETTER I WITH DOT ABOVE
		"\\{\\\\.I\\}" => "I",
		// <U0049> <U0130>

		// LATIN SMALL LETTER DOTLESS I
		"\\{\\\\i\\}" => "i",
		// <U0069> <U0131>

		// LATIN CAPITAL LETTER L WITH ACUTE
		"\\{\\\\'L\\}" => "L",
		// <U004C> <U0139>

		// LATIN SMALL LETTER L WITH ACUTE
		"\\{\\\\'l\\}" => "l",
		// <U006C> <U013A>

		// LATIN CAPITAL LETTER L WITH CARON
		"\\{\\\\v L\\}" => "L",
		// <U004C> <U013D>

		// LATIN SMALL LETTER L WITH CARON
		"\\{\\\\v l\\}" => "l",
		// <U006C> <U013E>

		// LATIN CAPITAL LETTER L WITH STROKE
		"\\{\\\\L\\}" => "L",
		// <U004C> <U0141>

		// LATIN SMALL LETTER L WITH STROKE
		"\\{\\\\l\\}" => "l",
		// <U006C> <U0142>

		// LATIN CAPITAL LETTER N WITH ACUTE
		"\\{\\\\'N\\}" => "N",
		// <U004E> <U0143>

		// LATIN SMALL LETTER N WITH ACUTE
		"\\{\\\\'n\\}" => "n",
		// <U006E> <U0144>

		// LATIN CAPITAL LETTER N WITH CARON
		"\\{\\\\v N\\}" => "N",
		// <U004E> <U0147>

		// LATIN SMALL LETTER N WITH CARON
		"\\{\\\\v n\\}" => "n",
		// <U006E> <U0148>

		// LATIN CAPITAL LETTER ENG
		"\\{\\\\NG\\}" => "NG", // "N"
		// "<U004E><U0047>";<U004E> <U014A>

		// LATIN SMALL LETTER ENG
		"\\{\\\\ng\\}" => "ng", // "n"
		// "<U006E><U0067>";<U006E> <U014B>

		// LATIN CAPITAL LETTER O WITH DOUBLE ACUTE
		"\\{\\\\H O\\}" => "O",
		// <U004F> <U0150>

		// LATIN SMALL LETTER O WITH DOUBLE ACUTE
		"\\{\\\\H o\\}" => "o",
		// <U006F> <U0151>

		// LATIN CAPITAL LIGATURE OE
		"\\{\\\\OE\\}" => "OE",
		// "<U004F><U0045>" <U0152>

		// LATIN SMALL LIGATURE OE
		"\\{\\\\oe\\}" => "oe",
		// "<U006F><U0065>" <U0153>

		// LATIN CAPITAL LETTER R WITH ACUTE
		"\\{\\\\'R\\}" => "R",
		// <U0052> <U0154>

		// LATIN SMALL LETTER R WITH ACUTE
		"\\{\\\\'r\\}" => "r",
		// <U0072> <U0155>

		// LATIN CAPITAL LETTER R WITH CARON
		"\\{\\\\v R\\}" => "R",
		// <U0052> <U0158>

		// LATIN SMALL LETTER R WITH CARON
		"\\{\\\\v r\\}" => "r",
		// <U0072> <U0159>

		// LATIN CAPITAL LETTER S WITH ACUTE
		"\\{\\\\'S\\}" => "S",
		// <U0053> <U015A>

		// LATIN SMALL LETTER S WITH ACUTE
		"\\{\\\\'s\\}" => "s",
		// <U0073> <U015B>

		// LATIN CAPITAL LETTER S WITH CEDILLA
		"\\{\\\\c S\\}" => "S",
		// <U0053> <U015E>

		// LATIN SMALL LETTER S WITH CEDILLA
		"\\{\\\\c s\\}" => "s",
		// <U0073> <U015F>

		// LATIN CAPITAL LETTER S WITH CARON
		"\\{\\\\v S\\}" => "S",
		// <U0053> <U0160>

		// LATIN SMALL LETTER S WITH CARON
		"\\{\\\\v s\\}" => "s",
		// <U0073> <U0161>

		// LATIN CAPITAL LETTER T WITH CEDILLA
		"\\{\\\\c T\\}" => "T",
		// <U0054> <U0162>

		// LATIN SMALL LETTER T WITH CEDILLA
		"\\{\\\\c t\\}" => "t",
		// <U0074> <U0163>

		// LATIN CAPITAL LETTER T WITH CARON
		"\\{\\\\v T\\}" => "T",
		// <U0054> <U0164>

		// LATIN SMALL LETTER T WITH CARON
		"\\{\\\\v t\\}" => "t",
		// <U0074> <U0165>

		// LATIN CAPITAL LETTER U WITH RING ABOVE
		"\\{\\\\r U\\}" => "U",
		// <U0055> <U016E>

		// LATIN SMALL LETTER U WITH RING ABOVE
		"\\{\\\\r u\\}" => "u",
		// <U0075> <U016F>

		// LATIN CAPITAL LETTER U WITH DOUBLE ACUTE
		"\\{\\\\H U\\}" => "U",
		// <U0055> <U0170>

		// LATIN SMALL LETTER U WITH DOUBLE ACUTE
		"\\{\\\\H u\\}" => "u",
		// <U0075> <U0171>

		// LATIN CAPITAL LETTER Y WITH DIAERESIS
		"\\{\\\\\"Y\\}" => "Y",
		// <U0059> <U0178>

		// LATIN CAPITAL LETTER Z WITH ACUTE
		"\\{\\\\'Z\\}" => "Z",
		// <U005A> <U0179>

		// LATIN SMALL LETTER Z WITH ACUTE
		"\\{\\\\'z\\}" => "z",
		// <U007A> <U017A>

		// LATIN CAPITAL LETTER Z WITH DOT ABOVE
		"\\{\\\\.Z\\}" => "Z",
		// <U005A> <U017B>

		// LATIN SMALL LETTER Z WITH DOT ABOVE
		"\\{\\\\.z\\}" => "z",
		// <U007A> <U017C>

		// LATIN CAPITAL LETTER Z WITH CARON
		"\\{\\\\v Z\\}" => "Z",
		// <U005A> <U017D>

		// LATIN SMALL LETTER Z WITH CARON
		"\\{\\\\v z\\}" => "z",
		// <U007A> <U017E>

		// LATIN SMALL LETTER F WITH HOOK
		"\\{\\\\textflorin\\}" => "f",
		// <U0066> <U0192>

		// MODIFIER LETTER CIRCUMFLEX ACCENT
		"\\{\\\\textasciicircum\\}" => "^",
		// <U005E> <U02C6>

		// DOUBLE ACUTE ACCENT
		"\\{\\\\textacutedbl\\}" => "\"",
		// <U0022> <U02DD>

		// EN DASH
		"\\{\\\\textendash\\}|--" => "–", // note that this endash is NOT <U2013>
		// <U002D> <U2013>

		// EM DASH
		"\\{\\\\textemdash\\}|---" => "––", // I don't know how to correctly print an emdash using the latin1 charset so we'll currently use two endashes instead
		// "<U002D><U002D>" <U2014>

		// DOUBLE VERTICAL LINE
		"\\{\\\\textbardbl\\}" => "||",
		// "<U007C><U007C>" <U2016>

		// DOUBLE LOW LINE
		"\\{\\\\textunderscore\\}" => "_",
		// <U005F> <U2017>

		// LEFT SINGLE QUOTATION MARK
		"\\{\\\\textquoteleft\\}" => "'",
		// <U0027> <U2018>

		// RIGHT SINGLE QUOTATION MARK
		"\\{\\\\textquoteright\\}" => "'",
		// <U0027> <U2019>

		// SINGLE LOW-9 QUOTATION MARK
		"\\{\\\\quotesinglbase\\}" => "'",
		// <U0027> <U201A>

		// LEFT DOUBLE QUOTATION MARK
		"\\{\\\\textquotedblleft\\}" => "\"",
		// <U0022> <U201C>

		// RIGHT DOUBLE QUOTATION MARK
		"\\{\\\\textquotedblright\\}" => "\"",
		// <U0022> <U201D>

		// DOUBLE LOW-9 QUOTATION MARK
		"\\{\\\\quotedblbase\\}" => "\"",
		// <U0022> <U201E>

		// DAGGER
		"\\{\\\\textdagger\\}" => "+",
		// <U002B> <U2020>

		// DOUBLE DAGGER
		"\\{\\\\textdaggerdbl\\}" => "++",
		// "<U002B><U002B>" <U2021>

		// BULLET
		"\\{\\\\textbullet\\}" => "o",
		// <U006F> <U2022>

		// HORIZONTAL ELLIPSIS
		"\\{\\\\textellipsis\\}" => "...",
		// "<U002E><U002E><U002E>" <U2026>

		// PER MILLE SIGN
//		"\\{\\\\textperthousand\\}" => "0/00", // "[permil]" // this is translated into proper refbase markup via 'transtab_bibtex_refbase.inc.php'
		// "<U0020><U0030><U002F><U0030><U0030>" <U2030>

		// SINGLE LEFT-POINTING ANGLE QUOTATION MARK
		"\\{\\\\guilsinglleft\\}" => "<",
		// <U003C> <U2039>

		// SINGLE RIGHT-POINTING ANGLE QUOTATION MARK
		"\\{\\\\guilsinglright\\}" => ">",
		// <U003E> <U203A>

		// FRACTION SLASH
		"\\{\\\\textfractionsolidus\\}" => "/",
		// <U002F> <U2044>

		// SUPERSCRIPT ZERO
//		'\\$\\^\\{0\\}\\$' => "^0", // "[super:0]" // superscript markup is translated into proper refbase markup via 'transtab_bibtex_refbase.inc.php'
		// "<U005E><U0030>";<U0030> <U2070>

		// SUPERSCRIPT FOUR
//		'\\$\\^\\{4\\}\\$' => "^4", // "[super:4]"
		// "<U005E><U0034>";<U0034> <U2074>

		// SUPERSCRIPT FIVE
//		'\\$\\^\\{5\\}\\$' => "^5", // "[super:5]"
		// "<U005E><U0035>";<U0035> <U2075>

		// SUPERSCRIPT SIX
//		'\\$\\^\\{6\\}\\$' => "^6", // "[super:6]"
		// "<U005E><U0036>";<U0036> <U2076>

		// SUPERSCRIPT SEVEN
//		'\\$\\^\\{7\\}\\$' => "^7", // "[super:7]"
		// "<U005E><U0037>";<U0037> <U2077>

		// SUPERSCRIPT EIGHT
//		'\\$\\^\\{8\\}\\$' => "^8", // "[super:8]"
		// "<U005E><U0038>";<U0038> <U2078>

		// SUPERSCRIPT NINE
//		'\\$\\^\\{9\\}\\$' => "^9", // "[super:9]"
		// "<U005E><U0039>";<U0039> <U2079>

		// SUPERSCRIPT PLUS SIGN
//		'\\$\\^\\{+\\}\\$' => "^+", // "[super:+]"
		// "<U005E><U002B>";<U002B> <U207A>

		// SUPERSCRIPT MINUS
//		'\\$\\^\\{-\\}\\$' => "^-", // "[super:-]"
		// "<U005E><U002D>";<U002D> <U207B>

		// SUPERSCRIPT EQUALS SIGN
//		'\\$\\^\\{=\\}\\$' => "^=", // "[super:=]"
		// "<U005E><U003D>";<U003D> <U207C>

		// SUPERSCRIPT LEFT PARENTHESIS
//		'\\$\\^\\{(\\}\\$' => "^(", // "[super:(]"
		// "<U005E><U0028>";<U0028> <U207D>

		// SUPERSCRIPT RIGHT PARENTHESIS
//		'\\$\\^\\{)\\}\\$' => "^)", // "[super:)]"
		// "<U005E><U0029>";<U0029> <U207E>

		// SUPERSCRIPT LATIN SMALL LETTER N
//		'\\$\\^\\{n\\}\\$' => "^n", // "[super:n]"
		// "<U005E><U006E>";<U006E> <U207F>

		// SUBSCRIPT ZERO
//		'\\$_\\{0\\}\\$' => "_0", // "[sub:0]" // subscript markup is translated into proper refbase markup via 'transtab_bibtex_refbase.inc.php'
		// "<U005F><U0030>";<U0030> <U2080>

		// SUBSCRIPT ONE
//		'\\$_\\{1\\}\\$' => "_1", // "[sub:1]"
		// "<U005F><U0031>";<U0031> <U2081>

		// SUBSCRIPT TWO
//		'\\$_\\{2\\}\\$' => "_2", // "[sub:2]"
		// "<U005F><U0032>";<U0032> <U2082>

		// SUBSCRIPT THREE
//		'\\$_\\{3\\}\\$' => "_3", // "[sub:3]"
		// "<U005F><U0033>";<U0033> <U2083>

		// SUBSCRIPT FOUR
//		'\\$_\\{4\\}\\$' => "_4", // "[sub:4]"
		// "<U005F><U0034>";<U0034> <U2084>

		// SUBSCRIPT FIVE
//		'\\$_\\{5\\}\\$' => "_5", // "[sub:5]"
		// "<U005F><U0035>";<U0035> <U2085>

		// SUBSCRIPT SIX
//		'\\$_\\{6\\}\\$' => "_6", // "[sub:6]"
		// "<U005F><U0036>";<U0036> <U2086>

		// SUBSCRIPT SEVEN
//		'\\$_\\{7\\}\\$' => "_7", // "[sub:7]"
		// "<U005F><U0037>";<U0037> <U2087>

		// SUBSCRIPT EIGHT
//		'\\$_\\{8\\}\\$' => "_8", // "[sub:8]"
		// "<U005F><U0038>";<U0038> <U2088>

		// SUBSCRIPT NINE
//		'\\$_\\{9\\}\\$' => "_9", // "[sub:9]"
		// "<U005F><U0039>";<U0039> <U2089>

		// SUBSCRIPT PLUS SIGN
//		'\\$_\\{+\\}\\$' => "_+", // "[sub:+]"
		// "<U005F><U002B>";<U002B> <U208A>

		// SUBSCRIPT MINUS
//		'\\$_\\{-\\}\\$' => "_-", // "[sub:-]"
		// "<U005F><U002D>";<U002D> <U208B>

		// SUBSCRIPT EQUALS SIGN
//		'\\$_\\{=\\}\\$' => "_=", // "[sub:=]"
		// "<U005F><U003D>";<U003D> <U208C>

		// SUBSCRIPT LEFT PARENTHESIS
//		'\\$_\\{(\\}\\$' => "_(", // "[sub:(]"
		// "<U005F><U0028>";<U0028> <U208D>

		// SUBSCRIPT RIGHT PARENTHESIS
//		'\\$_\\{)\\}\\$' => "_)", // "[sub:)]"
		// "<U005F><U0029>";<U0029> <U208E>

		// EURO SIGN
		"\\{\\\\texteuro\\}" => "EUR", // "E"
		// "<U0045><U0055><U0052>";<U0045> <U20AC>

		// DEGREE CELSIUS
		"\\{\\\\textcelsius\\}" => "°C",
		// "<U00B0><U0043>";<U0043> <U2103>

		// NUMERO SIGN
		"\\{\\\\textnumero\\}" => "No", // "Nº"
		// "<U004E><U00BA>";"<U004E><U006F>" <U2116>

		// SOUND RECORDING COPYRIGHT
		"\\{\\\\textcircledP\\}" => "(P)",
		// "<U0028><U0050><U0029>" <U2117>

		// SERVICE MARK
		"\\{\\\\textservicemark\\}" => "[SM]",
		// "<U005B><U0053><U004D><U005D>" <U2120>

		// TRADE MARK SIGN
		"\\{\\\\texttrademark\\}" => "[TM]",
		// "<U005B><U0054><U004D><U005D>" <U2122>

		// OHM SIGN
		"\\{\\\\textohm\\}" => "ohm", // "O"
		// <U03A9>;"<U006F><U0068><U006D>";<U004F> <U2126>

		// ESTIMATED SYMBOL
		"\\{\\\\textestimated\\}" => "e",
		// <U0065> <U212E>

		// LEFTWARDS ARROW
		"\\{\\\\textleftarrow\\}" => "<-",
		// "<U003C><U002D>" <U2190>

		// UPWARDS ARROW
		"\\{\\\\textuparrow\\}" => "^",
		// <U005E> <U2191>

		// RIGHTWARDS ARROW
		"\\{\\\\textrightarrow\\}" => "->",
		// "<U002D><U003E>" <U2192>

		// DOWNWARDS ARROW
		"\\{\\\\textdownarrow\\}" => "v",
		// <U0076> <U2193>

		// INFINITY
//		'\\$\\\\infty\\$' => "inf", // "[infinity]" // this is translated into proper refbase markup via 'transtab_bibtex_refbase.inc.php'
		// "<U0069><U006E><U0066>" <U221E>

		// LEFT-POINTING ANGLE BRACKET
		"\\{\\\\textlangle\\}" => "<",
		// <U003C> <U2329>

		// RIGHT-POINTING ANGLE BRACKET
		"\\{\\\\textrangle\\}" => ">",
		// <U003E> <U232A>

		// OPEN BOX
		"\\{\\\\textvisiblespace\\}" => "_",
		// <U005F> <U2423>

		// WHITE BULLET
		"\\{\\\\textopenbullet\\}" => "o"
		// <U006F> <U25E6>

	);

?>
