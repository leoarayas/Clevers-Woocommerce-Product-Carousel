<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var WC_Product $clevprca_product */
/** @var array       $settings */

if ( ! $clevprca_product ) {
    return;
}

$clevprca_discount = clevprca_get_discount_percentage( $clevprca_product, 'max' ); // 'min' / 'avg' también válidos.
?>
<div class="clevers-product card-2">
    <div class="product-media">
        <?php if ( null !== $clevprca_discount ) : ?>
            <?php echo wp_kses_post( clevprca_render_discount_badge( (int) $clevprca_discount, $settings, 'clevprca-badge-round' ) ); ?>
        <?php endif; ?>

        <a href="<?php echo esc_url( $clevprca_product->get_permalink() ); ?>" class="product-thumb" aria-label="<?php echo esc_attr( $clevprca_product->get_name() ); ?>">
            <?php echo wp_kses_post( clevprca_add_lazy_loading( $clevprca_product->get_image( 'woocommerce_thumbnail' ) ) ); ?>
        </a>
    </div>

    <a class="product-title" href="<?php echo esc_url( $clevprca_product->get_permalink() ); ?>">
        <?php echo esc_html( mb_strtoupper( $clevprca_product->get_name() ) ); ?>
    </a>

    <div class="price-area">
        <?php echo wp_kses_post( $clevprca_product->get_price_html() ); ?>
    </div>

    <div class="actions">
        <?php if ( $clevprca_product->is_type( 'simple' ) ) : ?>
            <a href="<?php echo esc_url( $clevprca_product->add_to_cart_url() ); ?>"
               class="clevers-button ajax_add_to_cart add_to_cart_button"
               data-product_id="<?php echo esc_attr( $clevprca_product->get_id() ); ?>"
               data-product_sku="<?php echo esc_attr( $clevprca_product->get_sku() ); ?>"
               aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name. */ __( 'Add %s to your cart', 'clevers-product-carousel' ), $clevprca_product->get_name() ) ); ?>">
                <?php esc_html_e( 'Añadir al carrito', 'clevers-product-carousel' ); ?>
            </a>
        <?php else : ?>
            <a href="<?php echo esc_url( $clevprca_product->get_permalink() ); ?>" class="clevers-button" aria-label="<?php echo esc_attr( $clevprca_product->get_name() ); ?>">
                <?php esc_html_e( 'Seleccionar opciones', 'clevers-product-carousel' ); ?>
            </a>
        <?php endif; ?>
    </div>
</div>
