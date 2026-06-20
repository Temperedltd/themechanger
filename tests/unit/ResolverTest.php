<?php
/**
 * Resolver tests.
 *
 * @package TemperedThemeChanger
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

final class ResolverTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['ttc_test_themes'] = array(
			'parent-theme' => new ThemeServiceFakeTheme( 'Parent Theme', 'parent-theme' ),
			'child-theme'  => new ThemeServiceFakeTheme( 'Child Theme', 'parent-theme' ),
			'alt-theme'    => new ThemeServiceFakeTheme( 'Alt Theme', 'alt-theme' ),
		);
	}

	protected function tearDown(): void {
		unset( $GLOBALS['ttc_test_themes'] );
	}

	private function load_resolver(): void {
		$theme_file    = dirname( __DIR__, 2 ) . '/includes/themes.php';
		$storage_file  = dirname( __DIR__, 2 ) . '/includes/storage.php';
		$resolver_file = dirname( __DIR__, 2 ) . '/includes/resolver.php';

		self::assertFileExists( $theme_file );
		self::assertFileExists( $storage_file );
		self::assertFileExists( $resolver_file );

		require_once $theme_file;
		require_once $storage_file;
		require_once $resolver_file;
	}

	public function test_post_theme_takes_precedence_over_term_and_default(): void {
		$this->load_resolver();

		self::assertSame(
			array(
				'stylesheet' => 'child-theme',
				'template'   => 'parent-theme',
			),
			TemperedThemeChanger\Resolver\resolve_theme(
				array(
					'active_stylesheet'  => 'parent-theme',
					'active_template'    => 'parent-theme',
					'post_theme'         => 'child-theme',
					'term_theme'         => 'alt-theme',
					'post_type_default'  => 'alt-theme',
				)
			)
		);
	}

	public function test_term_theme_takes_precedence_over_post_type_default(): void {
		$this->load_resolver();

		self::assertSame(
			array(
				'stylesheet' => 'alt-theme',
				'template'   => 'alt-theme',
			),
			TemperedThemeChanger\Resolver\resolve_theme(
				array(
					'active_stylesheet' => 'parent-theme',
					'active_template'   => 'parent-theme',
					'term_theme'        => 'alt-theme',
					'post_type_default' => 'child-theme',
				)
			)
		);
	}

	public function test_post_type_default_falls_back_to_active_theme_when_invalid(): void {
		$this->load_resolver();

		self::assertSame(
			array(
				'stylesheet' => 'parent-theme',
				'template'   => 'parent-theme',
			),
			TemperedThemeChanger\Resolver\resolve_theme(
				array(
					'active_stylesheet' => 'parent-theme',
					'active_template'   => 'parent-theme',
					'post_theme'        => 'missing-theme',
					'term_theme'        => '',
					'post_type_default' => 'also-missing',
				)
			)
		);
	}
}
