<?php

namespace Addwiki\Mediawiki\Api\Client;

use InvalidArgumentException;

/**
 * Please consider using a FluentRequest object
 */
class SimpleRequest implements Request {

	private string $action;
	private array $params = [];
	private array $headers = [];

	/**
	 * @param string $action The API action.
	 * @param array $params The parameters for the action.
	 * @param array $headers Any extra HTTP headers to send.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( string $action, array $params = [], array $headers = [] ) {
		if ( !is_string( $action ) ) {
			throw new InvalidArgumentException( '$action must be string' );
		}
		$this->action = $action;
		$this->params = $params;
		$this->headers = $headers;
	}

	public function getParams(): array {
		return array_merge( [ 'action' => $this->action ], $this->params );
	}

	public function getHeaders(): array {
		return $this->headers;
	}

}
