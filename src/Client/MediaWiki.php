<?php

namespace Addwiki\Mediawiki\Api\Client;

use Addwiki\Mediawiki\Api\Client\Action\ActionApi;
use Addwiki\Mediawiki\Api\Client\Action\Tokens;
use Addwiki\Mediawiki\Api\Client\Auth\AuthMethod;
use Addwiki\Mediawiki\Api\Client\Auth\NoAuth;
use Addwiki\Mediawiki\Api\Client\Discovery\ReallySimpleDiscovery;
use Addwiki\Mediawiki\Api\Client\Rest\RestApi;

/**
 * Client encompassing both REST and Action MediaWiki APIs
 */
class MediaWiki {

	/**
	 * @var string
	 */
	private const ACTION_PHP = 'api.php';

	/**
	 * @var string
	 */
	private const REST_PHP = 'rest.php';

	private string $baseUrl;

	private AuthMethod $auth;

	private ActionApi $action;

	private RestApi $rest;

	private array $config;

	public function __construct( string $baseUrl, AuthMethod $auth = null, array $config = [] ) {
		if ( $auth === null ) {
			$auth = new NoAuth();
		}

		$this->baseUrl = $baseUrl;
		$this->auth = $auth;
		$this->config = $config;
	}

	/**
	 * @param string $anApiEndpoint Either the REST or Action API endpoint e.g. https://en.wikipedia.org/w/api.php
	 * @param AuthMethod|null $auth
	 * @param array $config ClientInterface compatible configuration array
	 */
	public static function newFromEndpoint( string $anApiEndpoint, AuthMethod $auth = null, array $config = [] ): self {
		return new self( self::pruneActionOrRestPhp( $anApiEndpoint ), $auth, $config );
	}

	private static function pruneActionOrRestPhp( string $url ): string {
		return str_replace( 'rest.php', '', str_replace( self::ACTION_PHP, '', $url ) );
	}

	/**
	 * @param string $anApiEndpoint A page on a MediaWiki site e.g. https://en.wikipedia.org/wiki/Main_Page
	 * @param AuthMethod|null $auth
	 * @param array $config ClientInterface compatible configuration array
	 */
	public static function newFromPage( string $pageUrl, AuthMethod $auth = null, array $config = [] ): self {
		return new self( ReallySimpleDiscovery::baseFromPage( $pageUrl ), $auth, $config );
	}

	public function action(): ActionApi {
		if ( !isset( $this->action ) ) {
			$this->action = new ActionApi( $this->baseUrl . self::ACTION_PHP, $this->auth, null, null, $this->config );
		}

		return $this->action;
	}

	public function rest(): RestApi {
		if ( !isset( $this->rest ) ) {
			// TODO perhaps use the same Tokens object between the 2 APIs
			$this->rest = new RestApi( $this->baseUrl . self::REST_PHP, $this->auth, null, new Tokens( $this->action() ), $this->config );
		}

		return $this->rest;
	}

}
