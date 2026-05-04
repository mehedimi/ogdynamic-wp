<?php
/**
 * Plugin settings access.
 *
 * @package OGD
 */

namespace OGD;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {
	public const OPTION = 'ogd_settings';
	public const TEMPLATE_CACHE = 'ogd_template_cache';
	public const GENERATED_CACHE_PREFIX = 'ogd_generated_';

	public function defaults(): array {
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

	public function get(): array {
		$settings = get_option( self::OPTION, array() );

		return $this->merge_recursive_distinct( $this->defaults(), is_array( $settings ) ? $settings : array() );
	}

	public function update( array $settings ): array {
		if ( isset( $settings['api_key'] ) && false !== strpos( (string) $settings['api_key'], '•' ) ) {
			$current             = get_option( self::OPTION, array() );
			$settings['api_key'] = is_array( $current ) ? (string) ( $current['api_key'] ?? '' ) : '';
		}

		$merged = $this->merge_recursive_distinct( $this->defaults(), $this->sanitize( $settings ) );
		update_option( self::OPTION, $merged, false );

		return $merged;
	}

	public function get_api_key(): string {
		$settings = $this->get();

		return (string) $settings['api_key'];
	}

	public function get_public_settings(): array {
		$settings = $this->get();
		$settings['api_key'] = '' !== $settings['api_key'] ? '••••••••••••' : '';

		return $settings;
	}

	public function clear_cache(): void {
		delete_transient( self::TEMPLATE_CACHE );
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( 'ogdynamic' );
		}
	}

	public function available_post_types(): array {
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

	public function detect_seo_plugin(): string {
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

	public function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	private function sanitize( array $settings ): array {
		$settings['api_key'] = isset( $settings['api_key'] ) ? sanitize_text_field( wp_unslash( $settings['api_key'] ) ) : '';

		return $this->sanitize_mixed( $settings );
	}

	private function sanitize_mixed( $value ) {
		if ( is_array( $value ) ) {
			$clean = array();
			foreach ( $value as $key => $item ) {
				$clean[ sanitize_key( (string) $key ) ] = $this->sanitize_mixed( $item );
			}
			return $clean;
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}

		return sanitize_text_field( wp_unslash( (string) $value ) );
	}

	private function merge_recursive_distinct( array $defaults, array $overrides ): array {
		foreach ( $overrides as $key => $value ) {
			if ( is_array( $value ) && isset( $defaults[ $key ] ) && is_array( $defaults[ $key ] ) ) {
				$defaults[ $key ] = $this->merge_recursive_distinct( $defaults[ $key ], $value );
				continue;
			}
			$defaults[ $key ] = $value;
		}

		return $defaults;
	}
}
