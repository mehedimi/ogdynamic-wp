<?php
/**
 * Settings REST API endpoints.
 *
 * @package OGD
 */

namespace OGD\Controllers;

use OGD\Settings;
use WP_REST_Request;
use WP_REST_Server;

class SettingsController {
	private static Settings $settings;

	public static function init(): void {
		self::$settings = new Settings();

		register_rest_route(
			'ogdynamic/v1',
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'get' ),
					'permission_callback' => array( self::class, 'can_manage' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( self::class, 'save' ),
					'permission_callback' => array( self::class, 'can_manage' ),
				),
			)
		);
	}

	public static function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	public static function get() {
		return rest_ensure_response(
			array(
				'settings'    => self::$settings->get_public_settings(),
				'postTypes'   => self::$settings->available_post_types(),
				'seoPlugin'   => self::$settings->detect_seo_plugin(),
				'woocommerce' => self::$settings->is_woocommerce_active(),
			)
		);
	}

	public static function save( WP_REST_Request $request ) {
		$payload  = $request->get_json_params();
		$settings = self::$settings->update( is_array( $payload ) ? $payload : array() );
		self::$settings->clear_cache();

		return rest_ensure_response( array( 'settings' => self::$settings->get_public_settings(), 'saved' => true ) );
	}
}
