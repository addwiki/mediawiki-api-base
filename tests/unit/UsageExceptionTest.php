<?php

namespace Mediawiki\Api\Test;

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
		$this->assertEquals( '', $e->getMessage() );
		$this->assertEquals( '', $e->getApiCode() );
		$this->assertEquals( array(), $e->getApiResult() );
	}

	public function testUsageExceptionWithParams() {
		$e = new UsageException( 'imacode', 'imamsg', array( 'foo' => 'bar' ) );
		$this->assertEquals( 'imacode', $e->getApiCode() );
		$this->assertEquals( 'imamsg', $e->getMessage() );
		$this->assertEquals( array( 'foo' => 'bar' ), $e->getApiResult() );
	}

}
