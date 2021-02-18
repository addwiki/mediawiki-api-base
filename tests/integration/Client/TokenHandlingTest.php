<?php

namespace Addwiki\Mediawiki\Api\Tests\Integration\Client;

use Addwiki\Mediawiki\Api\Tests\Integration\BaseTestEnvironment;
use PHPUnit\Framework\TestCase;

class TokenHandlingTest extends TestCase {

	/**
	 * @dataProvider provideTokenTypes
	 *
	 * @covers Mediawiki\Api\MediawikiApi::getToken
	 * @covers Mediawiki\Api\MediawikiSession::getToken
	 */
	public function testGetAnonUserToken(): void {
		$api = BaseTestEnvironment::newInstance()->getApi();
		$this->assertEquals( '+\\', $api->getToken() );
	}

	/**
	 * @return string[][]
	 */
	public function provideTokenTypes(): array {
		return [
			[ 'csrf' ],
			[ 'edit' ],
		];
	}

}
