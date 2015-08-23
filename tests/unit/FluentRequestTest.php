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
		$this->assertInstanceOf( 'Mediawiki\Api\RequestOptions', $request->getOptions() );
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

	public function testSetOptions() {
		$request = new FluentRequest();

		$newOptions = new RequestOptions();
		$this->assertNotSame( $newOptions, $request->getOptions() );

		$request->setOptions( $newOptions );

		$this->assertSame( $newOptions, $request->getOptions() );
	}

	public function testSetAttempts() {
		$request = new FluentRequest();

		$request->setAttempts( 12 );

		$this->assertEquals( 12, $request->getOptions()->getAttempts() );
	}

}
