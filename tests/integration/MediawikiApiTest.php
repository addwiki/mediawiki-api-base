<?php

namespace Mediawiki\Api\Test;

use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\SimpleRequest;

/**
 * @author Addshore
 */
class MediawikiApiTest extends \PHPUnit_Framework_TestCase {

	public function testNewFromPage() {
		$api = MediawikiApi::newFromPage( 'http://deployment.wikimedia.beta.wmflabs.org/wiki/Main_Page' );
		$this->assertInstanceOf( 'Mediawiki\Api\MediawikiApi', $api );
	}

	public function testQueryGetResponse() {
		$api = MediawikiApi::newFromApiEndpoint( 'http://deployment.wikimedia.beta.wmflabs.org/w/api.php' );
		$response = $api->getRequest( new SimpleRequest( 'query' ) );
		$this->assertEquals( array( 'batchcomplete' => '' ), $response );
	}

	public function testQueryGetResponseAsync() {
		$api = MediawikiApi::newFromApiEndpoint( 'http://deployment.wikimedia.beta.wmflabs.org/w/api.php' );
		$response = $api->getRequestAsync( new SimpleRequest( 'query' ) );
		$this->assertEquals( array( 'batchcomplete' => '' ), $response->wait() );
	}

	public function testQueryPostResponse() {
		$api = MediawikiApi::newFromApiEndpoint( 'http://deployment.wikimedia.beta.wmflabs.org/w/api.php' );
		$response = $api->postRequest( new SimpleRequest( 'query' ) );
		$this->assertEquals( array( 'batchcomplete' => '' ), $response );
	}

	public function testQueryPostResponseAsync() {
		$api = MediawikiApi::newFromApiEndpoint( 'http://deployment.wikimedia.beta.wmflabs.org/w/api.php' );
		$response = $api->postRequestAsync( new SimpleRequest( 'query' ) );
		$this->assertEquals( array( 'batchcomplete' => '' ), $response->wait() );
	}

}
