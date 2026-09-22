<?php
/**
 * Regression tests for the WP-CLI render --file sandbox.
 *
 * The command must never write outside the plugin uploads directory, so
 * resolve_upload_path() has to reject anything that is not a plain filename.
 */

use PHPUnit\Framework\TestCase;

if ( ! defined( 'WP_CLI' ) ) {
	define( 'WP_CLI', true );
}

if ( ! class_exists( 'WP_CLI' ) ) {
	class WP_CLI {
		public static function error( $message ) {
			throw new RuntimeException( (string) $message );
		}
		public static function success( $message ) {
			unset( $message );
		}
		public static function add_command( $name, $callable ) {
			unset( $name, $callable );
		}
	}
}

if ( ! class_exists( 'WP_Query' ) ) {
	class WP_Query {
		public $posts = array();
		public function have_posts() {
			return false;
		}
	}
}

require_once dirname( __DIR__, 2 ) . '/includes/class-cli.php';

final class CliUploadPathTest extends TestCase {
	private function resolve( string $name ): string {
		$method = new ReflectionMethod( CLEVPRCA_CLI::class, 'resolve_upload_path' );
		$method->setAccessible( true );
		return (string) $method->invoke( new CLEVPRCA_CLI(), $name );
	}

	public function test_accepts_a_plain_filename_inside_plugin_uploads(): void {
		$path = $this->resolve( 'carousel-42.html' );

		$this->assertNotSame( '', $path );
		$this->assertStringContainsString( 'clevers-product-carousel', $path );
		$this->assertStringEndsWith( '/carousel-42.html', str_replace( '\\', '/', $path ) );
	}

	public function test_rejects_parent_traversal(): void {
		$this->assertSame( '', $this->resolve( '../evil.php' ) );
		$this->assertSame( '', $this->resolve( 'sub/../../evil.php' ) );
	}

	public function test_rejects_directory_separators(): void {
		$this->assertSame( '', $this->resolve( 'sub/file.html' ) );
		$this->assertSame( '', $this->resolve( 'sub\\file.html' ) );
	}

	public function test_rejects_stream_wrappers(): void {
		$this->assertSame( '', $this->resolve( 'php://filter/write=string.rot13/resource=x' ) );
	}

	public function test_rejects_empty_name(): void {
		$this->assertSame( '', $this->resolve( '' ) );
	}
}
