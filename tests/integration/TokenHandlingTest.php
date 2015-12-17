<?php

namespace Mediawiki\Api\Test;

use Mediawiki\Api\MediawikiApi;

/**
 * @author Addshore
 */
class TokenHandlingTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideTokenTypes
	 */
	public function testGetAnonUserToken() {
		$api = new MediawikiApi( 'http://localhost/w/api.php' );
		$this->assertEquals( '+\\', $api->getToken() );
	}

	public function provideTokenTypes() {
		return array(
			array( 'csrf' ),
			array( 'edit' ),
		);
	}

}
