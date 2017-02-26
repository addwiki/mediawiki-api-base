<?php

namespace Mediawiki\Api;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use InvalidArgumentException;
use Mediawiki\Api\Guzzle\ClientFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use SimpleXMLElement;

/**
 * Main class for this library
 *
 * @since 0.1
 *
 * @author Addshore
 */
class MediawikiApi implements MediawikiApiInterface, LoggerAwareInterface {

	/**
	 * @var ClientInterface|null Should be accessed through getClient
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
	 * @var string
	 */
	private $apiUrl;

	/**
	 * @since 2.0
	 *
	 * @param string $apiEndpoint e.g. https://en.wikipedia.org/w/api.php
	 *
	 * @return self returns a MediawikiApi instance using $apiEndpoint
	 */
	public static function newFromApiEndpoint( $apiEndpoint ) {
		return new self( $apiEndpoint );
	}

	/**
	 * @since 2.0
	 *
	 * @param string $url e.g. https://en.wikipedia.org OR https://de.wikipedia.org/wiki/Berlin
	 *
	 * @return self returns a MediawikiApi instance using the apiEndpoint provided by the RSD
	 *              file accessible on all Mediawiki pages
	 *
	 * @see https://en.wikipedia.org/wiki/Really_Simple_Discovery
	 */
	public static function newFromPage( $url ) {
		$tempClient = new Client( array( 'headers' => array( 'User-Agent' => 'addwiki-mediawiki-client' ) ) );
		$pageXml = new SimpleXMLElement( $tempClient->get( $url )->getBody() );
		$rsdElement = $pageXml->xpath( 'head/link[@type="application/rsd+xml"][@href]' );
		$rsdXml = new SimpleXMLElement( $tempClient->get( (string) $rsdElement[0]->attributes()['href'] )->getBody() );
		return self::newFromApiEndpoint( (string) $rsdXml->service->apis->api->attributes()->apiLink );
	}

	/**
	 * @param string $apiUrl The API Url
	 * @param ClientInterface|null $client Guzzle Client
	 * @param MediawikiSession|null $session Inject a custom session here
	 */
	public function __construct( $apiUrl, ClientInterface $client = null, MediawikiSession $session = null ) {
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
	 * Get the API URL (the URL to which API requests are sent, usually ending in api.php).
	 * This is useful if you've created this object via MediawikiApi::newFromPage().
	 *
	 * @since 2.3
	 *
	 * @return string The API URL.
	 */
	public function getApiUrl() {
		return $this->apiUrl;
	}

	/**
	 * @return ClientInterface
	 */
	private function getClient() {
		if( $this->client === null ) {
			$clientFactory = new ClientFactory();
			$clientFactory->setLogger( $this->logger );
			$this->client = $clientFactory->getClient();
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
		$promise = $this->getClient()->requestAsync(
			'GET',
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
		$promise = $this->getClient()->requestAsync(
			'POST',
			$this->apiUrl,
			$this->getClientRequestOptions( $request, $this->getPostRequestEncoding( $request ) )
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
		$response = $this->getClient()->request(
			'GET',
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
		$response = $this->getClient()->request(
			'POST',
			$this->apiUrl,
			$this->getClientRequestOptions( $request, $this->getPostRequestEncoding( $request ) )
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
     *
     * @return string
     */
	private function getPostRequestEncoding( Request $request ) {
	    foreach ( $request->getParams() as $value ) {
            if ( is_resource( $value ) ) {
                return 'multipart';
            }
        }
        return 'form_params';
    }

	/**
	 * @param Request $request
	 * @param string $paramsKey either 'query' or 'multipart'
	 *
	 * @throws RequestException
	 *
	 * @return array as needed by ClientInterface::get and ClientInterface::post
	 */
	private function getClientRequestOptions( Request $request, $paramsKey ) {

		$params = array_merge( $request->getParams(), array( 'format' => 'json' ) );
		if ( $paramsKey === 'multipart' ) {
			$params = $this->encodeMultipartParams( $params );
		}

		return array(
			$paramsKey => $params,
			'headers' => array_merge( $this->getDefaultHeaders(), $request->getHeaders() ),
		);
	}

	/**
	 * @param array $params
	 *
	 * @return array
	 */
	private function encodeMultipartParams( $params ) {

		return array_map(
			function ( $name, $value ) {

				return array(
					'name' => $name,
					'contents' => $value,
				);
			},
			array_keys( $params ),
			$params
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
		$credentials = $this->getLoginParams( $apiUser );
		$result = $this->postRequest( new SimpleRequest( 'login', $credentials ) );
		if ( $result['login']['result'] == "NeedToken" ) {
			$result = $this->postRequest( new SimpleRequest( 'login', array_merge( array( 'lgtoken' => $result['login']['token'] ), $credentials) ) );
		}
		if ( $result['login']['result'] == "Success" ) {
			$this->isLoggedIn = $apiUser->getUsername();
			return true;
		}

		$this->isLoggedIn = false;
		$this->logger->log( LogLevel::DEBUG, 'Login failed.', $result );
		$this->throwLoginUsageException( $result );
		return false;
	}

	/**
	 * @param ApiUser $apiUser
	 *
	 * @return string[]
	 */
	private function getLoginParams( ApiUser $apiUser ) {
		$params = array(
			'lgname' => $apiUser->getUsername(),
			'lgpassword' => $apiUser->getPassword(),
		);

		if( !is_null( $apiUser->getDomain() ) ) {
			$params['lgdomain'] = $apiUser->getDomain();
		}
		return $params;
	}

	/**
	 * @param array $result
	 *
	 * @throws UsageException
	 */
	private function throwLoginUsageException( $result ) {
		$loginResult = $result['login']['result'];

		throw new UsageException(
			'login-' . $loginResult,
			array_key_exists( 'reason', $result['login'] )
				? $result['login']['reason']
				: 'No Reason given',
			$result
		);
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
