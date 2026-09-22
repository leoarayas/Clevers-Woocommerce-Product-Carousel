<?php
/**
 * Discount helpers for Clevers Product Carousel.
 *
 * @package CLEVPRCA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calcula el % de descuento de un producto.
 *
 * @param WC_Product $product
 * @param string     $strategy  'max' | 'min' | 'avg'
 * @return int|null  Porcentaje entero (0-100) o null si no aplica
 */
function clevprca_get_discount_percentage( WC_Product $product, string $strategy = 'max' ): ?int {
	if ( ! $product->is_on_sale() ) {
		return null;
	}

	// Simple / external.
	if ( ! $product->is_type( 'variable' ) ) {
		$r = (float) ( $product->get_regular_price() ?: 0 );
		$s = (float) ( $product->get_sale_price() ?: 0 );
		if ( $r > 0 && $s > 0 && $s < $r ) {
			return (int) round( ( ( $r - $s ) / $r ) * 100 );
		}
		return null;
	}

	// Variable: calcula por variación.
	$discounts = array();
	foreach ( $product->get_children() as $vid ) {
		$v = wc_get_product( $vid );
		if ( ! $v || ! $v->is_on_sale() ) {
			continue;
		}
		$r = (float) ( $v->get_regular_price() ?: 0 );
		$s = (float) ( $v->get_sale_price() ?: 0 );
		if ( $r > 0 && $s > 0 && $s < $r ) {
			$discounts[] = ( ( $r - $s ) / $r ) * 100;
		}
	}

	if ( empty( $discounts ) ) {
		return null;
	}

	switch ( $strategy ) {
		case 'min':
			$pct = min( $discounts );
			break;
		case 'avg':
			$pct = array_sum( $discounts ) / count( $discounts );
			break;
		case 'max':
		default:
			$pct = max( $discounts );
			break;
	}

	/**
	 * Permite ajustar el redondeo (p.ej. ceil/floor).
	 *
	 * @param float      $pct
	 * @param WC_Product $product
	 * @param string     $strategy
	 */
	$pct = apply_filters( 'clevprca/discount_percentage/value', $pct, $product, $strategy );

	$pct_int = (int) round( $pct );
	return $pct_int > 0 ? $pct_int : null;
}

/**
 * Render del badge de descuento (HTML).
 *
 * @param int                  $percentage
 * @param array<string, mixed> $settings   (opcional) para decidir clase extra según preset
 * @param string|null          $extra_class
 * @return string
 */
function clevprca_render_discount_badge( int $percentage, array $settings = array(), ?string $extra_class = null ): string {
	$classes = array( 'clevprca-badge-discount' );
	if ( ! empty( $settings['preset'] ) ) {
		$classes[] = 'preset-' . clevprca_to_int( $settings['preset'] ) . '-badge';
	}
	if ( $extra_class ) {
		$classes[] = $extra_class;
	}

	$label = sprintf(
		/* translators: %d discount percentage */
		__( '%d%% de descuento', 'clevers-product-carousel' ),
		$percentage
	);

	/**
	 * Permite personalizar el texto visible del badge.
	 *
	 * @param string $inner_text
	 * @param int    $percentage
	 * @param array  $settings
	 */
	$inner_text = apply_filters( 'clevprca/discount_badge/text', $percentage . '%', $percentage, $settings );

	return sprintf(
		'<span class="%s" aria-label="%s" title="%s"><span class="sr-only">%s</span>%s</span>',
		esc_attr( implode( ' ', $classes ) ),
		esc_attr( $label ),
		esc_attr( $label ),
		esc_html( $label ),
		esc_html( $inner_text )
	);
}
