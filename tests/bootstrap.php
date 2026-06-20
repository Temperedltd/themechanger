<?php
/**
 * PHPUnit bootstrap.
 *
 * @package TemperedThemeChanger
 */

if ( file_exists( dirname( __DIR__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __DIR__ ) . '/vendor/autoload.php';
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! function_exists( 'absint' ) ) {
	function absint( mixed $maybeint ): int {
		return abs( (int) $maybeint );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( mixed $key ): string {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) ?? '' );
	}
}

if ( ! function_exists( 'is_multisite' ) ) {
	function is_multisite(): bool {
		return (bool) ( $GLOBALS['ttc_is_multisite'] ?? false );
	}
}

if ( ! function_exists( 'wp_get_themes' ) ) {
	function wp_get_themes( array $args = array() ): array {
		$GLOBALS['ttc_wp_get_themes_args'] = $args;

		return $GLOBALS['ttc_test_themes'] ?? array();
	}
}
