<?php

namespace Addwiki\Mediawiki\Api\Client\Discovery;

use Addwiki\Mediawiki\Api\Client\RsdException;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Client;
use SimpleXMLElement;

class ReallySimpleDiscovery {

	/**
	 * @see https://en.wikipedia.org/wiki/Really_Simple_Discovery
	 *
	 * @param string $pageUrl e.g. https://en.wikipedia.org OR https://de.wikipedia.org/wiki/Berlin
	 *
	 * @return string baseUrl for API endpoints eg. 'https://de.wikipedia.org/w/'
	 *
	 * @throws RsdException If the RSD URL could not be found in the page's HTML.
	 */
	public static function baseFromPage( string $pageUrl ): string {
		// TODO check if needed extensions are installed, and die if not?
		//         "ext-dom": "Needed if you want to discover APIs using only page URLs",
		//        "ext-simplexml": "Needed if you want to discover APIs using only page URLs"

		// Set up HTTP client and HTML document.
		$tempClient = new Client( [ 'headers' => [ 'User-Agent' => 'addwiki-mediawiki-client' ] ] );
		$pageHtml = $tempClient->get( $pageUrl )->getBody();
		$pageDoc = new DOMDocument();

		// Try to load the HTML (turn off errors temporarily; most don't matter, and if they do get
		// in the way of finding the API URL, will be reported in the RsdException below).
		$internalErrors = libxml_use_internal_errors( true );
		$pageDoc->loadHTML( $pageHtml );
		$libXmlErrors = libxml_get_errors();
		libxml_use_internal_errors( $internalErrors );

		// Extract the RSD link.
		$xpath = 'head/link[@type="application/rsd+xml"][@href]';
		$link = ( new DOMXpath( $pageDoc ) )->query( $xpath );
		if ( $link->length === 0 ) {
			// Format libxml errors for display.
			$libXmlErrorStr = array_reduce( $libXmlErrors, fn( $prevErr, $err ) => $prevErr . ', ' . $err->message . ' (line ' . $err->line . ')' );
			if ( $libXmlErrorStr ) {
				$libXmlErrorStr = sprintf( 'In addition, libxml had the following errors: %s', $libXmlErrorStr );
			}
			throw new RsdException( sprintf( 'Unable to find RSD URL in page: %s %s', $pageUrl, $libXmlErrorStr ) );
		}
		$linkItem = $link->item( 0 );
		if ( ( $linkItem->attributes ) === null ) {
			throw new RsdException( 'Unexpected RSD fetch error' );
		}
		/** @psalm-suppress NullReference */
		$rsdUrl = $linkItem->attributes->getNamedItem( 'href' )->nodeValue;

		// Then get the RSD XML, and return the API base url.
		$rsdXml = new SimpleXMLElement( $tempClient->get( $rsdUrl )->getBody() );
		$actionAPIUrl = (string)$rsdXml->service->apis->api->attributes()->apiLink;
		return str_replace( 'api.php', '', $actionAPIUrl );
	}

}
