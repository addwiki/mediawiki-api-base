<?php

namespace Mediawiki\Api\Test\Unit;

use Mediawiki\Api\UsageException;
use PHPUnit_Framework_TestCase;

/**
 * @author Addshore
 *
 * @covers Mediawiki\Api\UsageException
 */
class UsageExceptionTest extends PHPUnit_Framework_TestCase {

	public function testUsageExceptionWithNoParams() {
		$e = new UsageException();
		$this->assertSame( '', $e->getMessage() );
		$this->assertSame( '', $e->getApiCode() );
		$this->assertEquals( array(), $e->getApiResult() );
	}

	public function testUsageExceptionWithParams() {
		$e = new UsageException( 'imacode', 'imamsg', array( 'foo' => 'bar' ) );
		$this->assertSame( 'imacode', $e->getApiCode() );
		$this->assertSame( 'imamsg', $e->getMessage() );
		$this->assertEquals( array( 'foo' => 'bar' ), $e->getApiResult() );
	}

}
