<?php
/**
 * Dynamic image URL generation.
 *
 * @package OGD
 */

namespace OGD;

use WP_Post;

class ImageGenerator {
	public function __construct() {}

	public static function generate_for_post( int $post_id ): array {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return self::empty_result( 'Post not found.' );
		}

		$override = self::get_override( $post_id );
		if ( 'disabled' === $override['mode'] ) {
			return self::empty_result( 'ogdynamic disabled for this content.' );
		}
		if ( '' !== $override['custom_image_url'] ) {
			return array(
				'url'        => esc_url_raw( $override['custom_image_url'] ),
				'templateId' => '',
				'params'     => array(),
				'fallback'   => false,
				'message'    => 'Using custom image override.',
			);
		}

		$template_id = self::resolve_template_id( $post, $override );
		if ( '' === $template_id ) {
			return self::fallback_result( $post_id, 'No template configured.' );
		}

		$params = self::resolve_post_params( $post, $template_id, $override );
		$url    = self::build_image_url( $template_id, $params );

		return array(
			'url'        => $url,
			'templateId' => $template_id,
			'params'     => $params,
			'fallback'   => false,
			'message'    => '',
		);
	}

	public static function build_image_url( string $template_id, array $params ): string {
		$base  = 'https://ogdynamic.com/api/og/' . rawurlencode( $template_id );
		$query = http_build_query( array_filter( $params, static fn( $value ) => '' !== (string) $value ), '', '&', PHP_QUERY_RFC3986 );

		return $query ? $base . '?' . $query : $base;
	}

	public static function sample_params(): array {
		return array(
			'title'       => get_bloginfo( 'name' ),
			'description' => get_bloginfo( 'description' ),
			'site_name'   => get_bloginfo( 'name' ),
			'url'         => home_url( '/' ),
		);
	}

	private static function resolve_template_id( WP_Post $post, array $override ): string {
		if ( '' !== $override['template_id'] ) {
			return $override['template_id'];
		}

		$template = self::get_post_type_template( $post->post_type );
		if ( '' !== $template['template_id'] ) {
			return $template['template_id'];
		}

		$default_template = self::get_post_type_template( 'default' );

		return $default_template['template_id'];
	}

private static function resolve_post_params( WP_Post $post, string $template_id, array $override ): array {
		$template = self::get_post_type_template( $post->post_type );
		if ( $template_id !== $template['template_id'] ) {
			$template = self::get_post_type_template( 'default' );
		}

		$mappings = $template['map'];
		$params   = array();

		if ( empty( $mappings ) ) {
			$params = array(
				'title'       => $override['custom_title'] ?: get_the_title( $post ),
				'description' => $override['custom_description'] ?: self::get_description( $post ),
				'image'       => get_the_post_thumbnail_url( $post, 'full' ) ?: '',
				'url'         => get_permalink( $post ),
				'site_name'   => get_bloginfo( 'name' ),
			);
		}

		foreach ( $mappings as $mapping ) {
			$attr_key  = (string) ( $mapping['attr_key'] ?? '' );
			$value_key = (string) ( $mapping['value_key'] ?? '' );

			if ( '' === $attr_key || '' === $value_key ) {
				continue;
			}

			$params[ $attr_key ] = self::resolve_field_value( $post, $value_key );
		}

		foreach ( $override['custom_params'] as $key => $value ) {
			if ( '' !== $key && '' !== $value ) {
				$params[ sanitize_key( $key ) ] = $value;
			}
		}

		if ( '' !== $override['custom_title'] ) {
			$params['title'] = $override['custom_title'];
		}
		if ( '' !== $override['custom_description'] ) {
			$params['description'] = $override['custom_description'];
		}

		return $params;
	}

	private function resolve_field_value( WP_Post $post, string $source ): string {
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
			case 'site_description':
				$value = get_bloginfo( 'description' );
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
			case 'gallery_image':
				$gallery_ids = $product->get_gallery_image_ids();
				return isset( $gallery_ids[0] ) ? (string) wp_get_attachment_image_url( (int) $gallery_ids[0], 'full' ) : '';
			case 'price_html':
				return wp_strip_all_tags( $product->get_price_html() );
			case 'regular_price':
				return (string) $product->get_regular_price();
			case 'sale_price':
				return (string) $product->get_sale_price();
			case 'sku':
				return (string) $product->get_sku();
			case 'stock_status':
				return (string) $product->get_stock_status();
			case 'rating':
				return (string) $product->get_average_rating();
			case 'review_count':
				return (string) $product->get_review_count();
		}

		return '';
	}

	private static function get_description( WP_Post $post ): string {
		$excerpt = get_the_excerpt( $post );
		if ( '' !== $excerpt ) {
			return $excerpt;
		}

		return wp_trim_words( wp_strip_all_tags( $post->post_content ), 24 );
	}

	private static function get_override( int $post_id ): array {
		$raw = get_post_meta( $post_id, '_ogd_override', true );
		$raw = is_array( $raw ) ? $raw : array();

		return array_merge(
			array(
				'mode'               => 'inherit',
				'template_id'        => '',
				'custom_title'       => '',
				'custom_description' => '',
				'custom_image_url'   => '',
				'custom_params'      => array(),
			),
			$raw
		);
	}

	private static function fallback_result( int $post_id, string $message ): array {
		$url = get_the_post_thumbnail_url( $post_id, 'full' ) ?: '';

		return array(
			'url'        => esc_url_raw( $url ),
			'templateId' => '',
			'params'     => array(),
			'fallback'   => true,
			'message'    => $message,
		);
	}

	private static function get_post_type_template( string $post_type ): array {
		$template = Settings::get( 'mapping_' . sanitize_key( $post_type ) . '_template', array() );
		$template = is_array( $template ) ? $template : array();
		$map      = $template['map'] ?? array();

		return array(
			'template_id' => isset( $template['template_id'] ) ? (string) $template['template_id'] : '',
			'map'         => is_array( $map ) ? $map : array(),
		);
	}

	private static function empty_result( string $message ): array {
		return array(
			'url'        => '',
			'templateId' => '',
			'params'     => array(),
			'fallback'   => false,
			'message'    => $message,
		);
	}
}
