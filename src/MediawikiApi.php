<?php

namespace Mediawiki\Api;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Subscriber\Cookie;
use GuzzleHttp\Subscriber\Retry\RetrySubscriber;
use InvalidArgumentException;

class MediawikiApi {

	/**
	 * @var ClientInterface
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
	 * @var string
	 */
	private $version;

	/**
	 * @param string|ClientInterface $client Guzzle Client or api base url
	 * @param MediawikiSession|null $session Inject a custom session here
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $client, $session = null ) {
		if( is_string( $client ) ) {
			$client = new Client( array(
				'base_url' => $client,
				'defaults' => array(
					'headers' => array( 'User-Agent' => 'addwiki-guzzle-mediawiki-client' ),
				)
			) );
		} elseif ( !$client instanceof ClientInterface ) {
			throw new InvalidArgumentException( '$client must either be a string or ClientInterface instance' );
		}

		if( $session === null ) {
			$session = new MediawikiSession( $this );
		} elseif ( !$session instanceof MediawikiSession ){
			throw new InvalidArgumentException( '$session must either me null or MediawikiSession instance' );
		}

		$client->getEmitter()->attach( new Cookie( new CookieJar() ) );
		$client->getEmitter()->attach( new RetrySubscriber([ 'filter' => RetrySubscriber::createStatusFilter() ]) );

		$this->client = $client;
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
	 * @deprecated since 0.2 Please use getRequest
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
	 * @deprecated since 0.2 Please use postRequest
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
		$response = $this->getGuzzleGetResponse( $request );
		$resultArray = $response->json();

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
		$response = $this->getGuzzlePostResponse( $request );
		$resultArray = $response->json();

		$this->triggerErrors( $resultArray );
		$this->throwUsageExceptions( $resultArray );

		return $resultArray;
	}

	/**
	 * @param Request $request
	 *
	 * @return ResponseInterface
	 */
	private function getGuzzleGetResponse( Request $request ) {
		return $this->client->get(
			null,// Default to the base_url already set in the client
			$this->getGuzzleClientRequestOptions( $request, 'query' )
		);
	}

	/**
	 * @param Request $request
	 *
	 * @throws RequestException
	 *
	 * @return ResponseInterface
	 */
	private function getGuzzlePostResponse( Request $request ) {
		return $this->client->post(
			null,// Default to the base_url already set in the client
			$this->getGuzzleClientRequestOptions( $request, 'body' )
		);
	}

	/**
	 * @param Request $request
	 * @param string $bodyOrQuery
	 *
	 * @throws RequestException
	 *
	 * @return array as needed by ClientInterface::get and ClientInterface::post
	 */
	private function getGuzzleClientRequestOptions( Request $request, $bodyOrQuery ) {
		return array(
			$bodyOrQuery => array_merge( $request->getParams(), array( 'format' => 'json' ) ),
			'headers' => $request->getHeaders(),
		);
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
			throw new UsageException(
				$result['error']['code'],
				$result['error']['info'],
				$result
			);
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
			'lgpassword' => $apiUser->getPassword(),
		);

		if( !is_null( $apiUser->getDomain() ) ) {
			$credentials['lgdomain'] = $apiUser->getDomain();
		}

		$result = $this->postRequest( new SimpleRequest( 'login', $credentials ) );
		if ( $result['login']['result'] == "NeedToken" ) {
			$result = $this->postRequest( new SimpleRequest( 'login', array_merge( array( 'lgtoken' => $result['login']['token'] ), $credentials) ) );
		}
		if ( $result['login']['result'] == "Success" ) {
			$this->isLoggedIn = $apiUser->getUsername();
			return true;
		}

		$this->isLoggedIn = false;
		$this->throwLoginUsageException( $result );
		return false;
	}

	/**
	 * @param array $result
	 *
	 * @throws UsageException
	 */
	private function throwLoginUsageException( $result ) {
		$loginResult = $result['login']['result'];
		switch( $loginResult ) {
			case 'Illegal';
				throw new UsageException(
					'login-' . $loginResult,
					'You provided an illegal username',
					$result
				);
			case 'NotExists';
				throw new UsageException(
					'login-' . $loginResult,
					'The username you provided doesn\'t exist',
					$result
				);
			case 'WrongPass';
				throw new UsageException(
					'login-' . $loginResult,
					'The password you provided is incorrect',
					$result
				);
			case 'WrongPluginPass';
				throw new UsageException(
					'login-' . $loginResult,
					'An authentication plugin rather than MediaWiki itself rejected the password',
					$result
				);
			case 'CreateBlocked';
				throw new UsageException(
					'login-' . $loginResult,
					'The wiki tried to automatically create a new account for you, but your IP address has been blocked from account creation',
					$result
				);
			case 'Throttled';
				throw new UsageException(
					'login-' . $loginResult,
					'You\'ve logged in too many times in a short time.',
					$result
				);
			case 'Blocked';
				throw new UsageException(
					'login-' . $loginResult,
					'User is blocked',
					$result
				);
			case 'NeedToken';
				throw new UsageException(
					'login-' . $loginResult,
					'Either you did not provide the login token or the sessionid cookie.',
					$result
				);
			default:
				throw new UsageException(
					'login-' . $loginResult,
					$loginResult,
					$result
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
	public function getToken( $type = 'csrf' ) {
		return $this->session->getToken( $type );
	}

	/**
	 * @since 0.1
	 * Clears all tokens stored by the api
	 */
	public function clearTokens() {
		$this->session->clearTokens();
	}

	/**
	 * @return string
	 */
	public function getVersion(){
		if( !isset( $this->version ) ) {
			$result = $this->getRequest( new SimpleRequest( 'query', array(
				'meta' => 'siteinfo',
				'continue' => '',
			) ) );
			preg_match(
				'/\d+(?:\.\d+)+/',
				$result['query']['general']['generator'],
				$versionParts
			);
			$this->version = $versionParts[0];
		}
		return $this->version;
	}

}
