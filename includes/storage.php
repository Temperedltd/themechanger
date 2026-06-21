<?php
/**
 * Storage helpers.
 *
 * @package TemperedThemeChanger
 */

namespace TemperedThemeChanger\Storage;

use function TemperedThemeChanger\Themes\normalize_stylesheet;
use function TemperedThemeChanger\Themes\theme_exists;

defined( 'ABSPATH' ) || exit;

const META_KEY    = '_tempered_themechanger_theme';
const OPTION_NAME = 'tempered_themechanger_settings';

/**
 * Registers plugin storage with WordPress.
 */
function register(): void {
	if ( function_exists( 'register_post_meta' ) ) {
		register_post_meta(
			'',
			META_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => __NAMESPACE__ . '\\sanitize_theme_slug',
				'auth_callback'     => __NAMESPACE__ . '\\can_edit_post_meta',
			)
		);
	}

	if ( function_exists( 'register_term_meta' ) ) {
		register_term_meta(
			'',
			META_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => __NAMESPACE__ . '\\sanitize_theme_slug',
				'auth_callback'     => __NAMESPACE__ . '\\can_edit_term_meta',
			)
		);
	}

	if ( function_exists( 'register_setting' ) ) {
		register_setting(
			'tempered_themechanger',
			OPTION_NAME,
			array(
				'type'              => 'array',
				'default'           => default_settings(),
				'sanitize_callback' => __NAMESPACE__ . '\\sanitize_settings',
			)
		);
	}
}

/**
 * Checks whether the current user can edit post theme meta.
 *
 * @param bool|null $allowed   Existing auth value.
 * @param string    $meta_key  Meta key.
 * @param int       $object_id Object ID.
 */
function can_edit_post_meta( ?bool $allowed, string $meta_key, int $object_id ): bool {
	if ( ! function_exists( 'current_user_can' ) ) {
		return false;
	}

	return current_user_can( 'edit_post', $object_id );
}

/**
 * Checks whether the current user can edit term theme meta.
 *
 * @param bool|null $allowed   Existing auth value.
 * @param string    $meta_key  Meta key.
 * @param int       $object_id Object ID.
 */
function can_edit_term_meta( ?bool $allowed, string $meta_key, int $object_id ): bool {
	if ( ! function_exists( 'current_user_can' ) ) {
		return false;
	}

	return current_user_can( 'edit_term', $object_id );
}

/**
 * Returns default option settings.
 *
 * @return array{post_type_defaults: array<string, string>}
 */
function default_settings(): array {
	return array(
		'post_type_defaults' => array(),
	);
}

/**
 * Sanitizes a stored theme slug.
 *
 * @param mixed $theme_slug Theme slug.
 */
function sanitize_theme_slug( mixed $theme_slug ): string {
	$theme_slug = normalize_stylesheet( $theme_slug );

	if ( '' === $theme_slug || ! theme_exists( $theme_slug ) ) {
		return '';
	}

	return $theme_slug;
}

/**
 * Sanitizes plugin settings.
 *
 * @param mixed $settings Raw settings.
 * @return array{post_type_defaults: array<string, string>}
 */
function sanitize_settings( mixed $settings ): array {
	$sanitized = default_settings();

	if ( ! is_array( $settings ) || ! isset( $settings['post_type_defaults'] ) || ! is_array( $settings['post_type_defaults'] ) ) {
		return $sanitized;
	}

	foreach ( $settings['post_type_defaults'] as $post_type => $theme_slug ) {
		if ( ! is_string( $post_type ) || ! is_valid_post_type_key( $post_type ) ) {
			continue;
		}

		$sanitized['post_type_defaults'][ $post_type ] = sanitize_theme_slug( $theme_slug );
	}

	return $sanitized;
}

/**
 * Checks whether a post type key is already safely formed.
 *
 * @param string $post_type Post type key.
 */
function is_valid_post_type_key( string $post_type ): bool {
	return 1 === preg_match( '/^[a-z0-9_-]+$/', $post_type );
}
