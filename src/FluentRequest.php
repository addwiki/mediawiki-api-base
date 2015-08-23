<?php

namespace Mediawiki\Api;

/**
 * @since 1.0
 */
class FluentRequest implements Request {

	/**
	 * @var array
	 */
	private $params = array();

	/**
	 * @var array
	 */
	private $headers = array();

	public function getParams() {
		return $this->params;
	}

	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * @param string $action
	 *
	 * @return $this
	 */
	public function setAction( $action ) {
		$this->setParam( 'action', $action );
		return $this;
	}

	/**
	 * Totally overwrite any previously set params
	 *
	 * @since 1.0
	 *
	 * @param array $params
	 *
	 * @return $this
	 */
	public function setParams( array $params ) {
		$this->params = $params;
		return $this;
	}

	/**
	 * Totally overwrite any previously set params
	 *
	 * @since 1.0
	 *
	 * @param array $params
	 *
	 * @return $this
	 */
	public function addParams( array $params ) {
		$this->params = array_merge( $this->params, $params );
		return $this;
	}

	/**
	 * @since 1.0
	 *
	 * @param string $param
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setParam( $param, $value ) {
		$this->params[$param] = $value;
		return $this;
	}

	/**
	 * Totally overwrite any previously set params
	 *
	 * @since 1.0
	 *
	 * @param array $headers
	 *
	 * @return $this
	 */
	public function setHeaders( $headers ) {
		$this->headers = $headers;
		return $this;
	}

}
