# mediawiki-api-base

[![Build Status](https://travis-ci.org/addwiki/mediawiki-api-base.svg?branch=master)](https://travis-ci.org/addwiki/mediawiki-api-base)
[![Code Coverage](https://scrutinizer-ci.com/g/addwiki/mediawiki-api-base/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/addwiki/mediawiki-api-base/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/addwiki/mediawiki-api-base/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/addwiki/mediawiki-api-base/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/addwiki/mediawiki-api-base/version.png)](https://packagist.org/packages/addwiki/mediawiki-api-base)
[![Download count](https://poser.pugx.org/addwiki/mediawiki-api-base/d/total.png)](https://packagist.org/packages/addwiki/mediawiki-api-base)

addwiki/mediawiki-api-base is a PHP HTTP client wrapped around guzzle that makes it easy to interest with a mediawiki installation.

 - Uses PSR-3 interfaces for logging
 - Handles Mediawiki login, sessions, cookies and tokens
 - Handles response errors by throwing catchable UsageExceptions
 - Retries failed requests where possible

## Installation

Use composer to install the library and all its dependencies:

	composer require "addwiki/mediawiki-api-base:~1.0"

## Example Usage

You can either construct an api object by simply passing the api enbpoint:

```php
$api = new MediawikiApi( 'http://localhost/w/api.php' );
```

Or you can pass a guzzle ClientInterface, allowing more flexability:

```php
$api = new MediawikiApi( new Client( array( 'base_url' => 'http://localhost/w/api.php' ) ) );
```

You can easily log in and out:

```php
$api->login( new ApiUser( 'username', 'password' ) );
$api->logout();
```

And make various requests:

```php
$api->postRequest( FluentRequest::factory->setAction( 'purge' )->setParam( 'titles', 'FooBar' ) );

$queryResponse = $api->getRequest( FluentRequest::factory->setAction( 'query' )->setParam( 'meta', 'siteinfo' ) );

try{
	$api->postRequest( new SimpleRequest( 'FooBarBaz' ) );
}
catch ( UsageException $e ) {
	echo "Oh no the api returned an error!";
}
```
