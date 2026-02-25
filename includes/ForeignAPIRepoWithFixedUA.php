<?php

namespace Wikivy\WikivyMagic;

use MediaWiki\FileRepo\ForeignAPIRepo;
use MediaWiki\WikiMap\WikiMap;
use const MW_VERSION;

class ForeignAPIRepoWithFixedUA extends ForeignAPIRepo
{
	public function getUserAgent(): string {
		$mediaWikiVersion = MW_VERSION;
		$wikiId = WikiMap::getCurrentWikiId();
		return "QuickInstantCommons/$mediaWikiVersion MediaWiki/$mediaWikiVersion $wikiId (https://wikivy.com; tech@wikivy.com)";
	}

}
