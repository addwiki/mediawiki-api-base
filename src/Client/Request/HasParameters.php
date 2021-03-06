<?php

namespace Addwiki\Mediawiki\Api\Client\Request;

interface HasParameters {

	public const ENCODING_QUERY = 'query';
	public const ENCODING_MULTIPART = 'multipart';
	public const ENCODING_FORMPARAMS = 'form_params';

	/**
	 * @return mixed[]
	 */
	public function getParams(): array;

	public function setParams( array $params ): self;

	public function addParams( array $params ): self;

	public function setParam( string $param, string $value ): self;

	/**
	 * Infers the request encoding for POST requests from params and class used
	 * @return string one of the ENCODING_* constants
	 */
	public function getParameterEncoding() : string;

}
