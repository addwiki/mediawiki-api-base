<?php

namespace Addwiki\Mediawiki\Api\Tests\Unit\Guzzle;

use Addwiki\Mediawiki\Api\Guzzle\ClientFactory;
use GuzzleHttp\HandlerStack;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * @covers Mediawiki\Api\Guzzle\ClientFactory
 */
class ClientFactoryTest extends TestCase {

	public function testNoConfig(): void {
		$clientFactory = new ClientFactory();

		$client = $clientFactory->getClient();

		$this->assertSame( $client, $clientFactory->getClient() );

		$config = $client->getConfig();
		$this->assertEquals( $config['headers']['User-Agent'], 'Addwiki - mediawiki-api-base' );

		$this->assertFalse( empty( $config['cookies'] ) );
	}

	public function testUserAgent(): void {
		$clientFactory = new ClientFactory( [ 'user-agent' => 'Foobar' ] );

		$client = $clientFactory->getClient();

		$this->assertNull( $client->getConfig( 'user-agent' ) );

		$config = $client->getConfig();
		$this->assertEquals( $config['headers']['User-Agent'], 'Foobar' );
	}

	public function testHeaders(): void {
		$clientFactory = new ClientFactory( [
			'headers' => [
				'User-Agent' => 'Foobar',
				'X-Foo' => 'Bar',
			]
		] );

		$client = $clientFactory->getClient();

		$headers = $client->getConfig( 'headers' );
		$this->assertCount( 2, $headers );
		$this->assertEquals( $headers['User-Agent'], 'Foobar' );
		$this->assertEquals( $headers['X-Foo'], 'Bar' );
	}

	public function testHandler(): void {
		$handler = HandlerStack::create();

		$clientFactory = new ClientFactory( [ 'handler' => $handler ] );

		$client = $clientFactory->getClient();

		$this->assertSame( $handler, $client->getConfig( 'handler' ) );
	}

	public function testMiddleware(): void {
		$invoked = false;
		$middleware = static function () use ( &$invoked ): callable {
			return static function () use ( &$invoked ): void {
				$invoked = true;
			};
		};

		$clientFactory = new ClientFactory( [ 'middleware' => [ $middleware ] ] );

		$client = $clientFactory->getClient();

		$this->assertNull( $client->getConfig( 'middleware' ) );

		$request = $this->createMock( RequestInterface::class );

		$handler = $client->getConfig( 'handler' );
		$handler->remove( 'http_errors' );
		$handler->remove( 'allow_redirects' );
		$handler->remove( 'cookies' );
		$handler->remove( 'prepare_body' );
		$handler( $request, [] );

		$this->assertTrue( $invoked );
	}
}
