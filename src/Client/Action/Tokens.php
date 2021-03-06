<?php

namespace Addwiki\Mediawiki\Api\Client\Action;

use Addwiki\Mediawiki\Api\Client\Action\Request\ActionRequest;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class Tokens implements LoggerAwareInterface {

	private array $tokens = [];

	private ActionApi $api;

	/**
	 * @var bool if this session is running against mediawiki version pre 1.25
	 */
	private bool $usePre125TokensModule = false;

	private LoggerInterface $logger;

	/**
	 * @param ActionApi $api The API object to use for this session.
	 */
	public function __construct( ActionApi $api ) {
		$this->api = $api;
		$this->logger = new NullLogger();
	}

	/**
	 * Sets a logger instance on the object
	 *
	 * @param LoggerInterface $logger The new Logger object.
	 *
	 * @return null
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Tries to get the specified token from the API
	 *
	 * @param string $type The type of token to get.
	 */
	public function get( string $type = 'csrf' ): string {
		// If we don't already have the token that we want
		if ( !array_key_exists( $type, $this->tokens ) ) {
			$this->logger->log( LogLevel::DEBUG, 'Getting fresh token', [ 'type' => $type ] );

			// If we know that we don't have the new module mw<1.25
			if ( $this->usePre125TokensModule ) {
				return $this->reallyGetPre125Token( $type );
			} else {
				return $this->reallyGetToken( $type );
			}

		}

		return $this->tokens[$type];
	}

	private function reallyGetPre125Token( $type ) {
		// Suppress deprecation warning
		$result = @$this->api->request( // @codingStandardsIgnoreLine
			ActionRequest::simplePost( 'tokens', [ 'type' => $this->getOldTokenType( $type ) ] )
		);
		$this->tokens[$type] = array_pop( $result['tokens'] );

		return $this->tokens[$type];
	}

	private function reallyGetToken( $type ) {
		// We suppress errors on this call so the user doesn't get get a warning that isn't their fault.
		$result = @$this->api->request( // @codingStandardsIgnoreLine
			ActionRequest::simplePost( 'query', [
				'meta' => 'tokens',
				'type' => $this->getNewTokenType( $type ),
				'continue' => '',
			] )
		);
		// If mw<1.25 (no new module)
		$metaWarning = "Unrecognized value for parameter 'meta': tokens";
		if ( isset( $result['warnings']['query']['*'] )
			&& strpos( $result['warnings']['query']['*'], $metaWarning ) !== false ) {
			$this->usePre125TokensModule = true;
			$this->logger->log( LogLevel::DEBUG, 'Falling back to pre 1.25 token system' );
			$this->tokens[$type] = $this->reallyGetPre125Token( $type );
		} else {
			$this->tokens[$type] = array_pop( $result['query']['tokens'] );
		}

		return $this->tokens[$type];
	}

	/**
	 * Tries to guess a new token type from an old token type
	 */
	private function getNewTokenType( string $type ): string {
		switch ( $type ) {
			case 'edit':
			case 'delete':
			case 'protect':
			case 'move':
			case 'block':
			case 'unblock':
			case 'email':
			case 'import':
			case 'options':
				return 'csrf';
			default:
				// Return the same type, don't know what to do with this..
				return $type;
		}
	}

	/**
	 * Tries to guess an old token type from a new token type
	 */
	private function getOldTokenType( string $type ): string {
		if ( $type === 'csrf' ) {
			return 'edit';
		}
		return $type;
	}

	public function clear(): void {
		$this->logger->log( LogLevel::DEBUG, 'Clearing current tokens', [ 'tokens' => $this->tokens ] );
		$this->tokens = [];
	}

}
