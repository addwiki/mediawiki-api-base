<?php

namespace Addwiki\Mediawiki\Api\Client\Request;

class FluentRequest implements Request {

	private array $params = [];
	private array $headers = [];

	public function getParams(): array {
		return $this->params;
	}

	public function getHeaders(): array {
		return $this->headers;
	}

	public function getPostRequestEncoding(): string {
		if ( $this instanceof MultipartRequest ) {
			return self::ENCODING_MULTIPART;
		}
		foreach ( $this->getParams() as $value ) {
			if ( is_resource( $value ) ) {
				return self::ENCODING_MULTIPART;
			}
		}
		return self::ENCODING_FORMPARAMS;
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
