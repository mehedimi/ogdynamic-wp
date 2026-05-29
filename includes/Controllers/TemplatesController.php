<?php
/**
 * Templates REST API endpoints.
 *
 * @package OGD
 */

namespace OGDynamic\Controllers;

use OGDynamic\Template;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Class TemplatesController
 *
 * REST API controller for managing OG image templates.
 * Handles CRUD operations for template mappings per post type.
 */
class TemplatesController {

	/**
	 * Initializes REST API routes.
	 *
	 * @return void
	 */
	public static function init(): void {
		register_rest_route(
			'ogdynamic/v1',
			'/templates',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'get' ),
					'permission_callback' => array( self::class, 'can_manage' ),
				),
			)
		);

		register_rest_route(
			'ogdynamic/v1',
			'/templates/(?P<post_type>[a-z0-9_-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'get_post_type_template' ),
					'permission_callback' => array( self::class, 'can_manage' ),
					'args'                => array(
						'post_type' => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_key',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( self::class, 'update_post_type_template' ),
					'permission_callback' => array( self::class, 'can_manage' ),
					'args'                => array(
						'template_id' => array(
							'required'          => true,
							'validate_callback' => array( self::class, 'validate_template_id' ),
							'sanitize_callback' => array( self::class, 'sanitize_template_id' ),
						),
						'map'         => array(
							'required'          => true,
							'validate_callback' => array( self::class, 'validate_map' ),
							'sanitize_callback' => array( self::class, 'sanitize_map' ),
						),
						'post_type'   => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_key',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( self::class, 'delete_post_type_template' ),
					'permission_callback' => array( self::class, 'can_manage' ),
					'args'                => array(
						'post_type' => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_key',
						),
					),
				),
			)
		);
	}

	/**
	 * Checks if user can manage templates.
	 *
	 * @return bool True if user has permission, false otherwise.
	 */
	public static function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Gets all available templates.
	 *
	 * @return \WP_REST_Response REST response with post types and activated templates.
	 */
	public static function get(): \WP_REST_Response {
		$post_types           = Template::available_post_types();
		$activated_post_types = Template::get_activated_post_types();

		return rest_ensure_response(
			array(
				'data'      => $post_types,
				'templates' => $activated_post_types,
			)
		);
	}

	/**
	 * Gets template for a specific post type.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response REST response with template data and sources.
	 */
	public static function get_post_type_template( WP_REST_Request $request ): \WP_REST_Response {
		$post_type = (string) $request->get_param( 'post_type' );

		return rest_ensure_response(
			array(
				'data'    => Template::get_mapping( $post_type ),
				'sources' => Template::get_mapping_sources( $post_type ),
			)
		);
	}

	/**
	 * Updates template mapping for a post type.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response REST response with updated template data.
	 */
	public static function update_post_type_template( WP_REST_Request $request ): \WP_REST_Response {
		$post_type   = (string) $request->get_param( 'post_type' );
		$template_id = (string) $request->get_param( 'template_id' );
		$map         = $request->get_param( 'map' );

		$value = array(
			'template_id' => $template_id,
			'map'         => is_array( $map ) ? $map : array(),
		);

		Template::update_mapping( $post_type, $value );

		return rest_ensure_response(
			array(
				'data' => $value,
			)
		);
	}

	/**
	 * Deletes template mapping for a post type.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response REST response with empty data.
	 */
	public static function delete_post_type_template( WP_REST_Request $request ): \WP_REST_Response {
		$post_type = (string) $request->get_param( 'post_type' );

		Template::delete_mapping( $post_type );

		return rest_ensure_response(
			array(
				'data' => array(),
			)
		);
	}

	/**
	 * Validates template ID format.
	 *
	 * @param mixed $value The template ID to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate_template_id( $value ): bool {
		return is_string( $value ) && preg_match( '/^[0-9A-HJKMNP-TV-Z]{26}$/i', $value );
	}

	/**
	 * Sanitizes template ID.
	 *
	 * @param mixed $value The template ID to sanitize.
	 * @return string Sanitized template ID.
	 */
	public static function sanitize_template_id( $value ): string {
		return sanitize_text_field( wp_unslash( (string) $value ) );
	}

	/**
	 * Validates map array structure.
	 *
	 * @param mixed $value The map to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate_map( $value ): bool {
		if ( ! is_array( $value ) ) {
			return false;
		}

		foreach ( $value as $item ) {
			if ( ! is_array( $item ) ) {
				return false;
			}

			if ( ! isset( $item['attr_key'], $item['key'] ) ) {
				return false;
			}

			if ( ! is_string( $item['attr_key'] ) || ! is_string( $item['key'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Sanitizes map array.
	 *
	 * @param mixed $value The map to sanitize.
	 * @return array Sanitized map array.
	 */
	public static function sanitize_map( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$clean = array();

		foreach ( $value as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$attr_key = isset( $item['attr_key'] ) ? sanitize_text_field( wp_unslash( (string) $item['attr_key'] ) ) : '';
			$key      = isset( $item['key'] ) ? sanitize_text_field( wp_unslash( (string) $item['key'] ) ) : '';

			if ( '' === $attr_key && '' === $key ) {
				continue;
			}

			$clean[] = array(
				'attr_key' => $attr_key,
				'key'      => $key,
			);
		}

		return $clean;
	}
}
