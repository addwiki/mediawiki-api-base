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
	 * @param string $action
	 * @param array $params
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $action, array $params = array() ) {
		if( !is_string( $action ) ) {
			throw new InvalidArgumentException( '$action must be string' );
		}
		$this->action = $action;
		$this->params = $params;
	}

	/**
	 * @return array
	 *
	 * @since 0.2
	 */
	public function getParams() {
		return array_merge( array( 'action' => $this->action ) , $this->params );
	}
}