<?php
/**
 * Main plugin coordinator.
 *
 * @package OGD
 */

namespace OGD;

use OGD\Traits\Singleton;

final class OGDynamic {
	use Singleton;

	public function boot(): void {
		load_plugin_textdomain( 'ogdynamic', false, dirname( plugin_basename( OGDYNAMIC_FILE ) ) . '/languages' );

		RESTController::init();

		if ( is_admin() ) {
			Admin::register();
		} else {
			MetaTags::register();
		}
	}
}
