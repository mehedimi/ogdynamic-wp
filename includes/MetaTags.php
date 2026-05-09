<?php
/**
 * Frontend Open Graph/Twitter meta output.
 *
 * @package OGD
 */

namespace OGD;

class MetaTags {

	public static function register(): void {
		if ( self::is_rank_math_active() ) {
			self::register_rank_math_filters();
		}

		if ( self::is_yoast_seo_active() ) {
			self::register_yoast_seo_filters();
		}

		if ( self::is_aioseo_active() ) {
			self::register_aioseo_filters();
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

		add_action( 'wp_head', array( self::class, 'output' ), PHP_INT_MAX );
	}

	public static function output(): void {
		if ( ! ImageGenerator::has_generated_image() ) {
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

	private static function register_rank_math_filters(): void {
		add_filter(
			'rank_math/opengraph/facebook/image',
			static function ( $image ) {
				return ImageGenerator::has_generated_image() ? '' : $image;
			}
		);

		add_filter(
			'rank_math/opengraph/twitter/image',
			static function ( $image ) {
				return ImageGenerator::has_generated_image() ? '' : $image;
			}
		);

		add_filter(
			'rank_math/opengraph/pre_set_default_image',
			static function ( $pre_set ) {
				return ImageGenerator::has_generated_image() ? true : $pre_set;
			}
		);

		add_filter(
			'rank_math/opengraph/pre_set_content_image',
			static function ( $pre_set ) {
				return ImageGenerator::has_generated_image() ? true : $pre_set;
			}
		);
	}

	private static function register_yoast_seo_filters(): void {
		add_filter(
			'wpseo_opengraph_image',
			static function ( $image ) {
				return ImageGenerator::has_generated_image() ? '' : $image;
			}
		);

		add_filter(
			'wpseo_twitter_image',
			static function ( $image ) {
				return ImageGenerator::has_generated_image() ? '' : $image;
			}
		);
	}

	private static function register_aioseo_filters(): void {
		add_filter(
			'aioseo_facebook_tags',
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
			}
		);

		add_filter(
			'aioseo_twitter_tags',
			static function ( $tags ) {
				if ( ! ImageGenerator::has_generated_image() || ! is_array( $tags ) ) {
					return $tags;
				}

				unset(
					$tags['twitter:image']
				);

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
		return defined( 'RANK_MATH_VERSION' )
			|| self::is_plugin_active( 'seo-by-rank-math/rank-math.php' );
	}

	private static function is_yoast_seo_active(): bool {
		return defined( 'WPSEO_VERSION' )
			|| self::is_plugin_active( 'wordpress-seo/wp-seo.php' );
	}

	private static function is_aioseo_active(): bool {
		return defined( 'AIOSEO_VERSION' )
			|| function_exists( 'aioseo' )
			|| self::is_plugin_active( 'all-in-one-seo-pack/all_in_one_seo_pack.php' );
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
