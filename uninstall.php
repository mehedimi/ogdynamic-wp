<?php
/**
 * Optional uninstall cleanup.
 *
 * @package OGD
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$settings = get_option( 'ogd_settings', array() );
$cleanup  = is_array( $settings ) && ! empty( $settings['defaults']['cleanup_on_uninstall'] );

if ( ! $cleanup ) {
	return;
}

delete_option( 'ogd_settings' );
delete_transient( 'ogd_template_cache' );

$post_ids = get_posts(
	array(
		'post_type'      => 'any',
		'post_status'    => 'any',
		'fields'         => 'ids',
		'posts_per_page' => -1,
		'meta_key'       => '_ogd_override',
	)
);

foreach ( $post_ids as $post_id ) {
	delete_post_meta( (int) $post_id, '_ogd_override' );
}
