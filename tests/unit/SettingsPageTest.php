<?php
/**
 * Settings page tests.
 *
 * @package TemperedThemeChanger
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'add_theme_page' ) ) {
	function add_theme_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback ): string {
		$GLOBALS['ttc_add_theme_page_call'] = compact( 'page_title', 'menu_title', 'capability', 'menu_slug', 'callback' );

		return 'appearance_page_' . $menu_slug;
	}
}

if ( ! function_exists( 'add_options_page' ) ) {
	function add_options_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback ): string {
		$GLOBALS['ttc_add_options_page_call'] = compact( 'page_title', 'menu_title', 'capability', 'menu_slug', 'callback' );

		return 'settings_page_' . $menu_slug;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( string $capability, mixed ...$args ): bool {
		$GLOBALS['ttc_current_user_can_calls'][] = array_merge( array( $capability ), $args );

		return (bool) ( $GLOBALS['ttc_current_user_can_result'] ?? false );
	}
}

final class SettingsPageTest extends TestCase {
	protected function tearDown(): void {
		unset(
			$GLOBALS['ttc_add_theme_page_call'],
			$GLOBALS['ttc_add_options_page_call'],
			$GLOBALS['ttc_current_user_can_calls'],
			$GLOBALS['ttc_current_user_can_result']
		);
	}

	private function load_settings_page(): void {
		$settings_page_file = dirname( __DIR__, 2 ) . '/includes/admin/settings-page.php';

		self::assertFileExists( $settings_page_file );

		require_once $settings_page_file;
	}

	public function test_filters_defaultable_post_type_names(): void {
		$this->load_settings_page();

		self::assertSame(
			array( 'post', 'page', 'product' ),
			TemperedThemeChanger\Admin\SettingsPage\filter_defaultable_post_type_names(
				array( 'post', 'page', 'attachment', 'revision', 'product' )
			)
		);
	}

	public function test_registers_settings_page_under_appearance_with_switch_themes_capability(): void {
		$this->load_settings_page();

		TemperedThemeChanger\Admin\SettingsPage\add_settings_page();

		self::assertArrayNotHasKey( 'ttc_add_options_page_call', $GLOBALS );
		self::assertSame( 'Theme Changer', $GLOBALS['ttc_add_theme_page_call']['page_title'] );
		self::assertSame( 'Theme Changer', $GLOBALS['ttc_add_theme_page_call']['menu_title'] );
		self::assertSame( 'switch_themes', $GLOBALS['ttc_add_theme_page_call']['capability'] );
		self::assertSame( 'tempered-themechanger', $GLOBALS['ttc_add_theme_page_call']['menu_slug'] );
	}

	public function test_options_page_capability_is_switch_themes(): void {
		$this->load_settings_page();

		self::assertSame( 'switch_themes', TemperedThemeChanger\Admin\SettingsPage\settings_capability() );
	}

	public function test_render_settings_page_checks_switch_themes_capability(): void {
		$GLOBALS['ttc_current_user_can_result'] = false;

		$this->load_settings_page();

		ob_start();
		TemperedThemeChanger\Admin\SettingsPage\render_settings_page();
		$output = ob_get_clean();

		self::assertSame( '', $output );
		self::assertSame(
			array(
				array( 'switch_themes' ),
			),
			$GLOBALS['ttc_current_user_can_calls']
		);
	}
}
