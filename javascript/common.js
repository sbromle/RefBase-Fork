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
function toggleVisibility(id, imgid, txtid, txt) {
	var e = document.getElementById(id);
	var i = document.getElementById(imgid);
	var t = document.getElementById(txtid);
	if(e.style.display == 'block') {
		e.style.display = 'none';
		i.src = 'img/closed.gif';
		t.innerHTML = txt;
	}
	else {
		e.style.display = 'block';
		i.src = 'img/open.gif';
		t.innerHTML = '';
	}
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
