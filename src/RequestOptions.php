<?php

namespace Mediawiki\Api;

use InvalidArgumentException;

/**
 * @since 0.4
 */
class RequestOptions {

	/**
	 * @var integer
	 */
	private $attempts = 1;

	/**
	 * @param array $options associative array of options. Unknown options will be ignored.
	 */
	public function __construct( array $options = array() ) {
		if ( array_key_exists( 'attempts', $options ) ) {
			$this->setAttempts( $options['attempts'] );
		}
	}

	/**
	 * @since 0.4
	 *
	 * @return integer
	 */
	public function getAttempts() {
		return $this->attempts;
	}

	/**
	 * @since 0.4
	 *
	 * @param integer $attempts
	 *
	 * @return $this
	 */
	public function setAttempts( $attempts ) {
		if ( $attempts < 1 ) {
			throw new InvalidArgumentException( 'The number of attempts must be 1 or more.' );
		}

		$this->attempts = $attempts;

		return $this;
	}

}
