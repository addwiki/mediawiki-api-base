<?php

namespace Addwiki\Mediawiki\Api\Client\Request;

trait SimpleFactoryTrait {

	public static function factory(): self {
		return new static();
	}

	public static function f(): self {
		return new static();
	}

}
