<?php
/**
 * Integration tests for the public hooks of the Clevers Product Carousel.
 *
 * The WordPress/WooCommerce runtime is mocked in tests/bootstrap.php; these
 * tests focus on the contract exposed to third-party code via filters and
 * actions, not on full system behavior.
 */

use PHPUnit\Framework\TestCase;

final class HooksTest extends TestCase {
	protected function setUp(): void {
		reset_mock_state();
	}

	public function test_query_args_filter_receives_carousel_id_and_meta(): void {
		$GLOBALS['mock_state']['post_meta'][101] = array(
			'orderby' => 'date',
			'limit'   => 8,
		);
		$post = new WP_Post();
		$post->post_type = CLEVPRCA_SLUG;
		$GLOBALS['mock_state']['posts'][101] = $post;

		$captured = null;
		add_filter(
			'cleverspr_carousel_query_args',
			function ( $args, $carousel_id, $meta ) use ( &$captured ) {
				$captured = array(
					'args'        => $args,
					'carousel_id' => $carousel_id,
					'meta'        => $meta,
				);
				$args['limit'] = 4;
				return $args;
			},
			10,
			3
		);

		$args = clevprca_build_query_args( 101 );

		$this->assertIsArray( $captured );
		$this->assertSame( 101, $captured['carousel_id'] );
		$this->assertIsArray( $captured['meta'] );
		$this->assertSame( 4, $args['limit'] );
	}

	public function test_css_vars_filter_can_inject_custom_variables(): void {
		$settings = array(
			'color_primary' => '#ff0000',
		);
		$vars_map = array( 'color_primary' => '--clevers-primary' );

		add_filter(
			'cleverspr_carousel_css_vars',
			function ( $vars, $carousel_id, $settings_passed, $vars_map_passed ) {
				$vars[] = '--clevers-custom: 12px;';
				return $vars;
			},
			10,
			4
		);

		$vars = array( '--clevers-primary:#ff0000;' );
		$vars = apply_filters( 'cleverspr_carousel_css_vars', $vars, 7, $settings, $vars_map );

		$this->assertContains( '--clevers-primary:#ff0000;', $vars );
		$this->assertContains( '--clevers-custom: 12px;', $vars );
	}

	public function test_template_path_filter_receives_and_can_modify_rel_path(): void {
		$captured = null;
		add_filter(
			'cleverspr_carousel_template_path',
			function ( $rel_path ) use ( &$captured ) {
				$captured = $rel_path;
				return 'custom/' . $rel_path;
			},
			20
		);

		clevprca_locate_template( 'cards/card-1.php' );

		$this->assertSame( 'cards/card-1.php', $captured );
	}

	public function test_cache_ttl_filter_overrides_default(): void {
		$post = new WP_Post();
		$post->post_type = CLEVPRCA_SLUG;
		$GLOBALS['mock_state']['posts'][201] = $post;
		$GLOBALS['mock_state']['post_meta'][201] = array();
		$GLOBALS['mock_state']['locate_template_value'] = dirname( __DIR__ ) . '/fixtures/simple-template.php';

		add_filter(
			'cleverspr_carousel/cache_ttl',
			function ( $ttl ) {
				return 30 * MINUTE_IN_SECONDS;
			},
			10,
			4
		);

		$renderer = new CLEVPRCA_Render();
		$renderer->render_carousel( 201 );

		$this->assertNotEmpty( $GLOBALS['mock_state']['set_transients'] );
		$set = array_values( $GLOBALS['mock_state']['set_transients'] )[0];
		$this->assertSame( 30 * MINUTE_IN_SECONDS, $set['ttl'] );
	}

	public function test_before_and_after_render_actions_receive_products(): void {
		$post = new WP_Post();
		$post->post_type = CLEVPRCA_SLUG;
		$GLOBALS['mock_state']['posts'][301] = $post;
		$GLOBALS['mock_state']['post_meta'][301] = array();
		$GLOBALS['mock_state']['locate_template_value'] = dirname( __DIR__ ) . '/fixtures/simple-template.php';

		WC_Product_Query::$products = array( new WC_Product(), new WC_Product() );

		$before_called = false;
		$after_called  = false;

		add_action(
			'cleverspr_carousel_before_render',
			function ( $carousel_id, $settings, $products ) use ( &$before_called ) {
				$before_called = true;
				$this->assertSame( 301, $carousel_id );
				$this->assertIsArray( $settings );
				$this->assertIsArray( $products );
				$this->assertCount( 2, $products );
			},
			10,
			3
		);

		add_action(
			'cleverspr_carousel_after_render',
			function () use ( &$after_called ) {
				$after_called = true;
			},
			10,
			3
		);

		$renderer = new CLEVPRCA_Render();
		$renderer->render_carousel( 301 );

		$this->assertTrue( $before_called, 'cleverspr_carousel_before_render was not fired' );
		$this->assertTrue( $after_called, 'cleverspr_carousel_after_render was not fired' );

		WC_Product_Query::$products = array();
	}

	public function test_rendered_html_filter_can_post_process_output(): void {
		$post = new WP_Post();
		$post->post_type = CLEVPRCA_SLUG;
		$GLOBALS['mock_state']['posts'][401] = $post;
		$GLOBALS['mock_state']['post_meta'][401] = array();
		$GLOBALS['mock_state']['locate_template_value'] = dirname( __DIR__ ) . '/fixtures/simple-template.php';

		add_filter(
			'cleverspr_carousel/rendered_html',
			function ( $html ) {
				return $html . '<!-- post-processed -->';
			},
			10,
			4
		);

		$renderer = new CLEVPRCA_Render();
		$html     = $renderer->render_carousel( 401 );

		$this->assertStringEndsWith( '<!-- post-processed -->', $html );
	}
}
