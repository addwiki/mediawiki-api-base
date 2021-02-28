<?php

namespace Addwiki\Mediawiki\Api\Tests\Unit\Client\Auth;

use Addwiki\Mediawiki\Api\Client\Auth\UserAndPassword;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers Mediawiki\Api\Client\Auth\UserAndPassword
 */
class UserAndPasswordTest extends TestCase {

	/**
	 * @dataProvider provideValidConstruction
	 */
	public function testValidConstruction( string $user, string $pass ): void {
		$userAndPassword = new UserAndPassword( $user, $pass );
		$this->assertSame( $user, $userAndPassword->getUsername() );
		$this->assertSame( $pass, $userAndPassword->getPassword() );
	}

	public function provideValidConstruction(): array {
		return [
			[ 'user', 'pass' ],
		];
	}

	/**
	 * @dataProvider provideInvalidConstruction
	 */
	public function testInvalidConstruction( string $user, string $pass, ?string $domain = null ): void {
		$this->expectException( InvalidArgumentException::class );
		 new UserAndPassword( $user, $pass, $domain );
	}

	public function provideInvalidConstruction(): array {
		return [
			[ 'user', '' ],
			[ '', 'pass' ],
			[ '', '' ],
		];
	}

	/**
	 * @dataProvider provideTestEquals
	 */
	public function testEquals( UserAndPassword $user1, UserAndPassword $user2, bool $shouldEqual ): void {
		$this->assertSame( $shouldEqual, $user1->equals( $user2 ) );
		$this->assertSame( $shouldEqual, $user2->equals( $user1 ) );
	}

	public function provideTestEquals(): array {
		return [
			[ new UserAndPassword( 'usera', 'passa' ), new UserAndPassword( 'usera', 'passa' ), true ],
			[ new UserAndPassword( 'DIFF', 'passa' ), new UserAndPassword( 'usera', 'passa' ), false ],
			[ new UserAndPassword( 'usera', 'DIFF' ), new UserAndPassword( 'usera', 'passa' ), false ],
			[ new UserAndPassword( 'usera', 'passa' ), new UserAndPassword( 'DIFF', 'passa' ), false ],
			[ new UserAndPassword( 'usera', 'passa' ), new UserAndPassword( 'usera', 'DIFF' ), false ],
		];
	}

}
