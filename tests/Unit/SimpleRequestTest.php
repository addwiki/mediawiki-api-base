<?php

namespace Mediawiki\Api\Test\Unit;

use Mediawiki\Api\SimpleRequest;
use PHPUnit_Framework_TestCase;

/**
 * @author Addshore
 *
 * @covers Mediawiki\Api\SimpleRequest
 */
class SimpleRequestTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideValidConstruction
	 */
	public function testValidConstruction( $action, $params, $expected, $headers = array() ) {
		$request = new SimpleRequest( $action, $params, $headers );
		$this->assertEquals( $expected, $request->getParams() );
		$this->assertEquals( $headers, $request->getHeaders() );
	}

	public function provideValidConstruction() {
		return array(
			array( 'action', array(), array( 'action' => 'action' ) ),
			array( '1123', array(), array( 'action' => '1123' ) ),
			array( 'a', array( 'b' => 'c' ), array( 'action' => 'a', 'b' => 'c' ) ),
			array( 'a', array( 'b' => 'c', 'd' => 'e' ), array( 'action' => 'a', 'b' => 'c', 'd' => 'e' ) ),
			array( 'a', array( 'b' => 'c|d|e|f' ), array( 'action' => 'a', 'b' => 'c|d|e|f' ) ),
			array( 'foo', array(), array( 'action' => 'foo' ) ,array( 'foo' => 'bar' ) ),
		);
	}

	/**
	 * @dataProvider provideInvalidConstruction
	 */
	public function testInvalidConstruction( $action, $params ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new SimpleRequest( $action, $params );
	}

	public function provideInvalidConstruction() {
		return array(
			array( array(), array() ),
		);
	}

}
