<?php
  // Copyright:  Richard Karnesky <mailto:karnesky@gmail.com>
  //             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
  //             Please see the GNU General Public License for more details.

  $wgExtensionFunctions[] = "wfRefbase";

  function wfRefbase(){
    global $wgParser;

    $wgParser->setHook("REFBASE", "renderRefbase");
  }

  function renderRefbase($input, &$parser){
    $hostName="localhost";
    $databaseName="literature";
    $username="litwww";
    $password="%l1t3ratur3?";
    $tableRefs="refs";
    $databaseBaseURL="http://".$_SERVER['HTTP_HOST']."/refbase/";

  	$transtab_refbase_html = array(

  		"/__(?!_)(.+?)__/"     =>  "<u>\\1</u>", // the pattern for underline (__...__) must come before the one for italic (_..._)
  		"/_(.+?)_/"            =>  "<i>\\1</i>",
  		"/\\*\\*(.+?)\\*\\*/"  =>  "<b>\\1</b>",
  		"/\\[super:(.+?)\\]/i" =>  "<sup>\\1</sup>",
  		"/\\[sub:(.+?)\\]/i"   =>  "<sub>\\1</sub>",
  		"/\\[permil\\]/"       =>  "&permil;",
  		"/\\[infinity\\]/"     =>  "&infin;",
  		"/\\[alpha\\]/"        =>  "&alpha;",
  		"/\\[beta\\]/"         =>  "&beta;",
  		"/\\[gamma\\]/"        =>  "&gamma;",
  		"/\\[delta\\]/"        =>  "&delta;",
  		"/\\[epsilon\\]/"      =>  "&epsilon;",
  		"/\\[zeta\\]/"         =>  "&zeta;",
  		"/\\[eta\\]/"          =>  "&eta;",
  		"/\\[theta\\]/"        =>  "&theta;",
  		"/\\[iota\\]/"         =>  "&iota;",
  		"/\\[kappa\\]/"        =>  "&kappa;",
  		"/\\[lambda\\]/"       =>  "&lambda;",
  		"/\\[mu\\]/"           =>  "&mu;",
  		"/\\[nu\\]/"           =>  "&nu;",
  		"/\\[xi\\]/"           =>  "&xi;",
  		"/\\[omicron\\]/"      =>  "&omicron;",
  		"/\\[pi\\]/"           =>  "&pi;",
  		"/\\[rho\\]/"          =>  "&rho;",
  		"/\\[sigmaf\\]/"       =>  "&sigmaf;",
  		"/\\[sigma\\]/"        =>  "&sigma;",
  		"/\\[tau\\]/"          =>  "&tau;",
  		"/\\[upsilon\\]/"      =>  "&upsilon;",
  		"/\\[phi\\]/"          =>  "&phi;",
  		"/\\[chi\\]/"          =>  "&chi;",
  		"/\\[psi\\]/"          =>  "&psi;",
  		"/\\[omega\\]/"        =>  "&omega;",
  		"/\\[Alpha\\]/"        =>  "&Alpha;",
  		"/\\[Beta\\]/"         =>  "&Beta;",
  		"/\\[Gamma\\]/"        =>  "&Gamma;",
  		"/\\[Delta\\]/"        =>  "&Delta;",
  		"/\\[Epsilon\\]/"      =>  "&Epsilon;",
  		"/\\[Zeta\\]/"         =>  "&Zeta;",
  		"/\\[Eta\\]/"          =>  "&Eta;",
  		"/\\[Theta\\]/"        =>  "&Theta;",
  		"/\\[Iota\\]/"         =>  "&Iota;",
  		"/\\[Kappa\\]/"        =>  "&Kappa;",
  		"/\\[Lambda\\]/"       =>  "&Lambda;",
  		"/\\[Mu\\]/"           =>  "&Mu;",
  		"/\\[Nu\\]/"           =>  "&Nu;",
  		"/\\[Xi\\]/"           =>  "&Xi;",
  		"/\\[Omicron\\]/"      =>  "&Omicron;",
  		"/\\[Pi\\]/"           =>  "&Pi;",
  		"/\\[Rho\\]/"          =>  "&Rho;",
  		"/\\[Sigma\\]/"        =>  "&Sigma;",
  		"/\\[Tau\\]/"          =>  "&Tau;",
  		"/\\[Upsilon\\]/"      =>  "&Upsilon;",
  		"/\\[Phi\\]/"          =>  "&Phi;",
  		"/\\[Chi\\]/"          =>  "&Chi;",
  		"/\\[Psi\\]/"          =>  "&Psi;",
  		"/\\[Omega\\]/"        =>  "&Omega;",
  		"/(?:\"|&quot;)(.+?)(?:\"|&quot;)/" => "&ldquo;\\1&rdquo;",
  		"/ +- +/"              =>  " &#8211; "
  	);

    $link = mysql_connect($hostName, $username, $password);
    if (! $link)
      die("Couldn't connect to MySQL");
    mysql_select_db($databaseName, $link) or die("Couldn't open $db: ".mysql_error());

    //select the new updated values
    $result = mysql_query( "SELECT type, author, title, year, publication, volume, issue, pages, publisher, place, language, issn, doi FROM $tableRefs where serial=$input" )
      or die("SELECT Error: ".mysql_error());

    $cite = "";

    while ($row = mysql_fetch_array($result)) {
      if ($row["type"]=="Journal Article"){
        $cite .= "{{cite_journal|url=$databaseBaseURL"."show.php?record=$input|";
        if(!empty($row["author"])){
          $author=$row["author"];
          $aulast = extractAuthorsLastName(" *; *", " *, *", 1, $author);
          $aufirst = extractAuthorsGivenName(" *; *", " *, *", 1, $author);
          if(!empty($aulast))
            $cite .= "last=".$aulast."|";
          if(!empty($aufirst)) {
            $cite .= "first=".$aufirst."|";
            if(!empty($aulast))
              $cite .= "authorlink=$aufirst $aulast"."|";
          }
          $authorcount = count(split(" *; *", $author));
          $au="";
          for ($i=0; $i < $authorcount-1; $i++){
            $aul = extractAuthorsLastName(" *; *", " *, *", $i+2, $author);
            $auf = extractAuthorsGivenName(" *; *", " *, *", $i+2, $author);
            if (!empty($aul)) {
              if (!empty($auf))
                $au .= "[[$auf $aul|$aul, $auf]]; ";
            }
          }
          if (!empty($au))
           $cite .= "coauthors=".trim($au,'; ')."|";
        }
        if(!empty($row["year"]))
          $cite .= "year=".$row['year']."|";
        if(!empty($row["title"])){
          $title = searchReplaceText($transtab_refbase_html, $row['title'], true);
          $cite .= "title=".$title."|";
        }
        if(!empty($row["language"]))
          $cite .= "language=".$row['language']."|";
        if(!empty($row["publication"]))
          $cite .= "journal=".$row['publication']."|";
        if(!empty($row["volume"]))
          $cite .= "volume=".$row['volume']."|";
        if(!empty($row["issue"]))
          $cite .= "issue=".$row['issue']."|";
        if(!empty($row["pages"]))
          $cite .= "pages=".$row['pages']."|";
        if(!empty($row["place"]))
          $cite .= "location=".$row['place']."|";
        if(!empty($row["publiser"]))
          $cite .= "publisher=".$row['publisher']."|";
        if(!empty($row["issn"]))
          $cite .= "issn=".$row['issn']."|";
        if(!empty($row["doi"]))
          $cite .= "doi=".$row['doi']."|";
        $cite.="}}";
      }
    }
    global $wgParser;
    $output = $wgParser->recursiveTagParse( $cite );
    return $output;
  }

  // EXTRACT AUTHOR'S LAST NAME 
  // this function takes the contents of the author field and will extract the last name of a particular author (specified by position) 
  // (e.g., setting '$authorPosition' to "1" will return the 1st author's last name) 
  //  Note: this function assumes that: 
  //        1. within one author object, there's only *one* delimiter separating author name & initials! 
  //        2. author objects are stored in the db as "<author_name><author_initials_delimiter><author_initials>", i.e., initials follow *after* the author's name! 
  //  Required Parameters: 
  //        1. pattern describing delimiter that separates different authors 
  //        2. pattern describing delimiter that separates author name & initials (within one author) 
  //        3. position of the author whose last name shall be extracted (e.g., "1" will return the 1st author's last name) 
  //        4. contents of the author field 
  function extractAuthorsLastName($oldBetweenAuthorsDelim, $oldAuthorsInitialsDelim, $authorPosition, $authorContents) { 
    $authorsArray = split($oldBetweenAuthorsDelim, $authorContents); // get a list of all authors for this record 
    $authorPosition = ($authorPosition-1); // php array elements start with "0", so we decrease the authors position by 1 
    $singleAuthor = $authorsArray[$authorPosition]; // for the author in question, extract the full author name (last name & initials) 
    $singleAuthorArray = split($oldAuthorsInitialsDelim, $singleAuthor); // then, extract author name & initials to separate list items 
    $singleAuthorsLastName = $singleAuthorArray[0]; // extract this author's last name into a new variable 
    return $singleAuthorsLastName; 
  } 

  // EXTRACT AUTHOR'S GIVEN NAME 
  // this function takes the contents of the author field and will extract the given name of a particular author (specified by position) 
  // (e.g., setting '$authorPosition' to "1" will return the 1st author's given name) 
  //  Required Parameters: 
  //        1. pattern describing delimiter that separates different authors 
  //        2. pattern describing delimiter that separates author name & initials (within one author) 
  //        3. position of the author whose last name shall be extracted (e.g., "1" will return the 1st author's last name) 
  //        4. contents of the author field 
  function extractAuthorsGivenName($oldBetweenAuthorsDelim, $oldAuthorsInitialsDelim, $authorPosition, $authorContents) { 
    $authorsArray = split($oldBetweenAuthorsDelim, $authorContents); // get a list of all authors for this record 
    $authorPosition = ($authorPosition-1); // php array elements start with "0", so we decrease the authors position by 1 
    $singleAuthor = $authorsArray[$authorPosition]; // for the author in question, extract the full author name (last name & initials) 
    $singleAuthorArray = split($oldAuthorsInitialsDelim, $singleAuthor); // then, extract author name & initials to separate list items 
    if (!empty($singleAuthorArray[1])) 
      $singleAuthorsGivenName = $singleAuthorArray[1]; // extract this author's last name into a new variable 
    else 
      $singleAuthorsGivenName = ''; 
    return $singleAuthorsGivenName; 
  }

  // Perform search & replace actions on the given text input: 
  // ('$includesSearchPatternDelimiters' must be a boolean value that specifies whether the leading and trailing slashes 
  //  are included within the search pattern ['true'] or not ['false']) 
  function searchReplaceText($searchReplaceActionsArray, $sourceString, $includesSearchPatternDelimiters) { 
    // apply the search & replace actions defined in '$searchReplaceActionsArray' to the text passed in '$sourceString': 
    foreach ($searchReplaceActionsArray as $searchString => $replaceString) { 
      if (!$includesSearchPatternDelimiters) 
        $searchString = "/" . $searchString . "/"; // add search pattern delimiters 
      if (preg_match($searchString, $sourceString)) 
        $sourceString = preg_replace($searchString, $replaceString, $sourceString); 
    } 
    return $sourceString; 
  }
?>
