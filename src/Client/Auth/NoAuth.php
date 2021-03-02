<?php

namespace Addwiki\Mediawiki\Api\Client\Auth;

use Addwiki\Mediawiki\Api\Client\MediawikiApi;
use Addwiki\Mediawiki\Api\Client\Request\Request;

/**
 * For use with plain MediaWiki and no authentication (anon)
 */
class NoAuth implements AuthMethod {

	public function preRequestAuth( string $method, Request $request, MediawikiApi $api ): Request {
		return $request;
	}

	public function identifierForUserAgent(): ?string {
		return null;
	}

}
