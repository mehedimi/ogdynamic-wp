<?php
/**
 * Dynamic image URL generation.
 *
 * @package OGD
 */

namespace OGD;

class ImageGenerator {

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
		} elseif ( is_singular( 'post' ) ) {
			$key = 'post';
		} elseif ( is_singular( 'page' ) ) {
			$key = 'page';
		} else {
			$key = null;
		}

		return apply_filters( 'ogdynamic_template_key', $key );
	}

	public static function get_attrs(): ?array {
		$key = self::get_template_key();
		if ( null === $key ) {
			return null;
		}

		$mapping = Template::get_mapping( $key );
		if ( empty( $mapping ) ) {
			return null;
		}

		$method = 'get_' . $key . '_data';
		if ( ! method_exists( self::class, $method ) ) {
			return null;
		}

		return self::$method( $mapping );
	}

	/**
	 * Default template data.
	 * Only supports site-wide values like site_name and site_tagline.
	 *
	 * @param array $mapping Template field mapping.
	 * @return array Resolved field values.
	 */
	private static function get_default_data( array $mapping ): array {
		return self::resolve_mapping( null, $mapping );
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
	 * @param WP_Post|WP_Term|null $object  Post or term object.
	 * @param array                $mapping  Template field mapping.
	 * @return array Resolved field values keyed by attribute name.
	 */
	private static function resolve_mapping( $object, array $mapping ): array {
		$params = array();
		foreach ( $mapping['map'] ?? array() as $item ) {
			$attr_key  = (string) ( $item['attr_key'] ?? '' );
			$key      = (string) ( $item['key'] ?? '' );
			if ( '' === $attr_key || '' === $key ) {
				continue;
			}
			$params[ $attr_key ] = self::resolve_field_value( $object, $key );
		}
		return $params;
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
			$attr_key  = (string) ( $item['attr_key'] ?? '' );
			$key      = (string) ( $item['key'] ?? '' );
			if ( '' === $attr_key || '' === $key ) {
				continue;
			}
			$params[ $attr_key ] = self::resolve_archive_field( $key );
		}
		return $params;
	}

	/**
	 * Dispatch field resolution based on object type.
	 *
	 * @param WP_Post|WP_Term|null $object Post, term, or null object.
	 * @param string               $source  Field source identifier.
	 * @return string Resolved field value.
	 */
	private static function resolve_field_value( $object, string $source ): string {
		if ( $object instanceof \WP_Post ) {
			return self::resolve_post_field( $object, $source );
		}
		if ( $object instanceof \WP_Term ) {
			return self::resolve_term_field( $object, $source );
		}
		return '';
	}

	/**
	 * Resolve field values for WordPress post objects.
	 *
	 * @param WP_Post $post   Post object.
	 * @param string  $source  Field source identifier.
	 * @return string Resolved field value.
	 */
	private static function resolve_post_field( \WP_Post $post, string $source ): string {
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
				$value = get_the_post_thumbnail_url( $post, 'full' ) ?: '';
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
				$value = implode( ', ', wp_get_post_terms( $post->ID, 'category', array( 'fields' => 'names' ) ) );
				break;
			case 'tags':
				$value = implode( ', ', wp_get_post_terms( $post->ID, 'post_tag', array( 'fields' => 'names' ) ) );
				break;
			case 'permalink':
				$value = get_permalink( $post );
				break;
			case 'site_name':
				$value = get_bloginfo( 'name' );
				break;
			case 'site_tagline':
				$value = get_bloginfo( 'description' );
				break;
			case 'site_url':
				$value = home_url( '/' );
				break;
			case 'custom_meta':
				$value = '';
				break;
			case 'product_title':
				$value = get_the_title( $post );
				break;
			case 'product_short_description':
				$value = self::product_value( $post, 'short_description' );
				break;
			case 'product_image':
				$value = get_the_post_thumbnail_url( $post, 'full' ) ?: '';
				break;
			case 'product_gallery_image':
				$value = self::product_value( $post, 'gallery_image' );
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
				$value = implode( ', ', wp_get_post_terms( $post->ID, 'product_cat', array( 'fields' => 'names' ) ) );
				break;
			case 'product_tags':
				$value = implode( ', ', wp_get_post_terms( $post->ID, 'product_tag', array( 'fields' => 'names' ) ) );
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
			case 'product_url':
				$value = get_permalink( $post );
				break;
		}
		return (string) $value;
	}

	/**
	 * Resolve field values for WordPress term objects.
	 *
	 * @param WP_Term $term   Term object.
	 * @param string  $source  Field source identifier.
	 * @return string Resolved field value.
	 */
	private static function resolve_term_field( \WP_Term $term, string $source ): string {
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
			case 'site_url':
				$value = home_url( '/' );
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
			case 'site_url':
				$value = home_url( '/' );
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
	private static function product_value( \WP_Post $post, string $field ): string {
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
			case 'gallery_image':
				$gallery_ids = $product->get_gallery_image_ids();
				return isset( $gallery_ids[0] ) ? (string) wp_get_attachment_image_url( (int) $gallery_ids[0], 'full' ) : '';
			case 'price_html':
				return wp_strip_all_tags( $product->get_price_html() );
			case 'regular_price':
				return $product->get_regular_price();
			case 'sale_price':
				return $product->get_sale_price();
			case 'sku':
				return $product->get_sku();
			case 'stock_status':
				return $product->get_stock_status();
			case 'rating':
				return (string) $product->get_average_rating();
			case 'review_count':
				return (string) $product->get_review_count();
		}
		return '';
	}
}
