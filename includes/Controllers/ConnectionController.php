<?php
/**
 * Connection REST API endpoints.
 *
 * @package OGD
 */

namespace OGDynamic\Controllers;

use OGDynamic\Settings;
use OGDynamic\OAuth;
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

	public static function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	public static function get() {
		$token     = OAuth::get_access_token();
		$connected = '' !== $token;

		return rest_ensure_response(
			array(
				'data' => array(
					'api_key'   => $token,
					'connected' => $connected,
				),
			)
		);
	}

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

	public static function delete() {
		OAuth::delete();

		return rest_ensure_response(
			array(
				'data' => array(
					'api_key'   => '',
					'connected' => false,
				),
			)
		);
	}

	public static function handle_oauth_callback(): void {
		if ( ! self::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to connect ogdynamic.', 'ogdynamic' ) );
		}

		OAuth::handle_callback();
	}
}
