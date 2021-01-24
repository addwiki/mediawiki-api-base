# mediawiki-api-base

[![Code Coverage](https://scrutinizer-ci.com/g/addwiki/mediawiki-api-base/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/addwiki/mediawiki-api-base/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/addwiki/mediawiki-api-base/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/addwiki/mediawiki-api-base/?branch=master)

[![Latest Stable Version](https://poser.pugx.org/addwiki/mediawiki-api-base/version.png)](https://packagist.org/packages/addwiki/mediawiki-api-base)
[![Download count](https://poser.pugx.org/addwiki/mediawiki-api-base/d/total.png)](https://packagist.org/packages/addwiki/mediawiki-api-base)

Issue tracker: https://phabricator.wikimedia.org/project/profile/1490/

Documentation: https://addwiki.readthedocs.io

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
