<?php
// templates/cards/card-1.php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var WC_Product $clevprca_product */
/** @var array $settings */

if (!$clevprca_product) {
    return;
}

$clevprca_price_html = $clevprca_product->get_price_html();
$clevprca_permalink = $clevprca_product->get_permalink();
$clevprca_title = $clevprca_product->get_name();
$clevprca_img = $clevprca_product->get_image( 'woocommerce_thumbnail' );
$clevprca_attachment_id = $clevprca_product->get_image_id();

$clevprca_discount = clevprca_get_discount_percentage($clevprca_product, 'max');


$clevprca_aria_label = sprintf(
/* translators: %s: product title. */
        esc_html__('Add %s to your cart', 'clevers-product-carousel'),
        $clevprca_title
);
?>

<div class="clevers-card preset-1-card" data-product-id="<?php echo esc_attr($clevprca_product->get_id()); ?>">

    <a href="<?php echo esc_url($clevprca_permalink); ?>" class="product-thumb" aria-label="<?php echo esc_attr( $clevprca_title ); ?>">
        <?php if ($clevprca_discount) : ?>
            <?php echo wp_kses_post( clevprca_render_discount_badge( (int) $clevprca_discount, $settings, 'badge-discount' ) ); ?>
        <?php endif; ?>

        <?php echo wp_kses_post( clevprca_add_webp_support( $clevprca_img, (int) $clevprca_attachment_id ) ); ?>
    </a>
    <div class="product-info">

        <a href="<?php echo esc_url($clevprca_permalink); ?>" class="product-title">
            <?php echo esc_html($clevprca_title); ?>
        </a>

        <div class="price-area"><?php echo wp_kses_post($clevprca_price_html); ?></div>

        <?php if ($clevprca_product->is_type('simple')) : ?>

            <a href="<?php echo esc_url($clevprca_product->add_to_cart_url()); ?>"
               class="button add_to_cart_button ajax_add_to_cart"
               data-product_id="<?php echo esc_attr($clevprca_product->get_id()); ?>"
               data-product_sku="<?php echo esc_attr($clevprca_product->get_sku()); ?>"
               data-success_message="<?php echo esc_attr__( 'Product added to cart', 'clevers-product-carousel' ); ?>"
               aria-label="<?php echo esc_attr($clevprca_aria_label); ?>">
                <?php esc_html_e('Añadir al carrito', 'clevers-product-carousel'); ?>
            </a>

        <?php else : ?>

            <a href="<?php echo esc_url($clevprca_permalink); ?>" class="button select-options" aria-label="<?php echo esc_attr( $clevprca_title ); ?>">
                <?php esc_html_e('Seleccionar opciones', 'clevers-product-carousel'); ?>
            </a>

        <?php endif; ?>

    </div>
</div>
