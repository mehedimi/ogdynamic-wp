<?php
/**
 * Connection REST API endpoints.
 *
 * @package OGD
 */

namespace OGDynamic\Controllers;

use OGDynamic\Settings;
use OGDynamic\OAuth;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * Class ConnectionController
 *
 * REST API controller for managing OAuth connections.
 */
class ConnectionController {

	/**
	 * Initializes REST API routes.
	 *
	 * @return void
	 */
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
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( self::class, 'delete' ),
					'permission_callback' => array( self::class, 'can_manage' ),
				),
			)
		);

		register_rest_route(
			'ogdynamic/v1',
			'/connection/oauth/start',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'start_oauth' ),
				'permission_callback' => array( self::class, 'can_manage' ),
			)
		);
	}

	/**
	 * Checks if user can manage connections.
	 *
	 * @return bool True if user has permission, false otherwise.
	 */
	public static function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Gets connection status.
	 *
	 * @return WP_REST_Response REST response with connection status.
	 */
	public static function get(): WP_REST_Response {
		$token     = OAuth::get_access_token();
		$connected = '' !== $token;

		return rest_ensure_response(
			array(
				'data' => array(
					'connected' => $connected,
				),
			)
		);
	}

	/**
	 * Starts OAuth flow.
	 *
	 * @return mixed REST response or error.
	 */
	public static function start_oauth() {
		$result = OAuth::start();

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'data' => $result,
			)
		);
	}

	/**
	 * Deletes connection.
	 *
	 * @return WP_REST_Response REST response with empty data.
	 */
	public static function delete(): WP_REST_Response {
		OAuth::delete();

		return rest_ensure_response(
			array(
				'data' => array(
					'connected' => false,
				),
			)
		);
	}

	/**
	 * Handles OAuth callback.
	 *
	 * @return void
	 */
	public static function handle_oauth_callback(): void {
		if ( ! self::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to connect ogdynamic.', 'ogdynamic' ) );
		}

		OAuth::handle_callback();
	}
}
