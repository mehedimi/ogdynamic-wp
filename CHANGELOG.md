# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.4.0] - 2026-11-11

### Added
- "Convert to Image block" transform on the Canvas Image block: once an image is saved to the media library, the block can be handed off to `core/image` with one click for the full WordPress image toolset (sizing, aspect-ratio crop, focal point, caption, link, lightbox)
- Refreshed plugin screenshots (wp.org `assets/`) covering the redesigned admin UI, the Canvas Image block, and a saved banner rendered on the frontend

### Changed
- Plugin tagline and description now cover the full feature set — Open Graph and social media image generation plus any-size canvas images (ad banners, product photo cards, social graphics)

### Fixed
- The OAuth callback no longer fails with "Cannot modify header information - headers already sent": the token exchange and redirect now run on the admin `load-*` action before any output instead of inside the page render callback
- Canvas images rendered on the frontend by the Canvas Image block can no longer overflow their container: the `<img>` element gets inline `max-width:100%` and `height:auto`

## [0.3.0] - 2026-11-11

### Added
- Gutenberg block `ogdynamic/canvas-image`: pick any public canvas, fill its dynamic fields in the inspector (the live preview only re-renders when a field loses focus) and use **Generate** to download the rendered image into the media library; the canvas id, field values and attachment id are stored in the block's attributes JSON and the frontend renders the saved media image
- REST endpoints for the block (`GET /block/canvases`, `GET /block/canvases/{id}`, `POST /block/generate`), available to users who can edit posts; the generate endpoint reuses an existing upload for the same canvas and fields instead of downloading again
- The block editor script is written in TypeScript and built with `pnpm build` into `dist/block` (`vite.block.config.ts`)
- Image format selection in the block inspector: PNG (default), WebP or JPEG; non-PNG formats request the render with `config[format]=FORMAT`
- Canvas selectors list all public canvases regardless of size, showing each canvas' dimensions with a Recommended badge on 1200x630
- Canvas mappings store the canvas' `width`/`height`; Twitter card meta is only emitted for Twitter-compatible sizes — 1200x630 renders the `intent=twitter` variant, 1200x600 reuses the `og:image` URL as `twitter:image`, other sizes output `og:image` only
- ACF sources are grouped under their field group titles with an `ACF` badge in the field mapping dropdown
- Cursor pagination for canvas lists: the admin mapping selector and the block editor picker lazy-load pages (`next_cursor`) while scrolling, so accounts with many canvases stay responsive

### Changed
- Admin UI re-skinned to the ogdynamic app design system v2.1: purple `#635BFF` primary, slate neutrals, semantic success/warning/destructive tokens, new logo and WordPress menu icon
- Templates admin page renamed to "Open Graph Templates", with per-post-type SVG icons in the overview

### Fixed
- A stale OAuth token no longer disconnects the site: the connection recovers on a 401 and `GET /connection` returns the refreshed access token without a page reload
- An admin notice is shown when the production build is missing
- The admin menu icon renders as a CSS mask so it follows the admin menu colors
- `ogdy_*` transients are deleted during uninstall
- The Twitter image is also emitted through AIOSEO's dedicated Twitter tags filter
- The block's "Unsaved changes" detection compares the format-augmented query, so switching formats (PNG/WebP/JPEG) is detected correctly

### Removed
- Deprecated design templates: the selector no longer lists designs, the plugin no longer calls `v1/designs` or requests the `designs:read` OAuth scope, and `PUT /templates/{post_type}` accepts `type: canvas` only
- Saved `type: design` mappings: they stop rendering and are removed on first load — pick a canvas for the affected post type to generate OG images again

## [0.2.0] - 2026-10-11

### Added
- Canvas template support: map post types to ogdynamic canvases (mapping `type: canvas|design`, absent = design) rendered via the `/c/` CDN segment
- Canvas-aware mapping UI: grouped canvas/design selector fetching only public 1200x630 canvases (`v1/canvases?status=public&width=1200&height=630`)
- Warnings for saved mappings pointing to private or non-1200x630 canvases, plus a reconnect notice for tokens lacking the `canvases:read` scope
- Advanced Custom Fields (ACF) integration: location-matched fields become mappable sources (`acf_{field_key}`), resolved at render time in any ACF return format
- Twitter/X image variant for canvases via flat `intent=twitter` param

### Changed
- Designs are deprecated in favor of canvases; existing design mappings keep working
- Multi-value sources (tags, product tags, product attributes) are excluded from canvas mappings (flat string params only)
- OAuth scope now includes `canvases:read`

### Fixed
- Out-of-order template detail responses can no longer desync the mapping form (sequence-token guard)
- Orphaned field mapping entries are pruned when switching templates
- ACF image/file fields using the array return format resolve to the attachment URL instead of a comma-joined string
- ACF taxonomy fields render term names
- Combobox search input now filters options
- Duplicate error notices on canvas-list failure

## [0.1.0] - 2025-05-08

### Added
- MVP admin interface with Vue 3 + TypeScript
- Template configuration UI for post types
- Field mapping between WordPress data and OG image templates
- SearchableSelectInput component for template selection
- Auto-mapping of empty fields when loading design
- REST API endpoints for template management (`/wp-json/ogdynamic/v1/templates`)
- SEO plugin integration (Rank Math, Yoast SEO, AIOSEO, SEOPress, The SEO Framework, Squirrly SEO)
- WooCommerce product support (prices, SKU, attributes, stock status, ratings)
- Support for archive pages (homepage, blog, category, tag, author, date, search)
- Dynamic OG image URL generation via ogdynamic CDN
- Twitter card image URL generation
- Loading skeleton states in admin UI
- Dashboard with connection status and token owner info
- Onboarding flow with API key connection
- Multisite support with network-wide uninstall cleanup

### Changed
- Improved dashboard UI layout and styling
- Simplified asset loading in Admin.php
- Updated Vite config with base path and rolldownOptions
- Fixed deactivate action to use proper DELETE API with loading state
- Style form inputs with consistent design

### Fixed
- PHPCS compliance for WordPress coding standards
- TypeScript type checking across admin components
- isConnected check in dashboard for proper loading state

### Security
- Proper permission checks on all REST endpoints (`manage_options` capability required)
- Input sanitization and validation on all user-supplied data