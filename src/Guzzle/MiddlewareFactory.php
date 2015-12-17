<?php

namespace Mediawiki\Api\Guzzle;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @access private
 *
 * @author Addshore
 */
class MiddlewareFactory implements LoggerAwareInterface {

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct() {
		$this->logger = new NullLogger();
	}

	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @return callable
	 */
	public function retry() {
		return Middleware::retry( $this->newRetryDecider() );
	}

	/**
	 * @return callable
	 */
	private function newRetryDecider() {
		return function (
			$retries,
			Request $request,
			Response $response = null,
			RequestException $exception = null
		) {
			// Don't retry if we have run out of retries
			if ( $retries >= 5 ) {
				return false;
			}

			$shouldRetry = false;

			// Retry connection exceptions
			if( $exception instanceof ConnectException ) {
				$shouldRetry = true;
			}

			if( $response ) {
				$headers = $response->getHeaders();

				// Retry on server errors
				if( $response->getStatusCode() >= 500 ) {
					$shouldRetry = true;
				}

				// Retry if we have a response with an API error worth retrying
				if ( array_key_exists( 'mediawiki-api-error', $headers ) ) {
					foreach( $headers['mediawiki-api-error'] as $mediawikiApiErrorHeader ) {
						if ( in_array(
							$mediawikiApiErrorHeader,
							array(
								'ratelimited',
								'readonly',
								'internal_api_error_DBQueryError',
							)
						) ) {
							$shouldRetry = true;
						}
					}
				}
			}

			// Log if we are retrying
			if( $shouldRetry ) {
				$this->logger->warning(
					sprintf(
						'Retrying %s %s %s/5, %s',
						$request->getMethod(),
						$request->getUri(),
						$retries + 1,
						$response ? 'status code: ' . $response->getStatusCode() :
							$exception->getMessage()
					)
				);
			}

			return $shouldRetry;
		};
	}

}
