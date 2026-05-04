<?php
/**
 * Main plugin coordinator.
 *
 * @package OGD
 */

namespace OGD;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {
	private static $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot(): void {
		load_plugin_textdomain( 'ogdynamic', false, dirname( plugin_basename( OGD_FILE ) ) . '/languages' );

		$settings  = new Settings();
		$generator = new ImageGenerator( $settings );

		( new Admin( $settings ) )->register();
		( new RestController( $settings, $generator ) )->register();
		( new MetaBox( $settings, $generator ) )->register();
		( new MetaTags( $settings, $generator ) )->register();
	}
}
