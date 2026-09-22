<?php

use PHPUnit\Framework\TestCase;

final class RenderTest extends TestCase {
	protected function setUp(): void {
		reset_mock_state();
	}

	public function test_render_carousel_uses_cache_hit_without_querying_products(): void {
		$post = new WP_Post();
		$post->post_type = CLEVPRCA_SLUG;
		$GLOBALS['mock_state']['posts'][21] = $post;
		$GLOBALS['mock_state']['post_meta'][21] = array();

		$args = clevprca_build_query_args( 21 );
		$settings = clevprca_get_settings( 21 );
		$cache_key = 'cleverspr_carousel_21_v0_g0_' . md5( wp_json_encode( $args ) . '|' . wp_json_encode( $settings ) );
		$GLOBALS['mock_state']['transients'][ $cache_key ] = '<div>cached</div>';

		$renderer = new CLEVPRCA_Render();
		$html = $renderer->render_carousel( 21 );

		$this->assertStringContainsString( 'cached', $html );
		$this->assertSame( 0, WC_Product_Query::$construct_count );
	}

	public function test_render_carousel_cache_miss_queries_and_stores_transient(): void {
		$post = new WP_Post();
		$post->post_type = CLEVPRCA_SLUG;
		$GLOBALS['mock_state']['posts'][22] = $post;
		$GLOBALS['mock_state']['post_meta'][22] = array();
		$GLOBALS['mock_state']['locate_template_value'] = dirname( __DIR__ ) . '/fixtures/simple-template.php';

		$renderer = new CLEVPRCA_Render();
		$html = $renderer->render_carousel( 22 );

		$this->assertStringContainsString( 'Rendered from fixture', $html );
		$this->assertSame( 1, WC_Product_Query::$construct_count );
		$this->assertNotEmpty( $GLOBALS['mock_state']['set_transients'] );
	}

	public function test_render_block_matches_regression_snapshot(): void {
		$post = new WP_Post();
		$post->post_type = CLEVPRCA_SLUG;
		$GLOBALS['mock_state']['posts'][31] = $post;
		$GLOBALS['mock_state']['post_meta'][31] = array();
		$GLOBALS['mock_state']['locate_template_value'] = dirname( __DIR__ ) . '/fixtures/render-regression-template.php';

		$renderer = new CLEVPRCA_Render();
		$html = $renderer->render_block( array( 'carouselId' => 31 ) );

		$this->assertMatchesHtmlSnapshot( 'render-block-default.html', $html );
	}

	public function test_render_block_brizy_preview_matches_regression_snapshot(): void {
		$_REQUEST['action'] = 'brizy_in-front-editor';

		$post = new WP_Post();
		$post->post_type = CLEVPRCA_SLUG;
		$GLOBALS['mock_state']['posts'][32] = $post;
		$GLOBALS['mock_state']['post_meta'][32] = array();
		$GLOBALS['mock_state']['locate_template_value'] = dirname( __DIR__ ) . '/fixtures/render-regression-template.php';

		$renderer = new CLEVPRCA_Render();
		$html = $renderer->render_block( array( 'carouselId' => 32 ) );

		$this->assertMatchesHtmlSnapshot( 'render-block-brizy-preview.html', $html );
	}

	private function assertMatchesHtmlSnapshot( string $snapshot_name, string $actual_html ): void {
		$snapshot_path = dirname( __DIR__ ) . '/fixtures/snapshots/' . $snapshot_name;
		$this->assertFileExists( $snapshot_path, 'Missing snapshot: ' . $snapshot_name );

		$expected_html = (string) file_get_contents( $snapshot_path );
		$this->assertSame(
			$this->normalizeHtml( $expected_html ),
			$this->normalizeHtml( $actual_html ),
			'Snapshot mismatch for ' . $snapshot_name
		);
	}

	private function normalizeHtml( string $html ): string {
		$html = str_replace( array( "\r\n", "\r" ), "\n", trim( $html ) );
		$html = preg_replace( '/>\s+</', '><', $html );

		return (string) $html;
	}
}
