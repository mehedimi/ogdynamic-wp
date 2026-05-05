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

	public static function defaults(): array {
		return array(
			'api_key'          => '',
			'connection'       => array(
				'status'          => 'disconnected',
				'account_label'   => '',
				'plan'            => '',
				'usage'           => null,
				'last_checked_at' => '',
				'last_error'      => '',
			),
			'templates'        => array(),
			'template_updated' => '',
			'defaults'         => array(
				'global_template'      => '',
				'post_templates'       => array(),
				'product_template'     => '',
				'homepage_template'    => '',
				'archive_template'     => '',
				'fallback_image_url'   => '',
				'fallback_mode'        => 'featured_image',
				'meta_mode'            => 'image_only',
				'seo_mode'             => 'auto',
				'enabled_post_types'   => array( 'post', 'page' ),
				'editor_overrides'     => true,
				'cleanup_on_uninstall' => false,
			),
			'mappings'         => array(),
			'woocommerce'      => array(
				'enabled'                   => true,
				'variable_product_behavior' => 'parent',
			),
		);
	}

	public static function all(): array {
		$settings = self::get( 'settings', array() );

		return self::merge_recursive_distinct( self::defaults(), is_array( $settings ) ? $settings : array() );
	}

	public static function save( array $settings ): array {
		if ( isset( $settings['api_key'] ) && false !== strpos( (string) $settings['api_key'], '•' ) ) {
			$current             = self::get( 'settings', array() );
			$settings['api_key'] = is_array( $current ) ? (string) ( $current['api_key'] ?? '' ) : '';
		}

		$merged = self::merge_recursive_distinct( self::defaults(), self::sanitize( $settings ) );
		self::update( 'settings', $merged, false );

		return $merged;
	}

	public static function get_public_settings(): array {
		$settings = self::all();
		$settings['api_key'] = '' !== $settings['api_key'] ? '••••••••••••' : '';

		return $settings;
	}

	public static function clear_cache(): void {
		delete_transient( self::PREFIX . 'template_cache' );
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( 'ogdynamic' );
		}
	}

	public static function available_post_types(): array {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$items      = array();

		foreach ( $post_types as $post_type ) {
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

	private static function sanitize( array $settings ): array {
		$settings['api_key'] = isset( $settings['api_key'] ) ? sanitize_text_field( wp_unslash( $settings['api_key'] ) ) : '';

		return self::sanitize_mixed( $settings );
	}

	private static function sanitize_mixed( $value ) {
		if ( is_array( $value ) ) {
			$clean = array();
			foreach ( $value as $key => $item ) {
				$clean[ sanitize_key( (string) $key ) ] = self::sanitize_mixed( $item );
			}
			return $clean;
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}

		return sanitize_text_field( wp_unslash( (string) $value ) );
	}

	private static function merge_recursive_distinct( array $defaults, array $overrides ): array {
		foreach ( $overrides as $key => $value ) {
			if ( is_array( $value ) && isset( $defaults[ $key ] ) && is_array( $defaults[ $key ] ) ) {
				$defaults[ $key ] = self::merge_recursive_distinct( $defaults[ $key ], $value );
				continue;
			}
			$defaults[ $key ] = $value;
		}

		return $defaults;
	}
}
