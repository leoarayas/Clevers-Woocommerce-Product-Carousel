<?php
/**
 * Uninstall routine for Clevers Product Carousel.
 *
 * Removes plugin-specific data: custom post type entries, options, transients,
 * and post meta. Intended to run only when the plugin is deleted from the
 * WordPress admin (not on simple deactivation).
 *
 * @package CLEVPRCA
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

/**
 * Delete all Product Carousel CPT entries (and their post meta).
 *
 * Force-delete skips the trash so leftovers do not pile up after uninstall.
 */
$clevprca_carousel_ids = get_posts(
	array(
		'post_type'      => 'clevprca_carousel',
		'post_status'    => 'any',
		'numberposts'    => -1,
		'fields'         => 'ids',
		'suppress_filters' => false,
	)
);

foreach ( (array) $clevprca_carousel_ids as $clevprca_carousel_id ) {
	wp_delete_post( (int) $clevprca_carousel_id, true );
}

/**
 * Remove plugin-owned options.
 */
delete_option( 'clevprca_global_cache_bump' );

/**
 * Remove named transients used by the activation health check.
 */
delete_transient( 'clevprca_activation_health_check' );

/**
 * Remove cached carousel output transients (clevprca_carousel_*).
 *
 * Transients are stored as `_transient_<key>` / `_transient_timeout_<key>`
 * rows in wp_options, so a LIKE sweep is the only safe way to purge them.
 */
if ( isset( $wpdb ) && is_a( $wpdb, 'wpdb' ) ) {
	$clevprca_transient_like         = $wpdb->esc_like( '_transient_clevprca_carousel_' ) . '%';
	$clevprca_transient_timeout_like = $wpdb->esc_like( '_transient_timeout_clevprca_carousel_' ) . '%';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cleanup of plugin-owned transient rows during uninstall.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$clevprca_transient_like,
			$clevprca_transient_timeout_like
		)
	);
}
