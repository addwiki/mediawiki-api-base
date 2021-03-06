<?php

namespace Addwiki\Mediawiki\Api\Client\Action\Exception;

use Exception;

class UsageException extends Exception {

	private string $apiCode;
	private array $result = [];
	private string $rawMessage;

	/**
	 * @param string $apiCode The API error code.
	 * @param string $message The API error message.
	 * @param array $result the result the exception was generated from
	 */
	public function __construct( string $apiCode = '', string $message = '', $result = [] ) {
		$this->apiCode = $apiCode;
		$this->result = $result;
		$this->rawMessage = $message;
		$message = 'Code: ' . $apiCode . PHP_EOL .
			'Message: ' . $message . PHP_EOL .
			'Result: ' . json_encode( $result );
		parent::__construct( $message, 0, null );
	}

	public function getApiCode(): string {
		return $this->apiCode;
	}

	public function getApiResult(): array {
		return $this->result;
	}

	public function getRawMessage(): string {
		return $this->rawMessage;
	}

}
