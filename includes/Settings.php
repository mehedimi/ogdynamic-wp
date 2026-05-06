<?php
/**
 * Plugin settings access.
 *
 * @package OGD
 */

namespace OGD;

class Settings {
	public const PREFIX = 'ogdy_';
    public const TEMPLATE_KEY_PREFIX = 'mapping_';

	public static function get( string $key, $default = false ) {
		return get_option( self::option_name( $key ), $default );
	}

	public static function update( string $key, $value, bool $autoload = false ): bool {
		return update_option( self::option_name( $key ), $value, $autoload );
	}

	public static function get_api_key(): string {
		return (string) self::get( 'api_key', '' );
	}

	public static function available_post_types(): array {
		$items = array(
			array(
				'name'  => 'default',
				'label' => 'Default',
			),
		);

		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		foreach ( $post_types as $post_type ) {
			if ( 'attachment' === $post_type->name ) {
				continue;
			}

			$items[] = array(
				'name'  => $post_type->name,
				'label' => $post_type->label,
			);
		}

		return $items;
	}

    public static function unwrap_template_key(string  $full_key)
    {
        return substr( $full_key, strlen( self::PREFIX . self::TEMPLATE_KEY_PREFIX ));
    }

    public static function wrap_template_key(string  $key): string
    {
        return self::PREFIX . self::TEMPLATE_KEY_PREFIX . $key;
    }

	private static function option_name( string $key ): string {
		return self::PREFIX . $key;
	}
}
