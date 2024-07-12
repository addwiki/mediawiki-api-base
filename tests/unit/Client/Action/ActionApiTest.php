<?php

namespace Addwiki\Mediawiki\Api\Tests\Unit\Client\Action;

use Addwiki\Mediawiki\Api\Client\Action\ActionApi;
use Addwiki\Mediawiki\Api\Client\Action\Exception\UsageException;
use Addwiki\Mediawiki\Api\Client\Action\Request\ActionRequest;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;

class ActionApiTest extends TestCase {

	/**
	 * @return string[][]
	 */
	public function provideValidConstruction(): array {
		return [
			[ 'localhost' ],
			[ 'http://en.wikipedia.org/w/api.php' ],
			[ '127.0.0.1/foo/bar/wwwwwwwww/api.php' ],
		];
	}

	/**
	 * @dataProvider provideValidConstruction
	 */
	public function testValidConstruction( string $apiLocation ): void {
		new ActionApi( $apiLocation, null );
		$this->assertTrue( true );
	}

	/**
	 * @return ClientInterface&MockObject
	 */
	private function getMockClient() {
		return $this->createMock( ClientInterface::class );
	}

	/**
	 * @return MockObject&ResponseInterface
	 */
	private function getMockResponse( $responseValue ) {
		$mock = $this->createMock( ResponseInterface::class );
		$mock
			->method( 'getBody' )
			->willReturn( \GuzzleHttp\Psr7\Utils::streamFor( json_encode( $responseValue ) ) );
		return $mock;
	}

	/**
	 * @return array <int|string mixed[]>
	 */
	private function getExpectedRequestOpts( $params, $paramsLocation ): array {
		return [
			$paramsLocation => array_merge( $params, [ 'format' => 'json', 'assert' => 'anon' ] ),
			'headers' => [ 'User-Agent' => 'addwiki-mediawiki-client' ],
		];
	}

	public function testGetRequestThrowsUsageExceptionOnError(): void {
		$client = $this->getMockClient();
		$client->expects( $this->once() )
			->method( 'request' )
			->will( $this->returnValue(
				$this->getMockResponse( [ 'error' => [
					'code' => 'imacode',
					'info' => 'imamsg',
				] ] )
			) );
		$api = new ActionApi( '', null, $client, );

		try{
			$api->request( ActionRequest::simpleGet( 'foo' ) );
			$this->fail( 'No Usage Exception Thrown' );
		}
		catch ( UsageException $usageException ) {
			$this->assertEquals( 'imacode', $usageException->getApiCode() );
			$this->assertEquals( 'imamsg', $usageException->getRawMessage() );
		}
	}

	public function testPostRequestThrowsUsageExceptionOnError(): void {
		$client = $this->getMockClient();
		$client->expects( $this->once() )
			->method( 'request' )
			->will( $this->returnValue(
				$this->getMockResponse( [ 'error' => [
					'code' => 'imacode',
					'info' => 'imamsg',
				] ] )
			) );
		$api = new ActionApi( '', null, $client );

		try{
			$api->request( ActionRequest::simplePost( 'foo' ) );
			$this->fail( 'No Usage Exception Thrown' );
		}
		catch ( UsageException $e ) {
			$this->assertSame( 'imacode', $e->getApiCode() );
			$this->assertSame( 'imamsg', $e->getRawMessage() );
		}
	}

	/**
	 * @dataProvider provideActionsParamsResults
	 */
	public function testGetActionReturnsResult( array $expectedResult, string $action, array $params = [] ): void {
		$client = $this->getMockClient();
		$params = array_merge( [ 'action' => $action ], $params );
		$client->expects( $this->once() )
			->method( 'request' )
			->with( 'GET', null, $this->getExpectedRequestOpts( $params, 'query' ) )
			->will( $this->returnValue( $this->getMockResponse( $expectedResult ) ) );
		$api = new ActionApi( '', null, $client );

		$result = $api->request( ActionRequest::simpleGet( $action, $params ) );

		$this->assertEquals( $expectedResult, $result );
	}

	/**
	 * @dataProvider provideActionsParamsResults
	 */
	public function testPostActionReturnsResult( array $expectedResult, string $action, array $params = [] ): void {
		$client = $this->getMockClient();
		$params = array_merge( [ 'action' => $action ], $params );
		$client->expects( $this->once() )
			->method( 'request' )
			->with( 'POST', null, $this->getExpectedRequestOpts( $params, 'form_params' ) )
			->will( $this->returnValue( $this->getMockResponse( $expectedResult ) ) );
		$api = new ActionApi( '', null, $client );

		$result = $api->request( ActionRequest::simplePost( $action, $params ) );

		$this->assertEquals( $expectedResult, $result );
	}

	/**
	 * @return resource|bool
	 */
	private function getNullFilePointer() {
		if ( !file_exists( '/dev/null' ) ) {
			// windows
			return fopen( 'NUL', 'r' );
		}

		return fopen( '/dev/null', 'r' );
	}

	public function testPostActionWithFileReturnsResult(): void {
		$dummyFile = $this->getNullFilePointer();
		$params = [
			'filename' => 'foo.jpg',
			'file' => $dummyFile,
		];
		$client = $this->getMockClient();
		$client->expects( $this->once() )->method( 'request' )->with(
				'POST',
				null,
				[
					'multipart' => [
						[ 'name' => 'action', 'contents' => 'upload' ],
						[ 'name' => 'filename', 'contents' => 'foo.jpg' ],
						[ 'name' => 'file', 'contents' => $dummyFile ],
						[ 'name' => 'format', 'contents' => 'json' ],
						[ 'name' => 'assert', 'contents' => 'anon' ],
					],
					'headers' => [ 'User-Agent' => 'addwiki-mediawiki-client' ],
				]
			)->will( $this->returnValue( $this->getMockResponse( [ 'success ' => 1 ] ) ) );
		$api = new ActionApi( '', null, $client );

		$result = $api->request( ActionRequest::simplePost( 'upload', $params ) );

		$this->assertEquals( [ 'success ' => 1 ], $result );
	}

	public function provideActionsParamsResults(): array {
		return [
			[ [ 'key' => 'value' ], 'logout' ],
			[ [ 'key' => 'value' ], 'logout', [ 'param1' => 'v1' ] ],
			[ [ 'key' => 'value', 'key2' => 1212, [] ], 'logout' ],
		];
	}

	/**
	 * @dataProvider provideVersions
	 */
	public function testGetVersion( string $apiValue, string $expectedVersion ): void {
		$client = $this->getMockClient();
		$params = [ 'action' => 'query', 'meta' => 'siteinfo', 'continue' => '' ];
		$client->expects( $this->exactly( 1 ) )
			->method( 'request' )
			->with( 'GET', null, $this->getExpectedRequestOpts( $params, 'query' ) )
			->will( $this->returnValue( $this->getMockResponse( [
				'query' => [
					'general' => [
						'generator' => $apiValue,
					],
				],
			] ) ) );
		$api = new ActionApi( '', null, $client );
		$this->assertEquals( $expectedVersion, $api->getVersion() );
	}

	public function provideVersions(): array {
		return [
			[ 'MediaWiki 1.25wmf13', '1.25' ],
			[ 'MediaWiki 1.24.1', '1.24.1' ],
			[ 'MediaWiki 1.19', '1.19' ],
			[ 'MediaWiki 1.0.0', '1.0.0' ],
		];
	}

	public function testLogWarningsWithWarningsDeeperInTheArray(): void {
		$input = [
			'upload' => [
				'result' => 'Warning',
				'warnings' => [
					'duplicate-archive' => 'Test.jpg'
				],
				'fileKey' => '157pzg7r75j4.bs0wl9.15.jpg',
				'sessionKey' => '157pzg7r75j4.bs0wl9.15.jpg'
			]
		];

		$client = $this->getMockClient();
		$api = new ActionApi( '', null, $client );

		$logger = $this->createMock( LoggerInterface::class );
		$logger
			->expects( $this->once() )
			->method( 'warning' );

		$api->setLogger( $logger );

		// Make logWarnings() accessible so we can test it the easy way.
		$reflection = new ReflectionClass( get_class( $api ) );
		$method = $reflection->getMethod( 'logWarnings' );
		$method->setAccessible( true );

		$method->invokeArgs( $api, [ $input ] );
	}
}
