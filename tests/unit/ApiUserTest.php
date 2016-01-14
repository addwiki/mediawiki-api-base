<?php

namespace Mediawiki\Api\Test\Unit;

use Mediawiki\Api\ApiUser;

/**
 * @author Addshore
 *
 * @covers Mediawiki\Api\ApiUser
 */
class ApiUserTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideValidConstruction
	 */
	public function testValidConstruction( $user, $pass, $domain = null ) {
		$apiUser = new ApiUser( $user, $pass, $domain );
		$this->assertSame( $user, $apiUser->getUsername() );
		$this->assertSame( $pass, $apiUser->getPassword() );
		$this->assertSame( $domain, $apiUser->getDomain() );
	}

	public function provideValidConstruction() {
		return array(
			array( 'user', 'pass' ),
			array( 'user', 'pass', 'domain' ),
		);
	}

	/**
	 * @dataProvider provideInvalidConstruction
	 */
	public function testInvalidConstruction( $user, $pass, $domain = null ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		 new ApiUser( $user, $pass, $domain );
	}

	public function provideInvalidConstruction() {
		return array(
			array( 'user', '' ),
			array( '', 'pass' ),
			array( '', '' ),
			array( 'user', array() ),
			array( 'user', 455667 ),
			array( 34567, 'pass' ),
			array( array(), 'pass' ),
			array( 'user', 'pass', array() ),
		);
	}

	/**
	 * @dataProvider provideTestEquals
	 */
	public function testEquals( ApiUser $user1, ApiUser $user2, $shouldEqual ) {
		$this->assertSame( $shouldEqual, $user1->equals( $user2 ) );
		$this->assertSame( $shouldEqual, $user2->equals( $user1 ) );
	}

	public function provideTestEquals() {
		return array(
			array( new ApiUser( 'usera', 'passa' ), new ApiUser( 'usera', 'passa' ), true ),
			array( new ApiUser( 'usera', 'passa', 'domain' ), new ApiUser( 'usera', 'passa', 'domain' ), true ),
			array( new ApiUser( 'DIFF', 'passa' ), new ApiUser( 'usera', 'passa' ), false ),
			array( new ApiUser( 'usera', 'DIFF' ), new ApiUser( 'usera', 'passa' ), false ),
			array( new ApiUser( 'usera', 'passa' ), new ApiUser( 'DIFF', 'passa' ), false ),
			array( new ApiUser( 'usera', 'passa' ), new ApiUser( 'usera', 'DIFF' ), false ),
		);
	}

}
