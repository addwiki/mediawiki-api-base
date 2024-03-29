<?php

namespace Addwiki\Mediawiki\Api\Tests\Unit\Guzzle;

use Addwiki\Mediawiki\Api\Guzzle\MiddlewareFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * @todo test interaction with logger
 *
 * @covers Mediawiki\Api\Guzzle\MiddlewareFactory
 */
class MiddlewareFactoryTest extends TestCase {

	public function testRetriesConnectException(): void {
		$queue = [
			new ConnectException( 'Error 1', new Request( 'GET', 'test' ) ),
			new Response( 200, [ 'X-Foo' => 'Bar' ] ),
		];

		$client = $this->getClient( $queue, $delays );
		$response = $client->request( 'GET', '/' );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( [ 1000 ], $delays );
	}

	public function testRetries500Errors(): void {
		$queue = [
			new Response( 500 ),
			new Response( 200 ),
		];

		$client = $this->getClient( $queue, $delays );
		$response = $client->request( 'GET', '/' );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( [ 1000 ], $delays );
	}

	public function testRetriesSomeMediawikiApiErrorHeaders(): void {
		$queue = [
			new Response( 200, [ 'mediawiki-api-error' => 'ratelimited' ] ),
			new Response( 200, [ 'mediawiki-api-error' => 'maxlag' ] ),
			new Response( 200, [ 'mediawiki-api-error' => 'readonly' ] ),
			new Response( 200, [ 'mediawiki-api-error' => 'internal_api_error_DBQueryError' ] ),
			new Response( 200, [ 'mediawiki-api-error' => 'DoNotRetryThisHeader' ] ),
		];

		$client = $this->getClient( $queue, $delays );
		$response = $client->request( 'GET', '/' );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals(
			[ 'DoNotRetryThisHeader' ],
			$response->getHeader( 'mediawiki-api-error' )
		);
		$this->assertEquals( [ 1000, 2000, 3000, 4000 ], $delays );
	}

	public function testRetryAntiAbuseMeasure(): void {
		$antiAbusejson = json_encode(
			[
				'error' => [
					'info' => 'anti-abuse measure'
				]
			]
		);

		$queue = [
			new Response( 200, [ 'mediawiki-api-error' => 'failed-save' ], $antiAbusejson ),
			new Response( 200, [ 'mediawiki-api-error' => 'DoNotRetryThisHeader' ] ),
		];

		$client = $this->getClient( $queue, $delays );
		$response = $client->request( 'GET', '/' );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 'DoNotRetryThisHeader', $response->getHeaderLine( 'mediawiki-api-error' ) );
	}

	public function testRetryLimit(): void {
		$queue = [
			new ConnectException( 'Error 1', new Request( 'GET', 'test' ) ),
			new ConnectException( 'Error 2', new Request( 'GET', 'test' ) ),
			new ConnectException( 'Error 3', new Request( 'GET', 'test' ) ),
			new ConnectException( 'Error 4', new Request( 'GET', 'test' ) ),
			new ConnectException( 'Error 5', new Request( 'GET', 'test' ) ),
			new ConnectException( 'Error 6', new Request( 'GET', 'test' ) ),
			new Response( 200 ),
		];

		$client = $this->getClient( $queue );

		$this->expectException( ConnectException::class );
		$this->expectExceptionMessage( 'Error 6' );

		$client->request( 'GET', '/' );
	}

	public function testConnectExceptionRetryDelay(): void {
		$queue = [
			new ConnectException( '+1 second delay', new Request( 'GET', 'test' ) ),
			new ConnectException( '+2 second delay', new Request( 'GET', 'test' ) ),
			new Response( 200 ),
		];

		$client = $this->getClient( $queue, $delays );
		$response = $client->request( 'GET', '/' );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( [ 1000, 2000 ], $delays );
	}

	public function testServerErrorRetryDelay(): void {
		$queue = [
			new Response( 500 ),
			new Response( 503 ),
			new Response( 200 ),
		];

		$client = $this->getClient( $queue, $delays );
		$response = $client->request( 'GET', '/' );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( [ 1000, 2000 ], $delays );
	}

	public function testRelativeRetryDelayHeaderRetryDelay(): void {
		$queue = [
			new Response( 200, [ 'mediawiki-api-error' => 'maxlag', 'retry-after' => 10 ] ),
			new Response( 200 ),
		];

		$this->getClient( $queue, $delays )->request( 'GET', '/' );

		$this->assertEquals( [ 10000 ], $delays );
	}

	public function testAbsoluteRetryDelayHeaderRetryDelay(): void {
		$queue = [
			new Response(
				200,
				[
					'mediawiki-api-error' => 'maxlag',
					'retry-after' => gmdate( DATE_RFC1123, time() + 600 ),
				]
			),
			new Response( 200 ),
		];

		$client = $this->getClient( $queue, $delays );
		$response = $client->request( 'GET', '/' );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertCount( 1, $delays );
		// Allow 5 second delay while running this test.
		$this->assertGreaterThan( 600000 - 5000, $delays[0] );
	}

	public function testPastRetryDelayHeaderRetryDelay(): void {
		$queue = [
			new Response(
				200,
				[
					'mediawiki-api-error' => 'maxlag',
					'retry-after' => 'Fri, 31 Dec 1999 23:59:59 GMT',
				]
			),
			new Response( 200 ),
		];

		$client = $this->getClient( $queue, $delays );
		$response = $client->request( 'GET', '/' );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( [ 1000 ], $delays );
	}

	private function getClient( array $queue, &$delays = null ): Client {
		$mock = new MockHandler( $queue );

		$handler = HandlerStack::create( $mock );

		$middlewareFactory = new MiddlewareFactory();
		$handler->push( $middlewareFactory->retry() );

		$delayMocker = $this->getDelayMocker( $delays );
		$handler->push( $delayMocker );

		return new Client( [ 'handler' => $handler ] );
	}

	private function getDelayMocker( &$delays ): callable {
		return static function ( callable $handler ) use ( &$delays ): callable {
			return static function ( $request, array $options ) use ( $handler, &$delays ) {
				if ( isset( $options['delay'] ) ) {
					$delays[] = $options['delay'];
					unset( $options['delay'] );
				}

				return $handler( $request, $options );
			};
		};
	}
}
