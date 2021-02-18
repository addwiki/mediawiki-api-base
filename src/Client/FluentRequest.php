<?php

namespace Addwiki\Mediawiki\Api\Client;

/**
 * @since 1.0
 *
 * @author Addshore
 */
class FluentRequest implements Request {

	private array $params = [];

	private array $headers = [];

	/**
	 * @since 1.0
	 *
	 * @return array
	 */
	public function getParams(): array {
		return $this->params;
	}

	/**
	 * @since 1.0
	 *
	 * @return array
	 */
	public function getHeaders(): array {
		return $this->headers;
	}

	/**
	 * @since 1.0
	 *
	 * @return static
	 */
	public static function factory() {
		return new static();
	}

	/**
	 * @since 1.0
	 *
	 * @param string $action The action name.
	 *
	 * @return $this
	 */
	public function setAction( string $action ): self {
		$this->setParam( 'action', $action );
		return $this;
	}

	/**
	 * Totally overwrite any previously set params
	 *
	 * @since 1.0
	 *
	 * @param array $params New parameters.
	 *
	 * @return $this
	 */
	public function setParams( array $params ): self {
		$this->params = $params;
		return $this;
	}

	/**
	 * Totally overwrite any previously set params
	 *
	 * @since 1.0
	 *
	 * @param array $params Additional parameters.
	 *
	 * @return $this
	 */
	public function addParams( array $params ): self {
		$this->params = array_merge( $this->params, $params );
		return $this;
	}

	/**
	 * Set a single parameter.
	 *
	 * @since 1.0
	 *
	 * @param string $param The parameter name.
	 * @param string $value The parameter value.
	 *
	 * @return $this
	 */
	public function setParam( string $param, string $value ): self {
		$this->params[$param] = $value;
		return $this;
	}

	/**
	 * Totally overwrite any previously set HTTP headers.
	 *
	 * @since 1.0
	 *
	 * @param array $headers New headers.
	 *
	 * @return $this
	 */
	public function setHeaders( array $headers ): self {
		$this->headers = $headers;
		return $this;
	}

}
