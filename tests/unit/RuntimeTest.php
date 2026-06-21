<?php
/**
 * Runtime tests.
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

final class RuntimeTest extends TestCase {
	protected function tearDown(): void {
		unset(
			$GLOBALS['ttc_current_user_can_calls'],
			$GLOBALS['ttc_current_user_can_result'],
			$GLOBALS['tempered_themechanger_runtime_cache'],
			$_GET,
			$_POST
		);
	}

	private function load_runtime(): void {
		$runtime_file = dirname( __DIR__, 2 ) . '/includes/runtime.php';

		self::assertFileExists( $runtime_file );

		require_once $runtime_file;
	}

	public function test_allows_front_end_preview_rest_and_editor_contexts(): void {
		$this->load_runtime();

		self::assertTrue( TemperedThemeChanger\Runtime\is_allowed_context( array( 'is_front_end' => true ) ) );
		self::assertTrue( TemperedThemeChanger\Runtime\is_allowed_context( array( 'is_preview' => true ) ) );
		self::assertTrue( TemperedThemeChanger\Runtime\is_allowed_context( array( 'is_rest' => true ) ) );
		self::assertTrue( TemperedThemeChanger\Runtime\is_allowed_context( array( 'admin_page' => 'post.php' ) ) );
	}

	public function test_blocks_ajax_cron_login_and_unrelated_admin_contexts(): void {
		$this->load_runtime();

		self::assertFalse( TemperedThemeChanger\Runtime\is_allowed_context( array( 'doing_ajax' => true ) ) );
		self::assertFalse( TemperedThemeChanger\Runtime\is_allowed_context( array( 'doing_cron' => true ) ) );
		self::assertFalse( TemperedThemeChanger\Runtime\is_allowed_context( array( 'is_login' => true ) ) );
		self::assertFalse( TemperedThemeChanger\Runtime\is_allowed_context( array( 'is_cli' => true ) ) );
		self::assertFalse( TemperedThemeChanger\Runtime\is_allowed_context( array( 'admin_page' => 'plugins.php' ) ) );
	}

	public function test_current_context_flags_short_circuits_wp_cli(): void {
		if ( ! defined( 'WP_CLI' ) ) {
			define( 'WP_CLI', true );
		}

		$this->load_runtime();

		self::assertSame(
			array( 'is_cli' => true ),
			TemperedThemeChanger\Runtime\current_context_flags()
		);
	}

	public function test_query_conditionals_are_unavailable_without_wordpress_query_action(): void {
		$this->load_runtime();

		self::assertFalse( TemperedThemeChanger\Runtime\query_conditionals_are_available() );
	}

	public function test_front_end_request_ids_only_use_canonical_query_vars(): void {
		$this->load_runtime();

		self::assertSame(
			0,
			TemperedThemeChanger\Runtime\resolve_context_post_id(
				array(
					'post' => '123',
				),
				array( 'is_front_end' => true )
			)
		);
		self::assertSame(
			123,
			TemperedThemeChanger\Runtime\resolve_context_post_id(
				array(
					'p' => '123',
				),
				array( 'is_front_end' => true )
			)
		);
	}

	public function test_rest_and_admin_request_ids_require_post_edit_capability(): void {
		$this->load_runtime();

		$GLOBALS['ttc_current_user_can_result'] = false;

		self::assertSame(
			0,
			TemperedThemeChanger\Runtime\resolve_context_post_id(
				array(
					'post' => '123',
				),
				array( 'is_rest' => true )
			)
		);

		$GLOBALS['ttc_current_user_can_result'] = true;

		self::assertSame(
			123,
			TemperedThemeChanger\Runtime\resolve_context_post_id(
				array(
					'post' => '123',
				),
				array( 'admin_page' => 'post.php' )
			)
		);
		self::assertSame(
			array(
				array( 'edit_post', 123 ),
				array( 'edit_post', 123 ),
			),
			$GLOBALS['ttc_current_user_can_calls']
		);
	}

	public function test_resolution_cache_key_ignores_unrelated_request_values(): void {
		$this->load_runtime();

		$_GET = array(
			'post'    => '123',
			'content' => str_repeat( 'a', 1000 ),
		);
		$first_key = TemperedThemeChanger\Runtime\current_resolution_cache_key();

		$_GET = array(
			'post'    => '123',
			'content' => str_repeat( 'b', 1000 ),
		);

		self::assertSame( $first_key, TemperedThemeChanger\Runtime\current_resolution_cache_key() );
	}
}
