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
	function showPageFooter($HeaderString, $oldQuery)
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
               class="selected"        ><a href="index.php">References</a></li><li id="search"
                       ><a href="simple_search.php" title="search the main fields of the database">Simple Search</a></li>
                <li><a href="advanced_search.php" title="search all fields of the database">Advanced Search</a></li>
<?php

                // -------------------------------------------------------
                if (isset($_SESSION['user_permissions']) AND ereg("allow_add", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_add'...
                {
                // ... include a link to 'record.php?recordAction=add...':
?>

                <LI id="ca-watch"><a href="record.php?recordAction=add&amp;oldQuery=<? echo rawurlencode($oldQuery); ?>" title="add a record to the database">Add Record</a>
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
?>
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
<div class="portlet" id="p-nav">
          <h5>Navigation</h5>
<div class="pBody">

<ul>
              <li id="Seidman"><a
href="/index.php/Seidman_Group">Seidman Group</a>
              <ul>
                <li id="Tools"><a
href="/index.php/Category:Tools">Instruments</a></li>
                <li id="Research"><a
href="/index.php/Category:Research">Research</a></li>
                <li id="People"><a
href="/index.php/Category:People">People</a></li>
                <li id="Calendar"><a
href="http://arc.nucapt.northwestern.edu/phpicalendar/">Calendar</a></li>

              <li id="References"><a
href="http://arc.nucapt.northwestern.edu/refbase/">References</a></li>
              <li id="Visit"><a href="http://arc.nucapt.northwestern.edu/Visit">Visit</a></li>
              </ul></li>
              <li id="n-recentchanges"><a
href="/index.php/Special:Recentchanges">Recent changes</a></li>
              <li id="n-randompage"><a
href="/index.php/Special:Randompage">Random page</a></li>
            </ul>
          </div>
        </div>



<div class="portlet" id="p-tb">
          <h5>Search</h5>
          <div class="pBody">
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
          <h5>Tools</h5>
          <div class="pBody">
            <ul>
<?php

                // -------------------------------------------------------
                if (isset($_SESSION['user_permissions']) AND ereg("allow_add", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_add'...
                {
                // ... include a link to 'record.php?recordAction=add...':
?>

                <LI><a href="record.php?recordAction=add&amp;oldQuery=<? echo rawurlencode($oldQuery); ?>" title="add a record to the database">Add Record</a>
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
    <div id="f-poweredbyico"><a href="http://www.refbase.net/"><img
src="img/refbase_credit.gif" alt="refbase"
/></a></div>            <ul id="f-list">
<li><? echo date('j M Y'); ?></li>
                          <li id="f-about"><a 
href="/index.php/Seidman_Group" title="Seidman Group">NUCAPT: Northwestern University Center for Atom-Probe Tomography</a></li>                                                                                                    </ul>
      </div>    





</div>

<?php
	}
?>
