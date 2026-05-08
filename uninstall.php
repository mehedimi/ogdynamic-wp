<?php
/**
 * Plugin uninstall cleanup.
 *
 * @package OGD
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete all ogdynamic options for the current site.
 */
function ogdynamic_uninstall_delete_options(): void {
	global $wpdb;

	$ogdynamic_option_prefix = 'ogdy_';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$ogdynamic_option_names = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( $ogdynamic_option_prefix ) . '%'
		)
	);

	foreach ( $ogdynamic_option_names as $ogdynamic_option_name ) {
		delete_option( (string) $ogdynamic_option_name );
	}
}

/**
 * Delete ogdynamic data for the current site.
 */
function ogdynamic_uninstall_cleanup_site(): void {
	ogdynamic_uninstall_delete_options();
}

if ( is_multisite() ) {
	$ogdynamic_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $ogdynamic_site_ids as $ogdynamic_site_id ) {
		switch_to_blog( (int) $ogdynamic_site_id );
		ogdynamic_uninstall_cleanup_site();
		restore_current_blog();
	}
} else {
	ogdynamic_uninstall_cleanup_site();
}
