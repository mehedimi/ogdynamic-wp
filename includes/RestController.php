<?php
/**
 * REST API bootstrap.
 *
 * @package OGD
 */

namespace OGD;

use OGD\Controllers\ConnectionController;
use OGD\Controllers\TemplatesController;

class RESTController {
	public static function init(): void {
		add_action( 'rest_api_init', array( self::class, 'register_routes' ) );
	}

	public static function register_routes(): void {
		ConnectionController::init();
		TemplatesController::init();
	}
}
