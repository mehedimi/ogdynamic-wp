# ogdynamic WordPress Plugin — Full Audit

**Date:** 2026-10-31
**Scope:** Features, UI/UX, code quality, security, performance (branch `feat/v2.1-brand-and-canvas-only`, post PR #4 changes)
**Method:** Full read of all PHP classes (`includes/`, `ogdynamic.php`, `uninstall.php`) and the entire Vue admin app (`src/admin/`), config files, plus static checks (`php -l`, `vue-tsc`, `pnpm build`).

---

## Executive Summary

The plugin is in good shape for its size (~5,000 LOC PHP + Vue). Architecture is clean: a thin bootstrap, static-class modules with clear responsibilities, canvas-only mapping after the design removal, and a WordPress-native data model (options + transients, no tables). Security fundamentals are right — capability checks on every route, nonce-authenticated REST, sanitized inputs, PKCE OAuth with server-side state. The frontend renders **1–2 DB queries per public page** (measured: `get_option` on `ogdy_mapping_*`, autoload-off, memoized per request).

The audit found **one significant behavioral bug** (a 401 from the cloud API deleted the entire connection, including the refresh token), one broken failure path (missing production build rendered a dead admin page), and an incomplete uninstall cleanup. **All six findings marked "Fixed" below have been fixed in this pass**; the remaining items are documented recommendations.

---

## 1. Feature Inventory

| Feature | Status | Notes |
|---|---|---|
| OAuth 2.0 + PKCE connect flow | ✅ Working | Verifier stored server-side in a 5-min transient keyed by random state; single-use; `wp_safe_redirect` back |
| Token refresh | ✅ Working | Transparent server-side refresh when transient lapses; `expires_in − 60s` safety margin |
| Canvas template mapping (per post type) | ✅ Working | Public 1200×630 canvases only; auto-map + orphan pruning (`syncFieldMap`) |
| Legacy design mappings (pre-canvas) | ✅ Rendering | `/d/{id}` CDN branch retained; mapping page shows replace-with-canvas notice (PR #4) |
| OG/Twitter meta output | ✅ Working | `wp_head` fallback + 7 SEO-plugin integrations (Rank Math, Yoast, AIOSEO, SEOPress, The SEO Framework, Squirrly, Slim SEO) with duplicate suppression |
| Archive coverage | ✅ Working | home, blog, category, tag, author, date, search + `default` fallback |
| WooCommerce product sources | ✅ Working | price/sale/regular, SKU, stock, rating, reviews, attributes, categories/tags |
| ACF integration | ✅ Working | Location-matched sources (`acf_{key}`); image/file/gallery/link/multi-value handled in all return formats; graceful deactivation |
| Multi-value source exclusion | ✅ Working | Canvas URLs take flat string params only; `multi` sources filtered from dropdown, auto-map, and pruning |
| Multisite | ✅ Working | Per-site options; uninstall iterates sites |
| Dev mode (`OGDYNAMIC_DEV`) | ✅ Working | Vite client injection, custom server override |
| REST API `ogdynamic/v1` | ✅ Working | 6 endpoints, all `manage_options`-gated |

**Known intentional limitation:** a legacy design mapping can be *replaced* but not deactivated without first choosing a canvas.

---

## 2. Architecture & Code Quality — PHP

**Strengths**

- Clear module boundaries: `Admin` (assets), `MetaTags` (output), `ImageGenerator` (URL building), `Template` (mapping CRUD + sources), `OAuth` (auth), `Settings` (storage), thin controllers. Static classes are appropriate for a WordPress plugin of this scope.
- Consistent WPCS style, full docblocks, `ogdy_` prefixing everywhere via `Settings`, sanitize/escape calls in the right places.
- `uninstall.php` is multisite-aware with `WP_UNINSTALL_PLUGIN` guard.
- PHP 7.4 floor respected (verified: array spread, no 8.0-only syntax; composer platform pin = 7.4).
- Sequence-token guard (`templateRequestSeq`) in the mapping view prevents stale async responses — good frontend race handling.

**Weaknesses**

- `Admin::localize_admin_script()` calls `OAuth::get_access_token()` on every ogdynamic admin page load; if the refresh token is dead, this adds a synchronous 12s HTTP timeout (`TOKEN_ENDPOINT_ARGS`) to every page open, indefinitely (no negative-result caching). See Finding R1.
- `ImageGenerator` carries per-request mutable static state (`$template_type`, `$template_id`, `$query`) — safe in practice (set once in `get_mapping_for_key`, read everywhere), but subtle for future maintainers. Documented in code comments as of PR #4.
- No test suite and no CI (documented as intentional; see R8).

---

## 3. Frontend — Vue 3 + TypeScript

**Strengths:** Composition API + `<script setup>` throughout, typed API envelopes, error extraction from WP REST `details`/`params` shapes, skeletons for all loading states, shared axios clients with a single `useOgdApi()` instance.

**Weaknesses / notes**

- The cloud API Bearer header was frozen at module load (`Authorization: Bearer ${window.ogdynamicAdmin.apiKey}` at axios-client creation). Fixed in this pass to resolve per-request.
- Vue-side strings are not translatable (all PHP strings are). Needed only if translations are planned (R6).
- WP REST nonce expiry on long-open tabs yields 403 with no recovery UX (R2).
- An account with zero canvases gets an empty selector with no "create your first canvas" guidance (only the failure notice covers API errors). UX polish item (R7).

---

## 4. Security Review

| Area | Assessment |
|---|---|
| REST authorization | ✅ Every route `permission_callback` → `manage_options`; OAuth callback re-checks capability and `wp_die`s |
| Input handling | ✅ `sanitize_text_field`/`sanitize_key` on all inputs; map array validated structurally per-item |
| Output escaping | ✅ `esc_html`/`esc_url`/`esc_attr` used correctly; meta output `printf` + `esc_url` |
| SQL | ✅ All `$wpdb->prepare`'d (`Template`, `uninstall.php`); direct queries carry phpcs exemptions |
| OAuth | ✅ PKCE S256; state token (`wp:{uri}\|{nonce}`) + server-side verifier lookup; verifier single-use and deleted after exchange |
| Token exposure | ⚠️ Acceptable — access token embedded in the ogdynamic admin page via `wp_localize_script` (admin-only page) and returned by `GET /connection` (admin-only, nonce-gated). Same trust level as WordPress core's own patterns. |
| Scope validation | ⚠️ Token response `scope` is not checked at connect time — the `canvases:read` regression (AGENTS gotcha #7) could only be detected later via a failing canvas list (R3) |
| Rate limiting | No caching/limiting on SaaS calls from the admin; admin-only, low risk (R4) |

---

## 5. Performance & Database Impact

- **Public pages: 1–2 SELECTs on `wp_options`.** 1 when the page type has its own `ogdy_mapping_*` row, 2 on fallback to `default` (also when nothing is mapped). Everything else is memoized in static state for the request — SEO plugins firing 3–5 filters add zero queries.
- `autoload = false` on all plugin options: correct tradeoff (one targeted SELECT per relevant page instead of bloating the global autoload batch).
- With a persistent object cache (Redis/Memcached): effectively 0 queries per render.
- Field-value resolution uses standard WP lookups (thumbnail postmeta, `wp_get_post_terms`, `wc_get_product`, `get_field_object`) — only for actually-mapped fields, mostly cached by core/Woo/ACF.
- Admin: canvas list is fetched fresh from the SaaS on every mapping-page load (R4); token refresh happens synchronously during asset localization (R1).

---

## 6. UI/UX Review (post v2.1 re-skin)

**Good:** consistent v2.1 token system (purple primary, slate neutrals, semantic status colors) matching the main app; new logo + menu icon; loading skeletons everywhere; destructive actions styled per the danger recipe; legacy-design warning nudges users to migrate; aria-labels/title on all icon buttons; keyboard-focusable combobox with highlighted-state styling.

**Issues found and fixed in this pass**

- Long-open admin tabs nuked the connection on any 401 (see F1) — worst UX offender: a user leaving the tab open overnight would come back to a silently disconnected site after browsing canvases.
- Dashboard copy still spoke the API-key dialect ("API connection", "Your API token is valid", "Add an API key…").

**Remaining (recommendations)**: nonce-expiry recovery prompt (R2), zero-canvas empty state (R7), refreshed screenshots (R9).

---

## 7. Findings

Severity: 🔴 High · 🟠 Medium · 🟡 Low · ⚪ Info. Status: **Fixed** = changed in this pass; Open = recommendation.

| # | Sev | Finding | Status |
|---|-----|---------|--------|
| F1 | 🔴 | `useOgdCloudApi` 401 interceptor called `DELETE /connection`, wiping the refresh token and all token options — triggered by a merely-lapsed access token on a long-open page. No retry was attempted. | **Fixed** — 401 now probes `GET /connection` (which refreshes server-side), updates the token, retries the original request once; disconnects only when the server reports not connected. `ConnectionController::get()` now returns the refreshed token. |
| F2 | 🟠 | `Admin::asset_files()` returned a broken script URL (no error) when `dist/admin/.vite/manifest.json` was missing — admin page rendered "Loading…" forever with a console error and no guidance. | **Fixed** — returns null, enqueue skipped, admin notice explains: run `pnpm build` or enable `OGDYNAMIC_DEV`. |
| F3 | 🟠 | `uninstall.php` deleted `ogdy_*` options but left `_transient_ogdy_*` + `_transient_timeout_ogdy_*` rows (they don't match the `ogdy_` prefix). | **Fixed** — transient deletion added. |
| F4 | 🟡 | Cloud API Bearer header was baked at module scope, so any recovered/refreshed token wasn't used until page reload. | **Fixed** — request interceptor resolves the token from live state. |
| F5 | 🟡 | Dashboard copy from the pre-OAuth API-key era ("API connection", "Your API token is valid", "Add an API key before configuring…"). | **Fixed** — OAuth-appropriate wording. |
| F6 | 🟡 | Dead data: `oauth_client_id` listed in `TOKEN_OPTIONS` but never written anywhere. | **Fixed** — removed. |
| R1 | 🟠 | Synchronous token refresh (12s timeout) during `localize_admin_script()` on every admin page load; a broken connection retries the failing refresh forever. Recommend a short negative-cache transient (e.g. 60s) or deferring refresh to `GET /connection`. | Open |
| R2 | 🟠 | WP REST nonce expiry on long admin sessions → 403 `rest_cookie_invalid_nonce` with no recovery UX. Recommend detecting the error and prompting a page reload. | Open |
| R3 | 🟡 | OAuth token `scope` not validated at connect time; missing `canvases:read` is only discovered via a failed canvas list (reconnect notice). Validate scope in `store_token_response()` and warn immediately. | Open |
| R4 | 🟡 | `v1/canvases` list uncached — every mapping-page load hits the SaaS. Consider a 60s transient cache. | Open |
| R5 | 🟡 | No `og:image:alt` output; would need a mapped source (excerpt/ACF) decision first. | Open |
| R6 | 🟡 | Vue strings not translatable; PHP strings all wired for i18n. Adopt `@wordpress/i18n`-style extraction or vue-i18n if/when translations matter. | Open |
| R7 | ⚪ | Zero-canvas accounts see an empty selector without "create your first canvas" guidance. | Open |
| R8 | ⚪ | No automated tests/CI. At minimum add lint + build + `php -l` workflow; wp-env smoke test for REST routes would catch the F1 class of bugs. | Open |
| R9 | ⚪ | `readme.txt` screenshots show the pre-v2.1 rose UI; re-capture before next release. | Open |
| R10 | ⚪ | Version strings (plugin header, composer.json, package.json) at 0.2.0 while CHANGELOG has Unreleased entries — bump to 0.3.0 at release. | Open |
| R11 | ⚪ | Access token embedded in admin page source + `GET /connection` response — acceptable (admin-only), noted for security review completeness. | Accepted risk |

---

## 8. Fixes Applied in This Pass

| File | Change |
|---|---|
| `includes/Controllers/ConnectionController.php` | `GET /connection` returns `connected` + current (refreshed) access token |
| `src/admin/composables/useOgdCloudApi.ts` | Request-time Bearer header; 401 → probe connection, update token, retry once; never delete connection on a stale token |
| `includes/Admin.php` | `asset_files()` nullable + guarded enqueue; `render_build_notice()` on missing build |
| `uninstall.php` | Delete `_transient_ogdy_*` and `_transient_timeout_ogdy_*` rows |
| `src/admin/views/DashboardView.vue` | Copy: "ogdynamic connection" wording replaces API-key-era text |
| `includes/OAuth.php` | Removed never-written `oauth_client_id` from `TOKEN_OPTIONS` |

**Verification:** `php -l` clean on all modified files · `vue-tsc --noEmit` clean · `pnpm build` succeeds.

---

## 9. Recommendation Backlog (priority order)

1. **R1** — negative-cache the failed token refresh (cheap, removes a real 12s-per-page-load foot-gun).
2. **R2** — nonce-expiry recovery UX in `useOgdApi` (one interceptor + reload prompt).
3. **R8** — CI: `php -l`, `vue-tsc`, `pnpm build` on every PR; consider wp-env REST smoke tests.
4. **R3** — validate OAuth scope at connect; surface a clear "reconnect required" state.
5. **R4 + R7** — small canvas-list caching and zero-canvas empty state.
6. **R10/R9** — release hygiene: version bump + fresh screenshots.
