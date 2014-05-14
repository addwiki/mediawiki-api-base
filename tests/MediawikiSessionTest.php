<?php

/**
 * @covers Mediawiki\Api\MediawikiSession
 */
class MediawikiSessionTest extends \PHPUnit_Framework_TestCase {

	public function testConstruction() {
		$mockClient = $this->getMock( '\Guzzle\Service\Mediawiki\MediawikiApiClient' );
		$session = new \Mediawiki\Api\MediawikiSession( $mockClient );
		$this->assertInstanceOf( '\Mediawiki\Api\MediawikiSession', $session );
	}

	/**
	 * @dataProvider provideTokenTypes
	 */
	public function testGetToken( $tokenType ) {
		$mockClient = $this->getMock(
			'\Guzzle\Service\Mediawiki\MediawikiApiClient',
			array( 'tokens' )
		);
		$mockClient->expects( $this->exactly( 2 ) )
			->method( 'tokens' )
			->with( $this->equalTo( array( 'type' => $tokenType ) ) )
			->will( $this->returnValue( array(
				'tokens' => array(
					$tokenType => 'TKN-' . $tokenType,
				)
			) ) );

		$session = new \Mediawiki\Api\MediawikiSession( $mockClient );

		//Although we make 2 calls to the method we assert the tokens method about is only called once
		$this->assertEquals( 'TKN-' . $tokenType, $session->getToken() );
		$this->assertEquals( 'TKN-' . $tokenType, $session->getToken() );
		//Then clearing the tokens and calling again should make a second call!
		$session->clearTokens();
		$this->assertEquals( 'TKN-' . $tokenType, $session->getToken() );
	}

	public function provideTokenTypes() {
		return array(
			array( 'edit' ),
		);
	}

} 