<?php
/**
 * Frontend Open Graph/Twitter meta output.
 *
 * @package OGD
 */

namespace OGD;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MetaTags {
	private Settings $settings;
	private ImageGenerator $generator;

	public function __construct( Settings $settings, ImageGenerator $generator ) {
		$this->settings  = $settings;
		$this->generator = $generator;
	}

	public function register(): void {
		add_action( 'wp_head', array( $this, 'output' ), 5 );
	}

	public function output(): void {
		if ( is_admin() ) {
			return;
		}

		$settings = $this->settings->get();
		if ( 'disabled' === $settings['defaults']['seo_mode'] ) {
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

		if ( 'full' === $settings['defaults']['meta_mode'] ) {
			$description = esc_attr( get_the_excerpt() ?: get_bloginfo( 'description' ) );
			$permalink   = esc_url( get_permalink() );
			echo '<meta property="og:title" content="' . $title . "\" />\n";
			echo '<meta property="og:description" content="' . $description . "\" />\n";
			echo '<meta property="og:url" content="' . $permalink . "\" />\n";
			echo '<meta property="og:type" content="article" />' . "\n";
			echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . "\" />\n";
			echo '<meta name="twitter:title" content="' . $title . "\" />\n";
			echo '<meta name="twitter:description" content="' . $description . "\" />\n";
		}

		echo "<!-- /ogdynamic -->\n";
	}
}
