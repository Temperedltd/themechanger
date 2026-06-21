<?php
/**
 * Storage helpers.
 *
 * @package TemperedThemeChanger
 */

namespace TemperedThemeChanger\Storage;

use function TemperedThemeChanger\Access\can_change_post_theme;
use function TemperedThemeChanger\Themes\clear_cache;
use function TemperedThemeChanger\Themes\normalize_stylesheet;
use function TemperedThemeChanger\Themes\normalize_theme_allow_list;
use function TemperedThemeChanger\Themes\theme_exists;
use function TemperedThemeChanger\Themes\is_theme_switchable;
use const TemperedThemeChanger\OPTION_NAME as SETTINGS_OPTION_NAME;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/roles.php';

const META_KEY    = '_tempered_themechanger_theme';
const OPTION_NAME = SETTINGS_OPTION_NAME;

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
	unset( $allowed, $meta_key );

	return can_change_post_theme( $object_id );
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
 * @return array{post_type_defaults: array<string, string>, theme_allow_list: string[]}
 */
function default_settings(): array {
	return array(
		'post_type_defaults' => array(),
		'theme_allow_list'   => array(),
	);
}

/**
 * Sanitizes a stored theme slug.
 *
 * @param mixed $theme_slug Theme slug.
 */
function sanitize_theme_slug( mixed $theme_slug ): string {
	$theme_slug = normalize_stylesheet( $theme_slug );

	if ( '' === $theme_slug || ! is_theme_switchable( $theme_slug ) ) {
		return '';
	}

	return $theme_slug;
}

/**
 * Sanitizes a list of allowed theme stylesheet slugs.
 *
 * @param mixed $theme_slugs Theme slugs.
 * @return string[]
 */
function sanitize_theme_allow_list( mixed $theme_slugs ): array {
	return normalize_theme_allow_list( $theme_slugs );
}

/**
 * Sanitizes a theme slug against a prepared allow-list.
 *
 * @param mixed    $theme_slug Theme slug.
 * @param string[] $allow_list Prepared allow-list.
 */
function sanitize_theme_slug_with_allow_list( mixed $theme_slug, array $allow_list ): string {
	$theme_slug = normalize_stylesheet( $theme_slug );

	if ( '' === $theme_slug || ! theme_exists( $theme_slug ) ) {
		return '';
	}

	if ( array() === $allow_list ) {
		return $theme_slug;
	}

	return in_array( $theme_slug, $allow_list, true ) ? $theme_slug : '';
}

/**
 * Sanitizes plugin settings.
 *
 * @param mixed $settings Raw settings.
 * @return array{post_type_defaults: array<string, string>, theme_allow_list: string[]}
 */
function sanitize_settings( mixed $settings ): array {
	clear_cache();

	$sanitized = default_settings();

	if ( ! is_array( $settings ) ) {
		return $sanitized;
	}

	if ( isset( $settings['theme_allow_list'] ) ) {
		$sanitized['theme_allow_list'] = sanitize_theme_allow_list( $settings['theme_allow_list'] );
	}

	if ( isset( $settings['post_type_defaults'] ) && is_array( $settings['post_type_defaults'] ) ) {
		foreach ( $settings['post_type_defaults'] as $post_type => $theme_slug ) {
			if ( ! is_string( $post_type ) || ! is_valid_post_type_key( $post_type ) ) {
				continue;
			}

			$sanitized['post_type_defaults'][ $post_type ] = sanitize_theme_slug_with_allow_list( $theme_slug, $sanitized['theme_allow_list'] );
		}
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
