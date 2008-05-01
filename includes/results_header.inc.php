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
	// TODO: I18n


	// --------------------------------------------------------------------

	function displayResultsHeader($href, $formElementsGroup, $formElementsRefine, $formElementsDisplayOptions, $displayType)
	{
		global $displayResultsHeaderDefault;

		global $loc; // defined in 'locales/core.php'

		$resultsHeaderToggleText = "Search &amp; Display Options";

		if (isset($displayResultsHeaderDefault[$displayType]) AND ($displayResultsHeaderDefault[$displayType] == "open"))
		{
			$resultsHeaderDisplayStyle = "block";
			$resultsHeaderToggleImage = "img/open.gif";
			$resultsHeaderInitialToggleText = "";
		}
		else
		{
			$resultsHeaderDisplayStyle = "none";
			$resultsHeaderToggleImage = "img/closed.gif";
			$resultsHeaderInitialToggleText = $resultsHeaderToggleText;
		}
?>

<div class="resultsheader">
<div class="showhide">
	<a href="#" onclick="toggleVisibility('resultoptions','resultsHeaderToggleimg','resultsHeaderToggletxt','<?php echo $resultsHeaderToggleText; ?>')" title="toggle visibility">
		<img id="resultsHeaderToggleimg" class="toggleimg" src="<?php echo $resultsHeaderToggleImage; ?>" alt="<?php echo $loc["LinkTitle_ToggleVisibility"]; ?>" width="9" height="9" hspace="0" border="0">
		<span id="resultsHeaderToggletxt" class="toggletxt"><?php echo $resultsHeaderInitialToggleText; ?></span>
	</a>
</div>
<div id="resultoptions" style="display: <?php echo $resultsHeaderDisplayStyle; ?>;">
	<div id="showgroup">
<?php echo $formElementsGroup; ?>
	</div>
	<div id="refineresults">
<?php echo $formElementsRefine; ?>
	</div>
	<div id="displayopt">
<?php echo $formElementsDisplayOptions; ?>
	</div>
</div>
</div><?php
	}
?>
