// Project:    Web Reference Database (refbase) <http://www.refbase.net>
// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
//             original author(s).
//
//             This code is distributed in the hope that it will be useful,
//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
//             License for more details.
//
// File:       ./javascript/common.js
// Repository: $HeadURL$
// Author(s):  Matthias Steffens <mailto:refbase@extracts.de> and
//             Richard Karnesky <mailto:karnesky@gmail.com>
//
// Created:    26-Aug-08, 21:10
// Modified:   $Date$
//             $Author$
//             $Revision$

// This file contains common JavaScript functions that are used
// within the refbase interface.


// ------------------------------------------------------------------

// Select (or deselect) all checkboxes (of 'name=formpart') on a page:
function checkall(val, formpart) {
	var x = 0;
	while(document.queryResults.elements[x]) {
		if(document.queryResults.elements[x].name == formpart) {
			document.queryResults.elements[x].checked = val;
		}
		x++;
	}
	toggleRadio('allRecs', 'selRecs', val);
}

// ------------------------------------------------------------------

// Show or hide the element with the given 'id':
function toggleVisibility(id, imgid, txtid, txt, upd) {
	var e = document.getElementById(id);
	var i = document.getElementById(imgid);
	var t = document.getElementById(txtid);
	var upd;
	if (upd === undefined) upd = true;
	if (e.style.display == 'block' || e.style.display == '') {
		if (upd) e.style.display = 'none';
		i.src = 'img/closed.gif';
		t.innerHTML = txt;
	}
	else {
		if (upd) e.style.display = 'block';
		i.src = 'img/open.gif';
		t.innerHTML = '';
	}
}

// ------------------------------------------------------------------

// Show or hide the element with the given 'id' using a slide effect:
// TODO: figure out how to invoke the changes to the toggle image & text
//       (done via function 'toggleVisibility()') only *after* the slide
//       effect has been finished
// 
// Requires the Prototype & script.aculo.us JavaScript frameworks:
//   <http://www.prototypejs.org/> and <http://script.aculo.us/>
// 
// More info about 'Effect.toggle':
//   <http://github.com/madrobby/scriptaculous/wikis/effect-toggle>
function toggleVisibilitySlide(id, imgid, txtid, txt) {
	Effect.toggle(id, 'blind', {duration:0.4});
	toggleVisibility(id, imgid, txtid, txt, false);
}

// ------------------------------------------------------------------

// Select one of two radio buttons (and deselect the other):
// ('val=true' selects radio button 'id2' and deselects 'id1')
function toggleRadio(id1, id2, val) {
	document.getElementById(id1).checked = !(val);
	document.getElementById(id2).checked = val;
}

// ------------------------------------------------------------------

// Update the state of the "All Found Records" & "Selected Records"
// radio buttons based on whether any records where marked on a page:
function updateAllRecs() {
	var val=!eval("document.getElementById('allRecs').checked");
	var x=0;
	var checked=0;
	while(document.queryResults.elements[x]) {
		if(document.queryResults.elements[x].name == "marked[]") {
			if (eval("document.queryResults.elements[x].checked")) {
				checked++;
			}
		}
		x++;
	}
	if (checked>0) {
		val=true;
	} else {
		val=false;
	}
	toggleRadio('allRecs', 'selRecs', val);
}

// ------------------------------------------------------------------

// This is the callback function that gets called by the script.aculo.us
// 'Ajax.Autocompleter'. If it makes sense, this function modifies the suggest
// query such that the given search term ('entry') is prefixed with a CQL index
// ('selectedField') and a default relation ('all').
// 
// Requires the Prototype & script.aculo.us JavaScript frameworks:
//   <http://www.prototypejs.org/> and <http://script.aculo.us/>
// 
// More info about 'Ajax.Autocompleter':
//   <http://github.com/madrobby/scriptaculous/wikis/ajax-autocompleter>
function addCQLIndex(element, entry) {
	// NOTE: this 'if' block is a hack, see note above function 'buildSuggestElements()'
	//       in 'include.inc.php'
	if (entry.match(/^(id|col)-\w+-\w+=/) != null) {
		if (entry.match(/^id-\w+-\w+=/) != null) {
			// extract the ID of the HTML form element that contains the selected field:
			var selectedFieldID = entry.replace(/^id-(\w+)-\w+=.*/, "$1");
			// get the the name of the field that's currently selected in the
			// specified HTML form element:
			var selectedField = document.getElementById(selectedFieldID).value;
		}
		else if (entry.match(/^col-\w+-\w+=/) != null) {
			// extract the column/field name (that was passed literally):
			var selectedField = entry.replace(/^col-(\w+)-\w+=.*/, "$1");
		}
		// re-establish the correct query parameter name & value:
		entry = entry.replace(/^(id|col)-\w+-/, "");
	}
	else
		var selectedField = "keywords"; // fallback
	// NOTES: - we may need to exclude the 'abstract' index here until there's a smarter
	//          solution to present search suggestions from the abstract (currently, for
	//          each match, full or partial sentences from the abstract will be returned)
	//        - ATM, the special index 'main_fields' only works for search suggestions
	var CQLIndexes = " author title type year publication abbrev_journal volume issue"
	               + " pages keywords abstract address corporate_author thesis publisher"
	               + " place editor language summary_language orig_title series_editor"
	               + " series_title abbrev_series_title series_volume series_issue"
	               + " edition issn isbn medium area expedition conference notes"
	               + " approved location call_number serial marked copy selected"
	               + " user_keys user_notes user_file user_groups cite_key related"
	               + " file url doi contribution_id online_publication online_citation"
	               + " orig_record created_by modified_by main_fields ";
	if (CQLIndexes.search("\\b" + selectedField + "\\b") > 0)
		entry = entry.replace("=", "=" + selectedField + "%20all%20");
	return entry;
}

// ------------------------------------------------------------------
