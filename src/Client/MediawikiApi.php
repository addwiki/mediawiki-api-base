<?php

namespace Addwiki\Mediawiki\Api\Client;

use Addwiki\Mediawiki\Api\Client\Auth\AuthMethod;
use Addwiki\Mediawiki\Api\Client\Auth\NoAuth;
use Addwiki\Mediawiki\Api\Client\Auth\UserAndPassword;
use Addwiki\Mediawiki\Api\Client\Auth\UserAndPasswordWithDomain;
use Addwiki\Mediawiki\Api\Guzzle\ClientFactory;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use SimpleXMLElement;

class MediawikiApi implements MediawikiApiInterface, LoggerAwareInterface {

	private string $apiUrl;
	private AuthMethod $auth;
	/**
	 * Should be accessed through getClient
	 * @var ClientInterface|null
	 */
	private ?ClientInterface $client = null;
	private MediawikiSession $session;

	private ?AuthMethod $loggedInAuthMethod = null;
	private ?string $version = null;
	private LoggerInterface $logger;

	/**
	 * @param string $apiEndpoint e.g. https://en.wikipedia.org/w/api.php
	 *
	 * @return self returns a MediawikiApi instance using $apiEndpoint
	 */
	public static function newFromApiEndpoint( string $apiEndpoint ): MediawikiApi {
		return new self( $apiEndpoint );
	}

	/**
	 * Create a new MediawikiApi object from a URL to any page in a MediaWiki website.
	 *
	 * @see https://en.wikipedia.org/wiki/Really_Simple_Discovery
	 *
	 * @param string $url e.g. https://en.wikipedia.org OR https://de.wikipedia.org/wiki/Berlin
	 * @return self returns a MediawikiApi instance using the apiEndpoint provided by the RSD
	 *              file accessible on all Mediawiki pages
	 * @throws RsdException If the RSD URL could not be found in the page's HTML.
	 */
	public static function newFromPage( string $url ): MediawikiApi {
		// Set up HTTP client and HTML document.
		$tempClient = new Client( [ 'headers' => [ 'User-Agent' => 'addwiki-mediawiki-client' ] ] );
		$pageHtml = $tempClient->get( $url )->getBody();
		$pageDoc = new DOMDocument();

		// Try to load the HTML (turn off errors temporarily; most don't matter, and if they do get
		// in the way of finding the API URL, will be reported in the RsdException below).
		$internalErrors = libxml_use_internal_errors( true );
		$pageDoc->loadHTML( $pageHtml );
		$libXmlErrors = libxml_get_errors();
		libxml_use_internal_errors( $internalErrors );

		// Extract the RSD link.
		$xpath = 'head/link[@type="application/rsd+xml"][@href]';
		$link = ( new DOMXpath( $pageDoc ) )->query( $xpath );
		if ( $link->length === 0 ) {
			// Format libxml errors for display.
			$libXmlErrorStr = array_reduce( $libXmlErrors, fn( $prevErr, $err ) => $prevErr . ', ' . $err->message . ' (line ' . $err->line . ')' );
			if ( $libXmlErrorStr ) {
				$libXmlErrorStr = sprintf( 'In addition, libxml had the following errors: %s', $libXmlErrorStr );
			}
			throw new RsdException( sprintf( 'Unable to find RSD URL in page: %s %s', $url, $libXmlErrorStr ) );
		}
		$linkItem = $link->item( 0 );
		if ( ( $linkItem->attributes ) === null ) {
			throw new RsdException( 'Unexpected RSD fetch error' );
		}
		/** @psalm-suppress NullReference */
		$rsdUrl = $linkItem->attributes->getNamedItem( 'href' )->nodeValue;

		// Then get the RSD XML, and return the API link.
		$rsdXml = new SimpleXMLElement( $tempClient->get( $rsdUrl )->getBody() );
		return self::newFromApiEndpoint( (string)$rsdXml->service->apis->api->attributes()->apiLink );
	}

	/**
	 * @param string $apiUrl The API Url
	 * @param AuthMethod|null $auth Auth method to use. null for NoAuth
	 * @param ClientInterface|null $client Guzzle Client
	 * @param MediawikiSession|null $session Inject a custom session here
	 */
	public function __construct(
		string $apiUrl,
		AuthMethod $auth = null,
		ClientInterface $client = null,
		MediawikiSession $session = null
		) {
		if ( $auth === null ) {
			$auth = new NoAuth();
		}
		if ( $session === null ) {
			$session = new MediawikiSession( $this );
		}

		$this->apiUrl = $apiUrl;
		$this->auth = $auth;
		$this->client = $client;
		$this->session = $session;

		$this->logger = new NullLogger();
	}

	/**
	 * Get the API URL (the URL to which API requests are sent, usually ending in api.php).
	 * This is useful if you've created this object via MediawikiApi::newFromPage().
	 *
	 * @return string The API URL.
	 */
	public function getApiUrl(): string {
		return $this->apiUrl;
	}

	private function getClient(): ClientInterface {
		if ( !$this->client instanceof ClientInterface ) {
			$clientFactory = new ClientFactory();
			$clientFactory->setLogger( $this->logger );
			$this->client = $clientFactory->getClient();
		}
		return $this->client;
	}

	/**
	 * Sets a logger instance on the object
	 *
	 * @param LoggerInterface $logger The new Logger object.
	 *
	 * @return null
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
		$this->session->setLogger( $logger );
	}

	/**
	 * @param Request $request The GET request to send.
	 *
	 * @return PromiseInterface
	 *         Normally promising an array, though can be mixed (json_decode result)
	 *         Can throw UsageExceptions or RejectionExceptions
	 */
	public function getRequestAsync( Request $request ): PromiseInterface {
		$request->setParam( 'format', 'json' );
		$request = $this->auth->preRequestAuth( 'GET', $request, $this );
		$promise = $this->getClient()->requestAsync(
			'GET',
			$this->apiUrl,
			$this->getClientRequestOptions( $request, 'query' )
		);

		return $promise->then( fn( ResponseInterface $response ) => call_user_func( fn( ResponseInterface $response ) => $this->decodeResponse( $response ), $response ) );
	}

	/**
	 * @param Request $request The POST request to send.
	 *
	 * @return PromiseInterface
	 *         Normally promising an array, though can be mixed (json_decode result)
	 *         Can throw UsageExceptions or RejectionExceptions
	 */
	public function postRequestAsync( Request $request ): PromiseInterface {
		$request->setParam( 'format', 'json' );
		$request = $this->auth->preRequestAuth( 'POST', $request, $this );
		$promise = $this->getClient()->requestAsync(
			'POST',
			$this->apiUrl,
			$this->getClientRequestOptions( $request, $request->getPostRequestEncoding() )
		);

		return $promise->then( fn( ResponseInterface $response ) => call_user_func( fn( ResponseInterface $response ) => $this->decodeResponse( $response ), $response ) );
	}

	/**
	 * @param Request $request The GET request to send.
	 *
	 * @return mixed Normally an array
	 */
	public function getRequest( Request $request ) {
		$request->setParam( 'format', 'json' );
		$request = $this->auth->preRequestAuth( 'GET', $request, $this );
		$response = $this->getClient()->request(
			'GET',
			$this->apiUrl,
			$this->getClientRequestOptions( $request, 'query' )
		);

		return $this->decodeResponse( $response );
	}

	/**
	 * @param Request $request The POST request to send.
	 *
	 * @return mixed Normally an array
	 */
	public function postRequest( Request $request ) {
		$request->setParam( 'format', 'json' );
		$request = $this->auth->preRequestAuth( 'POST', $request, $this );
		$response = $this->getClient()->request(
			'POST',
			$this->apiUrl,
			$this->getClientRequestOptions( $request, $request->getPostRequestEncoding() )
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
	 * @param string $paramsKey either 'query' or 'multipart'
	 *
	 * @throws RequestException
	 * @return array as needed by ClientInterface::get and ClientInterface::post
	 */
	private function getClientRequestOptions( Request $request, string $paramsKey ): array {
		$params = $request->getParams();
		if ( $paramsKey === 'multipart' ) {
			$params = $this->encodeMultipartParams( $request, $params );
		}

		return [
			$paramsKey => $params,
			'headers' => array_merge( $this->getDefaultHeaders(), $request->getHeaders() ),
		];
	}

	/**
	 * Turn the normal key-value array of request parameters into a multipart array where each
	 * parameter is a new array with a 'name' and 'contents' elements (and optionally more, if the
	 * request is a MultipartRequest).
	 *
	 * @param Request $request The request to which the parameters belong.
	 * @param string[] $params The existing parameters. Not the same as $request->getParams().
	 *
	 * @return array <int mixed[]>
	 */
	private function encodeMultipartParams( Request $request, array $params ): array {
		// See if there are any multipart parameters in this request.
		$multipartParams = ( $request instanceof MultipartRequest )
			? $request->getMultipartParams()
			: [];
		return array_map(
			function ( $name, $value ) use ( $multipartParams ): array {
				$partParams = [
					'name' => $name,
					'contents' => $value,
				];
				if ( isset( $multipartParams[ $name ] ) ) {
					// If extra parameters have been set for this part, use them.
					$partParams = array_merge( $multipartParams[ $name ], $partParams );
				}
				return $partParams;
			},
			array_keys( $params ),
			$params
		);
	}

	private function getDefaultHeaders(): array {
		return [
			'User-Agent' => $this->getUserAgent(),
		];
	}

	private function getUserAgent(): string {
		if ( $this->isLoggedIn() ) {
			if ( $this->loggedInAuthMethod instanceof UserAndPassword || $this->loggedInAuthMethod instanceof UserAndPasswordWithDomain ) {
				return 'addwiki-mediawiki-client/' . $this->loggedInAuthMethod->getUsername();
			}
			return 'addwiki-mediawiki-client/' . 'SomeUnknownUser?';
		}
		return 'addwiki-mediawiki-client';
	}

	private function logWarnings( $result ): void {
		if ( is_array( $result ) ) {
			// Let's see if there is 'warnings' key on the first level of the array...
			if ( $this->logWarning( $result ) ) {
				return;
			}

			// ...if no then go one level deeper and check there for it.
			foreach ( $result as $value ) {
				if ( !is_array( $value ) ) {
					continue;
				}

				$this->logWarning( $value );
			}
		}
	}

	/**
	 * @param array $array Array response to look for warning in.
	 *
	 * @return bool Whether any warning has been logged or not.
	 */
	protected function logWarning( array $array ): bool {
		$found = false;

		if ( !array_key_exists( 'warnings', $array ) ) {
			return false;
		}

		foreach ( $array['warnings'] as $module => $warningData ) {
			// Accommodate both formatversion=2 and old-style API results
			$logPrefix = $module . ': ';
			if ( isset( $warningData['*'] ) ) {
				$this->logger->warning( $logPrefix . $warningData['*'], [ 'data' => $warningData ] );
			} elseif ( isset( $warningData['warnings'] ) ) {
				$this->logger->warning( $logPrefix . $warningData['warnings'], [ 'data' => $warningData ] );
			} else {
				$this->logger->warning( $logPrefix, [ 'data' => $warningData ] );
			}

			$found = true;
		}

		return $found;
	}

	/**
	 * @throws UsageException
	 */
	private function throwUsageExceptions( $result ): void {
		if ( is_array( $result ) && array_key_exists( 'error', $result ) ) {
			throw new UsageException(
				$result['error']['code'],
				$result['error']['info'],
				$result
			);
		}
	}

	public function isLoggedIn(): bool {
		return $this->loggedInAuthMethod instanceof AuthMethod;
	}

	/**
	 * @deprecated in 3.0, create a MediaWikiApi with an AuthMethod instead.
	 */
	public function login( ApiUser $oldApiUser ): bool {
		// If login is called, replace
		if ( $this->auth instanceof NoAuth ) {
			$this->auth = $oldApiUser;
		} elseif ( !$this->auth->equals( $oldApiUser ) ) {
			throw new LogicException(
				'You are calling the login method back compat layer, but are already providing an AuthMethod to the API class...'
			);
		}
		$this->auth->preRequestAuth( 'NULL', new SimpleRequest( 'dummyrequest' ), $this );
		$this->loggedInAuthMethod = $this->auth;
		return true;
	}

	public function logout(): bool {
		$this->logger->log( LogLevel::DEBUG, 'Logging out' );
		$result = $this->postRequest( new SimpleRequest( 'logout', [
			'token' => $this->getToken()
		] ) );
		if ( $result === [] ) {
			$this->loggedInAuthMethod = null;
			$this->clearTokens();
			return true;
		}
		return false;
	}

	public function getToken( $type = 'csrf' ): string {
		return $this->session->getToken( $type );
	}

	/**
	 * Clear all tokens stored by the API.
	 */
	public function clearTokens() {
		$this->session->clearTokens();
	}

	public function getVersion(): string {
		if ( $this->version === null ) {
			$result = $this->getRequest( new SimpleRequest( 'query', [
				'meta' => 'siteinfo',
				'continue' => '',
			] ) );
			preg_match(
				'#\d+(?:\.\d+)+#',
				$result['query']['general']['generator'],
				$versionParts
			);
			$this->version = $versionParts[0];
		}
		return $this->version;
	}

}
