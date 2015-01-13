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
	 * @var bool if this session is running against mediawiki version pre 1.25
	 */
	private $usePre125TokensModule = false;

	/**
	 * @param MediawikiApi $api
	 */
	public function __construct( MediawikiApi $api ) {
		$this->api = $api;
	}

	/**
	 * Tries to get the specified token from the API
	 *
	 * @since 0.1
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function getToken( $type = 'csrf' ) {
		// If we don't already have the token that we want
		if( !array_key_exists( $type, $this->tokens ) ) {

			// If the new module cant handle the token we want OR we don't have the new module mw<1.25
			if( !in_array( $type, array( 'csrf', 'watch', 'patrol', 'rollback', 'userrights' ) ) || $this->usePre125TokensModule ) {
				// Switch edit token type...
				$oldType = ($type === 'csrf' ? 'edit' : $type);
				// Suppress deprecation warning
				$result = @$this->api->postRequest(
					new SimpleRequest( 'tokens', array( 'type' => $oldType ) )
				);
				$this->tokens[$type] = array_pop( $result['tokens'] );
			} else {
				// We suppress errors on this call so the user doesn't get get a warning that isn't their fault.
				$result = @$this->api->postRequest(
					new SimpleRequest( 'query', array(
						'meta' => 'tokens',
						'type' => $type,
						'continue' => '',
					) )
				);
				// If mw<1.25 (no new module)
				if( array_key_exists( 'warnings', $result ) && array_key_exists( 'query', $result['warnings'] ) &&
					strstr( $result['warnings']['query']['*'], "Unrecognized value for parameter 'meta': tokens" ) ) {
					$this->usePre125TokensModule = true;
					$this->tokens[$type] = $this->getToken( $type );
				} else {
					$this->tokens[$type] = array_pop( $result['query']['tokens'] );
				}
			}

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