<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/results_header.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    07-May-04, 14:38
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is the results header include file.
	// It contains functions that build the results header
	// which gets displayed on every search results page.


	// --------------------------------------------------------------------

	function displayResultsHeader($href, $formElementsGroup, $formElementsRefine, $formElementsDisplayOptions)
	{
		// adjust column width according to the calling script (which is either 'search.php' or 'users.php')
		if ($href == "users.php")
			$tdWidthLeftRight = "345";
		else // if ($href == "search.php") // use the default width
			$tdWidthLeftRight = "340"; // on OSX, Mozilla/Firefox needs at least 340 for the right column (using the german locale), WebKit-based browsers are fine with 310
?>

<table class="resultsheader" align="center" border="0" cellpadding="0" cellspacing="0" width="94%" summary="This table holds the results header">
<tr>
	<td width="<?php echo $tdWidthLeftRight; ?>">
<?php echo $formElementsGroup; ?>
	</td>
	<td align="center">
<?php echo $formElementsRefine; ?>
	</td>
	<td align="right" width="<?php echo $tdWidthLeftRight; ?>">
<?php echo $formElementsDisplayOptions; ?>
	</td>
</tr>
</table><?php
	}
?>
