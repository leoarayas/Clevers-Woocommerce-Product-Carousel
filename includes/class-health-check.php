<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CLEVPRCA_Health_Check {
	const RESULT_TRANSIENT = 'clevprca_activation_health_check';

	/**
	 * Build a compatibility matrix with actionable diagnostics.
	 *
	 * @return array<string, mixed>
	 */
	public static function build_report() {
		global $wp_version;

		$required_wp  = '6.0';
		$required_php = '7.4';

		$uploads = function_exists( 'wp_upload_dir' ) ? wp_upload_dir() : array();
		$uploads_path = '';
		if ( isset( $uploads['basedir'] ) && is_string( $uploads['basedir'] ) ) {
			$uploads_path = $uploads['basedir'];
		}

		$checks = array(
			'wordpress' => self::make_check(
				__( 'WordPress version', 'clevers-product-carousel' ),
				$required_wp,
				is_string( $wp_version ) ? $wp_version : '',
				version_compare( is_string( $wp_version ) ? $wp_version : '0', $required_wp, '>=' ),
				__( 'Update WordPress to meet the plugin minimum version.', 'clevers-product-carousel' )
			),
			'php'       => self::make_check(
				__( 'PHP version', 'clevers-product-carousel' ),
				$required_php,
				PHP_VERSION,
				version_compare( PHP_VERSION, $required_php, '>=' ),
				__( 'Ask your host to upgrade PHP for better compatibility and security.', 'clevers-product-carousel' )
			),
			'woocommerce' => self::make_check(
				__( 'WooCommerce plugin', 'clevers-product-carousel' ),
				__( 'Active', 'clevers-product-carousel' ),
				class_exists( 'WooCommerce' ) ? __( 'Active', 'clevers-product-carousel' ) : __( 'Missing', 'clevers-product-carousel' ),
				class_exists( 'WooCommerce' ),
				__( 'Install and activate WooCommerce before using the carousel.', 'clevers-product-carousel' )
			),
			'uploads_writable' => self::make_check(
				__( 'Uploads directory writable', 'clevers-product-carousel' ),
				__( 'Writable', 'clevers-product-carousel' ),
				$uploads_path ? $uploads_path : __( 'Unavailable', 'clevers-product-carousel' ),
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- Read-only diagnostic check, not a mutation; WP_Filesystem is overkill for a permission probe.
				$uploads_path ? is_writable( $uploads_path ) : false,
				__( 'Grant write permission to wp-content/uploads for media and cache files.', 'clevers-product-carousel' )
			),
			'optimization_binaries' => self::build_binary_check(),
		);

		$has_errors = false;
		foreach ( $checks as $check ) {
			if ( empty( $check['ok'] ) ) {
				$has_errors = true;
				break;
			}
		}

		return array(
			'generated_at' => gmdate( 'c' ),
			'has_errors'   => $has_errors,
			'checks'       => $checks,
		);
	}

	/**
	 * Runs the activation health-check and stores the report for admin notice.
	 */
	public static function run_on_activation() {
		$report = self::build_report();
		set_transient( self::RESULT_TRANSIENT, $report, 10 * MINUTE_IN_SECONDS );
	}

	/**
	 * Render an activation notice if a report exists.
	 */
	public static function render_activation_notice() {
		if ( ! is_admin() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$report = get_transient( self::RESULT_TRANSIENT );
		if ( ! is_array( $report ) || empty( $report['checks'] ) || ! is_array( $report['checks'] ) ) {
			return;
		}

		delete_transient( self::RESULT_TRANSIENT );

		$notice_class = ! empty( $report['has_errors'] ) ? 'notice notice-warning' : 'notice notice-success';
		$title        = ! empty( $report['has_errors'] )
			? __( 'Clevers Product Carousel activated with compatibility warnings.', 'clevers-product-carousel' )
			: __( 'Clevers Product Carousel activated. Compatibility check passed.', 'clevers-product-carousel' );

		echo '<div class="' . esc_attr( $notice_class ) . '"><p><strong>' . esc_html( $title ) . '</strong></p><ul style="list-style:disc;margin-left:20px;">';
		foreach ( $report['checks'] as $check ) {
			if ( ! is_array( $check ) ) {
				continue;
			}

			$icon = ! empty( $check['ok'] ) ? '✅' : '⚠️';
			echo '<li>' . esc_html( sprintf( '%1$s %2$s: %3$s', $icon, $check['label'] ?? '', $check['actual'] ?? '' ) );
			if ( empty( $check['ok'] ) && ! empty( $check['action'] ) ) {
				echo '<br/><span style="opacity:.85;">' . esc_html( $check['action'] ) . '</span>';
			}
			echo '</li>';
		}
		echo '</ul></div>';
	}

	/**
	 * @param string $label
	 * @param string $expected
	 * @param string $actual
	 * @param bool   $ok
	 * @param string $action
	 *
	 * @return array<string, mixed>
	 */
	private static function make_check( $label, $expected, $actual, $ok, $action ) {
		return array(
			'label'    => $label,
			'expected' => $expected,
			'actual'   => $actual,
			'ok'       => (bool) $ok,
			'action'   => $action,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function build_binary_check() {
		$required = array( 'jpegoptim', 'optipng', 'pngquant', 'cwebp' );
		$found    = array();
		$missing  = array();

		foreach ( $required as $binary ) {
			if ( self::command_exists( $binary ) ) {
				$found[] = $binary;
			} else {
				$missing[] = $binary;
			}
		}

		$ok = count( $missing ) < count( $required );

		$actual = $found
			? sprintf(
				/* translators: 1: found binary list. 2: missing binary list. */
				__( 'Found: %1$s. Missing: %2$s.', 'clevers-product-carousel' ),
				implode( ', ', $found ),
				$missing ? implode( ', ', $missing ) : __( 'none', 'clevers-product-carousel' )
			)
			: __( 'No optimization binaries detected in PATH.', 'clevers-product-carousel' );

		$action = __( 'Install at least one supported optimizer binary (jpegoptim/optipng/pngquant/cwebp) on the server.', 'clevers-product-carousel' );

		return self::make_check(
			__( 'Optimization binaries', 'clevers-product-carousel' ),
			__( 'At least one available', 'clevers-product-carousel' ),
			$actual,
			$ok,
			$action
		);
	}

	/**
	 * @param string $command
	 *
	 * @return bool
	 */
	public static function command_exists( $command ) {
		if ( ! preg_match( '/^[A-Za-z0-9._-]+$/', $command ) ) {
			return false;
		}

		if ( function_exists( 'shell_exec' ) ) {
			$result = shell_exec( 'command -v ' . escapeshellarg( $command ) . ' 2>/dev/null' );
			if ( is_string( $result ) && '' !== trim( $result ) ) {
				return true;
			}
		}

		$path = getenv( 'PATH' );
		if ( ! is_string( $path ) || '' === $path ) {
			return false;
		}

		foreach ( explode( PATH_SEPARATOR, $path ) as $directory ) {
			if ( '' === $directory ) {
				continue;
			}
			$candidate = rtrim( $directory, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . $command;
			if ( is_file( $candidate ) && is_executable( $candidate ) ) {
				return true;
			}
		}

		return false;
	}
}
