<?php

namespace Addwiki\Mediawiki\Api\Client\Auth;

use Addwiki\Mediawiki\Api\Client\MediawikiApi;
use Addwiki\Mediawiki\Api\Client\Request\Request;
use InvalidArgumentException;
use MediaWiki\OAuthClient\Consumer as OAuthConsumer;
use MediaWiki\OAuthClient\Request as OAuthRequest;
use MediaWiki\OAuthClient\SignatureMethod\HmacSha1;
use MediaWiki\OAuthClient\Token as OAuthToken;

/**
 * For use with https://www.mediawiki.org/wiki/Extension:Oauth
 */
class OAuthOwnerConsumer implements AuthMethod {

	private string $consumerKey;
	private string $consumerSecret;
	private string $accessToken;
	private string $accessSecret;

	public function __construct( string $consumerKey, string $consumerSecret, string $accessToken, string $accessSecret ) {
		if ( empty( $consumerKey ) || empty( $consumerSecret ) || empty( $accessToken ) || empty( $accessSecret ) ) {
			throw new InvalidArgumentException( 'No empty fields allowed' );
		}
		$this->consumerKey = $consumerKey;
		$this->consumerSecret = $consumerSecret;
		$this->accessToken = $accessToken;
		$this->accessSecret = $accessSecret;
	}

	public function getConsumerKey(): string {
		return $this->consumerKey;
	}

	public function getConsumerSecret(): string {
		return $this->consumerSecret;
	}

	public function getAccessToken(): string {
		return $this->accessToken;
	}

	public function getAccessSecret(): string {
		return $this->accessSecret;
	}

	public function equals( OAuthOwnerConsumer $other ): bool {
		return $this->getConsumerKey() === $other->getConsumerKey()
			&& $this->getConsumerSecret() === $other->getConsumerSecret()
			&& $this->getAccessToken() === $other->getAccessToken()
			&& $this->getAccessSecret() === $other->getAccessSecret();
	}

	public function preRequestAuth( string $method, Request $request, MediawikiApi $api ): Request {
		$request->setHeaders( array_merge( $request->getHeaders(), [ 'Authorization' => $this->getAuthenticationHeaderValue( $method, $request, $api ) ] ) );
		return $request;
	}

	private function getAuthenticationHeaderValue( string $method, Request $request, MediawikiApi $api ): string {
		// Taken directly from https://www.mediawiki.org/wiki/OAuth/Owner-only_consumers
		$oauthConsumer = new OAuthConsumer( $this->getConsumerKey(), $this->getConsumerSecret() );
		$oauthToken = new OAuthToken( $this->getAccessToken(), $this->getAccessSecret() );
		$params = $request->getPostRequestEncoding() === Request::ENCODING_MULTIPART ? [] : $request->getParams();

		$oauthRequest = OAuthRequest::fromConsumerAndToken( $oauthConsumer, $oauthToken, $method, $api->getApiUrl(), $params );
		$oauthRequest->signRequest( new HmacSha1(), $oauthConsumer, $oauthToken );

		$htmlEncodedHeaderString = $oauthRequest->toHeader();
		return str_replace( 'Authorization: ', '', $htmlEncodedHeaderString );
	}

	public function identifierForUserAgent(): ?string {
		return 'oauth-consumer/' . $this->getConsumerKey();
	}

}
