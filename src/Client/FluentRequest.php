<?php

namespace Addwiki\Mediawiki\Api\Client;

class FluentRequest implements Request {

	private array $params = [];
	private array $headers = [];

	public function getParams(): array {
		return $this->params;
	}

	public function getHeaders(): array {
		return $this->headers;
	}

	public static function factory() {
		return new static();
	}

	public function setAction( string $action ): self {
		$this->setParam( 'action', $action );
		return $this;
	}

	public function setParams( array $params ): self {
		$this->params = $params;
		return $this;
	}

	public function addParams( array $params ): self {
		$this->params = array_merge( $this->params, $params );
		return $this;
	}

	public function setParam( string $param, string $value ): self {
		$this->params[$param] = $value;
		return $this;
	}

	public function setHeaders( array $headers ): self {
		$this->headers = $headers;
		return $this;
	}

}
