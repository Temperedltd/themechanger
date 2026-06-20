<?php
/**
 * Theme helpers.
 *
 * @package TemperedThemeChanger
 */

namespace TemperedThemeChanger\Themes;

defined( 'ABSPATH' ) || exit;

/**
 * Lists installed themes keyed by stylesheet.
 *
 * @return array<string, object>
 */
function installed_themes(): array {
	if ( ! function_exists( 'wp_get_themes' ) ) {
		return array();
	}

	return function_exists( 'is_multisite' ) && is_multisite()
		? wp_get_themes( array( 'allowed' => true ) )
		: wp_get_themes();
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
 * Checks whether a stored non-empty theme selection is no longer usable.
 *
 * @param string $stylesheet Stored stylesheet slug.
 */
function is_invalid_selection( string $stylesheet ): bool {
	$stylesheet = normalize_stylesheet( $stylesheet );

	return '' !== $stylesheet && ! theme_exists( $stylesheet );
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
 * @return array<string, string>
 */
function theme_choices(): array {
	$choices = array();

	foreach ( installed_themes() as $stylesheet => $theme ) {
		$stylesheet = normalize_stylesheet( $stylesheet );

		if ( '' === $stylesheet || ! is_object( $theme ) || ! method_exists( $theme, 'get' ) ) {
			continue;
		}

		$name                   = (string) $theme->get( 'Name' );
		$choices[ $stylesheet ] = '' === $name ? $stylesheet : $name;
	}

	natcasesort( $choices );

	return $choices;
}
