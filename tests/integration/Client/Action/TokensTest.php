<?php

namespace Addwiki\Mediawiki\Api\Tests\Integration\Client\Action;

use Addwiki\Mediawiki\Api\Tests\Integration\BaseTestEnvironment;
use PHPUnit\Framework\TestCase;

class TokensTest extends TestCase {

	/**
	 * @dataProvider provideTokenTypes
	 */
	public function testGetAnonUserToken(): void {
		$api = BaseTestEnvironment::newInstance()->getActionApi();
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
