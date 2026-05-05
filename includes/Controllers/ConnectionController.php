<?php
/**
 * Connection REST API endpoints.
 *
 * @package OGD
 */

namespace OGD\Controllers;

use OGD\Settings;
use WP_REST_Request;
use WP_REST_Server;

class ConnectionController {
	public static function init(): void {
		register_rest_route(
			'ogdynamic/v1',
			'/connection',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'get' ),
					'permission_callback' => array( self::class, 'can_manage' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( self::class, 'update' ),
					'permission_callback' => array( self::class, 'can_manage' ),
					'args'                => array(
						'api_key' => array(
							'required'          => true,
							'validate_callback' => array( self::class, 'validate_api_key' ),
							'sanitize_callback' => array( self::class, 'sanitize_api_key' ),
						),
					),
				),
			)
		);
	}

	public static function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	public static function validate_api_key( $value ): bool {
		return is_string( $value ) && '' !== $value && false === strpos( $value, '•' );
	}

	public static function sanitize_api_key( $value ): string {
		return sanitize_text_field( wp_unslash( (string) $value ) );
	}

	public static function get() {
		$api_key = Settings::get( 'api_key', '' );

		return rest_ensure_response(
			array(
				'data' => array(
					'api_key' => $api_key,
				),
			)
		);
	}

	public static function update( WP_REST_Request $request ) {
		$api_key = $request->get_param( 'api_key' );

		Settings::update( 'api_key', $api_key, false );

		return rest_ensure_response(
			array(
				'data' => array(
					'api_key' => $api_key,
				),
			)
		);
	}
}
