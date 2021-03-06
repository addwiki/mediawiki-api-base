<?php

namespace Addwiki\Mediawiki\Api\Tests\Integration\Client\Discovery;

use Addwiki\Mediawiki\Api\Client\Discovery\ReallySimpleDiscovery;
use Addwiki\Mediawiki\Api\Tests\Integration\BaseTestEnvironment;

class ReallySimpleDiscoveryTest {

	public function testNewFromPage(): void {
		$testEnv = BaseTestEnvironment::newInstance();
		$base = ReallySimpleDiscovery::baseFromPage( $testEnv->getPageUrl() );
		$this->assertSame( $testEnv->getApiUrl(), $base . 'api.php' );
	}

	public function testNewFromPageInvalidHtml(): void {
		$this->expectException( RsdException::class );
		$this->expectExceptionMessageMatches( "/Unable to find RSD URL in page.*/" );
		// This could be any URL that doesn't contain the RSD link, load.php works just fine!
		$nonWikiPage = str_replace( 'api.php', 'load.php', BaseTestEnvironment::newInstance()->getApiUrl() );
		$base = ReallySimpleDiscovery::baseFromPage( $nonWikiPage );
	}

	/**
	 * Duplicate element IDs break DOMDocument::loadHTML
	 * @see https://phabricator.wikimedia.org/T163527#3219833
	 */
	public function testNewFromPageWithDuplicateId(): void {
		$testPageName = __METHOD__;
		$testEnv = BaseTestEnvironment::newInstance();
		$wikiPageUrl = str_replace( 'api.php', sprintf( 'index.php?title=%s', $testPageName ), $testEnv->getApiUrl() );
		$api = $testEnv->getActionApi();

		// Test with no duplicate IDs.
		$this->savePage( $api, $testPageName, '<p id="unique-id"></p>' );
		$base1 = ReallySimpleDiscovery::baseFromPage( $wikiPageUrl );
		$this->assertSame( $testEnv->getApiUrl(), $base1 . 'api.php' );

		// Test with duplicate ID.
		$wikiText = '<p id="duplicated-id"></p><div id="duplicated-id"></div>';
		$this->savePage( $api, $testPageName, $wikiText );
		$base2 = ReallySimpleDiscovery::baseFromPage( $wikiPageUrl );
		$this->assertSame( $testEnv->getApiUrl(), $base2 . 'api.php' );
	}

}
