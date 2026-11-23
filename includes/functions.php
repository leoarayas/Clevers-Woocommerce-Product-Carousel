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
function clevprca_add_lazy_loading( string $html ): string {
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
function clevprca_supports_webp(): bool {
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
function clevprca_get_webp_image_url( int $attachment_id, string $size = 'woocommerce_thumbnail' ): string {
	if ( ! clevprca_supports_webp() ) {
		return '';
	}

	$image_data = wp_get_attachment_image_src( $attachment_id, $size );
	if ( ! $image_data ) {
		return '';
	}

	$upload_dir = wp_upload_dir();
	$image_url  = $image_data[0];
	if ( 0 !== strpos( $image_url, $upload_dir['baseurl'] ) ) {
		return '';
	}
	$image_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $image_url );
	if ( ! file_exists( $image_path ) ) {
		return '';
	}

	$info = pathinfo( $image_path );
	if ( ! isset( $info['dirname'] ) || '' === $info['dirname'] ) {
		return '';
	}
	$webp_path = $info['dirname'] . '/' . $info['filename'] . '.webp';

	if ( file_exists( $webp_path ) ) {
		return str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $webp_path );
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
function clevprca_add_webp_support( string $html, int $attachment_id ): string {
	if ( '' === $html || $attachment_id <= 0 ) {
		return $html;
	}

	$webp_url = clevprca_get_webp_image_url( $attachment_id );
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
function clevprca_get_carousel_meta( $id ): array {
	return (array) get_post_meta( $id, '_clevprca_settings', true );
}

/**
 * Devuelve los valores permitidos para orderby.
 *
 * @return string[]
 */
function clevprca_get_allowed_orderby_values(): array {
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
function clevprca_merge_product_ids( ?array $current, array $incoming, string $strategy ): array {
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
function clevprca_sanitize_css_value( $value ): string {
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
function clevprca_to_int( $value, int $default = 0 ): int {
	return is_numeric( $value ) ? (int) $value : $default;
}

/**
 * Converts scalar WordPress metadata to strings safely.
 *
 * @param mixed $value
 */
function clevprca_to_string( $value, string $default = '' ): string {
	return is_string( $value ) || is_int( $value ) || is_float( $value ) ? (string) $value : $default;
}

/**
 * Localiza una plantilla con soporte de override en tema.
 *
 * @param string $rel_path Ruta relativa dentro de templates/.
 * @return string
 */
function clevprca_locate_template( $rel_path ): string {
	$rel_path  = ltrim( (string) $rel_path, '/' );
	$rel_path  = apply_filters( 'cleverspr_carousel_template_path', $rel_path );
	$theme_path = 'clevers-product-carousel/' . $rel_path;
	$tpl        = locate_template( $theme_path );

	if ( $tpl ) {
		return $tpl;
	}

	return CLEVPRCA_DIR . 'templates/' . $rel_path;
}

/**
 * Construye los argumentos de la query para el carrusel.
 *
 * @param int $carousel_id ID del carrusel.
 * @return array<string, mixed>
 */
function clevprca_build_query_args( $carousel_id ) {
	$meta    = clevprca_get_carousel_meta( $carousel_id );
	$orderby = sanitize_text_field( clevprca_to_string( $meta['orderby'] ?? null, 'date' ) );

	if ( ! in_array( $orderby, clevprca_get_allowed_orderby_values(), true ) ) {
		$orderby = 'date';
	}

	$order = strtoupper( sanitize_text_field( clevprca_to_string( $meta['order'] ?? null, 'DESC' ) ) );
	$order = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';

	$args = array(
		'limit'  => max( 1, min( 48, clevprca_to_int( $meta['limit'] ?? null, 8 ) ) ),
		'order'  => $order,
		'return' => 'objects',
	);

	$manual_product_ids = array_values(
		array_unique(
			array_filter(
				array_map( static function ( $id ): int { return clevprca_to_int( $id ); }, (array) ( $meta['manual_product_ids'] ?? array() ) )
			)
		)
	);

	if ( ! empty( $meta['manual_products_enabled'] ) && ! empty( $manual_product_ids ) ) {
		$args['include'] = $manual_product_ids;
		$args['orderby'] = 'include';

		$args = apply_filters( 'cleverspr_carousel/query_args', $args, $carousel_id, $meta );
		$args = apply_filters( 'cleverspr_carousel_query_args', $args, $carousel_id, $meta );

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
	$strategy    = apply_filters( 'cleverspr_carousel/include_strategy', 'intersection', $carousel_id, $meta );
	$strategy    = ( 'union' === $strategy ) ? 'union' : 'intersection';

	if ( ! empty( $meta['on_sale'] ) ) {
		$include_ids = clevprca_merge_product_ids(
			$include_ids,
			(array) wc_get_product_ids_on_sale(),
			$strategy
		);
	}

	if ( ! empty( $meta['on_featured'] ) ) {
		$include_ids = clevprca_merge_product_ids(
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

	$args = apply_filters( 'cleverspr_carousel/query_args', $args, $carousel_id, $meta );
	$args = apply_filters( 'cleverspr_carousel_query_args', $args, $carousel_id, $meta );

	return is_array( $args ) ? $args : array();
}

/**
 * Indica si un color de texto deja el contenido invisible.
 *
 * @param mixed $value Valor CSS guardado en los ajustes (puede venir vacio o null).
 * @return bool
 */
function clevprca_color_is_invisible( $value ): bool {
	if ( ! is_string( $value ) ) {
		return true;
	}

	$value = strtolower( trim( $value ) );
	if ( '' === $value || 'none' === $value ) {
		return true;
	}

	$invisible = array( 'transparent', 'rgba(0,0,0,0)', 'rgba(0, 0, 0, 0)', 'rgba(255,255,255,0)', 'rgba(255, 255, 255, 0)', '#00000000', '#ffffff00' );

	return in_array( $value, $invisible, true );
}

/**
 * Luminancia relativa de un color hex, segun WCAG 2.x.
 *
 * @param mixed $color Color en formato hex.
 * @return float|null Null cuando el valor no es un hex valido.
 */
function clevprca_relative_luminance( $color ) {
	$hex = is_string( $color ) ? sanitize_hex_color( $color ) : '';
	if ( ! $hex ) {
		return null;
	}

	$hex = ltrim( $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) ) {
		return null;
	}

	$to_linear = static function ( $channel ): float {
		$channel /= 255;
		return $channel <= 0.03928 ? $channel / 12.92 : pow( ( $channel + 0.055 ) / 1.055, 2.4 );
	};

	return 0.2126 * $to_linear( hexdec( substr( $hex, 0, 2 ) ) )
		+ 0.7152 * $to_linear( hexdec( substr( $hex, 2, 2 ) ) )
		+ 0.0722 * $to_linear( hexdec( substr( $hex, 4, 2 ) ) );
}

/**
 * Ratio de contraste entre dos colores hex (1 a 21), segun WCAG 2.x.
 *
 * @param mixed $foreground Color de texto.
 * @param mixed $background Color de fondo.
 * @return float|null Null cuando alguno de los dos no es un hex valido.
 */
function clevprca_contrast_ratio( $foreground, $background ) {
	$fg = clevprca_relative_luminance( $foreground );
	$bg = clevprca_relative_luminance( $background );

	if ( null === $fg || null === $bg ) {
		return null;
	}

	$lighter = max( $fg, $bg );
	$darker  = min( $fg, $bg );

	return ( $lighter + 0.05 ) / ( $darker + 0.05 );
}

/**
 * Devuelve el color de texto que mejor contrasta sobre el fondo indicado.
 *
 * Se prueban los candidatos en orden de preferencia de diseno (blanco y casi
 * negro) y se devuelve el primero que alcanza 4.5:1. Si ninguno llega (grises
 * medios), se suma el negro puro, que siempre alcanza el umbral para cualquier
 * fondo, y si aun asi no lo logra se devuelve el de mayor contraste.
 *
 * @param mixed $background Color de fondo en formato hex.
 * @return string
 */
function clevprca_contrast_text_color( $background ): string {
	$candidates = array( '#FFFFFF', '#1A1A1A', '#000000' );

	$best       = '#FFFFFF';
	$best_ratio = -1.0;

	foreach ( $candidates as $candidate ) {
		$ratio = clevprca_contrast_ratio( $candidate, $background );

		if ( null === $ratio ) {
			return '#FFFFFF';
		}

		if ( $ratio >= 4.5 ) {
			return $candidate;
		}

		if ( $ratio > $best_ratio ) {
			$best_ratio = $ratio;
			$best       = $candidate;
		}
	}

	return $best;
}

/**
 * Corrige pares texto/fondo ilegibles: un texto transparente, vacio, igual al
 * fondo o con contraste insuficiente (bajo 4.5:1, WCAG AA para texto normal)
 * pasa al color de texto que mejor contraste sobre ese fondo.
 *
 * Cuando el fondo no esta configurado se evalua contra el valor por defecto
 * que trae assets/carousel.css (--clevers-button-background: #2563eb y
 * --clevers-bubble-background: #000000), porque ese es el color que el
 * visitante ve realmente. Un fondo explicitamente transparente se deja intacto:
 * ahi no hay un color propio contra el cual medir.
 *
 * @param array<string, mixed> $settings Ajustes del carrusel.
 * @return array<string, mixed>
 */
function clevprca_ensure_text_contrast( array $settings ): array {
	$pairs = array(
		'button_text' => array(
			'background_key'     => 'button_background',
			'css_default_bg'     => '#2563eb',
			'css_default_text'   => '#FFFFFF',
			'hover_key'          => 'color_secondary',
			'css_default_hover'  => '#1e40af',
		),
		'bubble_text' => array(
			'background_key'     => 'bubble_background',
			'css_default_bg'     => '#000000',
			'css_default_text'   => '#FFFFFF',
			'hover_key'          => '',
			'css_default_hover'  => '',
		),
	);

	foreach ( $pairs as $text_key => $pair ) {
		$background = isset( $settings[ $pair['background_key'] ] ) && is_string( $settings[ $pair['background_key'] ] )
			? trim( $settings[ $pair['background_key'] ] )
			: '';

		// Un fondo explicitamente transparente se deja intacto: no hay color
		// propio contra el cual medir.
		if ( '' !== $background && clevprca_color_is_invisible( $background ) ) {
			continue;
		}

		if ( '' === $background ) {
			$background = $pair['css_default_bg'];
		}

		$text        = isset( $settings[ $text_key ] ) && is_string( $settings[ $text_key ] )
			? trim( $settings[ $text_key ] )
			: '';
		$is_override = '' !== $text;

		// Sin color configurado el visitante ve el default del CSS; se evalua
		// ese valor para no dejar texto ilegible sobre un fondo personalizado.
		if ( ! $is_override ) {
			$text = $pair['css_default_text'];
		}

		if ( $is_override && clevprca_color_is_invisible( $text ) ) {
			$settings[ $text_key ] = clevprca_contrast_text_color( $background );
			continue;
		}

		$ratio = clevprca_contrast_ratio( $text, $background );
		if ( null === $ratio || $ratio < 4.5 ) {
			$text                  = clevprca_contrast_text_color( $background );
			$settings[ $text_key ] = $text;
		}

		// En hover el fondo pasa a --clevers-secondary. Si el color de texto
		// elegido no se lee ahi, se define un color propio para ese estado.
		if ( '' === (string) $pair['hover_key'] ) {
			continue;
		}

		$hover = isset( $settings[ $pair['hover_key'] ] ) && is_string( $settings[ $pair['hover_key'] ] )
			? trim( $settings[ $pair['hover_key'] ] )
			: '';

		if ( '' === $hover ) {
			$hover = $pair['css_default_hover'];
		}

		if ( '' === $hover || clevprca_color_is_invisible( $hover ) ) {
			continue;
		}

		if ( '' === $text ) {
			$text = $pair['css_default_text'];
		}

		$hover_ratio = clevprca_contrast_ratio( $text, $hover );
		if ( null === $hover_ratio || $hover_ratio < 4.5 ) {
			$settings['button_text_hover'] = clevprca_contrast_text_color( $hover );
		}
	}

	return $settings;
}

/**
 * Obtiene los ajustes del carrusel con valores por defecto.
 *
 * @param int $carousel_id ID del carrusel.
 * @return array<string, mixed>
 */
function clevprca_get_settings( $carousel_id ) {
	$meta     = clevprca_get_carousel_meta( $carousel_id );
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
	$settings = clevprca_ensure_text_contrast( $settings );

	return apply_filters( 'cleverspr_carousel/settings', $settings, $carousel_id );
}

/**
 * Obtiene métricas de procesamiento del carrusel.
 *
 * @param int $carousel_id ID del carrusel.
 * @return array<string, int|float|string>
 */
function clevprca_get_queue_metrics( int $carousel_id ): array {
	$defaults = array(
		'pending'                 => 0,
		'processed'               => 0,
		'failed'                  => 0,
		'avg_time_ms_per_product' => 0.0,
		'last_error'              => '',
		'last_run_at'             => '',
	);

	$stored = get_post_meta( $carousel_id, '_clevprca_queue_metrics', true );
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
function clevprca_update_queue_metrics(
	int $carousel_id,
	int $pending,
	int $processed,
	int $failed,
	float $avg_time_ms_per_product,
	string $last_error = ''
): void {
	update_post_meta(
		$carousel_id,
		'_clevprca_queue_metrics',
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
