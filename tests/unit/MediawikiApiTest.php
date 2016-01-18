<?php

namespace Mediawiki\Api\Test\Unit;

use Mediawiki\Api\ApiUser;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\SimpleRequest;
use Mediawiki\Api\UsageException;
use PHPUnit_Framework_TestCase;
use stdClass;

/**
 * @author Addshore
 *
 * @covers Mediawiki\Api\MediawikiApi
 */
class MediawikiApiTest extends PHPUnit_Framework_TestCase {

	public function provideValidConstruction() {
		return array(
			array( 'localhost' ),
			array( 'http://en.wikipedia.org/w/api.php' ),
			array( '127.0.0.1/foo/bar/wwwwwwwww/api.php' ),
		);
	}

	/**
	 * @dataProvider provideValidConstruction
	 */
	public function testValidConstruction( $apiLocation ) {
		new MediawikiApi( $apiLocation );
		$this->assertTrue( true );
	}

	public function provideInvalidConstruction() {
		return array(
			array( null ),
			array( 12345678 ),
			array( array() ),
			array( new stdClass() ),
		);
	}

	/**
	 * @dataProvider provideInvalidConstruction
	 */
	public function testInvalidConstruction( $apiLocation ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new MediawikiApi( $apiLocation );
	}

	private function getMockClient() {
		return $this->getMock( 'GuzzleHttp\ClientInterface' );
	}

	private function getMockResponse( $responseValue ) {
		$mock = $this->getMock( 'Psr\Http\Message\ResponseInterface' );
		$mock->expects( $this->any() )
			->method( 'getBody' )
			->will( $this->returnValue( json_encode( $responseValue ) ) );
		return $mock;
	}

	private function getExpectedRequestOpts( $params, $wasPosted = false ) {
		if( $wasPosted ) {
			$paramsLocation = 'form_params';
		} else {
			$paramsLocation = 'query';
		}
		return array(
			$paramsLocation => array_merge( $params, array( 'format' => 'json' ) ),
			'headers' => array( 'User-Agent' => 'addwiki-mediawiki-client' ),
		);
	}

	public function testGetRequestThrowsUsageExceptionOnError() {
		$client = $this->getMockClient();
		$client->expects( $this->once() )
			->method( 'request' )
			->will( $this->returnValue(
				$this->getMockResponse( array( 'error' => array(
					'code' => 'imacode',
					'info' => 'imamsg',
				) ) )
			) );
		$api = new MediawikiApi( '', $client );

		try{
			$api->getRequest( new SimpleRequest( 'foo' ) );
			$this->fail( 'No Usage Exception Thrown' );
		}
		catch( UsageException $e ) {
			$this->assertEquals( 'imacode', $e->getApiCode() );
			$this->assertEquals( 'imamsg', $e->getMessage() );
		}
	}

	public function testPostRequestThrowsUsageExceptionOnError() {
		$client = $this->getMockClient();
		$client->expects( $this->once() )
			->method( 'request' )
			->will( $this->returnValue(
				$this->getMockResponse( array( 'error' => array(
					'code' => 'imacode',
					'info' => 'imamsg',
				) ) )
			) );
		$api = new MediawikiApi( '', $client );

		try{
			$api->postRequest( new SimpleRequest( 'foo' ) );
			$this->fail( 'No Usage Exception Thrown' );
		}
		catch( UsageException $e ) {
			$this->assertSame( 'imacode', $e->getApiCode() );
			$this->assertSame( 'imamsg', $e->getMessage() );
		}
	}

	/**
	 * @dataProvider provideActionsParamsResults
	 */
	public function testGetActionReturnsResult( $expectedResult, $action, $params = array() ) {
		$client = $this->getMockClient();
		$client->expects( $this->once() )
			->method( 'request' )
			->with( 'GET', null, $this->getExpectedRequestOpts( array_merge( array( 'action' => $action ), $params ) ) )
			->will( $this->returnValue( $this->getMockResponse( $expectedResult ) ) );
		$api = new MediawikiApi( '', $client );

		$result = $api->getRequest( new SimpleRequest( $action, $params ) );

		$this->assertEquals( $expectedResult, $result );
	}

	/**
	 * @dataProvider provideActionsParamsResults
	 */
	public function testPostActionReturnsResult( $expectedResult, $action, $params = array() ) {
		$client = $this->getMockClient();
		$client->expects( $this->once() )
			->method( 'request' )
			->with( 'POST', null, $this->getExpectedRequestOpts( array_merge( array( 'action' => $action ), $params ), true ))
			->will( $this->returnValue( $this->getMockResponse( $expectedResult ) ) );
		$api = new MediawikiApi( '', $client );

		$result = $api->postRequest( new SimpleRequest( $action, $params ) );

		$this->assertEquals( $expectedResult, $result );

	}

	public function provideActionsParamsResults() {
		return array(
			array( array( 'key' => 'value' ), 'logout' ),
			array( array( 'key' => 'value' ), 'logout', array( 'param1' => 'v1' ) ),
			array( array( 'key' => 'value', 'key2' => 1212, array() ), 'logout' ),
		);
	}

	public function testGoodLoginSequence() {
		$client = $this->getMockClient();
		$user = new ApiUser( 'U1', 'P1' );
		$eq1 = array(
			'action' => 'login',
			'lgname' => 'U1',
			'lgpassword' => 'P1',
		);
		$client->expects( $this->at( 0 ) )
			->method( 'request' )
			->with( 'POST', null, $this->getExpectedRequestOpts( $eq1, true ) )
			->will( $this->returnValue( $this->getMockResponse( array( 'login' => array(
				'result' => 'NeedToken',
				'token' => 'IamLoginTK',
			) ) ) ) );
		$client->expects( $this->at( 1 ) )
			->method( 'request' )
			->with( 'POST', null, $this->getExpectedRequestOpts( array_merge( $eq1, array( 'lgtoken' => 'IamLoginTK' ) ), true ) )
			->will( $this->returnValue( $this->getMockResponse( array( 'login' => array( 'result' => 'Success' ) ) ) ) );
		$api = new MediawikiApi( '', $client );

		$this->assertTrue( $api->login( $user ) );
		$this->assertSame( 'U1', $api->isLoggedin() );
	}

	public function testBadLoginSequence() {
		$client = $this->getMockClient();
		$user = new ApiUser( 'U1', 'P1' );
		$eq1 = array(
			'action' => 'login',
			'lgname' => 'U1',
			'lgpassword' => 'P1',
		);
		$client->expects( $this->at( 0 ) )
			->method( 'request' )
			->with( 'POST', null, $this->getExpectedRequestOpts( $eq1, true ) )
			->will( $this->returnValue( $this->getMockResponse( array( 'login' => array(
				'result' => 'NeedToken',
				'token' => 'IamLoginTK',
			) ) ) ) );
		$client->expects( $this->at( 1 ) )
			->method( 'request' )
			->with( 'POST', null, $this->getExpectedRequestOpts( array_merge( $eq1, array( 'lgtoken' => 'IamLoginTK' ) ), true ) )
			->will( $this->returnValue( $this->getMockResponse( array( 'login' => array( 'result' => 'BADTOKENorsmthin' ) ) ) ) );
		$api = new MediawikiApi( '', $client );

		$this->setExpectedException( 'Mediawiki\Api\UsageException' );
		$api->login( $user );
	}

	public function testLogout() {
		$client = $this->getMockClient();
		$client->expects( $this->at( 0 ) )
			->method( 'request' )
			->with( 'POST', null, $this->getExpectedRequestOpts( array( 'action' => 'logout' ), true ) )
			->will( $this->returnValue( $this->getMockResponse( array( ) ) ) );
		$api = new MediawikiApi( '', $client );

		$this->assertTrue( $api->logout( ) );
	}

	public function testLogoutOnFailure() {
		$client = $this->getMockClient();
		$client->expects( $this->at( 0 ) )
			->method( 'request' )
			->with( 'POST', null, $this->getExpectedRequestOpts( array( 'action' => 'logout' ), true ) )
			->will( $this->returnValue( $this->getMockResponse( null ) ) );
		$api = new MediawikiApi( '', $client );

		$this->assertFalse( $api->logout( ) );
	}

	/**
	 * @dataProvider provideVersions
	 */
	public function testGetVersion( $apiValue, $expectedVersion ) {
		$client = $this->getMockClient();
		$client->expects( $this->exactly( 1 ) )
			->method( 'request' )
			->with( 'GET', null, $this->getExpectedRequestOpts( array( 'action' => 'query', 'meta' => 'siteinfo', 'continue' => '' ) ) )
			->will( $this->returnValue( $this->getMockResponse( array(
				'query' => array(
					'general' => array(
						'generator' => $apiValue,
					),
				),
			) ) ) );
		$api = new MediawikiApi( '', $client );
		$this->assertEquals( $expectedVersion, $api->getVersion() );
	}

	public function provideVersions() {
		return array(
			array( 'MediaWiki 1.25wmf13', '1.25' ),
			array( 'MediaWiki 1.24.1', '1.24.1' ),
			array( 'MediaWiki 1.19', '1.19' ),
			array( 'MediaWiki 1.0.0', '1.0.0' ),
		);
	}
}
