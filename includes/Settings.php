<?php
/**
 * Plugin settings access.
 *
 * @package OGD
 */

namespace OGDynamic;

class Settings {
	public const PREFIX = 'ogdy_';

	/**
	 * Gets a setting value.
	 *
	 * @param string $key     The option key.
	 * @param mixed  $fallback The fallback value if option doesn't exist.
	 * @return mixed The option value or fallback.
	 */
	public static function get( string $key, $fallback = false ) {
		return get_option( self::option_name( $key ), $fallback );
	}

	/**
	 * Updates a setting value.
	 *
	 * @param string $key     The option key.
	 * @param mixed  $value   The option value.
	 * @param bool   $autoload Whether to load the option on every page request.
	 * @return bool True if the value was updated, false otherwise.
	 */
	public static function update( string $key, $value, bool $autoload = false ): bool {
		return update_option( self::option_name( $key ), $value, $autoload );
	}

	/**
	 * Deletes a setting.
	 *
	 * @param string $key The option key.
	 * @return bool True if the option was deleted, false otherwise.
	 */
	public static function delete( string $key ): bool {
		return delete_option( self::option_name( $key ) );
	}

	/**
	 * Gets a transient value.
	 *
	 * @param string $key     The transient key.
	 * @param mixed  $fallback The fallback value if transient doesn't exist.
	 * @return mixed The transient value or fallback.
	 */
	public static function get_transient( string $key, $fallback = false ) {
		$transient = get_transient( self::option_name( $key ) );
		return false === $transient ? $fallback : $transient;
	}

	/**
	 * Sets a transient value.
	 *
	 * @param string $key        The transient key.
	 * @param mixed  $value      The transient value.
	 * @param int    $expiration The expiration time in seconds.
	 * @return bool True if the transient was set, false otherwise.
	 */
	public static function set_transient( string $key, $value, int $expiration = 0 ): bool {
		return set_transient( self::option_name( $key ), $value, $expiration );
	}

	/**
	 * Deletes a transient.
	 *
	 * @param string $key The transient key.
	 * @return bool True if the transient was deleted, false otherwise.
	 */
	public static function delete_transient( string $key ): bool {
		return delete_transient( self::option_name( $key ) );
	}

	/**
	 * Gets the refresh token.
	 *
	 * @return string The refresh token or empty string.
	 */
	public static function get_refresh_token(): string {
		return (string) self::get( 'oauth_refresh_token', '' );
	}


	/**
	 * Prepends the prefix to the option key.
	 *
	 * @param string $key The option key.
	 * @return string The prefixed option key.
	 */
	private static function option_name( string $key ): string {
		return self::PREFIX . $key;
	}
}
