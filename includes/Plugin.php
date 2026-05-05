<?php
/**
 * Main plugin coordinator.
 *
 * @package OGD
 */

namespace OGD;

use OGD\Traits\Singleton;

final class Plugin {
	use Singleton;

	private function __construct() {}

	public function boot(): void {
		load_plugin_textdomain( 'ogdynamic', false, dirname( plugin_basename( OGD_FILE ) ) . '/languages' );

		$settings  = new Settings();
		$generator = new ImageGenerator( $settings );

		( new Admin( $settings ) )->register();
		RESTController::init();
		( new MetaTags( $settings, $generator ) )->register();
	}
}
