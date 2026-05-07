<?php
/**
 * Optional uninstall cleanup.
 *
 * @package OGD
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$ogdynamic_settings = get_option( 'ogd_settings', array() );
$ogdynamic_cleanup  = is_array( $ogdynamic_settings ) && ! empty( $ogdynamic_settings['defaults']['cleanup_on_uninstall'] );

if ( ! $ogdynamic_cleanup ) {
	return;
}

delete_option( 'ogd_settings' );
delete_transient( 'ogd_template_cache' );

$ogdynamic_post_ids = get_posts(
	array(
		'post_type'      => 'any',
		'post_status'    => 'any',
		'fields'         => 'ids',
		'posts_per_page' => -1,
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_key'       => '_ogd_override',
	)
);

foreach ( $ogdynamic_post_ids as $ogdynamic_override_post_id ) {
	delete_post_meta( (int) $ogdynamic_override_post_id, '_ogd_override' );
}
