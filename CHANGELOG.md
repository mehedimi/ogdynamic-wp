# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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