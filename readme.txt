=== Clevers Product Carousel ===
Contributors: cleversdevs
Donate link: https://clevers.dev
Tags: woocommerce, carousel, products, ecommerce
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 1.3.0
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
[clevers_carousel id="123"]
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

* `clevers_carousel_query_args` (filter): Modify product query args before product lookup. Receives `$args`, `$carousel_id`, and `$meta`.
* `clevers_carousel_template_path` (filter): Override template relative path inside `templates/`.
* `clevers_carousel_css_vars` (filter): Adjust per-carousel CSS variables before inline output. Receives `$vars`, `$carousel_id`, `$settings`, and `$vars_map`.
* `clevers_carousel/cache_ttl` (filter): Control the TTL of the transient cache for rendered carousel HTML.
* `clevers_carousel/settings` (filter): Modify carousel settings before rendering.
* `clevers_carousel/card_template_relpath` (filter): Override card template path.
* `clevers_carousel/slider_data_attributes` (filter): Modify slider data attributes.

**Actions:**

* `clevers_carousel_before_render` (action): Fires right before carousel template rendering. Receives `$carousel_id`, `$settings`, and `$products`.
* `clevers_carousel_after_render` (action): Fires right after carousel template rendering. Receives `$carousel_id`, `$settings`, and `$products`.

**Examples:**

```php
// Modify query to limit price range
add_filter( 'clevers_carousel_query_args', function( $args, $carousel_id, $meta ) {
    $args['price_range'] = [ 20, 200 ];
    return $args;
}, 10, 3 );

// Add custom CSS variables
add_filter( 'clevers_carousel_css_vars', function( $vars, $carousel_id, $settings, $vars_map ) {
    $vars[] = '--clevers-margen-cards: 24px;';
    return $vars;
}, 10, 4 );

// Track carousel renders
add_action( 'clevers_carousel_before_render', function( $carousel_id, $settings, $products ) {
    do_action( 'mi_tracking/carousel_render_start', $carousel_id, count( $products ) );
}, 10, 3 );
```

Namespace variants such as `clevers_carousel/query_args`, `clevers_carousel/before`, and `clevers_carousel/after` are also available.

= WP-CLI commands =

When WP-CLI is available, the plugin registers a `clevers-carousel` command namespace:

* `wp clevers-carousel list [--preset=<n>]` — print every carousel (id, title, status, preset, shortcode) as a table. Filter by preset with `--preset`.
* `wp clevers-carousel flush-cache` — delete all `clv_carousel_*` cache transients and bump the global cache version so in-process renders rebuild. Use after imports or when prices/stock have changed but the cache has not expired yet.
* `wp clevers-carousel render <id> [--file=<path>]` — print the rendered HTML for a given carousel id to stdout, or write it to a file. Handy for headless smoke tests and template debugging.

Examples:

```
wp clevers-carousel list
wp clevers-carousel list --preset=2
wp clevers-carousel flush-cache
wp clevers-carousel render 42 --file=/tmp/carousel-42.html
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

= 1.3.0 =
* Added WP-CLI commands: `wp clevers-carousel list`, `wp clevers-carousel flush-cache`, `wp clevers-carousel render <id>`. Useful for staging environments, CI pipelines, and headless smoke tests.
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

= 1.2.3 =
Brizy integration, JSON import, frontend fallback, Gutenberg, REST API, and admin tool enhancements.

= 1.2.1 =
Feature, admin UX, caching and accessibility improvements.

= 1.1.2 =
Compatibility and packaging update for WordPress.org submission.

== License ==

This plugin is free software, licensed under GPLv2 or later.
