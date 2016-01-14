<?php

namespace Mediawiki\Api;

use GuzzleHttp\Promise\PromiseInterface;

/**
 * @since 2.2
 * @licence GNU GPL v2+
 * @author Addshore
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface MediawikiApiInterface {

	/**
	 * @since 2.2
	 *
	 * @param Request $request
	 *
	 * @return PromiseInterface
	 *         Normally promising an array, though can be mixed (json_decode result)
	 *         Can throw UsageExceptions or RejectionExceptions
	 */
	public function getRequestAsync( Request $request );

	/**
	 * @since 2.2
	 *
	 * @param Request $request
	 *
	 * @return PromiseInterface
	 *         Normally promising an array, though can be mixed (json_decode result)
	 *         Can throw UsageExceptions or RejectionExceptions
	 */
	public function postRequestAsync( Request $request );

	/**
	 * @since 2.2
	 *
	 * @param Request $request
	 *
	 * @return mixed Normally an array
	 */
	public function getRequest( Request $request );

	/**
	 * @since 2.2
	 *
	 * @param Request $request
	 *
	 * @return mixed Normally an array
	 */
	public function postRequest( Request $request );

	/**
	 * @since 2.2
	 *
	 * @return bool|string false or the name of the current user
	 */
	public function isLoggedin();

	/**
	 * @since 2.2
	 *
	 * @param ApiUser $apiUser
	 *
	 * @throws UsageException
	 * @return bool success
	 */
	public function login( ApiUser $apiUser );

	/**
	 * @since 2.2
	 *
	 * @return bool success
	 */
	public function logout();

	/**
	 * @since 2.2
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function getToken( $type = 'csrf' );

	/**
	 * @since 2.2
	 *
	 * Clears all tokens stored by the api
	 */
	public function clearTokens();

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getVersion();

}
