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

	public static function detect_seo_plugin(): string {
		if ( defined( 'WPSEO_VERSION' ) ) {
			return 'Yoast SEO';
		}
		if ( defined( 'RANK_MATH_VERSION' ) ) {
			return 'Rank Math';
		}
		if ( defined( 'AIOSEO_VERSION' ) ) {
			return 'All in One SEO';
		}
		if ( defined( 'SEOPRESS_VERSION' ) ) {
			return 'SEOPress';
		}

		return 'None detected';
	}

	public static function eco_plugins(): array {
		$plugins = array();

		if ( class_exists( 'WooCommerce' ) ) {
			$plugins[] = 'woocommerce';
		}
		if ( defined( 'EDD_VERSION' ) || class_exists( 'Easy_Digital_Downloads' ) ) {
			$plugins[] = 'edd';
		}

		/**
		 * Filter the detected ecommerce plugins for the admin boot payload.
		 *
		 * @param array<int, string> $plugins Detected plugin slugs.
		 */
		return array_values( array_unique( (array) apply_filters( 'ogdynamic_eco_plugins', $plugins ) ) );
	}

	public static function is_woocommerce_active(): bool {
		return in_array( 'woocommerce', self::eco_plugins(), true );
	}

	private static function option_name( string $key ): string {
		return self::PREFIX . $key;
	}
}
