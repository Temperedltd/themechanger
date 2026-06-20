<?php
/**
 * Theme resolver.
 *
 * @package TemperedThemeChanger
 */

namespace TemperedThemeChanger\Resolver;

use function TemperedThemeChanger\Storage\sanitize_theme_slug;
use function TemperedThemeChanger\Themes\normalize_stylesheet;
use function TemperedThemeChanger\Themes\resolve_template;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the effective theme for a prepared context.
 *
 * @param array<string, mixed> $context Resolution context.
 * @return array{stylesheet: string, template: string}
 */
function resolve_theme( array $context ): array {
	$selected_theme = resolve_selected_theme( $context );

	if ( null !== $selected_theme ) {
		return $selected_theme;
	}

	$active_stylesheet = normalize_stylesheet( $context['active_stylesheet'] ?? '' );
	$active_template   = normalize_stylesheet( $context['active_template'] ?? '' );

	if ( '' === $active_template && '' !== $active_stylesheet ) {
		$active_template = resolve_template( $active_stylesheet );
	}

	return array(
		'stylesheet' => $active_stylesheet,
		'template'   => $active_template,
	);
}

/**
 * Resolves the selected non-active theme for a prepared context.
 *
 * @param array<string, mixed> $context Resolution context.
 * @return array{stylesheet: string, template: string}|null
 */
function resolve_selected_theme( array $context ): ?array {
	foreach ( array( 'post_theme', 'term_theme', 'post_type_default' ) as $key ) {
		$stylesheet = sanitize_theme_slug( $context[ $key ] ?? '' );

		if ( '' === $stylesheet ) {
			continue;
		}

		return array(
			'stylesheet' => $stylesheet,
			'template'   => resolve_template( $stylesheet ),
		);
	}

	return null;
}
