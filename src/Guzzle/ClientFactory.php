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

	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
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
		$this->setUaHeaderFromConfigOrDefault();
		$this->setDefaultHandlerIfNotInConfigAlready();
		$this->setMiddlewareFromConfigWithDefaultRetry();
		return new Client( $this->config );
	}

	private function setUaHeaderFromConfigOrDefault(): void {
		// If a UA header is not already manually set
		if ( !array_key_exists( 'User-Agent', $this->config['headers'] ) ) {
			// and a UA is provided in the config
			if ( array_key_exists( 'user-agent', $this->config ) ) {
				// Set header to the config UA
				$this->config['headers']['User-Agent'] = $this->config['user-agent'];
			} else {
				// Set to a default
				$this->config['headers']['User-Agent'] = 'Addwiki - mediawiki-api-base';
			}
		}
		// Unset the config, so that Guzzle doesn't do anything with it.
		unset( $this->config['user-agent'] );
	}

	private function setDefaultHandlerIfNotInConfigAlready(): void {
		if ( !array_key_exists( 'handler', $this->config ) ) {
			$this->config['handler'] = HandlerStack::create( new CurlHandler() );
		}
	}

	private function setMiddlewareFromConfigWithDefaultRetry(): void {
		$middlewareFactory = new MiddlewareFactory();
		$middlewareFactory->setLogger( $this->logger );

		$this->config['middleware'][] = $middlewareFactory->retry();

		foreach ( $this->config['middleware'] as $middleware ) {
			$this->config['handler']->push( $middleware );
		}
		unset( $this->config['middleware'] );
	}

}
