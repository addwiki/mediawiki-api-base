<?php

namespace Addwiki\Mediawiki\Api\Tests\Integration;

use Addwiki\Mediawiki\Api\Client\MediawikiApi;
use Addwiki\Mediawiki\Api\Client\SimpleRequest;
use Exception;

/**
 * @author Addshore
 */
class BaseTestEnvironment {

	/** @var MediawikiApi */
	private $api;

	/** @var string */
	private $apiUrl;

	/** @var string */
	private $pageUrl;

	/**
	 * Get a new BaseTestEnvironment.
	 * This is identical to calling self::__construct() but is useful for fluent construction.
	 *
	 * @return BaseTestEnvironment
	 */
	public static function newInstance() {
		return new self();
	}

	/**
	 * Set up the test environment by creating a new API object pointing to a
	 * MediaWiki installation on localhost (or elsewhere as specified by the
	 * ADDWIKI_MW_API environment variable).
	 *
	 * @throws Exception If the ADDWIKI_MW_API environment variable does not end in 'api.php'
	 */
	public function __construct() {
		$apiUrl = getenv( 'ADDWIKI_MW_API' );

		if ( !$apiUrl ) {
			$apiUrl = "http://localhost:8877/api.php";
		}

		if ( substr( $apiUrl, -7 ) !== 'api.php' ) {
			$msg = sprintf( 'URL incorrect: %s', $apiUrl )
				. " (Set the ADDWIKI_MW_API environment variable correctly)";
			throw new Exception( $msg );
		}

		$this->apiUrl = $apiUrl;
		$this->pageUrl = str_replace( 'api.php', 'index.php?title=Special:SpecialPages', $apiUrl );
		$this->api = MediawikiApi::newFromApiEndpoint( $this->apiUrl );
	}

	/**
	 * Get the url of the api to test against, based on the MEDIAWIKI_API_URL environment variable.
	 * @return string
	 */
	public function getApiUrl() {
		return $this->apiUrl;
	}

	/**
	 * Get the url of a page on the wiki to test against, based on the api url.
	 * @return string
	 */
	public function getPageUrl() {
		return $this->pageUrl;
	}

	/**
	 * Get the MediawikiApi to test against
	 * @return MediawikiApi
	 */
	public function getApi() {
		return $this->api;
	}

	/**
	 * Save a wiki page.
	 * @param string $title The title of the page.
	 * @param string $content The complete page text to save.
	 */
	public function savePage( $title, $content ) {
		$params = [
			'title' => $title,
			'text' => $content,
			'md5' => md5( $content ),
			'token' => $this->api->getToken(),
		];
		$this->api->postRequest( new SimpleRequest( 'edit', $params ) );
	}
}
