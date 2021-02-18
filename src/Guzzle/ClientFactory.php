<?php

namespace Addwiki\Mediawiki\Api\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ClientFactory implements LoggerAwareInterface {

	private ?Client $client = null;
	private NullLogger $logger;
	private array $config;

	/**
	 * @param array $config All configuration settings supported by Guzzle, and these:
	 *          middleware => array of extra middleware to pass to guzzle
	 *          user-agent => string default user agent to use for requests
	 */
	public function __construct( array $config = [] ) {
		$this->logger = new NullLogger();
		$this->config = $config;
	}

	public function getClient(): ?Client {
		if ( $this->client === null ) {
			$this->client = $this->newClient();
		}
		return $this->client;
	}

	private function newClient(): Client {
		$this->config += [
			'cookies' => true,
			'headers' => [],
			'middleware' => [],
		];

		if ( !array_key_exists( 'User-Agent', $this->config['headers'] ) ) {
			if ( array_key_exists( 'user-agent', $this->config ) ) {
				$this->config['headers']['User-Agent'] = $this->config['user-agent'];
			} else {
				$this->config['headers']['User-Agent'] = 'Addwiki - mediawiki-api-base';
			}
		}
		unset( $this->config['user-agent'] );

		if ( !array_key_exists( 'handler', $this->config ) ) {
			$this->config['handler'] = HandlerStack::create( new CurlHandler() );
		}

		$middlewareFactory = new MiddlewareFactory();
		$middlewareFactory->setLogger( $this->logger );

		$this->config['middleware'][] = $middlewareFactory->retry();

		foreach ( $this->config['middleware'] as $middleware ) {
			$this->config['handler']->push( $middleware );
		}
		unset( $this->config['middleware'] );

		return new Client( $this->config );
	}

	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

}
