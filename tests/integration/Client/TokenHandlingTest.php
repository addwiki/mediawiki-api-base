<?php

namespace Addwiki\Mediawiki\Api\Tests\Integration\Client;

use Addwiki\Mediawiki\Api\Tests\Integration\BaseTestEnvironment;
use PHPUnit\Framework\TestCase;

/**
 * @author Addshore
 */
class TokenHandlingTest extends TestCase {

	/**
	 * @dataProvider provideTokenTypes
	 *
	 * @covers Mediawiki\Api\MediawikiApi::getToken
	 * @covers Mediawiki\Api\MediawikiSession::getToken
	 */
	public function testGetAnonUserToken() {
		$api = BaseTestEnvironment::newInstance()->getApi();
		$this->assertEquals( '+\\', $api->getToken() );
	}

	public function provideTokenTypes() {
		return [
			[ 'csrf' ],
			[ 'edit' ],
		];
	}

}
