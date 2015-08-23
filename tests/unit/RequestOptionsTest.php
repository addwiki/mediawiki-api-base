<?php

use Mediawiki\Api\RequestOptions;

/**
 * @covers Mediawiki\Api\RequestOptions
 */
class RequestOptionsTest extends \PHPUnit_Framework_TestCase {

	public function testConstructionDefaults() {
		$options = new RequestOptions();

		$this->assertEquals( 1, $options->getAttempts() );
	}

	public function testSetAttempts() {
		$options = new RequestOptions();

		$options->setAttempts( 9 );

		$this->assertEquals( 9, $options->getAttempts() );
	}

	public function provideValuesLessThanOne() {
		return array(
			array( 0 ),
			array( -2 ),
		);
	}

	/**
	 * @dataProvider provideValuesLessThanOne
	 */
	public function testAttemptsCanNoBeLessThanOne( $value ) {
		$options = new RequestOptions();

		$this->setExpectedException( '\InvalidArgumentException' );

		$options->setAttempts( $value );
	}

}
