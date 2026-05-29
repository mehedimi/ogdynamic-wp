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
4. Constructs URL: `https://cdn.ogdynamic.com/d/{template_id}?{resolved_params}`

### API Endpoints

- `GET /ogdynamic/v1/connection` - Get connection status
- `POST /ogdynamic/v1/connection/oauth/start` - Start OAuth flow
- `DELETE /ogdynamic/v1/connection` - Disconnect
- `GET /ogdynamic/v1/templates` - List available templates
- `GET /ogdynamic/v1/templates/{post_type}` - Get template for post type
- `PUT /ogdynamic/v1/templates/{post_type}` - Update template mapping
- `DELETE /ogdynamic/v1/templates/{post_type}` - Remove template mapping

### Template Fields

Supported sources by post type (see `Template::get_sources()`):
- **All**: `site_name`, `site_tagline`
- **Posts/Pages**: `post_title`, `excerpt`, `trimmed_content`, `featured_image`, `author_name`, `published_date`, `modified_date`, `category`, `tags`
- **Products**: All post fields + `product_short_description`, `product_price`, `regular_price`, `sale_price`, `sku`, `product_category`, `product_tags`, `product_attributes`, `stock_status`, `rating`, `review_count`

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
4. **Template ID format**: 26-char base36 string (regex: `/^[0-9A-HJKMNP-TV-Z]{26}$/i`)
5. **Transient keys**: All prefixed with `ogdy_` (via `Settings::option_name()`)
6. **Dev mode bypasses build**: Loads `main.ts` directly via Vite client, no manifest needed

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

- **API**: `https://ogdynamic.com/api`
- **CDN**: `https://cdn.ogdynamic.com/`
- **OAuth endpoint**: `/oauth/token`, `/oauth/authorize`
- **Redirect URI**: `admin.php?page=ogdynamic-oauth-callback`
