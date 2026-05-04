<?php
/**
 * Classic editor override meta box.
 *
 * @package OGD
 */

namespace OGD;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MetaBox {
	private Settings $settings;
	private ImageGenerator $generator;

	public function __construct( Settings $settings, ImageGenerator $generator ) {
		$this->settings  = $settings;
		$this->generator = $generator;
	}

	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'add' ) );
		add_action( 'save_post', array( $this, 'save' ) );
	}

	public function add(): void {
		$settings = $this->settings->get();
		$enabled  = (array) $settings['defaults']['enabled_post_types'];

		foreach ( $enabled as $post_type ) {
			add_meta_box(
				'ogdynamic_override',
				__( 'ogdynamic Social Image', 'ogdynamic' ),
				array( $this, 'render' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	public function render( WP_Post $post ): void {
		$override = get_post_meta( $post->ID, '_ogd_override', true );
		$override = is_array( $override ) ? $override : array();
		$override = array_merge(
			array(
				'mode'               => 'inherit',
				'template_id'        => '',
				'custom_title'       => '',
				'custom_description' => '',
				'custom_image_url'   => '',
			),
			$override
		);
		$preview = $this->generator->generate_for_post( $post->ID );

		wp_nonce_field( 'ogd_save_override', 'ogd_override_nonce' );
		?>
		<p>
			<label for="ogd_mode"><?php esc_html_e( 'Status', 'ogdynamic' ); ?></label>
			<select id="ogd_mode" name="ogd_override[mode]" class="widefat">
				<option value="inherit" <?php selected( $override['mode'], 'inherit' ); ?>><?php esc_html_e( 'Use global settings', 'ogdynamic' ); ?></option>
				<option value="enabled" <?php selected( $override['mode'], 'enabled' ); ?>><?php esc_html_e( 'Enable override', 'ogdynamic' ); ?></option>
				<option value="disabled" <?php selected( $override['mode'], 'disabled' ); ?>><?php esc_html_e( 'Disable ogdynamic', 'ogdynamic' ); ?></option>
			</select>
		</p>
		<p>
			<label for="ogd_template_id"><?php esc_html_e( 'Template override', 'ogdynamic' ); ?></label>
			<input id="ogd_template_id" name="ogd_override[template_id]" class="widefat" type="text" value="<?php echo esc_attr( $override['template_id'] ); ?>" placeholder="<?php esc_attr_e( 'Template ID', 'ogdynamic' ); ?>" />
		</p>
		<p>
			<label for="ogd_custom_title"><?php esc_html_e( 'Custom title', 'ogdynamic' ); ?></label>
			<input id="ogd_custom_title" name="ogd_override[custom_title]" class="widefat" type="text" value="<?php echo esc_attr( $override['custom_title'] ); ?>" />
		</p>
		<p>
			<label for="ogd_custom_description"><?php esc_html_e( 'Custom description', 'ogdynamic' ); ?></label>
			<textarea id="ogd_custom_description" name="ogd_override[custom_description]" class="widefat" rows="3"><?php echo esc_textarea( $override['custom_description'] ); ?></textarea>
		</p>
		<p>
			<label for="ogd_custom_image_url"><?php esc_html_e( 'Custom image URL', 'ogdynamic' ); ?></label>
			<input id="ogd_custom_image_url" name="ogd_override[custom_image_url]" class="widefat" type="url" value="<?php echo esc_url( $override['custom_image_url'] ); ?>" />
		</p>
		<?php if ( '' !== $preview['url'] ) : ?>
			<p>
				<a class="button button-secondary" href="<?php echo esc_url( $preview['url'] ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Preview Image', 'ogdynamic' ); ?>
				</a>
			</p>
			<input class="widefat" readonly value="<?php echo esc_attr( $preview['url'] ); ?>" onclick="this.select();" />
		<?php else : ?>
			<p class="description"><?php echo esc_html( $preview['message'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function save( int $post_id ): void {
		if ( ! isset( $_POST['ogd_override_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ogd_override_nonce'] ) ), 'ogd_save_override' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw = isset( $_POST['ogd_override'] ) && is_array( $_POST['ogd_override'] ) ? wp_unslash( $_POST['ogd_override'] ) : array();
		$override = array(
			'mode'               => in_array( $raw['mode'] ?? 'inherit', array( 'inherit', 'enabled', 'disabled' ), true ) ? $raw['mode'] : 'inherit',
			'template_id'        => sanitize_text_field( $raw['template_id'] ?? '' ),
			'custom_title'       => sanitize_text_field( $raw['custom_title'] ?? '' ),
			'custom_description' => sanitize_textarea_field( $raw['custom_description'] ?? '' ),
			'custom_image_url'   => esc_url_raw( $raw['custom_image_url'] ?? '' ),
			'custom_params'      => array(),
		);

		update_post_meta( $post_id, '_ogd_override', $override );
	}
}
