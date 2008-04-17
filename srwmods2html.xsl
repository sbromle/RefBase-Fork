<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xlink="http://www.w3.org/1999/xlink"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:srw="http://www.loc.gov/zing/srw/"
	xmlns:diag="http://www.loc.gov/zing/srw/diagnostic/"
	xmlns:srw_mods="info:srw/schema/1/mods-v3.2"
	xmlns:mods="http://www.loc.gov/mods/v3"
	xmlns:zr="http://explain.z3950.org/dtd/2.0/"
	exclude-result-prefixes="srw_mods mods srw" >
	<xsl:output method="html" indent="yes"/>
	<!--
		converts MODS v3.2 records (which are wrapped in SRW XML) to HTML
		modified after <http://www.loc.gov/standards/mods/mods.xsl> by Matthias Steffens <mailto:refbase@extracts.de>
	-->

	<!--
		TODO: - include previous/next navigation links (this will require the SRU query to be present in the SRU searchRetrieve response as extraResponseData!)
		      - see inline comments labeled wit "TODO"
	-->
	<xsl:variable name="dictionary" select="document('locales/en/modsDictionary.xml')/dictionary"/>
	<xsl:variable name="explainResponse" select="document('sru.php')/srw:explainResponse/srw:record/srw:recordData/zr:explain"/>
	<xsl:variable name="officialDatabaseName" select="$explainResponse/zr:databaseInfo/zr:title"/>
	<xsl:variable name="hostInstitutionName" select="$explainResponse/zr:databaseInfo/zr:author"/>
	<xsl:variable name="databaseBaseURL" select="$explainResponse/zr:databaseInfo/zr:links/zr:link[@type='www']"/>
	<xsl:variable name="logoURL" select="$explainResponse/zr:databaseInfo/zr:links/zr:link[@type='icon']"/>
	<xsl:variable name="defaultNumberOfRecords" select="$explainResponse/zr:configInfo/zr:default[@type='numberOfRecords']"/>

	<xsl:variable name="totalNumberOfRecords" select="srw:searchRetrieveResponse/srw:numberOfRecords"/>

	<!--
		TODO: uncomment when including previous/next navigation links

	<xsl:variable name="startRecord" select="srw:searchRetrieveResponse/srw:records/srw:record[1]/srw:recordPosition"/>
	<xsl:variable name="nextStartRecord" select="srw:searchRetrieveResponse/srw:nextRecordPosition"/>

	<xsl:variable name="previousStartRecord">
		<xsl:choose>
			<xsl:when test="$startRecord = $totalNumberOfRecords">
				<xsl:value-of select="$totalNumberOfRecords - $defaultNumberOfRecords"/>
			</xsl:when>

			<xsl:otherwise>
				<xsl:if test="$startRecord &gt; 0">
					<xsl:value-of select="$startRecord - ( $nextStartRecord - $startRecord )"/>
				</xsl:if>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	-->

	<xsl:template match="srw:searchRetrieveResponse">

		<html>
			<head>
				<title><xsl:value-of select="$officialDatabaseName"/> -- Query Results</title>
				<meta http-equiv="Content-Style-Type" content="text/css"/>
				<link rel="stylesheet" href="css/style.css" type="text/css" title="CSS Definition"/>
				<style type="text/css">
					DIV {vertical-align:top;font-size:1em}
					.label {vertical-align:top}
					.data {vertical-align:top}
					.level2 {margin-left:1em}
					.level3 {margin-left:2em}
					.level4 {margin-left:3em}
					.level5 {margin-left:4em}
					.level6 {margin-left:5em}
				</style>
			</head>
			<body bgcolor="#FFFFFF">
				<!-- page header: -->
				<table class="pageheader" align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This holds the title logo and info">
					<tr>
						<td valign="bottom" rowspan="2" align="left" width="120">
						<a>
							<xsl:attribute name="href">
								<!-- note that the logo should point to the URL given in '$hostInstitutionURL' but this information is currently not available in the SRU explain response -->
								<xsl:value-of select="$databaseBaseURL"/>
							</xsl:attribute>
							<img src="{$logoURL}" alt="Home" border="0" />
						</a>
						</td>
						<td>
							<h2><xsl:value-of select="$officialDatabaseName"/></h2>
							<span class="smallup">
								<a href="index.php" title="go to main page">Home</a><xsl:text> | </xsl:text>
								<a href="show.php?records=all" title="show all records in the database">Show All</a><xsl:text> | </xsl:text>
								<a href="simple_search.php" title="search the main fields of the database">Simple Search</a><xsl:text> | </xsl:text>
								<a href="advanced_search.php" title="search all fields of the database">Advanced Search</a>
							</span>
						</td>
						<td class="small" align="right" valign="middle"><br /></td>
					</tr>
					<tr>
						<xsl:choose>
							<!-- diagnostics: -->
							<xsl:when test="srw:diagnostics">
								<td><xsl:text>Your query caused the following error:</xsl:text></td>
							</xsl:when>
							<!-- search results: -->
							<xsl:otherwise>
								<td><xsl:value-of select="srw:records/srw:record[1]/srw:recordPosition"/>-<xsl:value-of select="srw:records/srw:record[position()=last()]/srw:recordPosition"/> of <xsl:value-of select="$totalNumberOfRecords"/> records found:</td>
							</xsl:otherwise>
						</xsl:choose>
						<td class="small" align="right" valign="middle"><a href="user_login.php" title="login to the database">Login</a></td>
					</tr>
				</table>
				<hr class="pageheader" align="center" width="95%" />
				<xsl:choose>
					<!-- diagnostics: -->
					<xsl:when test="srw:diagnostics">
						<xsl:apply-templates select="srw:diagnostics"/>
					</xsl:when>
					<!-- search results: -->
					<xsl:otherwise>
						<xsl:apply-templates select="srw:records"/>
					</xsl:otherwise>
				</xsl:choose>
				<!-- page footer: -->
				<hr class="pagefooter" align="center" width="95%" />
				<table class="pagefooter" align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds the footer">
					<tr>
						<td class="small" width="105"><a href="index.php" title="go to main page">Home</a></td>
						<td class="small" align="center">
							<a href="sru.php" title="search the SRU web service">SRU Search</a><xsl:text> | </xsl:text>
							<a href="library_search.php">
								<xsl:attribute name="title">
									<xsl:text>search the library of the </xsl:text><xsl:value-of select="$hostInstitutionName"/>
								</xsl:attribute>
								<xsl:text>Library Search</xsl:text>
							</a><xsl:text> | </xsl:text>
							<a href="show.php" title="display details for a particular record by entering its database serial number">Show Record</a><xsl:text> | </xsl:text>
							<a href="extract.php" title="extract citations from a text and build an appropriate reference list">Extract Citations</a>
						</td>
						<td class="small" align="right" width="105"><a href="http://wiki.refbase.net/" title="display help">Help</a></td>
					</tr>
				</table>
			</body>
		</html>
	</xsl:template>


	<xsl:template match="srw:diagnostics">
		<xsl:apply-templates select="diag:diagnostic"/>
	</xsl:template>


	<xsl:template match="diag:diagnostic">
		<table class="error" align="center" border="0" cellpadding="0" cellspacing="10" width="95%">
		<tr>
			<td valign="top">
				<xsl:text>Error </xsl:text>
				<xsl:value-of select="substring(uri,23)"/>
				<xsl:text> : </xsl:text>
				<b><xsl:value-of select="message"/></b>
				<xsl:if test="details">
					<xsl:text>: </xsl:text>
					<xsl:value-of select="details"/>
				</xsl:if>
			</td>
		</tr>
		<tr>
			<td>
				<xsl:text>Choose how to proceed: </xsl:text>
				<a href="javascript:history.back()">Go back</a>
				<xsl:text>  -OR-  </xsl:text>
				<a href="sru.php">New SRU search</a>
				<xsl:text>  -OR-  </xsl:text>
				<a href="index.php"><xsl:text>Goto </xsl:text><xsl:value-of select="$officialDatabaseName"/><xsl:text> Home</xsl:text></a>
			</td>
		</tr>
		</table>
	</xsl:template>


	<xsl:template match="srw:records">
		<!--
			TODO: uncomment when including previous/next navigation links

		<table class="pagenav" align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds browse links that link to the results pages of your query">
		<tr>
			<td align="center" valign="bottom">
				<xsl:if test="string-length($previousStartRecord)&gt;0 and $previousStartRecord &gt; 0">
					<a class="x-escape" href="{$databaseBaseURL}sru.php?version=1.1&amp;query=&amp;startRecord={$previousStartRecord}" rel="next">Previous records</a>
				</xsl:if>
				<xsl:if test="string-length($nextStartRecord)&gt;0">
					<xsl:if test="string-length($previousStartRecord)&gt;0 and $previousStartRecord &gt; 0"> | </xsl:if>
					<a class="x-escape" href="{$databaseBaseURL}sru.php?version=1.1&amp;query=&amp;startRecord={$nextStartRecord}" rel="next">Next records</a>
				</xsl:if>
			</td>
		</tr>
		</table>
		-->
		<xsl:apply-templates/>
	</xsl:template>


	<xsl:template match="srw:record">
		<!-- '<xsl:value-of select="position()"/>' shows: first record has position 2, second record has position 4, and so forth -->
		<xsl:if test="position() &gt; 2">
			<hr class="results" align="center" width="90%" />
		</xsl:if>
		<table class="results" align="center" border="0" cellpadding="0" cellspacing="7" width="90%" summary="This table holds a database record">
			<tr>
				<td colspan="2">
					<h4>Record number: <xsl:value-of select="srw:recordPosition"/></h4>
				</td>
			</tr>
			<xsl:apply-templates select="srw:recordData/mods:mods"/>
		</table>
	</xsl:template>


	<xsl:template match="srw:recordData/mods:mods">

		<xsl:choose>

			<xsl:when test="child::*">
				<tr>
					<td colspan="2">
						<b>
							<xsl:call-template name="longName">
								<xsl:with-param name="name">
									<xsl:value-of select="local-name()"/>
								</xsl:with-param>
							</xsl:call-template>

							<xsl:call-template name="attr"/>
						</b>
					</td>
				</tr>
				<xsl:apply-templates mode="level2"/>
			</xsl:when>

			<xsl:otherwise>
				<tr>
					<td width="350pt">
						<b>
							<xsl:call-template name="longName">
								<xsl:with-param name="name">
									<xsl:value-of select="local-name()"/>
								</xsl:with-param>
							</xsl:call-template>

							<xsl:call-template name="attr"/>
						</b>
					</td>
					<td>
						<xsl:call-template name="formatValue">
							<xsl:with-param name="name">
								<xsl:value-of select="local-name()"/>
							</xsl:with-param>
						</xsl:call-template>
					</td>
				</tr>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<xsl:template name="formatValue">
		<xsl:param name="name"/>

		<xsl:choose>

			<!-- DOI -->
			<xsl:when test="$name='identifier' and @type='doi'">
				<a href="http://dx.doi.org/{text()}">
					<xsl:value-of select="text()"/>
				</a>
			</xsl:when>

			<!-- ISBN -->
			<xsl:when test="$name='identifier' and @type='isbn'">
				<a href="http://isbn.nu/{text()}">
					<xsl:value-of select="text()"/>
				</a>
			</xsl:when>

			<!-- ISSN -->
			<xsl:when test="$name='identifier' and @type='issn'">
				<a href="http://journalseek.net/cgi-bin/journalseek/journalsearch.cgi?query={text()}&amp;field=allFields">
					<xsl:value-of select="text()"/>
				</a>
			</xsl:when>

			<!-- permanent refbase URL (e.g. <http://beta.refbase.net/show.php?record=12>) -->
			<xsl:when test="contains(text(),concat('exported from refbase (',$databaseBaseURL,'show.php?record='))">
				<xsl:variable name="refbaseURL" select="substring(text(),24,string-length(substring-before(substring(text(),24),'), last updated')))"/><!-- extracts the refbase URL from the notes string -->
				<xsl:variable name="lastUpdatedInfo" select="substring-after(text(),$refbaseURL)"/><!-- extracts the string after the refbase URL from the notes string -->
				<xsl:text>exported from refbase (</xsl:text>
				<a href="{$refbaseURL}">
					<xsl:text>record </xsl:text><xsl:value-of select="translate($refbaseURL,translate($refbaseURL,'0123456789',''),'')"/><!-- removes all characters except digits from string -->
				</a>
				<xsl:value-of select="$lastUpdatedInfo"/>
			</xsl:when>

			<!-- URL -->
			<xsl:when test="$name='url'">
				<a href="{text()}">
					<xsl:value-of select="text()"/>
				</a>
			</xsl:when>

			<xsl:when test="$name='identifier' and @type='uri'">
				<a href="{text()}">
					<xsl:value-of select="text()"/>
				</a>
			</xsl:when>

			<xsl:otherwise>
				<xsl:value-of select="text()"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<xsl:template match="*" mode="level2"> 

		<xsl:choose>

			<xsl:when test="child::*">
				<tr>
					<td colspan="2">
						<div class="level2">
							<xsl:call-template name="longName">
								<xsl:with-param name="name">
									<xsl:value-of select="local-name()"/>
								</xsl:with-param>
							</xsl:call-template>

							<xsl:call-template name="attr"/>
						</div>
					</td>
				</tr>
				<xsl:apply-templates mode="level3"/>
			</xsl:when>

			<xsl:otherwise>
				<tr>
					<td class="label">
						<div class="level2">
							<xsl:call-template name="longName">
								<xsl:with-param name="name">
									<xsl:value-of select="local-name()"/>
								</xsl:with-param>
							</xsl:call-template>

							<xsl:call-template name="attr"/>
						</div>
					</td>
					<td class="data">
						<xsl:call-template name="formatValue">
							<xsl:with-param name="name">
								<xsl:value-of select="local-name()"/>
							</xsl:with-param>
						</xsl:call-template>
					</td>
				</tr>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<xsl:template match="*" mode="level3">

		<xsl:choose>

			<xsl:when test="child::*">
				<tr>
					<td colspan="2">
						<div class="level3">
							<xsl:call-template name="longName">
								<xsl:with-param name="name">
									<xsl:value-of select="local-name()"/>
								</xsl:with-param>
							</xsl:call-template>

							<xsl:call-template name="attr"/>
						</div>
					</td>
				</tr>
				<xsl:apply-templates mode="level4"/>
			</xsl:when>

			<xsl:otherwise>
				<tr>
					<td class="label">
						<div class="level3">
							<xsl:call-template name="longName">
								<xsl:with-param name="name">
									<xsl:value-of select="local-name()"/>
								</xsl:with-param>
							</xsl:call-template>

							<xsl:call-template name="attr"/>
						</div>
					</td>
					<td class="data">
						<xsl:call-template name="formatValue">
							<xsl:with-param name="name">
								<xsl:value-of select="local-name()"/>
							</xsl:with-param>
						</xsl:call-template>
					</td>
				</tr>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<xsl:template match="*" mode="level4">

		<xsl:choose>

			<xsl:when test="child::*">
				<tr>
					<td colspan="2">
						<div class="level4">
							<xsl:call-template name="longName">
								<xsl:with-param name="name">
									<xsl:value-of select="local-name()"/>
								</xsl:with-param>
							</xsl:call-template>

							<xsl:call-template name="attr"/>
						</div>
					</td>
				</tr>
				<xsl:apply-templates mode="level5"/>
			</xsl:when>

			<xsl:otherwise>
				<tr>
					<td class="label">
						<div class="level4">
							<xsl:call-template name="longName">
								<xsl:with-param name="name">
									<xsl:value-of select="local-name()"/>
								</xsl:with-param>
							</xsl:call-template>

							<xsl:call-template name="attr"/>
						</div>
					</td>
					<td class="data">
						<xsl:call-template name="formatValue">
							<xsl:with-param name="name">
								<xsl:value-of select="local-name()"/>
							</xsl:with-param>
						</xsl:call-template>
					</td>
				</tr>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<xsl:template match="*" mode="level5">

		<xsl:choose>

			<xsl:when test="child::*">
				<tr>
					<td colspan="2">
						<div class="level5">
							<xsl:call-template name="longName">
								<xsl:with-param name="name">
									<xsl:value-of select="local-name()"/>
								</xsl:with-param>
							</xsl:call-template>

							<xsl:call-template name="attr"/>
						</div>
					</td>
				</tr>
				<xsl:apply-templates mode="level6"/>
			</xsl:when>

			<xsl:otherwise>
				<tr>
					<td class="label">
						<div class="level5">
							<xsl:call-template name="longName">
								<xsl:with-param name="name">
									<xsl:value-of select="local-name()"/>
								</xsl:with-param>
							</xsl:call-template>

							<xsl:call-template name="attr"/>
						</div>
					</td>
					<td class="data">
						<xsl:call-template name="formatValue">
							<xsl:with-param name="name">
								<xsl:value-of select="local-name()"/>
							</xsl:with-param>
						</xsl:call-template>
					</td>
				</tr>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>


	<xsl:template match="*" mode="level6">
		<tr>
			<td class="label">
				<div class="level6">
					<xsl:call-template name="longName">
						<xsl:with-param name="name">
							<xsl:value-of select="local-name()"/>
						</xsl:with-param>
					</xsl:call-template>

					<xsl:call-template name="attr"/>
				</div>
			</td>
			<td class="data">
				<xsl:value-of select="text()"/>
			</td>
		</tr>
	</xsl:template>



	<xsl:template name="longName">
		<xsl:param name="name"/>

		<xsl:choose>

			<xsl:when test="$dictionary/entry[@key=$name]">
				<xsl:value-of select="$dictionary/entry[@key=$name]"/>
			</xsl:when>

			<xsl:otherwise>
				<xsl:value-of select="$name"/>
			</xsl:otherwise>

		</xsl:choose>

	</xsl:template>


	<xsl:template name="attr">

		<xsl:for-each select="@type|@point">:<xsl:call-template name="longName"><xsl:with-param name="name"><xsl:value-of select="."/></xsl:with-param></xsl:call-template></xsl:for-each>
	
		<xsl:if test="@authority or @edition">

			<xsl:for-each select="@authority">(<xsl:call-template name="longName"><xsl:with-param name="name"><xsl:value-of select="."/></xsl:with-param></xsl:call-template></xsl:for-each>
			<xsl:if test="@edition">Edition <xsl:value-of select="@edition"/></xsl:if>)</xsl:if>

		<xsl:variable name="attrStr">

			<xsl:for-each select="@*[local-name()!='edition' and local-name()!='type' and local-name()!='authority' and local-name()!='point']">

				<xsl:value-of select="local-name()"/>="<xsl:value-of select="."/>",</xsl:for-each>
		</xsl:variable>

		<xsl:variable name="nattrStr" select="normalize-space($attrStr)"/>

		<xsl:if test="string-length($nattrStr)">(<xsl:value-of select="substring($nattrStr,1,string-length($nattrStr)-1)"/>)</xsl:if>
	</xsl:template>


</xsl:stylesheet>