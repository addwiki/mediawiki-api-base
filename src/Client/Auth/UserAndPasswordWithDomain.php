<?php

namespace Addwiki\Mediawiki\Api\Client\Auth;

use InvalidArgumentException;

/**
 * Represents a user that can log in to the API with a password and optional domain.
 * Such as with https://www.mediawiki.org/wiki/Extension:LDAP_Authentication
 */
class UserAndPasswordWithDomain extends UserAndPassword implements AuthMethod {

	private ?string $domain;

	/**
	 * @param string $username The username.
	 * @param string $password The user's password.
	 * @param string|null $domain The domain (for authentication systems that support domains).
	 */
	public function __construct( string $username, string $password, ?string $domain = null ) {
		parent::__construct( $username, $password );
		if ( $domain !== null && empty( $domain ) ) {
			throw new InvalidArgumentException( 'Domain is not allowed to be an empty string' );
		}
		$this->domain = $domain;
	}

	public function getDomain(): ?string {
		return $this->domain;
	}

	public function equals( UserAndPassword $other ): bool {
		return $other instanceof UserAndPasswordWithDomain
			&& $this->getUsername() === $other->getUsername()
			&& $this->getPassword() === $other->getPassword()
			&& $this->getDomain() === $other->getDomain();
	}

	protected function additionalParamsForPreRequestAuthCall(): array {
		return [
			'lgdomain' => $this->getDomain()
		];
	}

	public function identifierForUserAgent(): ?string {
		return 'user/' . $this->getUsername() . '@' . $this->getDomain();
	}

}
