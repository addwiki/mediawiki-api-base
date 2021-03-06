<?php

namespace Addwiki\Mediawiki\Api\Client\Action\Request;

interface HasParameterAction {

	public function setAction( string $action ): self;

}
