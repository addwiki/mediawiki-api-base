<?php

namespace Addwiki\Mediawiki\Api\Client;

use Exception;

/**
 * A MultipartRequest is the same as a FluentRequest with additional support for setting request
 * parameters (both normal parameters and headers) on multipart requests.
 *
 * @link http://docs.guzzlephp.org/en/stable/request-options.html#multipart
 */
class MultipartRequest extends FluentRequest {

	protected array $multipartParams = [];

	/**
	 * Check the structure of a multipart parameter array.
	 *
	 * @param mixed[] $params The multipart parameters to check.
	 *
	 * @throws Exception
	 */
	protected function checkMultipartParams( array $params ): void {
		foreach ( $params as $key => $val ) {
			if ( !is_array( $val ) ) {
				throw new Exception( sprintf( "Parameter '%s' must be an array.", $key ) );
			}
			if ( !array_key_exists( $key, $this->getParams() ) ) {
				throw new Exception( sprintf( "Parameter '%s' is not already set on this request.", $key ) );
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
	public function setMultipartParams( array $params ): self {
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
	public function addMultipartParams( array $params ): self {
		$this->checkMultipartParams( $params );
		$this->multipartParams = array_merge( $this->multipartParams, $params );
		return $this;
	}

	/**
	 * Get all multipart request parameters.
	 *
	 * @return mixed[]
	 */
	public function getMultipartParams(): array {
		return $this->multipartParams;
	}
}
