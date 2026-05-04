<?php
/**
 * Dynamic image URL generation.
 *
 * @package OGD
 */

namespace OGD;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ImageGenerator {
	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function generate_for_post( int $post_id ): array {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return $this->empty_result( 'Post not found.' );
		}

		$override = $this->get_override( $post_id );
		if ( 'disabled' === $override['mode'] ) {
			return $this->empty_result( 'ogdynamic disabled for this content.' );
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

		$template_id = $this->resolve_template_id( $post, $override );
		if ( '' === $template_id ) {
			return $this->fallback_result( $post_id, 'No template configured.' );
		}

		$params = $this->resolve_post_params( $post, $template_id, $override );
		$url    = $this->build_image_url( $template_id, $params );

		return array(
			'url'        => $url,
			'templateId' => $template_id,
			'params'     => $params,
			'fallback'   => false,
			'message'    => '',
		);
	}

	public function build_image_url( string $template_id, array $params ): string {
		$base  = 'https://ogdynamic.com/api/og/' . rawurlencode( $template_id );
		$query = http_build_query( array_filter( $params, static fn( $value ) => '' !== (string) $value ), '', '&', PHP_QUERY_RFC3986 );

		return $query ? $base . '?' . $query : $base;
	}

	public function sample_params(): array {
		return array(
			'title'       => get_bloginfo( 'name' ),
			'description' => get_bloginfo( 'description' ),
			'site_name'   => get_bloginfo( 'name' ),
			'url'         => home_url( '/' ),
		);
	}

	private function resolve_template_id( WP_Post $post, array $override ): string {
		$settings = $this->settings->get();
		if ( '' !== $override['template_id'] ) {
			return $override['template_id'];
		}

		if ( 'product' === $post->post_type && '' !== $settings['defaults']['product_template'] ) {
			return (string) $settings['defaults']['product_template'];
		}

		$post_type_templates = $settings['defaults']['post_templates'];
		if ( isset( $post_type_templates[ $post->post_type ] ) && '' !== $post_type_templates[ $post->post_type ] ) {
			return (string) $post_type_templates[ $post->post_type ];
		}

		return (string) $settings['defaults']['global_template'];
	}

	private function resolve_post_params( WP_Post $post, string $template_id, array $override ): array {
		$settings = $this->settings->get();
		$mappings = $settings['mappings'][ $template_id ] ?? array();
		$params   = array();

		if ( empty( $mappings ) ) {
			$params = array(
				'title'       => $override['custom_title'] ?: get_the_title( $post ),
				'description' => $override['custom_description'] ?: $this->get_description( $post ),
				'image'       => get_the_post_thumbnail_url( $post, 'full' ) ?: '',
				'url'         => get_permalink( $post ),
				'site_name'   => get_bloginfo( 'name' ),
			);
		}

		foreach ( $mappings as $variable => $mapping ) {
			$params[ $variable ] = $this->resolve_field_value( $post, $mapping );
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

	private function resolve_field_value( WP_Post $post, array $mapping ): string {
		$source   = $mapping['source'] ?? '';
		$fallback = $mapping['fallback'] ?? '';

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
				$value = $this->product_value( $post, 'short_description' );
				break;
			case 'product_image':
				$value = get_the_post_thumbnail_url( $post, 'full' ) ?: '';
				break;
			case 'product_gallery_image':
				$value = $this->product_value( $post, 'gallery_image' );
				break;
			case 'product_price':
				$value = $this->product_value( $post, 'price_html' );
				break;
			case 'regular_price':
				$value = $this->product_value( $post, 'regular_price' );
				break;
			case 'sale_price':
				$value = $this->product_value( $post, 'sale_price' );
				break;
			case 'currency':
				$value = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '';
				break;
			case 'sku':
				$value = $this->product_value( $post, 'sku' );
				break;
			case 'product_category':
				$value = implode( ', ', wp_get_post_terms( $post->ID, 'product_cat', array( 'fields' => 'names' ) ) );
				break;
			case 'product_tags':
				$value = implode( ', ', wp_get_post_terms( $post->ID, 'product_tag', array( 'fields' => 'names' ) ) );
				break;
			case 'stock_status':
				$value = $this->product_value( $post, 'stock_status' );
				break;
			case 'rating':
				$value = $this->product_value( $post, 'rating' );
				break;
			case 'review_count':
				$value = $this->product_value( $post, 'review_count' );
				break;
			case 'product_url':
				$value = get_permalink( $post );
				break;
			case 'custom_meta':
				$value = get_post_meta( $post->ID, (string) ( $mapping['meta_key'] ?? '' ), true );
				break;
			default:
				$value = (string) ( $mapping['static'] ?? '' );
		}

		return '' !== (string) $value ? (string) $value : (string) $fallback;
	}

	private function product_value( WP_Post $post, string $field ): string {
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

	private function get_description( WP_Post $post ): string {
		$excerpt = get_the_excerpt( $post );
		if ( '' !== $excerpt ) {
			return $excerpt;
		}

		return wp_trim_words( wp_strip_all_tags( $post->post_content ), 24 );
	}

	private function get_override( int $post_id ): array {
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

	private function fallback_result( int $post_id, string $message ): array {
		$settings = $this->settings->get();
		$url      = '';

		if ( 'featured_image' === $settings['defaults']['fallback_mode'] ) {
			$url = get_the_post_thumbnail_url( $post_id, 'full' ) ?: '';
		}
		if ( '' === $url && '' !== $settings['defaults']['fallback_image_url'] ) {
			$url = $settings['defaults']['fallback_image_url'];
		}

		return array(
			'url'        => esc_url_raw( $url ),
			'templateId' => '',
			'params'     => array(),
			'fallback'   => true,
			'message'    => $message,
		);
	}

	private function empty_result( string $message ): array {
		return array(
			'url'        => '',
			'templateId' => '',
			'params'     => array(),
			'fallback'   => false,
			'message'    => $message,
		);
	}
}
