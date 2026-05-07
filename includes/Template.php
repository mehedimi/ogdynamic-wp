<?php
/**
 * Template configuration management.
 *
 * @package OGD
 */

namespace OGD;

class Template {

	public const POST_TYPE_KEY_PREFIX = 'mapping_';

	private static ?array $sources_config = null;

	private static function get_sources(): array {
		if ( null === self::$sources_config ) {
			$site_sources         = array(
				array(
					'key'   => 'site_name',
					'label' => 'Site name',
				),
				array(
					'key'   => 'site_tagline',
					'label' => 'Site tagline',
				),
			);
			$post_sources         = array(
				array(
					'key'   => 'post_title',
					'label' => 'Post title',
				),
				array(
					'key'   => 'excerpt',
					'label' => 'Excerpt',
				),
				array(
					'key'   => 'trimmed_content',
					'label' => 'Trimmed content',
				),
				array(
					'key'   => 'featured_image',
					'label' => 'Featured image',
				),
				array(
					'key'   => 'author_name',
					'label' => 'Author name',
				),
				array(
					'key'   => 'published_date',
					'label' => 'Published date',
				),
				array(
					'key'   => 'modified_date',
					'label' => 'Modified date',
				),
				array(
					'key'   => 'category',
					'label' => 'Category',
				),
				array(
					'key'   => 'tags',
					'label' => 'Tags',
				),
				...$site_sources,
			);
			$page_sources         = array(
				array(
					'key'   => 'post_title',
					'label' => 'Page title',
				),
				array(
					'key'   => 'excerpt',
					'label' => 'Excerpt',
				),
				array(
					'key'   => 'trimmed_content',
					'label' => 'Trimmed content',
				),
				array(
					'key'   => 'featured_image',
					'label' => 'Featured image',
				),
				array(
					'key'   => 'author_name',
					'label' => 'Author name',
				),
				array(
					'key'   => 'published_date',
					'label' => 'Published date',
				),
				array(
					'key'   => 'modified_date',
					'label' => 'Modified date',
				),
				...$site_sources,
			);
			self::$sources_config = array(
				'default'  => $site_sources,
				'post'     => $post_sources,
				'page'     => $page_sources,
				'product'  => array(
					...$post_sources,
					array(
						'key'   => 'product_short_description',
						'label' => 'Product short description',
					),
					array(
						'key'   => 'product_price',
						'label' => 'Product price',
					),
					array(
						'key'   => 'regular_price',
						'label' => 'Regular price',
					),
					array(
						'key'   => 'sale_price',
						'label' => 'Sale price',
					),
					array(
						'key'   => 'currency',
						'label' => 'Currency',
					),
					array(
						'key'   => 'sku',
						'label' => 'SKU',
					),
					array(
						'key'   => 'product_category',
						'label' => 'Product category',
					),
					array(
						'key'   => 'product_tags',
						'label' => 'Product tags',
					),
					array(
						'key'   => 'product_attributes',
						'label' => 'Product attributes',
					),
					array(
						'key'   => 'stock_status',
						'label' => 'Stock status',
					),
					array(
						'key'   => 'rating',
						'label' => 'Rating',
					),
					array(
						'key'   => 'review_count',
						'label' => 'Review count',
					),
				),
				'home'     => $site_sources,
				'blog'     => $site_sources,
				'category' => array(
					array(
						'key'   => 'category',
						'label' => 'Category name',
					),
					...$site_sources,
				),
				'tag'      => array(
					array(
						'key'   => 'tag',
						'label' => 'Tag name',
					),
					...$site_sources,
				),
				'author'   => array(
					array(
						'key'   => 'author_name',
						'label' => 'Author name',
					),
					...$site_sources,
				),
				'date'     => $site_sources,
				'search'   => $site_sources,
			);
		}

		return self::$sources_config;
	}

	public static function get_mapping_sources( string $post_type ): array {
		$sources = self::get_sources();

		if ( isset( $sources[ $post_type ] ) ) {
			return $sources[ $post_type ];
		}

		return (array) apply_filters( 'ogdynamic_mapping_sources', $sources )[ $post_type ] ?? array();
	}

	public static function available_post_types(): array {
		$post_types = array(
			array(
				'name'        => 'default',
				'label'       => 'Default',
				'description' => 'OG image template for any post type without a specific template.',
			),
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$keys = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name ASC",
				$wpdb->esc_like( $prefix ) . '%'
			)
		);

		return array_map(
			static function ( $key ) {
				return self::unwrap_post_type( $key );
			},
			$keys
		);
	}
}
