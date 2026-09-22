<?php

use PHPUnit\Framework\TestCase;

final class HealthCheckTest extends TestCase {
	public function test_command_exists_rejects_unsafe_names(): void {
		$this->assertFalse( CLEVPRCA_Health_Check::command_exists( 'bad;rm -rf /' ) );
	}

	public function test_build_report_contains_compatibility_matrix(): void {
		$report = CLEVPRCA_Health_Check::build_report();

		$this->assertIsArray( $report );
		$this->assertArrayHasKey( 'checks', $report );
		$this->assertArrayHasKey( 'wordpress', $report['checks'] );
		$this->assertArrayHasKey( 'php', $report['checks'] );
		$this->assertArrayHasKey( 'woocommerce', $report['checks'] );
		$this->assertArrayHasKey( 'uploads_writable', $report['checks'] );
		$this->assertArrayHasKey( 'optimization_binaries', $report['checks'] );
	}

	public function test_run_on_activation_stores_report_transient(): void {
		CLEVPRCA_Health_Check::run_on_activation();

		$this->assertArrayHasKey( CLEVPRCA_Health_Check::RESULT_TRANSIENT, $GLOBALS['mock_state']['set_transients'] );
	}
}
