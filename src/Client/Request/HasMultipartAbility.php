<?php

namespace Addwiki\Mediawiki\Api\Client\Request;

/**
 * A MultipartTrait adds additional support for setting request
 * parameters (both normal parameters and headers) on multipart requests.
 *
 * @link http://docs.guzzlephp.org/en/stable/request-options.html#multipart
 */
interface HasMultipartAbility {

	/**
	 * @return bool Is this a multipart request?
	 */
	public function isMultipart(): bool;

	/**
	 * @param bool $multipart Force the request to be a multipart request
	 */
	public function setMultipart( bool $multipart ): self;

	/**
	 * Set all multipart parameters, replacing all existing ones.
	 *
	 * Each key of the array passed in here must be the name of a parameter already set on this
	 * request object.
	 *
	 * @param mixed[] $params The multipart parameters to use.
	 */
	public function setMultipartParams( array $params ): self;

	/**
	 * Add extra multipart parameters.
	 *
	 * Each key of the array passed in here must be the name of a parameter already set on this
	 * request object.
	 *
	 * @param mixed[] $params The multipart parameters to add to any already present.
	 */
	public function addMultipartParams( array $params ): self;

	/**
	 * Get all multipart request parameters.
	 *
	 * @return mixed[]
	 */
	public function getMultipartParams(): array;

}
