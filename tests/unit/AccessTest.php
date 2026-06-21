<?php
/**
 * Access helper tests.
 *
 * @package TemperedThemeChanger
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

final class AccessTest extends TestCase {
	protected function tearDown(): void {
		unset(
			$GLOBALS['ttc_current_user_id'],
			$GLOBALS['ttc_user_can_calls'],
			$GLOBALS['ttc_user_can_results'],
				$GLOBALS['ttc_apply_filters_calls'],
				$GLOBALS['ttc_apply_filters_results']
			);
	}

	private function load_access(): void {
		$access_file = dirname( __DIR__, 2 ) . '/includes/access.php';

		self::assertFileExists( $access_file );

		require_once $access_file;
	}

	public function test_user_with_post_edit_and_theme_changer_capability_can_change_post_theme(): void {
		$GLOBALS['ttc_user_can_results'] = array(
			'edit_post'         => true,
			'use_theme_changer' => true,
		);

		$this->load_access();

		self::assertTrue( TemperedThemeChanger\Access\can_change_post_theme( 123, 7 ) );
		self::assertSame(
			array(
				array( 7, 'edit_post', 123 ),
				array( 7, 'switch_themes' ),
				array( 7, 'use_theme_changer' ),
			),
			$GLOBALS['ttc_user_can_calls']
		);
	}

	public function test_user_with_post_edit_and_switch_themes_capability_can_change_post_theme(): void {
		$GLOBALS['ttc_user_can_results'] = array(
			'edit_post'      => true,
			'switch_themes'  => true,
		);

		$this->load_access();

		self::assertTrue( TemperedThemeChanger\Access\can_change_post_theme( 123, 7 ) );
		self::assertSame(
			array(
				array( 7, 'edit_post', 123 ),
				array( 7, 'switch_themes' ),
			),
			$GLOBALS['ttc_user_can_calls']
		);
	}

	public function test_user_without_theme_changer_or_switch_themes_capability_cannot_change_post_theme(): void {
		$GLOBALS['ttc_user_can_results'] = array(
			'edit_post' => true,
		);

		$this->load_access();

		self::assertFalse( TemperedThemeChanger\Access\can_change_post_theme( 123, 7 ) );
	}

	public function test_user_without_post_edit_capability_cannot_change_post_theme(): void {
		$GLOBALS['ttc_user_can_results'] = array(
			'edit_post'         => false,
			'use_theme_changer' => true,
		);

		$this->load_access();

		self::assertFalse( TemperedThemeChanger\Access\can_change_post_theme( 123, 7 ) );
	}

	public function test_filter_can_override_post_theme_access_decision(): void {
		$GLOBALS['ttc_user_can_results'] = array(
			'edit_post'         => true,
			'use_theme_changer' => true,
		);
		$GLOBALS['ttc_apply_filters_results'] = array(
			'tempered_themechanger_user_can_change_post_theme' => false,
		);

		$this->load_access();

		self::assertFalse( TemperedThemeChanger\Access\can_change_post_theme( 123, 7 ) );
		self::assertSame(
			array(
				array( 'tempered_themechanger_user_can_change_post_theme', true, 123, 7 ),
			),
				$GLOBALS['ttc_apply_filters_calls']
			);
	}

	public function test_filter_cannot_grant_access_when_baseline_capabilities_fail(): void {
		$GLOBALS['ttc_user_can_results'] = array(
			'edit_post' => true,
		);
		$GLOBALS['ttc_apply_filters_results'] = array(
			'tempered_themechanger_user_can_change_post_theme' => true,
		);

		$this->load_access();

		self::assertFalse( TemperedThemeChanger\Access\can_change_post_theme( 123, 7 ) );
		self::assertSame(
			array(
				array( 'tempered_themechanger_user_can_change_post_theme', false, 123, 7 ),
			),
			$GLOBALS['ttc_apply_filters_calls']
		);
	}
}
