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
		$api = MediawikiApi::newFromPage( 'http://deployment.wikimedia.beta.wmflabs.org/wiki/Main_Page' );
		$this->assertInstanceOf( 'Mediawiki\Api\MediawikiApi', $api );
	}

	/**
	 * @covers Mediawiki\Api\MediawikiApi::getRequest
	 * @covers Mediawiki\Api\MediawikiApi::getClientRequestOptions
	 * @covers Mediawiki\Api\MediawikiApi::decodeResponse
	 * @covers Mediawiki\Api\MediawikiApi::getClient
	 */
	public function testQueryGetResponse() {
		$api = MediawikiApi::newFromApiEndpoint( 'http://deployment.wikimedia.beta.wmflabs.org/w/api.php' );
		$response = $api->getRequest( new SimpleRequest( 'query' ) );
		$this->assertEquals( array( 'batchcomplete' => '' ), $response );
	}

	/**
	 * @covers Mediawiki\Api\MediawikiApi::getRequestAsync
	 * @covers Mediawiki\Api\MediawikiApi::getClientRequestOptions
	 * @covers Mediawiki\Api\MediawikiApi::decodeResponse
	 * @covers Mediawiki\Api\MediawikiApi::getClient
	 */
	public function testQueryGetResponseAsync() {
		$api = MediawikiApi::newFromApiEndpoint( 'http://deployment.wikimedia.beta.wmflabs.org/w/api.php' );
		$response = $api->getRequestAsync( new SimpleRequest( 'query' ) );
		$this->assertEquals( array( 'batchcomplete' => '' ), $response->wait() );
	}

	/**
	 * @covers Mediawiki\Api\MediawikiApi::postRequest
	 * @covers Mediawiki\Api\MediawikiApi::getClientRequestOptions
	 * @covers Mediawiki\Api\MediawikiApi::decodeResponse
	 * @covers Mediawiki\Api\MediawikiApi::getClient
	 */
	public function testQueryPostResponse() {
		$api = MediawikiApi::newFromApiEndpoint( 'http://deployment.wikimedia.beta.wmflabs.org/w/api.php' );
		$response = $api->postRequest( new SimpleRequest( 'query' ) );
		$this->assertEquals( array( 'batchcomplete' => '' ), $response );
	}

	/**
	 * @covers Mediawiki\Api\MediawikiApi::postRequestAsync
	 * @covers Mediawiki\Api\MediawikiApi::getClientRequestOptions
	 * @covers Mediawiki\Api\MediawikiApi::decodeResponse
	 * @covers Mediawiki\Api\MediawikiApi::getClient
	 */
	public function testQueryPostResponseAsync() {
		$api = MediawikiApi::newFromApiEndpoint( 'http://deployment.wikimedia.beta.wmflabs.org/w/api.php' );
		$response = $api->postRequestAsync( new SimpleRequest( 'query' ) );
		$this->assertEquals( array( 'batchcomplete' => '' ), $response->wait() );
	}

}
