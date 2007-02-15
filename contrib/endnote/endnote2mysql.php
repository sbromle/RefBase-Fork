<?php
  // Project:    Web Reference Database (refbase) <http://www.refbase.net>
  // Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
  //             original author.
  //
  //             This code is distributed in the hope that it will be useful,
  //             but WITHOUT ANY WARRANTY.  Please see the GNU General Public
  //             License for more details.
  //
  // File:       ./contrib/endnote/endnote2mysql.php
  // Author:     Richard Karnesky <mailto:karnesky@northwestern.edu>
  //
  // Created:    18-Mar-05, 17:10
  // Modified:   19-Mar-05, 13:50

  // WARNING
  // This hasn't been tested extensively & can cause weirdness.  Give the data
  // a once over in a spreadsheet to confirm it looks OK before import!!!
  
  // This processes the text file produced by using refbase.ens in Endnote:
  //  - Fixes linefeeds, which Endnote can't handle well:
  //    - trims carriage returns
  //    - replaces newlines with '\n'
  //    - trims '<REFBASE>' from the start of each citation
  //    - replaces '</REFBASE>' with a newline character
  //  - Replaces '<REFBASE J/>' with 'Journal' (a Field Name in Endnote)
  //  - Inserts '\N' between multiple tabs (to explicitly NULL empty fields)
  //  - Replaces '<Go to ISI>://*\t' with '\N' (bad URLs in Endnote)

  // TODO:
  //  - Tabs within fields aren't replaced!  This can screw things up!
  //  - Allow people to change import & export filenames
  //  - More fields (particularly all dates, first author, number of authors)
  //  - Better parsing of current fields (correct use of 'ed' vs 'eds)
  //  - Automatically import via mysql (intentionally unimplemented for safety)
  //  - Deprecate this whole darn mess by adding native import facilities ;-)
  
  $fin = file_get_contents('endnote.txt');
  if (!$fin) {
    echo "Error! Couldn't open endnote.txt.";
  }
  else {
    $fin = str_replace("\r","",$fin);
    $fin = str_replace("\n","\\n",$fin);
    $fin = str_replace("<REFBASE>","",$fin);
    $fin = str_replace("</REFBASE>\\n","\n",$fin);
    $fin = str_replace("<REFBASE J/>","Journal",$fin);
    $fin = preg_replace("/(?<=\t)(?=\t)/","\\N",$fin);
    $fin = preg_replace("/<Go to ISI>:\/\/\S*/","\\N",$fin);
  }
  do {
    if (!($fout = fopen('import.txt', "w"))) {
      $rc = 1; break;
    }
    if (!fwrite($fout, $fin)) {
      $rc = 2; break;
    }
    $rc = true;
  } while (0);
  if ($fout) {
    fclose($fout);
  }
?>
