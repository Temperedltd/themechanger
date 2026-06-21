<?php
/**
 * Role capability helpers.
 *
 * @package TemperedThemeChanger
 */

namespace TemperedThemeChanger\Roles;

use const TemperedThemeChanger\Access\CAPABILITY;
use const TemperedThemeChanger\OPTION_NAME;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/access.php';

/**
 * Registers role capability hooks.
 */
function bootstrap(): void {
	if ( function_exists( 'add_action' ) ) {
		add_action( 'admin_init', __NAMESPACE__ . '\\handle_admin_settings_submission' );
	}
}

/**
 * Returns editable WordPress roles.
 *
 * @return array<string, array{name?: string, capabilities?: array<string, bool>}>
 */
function editable_roles(): array {
	if ( function_exists( 'get_editable_roles' ) ) {
		return get_editable_roles();
	}

	if ( ! function_exists( 'wp_roles' ) ) {
		return array();
	}

	$roles = wp_roles();

	return is_object( $roles ) && isset( $roles->roles ) && is_array( $roles->roles ) ? $roles->roles : array();
}

/**
 * Returns a readable role label.
 *
 * @param string               $role_key Role key.
 * @param array<string, mixed> $role     Role definition.
 */
function role_label( string $role_key, array $role ): string {
	$label = isset( $role['name'] ) && is_scalar( $role['name'] ) ? (string) $role['name'] : $role_key;

	return function_exists( 'translate_user_role' ) ? translate_user_role( $label ) : $label;
}

/**
 * Checks whether a role definition has a capability.
 *
 * @param array<string, mixed> $role       Role definition.
 * @param string               $capability Capability name.
 */
function role_has_capability( array $role, string $capability ): bool {
	return ! empty( $role['capabilities'] ) && is_array( $role['capabilities'] ) && ! empty( $role['capabilities'][ $capability ] );
}

/**
 * Checks whether Theme Changer should manage the custom capability for a role.
 *
 * @param array<string, mixed> $role Role definition.
 */
function role_can_be_managed( array $role ): bool {
	return ! role_has_capability( $role, 'switch_themes' );
}

/**
 * Updates the custom Theme Changer capability for eligible roles.
 *
 * @param mixed $selected_roles Selected role keys.
 */
function update_theme_changer_capability( mixed $selected_roles ): void {
	if ( ! function_exists( 'get_role' ) ) {
		return;
	}

	$selected_roles = normalize_selected_roles( $selected_roles );

	foreach ( editable_roles() as $role_key => $role ) {
		if ( ! is_string( $role_key ) || ! is_array( $role ) || ! role_can_be_managed( $role ) ) {
			continue;
		}

		$role_object = get_role( $role_key );

		if ( ! is_object( $role_object ) ) {
			continue;
		}

		$has_capability = role_has_capability( $role, CAPABILITY );
		$is_selected    = in_array( $role_key, $selected_roles, true );

		if ( $is_selected ) {
			if ( $has_capability ) {
				continue;
			}

			if ( method_exists( $role_object, 'add_cap' ) ) {
				$role_object->add_cap( CAPABILITY );
			}
			continue;
		}

		if ( $has_capability && method_exists( $role_object, 'remove_cap' ) ) {
			$role_object->remove_cap( CAPABILITY );
		}
	}
}

/**
 * Handles role capability changes from the Theme Changer settings form.
 */
function handle_admin_settings_submission(): void {
	if ( empty( $_POST ) || ! is_array( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}

	$option_page = isset( $_POST['option_page'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		? sanitize_key( wp_unslash( $_POST['option_page'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		: '';

	if ( 'tempered_themechanger' !== $option_page || empty( $_POST[ OPTION_NAME ] ) || ! is_array( $_POST[ OPTION_NAME ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}

	$settings = wp_unslash( $_POST[ OPTION_NAME ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( ! is_array( $settings ) || ! isset( $settings['role_capabilities_submitted'] ) ) {
		return;
	}

	if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'switch_themes' ) ) {
		return;
	}

	if ( function_exists( 'check_admin_referer' ) ) {
		check_admin_referer( 'tempered_themechanger-options' );
	}

	update_theme_changer_capability( $settings['role_capabilities'] ?? array() );
}

/**
 * Normalizes submitted role keys.
 *
 * @param mixed $selected_roles Selected role keys.
 * @return string[]
 */
function normalize_selected_roles( mixed $selected_roles ): array {
	if ( ! is_array( $selected_roles ) ) {
		return array();
	}

	$normalized = array();

	foreach ( $selected_roles as $role_key ) {
		if ( ! is_scalar( $role_key ) ) {
			continue;
		}

		$role_key = sanitize_key( (string) $role_key );

		if ( '' !== $role_key ) {
			$normalized[ $role_key ] = $role_key;
		}
	}

	return array_values( $normalized );
}
