<?php

namespace Mediawiki\Api\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @since 2.1.0
 *
 * @author Addshore
 */
class ClientFactory implements LoggerAwareInterface {

	private $client;
	private $logger;

	public function __construct() {
		$this->logger = new NullLogger();
	}

	/**
	 * @since 2.1.0
	 *
	 * @return Client
	 */
	public function getClient() {
		if( $this->client === null ) {
			$this->client = $this->newClient();
		}
		return $this->client;
	}

	/**
	 * @return Client
	 */
	private function newClient() {
		$middlewareFactory = new MiddlewareFactory();
		$middlewareFactory->setLogger( $this->logger );

		$handlerStack = HandlerStack::create( new CurlHandler() );
		$handlerStack->push( $middlewareFactory->retry() );

		return new Client( array(
			'cookies' => true,
			'handler' => $handlerStack,
			'headers' => array( 'User-Agent' => 'Addwiki - mediawiki-api-base' ),
		) );
	}

	/**
	 * Sets a logger instance on the object
	 *
	 * @since 2.1.0
	 *
	 * @param LoggerInterface $logger
	 *
	 * @return null
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

}
