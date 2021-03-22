<?php

namespace Addwiki\Mediawiki\Api\Client\Action;

use Addwiki\Mediawiki\Api\Client\Action\Exception\UsageException;
use Addwiki\Mediawiki\Api\Client\Action\Request\ActionRequest;
use Addwiki\Mediawiki\Api\Client\Auth\AuthMethod;
use Addwiki\Mediawiki\Api\Client\Auth\NoAuth;
use Addwiki\Mediawiki\Api\Client\Auth\UserAndPassword;
use Addwiki\Mediawiki\Api\Client\Auth\UserAndPasswordWithDomain;
use Addwiki\Mediawiki\Api\Client\Request\Request;
use Addwiki\Mediawiki\Api\Client\Request\Requester;
use Addwiki\Mediawiki\Api\Guzzle\ClientFactory;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ActionApi implements Requester, LoggerAwareInterface {

	private string $apiUrl;
	private AuthMethod $auth;
	/**
	 * Should be accessed through getClient
	 * @var ClientInterface|null
	 */
	private ?ClientInterface $client = null;
	private Tokens $tokens;

	private ?string $version = null;
	private LoggerInterface $logger;

	/**
	 * @param string $apiUrl The API Url
	 * @param AuthMethod|null $auth Auth method to use. null for NoAuth
	 * @param ClientInterface|null $client Guzzle Client
	 * @param Tokens|null $tokens Inject a custom tokens object here
	 */
	public function __construct(
		string $apiUrl,
		AuthMethod $auth = null,
		ClientInterface $client = null,
		Tokens $tokens = null
		) {
		if ( $auth === null ) {
			$auth = new NoAuth();
		}
		if ( $tokens === null ) {
			$tokens = new Tokens( $this );
		}

		$this->apiUrl = $apiUrl;
		$this->auth = $auth;
		$this->client = $client;
		$this->tokens = $tokens;

		$this->logger = new NullLogger();
	}

	/**
	 * Get the API URL (the URL to which API requests are sent, usually ending in api.php).
	 * This is useful if you have this object without knowing the actual api URL
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
		$this->tokens->setLogger( $logger );
	}

	/**
	 * @param Request $request The request to send.
	 *
	 *         Normally promising an array, though can be mixed (json_decode result)
	 *         Can throw UsageExceptions or RejectionExceptions
	 */
	public function requestAsync( Request $request ): PromiseInterface {
		$request->setParam( 'format', 'json' );
		$request = $this->auth->preRequestAuth( $request, $this );

		$promise = $this->getClient()->requestAsync(
			$request->getMethod(),
			$this->apiUrl,
			$this->getClientRequestOptions( $request, $request->getParameterEncoding() )
		);

		return $promise->then( fn( ResponseInterface $response ) => call_user_func( fn( ResponseInterface $response ) => $this->decodeResponse( $response ), $response ) );
	}

	/**
	 * @param Request $request The request to send.
	 *
	 * @return mixed Normally an array
	 */
	public function request( Request $request ) {
		$request->setParam( 'format', 'json' );
		$request = $this->auth->preRequestAuth( $request, $this );

		$response = $this->getClient()->request(
			$request->getMethod(),
			$this->apiUrl,
			$this->getClientRequestOptions( $request, $request->getParameterEncoding() )
		);

		return $this->decodeResponse( $response );
	}

	/**
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
	 * request has multipart params).
	 *
	 * @param Request $request The request to which the parameters belong.
	 * @param string[] $params The existing parameters. Not the same as $request->getParams().
	 *
	 * @return array <int mixed[]>
	 */
	private function encodeMultipartParams( Request $request, array $params ): array {
		// See if there are any multipart parameters in this request.
		$multipartParams = ( $request->isMultipart() )
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
		if ( !$this->auth instanceof NoAuth ) {
			if ( $this->auth instanceof UserAndPassword || $this->auth instanceof UserAndPasswordWithDomain ) {
				return 'addwiki-mediawiki-client/' . $this->auth->getUsername();
			}
			return 'addwiki-mediawiki-client/SomeUnknownUser?';
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

	public function getToken( $type = 'csrf' ): string {
		return $this->tokens->get( $type );
	}

	/**
	 * Clear all tokens stored by the API.
	 */
	public function clearTokens(): void {
		$this->tokens->clear();
	}

	public function getVersion(): string {
		if ( $this->version === null ) {
			$result = $this->request( ActionRequest::simpleGet( 'query', [
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
