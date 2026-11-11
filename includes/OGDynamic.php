<?php
/**
 * Main plugin coordinator.
 *
 * @package OGD
 */

namespace OGDynamic;

use OGDynamic\Traits\Singleton;

final class OGDynamic {
	use Singleton;

	public function boot(): void {
		RestController::init();
		Block::register();

		if ( is_admin() ) {
			Admin::register();
		} else {
			MetaTags::register();
		}
	}
}
