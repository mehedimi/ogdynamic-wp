=== ogdynamic ===
Contributors: mehedimi
Tags: open graph, og image, og image generator, social media images, social share, twitter card, link preview, facebook preview, woocommerce, acf, gutenberg, seo
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Auto-generate dynamic Open Graph (OG) images for every post, page, product, and archive — the Open Graph image generator that keeps your social share previews, Facebook link previews, and Twitter cards fresh. Need more than OG images? The Canvas Image Gutenberg block saves any canvas size — ad banners, product photo cards, social graphics — straight to your media library.

== Description ==

When you share a link on Facebook, Twitter/X, LinkedIn, or Slack, the first thing people see is the link preview image. Without a compelling Open Graph image (og:image), your content gets ignored. Most WordPress sites either show the same generic image for every page or rely on the featured image alone — neither makes your content stand out in the feed.

**ogdynamic** is an Open Graph image generator that automatically creates professional, dynamic OG images for every page on your WordPress site. It connects to [ogdynamic.com](https://ogdynamic.com) and uses your canvas templates to generate a unique social share image for each post, product, or archive — including the post title, author, excerpt, product price, categories, and more — all without slowing down your website. And when you need more than OG images, the Canvas Image block turns any canvas into ad banners, product photo cards, or social media graphics saved right in your media library.

= Why Use ogdynamic? =

* **More clicks from social media** — Eye-catching social preview images and link previews get more clicks from Facebook, Twitter/X, LinkedIn, and other platforms
* **Save hours of design work** — No need to manually create share images for every post or product; your OG image generator runs on autopilot
* **Works with your existing setup** — Compatible with popular SEO plugins like Yoast SEO, Rank Math, AIOSEO, SEOPress, and more — no duplicate og:image tags
* **Set it and forget it** — Configure once and every new post automatically gets its own unique Open Graph image
* **eCommerce ready** — Full WooCommerce support with product price, SKU, stock status, and attributes in your social media images

= Features =

* **Dynamic Open Graph Images** — Automatically generate unique OG images for posts, pages, products, and archives using your content
* **Canvas Image Gutenberg Block** — Generate any canvas size in the block editor — ad banners, product photo cards, social media graphics — with a live preview, and save them to your media library as PNG, WebP, or JPEG
* **Facebook, Twitter/X & LinkedIn Ready** — Outputs proper og:image, og:image:width, og:image:height, twitter:card, and twitter:image meta tags for rich link previews
* **WooCommerce Integration** — Product OG images can include title, price, regular price, sale price, SKU, currency, stock status, rating, review count, categories, tags, and attributes
* **Archive Page Support** — Generate social share images for homepage, blog page, category archives, tag archives, author pages, date archives, and search results
* **Field Mapping** — Choose exactly which WordPress data appears in each canvas field using an intuitive admin interface
* **Canvas Editor** — Design your own OG image templates in the ogdynamic canvas editor; the mapping selector lists all your public canvases and recommends the 1200x630 ones
* **Custom Fields (ACF)** — Map Advanced Custom Fields data (text, image, gallery, link, taxonomy, relationship, and more) into your OG images
* **SEO Plugin Friendly** — Works alongside Yoast SEO, Rank Math, AIOSEO, SEOPress, The SEO Framework, Squirrly SEO, and Slim SEO without duplicate Open Graph tags
* **CDN Delivered** — OG images are generated and served from ogdynamic's fast CDN — zero impact on your WordPress server performance
* **Fallback Support** — Set a default template for any post type without a specific template configured

= How It Works =

1. Connect your WordPress site to ogdynamic via secure OAuth from the admin dashboard
2. Choose a template from your ogdynamic account for each content type (posts, pages, products, archives)
3. Map your WordPress content fields to the template variables (title, subtitle, image, price, etc.)
4. ogdynamic automatically generates the right Open Graph image for every page — no ongoing effort needed
5. For other image sizes, add the ogdynamic Canvas Image block to any post or page to generate ad banners, product photo cards, or social graphics into your media library

= Template Mappings =

Each template has overridable fields that you map to WordPress data. For example, you can map your post title to the template's headline field, your featured image to the background, and your author name to the byline.

Available WordPress fields by content type:
- **All content types**: Site name, site tagline
- **Posts & Pages**: Post title, excerpt, trimmed content, featured image, author name, published date, modified date, category, tags
- **Products (WooCommerce)**: All post fields plus product short description, product price, regular price, sale price, SKU, product category, product tags, product attributes, stock status, rating, review count
- **Archive pages**: Site name, site tagline, plus category or tag names for their respective archives
- **Custom fields (ACF)**: When Advanced Custom Fields is active, fields from groups assigned to the content type are listed as mapping sources

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

This plugin's source code is available at [https://github.com/ogdynamic-app/ogdynamic-wp](https://github.com/ogdynamic-app/ogdynamic-wp).

== Screenshots ==

1. **Onboarding screen** — Connect your WordPress site to ogdynamic via OAuth with a single click. See the benefits of dynamic OG images at a glance.
2. **Dashboard** — View your connection status and account details at a glance, with quick access to manage templates and update the connection.
3. **Open Graph Templates overview** — Browse all available post types and archive pages. Activated templates show a green badge so you can see which content types have OG images configured.
4. **Template mapping** — Select a canvas from your ogdynamic account, then map each template field to a WordPress data source with the live preview panel.
5. **Connection management** — Connect or disconnect your ogdynamic account. While connected, explore feature highlights for products, blog posts, reusable templates, and cross-platform previews.
6. **Canvas Image block** — Pick a public canvas in the block editor, fill in its dynamic fields with a live preview, and save the generated image straight to the media library.
7. **Saved canvas image on the frontend** — A banner generated with the Canvas Image block rendered in a post — any canvas size: ad banners, product photo cards, social graphics.

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

ogdynamic adds `og:image`, `og:image:width`, and `og:image:height` meta tags to every configured page. For images sized for Twitter — 1200x630 (the standard Open Graph size) or 1200x600 (Twitter's recommended 2:1 card size) — it also adds `twitter:card` and `twitter:image` so your cards render correctly on X/Twitter.

= Does it support Twitter/X card images? =

Yes. Canvases sized 1200x630 get a Twitter-optimized variant of your OG image, and canvases sized 1200x600 (Twitter's recommended summary_large_image ratio) reuse the Open Graph image directly. Other canvas sizes output the og:image tag only, which most platforms use for link previews.

= Will this slow down my WordPress site? =

No. The plugin only generates the image URL and adds a few meta tags to your page. The actual image is generated and served from ogdynamic's CDN. Your visitors never download the OG image during normal browsing — only social platforms fetch it when someone shares your link.

= Can I use this with my SEO plugin? =

Yes. ogdynamic works alongside Yoast SEO, Rank Math, All in One SEO Pack (AIOSEO), SEOPress, The SEO Framework, Squirrly SEO, and Slim SEO. When ogdynamic generates an OG image for a page, it automatically prevents the SEO plugin from outputting a duplicate og:image tag. If a page has no ogdynamic image configured, your SEO plugin's tags are used as normal.

= How do template mappings work? =

Each ogdynamic canvas has overridable fields like title, subtitle, and image. In the WordPress admin, you map these to WordPress data — for example, mapping the template's title field to your post title, or the image field to your featured image. The plugin then sends those values to ogdynamic, which renders them into your OG image. Empty fields are automatically skipped.

= What happens if I haven't set a template for a post type? =

You can configure a Default template as a fallback for any post type without its own template. If no default is set either, ogdynamic simply won't output an OG image for that content type, and your SEO plugin or theme will handle it normally.

= Does this work for WooCommerce product pages? =

Yes. When WooCommerce is active, ogdynamic adds a Product template option. You can include product-specific data like formatted price (with currency symbol), regular price, sale price, SKU, stock status, average rating, review count, product categories, tags, and attributes in your product OG images.

= Does it support category and tag archive pages? =

Yes. You can configure OG images for homepage, blog listing, category archives, tag archives, author pages, date archives, and search results pages.

= Can I use this with custom post types? =

Yes, any public post type is available. You can also set up a Default template as a fallback for all post types that don't have a dedicated template configured.

= What image size does ogdynamic use? =

OG images are generated at 1200x630 pixels, which is the recommended Open Graph image size for Facebook, Twitter/X, LinkedIn, and most social platforms. For other sizes, the Canvas Image block generates any canvas — ad banners, product photo cards, and more.

= Can I generate images other than Open Graph images? =

Yes. The ogdynamic Canvas Image block works with any public canvas in your account, whatever its size — ad banners, product photo cards, social media graphics, and more. Pick the canvas in the block editor, fill in its fields with a live preview, and save the rendered image to your media library in PNG, WebP, or JPEG. Open Graph meta tags are only managed when a template mapping is configured.

== Changelog ==

= 0.4.0 =
* New: saved canvas images can be converted to the regular WordPress Image block with one click, unlocking the full image toolset (sizing, aspect-ratio crop, focal point, caption, link, lightbox)
* Fix: completing the OAuth connection no longer ends in a "Cannot modify header information" warning — the callback now redirects back to the dashboard before any output is sent
* Fix: images rendered by the Canvas Image block are fully responsive — they never overflow the post column and keep their aspect ratio
* Updated the plugin description and added new screenshots of the redesigned admin, the Canvas Image block, and a saved banner on the frontend

= 0.3.0 =
* New "ogdynamic Canvas Image" block: pick a public canvas in the editor, fill in its dynamic fields with a live preview, and save the generated image straight to the media library in PNG, WebP or JPEG format
* The Canvas Image block accepts canvases of any size — ad banners, product photo cards, social graphics — not just 1200x630 OG images
* Design templates removed: the selector lists canvases only and saved design mappings stop rendering (they are removed on first load) — pick a canvas for the affected post type to generate OG images again
* Admin interface refreshed with the new ogdynamic branding and color scheme
* The canvas selector lists all public canvases of any size, shows canvas dimensions, and marks 1200x630 canvases as Recommended
* Mappings remember the canvas size: Twitter card tags are added for Twitter-friendly sizes (1200x630 and 1200x600), other sizes output og:image only
* ACF fields are grouped by field group with an ACF label in the mapping dropdown
* Long canvas lists load page by page while you scroll, so accounts with many canvases stay fast
* Reliability fixes: a stale connection recovers automatically instead of disconnecting, the Twitter image now appears under AIOSEO too, format changes in the block are detected as unsaved changes, and leftover transients are cleaned up on uninstall

= 0.2.0 =
* Canvas templates: design OG image templates in the ogdynamic canvas editor and map them to your content types (existing design mappings keep working but designs are deprecated)
* Template selector lists your public 1200x630 canvases and warns when a saved mapping points to a private or resized canvas
* Advanced Custom Fields (ACF) support: map ACF fields (text, image, gallery, link, taxonomy, relationship, and more) to template fields
* Orphaned field mappings are cleaned up automatically when switching templates
* Twitter/X image variant support for canvas templates

= 0.1.0 =
* MVP release with Vue 3 admin interface
* Template configuration per post type
* Field mapping between WordPress data and OG image templates
* WooCommerce product support
* SEO plugin compatibility - prevents conflicts with Rank Math, Yoast, AIOSEO, SEOPress, The SEO Framework, Squirrly SEO, and Slim SEO when ogdynamic generates an image
* Archive page support (homepage, blog, category, tag, author, date, search)
* Multisite support

== Upgrade Notice ==

= 0.4.0 =
Saved canvas images can now be converted to the regular WordPress Image block for advanced sizing and captions. This release also fixes the header warning some hosts showed right after connecting via OAuth.

= 0.3.0 =
Design templates are gone. Saved design mappings stop rendering and are removed — map a canvas to the affected content type to generate Open Graph images again.

= 0.2.0 =
Canvas templates are here. If you connected before canvases were introduced, reconnect your ogdynamic account once to refresh access, then map your content types to canvases.

= 0.1.0 =
Initial MVP release. Update ogdynamic settings after upgrading from any pre-1.0 version.
