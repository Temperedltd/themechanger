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

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( mixed $value ): mixed {
		return $value;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( mixed $value ): string|false {
		return json_encode( $value );
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
		$GLOBALS['ttc_wp_get_themes_args']    = $args;
		$GLOBALS['ttc_wp_get_themes_calls'][] = $args;

		return $GLOBALS['ttc_test_themes'] ?? array();
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $option_name, mixed $default = false ): mixed {
		$GLOBALS['ttc_get_option_calls'][] = array( $option_name, $default );

		return $GLOBALS['ttc_test_options'][ $option_name ] ?? $default;
	}
}

if ( ! function_exists( 'get_editable_roles' ) ) {
	function get_editable_roles(): array {
		return $GLOBALS['ttc_test_roles'] ?? array();
	}
}

if ( ! function_exists( 'get_role' ) ) {
	function get_role( string $role_name ): ?object {
		return $GLOBALS['ttc_test_role_objects'][ $role_name ] ?? null;
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id(): int {
		return (int) ( $GLOBALS['ttc_current_user_id'] ?? 1 );
	}
}

if ( ! function_exists( 'user_can' ) ) {
	function user_can( int $user_id, string $capability, mixed ...$args ): bool {
		$GLOBALS['ttc_user_can_calls'][] = array_merge( array( $user_id, $capability ), $args );
		$key                             = $user_id . ':' . $capability;

		if ( isset( $GLOBALS['ttc_user_can_results'][ $key ] ) ) {
			return (bool) $GLOBALS['ttc_user_can_results'][ $key ];
		}

		return (bool) ( $GLOBALS['ttc_user_can_results'][ $capability ] ?? false );
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( string $hook_name, mixed $value, mixed ...$args ): mixed {
		$GLOBALS['ttc_apply_filters_calls'][] = array_merge( array( $hook_name, $value ), $args );

		if ( isset( $GLOBALS['ttc_apply_filters_results'][ $hook_name ] ) ) {
			return $GLOBALS['ttc_apply_filters_results'][ $hook_name ];
		}

		return $value;
	}
}
