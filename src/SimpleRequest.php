<?php

namespace Mediawiki\Api;

use InvalidArgumentException;

/**
 * Please consider using a FluentRequest object
 *
 * @since 0.2
 *
 * @author Addshore
 */
class SimpleRequest implements Request {

	/**
	 * @var string
	 */
	private $action;

	/**
	 * @var array
	 */
	private $params;

	/**
	 * @var array
	 */
	private $headers;

	/**
	 * @param string $action The API action.
	 * @param array $params The parameters for the action.
	 * @param array $headers Any extra HTTP headers to send.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $action, array $params = [], array $headers = [] ) {
		if ( !is_string( $action ) ) {
			throw new InvalidArgumentException( '$action must be string' );
		}
		$this->action = $action;
		$this->params = $params;
		$this->headers = $headers;
	}

	/**
	 * @return string[]
	 */
	public function getParams() {
		return array_merge( [ 'action' => $this->action ], $this->params );
	}

	/**
	 * @return string[]
	 */
	public function getHeaders() {
		return $this->headers;
	}

}
