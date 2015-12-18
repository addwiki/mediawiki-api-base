<?php

namespace Mediawiki\Api\Test;

use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\SimpleRequest;

/**
 * @author Addshore
 */
class MediawikiApiTest extends \PHPUnit_Framework_TestCase {

	public function testNewFromPage() {
		$api = MediawikiApi::newFromPage( 'http://localhost/w' );
		$this->assertInstanceOf( 'Mediawiki\Api\MediawikiApi', $api );
	}

	public function testQueryGetResponse() {
		$api = new MediawikiApi( 'http://localhost/w/api.php' );
		$response = $api->getRequest( new SimpleRequest( 'query' ) );
		$this->assertEquals( array( 'batchcomplete' => '' ), $response );
	}

	public function testQueryGetResponseAsync() {
		$api = new MediawikiApi( 'http://localhost/w/api.php' );
		$response = $api->getRequestAsync( new SimpleRequest( 'query' ) );
		$this->assertEquals( array( 'batchcomplete' => '' ), $response->wait() );
	}

	public function testQueryPostResponse() {
		$api = new MediawikiApi( 'http://localhost/w/api.php' );
		$response = $api->postRequest( new SimpleRequest( 'query' ) );
		$this->assertEquals( array( 'batchcomplete' => '' ), $response );
	}

	public function testQueryPostResponseAsync() {
		$api = new MediawikiApi( 'http://localhost/w/api.php' );
		$response = $api->postRequestAsync( new SimpleRequest( 'query' ) );
		$this->assertEquals( array( 'batchcomplete' => '' ), $response->wait() );
	}

}
