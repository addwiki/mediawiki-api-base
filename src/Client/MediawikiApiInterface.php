<?php

namespace Addwiki\Mediawiki\Api\Client;

interface MediawikiApiInterface extends ApiRequester, AsyncApiRequester {

	/**
	 * @return bool|string false or the name of the current user
	 */
	public function isLoggedin();

	/**
	 * @param ApiUser $apiUser The ApiUser to log in as.
	 *
	 * @throws UsageException
	 * @return bool success
	 */
	public function login( ApiUser $apiUser ): bool;

	public function logout(): bool;

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
