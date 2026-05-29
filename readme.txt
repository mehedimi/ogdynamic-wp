=== ogdynamic ===
Contributors: mehedimi
Tags: open graph images, social media images, og image generator, woocommerce, seo
Requires at least: 6.3
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically generate beautiful Open Graph images for your WordPress posts, pages, WooCommerce products, and archive pages. Boost your social media link previews on Facebook, Twitter, LinkedIn, and more.

== Description ==

When you share a link on Facebook, Twitter, LinkedIn, or Slack, the first thing people see is the link preview image. Without a compelling Open Graph image, your content gets ignored. Most WordPress sites either show the same generic image for every page or rely on the featured image alone — neither makes your content stand out.

**ogdynamic** solves this by automatically generating professional, dynamic Open Graph images for every page on your WordPress site. It connects to [ogdynamic.com](https://ogdynamic.com) and uses customizable templates to create unique social media images that include your post title, author, excerpt, product price, categories, and more — all without slowing down your website.

= Why Use ogdynamic? =

* **Increase click-through rates** — Eye-catching social preview images get more clicks from Facebook, Twitter, LinkedIn, and other platforms
* **Save hours of design work** — No need to manually create share images for every post or product
* **Works with your existing setup** — Compatible with popular SEO plugins like Yoast SEO, Rank Math, AIOSEO, SEOPress, and more
* **Set it and forget it** — Configure once and every new post automatically gets its own unique OG image
* **eCommerce ready** — Full WooCommerce support with product price, SKU, stock status, and attributes in your social images

= Features =

* **Dynamic Open Graph Images** — Automatically generate unique OG images for posts, pages, products, and archives using your content
* **Facebook, Twitter & LinkedIn Ready** — Outputs proper og:image, og:image:width, og:image:height, and twitter:image meta tags
* **WooCommerce Integration** — Product OG images can include title, price, regular price, sale price, SKU, currency, stock status, rating, review count, categories, tags, and attributes
* **Archive Page Support** — Generate social images for homepage, blog page, category archives, tag archives, author pages, date archives, and search results
* **Field Mapping** — Choose exactly which WordPress data appears in each template field using an intuitive admin interface
* **SEO Plugin Friendly** — Automatically works alongside Yoast SEO, Rank Math, AIOSEO, SEOPress, The SEO Framework, Squirrly SEO, and Slim SEO without duplicate tags
* **CDN Delivered** — Images are generated and served from ogdynamic's fast CDN — zero impact on your WordPress server performance
* **Fallback Support** — Set a default template for any post type without a specific template configured

= How It Works =

1. Connect your WordPress site to ogdynamic via secure OAuth from the admin dashboard
2. Choose a template from your ogdynamic account for each content type (posts, pages, products, archives)
3. Map your WordPress content fields to the template variables (title, subtitle, image, price, etc.)
4. ogdynamic automatically generates the right Open Graph image for every page — no ongoing effort needed

= Template Mappings =

Each template has overridable fields that you map to WordPress data. For example, you can map your post title to the template's headline field, your featured image to the background, and your author name to the byline.

Available WordPress fields by content type:
- **All content types**: Site name, site tagline
- **Posts & Pages**: Post title, excerpt, trimmed content, featured image, author name, published date, modified date, category, tags
- **Products (WooCommerce)**: All post fields plus product short description, product price, regular price, sale price, SKU, product category, product tags, product attributes, stock status, rating, review count
- **Archive pages**: Site name, site tagline, plus category or tag names for their respective archives

= External Service =

This plugin connects to the ogdynamic service at [ogdynamic.com](https://ogdynamic.com).

The service is used to:

* Authenticate your site via OAuth when you connect the plugin
* Fetch your ogdynamic account and template data in the WordPress admin
* Generate and serve dynamic Open Graph images from ogdynamic's infrastructure

When an OG image is configured, the plugin adds the generated image URL to your page's social meta tags. Normal site visitors do not download the OG image during a regular page load. Social platforms and link preview tools fetch the image from ogdynamic's CDN when someone shares or previews the page URL.

An ogdynamic account is required because the image rendering work is performed by ogdynamic's servers instead of your WordPress server.

Service links:

* Service: [https://ogdynamic.com](https://ogdynamic.com)
* Terms of Service: [https://ogdynamic.com/terms](https://ogdynamic.com/terms)
* Privacy Policy: [https://ogdynamic.com/privacy](https://ogdynamic.com/privacy)

This plugin's source code is available at [https://github.com/mehedimi/ogdynamic-wp](https://github.com/mehedimi/ogdynamic-wp).

== Installation ==

= Automatic Installation =

1. Go to Plugins > Add New in WordPress
2. Search for "ogdynamic"
3. Click Install Now and activate the plugin

= Manual Installation =

1. Upload the `ogdynamic` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu in WordPress
3. Navigate to the ogdynamic admin page
4. Connect your ogdynamic.com account via OAuth
5. Configure templates for each content type

= Requirements =

* WordPress 6.3 or higher
* PHP 7.4 or higher
* An ogdynamic.com account (OAuth connection required)

== Frequently Asked Questions ==

= How do I add Open Graph images to my WordPress site? =

Install and activate ogdynamic, connect your ogdynamic.com account via OAuth, choose a template for each content type, and you're done. The plugin automatically adds og:image meta tags to every configured page.

= Do I need an ogdynamic.com account? =

Yes. Image generation happens on ogdynamic's servers, so your WordPress site stays fast. The plugin connects via secure OAuth — just log in with your ogdynamic account from the WordPress admin and you're set. No manual API key setup needed. Create a free account at [ogdynamic.com](https://ogdynamic.com).

= What Open Graph tags does this plugin add? =

ogdynamic adds `og:image`, `og:image:width`, `og:image:height`, `twitter:card`, and `twitter:image` meta tags to your pages. Images are 1200x630 pixels — the recommended size for Facebook, Twitter, and LinkedIn link previews.

= Will this slow down my WordPress site? =

No. The plugin only generates the image URL and adds a few meta tags to your page. The actual image is generated and served from ogdynamic's CDN. Your visitors never download the OG image during normal browsing — only social platforms fetch it when someone shares your link.

= Can I use this with my SEO plugin? =

Yes. ogdynamic works alongside Yoast SEO, Rank Math, All in One SEO Pack (AIOSEO), SEOPress, The SEO Framework, Squirrly SEO, and Slim SEO. When ogdynamic generates an OG image for a page, it automatically prevents the SEO plugin from outputting a duplicate og:image tag. If a page has no ogdynamic image configured, your SEO plugin's tags are used as normal.

= How do template mappings work? =

Each ogdynamic design has overridable fields like title, subtitle, and image. In the WordPress admin, you map these to WordPress data — for example, mapping the template's title field to your post title, or the image field to your featured image. The plugin then sends those values to ogdynamic, which renders them into your OG image. Empty fields are automatically skipped.

= What happens if I haven't set a template for a post type? =

You can configure a Default template as a fallback for any post type without its own template. If no default is set either, ogdynamic simply won't output an OG image for that content type, and your SEO plugin or theme will handle it normally.

= Does this work for WooCommerce product pages? =

Yes. When WooCommerce is active, ogdynamic adds a Product template option. You can include product-specific data like formatted price (with currency symbol), regular price, sale price, SKU, stock status, average rating, review count, product categories, tags, and attributes in your product OG images.

= Does it support category and tag archive pages? =

Yes. You can configure OG images for homepage, blog listing, category archives, tag archives, author pages, date archives, and search results pages.

= Can I use this with custom post types? =

Yes, any public post type is available. You can also set up a Default template as a fallback for all post types that don't have a dedicated template configured.

= What image size does ogdynamic use? =

OG images are generated at 1200x630 pixels, which is the recommended Open Graph image size for Facebook, Twitter/X, LinkedIn, and most social platforms.

== Changelog ==

= 0.1.0 =
* MVP release with Vue 3 admin interface
* Template configuration per post type
* Field mapping between WordPress data and OG image templates
* WooCommerce product support
* SEO plugin compatibility - prevents conflicts with Rank Math, Yoast, AIOSEO, SEOPress, The SEO Framework, Squirrly SEO, and Slim SEO when ogdynamic generates an image
* Archive page support (homepage, blog, category, tag, author, date, search)
* Multisite support

== Upgrade Notice ==

= 0.1.0 =
Initial MVP release. Update ogdynamic settings after upgrading from any pre-1.0 version.
