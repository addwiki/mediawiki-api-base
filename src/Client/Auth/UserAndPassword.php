<?php

namespace Addwiki\Mediawiki\Api\Client\Auth;

use Addwiki\Mediawiki\Api\Client\Action\ActionApi;
use Addwiki\Mediawiki\Api\Client\Action\Exception\UsageException;
use Addwiki\Mediawiki\Api\Client\Action\Request\ActionRequest;
use Addwiki\Mediawiki\Api\Client\Request\Request;
use Addwiki\Mediawiki\Api\Client\Request\Requester;
use InvalidArgumentException;

/**
 * For use with plain MediaWiki logins
 */
class UserAndPassword implements AuthMethod {

	private string $password;
	private string $username;
	private bool $isLoggedIn = false;

	public function __construct( string $username, string $password ) {
		if ( empty( $username ) || empty( $password ) ) {
			throw new InvalidArgumentException( 'Username and Password are not allowed to be empty' );
		}
		$this->username = $username;
		$this->password = $password;
	}

	public function getUsername(): string {
		return $this->username;
	}

	public function getPassword(): string {
		return $this->password;
	}

	public function equals( UserAndPassword $other ): bool {
		return $this->getUsername() === $other->getUsername()
			&& $this->getPassword() === $other->getPassword();
	}

	public function preRequestAuth( Request $request, Requester $requester ): Request {
		if ( !$requester instanceof ActionApi ) {
			// TODO remove / alter this when doing REST
			die( 'Only works with ActionApi for now' );
		}

		// Do nothing if we are already logged in
		if ( $this->isLoggedIn ) {
			// Verify that the user is logged in if set to user, not logged in if set to anon, or has the bot user right if bot.
			$request->setParam( 'assert', 'user' );
			return $request;
		}

		// Do not try to login if thi is a self call (we are logging in)
		if ( array_key_exists( 'action', $request->getParams() ) && $request->getParams()['action'] === 'login' ) {
			return $request;
		}

		$loginParams = [
			'lgname' => $this->getUsername(),
			'lgpassword' => $this->getPassword(),
		];

		// First Request
		$result = $requester->request( ActionRequest::simplePost( 'login', $loginParams ) );
		if ( $result['login']['result'] == 'NeedToken' ) {
			$params = array_merge( [ 'lgtoken' => $result['login']['token'] ], $loginParams );
			// Second Request
			$result = $requester->request( ActionRequest::simplePost( 'login', $params ) );
		}

		// Check for success
		if ( $result['login']['result'] == 'Success' ) {
			$this->isLoggedIn = true;
			return $request;
		}

		$this->isLoggedIn = false;

		$this->throwLoginUsageException( $result );

		return $request;
	}

	protected function additionalParamsForPreRequestAuthCall(): array {
		return [];
	}

	/**
	 * @throws UsageException
	 */
	private function throwLoginUsageException( array $result ): void {
		$loginResult = $result['login']['result'];

		// TODO use an Auth exception instead? (to make it easier to catch etc?)
		throw new UsageException(
			'login-' . $loginResult,
			array_key_exists( 'reason', $result['login'] )
				? $result['login']['reason']
				: 'No Reason given',
			$result
		);
	}

	public function identifierForUserAgent(): ?string {
		return 'user/' . $this->getUsername();
	}

}
