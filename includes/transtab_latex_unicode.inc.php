<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/transtab_latex_unicode.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    10-Aug-06, 23:55
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is a translation table for best-effort conversion from LaTeX to Unicode entities. It contains a comprehensive list of substitution strings for LaTeX characters,
	// which are used with the 'T1' font encoding. Uses commands from the 'textcomp' package. Unicode characters that can't be matched uniquely are commented out.
	// Adopted from 'transtab' by Markus Kuhn
	// (transtab.utf v1.8 2000-10-12 11:01:28+01 mgk25 Exp); see <http://www.cl.cam.ac.uk/~mgk25/unicode.html> for more info about Unicode and transtab.

	$transtab_latex_unicode = array(

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
//		"\\{\\\\textquoteright\\}" => "'", // defined below
		// <U2019> <U0027>

		// GRAVE ACCENT
//		"\\{\\\\textquoteleft\\}" => "`", // defined below
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

		// LATIN CAPITAL LETTER A WITH MACRON
//		"A" => "Ā",
		// <U0041> <U0100>

		// LATIN SMALL LETTER A WITH MACRON
//		"a" => "ā",
		// <U0061> <U0101>

		// LATIN CAPITAL LETTER A WITH BREVE
		"\\{\\\\u A\\}" => "Ă",
		// <U0041> <U0102>

		// LATIN SMALL LETTER A WITH BREVE
		"\\{\\\\u a\\}" => "ă",
		// <U0061> <U0103>

		// LATIN CAPITAL LETTER A WITH OGONEK
		"\\{\\\\k A\\}" => "Ą",
		// <U0041> <U0104>

		// LATIN SMALL LETTER A WITH OGONEK
		"\\{\\\\k a\\}" => "ą",
		// <U0061> <U0105>

		// LATIN CAPITAL LETTER C WITH ACUTE
		"\\{\\\\'C\\}" => "Ć",
		// <U0043> <U0106>

		// LATIN SMALL LETTER C WITH ACUTE
		"\\{\\\\'c\\}" => "ć",
		// <U0063> <U0107>

		// LATIN CAPITAL LETTER C WITH CIRCUMFLEX
//		"Ch" => "Ĉ", // "C"
		// "<U0043><U0068>";<U0043> <U0108>

		// LATIN SMALL LETTER C WITH CIRCUMFLEX
//		"ch" => "ĉ", // "c"
		// "<U0063><U0068>";<U0063> <U0109>

		// LATIN CAPITAL LETTER C WITH DOT ABOVE
//		"C" => "Ċ",
		// <U0043> <U010A>

		// LATIN SMALL LETTER C WITH DOT ABOVE
//		"c" => "ċ",
		// <U0063> <U010B>

		// LATIN CAPITAL LETTER C WITH CARON
		"\\{\\\\v C\\}" => "Č",
		// <U0043> <U010C>

		// LATIN SMALL LETTER C WITH CARON
		"\\{\\\\v c\\}" => "č",
		// <U0063> <U010D>

		// LATIN CAPITAL LETTER D WITH CARON
		"\\{\\\\v D\\}" => "Ď",
		// <U0044> <U010E>

		// LATIN SMALL LETTER D WITH CARON
		"\\{\\\\v d\\}" => "ď",
		// <U0064> <U010F>

		// LATIN CAPITAL LETTER D WITH STROKE
		"\\{\\\\DJ\\}" => "Đ",
		// <U0044> <U0110>

		// LATIN SMALL LETTER D WITH STROKE
		"\\{\\\\dj\\}" => "đ",
		// <U0064> <U0111>

		// LATIN CAPITAL LETTER E WITH MACRON
//		"E" => "Ē",
		// <U0045> <U0112>

		// LATIN SMALL LETTER E WITH MACRON
//		"e" => "ē",
		// <U0065> <U0113>

		// LATIN CAPITAL LETTER E WITH BREVE
//		"E" => "Ĕ",
		// <U0045> <U0114>

		// LATIN SMALL LETTER E WITH BREVE
//		"e" => "ĕ",
		// <U0065> <U0115>

		// LATIN CAPITAL LETTER E WITH DOT ABOVE
//		"E" => "Ė",
		// <U0045> <U0116>

		// LATIN SMALL LETTER E WITH DOT ABOVE
//		"e" => "ė",
		// <U0065> <U0117>

		// LATIN CAPITAL LETTER E WITH OGONEK
		"\\{\\\\k E\\}" => "Ę",
		// <U0045> <U0118>

		// LATIN SMALL LETTER E WITH OGONEK
		"\\{\\\\k e\\}" => "ę",
		// <U0065> <U0119>

		// LATIN CAPITAL LETTER E WITH CARON
		"\\{\\\\v E\\}" => "Ě",
		// <U0045> <U011A>

		// LATIN SMALL LETTER E WITH CARON
		"\\{\\\\v e\\}" => "ě",
		// <U0065> <U011B>

		// LATIN CAPITAL LETTER G WITH CIRCUMFLEX
//		"Gh" => "Ĝ", // "G"
		// "<U0047><U0068>";<U0047> <U011C>

		// LATIN SMALL LETTER G WITH CIRCUMFLEX
//		"gh" => "ĝ", // "g"
		// "<U0067><U0068>";<U0067> <U011D>

		// LATIN CAPITAL LETTER G WITH BREVE
		"\\{\\\\u G\\}" => "Ğ",
		// <U0047> <U011E>

		// LATIN SMALL LETTER G WITH BREVE
		"\\{\\\\u g\\}" => "ğ",
		// <U0067> <U011F>

		// LATIN CAPITAL LETTER G WITH DOT ABOVE
//		"G" => "Ġ",
		// <U0047> <U0120>

		// LATIN SMALL LETTER G WITH DOT ABOVE
//		"g" => "ġ",
		// <U0067> <U0121>

		// LATIN CAPITAL LETTER G WITH CEDILLA
//		"G" => "Ģ",
		// <U0047> <U0122>

		// LATIN SMALL LETTER G WITH CEDILLA
//		"g" => "ģ",
		// <U0067> <U0123>

		// LATIN CAPITAL LETTER H WITH CIRCUMFLEX
//		"Hh" => "Ĥ", // "H"
		// "<U0048><U0068>";<U0048> <U0124>

		// LATIN SMALL LETTER H WITH CIRCUMFLEX
//		"hh" => "ĥ", // "h"
		// "<U0068><U0068>";<U0068> <U0125>

		// LATIN CAPITAL LETTER H WITH STROKE
//		"H" => "Ħ",
		// <U0048> <U0126>

		// LATIN SMALL LETTER H WITH STROKE
//		"h" => "ħ",
		// <U0068> <U0127>

		// LATIN CAPITAL LETTER I WITH TILDE
//		"I" => "Ĩ",
		// <U0049> <U0128>

		// LATIN SMALL LETTER I WITH TILDE
//		"i" => "ĩ",
		// <U0069> <U0129>

		// LATIN CAPITAL LETTER I WITH MACRON
//		"I" => "Ī",
		// <U0049> <U012A>

		// LATIN SMALL LETTER I WITH MACRON
//		"i" => "ī",
		// <U0069> <U012B>

		// LATIN CAPITAL LETTER I WITH BREVE
//		"I" => "Ĭ",
		// <U0049> <U012C>

		// LATIN SMALL LETTER I WITH BREVE
//		"i" => "ĭ",
		// <U0069> <U012D>

		// LATIN CAPITAL LETTER I WITH OGONEK
//		"I" => "Į",
		// <U0049> <U012E>

		// LATIN SMALL LETTER I WITH OGONEK
//		"i" => "į",
		// <U0069> <U012F>

		// LATIN CAPITAL LETTER I WITH DOT ABOVE
		"\\{\\\\.I\\}" => "İ",
		// <U0049> <U0130>

		// LATIN SMALL LETTER DOTLESS I
		"\\{\\\\i\\}" => "ı",
		// <U0069> <U0131>

		// LATIN CAPITAL LIGATURE IJ
//		"IJ" => "Ĳ",
		// "<U0049><U004A>" <U0132>

		// LATIN SMALL LIGATURE IJ
//		"ij" => "ĳ",
		// "<U0069><U006A>" <U0133>

		// LATIN CAPITAL LETTER J WITH CIRCUMFLEX
//		"Jh" => "Ĵ", // "J"
		// "<U004A><U0068>";<U004A> <U0134>

		// LATIN SMALL LETTER J WITH CIRCUMFLEX
//		"jh" => "ĵ", // "j"
		// "<U006A><U0068>";<U006A> <U0135>

		// LATIN CAPITAL LETTER K WITH CEDILLA
//		"K" => "Ķ",
		// <U004B> <U0136>

		// LATIN SMALL LETTER K WITH CEDILLA
//		"k" => "ķ",
		// <U006B> <U0137>

		// LATIN SMALL LETTER KRA
//		"k" => "ĸ",
		// <U006B> <U0138>

		// LATIN CAPITAL LETTER L WITH ACUTE
		"\\{\\\\'L\\}" => "Ĺ",
		// <U004C> <U0139>

		// LATIN SMALL LETTER L WITH ACUTE
		"\\{\\\\'l\\}" => "ĺ",
		// <U006C> <U013A>

		// LATIN CAPITAL LETTER L WITH CEDILLA
//		"L" => "Ļ",
		// <U004C> <U013B>

		// LATIN SMALL LETTER L WITH CEDILLA
//		"l" => "ļ",
		// <U006C> <U013C>

		// LATIN CAPITAL LETTER L WITH CARON
		"\\{\\\\v L\\}" => "Ľ",
		// <U004C> <U013D>

		// LATIN SMALL LETTER L WITH CARON
		"\\{\\\\v l\\}" => "ľ",
		// <U006C> <U013E>

		// LATIN CAPITAL LETTER L WITH MIDDLE DOT
//		"L·" => "Ŀ", // "L.", "L"
		// "<U004C><U00B7>";"<U004C><U002E>";<U004C> <U013F>

		// LATIN SMALL LETTER L WITH MIDDLE DOT
//		"l·" => "ŀ", // "l.", "l"
		// "<U006C><U00B7>";"<U006C><U002E>";<U006C> <U0140>

		// LATIN CAPITAL LETTER L WITH STROKE
		"\\{\\\\L\\}" => "Ł",
		// <U004C> <U0141>

		// LATIN SMALL LETTER L WITH STROKE
		"\\{\\\\l\\}" => "ł",
		// <U006C> <U0142>

		// LATIN CAPITAL LETTER N WITH ACUTE
		"\\{\\\\'N\\}" => "Ń",
		// <U004E> <U0143>

		// LATIN SMALL LETTER N WITH ACUTE
		"\\{\\\\'n\\}" => "ń",
		// <U006E> <U0144>

		// LATIN CAPITAL LETTER N WITH CEDILLA
//		"N" => "Ņ",
		// <U004E> <U0145>

		// LATIN SMALL LETTER N WITH CEDILLA
//		"n" => "ņ",
		// <U006E> <U0146>

		// LATIN CAPITAL LETTER N WITH CARON
		"\\{\\\\v N\\}" => "Ň",
		// <U004E> <U0147>

		// LATIN SMALL LETTER N WITH CARON
		"\\{\\\\v n\\}" => "ň",
		// <U006E> <U0148>

		// LATIN SMALL LETTER N PRECEDED BY APOSTROPHE
//		"'n" => "ŉ",
		// "<U0027><U006E>" <U0149>

		// LATIN CAPITAL LETTER ENG
		"\\{\\\\NG\\}" => "Ŋ", // "N"
		// "<U004E><U0047>";<U004E> <U014A>

		// LATIN SMALL LETTER ENG
		"\\{\\\\ng\\}" => "ŋ", // "n"
		// "<U006E><U0067>";<U006E> <U014B>

		// LATIN CAPITAL LETTER O WITH MACRON
//		"O" => "Ō",
		// <U004F> <U014C>

		// LATIN SMALL LETTER O WITH MACRON
//		"o" => "ō",
		// <U006F> <U014D>

		// LATIN CAPITAL LETTER O WITH BREVE
//		"O" => "Ŏ",
		// <U004F> <U014E>

		// LATIN SMALL LETTER O WITH BREVE
//		"o" => "ŏ",
		// <U006F> <U014F>

		// LATIN CAPITAL LETTER O WITH DOUBLE ACUTE
		"\\{\\\\H O\\}" => "Ő",
		// <U004F> <U0150>

		// LATIN SMALL LETTER O WITH DOUBLE ACUTE
		"\\{\\\\H o\\}" => "ő",
		// <U006F> <U0151>

		// LATIN CAPITAL LIGATURE OE
		"\\{\\\\OE\\}" => "Œ",
		// "<U004F><U0045>" <U0152>

		// LATIN SMALL LIGATURE OE
		"\\{\\\\oe\\}" => "œ",
		// "<U006F><U0065>" <U0153>

		// LATIN CAPITAL LETTER R WITH ACUTE
		"\\{\\\\'R\\}" => "Ŕ",
		// <U0052> <U0154>

		// LATIN SMALL LETTER R WITH ACUTE
		"\\{\\\\'r\\}" => "ŕ",
		// <U0072> <U0155>

		// LATIN CAPITAL LETTER R WITH CEDILLA
//		"R" => "Ŗ",
		// <U0052> <U0156>

		// LATIN SMALL LETTER R WITH CEDILLA
//		"r" => "ŗ",
		// <U0072> <U0157>

		// LATIN CAPITAL LETTER R WITH CARON
		"\\{\\\\v R\\}" => "Ř",
		// <U0052> <U0158>

		// LATIN SMALL LETTER R WITH CARON
		"\\{\\\\v r\\}" => "ř",
		// <U0072> <U0159>

		// LATIN CAPITAL LETTER S WITH ACUTE
		"\\{\\\\'S\\}" => "Ś",
		// <U0053> <U015A>

		// LATIN SMALL LETTER S WITH ACUTE
		"\\{\\\\'s\\}" => "ś",
		// <U0073> <U015B>

		// LATIN CAPITAL LETTER S WITH CIRCUMFLEX
//		"Sh" => "Ŝ", // "S"
		// "<U0053><U0068>";<U0053> <U015C>

		// LATIN SMALL LETTER S WITH CIRCUMFLEX
//		"sh" => "ŝ", // "s"
		// "<U0073><U0068>";<U0073> <U015D>

		// LATIN CAPITAL LETTER S WITH CEDILLA
		"\\{\\\\c S\\}" => "Ş",
		// <U0053> <U015E>

		// LATIN SMALL LETTER S WITH CEDILLA
		"\\{\\\\c s\\}" => "ş",
		// <U0073> <U015F>

		// LATIN CAPITAL LETTER S WITH CARON
		"\\{\\\\v S\\}" => "Š",
		// <U0053> <U0160>

		// LATIN SMALL LETTER S WITH CARON
		"\\{\\\\v s\\}" => "š",
		// <U0073> <U0161>

		// LATIN CAPITAL LETTER T WITH CEDILLA
		"\\{\\\\c T\\}" => "Ţ",
		// <U0054> <U0162>

		// LATIN SMALL LETTER T WITH CEDILLA
		"\\{\\\\c t\\}" => "ţ",
		// <U0074> <U0163>

		// LATIN CAPITAL LETTER T WITH CARON
		"\\{\\\\v T\\}" => "Ť",
		// <U0054> <U0164>

		// LATIN SMALL LETTER T WITH CARON
		"\\{\\\\v t\\}" => "ť",
		// <U0074> <U0165>

		// LATIN CAPITAL LETTER T WITH STROKE
//		"T" => "Ŧ",
		// <U0054> <U0166>

		// LATIN SMALL LETTER T WITH STROKE
//		"t" => "ŧ",
		// <U0074> <U0167>

		// LATIN CAPITAL LETTER U WITH TILDE
//		"U" => "Ũ",
		// <U0055> <U0168>

		// LATIN SMALL LETTER U WITH TILDE
//		"u" => "ũ",
		// <U0075> <U0169>

		// LATIN CAPITAL LETTER U WITH MACRON
//		"U" => "Ū",
		// <U0055> <U016A>

		// LATIN SMALL LETTER U WITH MACRON
//		"u" => "ū",
		// <U0075> <U016B>

		// LATIN CAPITAL LETTER U WITH BREVE
//		"U" => "Ŭ",
		// <U0055> <U016C>

		// LATIN SMALL LETTER U WITH BREVE
//		"u" => "ŭ",
		// <U0075> <U016D>

		// LATIN CAPITAL LETTER U WITH RING ABOVE
		"\\{\\\\r U\\}" => "Ů",
		// <U0055> <U016E>

		// LATIN SMALL LETTER U WITH RING ABOVE
		"\\{\\\\r u\\}" => "ů",
		// <U0075> <U016F>

		// LATIN CAPITAL LETTER U WITH DOUBLE ACUTE
		"\\{\\\\H U\\}" => "Ű",
		// <U0055> <U0170>

		// LATIN SMALL LETTER U WITH DOUBLE ACUTE
		"\\{\\\\H u\\}" => "ű",
		// <U0075> <U0171>

		// LATIN CAPITAL LETTER U WITH OGONEK
//		"U" => "Ų",
		// <U0055> <U0172>

		// LATIN SMALL LETTER U WITH OGONEK
//		"u" => "ų",
		// <U0075> <U0173>

		// LATIN CAPITAL LETTER W WITH CIRCUMFLEX
//		"W" => "Ŵ",
		// <U0057> <U0174>

		// LATIN SMALL LETTER W WITH CIRCUMFLEX
//		"w" => "ŵ",
		// <U0077> <U0175>

		// LATIN CAPITAL LETTER Y WITH CIRCUMFLEX
//		"Y" => "Ŷ",
		// <U0059> <U0176>

		// LATIN SMALL LETTER Y WITH CIRCUMFLEX
//		"y" => "ŷ",
		// <U0079> <U0177>

		// LATIN CAPITAL LETTER Y WITH DIAERESIS
		"\\{\\\\\"Y\\}" => "Ÿ",
		// <U0059> <U0178>

		// LATIN CAPITAL LETTER Z WITH ACUTE
		"\\{\\\\'Z\\}" => "Ź",
		// <U005A> <U0179>

		// LATIN SMALL LETTER Z WITH ACUTE
		"\\{\\\\'z\\}" => "ź",
		// <U007A> <U017A>

		// LATIN CAPITAL LETTER Z WITH DOT ABOVE
		"\\{\\\\.Z\\}" => "Ż",
		// <U005A> <U017B>

		// LATIN SMALL LETTER Z WITH DOT ABOVE
		"\\{\\\\.z\\}" => "ż",
		// <U007A> <U017C>

		// LATIN CAPITAL LETTER Z WITH CARON
		"\\{\\\\v Z\\}" => "Ž",
		// <U005A> <U017D>

		// LATIN SMALL LETTER Z WITH CARON
		"\\{\\\\v z\\}" => "ž",
		// <U007A> <U017E>

		// LATIN SMALL LETTER LONG S
//		"s" => "ſ",
		// <U0073> <U017F>

		// LATIN SMALL LETTER F WITH HOOK
		"\\{\\\\textflorin\\}" => "ƒ",
		// <U0066> <U0192>

		// LATIN CAPITAL LETTER S WITH COMMA BELOW
//		"S" => "Ș", // "Ş"
		// <U015E>;<U0053> <U0218>

		// LATIN SMALL LETTER S WITH COMMA BELOW
//		"s" => "ș", // "ş"
		// <U015F>;<U0073> <U0219>

		// LATIN CAPITAL LETTER T WITH COMMA BELOW
//		"T" => "Ț", // "Ţ"
		// <U0162>;<U0054> <U021A>

		// LATIN SMALL LETTER T WITH COMMA BELOW
//		"t" => "ț", // "ţ"
		// <U0163>;<U0074> <U021B>

		// MODIFIER LETTER PRIME
//		"'" => "ʹ", // "′"
		// <U2032>;<U0027> <U02B9>

		// MODIFIER LETTER TURNED COMMA
//		"'" => "ʻ", // "‘"
		// <U2018> <U02BB>

		// MODIFIER LETTER APOSTROPHE
//		"'" => "ʼ", // "’"
		// <U2019>;<U0027> <U02BC>

		// MODIFIER LETTER REVERSED COMMA
//		"'" => "ʽ", // "‛"
		// <U201B> <U02BD>

		// MODIFIER LETTER CIRCUMFLEX ACCENT
		"\\{\\\\textasciicircum\\}" => "ˆ",
		// <U005E> <U02C6>

		// MODIFIER LETTER VERTICAL LINE
//		"'" => "ˈ",
		// <U0027> <U02C8>

		// MODIFIER LETTER MACRON
//		"-" => "ˉ", // "¯"
		// <U00AF> <U02C9>

		// MODIFIER LETTER LOW VERTICAL LINE
//		"," => "ˌ",
		// <U002C> <U02CC>

		// MODIFIER LETTER TRIANGULAR COLON
//		":" => "ː",
		// <U003A> <U02D0>

		// RING ABOVE
//		"o" => "˚", // "°"
		// <U00B0> <U02DA>

		// SMALL TILDE
//		"\\\\~(\\{\\})?" => "˜",
		// <U007E> <U02DC>

		// DOUBLE ACUTE ACCENT
		"\\{\\\\textacutedbl\\}" => "˝",
		// <U0022> <U02DD>

		// GREEK NUMERAL SIGN
//		"'" => "ʹ",
		// <U0027> <U0374>

		// GREEK LOWER NUMERAL SIGN
//		"," => "͵",
		// <U002C> <U0375>

		// GREEK QUESTION MARK
//		";" => ";",
		// <U003B> <U037E>

		// LATIN CAPITAL LETTER B WITH DOT ABOVE
//		"B" => "Ḃ",
		// <U0042> <U1E02>

		// LATIN SMALL LETTER B WITH DOT ABOVE
//		"b" => "ḃ",
		// <U0062> <U1E03>

		// LATIN CAPITAL LETTER D WITH DOT ABOVE
//		"D" => "Ḋ",
		// <U0044> <U1E0A>

		// LATIN SMALL LETTER D WITH DOT ABOVE
//		"d" => "ḋ",
		// <U0064> <U1E0B>

		// LATIN CAPITAL LETTER F WITH DOT ABOVE
//		"F" => "Ḟ",
		// <U0046> <U1E1E>

		// LATIN SMALL LETTER F WITH DOT ABOVE
//		"f" => "ḟ",
		// <U0066> <U1E1F>

		// LATIN CAPITAL LETTER M WITH DOT ABOVE
//		"M" => "Ṁ",
		// <U004D> <U1E40>

		// LATIN SMALL LETTER M WITH DOT ABOVE
//		"m" => "ṁ",
		// <U006D> <U1E41>

		// LATIN CAPITAL LETTER P WITH DOT ABOVE
//		"P" => "Ṗ",
		// <U0050> <U1E56>

		// LATIN SMALL LETTER P WITH DOT ABOVE
//		"p" => "ṗ",
		// <U0070> <U1E57>

		// LATIN CAPITAL LETTER S WITH DOT ABOVE
//		"S" => "Ṡ",
		// <U0053> <U1E60>

		// LATIN SMALL LETTER S WITH DOT ABOVE
//		"s" => "ṡ",
		// <U0073> <U1E61>

		// LATIN CAPITAL LETTER T WITH DOT ABOVE
//		"T" => "Ṫ",
		// <U0054> <U1E6A>

		// LATIN SMALL LETTER T WITH DOT ABOVE
//		"t" => "ṫ",
		// <U0074> <U1E6B>

		// LATIN CAPITAL LETTER W WITH GRAVE
//		"W" => "Ẁ",
		// <U0057> <U1E80>

		// LATIN SMALL LETTER W WITH GRAVE
//		"w" => "ẁ",
		// <U0077> <U1E81>

		// LATIN CAPITAL LETTER W WITH ACUTE
//		"W" => "Ẃ",
		// <U0057> <U1E82>

		// LATIN SMALL LETTER W WITH ACUTE
//		"w" => "ẃ",
		// <U0077> <U1E83>

		// LATIN CAPITAL LETTER W WITH DIAERESIS
//		"W" => "Ẅ",
		// <U0057> <U1E84>

		// LATIN SMALL LETTER W WITH DIAERESIS
//		"w" => "ẅ",
		// <U0077> <U1E85>

		// LATIN CAPITAL LETTER Y WITH GRAVE
//		"Y" => "Ỳ",
		// <U0059> <U1EF2>

		// LATIN SMALL LETTER Y WITH GRAVE
//		"y" => "ỳ",
		// <U0079> <U1EF3>

		// EN QUAD
//		" " => " ",
		// <U0020> <U2000>

		// EM QUAD
//		"  " => " ",
		// "<U0020><U0020>" <U2001>

		// EN SPACE
//		" " => " ",
		// <U0020> <U2002>

		// EM SPACE
//		"  " => " ",
		// "<U0020><U0020>" <U2003>

		// THREE-PER-EM SPACE
//		" " => " ",
		// <U0020> <U2004>

		// FOUR-PER-EM SPACE
//		" " => " ",
		// <U0020> <U2005>

		// SIX-PER-EM SPACE
//		" " => " ",
		// <U0020> <U2006>

		// FIGURE SPACE
//		" " => " ",
		// <U0020> <U2007>

		// PUNCTUATION SPACE
//		" " => " ",
		// <U0020> <U2008>

		// THIN SPACE
//		" " => " ",
		// <U0020> <U2009>

		// HYPHEN
//		"-" => "‐",
		// <U002D> <U2010>

		// NON-BREAKING HYPHEN
//		"-" => "‑",
		// <U002D> <U2011>

		// FIGURE DASH
//		"-" => "‒",
		// <U002D> <U2012>

		// EN DASH
		"\\{\\\\textendash\\}|--" => "–",
		// <U002D> <U2013>

		// EM DASH
		"\\{\\\\textemdash\\}|---" => "—",
		// "<U002D><U002D>" <U2014>

		// HORIZONTAL BAR
//		"--" => "―",
		// "<U002D><U002D>" <U2015>

		// DOUBLE VERTICAL LINE
		"\\{\\\\textbardbl\\}" => "‖",
		// "<U007C><U007C>" <U2016>

		// DOUBLE LOW LINE
		"\\{\\\\textunderscore\\}" => "‗",
		// <U005F> <U2017>

		// LEFT SINGLE QUOTATION MARK
		"\\{\\\\textquoteleft\\}" => "‘",
		// <U0027> <U2018>

		// RIGHT SINGLE QUOTATION MARK
		"\\{\\\\textquoteright\\}" => "’",
		// <U0027> <U2019>

		// SINGLE LOW-9 QUOTATION MARK
		"\\{\\\\quotesinglbase\\}" => "‚",
		// <U0027> <U201A>

		// SINGLE HIGH-REVERSED-9 QUOTATION MARK
//		"'" => "‛",
		// <U0027> <U201B>

		// LEFT DOUBLE QUOTATION MARK
		"\\{\\\\textquotedblleft\\}" => "“",
		// <U0022> <U201C>

		// RIGHT DOUBLE QUOTATION MARK
		"\\{\\\\textquotedblright\\}" => "”",
		// <U0022> <U201D>

		// DOUBLE LOW-9 QUOTATION MARK
		"\\{\\\\quotedblbase\\}" => "„",
		// <U0022> <U201E>

		// DOUBLE HIGH-REVERSED-9 QUOTATION MARK
//		"\"" => "‟",
		// <U0022> <U201F>

		// DAGGER
		"\\{\\\\textdagger\\}" => "†",
		// <U002B> <U2020>

		// DOUBLE DAGGER
		"\\{\\\\textdaggerdbl\\}" => "‡",
		// "<U002B><U002B>" <U2021>

		// BULLET
		"\\{\\\\textbullet\\}" => "•",
		// <U006F> <U2022>

		// TRIANGULAR BULLET
//		">" => "‣",
		// <U003E> <U2023>

		// ONE DOT LEADER
//		"." => "․",
		// <U002E> <U2024>

		// TWO DOT LEADER
//		".." => "‥",
		// "<U002E><U002E>" <U2025>

		// HORIZONTAL ELLIPSIS
		"\\{\\\\textellipsis\\}" => "…",
		// "<U002E><U002E><U002E>" <U2026>

		// HYPHENATION POINT
//		"-" => "‧",
		// <U002D> <U2027>

		// NARROW NO-BREAK SPACE
//		" " => " ",
		// <U0020> <U202F>

		// PER MILLE SIGN
		"\\{\\\\textperthousand\\}" => "‰",
		// "<U0020><U0030><U002F><U0030><U0030>" <U2030>

		// PRIME
//		"'" => "′",
		// <U0027> <U2032>

		// DOUBLE PRIME
//		"\"" => "″",
		// <U0022> <U2033>

		// TRIPLE PRIME
//		"'''" => "‴",
		// "<U0027><U0027><U0027>" <U2034>

		// REVERSED PRIME
//		"`" => "‵",
		// <U0060> <U2035>

		// REVERSED DOUBLE PRIME
//		"``" => "‶",
		// "<U0060><U0060>" <U2036>

		// REVERSED TRIPLE PRIME
//		"```" => "‷",
		// "<U0060><U0060><U0060>" <U2037>

		// SINGLE LEFT-POINTING ANGLE QUOTATION MARK
		"\\{\\\\guilsinglleft\\}" => "‹",
		// <U003C> <U2039>

		// SINGLE RIGHT-POINTING ANGLE QUOTATION MARK
		"\\{\\\\guilsinglright\\}" => "›",
		// <U003E> <U203A>

		// DOUBLE EXCLAMATION MARK
//		"!!" => "‼",
		// "<U0021><U0021>" <U203C>

		// OVERLINE
//		"-" => "‾",
		// <U002D> <U203E>

		// HYPHEN BULLET
//		"-" => "⁃",
		// <U002D> <U2043>

		// FRACTION SLASH
		"\\{\\\\textfractionsolidus\\}" => "⁄",
		// <U002F> <U2044>

		// QUESTION EXCLAMATION MARK
//		"?!" => "⁈",
		// "<U003F><U0021>" <U2048>

		// EXCLAMATION QUESTION MARK
//		"!?" => "⁉",
		// "<U0021><U003F>" <U2049>

		// TIRONIAN SIGN ET
//		"7" => "⁊",
		// <U0037> <U204A>

		// SUPERSCRIPT ZERO
		'\\$\\^\\{0\\}\\$' => "⁰", // "[super:0]" // "0"
		// "<U005E><U0030>";<U0030> <U2070>

		// SUPERSCRIPT FOUR
		'\\$\\^\\{4\\}\\$' => "⁴", // "[super:4]" // "4"
		// "<U005E><U0034>";<U0034> <U2074>

		// SUPERSCRIPT FIVE
		'\\$\\^\\{5\\}\\$' => "⁵", // "[super:5]" // "5"
		// "<U005E><U0035>";<U0035> <U2075>

		// SUPERSCRIPT SIX
		'\\$\\^\\{6\\}\\$' => "⁶", // "[super:6]" // "6"
		// "<U005E><U0036>";<U0036> <U2076>

		// SUPERSCRIPT SEVEN
		'\\$\\^\\{7\\}\\$' => "⁷", // "[super:7]" // "7"
		// "<U005E><U0037>";<U0037> <U2077>

		// SUPERSCRIPT EIGHT
		'\\$\\^\\{8\\}\\$' => "⁸", // "[super:8]" // "8"
		// "<U005E><U0038>";<U0038> <U2078>

		// SUPERSCRIPT NINE
		'\\$\\^\\{9\\}\\$' => "⁹", // "[super:9]" // "9"
		// "<U005E><U0039>";<U0039> <U2079>

		// SUPERSCRIPT PLUS SIGN
		'\\$\\^\\{+\\}\\$' => "⁺", // "[super:+]" // "+"
		// "<U005E><U002B>";<U002B> <U207A>

		// SUPERSCRIPT MINUS
		'\\$\\^\\{-\\}\\$' => "⁻", // "[super:-]" // "-"
		// "<U005E><U002D>";<U002D> <U207B>

		// SUPERSCRIPT EQUALS SIGN
		'\\$\\^\\{=\\}\\$' => "⁼", // "[super:=]" // "="
		// "<U005E><U003D>";<U003D> <U207C>

		// SUPERSCRIPT LEFT PARENTHESIS
		'\\$\\^\\{\\(\\}\\$' => "⁽", // "[super:(]" // "("
		// "<U005E><U0028>";<U0028> <U207D>

		// SUPERSCRIPT RIGHT PARENTHESIS
		'\\$\\^\\{\\)\\}\\$' => "⁾", // "[super:)]" // ")"
		// "<U005E><U0029>";<U0029> <U207E>

		// SUPERSCRIPT LATIN SMALL LETTER N
		'\\$\\^\\{n\\}\\$' => "ⁿ", // "[super:n]" // "n"
		// "<U005E><U006E>";<U006E> <U207F>

		// SUBSCRIPT ZERO
		'\\$_\\{0\\}\\$' => "₀", // "[sub:0]" // "0"
		// "<U005F><U0030>";<U0030> <U2080>

		// SUBSCRIPT ONE
		'\\$_\\{1\\}\\$' => "₁", // "[sub:1]" // "1"
		// "<U005F><U0031>";<U0031> <U2081>

		// SUBSCRIPT TWO
		'\\$_\\{2\\}\\$' => "₂", // "[sub:2]" // "2"
		// "<U005F><U0032>";<U0032> <U2082>

		// SUBSCRIPT THREE
		'\\$_\\{3\\}\\$' => "₃", // "[sub:3]" // "3"
		// "<U005F><U0033>";<U0033> <U2083>

		// SUBSCRIPT FOUR
		'\\$_\\{4\\}\\$' => "₄", // "[sub:4]" // "4"
		// "<U005F><U0034>";<U0034> <U2084>

		// SUBSCRIPT FIVE
		'\\$_\\{5\\}\\$' => "₅", // "[sub:5]" // "5"
		// "<U005F><U0035>";<U0035> <U2085>

		// SUBSCRIPT SIX
		'\\$_\\{6\\}\\$' => "₆", // "[sub:6]" // "6"
		// "<U005F><U0036>";<U0036> <U2086>

		// SUBSCRIPT SEVEN
		'\\$_\\{7\\}\\$' => "₇", // "[sub:7]" // "7"
		// "<U005F><U0037>";<U0037> <U2087>

		// SUBSCRIPT EIGHT
		'\\$_\\{8\\}\\$' => "₈", // "[sub:8]" // "8"
		// "<U005F><U0038>";<U0038> <U2088>

		// SUBSCRIPT NINE
		'\\$_\\{9\\}\\$' => "₉", // "[sub:9]" // "9"
		// "<U005F><U0039>";<U0039> <U2089>

		// SUBSCRIPT PLUS SIGN
		'\\$_\\{+\\}\\$' => "₊", // "[sub:+]" // "+"
		// "<U005F><U002B>";<U002B> <U208A>

		// SUBSCRIPT MINUS
		'\\$_\\{-\\}\\$' => "₋", // "[sub:-]" // "-"
		// "<U005F><U002D>";<U002D> <U208B>

		// SUBSCRIPT EQUALS SIGN
		'\\$_\\{=\\}\\$' => "₌", // "[sub:=]" // "="
		// "<U005F><U003D>";<U003D> <U208C>

		// SUBSCRIPT LEFT PARENTHESIS
		'\\$_\\{\\(\\}\\$' => "₍", // "[sub:(]" // "("
		// "<U005F><U0028>";<U0028> <U208D>

		// SUBSCRIPT RIGHT PARENTHESIS
		'\\$_\\{\\)\\}\\$' => "₎", // "[sub:)]" // ")"
		// "<U005F><U0029>";<U0029> <U208E>

		// EURO SIGN
		"\\{\\\\texteuro\\}" => "€", // "E"
		// "<U0045><U0055><U0052>";<U0045> <U20AC>

		// ACCOUNT OF
//		"a/c" => "℀",
		// "<U0061><U002F><U0063>" <U2100>

		// ADDRESSED TO THE SUBJECT
//		"a/s" => "℁",
		// "<U0061><U002F><U0073>" <U2101>

		// DEGREE CELSIUS
		"\\{\\\\textcelsius\\}" => "℃", // "°C"
		// "<U00B0><U0043>";<U0043> <U2103>

		// CARE OF
//		"c/o" => "℅",
		// "<U0063><U002F><U006F>" <U2105>

		// CADA UNA
//		"c/u" => "℆",
		// "<U0063><U002F><U0075>" <U2106>

		// DEGREE FAHRENHEIT
//		"F" => "℉", // "°F"
		// "<U00B0><U0046>";<U0046> <U2109>

		// SCRIPT SMALL L
//		"l" => "ℓ",
		// <U006C> <U2113>

		// NUMERO SIGN
		"\\{\\\\textnumero\\}" => "№", // "Nº"
		// "<U004E><U00BA>";"<U004E><U006F>" <U2116>

		// SOUND RECORDING COPYRIGHT
		"\\{\\\\textcircledP\\}" => "℗",
		// "<U0028><U0050><U0029>" <U2117>

		// SERVICE MARK
		"\\{\\\\textservicemark\\}" => "℠",
		// "<U005B><U0053><U004D><U005D>" <U2120>

		// TELEPHONE SIGN
//		"TEL" => "℡",
		// "<U0054><U0045><U004C>" <U2121>

		// TRADE MARK SIGN
		"\\{\\\\texttrademark\\}" => "™",
		// "<U005B><U0054><U004D><U005D>" <U2122>

		// OHM SIGN
		"\\{\\\\textohm\\}" => "Ω", // "Ω", "O"
		// <U03A9>;"<U006F><U0068><U006D>";<U004F> <U2126>

		// KELVIN SIGN
//		"K" => "K",
		// <U004B> <U212A>

		// ANGSTROM SIGN
//		"A" => "Å", // "Å"
		// <U00C5> <U212B>

		// ESTIMATED SYMBOL
		"\\{\\\\textestimated\\}" => "℮",
		// <U0065> <U212E>

		// VULGAR FRACTION ONE THIRD
//		" 1/3" => "⅓",
		// "<U0020><U0031><U002F><U0033>" <U2153>

		// VULGAR FRACTION TWO THIRDS
//		" 2/3" => "⅔",
		// "<U0020><U0032><U002F><U0033>" <U2154>

		// VULGAR FRACTION ONE FIFTH
//		" 1/5" => "⅕",
		// "<U0020><U0031><U002F><U0035>" <U2155>

		// VULGAR FRACTION TWO FIFTHS
//		" 2/5" => "⅖",
		// "<U0020><U0032><U002F><U0035>" <U2156>

		// VULGAR FRACTION THREE FIFTHS
//		" 3/5" => "⅗",
		// "<U0020><U0033><U002F><U0035>" <U2157>

		// VULGAR FRACTION FOUR FIFTHS
//		" 4/5" => "⅘",
		// "<U0020><U0034><U002F><U0035>" <U2158>

		// VULGAR FRACTION ONE SIXTH
//		" 1/6" => "⅙",
		// "<U0020><U0031><U002F><U0036>" <U2159>

		// VULGAR FRACTION FIVE SIXTHS
//		" 5/6" => "⅚",
		// "<U0020><U0035><U002F><U0036>" <U215A>

		// VULGAR FRACTION ONE EIGHTH
//		" 1/8" => "⅛",
		// "<U0020><U0031><U002F><U0038>" <U215B>

		// VULGAR FRACTION THREE EIGHTHS
//		" 3/8" => "⅜",
		// "<U0020><U0033><U002F><U0038>" <U215C>

		// VULGAR FRACTION FIVE EIGHTHS
//		" 5/8" => "⅝",
		// "<U0020><U0035><U002F><U0038>" <U215D>

		// VULGAR FRACTION SEVEN EIGHTHS
//		" 7/8" => "⅞",
		// "<U0020><U0037><U002F><U0038>" <U215E>

		// FRACTION NUMERATOR ONE
//		" 1/" => "⅟",
		// "<U0020><U0031><U002F>" <U215F>

		// ROMAN NUMERAL ONE
//		"I" => "Ⅰ",
		// <U0049> <U2160>

		// ROMAN NUMERAL TWO
//		"II" => "Ⅱ",
		// "<U0049><U0049>" <U2161>

		// ROMAN NUMERAL THREE
//		"III" => "Ⅲ",
		// "<U0049><U0049><U0049>" <U2162>

		// ROMAN NUMERAL FOUR
//		"IV" => "Ⅳ",
		// "<U0049><U0056>" <U2163>

		// ROMAN NUMERAL FIVE
//		"V" => "Ⅴ",
		// <U0056> <U2164>

		// ROMAN NUMERAL SIX
//		"VI" => "Ⅵ",
		// "<U0056><U0049>" <U2165>

		// ROMAN NUMERAL SEVEN
//		"VII" => "Ⅶ",
		// "<U0056><U0049><U0049>" <U2166>

		// ROMAN NUMERAL EIGHT
//		"VIII" => "Ⅷ",
		// "<U0056><U0049><U0049><U0049>" <U2167>

		// ROMAN NUMERAL NINE
//		"IX" => "Ⅸ",
		// "<U0049><U0058>" <U2168>

		// ROMAN NUMERAL TEN
//		"X" => "Ⅹ",
		// <U0058> <U2169>

		// ROMAN NUMERAL ELEVEN
//		"XI" => "Ⅺ",
		// "<U0058><U0049>" <U216A>

		// ROMAN NUMERAL TWELVE
//		"XII" => "Ⅻ",
		// "<U0058><U0049><U0049>" <U216B>

		// ROMAN NUMERAL FIFTY
//		"L" => "Ⅼ",
		// <U004C> <U216C>

		// ROMAN NUMERAL ONE HUNDRED
//		"C" => "Ⅽ",
		// <U0043> <U216D>

		// ROMAN NUMERAL FIVE HUNDRED
//		"D" => "Ⅾ",
		// <U0044> <U216E>

		// ROMAN NUMERAL ONE THOUSAND
//		"M" => "Ⅿ",
		// <U004D> <U216F>

		// SMALL ROMAN NUMERAL ONE
//		"i" => "ⅰ",
		// <U0069> <U2170>

		// SMALL ROMAN NUMERAL TWO
//		"ii" => "ⅱ",
		// "<U0069><U0069>" <U2171>

		// SMALL ROMAN NUMERAL THREE
//		"iii" => "ⅲ",
		// "<U0069><U0069><U0069>" <U2172>

		// SMALL ROMAN NUMERAL FOUR
//		"iv" => "ⅳ",
		// "<U0069><U0076>" <U2173>

		// SMALL ROMAN NUMERAL FIVE
//		"v" => "ⅴ",
		// <U0076> <U2174>

		// SMALL ROMAN NUMERAL SIX
//		"vi" => "ⅵ",
		// "<U0076><U0069>" <U2175>

		// SMALL ROMAN NUMERAL SEVEN
//		"vii" => "ⅶ",
		// "<U0076><U0069><U0069>" <U2176>

		// SMALL ROMAN NUMERAL EIGHT
//		"viii" => "ⅷ",
		// "<U0076><U0069><U0069><U0069>" <U2177>

		// SMALL ROMAN NUMERAL NINE
//		"ix" => "ⅸ",
		// "<U0069><U0078>" <U2178>

		// SMALL ROMAN NUMERAL TEN
//		"x" => "ⅹ",
		// <U0078> <U2179>

		// SMALL ROMAN NUMERAL ELEVEN
//		"xi" => "ⅺ",
		// "<U0078><U0069>" <U217A>

		// SMALL ROMAN NUMERAL TWELVE
//		"xii" => "ⅻ",
		// "<U0078><U0069><U0069>" <U217B>

		// SMALL ROMAN NUMERAL FIFTY
//		"l" => "ⅼ",
		// <U006C> <U217C>

		// SMALL ROMAN NUMERAL ONE HUNDRED
//		"c" => "ⅽ",
		// <U0063> <U217D>

		// SMALL ROMAN NUMERAL FIVE HUNDRED
//		"d" => "ⅾ",
		// <U0064> <U217E>

		// SMALL ROMAN NUMERAL ONE THOUSAND
//		"m" => "ⅿ",
		// <U006D> <U217F>

		// LEFTWARDS ARROW
		"\\{\\\\textleftarrow\\}" => "←",
		// "<U003C><U002D>" <U2190>

		// UPWARDS ARROW
		"\\{\\\\textuparrow\\}" => "↑",
		// <U005E> <U2191>

		// RIGHTWARDS ARROW
		"\\{\\\\textrightarrow\\}" => "→",
		// "<U002D><U003E>" <U2192>

		// DOWNWARDS ARROW
		"\\{\\\\textdownarrow\\}" => "↓",
		// <U0076> <U2193>

		// LEFT RIGHT ARROW
//		"<->" => "↔",
		// "<U003C><U002D><U003E>" <U2194>

		// LEFTWARDS DOUBLE ARROW
//		"<=" => "⇐",
		// "<U003C><U003D>" <U21D0>

		// RIGHTWARDS DOUBLE ARROW
//		"=>" => "⇒",
		// "<U003D><U003E>" <U21D2>

		// LEFT RIGHT DOUBLE ARROW
//		"<=>" => "⇔",
		// "<U003C><U003D><U003E>" <U21D4>

		// MINUS SIGN
//		"-" => "−", // "–"
		// <U2013>;<U002D> <U2212>

		// DIVISION SLASH
//		"/" => "∕",
		// <U002F> <U2215>

		// SET MINUS
//		"\\\\" => "∖",
		// <U005C> <U2216>

		// ASTERISK OPERATOR
//		"*" => "∗",
		// <U002A> <U2217>

		// RING OPERATOR
//		"o" => "∘",
		// <U006F> <U2218>

		// BULLET OPERATOR
//		"." => "∙", // "·"
		// <U00B7> <U2219>

		// INFINITY
		'\\$\\\\infty\\$' => "∞",
		// "<U0069><U006E><U0066>" <U221E>

		// DIVIDES
//		"|" => "∣",
		// <U007C> <U2223>

		// PARALLEL TO
//		"||" => "∥",
		// "<U007C><U007C>" <U2225>

		// RATIO
//		":" => "∶",
		// <U003A> <U2236>

		// TILDE OPERATOR
//		"\\\\~(\\{\\})?" => "∼",
		// <U007E> <U223C>

		// NOT EQUAL TO
//		"/=" => "≠",
		// "<U002F><U003D>" <U2260>

		// IDENTICAL TO
//		"=" => "≡",
		// <U003D> <U2261>

		// LESS-THAN OR EQUAL TO
//		"<=" => "≤",
		// "<U003C><U003D>" <U2264>

		// GREATER-THAN OR EQUAL TO
//		">=" => "≥",
		// "<U003E><U003D>" <U2265>

		// MUCH LESS-THAN
//		"<<" => "≪",
		// "<U003C><U003C>" <U226A>

		// MUCH GREATER-THAN
//		">>" => "≫",
		// "<U003E><U003E>" <U226B>

		// CIRCLED PLUS
//		"(+)" => "⊕",
		// "<U0028><U002B><U0029>" <U2295>

		// CIRCLED MINUS
//		"(-)" => "⊖",
		// "<U0028><U002D><U0029>" <U2296>

		// CIRCLED TIMES
//		"(x)" => "⊗",
		// "<U0028><U0078><U0029>" <U2297>

		// CIRCLED DIVISION SLASH
//		"(/)" => "⊘",
		// "<U0028><U002F><U0029>" <U2298>

		// RIGHT TACK
//		"|-" => "⊢",
		// "<U007C><U002D>" <U22A2>

		// LEFT TACK
//		"-|" => "⊣",
		// "<U002D><U007C>" <U22A3>

		// ASSERTION
//		"|-" => "⊦",
		// "<U007C><U002D>" <U22A6>

		// MODELS
//		"|=" => "⊧",
		// "<U007C><U003D>" <U22A7>

		// TRUE
//		"|=" => "⊨",
		// "<U007C><U003D>" <U22A8>

		// FORCES
//		"||-" => "⊩",
		// "<U007C><U007C><U002D>" <U22A9>

		// DOT OPERATOR
//		"." => "⋅", // "·"
		// <U00B7> <U22C5>

		// STAR OPERATOR
//		"*" => "⋆",
		// <U002A> <U22C6>

		// EQUAL AND PARALLEL TO
//		'\\$\\\\#\\$' => "⋕",
		// <U0023> <U22D5>

		// VERY MUCH LESS-THAN
//		"<<<" => "⋘",
		// "<U003C><U003C><U003C>" <U22D8>

		// VERY MUCH GREATER-THAN
//		">>>" => "⋙",
		// "<U003E><U003E><U003E>" <U22D9>

		// MIDLINE HORIZONTAL ELLIPSIS
//		"..." => "⋯",
		// "<U002E><U002E><U002E>" <U22EF>

		// LEFT-POINTING ANGLE BRACKET
		"\\{\\\\textlangle\\}" => "〈",
		// <U003C> <U2329>

		// RIGHT-POINTING ANGLE BRACKET
		"\\{\\\\textrangle\\}" => "〉",
		// <U003E> <U232A>

		// SYMBOL FOR NULL
//		"NUL" => "␀",
		// "<U004E><U0055><U004C>" <U2400>

		// SYMBOL FOR START OF HEADING
//		"SOH" => "␁",
		// "<U0053><U004F><U0048>" <U2401>

		// SYMBOL FOR START OF TEXT
//		"STX" => "␂",
		// "<U0053><U0054><U0058>" <U2402>

		// SYMBOL FOR END OF TEXT
//		"ETX" => "␃",
		// "<U0045><U0054><U0058>" <U2403>

		// SYMBOL FOR END OF TRANSMISSION
//		"EOT" => "␄",
		// "<U0045><U004F><U0054>" <U2404>

		// SYMBOL FOR ENQUIRY
//		"ENQ" => "␅",
		// "<U0045><U004E><U0051>" <U2405>

		// SYMBOL FOR ACKNOWLEDGE
//		"ACK" => "␆",
		// "<U0041><U0043><U004B>" <U2406>

		// SYMBOL FOR BELL
//		"BEL" => "␇",
		// "<U0042><U0045><U004C>" <U2407>

		// SYMBOL FOR BACKSPACE
//		"BS" => "␈",
		// "<U0042><U0053>" <U2408>

		// SYMBOL FOR HORIZONTAL TABULATION
//		"HT" => "␉",
		// "<U0048><U0054>" <U2409>

		// SYMBOL FOR LINE FEED
//		"LF" => "␊",
		// "<U004C><U0046>" <U240A>

		// SYMBOL FOR VERTICAL TABULATION
//		"VT" => "␋",
		// "<U0056><U0054>" <U240B>

		// SYMBOL FOR FORM FEED
//		"FF" => "␌",
		// "<U0046><U0046>" <U240C>

		// SYMBOL FOR CARRIAGE RETURN
//		"CR" => "␍",
		// "<U0043><U0052>" <U240D>

		// SYMBOL FOR SHIFT OUT
//		"SO" => "␎",
		// "<U0053><U004F>" <U240E>

		// SYMBOL FOR SHIFT IN
//		"SI" => "␏",
		// "<U0053><U0049>" <U240F>

		// SYMBOL FOR DATA LINK ESCAPE
//		"DLE" => "␐",
		// "<U0044><U004C><U0045>" <U2410>

		// SYMBOL FOR DEVICE CONTROL ONE
//		"DC1" => "␑",
		// "<U0044><U0043><U0031>" <U2411>

		// SYMBOL FOR DEVICE CONTROL TWO
//		"DC2" => "␒",
		// "<U0044><U0043><U0032>" <U2412>

		// SYMBOL FOR DEVICE CONTROL THREE
//		"DC3" => "␓",
		// "<U0044><U0043><U0033>" <U2413>

		// SYMBOL FOR DEVICE CONTROL FOUR
//		"DC4" => "␔",
		// "<U0044><U0043><U0034>" <U2414>

		// SYMBOL FOR NEGATIVE ACKNOWLEDGE
//		"NAK" => "␕",
		// "<U004E><U0041><U004B>" <U2415>

		// SYMBOL FOR SYNCHRONOUS IDLE
//		"SYN" => "␖",
		// "<U0053><U0059><U004E>" <U2416>

		// SYMBOL FOR END OF TRANSMISSION BLOCK
//		"ETB" => "␗",
		// "<U0045><U0054><U0042>" <U2417>

		// SYMBOL FOR CANCEL
//		"CAN" => "␘",
		// "<U0043><U0041><U004E>" <U2418>

		// SYMBOL FOR END OF MEDIUM
//		"EM" => "␙",
		// "<U0045><U004D>" <U2419>

		// SYMBOL FOR SUBSTITUTE
//		"SUB" => "␚",
		// "<U0053><U0055><U0042>" <U241A>

		// SYMBOL FOR ESCAPE
//		"ESC" => "␛",
		// "<U0045><U0053><U0043>" <U241B>

		// SYMBOL FOR FILE SEPARATOR
//		"FS" => "␜",
		// "<U0046><U0053>" <U241C>

		// SYMBOL FOR GROUP SEPARATOR
//		"GS" => "␝",
		// "<U0047><U0053>" <U241D>

		// SYMBOL FOR RECORD SEPARATOR
//		"RS" => "␞",
		// "<U0052><U0053>" <U241E>

		// SYMBOL FOR UNIT SEPARATOR
//		"US" => "␟",
		// "<U0055><U0053>" <U241F>

		// SYMBOL FOR SPACE
//		"SP" => "␠",
		// "<U0053><U0050>" <U2420>

		// SYMBOL FOR DELETE
//		"DEL" => "␡",
		// "<U0044><U0045><U004C>" <U2421>

		// OPEN BOX
		"\\{\\\\textvisiblespace\\}" => "␣",
		// <U005F> <U2423>

		// SYMBOL FOR NEWLINE
//		"NL" => "␤",
		// "<U004E><U004C>" <U2424>

		// SYMBOL FOR DELETE FORM TWO
//		"///" => "␥",
		// "<U002F><U002F><U002F>" <U2425>

		// SYMBOL FOR SUBSTITUTE FORM TWO
//		"?" => "␦",
		// <U003F> <U2426>

		// CIRCLED DIGIT ONE
//		"(1)" => "①", // "1"
		// "<U0028><U0031><U0029>";<U0031> <U2460>

		// CIRCLED DIGIT TWO
//		"(2)" => "②", // "2"
		// "<U0028><U0032><U0029>";<U0032> <U2461>

		// CIRCLED DIGIT THREE
//		"(3)" => "③", // "3"
		// "<U0028><U0033><U0029>";<U0033> <U2462>

		// CIRCLED DIGIT FOUR
//		"(4)" => "④", // "4"
		// "<U0028><U0034><U0029>";<U0034> <U2463>

		// CIRCLED DIGIT FIVE
//		"(5)" => "⑤", // "5"
		// "<U0028><U0035><U0029>";<U0035> <U2464>

		// CIRCLED DIGIT SIX
//		"(6)" => "⑥", // "6"
		// "<U0028><U0036><U0029>";<U0036> <U2465>

		// CIRCLED DIGIT SEVEN
//		"(7)" => "⑦", // "7"
		// "<U0028><U0037><U0029>";<U0037> <U2466>

		// CIRCLED DIGIT EIGHT
//		"(8)" => "⑧", // "8"
		// "<U0028><U0038><U0029>";<U0038> <U2467>

		// CIRCLED DIGIT NINE
//		"(9)" => "⑨", // "9"
		// "<U0028><U0039><U0029>";<U0039> <U2468>

		// CIRCLED NUMBER TEN
//		"(10)" => "⑩",
		// "<U0028><U0031><U0030><U0029>" <U2469>

		// CIRCLED NUMBER ELEVEN
//		"(11)" => "⑪",
		// "<U0028><U0031><U0031><U0029>" <U246A>

		// CIRCLED NUMBER TWELVE
//		"(12)" => "⑫",
		// "<U0028><U0031><U0032><U0029>" <U246B>

		// CIRCLED NUMBER THIRTEEN
//		"(13)" => "⑬",
		// "<U0028><U0031><U0033><U0029>" <U246C>

		// CIRCLED NUMBER FOURTEEN
//		"(14)" => "⑭",
		// "<U0028><U0031><U0034><U0029>" <U246D>

		// CIRCLED NUMBER FIFTEEN
//		"(15)" => "⑮",
		// "<U0028><U0031><U0035><U0029>" <U246E>

		// CIRCLED NUMBER SIXTEEN
//		"(16)" => "⑯",
		// "<U0028><U0031><U0036><U0029>" <U246F>

		// CIRCLED NUMBER SEVENTEEN
//		"(17)" => "⑰",
		// "<U0028><U0031><U0037><U0029>" <U2470>

		// CIRCLED NUMBER EIGHTEEN
//		"(18)" => "⑱",
		// "<U0028><U0031><U0038><U0029>" <U2471>

		// CIRCLED NUMBER NINETEEN
//		"(19)" => "⑲",
		// "<U0028><U0031><U0039><U0029>" <U2472>

		// CIRCLED NUMBER TWENTY
//		"(20)" => "⑳",
		// "<U0028><U0032><U0030><U0029>" <U2473>

		// PARENTHESIZED DIGIT ONE
//		"(1)" => "⑴", // "1"
		// "<U0028><U0031><U0029>";<U0031> <U2474>

		// PARENTHESIZED DIGIT TWO
//		"(2)" => "⑵", // "2"
		// "<U0028><U0032><U0029>";<U0032> <U2475>

		// PARENTHESIZED DIGIT THREE
//		"(3)" => "⑶", // "3"
		// "<U0028><U0033><U0029>";<U0033> <U2476>

		// PARENTHESIZED DIGIT FOUR
//		"(4)" => "⑷", // "4"
		// "<U0028><U0034><U0029>";<U0034> <U2477>

		// PARENTHESIZED DIGIT FIVE
//		"(5)" => "⑸", // "5"
		// "<U0028><U0035><U0029>";<U0035> <U2478>

		// PARENTHESIZED DIGIT SIX
//		"(6)" => "⑹", // "6"
		// "<U0028><U0036><U0029>";<U0036> <U2479>

		// PARENTHESIZED DIGIT SEVEN
//		"(7)" => "⑺", // "7"
		// "<U0028><U0037><U0029>";<U0037> <U247A>

		// PARENTHESIZED DIGIT EIGHT
//		"(8)" => "⑻", // "8"
		// "<U0028><U0038><U0029>";<U0038> <U247B>

		// PARENTHESIZED DIGIT NINE
//		"(9)" => "⑼", // "9"
		// "<U0028><U0039><U0029>";<U0039> <U247C>

		// PARENTHESIZED NUMBER TEN
//		"(10)" => "⑽",
		// "<U0028><U0031><U0030><U0029>" <U247D>

		// PARENTHESIZED NUMBER ELEVEN
//		"(11)" => "⑾",
		// "<U0028><U0031><U0031><U0029>" <U247E>

		// PARENTHESIZED NUMBER TWELVE
//		"(12)" => "⑿",
		// "<U0028><U0031><U0032><U0029>" <U247F>

		// PARENTHESIZED NUMBER THIRTEEN
//		"(13)" => "⒀",
		// "<U0028><U0031><U0033><U0029>" <U2480>

		// PARENTHESIZED NUMBER FOURTEEN
//		"(14)" => "⒁",
		// "<U0028><U0031><U0034><U0029>" <U2481>

		// PARENTHESIZED NUMBER FIFTEEN
//		"(15)" => "⒂",
		// "<U0028><U0031><U0035><U0029>" <U2482>

		// PARENTHESIZED NUMBER SIXTEEN
//		"(16)" => "⒃",
		// "<U0028><U0031><U0036><U0029>" <U2483>

		// PARENTHESIZED NUMBER SEVENTEEN
//		"(17)" => "⒄",
		// "<U0028><U0031><U0037><U0029>" <U2484>

		// PARENTHESIZED NUMBER EIGHTEEN
//		"(18)" => "⒅",
		// "<U0028><U0031><U0038><U0029>" <U2485>

		// PARENTHESIZED NUMBER NINETEEN
//		"(19)" => "⒆",
		// "<U0028><U0031><U0039><U0029>" <U2486>

		// PARENTHESIZED NUMBER TWENTY
//		"(20)" => "⒇",
		// "<U0028><U0032><U0030><U0029>" <U2487>

		// DIGIT ONE FULL STOP
//		"1." => "⒈", // "1"
		// "<U0031><U002E>";<U0031> <U2488>

		// DIGIT TWO FULL STOP
//		"2." => "⒉", // "2"
		// "<U0032><U002E>";<U0032> <U2489>

		// DIGIT THREE FULL STOP
//		"3." => "⒊", // "3"
		// "<U0033><U002E>";<U0033> <U248A>

		// DIGIT FOUR FULL STOP
//		"4." => "⒋", // "4"
		// "<U0034><U002E>";<U0034> <U248B>

		// DIGIT FIVE FULL STOP
//		"5." => "⒌", // "5"
		// "<U0035><U002E>";<U0035> <U248C>

		// DIGIT SIX FULL STOP
//		"6." => "⒍", // "6"
		// "<U0036><U002E>";<U0036> <U248D>

		// DIGIT SEVEN FULL STOP
//		"7." => "⒎", // "7"
		// "<U0037><U002E>";<U0037> <U248E>

		// DIGIT EIGHT FULL STOP
//		"8." => "⒏", // "8"
		// "<U0038><U002E>";<U0038> <U248F>

		// DIGIT NINE FULL STOP
//		"9." => "⒐", // "9"
		// "<U0039><U002E>";<U0039> <U2490>

		// NUMBER TEN FULL STOP
//		"10." => "⒑",
		// "<U0031><U0030><U002E>" <U2491>

		// NUMBER ELEVEN FULL STOP
//		"11." => "⒒",
		// "<U0031><U0031><U002E>" <U2492>

		// NUMBER TWELVE FULL STOP
//		"12." => "⒓",
		// "<U0031><U0032><U002E>" <U2493>

		// NUMBER THIRTEEN FULL STOP
//		"13." => "⒔",
		// "<U0031><U0033><U002E>" <U2494>

		// NUMBER FOURTEEN FULL STOP
//		"14." => "⒕",
		// "<U0031><U0034><U002E>" <U2495>

		// NUMBER FIFTEEN FULL STOP
//		"15." => "⒖",
		// "<U0031><U0035><U002E>" <U2496>

		// NUMBER SIXTEEN FULL STOP
//		"16." => "⒗",
		// "<U0031><U0036><U002E>" <U2497>

		// NUMBER SEVENTEEN FULL STOP
//		"17." => "⒘",
		// "<U0031><U0037><U002E>" <U2498>

		// NUMBER EIGHTEEN FULL STOP
//		"18." => "⒙",
		// "<U0031><U0038><U002E>" <U2499>

		// NUMBER NINETEEN FULL STOP
//		"19." => "⒚",
		// "<U0031><U0039><U002E>" <U249A>

		// NUMBER TWENTY FULL STOP
//		"20." => "⒛",
		// "<U0032><U0030><U002E>" <U249B>

		// PARENTHESIZED LATIN SMALL LETTER A
//		"(a)" => "⒜", // "a"
		// "<U0028><U0061><U0029>";<U0061> <U249C>

		// PARENTHESIZED LATIN SMALL LETTER B
//		"(b)" => "⒝", // "b"
		// "<U0028><U0062><U0029>";<U0062> <U249D>

		// PARENTHESIZED LATIN SMALL LETTER C
//		"(c)" => "⒞", // "c"
		// "<U0028><U0063><U0029>";<U0063> <U249E>

		// PARENTHESIZED LATIN SMALL LETTER D
//		"(d)" => "⒟", // "d"
		// "<U0028><U0064><U0029>";<U0064> <U249F>

		// PARENTHESIZED LATIN SMALL LETTER E
//		"(e)" => "⒠", // "e"
		// "<U0028><U0065><U0029>";<U0065> <U24A0>

		// PARENTHESIZED LATIN SMALL LETTER F
//		"(f)" => "⒡", // "f"
		// "<U0028><U0066><U0029>";<U0066> <U24A1>

		// PARENTHESIZED LATIN SMALL LETTER G
//		"(g)" => "⒢", // "g"
		// "<U0028><U0067><U0029>";<U0067> <U24A2>

		// PARENTHESIZED LATIN SMALL LETTER H
//		"(h)" => "⒣", // "h"
		// "<U0028><U0068><U0029>";<U0068> <U24A3>

		// PARENTHESIZED LATIN SMALL LETTER I
//		"(i)" => "⒤", // "i"
		// "<U0028><U0069><U0029>";<U0069> <U24A4>

		// PARENTHESIZED LATIN SMALL LETTER J
//		"(j)" => "⒥", // "j"
		// "<U0028><U006A><U0029>";<U006A> <U24A5>

		// PARENTHESIZED LATIN SMALL LETTER K
//		"(k)" => "⒦", // "k"
		// "<U0028><U006B><U0029>";<U006B> <U24A6>

		// PARENTHESIZED LATIN SMALL LETTER L
//		"(l)" => "⒧", // "l"
		// "<U0028><U006C><U0029>";<U006C> <U24A7>

		// PARENTHESIZED LATIN SMALL LETTER M
//		"(m)" => "⒨", // "m"
		// "<U0028><U006D><U0029>";<U006D> <U24A8>

		// PARENTHESIZED LATIN SMALL LETTER N
//		"(n)" => "⒩", // "n"
		// "<U0028><U006E><U0029>";<U006E> <U24A9>

		// PARENTHESIZED LATIN SMALL LETTER O
//		"(o)" => "⒪", // "o"
		// "<U0028><U006F><U0029>";<U006F> <U24AA>

		// PARENTHESIZED LATIN SMALL LETTER P
//		"(p)" => "⒫", // "p"
		// "<U0028><U0070><U0029>";<U0070> <U24AB>

		// PARENTHESIZED LATIN SMALL LETTER Q
//		"(q)" => "⒬", // "q"
		// "<U0028><U0071><U0029>";<U0071> <U24AC>

		// PARENTHESIZED LATIN SMALL LETTER R
//		"(r)" => "⒭", // "r"
		// "<U0028><U0072><U0029>";<U0072> <U24AD>

		// PARENTHESIZED LATIN SMALL LETTER S
//		"(s)" => "⒮", // "s"
		// "<U0028><U0073><U0029>";<U0073> <U24AE>

		// PARENTHESIZED LATIN SMALL LETTER T
//		"(t)" => "⒯", // "t"
		// "<U0028><U0074><U0029>";<U0074> <U24AF>

		// PARENTHESIZED LATIN SMALL LETTER U
//		"(u)" => "⒰", // "u"
		// "<U0028><U0075><U0029>";<U0075> <U24B0>

		// PARENTHESIZED LATIN SMALL LETTER V
//		"(v)" => "⒱", // "v"
		// "<U0028><U0076><U0029>";<U0076> <U24B1>

		// PARENTHESIZED LATIN SMALL LETTER W
//		"(w)" => "⒲", // "w"
		// "<U0028><U0077><U0029>";<U0077> <U24B2>

		// PARENTHESIZED LATIN SMALL LETTER X
//		"(x)" => "⒳", // "x"
		// "<U0028><U0078><U0029>";<U0078> <U24B3>

		// PARENTHESIZED LATIN SMALL LETTER Y
//		"(y)" => "⒴", // "y"
		// "<U0028><U0079><U0029>";<U0079> <U24B4>

		// PARENTHESIZED LATIN SMALL LETTER Z
//		"(z)" => "⒵", // "z"
		// "<U0028><U007A><U0029>";<U007A> <U24B5>

		// CIRCLED LATIN CAPITAL LETTER A
//		"(A)" => "Ⓐ", // "A"
		// "<U0028><U0041><U0029>";<U0041> <U24B6>

		// CIRCLED LATIN CAPITAL LETTER B
//		"(B)" => "Ⓑ", // "B"
		// "<U0028><U0042><U0029>";<U0042> <U24B7>

		// CIRCLED LATIN CAPITAL LETTER C
//		"(C)" => "Ⓒ", // "C"
		// "<U0028><U0043><U0029>";<U0043> <U24B8>

		// CIRCLED LATIN CAPITAL LETTER D
//		"(D)" => "Ⓓ", // "D"
		// "<U0028><U0044><U0029>";<U0044> <U24B9>

		// CIRCLED LATIN CAPITAL LETTER E
//		"(E)" => "Ⓔ", // "E"
		// "<U0028><U0045><U0029>";<U0045> <U24BA>

		// CIRCLED LATIN CAPITAL LETTER F
//		"(F)" => "Ⓕ", // "F"
		// "<U0028><U0046><U0029>";<U0046> <U24BB>

		// CIRCLED LATIN CAPITAL LETTER G
//		"(G)" => "Ⓖ", // "G"
		// "<U0028><U0047><U0029>";<U0047> <U24BC>

		// CIRCLED LATIN CAPITAL LETTER H
//		"(H)" => "Ⓗ", // "H"
		// "<U0028><U0048><U0029>";<U0048> <U24BD>

		// CIRCLED LATIN CAPITAL LETTER I
//		"(I)" => "Ⓘ", // "I"
		// "<U0028><U0049><U0029>";<U0049> <U24BE>

		// CIRCLED LATIN CAPITAL LETTER J
//		"(J)" => "Ⓙ", // "J"
		// "<U0028><U004A><U0029>";<U004A> <U24BF>

		// CIRCLED LATIN CAPITAL LETTER K
//		"(K)" => "Ⓚ", // "K"
		// "<U0028><U004B><U0029>";<U004B> <U24C0>

		// CIRCLED LATIN CAPITAL LETTER L
//		"(L)" => "Ⓛ", // "L"
		// "<U0028><U004C><U0029>";<U004C> <U24C1>

		// CIRCLED LATIN CAPITAL LETTER M
//		"(M)" => "Ⓜ", // "M"
		// "<U0028><U004D><U0029>";<U004D> <U24C2>

		// CIRCLED LATIN CAPITAL LETTER N
//		"(N)" => "Ⓝ", // "N"
		// "<U0028><U004E><U0029>";<U004E> <U24C3>

		// CIRCLED LATIN CAPITAL LETTER O
//		"(O)" => "Ⓞ", // "O"
		// "<U0028><U004F><U0029>";<U004F> <U24C4>

		// CIRCLED LATIN CAPITAL LETTER P
//		"(P)" => "Ⓟ", // "P"
		// "<U0028><U0050><U0029>";<U0050> <U24C5>

		// CIRCLED LATIN CAPITAL LETTER Q
//		"(Q)" => "Ⓠ", // "Q"
		// "<U0028><U0051><U0029>";<U0051> <U24C6>

		// CIRCLED LATIN CAPITAL LETTER R
//		"(R)" => "Ⓡ", // "R"
		// "<U0028><U0052><U0029>";<U0052> <U24C7>

		// CIRCLED LATIN CAPITAL LETTER S
//		"(S)" => "Ⓢ", // "S"
		// "<U0028><U0053><U0029>";<U0053> <U24C8>

		// CIRCLED LATIN CAPITAL LETTER T
//		"(T)" => "Ⓣ", // "T"
		// "<U0028><U0054><U0029>";<U0054> <U24C9>

		// CIRCLED LATIN CAPITAL LETTER U
//		"(U)" => "Ⓤ", // "U"
		// "<U0028><U0055><U0029>";<U0055> <U24CA>

		// CIRCLED LATIN CAPITAL LETTER V
//		"(V)" => "Ⓥ", // "V"
		// "<U0028><U0056><U0029>";<U0056> <U24CB>

		// CIRCLED LATIN CAPITAL LETTER W
//		"(W)" => "Ⓦ", // "W"
		// "<U0028><U0057><U0029>";<U0057> <U24CC>

		// CIRCLED LATIN CAPITAL LETTER X
//		"(X)" => "Ⓧ", // "X"
		// "<U0028><U0058><U0029>";<U0058> <U24CD>

		// CIRCLED LATIN CAPITAL LETTER Y
//		"(Y)" => "Ⓨ", // "Y"
		// "<U0028><U0059><U0029>";<U0059> <U24CE>

		// CIRCLED LATIN CAPITAL LETTER Z
//		"(Z)" => "Ⓩ", // "Z"
		// "<U0028><U005A><U0029>";<U005A> <U24CF>

		// CIRCLED LATIN SMALL LETTER A
//		"(a)" => "ⓐ", // "a"
		// "<U0028><U0061><U0029>";<U0061> <U24D0>

		// CIRCLED LATIN SMALL LETTER B
//		"(b)" => "ⓑ", // "b"
		// "<U0028><U0062><U0029>";<U0062> <U24D1>

		// CIRCLED LATIN SMALL LETTER C
//		"(c)" => "ⓒ", // "c"
		// "<U0028><U0063><U0029>";<U0063> <U24D2>

		// CIRCLED LATIN SMALL LETTER D
//		"(d)" => "ⓓ", // "d"
		// "<U0028><U0064><U0029>";<U0064> <U24D3>

		// CIRCLED LATIN SMALL LETTER E
//		"(e)" => "ⓔ", // "e"
		// "<U0028><U0065><U0029>";<U0065> <U24D4>

		// CIRCLED LATIN SMALL LETTER F
//		"(f)" => "ⓕ", // "f"
		// "<U0028><U0066><U0029>";<U0066> <U24D5>

		// CIRCLED LATIN SMALL LETTER G
//		"(g)" => "ⓖ", // "g"
		// "<U0028><U0067><U0029>";<U0067> <U24D6>

		// CIRCLED LATIN SMALL LETTER H
//		"(h)" => "ⓗ", // "h"
		// "<U0028><U0068><U0029>";<U0068> <U24D7>

		// CIRCLED LATIN SMALL LETTER I
//		"(i)" => "ⓘ", // "i"
		// "<U0028><U0069><U0029>";<U0069> <U24D8>

		// CIRCLED LATIN SMALL LETTER J
//		"(j)" => "ⓙ", // "j"
		// "<U0028><U006A><U0029>";<U006A> <U24D9>

		// CIRCLED LATIN SMALL LETTER K
//		"(k)" => "ⓚ", // "k"
		// "<U0028><U006B><U0029>";<U006B> <U24DA>

		// CIRCLED LATIN SMALL LETTER L
//		"(l)" => "ⓛ", // "l"
		// "<U0028><U006C><U0029>";<U006C> <U24DB>

		// CIRCLED LATIN SMALL LETTER M
//		"(m)" => "ⓜ", // "m"
		// "<U0028><U006D><U0029>";<U006D> <U24DC>

		// CIRCLED LATIN SMALL LETTER N
//		"(n)" => "ⓝ", // "n"
		// "<U0028><U006E><U0029>";<U006E> <U24DD>

		// CIRCLED LATIN SMALL LETTER O
//		"(o)" => "ⓞ", // "o"
		// "<U0028><U006F><U0029>";<U006F> <U24DE>

		// CIRCLED LATIN SMALL LETTER P
//		"(p)" => "ⓟ", // "p"
		// "<U0028><U0070><U0029>";<U0070> <U24DF>

		// CIRCLED LATIN SMALL LETTER Q
//		"(q)" => "ⓠ", // "q"
		// "<U0028><U0071><U0029>";<U0071> <U24E0>

		// CIRCLED LATIN SMALL LETTER R
//		"(r)" => "ⓡ", // "r"
		// "<U0028><U0072><U0029>";<U0072> <U24E1>

		// CIRCLED LATIN SMALL LETTER S
//		"(s)" => "ⓢ", // "s"
		// "<U0028><U0073><U0029>";<U0073> <U24E2>

		// CIRCLED LATIN SMALL LETTER T
//		"(t)" => "ⓣ", // "t"
		// "<U0028><U0074><U0029>";<U0074> <U24E3>

		// CIRCLED LATIN SMALL LETTER U
//		"(u)" => "ⓤ", // "u"
		// "<U0028><U0075><U0029>";<U0075> <U24E4>

		// CIRCLED LATIN SMALL LETTER V
//		"(v)" => "ⓥ", // "v"
		// "<U0028><U0076><U0029>";<U0076> <U24E5>

		// CIRCLED LATIN SMALL LETTER W
//		"(w)" => "ⓦ", // "w"
		// "<U0028><U0077><U0029>";<U0077> <U24E6>

		// CIRCLED LATIN SMALL LETTER X
//		"(x)" => "ⓧ", // "x"
		// "<U0028><U0078><U0029>";<U0078> <U24E7>

		// CIRCLED LATIN SMALL LETTER Y
//		"(y)" => "ⓨ", // "y"
		// "<U0028><U0079><U0029>";<U0079> <U24E8>

		// CIRCLED LATIN SMALL LETTER Z
//		"(z)" => "ⓩ", // "z"
		// "<U0028><U007A><U0029>";<U007A> <U24E9>

		// CIRCLED DIGIT ZERO
//		"(0)" => "⓪", // "0"
		// "<U0028><U0030><U0029>";<U0030> <U24EA>

		// BOX DRAWINGS LIGHT HORIZONTAL
//		"-" => "─",
		// <U002D> <U2500>

		// BOX DRAWINGS HEAVY HORIZONTAL
//		"=" => "━",
		// <U003D> <U2501>

		// BOX DRAWINGS LIGHT VERTICAL
//		"|" => "│",
		// <U007C> <U2502>

		// BOX DRAWINGS HEAVY VERTICAL
//		"|" => "┃",
		// <U007C> <U2503>

		// BOX DRAWINGS LIGHT TRIPLE DASH HORIZONTAL
//		"-" => "┄",
		// <U002D> <U2504>

		// BOX DRAWINGS HEAVY TRIPLE DASH HORIZONTAL
//		"=" => "┅",
		// <U003D> <U2505>

		// BOX DRAWINGS LIGHT TRIPLE DASH VERTICAL
//		"|" => "┆",
		// <U007C> <U2506>

		// BOX DRAWINGS HEAVY TRIPLE DASH VERTICAL
//		"|" => "┇",
		// <U007C> <U2507>

		// BOX DRAWINGS LIGHT QUADRUPLE DASH HORIZONTAL
//		"-" => "┈",
		// <U002D> <U2508>

		// BOX DRAWINGS HEAVY QUADRUPLE DASH HORIZONTAL
//		"=" => "┉",
		// <U003D> <U2509>

		// BOX DRAWINGS LIGHT QUADRUPLE DASH VERTICAL
//		"|" => "┊",
		// <U007C> <U250A>

		// BOX DRAWINGS HEAVY QUADRUPLE DASH VERTICAL
//		"|" => "┋",
		// <U007C> <U250B>

		// BOX DRAWINGS LIGHT DOWN AND RIGHT
//		"+" => "┌",
		// <U002B> <U250C>

		// BOX DRAWINGS DOWN LIGHT AND RIGHT HEAVY
//		"+" => "┍",
		// <U002B> <U250D>

		// BOX DRAWINGS DOWN HEAVY AND RIGHT LIGHT
//		"+" => "┎",
		// <U002B> <U250E>

		// BOX DRAWINGS HEAVY DOWN AND RIGHT
//		"+" => "┏",
		// <U002B> <U250F>

		// BOX DRAWINGS LIGHT DOWN AND LEFT
//		"+" => "┐",
		// <U002B> <U2510>

		// BOX DRAWINGS DOWN LIGHT AND LEFT HEAVY
//		"+" => "┑",
		// <U002B> <U2511>

		// BOX DRAWINGS DOWN HEAVY AND LEFT LIGHT
//		"+" => "┒",
		// <U002B> <U2512>

		// BOX DRAWINGS HEAVY DOWN AND LEFT
//		"+" => "┓",
		// <U002B> <U2513>

		// BOX DRAWINGS LIGHT UP AND RIGHT
//		"+" => "└",
		// <U002B> <U2514>

		// BOX DRAWINGS UP LIGHT AND RIGHT HEAVY
//		"+" => "┕",
		// <U002B> <U2515>

		// BOX DRAWINGS UP HEAVY AND RIGHT LIGHT
//		"+" => "┖",
		// <U002B> <U2516>

		// BOX DRAWINGS HEAVY UP AND RIGHT
//		"+" => "┗",
		// <U002B> <U2517>

		// BOX DRAWINGS LIGHT UP AND LEFT
//		"+" => "┘",
		// <U002B> <U2518>

		// BOX DRAWINGS UP LIGHT AND LEFT HEAVY
//		"+" => "┙",
		// <U002B> <U2519>

		// BOX DRAWINGS UP HEAVY AND LEFT LIGHT
//		"+" => "┚",
		// <U002B> <U251A>

		// BOX DRAWINGS HEAVY UP AND LEFT
//		"+" => "┛",
		// <U002B> <U251B>

		// BOX DRAWINGS LIGHT VERTICAL AND RIGHT
//		"+" => "├",
		// <U002B> <U251C>

		// BOX DRAWINGS VERTICAL LIGHT AND RIGHT HEAVY
//		"+" => "┝",
		// <U002B> <U251D>

		// BOX DRAWINGS UP HEAVY AND RIGHT DOWN LIGHT
//		"+" => "┞",
		// <U002B> <U251E>

		// BOX DRAWINGS DOWN HEAVY AND RIGHT UP LIGHT
//		"+" => "┟",
		// <U002B> <U251F>

		// BOX DRAWINGS VERTICAL HEAVY AND RIGHT LIGHT
//		"+" => "┠",
		// <U002B> <U2520>

		// BOX DRAWINGS DOWN LIGHT AND RIGHT UP HEAVY
//		"+" => "┡",
		// <U002B> <U2521>

		// BOX DRAWINGS UP LIGHT AND RIGHT DOWN HEAVY
//		"+" => "┢",
		// <U002B> <U2522>

		// BOX DRAWINGS HEAVY VERTICAL AND RIGHT
//		"+" => "┣",
		// <U002B> <U2523>

		// BOX DRAWINGS LIGHT VERTICAL AND LEFT
//		"+" => "┤",
		// <U002B> <U2524>

		// BOX DRAWINGS VERTICAL LIGHT AND LEFT HEAVY
//		"+" => "┥",
		// <U002B> <U2525>

		// BOX DRAWINGS UP HEAVY AND LEFT DOWN LIGHT
//		"+" => "┦",
		// <U002B> <U2526>

		// BOX DRAWINGS DOWN HEAVY AND LEFT UP LIGHT
//		"+" => "┧",
		// <U002B> <U2527>

		// BOX DRAWINGS VERTICAL HEAVY AND LEFT LIGHT
//		"+" => "┨",
		// <U002B> <U2528>

		// BOX DRAWINGS DOWN LIGHT AND LEFT UP HEAVY
//		"+" => "┩",
		// <U002B> <U2529>

		// BOX DRAWINGS UP LIGHT AND LEFT DOWN HEAVY
//		"+" => "┪",
		// <U002B> <U252A>

		// BOX DRAWINGS HEAVY VERTICAL AND LEFT
//		"+" => "┫",
		// <U002B> <U252B>

		// BOX DRAWINGS LIGHT DOWN AND HORIZONTAL
//		"+" => "┬",
		// <U002B> <U252C>

		// BOX DRAWINGS LEFT HEAVY AND RIGHT DOWN LIGHT
//		"+" => "┭",
		// <U002B> <U252D>

		// BOX DRAWINGS RIGHT HEAVY AND LEFT DOWN LIGHT
//		"+" => "┮",
		// <U002B> <U252E>

		// BOX DRAWINGS DOWN LIGHT AND HORIZONTAL HEAVY
//		"+" => "┯",
		// <U002B> <U252F>

		// BOX DRAWINGS DOWN HEAVY AND HORIZONTAL LIGHT
//		"+" => "┰",
		// <U002B> <U2530>

		// BOX DRAWINGS RIGHT LIGHT AND LEFT DOWN HEAVY
//		"+" => "┱",
		// <U002B> <U2531>

		// BOX DRAWINGS LEFT LIGHT AND RIGHT DOWN HEAVY
//		"+" => "┲",
		// <U002B> <U2532>

		// BOX DRAWINGS HEAVY DOWN AND HORIZONTAL
//		"+" => "┳",
		// <U002B> <U2533>

		// BOX DRAWINGS LIGHT UP AND HORIZONTAL
//		"+" => "┴",
		// <U002B> <U2534>

		// BOX DRAWINGS LEFT HEAVY AND RIGHT UP LIGHT
//		"+" => "┵",
		// <U002B> <U2535>

		// BOX DRAWINGS RIGHT HEAVY AND LEFT UP LIGHT
//		"+" => "┶",
		// <U002B> <U2536>

		// BOX DRAWINGS UP LIGHT AND HORIZONTAL HEAVY
//		"+" => "┷",
		// <U002B> <U2537>

		// BOX DRAWINGS UP HEAVY AND HORIZONTAL LIGHT
//		"+" => "┸",
		// <U002B> <U2538>

		// BOX DRAWINGS RIGHT LIGHT AND LEFT UP HEAVY
//		"+" => "┹",
		// <U002B> <U2539>

		// BOX DRAWINGS LEFT LIGHT AND RIGHT UP HEAVY
//		"+" => "┺",
		// <U002B> <U253A>

		// BOX DRAWINGS HEAVY UP AND HORIZONTAL
//		"+" => "┻",
		// <U002B> <U253B>

		// BOX DRAWINGS LIGHT VERTICAL AND HORIZONTAL
//		"+" => "┼",
		// <U002B> <U253C>

		// BOX DRAWINGS LEFT HEAVY AND RIGHT VERTICAL LIGHT
//		"+" => "┽",
		// <U002B> <U253D>

		// BOX DRAWINGS RIGHT HEAVY AND LEFT VERTICAL LIGHT
//		"+" => "┾",
		// <U002B> <U253E>

		// BOX DRAWINGS VERTICAL LIGHT AND HORIZONTAL HEAVY
//		"+" => "┿",
		// <U002B> <U253F>

		// BOX DRAWINGS UP HEAVY AND DOWN HORIZONTAL LIGHT
//		"+" => "╀",
		// <U002B> <U2540>

		// BOX DRAWINGS DOWN HEAVY AND UP HORIZONTAL LIGHT
//		"+" => "╁",
		// <U002B> <U2541>

		// BOX DRAWINGS VERTICAL HEAVY AND HORIZONTAL LIGHT
//		"+" => "╂",
		// <U002B> <U2542>

		// BOX DRAWINGS LEFT UP HEAVY AND RIGHT DOWN LIGHT
//		"+" => "╃",
		// <U002B> <U2543>

		// BOX DRAWINGS RIGHT UP HEAVY AND LEFT DOWN LIGHT
//		"+" => "╄",
		// <U002B> <U2544>

		// BOX DRAWINGS LEFT DOWN HEAVY AND RIGHT UP LIGHT
//		"+" => "╅",
		// <U002B> <U2545>

		// BOX DRAWINGS RIGHT DOWN HEAVY AND LEFT UP LIGHT
//		"+" => "╆",
		// <U002B> <U2546>

		// BOX DRAWINGS DOWN LIGHT AND UP HORIZONTAL HEAVY
//		"+" => "╇",
		// <U002B> <U2547>

		// BOX DRAWINGS UP LIGHT AND DOWN HORIZONTAL HEAVY
//		"+" => "╈",
		// <U002B> <U2548>

		// BOX DRAWINGS RIGHT LIGHT AND LEFT VERTICAL HEAVY
//		"+" => "╉",
		// <U002B> <U2549>

		// BOX DRAWINGS LEFT LIGHT AND RIGHT VERTICAL HEAVY
//		"+" => "╊",
		// <U002B> <U254A>

		// BOX DRAWINGS HEAVY VERTICAL AND HORIZONTAL
//		"+" => "╋",
		// <U002B> <U254B>

		// BOX DRAWINGS LIGHT DOUBLE DASH HORIZONTAL
//		"-" => "╌",
		// <U002D> <U254C>

		// BOX DRAWINGS HEAVY DOUBLE DASH HORIZONTAL
//		"=" => "╍",
		// <U003D> <U254D>

		// BOX DRAWINGS LIGHT DOUBLE DASH VERTICAL
//		"|" => "╎",
		// <U007C> <U254E>

		// BOX DRAWINGS HEAVY DOUBLE DASH VERTICAL
//		"|" => "╏",
		// <U007C> <U254F>

		// BOX DRAWINGS DOUBLE HORIZONTAL
//		"=" => "═",
		// <U003D> <U2550>

		// BOX DRAWINGS DOUBLE VERTICAL
//		"|" => "║",
		// <U007C> <U2551>

		// BOX DRAWINGS DOWN SINGLE AND RIGHT DOUBLE
//		"+" => "╒",
		// <U002B> <U2552>

		// BOX DRAWINGS DOWN DOUBLE AND RIGHT SINGLE
//		"+" => "╓",
		// <U002B> <U2553>

		// BOX DRAWINGS DOUBLE DOWN AND RIGHT
//		"+" => "╔",
		// <U002B> <U2554>

		// BOX DRAWINGS DOWN SINGLE AND LEFT DOUBLE
//		"+" => "╕",
		// <U002B> <U2555>

		// BOX DRAWINGS DOWN DOUBLE AND LEFT SINGLE
//		"+" => "╖",
		// <U002B> <U2556>

		// BOX DRAWINGS DOUBLE DOWN AND LEFT
//		"+" => "╗",
		// <U002B> <U2557>

		// BOX DRAWINGS UP SINGLE AND RIGHT DOUBLE
//		"+" => "╘",
		// <U002B> <U2558>

		// BOX DRAWINGS UP DOUBLE AND RIGHT SINGLE
//		"+" => "╙",
		// <U002B> <U2559>

		// BOX DRAWINGS DOUBLE UP AND RIGHT
//		"+" => "╚",
		// <U002B> <U255A>

		// BOX DRAWINGS UP SINGLE AND LEFT DOUBLE
//		"+" => "╛",
		// <U002B> <U255B>

		// BOX DRAWINGS UP DOUBLE AND LEFT SINGLE
//		"+" => "╜",
		// <U002B> <U255C>

		// BOX DRAWINGS DOUBLE UP AND LEFT
//		"+" => "╝",
		// <U002B> <U255D>

		// BOX DRAWINGS VERTICAL SINGLE AND RIGHT DOUBLE
//		"+" => "╞",
		// <U002B> <U255E>

		// BOX DRAWINGS VERTICAL DOUBLE AND RIGHT SINGLE
//		"+" => "╟",
		// <U002B> <U255F>

		// BOX DRAWINGS DOUBLE VERTICAL AND RIGHT
//		"+" => "╠",
		// <U002B> <U2560>

		// BOX DRAWINGS VERTICAL SINGLE AND LEFT DOUBLE
//		"+" => "╡",
		// <U002B> <U2561>

		// BOX DRAWINGS VERTICAL DOUBLE AND LEFT SINGLE
//		"+" => "╢",
		// <U002B> <U2562>

		// BOX DRAWINGS DOUBLE VERTICAL AND LEFT
//		"+" => "╣",
		// <U002B> <U2563>

		// BOX DRAWINGS DOWN SINGLE AND HORIZONTAL DOUBLE
//		"+" => "╤",
		// <U002B> <U2564>

		// BOX DRAWINGS DOWN DOUBLE AND HORIZONTAL SINGLE
//		"+" => "╥",
		// <U002B> <U2565>

		// BOX DRAWINGS DOUBLE DOWN AND HORIZONTAL
//		"+" => "╦",
		// <U002B> <U2566>

		// BOX DRAWINGS UP SINGLE AND HORIZONTAL DOUBLE
//		"+" => "╧",
		// <U002B> <U2567>

		// BOX DRAWINGS UP DOUBLE AND HORIZONTAL SINGLE
//		"+" => "╨",
		// <U002B> <U2568>

		// BOX DRAWINGS DOUBLE UP AND HORIZONTAL
//		"+" => "╩",
		// <U002B> <U2569>

		// BOX DRAWINGS VERTICAL SINGLE AND HORIZONTAL DOUBLE
//		"+" => "╪",
		// <U002B> <U256A>

		// BOX DRAWINGS VERTICAL DOUBLE AND HORIZONTAL SINGLE
//		"+" => "╫",
		// <U002B> <U256B>

		// BOX DRAWINGS DOUBLE VERTICAL AND HORIZONTAL
//		"+" => "╬",
		// <U002B> <U256C>

		// BOX DRAWINGS LIGHT ARC DOWN AND RIGHT
//		"+" => "╭",
		// <U002B> <U256D>

		// BOX DRAWINGS LIGHT ARC DOWN AND LEFT
//		"+" => "╮",
		// <U002B> <U256E>

		// BOX DRAWINGS LIGHT ARC UP AND LEFT
//		"+" => "╯",
		// <U002B> <U256F>

		// BOX DRAWINGS LIGHT ARC UP AND RIGHT
//		"+" => "╰",
		// <U002B> <U2570>

		// BOX DRAWINGS LIGHT DIAGONAL UPPER RIGHT TO LOWER LEFT
//		"/" => "╱",
		// <U002F> <U2571>

		// BOX DRAWINGS LIGHT DIAGONAL UPPER LEFT TO LOWER RIGHT
//		"\\\\" => "╲",
		// <U005C> <U2572>

		// BOX DRAWINGS LIGHT DIAGONAL CROSS
//		"X" => "╳",
		// <U0058> <U2573>

		// BOX DRAWINGS LIGHT LEFT AND HEAVY RIGHT
//		"-" => "╼",
		// <U002D> <U257C>

		// BOX DRAWINGS LIGHT UP AND HEAVY DOWN
//		"|" => "╽",
		// <U007C> <U257D>

		// BOX DRAWINGS HEAVY LEFT AND LIGHT RIGHT
//		"-" => "╾",
		// <U002D> <U257E>

		// BOX DRAWINGS HEAVY UP AND LIGHT DOWN
//		"|" => "╿",
		// <U007C> <U257F>

		// WHITE CIRCLE
//		"o" => "○",
		// <U006F> <U25CB>

		// WHITE BULLET
		"\\{\\\\textopenbullet\\}" => "◦"
		// <U006F> <U25E6>

		// BLACK STAR
//		"*" => "★",
		// <U002A> <U2605>

		// WHITE STAR
//		"*" => "☆",
		// <U002A> <U2606>

		// BALLOT BOX WITH X
//		"X" => "☒",
		// <U0058> <U2612>

		// SALTIRE
//		"X" => "☓",
		// <U0058> <U2613>

		// WHITE FROWNING FACE
//		":-(" => "☹",
		// "<U003A><U002D><U0028>" <U2639>

		// WHITE SMILING FACE
//		":-)" => "☺",
		// "<U003A><U002D><U0029>" <U263A>

		// BLACK SMILING FACE
//		"(-:" => "☻",
		// "<U0028><U002D><U003A>" <U263B>

		// MUSIC FLAT SIGN
//		"b" => "♭",
		// <U0062> <U266D>

		// MUSIC SHARP SIGN
//		'\\$\\\\#\\$' => "♯",
		// <U0023> <U266F>

		// UPPER BLADE SCISSORS
//		'\\$\\\\%<\\$' => "✁",
		// "<U0025><U003C>" <U2701>

		// BLACK SCISSORS
//		'\\$\\\\%<\\$' => "✂",
		// "<U0025><U003C>" <U2702>

		// LOWER BLADE SCISSORS
//		'\\$\\\\%<\\$' => "✃",
		// "<U0025><U003C>" <U2703>

		// WHITE SCISSORS
//		'\\$\\\\%<\\$' => "✄",
		// "<U0025><U003C>" <U2704>

		// VICTORY HAND
//		"V" => "✌",
		// <U0056> <U270C>

		// CHECK MARK
//		"v" => "✓", // "√"
		// <U221A> <U2713>

		// HEAVY CHECK MARK
//		"V" => "✔", // "√"
		// <U221A> <U2714>

		// MULTIPLICATION X
//		"x" => "✕",
		// <U0078> <U2715>

		// HEAVY MULTIPLICATION X
//		"x" => "✖",
		// <U0078> <U2716>

		// BALLOT X
//		"X" => "✗",
		// <U0058> <U2717>

		// HEAVY BALLOT X
//		"X" => "✘",
		// <U0058> <U2718>

		// OUTLINED GREEK CROSS
//		"+" => "✙",
		// <U002B> <U2719>

		// HEAVY GREEK CROSS
//		"+" => "✚",
		// <U002B> <U271A>

		// OPEN CENTRE CROSS
//		"+" => "✛",
		// <U002B> <U271B>

		// HEAVY OPEN CENTRE CROSS
//		"+" => "✜",
		// <U002B> <U271C>

		// LATIN CROSS
//		"+" => "✝",
		// <U002B> <U271D>

		// SHADOWED WHITE LATIN CROSS
//		"+" => "✞",
		// <U002B> <U271E>

		// OUTLINED LATIN CROSS
//		"+" => "✟",
		// <U002B> <U271F>

		// MALTESE CROSS
//		"+" => "✠",
		// <U002B> <U2720>

		// STAR OF DAVID
//		"*" => "✡",
		// <U002A> <U2721>

		// FOUR TEARDROP-SPOKED ASTERISK
//		"+" => "✢",
		// <U002B> <U2722>

		// FOUR BALLOON-SPOKED ASTERISK
//		"+" => "✣",
		// <U002B> <U2723>

		// HEAVY FOUR BALLOON-SPOKED ASTERISK
//		"+" => "✤",
		// <U002B> <U2724>

		// FOUR CLUB-SPOKED ASTERISK
//		"+" => "✥",
		// <U002B> <U2725>

		// BLACK FOUR POINTED STAR
//		"+" => "✦",
		// <U002B> <U2726>

		// WHITE FOUR POINTED STAR
//		"+" => "✧",
		// <U002B> <U2727>

		// STRESS OUTLINED WHITE STAR
//		"*" => "✩",
		// <U002A> <U2729>

		// CIRCLED WHITE STAR
//		"*" => "✪",
		// <U002A> <U272A>

		// OPEN CENTRE BLACK STAR
//		"*" => "✫",
		// <U002A> <U272B>

		// BLACK CENTRE WHITE STAR
//		"*" => "✬",
		// <U002A> <U272C>

		// OUTLINED BLACK STAR
//		"*" => "✭",
		// <U002A> <U272D>

		// HEAVY OUTLINED BLACK STAR
//		"*" => "✮",
		// <U002A> <U272E>

		// PINWHEEL STAR
//		"*" => "✯",
		// <U002A> <U272F>

		// SHADOWED WHITE STAR
//		"*" => "✰",
		// <U002A> <U2730>

		// HEAVY ASTERISK
//		"*" => "✱",
		// <U002A> <U2731>

		// OPEN CENTRE ASTERISK
//		"*" => "✲",
		// <U002A> <U2732>

		// EIGHT SPOKED ASTERISK
//		"*" => "✳",
		// <U002A> <U2733>

		// EIGHT POINTED BLACK STAR
//		"*" => "✴",
		// <U002A> <U2734>

		// EIGHT POINTED PINWHEEL STAR
//		"*" => "✵",
		// <U002A> <U2735>

		// SIX POINTED BLACK STAR
//		"*" => "✶",
		// <U002A> <U2736>

		// EIGHT POINTED RECTILINEAR BLACK STAR
//		"*" => "✷",
		// <U002A> <U2737>

		// HEAVY EIGHT POINTED RECTILINEAR BLACK STAR
//		"*" => "✸",
		// <U002A> <U2738>

		// TWELVE POINTED BLACK STAR
//		"*" => "✹",
		// <U002A> <U2739>

		// SIXTEEN POINTED ASTERISK
//		"*" => "✺",
		// <U002A> <U273A>

		// TEARDROP-SPOKED ASTERISK
//		"*" => "✻",
		// <U002A> <U273B>

		// OPEN CENTRE TEARDROP-SPOKED ASTERISK
//		"*" => "✼",
		// <U002A> <U273C>

		// HEAVY TEARDROP-SPOKED ASTERISK
//		"*" => "✽",
		// <U002A> <U273D>

		// SIX PETALLED BLACK AND WHITE FLORETTE
//		"*" => "✾",
		// <U002A> <U273E>

		// BLACK FLORETTE
//		"*" => "✿",
		// <U002A> <U273F>

		// WHITE FLORETTE
//		"*" => "❀",
		// <U002A> <U2740>

		// EIGHT PETALLED OUTLINED BLACK FLORETTE
//		"*" => "❁",
		// <U002A> <U2741>

		// CIRCLED OPEN CENTRE EIGHT POINTED STAR
//		"*" => "❂",
		// <U002A> <U2742>

		// HEAVY TEARDROP-SPOKED PINWHEEL ASTERISK
//		"*" => "❃",
		// <U002A> <U2743>

		// SNOWFLAKE
//		"*" => "❄",
		// <U002A> <U2744>

		// TIGHT TRIFOLIATE SNOWFLAKE
//		"*" => "❅",
		// <U002A> <U2745>

		// HEAVY CHEVRON SNOWFLAKE
//		"*" => "❆",
		// <U002A> <U2746>

		// SPARKLE
//		"*" => "❇",
		// <U002A> <U2747>

		// HEAVY SPARKLE
//		"*" => "❈",
		// <U002A> <U2748>

		// BALLOON-SPOKED ASTERISK
//		"*" => "❉",
		// <U002A> <U2749>

		// EIGHT TEARDROP-SPOKED PROPELLER ASTERISK
//		"*" => "❊",
		// <U002A> <U274A>

		// HEAVY EIGHT TEARDROP-SPOKED PROPELLER ASTERISK
//		"*" => "❋",
		// <U002A> <U274B>

		// LATIN SMALL LIGATURE FF
//		"ff" => "ﬀ",
		// "<U0066><U0066>" <UFB00>

		// LATIN SMALL LIGATURE FI
//		"fi" => "ﬁ",
		// "<U0066><U0069>" <UFB01>

		// LATIN SMALL LIGATURE FL
//		"fl" => "ﬂ",
		// "<U0066><U006C>" <UFB02>

		// LATIN SMALL LIGATURE FFI
//		"ffi" => "ﬃ",
		// "<U0066><U0066><U0069>" <UFB03>

		// LATIN SMALL LIGATURE FFL
//		"ffl" => "ﬄ",
		// "<U0066><U0066><U006C>" <UFB04>

		// LATIN SMALL LIGATURE LONG S T
//		"st" => "ﬅ", // "ſt"
		// "<U017F><U0074>";"<U0073><U0074>" <UFB05>

		// LATIN SMALL LIGATURE ST
//		"st" => "ﬆ"
		// "<U0073><U0074>" <UFB06>

	);

?>
