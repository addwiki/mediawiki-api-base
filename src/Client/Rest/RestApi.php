<?php

namespace Addwiki\Mediawiki\Api\Client\Rest;

use Addwiki\Mediawiki\Api\Client\Action\Tokens;
use Addwiki\Mediawiki\Api\Client\Auth\AuthMethod;
use Addwiki\Mediawiki\Api\Client\Auth\NoAuth;
use Addwiki\Mediawiki\Api\Client\Auth\UserAndPassword;
use Addwiki\Mediawiki\Api\Client\Auth\UserAndPasswordWithDomain;
use Addwiki\Mediawiki\Api\Client\Request\Request;
use Addwiki\Mediawiki\Api\Client\Request\Requester;
use Addwiki\Mediawiki\Api\Client\Rest\Request\HasJsonBody;
use Addwiki\Mediawiki\Api\Guzzle\ClientFactory;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class RestApi implements Requester, LoggerAwareInterface {

	private string $apiUrl;
	private AuthMethod $auth;
	/**
	 * Should be accessed through getClient
	 * @var ClientInterface|null
	 */
	private ?ClientInterface $client = null;
	private Tokens $tokens;
	private LoggerInterface $logger;

	/**
	 * @param string $apiUrl The API Url
	 * @param AuthMethod|null $auth Auth method to use. null for NoAuth
	 * @param ClientInterface|null $client Guzzle Client
	 * @param Tokens|null $tokens Inject a custom tokens here
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
			throw new InvalidArgumentException( 'tokens must be set?' );
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
	 *         Can throw RejectionExceptions
	 */
	public function requestAsync( Request $request ): PromiseInterface {
		$request = $this->auth->preRequestAuth( $request, $this );
		$promise = $this->getClient()->requestAsync(
			$request->getMethod(),
			$this->apiUrl . $request->getPath(),
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
		$request = $this->auth->preRequestAuth( $request, $this );
		$response = $this->getClient()->request(
			$request->getMethod(),
			$this->apiUrl . $request->getPath(),
			$this->getClientRequestOptions( $request, $request->getParameterEncoding() )
		);

		return $this->decodeResponse( $response );
	}

	/**
	 *
	 * @return mixed
	 */
	private function decodeResponse( ResponseInterface $response ) {
		return json_decode( $response->getBody(), true );
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

		$options = [
			$paramsKey => $params,
			'headers' => array_merge( $this->getDefaultHeaders(), $request->getHeaders() ),
		];

		if ( $request instanceof HasJsonBody ) {
			$options['json'] = $request->getJsonBody();
		}

		return $options;
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

	public function getToken( $type = 'csrf' ): string {
		return $this->tokens->get( $type );
	}

	/**
	 * Clear all tokens stored by the API.
	 */
	public function clearTokens(): void {
		$this->tokens->clear();
	}

}
