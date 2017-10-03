<?php

namespace Mediawiki\Api\Test\Unit;

use Exception;
use Mediawiki\Api\MultipartRequest;
use PHPUnit_Framework_TestCase;

class MultipartRequestTest extends PHPUnit_Framework_TestCase {

	public function testBasics() {
		$request = new MultipartRequest();
		$this->assertEquals( [], $request->getMultipartParams() );

		// One parameter.
		$request->setParam( 'testparam', 'value' );
		$request->addMultipartParams( [ 'testparam' => [ 'lorem' => 'ipsum' ] ] );
		$this->assertEquals(
			[ 'testparam' => [ 'lorem' => 'ipsum' ] ],
			$request->getMultipartParams()
		);

		// Another parameter.
		$request->setParam( 'testparam2', 'value' );
		$request->addMultipartParams( [ 'testparam2' => [ 'lorem2' => 'ipsum2' ] ] );
		$this->assertEquals(
			[
				'testparam' => [ 'lorem' => 'ipsum' ],
				'testparam2' => [ 'lorem2' => 'ipsum2' ],
			],
			$request->getMultipartParams()
		);
	}

	/**
	 * You are not allowed to set multipart parameters on a parameter that doesn't exist.
	 * @expectedException Exception
	 * @expectedExceptionMessage Parameter 'testparam' is not already set on this request.
	 */
	public function testParamNotYetSet() {
		$request = new MultipartRequest();
		$request->addMultipartParams( [ 'testparam' => [] ] );
	}
}
