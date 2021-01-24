# mediawiki-api-base

On Packagist:
[![Latest Stable Version](https://poser.pugx.org/addwiki/mediawiki-api-base/version.png)](https://packagist.org/packages/addwiki/mediawiki-api-base)
[![Download count](https://poser.pugx.org/addwiki/mediawiki-api-base/d/total.png)](https://packagist.org/packages/addwiki/mediawiki-api-base)

Issue tracker: https://github.com/addwiki/addwiki/issues

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
