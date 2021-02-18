<?php

namespace Addwiki\Mediawiki\Api\Tests\Unit\Client;

use Addwiki\Mediawiki\Api\Client\MediawikiApi;
use Addwiki\Mediawiki\Api\Client\MediawikiSession;
use Addwiki\Mediawiki\Api\Client\SimpleRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author Addshore
 *
 * @covers Mediawiki\Api\MediawikiSession
 */
class MediawikiSessionTest extends TestCase {

	/**
	 * @return MockObject|MediawikiApi
	 */
	private function getMockApi() {
		return $this->createMock( MediawikiApi::class );
	}

	public function testConstruction(): void {
		$session = new MediawikiSession( $this->getMockApi() );
		$this->assertInstanceOf( MediawikiSession::class, $session );
	}

	/**
	 * @dataProvider provideTokenTypes
	 */
	public function testGetToken( string $tokenType ): void {
		$mockApi = $this->getMockApi();
		$mockApi->expects( $this->exactly( 2 ) )
			->method( 'postRequest' )
			->with( $this->isInstanceOf( SimpleRequest::class ) )
			->willReturn( [
				'query' => [
					'tokens' => [
					$tokenType => 'TKN-' . $tokenType,
					]
				]
			] );

		$session = new MediawikiSession( $mockApi );

		// Although we make 2 calls to the method we assert the tokens method about is only called once
		$this->assertEquals( 'TKN-' . $tokenType, $session->getToken() );
		$this->assertEquals( 'TKN-' . $tokenType, $session->getToken() );
		// Then clearing the tokens and calling again should make a second call!
		$session->clearTokens();
		$this->assertEquals( 'TKN-' . $tokenType, $session->getToken() );
	}

	/**
	 * @dataProvider provideTokenTypes
	 */
	public function testGetTokenPre125( string $tokenType ): void {
		$mockApi = $this->getMockApi();
		$mockApi->expects( $this->at( 0 ) )
			->method( 'postRequest' )
			->with( $this->isInstanceOf( SimpleRequest::class ) )
			->willReturn( [
				'warnings' => [
					'query' => [
						'*' => "Unrecognized value for parameter 'meta': tokens",
					]
				]
			] );
		$mockApi->expects( $this->at( 1 ) )
			->method( 'postRequest' )
			->with( $this->isInstanceOf( SimpleRequest::class ) )
			->willReturn( [
				'tokens' => [
					$tokenType => 'TKN-' . $tokenType,
				]
			] );

		$session = new MediawikiSession( $mockApi );

		// Although we make 2 calls to the method we assert the tokens method about is only called once
		$this->assertSame( 'TKN-' . $tokenType, $session->getToken() );
		$this->assertSame( 'TKN-' . $tokenType, $session->getToken() );
	}

	public function provideTokenTypes(): array {
		return [
			[ 'csrf' ],
			[ 'edit' ],
		];
	}

}
