<?php

namespace Addwiki\Mediawiki\Api\Client;

use InvalidArgumentException;

/**
 * Represents a user that can log in to the api
 */
class ApiUser {

	private string $password;

	private string $username;

	private ?string $domain;

	/**
	 * @param string $username The username.
	 * @param string $password The user's password.
	 * @param string|null $domain The domain (for authentication systems that support domains).
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( string $username, string $password, ?string $domain = null ) {
		$domainIsStringOrNull = ( is_string( $domain ) || $domain === null );
		if ( !is_string( $username ) || !is_string( $password ) || !$domainIsStringOrNull ) {
			throw new InvalidArgumentException( 'Username, Password and Domain must all be strings' );
		}
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

	/**
	 * @param mixed $other Another ApiUser object to compare with.
	 */
	public function equals( $other ): bool {
		return $other instanceof self
			&& $this->username === $other->getUsername()
			&& $this->password === $other->getPassword()
			&& $this->domain === $other->getDomain();
	}

}
