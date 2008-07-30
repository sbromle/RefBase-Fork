<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/transtab_endnotexml_refbase.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    18-Jul-07, 13:15
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// Search & replace patterns for conversion from Endnote XML text style markup to refbase markup. Converts fontshape markup (italic, bold, underline) as well
	// as super- and subscript into appropriate refbase markup. Note that greek letters are left as is, so import of greek letters will require an UTF-8 database.
	// Notes: - search & replace patterns must be specified as perl-style regular expression and search patterns must include the leading & trailing slashes

	$transtab_endnotexml_refbase = array(

		'#<style face="italic"[^<>\r\n]*>(.+?)</style>#i'      => "_\\1_",
		'#<style face="bold"[^<>\r\n]*>(.+?)</style>#i'        => "**\\1**",
		'#<style face="underline"[^<>\r\n]*>(.+?)</style>#i'   => "__\\1__",
		'#<style face="superscript"[^<>\r\n]*>(.+?)</style>#i' => "[super:\\1]",
		'#<style face="subscript"[^<>\r\n]*>(.+?)</style>#i'   => "[sub:\\1]",
		'#<style face="[^<>"]*"[^<>\r\n]*>(.+?)</style>#i'     => "\\1", // remove all remaining <style> information

		// Bibutils 'endx2xml' v3.34 seems to require that titles are enclosed within a <style> container, so we put one back in:
		'#(?<=<title>)(.+?)(?=</title>)#i'                     => '<style face="normal" font="default" size="100%">' . "\\1" . '</style>', // title
		'#(?<=<secondary-title>)(.+?)(?=</secondary-title>)#i' => '<style face="normal" font="default" size="100%">' . "\\1" . '</style>', // secondary-title
		'#(?<=<full-title>)(.+?)(?=</full-title>)#i'           => '<style face="normal" font="default" size="100%">' . "\\1" . '</style>', // full-title
		'#(?<=<alt-title>)(.+?)(?=</alt-title>)#i'             => '<style face="normal" font="default" size="100%">' . "\\1" . '</style>', // alt-title

	);

?>
