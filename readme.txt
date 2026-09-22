=== Clevers Product Carousel ===
Contributors: cleversdev
Donate link: https://clevers.dev
Tags: woocommerce, carousel, products, ecommerce
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 1.4.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create professional WooCommerce product carousels with customizable presets, per-carousel colors, and a responsive Slick.js-based layout.

== Description ==

**Clevers Product Carousel** lets you create and display fully customizable WooCommerce product carousels from the WordPress admin area.

- Ready-to-use carousel and card presets.
- Per-carousel color settings for buttons, background, and text.
- Product filters: featured, on-sale, in-stock, and categories.
- Carousel controls: slides, autoplay, dots, and arrows.
- Gutenberg block to insert saved carousels from the editor.
- Cached rendering and dynamic CSS variables for performance.
- Theme-overridable templates (WooCommerce-style structure).

= Main Features =

* **Customizable presets:** 4 base designs that can be extended.
* **Dynamic colors:** define colors per carousel (primary, secondary, buttons, badges, etc.).
* **WooCommerce compatible:** uses WooCommerce product data and pricing.
* **Visual options:** autoplay, speed, slides to show, arrows, and dots.
* **Theme overrides:** copy `templates/carousels/carousel-1.php` or `templates/cards/card-1.php` to `/clevers-product-carousel/` inside your theme to customize markup.
* **Caching system:** reduces repeated queries and improves rendering speed.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install it from the WordPress plugin directory.
2. Activate the plugin from the "Plugins" menu in WordPress.
3. Create a new **Product Carousel** from the “Product Carousels” menu in the admin area.
4. Configure layout, colors, and filters.
5. Insert the carousel into any page or template using the shortcode:

```
[cleverspr_carousel id="123"]
```

Replace `123` with your carousel post ID.

You can also insert it using the **Clevers Product Carousel** block in Gutenberg.

== Frequently Asked Questions ==

= Can I use it without WooCommerce? =
No. The plugin requires WooCommerce to load products.

= How do I change the card layout? =
Copy the file from:
```
wp-content/plugins/clevers-product-carousel/templates/cards/card-1.php
```
to:
```
wp-content/themes/tu-tema/clevers-product-carousel/cards/card-1.php
```
and edit it in your theme.

= How do I customize colors? =
Each carousel includes color fields in the editor. You can also override CSS variables:

```css
#clevers-product-carousel-123 {
  --clevers-primary: #e63946;
  --clevers-secondary: #1d3557;
}
```

= Does the plugin provide hooks/filters for developers? =
Yes. The plugin offers several filters and actions for developers to extend its functionality:

**Filters:**

* `cleverspr_carousel_query_args` (filter): Modify product query args before product lookup. Receives `$args`, `$carousel_id`, and `$meta`.
* `cleverspr_carousel_template_path` (filter): Override template relative path inside `templates/`.
* `cleverspr_carousel_css_vars` (filter): Adjust per-carousel CSS variables before inline output. Receives `$vars`, `$carousel_id`, `$settings`, and `$vars_map`.
* `cleverspr_carousel/cache_ttl` (filter): Control the TTL of the transient cache for rendered carousel HTML.
* `cleverspr_carousel/settings` (filter): Modify carousel settings before rendering.
* `cleverspr_carousel/card_template_relpath` (filter): Override card template path.
* `cleverspr_carousel/slider_data_attributes` (filter): Modify slider data attributes.

**Actions:**

* `cleverspr_carousel_before_render` (action): Fires right before carousel template rendering. Receives `$carousel_id`, `$settings`, and `$products`.
* `cleverspr_carousel_after_render` (action): Fires right after carousel template rendering. Receives `$carousel_id`, `$settings`, and `$products`.

**Examples:**

```php
// Modify query to limit price range
add_filter( 'cleverspr_carousel_query_args', function( $args, $carousel_id, $meta ) {
    $args['price_range'] = [ 20, 200 ];
    return $args;
}, 10, 3 );

// Add custom CSS variables
add_filter( 'cleverspr_carousel_css_vars', function( $vars, $carousel_id, $settings, $vars_map ) {
    $vars[] = '--clevers-margen-cards: 24px;';
    return $vars;
}, 10, 4 );

// Track carousel renders
add_action( 'cleverspr_carousel_before_render', function( $carousel_id, $settings, $products ) {
    do_action( 'mi_tracking/carousel_render_start', $carousel_id, count( $products ) );
}, 10, 3 );
```

Namespace variants such as `cleverspr_carousel/query_args`, `cleverspr_carousel/before`, and `cleverspr_carousel/after` are also available.

= WP-CLI commands =

When WP-CLI is available, the plugin registers a `clevprca-carousel` command namespace:

* `wp clevprca-carousel list [--preset=<n>]` — print every carousel (id, title, status, preset, shortcode) as a table. Filter by preset with `--preset`.
* `wp clevprca-carousel flush-cache` — delete all `cleverspr_carousel_*` cache transients and bump the global cache version so in-process renders rebuild. Use after imports or when prices/stock have changed but the cache has not expired yet.
* `wp clevprca-carousel render <id> [--file=<name>]` — print the rendered HTML for a given carousel id to stdout, or write it into the plugin uploads directory. Handy for headless smoke tests and template debugging.

Examples:

```
wp clevprca-carousel list
wp clevprca-carousel list --preset=2
wp clevprca-carousel flush-cache
wp clevprca-carousel render 42 --file=carousel-42.html
```

These commands are loaded only when `WP_CLI` is defined, so there is no overhead in normal HTTP requests.

== Third-party Libraries ==

This plugin bundles **Slick.js v1.8.1** by Ken Wheeler, licensed under the MIT License.
Source: https://github.com/kenwheeler/slick

== Screenshots ==
1. Admin panel with carousel configuration fields.
2. Example WooCommerce product carousel on the frontend.
3. Per-carousel color settings.
4. Different card presets.

== Changelog ==

= 1.4.0 =
* **Breaking change:** the shortcode tag changed from `[clevers_carousel]` to `[cleverspr_carousel]`, and the Gutenberg block namespace from `clevers-product-carousel/carousel` to `cleverspr/carousel`. Existing content referencing the old names must be updated. The CPT slug (`clevers_carousel`) and the post meta keys are unchanged, so existing carousel posts keep their settings.
* WP-CLI `render --file` no longer writes to an arbitrary path. `--file` now takes a plain filename written inside `wp-content/uploads/clevers-product-carousel/`, resolved at runtime with `wp_upload_dir()`; path separators, `..` and stream wrappers are rejected.
* Renamed every plugin-owned global identifier from the shared `clv` / `clevers` prefixes to the unique `clevprca` prefix (functions, classes, constants, options, transients, post meta, script/style handles, nonces, AJAX actions, WP-CLI namespace).
* Removed the `function_exists()` guards around the plugin's own helper functions, which could cause a fatal error if another plugin loaded a same-named function first.

= 1.3.1 =
* Removed `Tested up to` from the main plugin header (declared only in readme.txt per WordPress.org guidelines) and bumped it to 7.1.
* Moved all admin inline `<style>`/`<script>` blocks to properly enqueued `assets/admin.css` and `assets/admin.js` files.
* Moved the Brizy preview `<style>` injection in the renderer to `wp_add_inline_style()` so it ships through the standard stylesheet pipeline.
* Wrapped shortcode and Gutenberg block render callbacks in `wp_kses_post()` to defend against untrusted content that may pass through the `clevers_product_carousel/rendered_html` filter.
* Renamed internal `CLV_*` constants to `CLEVPRCA_*` to satisfy the prefix length requirement (4+ characters, distinct from common words).
* Updated the `Contributors` field in readme.txt to the plugin owner (`cleversdev`).

= 1.3.0 =
* Added WP-CLI commands: `wp clevprca-carousel list`, `wp clevprca-carousel flush-cache`, `wp clevprca-carousel render <id>`. Useful for staging environments, CI pipelines, and headless smoke tests.
* Documented developer hooks (filters/actions) more clearly in the FAQ.
* No new runtime dependencies; WP-CLI integration adds zero overhead when WP-CLI is not in use.

= 1.2.3 =
* Enhanced Brizy editor integration.
* Refined JSON import flow.
* Improved frontend fallback behavior.
* Added Gutenberg block support improvements.
* Added queue-metrics observability fields (pending/processed/failed, avg time, last error) for the rendering pipeline.
* Enhanced admin tools and management UX.

= 1.2.1 =
* Added Gutenberg block for selecting saved carousels.
* Improved admin UI (sorting, categories selector, shortcode box, preset preview).
* Fixed filter combination logic for on-sale + featured products.
* Expanded cache invalidation hooks for WooCommerce updates.
* Accessibility and UX improvements for Slick carousel navigation.

= 1.1.2 =
* Updated compatibility headers for the latest WordPress version.
* Improved release packaging for WordPress.org-ready ZIP files.
* Code quality fixes for Plugin Check / WordPress Coding Standards.

= 0.2.0 =
* Added per-carousel color system (dynamic CSS variables).
* Improved rendering with cache support.
* Added theme template override support.
* General code refactor.

= 0.1.0 =
* Initial release with WooCommerce product carousel support.

== Upgrade Notice ==

= 1.4.0 =
Breaking: the shortcode is now `[cleverspr_carousel]` and the block `cleverspr/carousel`. Update existing content. Also: safer WP-CLI file output and unique `clevprca` / `cleverspr` prefixes.

= 1.3.1 =
WordPress.org plugin directory review fixes: enqueued admin assets, escaped render callbacks, fixed `Tested up to` and contributors list.

= 1.2.3 =
Brizy integration, JSON import, frontend fallback, Gutenberg, REST API, and admin tool enhancements.

= 1.2.1 =
Feature, admin UX, caching and accessibility improvements.

= 1.1.2 =
Compatibility and packaging update for WordPress.org submission.

== License ==

This plugin is free software, licensed under GPLv2 or later.
