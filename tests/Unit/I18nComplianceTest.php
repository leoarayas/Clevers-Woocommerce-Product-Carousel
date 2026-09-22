<?php

use PHPUnit\Framework\TestCase;

final class I18nComplianceTest extends TestCase {
	private const TEXT_DOMAIN = 'clevers-product-carousel';

	public function test_plugin_header_declares_expected_text_domain(): void {
		$bootstrap = file_get_contents( dirname( __DIR__, 2 ) . '/clevers-product-carousel.php' );
		$this->assertIsString( $bootstrap );
		$this->assertStringContainsString( 'Text Domain: ' . self::TEXT_DOMAIN, $bootstrap );
	}

	public function test_plugin_does_not_call_legacy_load_plugin_textdomain(): void {
		$bootstrap = file_get_contents( dirname( __DIR__, 2 ) . '/clevers-product-carousel.php' );
		$this->assertIsString( $bootstrap );
		// WordPress 4.6+ loads translations for plugins on wordpress.org
		// automatically on `init`, so load_plugin_textdomain() is no longer
		// required. The plugin deliberately omits it (see the comment in the
		// main plugin file).
		$this->assertDoesNotMatchRegularExpression(
			"/load_plugin_textdomain\\(\\s*'clevers-product-carousel'\\s*,/",
			$bootstrap
		);
	}

	public function test_translation_function_calls_use_plugin_text_domain(): void {
		$php_files = $this->collect_php_files( dirname( __DIR__, 2 ) );
		$errors = array();

		foreach ( $php_files as $file ) {
			$code = file_get_contents( $file );
			if ( false === $code ) {
				continue;
			}

			$patterns = array(
				'/\\b(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\\s*\\([^\\)]*,\\s*([\'"])([^\'"]+)\\1\\s*\\)/',
				'/\\b_x\\s*\\([^\\)]*,\\s*([\'"])[^\'"]+\\1\\s*,\\s*([\'"])([^\'"]+)\\2\\s*\\)/',
				'/\\b_n\\s*\\([^\\)]*,\\s*([\'"])[^\'"]+\\1\\s*,\\s*([\'"])[^\'"]+\\2\\s*,\\s*[^,\\)]+,\\s*([\'"])([^\'"]+)\\3\\s*\\)/',
			);

			foreach ( $patterns as $pattern ) {
				preg_match_all( $pattern, $code, $matches, PREG_SET_ORDER );
				foreach ( $matches as $match ) {
					$domain = end( $match );
					if ( self::TEXT_DOMAIN !== $domain ) {
						$errors[] = $file . ' => unexpected text domain: ' . $domain;
					}
				}
			}
		}

		$this->assertSame( array(), $errors, implode( PHP_EOL, $errors ) );
	}

	/**
	 * @return array<int, string>
	 */
	private function collect_php_files( string $root ): array {
		$directory = new RecursiveDirectoryIterator( $root );
		$iterator = new RecursiveIteratorIterator( $directory );
		$files = array();

		foreach ( $iterator as $file ) {
			if ( ! $file->isFile() || 'php' !== $file->getExtension() ) {
				continue;
			}

			$path = $file->getPathname();
			if ( false !== strpos( $path, '/tests/' ) || false !== strpos( $path, '/vendor/' ) ) {
				continue;
			}

			$files[] = $path;
		}

		return $files;
	}
}
