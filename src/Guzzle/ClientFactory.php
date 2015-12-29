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
	private $config;

	/**
	 * @since 2.1.0
	 *
	 * @param array $config with possible keys:
	 *          middleware => array of extra middleware to pass to guzzle
	 *          user-agent => string default user agent to use for requests
	 */
	public function __construct( array $config = array() ) {
		$this->logger = new NullLogger();
		$this->config = $config;
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

		if( array_key_exists( 'user-agent', $this->config ) ) {
			$ua = $this->config['user-agent'];
		} else {
			$ua = 'Addwiki - mediawiki-api-base';
		}

		if( array_key_exists( 'middleware', $this->config ) ) {
			foreach( $this->config['middleware'] as $middleware ) {
				$handlerStack->push( $middleware );
			}
		}

		return new Client( array(
			'cookies' => true,
			'handler' => $handlerStack,
			'headers' => array( 'User-Agent' => $ua ),
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
