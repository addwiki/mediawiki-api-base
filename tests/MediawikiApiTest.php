<?php

namespace Mediawiki\Api\Test;

use Mediawiki\Api\ApiUser;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\UsageException;
use stdClass;

/**
 * @covers Mediawiki\Api\MediawikiApi
 */
class MediawikiApiTest extends \PHPUnit_Framework_TestCase {

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
	public function testValidConstruction( $apilocation ) {
		new MediawikiApi( $apilocation );
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
	public function testInvalidConstruction( $apilocation ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new MediawikiApi( $apilocation );
	}

	private function getMockClient() {
		$mock = $this->getMockBuilder( 'Guzzle\Service\Mediawiki\MediawikiApiClient' )
			->disableOriginalConstructor()
			->setMethods( array( 'getAction', 'postAction' ) )
			->getMock();
		return $mock;
	}

	public function testGetActionThrowsUsageExceptionOnError() {
		$client = $this->getMockClient();
		$client->expects( $this->once() )
			->method( 'getAction' )
			->will( $this->returnValue(
				array( 'error' => array(
					'code' => 'imacode',
					'info' => 'imamsg',
				) )
			) );
		$api = new MediawikiApi( $client );

		try{
			$api->getAction( 'foo' );
			$this->fail( 'No Usage Exception Thrown' );
		}
		catch( UsageException $e ) {
			$this->assertEquals( 'imacode', $e->getApiCode() );
			$this->assertEquals( 'imamsg', $e->getMessage() );
		}
	}

	public function testPostActionThrowsUsageExceptionOnError() {
		$client = $this->getMockClient();
		$client->expects( $this->once() )
			->method( 'postAction' )
			->will( $this->returnValue(
				array( 'error' => array(
					'code' => 'imacode',
					'info' => 'imamsg',
				) )
			) );
		$api = new MediawikiApi( $client );

		try{
			$api->postAction( 'foo' );
			$this->fail( 'No Usage Exception Thrown' );
		}
		catch( UsageException $e ) {
			$this->assertEquals( 'imacode', $e->getApiCode() );
			$this->assertEquals( 'imamsg', $e->getMessage() );
		}
	}

	/**
	 * @dataProvider provideActionsParamsResults
	 */
	public function testGetActionReturnsResult( $expectedResult, $action, $params = array() ) {
		$client = $this->getMockClient();
		$client->expects( $this->once() )
			->method( 'getAction' )
			->with( $this->equalTo( array_merge( array( 'action' => $action ), $params ) ) )
			->will( $this->returnValue( $expectedResult ) );
		$api = new MediawikiApi( $client );

		$result = $api->getAction( $action, $params );

		$this->assertEquals( $expectedResult, $result );

	}

	/**
	 * @dataProvider provideActionsParamsResults
	 */
	public function testPostActionReturnsResult( $expectedResult, $action, $params = array() ) {
		$client = $this->getMockClient();
		$client->expects( $this->once() )
			->method( 'postAction' )
			->with( $this->equalTo( array_merge( array( 'action' => $action ), $params ) ) )
			->will( $this->returnValue( $expectedResult ) );
		$api = new MediawikiApi( $client );

		$result = $api->postAction( $action, $params );

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
			->method( 'postAction' )
			->with( $this->equalTo( $eq1 ) )
			->will( $this->returnValue( array( 'login' => array(
				'result' => 'NeedToken',
				'token' => 'IamLoginTK',
			) ) ) );
		$client->expects( $this->at( 1 ) )
			->method( 'postAction' )
			->with( $this->equalTo( array_merge( $eq1, array( 'lgtoken' => 'IamLoginTK' ) ) ) )
			->will( $this->returnValue( array( 'login' => array( 'result' => 'Success' ) ) ) );
		$api = new MediawikiApi( $client );

		$this->assertTrue( $api->login( $user ) );
		$this->assertEquals( 'U1', $api->isLoggedin() );
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
			->method( 'postAction' )
			->with( $this->equalTo( $eq1 ) )
			->will( $this->returnValue( array( 'login' => array(
				'result' => 'NeedToken',
				'token' => 'IamLoginTK',
			) ) ) );
		$client->expects( $this->at( 1 ) )
			->method( 'postAction' )
			->with( $this->equalTo( array_merge( $eq1, array( 'lgtoken' => 'IamLoginTK' ) ) ) )
			->will( $this->returnValue( array( 'login' => array( 'result' => 'BADTOKENorsmthin' ) ) ) );
		$api = new MediawikiApi( $client );

		$this->assertFalse( $api->login( $user ) );
		$this->assertFalse( $api->isLoggedin() );
	}

	public function testLogout() {
		$client = $this->getMockClient();
		$client->expects( $this->at( 0 ) )
			->method( 'postAction' )
			->with( array( 'action' => 'logout' ) )
			->will( $this->returnValue( array( ) ) );
		$api = new MediawikiApi( $client );

		$this->assertTrue( $api->logout( ) );
	}

	public function testLogoutOnFailure() {
		$client = $this->getMockClient();
		$client->expects( $this->at( 0 ) )
			->method( 'postAction' )
			->with( array( 'action' => 'logout' ) )
			->will( $this->returnValue( null ) );
		$api = new MediawikiApi( $client );

		$this->assertFalse( $api->logout( ) );
	}

} 