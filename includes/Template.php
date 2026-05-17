<?php
/**
 * Template configuration management.
 *
 * @package OGD
 */

namespace OGDynamic;

/**
 * Class Template
 *
 * Manages template mappings for different post types and archive pages.
 * Handles source configuration for dynamic content replacement in OG image generation.
 */
class Template {

	/**
	 * Post type key prefix for option names.
	 *
	 * @var string
	 */
	public const POST_TYPE_KEY_PREFIX = 'mapping_';

	/**
	 * Cached sources configuration.
	 *
	 * @var array|null
	 */
	private static ?array $sources_config = null;

	/**
	 * Gets all available mapping sources.
	 *
	 * @return array Associative array of post types to source arrays.
	 */
	private static function get_sources(): array {
		if ( null === self::$sources_config ) {
			$site_sources = array(
				array(
					'key'   => 'site_name',
					'label' => __( 'Site name', 'ogdynamic' ),
				),
				array(
					'key'   => 'site_tagline',
					'label' => __( 'Site tagline', 'ogdynamic' ),
				),
			);

			$post_sources = array(
				array(
					'key'   => 'post_title',
					'label' => __( 'Post title', 'ogdynamic' ),
				),
				array(
					'key'   => 'excerpt',
					'label' => __( 'Excerpt', 'ogdynamic' ),
				),
				array(
					'key'   => 'trimmed_content',
					'label' => __( 'Trimmed content', 'ogdynamic' ),
				),
				array(
					'key'   => 'featured_image',
					'label' => __( 'Featured image', 'ogdynamic' ),
				),
				array(
					'key'   => 'author_name',
					'label' => __( 'Author name', 'ogdynamic' ),
				),
				array(
					'key'   => 'published_date',
					'label' => __( 'Published date', 'ogdynamic' ),
				),
				array(
					'key'   => 'modified_date',
					'label' => __( 'Modified date', 'ogdynamic' ),
				),
				array(
					'key'   => 'category',
					'label' => __( 'Category', 'ogdynamic' ),
				),
				array(
					'key'   => 'tags',
					'label' => __( 'Tags', 'ogdynamic' ),
				),
				...$site_sources,
			);

			$page_sources = array(
				array(
					'key'   => 'post_title',
					'label' => __( 'Page title', 'ogdynamic' ),
				),
				array(
					'key'   => 'excerpt',
					'label' => __( 'Excerpt', 'ogdynamic' ),
				),
				array(
					'key'   => 'trimmed_content',
					'label' => __( 'Trimmed content', 'ogdynamic' ),
				),
				array(
					'key'   => 'featured_image',
					'label' => __( 'Featured image', 'ogdynamic' ),
				),
				array(
					'key'   => 'author_name',
					'label' => __( 'Author name', 'ogdynamic' ),
				),
				array(
					'key'   => 'published_date',
					'label' => __( 'Published date', 'ogdynamic' ),
				),
				array(
					'key'   => 'modified_date',
					'label' => __( 'Modified date', 'ogdynamic' ),
				),
				...$site_sources,
			);

			$product_sources = array(
				array(
					'key'   => 'post_title',
					'label' => __( 'Product title', 'ogdynamic' ),
				),
				array(
					'key'   => 'excerpt',
					'label' => __( 'Product excerpt', 'ogdynamic' ),
				),
				array(
					'key'   => 'trimmed_content',
					'label' => __( 'Product description', 'ogdynamic' ),
				),
				array(
					'key'   => 'featured_image',
					'label' => __( 'Product image', 'ogdynamic' ),
				),
				array(
					'key'   => 'author_name',
					'label' => __( 'Product author', 'ogdynamic' ),
				),
				array(
					'key'   => 'published_date',
					'label' => __( 'Published date', 'ogdynamic' ),
				),
				array(
					'key'   => 'modified_date',
					'label' => __( 'Modified date', 'ogdynamic' ),
				),
				array(
					'key'   => 'product_short_description',
					'label' => __( 'Product short description', 'ogdynamic' ),
				),
				array(
					'key'   => 'product_price',
					'label' => __( 'Product price', 'ogdynamic' ),
				),
				array(
					'key'   => 'regular_price',
					'label' => __( 'Regular price', 'ogdynamic' ),
				),
				array(
					'key'   => 'sale_price',
					'label' => __( 'Sale price', 'ogdynamic' ),
				),
				array(
					'key'   => 'currency',
					'label' => __( 'Currency', 'ogdynamic' ),
				),
				array(
					'key'   => 'sku',
					'label' => __( 'SKU', 'ogdynamic' ),
				),
				array(
					'key'   => 'product_category',
					'label' => __( 'Product category', 'ogdynamic' ),
				),
				array(
					'key'   => 'product_tags',
					'label' => __( 'Product tags', 'ogdynamic' ),
				),
				array(
					'key'   => 'product_attributes',
					'label' => __( 'Product attributes', 'ogdynamic' ),
				),
				array(
					'key'   => 'stock_status',
					'label' => __( 'Stock status', 'ogdynamic' ),
				),
				array(
					'key'   => 'rating',
					'label' => __( 'Rating', 'ogdynamic' ),
				),
				array(
					'key'   => 'review_count',
					'label' => __( 'Review count', 'ogdynamic' ),
				),
				...$site_sources,
			);

			self::$sources_config = array(
				'default'  => $site_sources,
				'post'     => $post_sources,
				'page'     => $page_sources,
				'product'  => $product_sources,
				'home'     => $site_sources,
				'blog'     => $site_sources,
				'category' => array(
					array(
						'key'   => 'category',
						'label' => __( 'Category name', 'ogdynamic' ),
					),
					...$site_sources,
				),
				'tag'      => array(
					array(
						'key'   => 'tag',
						'label' => __( 'Tag name', 'ogdynamic' ),
					),
					...$site_sources,
				),
				'author'   => array(
					array(
						'key'   => 'author_name',
						'label' => __( 'Author name', 'ogdynamic' ),
					),
					...$site_sources,
				),
				'date'     => $site_sources,
				'search'   => $site_sources,
			);
		}

		return self::$sources_config;
	}

	/**
	 * Gets mapping sources for a specific post type.
	 *
	 * @param string $post_type The post type slug.
	 * @return array{
	 *   key: string,
	 *   label: string
	 * }[] Array of available sources for the post type.
	 */
	public static function get_mapping_sources( string $post_type ): array {
		$sources = self::get_sources();

		if ( isset( $sources[ $post_type ] ) ) {
			return $sources[ $post_type ];
		}

		$filtered_sources = (array) apply_filters( 'ogdynamic_mapping_sources', $sources );

		return $filtered_sources[ $post_type ] ?? array();
	}

	/**
	 * Gets available post types with their labels and descriptions.
	 *
	 * @return array Array of post type configurations.
	 */
	public static function available_post_types(): array {
		$post_types = array(
			array(
				'name'        => 'default',
				'label'       => __( 'Default', 'ogdynamic' ),
				'description' => __( 'OG image template for any post type without a specific template.', 'ogdynamic' ),
			),
			array(
				'name'        => 'post',
				'label'       => __( 'Single Post', 'ogdynamic' ),
				'description' => __( 'OG image template for single blog posts.', 'ogdynamic' ),
			),
			array(
				'name'        => 'page',
				'label'       => __( 'Page', 'ogdynamic' ),
				'description' => __( 'OG image template for pages.', 'ogdynamic' ),
			),
		);

		if ( self::is_woocommerce_active() ) {
			$post_types[] = array(
				'name'        => 'product',
				'label'       => __( 'Product', 'ogdynamic' ),
				'description' => __( 'OG image template for WooCommerce products.', 'ogdynamic' ),
			);
		}

		$archive_post_types = array(
			array(
				'name'        => 'home',
				'label'       => __( 'Homepage', 'ogdynamic' ),
				'description' => __( 'OG image template for the site homepage.', 'ogdynamic' ),
			),
			array(
				'name'        => 'blog',
				'label'       => __( 'Blog Page', 'ogdynamic' ),
				'description' => __( 'OG image template for the blog listing page.', 'ogdynamic' ),
			),
			array(
				'name'        => 'category',
				'label'       => __( 'Category Archive', 'ogdynamic' ),
				'description' => __( 'OG image template for category archive pages.', 'ogdynamic' ),
			),
			array(
				'name'        => 'tag',
				'label'       => __( 'Tag Archive', 'ogdynamic' ),
				'description' => __( 'OG image template for tag archive pages.', 'ogdynamic' ),
			),
			array(
				'name'        => 'author',
				'label'       => __( 'Author Archive', 'ogdynamic' ),
				'description' => __( 'OG image template for author archive pages.', 'ogdynamic' ),
			),
			array(
				'name'        => 'date',
				'label'       => __( 'Date Archive', 'ogdynamic' ),
				'description' => __( 'OG image template for date-based archive pages.', 'ogdynamic' ),
			),
			array(
				'name'        => 'search',
				'label'       => __( 'Search Results', 'ogdynamic' ),
				'description' => __( 'OG image template for search results pages.', 'ogdynamic' ),
			),
		);

		$post_types = array_merge( $post_types, $archive_post_types );

		return (array) apply_filters( 'ogdynamic_available_post_types', $post_types );
	}

	/**
	 * Checks if WooCommerce is active.
	 *
	 * @return bool True if WooCommerce is active, false otherwise.
	 */
	private static function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' ) || function_exists( 'WC' );
	}

	/**
	 * Gets the option name for a post type mapping.
	 *
	 * @param string $post_type The post type slug.
	 * @return string The full option name.
	 */
	public static function option_name( string $post_type ): string {
		return Settings::PREFIX . self::POST_TYPE_KEY_PREFIX . sanitize_key( $post_type );
	}

	/**
	 * Extracts post type from option name.
	 *
	 * @param string $option_name The full option name.
	 * @return string The post type slug.
	 */
	public static function unwrap_post_type( string $option_name ): string {
		return substr( $option_name, strlen( Settings::PREFIX . self::POST_TYPE_KEY_PREFIX ) );
	}

	/**
	 * Gets the mapping configuration for a post type.
	 *
	 * @param string $post_type The post type slug.
	 * @return array The mapping configuration array.
	 */
	public static function get_mapping( string $post_type ): array {
		return (array) get_option( self::option_name( $post_type ), array() );
	}

	/**
	 * Updates the mapping configuration for a post type.
	 *
	 * @param string $post_type The post type slug.
	 * @param array  $value     The new mapping configuration.
	 * @return bool True if updated successfully, false otherwise.
	 */
	public static function update_mapping( string $post_type, array $value ): bool {
		return update_option( self::option_name( $post_type ), $value, false );
	}

	/**
	 * Deletes the mapping configuration for a post type.
	 *
	 * @param string $post_type The post type slug.
	 * @return bool True if deleted successfully, false otherwise.
	 */
	public static function delete_mapping( string $post_type ): bool {
		return delete_option( self::option_name( $post_type ) );
	}

	/**
	 * Gets all activated post types with mappings.
	 *
	 * @return array Array of post type slugs.
	 */
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
