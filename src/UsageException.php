<?php

namespace Mediawiki\Api;

use Exception;

/**
 * Class representing a Mediawiki Api UsageException
 *
 * @since 0.1
 */
class UsageException extends Exception {

	/**
	 * @var string
	 */
	private $apiCode;

	/**
	 * @since 0.1
	 *
	 * @param string $apiCode
	 * @param string $message
	 */
	public function __construct( $apiCode = '', $message = '' ) {
		$this->apiCode = $apiCode;
		parent::__construct( $message, 0, null );
	}

	/**
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getApiCode() {
		return $this->apiCode;
	}

} 