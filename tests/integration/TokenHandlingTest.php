<?php

namespace Mediawiki\Api\Test;

class TokenHandlingTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideTokenTypes
	 */
	public function testGetAnonUserToken() {
		$api = new \Mediawiki\Api\MediawikiApi( 'http://localhost/w/api.php' );
		$this->assertEquals( '+\\', $api->getToken() );
	}

	public function provideTokenTypes() {
		return array(
			array( 'csrf' ),
			array( 'edit' ),
		);
	}

} 