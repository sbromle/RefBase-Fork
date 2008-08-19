<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/transtab_unicode_charset.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    24-Jul-08, 17:00
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// Search & replace patterns and variables for matching (and conversion of) Unicode character case & classes.
	// Search & replace patterns must be specified as perl-style regular expression and search patterns must include the
	// leading & trailing slashes.

	// NOTE: Quote from <http://www.onphp5.com/article/22> ("i18n with PHP5: Pitfalls"):
	//       "PCRE and other regular expression extensions are not locale-aware. This most notably influences the \w class
	//        that is unable to work for Cyrillic letters. There could be a workaround for this if some preprocessor for the
	//        regex string could replace \w and friends with character range prior to calling PCRE functions."
	//       
	//       In case of a UTF-8 based system, Unicode character properties ("\p{...}" or "\P{...}") can be used instead of the
	//       normal and POSIX character classes. These are available since PHP 4.4.0 and PHP 5.1.0. Note that the use of Unicode
	//       properties requires the "/.../u" PCRE pattern modifier! More info:
	//       <http://www.php.net/manual/en/regexp.reference.php#regexp.reference.unicode>

	//       The variables '$alnum', '$alpha', '$cntrl', '$dash', '$digit', '$graph', '$lower', '$print', '$punct', '$space',
	//       '$upper', '$word' must be used within a perl-style regex character class and require the "/.../u" PCRE pattern modifier.

	// Matches Unicode letters & digits:
	$alnum = "\p{Ll}\p{Lu}\p{Lt}\p{Lo}\p{Nd}"; // Unicode-aware equivalent of "[:alnum:]"

	// Matches Unicode letters:
	$alpha = "\p{Ll}\p{Lu}\p{Lt}\p{Lo}"; // Unicode-aware equivalent of "[:alpha:]"

	// Matches Unicode control codes & characters not in other categories:
	$cntrl = "\p{C}"; // Unicode-aware equivalent of "[:cntrl:]"

	// Matches Unicode dashes & hyphens:
	$dash = "\p{Pd}";

	// Matches Unicode digits:
	$digit = "\p{Nd}"; // Unicode-aware equivalent of "[:digit:]"

	// Matches Unicode printing characters (excluding space):
	$graph = "^\p{C}\t\n\f\r\p{Z}"; // Unicode-aware equivalent of "[:graph:]"

	// Matches Unicode lower case letters:
	$lower = "\p{Ll}"; // Unicode-aware equivalent of "[:lower:]"

	// Matches Unicode printing characters (including space):
	$print = "\P{C}"; // same as "^\p{C}", Unicode-aware equivalent of "[:print:]"

	// Matches Unicode punctuation (printing characters excluding letters & digits):
	$punct = "\p{P}"; // Unicode-aware equivalent of "[:punct:]"

	// Matches Unicode whitespace (separating characters with no visual representation):
	$space = "\t\n\f\r\p{Z}"; // Unicode-aware equivalent of "[:space:]"

	// Matches Unicode upper case letters:
	$upper = "\p{Lu}\p{Lt}"; // Unicode-aware equivalent of "[:upper:]"

	// Matches Unicode "word" characters:
	$word = "_\p{Ll}\p{Lu}\p{Lt}\p{Lo}\p{Nd}"; // Unicode-aware equivalent of "[:word:]" (or "[:alnum:]" plus "_")

	// Defines the PCRE pattern modifier(s) to be used in conjunction with the above variables:
	// More info: <http://www.php.net/manual/en/reference.pcre.pattern.modifiers.php>
	$patternModifiers = "u"; // the "u" (PCRE_UTF8) pattern modifier causes PHP/PCRE to treat pattern strings as UTF-8


	// Converts Unicode upper case letters to their corresponding lower case letter:
	// TODO!
	$transtab_upper_lower = array(


	);


	// Converts Unicode lower case letters to their corresponding upper case letter:
	// TODO!
	$transtab_lower_upper = array(


	);

?>
