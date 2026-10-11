# AGENTS.md

## Quick Start

**This is a WordPress plugin** that connects to the ogdynamic SaaS for dynamic Open Graph image generation.

### Build & Run

- **PHP linting**: `composer lint:php`
- **PHP fix**: `composer fix:php`
- **Frontend dev mode**: `pnpm dev` (runs on `http://127.0.0.1:5173`)
- **Frontend build**: `pnpm build`

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

### Data Flow

1. Admin sets template mappings per post type (stored as options)
2. On frontend: `MetaTags` → `ImageGenerator` → `Template::get_mapping()`
3. `ImageGenerator` resolves field values from post/term objects
4. Constructs URL by mapping `type`: canvas → `https://cdn.ogdynamic.com/c/{template_id}?{resolved_params}`, design → `https://cdn.ogdynamic.com/d/{template_id}?{resolved_params}`

### Template Sources (SaaS)

Templates come from the ogdynamic SaaS in two flavors; the mapping option stores which one (`type: 'canvas' | 'design'`, absent = design for backward compatibility):

- **Canvas** (primary): user-designed in the SaaS canvas editor. List/detail via `GET v1/canvases`, `GET v1/canvases/{id}` (scope `canvases:read`). The index supports filters: `status` (lowercase enum, e.g. `public`), `width`/`height` (exact-match integers, 1–10000). The mapping page fetches `v1/canvases?status=public&width=1200&height=630` — only public OG-size canvases are selectable. Overridable fields come from `override_fields[]` — `{field, name, type, default}` where `field` is the layer's `fieldId` (the query-param name) and image-type layers take a URL value. Only **public** canvases render OG images; the mapping page warns when a saved mapping points to a private or non-1200×630 canvas (such canvases fall out of the filtered list but still load by id).
- **Design** (deprecated, to be removed): list/detail via `GET v1/designs`, `GET v1/designs/{id}?include=template` (scope `designs:read`). Fields come from `template.schema[].fields[]` with `allowOverride`. The selector lists designs under a deprecated group (only when the account owns any) and shows a deprecation notice while one is selected.

### API Endpoints

- `GET /ogdynamic/v1/connection` - Get connection status
- `POST /ogdynamic/v1/connection/oauth/start` - Start OAuth flow
- `DELETE /ogdynamic/v1/connection` - Disconnect
- `GET /ogdynamic/v1/templates` - List available templates
- `GET /ogdynamic/v1/templates/{post_type}` - Get template for post type
- `PUT /ogdynamic/v1/templates/{post_type}` - Update template mapping (optional `type`: `design`|`canvas`, default `design`)
- `DELETE /ogdynamic/v1/templates/{post_type}` - Remove template mapping

### Template Fields

Supported sources by post type (see `Template::get_sources()`):
- **All**: `site_name`, `site_tagline`
- **Posts/Pages**: `post_title`, `excerpt`, `trimmed_content`, `featured_image`, `author_name`, `published_date`, `modified_date`, `category`, `tags`
- **Products**: All post fields + `product_short_description`, `product_price`, `regular_price`, `sale_price`, `sku`, `product_category`, `product_tags`, `product_attributes`, `stock_status`, `rating`, `review_count`

**ACF**: When Advanced Custom Fields is active, fields from field groups whose location rules target the post type are appended as mappable sources — key `acf_{acf_field_key}`, label `"{Group title}: {Field label}"` (see `Template::get_acf_sources()`). At render time (`ImageGenerator::acf_field_value()`): image/file/gallery fields resolve to an attachment URL (galleries use the first image), link fields to their URL, multi-value fields join scalar members with `, `, and everything else is cast to a tag-free string. Repeater/flexible-content/group fields are excluded (no single scalar value). Sources vanish gracefully (value `''`) if ACF is deactivated after mappings are saved.

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
4. **Template ID format**: 26-char ULID string (regex: `/^[0-9A-HJKMNP-TV-Z]{26}$/i`) — same format for canvases and designs
5. **Transient keys**: All prefixed with `ogdy_` (via `Settings::option_name()`)
6. **Dev mode bypasses build**: Loads `main.ts` directly via Vite client, no manifest needed
7. **OAuth scope includes `canvases:read`**: tokens issued before this scope was added lack canvas access — sites must disconnect/reconnect; the template editor shows a reconnect notice when the canvas list fails
8. **Canvas query params are flat strings**: the render service ignores array-style params (`tags[]=`), unlike designs. Multi-value sources (`tags`, `product_tags`, `product_attributes`) are flagged `multi` in `Template::get_sources()` and hidden from the mapping UI when a canvas is selected

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

- **API**: `https://ogdynamic.com/api` (`v1/canvases`, `v1/designs`, `v1/me`)
- **CDN**: `https://cdn.ogdynamic.com/` (`c/{canvas_id}` for canvases, `d/{design_id}` for designs)
- **SaaS editor**: `https://ogdynamic.com/canvases/create`, `/canvases/{id}/edit`
- **OAuth endpoint**: `/oauth/token`, `/oauth/authorize`
- **Redirect URI**: `admin.php?page=ogdynamic-oauth-callback`
