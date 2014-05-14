<?php

namespace Mediawiki\Api;

use InvalidArgumentException;

/**
 * Represents a user that can log in to the api
 */
class ApiUser {

	/**
	 * @var string
	 */
	private $password;

	/**
	 * @var string
	 */
	private $username;

	/**
	 * @param string $username
	 * @param string $password
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $username, $password ) {
		if( !is_string( $username ) || !is_string( $password ) ) {
			throw new InvalidArgumentException( 'Username and Password must both be strings' );
		}
		if( empty( $username ) || empty( $password ) ) {
			throw new InvalidArgumentException( 'Username and Password are not allowed to be empty' );
		}
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * @since 0.1
	 * @return string
	 */
	public function getUsername() {
		return $this->username;
	}

	/**
	 * @since 0.1
	 * @return string
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 * @since 0.1
	 * @param mixed $other
	 *
	 * @return bool
	 */
	public function equals( $other ) {
		if( $other instanceof ApiUser && $this->username == $other->getUsername() && $this->password == $other->getPassword() ) {
			return true;
		}
		return false;
	}

} 