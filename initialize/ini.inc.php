<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./initialize/ini.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    12-Jan-03, 17:58
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This is the customization include file.
	// It contains variables that are common to all scripts and whose values can/should be customized.
	// I.e., you can adjust their values as needed but you must not change the variable names themselves!

	// --------------------------------------------------------------------

	// The official name of this literature database:
	$officialDatabaseName = "Your Literature Database"; // e.g. "IPÖ Literature Database"


	// The base url for this literature database (i.e., the URL to the root directory):
	// It will be used within RSS feeds and when sending notification emails to database users.
	// (IMPORTANT: the base url MUST end with a slash!)
	$databaseBaseURL = preg_replace('#[^/]*$#e','','http://'.$_SERVER['HTTP_HOST'].scriptURL(),1); // e.g. "http://polaris.ipoe.uni-kiel.de/refs/"


	// Specify who'll be allowed to add a new user to the users table:
	// Note, that you should leave this variable as it is, if you're going to use the 'install.php'
	// script and the provided database structure file ('install.sql') for installation. This variable
	// is only provided for people who want to install the refbase database manually (i.e. without using
	// 'install.php' & 'install.sql'). If so, setting this value to "everyone" enables you to add the
	// admin as the very first user (don't forget to specify his email address below!). Then, change the
	// value of $addNewUsers to "admin". By that you prevent other users from messing with your users
	// table. (If the value is set to "everyone", any user will be able to add users to the users table!)
	$addNewUsers = "admin"; // possible values: "everyone", "admin"


	// The admin email address (by which a user is granted admin status after successful login!):
	// Note that you must NOT change this email address unless you've already logged in and created your
	// own admin user!
	$adminLoginEmail = "user@refbase.net"; // e.g. "admin@ipoe.uni-kiel.de"


	// The feedback email address to which any support questions or suggestions should be sent:
	$feedbackEmail = "FEEDBACK_EMAIL_ADDRESS"; // e.g. "admin@ipoe.uni-kiel.de"


	// The full name of the institution hosting this literature database:
	$hostInstitutionName = "Institute for ..."; // e.g. "Institute for Polar Ecology"


	// The abbreviated name of the institution hosting this literature database:
	$hostInstitutionAbbrevName = "..."; // e.g. "IPÖ"


	// The URL of the institution hosting this literature database:
	$hostInstitutionURL = "INSTITUTION_WEB_ADDRESS"; // e.g. "http://www.uni-kiel.de/ipoe/"


	// The URL to any (custom) help resources for this literature database:
	// (specify an empty string if you don't want a help link: '$helpResourcesURL = "";')
	$helpResourcesURL = "http://wiki.refbase.net/"; // e.g. "http://wiki.refbase.net/"


	// Specify whether announcements should be sent to the email address given in '$mailingListEmail':
	// If $sendEmailAnnouncements = "yes", a short info will be mailed to the email address specified
	// in $mailingListEmail if a new record has been added to the database.
	$sendEmailAnnouncements = "no"; // possible values: "yes", "no"


	// The mailing list email address to which any announcements should be sent:
	$mailingListEmail = "ANNOUNCEMENT_EMAIL_ADDRESS"; // e.g. "ipoelit-announce@ipoe.uni-kiel.de"


	// The character encoding that's used as content-type for HTML, RSS and email output:
	// IMPORTANT NOTES: - the encoding type specified here must match the default character set you've
	//                    chosen on install for your refbase MySQL database & tables!
	//                  - plus, the character encoding of this file ('ini.inc.php') MUST match the
	//                    encoding type specified in '$contentTypeCharset'! This means, if you're going to
	//                    use "UTF-8", you must re-save this file with encoding "Unicode (UTF-8, no BOM)".
	$contentTypeCharset = "ISO-8859-1"; // possible values: "ISO-8859-1", "UTF-8"


	// In case you're using a latin1-encoded database ('$contentTypeCharset=ISO-8859-1'), specify whether
	// exported data (Bibtex/Endnote/RIS or MODS/SRW/ODF XML) shall be converted to Unicode (UTF-8, no BOM).
	// Conversion of exported data to UTF-8 ('$convertExportDataToUTF8=yes') is required to correctly convert
	// refbase markup such as super- and subscript or greek letters. If you set this variable to "no", then
	// the relevant refbase markup will not get converted for a latin1 database(*) and output will be in
	// ISO-8859-1 encoding.
	// (*: as a notable exception, conversion of refbase markup such as super- and subscript or greek letters
	//     will be done upon Bibtex export even if this variable is set to "no")
	$convertExportDataToUTF8 = "yes"; // possible values: "yes", "no"


	// The path to the default CSS stylesheet which will be used for all page views except print view:
	$defaultStyleSheet = "css/style.css"; // e.g. "css/style.css"


	// The path to the CSS stylesheet which will be used for print view:
	$printStyleSheet = "css/style_print.css"; // e.g. "css/style_print.css"


	// The number of records that's returned by default:
	// Note that this setting also controls how many records will be returned by default for RSS, SRU and
	// CLI queries.
	$defaultNumberOfRecords = 5;


	// Defines the default user permissions when adding new users:
	// Possible values for each of the permission settings: "yes", "no"
	// Allow a newly created user to:
	$defaultUserPermissions = array("yes", // add records to the database ('allow_add')
									"yes", // edit records in the database ('allow_edit')
									"yes", // delete records from the database ('allow_delete')
									"yes", // download files which are associated with particular records ('allow_download')
									"yes", // upload files to the database ('allow_upload')
									"yes", // view any record details ('allow_details_view')
									"yes", // view records in print view ('allow_print_view')
									"no", // view records in browse view ('allow_browse_view')
									"yes", // build a reference list from selected records ('allow_cite')
									"yes", // import records into the database ('allow_import')
									"yes", // batch import records into the database ('allow_batch_import')
									"yes", // export records from the database ('allow_export')
									"yes", // batch export records from the database ('allow_batch_export')
									"yes", // use the 'user groups' feature ('allow_user_groups')
									"yes", // use the 'user queries' feature ('allow_user_queries')
									"yes", // generate dynamic RSS feeds from any query ('allow_rss_feeds')
									"yes", // execute custom SQL queries via 'sql_search.php' ('allow_sql_search')
									"yes", // change his/her personal data (like name, address or password) ('allow_modify_options')
									"no"); // fully edit the contents of the 'call_number' field (like the database admin) ('allow_edit_call_number')
											// [note that the 'allow_edit_call_number' permission setting isn't honoured yet!]


	// When adding a new user, the following export formats will be made available to the new user by default:
	// The specified format names must have matching entries within the 'formats' MySQL table.
	$defaultUserExportFormats = array("BibTeX",
										"Endnote",
										"RIS",
										"ISI",
										"MODS XML",
										"ODF XML",
										"Word XML");


	// When adding a new user, the following citation formats will be made available to the new user by default:
	// The specified format names must have matching entries within the 'formats' MySQL table.
	$defaultUserCiteFormats = array("html",
									"RTF",
									"PDF",
									"LaTeX");


	// When adding a new user, the following citation styles will be made available to the new user by default:
	// The specified citation styles must have matching entries within the 'styles' MySQL table.
	$defaultUserStyles = array("APA",
								"MLA",
								"Polar Biol",
								"Deep Sea Res",
								"J Glaciol",
								"Text Citation");


	// When adding a new user, the following reference types will be made available to the new user by default:
	// The specified reference types must have matching entries within the 'types' MySQL table.
	$defaultUserTypes = array("Journal Article",
								"Book Chapter",
								"Book Whole",
								"Conference Article",
								"Conference Volume",
								"Journal",
								"Manual",
								"Manuscript",
								"Map",
								"Miscellaneous",
								"Newspaper Article",
								"Patent",
								"Report",
								"Software");


	// Defines the default user options when adding new users:
	$defaultUserOptions = array(
								// controls whether to include cite keys on export or not:
								"yes", // 'export_cite_keys' -- possible values: "yes", "no"

								// controls whether cite keys will be auto-generated on export:
								"yes", // 'autogenerate_cite_keys' -- possible values: "yes", "no"

								// controls whether auto-generated cite keys will overwrite record-specific contents from
								// the user-specific 'cite_key' field on export:
								"no", // 'prefer_autogenerated_cite_keys' -- possible values: "yes", "no"

								// controls whether the user's custom cite key format shall be used (instead of the default
								// cite key format provided in '$defaultCiteKeyFormat', see below):
								"no", // 'use_custom_cite_key_format' -- possible values: "yes", "no"

								// the user's custom cite key format:
								// see comments for '$fileNamingScheme' (below) for more info on supported placeholders
								"<:firstAuthor:><:year:>", // 'cite_key_format' -- e.g. "<:firstAuthor:><:year:>"

								// controls whether to add incrementing numbers to any duplicate cite keys:
								"yes", // 'uniquify_duplicate_cite_keys' -- possible values: "yes", "no"

								// controls how non-ASCII characters will be treated in cite keys:
								// (keep empty in order to use the site default given in '$handleNonASCIICharsInCiteKeysDefault')
								"", // 'nonascii_chars_in_cite_keys' -- possible values: "strip", "keep", "transliterate", ""

								// controls whether the user's custom text citation format shall be used (instead of the default
								// text citation format provided in '$defaultTextCitationFormat', see below):
								"no", // 'use_custom_text_citation_format' -- possible values: "yes", "no"

								// the user's custom text citation format:
								// see comments for '$fileNamingScheme' (below) for more info on supported placeholders
								"<:authors[2| & | et al.]:>< :year:>< {:recordIdentifier:}>"); // 'text_citation_format' -- e.g. "<:authors[2| & | et al.]:>< :year:>< {:recordIdentifier:}>"


	// The default cite key format used for auto-generation of cite keys:
	// see comments for '$fileNamingScheme' (below) for more info on supported placeholders
	$defaultCiteKeyFormat = "<:authors:><:year:>"; // e.g. "<:authors:><:year:>"


	// Default setting that controls how non-ASCII characters will be treated in auto-generated cite keys:
	// (this default setting can be overwritten by user-specific settings)
	//   - "strip": removes any non-ASCII characters
	//   - "keep": keeps any non-ASCII characters (note that bibutils may strip any non-ASCII chars from cite
	//             keys when exporting to Endnote, RIS or BibTeX, depending on the bibutils version you're using)
	//   - "transliterate": attempts to transliterate most of the non-ASCII characters and strips all non-ASCII
	//                      chars that can't be converted into ASCII equivalents (this is the recommended option)
	$handleNonASCIICharsInCiteKeysDefault = "transliterate"; // possible values: "strip", "keep", "transliterate"


	// The name of the default citation style:
	// This name must correspond to an entry within the 'styles' MySQL table.
	// It will be used for citation output within 'show.php' and the 'generateRSS()' function.
	$defaultCiteStyle = "APA";


	// The size of the PDF page (used when outputting citations as PDF):
	$pdfPageSize = "a4"; // possible values: "a4", "letter"


	// The default text citation format:
	// see comments for '$fileNamingScheme' (below) for more info on supported placeholders
	$defaultTextCitationFormat = "<:authors[2| & | et al.]:>< :year:>< {:recordIdentifier:}>"; // e.g. "<:authors[2| & | et al.]:>< :year:>< {:recordIdentifier:}>"


	// The name of the default export format:
	// This name must correspond to an entry within the 'formats' MySQL table (of 'format_type' = "export").
	// It will be used when 'show.php' was called with 'submit=Export' but no 'exportFormat' parameter was specified.
	$defaultExportFormat = "RIS";


	// The default language selection, can be overwritten by user-defined language
	$defaultLanguage = "en"; // e.g. "en", "de" or "fr"


	// Specify who'll be allowed to see files associated with any records:
	// Set this variable to "everyone" if you want _any_ visitor of your database (whether he's logged
	// in or not) to be able to see links to any associated files. If you choose "login" instead, a
	// user must be logged in to view any files. Finally, use "user-specific" if you want to set this
	// permission individually for each user. Note that, setting this variable to either "everyone" or
	// "login" will override the user-specific permission setting for file downloads ("allow_download"
	// permission).
	$fileVisibility = "user-specific"; // possible values: "everyone", "login", "user-specific"


	// Specify a condition where files will be always made visible [optional]:
	// This variable can be used to specify a condition where the above rule of file visibility can be
	// by-passed (thus allowing download access to some particular files while all other files are
	// protected by the above rule). Files will be shown regardless of the above rule if the specified
	// condition is met. First param must be a valid field name from table 'refs', second param the
	// conditional expression (specified as /perl-style regular expression/ -> see note at the end of
	// this file). The given example will *always* show links to files where the 'thesis' field of the
	// corresponding record is not empty. If you do not wish to make any exception to the above rule,
	// just specify an empty array, like: '$fileVisibilityException = array();'. Use the "/.../i"
	// modifier to invoke case insensitive matching.
	$fileVisibilityException = array("thesis", "/.+/"); // e.g. 'array("thesis", "/.+/")'


	// Define what will be searched by "library_search.php":
	// refbase offers a "Library Search" feature that provides a separate search page for searching an
	// institution's library. All searches performed thru this search form will be restricted to
	// records that match the specified condition. First param must be a valid field name from table
	// 'refs', second param the conditional expression (specified as MySQL extended regular expression
	// -> see note at the end of this file). Of course, you could also use this feature to restrict
	// searches thru "library_search.php" by _any_ other condition. E.g., with "location" as the first
	// parameter and your own login email address as the second parameter, any "library" search would
	// be restricted to your personal literature data set.
	$librarySearchPattern = array("location", "library"); // e.g. 'array("location", "IPÖ Library")'


	// The base DIR path to your default file directory:
	// I.e., the local path to the root directory where any PDF files etc. are stored. This must be a
	// valid path specification to a local directory that's accessible (read+write) by the server. As an
	// example, if you're using the Apache web server on a unix machine and if your default file
	// directory (named "files") is located on the root level of your refbase script directory (named
	// "refs") the path spec could be something like: "/usr/local/httpd/htdocs/refs/files/"
	// (IMPORTANT: if given, the base dir MUST end with a slash!)
	$filesBaseDir = "PATH_TO_FILES_BASE_DIRECTORY"; // e.g. "/usr/local/httpd/htdocs/refs/files/"


	// The URL to the default file directory that you've specified in $filesBaseDir:
	// Any string within the 'file' field of the 'refs' table that doesn't start with "http://" or
	// "ftp://" will get prefixed with this URL. If your files directory is within your refbase root
	// directory, specify a *relative* path (e.g.: "files/" if the directory is on the same level as the
	// refbase php scripts and it's named "files"). Alternatively, if your files directory is somewhere
	// else within your server's DocumentRoot, you must specify an *absolute* path (e.g.: "/files/" if
	// the directory is on the uppermost level of your DocumentRoot and it's named "files"). If,
	// instead, you want to use *complete* path specifications within the 'file' field (e.g. because
	// your files are located within multiple directories), simply don't specify any URL here, i.e.,
	// keep it empty: '$filesBaseURL = "";'
	// (IMPORTANT: if given, the base url MUST end with a slash!)
	$filesBaseURL = "URL_TO_FILES_BASE_DIRECTORY"; // e.g. "files/"


	// Specify if files should be moved into sub-directories:
	//   - "never": files will always be copied to the root files dir (i.e. don't use any sub-directories)
	//   - "always": auto-generate new sub-directories if required (according to the naming scheme
	//               given in '$dirNamingScheme', see below)
	//   - "existing": only copy files into sub-directories if the sub-directory already exists
	$moveFilesIntoSubDirectories = "always"; // possible values: "never", "always", "existing"


	// Specify the naming scheme for auto-generated sub-directories:
	// (see comments for '$fileNamingScheme' for more info on supported placeholders)
	// Notes: - use slashes ('/' or '\') to separate between multiple sub-directories
	//        - you're allowed to use any characters between (or within) placeholders except the delimiters
	//          '<', '>' and ':'
	//        - handling of any non-ASCII chars will be controlled by '$handleNonASCIIChars' and unwanted
	//          characters can be excluded with the help of '$allowedDirNameCharacters' (see below)
	$dirNamingScheme = "<:firstAuthor:>/<:year:>"; // e.g. "<:firstAuthor:>/<:year:>"


	// Specify whether refbase shall rename uploaded files:
	// (note that if '$renameUploadedFiles' is set to "no", uploaded files will be blocked if they
	// contain any other characters than specified in '$allowedFileNameCharacters', see below)
	$renameUploadedFiles = "yes"; // possible values: "yes", "no"


	// Specify how to rename uploaded files:
	// Supported placeholders:
	// <:serial:>, <:authors:>, <:firstAuthor:>, <:secondAuthor:>, <:title:>, <:year:>, <:publication:>,
	// <:abbrevJournal:>, <:volume:>, <:issue:>, <:pages:>, <:startPage:>, <:endPage:>, <:keywords:>,
	// <:issn:>, <:isbn:>, <:issn_isbn:>, <:area:>, <:notes:>, <:userKeys:>, <:citeKey:>, <:doi:>,
	// <:recordIdentifier:>, <:randomNumber:>
	// Notes: - some of these placeholders offer options (e.g. how many words/items shall be extracted
	//          from the given field), please see the refbase online documentation for more info about
	//          placeholders and their syntax: <http://placeholders.refbase.net/>
	//        - existing file extensions will be kept untouched by this naming scheme
	//        - you're allowed to use any characters between (or within) placeholders except the delimiters
	//          '<', '>' and ':'
	//        - handling of any non-ASCII chars will be controlled by '$handleNonASCIIChars' and unwanted
	//          characters can be excluded with the help of '$allowedFileNameCharacters' (see below)
	//        - it is strongly recommended to always include the '<:serial:>' placeholder in order to ensure
	//          truly unique file names, otherwise you'll risk files already on the server getting overwritten
	//          by newly uploaded files (that got assigned the same name)
	$fileNamingScheme = "<:serial:>_<:authors:><:year:>"; // e.g. "<:serial:>_<:authors:><:year:>"


	// Specify how non-ASCII characters shall be treated in file/directory names:
	//   - "strip": removes any non-ASCII characters
	//   - "keep": keeps any non-ASCII characters (which, depending on your file system, may cause problems!)
	//   - "transliterate": attempts to transliterate most of the non-ASCII characters and strips all non-ASCII
	//                      chars that can't be converted into ASCII equivalents (this is the recommended option)
	$handleNonASCIIChars = "transliterate"; // possible values: "strip", "keep", "transliterate"


	// Specify all characters that will be allowed in file names:
	// In addition to the character conversion invoked by '$handleNonASCIIChars' (see above), this variable
	// allows you to further restrict generation of file names to a particular set of characters.
	// If '$renameUploadedFiles' is set to "no", uploaded files will be blocked if they contain any other
	// characters than specified here. If '$renameUploadedFiles' is set to "yes", any characters which
	// are not listed below will be removed from the generated file name.
	// (given expression must be specified as contents of a perl-style regular expression character class -> see
	// note at the end of this file; you can simply specify an empty string if you don't want any further character
	// conversion: '$allowedFileNameCharacters = "";')
	$allowedFileNameCharacters = "a-zA-Z0-9+_.-"; // e.g. "a-zA-Z0-9+_.-"

	// Specify all characters that will be allowed in directory names:
	// (same notes apply as for '$allowedFileNameCharacters')
	$allowedDirNameCharacters = "a-zA-Z0-9+_-"; // e.g. "a-zA-Z0-9+_-"


	// Choose whether case transformations shall be applied to the file/directory names:
	//   - "no": don't perform any case tranformations (i.e, keep the file/directory names as is)
	//   - "lower": transform all characters to lower case
	//   - "upper": transform all characters to upper case
	// (note that case transformations will be always performed regardless of any other file/directory
	// related settings)
	// File names:
	$changeCaseInFileNames = "no"; // possible values: "no", "lower", "upper"

	// DIR names:
	$changeCaseInDirNames = "lower"; // possible values: "no", "lower", "upper"


	// Default options for placeholders used by the file/directory name, cite key and
	// link auto-generation features:
	// Notes: - please see the refbase online documentation for more info about placeholders and
	//          their syntax: <http://placeholders.refbase.net/>
	//
	// Default options for '<:authors:>':
	// syntax: "[USE_MAX_NUMBER_OF_AUTHORS|AUTHOR_CONNECTOR|ET_AL_IDENTIFIER]"
	$extractDetailsAuthorsDefault = "[2|+|_etal]"; // e.g. "[2|+|_etal]"

	// Default options for '<:title:>':
	// syntax: "[NUMBER_OF_WORDS_FROM_TITLE_FIELD]"
	$extractDetailsTitleDefault = "[1]"; // e.g. "[1]"

	// Default options for '<:year:>':
	// syntax: "[DIGIT_FORMAT]"
	$extractDetailsYearDefault = "[4]"; // possible values: "[2]", "[4]"

	// Default options for '<:publication:>':
	// syntax: "[NUMBER_OF_WORDS_FROM_PUBLICATION_FIELD]"
	$extractDetailsPublicationDefault = "[3]"; // e.g. "[3]"

	// Default options for '<:abbrevJournal:>':
	// syntax: "[NUMBER_OF_WORDS_FROM_ABBREVJOURNAL_FIELD]"
	$extractDetailsAbbrevJournalDefault = "[3]"; // e.g. "[3]"

	// Default options for '<:keywords:>':
	// syntax: "[NUMBER_OF_ITEMS_FROM_KEYWORDS_FIELD]"
	$extractDetailsKeywordsDefault = "[1]"; // e.g. "[1]"

	// Default options for '<:area:>':
	// syntax: "[NUMBER_OF_ITEMS_FROM_AREA_FIELD]"
	$extractDetailsAreaDefault = "[1]"; // e.g. "[1]"

	// Default options for '<:notes:>':
	// syntax: "[NUMBER_OF_WORDS_FROM_NOTES_FIELD]"
	$extractDetailsNotesDefault = "[1]"; // e.g. "[1]"

	// Default options for '<:userKeys:>':
	// syntax: "[NUMBER_OF_ITEMS_FROM_USERKEYS_FIELD]"
	$extractDetailsUserKeysDefault = "[1]"; // e.g. "[1]"

	// Default options for '<:randomNumber:>':
	// syntax: "[MINIMUM_NUMBER|MAXIMUM_NUMBER]"
	// specify an empty string (or "[|]") to use the maximum possible range: '$extractDetailsRandomNumberDefault = "";'
	$extractDetailsRandomNumberDefault = "[0|99999]"; // e.g. "[0|99999]"


	// Specify which links shall be displayed (if available and if the Links column is visible):
	// (note that for List and Citation view, only one link will be printed for links of type
	// url/doi/isbn/xref; order of preference: doi, url, isbn, xref)
	// possible array items: "details", "edit", "file", "url", "doi", "isbn", "xref"
	// List view:
	$showLinkTypesInListView = array("details", "edit", "file", "url", "doi", "isbn", "xref");

	// Citation view:
	$showLinkTypesInCitationView = array("details", "file");


	// Specify the base URL to an OpenURL resolver:
	// Notes: - more info about the OpenURL standard (including pointers to further documentation) is available at <http://en.wikipedia.org/wiki/OpenURL>
	//        - specify an empty string if you don't want any auto-generation of OpenURL links: '$openURLResolver = "";'
	$openURLResolver = "http://www.crossref.org/openurl?pid=rfbs:rfbs228";
	// Examples for OpenURL resolvers:
	// - Generic OpenURL resolver provided by CrossRef: "http://www.crossref.org/openurl?pid=rfbs:rfbs228" ('pid=rfbs:rfbs228' identifies refbase as a registered CrossRef member)
	// - 1Cate, the OpenURL link-server from ISI/Openly: "http://isi.1cate.com/?sid=ISI:WoS"
	// - WorldCat OpenURL resolver which redirects to "the best" resolver for the client IP address: "http://worldcatlibraries.org/registry/gateway"


	// Define the format for an ISBN lookup URL:
	// Notes: - obviously, the ISBN link will only get auto-generated if an ISBN number is present
	//        - specify an empty string if you don't want any auto-generation of ISBN links: '$isbnURLFormat = "";'
	$isbnURLFormat = "http://isbn.nu/<:isbn:>";
	// e.g. isbn.nu ISBN resolver: "http://isbn.nu/<:isbn:>"


	// If your institution has access to particular databases of the "Cambridge Scientific Abstracts"
	// (CSA) Internet Database Service (http://www.csa1.co.uk/csa/index.html), you can specify the
	// direct URL to the database(s) below. Why that? The 'import.php' script offers an import form
	// that enables a user to import records from the CSA Internet Database Service. The URL you specify
	// here will appear as link within the explanatory text of 'import.php' pointing your users
	// directly to the CSA databases you have access to.
	// e.g. "http://www.csa1.co.uk/htbin/dbrng.cgi?username=...&amp;access=...&amp;cat=aquatic&amp;quick=1"
	$importCSArecordsURL = "http://www.csa1.co.uk/csa/index.html";


	// The search & replace actions defined in file 'includes/transtab_refbase_html.inc.php' will be
	// applied to the 'title', 'address', 'keywords' and 'abstract' fields (the list of fields can be
	// modified below, see '$searchReplaceActionsArray'). This feature is meant to provide richer text
	// capabilities (like displaying italics or super-/subscript) from the plain text data delivered by
	// the MySQL database. It works by means of "human readable markup" that's used within the plain
	// text fields of the database to define rich text characters. E.g., if you enclose a particular
	// word by substrings (like '_in-situ_') this word will be output in italics. Similarly, '**word**'
	// will print the word in boldface, 'CO[sub:2]' will cause the number in 'CO2' to be set as
	// subscript while '[delta]' will produce a greek delta letter. Feel free to customize this markup
	// scheme to your needs (the left column of the array in 'transtab_refbase_html.inc.php' represents
	// regular expression patterns matching the human readable markup that's used in your database while
	// the right column represents the equivalent HTML encoding). If you do not wish to perform any
	// search and replace actions, just specify an empty array, like: '$transtab_refbase_html =
	// array();'.
	include 'includes/transtab_refbase_html.inc.php'; // include refbase markup -> HTML search & replace patterns


	// Defines search & replace 'actions' that will be applied to all those refbase fields that are listed in the corresponding 'fields' element
	// upon WEB DISPLAY. Search & replace patterns must be specified as perl-style regular expression (including the leading & trailing slashes)
	// and may include mode modifiers (such as '/.../i' to perform a case insensitive match) -> see note at the end of this file.
	// If you don't want to perform any search and replace actions, specify an empty array, like: '$searchReplaceActionsArray = array();')
	// 								"/Search Pattern/"    =>  "Replace Pattern"
	$searchReplaceActionsArray = array(
										array(
												'fields'  => array("title", "address", "keywords", "abstract", "orig_title", "series_title", "abbrev_series_title", "notes"),
												'actions' => $transtab_refbase_html // perform search & replace actions that provide for human readable markup (as defined in 'includes/transtab_refbase_html.inc.php')
											)
//										,
//										array(
//												'fields'  => array("address", "abstract"),
//												'actions' => array(
//																	"/((?:ht|f)tp:\/\/[^ \"<>\r\n]+?)(?=&gt;|&quot;|[ \"<>\r\n]|$)/" => "<a target='_new' href='\\1'>\\1</a>", // generate clickable links from any web addresses
//																	"/([0-9a-zA-Z._-]+)@([0-9a-zA-Z._-]+)\\.([a-zA-Z]{2,3})/"        => "<a href='mailto:\\1@\\2.\\3'>\\1@\\2.\\3</a>" // generate clickable links from any email addresses
//																)
//											)
									);

	// --------------------------------------------------------------------

	// Note regarding the use of regular expressions:

	// Certain variables within this file expect you to enter search patterns as either "MySQL
	// extended" or "perl-style" regular expression. While regular expressions provide a powerful
	// syntax for searching they may be somewhat difficult to write and daunting if you're new to the
	// concept. If you require help coming up with a correct regular expression that matches your
	// needs, you may want to visit <http://grep.extracts.de/> for pointers to language-specific
	// documentation, tutorials, books and regex-aware applications. Alternatively, you're welcome to
	// post a message to the refbase help forum: <http://sourceforge.net/forum/forum.php?forum_id=218758>

?>
