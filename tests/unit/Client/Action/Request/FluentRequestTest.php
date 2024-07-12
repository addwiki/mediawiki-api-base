<?php

namespace Addwiki\Mediawiki\Api\Tests\Unit\Client\Action\Request;

use Addwiki\Mediawiki\Api\Client\Action\Request\ActionRequest;

use PHPUnit\Framework\TestCase;

class FluentRequestTest extends TestCase {

	public function testFactory(): void {
		$this->assertInstanceOf( ActionRequest::class, ActionRequest::factory() );
	}

	public function testConstructionDefaults(): void {
		$request = new ActionRequest();

		$this->assertEquals( [], $request->getParams() );
		$this->assertEquals( [], $request->getHeaders() );
	}

	public function testSetParams(): void {
		$request = new ActionRequest();

		$params = [ 'foo', 'bar' ];
		$request->setParams( $params );

		$this->assertEquals( $params, $request->getParams() );
	}

	public function testSetParam(): void {
		$request = new ActionRequest();

		$request->setParam( 'paramName', 'fooValue' );

		$this->assertEquals( [ 'paramName' => 'fooValue' ], $request->getParams() );
	}

	public function testAddParams(): void {
		$request = new ActionRequest();

		$params = [ 'a' => 'foo', 'b' => 'bar' ];
		$request->addParams( $params );

		$this->assertEquals( $params, $request->getParams() );
	}

	public function testSetHeaders(): void {
		$request = new ActionRequest();

		$params = [ 'foo', 'bar' ];
		$request->setHeaders( $params );

		$this->assertEquals( $params, $request->getHeaders() );
	}

	public function testSetAction(): void {
		$request = new ActionRequest();

		$request->setAction( 'fooAction' );

		$this->assertEquals( [ 'action' => 'fooAction' ], $request->getParams() );
	}

	public function testGetParameterEncoding(): void {
		$request = ActionRequest::factory();

		$request->setMethod( 'get' );
		$this->assertSame( 'query', $request->getParameterEncoding() );

		$request->setMethod( 'GET' );
		$this->assertSame( 'query', $request->getParameterEncoding() );

		$request->setMethod( 'post' );
		$this->assertSame( 'form_params', $request->getParameterEncoding() );
	}

}
