<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CLEVPRCA_CPT {

	public function init() {
		add_action( 'init', array( $this, 'register_cpt_and_assets' ) );
		add_action( 'after_setup_theme', array( $this, 'add_image_sizes' ) );
		add_filter( 'use_block_editor_for_post_type', array( $this, 'force_classic_editor_for_carousel_cpt' ), 10, 2 );
	}

	public function force_classic_editor_for_carousel_cpt( $use_block_editor, $post_type ) {
		if ( CLEVPRCA_SLUG === $post_type ) {
			return false;
		}

		return $use_block_editor;
	}

	public function add_image_sizes() {
		add_image_size(
			'cleverspr_carousel_thumb',
			330,
			400,
			true // hard crop
		);
	}

	public function register_cpt_and_assets() {
		// Slick desde el propio plugin (no CDN).
		wp_register_style(
			'clevprca-slick',
			CLEVPRCA_URL . 'assets/vendor/slick/slick.css',
			array(),
			'1.8.1'
		);

		wp_register_style(
			'clevprca-slick-theme',
			CLEVPRCA_URL . 'assets/vendor/slick/slick-theme.css',
			array( 'clevprca-slick' ),
			'1.8.1'
		);

		wp_register_script(
			'clevprca-slick',
			CLEVPRCA_URL . 'assets/vendor/slick/slick.min.js',
			array( 'jquery' ),
			'1.8.1',
			true
		);

		// Tus assets locales con busting por filemtime.
		$css     = CLEVPRCA_DIR . 'assets/carousel.css';
		$js      = CLEVPRCA_DIR . 'assets/carousel.js';
		$css_ver = file_exists( $css ) ? filemtime( $css ) : '0.1.0';
		$js_ver  = file_exists( $js ) ? filemtime( $js ) : '0.1.0';

		wp_register_style(
			'clevprca-carousel',
			CLEVPRCA_URL . 'assets/carousel.css',
			array( 'clevprca-slick', 'clevprca-slick-theme' ),
			$css_ver
		);

		wp_register_script(
			'clevprca-carousel',
			CLEVPRCA_URL . 'assets/carousel.js',
			array( 'clevprca-slick' ),
			$js_ver,
			true
		);

		wp_localize_script(
			'clevprca-carousel',
			'clevprcaCarouselI18n',
			array(
				'prevSlide' => __( 'Previous slide', 'clevers-product-carousel' ),
				'nextSlide' => __( 'Next slide', 'clevers-product-carousel' ),
				/* translators: %d: slide number. */
				'goToSlide' => __( 'Go to slide %d', 'clevers-product-carousel' ),
				'carousel'  => __( 'Product carousel', 'clevers-product-carousel' ),
			)
		);

		$labels = array(
			'name'          => __( 'Product Carousels', 'clevers-product-carousel' ),
			'singular_name' => __( 'Product Carousel', 'clevers-product-carousel' ),
			'add_new'       => __( 'Add New', 'clevers-product-carousel' ),
			'add_new_item'  => __( 'Add New Carousel', 'clevers-product-carousel' ),
			'edit_item'     => __( 'Edit Carousel', 'clevers-product-carousel' ),
			'new_item'      => __( 'New Carousel', 'clevers-product-carousel' ),
			'all_items'     => __( 'All Carousels', 'clevers-product-carousel' ),
			'menu_name'     => __( 'Product Carousels', 'clevers-product-carousel' ),
		);

		$args = array(
			'labels'          => $labels,
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'show_in_rest'    => true,
			'menu_icon'       => 'dashicons-images-alt2',
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		);

		register_post_type( CLEVPRCA_SLUG, $args );
	}
}
