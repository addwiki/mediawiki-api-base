<?php

namespace Addwiki\Mediawiki\Api\Client;

interface MediawikiApiInterface extends ApiRequester, AsyncApiRequester {

	/**
	 * @param string $type The type of token to get.
	 */
	public function getToken( $type = 'csrf' ): string;

	/**
	 * Clears all tokens stored by the api
	 */
	public function clearTokens();

	public function getVersion(): string;

}
