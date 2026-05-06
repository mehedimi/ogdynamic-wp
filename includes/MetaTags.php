<?php
/**
 * Frontend Open Graph/Twitter meta output.
 *
 * @package OGD
 */

namespace OGD;

class MetaTags {
	private ImageGenerator $generator;

	public function __construct( ImageGenerator $generator ) {
		$this->generator = $generator;
	}

	public function register(): void {
		add_action( 'wp_head', array( $this, 'output' ), 5 );
	}

	public function output(): void {
		if ( is_admin() ) {
			return;
		}

		$result = null;
		if ( is_singular() ) {
			$result = $this->generator->generate_for_post( get_queried_object_id() );
		}

		if ( ! is_array( $result ) || empty( $result['url'] ) ) {
			return;
		}

		$url   = esc_url( $result['url'] );
		$title = esc_attr( wp_strip_all_tags( single_post_title( '', false ) ?: get_bloginfo( 'name' ) ) );
		$alt   = $title;

		echo "\n<!-- ogdynamic -->\n";
		echo '<meta property="og:image" content="' . $url . "\" />\n";
		echo '<meta property="og:image:secure_url" content="' . $url . "\" />\n";
		echo '<meta property="og:image:width" content="1200" />' . "\n";
		echo '<meta property="og:image:height" content="630" />' . "\n";
		echo '<meta property="og:image:alt" content="' . $alt . "\" />\n";
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		echo '<meta name="twitter:image" content="' . $url . "\" />\n";
		echo '<meta name="twitter:image:alt" content="' . $alt . "\" />\n";

		echo "<!-- /ogdynamic -->\n";
	}
}
