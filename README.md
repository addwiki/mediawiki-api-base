# mediawiki-api-base

[![GitHub issue custom search in repo](https://img.shields.io/github/issues-search/addwiki/addwiki?label=issues&query=is%3Aissue%20is%3Aopen%20%5Bmediawiki-api-base%5D)](https://github.com/addwiki/addwiki/issues?q=is%3Aissue+is%3Aopen+%5Bmediawiki-api-base%5D+)
[![Latest Stable Version](https://poser.pugx.org/addwiki/mediawiki-api-base/version.png)](https://packagist.org/packages/addwiki/mediawiki-api-base)
[![Download count](https://poser.pugx.org/addwiki/mediawiki-api-base/d/total.png)](https://packagist.org/packages/addwiki/mediawiki-api-base)

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
