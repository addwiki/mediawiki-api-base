<?php

namespace Addwiki\Mediawiki\Api\Tests\Integration\Client\Action;

use Addwiki\Mediawiki\Api\Client\Rest\Request\RestRequest;
use Addwiki\Mediawiki\Api\Tests\Integration\BaseTestEnvironment;
use PHPUnit\Framework\TestCase;

class RestApiTest extends TestCase {

	public function testRequestGetResponse(): void {
		$api = BaseTestEnvironment::newInstance()->getRestApi();
		$request = RestRequest::f()->setMethod( 'GET' )->setPath( '/v1/page/Main Page/bare' );
		$response = $api->request( $request );
		$this->assertIsArray( $response );
		$this->assertSame( 'Main_Page', $response['key'] );
	}

	public function testRequestGetResponseAsync(): void {
		$api = BaseTestEnvironment::newInstance()->getRestApi();
		$request = RestRequest::f()->setMethod( 'GET' )->setPath( '/v1/page/Main Page/bare' );
		$response = $api->requestAsync( $request );
		$response = $response->wait();
		$this->assertIsArray( $response );
		$this->assertSame( 'Main_Page', $response['key'] );
	}

	public function testRequestPostResponse(): void {
		$testEnv = BaseTestEnvironment::newInstance();
		$api = $testEnv->getRestApi();
		$title = uniqid( 'RestApiTest-' );
		$request = RestRequest::f()->setMethod( 'POST' )->setPath( '/v1/page' )->setJsonBody( [
			'title' => $title,
			'source' => 'Page Content :)',
			'comment' => 'Some creation comment',
			'token' => $testEnv->getActionApi()->getToken( 'csrf' ),
			] );
		$response = $api->request( $request );
		$this->assertIsArray( $response );
		$this->assertSame( $title, $response['title'] );
	}

	public function testRequestPostResponseAsync(): void {
		$testEnv = BaseTestEnvironment::newInstance();
		$api = $testEnv->getRestApi();
		$title = uniqid( 'RestApiTest-' );
		$request = RestRequest::f()->setMethod( 'POST' )->setPath( '/v1/page' )->setJsonBody( [
			'title' => $title,
			'source' => 'Page Content :)',
			'comment' => 'Some creation comment',
			'token' => $testEnv->getActionApi()->getToken( 'csrf' ),
			] );
		$response = $api->requestAsync( $request );
		$response = $response->wait();
		$this->assertIsArray( $response );
		$this->assertSame( $title, $response['title'] );
	}

}
