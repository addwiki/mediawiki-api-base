<?php

namespace Addwiki\Mediawiki\Api\Tests\Unit\Client;

use Addwiki\Mediawiki\Api\Client\SimpleRequest;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @author Addshore
 *
 * @covers Mediawiki\Api\SimpleRequest
 */
class SimpleRequestTest extends TestCase {

	/**
	 * @dataProvider provideValidConstruction
	 */
	public function testValidConstruction( $action, $params, $expected, $headers = [] ) {
		$request = new SimpleRequest( $action, $params, $headers );
		$this->assertEquals( $expected, $request->getParams() );
		$this->assertEquals( $headers, $request->getHeaders() );
	}

	public function provideValidConstruction() {
		return [
			[ 'action', [], [ 'action' => 'action' ] ],
			[ '1123', [], [ 'action' => '1123' ] ],
			[ 'a', [ 'b' => 'c' ], [ 'action' => 'a', 'b' => 'c' ] ],
			[ 'a', [ 'b' => 'c', 'd' => 'e' ], [ 'action' => 'a', 'b' => 'c', 'd' => 'e' ] ],
			[ 'a', [ 'b' => 'c|d|e|f' ], [ 'action' => 'a', 'b' => 'c|d|e|f' ] ],
			[ 'foo', [], [ 'action' => 'foo' ] ,[ 'foo' => 'bar' ] ],
		];
	}

	/**
	 * @dataProvider provideInvalidConstruction
	 */
	public function testInvalidConstruction( $action, $params ) {
		$this->expectException( InvalidArgumentException::class );
		new SimpleRequest( $action, $params );
	}

	public function provideInvalidConstruction() {
		return [
			[ [], [] ],
		];
	}

}
