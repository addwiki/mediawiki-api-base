<?php

namespace Addwiki\Mediawiki\Api\Guzzle;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @access private
 */
class MiddlewareFactory implements LoggerAwareInterface {

	private NullLogger $logger;

	public function __construct() {
		$this->logger = new NullLogger();
	}

	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @private
	 *
	 * @param bool $delay default to true, can be false to speed up tests
	 */
	public function retry( bool $delay = true ) : callable {
		if ( $delay ) {
			return Middleware::retry( $this->newRetryDecider(), $this->getRetryDelay() );
		} else {
			return Middleware::retry( $this->newRetryDecider() );
		}
	}

	/**
	 * Returns a method that takes the number of retries and returns the number of miliseconds
	 * to wait
	 */
	private function getRetryDelay(): callable {
		return function ( $numberOfRetries, Response $response = null ) {
			// The $response argument is only passed as of Guzzle 6.2.2.
			if ( $response !== null ) {
				// Retry-After may be a number of seconds or an absolute date (RFC 7231,
				// section 7.1.3).
				$retryAfter = $response->getHeaderLine( 'Retry-After' );

				if ( is_numeric( $retryAfter ) ) {
					return 1000 * $retryAfter;
				}

				if ( $retryAfter !== '' ) {
					$seconds = strtotime( $retryAfter ) - time();
					return 1000 * max( 1, $seconds );
				}
			}

			return 1000 * $numberOfRetries;
		};
	}

	private function newRetryDecider(): callable {
		return function (
			$retries,
			Request $request,
			Response $response = null,
			TransferException $exception = null
		): bool {
			// Don't retry if we have run out of retries
			if ( $retries >= 5 ) {
				return false;
			}

			$shouldRetry = false;

			// Retry connection exceptions
			if ( $exception instanceof ConnectException ) {
				$shouldRetry = true;
			}

			if ( $response !== null ) {
				$data = json_decode( $response->getBody(), true );

				// Retry on server errors
				if ( $response->getStatusCode() >= 500 ) {
					$shouldRetry = true;
				}

				foreach ( $response->getHeader( 'Mediawiki-Api-Error' ) as $mediawikiApiErrorHeader ) {
					$RetryAfterResponseHeaderLine = $response->getHeaderLine( 'Retry-After' );
					if (
						// Retry if the API explicitly tells us to:
						// https://www.mediawiki.org/wiki/Manual:Maxlag_parameter
						$RetryAfterResponseHeaderLine
						||
						// Retry if we have a response with an API error worth retrying
						in_array(
							$mediawikiApiErrorHeader,
							[
								'ratelimited',
								'maxlag',
								'readonly',
								'internal_api_error_DBQueryError',
							]
						)
						||
						// Or if we have been stopped from saving as an 'anti-abuse measure'
						// Note: this tries to match "actionthrottledtext" i18n message for mediawiki
						(
							$mediawikiApiErrorHeader == 'failed-save' &&
							strstr( $data['error']['info'], 'anti-abuse measure' )
						)
					) {
						$shouldRetry = true;
					}

				}
			}

			// Log if we are retrying
			if ( $shouldRetry ) {
				$this->logger->warning(
					sprintf(
						'Retrying %s %s %s/5, %s',
						$request->getMethod(),
						$request->getUri(),
						$retries + 1,
						$response !== null ? 'status code: ' . $response->getStatusCode() :
							$exception->getMessage()
					)
				);
			}

			return $shouldRetry;
		};
	}

}
