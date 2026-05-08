<?php
/**
 * Admin page and asset loading.
 *
 * @package OGD
 */

namespace OGD;

class Admin {
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
		add_filter( 'script_loader_tag', array( self::class, 'module_script_tag' ), 10, 3 );
	}

	public static function add_menu(): void {
		add_menu_page(
			__( 'ogdynamic', 'ogdynamic' ),
			__( 'ogdynamic', 'ogdynamic' ),
			'manage_options',
			'ogdynamic',
			array( self::class, 'render' ),
			'dashicons-format-image',
			58
		);
	}

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

		wp_enqueue_style( 'ogdynamic-admin', $asset['css'], array(), OGDYNAMIC_VERSION );
		self::localize_admin_script();
	}

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

	private static function localize_admin_script(): void {
		wp_localize_script(
			'ogdynamic-admin',
			'ogdynamicAdmin',
			array(
				'restUrl' => esc_url_raw( rest_url( 'ogdynamic/v1/' ) ),
				'apiUrl'  => esc_url_raw( OGDYNAMIC_API ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'apiKey'  => Settings::get_api_key(),
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

	private static function asset_files(): array {
		$manifest_path = OGDYNAMIC_PATH . 'dist/admin/.vite/manifest.json';

		$manifest = wp_json_file_decode( $manifest_path, array( 'associative' => true ) );
		$entry    = $manifest['src/admin/main.ts'] ?? null;

		return array(
			'js'  => OGDYNAMIC_URL . 'dist/admin/' . $entry['file'],
			'css' => isset( $entry['css'][0] ) ? OGDYNAMIC_URL . 'dist/admin/' . $entry['css'][0] : '',
		);
	}

	private static function is_dev_mode(): bool {
		return defined( 'OGDYNAMIC_DEV' ) && true === OGDYNAMIC_DEV;
	}

	private static function dev_server_url(): string {
		$server = defined( 'OGDYNAMIC_DEV_SERVER' ) ? (string) OGDYNAMIC_DEV_SERVER : 'http://127.0.0.1:5173';

		return untrailingslashit( $server );
	}
}
