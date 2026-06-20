<?php
/**
 * Runtime theme switching.
 *
 * @package TemperedThemeChanger
 */

namespace TemperedThemeChanger\Runtime;

use function TemperedThemeChanger\Resolver\resolve_selected_theme;
use function TemperedThemeChanger\Storage\default_settings;
use const TemperedThemeChanger\Storage\META_KEY;
use const TemperedThemeChanger\Storage\OPTION_NAME;

defined( 'ABSPATH' ) || exit;

/**
 * Registers runtime filters.
 */
function bootstrap(): void {
	if ( ! function_exists( 'add_filter' ) ) {
		return;
	}

	add_filter( 'pre_option_stylesheet', __NAMESPACE__ . '\\filter_stylesheet' );
	add_filter( 'pre_option_template', __NAMESPACE__ . '\\filter_template' );
	add_filter( 'stylesheet', __NAMESPACE__ . '\\filter_stylesheet' );
	add_filter( 'template', __NAMESPACE__ . '\\filter_template' );
}

/**
 * Filters the current stylesheet.
 *
 * @param string|false $stylesheet Current stylesheet value.
 * @return string|false
 */
function filter_stylesheet( string|false $stylesheet ): string|false {
	if ( ! is_allowed_context( current_context_flags() ) ) {
		return $stylesheet;
	}

	$selected_theme = resolve_selected_theme( current_resolution_context() );

	return null === $selected_theme ? $stylesheet : $selected_theme['stylesheet'];
}

/**
 * Filters the current template.
 *
 * @param string|false $template Current template value.
 * @return string|false
 */
function filter_template( string|false $template ): string|false {
	if ( ! is_allowed_context( current_context_flags() ) ) {
		return $template;
	}

	$selected_theme = resolve_selected_theme( current_resolution_context() );

	return null === $selected_theme ? $template : $selected_theme['template'];
}

/**
 * Checks whether theme switching is allowed for a request context.
 *
 * @param array<string, mixed> $context Request context flags.
 */
function is_allowed_context( array $context ): bool {
	if ( ! empty( $context['doing_ajax'] ) || ! empty( $context['doing_cron'] ) || ! empty( $context['is_login'] ) || ! empty( $context['is_cli'] ) ) {
		return false;
	}

	if ( ! empty( $context['is_rest'] ) || ! empty( $context['is_preview'] ) || ! empty( $context['is_front_end'] ) ) {
		return true;
	}

	if ( isset( $context['admin_page'] ) ) {
		return in_array( $context['admin_page'], array( 'post.php', 'post-new.php' ), true );
	}

	return false;
}

/**
 * Builds request context flags from WordPress globals.
 *
 * @return array<string, mixed>
 */
function current_context_flags(): array {
	if ( is_cli_request() ) {
		return array(
			'is_cli' => true,
		);
	}

	$pagenow = $GLOBALS['pagenow'] ?? '';

	return array(
		'doing_ajax'   => function_exists( 'wp_doing_ajax' ) && wp_doing_ajax(),
		'doing_cron'   => function_exists( 'wp_doing_cron' ) && wp_doing_cron(),
		'is_login'     => 'wp-login.php' === $pagenow,
		'is_rest'      => defined( 'REST_REQUEST' ) && REST_REQUEST,
		'is_preview'   => function_exists( 'is_preview' ) && is_preview(),
		'is_front_end' => function_exists( 'is_admin' ) && ! is_admin(),
		'admin_page'   => function_exists( 'is_admin' ) && is_admin() ? $pagenow : '',
	);
}

/**
 * Checks whether the request is running from a command-line runtime.
 */
function is_cli_request(): bool {
	return ( defined( 'WP_CLI' ) && WP_CLI ) || 'cli' === PHP_SAPI;
}

/**
 * Builds a resolver context for the current request.
 *
 * @return array<string, mixed>
 */
function current_resolution_context(): array {
	$context   = current_context_flags();
	$post_id   = resolve_context_post_id( request_values(), $context );
	$term_id   = 0;
	$post_type = '';

	if ( query_conditionals_are_available() ) {
		if ( 0 === $post_id && function_exists( 'is_singular' ) && is_singular() && function_exists( 'get_queried_object_id' ) ) {
			$post_id = absint( get_queried_object_id() );
		}

		if ( function_exists( 'is_tax' ) && function_exists( 'is_category' ) && function_exists( 'is_tag' ) && ( is_tax() || is_category() || is_tag() ) ) {
			$term_id = current_term_id();
		}
	}

	if ( 0 < $post_id && function_exists( 'get_post_type' ) ) {
		$post_type = (string) get_post_type( $post_id );
	}

	return array(
		'post_theme'        => 0 < $post_id && function_exists( 'get_post_meta' ) ? get_post_meta( $post_id, META_KEY, true ) : '',
		'term_theme'        => 0 < $term_id && function_exists( 'get_term_meta' ) ? get_term_meta( $term_id, META_KEY, true ) : '',
		'post_type_default' => '' === $post_type ? '' : post_type_default_theme( $post_type ),
	);
}

/**
 * Resolves a post ID from request values for an allowed request context.
 *
 * @param array<string, mixed> $request Request values.
 * @param array<string, mixed> $context Request context flags.
 */
function resolve_context_post_id( array $request, array $context ): int {
	if ( ! empty( $context['is_front_end'] ) ) {
		return resolve_request_post_id( $request, array( 'p', 'page_id' ) );
	}

	if ( ! empty( $context['is_preview'] ) ) {
		return editable_request_post_id( $request, array( 'preview_id', 'p', 'page_id' ) );
	}

	if ( ! empty( $context['is_rest'] ) ) {
		return editable_request_post_id( $request, array( 'post', 'post_ID', 'id' ) );
	}

	if ( isset( $context['admin_page'] ) && 'post.php' === $context['admin_page'] ) {
		return editable_request_post_id( $request, array( 'post', 'post_ID' ) );
	}

	return 0;
}

/**
 * Checks whether WordPress query conditionals are safe to call.
 */
function query_conditionals_are_available(): bool {
	return function_exists( 'did_action' ) && 0 < did_action( 'wp' );
}

/**
 * Resolves a post ID from request values.
 *
 * @param array<string, mixed> $request    Request values.
 * @param string[]             $candidates Candidate request keys.
 */
function resolve_request_post_id( array $request, array $candidates = array( 'post', 'post_ID', 'p', 'page_id', 'preview_id' ) ): int {
	foreach ( $candidates as $candidate ) {
		if ( ! isset( $request[ $candidate ] ) ) {
			continue;
		}

		$post_id = absint( $request[ $candidate ] );

		if ( 0 < $post_id ) {
			return $post_id;
		}
	}

	return 0;
}

/**
 * Resolves an editable post ID from request values.
 *
 * @param array<string, mixed> $request    Request values.
 * @param string[]             $candidates Candidate request keys.
 */
function editable_request_post_id( array $request, array $candidates ): int {
	$post_id = resolve_request_post_id( $request, $candidates );

	if ( 0 === $post_id || ! function_exists( 'current_user_can' ) ) {
		return 0;
	}

	return current_user_can( 'edit_post', $post_id ) ? $post_id : 0;
}

/**
 * Gets request values.
 *
 * @return array<string, mixed>
 */
function request_values(): array {
	$get_values  = array();
	$post_values = array();

	if ( isset( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$get_values = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	if ( isset( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$post_values = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	return array_merge( $get_values, $post_values );
}

/**
 * Returns the current queried term ID.
 */
function current_term_id(): int {
	if ( ! function_exists( 'get_queried_object' ) ) {
		return 0;
	}

	$term = get_queried_object();

	if ( ! is_object( $term ) || empty( $term->term_id ) ) {
		return 0;
	}

	return absint( $term->term_id );
}

/**
 * Reads a post type default theme.
 *
 * @param string $post_type Post type key.
 */
function post_type_default_theme( string $post_type ): string {
	if ( ! function_exists( 'get_option' ) ) {
		return '';
	}

	$settings = get_option( OPTION_NAME, default_settings() );

	if ( ! is_array( $settings ) || empty( $settings['post_type_defaults'][ $post_type ] ) ) {
		return '';
	}

	return (string) $settings['post_type_defaults'][ $post_type ];
}
