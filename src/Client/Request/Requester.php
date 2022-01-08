<?php

namespace Addwiki\Mediawiki\Api\Client\Request;

use GuzzleHttp\Promise\PromiseInterface;

/**
 * Common interface to be shared between APIs that allow making generic requests.
 */
interface Requester {

	/**
	 * Get the API URL (the URL to which API requests are sent, usually ending in api.php or rest.php).
	 * This is useful if you have something that implements this interface without knowing the actual api URL
	 *
	 * @return string The API URL.
	 */
	public function getApiUrl(): string;

	/**
	 * @param Request $request The request to send.
	 *
	 * @return mixed Normally an array
	 */
	public function request( Request $request );

	/**
	 * @param Request $request The request to send.
	 *
	 *         Normally promising an array, though can be mixed (json_decode result)
	 */
	public function requestAsync( Request $request ): PromiseInterface;

}
