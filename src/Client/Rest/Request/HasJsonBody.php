<?php

namespace Addwiki\Mediawiki\Api\Client\Rest\Request;

interface HasJsonBody {

	public function setJsonBody( array $body ): self;

	public function getJsonBody(): ?array;

}
