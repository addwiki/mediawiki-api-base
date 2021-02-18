<?php

namespace Addwiki\Mediawiki\Api\Tests\Integration\Client;

use Addwiki\Mediawiki\Api\Client\MediawikiApi;
use Addwiki\Mediawiki\Api\Client\RsdException;
use Addwiki\Mediawiki\Api\Client\SimpleRequest;
use Addwiki\Mediawiki\Api\Tests\Integration\BaseTestEnvironment;
use PHPUnit\Framework\TestCase;

/**
 * @author Addshore
 */
class MediawikiApiTest extends TestCase {

	/**
	 * @covers Mediawiki\Api\MediawikiApi::newFromPage
	 */
	public function testNewFromPage() {
		$api = MediawikiApi::newFromPage( BaseTestEnvironment::newInstance()->getPageUrl() );
		$this->assertInstanceOf( MediawikiApi::class, $api );
	}

	/**
	 * @covers Mediawiki\Api\MediawikiApi::newFromPage
	 */
	public function testNewFromPageInvalidHtml() {
		$this->expectException( RsdException::class );
		$this->expectExceptionMessageMatches( "/Unable to find RSD URL in page.*/" );
		// This could be any URL that doesn't contain the RSD link, load.php works just fine!
		$nonWikiPage = str_replace( 'api.php', 'load.php', BaseTestEnvironment::newInstance()->getApiUrl() );
		MediawikiApi::newFromPage( $nonWikiPage );
	}

	/**
	 * Duplicate element IDs break DOMDocument::loadHTML
	 * @see https://phabricator.wikimedia.org/T163527#3219833
	 * @covers Mediawiki\Api\MediawikiApi::newFromPage
	 */
	public function testNewFromPageWithDuplicateId() {
		$testPageName = __METHOD__;
		$testEnv = BaseTestEnvironment::newInstance();
		$wikiPageUrl = str_replace( 'api.php', sprintf( 'index.php?title=%s', $testPageName ), $testEnv->getApiUrl() );

		// Test with no duplicate IDs.
		$testEnv->savePage( $testPageName, '<p id="unique-id"></p>' );
		$api1 = MediawikiApi::newFromPage( $wikiPageUrl );
		$this->assertInstanceOf( MediawikiApi::class, $api1 );

		// Test with duplicate ID.
		$wikiText = '<p id="duplicated-id"></p><div id="duplicated-id"></div>';
		$testEnv->savePage( $testPageName, $wikiText );
		$api2 = MediawikiApi::newFromPage( $wikiPageUrl );
		$this->assertInstanceOf( MediawikiApi::class, $api2 );
	}

	/**
	 * @covers Mediawiki\Api\MediawikiApi::getRequest
	 * @covers Mediawiki\Api\MediawikiApi::getClientRequestOptions
	 * @covers Mediawiki\Api\MediawikiApi::decodeResponse
	 * @covers Mediawiki\Api\MediawikiApi::getClient
	 */
	public function testQueryGetResponse() {
		$api = BaseTestEnvironment::newInstance()->getApi();
		$response = $api->getRequest( new SimpleRequest( 'query' ) );
		$this->assertIsArray( $response );
	}

	/**
	 * @covers Mediawiki\Api\MediawikiApi::getRequestAsync
	 * @covers Mediawiki\Api\MediawikiApi::getClientRequestOptions
	 * @covers Mediawiki\Api\MediawikiApi::decodeResponse
	 * @covers Mediawiki\Api\MediawikiApi::getClient
	 */
	public function testQueryGetResponseAsync() {
		$api = BaseTestEnvironment::newInstance()->getApi();
		$response = $api->getRequestAsync( new SimpleRequest( 'query' ) );
		$this->assertIsArray( $response->wait() );
	}

	/**
	 * @covers Mediawiki\Api\MediawikiApi::postRequest
	 * @covers Mediawiki\Api\MediawikiApi::getClientRequestOptions
	 * @covers Mediawiki\Api\MediawikiApi::decodeResponse
	 * @covers Mediawiki\Api\MediawikiApi::getClient
	 */
	public function testQueryPostResponse() {
		$api = BaseTestEnvironment::newInstance()->getApi();
		$response = $api->postRequest( new SimpleRequest( 'query' ) );
		$this->assertIsArray( $response );
	}

	/**
	 * @covers Mediawiki\Api\MediawikiApi::postRequestAsync
	 * @covers Mediawiki\Api\MediawikiApi::getClientRequestOptions
	 * @covers Mediawiki\Api\MediawikiApi::decodeResponse
	 * @covers Mediawiki\Api\MediawikiApi::getClient
	 */
	public function testQueryPostResponseAsync() {
		$api = BaseTestEnvironment::newInstance()->getApi();
		$response = $api->postRequestAsync( new SimpleRequest( 'query' ) );
		$this->assertIsArray( $response->wait() );
	}

}
