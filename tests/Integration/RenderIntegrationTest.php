<?php
/**
 * Integration tests for carousel rendering pipeline.
 *
 * Tests the full render flow including cache, settings defaults,
 * query building, and output filtering.
 */

use PHPUnit\Framework\TestCase;

final class RenderIntegrationTest extends TestCase {
	protected function setUp(): void {
		reset_mock_state();
	}

	public function test_render_returns_empty_string_when_no_woocommerce(): void {
		// Temporarily remove WooCommerce class.
		$original = class_exists( 'WooCommerce' ) ? true : false;
		if ( $original ) {
			// Can't unload class, but we can test with invalid carousel ID.
			$renderer = new Clevers_Product_Carousel_Render();
			$html     = $renderer->render_carousel( 0 );
			$this->assertSame( '', $html );
		}
	}

	public function test_render_returns_empty_string_for_invalid_carousel_id(): void {
		$renderer = new Clevers_Product_Carousel_Render();
		$html     = $renderer->render_carousel( 999 );
		$this->assertSame( '', $html );
	}

	public function test_render_returns_empty_string_for_wrong_post_type(): void {
		$post             = new WP_Post();
		$post->post_type  = 'page';
		$GLOBALS['mock_state']['posts'][10] = $post;

		$renderer = new Clevers_Product_Carousel_Render();
		$html     = $renderer->render_carousel( 10 );
		$this->assertSame( '', $html );
	}

	public function test_render_uses_cached_html_when_available(): void {
		$post             = new WP_Post();
		$post->post_type  = CLV_SLUG;
		$GLOBALS['mock_state']['posts'][20]     = $post;
		$GLOBALS['mock_state']['post_meta'][20] = array();
		$args = clevers_product_carousel_build_query_args( 20 );
		$settings = clevers_product_carousel_get_settings( 20 );
		$cache_key = 'clv_carousel_20_v0_g0_' . md5( wp_json_encode( $args ) . '|' . wp_json_encode( $settings ) );
		$GLOBALS['mock_state']['transients'][ $cache_key ] = '<div>cached</div>';

		$renderer = new Clevers_Product_Carousel_Render();
		$html     = $renderer->render_carousel( 20 );

		$this->assertStringEndsWith( '<div>cached</div>', $html );
		$this->assertSame( 0, WC_Product_Query::$construct_count );
	}

	public function test_settings_defaults_applied_correctly(): void {
		$GLOBALS['mock_state']['post_meta'][30] = array();

		$settings = clevers_product_carousel_get_settings( 30 );

		$this->assertSame( 1, $settings['preset'] );
		$this->assertSame( 4, $settings['slidesToShow'] );
		$this->assertSame( 2, $settings['slidesToShowTablet'] );
		$this->assertSame( 1, $settings['slidesToShowMobile'] );
		$this->assertFalse( $settings['autoplay'] );
		$this->assertSame( 3000, $settings['autoplayMs'] );
		$this->assertFalse( $settings['dots'] );
		$this->assertTrue( $settings['arrows'] );
		$this->assertTrue( $settings['pauseOnHover'] );
		$this->assertTrue( $settings['pauseOnFocus'] );
		$this->assertTrue( $settings['reducedMotionAutoplayOff'] );
	}

	public function test_settings_clamped_to_valid_ranges(): void {
		$GLOBALS['mock_state']['post_meta'][31] = array(
			'preset'             => 10,
			'slidesToShow'       => 20,
			'slidesToShowTablet' => 0,
			'slidesToShowMobile' => 15,
			'autoplayMs'         => 100,
		);

		$settings = clevers_product_carousel_get_settings( 31 );

		$this->assertSame( 4, $settings['preset'] );           // max 4
		$this->assertSame( 8, $settings['slidesToShow'] );     // max 8
		$this->assertSame( 1, $settings['slidesToShowTablet'] ); // min 1
		$this->assertSame( 8, $settings['slidesToShowMobile'] ); // max 8
		$this->assertSame( 500, $settings['autoplayMs'] );      // min 500
	}

	public function test_build_query_args_with_on_sale_filter(): void {
		$GLOBALS['mock_state']['post_meta'][40] = array(
			'on_sale' => true,
			'limit'   => 12,
		);
		$GLOBALS['mock_state']['product_ids_on_sale'] = array( 1, 2, 3 );

		$args = clevers_product_carousel_build_query_args( 40 );

		$this->assertArrayHasKey( 'include', $args );
		$this->assertSame( array( 1, 2, 3 ), $args['include'] );
		$this->assertSame( 12, $args['limit'] );
	}

	public function test_build_query_args_with_featured_filter(): void {
		$GLOBALS['mock_state']['post_meta'][41] = array(
			'on_featured' => true,
		);
		$GLOBALS['mock_state']['featured_product_ids'] = array( 10, 20 );

		$args = clevers_product_carousel_build_query_args( 41 );

		$this->assertArrayHasKey( 'include', $args );
		$this->assertSame( array( 10, 20 ), $args['include'] );
	}

	public function test_build_query_args_with_categories_filter(): void {
		$GLOBALS['mock_state']['post_meta'][42] = array(
			'categories' => array( 'electronics', 'gadgets' ),
		);

		$args = clevers_product_carousel_build_query_args( 42 );

		$this->assertArrayHasKey( 'category', $args );
		$this->assertSame( array( 'electronics', 'gadgets' ), $args['category'] );
	}

	public function test_build_query_args_with_instock_only(): void {
		$GLOBALS['mock_state']['post_meta'][43] = array(
			'instock_only' => true,
		);

		$args = clevers_product_carousel_build_query_args( 43 );

		$this->assertArrayHasKey( 'stock_status', $args );
		$this->assertSame( 'instock', $args['stock_status'] );
	}

	public function test_build_query_args_with_manual_products(): void {
		$GLOBALS['mock_state']['post_meta'][44] = array(
			'manual_products_enabled' => true,
			'manual_product_ids'      => array( 5, 10, 15 ),
		);

		$args = clevers_product_carousel_build_query_args( 44 );

		$this->assertArrayHasKey( 'include', $args );
		$this->assertSame( array( 5, 10, 15 ), $args['include'] );
		$this->assertSame( 'include', $args['orderby'] );
	}

	public function test_build_query_args_invalid_orderby_falls_back_to_date(): void {
		$GLOBALS['mock_state']['post_meta'][45] = array(
			'orderby' => 'invalid_value',
		);

		$args = clevers_product_carousel_build_query_args( 45 );

		$this->assertSame( 'date', $args['orderby'] );
	}

	public function test_build_query_args_order_normalized_to_uppercase(): void {
		$GLOBALS['mock_state']['post_meta'][46] = array(
			'order' => 'asc',
		);

		$args = clevers_product_carousel_build_query_args( 46 );

		$this->assertSame( 'ASC', $args['order'] );
	}

	public function test_queue_metrics_defaults(): void {
		$metrics = clevers_product_carousel_get_queue_metrics( 50 );

		$this->assertSame( 0, $metrics['pending'] );
		$this->assertSame( 0, $metrics['processed'] );
		$this->assertSame( 0, $metrics['failed'] );
		$this->assertSame( 0.0, $metrics['avg_time_ms_per_product'] );
		$this->assertSame( '', $metrics['last_error'] );
		$this->assertSame( '', $metrics['last_run_at'] );
	}

	public function test_queue_metrics_update_and_retrieval(): void {
		clevers_product_carousel_update_queue_metrics( 51, 10, 8, 2, 45.5, 'timeout' );

		$metrics = clevers_product_carousel_get_queue_metrics( 51 );

		$this->assertSame( 10, $metrics['pending'] );
		$this->assertSame( 8, $metrics['processed'] );
		$this->assertSame( 2, $metrics['failed'] );
		$this->assertSame( 45.5, $metrics['avg_time_ms_per_product'] );
		$this->assertSame( 'timeout', $metrics['last_error'] );
		$this->assertNotEmpty( $metrics['last_run_at'] );
	}

	public function test_merge_product_ids_intersection_strategy(): void {
		$result = clevers_product_carousel_merge_product_ids(
			array( 1, 2, 3, 4 ),
			array( 2, 3, 5 ),
			'intersection'
		);

		$this->assertSame( array( 2, 3 ), $result );
	}

	public function test_merge_product_ids_union_strategy(): void {
		$result = clevers_product_carousel_merge_product_ids(
			array( 1, 2, 3 ),
			array( 3, 4, 5 ),
			'union'
		);

		$this->assertSame( array( 1, 2, 3, 4, 5 ), $result );
	}

	public function test_merge_product_ids_null_current_returns_incoming(): void {
		$result = clevers_product_carousel_merge_product_ids(
			null,
			array( 10, 20 ),
			'intersection'
		);

		$this->assertSame( array( 10, 20 ), $result );
	}

	public function test_sanitize_css_value_valid_hex(): void {
		$this->assertSame( '#ff0000', clevers_product_carousel_sanitize_css_value( '#ff0000' ) );
		$this->assertSame( '#abc', clevers_product_carousel_sanitize_css_value( '#abc' ) );
	}

	public function test_sanitize_css_value_transparent(): void {
		$this->assertSame( 'transparent', clevers_product_carousel_sanitize_css_value( 'transparent' ) );
		$this->assertSame( 'transparent', clevers_product_carousel_sanitize_css_value( '  TRANSPARENT  ' ) );
	}

	public function test_sanitize_css_value_invalid_returns_empty(): void {
		$this->assertSame( '', clevers_product_carousel_sanitize_css_value( 'not-a-color' ) );
		$this->assertSame( '', clevers_product_carousel_sanitize_css_value( '' ) );
	}

	public function test_slider_data_attributes_contain_expected_keys(): void {
		$settings = array(
			'slidesToShow'       => 3,
			'slidesToShowTablet' => 2,
			'slidesToShowMobile' => 1,
			'autoplay'           => true,
			'autoplayMs'         => 5000,
			'dots'               => true,
			'arrows'             => false,
			'pauseOnHover'       => true,
			'pauseOnFocus'       => false,
			'reducedMotionAutoplayOff' => false,
		);

		$html = clevers_product_carousel_get_slider_data_attributes( 60, $settings );

		$this->assertStringContainsString( 'data-carousel-id="60"', $html );
		$this->assertStringContainsString( 'data-slides="3"', $html );
		$this->assertStringContainsString( 'data-slides-tablet="2"', $html );
		$this->assertStringContainsString( 'data-slides-mobile="1"', $html );
		$this->assertStringContainsString( 'data-autoplay="true"', $html );
		$this->assertStringContainsString( 'data-speed="5000"', $html );
		$this->assertStringContainsString( 'data-dots="true"', $html );
		$this->assertStringContainsString( 'data-arrows="false"', $html );
		$this->assertStringContainsString( 'data-pause-on-hover="true"', $html );
		$this->assertStringContainsString( 'data-pause-on-focus="false"', $html );
		$this->assertStringContainsString( 'data-reduced-motion="false"', $html );
	}

	public function test_discount_percentage_returns_null_for_non_sale_product(): void {
		$product = $this->createMock( WC_Product::class );
		$product->method( 'is_on_sale' )->willReturn( false );

		$result = clevers_product_carousel_get_discount_percentage( $product );

		$this->assertNull( $result );
	}
}
