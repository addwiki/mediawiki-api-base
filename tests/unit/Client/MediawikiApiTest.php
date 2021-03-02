<?php

namespace Addwiki\Mediawiki\Api\Tests\Unit\Client;

use Addwiki\Mediawiki\Api\Client\ApiUser;
use Addwiki\Mediawiki\Api\Client\MediawikiApi;
use Addwiki\Mediawiki\Api\Client\Request\SimpleRequest;
use Addwiki\Mediawiki\Api\Client\UsageException;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * @covers Mediawiki\Api\MediawikiApi
 */
class MediawikiApiTest extends TestCase {

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
		new MediawikiApi( $apiLocation, null );
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
			->willReturn( json_encode( $responseValue ) );
		return $mock;
	}

	/**
	 * @return array <int|string mixed[]>
	 */
	private function getExpectedRequestOpts( $params, $paramsLocation ): array {
		return [
			$paramsLocation => array_merge( $params, [ 'format' => 'json' ] ),
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
		$api = new MediawikiApi( '', null, $client, );

		try{
			$api->getRequest( new SimpleRequest( 'foo' ) );
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
		$api = new MediawikiApi( '', null, $client );

		try{
			$api->postRequest( new SimpleRequest( 'foo' ) );
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
		$api = new MediawikiApi( '', null, $client );

		$result = $api->getRequest( new SimpleRequest( $action, $params ) );

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
		$api = new MediawikiApi( '', null, $client );

		$result = $api->postRequest( new SimpleRequest( $action, $params ) );

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
					],
					'headers' => [ 'User-Agent' => 'addwiki-mediawiki-client' ],
				]
			)->will( $this->returnValue( $this->getMockResponse( [ 'success ' => 1 ] ) ) );
		$api = new MediawikiApi( '', null, $client );

		$result = $api->postRequest( new SimpleRequest( 'upload', $params ) );

		$this->assertEquals( [ 'success ' => 1 ], $result );
	}

	public function provideActionsParamsResults(): array {
		return [
			[ [ 'key' => 'value' ], 'logout' ],
			[ [ 'key' => 'value' ], 'logout', [ 'param1' => 'v1' ] ],
			[ [ 'key' => 'value', 'key2' => 1212, [] ], 'logout' ],
		];
	}

	public function testGoodLoginSequence(): void {
		$user = new ApiUser( 'U1', 'P1' );
		$eq1 = [
			'action' => 'login',
			'lgname' => 'U1',
			'lgpassword' => 'P1',
		];
		$params = array_merge( $eq1, [ 'lgtoken' => 'IamLoginTK' ] );

		$client = $this->getMockClient();
		$client->method( 'request' )
			->withConsecutive(
				[ 'POST', null, $this->getExpectedRequestOpts( $eq1, 'form_params' ) ],
				[ 'POST', null, $this->getExpectedRequestOpts( $params, 'form_params' ) ]
			)
			->willReturnOnConsecutiveCalls(
				$this->getMockResponse( [ 'login' => [
					'result' => 'NeedToken',
					'token' => 'IamLoginTK',
				] ] ),
				$this->getMockResponse( [ 'login' => [ 'result' => 'Success' ] ] )
			);

		$api = new MediawikiApi( '', null, $client );
		$this->assertTrue( $api->login( $user ) );
		$this->assertSame( true, $api->isLoggedIn() );
	}

	public function testBadLoginSequence(): void {
		$client = $this->getMockClient();
		$user = new ApiUser( 'U1', 'P1' );
		$eq1 = [
			'action' => 'login',
			'lgname' => 'U1',
			'lgpassword' => 'P1',
		];
		$params = array_merge( $eq1, [ 'lgtoken' => 'IamLoginTK' ] );

		$client->method( 'request' )
			->withConsecutive(
				[ 'POST', null, $this->getExpectedRequestOpts( $eq1, 'form_params' ) ],
				[ 'POST', null, $this->getExpectedRequestOpts( $params, 'form_params' ) ],
			)
			->willReturnOnConsecutiveCalls(
				$this->getMockResponse( [ 'login' => [
					'result' => 'NeedToken',
					'token' => 'IamLoginTK',
				] ] ),
				$this->getMockResponse( [ 'login' => [ 'result' => 'BADTOKENorsmthin' ] ] )
			);

		$api = new MediawikiApi( '', null, $client );
		$this->expectException( UsageException::class );
		$api->login( $user );
	}

	public function testLogout(): void {
		$client = $this->getMockClient();
		$client->method( 'request' )
			->withConsecutive(
				[ 'POST', null, $this->getExpectedRequestOpts( [
					'action' => 'query',
					'meta' => 'tokens',
					'type' => 'csrf',
					'continue' => ''
				], 'form_params' ) ],
				[ 'POST', null, $this->getExpectedRequestOpts( [
					'action' => 'logout',
					'token' => 'TKN-csrf'
				], 'form_params' ) ]
			)
			->willReturnOnConsecutiveCalls(
				$this->returnValue( $this->getMockResponse( [
					'query' => [
						'tokens' => [
							'csrf' => 'TKN-csrf',
						]
					]
				] ) ),
				$this->returnValue( $this->getMockResponse( [] ) )
			);
		$api = new MediawikiApi( '', null, $client );

		$this->assertTrue( $api->logout() );
	}

	public function testLogoutOnFailure(): void {
		$client = $this->getMockClient();
		$client->method( 'request' )
			->withConsecutive(
				[ 'POST', null, $this->getExpectedRequestOpts( [
					'action' => 'query',
					'meta' => 'tokens',
					'type' => 'csrf',
					'continue' => ''
				], 'form_params' ) ],
				[ 'POST', null, $this->getExpectedRequestOpts( [
					'action' => 'logout',
					'token' => 'TKN-csrf'
				], 'form_params' ) ]
			)
			->willReturnOnConsecutiveCalls(
				$this->returnValue( $this->getMockResponse( [
					'query' => [
						'tokens' => [
							'csrf' => 'TKN-csrf',
						]
					]
				] ) ),
				$this->returnValue( $this->getMockResponse( null ) )
			);
		$api = new MediawikiApi( '', null, $client );

		$this->assertFalse( $api->logout() );
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
		$api = new MediawikiApi( '', null, $client );
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
		$api = new MediawikiApi( '', null, $client );

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
