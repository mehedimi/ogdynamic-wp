<?php
/**
 * Admin page and asset loading.
 *
 * @package OGD
 */

namespace OGDynamic;

use OGDynamic\Controllers\ConnectionController;

class Admin {
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
		add_filter( 'script_loader_tag', array( self::class, 'module_script_tag' ), 10, 3 );
	}

	/**
	 * Adds admin menu pages.
	 *
	 * @return void
	 */
	public static function add_menu(): void {
		add_menu_page(
			__( 'ogdynamic', 'ogdynamic' ),
			__( 'ogdynamic', 'ogdynamic' ),
			'manage_options',
			'ogdynamic',
			array( self::class, 'render' ),
			self::menu_icon(),
			58
		);

		add_submenu_page(
			null,
			__( 'ogdynamic OAuth Callback', 'ogdynamic' ),
			__( 'ogdynamic OAuth Callback', 'ogdynamic' ),
			'manage_options',
			'ogdynamic-oauth-callback',
			array( self::class, 'render_oauth_callback' )
		);
	}

	/**
	 * Enqueues admin scripts and styles.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public static function enqueue( string $hook ): void {
		if ( 'toplevel_page_ogdynamic' !== $hook ) {
			return;
		}

		if ( self::is_dev_mode() ) {
			self::enqueue_dev_assets();
			self::localize_admin_script();
			return;
		}

		$asset = self::asset_files();

		wp_enqueue_script(
			'ogdynamic-admin',
			$asset['js'],
			array(),
			OGDYNAMIC_VERSION,
			true
		);

		foreach ( $asset['css'] as $index => $css ) {
			wp_enqueue_style( 'ogdynamic-admin-css-' . $index, $css, array(), OGDYNAMIC_VERSION );
		}

		self::localize_admin_script();
	}

	/**
	 * Adds module script tag for admin scripts.
	 *
	 * @param string $tag    The script tag.
	 * @param string $handle The script handle.
	 * @param string $src    The script source URL.
	 * @return string The modified script tag.
	 */
	public static function module_script_tag( string $tag, string $handle, string $src ): string {
		if ( ! in_array( $handle, array( 'ogdynamic-vite-client', 'ogdynamic-admin' ), true ) ) {
			return $tag;
		}

		return sprintf(
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
			'<script type="module" src="%s" id="%s-js"></script>' . "\n",
			esc_url( $src ),
			esc_attr( $handle )
		);
	}

	/**
	 * Enqueues assets in development mode.
	 *
	 * @return void
	 */
	private static function enqueue_dev_assets(): void {
		$server = self::dev_server_url();

		wp_enqueue_script(
			'ogdynamic-vite-client',
			$server . '/@vite/client',
			array(),
			OGDYNAMIC_VERSION,
			true
		);

		wp_enqueue_script(
			'ogdynamic-admin',
			$server . '/src/admin/main.ts',
			array( 'ogdynamic-vite-client' ),
			OGDYNAMIC_VERSION,
			true
		);
	}

	/**
	 * Returns the menu icon SVG.
	 *
	 * @return string The base64-encoded SVG icon.
	 */
	private static function menu_icon(): string {
		return 'data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PSIwIDAgMjUwIDI0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiBhcmlhLWhpZGRlbj0idHJ1ZSI+PHJlY3QgeD0iMjgiIHk9IjI3IiB3aWR0aD0iMTk1IiBoZWlnaHQ9IjE4NSIgcng9IjMzIiBzdHJva2U9ImN1cnJlbnRDb2xvciIgc3Ryb2tlLXdpZHRoPSIxNCIvPjxjaXJjbGUgY3g9Ijc4LjciIGN5PSI4MS43IiByPSIxNS42IiBmaWxsPSJjdXJyZW50Q29sb3IiLz48cmVjdCB4PSIxMzAuNyIgeT0iNzEuOSIgd2lkdGg9IjYzLjciIGhlaWdodD0iMjgiIHJ4PSIxNCIgZmlsbD0iY3VycmVudENvbG9yIi8+PHJlY3QgeD0iMTMwLjciIHk9IjExNS4zIiB3aWR0aD0iNTQuNiIgaGVpZ2h0PSIyMy44IiByeD0iMTEuOSIgZmlsbD0iY3VycmVudENvbG9yIi8+PHBhdGggZD0iTTUxLjM5OTYgMTY0LjM0OUw4Ni40OTk2IDExOC4wOTlDOTAuODMyOSAxMTIuNDkzIDk1LjU5OTYgMTEyLjk2IDEwMC44IDExOS41TDEzNS45IDE2NC4zNDlDMTQwLjIzMyAxNzEuODIzIDEzOC41IDE3NS41NjEgMTMwLjcgMTc1LjU2MUg2MC40OTk2QzUwLjA5OTYgMTc1LjU2MSA0Ny4wNjYyIDE3MS44MjMgNTEuMzk5NiAxNjQuMzQ5WiIgZmlsbD0iY3VycmVudENvbG9yIi8+PC9zdmc+Cg==';
	}

	private static function localize_admin_script(): void {
		$api_key = OAuth::get_access_token();

		wp_localize_script(
			'ogdynamic-admin',
			'ogdynamicAdmin',
			array(
				'restUrl' => esc_url_raw( rest_url( 'ogdynamic/v1/' ) ),
				'apiUrl'  => esc_url_raw( OGDYNAMIC_API ),
				'cdnUrl'  => esc_url_raw( OGDYNAMIC_CDN ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'apiKey'  => $api_key,
			)
		);
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="wrap ogdynamic-wrap"><div id="ogdynamic-admin-app">';
		echo '<h1>' . esc_html__( 'ogdynamic', 'ogdynamic' ) . '</h1>';
		echo '<p>' . esc_html__( 'Loading ogdynamic admin…', 'ogdynamic' ) . '</p>';
		echo '</div></div>';
	}

	/**
	 * Renders the OAuth callback page.
	 *
	 * @return void
	 */
	public static function render_oauth_callback(): void {
		ConnectionController::handle_oauth_callback();
	}

	/**
	 * Gets asset files from Vite manifest.
	 *
	 * @return array Array with 'js' and 'css' file paths.
	 */
	private static function asset_files(): array {
		$manifest_path = OGDYNAMIC_PATH . 'dist/admin/.vite/manifest.json';

		$manifest = wp_json_file_decode( $manifest_path, array( 'associative' => true ) );
		$entry    = $manifest['src/admin/main.ts'] ?? null;

		return array(
			'js'  => OGDYNAMIC_URL . 'dist/admin/' . $entry['file'],
			'css' => array_map(
				static function ( string $css ): string {
					return OGDYNAMIC_URL . 'dist/admin/' . $css;
				},
				$entry['css'] ?? array()
			),
		);
	}

	/**
	 * Checks if development mode is enabled.
	 *
	 * @return bool True if dev mode is enabled, false otherwise.
	 */
	private static function is_dev_mode(): bool {
		return defined( 'OGDYNAMIC_DEV' ) && true === OGDYNAMIC_DEV;
	}

	/**
	 * Gets the Vite dev server URL.
	 *
	 * @return string The dev server URL.
	 */
	private static function dev_server_url(): string {
		$server = defined( 'OGDYNAMIC_DEV_SERVER' ) ? (string) OGDYNAMIC_DEV_SERVER : 'http://127.0.0.1:5173';

		return untrailingslashit( $server );
	}
}
