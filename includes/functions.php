<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Agrega lazy loading a una imagen HTML.
 *
 * @param string $html HTML de la imagen.
 * @return string HTML con lazy loading.
 */
function clevers_product_carousel_add_lazy_loading( string $html ): string {
	if ( '' === $html ) {
		return $html;
	}

	// Si ya tiene loading attribute, no hacer nada.
	if ( false !== strpos( $html, 'loading=' ) ) {
		return $html;
	}

	// Agregar loading="lazy" antes del cierre del tag img.
	return str_replace( '<img ', '<img loading="lazy" ', $html );
}

/**
 * Verifica si el servidor soporta WebP.
 *
 * @return bool
 */
function clevers_product_carousel_supports_webp(): bool {
	if ( ! function_exists( 'wp_image_editor_supports' ) ) {
		return false;
	}

	return (bool) wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
}

/**
 * Obtiene la URL de imagen en formato WebP si está disponible.
 *
 * @param int    $attachment_id ID del attachment.
 * @param string $size Tamaño de la imagen.
 * @return string URL de la imagen (WebP o original).
 */
function clevers_product_carousel_get_webp_image_url( int $attachment_id, string $size = 'woocommerce_thumbnail' ): string {
	if ( ! clevers_product_carousel_supports_webp() ) {
		return '';
	}

	$image_data = wp_get_attachment_image_src( $attachment_id, $size );
	if ( ! $image_data ) {
		return '';
	}

	$original_path = get_attached_file( $attachment_id );

	if ( ! $original_path || ! file_exists( $original_path ) ) {
		return '';
	}

	$info = pathinfo( $original_path );
	if ( ! isset( $info['dirname'] ) || '' === $info['dirname'] ) {
		return '';
	}
	$webp_path = $info['dirname'] . '/' . $info['filename'] . '.webp';

	if ( file_exists( $webp_path ) ) {
		$upload_dir = wp_upload_dir();
		$webp_url = str_replace( $upload_dir['basedir'], $upload_dir['basedir'], $webp_path );
		return str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $webp_url );
	}

	return '';
}

/**
 * Agrega soporte WebP a una imagen HTML si está disponible.
 *
 * @param string $html HTML de la imagen.
 * @param int    $attachment_id ID del attachment.
 * @return string HTML con picture element si WebP está disponible.
 */
function clevers_product_carousel_add_webp_support( string $html, int $attachment_id ): string {
	if ( '' === $html || $attachment_id <= 0 ) {
		return $html;
	}

	$webp_url = clevers_product_carousel_get_webp_image_url( $attachment_id );
	if ( '' === $webp_url ) {
		return $html;
	}

	// Extraer la URL original de la imagen.
	if ( ! preg_match( '/src="([^"]+)"/', $html, $matches ) ) {
		return $html;
	}

	$original_url = $matches[1];

	// Crear picture element con WebP y fallback.
	$picture = '<picture>';
	$picture .= '<source srcset="' . esc_url( $webp_url ) . '" type="image/webp">';
	$picture .= str_replace( '<img ', '<img loading="lazy" ', $html );
	$picture .= '</picture>';

	return $picture;
}

/**
 * Obtiene los metadatos del carrusel.
 *
 * @param int $id ID del post.
 * @return array<string, mixed>
 */
function clevers_product_carousel_get_carousel_meta( $id ): array {
	return (array) get_post_meta( $id, '_clv_settings', true );
}

/**
 * Devuelve los valores permitidos para orderby.
 *
 * @return string[]
 */
function clevers_product_carousel_get_allowed_orderby_values(): array {
	return array(
		'date',
		'modified',
		'title',
		'menu_order',
		'rand',
		'price',
		'popularity',
		'rating',
		'date_modified',
	);
}

/**
 * Combina listas de IDs con estrategia configurable.
 *
 * @param int[]|null $current  Lista actual.
 * @param int[]      $incoming Lista entrante.
 * @param string     $strategy intersection|union
 * @return int[]
 */
function clevers_product_carousel_merge_product_ids( ?array $current, array $incoming, string $strategy ): array {
	$incoming = array_values( array_unique( array_map( 'intval', $incoming ) ) );

	if ( null === $current ) {
		return $incoming;
	}

	if ( 'union' === $strategy ) {
		return array_values( array_unique( array_merge( $current, $incoming ) ) );
	}

	return array_values( array_intersect( $current, $incoming ) );
}

/**
 * Sanitiza valores simples para CSS variables.
 *
 * @param mixed $value Valor.
 * @return string
 */
function clevers_product_carousel_sanitize_css_value( $value ): string {
	if ( ! is_string( $value ) && ! is_int( $value ) && ! is_float( $value ) ) {
		return '';
	}
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}

	if ( 'transparent' === strtolower( $value ) ) {
		return 'transparent';
	}

	$hex = sanitize_hex_color( $value );
	return $hex ? $hex : '';
}

/**
 * Converts numeric settings from WordPress metadata to integers safely.
 *
 * @param mixed $value
 */
function clevers_product_carousel_to_int( $value, int $default = 0 ): int {
	return is_numeric( $value ) ? (int) $value : $default;
}

/**
 * Converts scalar WordPress metadata to strings safely.
 *
 * @param mixed $value
 */
function clevers_product_carousel_to_string( $value, string $default = '' ): string {
	return is_string( $value ) || is_int( $value ) || is_float( $value ) ? (string) $value : $default;
}

/**
 * Localiza una plantilla con soporte de override en tema.
 *
 * @param string $rel_path Ruta relativa dentro de templates/.
 * @return string
 */
function clevers_product_carousel_locate_template( $rel_path ): string {
	$rel_path  = ltrim( (string) $rel_path, '/' );
	$rel_path  = apply_filters( 'clevers_carousel_template_path', $rel_path );
	$theme_path = 'clevers-product-carousel/' . $rel_path;
	$tpl        = locate_template( $theme_path );

	if ( $tpl ) {
		return $tpl;
	}

	return CLV_DIR . 'templates/' . $rel_path;
}

/**
 * Construye los argumentos de la query para el carrusel.
 *
 * @param int $carousel_id ID del carrusel.
 * @return array<string, mixed>
 */
function clevers_product_carousel_build_query_args( $carousel_id ) {
	$meta    = clevers_product_carousel_get_carousel_meta( $carousel_id );
	$orderby = sanitize_text_field( clevers_product_carousel_to_string( $meta['orderby'] ?? null, 'date' ) );

	if ( ! in_array( $orderby, clevers_product_carousel_get_allowed_orderby_values(), true ) ) {
		$orderby = 'date';
	}

	$order = strtoupper( sanitize_text_field( clevers_product_carousel_to_string( $meta['order'] ?? null, 'DESC' ) ) );
	$order = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';

	$args = array(
		'limit'  => max( 1, min( 48, clevers_product_carousel_to_int( $meta['limit'] ?? null, 8 ) ) ),
		'order'  => $order,
		'return' => 'objects',
	);

	$manual_product_ids = array_values(
		array_unique(
			array_filter(
				array_map( static function ( $id ): int { return clevers_product_carousel_to_int( $id ); }, (array) ( $meta['manual_product_ids'] ?? array() ) )
			)
		)
	);

	if ( ! empty( $meta['manual_products_enabled'] ) && ! empty( $manual_product_ids ) ) {
		$args['include'] = $manual_product_ids;
		$args['orderby'] = 'include';

		$args = apply_filters( 'clevers_carousel/query_args', $args, $carousel_id, $meta );
		$args = apply_filters( 'clevers_carousel_query_args', $args, $carousel_id, $meta );

		return is_array( $args ) ? $args : array();
	}

	switch ( $orderby ) {
		case 'price':
		case 'popularity':
		case 'rating':
		case 'modified':
		case 'menu_order':
		case 'rand':
		case 'title':
		case 'date_modified':
			$args['orderby'] = $orderby;
			break;
		default:
			$args['orderby'] = 'date';
			break;
	}

	if ( ! empty( $meta['categories'] ) ) {
		$args['category'] = array_values(
			array_filter(
				array_map( 'sanitize_title', (array) $meta['categories'] )
			)
		);
	}

	$include_ids = null;
	$strategy    = apply_filters( 'clevers_carousel/include_strategy', 'intersection', $carousel_id, $meta );
	$strategy    = ( 'union' === $strategy ) ? 'union' : 'intersection';

	if ( ! empty( $meta['on_sale'] ) ) {
		$include_ids = clevers_product_carousel_merge_product_ids(
			$include_ids,
			(array) wc_get_product_ids_on_sale(),
			$strategy
		);
	}

	if ( ! empty( $meta['on_featured'] ) ) {
		$include_ids = clevers_product_carousel_merge_product_ids(
			$include_ids,
			(array) wc_get_featured_product_ids(),
			$strategy
		);
	}

	if ( null !== $include_ids ) {
		$args['include'] = ! empty( $include_ids ) ? array_values( $include_ids ) : array( 0 );
	}

	if ( ! empty( $meta['instock_only'] ) ) {
		$args['stock_status'] = 'instock';
	}

	$args = apply_filters( 'clevers_carousel/query_args', $args, $carousel_id, $meta );
	$args = apply_filters( 'clevers_carousel_query_args', $args, $carousel_id, $meta );

	return is_array( $args ) ? $args : array();
}

/**
 * Obtiene los ajustes del carrusel con valores por defecto.
 *
 * @param int $carousel_id ID del carrusel.
 * @return array<string, mixed>
 */
function clevers_product_carousel_get_settings( $carousel_id ) {
	$meta     = clevers_product_carousel_get_carousel_meta( $carousel_id );
	$defaults = array(
		'preset'                    => 1,
		'slidesToShow'              => 4,
		'slidesToShowTablet'        => 2,
		'slidesToShowMobile'        => 1,
		'autoplay'                  => false,
		'autoplayMs'                => 3000,
		'dots'                      => false,
		'arrows'                    => true,
		'pauseOnHover'              => true,
		'pauseOnFocus'              => true,
		'reducedMotionAutoplayOff'  => true,
		'builder_compat_mode'       => false,
		'builder_init_delay_ms'     => 0,
		'builder_disable_center_mode' => false,
	);

	$settings = wp_parse_args( $meta, $defaults );
	$settings['preset']       = max( 1, min( 4, (int) $settings['preset'] ) );
	$settings['slidesToShow'] = max( 1, min( 8, (int) $settings['slidesToShow'] ) );
	$settings['slidesToShowTablet'] = max( 1, min( 8, (int) ( $settings['slidesToShowTablet'] ?? min( 2, $settings['slidesToShow'] ) ) ) );
	$settings['slidesToShowMobile'] = max( 1, min( 8, (int) ( $settings['slidesToShowMobile'] ?? 1 ) ) );
	$settings['autoplayMs']   = max( 500, min( 60000, (int) $settings['autoplayMs'] ) );
	$settings['autoplay']     = ! empty( $settings['autoplay'] );
	$settings['dots']         = ! empty( $settings['dots'] );
	$settings['arrows']       = ! empty( $settings['arrows'] );
	$settings['builder_compat_mode'] = ! empty( $settings['builder_compat_mode'] );
	$settings['builder_init_delay_ms'] = max( 0, min( 5000, (int) ( $settings['builder_init_delay_ms'] ?? 0 ) ) );
	$settings['builder_disable_center_mode'] = ! empty( $settings['builder_disable_center_mode'] );

	return apply_filters( 'clevers_carousel/settings', $settings, $carousel_id );
}

/**
 * Obtiene métricas de procesamiento del carrusel.
 *
 * @param int $carousel_id ID del carrusel.
 * @return array<string, int|float|string>
 */
function clevers_product_carousel_get_queue_metrics( int $carousel_id ): array {
	$defaults = array(
		'pending'                 => 0,
		'processed'               => 0,
		'failed'                  => 0,
		'avg_time_ms_per_product' => 0.0,
		'last_error'              => '',
		'last_run_at'             => '',
	);

	$stored = get_post_meta( $carousel_id, '_clv_queue_metrics', true );
	if ( ! is_array( $stored ) ) {
		return $defaults;
	}

	$metrics = wp_parse_args( $stored, $defaults );
	$metrics['pending']   = max( 0, (int) $metrics['pending'] );
	$metrics['processed'] = max( 0, (int) $metrics['processed'] );
	$metrics['failed']    = max( 0, (int) $metrics['failed'] );
	$metrics['avg_time_ms_per_product'] = max( 0, round( (float) $metrics['avg_time_ms_per_product'], 2 ) );
	$metrics['last_error'] = sanitize_text_field( (string) $metrics['last_error'] );
	$metrics['last_run_at'] = sanitize_text_field( (string) $metrics['last_run_at'] );

	return $metrics;
}

/**
 * Registra métricas del pipeline de render del carrusel.
 *
 * @param int    $carousel_id ID del carrusel.
 * @param int    $pending Cantidad pendiente.
 * @param int    $processed Cantidad procesada.
 * @param int    $failed Cantidad fallida.
 * @param float  $avg_time_ms_per_product Tiempo promedio por producto.
 * @param string $last_error Último error.
 * @return void
 */
function clevers_product_carousel_update_queue_metrics(
	int $carousel_id,
	int $pending,
	int $processed,
	int $failed,
	float $avg_time_ms_per_product,
	string $last_error = ''
): void {
	update_post_meta(
		$carousel_id,
		'_clv_queue_metrics',
		array(
			'pending'                 => max( 0, $pending ),
			'processed'               => max( 0, $processed ),
			'failed'                  => max( 0, $failed ),
			'avg_time_ms_per_product' => max( 0, round( $avg_time_ms_per_product, 2 ) ),
			'last_error'              => sanitize_text_field( $last_error ),
			'last_run_at'             => gmdate( 'c' ),
		)
	);
}
