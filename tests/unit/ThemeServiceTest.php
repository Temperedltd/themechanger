<?php
/**
 * Theme service tests.
 *
 * @package TemperedThemeChanger
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( mixed $key ): string {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) ?? '' );
	}
}

if ( ! function_exists( 'is_multisite' ) ) {
	function is_multisite(): bool {
		return (bool) ( $GLOBALS['ttc_is_multisite'] ?? false );
	}
}

if ( ! function_exists( 'wp_get_themes' ) ) {
	function wp_get_themes( array $args = array() ): array {
		$GLOBALS['ttc_wp_get_themes_args'] = $args;

		return $GLOBALS['ttc_test_themes'] ?? array();
	}
}

final class ThemeServiceFakeTheme {
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

final class ThemeServiceTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['ttc_test_themes'] = array(
			'parent-theme' => new ThemeServiceFakeTheme( 'Parent Theme', 'parent-theme' ),
			'child-theme'  => new ThemeServiceFakeTheme( 'Child Theme', 'parent-theme' ),
		);
	}

	protected function tearDown(): void {
		unset( $GLOBALS['ttc_is_multisite'], $GLOBALS['ttc_test_themes'], $GLOBALS['ttc_wp_get_themes_args'] );
	}

	private function load_theme_service(): void {
		$theme_file = dirname( __DIR__, 2 ) . '/includes/themes.php';

		self::assertFileExists( $theme_file );

		require_once $theme_file;
	}

	public function test_validates_installed_theme_stylesheets(): void {
		$this->load_theme_service();

		self::assertTrue( TemperedThemeChanger\Themes\theme_exists( 'parent-theme' ) );
		self::assertTrue( TemperedThemeChanger\Themes\theme_exists( 'child-theme' ) );
		self::assertFalse( TemperedThemeChanger\Themes\theme_exists( 'missing-theme' ) );
	}

	public function test_resolves_child_theme_template_slug(): void {
		$this->load_theme_service();

		self::assertSame( 'parent-theme', TemperedThemeChanger\Themes\resolve_template( 'child-theme' ) );
		self::assertSame( 'parent-theme', TemperedThemeChanger\Themes\resolve_template( 'parent-theme' ) );
		self::assertSame( '', TemperedThemeChanger\Themes\resolve_template( 'missing-theme' ) );
	}

	public function test_normalizes_stylesheet_slugs(): void {
		$this->load_theme_service();

		self::assertSame( 'child-theme', TemperedThemeChanger\Themes\normalize_stylesheet( ' Child Theme! ' ) );
		self::assertSame( '', TemperedThemeChanger\Themes\normalize_stylesheet( array( 'child-theme' ) ) );
	}

	public function test_lists_only_allowed_themes_on_multisite(): void {
		$GLOBALS['ttc_is_multisite'] = true;

		$this->load_theme_service();

		TemperedThemeChanger\Themes\installed_themes();

		self::assertSame( array( 'allowed' => true ), $GLOBALS['ttc_wp_get_themes_args'] );
	}

	public function test_detects_invalid_stored_theme_selection(): void {
		$this->load_theme_service();

		self::assertFalse( TemperedThemeChanger\Themes\is_invalid_selection( '' ) );
		self::assertFalse( TemperedThemeChanger\Themes\is_invalid_selection( 'child-theme' ) );
		self::assertTrue( TemperedThemeChanger\Themes\is_invalid_selection( 'missing-theme' ) );
	}
}
