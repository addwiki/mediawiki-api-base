<?php

namespace Mediawiki\Api;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Event\AbstractTransferEvent;
use GuzzleHttp\Event\SubscriberInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Subscriber\Cookie;
use GuzzleHttp\Subscriber\Retry\RetrySubscriber;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class MediawikiApi implements LoggerAwareInterface {

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
	 * @var LoggerInterface
	 */
	private $logger = null;

	/**
	 * @var SubscriberInterface[] references to the subscribers we have added to the client
	 */
	private $clientSubscribers = array();

	/**
	 * @param string|ClientInterface $client Guzzle Client or api base url
	 * @param MediawikiSession|null $session Inject a custom session here
	 */
	public function __construct( $client, MediawikiSession $session = null ) {
		if( is_string( $client ) ) {
			$client = new Client( array( 'base_url' => $client ) );
		} elseif ( !$client instanceof ClientInterface ) {
			throw new InvalidArgumentException( '$client must either be a string or ClientInterface instance' );
		}

		$this->client = $client;

		if( $session === null ) {
			$this->session = new MediawikiSession( $this );
		}

		$this->attachRetrySubscribersToClient();
		$client->getEmitter()->attach( new Cookie( new CookieJar() ) );
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
		$this->detatchRetrySubscribersFromClient();
		$this->logger = $logger;
		$this->session->setLogger( $logger );
		$this->attachLoggingRetrySubscribersToClient();
	}

	private function detatchRetrySubscribersFromClient() {
		foreach( $this->clientSubscribers as $key => $subscriber ) {
			$this->client->getEmitter()->detach( $subscriber );
			unset( $this->clientSubscribers[$key] );
		}
	}

	private function attachRetrySubscribersToClient() {
		foreach( $this->getRetryFilters() as $filter ) {
			$subscriber = new RetrySubscriber( array( 'filter' => $filter ) );
			$this->clientSubscribers[] = $subscriber;
			$this->client->getEmitter()->attach( $subscriber );
		}
	}

	private function attachLoggingRetrySubscribersToClient() {
		foreach( $this->getRetryFilters() as $filter ) {
			$subscriber = new RetrySubscriber(
				array(
					'filter' => RetrySubscriber::createLoggingDelay(
						$filter,
						$this->logger
					),
				)
			);
			$this->clientSubscribers[] = $subscriber;
			$this->client->getEmitter()->attach( $subscriber );
		}
	}

	/**
	 * @return callable[]
	 */
	private function getRetryFilters() {
		return array(
			RetrySubscriber::createStatusFilter(),
			RetrySubscriber::createConnectFilter(),
			$this->getMediawikiApiErrorRetrySubscriber(),
		);
	}

	/**
	 * @return callable
	 */
	private function getMediawikiApiErrorRetrySubscriber() {
		return function ( $retries, AbstractTransferEvent $event ) {
			$response = $event->getResponse();

			// A response is not always received (e.g., for timeouts)
			if ( !$response ) {
				return false;
			}

			$headers = $response->getHeaders();
			if ( array_key_exists( 'mediawiki-api-error', $headers ) ) {
				return in_array(
					$headers['mediawiki-api-error'],
					array(
						'ratelimited',
						'readonly',
						'internal_api_error_DBQueryError',
					)
				);
			}

			return false;
		};
	}

	/**
	 * Wraps $this->logger->log but only logs when a logger exists
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 */
	private function log( $level, $message, array $context = array() ) {
		if( $this->logger !== null ) {
			$this->logger->log( $level, $message, $context );
		}
	}

	/**
	 * @since 0.2
	 * @param Request $request
	 * @return mixed
	 */
	public function getRequest( Request $request ) {
		$response = $this->getGuzzleGetResponse( $request );
		$resultArray = $response->json();

		$this->logWarnings( $resultArray );
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

		$this->logWarnings( $resultArray );
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
				$this->log( LogLevel::WARNING, $module . ': ' . $warningData['*'], array( 'data' => $warningData ) );
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
		$this->log( LogLevel::DEBUG, 'Logging in' );

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
		$this->log( LogLevel::DEBUG, 'Logging out' );
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
