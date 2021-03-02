<?php

namespace Addwiki\Mediawiki\Api\Client;

use Addwiki\Mediawiki\Api\Client\Request\Request;

interface ApiRequester {

	/**
	 * @param Request $request The GET request to send.
	 *
	 * @return mixed Normally an array
	 */
	public function getRequest( Request $request );

	/**
	 * @param Request $request The POST request to send.
	 *
	 * @return mixed Normally an array
	 */
	public function postRequest( Request $request );

}
