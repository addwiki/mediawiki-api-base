<?php

namespace Addwiki\Mediawiki\Api\Tests\Unit\Client\Auth;

use Addwiki\Mediawiki\Api\Client\Action\ActionApi;
use Addwiki\Mediawiki\Api\Client\Action\Exception\UsageException;
use Addwiki\Mediawiki\Api\Client\Action\Request\ActionRequest;
use Addwiki\Mediawiki\Api\Client\Auth\UserAndPassword;
use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * @covers Mediawiki\Api\Client\Auth\UserAndPassword
 */
class UserAndPasswordTest extends TestCase {

	/**
	 * @dataProvider provideValidConstruction
	 */
	public function testValidConstruction( string $user, string $pass ): void {
		$userAndPassword = new UserAndPassword( $user, $pass );
		$this->assertSame( $user, $userAndPassword->getUsername() );
		$this->assertSame( $pass, $userAndPassword->getPassword() );
	}

	public function provideValidConstruction(): array {
		return [
			[ 'user', 'pass' ],
		];
	}

	/**
	 * @dataProvider provideInvalidConstruction
	 */
	public function testInvalidConstruction( string $user, string $pass, ?string $domain = null ): void {
		$this->expectException( InvalidArgumentException::class );
		 new UserAndPassword( $user, $pass, $domain );
	}

	public function provideInvalidConstruction(): array {
		return [
			[ 'user', '' ],
			[ '', 'pass' ],
			[ '', '' ],
		];
	}

	/**
	 * @dataProvider provideTestEquals
	 */
	public function testEquals( UserAndPassword $user1, UserAndPassword $user2, bool $shouldEqual ): void {
		$this->assertSame( $shouldEqual, $user1->equals( $user2 ) );
		$this->assertSame( $shouldEqual, $user2->equals( $user1 ) );
	}

	public function provideTestEquals(): array {
		return [
			[ new UserAndPassword( 'usera', 'passa' ), new UserAndPassword( 'usera', 'passa' ), true ],
			[ new UserAndPassword( 'DIFF', 'passa' ), new UserAndPassword( 'usera', 'passa' ), false ],
			[ new UserAndPassword( 'usera', 'DIFF' ), new UserAndPassword( 'usera', 'passa' ), false ],
			[ new UserAndPassword( 'usera', 'passa' ), new UserAndPassword( 'DIFF', 'passa' ), false ],
			[ new UserAndPassword( 'usera', 'passa' ), new UserAndPassword( 'usera', 'DIFF' ), false ],
		];
	}

	/**
	 * @return MockObject&ResponseInterface
	 */
	private function getMockResponse( $responseValue ) {
		$mock = $this->createMock( ResponseInterface::class );
		$mock
			->method( 'getBody' )
			->willReturn( \GuzzleHttp\Psr7\Utils::streamFor( json_encode( $responseValue ) ) );
		return $mock;
	}

	/**
	 * @return array <int|string mixed[]>
	 */
	private function getExpectedRequestOpts( $params, $paramsLocation ): array {
		return [
			$paramsLocation => array_merge( $params, [ 'format' => 'json' ] ),
			'headers' => [ 'User-Agent' => 'addwiki-mediawiki-client/U1' ],
		];
	}

	public function testGoodLoginSequence(): void {
		$eq1 = [
			'action' => 'login',
			'lgname' => 'U1',
			'lgpassword' => 'P1',
		];
		$params = array_merge( $eq1, [ 'lgtoken' => 'IamLoginTK' ] );

		$client = $this->createMock( ClientInterface::class );
		$client->expects( $this->exactly( 2 ) )
			->method( 'request' )
			->withConsecutive(
				[ 'POST', null, $this->getExpectedRequestOpts( $eq1, 'form_params' ) ],
				[ 'POST', null, $this->getExpectedRequestOpts( $params, 'form_params' ) ],
			)
			->willReturnOnConsecutiveCalls(
				$this->getMockResponse( [ 'login' => [
					'result' => 'NeedToken',
					'token' => 'IamLoginTK',
				] ] ),
				$this->getMockResponse( [ 'login' => [ 'result' => 'Success' ] ] )
			);

		$auth = new UserAndPassword( 'U1', 'P1' );
		$api = new ActionApi( '', $auth, $client );
		$auth->preRequestAuth( ActionRequest::simpleGet( 'dummyrequest' ), $api );
	}

	public function testBadLoginSequence(): void {
		$client = $this->createMock( ClientInterface::class );
		$eq1 = [
			'action' => 'login',
			'lgname' => 'U1',
			'lgpassword' => 'P1',
		];
		$params = array_merge( $eq1, [ 'lgtoken' => 'IamLoginTK' ] );

		$client->expects( $this->exactly( 2 ) )
			->method( 'request' )
			->withConsecutive(
				[ 'POST', null, $this->getExpectedRequestOpts( $eq1, 'form_params' ) ],
				[ 'POST', null, $this->getExpectedRequestOpts( $params, 'form_params' ) ],
			)
			->willReturnOnConsecutiveCalls(
				$this->getMockResponse( [ 'login' => [
					'result' => 'NeedToken',
					'token' => 'IamLoginTK',
				] ] ),
				$this->getMockResponse( [ 'login' => [ 'result' => 'BADTOKENorsmthin' ] ] )
			);

		$auth = new UserAndPassword( 'U1', 'P1' );
		$api = new ActionApi( '', $auth, $client );
		$this->expectException( UsageException::class );
		$auth->preRequestAuth( ActionRequest::simpleGet( 'dummyrequest' ), $api );
	}

}
