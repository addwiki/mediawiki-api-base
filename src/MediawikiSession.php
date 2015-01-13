<?php

namespace Mediawiki\Api;

/**
 * @since 0.1
 */
class MediawikiSession {

	/**
	 * @var array
	 */
	private $tokens = array();

	/**
	 * @var MediawikiApi
	 */
	private $api;

	/**
	 * @param MediawikiApi $api
	 */
	public function __construct( MediawikiApi $api ) {
		$this->api = $api;
	}

	/**
	 * @since 0.1
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function getToken( $type = 'csrf' ) {
		if( !array_key_exists( $type , $this->tokens ) ) {
			$result = $this->api->postRequest(
				new SimpleRequest( 'query', array(
					'meta' => 'tokens',
					'type' => $type,
					'continue' => ''
				) )
			);
			$this->tokens[$type] = array_pop( $result['query']['tokens'] );
		}
		return $this->tokens[$type];
	}

	/**
	 * Clears all tokens stored by the api
	 *
	 * @since 0.2
	 */
	public function clearTokens() {
		$this->tokens = array();
	}

}