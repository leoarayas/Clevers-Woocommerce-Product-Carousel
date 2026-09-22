<?php
// templates/cards/card-1.php (preset 3)

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Card 3 / Preset 3
 *
 * @var WC_Product $clevprca_product
 * @var array      $settings
 */

if ( ! $clevprca_product instanceof WC_Product ) {
    return;
}

$clevprca_price_html = $clevprca_product->get_price_html();
$clevprca_permalink  = $clevprca_product->get_permalink();
$clevprca_title      = $clevprca_product->get_name();
$clevprca_img        = $clevprca_product->get_image( 'woocommerce_thumbnail' );

// Helper con prefijo del plugin.
$clevprca_discount = clevprca_get_discount_percentage(
    $clevprca_product,
    'max' // 'min' / 'avg' también válidos
);
?>
<div class="clevers-card preset-3-card" data-product-id="<?php echo esc_attr( $clevprca_product->get_id() ); ?>">
    <a href="<?php echo esc_url( $clevprca_permalink ); ?>" class="product-thumb" aria-label="<?php echo esc_attr( $clevprca_title ); ?>">
        <?php if ( $clevprca_discount ) : ?>
            <?php echo wp_kses_post( clevprca_render_discount_badge( (int) $clevprca_discount, $settings, 'badge-discount' ) ); ?>
        <?php endif; ?>

        <?php echo wp_kses_post( clevprca_add_lazy_loading( $clevprca_img ) ); ?>
    </a>

    <div class="product-info">
        <a href="<?php echo esc_url( $clevprca_permalink ); ?>" class="product-title">
            <?php echo esc_html( $clevprca_title ); ?>
        </a>

        <div class="price-area">
            <?php echo wp_kses_post( $clevprca_price_html ); ?>
        </div>

            <a href="<?php echo esc_url( $clevprca_permalink ); ?>" class="button select-options" aria-label="<?php echo esc_attr( $clevprca_title ); ?>">
                <?php esc_html_e( 'Ver Producto', 'clevers-product-carousel' ); ?>
            </a>

    </div>
</div>
