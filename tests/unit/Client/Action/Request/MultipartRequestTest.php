<?php

namespace Addwiki\Mediawiki\Api\Tests\Unit\Client\Action\Request;

use Addwiki\Mediawiki\Api\Client\Action\Request\ActionRequest;
use Exception;

use PHPUnit\Framework\TestCase;

class MultipartRequestTest extends TestCase {

	public function testBasics(): void {
		$request = new ActionRequest();
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
	 */
	public function testParamNotYetSet(): void {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( "Parameter 'testparam' is not already set on this request." );
		$request = new ActionRequest();
		$request->addMultipartParams( [ 'testparam' => [] ] );
	}
}
