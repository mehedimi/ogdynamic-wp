<?php
/**
 * REST API bootstrap.
 *
 * @package OGD
 */

namespace OGDynamic;

use OGDynamic\Controllers\ConnectionController;
use OGDynamic\Controllers\TemplatesController;

class RestController {
	public static function init(): void {
		add_action( 'rest_api_init', array( self::class, 'register_routes' ) );
	}

	public static function register_routes(): void {
		ConnectionController::init();
		TemplatesController::init();
	}
}
