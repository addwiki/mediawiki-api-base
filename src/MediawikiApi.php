<?php

namespace Mediawiki\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use InvalidArgumentException;
use Mediawiki\Api\Guzzle\MiddlewareFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

/**
 * Main class for this library
 *
 * @since 0.1
 *
 * @author Addshore
 */
class MediawikiApi implements LoggerAwareInterface {

	/**
	 * @var Client|null Should be accessed through getClient
	 */
	private $client = null;

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
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @since 2.0.0
	 *
	 * @param string $apiEndpoint e.g. https://en.wikipedia.org/w/api.php
	 *
	 * @return self returns a MediawikiApi instance using $apiEndpoint
	 */
	public static function newFromApiEndpoint( $apiEndpoint ) {
		return new self( $apiEndpoint );
	}

	/**
	 * @since 2.0.0
	 *
	 * @param string $url e.g. https://en.wikipedia.org OR https://de.wikipedia.org/wiki/Berlin
	 *
	 * @return self returns a MediawikiApi instance using the apiEndpoint provided by the RSD
	 *              file accessible on all Mediawiki pages
	 *
	 * @see https://en.wikipedia.org/wiki/Really_Simple_Discovery
	 */
	public static function newFromPage( $url ) {
		//TODO implement me
	}

	/**
	 * @access private
	 *
	 * @param string $apiUrl The API Url
	 * @param Client|null $client Guzzle Client
	 * @param MediawikiSession|null $session Inject a custom session here
	 */
	public function __construct( $apiUrl, Client $client = null, MediawikiSession $session = null ) {
		if( !is_string( $apiUrl ) ) {
			throw new InvalidArgumentException( '$apiUrl must be a string' );
		}
		if( $session === null ) {
			$session = new MediawikiSession( $this );
		}

		$this->apiUrl = $apiUrl;
		$this->client = $client;
		$this->session = $session;

		$this->logger = new NullLogger();
	}

	/**
	 * @return Client
	 */
	private function getClient() {
		if( $this->client === null ) {
			$middlewareFactory = new MiddlewareFactory();
			$middlewareFactory->setLogger( $this->logger );

			$handlerStack = HandlerStack::create( new CurlHandler() );
			$handlerStack->push( $middlewareFactory->retry() );

			$this->client = new Client( array(
				'cookies' => true,
				'handler' => $handlerStack,
			) );
		}
		return $this->client;
	}

	/**
	 * Sets a logger instance on the object
	 *
	 * @since 1.1
	 *
	 * @param LoggerInterface $logger
	 *
	 * @return null
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
		$this->session->setLogger( $logger );
	}

	/**
	 * @since 2.0
	 *
	 * @param Request $request
	 *
	 * @return PromiseInterface
	 *         Normally promising an array, though can be mixed (json_decode result)
	 *         Can throw UsageExceptions or RejectionExceptions
	 */
	public function getRequestAsync( Request $request ) {
		$promise = $this->getClient()->getAsync(
			$this->apiUrl,
			$this->getClientRequestOptions( $request, 'query' )
		);

		return $promise->then( function( ResponseInterface $response ) {
			return call_user_func( array( $this, 'decodeResponse' ), $response );
		} );
	}

	/**
	 * @since 2.0
	 *
	 * @param Request $request
	 *
	 * @return PromiseInterface
	 *         Normally promising an array, though can be mixed (json_decode result)
	 *         Can throw UsageExceptions or RejectionExceptions
	 */
	public function postRequestAsync( Request $request ) {
		$promise = $this->getClient()->postAsync(
			$this->apiUrl,
			$this->getClientRequestOptions( $request, 'form_params' )
		);

		return $promise->then( function( ResponseInterface $response ) {
			return call_user_func( array( $this, 'decodeResponse' ), $response );
		} );
	}

	/**
	 * @since 0.2
	 *
	 * @param Request $request
	 *
	 * @return mixed Normally an array
	 */
	public function getRequest( Request $request ) {
		$response = $this->getClient()->get(
			$this->apiUrl,
			$this->getClientRequestOptions( $request, 'query' )
		);

		return $this->decodeResponse( $response );
	}

	/**
	 * @since 0.2
	 *
	 * @param Request $request
	 *
	 * @return mixed Normally an array
	 */
	public function postRequest( Request $request ) {
		$response = $this->getClient()->post(
			$this->apiUrl,
			$this->getClientRequestOptions( $request, 'form_params' )
		);

		return $this->decodeResponse( $response );
	}

	/**
	 * @param ResponseInterface $response
	 *
	 * @return mixed
	 * @throws UsageException
	 */
	private function decodeResponse( ResponseInterface $response ) {
		$resultArray = json_decode( $response->getBody(), true );

		$this->logWarnings( $resultArray );
		$this->throwUsageExceptions( $resultArray );

		return $resultArray;
	}

	/**
	 * @param Request $request
	 * @param string $paramsKey either 'query' or 'form_params'
	 *
	 * @throws RequestException
	 *
	 * @return array as needed by ClientInterface::get and ClientInterface::post
	 */
	private function getClientRequestOptions( Request $request, $paramsKey ) {
		return array(
			$paramsKey => array_merge( $request->getParams(), array( 'format' => 'json' ) ),
			'headers' => array_merge( $this->getDefaultHeaders(), $request->getHeaders() ),
		);
	}

	/**
	 * @return array
	 */
	private function getDefaultHeaders() {
		return array(
			'User-Agent' => $this->getUserAgent(),
		);
	}

	private function getUserAgent() {
		$loggedIn = $this->isLoggedin();
		if( $loggedIn ) {
			return 'addwiki-mediawiki-client/' . $loggedIn;
		}
		return 'addwiki-mediawiki-client';
	}

	/**
	 * @param $result
	 */
	private function logWarnings( $result ) {
		if( is_array( $result ) && array_key_exists( 'warnings', $result ) ) {
			foreach( $result['warnings'] as $module => $warningData ) {
				$this->logger->log( LogLevel::WARNING, $module . ': ' . $warningData['*'], array( 'data' => $warningData ) );
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
		$this->logger->log( LogLevel::DEBUG, 'Logging in' );

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
	 *
	 * @return bool success
	 */
	public function logout() {
		$this->logger->log( LogLevel::DEBUG, 'Logging out' );
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
	 *
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
