<?php
  // Project:    Web Reference Database (refbase) <http://www.refbase.net>
  // Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
  //             original author(s).
  //
  //             This code is distributed in the hope that it will be useful,
  //             but WITHOUT ANY WARRANTY. Please see the GNU General Public
  //             License for more details.
  //
  // File:       ./includes/openurl.inc.php
  // Repository: $HeadURL$
  // Author(s):  Richard Karnesky <mailto:karnesky@gmail.com>
  //
  // Created:    06-Sep-06, 16:30
  // Modified:   $Date$
  //             $Author$
  //             $Revision$

  // This include file contains functions that generate OpenURL and COinS data.
  // More info about the OpenURL standard (including pointers to further documentation) is available
  // at <http://en.wikipedia.org/wiki/OpenURL>. For more info about COinS, see <http://ocoins.info/>.

  // TODO: Multiple aus have the same array key, so we apped a number that is later stripped
  //       Cleanup if possible


  // Include refbase markup -> plain text search & replace patterns
  include 'includes/transtab_refbase_ascii.inc.php';

  function openURL($row) {
    global $openURLResolver; // these variables are defined in 'ini.inc.php'
    global $hostInstitutionAbbrevName;

    $co = contextObject($row); 
    $co["sid"] = "refbase:" . $hostInstitutionAbbrevName;

    $openURL = $openURLResolver;

    if (!ereg("\?", $openURLResolver))
      $openURL .= "?";
    else
      $openURL .= "&amp;";

    $openURL .= "ctx_ver=Z39.88-2004";

    foreach ($co as $coKey => $coValue) {
      $coKey = ereg_replace("rft.", "", $coKey);
      $coKey = ereg_replace("au[0-9]*", "au", $coKey);
      $openURL .= "&amp;" . $coKey . "=" . rawurlencode($coValue);
    }

    return $openURL;
  }

  function coins($row) {
    // fmt_info (type)
    $fmt = "info:ofi/fmt:kev:mtx:";
    // 'dissertation' is compatible with the 1.0 spec, but not the 0.1 spec
    if (!empty($row['thesis']))
      $fmt .= "dissertation";
    elseif (ereg("Journal", $row['type']))
      $fmt .= "journal";
    elseif (ereg("Book", $row['type']))
      $fmt .= "book";
    // 'dc' (dublin core) is compatible with the 1.0 spec, but not the 0.1 spec.
    // We default to this, as it is the most generic type.
    else
      $fmt .= "dc";

    $co = contextObject($row); 

    $coins = "ctx_ver=Z39.88-2004" . "&amp;rft_val_fmt=" . urlencode($fmt);

    foreach ($co as $coKey => $coValue) {
      // 'urlencode()' differs from 'rawurlencode() (i.e., RFC1738 encoding)
      // in that spaces are encoded as plus (+) signs
      $coKey = ereg_replace("au[0-9]*", "au", $coKey);
      $coins .= "&amp;" . $coKey . "=" . urlencode($coValue);
    }

    $coinsSpan = "<span class=\"Z3988\" title=\"" . $coins . "\"></span>";

    return $coinsSpan;
  }

  function contextObject($row) {
    global $databaseBaseURL; // defined in 'ini.inc.php'

    // The array '$transtab_refbase_ascii' contains search & replace patterns for
    // conversion from refbase markup to plain text
    global $transtab_refbase_ascii; // defined in 'transtab_refbase_ascii.inc.php'

    // Defines search & replace 'actions' that will be applied to all those
    // refbase fields that are listed in the corresponding 'fields' element:
    $plainTextSearchReplaceActionsArray = array(
      array(
        'fields'  => array("title", "address", "keywords", "abstract", "orig_title", "series_title", "abbrev_series_title", "notes", "publication"),
        'actions' => $transtab_refbase_ascii
      )
    );

    foreach ($row as $rowFieldName => $rowFieldValue)
      // Apply search & replace 'actions' to all fields that are listed in the 'fields'
      // element of the arrays contained in '$plainTextSearchReplaceActionsArray':
      foreach ($plainTextSearchReplaceActionsArray as $fieldActionsArray)
        if (in_array($rowFieldName, $fieldActionsArray['fields']))
          // function 'searchReplaceText()' is defined in 'include.inc.php'
          $row[$rowFieldName] = searchReplaceText($fieldActionsArray['actions'], $row[$rowFieldName], true);

    $co = array();

    // rfr_id
    $co["rfr_id"] = "info:sid/" . ereg_replace("http://", "", $databaseBaseURL);

    // genre (type)
    if (isset($row['type'])) {
      if ($row['type'] == "Journal Article")
        $co["rft.genre"] = "article";
      elseif ($row['type'] == "Book Chapter")
        $co["rft.genre"] = "bookitem";
      elseif ($row['type'] == "Book")
        $co["rft.genre"] = "book";
      elseif ($row['type'] == "Journal")
        $co["rft.genre"] = "journal";
    }

    // atitle, btitle, title (title, publication)
    if (($row['type'] == "Journal Article") || ($row['type'] == "Book Chapter")) {
      if (!empty($row['title']))
        $co["rft.atitle"] = $row['title'];
      if (!empty($row['publication'])) {
        $co["rft.title"] = $row['publication'];
        if ($row['type'] == "Book Chapter")
          $co["rft.btitle"] = $row['publication'];
      }
    }
    elseif (!empty($row['title']))
      $co["rft.title"] = $row['title'];
    if (($row['type'] == "Book Whole") && (!empty($row['title'])))
      $co["rft.btitle"] = $row['title'];

    // stitle (abbrev_journal)
    if (!empty($row['abbrev_journal']))
      $co["rft.stitle"] = $row['abbrev_journal'];

    // series (series_title)
    if (!empty($row['series_title']))
      $co["rft.series"] = $row['series_title'];

    // issn
    if (!empty($row['issn']))
      $co["rft.issn"] = $row['issn'];

    // isbn
    if (!empty($row['isbn']))
      $co["rft.isbn"] = $row['isbn'];

    // date (year)
    if (!empty($row['year']))
      $co["rft.date"] = $row['year'];

    // volume
    if (!empty($row['volume']))
      $co["rft.volume"] = $row['volume'];

    // issue
    if (!empty($row['issue']))
      $co["rft.issue"] = $row['issue'];

    // spage, epage, tpages (pages)
    // NOTE: lifted from modsxml.inc.php--should throw some into a new include file
    if (!empty($row['pages'])) {
      if (ereg("[0-9] *- *[0-9]", $row['pages'])) {
        list($pagestart, $pageend) = preg_split('/\s*[-]\s*/', $row['pages']);
        if ($pagestart < $pageend) {
          $co["rft.spage"] = $pagestart;
          $co["rft.epage"] = $pageend;
        }
      }
      elseif ($row['type'] == "Book Whole") {
        $pagetotal = preg_replace('/^(\d+)\s*pp?\.?$/', "\\1", $row['pages']);
        $co["rft.tpages"] = $pagetotal;
      }
      else
        $co["rft.spage"] = $row['pages'];
    }

    // aulast, aufirst, author (author)
    if (!empty($row['author'])) {
      $author = $row['author'];
      $aulast = extractAuthorsLastName(" *; *", " *, *", 1, $author);
      $aufirst = extractAuthorsGivenName(" *; *", " *, *", 1, $author);
      if (!empty($aulast))
        $co["rft.aulast"] = $aulast;
      if (!empty($aufirst))
        $co["rft.aufirst"] = $aufirst;
      // TODO: cleanup and put this function in include.inc.php?
      $authorcount = count(split(" *; *", $author));
      for ($i=0; $i < $authorcount-1; $i++){
        $aul = extractAuthorsLastName(" *; *", " *, *", $i+2, $author);
        $auf = extractAuthorsGivenName(" *; *", " *, *", $i+2, $author);
        if (!empty($aul)) {
          $au = $aul;
          if (!empty($auf))
            $au .= ", ";
        }
        if (!empty($auf))
          $au .= $auf;
        if (!empty($au))
          $co["rft.au".$i] = $au;
      }
    }

    // pub (publisher)
    if (!empty($row['publisher']))
      $co["rft.pub"] = $row['publisher'];

    // place
    if (!empty($row['place']))
      $co["rft.place"] = $row['place'];

    // id (doi, url)
    if (!empty($row['doi']))
      $co["rft_id"] = "info:doi/" . $row['doi'];
    elseif (!empty($row['url']))
      $co["rft_id"] = $row['url'];

    return $co;
  }
?>
