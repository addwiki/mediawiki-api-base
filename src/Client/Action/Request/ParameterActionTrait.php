<?php

namespace Addwiki\Mediawiki\Api\Client\Action\Request;

/**
 * Must be used in conjunction with HasParameters
 */
trait ParameterActionTrait {

	public function setAction( string $action ): self {
		$this->setParam( 'action', $action );
		return $this;
	}

}
