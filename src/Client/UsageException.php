<?php

namespace Addwiki\Mediawiki\Api\Client;

use Exception;

/**
 * Class representing a Mediawiki Api UsageException
 *
 * @since 0.1
 *
 * @author Addshore
 */
class UsageException extends Exception {

	private string $apiCode;

	private array $result = [];

	private string $rawMessage;

	/**
	 * @since 0.1
	 *
	 * @param string $apiCode The API error code.
	 * @param string $message The API error message.
	 * @param array $result the result the exception was generated from
	 */
	public function __construct( $apiCode = '', $message = '', $result = [] ) {
		$this->apiCode = $apiCode;
		$this->result = $result;
		$this->rawMessage = $message;
		$message = 'Code: ' . $apiCode . PHP_EOL .
			'Message: ' . $message . PHP_EOL .
			'Result: ' . json_encode( $result );
		parent::__construct( $message, 0, null );
	}

	/**
	 * @since 0.1
	 */
	public function getApiCode(): string {
		return $this->apiCode;
	}

	/**
	 * @since 0.3
	 *
	 * @return mixed[]
	 */
	public function getApiResult(): array {
		return $this->result;
	}

	/**
	 * @since 2.3.0
	 */
	public function getRawMessage(): string {
		return $this->rawMessage;
	}

}
