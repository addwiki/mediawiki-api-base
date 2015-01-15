These are the release notes for the [mediawiki-api-base](README.md).

## Version 0.3 (development)

* UsageExceptions can now contain the full api result array
* No longer uses addwiki/guzzle-mediawiki-client
* Now using "guzzlehttp/guzzle": "~5.0" ( From "guzzle/guzzle": "~3.2" )
* Added getHeaders method to Request interface

## Version 0.2 (13 January 2015)

### Compatibility changes

* Session objects now use action=query&meta=tokens to get tokens when possible.
NOTE: [Token names have changed between versions](//www.mediawiki.org/wiki/API:Tokens)

### Deprecations

* MediawikiApi getAction and postAction methods have been deprecated in favour of getRequest and postRequest

### New features

* If warnings are present in API results E_USER_WARNING errors are triggered
* The Request interface and SimpleRequest class have been added
* MediawikiApi now has a getRequest and postRequest method
* MediawikiApi now has a getVersion method
* Unsuccessful logins now throw a UsageException with extra details

## Version 0.1.2 (25 May 2014)

* Fix issue where API tokens were not returned

## Version 0.1 (12 May 2014)

* Initial release after split from mediawiki-api lib
