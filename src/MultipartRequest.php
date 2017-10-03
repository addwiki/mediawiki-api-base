<?php

namespace Mediawiki\Api;

use Exception;

/**
 * A MultipartRequest is the same as a FluentRequest with additional support for setting request
 * parameters (both normal parameters and headers) on multipart requests.
 *
 * @link http://docs.guzzlephp.org/en/stable/request-options.html#multipart
 *
 * @since 2.4.0
 */
class MultipartRequest extends FluentRequest {

	/** @var mixed[] */
	protected $multipartParams = [];

	/**
	 * Check the structure of a multipart parameter array.
	 *
	 * @param mixed[] $params The multipart parameters to check.
	 *
	 * @throws Exception
	 */
	protected function checkMultipartParams( $params ) {
		foreach ( $params as $key => $val ) {
			if ( !is_array( $val ) ) {
				throw new Exception( "Parameter '$key' must be an array." );
			}
			if ( !in_array( $key, array_keys( $this->getParams() ) ) ) {
				throw new Exception( "Parameter '$key' is not already set on this request." );
			}
		}
	}

	/**
	 * Set all multipart parameters, replacing all existing ones.
	 *
	 * Each key of the array passed in here must be the name of a parameter already set on this
	 * request object.
	 *
	 * @param mixed[] $params The multipart parameters to use.
	 * @return $this
	 */
	public function setMultipartParams( $params ) {
		$this->checkMultipartParams( $params );
		$this->multipartParams = $params;
		return $this;
	}

	/**
	 * Add extra multipart parameters.
	 *
	 * Each key of the array passed in here must be the name of a parameter already set on this
	 * request object.
	 *
	 * @param mixed[] $params The multipart parameters to add to any already present.
	 *
	 * @return $this
	 */
	public function addMultipartParams( $params ) {
		$this->checkMultipartParams( $params );
		$this->multipartParams = array_merge( $this->multipartParams, $params );
		return $this;
	}

	/**
	 * Get all multipart request parameters.
	 *
	 * @return mixed[]
	 */
	public function getMultipartParams() {
		return $this->multipartParams;
	}
}
