<?php

namespace Addwiki\Mediawiki\Api\Tests\Integration\Client;

use Addwiki\Mediawiki\Api\Client\Auth\NoAuth;
use Addwiki\Mediawiki\Api\Client\MediawikiApi;
use Addwiki\Mediawiki\Api\Client\Request\MultipartRequest;
use Addwiki\Mediawiki\Api\Client\Request\SimpleRequest;
use Addwiki\Mediawiki\Api\Tests\Integration\BaseTestEnvironment;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase {

	private function getUserInfo( MediaWikiApi $api ) : array {
		return $api->getRequest( new SimpleRequest( 'query', [ 'meta' => 'userinfo' ] ) );
	}

	private function assertUserLoggedIn( string $expectedUser, MediawikiApi $api ) {
		$this->assertSame( $expectedUser, $this->getUserInfo( $api )['query']['userinfo']['name'] );
	}

	private function assertAnon( MediawikiApi $api ) {
		$this->assertArrayHasKey( 'anon', $this->getUserInfo( $api )['query']['userinfo'] );
	}

	private function getUserInfoUsingPost( MediaWikiApi $api ) : array {
		return $api->postRequest( new SimpleRequest( 'query', [ 'meta' => 'userinfo' ] ) );
	}

	private function assertUserLoggedInUsingPost( string $expectedUser, MediawikiApi $api ) {
		$this->assertSame( $expectedUser, $this->getUserInfoUsingPost( $api )['query']['userinfo']['name'] );
	}

	public function testNoAuth() {
		$this->assertAnon( BaseTestEnvironment::newInstance()->getApi( new NoAuth() ) );
	}

	public function testUsernamePasswordAuth() {
		$env = BaseTestEnvironment::newInstance();
		$auth = $env->getUserAndPasswordAuth();
		$api = $env->getApi( $auth );
		$this->assertUserLoggedIn( $auth->getUsername(), $api );
	}

	public function testOAuthAuthGet() {
		$env = BaseTestEnvironment::newInstance();
		$auth = $env->getOAuthOwnerConsumerAuth();
		$api = $env->getApi( $auth );
		$this->assertUserLoggedIn( 'CIUser', $api );
	}

	public function testOAuthAuthPost() {
		$env = BaseTestEnvironment::newInstance();
		$auth = $env->getOAuthOwnerConsumerAuth();
		$api = $env->getApi( $auth );
		$this->assertUserLoggedInUsingPost( 'CIUser', $api );
	}

	public function testOAuthAuthPostMultipart() {
		$env = BaseTestEnvironment::newInstance();
		$auth = $env->getOAuthOwnerConsumerAuth();
		$api = $env->getApi( $auth );
		$multiRequest = new MultipartRequest();
		$multiRequest->setParams( [ 'action' => 'query', 'meta' => 'userinfo' ] );
		$this->assertSame( 'CIUser', $api->postRequest( $multiRequest )['query']['userinfo']['name'] );
	}

}
