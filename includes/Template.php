<?php
/**
 * Template configuration management.
 *
 * @package OGD
 */

namespace OGD;

class Template {

	public const POST_TYPE_KEY_PREFIX = 'mapping_';

	public static function available_post_types(): array {
		$post_types = array(
			array(
				'name'        => 'post',
				'label'       => 'Single Post',
				'description' => 'OG image template for single blog posts.',
			),
			array(
				'name'        => 'page',
				'label'       => 'Page',
				'description' => 'OG image template for pages.',
			),
			array(
				'name'        => 'home',
				'label'       => 'Homepage',
				'description' => 'OG image template for the site homepage.',
			),
			array(
				'name'        => 'blog',
				'label'       => 'Blog Page',
				'description' => 'OG image template for the blog listing page.',
			),
			array(
				'name'        => 'category',
				'label'       => 'Category Archive',
				'description' => 'OG image template for category archive pages.',
			),
			array(
				'name'        => 'tag',
				'label'       => 'Tag Archive',
				'description' => 'OG image template for tag archive pages.',
			),
			array(
				'name'        => 'author',
				'label'       => 'Author Archive',
				'description' => 'OG image template for author archive pages.',
			),
			array(
				'name'        => 'date',
				'label'       => 'Date Archive',
				'description' => 'OG image template for date-based archive pages.',
			),
			array(
				'name'        => 'search',
				'label'       => 'Search Results',
				'description' => 'OG image template for search results pages.',
			),
		);

		return (array) apply_filters( 'ogdynamic_available_post_types', $post_types );
	}

	public static function option_name( string $post_type ): string {
		return Settings::PREFIX . self::POST_TYPE_KEY_PREFIX . sanitize_key( $post_type );
	}

	public static function unwrap_post_type( string $option_name ): string {
		return substr( $option_name, strlen( Settings::PREFIX . self::POST_TYPE_KEY_PREFIX ) );
	}

	public static function get_mapping( string $post_type ): array {
		return (array) get_option( self::option_name( $post_type ), array() );
	}

	public static function update_mapping( string $post_type, array $value ): bool {
		return update_option( self::option_name( $post_type ), $value, false );
	}

	public static function delete_mapping( string $post_type ): bool {
		return delete_option( self::option_name( $post_type ) );
	}

	public static function get_activated_post_types(): array {
		global $wpdb;

		$prefix = Settings::PREFIX . self::POST_TYPE_KEY_PREFIX;

		$keys = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name ASC",
				$wpdb->esc_like( $prefix ) . '%'
			)
		);

		return array_map( static function ( $key ) {
			return self::unwrap_post_type( $key );
		}, $keys );
	}
}