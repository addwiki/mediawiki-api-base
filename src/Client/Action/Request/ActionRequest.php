<?php

namespace Addwiki\Mediawiki\Api\Client\Action\Request;

use Addwiki\Mediawiki\Api\Client\Request\StandardRequest;

class ActionRequest extends StandardRequest implements HasParameterAction {

	use ParameterActionTrait;

	public static function simpleGet( string $action, array $params = [], array $headers = [] ): self {
		$req = new self();
		$req->setMethod( 'GET' );
		$req->setAction( $action );
		$req->addParams( $params );
		$req->setHeaders( $headers );
		return $req;
	}

	public static function simplePost( string $action, array $params = [], array $headers = [] ): self {
		$req = new self();
		$req->setMethod( 'POST' );
		$req->setAction( $action );
		$req->addParams( $params );
		$req->setHeaders( $headers );
		return $req;
	}

}
