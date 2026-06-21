<?php
/**
 * Theme helpers.
 *
 * @package TemperedThemeChanger
 */

namespace TemperedThemeChanger\Themes;

use const TemperedThemeChanger\OPTION_NAME;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/constants.php';

/**
 * Clears request-scoped theme helper caches.
 */
function clear_cache(): void {
	$GLOBALS['tempered_themechanger_theme_cache'] = array();
}

/**
 * Reads a request-scoped cached value.
 *
 * @param string $key Cache key.
 */
function cached_value( string $key ): mixed {
	return $GLOBALS['tempered_themechanger_theme_cache'][ $key ] ?? null;
}

/**
 * Stores a request-scoped cached value.
 *
 * @param string $key   Cache key.
 * @param mixed  $value Cached value.
 */
function set_cached_value( string $key, mixed $value ): mixed {
	$GLOBALS['tempered_themechanger_theme_cache'][ $key ] = $value;

	return $value;
}

/**
 * Lists installed themes keyed by stylesheet.
 *
 * @return array<string, object>
 */
function installed_themes(): array {
	if ( ! function_exists( 'wp_get_themes' ) ) {
		return array();
	}

	$cache_key = function_exists( 'is_multisite' ) && is_multisite() ? 'installed_themes_multisite' : 'installed_themes';
	$themes    = cached_value( $cache_key );

	if ( is_array( $themes ) ) {
		return $themes;
	}

	$themes = 'installed_themes_multisite' === $cache_key
		? wp_get_themes( array( 'allowed' => true ) )
		: wp_get_themes();

	return set_cached_value( $cache_key, $themes );
}

/**
 * Normalizes a stylesheet slug.
 *
 * @param mixed $stylesheet Stylesheet value.
 */
function normalize_stylesheet( mixed $stylesheet ): string {
	if ( ! is_scalar( $stylesheet ) ) {
		return '';
	}

	$stylesheet = strtolower( trim( (string) $stylesheet ) );
	$stylesheet = preg_replace( '/\s+/', '-', $stylesheet ) ?? '';

	return sanitize_key( $stylesheet );
}

/**
 * Checks whether a stylesheet belongs to an installed theme.
 *
 * @param string $stylesheet Stylesheet slug.
 */
function theme_exists( string $stylesheet ): bool {
	$stylesheet = normalize_stylesheet( $stylesheet );

	if ( '' === $stylesheet ) {
		return false;
	}

	return isset( installed_themes()[ $stylesheet ] );
}

/**
 * Returns the configured theme allow-list.
 *
 * @return string[]
 */
function theme_allow_list(): array {
	if ( ! function_exists( 'get_option' ) ) {
		return array();
	}

	$cached_allow_list = cached_value( 'theme_allow_list' );

	if ( is_array( $cached_allow_list ) ) {
		return $cached_allow_list;
	}

	$settings = get_option(
		OPTION_NAME,
		array(
			'theme_allow_list' => array(),
		)
	);

	if ( ! is_array( $settings ) || empty( $settings['theme_allow_list'] ) || ! is_array( $settings['theme_allow_list'] ) ) {
		return set_cached_value( 'theme_allow_list', array() );
	}

	return set_cached_value( 'theme_allow_list', normalize_theme_allow_list( $settings['theme_allow_list'] ) );
}

/**
 * Checks whether theme switching is constrained to an allow-list.
 */
function has_theme_allow_list(): bool {
	return array() !== theme_allow_list();
}

/**
 * Checks whether a stylesheet can be selected by Theme Changer.
 *
 * @param string $stylesheet Stylesheet slug.
 */
function is_theme_switchable( string $stylesheet ): bool {
	$stylesheet = normalize_stylesheet( $stylesheet );

	if ( '' === $stylesheet || ! theme_exists( $stylesheet ) ) {
		return false;
	}

	$allow_list = theme_allow_list();

	if ( array() === $allow_list ) {
		return true;
	}

	return in_array( $stylesheet, $allow_list, true );
}

/**
 * Normalizes theme stylesheet slugs to installed theme slugs.
 *
 * @param mixed $theme_slugs Theme slugs.
 * @return string[]
 */
function normalize_theme_allow_list( mixed $theme_slugs ): array {
	if ( ! is_array( $theme_slugs ) ) {
		return array();
	}

	$sanitized = array();

	foreach ( $theme_slugs as $theme_slug ) {
		$theme_slug = normalize_stylesheet( $theme_slug );

		if ( '' === $theme_slug || ! theme_exists( $theme_slug ) ) {
			continue;
		}

		$sanitized[ $theme_slug ] = $theme_slug;
	}

	$sanitized = array_values( $sanitized );
	sort( $sanitized, SORT_STRING );

	return $sanitized;
}

/**
 * Checks whether a stored non-empty theme selection is no longer usable.
 *
 * @param string $stylesheet Stored stylesheet slug.
 */
function is_invalid_selection( string $stylesheet ): bool {
	$stylesheet = normalize_stylesheet( $stylesheet );

	return '' !== $stylesheet && ! is_theme_switchable( $stylesheet );
}

/**
 * Resolves a theme template slug from a stylesheet.
 *
 * @param string $stylesheet Stylesheet slug.
 */
function resolve_template( string $stylesheet ): string {
	$stylesheet = normalize_stylesheet( $stylesheet );
	$themes     = installed_themes();

	if ( '' === $stylesheet || ! isset( $themes[ $stylesheet ] ) ) {
		return '';
	}

	$theme = $themes[ $stylesheet ];

	if ( ! is_object( $theme ) || ! method_exists( $theme, 'get_template' ) ) {
		return $stylesheet;
	}

	return normalize_stylesheet( $theme->get_template() );
}

/**
 * Builds dropdown choices for installed themes.
 *
 * @param bool $switchable_only Whether to limit choices to switchable themes.
 * @return array<string, string>
 */
function theme_choices( bool $switchable_only = true ): array {
	$choices    = array();
	$allow_list = $switchable_only ? theme_allow_list() : array();

	foreach ( installed_themes() as $stylesheet => $theme ) {
		$stylesheet = normalize_stylesheet( $stylesheet );

		if ( '' === $stylesheet || ! is_object( $theme ) || ! method_exists( $theme, 'get' ) ) {
			continue;
		}

		if ( $switchable_only && array() !== $allow_list && ! in_array( $stylesheet, $allow_list, true ) ) {
			continue;
		}

		$name                   = (string) $theme->get( 'Name' );
		$choices[ $stylesheet ] = '' === $name ? $stylesheet : $name;
	}

	natcasesort( $choices );

	return $choices;
}
