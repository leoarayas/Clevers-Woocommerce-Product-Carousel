# Changelog

All notable changes to **Clevers Product Carousel** are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/) and the
project adheres to [Semantic Versioning](https://semver.org/).

## [1.4.1] - 2026-10-30

### Fixed
- Fixed `.distignore` rule `/vendor` to avoid inadvertently excluding `assets/vendor/` from distribution packages.
- Fixed invalid 5-digit hex color syntax (`#00000` -> `#000000`) for `--clevers-bubble-background`.
- Added inline CSS custom properties injection directly into the slider container's `style` attribute, ensuring custom per-carousel colors apply reliably when page/object caching is active.
- Improved Preset 2 layout styling: equal-height slides with flexbox stretch, fixed height container for thumbnails (`object-fit: contain`), line-clamped titles, aligned bottom action buttons, and modernized circular floating arrows.
- Fixed `bin/bump-version.php` regex pattern and added null check when updating the version badge in `README.md`.
- `tests/bootstrap.php` defines `WC_Product` with the WooCommerce surface used by `helpers-discount.php`.
- `do_action()` mock in `tests/bootstrap.php` invokes registered callbacks.
- Bootstrap `require_once`s `includes/helpers-discount.php` for discount helpers.

### Added
- `phpstan.neon` committed next to `phpstan.neon.dist`.

## [1.4.0] - 2026-09

### Changed (breaking)
- Renamed the public shortcode tag from `[clevers_carousel]` to
  `[cleverspr_carousel]` and the Gutenberg block namespace from
  `clevers-product-carousel/carousel` to `cleverspr/carousel`. Existing
  content referencing the old names must be updated. The CPT slug
  (`clevers_carousel`) and post meta keys are unchanged, so existing
  carousel posts keep their settings.

### Fixed
- WP-CLI `render --file` no longer writes to an arbitrary filesystem path.
  `--file` now accepts a plain filename and the output is written inside
  `wp-content/uploads/clevers-product-carousel/`, resolved at runtime with
  `wp_upload_dir()`. Path separators, `..` and stream wrappers are rejected.
- Removed the `function_exists()` guards around the plugin's own helper
  functions; a same-named function loaded by another plugin first would have
  caused a fatal error.

### Changed
- Renamed every plugin-owned global identifier from the shared `clv` /
  `clevers` prefixes to the unique `clevprca` prefix: options, transients,
  post meta keys, hooks, script/style handles, nonces, AJAX actions and the
  WP-CLI command namespace.

## [1.3.2] - 2026-09

### Fixed
- WP-CLI `render --file` no longer writes to an arbitrary filesystem path.
  `--file` now accepts a plain filename and the output is written inside
  `wp-content/uploads/clevers-product-carousel/`, resolved at runtime with
  `wp_upload_dir()`. Path separators, `..` and stream wrappers are rejected.
- Removed the `function_exists()` guards around the plugin's own helper
  functions; a same-named function loaded by another plugin first would have
  caused a fatal error.

### Changed
- Renamed every plugin-owned global identifier from the shared `clv` /
  `clevers` prefixes to the unique `clevprca` prefix: options, transients,
  post meta keys, hooks, script/style handles, block namespace, nonces, AJAX
  actions and the WP-CLI command namespace.

## [1.2.3] - 2026-06

### Added
- PHPStan static analysis gate at level 9 via `phpstan.neon.dist`.
- WebP support via `<picture>` element with lazy loading.
- Documentation for public filters and actions.

## [1.2.0] - 2026-04

### Added
- WooCommerce mock integration tests covering shortcodes, render
  filters and public hooks.

## [1.0.0] - 2026-02

### Added
- Initial public release of the WooCommerce product carousel plugin.
