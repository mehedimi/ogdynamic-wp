# ogdynamic — Open Graph Image Generator for WordPress

![WordPress](https://img.shields.io/badge/WordPress-6.3%2B-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/License-GPLv2-green)

**ogdynamic** auto-generates dynamic Open Graph (OG) images for every WordPress post, page, WooCommerce product, and archive page — unique social share images built from your content, so every Facebook link preview, Twitter card, and LinkedIn snapshot looks fresh. Need more than OG images? The **Canvas Image Gutenberg block** renders any public canvas at **any size** — ad banners, product photo cards, social media graphics — straight into your media library as PNG, WebP, or JPEG.

Images are designed in the [ogdynamic](https://ogdynamic.com) canvas editor and rendered by ogdynamic's CDN, so your WordPress server stays fast.

## Features

- **Dynamic Open Graph images** — a unique OG image for every post, page, product, and archive, mapped from your content (title, excerpt, price, featured image, …)
- **Canvas Image block** — Gutenberg block that renders any public canvas at any size into the media library, in PNG, WebP, or JPEG, with a live editor preview
- **Twitter card support** — `twitter:card` and `twitter:image` output for Twitter-sized images, with the Open Graph image reused for 1200×600 canvases
- **WooCommerce integration** — product price, SKU, stock status, rating, categories, tags, and attributes as mappable fields
- **ACF support** — map Advanced Custom Fields (text, image, gallery, link, taxonomy, …) into your OG images
- **SEO plugin friendly** — no duplicate OG tags alongside Yoast SEO, Rank Math, AIOSEO, SEOPress, The SEO Framework, Squirrly SEO, and Slim SEO
- **CDN delivered** — images are generated and served from ogdynamic's infrastructure, with zero impact on site speed

## Installation

1. In WordPress, go to **Plugins → Add New** and search for "ogdynamic" (or upload the plugin folder to `/wp-content/plugins/`)
2. Activate the plugin and open the **ogdynamic** admin page
3. Connect your ogdynamic.com account via OAuth
4. Map a canvas to each content type, or add the **ogdynamic Canvas Image** block in the editor

Requirements: WordPress 6.3+, PHP 7.4+, an [ogdynamic.com](https://ogdynamic.com) account.

## Development

```bash
composer install        # autoloader (required — the plugin shows a notice without it)
pnpm install

pnpm dev                # admin UI dev server (http://127.0.0.1:5173)
pnpm build              # production build: admin app + block editor bundle
pnpm build:block        # block bundle only
pnpm dev:block          # watch the block bundle
pnpm typecheck          # vue-tsc

composer lint:php       # PHPCS (WordPress Coding Standards)
composer fix:php        # auto-fix PHPCS violations
```

Enable dev mode in `wp-config.php` to load the admin app from the Vite dev server instead of the built assets:

```php
define( 'OGDYNAMIC_DEV', true );
// optionally: define( 'OGDYNAMIC_DEV_SERVER', 'http://custom:5173' );
```

### Code layout

| Path | Purpose |
|------|---------|
| `ogdynamic.php` | Plugin bootstrap |
| `includes/` | PHP classes, PSR-4 under `OGDynamic\` |
| `includes/Controllers/` | REST controllers for `ogdynamic/v1` |
| `src/admin/` | Vue 3 + Tailwind admin app |
| `src/block/` | TypeScript/React source of the `ogdynamic/canvas-image` block |
| `blocks/` | `block.json` metadata |
| `dist/` | Built assets (admin: `.vite/manifest.json`, block: `dist/block/`) |

## Links

- [ogdynamic.com](https://ogdynamic.com) — service website
- [Terms of Service](https://ogdynamic.com/terms) · [Privacy Policy](https://ogdynamic.com/privacy)
- WordPress [readme.txt](readme.txt) — full plugin listing and FAQ

## License

[GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html)
