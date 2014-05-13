mediawiki-api-base
==================
[![Build Status](https://travis-ci.org/addwiki/mediawiki-api-base.svg?branch=master)](https://travis-ci.org/addwiki/mediawiki-api-base)
[![Code Coverage](https://scrutinizer-ci.com/g/addwiki/mediawiki-api-base/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/addwiki/mediawiki-api-base/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/addwiki/mediawiki-api-base/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/addwiki/mediawiki-api-base/?branch=master)

On Packagist:
[![Latest Stable Version](https://poser.pugx.org/addwiki/mediawiki-api-base/version.png)](https://packagist.org/packages/addwiki/mediawiki-api-base)
[![Download count](https://poser.pugx.org/addwiki/mediawiki-api-base/d/total.png)](https://packagist.org/packages/addwiki/mediawiki-api-base)

## Installation

Use composer to install the library and all its dependencies:

	composer require "addwiki/mediawiki-api-base:dev-master"

## Example Usage

```php
// Load all the stuff
require_once( __DIR__ . '/vendor/autoload.php' );

// Log in to a wiki
$api = new \Mediawiki\Api\MediawikiApi( 'http://localhost/w/api.php' );
$api->login( new \Mediawiki\Api\ApiUser( 'username', 'password' ) );

// Make a POST request
$api->postAction( 'purge', array( 'titles' => 'FooBar' ) );

// Make a GET request
$queryResponse = $api->getAction( 'query', array( 'meta' => 'siteinfo' ) );

// Make a bad request and catch the error
try{
	$api->postAction( 'FooBarBaz' );
}
catch ( UsageException $e ) {
	echo "Oh no the api returned an error!";
}

//Logout
$api->logout();
```
