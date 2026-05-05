<?php
/**
 * Plugin settings access.
 *
 * @package OGD
 */

namespace OGD;

class Settings {
	public const PREFIX = 'ogdy_';

	public static function get( string $key, $default = false ) {
		return get_option( self::option_name( $key ), $default );
	}

	public static function update( string $key, $value, bool $autoload = false ): bool {
		return update_option( self::option_name( $key ), $value, $autoload );
	}

	private static function option_name( string $key ): string {
		return self::PREFIX . $key;
	}
}
