<?php

use PHPUnit\Framework\TestCase;

final class ShortcodeTest extends TestCase {
	protected function setUp(): void {
		reset_mock_state();
	}

	public function test_shortcode_renders_expected_carousel_id(): void {
		$post = new WP_Post();
		$post->post_type = CLEVPRCA_SLUG;
		$GLOBALS['mock_state']['posts'][55] = $post;
		$GLOBALS['mock_state']['locate_template_value'] = dirname( __DIR__ ) . '/fixtures/simple-template.php';

		$renderer = new CLEVPRCA_Render();
		$html = $renderer->shortcode( array( 'id' => 55 ) );

		$this->assertStringContainsString( 'Rendered from fixture', $html );
	}

	public function test_locate_template_prefers_theme_override(): void {
		$GLOBALS['mock_state']['locate_template_value'] = '/theme/clevers-product-carousel/cards/card-1.php';

		$resolved = clevprca_locate_template( 'cards/card-1.php' );

		$this->assertSame( '/theme/clevers-product-carousel/cards/card-1.php', $resolved );
	}
}
