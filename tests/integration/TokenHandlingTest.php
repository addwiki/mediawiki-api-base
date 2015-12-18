<?php

namespace Mediawiki\Api\Test\Integration;

use Mediawiki\Api\MediawikiApi;

/**
 * @author Addshore
 */
class TokenHandlingTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideTokenTypes
	 *
	 * @covers Mediawiki\Api\MediawikiApi::getToken
	 * @covers Mediawiki\Api\MediawikiSession::getToken
	 */
	public function testGetAnonUserToken() {
		$api = MediawikiApi::newFromApiEndpoint( 'http://deployment.wikimedia.beta.wmflabs.org/w/api.php' );
		$this->assertEquals( '+\\', $api->getToken() );
	}

	public function provideTokenTypes() {
		return array(
			array( 'csrf' ),
			array( 'edit' ),
		);
	}

}
