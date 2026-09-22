<?php
/**
 * Plugin Name: Clevers Product Carousel
 * Plugin URI:  https://github.com/agenciaingenium/Clevers-Woocommerce-Product-Carousel/
 * Description: Create customizable WooCommerce product carousels with server-side rendering and theme-overridable templates.
 * Author:      Clevers Devs
 * Author URI:  https://clevers.dev
 * Version:     1.4.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: clevers-product-carousel
 * Domain Path: /languages/
 *
 * Note: "Tested up to" is declared only in readme.txt, per WordPress.org guidelines.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
//  Constantes
// -----------------------------------------------------------------------------
if ( ! defined( 'CLEVPRCA_SLUG' ) ) {
	define( 'CLEVPRCA_SLUG', 'cleverspr_carousel' );
}

if ( ! defined( 'CLEVPRCA_DIR' ) ) {
	define( 'CLEVPRCA_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'CLEVPRCA_URL' ) ) {
	define( 'CLEVPRCA_URL', plugin_dir_url( __FILE__ ) );
}

// -----------------------------------------------------------------------------
//  Includes & Init
// -----------------------------------------------------------------------------
require_once CLEVPRCA_DIR . 'includes/functions.php';
require_once CLEVPRCA_DIR . 'includes/helpers-discount.php';
require_once CLEVPRCA_DIR . 'includes/class-cpt.php';
require_once CLEVPRCA_DIR . 'includes/class-admin.php';
require_once CLEVPRCA_DIR . 'includes/class-render.php';
require_once CLEVPRCA_DIR . 'includes/class-health-check.php';
require_once CLEVPRCA_DIR . 'includes/class-cli.php';

// Translations for the `clevers-product-carousel` text domain are loaded
// automatically by WordPress on `init` since WP 4.6, so no explicit
// load_plugin_textdomain() call is needed.

function clevprca_init(): void {
	$cpt = new CLEVPRCA_CPT();
	$cpt->init();

	if ( is_admin() ) {
		$admin = new CLEVPRCA_Admin();
		$admin->init();
	}

	$render = new CLEVPRCA_Render();
	$render->init();
}

clevprca_init();

register_activation_hook( __FILE__, array( 'CLEVPRCA_Health_Check', 'run_on_activation' ) );
add_action( 'admin_notices', array( 'CLEVPRCA_Health_Check', 'render_activation_notice' ) );
