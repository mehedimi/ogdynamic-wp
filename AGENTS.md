# AGENTS.md

## Quick Start

**This is a WordPress plugin** that connects to the ogdynamic SaaS for dynamic Open Graph image generation.

### Build & Run

- **PHP linting**: `composer lint:php`
- **PHP fix**: `composer fix:php`
- **Frontend dev mode**: `pnpm dev` (runs on `http://127.0.0.1:5173`)
- **Frontend build**: `pnpm build` (admin app + block editor bundle; block only: `pnpm build:block`, watch: `pnpm dev:block`)
- **Type check**: `pnpm typecheck`

### Development Flags

- Enable dev mode: `define( 'OGDYNAMIC_DEV', true );`
- Custom dev server: `define( 'OGDYNAMIC_DEV_SERVER', 'http://custom:5173' );`

## Architecture

### Entry Points

- **Plugin bootstrap**: `ogdynamic.php`
- **Singleton coordinator**: `includes/OGDynamic.php`
- **Admin UI**: `src/admin/main.ts` → Vue 3 app
- **REST API**: `ogdynamic/v1/*` routes in `includes/RestController.php`

### Key Components

| Component | Location | Purpose |
|-----------|----------|---------|
| `OAuth` | `includes/OAuth.php` | OAuth 2.0 with PKCE flow |
| `Template` | `includes/Template.php` | Post type → template mapping |
| `ImageGenerator` | `includes/ImageGenerator.php` | Dynamic image URL construction |
| `MetaTags` | `includes/MetaTags.php` | Output OG/Twitter meta tags |
| `Admin` | `includes/Admin.php` | Admin page & asset loading |
| `Block` | `includes/Block.php` | Registers/renders the `ogdynamic/canvas-image` block |
| `BlockController` | `includes/Controllers/BlockController.php` | Block REST endpoints (canvas proxy, generate & save) |

### Data Flow

1. Admin sets template mappings per post type (stored as options)
2. On frontend: `MetaTags` → `ImageGenerator` → `Template::get_mapping()`. Mappings store the canvas' `width`/`height`; meta tags use the stored dims (null → standard 1200×630) and the Twitter image link is only emitted for Twitter-compatible sizes: 1200×630 renders the `intent=twitter` variant, 1200×600 (Twitter's recommended 2:1) reuses the `og:image` URL as-is, other sizes send just the `og:image` link
3. `ImageGenerator` resolves field values from post/term objects
4. Constructs URL by mapping `type`: canvas → `https://cdn.ogdynamic.com/c/{template_id}?{resolved_params}` (legacy design mappings, `type: design` or absent, still render via `d/{template_id}`)

### Template Sources (SaaS)

Templates are ogdynamic **canvases** (the design feature has been removed from the plugin):

- **Canvas**: user-designed in the SaaS canvas editor. List/detail via `GET v1/canvases`, `GET v1/canvases/{id}` (scope `canvases:read`). The index supports filters: `status` (lowercase enum, e.g. `public`), `width`/`height` (exact-match integers, 1–10000). The mapping page fetches `v1/canvases?status=public` — all public canvases are selectable, regardless of size. Overridable fields come from `override_fields[]` — `{field, name, type, default}` where `field` is the layer's `fieldId` (the query-param name) and image-type layers take a URL value. Only **public** canvases render OG images; the mapping page warns when a saved mapping points to a private or non-1200×630 canvas (such canvases still load by id).
- **Legacy designs**: mappings saved with `type: design` (or without a type) are no longer editable — the mapping page shows a notice asking to pick a canvas — but they keep rendering through the `d/{template_id}` CDN segment until replaced or deactivated.

### API Endpoints

- `GET /ogdynamic/v1/connection` - Get connection status
- `POST /ogdynamic/v1/connection/oauth/start` - Start OAuth flow
- `DELETE /ogdynamic/v1/connection` - Disconnect
- `GET /ogdynamic/v1/templates` - List available templates
- `GET /ogdynamic/v1/templates/{post_type}` - Get template for post type
- `PUT /ogdynamic/v1/templates/{post_type}` - Update template mapping (optional `type`: `canvas` only, default `canvas`; optional `width`/`height` ints, default 1200/630, the canvas' dimensions)
- `DELETE /ogdynamic/v1/templates/{post_type}` - Remove template mapping
- `GET /ogdynamic/v1/block/canvases` - Public canvases for the block (`edit_posts`)
- `GET /ogdynamic/v1/block/canvases/{id}` - Canvas incl. `override_fields` for the block (`edit_posts`)
- `POST /ogdynamic/v1/block/generate` - `canvas_id`, `params` (flat string map), optional `post_id`, `alt`; saves the rendered image to the media library (`edit_posts` + `upload_files`)

### Canvas Image Block

`ogdynamic/canvas-image` (`blocks/canvas-image/block.json`, TypeScript/React source in `src/block/`, built by `vite.block.config.ts` into `dist/block/ogdynamic-block.{js,css}` as a classic IIFE script — `@wordpress/*` packages are externals mapped to `wp.*` globals, JSX uses the `createElement` pragma, script deps are declared in `Block::register_block()`).

- Editor: the inspector lists public canvases and the selected canvas' `override_fields`; typed values stay in local state and are only committed to the `params` attribute on blur, which is what changes the live preview `<img>` URL (`cdn/c/{id}?{params}`). **Generate & Save** posts to `POST /block/generate`.
- Attributes JSON stores `canvasId`, `canvasName`, `params`, `width`, `height`, `alt`, `attachmentId`, `imageUrl`, `savedQuery`, `savedAt`. `savedQuery` is compared with the current params to show "Unsaved changes" and disable the button when nothing changed.
- Server: `BlockController` proxies `v1/canvases` with the stored OAuth token (editors never get the token; permission `edit_posts`, generate also needs `upload_files`). Generate sideloads the CDN image via `download_url()` + `media_handle_sideload()`, tags the attachment with `_ogdynamic_hash` / `_ogdynamic_canvas_id` / `_ogdynamic_params` / `_ogdynamic_source_url`, and reuses an existing attachment with the same hash (md5 of canvas id + canvas `updated_at` revision + sorted flat query) instead of downloading again. Only public canvases are accepted.
- Frontend: `Block::render()` (dynamic block, `save` returns null) outputs `<figure>` + `wp_get_attachment_image()` of `attachmentId` (falls back to `imageUrl`); nothing is output until an image has been saved.

### Template Fields

Supported sources by post type (see `Template::get_sources()`):
- **All**: `site_name`, `site_tagline`
- **Posts/Pages**: `post_title`, `excerpt`, `trimmed_content`, `featured_image`, `author_name`, `published_date`, `modified_date`, `category`, `tags`
- **Products**: All post fields + `product_short_description`, `product_price`, `regular_price`, `sale_price`, `sku`, `product_category`, `product_tags`, `product_attributes`, `stock_status`, `rating`, `review_count`

**ACF**: When Advanced Custom Fields is active, fields from field groups whose location rules target the post type are appended as mappable sources — key `acf_{acf_field_key}`, label `{Field label}`, `group` set to the field group title, and an `ACF` badge; the source dropdown groups core sources under "WordPress" and ACF ones under each field group title (see `Template::get_acf_sources()`). At render time (`ImageGenerator::acf_field_value()`): image/file/gallery fields resolve to an attachment URL in any return format (galleries use the first image), link fields to their URL, multi-value fields join scalar members, post titles, and term names with `, `, and everything else is cast to a tag-free string. Repeater/flexible-content/group fields are excluded (no single scalar value). Sources vanish gracefully (value `''`) if ACF is deactivated after mappings are saved.

## Conventions

- **Namespace**: `OGDynamic\` (PSR-4 → `includes/`)
- **Option prefix**: `ogdy_` (see `Settings::PREFIX`)
- **Admin page**: `toplevel_page_ogdynamic`
- **Vue router**: Hash mode (`createWebHashHistory`)
- **REST version**: `ogdynamic/v1`
- **Client ID**: Hardcoded constant `OGDYNAMIC_CLIENT_ID`

### PHP Standards

- PHP_CodeSniffer with WordPress Coding Standards
- Minimum PHP: 7.4
- Autoload via Composer (required)

### Frontend Stack

- Vue 3 + TypeScript + Composition API
- Vite 8 + `@vitejs/plugin-vue`
- Tailwind CSS 4 + `@tailwindcss/vite`
- Reka UI 2.9 (design system)
- Vue Router 4.6 (hash history)

### Build Output

- Dev: Vite serves from `/src/admin/main.ts`
- Production: `dist/admin/.vite/manifest.json` with entry `src/admin/main.ts`

## Gotchas

1. **Composer required**: Plugin checks for `vendor/autoload.php` and shows admin notice if missing
2. **SEO plugin conflicts**: `MetaTags` hooks into Rank Math, Yoast, AIOSEO, SEOPress, The SEO Framework, Squirrly, and Slim SEO to avoid duplicate meta output
3. **OAuth callback**: Handled via `ogdynamic-oauth-callback` admin page (no menu)
4. **Template ID format**: 26-char ULID string (regex: `/^[0-9A-HJKMNP-TV-Z]{26}$/i`)
5. **Transient keys**: All prefixed with `ogdy_` (via `Settings::option_name()`)
6. **Dev mode bypasses build**: Loads `main.ts` directly via Vite client, no manifest needed
7. **OAuth scope includes `canvases:read`**: tokens issued before this scope was added lack canvas access — sites must disconnect/reconnect; the template editor shows a reconnect notice when the canvas list fails
8. **Canvas query params are flat strings**: the render service ignores array-style params (`tags[]=`). Multi-value sources (`tags`, `product_tags`, `product_attributes`) are flagged `multi` in `Template::get_sources()` and excluded from mappings in the mapping UI (dropdown, auto-map, and saved-entry pruning in `syncFieldMap()`). The twitter image variant uses flat `intent=twitter` (legacy design mappings use `config[intent]=twitter`)

## Testing

No test suite configured. Manual testing required:

1. Activate plugin
2. Run `composer install` (autoloader required)
3. Run `pnpm build` (production) or `pnpm dev` (development)
4. Navigate to WordPress admin → ogdynamic
5. Connect via OAuth
6. Configure template mappings
7. View frontend to verify OG meta tags

## External Services

- **API**: `https://ogdynamic.com/api` (`v1/canvases`, `v1/me`)
- **CDN**: `https://cdn.ogdynamic.com/` (`c/{canvas_id}`; `d/{design_id}` legacy designs only)
- **SaaS editor**: `https://ogdynamic.com/canvases/create`, `/canvases/{id}/edit`
- **OAuth endpoint**: `/oauth/token`, `/oauth/authorize`
- **Redirect URI**: `admin.php?page=ogdynamic-oauth-callback`
