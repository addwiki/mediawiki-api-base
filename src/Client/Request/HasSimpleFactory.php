<?php

namespace Addwiki\Mediawiki\Api\Client\Request;

interface HasSimpleFactory {

	public static function factory(): self;

	public static function f(): self;

}
