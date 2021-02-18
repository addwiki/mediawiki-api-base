<?php

namespace Addwiki\Mediawiki\Api\Tests\Unit\Client;

use Addwiki\Mediawiki\Api\Client\FluentRequest;
use PHPUnit\Framework\TestCase;

/**
 * @covers Mediawiki\Api\FluentRequest
 */
class FluentRequestTest extends TestCase {

	public function testFactory(): void {
		$this->assertInstanceOf( FluentRequest::class, FluentRequest::factory() );
	}

	public function testConstructionDefaults(): void {
		$request = new FluentRequest();

		$this->assertEquals( [], $request->getParams() );
		$this->assertEquals( [], $request->getHeaders() );
	}

	public function testSetParams(): void {
		$request = new FluentRequest();

		$params = [ 'foo', 'bar' ];
		$request->setParams( $params );

		$this->assertEquals( $params, $request->getParams() );
	}

	public function testSetParam(): void {
		$request = new FluentRequest();

		$request->setParam( 'paramName', 'fooValue' );

		$this->assertEquals( [ 'paramName' => 'fooValue' ], $request->getParams() );
	}

	public function testAddParams(): void {
		$request = new FluentRequest();

		$params = [ 'a' => 'foo', 'b' => 'bar' ];
		$request->addParams( $params );

		$this->assertEquals( $params, $request->getParams() );
	}

	public function testSetHeaders(): void {
		$request = new FluentRequest();

		$params = [ 'foo', 'bar' ];
		$request->setHeaders( $params );

		$this->assertEquals( $params, $request->getHeaders() );
	}

	public function testSetAction(): void {
		$request = new FluentRequest();

		$request->setAction( 'fooAction' );

		$this->assertEquals( [ 'action' => 'fooAction' ], $request->getParams() );
	}

}
