<?php
/**
 * Dynamic image URL generation.
 *
 * @package OGD
 */

namespace OGD;

use WP_Post;
use WP_Term;
use WP_User;

class ImageGenerator {
	private const CDN_BASE_URL = 'https://cdn.ogdynamic.com/d/';

	protected static ?array $query = null;

	protected static ?string $template_id = null;

	public static function get_template_key(): ?string {
		if ( is_category() ) {
			$key = 'category';
		} elseif ( is_tag() ) {
			$key = 'tag';
		} elseif ( is_author() ) {
			$key = 'author';
		} elseif ( is_date() ) {
			$key = 'date';
		} elseif ( is_search() ) {
			$key = 'search';
		} elseif ( is_front_page() ) {
			$key = 'home';
		} elseif ( is_home() ) {
			$key = 'blog';
		} elseif ( is_singular( 'product' ) ) {
			$key = 'product';
		} elseif ( is_singular( 'post' ) ) {
			$key = 'post';
		} elseif ( is_singular( 'page' ) ) {
			$key = 'page';
		} else {
			$key = null;
		}

		$key = apply_filters( 'ogdynamic_template_key', $key );

		return is_string( $key ) && '' !== $key ? sanitize_key( $key ) : null;
	}

	public static function has_generated_image(): bool {
		$attrs = self::get_attrs();

		if ( null === $attrs ) {
			return false;
		}

		self::$query = $attrs;

		return true;
	}

	protected static function get_template_url(): string {
		return self::CDN_BASE_URL . self::$template_id;
	}

	public static function get_image_url(): string {
		$url = self::get_template_url();

		if ( empty( self::$query ) ) {
			return $url;
		}

		return $url . '?' . http_build_query( self::$query );
	}

	public static function get_twitter_image_url(): string {
		return self::get_template_url() . '?' . http_build_query(
			array_merge(
				self::$query,
				array(
					'config' => array(
						'intent' => 'twitter',
					),
				)
			)
		);
	}

	public static function get_attrs(): ?array {
		$mapping = self::get_current_mapping();

		if ( null === $mapping ) {
			return null;
		}

		return self::get_mapping_attrs( $mapping['key'], $mapping['mapping'] );
	}

	public static function build_image_url( string $template_id, array $params ): string {
		$base_url = (string) apply_filters( 'ogdynamic_image_base_url', self::CDN_BASE_URL );
		$base     = trailingslashit( $base_url ) . rawurlencode( $template_id );
		$query    = http_build_query( self::filter_params( $params ), '', '&', PHP_QUERY_RFC3986 );

		return $query ? $base . '?' . $query : $base;
	}

	private static function get_current_mapping(): ?array {
		$key = self::get_template_key();

		if ( null === $key ) {
			$key = 'default';
		}

		return self::get_mapping_for_key( $key );
	}

	private static function get_mapping_for_key( string $key ): ?array {
		$mapping = Template::get_mapping( $key );

        if ( empty($mapping) && 'default' !== $key ) {
            $mapping = Template::get_mapping( 'default' );
        }

		if ( empty( $mapping ) ) {
			return null;
		}

		self::$template_id = $mapping['template_id'];

		return array(
			'key'     => $key,
			'mapping' => $mapping,
		);
	}

	private static function get_mapping_attrs( string $key, array $mapping ): array {
		$method = 'get_' . $key . '_data';
		if ( ! method_exists( self::class, $method ) ) {
			return array();
		}

		return self::$method( $mapping );
	}

	private static function get_mapping_attrs_for_object( WP_Post $post, array $mapping ): array {
		return self::filter_params( self::resolve_mapping( $post, $mapping ) );
	}

	/**
	 * Default template data.
	 * Only supports site-wide values like site_name and site_tagline.
	 *
	 * @param array $mapping Template field mapping.
	 * @return array Resolved field values.
	 */
	private static function get_default_data( array $mapping ): array {
		return self::resolve_archive_mapping( $mapping );
	}

	/**
	 * Single post template data.
	 *
	 * @param array $mapping Template field mapping.
	 * @return array Resolved field values.
	 */
	private static function get_post_data( array $mapping ): array {
		$post = get_post();
		if ( ! $post ) {
			return array();
		}
		return self::resolve_mapping( $post, $mapping );
	}

	/**
	 * Page template data.
	 *
	 * @param array $mapping Template field mapping.
	 * @return array Resolved field values.
	 */
	private static function get_page_data( array $mapping ): array {
		$post = get_post();
		if ( ! $post ) {
			return array();
		}
		return self::resolve_mapping( $post, $mapping );
	}

	/**
	 * Homepage template data.
	 *
	 * @param array $mapping Template field mapping.
	 * @return array Resolved field values.
	 */
	private static function get_home_data( array $mapping ): array {
		return self::resolve_archive_mapping( $mapping );
	}

	/**
	 * Blog listing page template data.
	 *
	 * @param array $mapping Template field mapping.
	 * @return array Resolved field values.
	 */
	private static function get_blog_data( array $mapping ): array {
		return self::resolve_archive_mapping( $mapping );
	}

	/**
	 * Category archive template data.
	 *
	 * @param array $mapping Template field mapping.
	 * @return array Resolved field values.
	 */
	private static function get_category_data( array $mapping ): array {
		$term = get_queried_object();
		if ( ! $term ) {
			return array();
		}
		return self::resolve_mapping( $term, $mapping );
	}

	/**
	 * Tag archive template data.
	 *
	 * @param array $mapping Template field mapping.
	 * @return array Resolved field values.
	 */
	private static function get_tag_data( array $mapping ): array {
		$term = get_queried_object();
		if ( ! $term ) {
			return array();
		}
		return self::resolve_mapping( $term, $mapping );
	}

	/**
	 * Author archive template data.
	 *
	 * @param array $mapping Template field mapping.
	 * @return array Resolved field values.
	 */
	private static function get_author_data( array $mapping ): array {
		$term = get_queried_object();
		if ( ! $term ) {
			return array();
		}
		return self::resolve_mapping( $term, $mapping );
	}

	/**
	 * Date archive template data.
	 *
	 * @param array $mapping Template field mapping.
	 * @return array Resolved field values.
	 */
	private static function get_date_data( array $mapping ): array {
		return self::resolve_archive_mapping( $mapping );
	}

	/**
	 * Search results template data.
	 *
	 * @param array $mapping Template field mapping.
	 * @return array Resolved field values.
	 */
	private static function get_search_data( array $mapping ): array {
		return self::resolve_archive_mapping( $mapping );
	}

	/**
	 * WooCommerce product template data.
	 *
	 * @param array $mapping Template field mapping.
	 * @return array Resolved field values.
	 */
	private static function get_product_data( array $mapping ): array {
		$post = get_post();
		if ( ! $post ) {
			return array();
		}
		return self::resolve_mapping( $post, $mapping );
	}

	/**
	 * Resolve field values using a post or term object.
	 *
	 * @param WP_Post|WP_Term|WP_User|null $source_object  Post, term, or user object.
	 * @param array                        $mapping  Template field mapping.
	 * @return array Resolved field values keyed by attribute name.
	 */
	private static function resolve_mapping( $source_object, array $mapping ): array {
		$params = array();
		foreach ( $mapping['map'] ?? array() as $item ) {
			$attr_key = (string) ( $item['attr_key'] ?? '' );
			$key      = (string) ( $item['key'] ?? '' );
			if ( '' === $attr_key || '' === $key ) {
				continue;
			}
			$params[ $attr_key ] = self::resolve_field_value( $source_object, $key );
		}
		return self::filter_params( $params );
	}

	/**
	 * Resolve field values for archive pages (home, blog, date, search).
	 * Archive pages don't have a specific object, only site-wide data.
	 *
	 * @param array $mapping Template field mapping.
	 * @return array Resolved field values keyed by attribute name.
	 */
	private static function resolve_archive_mapping( array $mapping ): array {
		$params = array();
		foreach ( $mapping['map'] ?? array() as $item ) {
			$attr_key = (string) ( $item['attr_key'] ?? '' );
			$key      = (string) ( $item['key'] ?? '' );
			if ( '' === $attr_key || '' === $key ) {
				continue;
			}
			$params[ $attr_key ] = self::resolve_archive_field( $key );
		}
		return self::filter_params( $params );
	}

	/**
	 * Dispatch field resolution based on object type.
	 *
	 * @param WP_Post|WP_Term|WP_User|null $source_object Post, term, user, or null object.
	 * @param string                       $source  Field source identifier.
	 * @return mixed Resolved field value.
	 */
	private static function resolve_field_value( $source_object, string $source ) {
		if ( $source_object instanceof WP_Post ) {
			return self::resolve_post_field( $source_object, $source );
		}
		if ( $source_object instanceof WP_Term ) {
			return self::resolve_term_field( $source_object, $source );
		}
		if ( $source_object instanceof WP_User ) {
			return self::resolve_user_field( $source_object, $source );
		}
		return '';
	}

	/**
	 * Resolve field values for WordPress post objects.
	 *
	 * @param WP_Post $post   Post object.
	 * @param string  $source  Field source identifier.
	 * @return mixed Resolved field value.
	 */
	private static function resolve_post_field( WP_Post $post, string $source ) {
		$value = '';
		switch ( $source ) {
			case 'post_title':
				$value = get_the_title( $post );
				break;
			case 'excerpt':
				$value = get_the_excerpt( $post );
				break;
			case 'trimmed_content':
				$value = wp_trim_words( wp_strip_all_tags( $post->post_content ), 24 );
				break;
			case 'featured_image':
				$image_url = get_the_post_thumbnail_url( $post, 'full' );
				$value     = false !== $image_url ? $image_url : '';
				break;
			case 'author_name':
				$value = get_the_author_meta( 'display_name', (int) $post->post_author );
				break;
			case 'published_date':
				$value = get_the_date( '', $post );
				break;
			case 'modified_date':
				$value = get_the_modified_date( '', $post );
				break;
			case 'category':
				$value = implode( ', ', self::term_names( $post->ID, 'category' ) );
				break;
			case 'tags':
				$value = self::term_names( $post->ID, 'post_tag' );
				break;
			case 'site_name':
				$value = get_bloginfo( 'name' );
				break;
			case 'site_tagline':
				$value = get_bloginfo( 'description' );
				break;
			case 'product_short_description':
				$value = self::product_value( $post, 'short_description' );
				break;
			case 'product_price':
				$value = self::product_value( $post, 'price_html' );
				break;
			case 'regular_price':
				$value = self::product_value( $post, 'regular_price' );
				break;
			case 'sale_price':
				$value = self::product_value( $post, 'sale_price' );
				break;
			case 'currency':
				$value = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '';
				break;
			case 'sku':
				$value = self::product_value( $post, 'sku' );
				break;
			case 'product_category':
				$value = implode( ', ', self::term_names( $post->ID, 'product_cat' ) );
				break;
			case 'product_tags':
				$value = self::term_names( $post->ID, 'product_tag' );
				break;
			case 'product_attributes':
				$value = self::product_attributes( $post );
				break;
			case 'stock_status':
				$value = self::product_value( $post, 'stock_status' );
				break;
			case 'rating':
				$value = self::product_value( $post, 'rating' );
				break;
			case 'review_count':
				$value = self::product_value( $post, 'review_count' );
				break;
		}
		return $value;
	}

	/**
	 * Resolve field values for WordPress term objects.
	 *
	 * @param WP_Term $term   Term object.
	 * @param string  $source  Field source identifier.
	 * @return string Resolved field value.
	 */
	private static function resolve_term_field( WP_Term $term, string $source ): string {
		$value = '';
		switch ( $source ) {
			case 'category':
			case 'tag':
				$value = $term->name;
				break;
			case 'site_name':
				$value = get_bloginfo( 'name' );
				break;
			case 'site_tagline':
				$value = get_bloginfo( 'description' );
				break;
		}
		return (string) $value;
	}

	/**
	 * Resolve field values for WordPress user objects.
	 *
	 * @param WP_User $user   User object.
	 * @param string  $source Field source identifier.
	 * @return string Resolved field value.
	 */
	private static function resolve_user_field( WP_User $user, string $source ): string {
		$value = '';
		switch ( $source ) {
			case 'author_name':
				$value = $user->display_name;
				break;
			case 'site_name':
				$value = get_bloginfo( 'name' );
				break;
			case 'site_tagline':
				$value = get_bloginfo( 'description' );
				break;
		}
		return (string) $value;
	}

	/**
	 * Resolve field values for archive pages.
	 *
	 * @param string $source Field source identifier.
	 * @return string Resolved field value.
	 */
	private static function resolve_archive_field( string $source ): string {
		$value = '';
		switch ( $source ) {
			case 'site_name':
				$value = get_bloginfo( 'name' );
				break;
			case 'site_tagline':
				$value = get_bloginfo( 'description' );
				break;
		}
		return (string) $value;
	}

	/**
	 * Get WooCommerce product field value.
	 *
	 * @param WP_Post $post   Post object.
	 * @param string  $field  Field identifier.
	 * @return string Field value.
	 */
	private static function product_value( WP_Post $post, string $field ): string {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '';
		}
		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			return '';
		}
		switch ( $field ) {
			case 'short_description':
				return wp_strip_all_tags( $product->get_short_description() );
			case 'price_html':
				return wp_strip_all_tags( $product->get_price_html() );
			case 'regular_price':
				return $product->get_regular_price();
			case 'sale_price':
				return $product->get_sale_price();
			case 'sku':
				return $product->get_sku();
			case 'stock_status':
				return (string) $product->get_stock_status();
			case 'rating':
				return (string) $product->get_average_rating();
			case 'review_count':
				return (string) $product->get_review_count();
		}
		return '';
	}

	private static function product_attributes( WP_Post $post ): array {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return array();
		}

		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			return array();
		}

		$attributes = array();
		foreach ( $product->get_attributes() as $attribute ) {
			$name  = $attribute->get_name();
			$label = function_exists( 'wc_attribute_label' ) ? wc_attribute_label( $name, $product ) : $name;
			$value = self::product_attribute_value( $product->get_id(), $attribute );

			if ( '' === $value ) {
				continue;
			}

			$attributes[] = array(
				'label' => $label,
				'value' => $value,
			);
		}

		return $attributes;
	}

	private static function product_attribute_value( int $product_id, $attribute ): string {
		if ( $attribute->is_taxonomy() && function_exists( 'wc_get_product_terms' ) ) {
			$terms = wc_get_product_terms( $product_id, $attribute->get_name(), array( 'fields' => 'names' ) );
			if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
				return '';
			}

			return implode( ', ', $terms );
		}

		return implode(
			', ',
			array_map(
				'wc_clean',
				array_map( 'strval', $attribute->get_options() )
			)
		);
	}

	private static function term_names( int $post_id, string $taxonomy ): array {
		$terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		return array_values( array_map( 'strval', $terms ) );
	}

	private static function filter_params( array $params ): array {
		return array_filter(
			$params,
			static function ( $value ): bool {
				if ( is_array( $value ) ) {
					return array() !== $value;
				}

				return '' !== trim( (string) $value );
			}
		);
	}
}
