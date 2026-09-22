<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CLEVPRCA_Render {

	public function init(): void {
		add_shortcode( 'cleverspr_carousel', array( $this, 'shortcode' ) );
		add_action( 'init', array( $this, 'register_block_type' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );

		// Invalidación por cambios de productos.
		add_action( 'save_post_product', array( $this, 'invalidate_cache' ) );
		add_action( 'save_post_product_variation', array( $this, 'invalidate_cache' ) );
		add_action( 'woocommerce_update_product', array( $this, 'invalidate_cache' ) );
		add_action( 'woocommerce_delete_product_transients', array( $this, 'invalidate_cache' ) );
		add_action( 'woocommerce_scheduled_sales', array( $this, 'invalidate_cache' ) );
		add_action( 'set_object_terms', array( $this, 'invalidate_cache_on_terms_change' ), 10, 6 );
		add_action( 'updated_post_meta', array( $this, 'invalidate_cache_on_product_meta_change' ), 10, 4 );
		add_action( 'added_post_meta', array( $this, 'invalidate_cache_on_product_meta_change' ), 10, 4 );
		add_action( 'deleted_post_meta', array( $this, 'invalidate_cache_on_product_meta_change' ), 10, 4 );
	}

	public function maybe_enqueue_assets(): void {
		if ( is_admin() || ! is_singular() ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$content       = (string) $post->post_content;
		$has_shortcode = has_shortcode( $content, 'cleverspr_carousel' );
		$has_block     = function_exists( 'has_block' ) && has_block( 'cleverspr/carousel', $post );

		if ( ! $has_shortcode && ! $has_block ) {
			return;
		}

		wp_enqueue_style( 'clevprca-slick' );
		wp_enqueue_style( 'clevprca-slick-theme' );
		wp_enqueue_style( 'clevprca-carousel' );
		wp_enqueue_script( 'clevprca-slick' );
		wp_enqueue_script( 'clevprca-carousel' );
	}

	/** @param array<string, mixed>|string $atts */
	public function shortcode( $atts ): string {
		$atts = is_array( $atts ) ? $atts : array();

		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts
		);

		/**
		 * The returned HTML may be modified by third-party code via the
		 * `cleverspr_carousel/rendered_html` filter. Wrap the final output in
		 * wp_kses_post() so any untrusted markup added through the filter is
		 * stripped before reaching the page (defense in depth).
		 */
		return wp_kses_post( $this->render_carousel( (int) $atts['id'] ) );
	}

	public function register_block_type(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$handle = 'clevprca-carousel-block-editor';
		$src    = CLEVPRCA_URL . 'assets/block.js';
		$path   = CLEVPRCA_DIR . 'assets/block.js';
		$ver    = file_exists( $path ) ? (string) filemtime( $path ) : '1.0.0';

		wp_register_script(
			$handle,
			$src,
			array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor', 'wp-data' ),
			$ver,
			true
		);

		register_block_type(
			'cleverspr/carousel',
			array(
				'api_version'     => 2,
				'editor_script'   => $handle,
				'render_callback' => array( $this, 'render_block' ),
				'attributes'      => array(
					'carouselId' => array(
						'type'    => 'number',
						'default' => 0,
					),
				),
			)
		);
	}

	/** @param array<string, mixed> $attributes */
	public function render_block( array $attributes ): string {
		$carousel_id = clevprca_to_int( $attributes['carouselId'] ?? null );

		// See note in shortcode(): wp_kses_post() defends against untrusted HTML
		// injected through the cleverspr_carousel/rendered_html filter.
		return wp_kses_post( $this->render_carousel( $carousel_id ) );
	}

	public function render_carousel( int $carousel_id ): string {
		if ( ! class_exists( 'WooCommerce' ) || $carousel_id <= 0 ) {
			return '';
		}

		$carousel = get_post( $carousel_id );
		if ( ! $carousel || CLEVPRCA_SLUG !== $carousel->post_type ) {
			return '';
		}

		$args     = clevprca_build_query_args( $carousel_id );
		$settings = clevprca_get_settings( $carousel_id );

		wp_enqueue_style( 'clevprca-slick' );
		wp_enqueue_style( 'clevprca-slick-theme' );
		wp_enqueue_style( 'clevprca-carousel' );
		wp_enqueue_script( 'clevprca-slick' );
		wp_enqueue_script( 'clevprca-carousel' );

		$this->enqueue_inline_vars( $carousel_id, $settings );

		$ver       = (int) get_post_meta( $carousel_id, '_clevprca_cache_version', true );
		$bump      = (int) get_option( 'clevprca_global_cache_bump', 0 );
		$cache_key = 'cleverspr_carousel_' . $carousel_id . '_v' . $ver . '_g' . $bump . '_' .
			md5( wp_json_encode( $args ) . '|' . wp_json_encode( $settings ) );

		$html = get_transient( $cache_key );
		if ( false !== $html ) {
			$html = $this->inject_brizy_editor_preview_css( (string) $html );
			return (string) apply_filters( 'cleverspr_carousel/rendered_html', $html, $carousel_id, $settings, true );
		}

		$start_time = microtime( true );
		$products   = ( new WC_Product_Query( $args ) )->get_products();
		$products   = apply_filters( 'cleverspr_carousel/products', $products, $carousel_id, $args, $settings );
		$pending    = is_array( $products ) ? count( $products ) : 0;

		// Save global product to restore later.
		// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		global $product;
		$original_product = $product;

		ob_start();
		try {
			do_action( 'cleverspr_carousel/before', $carousel_id, $settings, $products );
			do_action( 'cleverspr_carousel_before_render', $carousel_id, $settings, $products );

			$template_rel = 'carousels/carousel-' . clevprca_to_int( $settings['preset'] ?? null, 1 ) . '.php';
			$template_rel = apply_filters( 'cleverspr_carousel/carousel_template_relpath', $template_rel, $carousel_id, $settings, $products );
			include clevprca_locate_template( $template_rel );

			do_action( 'cleverspr_carousel/after', $carousel_id, $settings, $products );
			do_action( 'cleverspr_carousel_after_render', $carousel_id, $settings, $products );
			$html = (string) ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
			$elapsed_ms = max( 0, ( microtime( true ) - $start_time ) * 1000 );
			clevprca_update_queue_metrics(
				$carousel_id,
				$pending,
				0,
				max( 1, $pending ),
				$pending > 0 ? ( $elapsed_ms / $pending ) : $elapsed_ms,
				$e->getMessage()
			);
			$product = $original_product;
			return '';
		}

		$product = $original_product;
		// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

		$elapsed_ms = max( 0, ( microtime( true ) - $start_time ) * 1000 );
		clevprca_update_queue_metrics(
			$carousel_id,
			0,
			$pending,
			0,
			$pending > 0 ? ( $elapsed_ms / $pending ) : $elapsed_ms
		);

		$cache_ttl = (int) apply_filters( 'cleverspr_carousel/cache_ttl', 10 * MINUTE_IN_SECONDS, $carousel_id, $settings, $args );
		set_transient( $cache_key, $html, max( MINUTE_IN_SECONDS, $cache_ttl ) );

		$html = $this->inject_brizy_editor_preview_css( (string) $html );
		return (string) apply_filters( 'cleverspr_carousel/rendered_html', $html, $carousel_id, $settings, false );
	}

	/**
	 * Brizy editor executes the shortcode HTML but often skips the carousel JS.
	 * Inject a scoped CSS-only horizontal preview that applies only inside Brizy
	 * shortcode wrappers, without affecting the frontend.
	 *
	 * The CSS is enqueued via wp_add_inline_style() (proper WordPress enqueue
	 * pipeline) rather than concatenated as an inline <style> tag into the
	 * rendered HTML.
	 *
	 * @param string $html Rendered shortcode HTML.
	 * @return string
	 */
	private function inject_brizy_editor_preview_css( string $html ): string {
		if ( '' === $html ) {
			return $html;
		}
		if ( ! clevprca_is_brizy_editor_preview_request() ) {
			return $html;
		}

		$css  = '.brz-wp-shortcode .clevers-product-carousel .slick-carousel:not(.slick-initialized){display:grid!important;grid-auto-flow:column!important;grid-auto-columns:calc(100%/var(--clevprca-fallback-desktop,4))!important;gap:16px!important;overflow-x:auto!important;overflow-y:hidden!important;align-items:stretch!important;-webkit-overflow-scrolling:touch;scroll-snap-type:x proximity;}';
		$css .= '.brz-wp-shortcode .clevers-product-carousel .slick-carousel:not(.slick-initialized) .carousel-item{min-width:0;scroll-snap-align:start;}';
		$css .= '@media (max-width:1024px){.brz-wp-shortcode .clevers-product-carousel .slick-carousel:not(.slick-initialized){grid-auto-columns:calc(100%/var(--clevprca-fallback-tablet,2))!important;}}';
		$css .= '@media (max-width:768px){.brz-wp-shortcode .clevers-product-carousel .slick-carousel:not(.slick-initialized){grid-auto-columns:calc(100%/var(--clevprca-fallback-mobile,1))!important;}}';

		wp_add_inline_style( 'clevprca-carousel', $css );

		return $html;
	}


	/** @param array<string, mixed> $settings */
	private function enqueue_inline_vars( int $carousel_id, array $settings ): void {
		$vars_map = array(
			'color_primary'     => '--clevers-primary',
			'color_primary2'    => '--clevers-primary-hover',
			'color_secondary'   => '--clevers-secondary',
			'color_accent'      => '--clevers-accent',
			'color_text'        => '--clevers-text',
			'color_card_bg'     => '--clevers-card-bg',
			'color_border'      => '--clevers-border',
			'bubble_background' => '--clevers-bubble-background',
			'bubble_text'       => '--clevers-bubble-text',
			'button_background' => '--clevers-button-background',
			'button_text'       => '--clevers-button-text',
		);

		$vars = array();
		foreach ( $vars_map as $setting_key => $css_var ) {
			if ( empty( $settings[ $setting_key ] ) ) {
				continue;
			}

			$sanitized = clevprca_sanitize_css_value( $settings[ $setting_key ] );
			if ( '' === $sanitized ) {
				continue;
			}

			$vars[] = $css_var . ':' . $sanitized . ';';
		}

		$vars = apply_filters( 'cleverspr_carousel_css_vars', $vars, $carousel_id, $settings, $vars_map );
		if ( ! is_array( $vars ) ) {
			$vars = array();
		}

		if ( ! $vars ) {
			return;
		}

		$inline = '#clevers-product-carousel-' . $carousel_id . '{' . implode( '', array_map( static function ( $value ): string { return clevprca_to_string( $value ); }, $vars ) ) . '}';
		wp_add_inline_style( 'clevprca-carousel', $inline );
	}

	public function invalidate_cache(): void {
		update_option(
			'clevprca_global_cache_bump',
			(int) get_option( 'clevprca_global_cache_bump', 0 ) + 1,
			false
		);
	}

	/**
	 * @param int $object_id
	 * @param mixed $terms
	 * @param mixed $tt_ids
	 * @param string $taxonomy
	 * @param bool $append
	 * @param mixed $old_tt_ids
	 */
	public function invalidate_cache_on_terms_change( int $object_id, $terms, $tt_ids, string $taxonomy, bool $append, $old_tt_ids ): void {
		unset( $terms, $tt_ids, $append, $old_tt_ids );

		if ( 'product_cat' !== $taxonomy && 'product_tag' !== $taxonomy ) {
			return;
		}

		$post_type = get_post_type( (int) $object_id );
		if ( 'product' !== $post_type && 'product_variation' !== $post_type ) {
			return;
		}

		$this->invalidate_cache();
	}

	/**
	 * @param mixed $meta_id
	 * @param int $object_id
	 * @param string $meta_key
	 * @param mixed $meta_value
	 */
	public function invalidate_cache_on_product_meta_change( $meta_id, int $object_id, string $meta_key, $meta_value ): void {
		unset( $meta_id, $meta_value );

		$post_type = get_post_type( (int) $object_id );
		if ( 'product' !== $post_type && 'product_variation' !== $post_type ) {
			return;
		}

		$watched_keys = array(
			'_price',
			'_sale_price',
			'_regular_price',
			'_stock',
			'_stock_status',
			'_featured',
			'_sale_price_dates_from',
			'_sale_price_dates_to',
		);

		if ( in_array( (string) $meta_key, $watched_keys, true ) ) {
			$this->invalidate_cache();
		}
	}
}

/**
 * Helper functions for templates.
 *
 * @param WC_Product $clevprca_product Producto.
 * @param array<string, mixed> $settings Ajustes.
 * @return void
 */
function clevprca_render_card( $clevprca_product, $settings ) {
	// Compatibilidad con plantillas WooCommerce que usan global $product.
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce template compatibility.
	$GLOBALS['product'] = $clevprca_product;

	$tpl = 'cards/card-' . clevprca_to_int( $settings['preset'] ?? null, 1 ) . '.php';
	$tpl = apply_filters( 'cleverspr_carousel/card_template_relpath', $tpl, $clevprca_product, $settings );

	include clevprca_locate_template( $tpl );
}

/**
 * Construye atributos para el contenedor Slick.
 *
 * @param int   $carousel_id ID del carrusel.
 * @param array<string, mixed> $settings Ajustes.
 * @return string
 */
function clevprca_get_slider_data_attributes( $carousel_id, array $settings ): string {
	$attrs = array(
		'data-carousel-id'      => (string) clevprca_to_int( $carousel_id ),
			'data-slides'           => (string) max( 1, min( 8, clevprca_to_int( $settings['slidesToShow'] ?? null, 4 ) ) ),
			'data-slides-tablet'    => (string) max( 1, min( 8, clevprca_to_int( $settings['slidesToShowTablet'] ?? null, 2 ) ) ),
			'data-slides-mobile'    => (string) max( 1, min( 8, clevprca_to_int( $settings['slidesToShowMobile'] ?? null, 1 ) ) ),
		'data-autoplay'         => ! empty( $settings['autoplay'] ) ? 'true' : 'false',
		'data-speed'            => (string) max( 500, min( 60000, clevprca_to_int( $settings['autoplayMs'] ?? null, 3000 ) ) ),
		'data-dots'             => ! empty( $settings['dots'] ) ? 'true' : 'false',
		'data-arrows'           => ! empty( $settings['arrows'] ) ? 'true' : 'false',
		'data-pause-on-hover'   => ! empty( $settings['pauseOnHover'] ) ? 'true' : 'false',
		'data-pause-on-focus'   => ! empty( $settings['pauseOnFocus'] ) ? 'true' : 'false',
			'data-reduced-motion'   => ! empty( $settings['reducedMotionAutoplayOff'] ) ? 'true' : 'false',
			'data-builder-compat'   => ! empty( $settings['builder_compat_mode'] ) ? 'true' : 'false',
			'data-builder-delay'    => (string) max( 0, min( 5000, clevprca_to_int( $settings['builder_init_delay_ms'] ?? null ) ) ),
			'data-disable-center-on-builder' => ! empty( $settings['builder_disable_center_mode'] ) ? 'true' : 'false',
	);

	$attrs['style'] = sprintf(
		'--clevprca-fallback-desktop:%1$d;--clevprca-fallback-tablet:%2$d;--clevprca-fallback-mobile:%3$d;',
		(int) $attrs['data-slides'],
		(int) $attrs['data-slides-tablet'],
		(int) $attrs['data-slides-mobile']
	);

	// Brizy editor preview often renders shortcode HTML without running Slick JS.
	// Force a horizontal, scrollable layout directly inline so the preview remains usable.
	if ( clevprca_is_brizy_editor_preview_request() ) {
		$attrs['style'] .= 'display:grid;grid-auto-flow:column;grid-auto-columns:calc(100%/var(--clevprca-fallback-desktop,4));gap:16px;overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch;';
	}

	$attrs = apply_filters( 'cleverspr_carousel/slider_data_attributes', $attrs, $carousel_id, $settings );

	$parts = array();
	foreach ( $attrs as $name => $value ) {
		$parts[] = sprintf( '%s="%s"', esc_attr( (string) $name ), esc_attr( (string) $value ) );
	}

	return implode( ' ', $parts );
}

/**
 * Detect Brizy editor preview contexts (iframe/editor/AJAX shortcode preview).
 *
 * @return bool
 */
function clevprca_is_brizy_editor_preview_request(): bool {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only boolean probe (stripos) on request context, never persisted or echoed. wp_unslash() strips magic-quote slashes before substring matching.
	$action = isset( $_REQUEST['action'] ) ? (string) wp_unslash( $_REQUEST['action'] ) : '';
	if ( false !== stripos( $action, 'in-front-editor' ) ) {
		return true;
	}

	if ( isset( $_REQUEST['is-editor-iframe'] ) ) {
		return true;
	}

	if ( false !== stripos( $action, 'shortcode_content' ) ) {
		$shortcode = isset( $_REQUEST['shortcode'] ) ? (string) wp_unslash( $_REQUEST['shortcode'] ) : '';
		if ( false !== stripos( $shortcode, '[cleverspr_carousel' ) ) {
			return true;
		}
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	$referer = wp_get_referer();
	if ( is_string( $referer ) && false !== stripos( $referer, 'in-front-editor' ) ) {
		return true;
	}

	return false;
}
