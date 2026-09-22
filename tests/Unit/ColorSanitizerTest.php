<?php

use PHPUnit\Framework\TestCase;

final class ColorSanitizerTest extends TestCase {
	public function test_accepts_hex_and_transparent_values(): void {
		$this->assertSame( '#aabbcc', clevprca_sanitize_css_value( '#AABBCC' ) );
		$this->assertSame( 'transparent', clevprca_sanitize_css_value( 'transparent' ) );
	}

	public function test_rejects_non_color_values(): void {
		$this->assertSame( '', clevprca_sanitize_css_value( 'rgb(0,0,0)' ) );
		$this->assertSame( '', clevprca_sanitize_css_value( 'javascript:alert(1)' ) );
	}
}
