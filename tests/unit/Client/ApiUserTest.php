<?php

namespace Addwiki\Mediawiki\Api\Tests\Unit\Client;

use Addwiki\Mediawiki\Api\Client\ApiUser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers Mediawiki\Api\ApiUser
 */
class ApiUserTest extends TestCase {

	/**
	 * @dataProvider provideValidConstruction
	 */
	public function testValidConstruction( string $user, string $pass, ?string $domain = null ): void {
		$apiUser = new ApiUser( $user, $pass, $domain );
		$this->assertSame( $user, $apiUser->getUsername() );
		$this->assertSame( $pass, $apiUser->getPassword() );
		$this->assertSame( $domain, $apiUser->getDomain() );
	}

	public function provideValidConstruction(): array {
		return [
			[ 'user', 'pass' ],
			[ 'user', 'pass', 'domain' ],
		];
	}

	/**
	 * @dataProvider provideInvalidConstruction
	 */
	public function testInvalidConstruction( string $user, string $pass, ?string $domain = null ): void {
		$this->expectException( InvalidArgumentException::class );
		 new ApiUser( $user, $pass, $domain );
	}

	public function provideInvalidConstruction(): array {
		return [
			[ 'user', '' ],
			[ '', 'pass' ],
			[ '', '' ],
			[ '', '', '' ],
			[ 'aaa', 'aaa', '' ],
		];
	}

	/**
	 * @dataProvider provideTestEquals
	 */
	public function testEquals( ApiUser $user1, ApiUser $user2, bool $shouldEqual ): void {
		$this->assertSame( $shouldEqual, $user1->equals( $user2 ) );
		$this->assertSame( $shouldEqual, $user2->equals( $user1 ) );
	}

	public function provideTestEquals(): array {
		return [
			[ new ApiUser( 'usera', 'passa' ), new ApiUser( 'usera', 'passa' ), true ],
			[ new ApiUser( 'usera', 'passa', 'domain' ), new ApiUser( 'usera', 'passa', 'domain' ), true ],
			[ new ApiUser( 'DIFF', 'passa' ), new ApiUser( 'usera', 'passa' ), false ],
			[ new ApiUser( 'usera', 'DIFF' ), new ApiUser( 'usera', 'passa' ), false ],
			[ new ApiUser( 'usera', 'passa' ), new ApiUser( 'DIFF', 'passa' ), false ],
			[ new ApiUser( 'usera', 'passa' ), new ApiUser( 'usera', 'DIFF' ), false ],
		];
	}

}
