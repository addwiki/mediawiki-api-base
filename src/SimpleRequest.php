<?php

namespace Mediawiki\Api;

use InvalidArgumentException;

/**
 * @since 0.2
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
	 * @param string $action
	 * @param array $params
	 * @param array $headers
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $action, array $params = array(), array $headers = array() ) {
		if( !is_string( $action ) ) {
			throw new InvalidArgumentException( '$action must be string' );
		}
		$this->action = $action;
		$this->params = $params;
		$this->headers = $headers;
	}

	/**
	 * @return array
	 *
	 * @since 0.2
	 */
	public function getParams() {
		return array_merge( array( 'action' => $this->action ) , $this->params );
	}

	/**
	 * @return array
	 *
	 * @since 0.3
	 */
	public function getHeaders() {
		return $this->headers;
	}
}