<?php

namespace Addwiki\Mediawiki\Api\Client\Auth;

use InvalidArgumentException;

/**
 * Represents a user that can log in to the API with a password and optional domain.
 * Such as with https://www.mediawiki.org/wiki/Extension:LDAP_Authentication
 */
class UserAndPasswordWithDomain implements AuthMethod {

	private string $password;
	private string $username;
	private ?string $domain;

	/**
	 * @param string $username The username.
	 * @param string $password The user's password.
	 * @param string|null $domain The domain (for authentication systems that support domains).
	 */
	public function __construct( string $username, string $password, ?string $domain = null ) {
		if ( empty( $username ) || empty( $password ) ) {
			throw new InvalidArgumentException( 'Username and Password are not allowed to be empty' );
		}
		if ( $domain !== null && empty( $domain ) ) {
			throw new InvalidArgumentException( 'Domain is not allowed to be an empty string' );
		}
		$this->username = $username;
		$this->password = $password;
		$this->domain   = $domain;
	}

	public function getUsername(): string {
		return $this->username;
	}

	public function getPassword(): string {
		return $this->password;
	}

	public function getDomain(): ?string {
		return $this->domain;
	}

	public function equals( UserAndPasswordWithDomain $other ): bool {
		return $this->username === $other->getUsername()
			&& $this->password === $other->getPassword()
			&& $this->domain === $other->getDomain();
	}

}
