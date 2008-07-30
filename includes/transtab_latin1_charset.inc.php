<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/transtab_latin1_charset.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    24-Jul-08, 17:45
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// Search & replace patterns and variables for matching (and conversion of) ISO-8859-1 character case & classes.
	// Search & replace patterns must be specified as perl-style regular expression and search patterns must include the
	// leading & trailing slashes.

	// NOTE: Quote from <http://www.onphp5.com/article/22> ("i18n with PHP5: Pitfalls"):
	//       "PCRE and other regular expression extensions are not locale-aware. This most notably influences the \w class
	//        that is unable to work for Cyrillic letters. There could be a workaround for this if some preprocessor for the
	//        regex string could replace \w and friends with character range prior to calling PCRE functions."

	//       The 'start_session()' function in file 'include.inc.php' should establish an appropriate locale via function
	//       'setSystemLocale()' so that e.g. '[[:upper:]]' would also match 'Ø' etc. However, since locale support depends
	//       on the individual server & system, we keep the workaround which literally specifies higher ASCII chars of the
	//       latin1 character set below. (in order to have this work, the character encoding of 'search.php' must be set to
	//       'Western (Iso Latin 1)' aka 'ISO-8859-1'!)
	//       higher ASCII chars upper case = "ÄÅÁÀÂÃÇÉÈÊËÑÖØÓÒÔÕÜÚÙÛÍÌÎÏÆ"
	//       higher ASCII chars lower case = "äåáàâãçéèêëñöøóòôõüúùûíìîïæÿß"

	//       The variables '$alnum', '$alpha', '$cntrl', '$digit', '$graph', '$lower', '$print', '$punct', '$space', '$upper',
	//       '$word' must be used within a perl-style regex character class.

	// Matches ISO-8859-1 letters & digits:
	$alnum = "[:alnum:]ÄÅÁÀÂÃÇÉÈÊËÑÖØÓÒÔÕÜÚÙÛÍÌÎÏÆäåáàâãçéèêëñöøóòôõüúùûíìîïæÿß";

	// Matches ISO-8859-1 letters:
	$alpha = "[:alpha:]ÄÅÁÀÂÃÇÉÈÊËÑÖØÓÒÔÕÜÚÙÛÍÌÎÏÆäåáàâãçéèêëñöøóòôõüúùûíìîïæÿß";

	// Matches ISO-8859-1 control characters:
	$cntrl = "[:cntrl:]";

	// Matches ISO-8859-1 digits:
	$digit = "[:digit:]";

	// Matches ISO-8859-1 printing characters (excluding space):
	$graph = "[:graph:]ÄÅÁÀÂÃÇÉÈÊËÑÖØÓÒÔÕÜÚÙÛÍÌÎÏÆäåáàâãçéèêëñöøóòôõüúùûíìîïæÿß";

	// Matches ISO-8859-1 lower case letters:
	$lower = "[:lower:]äåáàâãçéèêëñöøóòôõüúùûíìîïæÿß";

	// Matches ISO-8859-1 printing characters (including space):
	$print = "[:print:]ÄÅÁÀÂÃÇÉÈÊËÑÖØÓÒÔÕÜÚÙÛÍÌÎÏÆäåáàâãçéèêëñöøóòôõüúùûíìîïæÿß";

	// Matches ISO-8859-1 punctuation:
	$punct = "[:punct:]";

	// Matches ISO-8859-1 whitespace (separating characters with no visual representation):
	$space = "[:space:]";

	// Matches ISO-8859-1 upper case letters:
	$upper = "[:upper:]ÄÅÁÀÂÃÇÉÈÊËÑÖØÓÒÔÕÜÚÙÛÍÌÎÏÆ";

	// Matches ISO-8859-1 "word" characters:
	$word = "_[:alnum:]ÄÅÁÀÂÃÇÉÈÊËÑÖØÓÒÔÕÜÚÙÛÍÌÎÏÆäåáàâãçéèêëñöøóòôõüúùûíìîïæÿß";

	// Defines the PCRE pattern modifier(s) to be used in conjunction with the above variables:
	// More info: <http://www.php.net/manual/en/reference.pcre.pattern.modifiers.php>
	$patternModifiers = "";


	// Converts ISO-8859-1 upper case letters to their corresponding lower case letter:
	// TODO!
	$transtab_upper_lower = array(


	);


	// Converts ISO-8859-1 lower case letters to their corresponding upper case letter:
	// TODO!
	$transtab_lower_upper = array(


	);

?>
