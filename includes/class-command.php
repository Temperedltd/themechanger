<?php
/**
 * WP-CLI command.
 *
 * @package TemperedThemeChanger
 */

namespace TemperedThemeChanger\CLI;

use function TemperedThemeChanger\Storage\default_settings;
use function TemperedThemeChanger\Storage\sanitize_settings;
use function TemperedThemeChanger\Storage\sanitize_theme_allow_list;
use function TemperedThemeChanger\Themes\is_theme_switchable;
use function TemperedThemeChanger\Themes\normalize_stylesheet;
use function TemperedThemeChanger\Themes\theme_exists;
use const TemperedThemeChanger\Storage\META_KEY;
use const TemperedThemeChanger\Storage\OPTION_NAME;

defined( 'ABSPATH' ) || exit;

/**
 * Manages Theme Changer overrides and defaults.
 */
final class Command {
	/**
	 * Gets the selected theme for a post.
	 *
	 * @param string[]              $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function get( array $args, array $assoc_args ): void {
		unset( $assoc_args );

		$post_id = $this->post_id_arg( $args[0] ?? '' );

		$this->post_or_error( $post_id );

		$theme = function_exists( 'get_post_meta' ) ? (string) get_post_meta( $post_id, META_KEY, true ) : '';
		\WP_CLI::line( '' === $theme ? 'active-site-theme' : $theme );
	}

	/**
	 * Sets the selected theme for a post.
	 *
	 * @param string[]              $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function set( array $args, array $assoc_args ): void {
		unset( $assoc_args );

		$post_id = $this->post_id_arg( $args[0] ?? '' );
		$theme   = $this->switchable_theme_arg( $args[1] ?? '' );

		$this->post_or_error( $post_id );

		if ( function_exists( 'update_post_meta' ) ) {
			update_post_meta( $post_id, META_KEY, $theme );
		}

		\WP_CLI::success( sprintf( 'Set theme for post %d to %s.', $post_id, $theme ) );
	}

	/**
	 * Clears the selected theme for a post.
	 *
	 * @param string[]              $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function clear( array $args, array $assoc_args ): void {
		unset( $assoc_args );

		$post_id = $this->post_id_arg( $args[0] ?? '' );

		$this->post_or_error( $post_id );

		if ( function_exists( 'delete_post_meta' ) ) {
			delete_post_meta( $post_id, META_KEY );
		}

		\WP_CLI::success( sprintf( 'Cleared theme for post %d.', $post_id ) );
	}

	/**
	 * Gets the default theme for a post type.
	 *
	 * @param string[]              $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function get_default( array $args, array $assoc_args ): void {
		unset( $assoc_args );

		$post_type = $this->post_type_arg( $args[0] ?? '' );
		$settings  = $this->settings();
		$theme     = (string) ( $settings['post_type_defaults'][ $post_type ] ?? '' );

		\WP_CLI::line( '' === $theme ? 'active-site-theme' : $theme );
	}

	/**
	 * Sets the default theme for a post type.
	 *
	 * @param string[]              $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function set_default( array $args, array $assoc_args ): void {
		unset( $assoc_args );

		$post_type = $this->post_type_arg( $args[0] ?? '' );
		$theme     = $this->switchable_theme_arg( $args[1] ?? '' );
		$settings  = $this->settings();

		$settings['post_type_defaults'][ $post_type ] = $theme;
		$this->update_settings( $settings );

		\WP_CLI::success( sprintf( 'Set default theme for %s to %s.', $post_type, $theme ) );
	}

	/**
	 * Clears the default theme for a post type.
	 *
	 * @param string[]              $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function clear_default( array $args, array $assoc_args ): void {
		unset( $assoc_args );

		$post_type = $this->post_type_arg( $args[0] ?? '' );
		$settings  = $this->settings();

		unset( $settings['post_type_defaults'][ $post_type ] );
		$this->update_settings( $settings );

		\WP_CLI::success( sprintf( 'Cleared default theme for %s.', $post_type ) );
	}

	/**
	 * Gets the theme allow-list.
	 *
	 * @param string[]              $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function get_allow_list( array $args, array $assoc_args ): void {
		unset( $args, $assoc_args );

		$settings   = $this->settings();
		$allow_list = $settings['theme_allow_list'];

		if ( array() === $allow_list ) {
			\WP_CLI::line( 'all-themes' );
			return;
		}

		foreach ( $allow_list as $theme ) {
			\WP_CLI::line( $theme );
		}
	}

	/**
	 * Adds a theme to the allow-list.
	 *
	 * @param string[]              $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function add_allowed_theme( array $args, array $assoc_args ): void {
		unset( $assoc_args );

		$theme    = $this->theme_arg( $args[0] ?? '' );
		$settings = $this->settings();

		$settings['theme_allow_list'][] = $theme;
		$settings['theme_allow_list']   = sanitize_theme_allow_list( $settings['theme_allow_list'] );
		$this->update_settings( $settings );

		\WP_CLI::success( sprintf( 'Added %s to the theme allow-list.', $theme ) );
	}

	/**
	 * Removes a theme from the allow-list.
	 *
	 * @param string[]              $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function remove_allowed_theme( array $args, array $assoc_args ): void {
		unset( $assoc_args );

		$theme    = $this->theme_arg( $args[0] ?? '' );
		$settings = $this->settings();

		$settings['theme_allow_list'] = array_values(
			array_filter(
				$settings['theme_allow_list'],
				static fn ( string $allowed_theme ): bool => $theme !== $allowed_theme
			)
		);
		$settings['theme_allow_list'] = sanitize_theme_allow_list( $settings['theme_allow_list'] );
		$this->update_settings( $settings );

		\WP_CLI::success( sprintf( 'Removed %s from the theme allow-list.', $theme ) );
	}

	/**
	 * Returns a valid post ID argument.
	 *
	 * @param string $value Raw argument.
	 */
	private function post_id_arg( string $value ): int {
		$post_id = absint( $value );

		if ( 0 === $post_id ) {
			\WP_CLI::error( 'Post ID must be a positive integer.' );
		}

		return $post_id;
	}

	/**
	 * Returns a valid theme stylesheet argument.
	 *
	 * @param string $value Raw argument.
	 */
	private function theme_arg( string $value ): string {
		$theme = normalize_stylesheet( $value );

		if ( '' === $theme || ! theme_exists( $theme ) ) {
			\WP_CLI::error( sprintf( 'Theme not found: %s', $value ) );
		}

		return $theme;
	}

	/**
	 * Returns a valid switchable theme stylesheet argument.
	 *
	 * @param string $value Raw argument.
	 */
	private function switchable_theme_arg( string $value ): string {
		$theme = $this->theme_arg( $value );

		if ( ! is_theme_switchable( $theme ) ) {
			\WP_CLI::error( sprintf( 'Theme not allowed: %s', $theme ) );
		}

		return $theme;
	}

	/**
	 * Returns a valid post type argument.
	 *
	 * @param string $value Raw argument.
	 */
	private function post_type_arg( string $value ): string {
		$post_type = sanitize_key( $value );

		if ( ! $this->is_defaultable_post_type( $post_type ) ) {
			\WP_CLI::error( sprintf( 'Post type not supported: %s', $value ) );
		}

		return $post_type;
	}

	/**
	 * Returns a post object or raises a command error.
	 *
	 * @param int $post_id Post ID.
	 */
	private function post_or_error( int $post_id ): object {
		$post = function_exists( 'get_post' ) ? get_post( $post_id ) : null;

		if ( ! is_object( $post ) ) {
			\WP_CLI::error( sprintf( 'Post not found: %d', $post_id ) );
		}

		return $post;
	}

	/**
	 * Checks whether a post type supports defaults.
	 *
	 * @param string $post_type Post type key.
	 */
	private function is_defaultable_post_type( string $post_type ): bool {
		if ( '' === $post_type || in_array( $post_type, array( 'attachment', 'revision', 'nav_menu_item' ), true ) ) {
			return false;
		}

		$post_type_object = function_exists( 'get_post_type_object' ) ? get_post_type_object( $post_type ) : null;

		return is_object( $post_type_object ) && ! empty( $post_type_object->public );
	}

	/**
	 * Returns stored settings.
	 *
	 * @return array{post_type_defaults: array<string, string>, theme_allow_list: string[]}
	 */
	private function settings(): array {
		$settings = function_exists( 'get_option' ) ? get_option( OPTION_NAME, default_settings() ) : default_settings();

		if ( ! is_array( $settings ) ) {
			return default_settings();
		}

		return sanitize_settings( $settings );
	}

	/**
	 * Persists settings.
	 *
	 * @param array{post_type_defaults: array<string, string>, theme_allow_list: string[]} $settings Settings.
	 */
	private function update_settings( array $settings ): void {
		if ( function_exists( 'update_option' ) ) {
			update_option( OPTION_NAME, sanitize_settings( $settings ) );
		}
	}
}
