=== ogdynamic ===
Contributors: (author name)
Tags: og:image, open graph, social media, ogdynamic, dynamic images, WooCommerce
Requires at least: 6.3
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
License: proprietary

Connect WordPress to ogdynamic and generate dynamic Open Graph images for posts, pages, products, and archives.

== Description ==

ogdynamic connects your WordPress site to [ogdynamic.com](https://ogdynamic.com) to automatically generate dynamic Open Graph images for your content.

= Features =

* **Dynamic OG Images** - Automatically generate Open Graph images for posts, pages, products, and archive pages
* **WooCommerce Support** - Product images include price, SKU, stock status, and attributes
* **SEO Plugin Compatibility** - Works with Rank Math, Yoast SEO, AIOSEO, SEOPress, The SEO Framework, and Squirrly SEO
* **Field Mapping** - Map WordPress content to image template variables using an intuitive admin interface
* **Archive Pages** - Support for homepage, blog, category, tag, author, date, and search result pages
* **Performance** - Images served via CDN for fast loading

= How It Works =

1. Connect your site with an API key from ogdynamic.com
2. Choose an OG image template for each content type
3. Map WordPress fields to template variables
4. ogdynamic automatically generates the right image for each page

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

Yes, you need to create an account at [ogdynamic.com](https://ogdynamic.com) to get an API key.

= Which SEO plugins are supported? =

ogdynamic is compatible with:
* Rank Math
* Yoast SEO
* All in One SEO Pack (AIOSEO)
* SEOPress
* The SEO Framework
* Squirrly SEO

When ogdynamic generates an image for a page, it automatically disables the Open Graph image from these plugins to prevent conflicts.

= Does it support WooCommerce? =

Yes, ogdynamic fully supports WooCommerce products. You can include product title, price, SKU, stock status, categories, tags, and attributes in your OG images.

= Can I use custom post types? =

Yes, you can create templates for custom post types. Use the "Default" template for any post type without a specific template.

== Changelog ==

= 0.1.0 =
* MVP release with Vue 3 admin interface
* Template configuration per post type
* Field mapping between WordPress data and OG image templates
* WooCommerce product support
* SEO plugin integration (Rank Math, Yoast, AIOSEO, SEOPress, The SEO Framework, Squirrly)
* Archive page support (homepage, blog, category, tag, author, date, search)
* Multisite support

== Upgrade Notice ==

= 0.1.0 =
Initial MVP release. Update ogdynamic settings after upgrading from any pre-1.0 version.