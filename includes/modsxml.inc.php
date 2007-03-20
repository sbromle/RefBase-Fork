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
  //   <http://www.scripps.edu/~cdputnam/software/bibutils/bibutils.html>

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
  //   - Can't find a place in MODS XML
  //     - file


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

    global $contentTypeCharset; // these variables are defined in 'ini.inc.php'
    global $convertExportDataToUTF8;

    // The array '$transtab_refbase_unicode' contains search & replace patterns for conversion from refbase markup to Unicode entities.
    global $transtab_refbase_unicode; // defined in 'transtab_refbase_unicode.inc.php'

    $exportPrivate = True;  // This will be a global variable or will be used
                            // when modsRow is called and will determine if we
                            // export user-specific data

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
                                                          'fields'  => array("title", "address", "keywords", "abstract", "orig_title", "series_title", "abbrev_series_title", "notes", "publication"),
                                                          'actions' => $transtab_refbase_unicode
                                                      );

    // Apply field-specific search & replace 'actions' to all fields that are listed in the 'fields' element of the arrays contained in '$fieldSpecificSearchReplaceActionsArray':
    foreach ($fieldSpecificSearchReplaceActionsArray as $fieldActionsArray)
      foreach ($row as $rowFieldName => $rowFieldValue)
        if (in_array($rowFieldName, $fieldActionsArray['fields']))
          $row[$rowFieldName] = searchReplaceText($fieldActionsArray['actions'], $rowFieldValue, true); // function 'searchReplaceText()' is defined in 'include.inc.php'

    // Create an XML object for a single record.
    $record = new XML("mods");
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
      if (!ereg("Book Chapter|Journal Article", $row['type'])) {
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
    if ((!empty($row['user_notes'])) && $exportPrivate)
      $record->setTagContent($row['user_notes'], "mods/note");

    // typeOfResource
    // maps are 'cartographic' and everything else is 'text'
    $type = new XMLBranch("typeOfResource");
    if ($row['type'] == "Map") {
      $type->setTagContent("cartographic");
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
    //   URL (also an identifier)
    //   NOTE: This field is excluded by the default cite SELECT method
    if (!empty($row['url'])) {
      $location = new XMLBranch("location");
      $location->setTagContent($row['url'], "location/url");

      $identifier = new XMLBranch("identifier");
      $identifier->setTagContent($row['url']);
      $identifier->setTagAttribute("type", "uri");

      $record->addXMLBranch($location);
      $record->addXMLBranch($identifier);
    }

    // identifier
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

    // --- BEGIN TYPE != BOOK CHAPTER || JOURNAL ARTICLE || CONFERENCE ARTICLE ---
    //   |
    //   | BOOK WHOLE, CONFERENCE VOLUME, JOURNAL, MANUSCRIPT, and MAP have some info
    //   | as a branch off the root, where as BOOK CHAPTER, JOURNAL ARTICLE and
    //   | CONFERENCE ARTICLE place it in the relatedItem branch.

    if (!ereg("Book Chapter|Journal Article|Conference Article", $row['type'])) {
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
          foreach ($nameArray as $singleName)
            $record->addXMLBranch($singleName);
        }
      }
      //   corporate
      //   (we treat a 'corporate_author' similar to how bibutils converts the BibTeX
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
      //            While Zotero expects 'marcgt', bibutils uses (expects?) 'marc', so we
      //            should adopt 'marcgt' as authority attribute when bibutils recognizes it.
      //            [1]<http://www.loc.gov/marc/sourcecode/genre/genresource.html>
      $genremarc->setTagAttribute("authority", "marc");

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
        else if ($row['type'] == "Manuscript") {
          $genremarc->setTagContent("loose-leaf");
          $genre->setTagContent("manuscript");
        }
        else if ($row['type'] == "Map") {
          $genremarc->setTagContent("map");
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
        // NOTE: we should use 'authority="marcgt"' (see note above)
        $thesismarc->setTagAttribute("authority", "marc");

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

    // --- END TYPE != BOOK CHAPTER || JOURNAL ARTICLE || CONFERENCE ARTICLE ---

    // -----------------------------------------

    // --- BEGIN TYPE == BOOK CHAPTER || JOURNAL ARTICLE || CONFERENCE ARTICLE ---
    //   |
    //   | NOTE: These are currently the only types that have publication,
    //   |       abbrev_journal, volume, and issue added.
    //   | A lot of info goes into the relatedItem branch.

    else { // if (ereg("Book Chapter|Journal Article|Conference Article", $row['type']))
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
        if ($row['type'] == "Journal Article") {
          $related->setTagContent("continuing",
                                  "relatedItem/originInfo/issuance");
          $genremarc = new XMLBranch("genre");
          $genre = new XMLBranch("genre");

          $genremarc->setTagContent("periodical");
          // NOTE: we should use 'authority="marcgt"' (see note above)
          $genremarc->setTagAttribute("authority", "marc");

          $genre->setTagContent("academic journal");

          $related->addXMLBranch($genremarc);
          $related->addXMLBranch($genre);
        }
        else if ($row['type'] == "Conference Article") {
          $related->setTagContent("conference publication", "relatedItem/genre");
          // NOTE: we should use 'authority="marcgt"' (see note above)
          $related->setTagAttribute("authority", "marc", "relatedItem/genre");
        }
        else { // if ($row['type'] == "Book Chapter")
          $related->setTagContent("monographic",
                                  "relatedItem/originInfo/issuance");
          $related->setTagContent("book", "relatedItem/genre");
          // NOTE: we should use 'authority="marcgt"' (see note above)
          $related->setTagAttribute("authority", "marc", "relatedItem/genre");
        }
      }
      //   thesis
      else { // if (!empty($row['thesis']))
        $thesismarc = new XMLBranch("genre");
        $thesis = new XMLBranch("genre");

        $thesismarc->setTagContent("theses");
        // NOTE: we should use 'authority="marcgt"' (see note above)
        $thesismarc->setTagAttribute("authority", "marc");

        $thesis->setTagContent($row['thesis']);

        $related->addXMLBranch($thesismarc);
        $related->addXMLBranch($thesis);
      }

      if ((!empty($row['year'])) || (!empty($row['volume'])) ||
          (!empty($row['issue'])) || (!empty($row['pages'])) ||
          (!empty($row['doi']))) {
        $part = new XMLBranch("part");
        // identifier
        //   doi
        if (!empty($row['doi'])) {
          $identifier = new XMLBranch("identifier");
          $identifier->setTagContent($row['doi']);
          $identifier->setTagAttribute("type", "doi");
          $record->addXMLBranch($identifier);
        }

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

    // --- END TYPE == BOOK CHAPTER || JOURNAL ARTICLE || CONFERENCE ARTICLE ---


    return $record;
  }

  // --------------------------------------------------------------------

?>
