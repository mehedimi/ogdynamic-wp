<?php
/**
 * REST API endpoints.
 *
 * @package OGD
 */

namespace OGD;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RestController {
	private Settings $settings;
	private ImageGenerator $generator;

	public function __construct( Settings $settings, ImageGenerator $generator ) {
		$this->settings  = $settings;
		$this->generator = $generator;
	}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	public function routes(): void {
		register_rest_route(
			'ogdynamic/v1',
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);

		register_rest_route(
			'ogdynamic/v1',
			'/connection/test',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'test_connection' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);

		register_rest_route(
			'ogdynamic/v1',
			'/templates/refresh',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'refresh_templates' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);

		register_rest_route(
			'ogdynamic/v1',
			'/preview',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'preview' ),
				'permission_callback' => array( $this, 'can_preview' ),
			)
		);

		register_rest_route(
			'ogdynamic/v1',
			'/cache/clear',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'clear_cache' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);

		register_rest_route(
			'ogdynamic/v1',
			'/debug',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'debug' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);
	}

	public function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	public function can_preview(): bool {
		return current_user_can( 'edit_posts' );
	}

	public function get_settings() {
		return rest_ensure_response(
			array(
				'settings'    => $this->settings->get_public_settings(),
				'postTypes'   => $this->settings->available_post_types(),
				'seoPlugin'   => $this->settings->detect_seo_plugin(),
				'woocommerce' => $this->settings->is_woocommerce_active(),
			)
		);
	}

	public function save_settings( WP_REST_Request $request ) {
		$payload  = $request->get_json_params();
		$settings = $this->settings->update( is_array( $payload ) ? $payload : array() );
		$this->settings->clear_cache();

		return rest_ensure_response( array( 'settings' => $this->settings->get_public_settings(), 'saved' => true ) );
	}

	public function test_connection( WP_REST_Request $request ) {
		$params  = $request->get_json_params();
		$api_key = isset( $params['api_key'] ) ? sanitize_text_field( wp_unslash( $params['api_key'] ) ) : $this->settings->get_api_key();

		if ( '' === $api_key || false !== strpos( $api_key, '•' ) ) {
			return new WP_Error( 'ogd_missing_api_key', __( 'Enter a valid ogdynamic API key.', 'ogdynamic' ), array( 'status' => 400 ) );
		}

		$response = wp_remote_get(
			'https://ogdynamic.com/api/v1/account',
			array(
				'timeout' => 12,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Accept'        => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'ogd_connection_failed', $response->get_error_message(), array( 'status' => 502 ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error( 'ogd_connection_failed', __( 'ogdynamic rejected the API key or could not validate the account.', 'ogdynamic' ), array( 'status' => $code ) );
		}

		$body     = json_decode( wp_remote_retrieve_body( $response ), true );
		$settings = $this->settings->get();
		$settings['api_key'] = $api_key;
		$settings['connection'] = array(
			'status'          => 'connected',
			'account_label'   => (string) ( $body['email'] ?? $body['name'] ?? '' ),
			'plan'            => (string) ( $body['plan'] ?? '' ),
			'usage'           => $body['usage'] ?? null,
			'last_checked_at' => current_time( 'mysql' ),
			'last_error'      => '',
		);
		$this->settings->update( $settings );

		return rest_ensure_response( array( 'connected' => true, 'settings' => $this->settings->get_public_settings() ) );
	}

	public function refresh_templates() {
		$api_key = $this->settings->get_api_key();
		if ( '' === $api_key ) {
			return new WP_Error( 'ogd_missing_api_key', __( 'Connect your ogdynamic account before refreshing templates.', 'ogdynamic' ), array( 'status' => 400 ) );
		}

		$response = wp_remote_get(
			'https://ogdynamic.com/api/v1/templates',
			array(
				'timeout' => 12,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Accept'        => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'ogd_template_refresh_failed', $response->get_error_message(), array( 'status' => 502 ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error( 'ogd_template_refresh_failed', __( 'Could not fetch templates from ogdynamic.', 'ogdynamic' ), array( 'status' => $code ) );
		}

		$body      = json_decode( wp_remote_retrieve_body( $response ), true );
		$templates = $body['templates'] ?? $body['data'] ?? array();
		$templates = is_array( $templates ) ? array_values( $templates ) : array();
		$settings  = $this->settings->get();
		$settings['templates'] = $templates;
		$settings['template_updated'] = current_time( 'mysql' );
		$this->settings->update( $settings );

		return rest_ensure_response( array( 'templates' => $templates, 'settings' => $this->settings->get_public_settings() ) );
	}

	public function preview( WP_REST_Request $request ) {
		$params  = $request->get_json_params();
		$post_id = isset( $params['postId'] ) ? absint( $params['postId'] ) : 0;

		if ( $post_id > 0 ) {
			return rest_ensure_response( $this->generator->generate_for_post( $post_id ) );
		}

		$template_id = isset( $params['templateId'] ) ? sanitize_text_field( wp_unslash( $params['templateId'] ) ) : '';
		if ( '' === $template_id ) {
			$settings    = $this->settings->get();
			$template_id = (string) $settings['defaults']['global_template'];
		}

		if ( '' === $template_id ) {
			return new WP_Error( 'ogd_missing_template', __( 'Select a template before previewing.', 'ogdynamic' ), array( 'status' => 400 ) );
		}

		$params = $this->generator->sample_params();

		return rest_ensure_response(
			array(
				'url'        => $this->generator->build_image_url( $template_id, $params ),
				'templateId' => $template_id,
				'params'     => $params,
				'fallback'   => false,
				'message'    => '',
			)
		);
	}

	public function clear_cache() {
		$this->settings->clear_cache();

		return rest_ensure_response( array( 'cleared' => true ) );
	}

	public function debug() {
		$settings = $this->settings->get_public_settings();

		return rest_ensure_response(
			array(
				'connection'  => $settings['connection'],
				'seoPlugin'   => $this->settings->detect_seo_plugin(),
				'woocommerce' => $this->settings->is_woocommerce_active(),
				'versions'    => array(
					'wordpress' => get_bloginfo( 'version' ),
					'php'       => PHP_VERSION,
					'plugin'    => OGD_VERSION,
				),
				'siteUrl'     => home_url( '/' ),
				'templates'   => count( $settings['templates'] ),
			)
		);
	}
}
