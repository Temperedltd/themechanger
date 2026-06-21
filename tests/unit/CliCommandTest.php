<?php
/**
 * WP-CLI command tests.
 *
 * @package TemperedThemeChanger
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

if ( ! defined( 'WP_CLI' ) ) {
	define( 'WP_CLI', true );
}

if ( ! class_exists( 'WP_CLI' ) ) {
	final class WP_CLI {
		/**
		 * @var array<string, mixed>
		 */
		public static array $commands = array();

		/**
		 * @var string[]
		 */
		public static array $lines = array();

		/**
		 * @var string[]
		 */
		public static array $successes = array();

		/**
		 * Registers a WP-CLI command.
		 *
		 * @param string $name     Command name.
		 * @param mixed  $callable Command callable.
		 */
		public static function add_command( string $name, mixed $callable ): void {
			self::$commands[ $name ] = $callable;
		}

		/**
		 * Captures normal command output.
		 *
		 * @param string $message Message.
		 */
		public static function line( string $message ): void {
			self::$lines[] = $message;
		}

		/**
		 * Captures success output.
		 *
		 * @param string $message Message.
		 */
		public static function success( string $message ): void {
			self::$successes[] = $message;
		}

		/**
		 * Raises a command error.
		 *
		 * @param string $message Message.
		 */
		public static function error( string $message ): never {
			throw new RuntimeException( $message );
		}
	}
}

final class CliCommandFakeTheme {
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

if ( ! function_exists( 'get_post' ) ) {
	function get_post( int $post_id ): ?object {
		return $GLOBALS['ttc_test_posts'][ $post_id ] ?? null;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( int $post_id, string $meta_key, bool $single ): string {
		return (string) ( $GLOBALS['ttc_test_post_meta'][ $post_id ][ $meta_key ] ?? '' );
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( int $post_id, string $meta_key, string $meta_value ): bool {
		$GLOBALS['ttc_update_post_meta_calls'][]       = array( $post_id, $meta_key, $meta_value );
		$GLOBALS['ttc_test_post_meta'][ $post_id ][ $meta_key ] = $meta_value;

		return true;
	}
}

if ( ! function_exists( 'delete_post_meta' ) ) {
	function delete_post_meta( int $post_id, string $meta_key ): bool {
		$GLOBALS['ttc_delete_post_meta_calls'][] = array( $post_id, $meta_key );
		unset( $GLOBALS['ttc_test_post_meta'][ $post_id ][ $meta_key ] );

		return true;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $option_name, mixed $default = false ): mixed {
		return $GLOBALS['ttc_test_options'][ $option_name ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $option_name, mixed $value ): bool {
		$GLOBALS['ttc_update_option_calls'][]       = array( $option_name, $value );
		$GLOBALS['ttc_test_options'][ $option_name ] = $value;

		return true;
	}
}

if ( ! function_exists( 'get_post_type_object' ) ) {
	function get_post_type_object( string $post_type ): ?object {
		return $GLOBALS['ttc_test_post_type_objects'][ $post_type ] ?? null;
	}
}

final class CliCommandTest extends TestCase {
	protected function setUp(): void {
		WP_CLI::$commands  = array();
		WP_CLI::$lines     = array();
		WP_CLI::$successes = array();

		$GLOBALS['ttc_test_themes'] = array(
			'parent-theme' => new CliCommandFakeTheme( 'Parent Theme', 'parent-theme' ),
			'child-theme'  => new CliCommandFakeTheme( 'Child Theme', 'parent-theme' ),
		);
		$GLOBALS['ttc_test_posts']  = array(
			123 => (object) array(
				'ID'        => 123,
				'post_type' => 'page',
			),
		);
		$GLOBALS['ttc_test_post_type_objects'] = array(
			'post' => (object) array(
				'public' => true,
			),
			'page' => (object) array(
				'public' => true,
			),
		);
		$GLOBALS['ttc_test_options']           = array();
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['ttc_test_themes'],
			$GLOBALS['ttc_test_posts'],
			$GLOBALS['ttc_test_post_meta'],
			$GLOBALS['ttc_update_post_meta_calls'],
			$GLOBALS['ttc_delete_post_meta_calls'],
			$GLOBALS['ttc_test_options'],
			$GLOBALS['ttc_update_option_calls'],
			$GLOBALS['ttc_test_post_type_objects'],
			$GLOBALS['tempered_themechanger_theme_cache']
		);
	}

	private function load_cli(): void {
		$theme_file   = dirname( __DIR__, 2 ) . '/includes/themes.php';
		$storage_file = dirname( __DIR__, 2 ) . '/includes/storage.php';
		$cli_file     = dirname( __DIR__, 2 ) . '/includes/cli.php';

		self::assertFileExists( $theme_file );
		self::assertFileExists( $storage_file );
		self::assertFileExists( $cli_file );

		require_once $theme_file;
		require_once $storage_file;
		require_once $cli_file;

		TemperedThemeChanger\Themes\clear_cache();
	}

	public function test_bootstrap_registers_theme_changer_command(): void {
		$this->load_cli();

		TemperedThemeChanger\CLI\bootstrap();

		self::assertArrayHasKey( 'theme-changer', WP_CLI::$commands );
		self::assertArrayHasKey( 'theme-changer get-default', WP_CLI::$commands );
		self::assertArrayHasKey( 'theme-changer set-default', WP_CLI::$commands );
		self::assertArrayHasKey( 'theme-changer clear-default', WP_CLI::$commands );
		self::assertArrayHasKey( 'theme-changer get-allow-list', WP_CLI::$commands );
		self::assertArrayHasKey( 'theme-changer add-allowed-theme', WP_CLI::$commands );
		self::assertArrayHasKey( 'theme-changer remove-allowed-theme', WP_CLI::$commands );
	}

	public function test_sets_theme_for_post_id(): void {
		$this->load_cli();

		$command = new TemperedThemeChanger\CLI\Command();
		$command->set( array( '123', 'child-theme' ), array() );

		self::assertSame(
			array(
				array( 123, '_tempered_themechanger_theme', 'child-theme' ),
			),
			$GLOBALS['ttc_update_post_meta_calls']
		);
		self::assertSame( array( 'Set theme for post 123 to child-theme.' ), WP_CLI::$successes );
	}

	public function test_set_rejects_missing_theme(): void {
		$this->load_cli();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Theme not found: missing-theme' );

		$command = new TemperedThemeChanger\CLI\Command();
		$command->set( array( '123', 'missing-theme' ), array() );
	}

	public function test_set_rejects_disallowed_theme(): void {
		$GLOBALS['ttc_test_options']['tempered_themechanger_settings'] = array(
			'post_type_defaults' => array(),
			'theme_allow_list'   => array( 'parent-theme' ),
		);

		$this->load_cli();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Theme not allowed: child-theme' );

		$command = new TemperedThemeChanger\CLI\Command();
		$command->set( array( '123', 'child-theme' ), array() );
	}

	public function test_clears_theme_for_post_id(): void {
		$GLOBALS['ttc_test_post_meta'][123]['_tempered_themechanger_theme'] = 'child-theme';

		$this->load_cli();

		$command = new TemperedThemeChanger\CLI\Command();
		$command->clear( array( '123' ), array() );

		self::assertSame(
			array(
				array( 123, '_tempered_themechanger_theme' ),
			),
			$GLOBALS['ttc_delete_post_meta_calls']
		);
		self::assertSame( array( 'Cleared theme for post 123.' ), WP_CLI::$successes );
	}

	public function test_get_outputs_post_theme_or_active_site_theme(): void {
		$GLOBALS['ttc_test_post_meta'][123]['_tempered_themechanger_theme'] = 'child-theme';

		$this->load_cli();

		$command = new TemperedThemeChanger\CLI\Command();
		$command->get( array( '123' ), array() );

		self::assertSame( array( 'child-theme' ), WP_CLI::$lines );
	}

	public function test_sets_post_type_default_theme(): void {
		$this->load_cli();

		$command = new TemperedThemeChanger\CLI\Command();
		$command->set_default( array( 'post', 'child-theme' ), array() );

		self::assertSame(
			array(
				array(
					'tempered_themechanger_settings',
					array(
						'post_type_defaults' => array(
							'post' => 'child-theme',
						),
						'theme_allow_list'   => array(),
					),
				),
			),
			$GLOBALS['ttc_update_option_calls']
		);
		self::assertSame( array( 'Set default theme for post to child-theme.' ), WP_CLI::$successes );
	}

	public function test_set_default_rejects_disallowed_theme(): void {
		$GLOBALS['ttc_test_options']['tempered_themechanger_settings'] = array(
			'post_type_defaults' => array(),
			'theme_allow_list'   => array( 'parent-theme' ),
		);

		$this->load_cli();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Theme not allowed: child-theme' );

		$command = new TemperedThemeChanger\CLI\Command();
		$command->set_default( array( 'post', 'child-theme' ), array() );
	}

	public function test_clears_post_type_default_theme(): void {
		$GLOBALS['ttc_test_options']['tempered_themechanger_settings'] = array(
			'post_type_defaults' => array(
				'post' => 'child-theme',
				'page' => 'parent-theme',
			),
		);

		$this->load_cli();

		$command = new TemperedThemeChanger\CLI\Command();
		$command->clear_default( array( 'post' ), array() );

		self::assertSame(
			array(
				array(
					'tempered_themechanger_settings',
					array(
						'post_type_defaults' => array(
							'page' => 'parent-theme',
						),
						'theme_allow_list'   => array(),
					),
				),
			),
			$GLOBALS['ttc_update_option_calls']
		);
		self::assertSame( array( 'Cleared default theme for post.' ), WP_CLI::$successes );
	}

	public function test_get_allow_list_outputs_all_themes_when_list_is_empty(): void {
		$this->load_cli();

		$command = new TemperedThemeChanger\CLI\Command();

		self::assertTrue( method_exists( $command, 'get_allow_list' ) );

		$command->get_allow_list( array(), array() );

		self::assertSame( array( 'all-themes' ), WP_CLI::$lines );
	}

	public function test_get_allow_list_outputs_allowed_theme_stylesheets(): void {
		$GLOBALS['ttc_test_options']['tempered_themechanger_settings'] = array(
			'post_type_defaults' => array(),
			'theme_allow_list'   => array( 'child-theme', 'parent-theme' ),
		);

		$this->load_cli();

		$command = new TemperedThemeChanger\CLI\Command();

		self::assertTrue( method_exists( $command, 'get_allow_list' ) );

		$command->get_allow_list( array(), array() );

		self::assertSame( array( 'child-theme', 'parent-theme' ), WP_CLI::$lines );
	}

	public function test_add_allowed_theme_updates_allow_list_and_preserves_defaults(): void {
		$GLOBALS['ttc_test_options']['tempered_themechanger_settings'] = array(
			'post_type_defaults' => array(
				'post' => 'parent-theme',
			),
			'theme_allow_list'   => array( 'parent-theme' ),
		);

		$this->load_cli();

		$command = new TemperedThemeChanger\CLI\Command();

		self::assertTrue( method_exists( $command, 'add_allowed_theme' ) );

		$command->add_allowed_theme( array( 'child-theme' ), array() );

		self::assertSame(
			array(
				array(
					'tempered_themechanger_settings',
					array(
						'post_type_defaults' => array(
							'post' => 'parent-theme',
						),
						'theme_allow_list'   => array( 'child-theme', 'parent-theme' ),
					),
				),
			),
			$GLOBALS['ttc_update_option_calls']
		);
		self::assertSame( array( 'Added child-theme to the theme allow-list.' ), WP_CLI::$successes );
	}

	public function test_remove_allowed_theme_updates_allow_list(): void {
		$GLOBALS['ttc_test_options']['tempered_themechanger_settings'] = array(
			'post_type_defaults' => array(),
			'theme_allow_list'   => array( 'child-theme' ),
		);

		$this->load_cli();

		$command = new TemperedThemeChanger\CLI\Command();

		self::assertTrue( method_exists( $command, 'remove_allowed_theme' ) );

		$command->remove_allowed_theme( array( 'child-theme' ), array() );

		self::assertSame(
			array(
				array(
					'tempered_themechanger_settings',
					array(
						'post_type_defaults' => array(),
						'theme_allow_list'   => array(),
					),
				),
			),
			$GLOBALS['ttc_update_option_calls']
		);
		self::assertSame( array( 'Removed child-theme from the theme allow-list.' ), WP_CLI::$successes );
	}
}
