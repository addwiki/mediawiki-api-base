<?php

namespace Mediawiki\Api;

/**
 * @since 0.4
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

	/**
	 * @var RequestOptions
	 */
	private $options;

	public function __construct() {
		$this->options = new RequestOptions();
	}

	public function getParams() {
		return $this->params;
	}

	public function getHeaders() {
		return $this->headers;
	}

	public function getOptions() {
		return $this->options;
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
	 * @since 0.4
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
	 * @since 0.4
	 *
	 * @param array $headers
	 *
	 * @return $this
	 */
	public function setHeaders( $headers ) {
		$this->headers = $headers;
		return $this;
	}

	/**
	 * @since 0.4
	 *
	 * @param RequestOptions $options
	 *
	 * @return $this
	 */
	public function setOptions( RequestOptions $options ) {
		$this->options = $options;
		return $this;
	}

	/**
	 * @since 0.4
	 *
	 * @param integer $attempts
	 *
	 * @return $this
	 */
	public function setAttempts( $attempts ) {
		$this->options->setAttempts( $attempts );
		return $this;
	}

}
