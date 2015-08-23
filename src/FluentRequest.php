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
		$this->params['action'] = $action;
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
	public function setParams( $params ) {
		$this->params = $params;
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
