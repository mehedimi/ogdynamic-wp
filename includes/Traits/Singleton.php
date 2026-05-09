<?php
/**
 * Reusable singleton helper.
 *
 * @package OGD
 */

namespace OGDynamic\Traits;

use LogicException;

trait Singleton {
	/**
	 * Singleton instance for the consuming class.
	 *
	 * @var static|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance for the consuming class.
	 *
	 * @return static
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * Prevent cloning singleton instances.
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing singleton instances.
	 *
	 * @throws LogicException When unserializing is attempted.
	 */
	public function __wakeup() {
		throw new LogicException( 'Cannot unserialize singleton instance.' );
	}
}
