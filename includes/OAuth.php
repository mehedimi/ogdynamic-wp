<?php
/**
 * OAuth flow handler.
 *
 * @package OGD
 */

namespace OGDynamic;

class OAuth {
	private const SCOPE                  = 'user:read designs:read';
	private const TOKEN_OPTIONS          = array( 'api_key', 'oauth_access_token', 'oauth_refresh_token', 'oauth_expires_at', 'oauth_state', 'oauth_code_verifier' );
	private const TRANSIENT_ACCESS_TOKEN = 'access_token';
	private const TOKEN_ENDPOINT_ARGS    = array(
		'timeout' => 12,
		'headers' => array(
			'Accept' => 'application/json',
		),
	);

	/**
	 * Starts the OAuth flow.
	 *
	 * @return array|WP_Error Returns array with authorize_url or WP_Error on error.
	 */
	public static function start() {
		$client_id = self::get_or_register_client_id();

		$state         = self::random_url_token( 32 );
		$code_verifier = self::random_url_token( 64 );

		Settings::update( 'oauth_state', $state, false );
		Settings::update( 'oauth_code_verifier', $code_verifier, false );

		return array(
			'authorize_url' => self::authorize_url( $client_id, $state, $code_verifier ),
		);
	}

	/**
	 * Gets a valid access token.
	 *
	 * @return string Returns access token string or empty string on error.
	 */
	public static function get_access_token() {
		$token = Settings::get_transient( 'access_token', '' );

		if ( '' !== $token ) {
			return $token;
		}

		$refresh_token = Settings::get_refresh_token();

		if ( '' === $refresh_token ) {
			return '';
		}

		$result = self::refresh_access_token( $refresh_token );

		if ( is_wp_error( $result ) ) {
			return '';
		}

		self::store_token_response( $result );

		return $result['access_token'];
	}

	/**
	 * Handles the OAuth callback.
	 *
	 * @return void
	 */
	public static function handle_callback(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$error = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : '';

		if ( '' !== $error ) {
			wp_die( esc_html( $error ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';

		if ( '' === $code || '' === $state || ! hash_equals( (string) Settings::get( 'oauth_state', '' ), $state ) ) {
			wp_die( esc_html__( 'Invalid ogdynamic OAuth callback state.', 'ogdynamic' ) );
		}

		$token = self::exchange_code_for_token( $code );

		Settings::delete( 'oauth_state' );
		Settings::delete( 'oauth_code_verifier' );

		if ( is_wp_error( $token ) ) {
			wp_die( esc_html( $token->get_error_message() ) );
		}

		self::store_token_response( $token );

		wp_safe_redirect( admin_url( 'admin.php?page=ogdynamic#/connection' ) );
		exit;
	}

	/**
	 * Deletes the connection.
	 *
	 * @return void
	 */
	public static function delete(): void {
		foreach ( self::TOKEN_OPTIONS as $key ) {
			Settings::delete( $key );
		}
		Settings::delete_transient( 'access_token' );
	}

	protected static function get_or_register_client_id(): string {
		$client_id = Settings::get_oauth_client_id();

		if ( '' !== $client_id ) {
			return $client_id;
		}

		$client_id = OGDYNAMIC_CLIENT_ID;
		Settings::update( 'oauth_client_id', $client_id, false );

		return $client_id;
	}

	/**
	 * Exchanges token with OAuth endpoint.
	 *
	 * @param string $grant_type The grant type (authorization_code or refresh_token).
	 * @param array  $body Additional request body parameters.
	 * @return array|WP_Error Returns response body or WP_Error on failure.
	 */
	protected static function exchange_token( string $grant_type, array $body = array() ) {
		$client_id = self::get_or_register_client_id();

		$request_body = array(
			'grant_type' => $grant_type,
			'client_id'  => $client_id,
		);

		$response = wp_remote_post(
			self::app_endpoint( 'oauth/token' ),
			array_merge(
				self::TOKEN_ENDPOINT_ARGS,
				array(
					'body' => array_merge( $request_body, $body ),
				)
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status        = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 || ! is_array( $response_body ) || empty( $response_body['access_token'] ) ) {
			return new WP_Error( 'ogd_oauth_token_failed', __( 'Could not complete ogdynamic OAuth connection.', 'ogdynamic' ) );
		}

		return $response_body;
	}

	/**
	 * Exchanges authorization code for access token.
	 *
	 * @param string $code The authorization code.
	 * @return array|WP_Error Returns response body or WP_Error on failure.
	 */
	protected static function exchange_code_for_token( string $code ) {
		$code_verifier = (string) Settings::get( 'oauth_code_verifier', '' );

		if ( '' === $code_verifier ) {
			return new WP_Error( 'ogd_oauth_missing_state', __( 'Missing ogdynamic OAuth session data.', 'ogdynamic' ) );
		}

		return self::exchange_token(
			'authorization_code',
			array(
				'redirect_uri'  => self::redirect_uri(),
				'code'          => $code,
				'code_verifier' => $code_verifier,
			)
		);
	}

	/**
	 * Refreshes access token using refresh token.
	 *
	 * @param string $refresh_token The refresh token.
	 * @return array|WP_Error Returns response body or WP_Error on failure.
	 */
	protected static function refresh_access_token( string $refresh_token ) {
		return self::exchange_token(
			'refresh_token',
			array(
				'refresh_token' => $refresh_token,
			)
		);
	}

	/**
	 * Stores token response in transient and settings.
	 *
	 * @param array $token Token response from OAuth endpoint.
	 * @return void
	 */
	protected static function store_token_response( array $token ): void {
		$expires_in = isset( $token['expires_in'] ) ? absint( $token['expires_in'] ) : 0;

		Settings::set_transient( self::TRANSIENT_ACCESS_TOKEN, sanitize_text_field( (string) $token['access_token'] ), max( $expires_in, 0 ) );
		Settings::update( 'oauth_refresh_token', sanitize_text_field( (string) ( $token['refresh_token'] ?? '' ) ), false );
		Settings::update( 'oauth_expires_at', $expires_in > 0 ? time() + $expires_in : 0, false );
		Settings::delete( 'api_key' );
	}

	protected static function authorize_url( string $client_id, string $state, string $code_verifier ): string {
		return add_query_arg(
			array(
				'client_id'             => $client_id,
				'redirect_uri'          => self::redirect_uri(),
				'response_type'         => 'code',
				'scope'                 => self::SCOPE,
				'state'                 => $state,
				'code_challenge'        => self::code_challenge( $code_verifier ),
				'code_challenge_method' => 'S256',
			),
			self::app_endpoint( 'oauth/authorize' )
		);
	}

	protected static function code_challenge( string $code_verifier ): string {
		return self::base64_url_encode( hash( 'sha256', $code_verifier, true ) );
	}

	protected static function random_url_token( int $bytes ): string {
		return self::base64_url_encode( random_bytes( $bytes ) );
	}

	/**
	 * Encodes string for URL-safe OAuth tokens (PKCE flow).
	 *
	 * @param string $value The value to encode.
	 *
	 * @codeCoverageIgnore
	 */
	protected static function base64_url_encode( string $value ): string {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
	}

	protected static function redirect_uri(): string {
		return admin_url( 'admin.php?page=ogdynamic-oauth-callback' );
	}

	protected static function app_endpoint( string $path ): string {
		return trailingslashit( self::app_url() ) . $path;
	}

	protected static function app_url(): string {
		$app_url = preg_replace( '#/api$#', '', untrailingslashit( OGDYNAMIC_API ) );

		return is_string( $app_url ) ? $app_url : untrailingslashit( OGDYNAMIC_API );
	}
}
