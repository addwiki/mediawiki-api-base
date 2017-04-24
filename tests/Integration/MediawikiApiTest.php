<?php

namespace Mediawiki\Api\Test\Integration;

use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\SimpleRequest;

/**
 * @author Addshore
 */
class MediawikiApiTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @covers Mediawiki\Api\MediawikiApi::newFromPage
	 */
	public function testNewFromPage() {
		$api = MediawikiApi::newFromPage( TestEnvironment::newInstance()->getPageUrl() );
		$this->assertInstanceOf( 'Mediawiki\Api\MediawikiApi', $api );
	}

	/**
	 * @covers Mediawiki\Api\MediawikiApi::newFromPage
	 * @expectedException Mediawiki\Api\RsdException
	 * @expectedExceptionMessageRegExp |Unable to find RSD URL in page.*|
	 */
	public function testNewFromPageInvalidHtml() {
		// This could be any URL that doesn't contain the RSD link, but the README URL
		// is a test-accessible one that doesn't return 404.
		$nonWikiPage = str_replace( 'api.php', 'README', TestEnvironment::newInstance()->getApiUrl() );
		MediawikiApi::newFromPage( $nonWikiPage );
	}

	/**
	 * @covers Mediawiki\Api\MediawikiApi::getRequest
	 * @covers Mediawiki\Api\MediawikiApi::getClientRequestOptions
	 * @covers Mediawiki\Api\MediawikiApi::decodeResponse
	 * @covers Mediawiki\Api\MediawikiApi::getClient
	 */
	public function testQueryGetResponse() {
		$api = TestEnvironment::newInstance()->getApi();
		$response = $api->getRequest( new SimpleRequest( 'query' ) );
		$this->assertInternalType( 'array', $response );
	}

	/**
	 * @covers Mediawiki\Api\MediawikiApi::getRequestAsync
	 * @covers Mediawiki\Api\MediawikiApi::getClientRequestOptions
	 * @covers Mediawiki\Api\MediawikiApi::decodeResponse
	 * @covers Mediawiki\Api\MediawikiApi::getClient
	 */
	public function testQueryGetResponseAsync() {
		$api = TestEnvironment::newInstance()->getApi();
		$response = $api->getRequestAsync( new SimpleRequest( 'query' ) );
		$this->assertInternalType( 'array', $response->wait() );
	}

	/**
	 * @covers Mediawiki\Api\MediawikiApi::postRequest
	 * @covers Mediawiki\Api\MediawikiApi::getClientRequestOptions
	 * @covers Mediawiki\Api\MediawikiApi::decodeResponse
	 * @covers Mediawiki\Api\MediawikiApi::getClient
	 */
	public function testQueryPostResponse() {
		$api = TestEnvironment::newInstance()->getApi();
		$response = $api->postRequest( new SimpleRequest( 'query' ) );
		$this->assertInternalType( 'array', $response );
	}

	/**
	 * @covers Mediawiki\Api\MediawikiApi::postRequestAsync
	 * @covers Mediawiki\Api\MediawikiApi::getClientRequestOptions
	 * @covers Mediawiki\Api\MediawikiApi::decodeResponse
	 * @covers Mediawiki\Api\MediawikiApi::getClient
	 */
	public function testQueryPostResponseAsync() {
		$api = TestEnvironment::newInstance()->getApi();
		$response = $api->postRequestAsync( new SimpleRequest( 'query' ) );
		$this->assertInternalType( 'array', $response->wait() );
	}

}
