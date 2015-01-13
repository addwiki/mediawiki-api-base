<?php

namespace Mediawiki\Api\Test;

class TokenHandlingTest extends \PHPUnit_Framework_TestCase {

	public function testGetAnonUserToken() {
		$api = new \Mediawiki\Api\MediawikiApi( 'http://localhost/w/api.php' );
		$this->assertEquals( '+\\', $api->getToken( 'csrf' ) );
	}

} 