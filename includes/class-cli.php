<?php
/**
 * WP-CLI commands for Clevers Product Carousel.
 *
 * Provides operational tooling for headless environments, CI pipelines,
 * and developers who prefer the terminal over the admin UI.
 *
 * Loaded conditionally by clevers-product-carousel.php only when WP_CLI
 * is defined, so this file has zero runtime cost in normal HTTP requests.
 *
 * @package Clevers_Product_Carousel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * WP-CLI command surface: `wp clevers-carousel <subcommand>`.
 *
 * Subcommands:
 *   list            List all carousels with id, title, and preset.
 *   flush-cache     Delete every transient used by the carousel renderer.
 *   render <id>     Print the rendered HTML for a given carousel id (handy
 *                   for headless smoke tests and template debugging).
 */
class Clevers_Product_Carousel_CLI {

	/**
	 * List every carousel CPT entry as a WP-CLI table.
	 *
	 * Optional `--preset=<n>` narrows the output to a single preset.
	 *
	 * @when after_wp_load
	 *
	 * @param array<string,string> $args       Positional args.
	 * @param array<string,string> $assoc_args Associative args (--preset).
	 */
	public function list( $args, $assoc_args ): void {
		$preset_filter = isset( $assoc_args['preset'] ) ? (int) $assoc_args['preset'] : 0;

		$query = new WP_Query(
			array(
				'post_type'      => CLV_SLUG,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		if ( ! $query->have_posts() ) {
			WP_CLI::success( 'No carousels found.' );
			return;
		}

		$rows = array();
		foreach ( $query->posts as $carousel ) {
			$meta  = clevers_product_carousel_get_carousel_meta( $carousel->ID );
			$preset = isset( $meta['preset'] ) ? (int) $meta['preset'] : 1;

			if ( $preset_filter > 0 && $preset !== $preset_filter ) {
				continue;
			}

			$rows[] = array(
				'ID'        => (string) $carousel->ID,
				'Title'     => $carousel->post_title,
				'Status'    => $carousel->post_status,
				'Preset'    => (string) $preset,
				'Shortcode' => sprintf( '[clevers_carousel id="%d"]', $carousel->ID ),
			);
		}

		if ( empty( $rows ) ) {
			WP_CLI::success( sprintf( 'No carousels match preset=%d.', $preset_filter ) );
			return;
		}

		WP_CLI\Utils\format_items( 'table', $rows, array( 'ID', 'Title', 'Status', 'Preset', 'Shortcode' ) );
		WP_CLI::success( sprintf( '%d carousel(s) listed.', count( $rows ) ) );
	}

	/**
	 * Delete every carousel cache transient (clv_carousel_*) plus the
	 * activation health-check transient. Useful when content has changed
	 * but the cache has not expired yet, or after running imports.
	 *
	 * ## EXAMPLES
	 *
	 *     wp clevers-carousel flush-cache
	 *
	 * @when after_wp_load
	 */
	public function flush_cache(): void {
		global $wpdb;

		if ( ! isset( $wpdb ) || ! is_a( $wpdb, 'wpdb' ) ) {
			WP_CLI::error( 'wpdb not available; cannot flush transients.' );
			return;
		}

		$like_cache    = $wpdb->esc_like( '_transient_clv_carousel_' ) . '%';
		$like_timeout  = $wpdb->esc_like( '_transient_timeout_clv_carousel_' ) . '%';
		$like_health   = $wpdb->esc_like( '_transient_clv_activation_health_check' ) . '%';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk delete of plugin-owned transient rows; not a user-facing query.
		$cache_deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$like_cache,
				$like_timeout
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Single-row delete of the plugin-owned health-check transient.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name = %s",
				'_transient_clv_activation_health_check'
			)
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Single-row delete of the health-check timeout entry.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name = %s",
				'_transient_timeout_clv_activation_health_check'
			)
		);

		// Bump the global cache version so any in-process callers rebuild too.
		update_option( 'clv_global_cache_bump', (int) get_option( 'clv_global_cache_bump', 0 ) + 1, false );

		WP_CLI::success( sprintf( 'Flushed %d carousel cache transient row(s).', (int) $cache_deleted ) );
	}

	/**
	 * Render a carousel to stdout. Useful for headless smoke tests,
	 * template debugging, and capturing markup into a file or pipe.
	 *
	 * By default the rendered HTML is printed. `--file=<path>` writes it
	 * to a file instead. Returns nothing on success; non-zero exit on
	 * failure (via WP_CLI::error).
	 *
	 * ## OPTIONS
	 *
	 * [--file=<path>]
	 *   Write the rendered HTML to <path> instead of stdout.
	 *
	 * ## EXAMPLES
	 *
	 *     wp clevers-carousel render 42
	 *     wp clevers-carousel render 42 --file=/tmp/carousel-42.html
	 *
	 * @when after_wp_load
	 *
	 * @param array<int,string>     $args       First positional arg: carousel id.
	 * @param array<string,string>  $assoc_args Associative args (--file).
	 */
	public function render( $args, $assoc_args ): void {
		if ( empty( $args[0] ) ) {
			WP_CLI::error( 'Missing carousel id. Usage: wp clevers-carousel render <id>' );
		}

		$id  = (int) $args[0];
		$out = isset( $assoc_args['file'] ) ? (string) $assoc_args['file'] : '';

		if ( $id <= 0 ) {
			WP_CLI::error( sprintf( 'Invalid carousel id "%s".', $args[0] ) );
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			WP_CLI::error( 'WooCommerce is not active; the carousel renderer requires it.' );
		}

		$render = new Clevers_Product_Carousel_Render();
		$html   = $render->render_carousel( $id );

		if ( '' === $html ) {
			WP_CLI::error( sprintf( 'Carousel %d rendered empty (missing, wrong CPT, or WooCommerce returned no products).', $id ) );
		}

		if ( '' !== $out ) {
			$written = file_put_contents( $out, $html );
			if ( false === $written ) {
				WP_CLI::error( sprintf( 'Could not write to %s.', $out ) );
			}
			WP_CLI::success( sprintf( 'Wrote %d bytes to %s.', (int) $written, $out ) );
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI output of already-escaped HTML; not a browser context.
		echo $html;
	}
}

WP_CLI::add_command( 'clevers-carousel', 'Clevers_Product_Carousel_CLI' );
