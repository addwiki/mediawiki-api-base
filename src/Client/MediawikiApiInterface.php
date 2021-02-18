<?php

namespace Addwiki\Mediawiki\Api\Client;

/**
 * @since 2.2
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface MediawikiApiInterface extends ApiRequester, AsyncApiRequester {

	/**
	 * @since 2.2
	 *
	 * @return bool|string false or the name of the current user
	 */
	public function isLoggedin();

	/**
	 * @since 2.2
	 *
	 * @param ApiUser $apiUser The ApiUser to log in as.
	 *
	 * @throws UsageException
	 * @return bool success
	 */
	public function login( ApiUser $apiUser ): bool;

	/**
	 * @since 2.2
	 *
	 * @return bool success
	 */
	public function logout(): bool;

	/**
	 * @since 2.2
	 *
	 * @param string $type The type of token to get.
	 */
	public function getToken( $type = 'csrf' ): string;

	/**
	 * @since 2.2
	 *
	 * Clears all tokens stored by the api
	 */
	public function clearTokens();

	/**
	 * @since 2.2
	 */
	public function getVersion(): string;

}
