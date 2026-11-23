<?php

use PHPUnit\Framework\TestCase;

final class TextContrastTest extends TestCase {
	public function test_invisible_values_are_detected(): void {
		$this->assertTrue( clevprca_color_is_invisible( 'transparent' ) );
		$this->assertTrue( clevprca_color_is_invisible( 'TRANSPARENT' ) );
		$this->assertTrue( clevprca_color_is_invisible( '' ) );
		$this->assertTrue( clevprca_color_is_invisible( 'rgba(0,0,0,0)' ) );
		$this->assertTrue( clevprca_color_is_invisible( null ) );
		$this->assertFalse( clevprca_color_is_invisible( '#FFFFFF' ) );
	}

	public function test_contrast_ratio_matches_wcag(): void {
		$this->assertEqualsWithDelta( 21.0, clevprca_contrast_ratio( '#000000', '#FFFFFF' ), 0.01 );
		$this->assertEqualsWithDelta( 1.0, clevprca_contrast_ratio( '#FFFFFF', '#FFFFFF' ), 0.01 );
		$this->assertEqualsWithDelta( 3.72, clevprca_contrast_ratio( '#000000', '#147807' ), 0.02 );
		$this->assertNull( clevprca_contrast_ratio( 'rgb(0,0,0)', '#FFFFFF' ) );
	}

	public function test_contrast_color_returns_white_on_dark_backgrounds(): void {
		$this->assertSame( '#FFFFFF', clevprca_contrast_text_color( '#2D5016' ) );
		$this->assertSame( '#FFFFFF', clevprca_contrast_text_color( '#147807' ) );
		$this->assertSame( '#FFFFFF', clevprca_contrast_text_color( '#000000' ) );
	}

	public function test_contrast_color_returns_dark_on_light_backgrounds(): void {
		$this->assertSame( '#1A1A1A', clevprca_contrast_text_color( '#F4A300' ) );
		$this->assertSame( '#1A1A1A', clevprca_contrast_text_color( '#FFFFFF' ) );
	}

	public function test_contrast_color_prefers_white_when_it_contrasts_more(): void {
		// Sobre #767676 el blanco da 4.54:1 y el casi negro 3.83:1.
		$this->assertSame( '#FFFFFF', clevprca_contrast_text_color( '#767676' ) );
	}

	public function test_contrast_color_uses_pure_black_on_mid_gray(): void {
		// Sobre #7C7C7C el blanco da 4.17:1 y el casi negro 4.17:1; solo el negro puro pasa 4.5.
		$this->assertSame( '#000000', clevprca_contrast_text_color( '#7C7C7C' ) );
	}

	public function test_every_background_gets_at_least_aa_contrast(): void {
		foreach ( array( '#000000', '#2D5016', '#767676', '#7C7C7C', '#F4A300', '#FFFFFF' ) as $background ) {
			$color = clevprca_contrast_text_color( $background );
			$this->assertGreaterThanOrEqual( 4.5, clevprca_contrast_ratio( $color, $background ), 'Fondo ' . $background );
		}
	}

	public function test_contrast_color_falls_back_to_white_without_hex(): void {
		$this->assertSame( '#FFFFFF', clevprca_contrast_text_color( 'rgb(0,0,0)' ) );
	}

	public function test_transparent_button_text_is_replaced(): void {
		$settings = clevprca_ensure_text_contrast(
			array(
				'button_background' => '#2D5016',
				'button_text'       => 'transparent',
			)
		);

		$this->assertSame( '#FFFFFF', $settings['button_text'] );
	}

	public function test_bubble_text_on_light_background_becomes_dark(): void {
		$settings = clevprca_ensure_text_contrast(
			array(
				'bubble_background' => '#F4A300',
				'bubble_text'       => 'transparent',
			)
		);

		$this->assertSame( '#1A1A1A', $settings['bubble_text'] );
	}

	public function test_low_contrast_text_is_replaced(): void {
		$settings = clevprca_ensure_text_contrast(
			array(
				'bubble_background' => '#147807',
				'bubble_text'       => '#000000',
			)
		);

		$this->assertSame( '#FFFFFF', $settings['bubble_text'] );
	}

	public function test_text_equal_to_background_is_replaced(): void {
		$settings = clevprca_ensure_text_contrast(
			array(
				'button_background' => '#2D5016',
				'button_text'       => '#2d5016',
			)
		);

		$this->assertSame( '#FFFFFF', $settings['button_text'] );
	}

	public function test_readable_text_is_left_untouched(): void {
		$settings = clevprca_ensure_text_contrast(
			array(
				'button_background' => '#2D5016',
				'button_text'       => '#F5F0EB',
				'bubble_background' => '#F4A300',
				'bubble_text'       => '#1A1A1A',
			)
		);

		$this->assertSame( '#F5F0EB', $settings['button_text'] );
		$this->assertSame( '#1A1A1A', $settings['bubble_text'] );
	}

	public function test_borderline_text_above_threshold_is_kept(): void {
		// #FFFFFF sobre #767676 da 4.54:1, sobre el minimo de 4.5.
		$settings = clevprca_ensure_text_contrast(
			array(
				'button_background' => '#767676',
				'button_text'       => '#FFFFFF',
			)
		);

		$this->assertSame( '#FFFFFF', $settings['button_text'] );
	}

	public function test_missing_background_uses_css_default(): void {
		// Sin fondo configurado el CSS usa #2563eb para el boton.
		$settings = clevprca_ensure_text_contrast( array( 'button_text' => 'transparent' ) );

		$this->assertSame( '#FFFFFF', $settings['button_text'] );
	}

	public function test_low_contrast_bubble_text_without_background_is_fixed(): void {
		// Sin fondo configurado el CSS usa #000000 para el badge: #111111 es ilegible.
		$settings = clevprca_ensure_text_contrast( array( 'bubble_text' => '#111111' ) );

		$this->assertSame( '#FFFFFF', $settings['bubble_text'] );
	}

	public function test_explicitly_transparent_background_is_left_alone(): void {
		$settings = clevprca_ensure_text_contrast(
			array(
				'button_background' => 'transparent',
				'button_text'       => 'transparent',
			)
		);

		$this->assertSame( 'transparent', $settings['button_text'] );
	}

	public function test_custom_background_without_text_gets_readable_default(): void {
		$settings = clevprca_ensure_text_contrast( array( 'button_background' => '#FFFFFF' ) );

		$this->assertSame( '#1A1A1A', $settings['button_text'] );
	}

	public function test_no_hover_override_needed_when_text_reads_on_secondary(): void {
		$settings = clevprca_ensure_text_contrast(
			array(
				'button_background' => '#2D5016',
				'color_secondary'   => '#1A1A1A',
			)
		);

		$this->assertArrayNotHasKey( 'button_text_hover', $settings );
	}

	public function test_hover_gets_own_color_when_text_fails_on_secondary(): void {
		$settings = clevprca_ensure_text_contrast(
			array(
				'button_background' => '#F4A300',
				'color_secondary'   => '#1A1A1A',
			)
		);

		$this->assertSame( '#1A1A1A', $settings['button_text'] );
		$this->assertSame( '#FFFFFF', $settings['button_text_hover'] );
	}

	public function test_settings_without_colors_are_not_touched(): void {
		$input = array(
			'preset'       => 2,
			'slidesToShow' => 4,
		);

		$this->assertSame( $input, clevprca_ensure_text_contrast( $input ) );
	}
}
