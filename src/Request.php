<?php

namespace Mediawiki\Api;

/**
 * @since 0.2
 */
interface Request {

	/**
	 * @since 0.2
	 * @return array
	 */
	public function getParams();

	/**
	 * Associative array of headers to add to the request.
	 * Each key is the name of a header, and each value is a string or array of strings representing the header field values.
	 *
	 * @since 0.3
	 * @return array
	 */
	public function getHeaders();

	/**
	 * @since 0.4
	 * @return RequestOptions
	 */
	public function getOptions();

}
