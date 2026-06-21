<?php
/**
 * Plugin metadata tests.
 *
 * @package TemperedThemeChanger
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

final class PluginMetadataTest extends TestCase {
	public function test_plugin_entrypoint_declares_expected_metadata(): void {
		$plugin_file = dirname( __DIR__, 2 ) . '/tempered-themechanger.php';
		$contents    = file_get_contents( $plugin_file );

		self::assertIsString( $contents );
		self::assertStringContainsString( 'Plugin Name: Theme Changer', $contents );
		self::assertStringContainsString( 'Author: Tempered Ltd', $contents );
		self::assertStringContainsString( 'Text Domain: tempered-themechanger', $contents );
		self::assertStringContainsString( 'Requires at least: 7.0', $contents );
		self::assertStringContainsString( 'Requires PHP: 8.2', $contents );
	}

	public function test_production_bootstrap_does_not_start_runtime_spike(): void {
		$plugin_file = dirname( __DIR__, 2 ) . '/includes/plugin.php';
		$contents    = file_get_contents( $plugin_file );

		self::assertIsString( $contents );
		self::assertStringNotContainsString( 'RuntimeSpike\\bootstrap', $contents );
	}
}
