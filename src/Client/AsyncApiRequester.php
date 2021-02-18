<?php

namespace Addwiki\Mediawiki\Api\Client;

use GuzzleHttp\Promise\PromiseInterface;

interface AsyncApiRequester {

	/**
	 * @param Request $request The GET request to send.
	 *
	 *         Normally promising an array, though can be mixed (json_decode result)
	 *         Can throw UsageExceptions or RejectionExceptions
	 */
	public function getRequestAsync( Request $request ): PromiseInterface;

	/**
	 * @param Request $request The POST request to send.
	 *
	 *         Normally promising an array, though can be mixed (json_decode result)
	 *         Can throw UsageExceptions or RejectionExceptions
	 */
	public function postRequestAsync( Request $request ): PromiseInterface;

}
