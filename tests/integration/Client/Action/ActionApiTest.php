<?php

namespace Addwiki\Mediawiki\Api\Tests\Integration\Client\Action;

use Addwiki\Mediawiki\Api\Client\Action\Request\ActionRequest;
use Addwiki\Mediawiki\Api\Tests\Integration\BaseTestEnvironment;
use PHPUnit\Framework\TestCase;

class ActionApiTest extends TestCase {

	public function testQueryGetResponse(): void {
		$api = BaseTestEnvironment::newInstance()->getActionApi();
		$response = $api->request( ActionRequest::simpleGet( 'query' ) );
		$this->assertIsArray( $response );
	}

	public function testQueryGetResponseAsync(): void {
		$api = BaseTestEnvironment::newInstance()->getActionApi();
		$response = $api->requestAsync( ActionRequest::simpleGet( 'query' ) );
		$this->assertIsArray( $response->wait() );
	}

	public function testQueryPostResponse(): void {
		$api = BaseTestEnvironment::newInstance()->getActionApi();
		$response = $api->request( ActionRequest::simplePost( 'query' ) );
		$this->assertIsArray( $response );
	}

	public function testQueryPostResponseAsync(): void {
		$api = BaseTestEnvironment::newInstance()->getActionApi();
		$response = $api->requestAsync( ActionRequest::simplePost( 'query' ) );
		$this->assertIsArray( $response->wait() );
	}

}
