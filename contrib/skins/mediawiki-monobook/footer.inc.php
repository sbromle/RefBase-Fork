<?php
	// Copyright:  Richard Karnesky <karnesky@gmail.com>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./includes/footer.inc.php

	// --------------------------------------------------------------------

	// Inserts the closing HTML </body> and </html> tags:
	function displayHTMLfoot()
	{
?>

</body>
</html>
<?php
	}

	// --------------------------------------------------------------------

	// Displays the visible footer:
	function showPageFooter($HeaderString)
	{
		global $officialDatabaseName; // usage example: <a href="index.php">[? echo encodeHTML($officialDatabaseName); ?]</a>
		global $hostInstitutionAbbrevName; // usage example: <a href="[? echo $hostInstitutionURL; ?]">[? echo encodeHTML($hostInstitutionAbbrevName); ?] Home</a>
		global $hostInstitutionName; // (note: in the examples above, square brackets must be replaced by their respective angle brackets)
		global $hostInstitutionURL;
		global $helpResourcesURL;

		global $loginWelcomeMsg; // these variables are globally defined in function 'showLogin()' in 'include.inc.php'
		global $loginStatus;
		global $loginLinks;

		global $loc; // '$loc' is made globally available in 'core.php'
?>
</div></div></div>








      <div id="column-one">
        <div id="p-cactions" class="portlet">
          <h5>Views</h5>
          <ul>

            <li id="ca-talk"
               class="selected"        ><a href="index.php">References</a></li><li id="search"><a href="simple_search.php" title="search the main fields of the database">Simple Search</a></li>
                <li><a href="advanced_search.php" title="search all fields of the database">Advanced Search</a></li>
<?php

                // -------------------------------------------------------
                if (isset($_SESSION['user_permissions']) AND ereg("allow_add", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_add'...
                {
                // ... include a link to 'record.php?recordAction=add...':
?>

                <LI id="ca-watch"><a href="record.php?recordAction=add" title="add a record to the database">Add Record</a>
                <?php
                }
                else {?>
  <LI id="ca-watch"><a href="/refbase-svn/record.php?recordAction=add" title="add a suggested record to the database">Add Record (suggest)</a> 
                <?php }

                // -------------------------------------------------------
                if (isset($_SESSION['user_permissions']) AND ereg("(allow_import|allow_batch_import)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains either 'allow_import' or 'allow_batch_import'...
                {
                // ... include a link to 'import.php':
?>

                <LI><a href="import.php" title="import records">Import</a>
                <?php
                } else {
?>
<LI><a href="/refbase-svn/import.php" title="import suggested records">Import (suggest)</a> 
                <?php
                }?>
                       </ul>
        </div>





<div class="portlet" id="p-personal">
          <h5>Personal tools</h5>
<div class="pBody">
            <ul>
            <li id="pt-userpage"><? echo preg_replace('/You\'re logged in as<br>/','',$loginStatus); ?></li><li id="pt-mytalk">
<? echo $loginLinks; ?></li>
</ul>
          </div>
        </div>
<div class="portlet" id="p-logo">
          <a style="background-image:
url(../skins/common/images/wiki.png);"
            href="../index.php/Seidman_Group"
            title="Seidman Group"></a>
</div>
    <div class='portlet' id='p-Seidman_Group'>
    <h5>Seidman Group</h5>
    <div class='pBody'>

      <ul>
        <li id="n-Seidman-Group"><a href="/Seidman_Group">Seidman Group</a></li>
        <li id="n-Research"><a href="/Category:Research">Research</a></li>
        <li id="n-People"><a href="/Category:People">People</a></li>
      </ul>
    </div>
  </div>

    <div class='portlet' id='p-NUCAPT'>
    <h5>NUCAPT</h5>
    <div class='pBody'>
      <ul>
        <li id="n-Instruments"><a href="/Category:Tools">Instruments</a></li>
        <li id="n-Calendar"><a href="http://arc.nucapt.northwestern.edu/phpicalendar">Calendar</a></li>
        <li id="n-Visit"><a href="/Visit">Visit</a></li>

      </ul>
    </div>
  </div>
    <div class='portlet' id='p-Atom-Probe_Tomography'>
    <h5>Atom-Probe Tomography</h5>
    <div class='pBody'>
      <ul>
        <li id="n-References"><a href="http://arc.nucapt.northwestern.edu/refbase">References</a></li>

        <li id="n-AtomProbe-mailing-list"><a href="http://arc.nucapt.northwestern.edu/mailman/listinfo/atomprobe">AtomProbe mailing list</a></li>
      </ul>
    </div>
  </div>


<div class="portlet" id="p-tb">
          <h5>Search</h5>
          <div class="pBody">
<?php echo buildQuickSearchElements($query, $queryURL, $showQuery, $showLinks, $showRows, $citeStyle, $citeOrder, $displayType); ?>
            <ul>
                <li><a href="simple_search.php" title="search the main fields of the database">Simple Search</a>
                <li><a href="advanced_search.php" title="search all fields of the database">Advanced Search</a>
<?php

                // -------------------------------------------------------
                if (isset($_SESSION['user_permissions']) AND ereg("allow_sql_search", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_sql_search'...
                {
// ... include a link to 'sql_search.php':
?>              
 
                <li><a href="sql_search.php" title="search the database by use of a SQL query">SQL Search</a>
                <?php
                }

                // -------------------------------------------------------
?>
<li><a href="library_search.php" title="search the library of the <? echo encodeHTML($hostInstitutionAbbrevName); ?>">Library Search</a>
</ul>

        </div></div>
        <div class="portlet" id="p-tc">
          <h5>Toolbox</h5>
          <div class="pBody">
            <ul>
<?php

                // -------------------------------------------------------
                if (isset($_SESSION['user_permissions']) AND ereg("allow_add", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_add'...
                {
                // ... include a link to 'record.php?recordAction=add...':
?>

                <LI><a href="record.php?recordAction=add" title="add a record to the database">Add Record</a>
                <?php
                }

                // -------------------------------------------------------
                if (isset($_SESSION['user_permissions']) AND ereg("(allow_import|allow_batch_import)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains either 'allow_import' or 'allow_batch_import'...
                {
                // ... include a link to 'import.php':
?>

                <LI><a href="import.php" title="import records">Import</a>
                <?php
                }

                // -------------------------------------------------------
                if (isset($_SESSION['user_permissions']) AND ereg("allow_details_view", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_details_view'...
                {
                // ... include a link to 'show.php':
?>







   <LI><a href="show.php" title="display details for a particular record by entering its database serial number">Show Record</a>
                <?php
                }
 if (isset($_SESSION['user_permissions']) AND ereg("allow_cite", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_cite'...
                {
                // ... include a link to 'extract.php':
?>

                <LI><a href="extract.php" title="extract citations from a text and build an appropriate reference list">Extract Citations</a><?php
                }

                // -------------------------------------------------------
?>
</ul>
          </div></div></div>



      <div class="visualClear"></div>
      <div id="footer">
    <div id="f-poweredbyico"><a href="http://www.refbase.net/"><img src="img/refbase_credit.gif" alt="refbase"></a></div><ul id="f-list">
<li><? echo date('j M Y'); ?></li>
                          <li id="f-about"><a 
href="/index.php/Seidman_Group" title="Seidman Group">NUCAPT: Northwestern University Center for Atom-Probe Tomography</a></li>                                                                                                    </ul>
      </div>    





</div>

<?php
	}
?>
