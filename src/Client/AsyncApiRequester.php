<?php

namespace Addwiki\Mediawiki\Api\Client;

use GuzzleHttp\Promise\PromiseInterface;

/**
 * @since 2.2
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface AsyncApiRequester {

	/**
	 * @since 2.2
	 *
	 * @param Request $request The GET request to send.
	 *
	 *         Normally promising an array, though can be mixed (json_decode result)
	 *         Can throw UsageExceptions or RejectionExceptions
	 */
	public function getRequestAsync( Request $request ): PromiseInterface;

	/**
	 * @since 2.2
	 *
	 * @param Request $request The POST request to send.
	 *
	 *         Normally promising an array, though can be mixed (json_decode result)
	 *         Can throw UsageExceptions or RejectionExceptions
	 */
	public function postRequestAsync( Request $request ): PromiseInterface;

}
