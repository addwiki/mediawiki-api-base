<?php

namespace Addwiki\Mediawiki\Api\Tests\Unit\Client\Action;

use Addwiki\Mediawiki\Api\Client\Action\ActionApi;
use Addwiki\Mediawiki\Api\Client\Action\Tokens;
use Addwiki\Mediawiki\Api\Client\Request\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TokensTest extends TestCase {

	/**
	 * @return MockObject|ActionApi
	 */
	private function getMockApi() {
		return $this->createMock( ActionApi::class );
	}

	public function testConstruction(): void {
		$session = new Tokens( $this->getMockApi() );
		$this->assertInstanceOf( Tokens::class, $session );
	}

	/**
	 * @dataProvider provideTokenTypes
	 */
	public function testGetToken( string $tokenType ): void {
		$mockApi = $this->getMockApi();
		$mockApi->expects( $this->exactly( 2 ) )
			->method( 'request' )
			->with( $this->isInstanceOf( Request::class ) )
			->willReturn( [
				'query' => [
					'tokens' => [
					$tokenType => 'TKN-' . $tokenType,
					]
				]
			] );

		$session = new Tokens( $mockApi );

		// Although we make 2 calls to the method we assert the tokens method about is only called once
		$this->assertEquals( 'TKN-' . $tokenType, $session->get() );
		$this->assertEquals( 'TKN-' . $tokenType, $session->get() );
		// Then clearing the tokens and calling again should make a second call!
		$session->clear();
		$this->assertEquals( 'TKN-' . $tokenType, $session->get() );
	}

	/**
	 * @dataProvider provideTokenTypes
	 */
	public function testGetTokenPre125( string $tokenType ): void {
		$mockApi = $this->getMockApi();
		$mockApi->method( 'request' )
			->with( $this->isInstanceOf( Request::class ) )
			->willReturnOnConsecutiveCalls(
				[
					'warnings' => [
						'query' => [
							'*' => "Unrecognized value for parameter 'meta': tokens",
						]
					]
				],
				[
					'tokens' => [
						$tokenType => 'TKN-' . $tokenType,
					]
				]
			);

		$session = new Tokens( $mockApi );

		// Although we make 2 calls to the method we assert the tokens method about is only called once
		$this->assertSame( 'TKN-' . $tokenType, $session->get() );
		$this->assertSame( 'TKN-' . $tokenType, $session->get() );
	}

	public function provideTokenTypes(): array {
		return [
			[ 'csrf' ],
			[ 'edit' ],
		];
	}

}
