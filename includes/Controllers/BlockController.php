<?php
/**
 * Block editor REST API endpoints.
 *
 * @package OGD
 */

namespace OGDynamic\Controllers;

use OGDynamic\OAuth;
use OGDynamic\Settings;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class BlockController
 *
 * Serves the canvas image block: proxies the ogdynamic canvas API with the
 * stored OAuth token (so editors never see it) and saves generated images
 * into the media library.
 */
class BlockController {

	/**
	 * Attachment meta key holding the canvas + params hash used to reuse uploads.
	 *
	 * @var string
	 */
	public const META_HASH = '_ogdynamic_hash';

	/**
	 * Attachment meta key holding the canvas id.
	 *
	 * @var string
	 */
	public const META_CANVAS_ID = '_ogdynamic_canvas_id';

	/**
	 * Attachment meta key holding the JSON encoded dynamic params.
	 *
	 * @var string
	 */
	public const META_PARAMS = '_ogdynamic_params';

	/**
	 * Attachment meta key holding the CDN URL the image was generated from.
	 *
	 * @var string
	 */
	public const META_SOURCE_URL = '_ogdynamic_source_url';

	/**
	 * Image mime types accepted from the render service, mapped to extensions.
	 *
	 * @var array
	 */
	private const ALLOWED_MIMES = array(
		'image/png'  => 'png',
		'image/jpeg' => 'jpg',
		'image/webp' => 'webp',
	);

	/**
	 * Initializes REST API routes.
	 *
	 * @return void
	 */
	public static function init(): void {
		register_rest_route(
			'ogdynamic/v1',
			'/block/canvases',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'get_canvases' ),
				'permission_callback' => array( self::class, 'can_edit' ),
			)
		);

		register_rest_route(
			'ogdynamic/v1',
			'/block/canvases/(?P<id>[0-9A-Za-z]{26})',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'get_canvas' ),
				'permission_callback' => array( self::class, 'can_edit' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => array( TemplatesController::class, 'validate_template_id' ),
					),
				),
			)
		);

		register_rest_route(
			'ogdynamic/v1',
			'/block/generate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'generate' ),
				'permission_callback' => array( self::class, 'can_generate' ),
				'args'                => array(
					'canvas_id' => array(
						'required'          => true,
						'validate_callback' => array( TemplatesController::class, 'validate_template_id' ),
						'sanitize_callback' => array( TemplatesController::class, 'sanitize_template_id' ),
					),
					'params'    => array(
						'required'          => false,
						'default'           => array(),
						'validate_callback' => array( self::class, 'validate_params' ),
						'sanitize_callback' => array( self::class, 'sanitize_params' ),
					),
					'post_id'   => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
					'alt'       => array(
						'required'          => false,
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Checks if the user can use the block in the editor.
	 *
	 * @return bool True if user has permission, false otherwise.
	 */
	public static function can_edit(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Checks if the user can save generated images into the media library.
	 *
	 * @return bool True if user has permission, false otherwise.
	 */
	public static function can_generate(): bool {
		return current_user_can( 'edit_posts' ) && current_user_can( 'upload_files' );
	}

	/**
	 * Lists the public canvases of the connected account.
	 *
	 * @return WP_REST_Response|WP_Error REST response with canvas summaries.
	 */
	public static function get_canvases() {
		$payload = self::api_get( 'v1/canvases?status=public' );

		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		$canvases = is_array( $payload['data'] ?? null ) ? $payload['data'] : array();

		return rest_ensure_response( array( 'data' => $canvases ) );
	}

	/**
	 * Gets one canvas including its overridable fields.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response|WP_Error REST response with the canvas.
	 */
	public static function get_canvas( WP_REST_Request $request ) {
		$canvas = self::fetch_canvas( (string) $request->get_param( 'id' ) );

		if ( is_wp_error( $canvas ) ) {
			return $canvas;
		}

		return rest_ensure_response( array( 'data' => $canvas ) );
	}

	/**
	 * Downloads the rendered canvas image into the media library.
	 *
	 * An upload for the same canvas and params is reused instead of
	 * downloading the image again.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response|WP_Error REST response with the attachment.
	 */
	public static function generate( WP_REST_Request $request ) {
		$canvas_id = (string) $request->get_param( 'canvas_id' );
		$params    = $request->get_param( 'params' );
		$params    = is_array( $params ) ? $params : array();
		$post_id   = (int) $request->get_param( 'post_id' );
		$alt       = (string) $request->get_param( 'alt' );

		if ( $post_id > 0 && ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'ogd_forbidden_post', __( 'You are not allowed to attach images to this post.', 'ogdynamic' ), array( 'status' => 403 ) );
		}

		$canvas = self::fetch_canvas( $canvas_id );

		if ( is_wp_error( $canvas ) ) {
			return $canvas;
		}

		if ( 'public' !== $canvas['status'] ) {
			return new WP_Error( 'ogd_canvas_not_public', __( 'Only public canvases can render images. Set the canvas to public in ogdynamic first.', 'ogdynamic' ), array( 'status' => 400 ) );
		}

		$query = http_build_query( $params );

		$hash = md5( $canvas_id . $canvas['updated_at'] . '?' . $query );

		$existing = self::find_attachment( $hash );

		if ( $existing > 0 ) {
			self::sync_alt( $existing, $alt );

			return rest_ensure_response( array( 'data' => self::attachment_payload( $existing, $canvas, true ) ) );
		}

		$source_url = OGDYNAMIC_CDN . 'c/' . rawurlencode( $canvas_id ) . ( '' !== $query ? '?' . $query : '' );
		$attachment = self::sideload( $source_url, $canvas, $hash, $post_id );

		if ( is_wp_error( $attachment ) ) {
			return $attachment;
		}

		update_post_meta( $attachment, self::META_HASH, $hash );
		update_post_meta( $attachment, self::META_CANVAS_ID, $canvas_id );
		update_post_meta( $attachment, self::META_PARAMS, wp_slash( wp_json_encode( $params ) ) );
		update_post_meta( $attachment, self::META_SOURCE_URL, esc_url_raw( $source_url ) );

		self::sync_alt( $attachment, $alt );

		return rest_ensure_response( array( 'data' => self::attachment_payload( $attachment, $canvas, false ) ) );
	}

	/**
	 * Validates the dynamic params structure.
	 *
	 * @param mixed $value The params to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate_params( $value ): bool {
		if ( ! is_array( $value ) ) {
			return false;
		}

		foreach ( $value as $key => $item ) {
			if ( ! is_string( $key ) || ! is_scalar( $item ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Sanitizes the dynamic params into a flat, sorted string map.
	 *
	 * Canvas render URLs only accept flat string params; empty values are
	 * dropped so canvas defaults apply, and keys are sorted so equal
	 * input always produces the same URL and hash.
	 *
	 * @param mixed $value The params to sanitize.
	 * @return array Sanitized params.
	 */
	public static function sanitize_params( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$clean = array();

		foreach ( $value as $key => $item ) {
			if ( ! is_string( $key ) || ! preg_match( '/^[A-Za-z0-9_.\-]{1,64}$/', $key ) || ! is_scalar( $item ) ) {
				continue;
			}

			$item = trim( sanitize_text_field( wp_unslash( (string) $item ) ) );

			if ( '' === $item ) {
				continue;
			}

			$clean[ $key ] = function_exists( 'mb_substr' ) ? mb_substr( $item, 0, 2000 ) : substr( $item, 0, 2000 );
		}

		ksort( $clean );

		return $clean;
	}

	/**
	 * Fetches and normalizes a single canvas.
	 *
	 * @param string $canvas_id The canvas id.
	 * @return array|WP_Error Normalized canvas or error.
	 */
	private static function fetch_canvas( string $canvas_id ) {
		$payload = self::api_get( 'v1/canvases/' . rawurlencode( $canvas_id ) );

		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		$canvas = $payload['data'] ?? null;

		if ( ! is_array( $canvas ) ) {
			return new WP_Error( 'ogd_canvas_invalid', __( 'Unexpected response from ogdynamic.', 'ogdynamic' ), array( 'status' => 502 ) );
		}

		$fields = array();

		foreach ( is_array( $canvas['override_fields'] ?? null ) ? $canvas['override_fields'] : array() as $field ) {
			if ( ! is_array( $field ) || empty( $field['field'] ) ) {
				continue;
			}

			$fields[] = array(
				'field'   => (string) $field['field'],
				'name'    => isset( $field['name'] ) ? (string) $field['name'] : (string) $field['field'],
				'type'    => isset( $field['type'] ) ? (string) $field['type'] : 'text',
				'default' => isset( $field['default'] ) && is_scalar( $field['default'] ) ? (string) $field['default'] : '',
			);
		}

		// Normalize the fields the controller relies on so missing API keys
		// never surface as undefined-index warnings downstream.
		return array_merge(
			$canvas,
			array(
				'name'            => isset( $canvas['name'] ) ? (string) $canvas['name'] : '',
				'status'          => isset( $canvas['status'] ) ? (string) $canvas['status'] : '',
				'updated_at'      => isset( $canvas['updated_at'] ) ? (string) $canvas['updated_at'] : '',
				'width'           => isset( $canvas['width'] ) ? (int) $canvas['width'] : 0,
				'height'          => isset( $canvas['height'] ) ? (int) $canvas['height'] : 0,
				'override_fields' => $fields,
			)
		);
	}

	/**
	 * Performs an authenticated GET against the ogdynamic API.
	 *
	 * A 401 is retried once after dropping the cached access token so the
	 * refresh token can mint a new one.
	 *
	 * @param string $path API path relative to the API root.
	 * @return array|WP_Error Decoded JSON body or error.
	 */
	private static function api_get( string $path ) {
		$url = untrailingslashit( OGDYNAMIC_API ) . '/' . ltrim( $path, '/' );

		for ( $attempt = 0; $attempt < 2; $attempt++ ) {
			$token = OAuth::get_access_token();

			if ( '' === $token ) {
				return new WP_Error( 'ogd_not_connected', __( 'ogdynamic is not connected. Connect your account in the ogdynamic settings.', 'ogdynamic' ), array( 'status' => 403 ) );
			}

			$response = wp_remote_get(
				$url,
				array(
					'timeout' => 15,
					'headers' => array(
						'Accept'        => 'application/json',
						'Authorization' => 'Bearer ' . $token,
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return new WP_Error( 'ogd_api_unreachable', $response->get_error_message(), array( 'status' => 502 ) );
			}

			$status = (int) wp_remote_retrieve_response_code( $response );

			if ( 401 === $status && 0 === $attempt ) {
				Settings::delete_transient( 'access_token' );
				continue;
			}

			break;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 401 === $status || 403 === $status ) {
			return new WP_Error( 'ogd_unauthorized', __( 'ogdynamic rejected the request. Reconnect your account in the ogdynamic settings.', 'ogdynamic' ), array( 'status' => 403 ) );
		}

		if ( 404 === $status ) {
			return new WP_Error( 'ogd_canvas_not_found', __( 'Canvas not found.', 'ogdynamic' ), array( 'status' => 404 ) );
		}

		if ( $status < 200 || $status >= 300 || ! is_array( $body ) ) {
			return new WP_Error( 'ogd_api_error', __( 'ogdynamic returned an unexpected response.', 'ogdynamic' ), array( 'status' => 502 ) );
		}

		return $body;
	}

	/**
	 * Finds an existing upload for a canvas + params hash.
	 *
	 * @param string $hash The canvas + params hash.
	 * @return int Attachment id or 0 when none exists.
	 */
	private static function find_attachment( string $hash ): int {
		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'     => array(
					array(
						'key'   => self::META_HASH,
						'value' => $hash,
					),
				),
			)
		);

		if ( empty( $ids ) || ! wp_get_attachment_url( (int) $ids[0] ) ) {
			return 0;
		}

		return (int) $ids[0];
	}

	/**
	 * Downloads an image and adds it to the media library.
	 *
	 * @param string $source_url The CDN render URL.
	 * @param array  $canvas     Normalized canvas.
	 * @param string $hash       The canvas + params hash.
	 * @param int    $post_id    Post to attach the image to (0 for none).
	 * @return int|WP_Error Attachment id or error.
	 */
	private static function sideload( string $source_url, array $canvas, string $hash, int $post_id ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = download_url( $source_url, 30 );

		if ( is_wp_error( $tmp ) ) {
			return new WP_Error( 'ogd_download_failed', $tmp->get_error_message(), array( 'status' => 502 ) );
		}

		$mime = wp_get_image_mime( $tmp );

		if ( ! $mime || ! isset( self::ALLOWED_MIMES[ $mime ] ) ) {
			wp_delete_file( $tmp );

			return new WP_Error( 'ogd_invalid_image', __( 'The render service did not return a valid image.', 'ogdynamic' ), array( 'status' => 502 ) );
		}

		$title = '' !== $canvas['name'] ? $canvas['name'] : __( 'ogdynamic image', 'ogdynamic' );
		$slug  = sanitize_title( $title );

		$attachment = media_handle_sideload(
			array(
				'name'     => sanitize_file_name( ( '' !== $slug ? $slug : 'ogdynamic' ) . '-' . substr( $hash, 0, 8 ) . '.' . self::ALLOWED_MIMES[ $mime ] ),
				'tmp_name' => $tmp,
			),
			$post_id,
			$title
		);

		if ( is_wp_error( $attachment ) ) {
			wp_delete_file( $tmp );

			return new WP_Error( 'ogd_sideload_failed', $attachment->get_error_message(), array( 'status' => 500 ) );
		}

		return (int) $attachment;
	}

	/**
	 * Stores the alt text on an attachment.
	 *
	 * The block keeps its own alt attribute, so the attachment meta is
	 * synced on every generate; an empty value removes a stored alt.
	 *
	 * @param int    $attachment_id The attachment id.
	 * @param string $alt           Alt text, empty to clear.
	 * @return void
	 */
	private static function sync_alt( int $attachment_id, string $alt ): void {
		$alt = sanitize_text_field( $alt );

		if ( '' === $alt ) {
			delete_post_meta( $attachment_id, '_wp_attachment_image_alt' );

			return;
		}

		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
	}

	/**
	 * Builds the response data for a media library attachment.
	 *
	 * @param int   $attachment_id The attachment id.
	 * @param array $canvas        Normalized canvas.
	 * @param bool  $reused        Whether an existing upload was reused.
	 * @return array Attachment data.
	 */
	private static function attachment_payload( int $attachment_id, array $canvas, bool $reused ): array {
		$meta = wp_get_attachment_metadata( $attachment_id );

		return array(
			'attachment_id' => $attachment_id,
			'url'           => (string) wp_get_attachment_url( $attachment_id ),
			'width'         => is_array( $meta ) && ! empty( $meta['width'] ) ? (int) $meta['width'] : $canvas['width'],
			'height'        => is_array( $meta ) && ! empty( $meta['height'] ) ? (int) $meta['height'] : $canvas['height'],
			'reused'        => $reused,
		);
	}
}
