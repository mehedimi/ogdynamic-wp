<?php
/**
 * Plugin Name: ogdynamic
 * Plugin URI: https://ogdynamic.com
 * Description: Connect WordPress to ogdynamic and generate dynamic Open Graph images for posts, pages, products, and archives.
 * Version: 0.1.0
 * Author: mehedimi
 * Author URI: https://mehedi.im
 * Text Domain: ogdynamic
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.3
 * Requires PHP: 7.4
 *
 * @package OGD
 */

use OGDynamic\OGDynamic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OGDYNAMIC_VERSION', '0.1.0' );
define( 'OGDYNAMIC_PATH', plugin_dir_path( __FILE__ ) );
define( 'OGDYNAMIC_URL', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'OGDYNAMIC_API' ) ) {
	define( 'OGDYNAMIC_API', 'https://ogdynamic.com/api' );
}

if ( ! defined( 'OGDYNAMIC_CDN' ) ) {
	define( 'OGDYNAMIC_CDN', 'https://cdn.ogdynamic.com/' );
}

define( 'OGDYNAMIC_CLIENT_ID', '019e3b00-cb44-7141-998d-afc08913d5d1' );

$ogdynamic_autoload = OGDYNAMIC_PATH . 'vendor/autoload.php';

if ( ! file_exists( $ogdynamic_autoload ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'ogdynamic requires Composer autoload files. Run composer install or composer dump-autoload in the plugin directory.', 'ogdynamic' ) . '</p></div>';
		}
	);

	return;
}

require_once $ogdynamic_autoload;

add_action(
	'plugins_loaded',
	static function () {
		OGDynamic::instance()->boot();
	}
);
