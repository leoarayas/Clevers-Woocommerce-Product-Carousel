# Changelog

All notable changes to **Clevers Product Carousel** are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/) and the
project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Fixed
- `tests/bootstrap.php` now defines `WC_Product` with the WooCommerce
  surface used by `helpers-discount.php` (`is_on_sale`, `is_type`,
  `get_regular_price`, `get_sale_price`, `get_children`) so PHPUnit can
  generate mocks for discount tests.
- `do_action()` mock in `tests/bootstrap.php` now invokes the registered
  callbacks instead of being a no-op, so the
  `clevprca_carousel_before_render` / `clevprca_carousel_after_render`
  assertions in `HooksTest::test_before_and_after_render_actions_receive_products`
  actually fire.
- Bootstrap now `require_once`s `includes/helpers-discount.php`, which
  exposes `clevprca_get_discount_percentage()` for the
  `RenderIntegrationTest::test_discount_percentage_returns_null_for_non_sale_product`
  case.

### Added
- `phpstan.neon` committed next to `phpstan.neon.dist` so the static
  analysis gate can run with `phpstan analyse -c phpstan.neon` locally
  without copying the dist file.

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
