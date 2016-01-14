<?php

namespace Mediawiki\Api\Test\Unit\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mediawiki\Api\Guzzle\MiddlewareFactory;

/**
 * @author Addshore
 *
 * @todo test interaction with logger
 *
 * @covers Mediawiki\Api\Guzzle\MiddlewareFactory
 */
class MiddlewareFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testRetriesConnectException() {
		$middlewareFactory = new MiddlewareFactory();

		$mock = new MockHandler(
			array(
				new ConnectException( "Error 1", new Request( 'GET', 'test' ) ),
				new Response( 200, [ 'X-Foo' => 'Bar' ] ),
			)
		);

		$handler = HandlerStack::create( $mock );
		$handler->push( $middlewareFactory->retry( false ) );
		$client = new Client( [ 'handler' => $handler ] );

		$this->assertEquals( 200, $client->request( 'GET', '/' )->getStatusCode() );
	}

	public function testRetries500Errors() {
		$middlewareFactory = new MiddlewareFactory();

		$mock = new MockHandler(
			array(
				new Response( 500 ),
				new Response( 200 ),
			)
		);

		$handler = HandlerStack::create( $mock );
		$handler->push( $middlewareFactory->retry( false ) );
		$client = new Client( [ 'handler' => $handler ] );

		$this->assertEquals( 200, $client->request( 'GET', '/' )->getStatusCode() );
	}

	public function testRetriesSomeMediawikiApiErrorHeaders() {
		$middlewareFactory = new MiddlewareFactory();

		$mock = new MockHandler(
			array(
				new Response( 200, array( 'mediawiki-api-error' => 'ratelimited' ) ),
				new Response( 200, array( 'mediawiki-api-error' => 'readonly' ) ),
				new Response( 200, array( 'mediawiki-api-error' => 'internal_api_error_DBQueryError' ) ),
				new Response( 200, array( 'mediawiki-api-error' => 'DoNotRetryThisHeader' ) ),
			)
		);

		$handler = HandlerStack::create( $mock );
		$handler->push( $middlewareFactory->retry( false ) );
		$client = new Client( [ 'handler' => $handler ] );

		$response = $client->request( 'GET', '/' );
		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals(
			array( 'DoNotRetryThisHeader' ),
			$response->getHeader( 'mediawiki-api-error' )
		);
	}

	public function testRetryAntiAbuseMeasure() {
		$middlewareFactory = new MiddlewareFactory();

		$antiAbusejson = json_encode(
			array(
				'error' => array(
					'info' => 'anti-abuse measure'
				)
			)
		);

		$mock = new MockHandler(
			array(
				new Response( 200, array( 'mediawiki-api-error' => 'failed-save' ), $antiAbusejson ),
				new Response( 200, array( 'mediawiki-api-error' => 'DoNotRetryThisHeader' ) ),
			)
		);

		$handler = HandlerStack::create( $mock );
		$handler->push( $middlewareFactory->retry( false ) );
		$client = new Client( [ 'handler' => $handler ] );

		$response = $client->request( 'GET', '/' );
		$this->assertSame( 200, $response->getStatusCode() );
		$this->assertEquals(
			array( 'DoNotRetryThisHeader' ),
			$response->getHeader( 'mediawiki-api-error' )
		);
	}

	public function testRetryLimit() {
		$middlewareFactory = new MiddlewareFactory();

		$mock = new MockHandler(
			array(
				new ConnectException( "Error 1", new Request( 'GET', 'test' ) ),
				new ConnectException( "Error 2", new Request( 'GET', 'test' ) ),
				new ConnectException( "Error 3", new Request( 'GET', 'test' ) ),
				new ConnectException( "Error 4", new Request( 'GET', 'test' ) ),
				new ConnectException( "Error 5", new Request( 'GET', 'test' ) ),
				new ConnectException( "Error 6", new Request( 'GET', 'test' ) ),
			)
		);

		$handler = HandlerStack::create( $mock );
		$handler->push( $middlewareFactory->retry( false ) );
		$client = new Client( [ 'handler' => $handler ] );

		$this->setExpectedException(
			'GuzzleHttp\Exception\ConnectException',
			'Error 6'
		);

		$client->request( 'GET', '/' )->getStatusCode();
	}

	public function testRetryDelay() {
		$middlewareFactory = new MiddlewareFactory();

		$mock = new MockHandler(
			array(
				new ConnectException( "+1 second delay", new Request( 'GET', 'test' ) ),
				new ConnectException( "+2 second delay", new Request( 'GET', 'test' ) ),
				new Response( 200 ),
			)
		);

		$handler = HandlerStack::create( $mock );
		$handler->push( $middlewareFactory->retry( true ) );
		$client = new Client( [ 'handler' => $handler ] );

		$startTime = time();
		$client->request( 'GET', '/' )->getStatusCode();
		$endTime = time();

		$this->assertGreaterThan( $startTime + 2, $endTime );
	}

}
