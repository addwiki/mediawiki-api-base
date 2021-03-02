<?php

namespace Addwiki\Mediawiki\Api\Client;

use InvalidArgumentException;

class SimpleRequest extends FluentRequest implements Request {

	/**
	 * @param string $action The API action.
	 * @param array $params The parameters for the action.
	 * @param array $headers Any extra HTTP headers to send.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( string $action, array $params = [], array $headers = [] ) {
		$this->setAction( $action );
		$this->addParams( $params );
		$this->setHeaders( $headers );
	}

}
