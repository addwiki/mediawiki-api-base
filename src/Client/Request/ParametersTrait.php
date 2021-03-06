<?php

namespace Addwiki\Mediawiki\Api\Client\Request;

/**
 * Must be used in conjunction with HasMethod
 */
trait ParametersTrait {

	private array $params = [];

	public function getParams(): array {
		return $this->params;
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

	public function getParameterEncoding(): string {
		if ( $this->getMethod() === 'GET' ) {
			return self::ENCODING_QUERY;
		}
		if ( $this instanceof HasMultipartAbility && $this->isMultipart() ) {
			return self::ENCODING_MULTIPART;
		}
		return self::ENCODING_FORMPARAMS;
	}

}
