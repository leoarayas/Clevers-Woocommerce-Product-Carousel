<?php

use PHPUnit\Framework\TestCase;

final class PluginBootstrapSmokeTest extends TestCase {
	protected function setUp(): void {
		reset_mock_state();
	}

	public function test_plugin_bootstrap_registers_expected_hooks(): void {
		require_once dirname( __DIR__, 2 ) . '/clevers-product-carousel.php';

		$this->assertArrayHasKey( 'init', $GLOBALS['mock_state']['actions'] );
		$this->assertArrayHasKey( 'after_setup_theme', $GLOBALS['mock_state']['actions'] );
		$this->assertArrayHasKey( 'wp_enqueue_scripts', $GLOBALS['mock_state']['actions'] );
		$this->assertArrayHasKey( 'cleverspr_carousel', $GLOBALS['mock_state']['shortcodes'] );
	}
}
