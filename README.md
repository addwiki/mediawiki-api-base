# mediawiki-api-base

[![GitHub issue custom search in repo](https://img.shields.io/github/issues-search/addwiki/addwiki?label=issues&query=is%3Aissue%20is%3Aopen%20%5Bmediawiki-api-base%5D)](https://github.com/addwiki/addwiki/issues?q=is%3Aissue+is%3Aopen+%5Bmediawiki-api-base%5D+)
[![Latest Stable Version](https://poser.pugx.org/addwiki/mediawiki-api-base/version.png)](https://packagist.org/packages/addwiki/mediawiki-api-base)
[![Download count](https://poser.pugx.org/addwiki/mediawiki-api-base/d/total.png)](https://packagist.org/packages/addwiki/mediawiki-api-base)

This library provides basic access to the MediaWiki Action API.
This library features simple methods allowing you to login, logout and do both GET and POST requests.
This library should work with most if not all MediaWiki versions due to its simplicity.

You can find the fill documentation at https://addwiki.github.io/mediawiki-api-base/

## Example

A quick example can be found below:

```php
use \Addwiki\Mediawiki\Api\Client\Auth\UserAndPassword;
use \Addwiki\Mediawiki\Api\Client\Action\MediawikiApi;

$auth = new UserAndPassword( 'username', 'password' )
$api = MediawikiApi::newFromPage( 'https://en.wikipedia.org/wiki/Berlin', $auth );
$purgeRequest = FluentRequest::factory()->setAction( 'purge' )->setParam( 'titles', 'Berlin' );
$api->postRequest( $purgeRequest );
```

## Integration tests

Run the MediaWiki test site:

```sh
docker-compose -f docker-compose-ci.yml up -d
```

Run the tests:

```sh
composer phpunit-integration
```

Destroy the site that was used for testing:

```sh
docker-compose -f docker-compose-ci.yml down --volumes
```
