<?php

namespace Addwiki\Mediawiki\Api\Client;

interface Request {

	/**
	 * @return mixed[]
	 */
	public function getParams(): array;

	/**
	 * Associative array of headers to add to the request.
	 * Each key is the name of a header, and each value is a string or array of strings representing
	 * the header field values.
	 *
	 * @return mixed[]
	 */
	public function getHeaders(): array;

}
