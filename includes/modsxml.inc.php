<?php
  // Project:    Web Reference Database (refbase) <http://www.refbase.net>
  // Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
  //             original author(s).
  //
  //             This code is distributed in the hope that it will be useful,
  //             but WITHOUT ANY WARRANTY. Please see the GNU General Public
  //             License for more details.
  //
  // File:       ./includes/modsxml.inc.php
  // Repository: $HeadURL$
  // Author(s):  Richard Karnesky <mailto:karnesky@gmail.com>
  //
  // Created:    02-Oct-04, 12:00
  // Modified:   $Date$
  //             $Author$
  //             $Revision$

  // This include file contains functions that'll export records to MODS XML.
  // Requires ActiveLink PHP XML Package, which is available under the GPL from:
  // <http://www.active-link.com/software/>


  // Incorporate some include files:
  include_once 'includes/transtab_refbase_unicode.inc.php'; // include refbase markup -> Unicode search & replace patterns

  // Import the ActiveLink Packages
  require_once("classes/include.php");
  import("org.active-link.xml.XML");
  import("org.active-link.xml.XMLDocument");


  // For more on MODS, see:
  //   <http://www.loc.gov/standards/mods/>
  //   <http://www.scripps.edu/~cdputnam/software/bibutils/>

  // TODO:
  //   Stuff in '// NOTE' comments
  //   There's a lot of overlap in the portions that depend on types.  I plan
  //     on refactoring this, so that they can make calls to the same function.

  // I don't know what to do with some fields
  // See <http://www.loc.gov/standards/mods/v3/mods-3-0-outline.html>
  //   - Require clever parsing
  //     - address (?name->affiliation?)
  //     - medium  (?typeOfResource?)
  //   - Don't know how refbase users use these
  //     - area (could be either topic or geographic, so we do nothing)
  //     - expedition


  // --------------------------------------------------------------------

  // Generates relatedItem branch for series
  function serialBranch($series_editor, $series_title, $abbrev_series_title,
                        $series_volume, $series_issue) {
    $series = new XMLBranch("relatedItem");
    $series->setTagAttribute("type", "series");

    // title
    if (!empty($series_title))
      $series->setTagContent($series_title, "relatedItem/titleInfo/title");

    // abbrev. title
    if (!empty($abbrev_series_title)) {
      $titleabbrev = NEW XMLBranch("titleInfo");
      $titleabbrev->setTagAttribute("type", "abbreviated");
      $titleabbrev->setTagContent($abbrev_series_title, "titleInfo/title");
      $series->addXMLBranch($titleabbrev);
    }

    // editor
    if (!empty($series_editor)) {
      if (ereg(" *\(eds?\)$", $series_editor))
        $series_editor = ereg_replace("[ \r\n]*\(eds?\)", "", $series_editor);
      $nameArray = separateNames("/\s*;\s*/", "/\s*,\s*/", " ", $series_editor,
                                 "personal", "editor");
      // NOTE: Ideally, the third parameter ('$betweenGivensDelim') of the
      //       'separateNames()' function would be also a 'preg_split' pattern
      //       (using the 'PREG_SPLIT_NO_EMPTY' flag) similar to this one:
      // 
      //       "/(?<=^|[$upper])\W+|(?<=^|[$upper])(?=$|[$upper])/$patternModifiers"
      // 
      //       This would allow to separate initials that aren't separated by
      //       any whitespace or punctuation (such as in "Steffens, MT").
      //       However, if the initials (full or given names) contain any higher
      //       ASCII chars, this would garble the initials/given names IF a
      //       latin1-based database is used AND variable '$convertExportDataToUTF8'
      //       in 'ini.inc.php' is set to "yes". This is since the splitting is
      //       currently done AFTER the person string has been converted to UTF-8.
      //       If there's no other solution (such as temporarily changing the
      //       system's locale to UTF-8?) splitting should be done *before*
      //       conversion to UTF-8. But then, person names must be converted to
      //       UTF-8 separately.
      foreach ($nameArray as $singleName)
        $series->addXMLBranch($singleName);
    }

    // volume, issue
    if ((!empty($series_volume)) || (!empty($series_issue))) {
      $part = new XMLBranch("part");
      if (!empty($series_volume)) {
        $detailvolume = new XMLBranch("detail");
        $detailvolume->setTagContent($series_volume, "detail/number");
        $detailvolume->setTagAttribute("type", "volume");
        $part->addXMLBranch($detailvolume);
      }
      if (!empty($series_issue)) {
        $detailnumber = new XMLBranch("detail");
        $detailnumber->setTagContent($series_issue, "detail/number");
        $detailnumber->setTagAttribute("type", "issue");
        $part->addXMLBranch($detailnumber);
      }
      $series->addXMLBranch($part);
    }

    return $series;
  }

  // --------------------------------------------------------------------

  // Separates people's names and then those names into their functional parts:
  //   {{Family1,{Given1-1,Given1-2}},{Family2,{Given2}}})
  // Adds these to an array of XMLBranches.
  function separateNames($betweenNamesDelim, $nameGivenDelim,
                         $betweenGivensDelim, $names, $type, $role) {
    $nameArray = array();
    $nameArray = preg_split($betweenNamesDelim, $names); // get a list of all authors
    foreach ($nameArray as $singleName){
      $nameBranch = new XMLBranch("name");
      $nameBranch->setTagAttribute("type", $type);

      if (preg_match($nameGivenDelim, $singleName))
        list($singleNameFamily, $singleNameGivens) = preg_split($nameGivenDelim,
                                                                $singleName);
      else {
        $singleNameFamily = $singleName;
        $singleNameGivens = "";
      }

      $nameFamilyBranch = new XMLBranch("namePart");
      $nameFamilyBranch->setTagAttribute("type", "family");
      $nameFamilyBranch->setTagContent($singleNameFamily);
      $nameBranch->addXMLBranch($nameFamilyBranch);

      if (!empty($singleNameGivens)) {
        $singleNameGivenArray = split($betweenGivensDelim, $singleNameGivens);
        // NOTE: see notes below first call to function 'separateNames()'
        foreach ($singleNameGivenArray as $singleNameGiven) {
          $nameGivenBranch = new XMLBranch("namePart");
          $nameGivenBranch->setTagAttribute("type", "given");
          $nameGivenBranch->setTagContent($singleNameGiven);
          $nameBranch->addXMLBranch($nameGivenBranch);
        }
      }

      $nameBranch->setTagContent($role,"name/role/roleTerm");
      $nameBranch->setTagAttribute("authority", "marcrelator",
                                     "name/role/roleTerm");
      $nameBranch->setTagAttribute("type", "text", "name/role/roleTerm");

      array_push($nameArray, $nameBranch);
    }
    return $nameArray;
  }

  // --------------------------------------------------------------------

  function modsCollection($result) {

    global $contentTypeCharset; // these variables are defined in 'ini.inc.php'
    global $convertExportDataToUTF8;

    global $citeKeysArray; // '$citeKeysArray' is made globally available from
                          // within this function

    // Individual records are objects and collections of records are strings

    $exportArray = array(); // array for individually exported records
    $citeKeysArray = array(); // array of cite keys (used to ensure uniqueness of
                             // cite keys among all exported records)

    // Generate the export for each record and push them onto an array:
    while ($row = @ mysql_fetch_array($result)) {
      // Export the current record as MODS XML
      $record = modsRecord($row);

      if (!empty($record)) // unless the record buffer is empty...
        array_push($exportArray, $record); // ...add it to an array of exports
    }

    $modsCollectionDoc = new XMLDocument();

    if (($convertExportDataToUTF8 == "yes") AND ($contentTypeCharset != "UTF-8"))
      $modsCollectionDoc->setEncoding("UTF-8");
    else
      $modsCollectionDoc->setEncoding($contentTypeCharset);

    $modsCollection = new XML("modsCollection");
    $modsCollection->setTagAttribute("xmlns", "http://www.loc.gov/mods/v3");
    foreach ($exportArray as $mods)
      $modsCollection->addXMLasBranch($mods);

    $modsCollectionDoc->setXML($modsCollection);
    $modsCollectionString = $modsCollectionDoc->getXMLString();

    return $modsCollectionString;
  }

  // --------------------------------------------------------------------

  // Returns an XML object (mods) of a single record
  function modsRecord($row) {

    global $databaseBaseURL; // these variables are defined in 'ini.inc.php'
    global $contentTypeCharset;
    global $fileVisibility;
    global $fileVisibilityException;
    global $filesBaseURL;
    global $convertExportDataToUTF8;

    // The array '$transtab_refbase_unicode' contains search & replace patterns for conversion from refbase markup to Unicode entities.
    global $transtab_refbase_unicode; // defined in 'transtab_refbase_unicode.inc.php'

    $exportPrivate = True;  // This will be a global variable or will be used
                            // when modsRow is called and will determine if we
                            // export user-specific data

    $exportRecordURL = True;  // Specifies whether an attribution string containing
                              // the URL to the refbase database record (and the last
                              // modification date) shall be written to the notes branch.
                              // Note that this string is required by the "-A|--append"
                              // feature of the 'refbase' command line client

    // convert this record's modified date/time info to UNIX time stamp format:
    // => "date('D, j M Y H:i:s O')", e.g. "Sat, 15 Jul 2006 22:24:16 +0200"
    // function 'generateRFC2822TimeStamp()' is defined in 'include.inc.php'
    $currentDateTimeStamp = generateRFC2822TimeStamp($row['modified_date'], $row['modified_time']);

    // --- BEGIN TYPE * ---
    //   |
    //   | These apply to everything

    // this is a stupid hack that maps the names of the '$row' array keys to those used
    // by the '$formVars' array (which is required by function 'generateCiteKey()')
    // (eventually, the '$formVars' array should use the MySQL field names as names for its array keys)
    $formVars = buildFormVarsArray($row); // function 'buildFormVarsArray()' is defined in 'include.inc.php'

    // generate or extract the cite key for this record
    $citeKey = generateCiteKey($formVars); // function 'generateCiteKey()' is defined in 'include.inc.php'

    // Encode special chars and perform charset conversions:
    foreach ($row as $rowFieldName => $rowFieldValue) {
      // We only convert those special chars to entities which are supported by XML:
      // function 'encodeHTMLspecialchars()' is defined in 'include.inc.php'
      $row[$rowFieldName] = encodeHTMLspecialchars($row[$rowFieldName]);

      // Convert field data to UTF-8:
      // (if '$convertExportDataToUTF8' is set to "yes" in 'ini.inc.php' and character encoding is not UTF-8 already)
      // (Note that charset conversion can only be done *after* the cite key has been generated, otherwise cite key
      //  generation will produce garbled text!)
      // function 'convertToCharacterEncoding()' is defined in 'include.inc.php'
      if (($convertExportDataToUTF8 == "yes") AND ($contentTypeCharset != "UTF-8"))
        $row[$rowFieldName] = convertToCharacterEncoding("UTF-8", "IGNORE", $row[$rowFieldName]);
    }

    // Defines field-specific search & replace 'actions' that will be applied to all those refbase fields that are listed in the corresponding 'fields' element:
    // (If you don't want to perform any search and replace actions, specify an empty array, like: '$fieldSpecificSearchReplaceActionsArray = array();'.
    //  Note that the search patterns MUST include the leading & trailing slashes -- which is done to allow for mode modifiers such as 'imsxU'.)
    //                                              "/Search Pattern/"  =>  "Replace Pattern"
    $fieldSpecificSearchReplaceActionsArray = array();

    if ($convertExportDataToUTF8 == "yes")
      $fieldSpecificSearchReplaceActionsArray[] = array(
                                                          'fields'  => array("title", "publication", "abbrev_journal", "address", "keywords", "abstract", "orig_title", "series_title", "abbrev_series_title", "notes"),
                                                          'actions' => $transtab_refbase_unicode
                                                      );

    // Apply field-specific search & replace 'actions' to all fields that are listed in the 'fields' element of the arrays contained in '$fieldSpecificSearchReplaceActionsArray':
    foreach ($fieldSpecificSearchReplaceActionsArray as $fieldActionsArray)
      foreach ($row as $rowFieldName => $rowFieldValue)
        if (in_array($rowFieldName, $fieldActionsArray['fields']))
          $row[$rowFieldName] = searchReplaceText($fieldActionsArray['actions'], $rowFieldValue, true); // function 'searchReplaceText()' is defined in 'include.inc.php'

    // Create an XML object for a single record.
    $record = new XML("mods");
    $record->setTagAttribute("version", "3.2");
    if (!empty($citeKey))
      $record->setTagAttribute("ID", $citeKey);

    // titleInfo
    //   Regular Title
    if (!empty($row['title']))
      $record->setTagContent($row['title'], "mods/titleInfo/title");

    //   Translated Title
    //   NOTE: This field is excluded by the default cite SELECT method
    if (!empty($row['orig_title'])) {
      $orig_title = new XMLBranch("titleInfo");
      $orig_title->setTagAttribute("type", "translated");
      $orig_title->setTagContent($row['orig_title'], "titleInfo/title");
      $record->addXMLBranch($orig_title);
    }

    // name
    //   author
    if (!empty($row['author'])) {
      if (ereg(" *\(eds?\)$", $row['author'])) {
        $author = ereg_replace("[ \r\n]*\(eds?\)", "", $row['author']);
        $nameArray = separateNames("/\s*;\s*/", "/\s*,\s*/", " ", $author, "personal",
                                   "editor");
        // NOTE: see notes below first call to function 'separateNames()'
      }
      else if ($row['type'] == "Map") {
        $nameArray = separateNames("/\s*;\s*/", "/\s*,\s*/", " ", $row['author'], "personal",
                                   "cartographer");
      }
      else {
        $nameArray = separateNames("/\s*;\s*/", "/\s*,\s*/", " ", $row['author'], "personal",
                                   "author");
      }
      foreach ($nameArray as $singleName) {
        $record->addXMLBranch($singleName);
      }
    }

    // originInfo
    if ((!empty($row['year'])) || (!empty($row['publisher'])) ||
         (!empty($row['place']))) {
      $origin = new XMLBranch("originInfo");

      // dateIssued
      if (!empty($row['year']))
        $origin->setTagContent($row['year'], "originInfo/dateIssued");

      // Book Chapters and Journal Articles only have a dateIssued
      // (editions, places, and publishers are associated with the host)
      if (!ereg("^(Book Chapter|Journal Article)$", $row['type'])) {
        // publisher
        if (!empty($row['publisher']))
          $origin->setTagContent($row['publisher'], "originInfo/publisher");
        // place
        if (!empty($row['place'])) {
          $origin->setTagContent($row['place'], "originInfo/place/placeTerm");
          $origin->setTagAttribute("type", "text",
                                   "originInfo/place/placeTerm");
        }
        // edition
        if (!empty($row['edition']))
          $origin->setTagContent($row['edition'], "originInfo/edition");
      }

      if ($origin->hasBranch())
        $record->addXMLBranch($origin);
    }

    // language
    if (!empty($row['language']))
      $record->setTagContent($row['language'], "mods/language");

    // abstract
    // NOTE: This field is excluded by the default cite SELECT method
    if (!empty($row['abstract'])) {
      $abstract = new XMLBranch("abstract");
      $abstract->setTagContent($row['abstract']);
      if (!empty($row['summary_language'])) {
        $abstract->setTagAttribute("lang", $row['summary_language']);
      }
      $record->addXMLBranch($abstract);
    }

    // subject
    //   keywords
    if (!empty($row['keywords'])) {
      $subjectArray = array();
      $subjectArray = preg_split("/\s*;\s*/", $row['keywords']); // "unrelated" keywords
      foreach ($subjectArray as $singleSubject) {
        $subjectBranch = new XMLBranch("subject");

        $topicArray = array();
        $topicArray = preg_split("/\s*,\s*/", $singleSubject); // "related" keywords
        foreach ($topicArray as $singleTopic) {
          $topicBranch = new XMLBranch("topic");
          $topicBranch->setTagContent($singleTopic);

          $subjectBranch->addXMLBranch($topicBranch);
        }
        $record->addXMLBranch($subjectBranch);
      }
    }
    //   userkeys
    //   NOTE: a copy of the above.  Needs to be a separate function later.
    if ((!empty($row['user_keys'])) && $exportPrivate) {
      $subjectArray = array();
      $subjectArray = preg_split("/\s*;\s*/", $row['user_keys']); // "unrelated" keywords
      foreach ($subjectArray as $singleSubject) {
        $subjectBranch = new XMLBranch("subject");

        $topicArray = array();
        $topicArray = preg_split("/\s*,\s*/", $singleSubject); // "related" keywords
        foreach ($topicArray as $singleTopic) {
          $topicBranch = new XMLBranch("topic");
          $topicBranch->setTagContent($singleTopic);

          $subjectBranch->addXMLBranch($topicBranch);
        }
        $record->addXMLBranch($subjectBranch);
      }
    }
    //   user_groups
    //   NOTE: a copy of the above.  Needs to be a separate function later.
    if ((!empty($row['user_groups'])) && $exportPrivate) {
      $subjectArray = array();
      $subjectArray = preg_split("/\s*;\s*/", $row['user_groups']); // "unrelated" keywords
      foreach ($subjectArray as $singleSubject) {
        $subjectBranch = new XMLBranch("subject");

        $topicArray = array();
        $topicArray = preg_split("/\s*,\s*/", $singleSubject); // "related" keywords
        foreach ($topicArray as $singleTopic) {
          $topicBranch = new XMLBranch("topic");
          $topicBranch->setTagContent($singleTopic);

          $subjectBranch->addXMLBranch($topicBranch);
        }
        $record->addXMLBranch($subjectBranch);
      }
    }

    // notes
    if (!empty($row['notes']))
      $record->setTagContent($row['notes'], "mods/note");
    // user_notes
    if ((!empty($row['user_notes'])) && $exportPrivate) // replaces any generic notes
      $record->setTagContent($row['user_notes'], "mods/note");
    // refbase attribution string
    if ($exportRecordURL) {
        $attributionBranch = new XMLBranch("note");
        $attributionBranch->setTagContent("exported from refbase ("
          . $databaseBaseURL . "show.php?record=" . $row['serial']
          . "), last updated on " . $currentDateTimeStamp);
        $record->addXMLBranch($attributionBranch);
    }

    // typeOfResource
    // maps are 'cartographic', software is 'software, multimedia',
    // and everything else is 'text'
    $type = new XMLBranch("typeOfResource");
    if ($row['type'] == "Map") {
      $type->setTagContent("cartographic");
    }
    else if ($row['type'] == "Software") {
      $type->setTagContent("software, multimedia");
    }
    else {
      $type->setTagContent("text");
    }
    if ($row['type'] == "Manuscript") {
      $type->setTagAttribute("manuscript", "yes");
    }
    $record->addXMLBranch($type);

    // location
    //   Physical Location
    //   NOTE: This field is excluded by the default cite SELECT method
    //         This should also be parsed later
    if (!empty($row['location'])) {
      $location = new XMLBranch("location");
      $locationArray = array();
      $locationArray = preg_split("/\s*;\s*/", $row['location']);
      foreach ($locationArray as $singleLocation) {
        $locationBranch = new XMLBranch("physicalLocation");
        $locationBranch->setTagContent($singleLocation);
        $location->addXMLBranch($locationBranch);
      }
      $record->addXMLBranch($location);
    }
    //   URL (also an identifier, see below)
    //   NOTE: This field is excluded by the default cite SELECT method
    if (!empty($row['url'])) {
      $location = new XMLBranch("location");
      $location->setTagContent($row['url'], "location/url");
      $record->addXMLBranch($location);
    }
    // Include a link to any corresponding FILE if one of the following conditions is met:
    // - the variable '$fileVisibility' (defined in 'ini.inc.php') is set to 'everyone'
    // - the variable '$fileVisibility' is set to 'login' AND the user is logged in
    // - the variable '$fileVisibility' is set to 'user-specific' AND the 'user_permissions' session variable contains 'allow_download'
    // - the array variable '$fileVisibilityException' (defined in 'ini.inc.php') contains a pattern (in array element 1) that matches the contents of the field given (in array element 0)
    if ($fileVisibility == "everyone" OR ($fileVisibility == "login" AND isset($_SESSION['loginEmail'])) OR ($fileVisibility == "user-specific" AND (isset($_SESSION['user_permissions']) AND ereg("allow_download", $_SESSION['user_permissions']))) OR (!empty($fileVisibilityException) AND preg_match($fileVisibilityException[1], $row[$fileVisibilityException[0]])))
    {
      //   file
      //   Note that when converting MODS to Endnote or RIS, Bibutils will include the above
      //   URL (if given), otherwise it'll take the URL from the 'file' field. I.e. for
      //   Endnote or RIS, the URL to the PDF is only included if no regular URL is available.
      if (!empty($row['file'])) {
        $location = new XMLBranch("location");

        if (ereg('^(https?|ftp|file)://', $row['file'])) { // if the 'file' field contains a full URL (starting with "http://", "https://",  "ftp://", or "file://")
          $URLprefix = ""; // we don't alter the URL given in the 'file' field
        }
        else { // if the 'file' field contains only a partial path (like 'polarbiol/10240001.pdf') or just a file name (like '10240001.pdf')
          // use the base URL of the standard files directory as prefix:
          if (ereg('^/', $filesBaseURL)) // absolute path -> file dir is located outside of refbase root dir
            $URLprefix = 'http://' . $_SERVER['HTTP_HOST'] . $filesBaseURL;
          else // relative path -> file dir is located within refbase root dir
            $URLprefix = $databaseBaseURL . $filesBaseURL;
        }

        $location->setTagContent($URLprefix . $row['file'], "location/url");
        $location->setTagAttribute("displayLabel", "Electronic full text", "location/url");
        // the 'access' attribute requires MODS v3.2 or greater:
        $location->setTagAttribute("access", "raw object", "location/url");
        $record->addXMLBranch($location);
      }
    }

    // identifier
    //   url
    if (!empty($row['url'])) {
      $identifier = new XMLBranch("identifier");
      $identifier->setTagContent($row['url']);
      $identifier->setTagAttribute("type", "uri");
      $record->addXMLBranch($identifier);
    }
    //   doi
    if (!empty($row['doi'])) {
      $identifier = new XMLBranch("identifier");
      $identifier->setTagContent($row['doi']);
      $identifier->setTagAttribute("type", "doi");
      $record->addXMLBranch($identifier);
    }
    //   pubmed
    //   NOTE: Until refbase stores PubMed & arXiv IDs in a better way,
    //         we extract them from the 'notes' field
    if (preg_match("/PMID *: *\d+/i", $row['notes'])) {
      $identifier = new XMLBranch("identifier");
      $identifier->setTagContent(preg_replace("/.*?PMID *: *(\d+).*/i", "\\1", $row['notes']));
      $identifier->setTagAttribute("type", "pubmed");
      $record->addXMLBranch($identifier);
    }
    //   arxiv
    //   NOTE: see note for pubmed
    if (preg_match("/arXiv *: *[^ ;]+/i", $row['notes'])) {
      $identifier = new XMLBranch("identifier");
      $identifier->setTagContent(preg_replace("/.*?arXiv *: *([^ ;]+).*/i", "\\1", $row['notes']));
      $identifier->setTagAttribute("type", "arxiv");
      $record->addXMLBranch($identifier);
    }
    //   cite_key
    if (!empty($citeKey)) {
      $identifier = new XMLBranch("identifier");
      $identifier->setTagContent($citeKey);
      $identifier->setTagAttribute("type", "citekey");
      $record->addXMLBranch($identifier);
    }
    //   local--CALL NUMBER
    //   NOTE: This should really be parsed!
    if (!empty($row['call_number'])) {
      $identifierArray = array();
      $identifierArray = preg_split("/\s*;\s*/", $row['call_number']);
      foreach ($identifierArray as $singleIdentifier) {
        if (!preg_match("/@\s*$/", $singleIdentifier)) {
          $identifierBranch = new XMLBranch("identifier");
          $identifierBranch->setTagContent($singleIdentifier);
          $identifierBranch->setTagAttribute("type","local");
          $record->addXMLBranch($identifierBranch);
        }
      }
    }

    // --- END TYPE * ---

    // -----------------------------------------

    // --- BEGIN TYPE != ABSTRACT || BOOK CHAPTER || CONFERENCE ARTICLE || JOURNAL ARTICLE || MAGAZINE ARTICLE || NEWSPAPER ARTICLE ---
    //   |
    //   | BOOK WHOLE, CONFERENCE VOLUME, JOURNAL, MANUAL, MANUSCRIPT, MAP, MISCELLANEOUS, PATENT,
    //   | REPORT, and SOFTWARE have some info as a branch off the root, whereas ABSTRACT, BOOK CHAPTER,
    //   | CONFERENCE ARTICLE, JOURNAL ARTICLE, MAGAZINE ARTICLE and NEWSPAPER ARTICLE place it in the relatedItem branch.

    if (!ereg("^(Abstract|Book Chapter|Conference Article|Journal Article|Magazine Article|Newspaper Article)$", $row['type'])) {
      // name
      //   editor
      if (!empty($row['editor'])) {
        $editor=$row['editor'];
        $author=$row['author'];
        if (ereg(" *\(eds?\)$", $editor))
          $editor = ereg_replace("[ \r\n]*\(eds?\)", "", $editor);
        if (ereg(" *\(eds?\)$", $author))
          $author = ereg_replace("[ \r\n]*\(eds?\)", "", $author);
        if ($editor != $author) {
          $nameArray = separateNames("/\s*;\s*/", "/\s*,\s*/", " ", $editor,
                                     "personal", "editor");
          // NOTE: see notes below first call to function 'separateNames()'
          foreach ($nameArray as $singleName)
            $record->addXMLBranch($singleName);
        }
      }
      //   corporate
      //   (we treat a 'corporate_author' similar to how Bibutils converts the BibTeX
      //   'organization' field to MODS XML, i.e., we add a separate name element with
      //    a 'type="corporate"' attribute and an 'author' role)
      if (!empty($row['corporate_author'])) {
        $nameBranch = new XMLBranch("name");
        $nameBranch->setTagAttribute("type", "corporate");
        $nameBranch->setTagContent($row['corporate_author'], "name/namePart");
        $nameBranch->setTagContent("author", "name/role/roleTerm");
        $nameBranch->setTagAttribute("authority", "marcrelator", "name/role/roleTerm");
        $nameBranch->setTagAttribute("type", "text", "name/role/roleTerm");
        $record->addXMLBranch($nameBranch);
      }
      //   conference
      if (!empty($row['conference'])) {
        $nameBranch = new XMLBranch("name");
        $nameBranch->setTagAttribute("type", "conference");
        $nameBranch->setTagContent($row['conference'], "name/namePart");
        $record->addXMLBranch($nameBranch);
      }

      // genre
      //   type
      //      NOTE: Is there a better MARC genre[1] for 'manuscript?'
      //            [1]<http://www.loc.gov/marc/sourcecode/genre/genrelist.html>
      $genremarc = new XMLBranch("genre");
      $genre = new XMLBranch("genre");
      //      NOTE: According to the MARC "Source Codes for Genre"[1]
      //            the MARC authority should be 'marcgt', not 'marc'.
      //            [1]<http://www.loc.gov/marc/sourcecode/genre/genresource.html>
      $genremarc->setTagAttribute("authority", "marcgt");

      if (empty($row['thesis'])) { // theses will get their own genre (see below)
        if ($row['type'] == "Book Whole") {
          $record->setTagContent("monographic",
                                 "mods/originInfo/issuance");
          $genremarc->setTagContent("book");
        }
        else if ($row['type'] == "Conference Volume") {
          $genremarc->setTagContent("conference publication");
        }
        else if ($row['type'] == "Journal") {
          $genremarc->setTagContent("periodical");
          $genre->setTagContent("academic journal");
        }
        else if ($row['type'] == "Manual") { // should we set '<issuance>monographic' here (and for the ones below)?
          $genremarc->setTagContent("instruction");
          $genre->setTagContent("manual");
        }
        else if ($row['type'] == "Manuscript") {
          $genremarc->setTagContent("loose-leaf");
          $genre->setTagContent("manuscript");
        }
        else if ($row['type'] == "Map") {
          $genremarc->setTagContent("map");
        }
        else if ($row['type'] == "Miscellaneous") {
          $genre->setTagContent("miscellaneous");
        }
        else if ($row['type'] == "Patent") {
          $genremarc->setTagContent("patent");
        }
        else if ($row['type'] == "Report") {
          $genremarc->setTagContent("technical report");
          $genre->setTagContent("report");
        }
        else if ($row['type'] == "Software") {
//        $genremarc->setTagContent("programmed text"); // would this be correct?
          $genre->setTagContent("software");
        }
        else if (!empty($row['type'])) { // catch-all: don't use a MARC genre
          $genre->setTagContent($row['type']);
        }
        if ($genremarc->hasLeaf())
          $record->addXMLBranch($genremarc);
        if ($genre->hasLeaf())
          $record->addXMLBranch($genre);
      }
      //   thesis
      else { // if (!empty($row['thesis']))
        $record->setTagContent("monographic",
                               "mods/originInfo/issuance");
        $thesismarc = new XMLBranch("genre");
        $thesis = new XMLBranch("genre");

        $thesismarc->setTagContent("theses");
        $thesismarc->setTagAttribute("authority", "marcgt");

        // tweak thesis names so that Bibutils will recognize them:
        if ($row['thesis'] == "Master's thesis")
          $row['thesis'] = "Masters thesis";

        $thesis->setTagContent($row['thesis']);

        $record->addXMLBranch($thesismarc);
        $record->addXMLBranch($thesis);
      }

      // physicalDescription
      //   pages
      if (!empty($row['pages'])) {
        $description = new XMLBranch("physicalDescription");
        $pages = new XMLBranch("extent");
        $pages->setTagAttribute("unit", "pages");
        if (ereg("[0-9] *- *[0-9]", $row['pages'])) { // if a page range
          // split the page range into start and end pages
          list($pagestart, $pageend) = preg_split('/\s*[-]\s*/', $row['pages']);
          if ($pagestart < $pageend) { // extents MUST span multiple pages
            $pages->setTagContent($pagestart, "extent/start");
            $pages->setTagContent($pageend, "extent/end");
          }
          else {
            $pages->setTagContent($row['pages']);
          }
        }
        else if (preg_match("/^\d\d*\s*pp?.?$/", $row['pages'])) {
          list($pagetotal) = preg_split('/\s*pp?/', $row['pages']);
          $pages->setTagContent($pagetotal, "extent/total");
        }
        else {
          $pages->setTagContent($row['pages']);
        }
        $description->addXMLBranch($pages);
        $record->addXMLBranch($description);
      }

      // identifier
      //   isbn
      if (!empty($row['isbn'])) {
        $identifier = new XMLBranch("identifier");
        $identifier->setTagContent($row['isbn']);
        $identifier->setTagAttribute("type", "isbn");
        $record->addXMLBranch($identifier);
      }
      //   issn
      if (!empty($row['issn'])) {
        $identifier = new XMLBranch("identifier");
        $identifier->setTagContent($row['issn']);
        $identifier->setTagAttribute("type", "issn");
        $record->addXMLBranch($identifier);
      }

      // series
      if ((!empty($row['series_editor'])) || (!empty($row['series_title'])) ||
          (!empty($row['abbrev_series_title'])) ||
          (!empty($row['series_volume'])) || (!empty($row['series_issue']))) {
        $record->addXMLBranch(serialBranch($row['series_editor'],
                                           $row['series_title'],
                                           $row['abbrev_series_title'],
                                           $row['series_volume'],
                                           $row['series_issue']));
      }
    }

    // --- END TYPE != ABSTRACT || BOOK CHAPTER || CONFERENCE ARTICLE || JOURNAL ARTICLE || MAGAZINE ARTICLE || NEWSPAPER ARTICLE ---

    // -----------------------------------------

    // --- BEGIN TYPE == ABSTRACT || BOOK CHAPTER || CONFERENCE ARTICLE || JOURNAL ARTICLE || MAGAZINE ARTICLE || NEWSPAPER ARTICLE ---
    //   |
    //   | NOTE: These are currently the only types that have publication,
    //   |       abbrev_journal, volume, and issue added.
    //   | A lot of info goes into the relatedItem branch.

    else { // if (ereg("^(Abstract|Book Chapter|Conference Article|Journal Article|Magazine Article|Newspaper Article)$", $row['type']))
      // relatedItem
      $related = new XMLBranch("relatedItem");
      $related->setTagAttribute("type", "host");

      // title (Publication)
      if (!empty($row['publication']))
        $related->setTagContent($row['publication'],
                                "relatedItem/titleInfo/title");

      // title (Abbreviated Journal)
      if (!empty($row['abbrev_journal'])) {
        $titleabbrev = NEW XMLBranch("titleInfo");
        $titleabbrev->setTagAttribute("type", "abbreviated");
        $titleabbrev->setTagContent($row['abbrev_journal'], "titleInfo/title");
        $related->addXMLBranch($titleabbrev);
      }

      // name
      //   editor
      if (!empty($row['editor'])) {
        $editor=$row['editor'];
        if (ereg(" *\(eds?\)$", $editor))
          $editor = ereg_replace("[ \r\n]*\(eds?\)", "", $editor);
        $nameArray = separateNames("/\s*;\s*/", "/\s*,\s*/", " ", $editor,
                                   "personal", "editor");
        // NOTE: see notes below first call to function 'separateNames()'
        foreach ($nameArray as $singleName)
          $related->addXMLBranch($singleName);
      }
      //   corporate
      //   NOTE: a copy of the code for 'corporate_author' above.
      //         Needs to be a separate function later.
      if (!empty($row['corporate_author'])) {
        $nameBranch = new XMLBranch("name");
        $nameBranch->setTagAttribute("type", "corporate");
        $nameBranch->setTagContent($row['corporate_author'], "name/namePart");
        $nameBranch->setTagContent("author", "name/role/roleTerm");
        $nameBranch->setTagAttribute("authority", "marcrelator", "name/role/roleTerm");
        $nameBranch->setTagAttribute("type", "text", "name/role/roleTerm");
        $related->addXMLBranch($nameBranch);
      }
      //   conference
      //   NOTE: a copy of the code for 'conference' above.
      //         Needs to be a separate function later.
      if (!empty($row['conference'])) {
        $nameBranch = new XMLBranch("name");
        $nameBranch->setTagAttribute("type", "conference");
        $nameBranch->setTagContent($row['conference'], "name/namePart");
        $related->addXMLBranch($nameBranch);
      }

      // originInfo
      $relorigin = new XMLBranch("originInfo");
      // dateIssued
      if (!empty($row['year']))
        $relorigin->setTagContent($row['year'],"originInfo/dateIssued");
      // publisher
      if (!empty($row['publisher']))
        $relorigin->setTagContent($row['publisher'], "originInfo/publisher");
      // place
      if (!empty($row['place'])) {
        $relorigin->setTagContent($row['place'], "originInfo/place/placeTerm");
        $relorigin->setTagAttribute("type", "text",
                                    "originInfo/place/placeTerm");
      }
      // edition
      if (!empty($row['edition']))
        $relorigin->setTagContent($row['edition'], "originInfo/edition");
      if ($relorigin->hasBranch())
        $related->addXMLBranch($relorigin);

      // genre (and originInfo/issuance)
      if (empty($row['thesis'])) { // theses will get their own genre (see below)
        if (ereg("^(Journal Article|Magazine Article)$", $row['type'])) {
          $related->setTagContent("continuing",
                                  "relatedItem/originInfo/issuance");
          $genremarc = new XMLBranch("genre");
          $genre = new XMLBranch("genre");

          $genremarc->setTagContent("periodical");
          $genremarc->setTagAttribute("authority", "marcgt");

          if ($row['type'] == "Magazine Article")
            $genre->setTagContent("magazine");
          else
            $genre->setTagContent("academic journal");

          $related->addXMLBranch($genremarc);
          $related->addXMLBranch($genre);
        }
        else if ($row['type'] == "Abstract") {
          $record->setTagContent("abstract or summary", "mods/genre");
          $record->setTagAttribute("authority", "marcgt", "mods/genre");
        }
        else if ($row['type'] == "Conference Article") {
          $related->setTagContent("conference publication", "relatedItem/genre");
          $related->setTagAttribute("authority", "marcgt", "relatedItem/genre");
        }
        else if ($row['type'] == "Newspaper Article") {
          $related->setTagContent("continuing",
                                  "relatedItem/originInfo/issuance");
          $related->setTagContent("newspaper", "relatedItem/genre");
          $related->setTagAttribute("authority", "marcgt", "relatedItem/genre");
        }
        else { // if ($row['type'] == "Book Chapter")
          $related->setTagContent("monographic",
                                  "relatedItem/originInfo/issuance");
          $related->setTagContent("book", "relatedItem/genre");
          $related->setTagAttribute("authority", "marcgt", "relatedItem/genre");
        }
      }
      //   thesis
      else { // if (!empty($row['thesis']))
        $thesismarc = new XMLBranch("genre");
        $thesis = new XMLBranch("genre");

        $thesismarc->setTagContent("theses");
        $thesismarc->setTagAttribute("authority", "marcgt");

        // tweak thesis names so that Bibutils will recognize them:
        if ($row['thesis'] == "Master's thesis")
          $row['thesis'] = "Masters thesis";

        $thesis->setTagContent($row['thesis']);

        $related->addXMLBranch($thesismarc);
        $related->addXMLBranch($thesis);
      }

      if ((!empty($row['year'])) || (!empty($row['volume'])) ||
        (!empty($row['issue'])) || (!empty($row['pages']))) {
        $part = new XMLBranch("part");

        if (!empty($row['year']))
          $part->setTagContent($row['year'], "date");
        if (!empty($row['volume'])) {
          $detailvolume = new XMLBranch("detail");
          $detailvolume->setTagContent($row['volume'], "detail/number");
          $detailvolume->setTagAttribute("type", "volume");
          $part->addXMLBranch($detailvolume);
        }
        if (!empty($row['issue'])) {
          $detailnumber = new XMLBranch("detail");
          $detailnumber->setTagContent($row['issue'], "detail/number");
          $detailnumber->setTagAttribute("type", "issue");
          $part->addXMLBranch($detailnumber);
        }
        if (!empty($row['pages'])) {
          if (ereg("[0-9] *- *[0-9]", $row['pages'])) { // if a page range
            // split the page range into start and end pages
            list($pagestart, $pageend) = preg_split('/\s*[-]\s*/', $row['pages']);
            if ($pagestart < $pageend) { // extents MUST span multiple pages
              $pages = new XMLBranch("extent");
              $pages->setTagContent($pagestart, "extent/start");
              $pages->setTagContent($pageend, "extent/end");
              $pages->setTagAttribute("unit", "page");
            }
            else {
              $pages = new XMLBranch("detail");
              if ($pagestart == $pageend) // single-page item
                $pages->setTagContent($pagestart, "detail/number");
              else
                $pages->setTagContent($row['pages'], "detail/number");
              $pages->setTagAttribute("type", "page");
            }
          }
          else {
            $pages = new XMLBranch("detail");
            $pages->setTagContent($row['pages'], "detail/number");
            $pages->setTagAttribute("type", "page");
          }
          $part->addXMLBranch($pages);
        }
        $related->addXMLBranch($part);
      }

      // identifier
      //   isbn
      if (!empty($row['isbn'])) {
        $identifier = new XMLBranch("identifier");
        $identifier->setTagContent($row['isbn']);
        $identifier->setTagAttribute("type", "isbn");
        $related->addXMLBranch($identifier);
      }
      //   issn
      if (!empty($row['issn'])) {
        $identifier = new XMLBranch("identifier");
        $identifier->setTagContent($row['issn']);
        $identifier->setTagAttribute("type", "issn");
        $related->addXMLBranch($identifier);
      }

      // series
      if ((!empty($row['series_editor'])) || (!empty($row['series_title'])) ||
          (!empty($row['abbrev_series_title'])) ||
          (!empty($row['series_volume'])) || (!empty($row['series_issue']))) {
        $related->addXMLBranch(serialBranch($row['series_editor'],
                                            $row['series_title'],
                                            $row['abbrev_series_title'],
                                            $row['series_volume'],
                                            $row['series_issue']));
      }

      $record->addXMLBranch($related);
    }

    // --- END TYPE == ABSTRACT || BOOK CHAPTER || CONFERENCE ARTICLE || JOURNAL ARTICLE || MAGAZINE ARTICLE || NEWSPAPER ARTICLE ---


    return $record;
  }

  // --------------------------------------------------------------------

?>
