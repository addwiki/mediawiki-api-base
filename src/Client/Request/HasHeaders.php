<?php

namespace Addwiki\Mediawiki\Api\Client\Request;

interface HasHeaders {

	/**
	 * Associative array of headers to add to the request.
	 * Each key is the name of a header, and each value is a string or array of strings representing
	 * the header field values.
	 *
	 * @return mixed[]
	 */
	public function getHeaders(): array;

	public function setHeaders( array $headers ): self;

}
