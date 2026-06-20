<?php
/**
 * Storage service tests.
 *
 * @package TemperedThemeChanger
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( string $capability, mixed ...$args ): bool {
		$GLOBALS['ttc_current_user_can_calls'][] = array_merge( array( $capability ), $args );

		return (bool) ( $GLOBALS['ttc_current_user_can_result'] ?? false );
	}
}

final class StorageServiceTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['ttc_test_themes'] = array(
			'parent-theme' => new ThemeServiceFakeTheme( 'Parent Theme', 'parent-theme' ),
			'child-theme'  => new ThemeServiceFakeTheme( 'Child Theme', 'parent-theme' ),
		);
	}

	protected function tearDown(): void {
		unset( $GLOBALS['ttc_current_user_can_calls'], $GLOBALS['ttc_current_user_can_result'], $GLOBALS['ttc_test_themes'] );
	}

	private function load_storage_service(): void {
		$theme_file   = dirname( __DIR__, 2 ) . '/includes/themes.php';
		$storage_file = dirname( __DIR__, 2 ) . '/includes/storage.php';

		self::assertFileExists( $theme_file );
		self::assertFileExists( $storage_file );

		require_once $theme_file;
		require_once $storage_file;
	}

	public function test_sanitizes_theme_slug_to_installed_theme_or_empty_string(): void {
		$this->load_storage_service();

		self::assertSame( 'child-theme', TemperedThemeChanger\Storage\sanitize_theme_slug( ' child-theme ' ) );
		self::assertSame( '', TemperedThemeChanger\Storage\sanitize_theme_slug( 'missing-theme' ) );
		self::assertSame( '', TemperedThemeChanger\Storage\sanitize_theme_slug( array( 'child-theme' ) ) );
	}

	public function test_sanitizes_post_type_default_settings(): void {
		$this->load_storage_service();

		self::assertSame(
			array(
				'post_type_defaults' => array(
					'post' => 'child-theme',
					'page' => '',
				),
			),
			TemperedThemeChanger\Storage\sanitize_settings(
				array(
					'post_type_defaults' => array(
						'post'     => 'child-theme',
						'page'     => 'missing-theme',
						'bad type' => 'parent-theme',
					),
				)
			)
		);
	}

	public function test_sanitizes_non_array_settings_to_empty_defaults(): void {
		$this->load_storage_service();

		self::assertSame(
			array(
				'post_type_defaults' => array(),
			),
			TemperedThemeChanger\Storage\sanitize_settings( 'not-settings' )
		);
	}

	public function test_post_meta_auth_checks_the_specific_post(): void {
		$GLOBALS['ttc_current_user_can_result'] = true;

		$this->load_storage_service();

		self::assertTrue( TemperedThemeChanger\Storage\can_edit_post_meta( true, '_tempered_themechanger_theme', 123 ) );
		self::assertSame(
			array(
				array( 'edit_post', 123 ),
			),
			$GLOBALS['ttc_current_user_can_calls']
		);
	}

	public function test_term_meta_auth_checks_the_specific_term(): void {
		$GLOBALS['ttc_current_user_can_result'] = true;

		$this->load_storage_service();

		self::assertTrue( TemperedThemeChanger\Storage\can_edit_term_meta( true, '_tempered_themechanger_theme', 456 ) );
		self::assertSame(
			array(
				array( 'edit_term', 456 ),
			),
			$GLOBALS['ttc_current_user_can_calls']
		);
	}
}
