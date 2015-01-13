<?php

namespace Mediawiki\Api;

use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;
use Guzzle\Plugin\Cookie\CookiePlugin;
use Guzzle\Service\Mediawiki\MediawikiApiClient;
use InvalidArgumentException;

class MediawikiApi {

	/**
	 * @var MediawikiApiClient
	 */
	private $client;

	/**
	 * @var bool|string
	 */
	private $isLoggedIn;

	/**
	 * @var MediawikiSession
	 */
	private $session;

	/**
	 * @param string|MediawikiApiClient $client either the url or the api or
	 * @param MediawikiSession|null $session Inject a custom session here
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $client, $session = null ) {
		if( is_string( $client ) ) {
			$client = MediawikiApiClient::factory( array( 'base_url' => $client ) );
		} elseif ( !$client instanceof MediawikiApiClient ) {
			throw new InvalidArgumentException();
		}

		if( $session === null ) {
			$session = new MediawikiSession( $this );
		} elseif ( !$session instanceof MediawikiSession ){
			throw new InvalidArgumentException();
		}

		$this->client = $client;
		$this->client->addSubscriber( new CookiePlugin( new ArrayCookieJar() ) );
		$this->session = $session;
	}

	/**
	 * @since 0.1
	 *
	 * @param string $action
	 * @param array $params
	 *
	 * @return mixed
	 *
	 * @deprecated since 0.2 Please use getRequest with a SimpleRequest object instead
	 */
	public function getAction( $action, $params = array() ) {
		return $this->getRequest( new SimpleRequest( $action, $params ) );
	}

	/**
	 * @since 0.1
	 *
	 * @param string $action
	 * @param array $params
	 *
	 * @return mixed
	 *
	 * @deprecated since 0.2 Please use postRequest with a SimpleRequest object instead
	 */
	public function postAction( $action, $params = array() ) {
		return $this->postRequest( new SimpleRequest( $action, $params ) );
	}

	/**
	 * @since 0.2
	 * @param Request $request
	 * @return mixed
	 */
	public function getRequest( Request $request ) {
		$resultArray = $this->client->getAction( $request->getParams() );
		$this->triggerErrors( $resultArray );
		$this->throwUsageExceptions( $resultArray );
		return $resultArray;
	}

	/**
	 * @since 0.2
	 * @param Request $request
	 * @return mixed
	 */
	public function postRequest( Request $request ) {
		$resultArray = $this->client->postAction( $request->getParams() );
		$this->triggerErrors( $resultArray );
		$this->throwUsageExceptions( $resultArray );
		return $resultArray;
	}

	/**
	 * @param $result
	 */
	private function triggerErrors( $result ) {
		if( is_array( $result ) && array_key_exists( 'warnings', $result ) ) {
			foreach( $result['warnings'] as $module => $warningData ) {
				trigger_error( $module . ': ' . $warningData['*'], E_USER_WARNING );
			}
		}
	}

	/**
	 * @param array $result
	 *
	 * @throws UsageException
	 */
	private function throwUsageExceptions( $result ) {
		if( is_array( $result ) && array_key_exists( 'error', $result ) ) {
			throw new UsageException( $result['error']['code'], $result['error']['info'] );
		}
	}

	/**
	 * @since 0.1
	 *
	 * @return bool|string false or the name of the current user
	 */
	public function isLoggedin() {
		return $this->isLoggedIn;
	}

	/**
	 * @since 0.1
	 *
	 * @param ApiUser $apiUser
	 *
	 * @throws UsageException
	 * @return bool success
	 */
	public function login( ApiUser $apiUser ) {
		$credentials = array(
			'lgname' => $apiUser->getUsername(),
			'lgpassword' => $apiUser->getPassword()
		);

		$result = $this->postRequest( new SimpleRequest( 'login', $credentials ) );
		if ( $result['login']['result'] == "NeedToken" ) {
			$result = $this->postRequest( new SimpleRequest( 'login', array_merge( array( 'lgtoken' => $result['login']['token'] ), $credentials) ) );
		}
		if ( $result['login']['result'] == "Success" ) {
			$this->isLoggedIn = $apiUser->getUsername();
			return true;
		}

		$this->isLoggedIn = false;
		$this->throwLoginUsageException( $result['login']['result'] );
		return false;
	}

	/**
	 * @param string $loginResult
	 *
	 * @throws UsageException
	 */
	private function throwLoginUsageException( $loginResult ) {
		switch( $loginResult ) {
			case 'Illegal';
				throw new UsageException(
					'login-' . $loginResult,
					'You provided an illegal username'
				);
			case 'NotExists';
				throw new UsageException(
					'login-' . $loginResult,
					'The username you provided doesn\'t exist'
				);
			case 'WrongPass';
				throw new UsageException(
					'login-' . $loginResult,
					'The password you provided is incorrect'
				);
			case 'WrongPluginPass';
				throw new UsageException(
					'login-' . $loginResult,
					'An authentication plugin rather than MediaWiki itself rejected the password'
				);
			case 'CreateBlocked';
				throw new UsageException(
					'login-' . $loginResult,
					'The wiki tried to automatically create a new account for you, but your IP address has been blocked from account creation'
				);
			case 'Throttled';
				throw new UsageException(
					'login-' . $loginResult,
					'You\'ve logged in too many times in a short time.'
				);
			case 'Blocked';
				throw new UsageException(
					'login-' . $loginResult,
					'User is blocked'
				);
			case 'NeedToken';
				throw new UsageException(
					'login-' . $loginResult,
					'Either you did not provide the login token or the sessionid cookie.'
				);
			default:
				throw new UsageException(
					'login-' . $loginResult,
					$loginResult
				);
		}
	}

	/**
	 * @since 0.1
	 * @return bool success
	 */
	public function logout() {
		$result = $this->postRequest( new SimpleRequest( 'logout' ) );
		if( $result === array() ) {
			$this->isLoggedIn = false;
			$this->clearTokens();
			return true;
		}
		return false;
	}

	/**
	 * @since 0.1
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function getToken( $type = 'edit' ) {
		return $this->session->getToken( $type );
	}

	/**
	 * @since 0.1
	 * Clears all tokens stored by the api
	 */
	public function clearTokens() {
		$this->session->clearTokens();
	}

}
