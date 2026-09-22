<?php

use PHPUnit\Framework\TestCase;

final class CacheInvalidationTest extends TestCase {
	protected function setUp(): void {
		reset_mock_state();
	}

	public function test_invalidate_cache_increments_global_bump_from_default_value(): void {
		$renderer = new CLEVPRCA_Render();
		$renderer->invalidate_cache();

		$this->assertSame( 1, $GLOBALS['mock_state']['options']['clevprca_global_cache_bump'] );
	}

	public function test_invalidate_cache_increments_existing_global_bump(): void {
		$GLOBALS['mock_state']['options']['clevprca_global_cache_bump'] = 7;

		$renderer = new CLEVPRCA_Render();
		$renderer->invalidate_cache();

		$this->assertSame( 8, $GLOBALS['mock_state']['options']['clevprca_global_cache_bump'] );
	}

	public function test_invalidate_cache_on_terms_change_ignores_irrelevant_taxonomies_and_post_types(): void {
		$product = new WP_Post();
		$product->post_type = 'product';
		$GLOBALS['mock_state']['posts'][51] = $product;

		$page = new WP_Post();
		$page->post_type = 'page';
		$GLOBALS['mock_state']['posts'][52] = $page;

		$renderer = new CLEVPRCA_Render();

		$renderer->invalidate_cache_on_terms_change( 51, array(), array(), 'category', false, array() );
		$this->assertSame( 0, (int) get_option( 'clevprca_global_cache_bump', 0 ) );

		$renderer->invalidate_cache_on_terms_change( 52, array(), array(), 'product_cat', false, array() );
		$this->assertSame( 0, (int) get_option( 'clevprca_global_cache_bump', 0 ) );
	}

	public function test_invalidate_cache_on_terms_change_increments_for_product_taxonomies(): void {
		$product = new WP_Post();
		$product->post_type = 'product';
		$GLOBALS['mock_state']['posts'][53] = $product;

		$variation = new WP_Post();
		$variation->post_type = 'product_variation';
		$GLOBALS['mock_state']['posts'][54] = $variation;

		$renderer = new CLEVPRCA_Render();
		$renderer->invalidate_cache_on_terms_change( 53, array(), array(), 'product_cat', false, array() );
		$renderer->invalidate_cache_on_terms_change( 54, array(), array(), 'product_tag', false, array() );

		$this->assertSame( 2, $GLOBALS['mock_state']['options']['clevprca_global_cache_bump'] );
	}

	public function test_invalidate_cache_on_product_meta_change_only_bumps_for_watched_keys_on_products(): void {
		$product = new WP_Post();
		$product->post_type = 'product';
		$GLOBALS['mock_state']['posts'][55] = $product;

		$renderer = new CLEVPRCA_Render();

		$renderer->invalidate_cache_on_product_meta_change( 1, 55, '_not_watched', '' );
		$this->assertSame( 0, (int) get_option( 'clevprca_global_cache_bump', 0 ) );

		$renderer->invalidate_cache_on_product_meta_change( 1, 55, '_stock_status', 'instock' );
		$this->assertSame( 1, (int) get_option( 'clevprca_global_cache_bump', 0 ) );
	}

	public function test_render_carousel_uses_global_bump_in_cache_key_after_invalidation(): void {
		$post = new WP_Post();
		$post->post_type = CLEVPRCA_SLUG;
		$GLOBALS['mock_state']['posts'][56] = $post;
		$GLOBALS['mock_state']['post_meta'][56] = array();
		$GLOBALS['mock_state']['locate_template_value'] = dirname( __DIR__ ) . '/fixtures/simple-template.php';

		$renderer = new CLEVPRCA_Render();
		$renderer->invalidate_cache();
		$renderer->render_carousel( 56 );

		$stored_keys = array_keys( $GLOBALS['mock_state']['set_transients'] );
		$this->assertCount( 1, $stored_keys );
		$this->assertStringContainsString( '_g1_', $stored_keys[0] );
	}
}
