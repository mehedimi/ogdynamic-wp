<?php
/**
 * Plugin Name: ogdynamic
 * Plugin URI: https://ogdynamic.com
 * Description: Connect WordPress to ogdynamic and generate dynamic Open Graph images for posts, pages, products, and archives.
 * Version: 0.1.0
 * Author: ogdynamic
 * Author URI: https://ogdynamic.com
 * Text Domain: ogdynamic
 * Requires at least: 6.3
 * Requires PHP: 7.4
 *
 * @package OGD
 */

use OGD\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OGD_VERSION', '0.1.0' );
define( 'OGD_FILE', __FILE__ );
define( 'OGD_PATH', plugin_dir_path( __FILE__ ) );
define( 'OGD_URL', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'OGD_API' ) ) {
	define( 'OGD_API', 'https://ogdynamic.com/api' );
}

$ogd_autoload = OGD_PATH . 'vendor/autoload.php';

if ( ! file_exists( $ogd_autoload ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'ogdynamic requires Composer autoload files. Run composer install or composer dump-autoload in the plugin directory.', 'ogdynamic' ) . '</p></div>';
		}
	);

	return;
}

require_once $ogd_autoload;

add_action(
	'plugins_loaded',
	static function () {
		Plugin::instance()->boot();
	}
);
