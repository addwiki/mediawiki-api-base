<?php

namespace Addwiki\Mediawiki\Api\Client\Auth;

use Addwiki\Mediawiki\Api\Client\Request\Request;
use Addwiki\Mediawiki\Api\Client\Request\Requester;

interface AuthMethod {

	/**
	 * This will be called before every Request to the API.
	 * It is up to the implementations to decide if anything needs to be done here, such as other API calls, or Request modifications.
	 * For example action=login could be called, or an Authentication header could be added.
	 */
	public function preRequestAuth( Request $request, Requester $requester ): Request;

	/**
	 * We want to provide a useful user agent, not matter the authentication method.
	 * So allow the method to define what is provided.
	 * This could be a username, or a consumer ID for example.
	 * null can be used if the method can't provide anything useful.
	 *
	 * Example: "user/Addshore" or "oauth-consumer/123abc"
	 */
	public function identifierForUserAgent(): ?string;

}
