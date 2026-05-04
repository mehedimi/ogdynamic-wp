<?php
/**
 * Admin page and asset loading.
 *
 * @package OGD
 */

namespace OGD;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {
	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'script_loader_tag', array( $this, 'module_script_tag' ), 10, 3 );
	}

	public function add_menu(): void {
		add_menu_page(
			__( 'ogdynamic', 'ogdynamic' ),
			__( 'ogdynamic', 'ogdynamic' ),
			'manage_options',
			'ogdynamic',
			array( $this, 'render' ),
			'dashicons-format-image',
			58
		);

	}

	public function enqueue( string $hook ): void {
		if ( 'toplevel_page_ogdynamic' !== $hook ) {
			return;
		}

		if ( $this->is_dev_mode() ) {
			$this->enqueue_dev_assets();
			$this->localize_admin_script();
			return;
		}

		$asset = $this->asset_files();

		wp_enqueue_script(
			'ogdynamic-admin',
			$asset['js'],
			array(),
			OGD_VERSION,
			true
		);

		if ( '' !== $asset['css'] ) {
			wp_enqueue_style( 'ogdynamic-admin', $asset['css'], array(), OGD_VERSION );
		}

		$this->localize_admin_script();
	}

	public function module_script_tag( string $tag, string $handle, string $src ): string {
		if ( ! in_array( $handle, array( 'ogdynamic-vite-client', 'ogdynamic-admin' ), true ) ) {
			return $tag;
		}

		return sprintf(
			'<script type="module" src="%s" id="%s-js"></script>' . "\n",
			esc_url( $src ),
			esc_attr( $handle )
		);
	}

	private function enqueue_dev_assets(): void {
		$server = $this->dev_server_url();

		wp_enqueue_script(
			'ogdynamic-vite-client',
			$server . '/@vite/client',
			array(),
			null,
			true
		);

		wp_enqueue_script(
			'ogdynamic-admin',
			$server . '/src/admin/main.ts',
			array( 'ogdynamic-vite-client' ),
			null,
			true
		);
	}

	private function localize_admin_script(): void {
		wp_localize_script(
			'ogdynamic-admin',
			'ogdynamicAdmin',
			array(
				'restUrl'     => esc_url_raw( rest_url( 'ogdynamic/v1/' ) ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'settings'    => $this->settings->get_public_settings(),
				'postTypes'   => $this->settings->available_post_types(),
				'seoPlugin'   => $this->settings->detect_seo_plugin(),
				'woocommerce' => $this->settings->is_woocommerce_active(),
			)
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="wrap ogdynamic-wrap"><div id="ogdynamic-admin-app">';
		echo '<h1>' . esc_html__( 'ogdynamic', 'ogdynamic' ) . '</h1>';
		echo '<p>' . esc_html__( 'Loading ogdynamic admin…', 'ogdynamic' ) . '</p>';
		echo '</div></div>';
	}

	private function asset_files(): array {
		$manifest_path = OGD_PATH . 'dist/admin/.vite/manifest.json';

		if ( file_exists( $manifest_path ) ) {
			$manifest = json_decode( (string) file_get_contents( $manifest_path ), true );
			$entry    = $manifest['src/admin/main.ts'] ?? null;

			if ( is_array( $entry ) ) {
				return array(
					'js'  => OGD_URL . 'dist/admin/' . $entry['file'],
					'css' => isset( $entry['css'][0] ) ? OGD_URL . 'dist/admin/' . $entry['css'][0] : '',
				);
			}
		}

		return array(
			'js'  => OGD_URL . 'assets/admin/fallback.js',
			'css' => OGD_URL . 'assets/admin/fallback.css',
		);
	}

	private function is_dev_mode(): bool {
		return defined( 'OGDYNAMIC_DEV' ) && true === OGDYNAMIC_DEV;
	}

	private function dev_server_url(): string {
		$server = defined( 'OGDYNAMIC_DEV_SERVER' ) ? (string) OGDYNAMIC_DEV_SERVER : 'http://127.0.0.1:5173';

		return untrailingslashit( $server );
	}
}
