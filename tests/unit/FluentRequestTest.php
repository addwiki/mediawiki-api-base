<?php
use Mediawiki\Api\FluentRequest;
use Mediawiki\Api\RequestOptions;

/**
 * @covers Mediawiki\Api\FluentRequest
 */
class FluentRequestTest extends \PHPUnit_Framework_TestCase {

	public function testConstructionDefaults() {
		$request = new FluentRequest();

		$this->assertEquals( array(), $request->getParams() );
		$this->assertEquals( array(), $request->getHeaders() );
	}

	public function testSetParams() {
		$request = new FluentRequest();

		$params = array( 'foo', 'bar' );
		$request->setParams( $params );

		$this->assertEquals( $params, $request->getParams() );
	}

	public function testSetHeaders() {
		$request = new FluentRequest();

		$params = array( 'foo', 'bar' );
		$request->setHeaders( $params );

		$this->assertEquals( $params, $request->getHeaders() );
	}

}
