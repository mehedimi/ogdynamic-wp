<?php
/**
 * Connection REST API endpoints.
 *
 * @package OGD
 */

namespace OGD\Controllers;

use OGD\Settings;
use WP_Error;
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
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( self::class, 'delete' ),
					'permission_callback' => array( self::class, 'can_manage' ),
				),
			)
		);
	}

	public static function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	public static function validate_api_key( $value ) {
		if ( ! is_string( $value ) || '' === $value || false !== strpos( $value, '•' ) ) {
			return false;
		}

		return self::verify_api_key( self::sanitize_api_key( $value ) );
	}

	public static function sanitize_api_key( $value ): string {
		return sanitize_text_field( wp_unslash( (string) $value ) );
	}

	public static function get() {
		return rest_ensure_response(
			array(
				'data' => array(
					'api_key' => Settings::get_api_key(),
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
					'api_key' => Settings::get_api_key(),
				),
			)
		);
	}

	public static function delete() {
		Settings::update( 'api_key', '', false );

		return rest_ensure_response(
			array(
				'data' => array(
					'api_key' => '',
				),
			)
		);
	}

	private static function verify_api_key( string $api_key ) {
		$response = wp_remote_get(
			trailingslashit( OGD_API ) . 'v1/me',
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
			return new WP_Error( 'ogd_invalid_api_key', __( 'Could not verify the ogdynamic API key.', 'ogdynamic' ), array( 'status' => 401 ) );
		}

		return true;
	}

}
