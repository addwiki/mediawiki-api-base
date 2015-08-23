<?php

namespace Mediawiki\Api;

use InvalidArgumentException;

/**
 * Please consider using a FluentRequest object
 *
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
	 * @var RequestOptions
	 */
	private $options;

	/**
	 * @param string $action
	 * @param array $params
	 * @param array $headers
	 * @param RequestOptions $options
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$action,
		array $params = array(),
		array $headers = array(),
		RequestOptions $options = null
	) {
		if( !is_string( $action ) ) {
			throw new InvalidArgumentException( '$action must be string' );
		}
		if( is_null( $options ) ) {
			$options = new RequestOptions();
		}
		$this->action = $action;
		$this->params = $params;
		$this->headers = $headers;
		$this->options = $options;
	}

	public function getParams() {
		return array_merge( array( 'action' => $this->action ) , $this->params );
	}

	public function getHeaders() {
		return $this->headers;
	}

	public function getOptions() {
		return $this->options;
	}

}
