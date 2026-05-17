<?php
/**
 * OAuth flow handler.
 *
 * @package OGD
 */

namespace OGDynamic;

/**
 * Class OAuth
 *
 * Handles OAuth 2.0 flow with PKCE for ogdynamic API authentication.
 */
class OAuth {

	/**
	 * OAuth scope for API access.
	 *
	 * @var string
	 */
	private const SCOPE = 'user:read designs:read';

	/**
	 * Token option keys to delete on disconnect.
	 *
	 * @var array
	 */
	private const TOKEN_OPTIONS = array(
		'api_key',
		'oauth_access_token',
		'oauth_refresh_token',
		'oauth_expires_at',
		'oauth_client_id',
	);

	/**
	 * Transient key for access token.
	 *
	 * @var string
	 */
	private const TRANSIENT_ACCESS_TOKEN = 'access_token';

	/**
	 * Default arguments for token endpoint requests.
	 *
	 * @var array
	 */
	private const TOKEN_ENDPOINT_ARGS = array(
		'timeout' => 12,
		'headers' => array(
			'Accept' => 'application/json',
		),
	);

	/**
	 * Starts the OAuth flow.
	 *
	 * @return array{authorize_url: string} Returns array with authorize_url.
	 */
	public static function start(): array {
		$state         = self::random_url_token( 32 );
		$code_verifier = self::random_url_token( 64 );

		Settings::set_transient( 'oauth_state', $state, 5 * MINUTE_IN_SECONDS );
		Settings::set_transient( 'oauth_code_verifier', $code_verifier, 5 * MINUTE_IN_SECONDS );

		return array(
			'authorize_url' => self::authorize_url( OGDYNAMIC_CLIENT_ID, $state, $code_verifier ),
		);
	}

	/**
	 * Gets a valid access token.
	 *
	 * @return string Returns access token string or empty string on error.
	 */
	public static function get_access_token(): string {
		$token = Settings::get_transient( self::TRANSIENT_ACCESS_TOKEN, '' );

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

		if ( '' === $code || '' === $state || ! hash_equals( (string) Settings::get_transient( 'oauth_state', '' ), $state ) ) {
			wp_die( esc_html__( 'Invalid ogdynamic OAuth callback state.', 'ogdynamic' ) );
		}

		$token = self::exchange_code_for_token( $code );

		Settings::delete_transient( 'oauth_state' );
		Settings::delete_transient( 'oauth_code_verifier' );

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
		Settings::delete_transient( self::TRANSIENT_ACCESS_TOKEN );
	}


	/**
	 * Exchanges authorization code for access token.
	 *
	 * @param string $code The authorization code.
	 * @return array|\WP_Error Returns response body or \WP_Error on failure.
	 */
	protected static function exchange_code_for_token( string $code ) {
		$code_verifier = (string) Settings::get_transient( 'oauth_code_verifier', '' );

		if ( '' === $code_verifier ) {
			return new \WP_Error( 'ogd_oauth_missing_state', __( 'Missing ogdynamic OAuth session data.', 'ogdynamic' ) );
		}

		$body = array(
			'grant_type'    => 'authorization_code',
			'client_id'     => OGDYNAMIC_CLIENT_ID,
			'redirect_uri'  => self::redirect_uri(),
			'code'          => $code,
			'code_verifier' => $code_verifier,
		);

		$response = wp_remote_post(
			self::app_endpoint( 'oauth/token' ),
			array_merge(
				self::TOKEN_ENDPOINT_ARGS,
				array(
					'body' => $body,
				)
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status        = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 || ! is_array( $response_body ) || empty( $response_body['access_token'] ) ) {
			return new \WP_Error( 'ogd_oauth_token_failed', __( 'Could not complete ogdynamic OAuth connection.', 'ogdynamic' ) );
		}

		return $response_body;
	}

	/**
	 * Refreshes access token using refresh token.
	 *
	 * @param string $refresh_token The refresh token.
	 * @return array|\WP_Error Returns response body or \WP_Error on failure.
	 */
	protected static function refresh_access_token( string $refresh_token ) {
		$body = array(
			'grant_type'    => 'refresh_token',
			'client_id'     => OGDYNAMIC_CLIENT_ID,
			'refresh_token' => $refresh_token,
		);

		$response = wp_remote_post(
			self::app_endpoint( 'oauth/token' ),
			array_merge(
				self::TOKEN_ENDPOINT_ARGS,
				array(
					'body' => $body,
				)
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status        = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 || ! is_array( $response_body ) || empty( $response_body['access_token'] ) ) {
			return new \WP_Error( 'ogd_oauth_token_refresh_failed', __( 'Could not refresh ogdynamic OAuth token.', 'ogdynamic' ) );
		}

		return $response_body;
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

	/**
	 * Generates the OAuth authorize URL.
	 *
	 * @param string $client_id     The OAuth client ID.
	 * @param string $state         The state parameter for CSRF protection.
	 * @param string $code_verifier The PKCE code verifier.
	 * @return string The authorize URL.
	 */
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

	/**
	 * Generates code challenge for PKCE flow.
	 *
	 * @param string $code_verifier The PKCE code verifier.
	 * @return string The code challenge.
	 */
	protected static function code_challenge( string $code_verifier ): string {
		return self::base64_url_encode( hash( 'sha256', $code_verifier, true ) );
	}

	/**
	 * Generates a random URL-safe token.
	 *
	 * @param int $bytes Number of random bytes to generate.
	 * @return string The base64 URL-encoded token.
	 */
	protected static function random_url_token( int $bytes ): string {
		return self::base64_url_encode( random_bytes( $bytes ) );
	}

	/**
	 * Encodes string for URL-safe OAuth tokens (PKCE flow).
	 *
	 * @param string $value The value to encode.
	 * @return string The base64 URL-encoded string.
	 */
	protected static function base64_url_encode( string $value ): string {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
	}

	/**
	 * Gets the OAuth redirect URI.
	 *
	 * @return string The redirect URI.
	 */
	protected static function redirect_uri(): string {
		return admin_url( 'admin.php?page=ogdynamic-oauth-callback' );
	}

	/**
	 * Gets the full app endpoint URL.
	 *
	 * @param string $path The endpoint path.
	 * @return string The full endpoint URL.
	 */
	protected static function app_endpoint( string $path ): string {
		return trailingslashit( self::app_url() ) . $path;
	}

	/**
	 * Gets the base app URL.
	 *
	 * @return string The base app URL.
	 */
	protected static function app_url(): string {
		$app_url = preg_replace( '#/api$#', '', untrailingslashit( OGDYNAMIC_API ) );

		return is_string( $app_url ) ? $app_url : untrailingslashit( OGDYNAMIC_API );
	}
}
