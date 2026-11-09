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
		add_action( 'admin_head', array( self::class, 'print_menu_icon_styles' ) );
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
			'none',
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

		if ( null === $asset ) {
			add_action( 'admin_notices', array( self::class, 'render_build_notice' ) );
			return;
		}

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
	 * Base64-encoded menu icon SVG (tightly-cropped viewBox so it fills the icon slot).
	 *
	 * @var string
	 */
	const MENU_ICON_SVG = 'data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PSIyMSAyMCAyMDkgMTk5IiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGFyaWEtaGlkZGVuPSJ0cnVlIj48cmVjdCB4PSIyOCIgeT0iMjciIHdpZHRoPSIxOTUiIGhlaWdodD0iMTg1IiByeD0iMzMiIHN0cm9rZT0iY3VycmVudENvbG9yIiBzdHJva2Utd2lkdGg9IjE0Ii8+PGNpcmNsZSBjeD0iNzguNyIgY3k9IjgxLjciIHI9IjE1LjYiIGZpbGw9ImN1cnJlbnRDb2xvciIvPjxyZWN0IHg9IjEzMC43IiB5PSI3MS45IiB3aWR0aD0iNjMuNyIgaGVpZ2h0PSIyOCIgcng9IjE0IiBmaWxsPSJjdXJyZW50Q29sb3IiLz48cmVjdCB4PSIxMzAuNyIgeT0iMTE1LjMiIHdpZHRoPSI1NC42IiBoZWlnaHQ9IjIzLjgiIHJ4PSIxMS45IiBmaWxsPSJjdXJyZW50Q29sb3IiLz48cGF0aCBkPSJNNTEuMzk5NiAxNjQuMzQ5TDg2LjQ5OTYgMTE4LjA5OUM5MC44MzI5IDExMi40OTMgOTUuNTk5NiAxMTIuOTYgMTAwLjggMTE5LjVMMTM1LjkgMTY0LjM0OUMxNDAuMjMzIDE3MS44MjMgMTM4LjUgMTc1LjU2MSAxMzAuNyAxNzUuNTYxSDYwLjQ5OTZDNTAuMDk5NiAxNzUuNTYxIDQ3LjA2NjIgMTcxLjgyMyA1MS4zOTk2IDE2NC4zNDlaIiBmaWxsPSJjdXJyZW50Q29sb3IiLz48L3N2Zz4=';

	/**
	 * Outputs the admin menu icon CSS.
	 *
	 * Core recolors menu icons by setting `color` on the icon container; a base64
	 * data-URI passed to add_menu_page() is rendered as a background image instead,
	 * so it keeps a fixed dark color and turns into an unreadable box on the active
	 * (blue) menu item. Rendering the icon as a CSS mask makes it inherit those
	 * colors: gray by default, blue on hover, white on the active item.
	 *
	 * @return void
	 */
	public static function print_menu_icon_styles(): void {
		echo '<style id="ogdynamic-menu-icon">'
			. '#adminmenu li#toplevel_page_ogdynamic .wp-menu-image::before{'
			. 'content:"";display:block;box-sizing:border-box;width:20px;height:20px;'
			. 'margin:7px auto 0;padding:0;color:inherit;background-color:currentColor;'
			. '-webkit-mask:url("' . esc_attr( self::MENU_ICON_SVG ) . '") center / contain no-repeat;'
			. 'mask:url("' . esc_attr( self::MENU_ICON_SVG ) . '") center / contain no-repeat;'
			. '}</style>';
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
	 * @return array|null Array with 'js' and 'css' file paths, or null when the production build is missing.
	 */
	private static function asset_files(): ?array {
		$manifest_path = OGDYNAMIC_PATH . 'dist/admin/.vite/manifest.json';

		if ( ! file_exists( $manifest_path ) ) {
			return null;
		}

		$manifest = wp_json_file_decode( $manifest_path, array( 'associative' => true ) );
		$entry    = is_array( $manifest ) ? ( $manifest['src/admin/main.ts'] ?? null ) : null;

		if ( ! is_array( $entry ) || empty( $entry['file'] ) ) {
			return null;
		}

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
	 * Shows a notice when the production assets have not been built.
	 *
	 * @return void
	 */
	public static function render_build_notice(): void {
		echo '<div class="notice notice-error"><p>' .
			esc_html__( 'ogdynamic admin assets are missing. Run "pnpm build" in the plugin directory (or enable OGDYNAMIC_DEV for development).', 'ogdynamic' ) .
			'</p></div>';
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
