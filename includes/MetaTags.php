<?php
/**
 * Frontend Open Graph/Twitter meta output.
 *
 * @package OGD
 */

namespace OGD;

class MetaTags {

	private static bool $output_via_plugins = false;


	public static function register(): void {
		if ( self::is_rank_math_active() ) {
			self::handle_rank_math_og();
		}

		if ( self::is_yoast_seo_active() ) {
			self::handle_yoast_seo_filters();
		}

		if ( self::is_aioseo_active() ) {
			self::handle_aioseo_filters();
		}

		if ( self::is_seopress_active() ) {
			self::register_seopress_filters();
		}

		if ( self::is_the_seo_framework_active() ) {
			self::register_the_seo_framework_filters();
		}

		if ( self::is_squirrly_seo_active() ) {
			self::register_squirrly_seo_filters();
		}

		add_action( 'wp_head', array( self::class, 'output' ) );
	}

	public static function handle_rank_math_og(): void {

		self::$output_via_plugins = true;

		add_filter(
			'rank_math/opengraph/twitter/image',
			function ( $attachment_url ) {
				return ImageGenerator::has_generated_image() ? ImageGenerator::get_twitter_image_url() : $attachment_url;
			}
		);

		add_filter(
			'rank_math/opengraph/facebook/image',
			function ( $attachment_url ) {
				return ImageGenerator::has_generated_image() ? ImageGenerator::get_image_url() : $attachment_url;
			}
		);

		add_filter(
			'rank_math/opengraph/facebook/image_array',
			function ( $urls ) {
				if ( ! ImageGenerator::has_generated_image() ) {
					return $urls;
				}

				return array(
					'width'  => 1200,
					'height' => 630,
					'url'    => ImageGenerator::get_image_url(),
				);
			}
		);
	}

	public static function output(): void {
		if ( self::$output_via_plugins || ! ImageGenerator::has_generated_image() ) {
			return;
		}

		printf(
			'<!-- ogdynamic -->
<meta property="og:image" content="%s" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:image" content="%s" />
<!-- /ogdynamic -->
',
			esc_url( ImageGenerator::get_image_url() ),
			esc_url( ImageGenerator::get_twitter_image_url() )
		);
	}

	private static function handle_yoast_seo_filters(): void {
		self::$output_via_plugins = true;

		add_filter(
			'wpseo_opengraph_image',
			static function ( $image ) {
				return ImageGenerator::has_generated_image() ? ImageGenerator::get_image_url() : $image;
			}
		);

		add_filter(
			'wpseo_twitter_image',
			static function ( $image ) {
				return ImageGenerator::has_generated_image() ? ImageGenerator::get_twitter_image_url() : $image;
			}
		);

		add_filter(
			'wpseo_opengraph_image_type',
			static function ( $type ) {
				return ImageGenerator::has_generated_image() ? false : $type;
			}
		);

		add_filter(
			'wpseo_opengraph_image_width',
			static function ( $width ) {
				return ImageGenerator::has_generated_image() ? 1200 : $width;
			}
		);

		add_filter(
			'wpseo_opengraph_image_height',
			static function ( $height ) {
				return ImageGenerator::has_generated_image() ? 630 : $height;
			}
		);
	}

	private static function handle_aioseo_filters(): void {
		self::$output_via_plugins = true;

		add_filter(
			'aioseo_facebook_tags',
			static function ( $tags ) {
				if ( ! ImageGenerator::has_generated_image() || ! is_array( $tags ) ) {
					return $tags;
				}

				$tags['og:image']        = ImageGenerator::get_image_url();
				$tags['og:image:width']  = 1200;
				$tags['og:image:height'] = 630;

				return $tags;
			}
		);

		add_filter(
			'aioseo_twitter_tags',
			static function ( $tags ) {
				if ( ! ImageGenerator::has_generated_image() || ! is_array( $tags ) ) {
					return $tags;
				}

				$tags['twitter:image'] = ImageGenerator::get_twitter_image_url();

				return $tags;
			}
		);
	}

	private static function register_seopress_filters(): void {
		add_filter(
			'seopress_social_og_thumb',
			static function ( $image ) {
				return ImageGenerator::has_generated_image() ? '' : $image;
			}
		);
		add_filter(
			'seopress_social_twitter_card_thumb',
			static function ( $image ) {
				return ImageGenerator::has_generated_image() ? '' : $image;
			}
		);

		add_filter(
			'seopress_social_twitter_card_summary',
			static function ( $card ) {
				return ImageGenerator::has_generated_image() ? '' : $card;
			}
		);
	}

	private static function register_the_seo_framework_filters(): void {
		add_filter(
			'the_seo_framework_meta_render_data',
			static function ( $tags ) {
				if ( ! ImageGenerator::has_generated_image() || ! is_array( $tags ) ) {
					return $tags;
				}

				unset(
					$tags['og:image'],
					$tags['og:image:secure_url'],
					$tags['og:image:width'],
					$tags['og:image:height'],
					$tags['og:image:type'],
					$tags['twitter:card'],
					$tags['twitter:image']
				);

				return $tags;
			}
		);
	}

	private static function register_squirrly_seo_filters(): void {
		add_filter(
			'sq_open_graph',
			static function ( $tags ) {
				if ( ! ImageGenerator::has_generated_image() || ! is_array( $tags ) ) {
					return $tags;
				}

				unset(
					$tags['og:image'],
					$tags['og:image:secure_url'],
					$tags['og:image:width'],
					$tags['og:image:height'],
					$tags['og:image:type'],
					$tags['twitter:card']
				);

				return $tags;
			},
			11
		);

		add_filter(
			'sq_twitter_card',
			static function ( $tags ) {
				if ( ! ImageGenerator::has_generated_image() || ! is_array( $tags ) ) {
					return $tags;
				}

				unset(
					$tags['twitter:image']
				);

				return $tags;
			},
			11
		);
	}

	private static function is_rank_math_active(): bool {
		return defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' );
	}

	private static function is_yoast_seo_active(): bool {
		return defined( 'WPSEO_VERSION' ) || function_exists( 'YoastSEO' );
	}

	private static function is_aioseo_active(): bool {
		return defined( 'AIOSEO_VERSION' )
			|| function_exists( 'aioseo' );
	}

	private static function is_seopress_active(): bool {
		return defined( 'SEOPRESS_VERSION' )
			|| self::is_plugin_active( 'wp-seopress/seopress.php' );
	}

	private static function is_the_seo_framework_active(): bool {
		return function_exists( 'the_seo_framework' )
			|| self::is_plugin_active( 'autodescription/autodescription.php' );
	}

	private static function is_squirrly_seo_active(): bool {
		return defined( 'SQ_VERSION' )
			|| self::is_plugin_active( 'squirrly-seo/squirrly.php' );
	}

	private static function is_plugin_active( string $plugin ): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $plugin ) || is_plugin_active_for_network( $plugin );
	}
}
