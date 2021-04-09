<?php

namespace Addwiki\Mediawiki\Api\Tests\Unit\Client;

use Addwiki\Mediawiki\Api\Client\Action\ActionApi;
use Addwiki\Mediawiki\Api\Client\MediaWiki;
use Addwiki\Mediawiki\Api\Client\Rest\RestApi;
use PHPUnit\Framework\TestCase;

class MediaWikiTest extends TestCase {

	public function testAction(): void {
		$mw = new MediaWiki( 'someFakeBaseUrl' );
		$this->assertInstanceOf( ActionApi::class, $mw->action() );
	}

	public function testRest(): void {
		$mw = new MediaWiki( 'someFakeBaseUrl' );
		$this->assertInstanceOf( RestApi::class, $mw->rest() );
	}

}
