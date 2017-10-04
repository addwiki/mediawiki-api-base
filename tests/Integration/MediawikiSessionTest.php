<?php

namespace Mediawiki\Api\Test\Integration;

use Mediawiki\Api\MediawikiSession;
use PHPUnit_Framework_TestCase;

class MediawikiSessionTest extends PHPUnit_Framework_TestCase {

	public function testSession() {
		$api = TestEnvironment::newInstance()->getApi();
		$session = new MediawikiSession( $api );
		$token = $session->getToken();
		$this->assertEquals( '+\\', $token );
	}
}
