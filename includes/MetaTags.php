<?php
/**
 * Frontend Open Graph/Twitter meta output.
 *
 * @package OGD
 */

namespace OGD;

class MetaTags {

	public static function register(): void {
		add_action( 'wp_head', array( self::class, 'output' ), 5 );
	}

	public static function output(): void {

        if(!ImageGenerator::has_generated_image()) {
            return;
        }

		echo "\n<!-- ogdynamic -->\n";
		echo '<meta property="og:image" content="' . esc_url( ImageGenerator::get_image_url() ) . "\" />\n";
		echo '<meta property="og:image:width" content="1200" />' . "\n";
		echo '<meta property="og:image:height" content="630" />' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url( ImageGenerator::get_twitter_image_url() ) . "\" />\n";
		echo "<!-- /ogdynamic -->\n";
	}
}
