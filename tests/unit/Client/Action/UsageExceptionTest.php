<?php

namespace Addwiki\Mediawiki\Api\Tests\Unit\Client\Action;

use Addwiki\Mediawiki\Api\Client\Action\Exception\UsageException;
use PHPUnit\Framework\TestCase;

/**
 * @covers Mediawiki\Api\UsageException
 */
class UsageExceptionTest extends TestCase {

	public function testUsageExceptionWithNoParams(): void {
		$e = new UsageException();
		$this->assertSame(
			'Code: ' . PHP_EOL .
			'Message: ' . PHP_EOL .
			'Result: []',
			$e->getMessage()
		);
		$this->assertSame( '', $e->getApiCode() );
		$this->assertEquals( [], $e->getApiResult() );
	}

	public function testUsageExceptionWithParams(): void {
		$e = new UsageException( 'imacode', 'imamsg', [ 'foo' => 'bar' ] );
		$this->assertSame( 'imacode', $e->getApiCode() );
		$this->assertSame(
			'Code: imacode' . PHP_EOL .
			'Message: imamsg' . PHP_EOL .
			'Result: {"foo":"bar"}',
			$e->getMessage()
		);
		$this->assertEquals( [ 'foo' => 'bar' ], $e->getApiResult() );
	}

}
