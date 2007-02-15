# Project:    Web Reference Database (refbase) <http://www.refbase.net>
# Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
#             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
#             Please see the GNU General Public License for more details.
# File:       ./install.sql
# Created:    02-Oct-04, 20:11
# Modified:   13-Feb-07, 17:59

# MySQL database structure & initial data (for use with 'latin1' character set)

# created with phpMyAdmin (version 2.5.5-pl1) http://www.phpmyadmin.net

# --------------------------------------------------------

#
# table structure for table `auth`
#

DROP TABLE IF EXISTS `auth`;
CREATE TABLE `auth` (
  `user_id` mediumint(8) unsigned NOT NULL default '0',
  `email` varchar(50) NOT NULL default '',
  `password` varchar(15) NOT NULL default '',
  PRIMARY KEY  (`user_id`)
) TYPE=MyISAM;

#
# data for table `auth`
#

INSERT INTO `auth` VALUES (1, 'user@refbase.net', 'usLtr5Vq964qs');

# --------------------------------------------------------

#
# table structure for table `deleted`
#

DROP TABLE IF EXISTS `deleted`;
CREATE TABLE `deleted` (
  `author` text,
  `address` text,
  `corporate_author` varchar(255) default NULL,
  `first_author` varchar(100) default NULL,
  `author_count` tinyint(3) unsigned default NULL,
  `title` text,
  `orig_title` text,
  `publication` varchar(255) default NULL,
  `abbrev_journal` varchar(100) default NULL,
  `year` smallint(6) default NULL,
  `volume` varchar(50) default NULL,
  `volume_numeric` smallint(5) unsigned default NULL,
  `issue` varchar(50) default NULL,
  `pages` varchar(50) default NULL,
  `first_page` mediumint(8) unsigned default NULL,
  `keywords` text,
  `abstract` text,
  `edition` tinyint(3) unsigned default NULL,
  `editor` text,
  `publisher` varchar(255) default NULL,
  `place` varchar(100) default NULL,
  `MEDIUM` varchar(50) default NULL,
  `series_editor` text,
  `series_title` text,
  `abbrev_series_title` varchar(100) default NULL,
  `series_volume` varchar(50) default NULL,
  `series_volume_numeric` smallint(5) unsigned default NULL,
  `series_issue` varchar(50) default NULL,
  `issn` varchar(100) default NULL,
  `isbn` varchar(100) default NULL,
  `language` varchar(100) default NULL,
  `summary_language` varchar(100) default NULL,
  `area` varchar(255) default NULL,
  `TYPE` varchar(100) default NULL,
  `thesis` enum('Bachelor''s thesis','Master''s thesis','Ph.D. thesis','Diploma thesis','Doctoral thesis','Habilitation thesis') default NULL,
  `expedition` varchar(255) default NULL,
  `doi` varchar(100) default NULL,
  `conference` varchar(255) default NULL,
  `url` varchar(255) default NULL,
  `call_number` text,
  `location` text,
  `contribution_id` varchar(100) default NULL,
  `online_publication` enum('no','yes') NOT NULL default 'no',
  `online_citation` varchar(255) default NULL,
  `FILE` varchar(255) default NULL,
  `notes` text,
  `serial` mediumint(8) unsigned NOT NULL auto_increment,
  `orig_record` mediumint(9) default NULL,
  `approved` enum('no','yes') NOT NULL default 'no',
  `created_date` date default NULL,
  `created_time` time default NULL,
  `created_by` varchar(100) default NULL,
  `modified_date` date default NULL,
  `modified_time` time default NULL,
  `modified_by` varchar(100) default NULL,
  `deleted_date` date default NULL,
  `deleted_time` time default NULL,
  `deleted_by` varchar(100) default NULL,
  PRIMARY KEY  (`serial`)
) TYPE=MyISAM;

#
# data for table `deleted`
#

# --------------------------------------------------------

#
# table structure for table `depends`
#

DROP TABLE IF EXISTS `depends`;
CREATE TABLE `depends` (
  `depends_id` mediumint(8) unsigned NOT NULL auto_increment,
  `depends_external` varchar(100) default NULL,
  `depends_enabled` enum('true','false') NOT NULL default 'true',
  `depends_path` varchar(255) default NULL,
  PRIMARY KEY  (`depends_id`)
) TYPE=MyISAM;

#
# data for table `depends`
#

INSERT INTO `depends` VALUES (1, 'refbase', 'true', NULL),
(2, 'bibutils', 'true', NULL);

# --------------------------------------------------------

#
# table structure for table `formats`
#

DROP TABLE IF EXISTS `formats`;
CREATE TABLE `formats` (
  `format_id` mediumint(8) unsigned NOT NULL auto_increment,
  `format_name` varchar(100) default NULL,
  `format_type` enum('export','import','cite') NOT NULL default 'export',
  `format_enabled` enum('true','false') NOT NULL default 'true',
  `format_spec` varchar(255) default NULL,
  `order_by` varchar(25) default NULL,
  `depends_id` mediumint(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (`format_id`),
  KEY `format_name` (`format_name`)
) TYPE=MyISAM;

#
# data for table `formats`
#

INSERT INTO `formats` VALUES (1, 'MODS XML', 'import', 'true', 'bibutils/import_modsxml2refbase.php', '06', 2), 
(2, 'MODS XML', 'export', 'true', 'export_modsxml.php', '06', 1), 
(3, 'Text (CSV)', 'export', 'false', 'export_textcsv.php', '07', 1), 
(4, 'BibTeX', 'import', 'true', 'bibutils/import_bib2refbase.php', '01', 2), 
(5, 'BibTeX', 'export', 'true', 'bibutils/export_xml2bib.php', '01', 2), 
(6, 'Endnote', 'import', 'true', 'bibutils/import_end2refbase.php', '02', 2), 
(7, 'Endnote', 'export', 'true', 'bibutils/export_xml2end.php', '02', 2), 
(8, 'Pubmed Medline', 'import', 'true', 'import_medline2refbase.php', '08', 1),
(9, 'Pubmed XML', 'import', 'true', 'bibutils/import_med2refbase.php', '09', 2), 
(10, 'RIS', 'import', 'true', 'import_ris2refbase.php', '03', 1), 
(11, 'RIS', 'export', 'true', 'bibutils/export_xml2ris.php', '03', 2), 
(12, 'ISI', 'import', 'true', 'import_isi2refbase.php', '04', 1), 
(13, 'CSA', 'import', 'true', 'import_csa2refbase.php', '05', 1),
(14, 'Copac', 'import', 'true', 'bibutils/import_copac2refbase.php', '10', 2),
(15, 'SRW XML', 'export', 'true', 'export_srwxml.php', '11', 1), 
(16, 'ODF XML', 'export', 'true', 'export_odfxml.php', '12', 1), 
(17, 'OpenSearch RSS', 'export', 'false', 'export_osrss.php', '13', 1),
(18, 'html', 'cite', 'true', 'formats/cite_html.php', '14', 1),
(19, 'RTF', 'cite', 'true', 'formats/cite_rtf.php', '15', 1),
(20, 'PDF', 'cite', 'true', 'formats/cite_pdf.php', '16', 1),
(21, 'LaTeX', 'cite', 'true', 'formats/cite_latex.php', '17', 1),
(22, 'Markdown', 'cite', 'true', 'formats/cite_markdown.php', '18', 1),
(23, 'ASCII', 'cite', 'true', 'formats/cite_ascii.php', '19', 1),
(24, 'RefWorks', 'import', 'true', 'import_refworks2refbase.php', '20', 1);

# --------------------------------------------------------

#
# table structure for table `languages`
#

DROP TABLE IF EXISTS `languages`;
CREATE TABLE `languages` (
  `language_id` mediumint(8) unsigned NOT NULL auto_increment,
  `language_name` varchar(50) default NULL,
  `language_enabled` enum('true','false') NOT NULL default 'true',
  `order_by` varchar(25) default NULL,
  PRIMARY KEY  (`language_id`),
  KEY `language_name` (`language_name`)
) TYPE=MyISAM;

#
# data for table `languages`
#

INSERT INTO `languages` VALUES (1, 'en', 'true', '1'), 
(2, 'de', 'true', '2'), 
(3, 'fr', 'true', '3');

# --------------------------------------------------------

#
# table structure for table `queries`
#

DROP TABLE IF EXISTS `queries`;
CREATE TABLE `queries` (
  `query_id` mediumint(8) unsigned NOT NULL auto_increment,
  `user_id` mediumint(8) unsigned NOT NULL default '0',
  `query_name` varchar(255) default NULL,
  `display_type` varchar(25) default NULL,
  `view_type` varchar(25) default NULL,
  `query` text,
  `show_query` tinyint(3) unsigned default NULL,
  `show_links` tinyint(3) unsigned default NULL,
  `show_rows` mediumint(8) unsigned default NULL,
  `cite_style_selector` varchar(50) default NULL,
  `cite_order` varchar(25) default NULL,
  `last_execution` datetime default NULL,
  PRIMARY KEY  (`query_id`),
  KEY `user_id` (`user_id`,`query_name`)
) TYPE=MyISAM;

#
# data for table `queries`
#

INSERT INTO `queries` VALUES (1, 1, 'My refs edited today', '', 'Web', 'SELECT author, title, year, publication, modified_by, modified_time FROM refs WHERE location RLIKE "user@refbase.net" AND modified_date = CURDATE() ORDER BY modified_time DESC', 0, 1, 5, '', '', '2004-06-02 18:37:07'),
(2, 1, 'My refs (print view)', 'Show', 'Print', 'SELECT author, title, year, publication, volume, pages FROM refs LEFT JOIN user_data ON serial = record_id AND user_id = 1 WHERE location RLIKE "user@refbase.net" ORDER BY author, year DESC, publication', 0, 1, 50, '', '', '2004-07-30 22:37:02'),
(3, 1, 'My refs (keys & groups)', '', 'Web', 'SELECT author, title, year, publication, user_keys, user_groups FROM refs LEFT JOIN user_data ON serial = record_id AND user_id = 1 WHERE location RLIKE "user@refbase.net" ORDER BY author, year DESC, publication', 0, 1, 5, '', '', '2004-07-30 23:24:28'),
(4, 1, 'Abstracts (print view)', '', 'Print', 'SELECT author, year, abstract FROM refs WHERE serial RLIKE ".+" ORDER BY author, year DESC, publication', 0, 1, 5, '', '', '2004-07-30 22:36:48');

# --------------------------------------------------------

#
# table structure for table `refs`
#

DROP TABLE IF EXISTS `refs`;
CREATE TABLE `refs` (
  `author` text,
  `address` text,
  `corporate_author` varchar(255) default NULL,
  `first_author` varchar(100) default NULL,
  `author_count` tinyint(3) unsigned default NULL,
  `title` text,
  `orig_title` text,
  `publication` varchar(255) default NULL,
  `abbrev_journal` varchar(100) default NULL,
  `year` smallint(6) default NULL,
  `volume` varchar(50) default NULL,
  `volume_numeric` smallint(5) unsigned default NULL,
  `issue` varchar(50) default NULL,
  `pages` varchar(50) default NULL,
  `first_page` mediumint(8) unsigned default NULL,
  `keywords` text,
  `abstract` text,
  `edition` tinyint(3) unsigned default NULL,
  `editor` text,
  `publisher` varchar(255) default NULL,
  `place` varchar(100) default NULL,
  `MEDIUM` varchar(50) default NULL,
  `series_editor` text,
  `series_title` text,
  `abbrev_series_title` varchar(100) default NULL,
  `series_volume` varchar(50) default NULL,
  `series_volume_numeric` smallint(5) unsigned default NULL,
  `series_issue` varchar(50) default NULL,
  `issn` varchar(100) default NULL,
  `isbn` varchar(100) default NULL,
  `language` varchar(100) default NULL,
  `summary_language` varchar(100) default NULL,
  `area` varchar(255) default NULL,
  `TYPE` varchar(100) default NULL,
  `thesis` enum('Bachelor''s thesis','Master''s thesis','Ph.D. thesis','Diploma thesis','Doctoral thesis','Habilitation thesis') default NULL,
  `expedition` varchar(255) default NULL,
  `doi` varchar(100) default NULL,
  `conference` varchar(255) default NULL,
  `url` varchar(255) default NULL,
  `call_number` text,
  `location` text,
  `contribution_id` varchar(100) default NULL,
  `online_publication` enum('no','yes') NOT NULL default 'no',
  `online_citation` varchar(255) default NULL,
  `FILE` varchar(255) default NULL,
  `notes` text,
  `serial` mediumint(8) unsigned NOT NULL auto_increment,
  `orig_record` mediumint(9) default NULL,
  `approved` enum('no','yes') NOT NULL default 'no',
  `created_date` date default NULL,
  `created_time` time default NULL,
  `created_by` varchar(100) default NULL,
  `modified_date` date default NULL,
  `modified_time` time default NULL,
  `modified_by` varchar(100) default NULL,
  PRIMARY KEY  (`serial`)
) TYPE=MyISAM;

#
# data for table `refs`
#

INSERT INTO `refs` VALUES ('Chapelle, G; Peck, LS', NULL, NULL, 'Chapelle, G', 2, 'Polar gigantism dictated by oxygen availability', NULL, 'Nature', 'Nature', 1999, '399', 399, NULL, '114-115', 114, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0028-0836', NULL, 'English', NULL, 'Black Sea; Caspian Sea; Lake Baikal', 'Journal Article', NULL, NULL, NULL, NULL, NULL, 'refbase @ user @ 137', 'Initial refbase user (user@refbase.net)', NULL, 'no', NULL, NULL, NULL, 1, NULL, 'no', '2001-04-30', '18:56:26', 'Initial refbase user (user@refbase.net)', '2004-01-08', '21:20:55', 'Initial refbase user (user@refbase.net)'),
('Hilmer, M', NULL, NULL, 'Hilmer, M', 1, 'A model study of Arctic sea ice variability', NULL, NULL, NULL, 2001, NULL, NULL, NULL, '157 pp', 157, NULL, NULL, NULL, NULL, 'Inst Meereskunde', 'Kiel', 'pp', NULL, 'Berichte aus dem Institut für Meereskunde an der Christian-Albrechts-Universität Kiel', 'Ber Inst Meereskd Christian-Albrechts-Univ Kiel', '320', 320, NULL, '0341-8561', NULL, 'English', 'English; German', 'Arctic Ocean', 'Book Whole', 'Doctoral thesis', NULL, NULL, NULL, NULL, 'refbase @ user @ 468', 'Initial refbase user (user@refbase.net)', NULL, 'no', NULL, NULL, NULL, 2, NULL, 'yes', '2001-10-16', '17:33:46', 'Initial refbase user (user@refbase.net)', '2004-06-27', '13:27:54', 'Initial refbase user (user@refbase.net)'),
('Hobson, KA; Ambrose Jr, WG; Renaud, PE', 'Canadian Wildlife Service, 115 Perimeter Road, Saskatoon, SK S7N 0X4, Canada', NULL, 'Hobson, KA', 3, 'Sources of primary production, benthic-pelagic coupling, and trophic relationships within the Northeast Water Polynya: Insights from [delta][super:13]C and [delta][super:15]N analysis', NULL, 'Marine Ecology Progress Series', 'Mar Ecol Prog Ser', 1995, '128', 128, '1-3', '1-10', 1, 'phytobenthos; polynyas; carbon 13; nitrogen isotopes; food webs; check lists; trophic structure; Algae; PNE, Greenland, Northeast Water Polynya', 'We used stable carbon ([super:13]C/[super:12]C) and nitrogen ([super:15]N/[super:14]N) isotope analysis to investigate linkages between sources of primary production and the pelagic and benthic components of the Northeast Water (NEW) Polynya off northeastern Greenland. Ice algae was enriched in [super:13]C (mean [delta][super:13]C = -18.6 vs -27.9 ppt) and [super:15]N (mean [delta][super:15]N = 8.3 vs 4.9 ppt) over particulate organic matter (POM) suggesting that the relative importance of these sources might be traced isotopically. Most grazing crustaceans and filter-feeding bivalves had [delta][super:13]C and [delta][super:15]N values in the range of -21 to -23 ppt and 7 to 9 ppt, respectively, indicating a direct pathway from POM. Close benthic-pelagic coupling was also confirmed for other benthic organisms examined with the exception of the predatory or deposit feeding echinoderms _Ophiocten_, _Ophiacantha_ and _Pontaster_. Compared with other Arctic and temperate marine food webs, stable-carbon isotope values for the NEW Polynya were depleted in [super:13]C. A [delta][super:15]N trophic model that incorporated taxon-specific isotopic fractionation factors indicated that the NEW Polynya consisted of 4.5 to 5 trophic levels. Stable-isotope analysis may be well suited to establishing the importance of polynyas as sites of high primary productivity and tight benthic-pelagic coupling relative to regions of more permanent ice cover.', NULL, NULL, 'Inter-Research', 'Oldendorf/Luhe', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0171-8630', NULL, 'English', 'English', 'Northeast Water Polynya; Northeast Greenland Shelf; Arctic', 'Journal Article', NULL, NULL, NULL, NULL, NULL, 'refbase @ user @ 133', 'Initial refbase user (user@refbase.net)', NULL, 'no', NULL, 'marecolprogser/Hobson_et_al1995.pdf', 'Bibliogr.: 63 ref.', 3, -3, 'no', '2001-04-30', '18:43:59', 'Initial refbase user (user@refbase.net)', '2004-05-25', '00:29:50', 'Initial refbase user (user@refbase.net)'),
('Hobson, KA; Ambrose Jr, WG; Renaud, PE', NULL, NULL, 'Hobson, KA', 3, 'Sources of primary production, benthic-pelagic coupling, and trophic relationships within the Northeast Water Polynya: insights from [delta][super:13]C and [delta][super:15]N analysis', NULL, 'Marine Ecology Progress Series', 'Mar Ecol Prog Ser', 1995, '128', 128, NULL, '1-10', 1, 'ARK; Greenland; NEW; Polynya; Isotopes; 13C; 15N; Benthos; Food', NULL, NULL, NULL, 'Inter-Research', 'Oldendorf/Luhe', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0171-8630', NULL, 'English', 'English', NULL, 'Journal Article', NULL, NULL, NULL, NULL, NULL, 'refbase @ user @ NEW Zonation(96)', 'Initial refbase user (user@refbase.net)', NULL, 'no', NULL, NULL, NULL, 10, 3, 'no', '2002-08-22', '23:02:10', 'Initial refbase user (user@refbase.net)', '2004-05-25', '00:30:56', 'Initial refbase user (user@refbase.net)'),
('Schleser, GH; Jayasekera, R', NULL, NULL, 'Schleser, GH', 2, '[delta][super:13]C-variations of leaves in forests as an indication of reassimilated CO[sub:2] from the soil', NULL, 'Oecologia', 'Oecologia', 1985, '65', 65, NULL, '536-542', 536, 'soil respiration; photosynthesis; vascular plant', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Journal Article', NULL, NULL, NULL, NULL, NULL, 'refbase @ user @ ms', 'Initial refbase user (user@refbase.net)', NULL, 'no', NULL, NULL, NULL, 11, NULL, 'no', '2002-10-21', '14:42:41', 'Initial refbase user (user@refbase.net)', '2004-01-08', '21:18:56', 'Initial refbase user (user@refbase.net)'),
('Lohrmann, A; Cabrera, R; Kraus, NC', NULL, NULL, 'Lohrmann, A', 3, 'Acoustic-doppler velocimeter (ADV) for laboratory use', NULL, 'Fundamentals and advancements in hydraulic measurements and experimentation. Proceedings, Hydraulic Division/ASCE, August 1994', NULL, 1994, NULL, NULL, NULL, '351-365', 351, 'methods; flow; flume', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'English', NULL, NULL, 'Journal Article', NULL, NULL, NULL, 'Symposium on fundamentals and advancements in hydraulic measurements and experimentation', NULL, 'refbase @ user @ ', 'Initial refbase user (user@refbase.net)', NULL, 'no', NULL, 'http://www.nortek-as.com/biblio/N4000-702.pdf', NULL, 8, NULL, 'no', '2002-10-24', '15:48:28', 'Initial refbase user (user@refbase.net)', '2004-01-08', '21:19:18', 'Initial refbase user (user@refbase.net)'),
('Thomas, DN; Dieckmann, GS (eds)', 'Thomas: School of Ocean Sciences, University of Wales, Bangor, UK; Dieckmann: Alfred Wegener Institute for Polar and Marine Research, Bremerhaven, Germany', NULL, 'Thomas, DN', 2, 'Sea ice - an introduction to its physics, chemistry, biology and geology', NULL, NULL, NULL, 2003, NULL, NULL, NULL, '402 pp', 402, 'Sea Ice', 'Sea ice, which covers up to 7% of the planet\'s surface, is a major component of the world\'s oceans, partly driving ocean circulation and global climate patterns. It provides a habitat for a rich diversity of marine organisms, and is a valuable source of information in studies of global climate change and the evolution of present day life forms. Increasingly, sea ice is being used as a proxy for extraterrestrial ice covered systems.\r\n\r\n_Sea Ice_ provides a comprehensive review of our current available knowledge of polar pack ice, the study of which is severely constrained by the logistic difficulties of working in such harsh and remote regions of the earth. The book\'s editors, Drs Thomas and Dieckmann have drawn together an impressive group of international contributing authors, providing a well-edited and integrated volume, which will stand for many years as the standard work on the subject. Contents of the book include details of the growth, microstructure and properties of sea ice, large-scale variations in thickness and characteristics, its primary production, micro-and macrobiology, sea ice as a habitat for birds and mammals, sea ice biogeochemistry, particulate flux, and the distribution and significance of palaeo sea ice.', NULL, 'Thomas, DN; Dieckmann, GS', 'Blackwell Science Ltd', 'Oxford', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0-632-05808-0', 'English', NULL, NULL, 'Book Whole', NULL, NULL, NULL, NULL, 'http://www.blackwellpublishing.com/book.asp?ref=0632058080&site=1', 'refbase @ user @ library-34/436/1', 'Initial refbase user (user@refbase.net)', NULL, 'no', NULL, NULL, '40 Illustrations', 7, NULL, 'yes', '2003-12-02', '13:27:50', 'Initial refbase user (user@refbase.net)', '2004-01-08', '21:18:26', 'Initial refbase user (user@refbase.net)'),
('de Castellvi, J (ed)', NULL, NULL, 'de Castellvi, J', 1, 'Actas des tercer symposium espanol de estudios Antarcticos. Gredos, 3 al 5 de octubre de 1989', NULL, NULL, NULL, 1990, NULL, NULL, NULL, '379 pp', 379, NULL, NULL, NULL, 'de Castellvi, J', 'Comision interministerial de Cienctia y Technologia', 'Madrid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Spanish', NULL, NULL, 'Book Whole', NULL, NULL, NULL, NULL, NULL, 'refbase @ user @ library-32/19/20', 'Initial refbase user (user@refbase.net)', NULL, 'no', NULL, NULL, NULL, 9, NULL, 'no', '1997-08-22', '00:00:00', 'Initial refbase user (user@refbase.net)', '2004-01-08', '21:20:49', 'Initial refbase user (user@refbase.net)'),
('Aberle, N; Witte, U', 'Aberle, Witte: Max Planck Institute for Marine Microbiology, Celsiusstr. 1, 28359 Bremen, Germany; Aberle: Present address: Max Planck Institute for Limnology, August-Thienemann-Str. 2, 24306 Plön, Germany; Email: aberle@mpil-ploen.mpg.de', NULL, 'Aberle, N', 2, 'Deep-sea macrofauna exposed to a simulated sedimentation event in the abyssal NE Atlantic: _in situ_ pulse-chase experiments using [super:13]C-labelled phytodetritus', NULL, 'Marine Ecology Progress Series', 'Mar Ecol Prog Ser', 2003, '251', 251, NULL, '37-47', 37, 'Deep-sea; Pulse-chase experiment; [delta][super:13]C; Benthic carbon remineralisation; Macrofauna; Atlantic Ocean, Porcupine Abyssal Plain', 'Tracer experiments with [super:13]C-labelled diatoms _Thalassiosira rotula_ (Bacillariophycea, 98% [super:13]C-labelled) were conducted at the Porcupine Abyssal Plain (PAP) in the NE Atlantic (BENGAL Station; 48°50\'N, 16°30\'W, 4850 m depth) during May/June 2000. _In situ_ enrichment experiments were carried out using deep-sea benthic chamber landers: within the chambers a spring bloom was simulated and the fate of this food-pulse within the abyssal macrobenthic community was followed. In focus was the role of different macrofauna taxa and their vertical distribution within the sediment column in consuming and reworking the freshly deposited material. _T. rotula_ is one of the most abundant pelagic diatoms in the NE Atlantic and therefore 0.2 g of freeze dried _T. rotula_, equivalent to 1 g algal C m[super:-2] yr[super:-1], was injected into each incubation chamber. Three different incubation times of 2.5, 8 and 23 d were chosen in order to follow the uptake of [super:13]C-labelled phytodetritus by macrofauna. After only 2.5 d, 77% of all macrofauna organisms showed tracer uptake. After 23 d the highest degree of enrichment was measured and 95% of the individuals had taken up [super:13]C from the introduced algal material. In addition to that a downward transport of organic matter was observed, even though the mixing was not very intense. The initial processing of carbon was dominated by polychaetes that made up a percentage of 52% of total macrofauna. In general macrofauna organisms that lived close to the sediment surface had higher access to the simulated food-pulse, confirming the hypothesis that individuals close to the sediment surface have the strongest impact on the decomposition of phytodetritus. In our study we observed only modest vertical entrainment of [super:13]C tracers into the sediment. With regard to contradictory results from former [super:13]C-enrichment experiments in bathyal regions, compared to results from our study site in the abyssal plain, we thus propose pronounced differences in feeding strategies between macrofauna communities from continental margins and abyssal plains.', NULL, NULL, 'Inter-Research', 'Oldendorf/Luhe', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0171-8630', NULL, 'English', 'English', 'NE Atlantic', 'Journal Article', NULL, NULL, NULL, NULL, 'http://www.int-res.com/abstracts/meps/v251/p37-47.html', 'refbase @ user @ 706', 'Initial refbase user (user@refbase.net)', NULL, 'no', NULL, 'marecolprogser/m251p037.pdf', NULL, 4, NULL, 'no', '2003-11-17', '17:36:44', 'Initial refbase user (user@refbase.net)', '2004-10-02', '18:18:59', 'refbase user (testuser@refbase.net)'),
('Bischof, K; Peralta, G; Kräbs, G; van de Poll, WH; Perez-Llorens, JL; Breeman, AM', NULL, NULL, 'Bischof, K', 3, 'Effects of solar UV-B radiation on canopy structure of _Ulva_ communities from southern Spain', NULL, 'Journal of Experimental Botany', 'J Exp Bot', 2002, '53', 53, '379', '2411-2421', 2411, 'canopy formation; photosynthesis; ultraviolet radiation; _Ulva rotundata_', 'Within the sheltered creeks of Cádiz bay, _Ulva_ thalli form extended mat-like canopies. The effect of solar ultraviolet radiation on photosynthetic activity, the composition of photosynthetic and xanthophyll cycle pigments, and the amount of RubisCO, chaperonin 60 (CPN 60), and the induction of DNA damage in _Ulva_ aff. _rotundata_ Bliding from southern Spain was assessed in the field. Samples collected from the natural community were covered by screening filters, generating different radiation conditions. During daily cycles, individual thalli showed photoinhibitory effects of the natural solar radiation. This inhibition was even more pronounced in samples only exposed to photosynthetically active radiation (PAR). Strongly increased heat dissipation in these samples indicated the activity of regulatory mechanisms involved in dynamic photoinhibition. Adverse effects of UV-B radiation on photosynthesis were only observed in combination with high levels of PAR, indicating the synergistic effects of the two wavelength ranges. In samples exposed either to PAR+UV-A or to UV-B+UV-A without PAR, no inhibition of photosynthetic quantum yield was found in the course of the day. At the natural site, the top layer of the mat-like canopies is generally completely bleached. Artificially designed _Ulva_ canopies exhibited fast bleaching of the top layer under the natural solar radiation conditions, while this was not observed in canopies either shielded from UV or from PAR. The bleached first layer of the canopies acts as a selective UV-B filter, and thus prevents subcanopy thalli from exposure to harmful radiation. This was confirmed by the differences in photosynthetic activity, pigment composition, and the concentration of RubisCO in thalli with different positions within the canopy. In addition, the induction of the stress protein CPN 60 under UV exposure and the low accumulation of DNA damage indicate the presence of physiological protection mechanisms against harmful UV-B. A mechanism of UV-B-induced inhibition of photosynthesis under field conditions is proposed.', NULL, NULL, 'Oxford University Press', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'English', 'English', 'southern Spain', 'Journal Article', NULL, NULL, '10.1093/jxb/erf091', NULL, 'http://jxb.oupjournals.org/cgi/content/abstract/53/379/2411', 'refbase @ user @ ', 'Initial refbase user (user@refbase.net)', NULL, 'no', NULL, 'jexpbot/jxb-erf091.pdf', NULL, 12, NULL, 'no', '2003-11-17', '17:47:02', 'Initial refbase user (user@refbase.net)', '2004-05-24', '22:50:50', 'Initial refbase user (user@refbase.net)'),
('Amon, RMW; Budéus, G; Meon, B', NULL, NULL, 'Amon, RMW', 3, 'Dissolved organic carbon distribution and origin in the Nordic Seas: Exchanges with the Arctic Ocean and the North Atlantic', NULL, 'Journal of Geophysical Research', 'J Geophys Res', 2003, '108', 108, 'C7', NULL, NULL, 'dissolved organic matter; dissolved organic carbon; chromophoric dissolved organic matter; fluorescence; vertical carbon transport', 'Dissolved organic carbon (DOC) and in situ fluorescence were measured along with hydrographic parameters in the Greenland, Iceland, and Norwegian Seas (Nordic Seas). Surface (<100 m) concentrations of DOC ranged from 60 to 118 µM with elevated values in the East Greenland Current (EGC) which transports water from the Arctic Ocean to the North Atlantic. EGC surface waters also showed a pronounced fluorescence maximum between 30 and 120 m depth in all EGC sections indicating the abundance of Arctic river derived DOC in this current. Based on fluorescence we estimated that 20-50% of the annual river discharge to the Arctic Ocean was exported in the EGC. The fluorescence maximum was typically associated with salinity around 33 and temperatures below -1°C which are characteristic of surface and upper halocline water in the Arctic Ocean. The elevated fluorescence in this water mass suggests a strong Eurasian shelf component and also suggests that in situ fluorescence could be used to trace Eurasian shelf water in the central Arctic Ocean. DOC concentrations in the Nordic Sea basins (>1000 m) were relatively high (~50 µM DOC) compared with other ocean basins indicating active vertical transport of DOC in this region on decadal timescales. Based on existing vertical transport estimates and 15 µM of semilabile DOC we calculated an annual vertical net DOC export of 3.5 Tg C yr[super:-1] in the Greenland Sea and about 36 Tg C yr[super:-1] for the entire Arctic Mediterranean Sea (AMS) including the Greenland-Scotland Ridge overflow. It appears that physical processes play a determining role for the distribution of DOC in the AMS.', NULL, NULL, 'American Geophysical Union', 'Washington, DC', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'English', 'English', 'Nordic Seas', 'Journal Article', NULL, NULL, '10.1029/2002JC001594', NULL, 'http://www.agu.org/pubs/crossref/2003/2002JC001594.shtml', 'refbase @ user @ ms', 'Initial refbase user (user@refbase.net)', NULL, 'yes', '3221', 'jgeophysres/2002JC001594.pdf', NULL, 5, NULL, 'no', '2003-11-23', '14:28:56', 'Initial refbase user (user@refbase.net)', '2004-05-24', '22:50:40', 'Initial refbase user (user@refbase.net)'),
('Gerland, S; Winther, J-G; Örbæk, JB; Ivanov, BV', 'Norwegian Polar Institute, Polar Environmental Centre, N-9296 Tromsoe, Norway', NULL, 'Gerland, S', 3, 'Physical properties, spectral reflectance and thickness development of first year fast ice in Kongsfjorden, Svalbard', NULL, 'Proceedings of the International Symposium on Polar Aspects of Global Change', NULL, 1999, NULL, NULL, NULL, '275-282', 275, 'Fast ice; Ice properties; Reflectance; Ice thickness; Physical properties; PNE, Norway, Svalbard, Kongsfjorden', 'A ground truth study was performed on first year fast ice in Kongsfjorden, Svalbard, during spring 1997 and 1998. The survey included sea ice thickness monitoring as well as observation of surface albedo, attenuation of optical radiation in the ice, physical properties and texture of snow and sea ice. The average total sea ice thickness in May was about 0.9 m, including a 0.2 m thick snow layer on top. Within a few weeks in both years, the snow melted almost completely, whereas the ice thickness decreased by not more than 0.05 m. During spring, the lower part of the snow refroze into a solid layer. The sea ice became more porous. Temperatures in the sea ice increased and the measurable salinity of the sea ice decreased with time. Due to snow cover thinning and snow grain growth, maximum surface albedo decreased from 0.96 to 0.74. Texture analysis on cores showed columnar ice with large crystals (max. crystal length > 0.1 m) below a 0.11 m thick mixed surface layer of granular ice with smaller crystals. In both years, we observed sea ice algae at the bottom part of the ice. This layer has a significant effect on the radiation transmissivity.', NULL, NULL, 'Norsk Polarinstitutt', NULL, NULL, NULL, 'Polar Research', 'Polar Res', '18', 18, '2', '0800-0395', NULL, 'English', 'English', NULL, 'Book Chapter', NULL, NULL, NULL, 'International Symposium on Polar Aspects of Global Change, Tromso (Norway), 24-28 Aug 1998', NULL, 'refbase @ user @ 726', 'Initial refbase user (user@refbase.net)', NULL, 'no', NULL, 'gerland_etal.99.doc', 'Conference', 6, NULL, 'no', '2003-11-24', '19:00:20', 'Initial refbase user (user@refbase.net)', '2004-05-24', '22:49:56', 'Initial refbase user (user@refbase.net)');

# --------------------------------------------------------

#
# table structure for table `styles`
#

DROP TABLE IF EXISTS `styles`;
CREATE TABLE `styles` (
  `style_id` mediumint(8) unsigned NOT NULL auto_increment,
  `style_name` varchar(100) default NULL,
  `style_enabled` enum('true','false') NOT NULL default 'true',
  `style_spec` varchar(255) default NULL,
  `order_by` varchar(25) default NULL,
  `depends_id` mediumint(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (`style_id`),
  KEY `style_name` (`style_name`)
) TYPE=MyISAM;

#
# data for table `styles`
#

INSERT INTO `styles` VALUES
(1, 'APA', 'true', 'styles/cite_APA.php', 'A010', 1),
(2, 'MLA', 'true', 'styles/cite_MLA.php', 'A030', 1),
(3, 'Polar Biol', 'true', 'styles/cite_PolarBiol_MarBiol_MEPS.php', 'B010', 1),
(4, 'Mar Biol', 'true', 'styles/cite_PolarBiol_MarBiol_MEPS.php', 'B020', 1),
(5, 'MEPS', 'true', 'styles/cite_PolarBiol_MarBiol_MEPS.php', 'B030', 1),
(6, 'Deep Sea Res', 'true', 'styles/cite_DeepSeaRes.php', 'B040', 1),
(7, 'Ann Glaciol', 'true', 'styles/cite_AnnGlaciol_JGlaciol.php', 'B050', 1),
(8, 'J Glaciol', 'true', 'styles/cite_AnnGlaciol_JGlaciol.php', 'B060', 1),
(9, 'Text Citation', 'true', 'styles/cite_TextCitation.php', 'C010', 1);

# --------------------------------------------------------

#
# table structure for table `types`
#

DROP TABLE IF EXISTS `types`;
CREATE TABLE `types` (
  `type_id` mediumint(8) unsigned NOT NULL auto_increment,
  `type_name` varchar(100) default NULL,
  `type_enabled` enum('true','false') NOT NULL default 'true',
  `base_type_id` mediumint(8) unsigned default NULL,
  `order_by` varchar(25) default NULL,
  PRIMARY KEY  (`type_id`),
  KEY `type_name` (`type_name`)
) TYPE=MyISAM;

#
# data for table `types`
#

INSERT INTO `types` VALUES (1, 'Journal Article', 'true', 1, '1'),
(2, 'Book Chapter', 'true', 2, '2'),
(3, 'Book Whole', 'true', 3, '3'),
(4, 'Conference Article', 'true', 2, '4'),
(5, 'Conference Volume', 'true', 3, '5'),
(6, 'Journal', 'true', 3, '6'),
(7, 'Manuscript', 'true', 3, '7'),
(8, 'Map', 'true', 3, '8');

# --------------------------------------------------------

#
# table structure for table `user_data`
#

DROP TABLE IF EXISTS `user_data`;
CREATE TABLE `user_data` (
  `data_id` mediumint(8) unsigned NOT NULL auto_increment,
  `user_id` mediumint(8) unsigned NOT NULL default '0',
  `record_id` mediumint(8) unsigned NOT NULL default '0',
  `marked` enum('no','yes') NOT NULL default 'no',
  `copy` enum('false','true','ordered','fetch') NOT NULL default 'false',
  `selected` enum('no','yes') NOT NULL default 'no',
  `user_keys` text,
  `user_notes` text,
  `user_file` varchar(255) default NULL,
  `user_groups` text,
  `cite_key` varchar(255) default NULL,
  `related` text,
  PRIMARY KEY  (`data_id`),
  KEY `user_id` (`user_id`,`record_id`)
) TYPE=MyISAM;

#
# data for table `user_data`
#

INSERT INTO `user_data` VALUES (1, 1, 1, 'no', 'true', 'no', 'Oxygen; Environmental Impact; Crustacea; Amphipoda', '', '', '', NULL, NULL),
(2, 1, 2, 'yes', 'false', 'no', 'Modeling; NAO; Ice Export; Ice Transport; Ice Thickness; Ice Extent / Cover; Ice Concentration; Ice Drift', 'Dissertation 2001, Mathematisch-Naturwissenschaftliche Fakultät der CAU Kiel', '', '', '', ''),
(3, 1, 3, 'no', 'true', 'no', 'Isotopes; Pelagic-Benthic Coupling; Polynya; Primary Production', '', '', 'Ecology; Primary Production; Stable Isotopes', '', 'orig_record RLIKE "^-?3$"'),
(4, 1, 4, 'yes', 'true', 'no', '', '', '', 'Ecology; Stable Isotopes', '', 'user_groups:Ecology'),
(5, 1, 5, 'no', 'fetch', 'no', '', '', '', '', '', ''),
(6, 1, 6, 'no', 'false', 'no', '', '', '', 'Ice', '', ''),
(7, 1, 7, 'yes', 'fetch', 'yes', '', '', '', 'Ice', NULL, NULL),
(8, 1, 8, 'no', 'false', 'no', '', '', '', NULL, NULL, NULL),
(9, 1, 9, 'no', 'false', 'no', '', '', '', '', NULL, NULL),
(10, 1, 10, 'no', 'false', 'no', '', '', '', 'Ecology; Primary Production; Stable Isotopes', '', '3'),
(11, 1, 11, 'no', 'false', 'no', NULL, NULL, NULL, 'Ecology; Stable Isotopes', NULL, NULL),
(12, 1, 12, 'no', 'false', 'no', '', '', '', '', '', '');

# --------------------------------------------------------

#
# table structure for table `user_formats`
#

DROP TABLE IF EXISTS `user_formats`;
CREATE TABLE `user_formats` (
  `user_format_id` mediumint(8) unsigned NOT NULL auto_increment,
  `format_id` mediumint(8) unsigned NOT NULL default '0',
  `user_id` mediumint(8) unsigned NOT NULL default '0',
  `show_format` enum('true','false') NOT NULL default 'true',
  PRIMARY KEY  (`user_format_id`),
  KEY `format_id` (`format_id`,`user_id`)
) TYPE=MyISAM;

#
# data for table `user_formats`
#

INSERT INTO `user_formats` VALUES (1, 1, 0, 'false'),
(2, 2, 0, 'false'),
(3, 3, 0, 'false'),
(4, 4, 0, 'false'),
(5, 5, 0, 'false'),
(6, 6, 0, 'false'),
(7, 7, 0, 'false'),
(8, 8, 0, 'false'),
(9, 9, 0, 'false'),
(10, 10, 0, 'false'),
(11, 11, 0, 'false'),
(12, 12, 0, 'false'),
(13, 13, 0, 'false'),
(14, 14, 0, 'false'),
(15, 16, 0, 'false'),
(16, 18, 0, 'true'),
(17, 19, 0, 'true'),
(18, 20, 0, 'true'),
(19, 21, 0, 'true'),
(20, 1, 1, 'true'),
(21, 2, 1, 'true'),
(22, 3, 1, 'false'),
(23, 4, 1, 'true'),
(24, 5, 1, 'true'),
(25, 6, 1, 'true'),
(26, 7, 1, 'true'),
(27, 8, 1, 'true'),
(28, 9, 1, 'true'),
(29, 10, 1, 'true'),
(30, 11, 1, 'true'),
(31, 12, 1, 'true'),
(32, 13, 1, 'true'),
(33, 14, 1, 'true'),
(34, 16, 1, 'true'),
(35, 18, 1, 'true'),
(36, 19, 1, 'true'),
(37, 20, 1, 'true'),
(38, 21, 1, 'true'),
(39, 22, 1, 'true'),
(40, 23, 1, 'true');

# --------------------------------------------------------

#
# table structure for table `user_options`
#

DROP TABLE IF EXISTS `user_options`;
CREATE TABLE `user_options` (
  `option_id` mediumint(8) unsigned NOT NULL auto_increment,
  `user_id` mediumint(8) unsigned NOT NULL default '0',
  `export_cite_keys` enum('yes','no') NOT NULL default 'yes',
  `autogenerate_cite_keys` enum('yes','no') NOT NULL default 'yes',
  `prefer_autogenerated_cite_keys` enum('no','yes') NOT NULL default 'no',
  `use_custom_cite_key_format` enum('no','yes') NOT NULL default 'no',
  `cite_key_format` varchar(255) default NULL,
  `uniquify_duplicate_cite_keys` enum('yes','no') NOT NULL default 'yes',
  `nonascii_chars_in_cite_keys` enum('transliterate','strip','keep') default NULL,
  `use_custom_text_citation_format` enum('no','yes') NOT NULL default 'no',
  `text_citation_format` varchar(255) default NULL,
  PRIMARY KEY  (`option_id`),
  KEY `user_id` (`user_id`)
) TYPE=MyISAM;

#
# data for table `user_options`
#

INSERT INTO `user_options` VALUES (1, 0, 'yes', 'yes', 'no', 'no', '<:authors:><:year:>', 'yes', NULL, 'no', '<:authors[2| & | et al.]:>< :year:>< {:recordIdentifier:}>'),
(2, 1, 'yes', 'yes', 'no', 'no', '<:firstAuthor:><:year:>', 'yes', NULL, 'no', '<:authors[2| & | et al.]:>< :year:>< {:recordIdentifier:}>');

# --------------------------------------------------------

#
# table structure for table `user_permissions`
#

DROP TABLE IF EXISTS `user_permissions`;
CREATE TABLE `user_permissions` (
  `user_permission_id` mediumint(8) unsigned NOT NULL auto_increment,
  `user_id` mediumint(8) unsigned NOT NULL default '0',
  `allow_add` enum('yes','no') NOT NULL default 'yes',
  `allow_edit` enum('yes','no') NOT NULL default 'yes',
  `allow_delete` enum('yes','no') NOT NULL default 'yes',
  `allow_download` enum('yes','no') NOT NULL default 'yes',
  `allow_upload` enum('yes','no') NOT NULL default 'yes',
  `allow_details_view` enum('yes','no') NOT NULL default 'yes',
  `allow_print_view` enum('yes','no') NOT NULL default 'yes',
  `allow_browse_view` enum('yes','no') NOT NULL default 'yes',
  `allow_cite` enum('yes','no') NOT NULL default 'yes',
  `allow_import` enum('yes','no') NOT NULL default 'yes',
  `allow_batch_import` enum('yes','no') NOT NULL default 'yes',
  `allow_export` enum('yes','no') NOT NULL default 'yes',
  `allow_batch_export` enum('yes','no') NOT NULL default 'yes',
  `allow_user_groups` enum('yes','no') NOT NULL default 'yes',
  `allow_user_queries` enum('yes','no') NOT NULL default 'yes',
  `allow_rss_feeds` enum('yes','no') NOT NULL default 'yes',
  `allow_sql_search` enum('yes','no') NOT NULL default 'yes',
  `allow_modify_options` enum('yes','no') NOT NULL default 'yes',
  `allow_edit_call_number` enum('no','yes') NOT NULL default 'no',
  PRIMARY KEY  (`user_permission_id`),
  KEY `user_id` (`user_id`)
) TYPE=MyISAM;

#
# data for table `user_permissions`
#

INSERT INTO `user_permissions` VALUES (1, 0, 'no', 'no', 'no', 'no', 'no', 'yes', 'yes', 'no', 'yes', 'no', 'no', 'no', 'no', 'no', 'no', 'yes', 'no', 'no', 'no'),
(2, 1, 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'no', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'no');

# --------------------------------------------------------

#
# table structure for table `user_styles`
#

DROP TABLE IF EXISTS `user_styles`;
CREATE TABLE `user_styles` (
  `user_style_id` mediumint(8) unsigned NOT NULL auto_increment,
  `style_id` mediumint(8) unsigned NOT NULL default '0',
  `user_id` mediumint(8) unsigned NOT NULL default '0',
  `show_style` enum('true','false') NOT NULL default 'true',
  PRIMARY KEY  (`user_style_id`),
  KEY `style_id` (`style_id`,`user_id`)
) TYPE=MyISAM;

#
# data for table `user_styles`
#

INSERT INTO `user_styles` VALUES (1, 1, 0, 'true'),
(2, 2, 0, 'true'),
(3, 3, 0, 'true'),
(4, 6, 0, 'true'),
(5, 8, 0, 'true'),
(6, 9, 0, 'true'),
(7, 1, 1, 'true'),
(8, 2, 1, 'true'),
(9, 3, 1, 'true'),
(10, 6, 1, 'true'),
(11, 8, 1, 'true'),
(12, 9, 1, 'true');

# --------------------------------------------------------

#
# table structure for table `user_types`
#

DROP TABLE IF EXISTS `user_types`;
CREATE TABLE `user_types` (
  `user_type_id` mediumint(8) unsigned NOT NULL auto_increment,
  `type_id` mediumint(8) unsigned NOT NULL default '0',
  `user_id` mediumint(8) unsigned NOT NULL default '0',
  `show_type` enum('true','false') NOT NULL default 'true',
  PRIMARY KEY  (`user_type_id`),
  KEY `type_id` (`type_id`,`user_id`)
) TYPE=MyISAM;

#
# data for table `user_types`
#

INSERT INTO `user_types` VALUES (1, 1, 1, 'true'),
(2, 2, 1, 'true'),
(3, 3, 1, 'true'),
(4, 4, 1, 'true'),
(5, 5, 1, 'true'),
(6, 6, 1, 'true'),
(7, 7, 1, 'true'),
(8, 8, 1, 'true'),
(9, 1, 0, 'true'),
(10, 2, 0, 'true'),
(11, 3, 0, 'true'),
(12, 4, 0, 'true'),
(13, 5, 0, 'true'),
(14, 6, 0, 'true'),
(15, 7, 0, 'true'),
(16, 8, 0, 'true');

# --------------------------------------------------------

#
# table structure for table `users`
#

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `first_name` varchar(50) default NULL,
  `last_name` varchar(50) default NULL,
  `title` varchar(25) default NULL,
  `institution` varchar(255) default NULL,
  `abbrev_institution` varchar(25) default NULL,
  `corporate_institution` varchar(255) default NULL,
  `address_line_1` varchar(50) default NULL,
  `address_line_2` varchar(50) default NULL,
  `address_line_3` varchar(50) default NULL,
  `zip_code` varchar(25) default NULL,
  `city` varchar(40) default NULL,
  `state` varchar(50) default NULL,
  `country` varchar(40) default NULL,
  `phone` varchar(50) default NULL,
  `email` varchar(50) default NULL,
  `url` varchar(255) default NULL,
  `keywords` text,
  `notes` text,
  `last_login` datetime default NULL,
  `logins` mediumint(8) unsigned default NULL,
  `language` varchar(50) default 'en',
  `user_id` mediumint(8) unsigned NOT NULL auto_increment,
  `user_groups` text,
  `marked` enum('no','yes') NOT NULL default 'no',
  `created_date` date default NULL,
  `created_time` time default NULL,
  `created_by` varchar(100) default NULL,
  `modified_date` date default NULL,
  `modified_time` time default NULL,
  `modified_by` varchar(100) default NULL,
  PRIMARY KEY  (`user_id`)
) TYPE=MyISAM;

#
# data for table `users`
#

INSERT INTO `users` VALUES ('Initial', 'refbase user', 'Mr', '', 'refbase', '', '', '', '', '', '', '', '', '', 'user@refbase.net', 'http://www.refbase.net/', NULL, NULL, '2004-11-01 12:00:00', 0, 'en', 1, NULL, 'no', '2004-11-01', '12:00:00', 'Initial refbase user (user@refbase.net)', '2004-11-01', '12:00:00', 'Initial refbase user (user@refbase.net)');
