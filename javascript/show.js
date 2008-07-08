// Project:    Web Reference Database (refbase) <http://www.refbase.net>
// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
//             original author(s).
//
//             This code is distributed in the hope that it will be useful,
//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
//             License for more details.
//
// File:       ./javascript/show.js
// Repository: $HeadURL$
// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
//
// Created:    27-May-07, 00:54
// Modified:   $Date$
//             $Author$
//             $Revision$

// This Javascript allows to dynamically include references from a refbase database
// within your own HTML pages.

// Sample HTML pages that show how to call function 'showRefs()' from within your
// own HTML pages:
// <http://beta.refbase.net/include_test.html>
// <http://beta.refbase.net/javascript/include_test2.html>
// <http://beta.refbase.net/javascript/include_test3.html>

// More info is available in the refbase forums:
// <http://sourceforge.net/forum/message.php?msg_id=4404553>

// You'll need to adopt following variables to your needs:
// - variable 'authorNames' contains regex patterns for all authors whose names
//   shall be printed in bold face
// - variable 'url' in function 'showRefs()' must contain the URL to the refbase
//   'show.php' script on your own server
// - you may want to tweak other parameter settings in function 'showRefs' (e.g.
//   parameters 'showRows', 'citeStyle' or 'citeOrder')

// Also, please make sure that this javascript file is executable.

// AJAX-specific functions were adopted from sample code by Neil Saunders:
// <http://nsaunders.wordpress.com/2007/02/20/my-first-ajax-for-bioinformatics-page/>

// TODO: easier customization


var xmlHTTP;

// List of authors whose names will be printed in bold face (adopt to your needs):
// NOTES: - the given regex patterns must match the author string formatting of the
//          chosen citation style (see 'citeStyle' parameter in function 'showRefs')
//        - each regex pattern must be enclosed in parentheses
var authorNames = new Array(
                             /(Granskog, M\.)/g,
                             /(Mock, T\.)/g,
                             /(Spindler, M\.)/g,
                             /(Thomas, D\. N\.)/g
                           );

// ------------------------------------------------------------------

// Create refbase query URL:
// This function expects a query string of 'show.php' parameter=value pairs. More info about
// the 'show.php' API: <http://linking.refbase.net/>, <http://bibliographies.refbase.net/>
function showRefs(query) {
	if (query == "none") {
		document.getElementById("refs").innerHTML = "References will be listed here.";
	}
	else {
		var url = "http://beta.refbase.net/show.php"; // set URL to your server's own 'show.php' script
		url = url + "?"             + query;
		url = url + "&client="      + "inc-refbase-1.0"; // "inc-" indicates include mechanisms
		url = url + "&wrapResults=" + "0"; // output only a partial document structure containing solely the search results

		// set defaults if some params were not given in the query:
		// (empty param values trigger the database defaults)
		if (query.search(/submit=(Display|Cite|Export|Browse)?(?=&|$)/) == -1)
			url = url + "&submit=Cite"; // note that, currently, only 'submit=Cite' is fully supported

		if (query.search(/showLinks=[01]/) == -1)
			url = url + "&showLinks=1";

		if (query.search(/showRows=\d+/) == -1)
			url = url + "&showRows=100";

		if (query.search(/startRecord=\d+/) == -1)
			url = url + "&startRecord=1";

		if (query.search(/citeStyle=\w+/) == -1)
			url = url + "&citeStyle=APA"; // the specified citation style must have a matching entry within the 'styles' MySQL table

		if (query.search(/citeOrder=\w+/) == -1)
			url = url + "&citeOrder=year"; // possible values: "author", "year", "type", "type-year"

		if (query.search(/without=/) == -1)
			url = url + "&without=dups";

		getURL(url);
	}
}

// ------------------------------------------------------------------

// Print a string (that matches a pattern in 'authorNames') in bold face:
function highlightAuthors(sourceText) {
	for (var i = 0; i < authorNames.length; i++) {
		if (sourceText.search(authorNames[i]) > 0)
			sourceText = sourceText.replace(authorNames[i], "<b>$1</b>");
	}

	return sourceText;
}

// ------------------------------------------------------------------

// Send an XMLHTTP request:
function getURL(url) {
	xmlHTTP = getXMLHTTPObject();

	if (xmlHTTP == null) {
		alert ("Browser does not support HTTP Request");
		return;
	}

	xmlHTTP.onreadystatechange = stateChanged;
	xmlHTTP.open("GET", url, true);
	xmlHTTP.send(null);
}

// ------------------------------------------------------------------

// Update an HTML element (with id = "refs") with the response text returned by the XMLHTTP request:
function stateChanged() {
	document.getElementById("refs").innerHTML = "Fetching references from database... " + "<img src='../img/progress.gif'>";

	if (xmlHTTP.readyState == 4 || xmlHTTP.readyState == "complete") {
		var response = xmlHTTP.responseText;

		if (!response) {
			document.getElementById("refs").innerHTML = "No data returned!";
		}
		else {
			document.getElementById("refs").innerHTML = highlightAuthors(response);
		}
	}

}

// ------------------------------------------------------------------

// Create an XMLHTTP object:
function getXMLHTTPObject() {
	var xmlHTTP = null;

	try {
		// Firefox, Opera 8.0+, Safari:
		xmlHTTP = new XMLHttpRequest();
	}
	catch (e) {
		// Internet Explorer:
		try {
			xmlHTTP = new ActiveXObject("Msxml2.XMLHTTP");
		}
		catch (e) {
			xmlHTTP = new ActiveXObject("Microsoft.XMLHTTP");
		}
	}

	return xmlHTTP;
}

// ------------------------------------------------------------------
