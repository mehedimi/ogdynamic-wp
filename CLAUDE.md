# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Build & Development

- **PHP lint**: `composer lint:php`
- **PHP auto-fix**: `composer fix:php`
- **Frontend dev**: `pnpm dev` (Vite at `http://127.0.0.1:5173`)
- **Frontend build**: `pnpm build` (outputs to `dist/admin/`)
- **Type check**: `pnpm typecheck`

Enable dev mode in wp-config: `define( 'OGDYNAMIC_DEV', true );`

## Architecture

This is a **WordPress plugin** that connects to the ogdynamic SaaS for dynamic Open Graph image generation.

### Bootstrap & Entry Points

- `ogdynamic.php` — plugin header, defines constants (`OGDYNAMIC_API`, `OGDYNAMIC_CDN`, `OGDYNAMIC_CLIENT_ID`, etc.), requires autoloader, hooks `OGDynamic::instance()->boot()` on `plugins_loaded`
- `includes/OGDynamic.php` — singleton coordinator. On boot: always registers REST routes. In admin context, registers `Admin`. In frontend context, registers `MetaTags`.
- `src/admin/main.ts` — Vue 3 app entry, mounts at `#ogdynamic-admin-app`
- `includes/RestController.php` — registers all REST routes under `ogdynamic/v1`

### Key PHP Classes

| Class | Role |
|-------|------|
| `OGDynamic` (`includes/OGDynamic.php`) | Singleton bootstrapper, routes to Admin or MetaTags based on context |
| `Admin` (`includes/Admin.php`) | Admin menu page, enqueues Vue app (Vite manifest in prod, dev server in dev) |
| `OAuth` (`includes/OAuth.php`) | OAuth 2.0 + PKCE flow: start, callback, token exchange, token refresh |
| `Template` (`includes/Template.php`) | Post-type-to-template mapping CRUD, source field definitions |
| `ImageGenerator` (`includes/ImageGenerator.php`) | Resolves template mappings to CDN image URLs on the frontend |
| `MetaTags` (`includes/MetaTags.php`) | Outputs OG/Twitter meta tags; integrates with 7 SEO plugins to prevent duplicate output |
| `Settings` (`includes/Settings.php`) | Thin wrapper around WP options/transients with `ogdy_` prefix |
| `Controllers/ConnectionController.php` | REST endpoints: connection status, OAuth start, disconnect |
| `Controllers/TemplatesController.php` | REST endpoints: list templates, get/update/delete template mappings |

### Data Flow

1. Admin configures template → post-type mappings via Vue UI → stored as WordPress options (`ogdy_mapping_{post_type}`)
2. Frontend request: `MetaTags::register()` hooks into `wp_head` (and SEO plugins)
3. `ImageGenerator` determines the current template key from the queried object (post → `post`, product → `product`, category → `category`, etc.), with fallback to `default`
4. `Template::get_mapping()` retrieves the stored field mappings
5. `ImageGenerator` resolves WordPress data (post title, excerpt, prices, etc.) against the mapping
6. A CDN URL is constructed: `https://cdn.ogdynamic.com/d/{template_id}?{resolved_params}`

### REST API (`ogdynamic/v1`)

All endpoints require `manage_options` capability.
- `GET /connection` — connection status
- `POST /connection/oauth/start` — start OAuth
- `DELETE /connection` — disconnect
- `GET /templates` — list available templates
- `GET /templates/{post_type}` — get mapping
- `PUT /templates/{post_type}` — update mapping
- `DELETE /templates/{post_type}` — remove mapping

### Frontend Stack

- Vue 3 + TypeScript + Composition API
- Vite 8, Tailwind CSS 4, Reka UI 2.9, Vue Router 4.6 (hash history)
- `useOgdApi.ts` — WordPress REST API client (axios, nonce auth)
- `useOgdCloudApi.ts` — ogdynamic cloud API client (bearer token auth)
- `useOgdConnection.ts` — OAuth connection state management
- Views: `DashboardView`, `TemplatesView`, `TemplatePostTypeView`, `ConnectionView`, `OnboardingView`

### Conventions

- **Namespace**: `OGDynamic\` (PSR-4 → `includes/`)
- **Option/transient prefix**: `ogdy_` (`Settings::PREFIX`)
- **Admin page hook**: `toplevel_page_ogdynamic`
- **Template IDs**: 26-char base36 `/^[0-9A-HJKMNP-TV-Z]{26}$/i`
- **PHP**: 7.4+, WordPress Coding Standards (PHPCS), no docblock requirement
- **Singleton**: Main class uses `OGDynamic\Traits\Singleton`

## Gotchas

- **Composer is required**: plugin shows admin notice if `vendor/autoload.php` is missing
- **SEO plugins**: `MetaTags` integrates with Rank Math, Yoast, AIOSEO, SEOPress, The SEO Framework, Squirrly, Slim SEO to deduplicate OG tags
- **OAuth callback**: handled by hidden admin page `ogdynamic-oauth-callback` (no menu item)
- **Dev mode**: bypasses `dist/` entirely, loads `main.ts` via Vite client — manifest is not used
- **Token storage**: access tokens cached in transients (prefix `ogdy_`), refresh token in options
- **No test suite**: manual testing only
