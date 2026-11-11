<?php
/**
 * Canvas image Gutenberg block.
 *
 * @package OGD
 */

namespace OGDynamic;

class Block {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	public const NAME = 'ogdynamic/canvas-image';

	/**
	 * Script / style handle of the editor bundle.
	 *
	 * @var string
	 */
	private const HANDLE = 'ogdynamic-block-editor';

	/**
	 * Registers the block hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( self::class, 'localize_editor_script' ) );
	}

	/**
	 * Registers the block type and its editor assets.
	 *
	 * The block is registered even when the editor bundle has not been built
	 * so posts that already contain it keep rendering on the frontend.
	 *
	 * @return void
	 */
	public static function register_block(): void {
		$args = array(
			'render_callback' => array( self::class, 'render' ),
		);

		$js_file  = 'dist/block/ogdynamic-block.js';
		$css_file = 'dist/block/ogdynamic-block.css';

		if ( file_exists( OGDYNAMIC_PATH . $js_file ) ) {
			wp_register_script(
				self::HANDLE,
				OGDYNAMIC_URL . $js_file,
				array( 'wp-api-fetch', 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-data', 'wp-element', 'wp-i18n' ),
				(string) filemtime( OGDYNAMIC_PATH . $js_file ),
				true
			);

			wp_set_script_translations( self::HANDLE, 'ogdynamic', OGDYNAMIC_PATH . 'languages' );

			$args['editor_script_handles'] = array( self::HANDLE );
		}

		if ( file_exists( OGDYNAMIC_PATH . $css_file ) ) {
			wp_register_style(
				self::HANDLE,
				OGDYNAMIC_URL . $css_file,
				array(),
				(string) filemtime( OGDYNAMIC_PATH . $css_file )
			);

			$args['editor_style_handles'] = array( self::HANDLE );
		}

		register_block_type( OGDYNAMIC_PATH . 'blocks/canvas-image', $args );
	}

	/**
	 * Passes runtime configuration to the editor script.
	 *
	 * @return void
	 */
	public static function localize_editor_script(): void {
		if ( ! wp_script_is( self::HANDLE, 'registered' ) ) {
			return;
		}

		$config = array(
			'cdnUrl'      => esc_url_raw( OGDYNAMIC_CDN ),
			'connected'   => '' !== Settings::get_refresh_token(),
			'settingsUrl' => current_user_can( 'manage_options' )
				? esc_url_raw( admin_url( 'admin.php?page=ogdynamic#/connection' ) )
				: '',
		);

		wp_add_inline_script(
			self::HANDLE,
			'window.ogdynamicBlock = ' . wp_json_encode( $config ) . ';',
			'before'
		);
	}

	/**
	 * Renders the saved media library image on the frontend.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Block HTML, empty until an image has been saved.
	 */
	public static function render( array $attributes ): string {
		$attachment_id = isset( $attributes['attachmentId'] ) ? absint( $attributes['attachmentId'] ) : 0;
		$alt           = isset( $attributes['alt'] ) ? (string) $attributes['alt'] : '';
		$image         = '';
		// Canvases may be any size, so the image must never overflow its
		// container and keep its aspect ratio.
		$style = 'max-width:100%;height:auto;';

		if ( $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ) {
			$attr = array( 'style' => $style );

			if ( '' !== $alt ) {
				$attr['alt'] = $alt;
			}

			$image = wp_get_attachment_image( $attachment_id, 'full', false, $attr );
		}

		if ( '' === $image && ! empty( $attributes['imageUrl'] ) ) {
			$image = sprintf(
				'<img src="%1$s" alt="%2$s" style="%3$s" />',
				esc_url( (string) $attributes['imageUrl'] ),
				esc_attr( $alt ),
				esc_attr( $style )
			);
		}

		if ( '' === $image ) {
			return '';
		}

		return sprintf(
			'<figure %1$s>%2$s</figure>',
			get_block_wrapper_attributes(),
			$image
		);
	}
}
