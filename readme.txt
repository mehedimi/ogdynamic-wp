=== ogdynamic ===
Contributors: mehedimi
Tags: open graph, social images, dynamic images, woocommerce, seo
Requires at least: 6.3
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect WordPress to ogdynamic and generate dynamic Open Graph images for posts, pages, products, and archives.

== Description ==

**ogdynamic** connects your WordPress site to [ogdynamic.com](https://ogdynamic.com) to automatically generate dynamic Open Graph images for your content.

= Features =

* **Dynamic OG Images** - Automatically generate Open Graph images for posts, pages, products, and archive pages
* **WooCommerce Support** - Product images include price, SKU, stock status, and attributes
* **SEO Plugin Compatibility** - When ogdynamic generates an OG image, it automatically prevents conflicts with popular SEO plugins by disabling their og:image output
* **Field Mapping** - Map WordPress content to image template variables using an intuitive admin interface
* **Archive Pages** - Support for homepage, blog, category, tag, author, date, and search result pages
* **Performance** - Images served via CDN for fast loading

= How It Works =

1. Connect your site with an API key from ogdynamic.com
2. Choose an OG image template for each content type
3. Map WordPress fields to template variables
4. ogdynamic automatically generates the right image for each page

= External Service =

This plugin connects to the ogdynamic service at [ogdynamic.com](https://ogdynamic.com).

The service is used to:

* Verify your API key when you connect the plugin
* Fetch your ogdynamic account and design/template data in the WordPress admin
* Generate and serve dynamic Open Graph images from ogdynamic infrastructure

When an OG image is configured, the plugin adds an image URL from `cdn.ogdynamic.com` to the page's social meta tags. The URL contains the selected template/design ID and the mapped field values needed to render that image, such as title, excerpt, featured image URL, product price, SKU, categories, tags, or other fields you choose in the mapping UI.

Normal site visitors do not download the generated OG image during a regular page load. Social platforms and link preview tools fetch the image from ogdynamic's CDN when someone shares or previews the page URL.

An ogdynamic account and API key are required because the image rendering work is performed by ogdynamic's servers instead of your WordPress server.

Service links:

* Service: [https://ogdynamic.com](https://ogdynamic.com)
* API token page: [https://ogdynamic.com/settings/tokens](https://ogdynamic.com/settings/tokens)
* Terms of Service: [https://ogdynamic.com/terms](https://ogdynamic.com/terms)
* Privacy Policy: [https://ogdynamic.com/privacy](https://ogdynamic.com/privacy)
* Source code: [https://github.com/mehedimi/ogdynamic-wp](https://github.com/mehedimi/ogdynamic-wp)

== Installation ==

= Automatic Installation =

1. Go to Plugins > Add New in WordPress
2. Search for "ogdynamic"
3. Click Install Now and activate the plugin

= Manual Installation =

1. Upload the `ogdynamic` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu in WordPress
3. Navigate to the ogdynamic admin page
4. Enter your API key from ogdynamic.com
5. Configure templates for each content type

= Requirements =

* WordPress 6.3 or higher
* PHP 7.4 or higher
* An ogdynamic.com account with an API key

== Frequently Asked Questions ==

= Do I need an ogdynamic.com account? =

Yes. ogdynamic images are generated on ogdynamic's servers because rendering social images is a heavier computational task than WordPress should handle during normal page loads. Your API key connects your WordPress site to that image generation service. You can create an API key from [ogdynamic.com/settings/tokens](https://ogdynamic.com/settings/tokens).

= What does the plugin generate? =

ogdynamic generates dynamic Open Graph image URLs and adds the required social image meta tags for configured content. The generated image uses the ogdynamic design you activate for a post type or archive type.

= Does image generation slow down my website? =

No. The plugin only generates the image URL and adds it to the page's social meta tags. Normal visitors do not load the OG image during a regular page view. The image is fetched by social platforms when someone shares your link. Images are generated and served from ogdynamic's infrastructure, with a Rust-powered image generation service designed for fast rendering.

= How do template mappings work? =

Choose one of your ogdynamic designs, then map its overridable fields to WordPress sources such as post title, excerpt, featured image, site name, product price, SKU, stock status, categories, tags, or attributes. Static text should be set directly in the design on ogdynamic.com.

= What happens if a post type has no template? =

If a specific post type does not have an active template, ogdynamic can fall back to the Default template when you configure one. If no matching template exists, ogdynamic does not output an OG image for that page.

= Does it support archive pages? =

Yes. You can configure templates for supported archive targets such as the homepage, blog listing, category, tag, author, date, and search pages.

= Which SEO plugins are supported? =

ogdynamic works completely independently and doesn't require any SEO plugin to function.

However, if you have one of these SEO plugins installed, ogdynamic will automatically prevent duplicate social image tags when ogdynamic has generated an image for the page:

* Rank Math
* Yoast SEO
* All in One SEO Pack (AIOSEO)
* SEOPress
* The SEO Framework
* Squirrly SEO
* Slim SEO

If a page doesn't have an ogdynamic image configured, the SEO plugin's og:image tags will be used normally.

= Does it support WooCommerce? =

Yes. When WooCommerce is active, ogdynamic adds a Product template target. You can map product title, featured image, formatted prices with currency symbols, SKU, stock status, categories, tags, and product attributes.

= Can I use custom post types? =

Yes, when the post type is available to the plugin. You can also configure the Default template as a fallback for post types without a specific template.

== Changelog ==

= 0.1.0 =
* MVP release with Vue 3 admin interface
* Template configuration per post type
* Field mapping between WordPress data and OG image templates
* WooCommerce product support
* SEO plugin compatibility - prevents conflicts with Rank Math, Yoast, AIOSEO, SEOPress, The SEO Framework, and Squirrly when ogdynamic generates an image
* Archive page support (homepage, blog, category, tag, author, date, search)
* Multisite support

== Upgrade Notice ==

= 0.1.0 =
Initial MVP release. Update ogdynamic settings after upgrading from any pre-1.0 version.
