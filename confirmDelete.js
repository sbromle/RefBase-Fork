	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./confirmDelete.js
	// Created:    6-Jan-03, 21:37
	// Modified:   6-Jan-03, 22:29

	// JavaScript functions that attempt
	// to provide some client-side validation
	// of user actions

function confirmDelete(submitAction)
{
	if (submitAction.value == 'Delete Record')
	{
		Check = confirm("Really DELETE this record?");
		if(Check == false)
			return false;
		else
			return true;
	}
	else
		return true;
}
