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

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( string $text, string $domain = 'default' ): string {
		unset( $domain );

		return $text;
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( string $text, string $domain = 'default' ): void {
		unset( $domain );

		echo $text;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( string $text ): string {
		return $text;
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( string $text ): string {
		return $text;
	}
}

if ( ! function_exists( 'settings_fields' ) ) {
	function settings_fields( string $option_group ): void {
		echo '<input type="hidden" name="option_page" value="' . esc_attr( $option_group ) . '">';
	}
}

if ( ! function_exists( 'submit_button' ) ) {
	function submit_button(): void {
		echo '<button type="submit">Save Changes</button>';
	}
}

if ( ! function_exists( 'selected' ) ) {
	function selected( mixed $selected, mixed $current = true, bool $display = true ): string {
		$result = (string) $selected === (string) $current ? ' selected="selected"' : '';

		if ( $display ) {
			echo $result;
		}

		return $result;
	}
}

if ( ! function_exists( 'checked' ) ) {
	function checked( mixed $checked, mixed $current = true, bool $display = true ): string {
		$result = (string) $checked === (string) $current ? ' checked="checked"' : '';

		if ( $display ) {
			echo $result;
		}

		return $result;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( string $capability, mixed ...$args ): bool {
		$GLOBALS['ttc_current_user_can_calls'][] = array_merge( array( $capability ), $args );

		return (bool) ( $GLOBALS['ttc_current_user_can_result'] ?? false );
	}
}

if ( ! function_exists( 'get_post_types' ) ) {
	function get_post_types( array $args = array(), string $output = 'names' ): array {
		unset( $args, $output );

		return array( 'post', 'page' );
	}
}

if ( ! function_exists( 'get_post_type_object' ) ) {
	function get_post_type_object( string $post_type ): ?object {
		return $GLOBALS['ttc_test_post_type_objects'][ $post_type ] ?? null;
	}
}

final class SettingsPageFakeTheme {
	public function __construct(
		private readonly string $name,
		private readonly string $template
	) {
	}

	public function get( string $header ): string {
		return 'Name' === $header ? $this->name : '';
	}

	public function get_template(): string {
		return $this->template;
	}
}

final class SettingsPageTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['ttc_test_themes'] = array(
			'parent-theme' => new SettingsPageFakeTheme( 'Parent Theme', 'parent-theme' ),
			'child-theme'  => new SettingsPageFakeTheme( 'Child Theme', 'parent-theme' ),
		);
		$GLOBALS['ttc_test_post_type_objects'] = array(
			'post' => (object) array(
				'labels' => (object) array(
					'singular_name' => 'Post',
				),
			),
			'page' => (object) array(
				'labels' => (object) array(
					'singular_name' => 'Page',
				),
			),
		);
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['ttc_add_theme_page_call'],
			$GLOBALS['ttc_add_options_page_call'],
			$GLOBALS['ttc_current_user_can_calls'],
			$GLOBALS['ttc_current_user_can_result'],
			$GLOBALS['ttc_test_options'],
			$GLOBALS['ttc_test_roles'],
			$GLOBALS['ttc_test_role_objects'],
			$GLOBALS['ttc_test_themes'],
			$GLOBALS['ttc_test_post_type_objects'],
			$GLOBALS['tempered_themechanger_theme_cache']
		);
	}

	private function load_settings_page(): void {
		$theme_file         = dirname( __DIR__, 2 ) . '/includes/themes.php';
		$storage_file       = dirname( __DIR__, 2 ) . '/includes/storage.php';
		$settings_page_file = dirname( __DIR__, 2 ) . '/includes/admin/settings-page.php';

		self::assertFileExists( $theme_file );
		self::assertFileExists( $storage_file );
		self::assertFileExists( $settings_page_file );

		require_once $theme_file;
		require_once $storage_file;
		require_once $settings_page_file;

		TemperedThemeChanger\Themes\clear_cache();
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

	public function test_render_settings_page_includes_theme_allow_list_controls(): void {
		$GLOBALS['ttc_current_user_can_result'] = true;
		$GLOBALS['ttc_test_options']['tempered_themechanger_settings'] = array(
			'post_type_defaults' => array(),
			'theme_allow_list'   => array( 'child-theme' ),
		);

		$this->load_settings_page();

		ob_start();
		TemperedThemeChanger\Admin\SettingsPage\render_settings_page();
		$output = ob_get_clean();

		self::assertStringContainsString( 'tempered_themechanger_settings[theme_allow_list][]', $output );
		self::assertStringContainsString( 'Parent Theme', $output );
		self::assertStringContainsString( 'Child Theme', $output );
		self::assertStringContainsString( 'Select no themes to allow every installed and allowed theme.', $output );
	}

	public function test_render_settings_page_includes_role_capability_controls(): void {
		$GLOBALS['ttc_current_user_can_result'] = true;
		$GLOBALS['ttc_test_roles']              = array(
			'administrator' => array(
				'name'         => 'Administrator',
				'capabilities' => array(
					'switch_themes' => true,
				),
			),
			'editor'        => array(
				'name'         => 'Editor',
				'capabilities' => array(
					'use_theme_changer' => true,
				),
			),
			'author'        => array(
				'name'         => 'Author',
				'capabilities' => array(),
			),
		);

		$this->load_settings_page();

		ob_start();
		TemperedThemeChanger\Admin\SettingsPage\render_settings_page();
		$output = ob_get_clean();

		self::assertStringContainsString( 'Role access', $output );
		self::assertStringContainsString( 'tempered_themechanger_settings[role_capabilities_submitted]', $output );
		self::assertStringContainsString( 'tempered_themechanger_settings[role_capabilities][]', $output );
		self::assertStringContainsString( 'value="editor" checked="checked"', $output );
		self::assertStringContainsString( 'value="author"', $output );
		self::assertStringContainsString( 'Administrator', $output );
		self::assertStringContainsString( 'Already has switch_themes and can use Theme Changer.', $output );
		self::assertStringNotContainsString( 'value="administrator"', $output );
	}
}
