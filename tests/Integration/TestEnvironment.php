<?php

namespace Mediawiki\Api\Test\Integration;

use Exception;
use Mediawiki\Api\Guzzle\ClientFactory;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\SimpleRequest;

/**
 * @author Addshore
 */
class TestEnvironment {

	public static function newInstance() {
		return new self();
	}

	/** @var MediawikiApi */
	private $api;

	/** @var string */
	private $apiUrl;

	/** @var string */
	private $pageUrl;

	/**
	 * Set up the test environment by creating a new API object pointing to a
	 * MediaWiki installation on localhost (or elsewhere as specified by the
	 * ADDWIKI_MW_API environment variable).
	 *
	 * @throws Exception If the ADDWIKI_MW_API environment variable does not end in 'api.php'
	 */
	public function __construct() {
		$apiUrl = getenv( 'ADDWIKI_MW_API' );

		if ( substr( $apiUrl, -7 ) !== 'api.php' ) {
			$msg = "URL incorrect: $apiUrl"
				." (Set the ADDWIKI_MW_API environment variable correctly)";
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
	 * Run all jobs in the queue. This only works if the MediaWiki installation has $wgJobRunRate
	 * set to greater than zero.
	 * @todo This and TestEnvironment::getJobQueueLength() should probably not live here.
	 * @return void
	 */
	public function runJobs( $maxJobs = 10 ) {
		$reqestProps = [ 'meta'=>'siteinfo', 'siprop'=>'general' ];
		$siteInfoRequest = new SimpleRequest( 'query', $reqestProps );
		$out = $this->getApi()->getRequest( $siteInfoRequest );
		$mainPageUrl = $out['query']['general']['base'];

		$jobsRun = 0;
		$initialLength = $this->getJobQueueLength( $this->getApi() );
		do {
			$jobsRun++;
			$cf = new ClientFactory();
			$cf->getClient()->get( $mainPageUrl );

			$currentLength = $this->getJobQueueLength( $this->getApi() );
		} while (
			$currentLength > 0 &&
			$jobsRun < $maxJobs &&
			$currentLength < $initialLength - $maxJobs
		);
	}

	/**
	 * Get the number of jobs currently in the queue.
	 * @todo This and TestEnvironment::runJobs() should probably not live here.
	 * @param MediawikiApi $api
	 * @return integer
	 */
	public function getJobQueueLength( MediawikiApi $api ) {
		$req = new SimpleRequest( 'query', [
				'meta'=>'siteinfo',
				'siprop'=>'statistics',
			]
		);
		$out = $api->getRequest( $req );
		return (int) $out['query']['statistics']['jobs'];
	}

}