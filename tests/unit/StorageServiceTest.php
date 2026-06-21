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

final class StorageServiceFakeTheme {
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

final class StorageServiceFakeRole {
	/**
	 * @var string[]
	 */
	public array $added_caps = array();

	/**
	 * @var string[]
	 */
	public array $removed_caps = array();

	public function add_cap( string $capability ): void {
		$this->added_caps[] = $capability;
	}

	public function remove_cap( string $capability ): void {
		$this->removed_caps[] = $capability;
	}
}

final class StorageServiceTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['ttc_test_themes'] = array(
			'parent-theme' => new StorageServiceFakeTheme( 'Parent Theme', 'parent-theme' ),
			'child-theme'  => new StorageServiceFakeTheme( 'Child Theme', 'parent-theme' ),
		);
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['ttc_current_user_can_calls'],
			$GLOBALS['ttc_current_user_can_result'],
			$GLOBALS['ttc_current_user_id'],
			$GLOBALS['ttc_user_can_calls'],
			$GLOBALS['ttc_user_can_results'],
			$GLOBALS['ttc_apply_filters_calls'],
			$GLOBALS['ttc_apply_filters_results'],
			$GLOBALS['ttc_test_themes'],
			$GLOBALS['ttc_test_options'],
			$GLOBALS['ttc_test_roles'],
			$GLOBALS['ttc_test_role_objects'],
			$GLOBALS['tempered_themechanger_theme_cache'],
			$_POST
		);
	}

	private function load_storage_service(): void {
		$theme_file   = dirname( __DIR__, 2 ) . '/includes/themes.php';
		$access_file  = dirname( __DIR__, 2 ) . '/includes/access.php';
		$storage_file = dirname( __DIR__, 2 ) . '/includes/storage.php';

		self::assertFileExists( $theme_file );
		self::assertFileExists( $access_file );
		self::assertFileExists( $storage_file );

		require_once $theme_file;
		require_once $access_file;
		require_once $storage_file;

		TemperedThemeChanger\Themes\clear_cache();
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
				'theme_allow_list'   => array(),
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
				'theme_allow_list'   => array(),
			),
			TemperedThemeChanger\Storage\sanitize_settings( 'not-settings' )
		);
	}

	public function test_default_settings_include_empty_theme_allow_list(): void {
		$this->load_storage_service();

		self::assertSame(
			array(
				'post_type_defaults' => array(),
				'theme_allow_list'   => array(),
			),
			TemperedThemeChanger\Storage\default_settings()
		);
	}

	public function test_sanitizes_theme_allow_list_settings(): void {
		$this->load_storage_service();

		self::assertSame(
			array(
				'post_type_defaults' => array(),
				'theme_allow_list'   => array( 'child-theme', 'parent-theme' ),
			),
			TemperedThemeChanger\Storage\sanitize_settings(
				array(
					'theme_allow_list' => array( ' child-theme ', 'missing-theme', 'parent-theme', 'child-theme' ),
				)
			)
		);
	}

	public function test_sanitizes_post_type_defaults_against_submitted_theme_allow_list(): void {
		$GLOBALS['ttc_test_options']['tempered_themechanger_settings'] = array(
			'post_type_defaults' => array(),
			'theme_allow_list'   => array( 'child-theme' ),
		);

		$this->load_storage_service();

		self::assertSame(
			array(
				'post_type_defaults' => array(
					'post' => 'parent-theme',
				),
				'theme_allow_list'   => array( 'child-theme', 'parent-theme' ),
			),
			TemperedThemeChanger\Storage\sanitize_settings(
				array(
					'post_type_defaults' => array(
						'post' => 'parent-theme',
					),
					'theme_allow_list'   => array( 'child-theme', 'parent-theme' ),
				)
			)
		);
	}

	public function test_sanitizes_empty_theme_allow_list_to_all_themes_mode(): void {
		$this->load_storage_service();

		self::assertSame(
			array(
				'post_type_defaults' => array(),
				'theme_allow_list'   => array(),
			),
			TemperedThemeChanger\Storage\sanitize_settings(
				array(
					'theme_allow_list' => array(),
				)
			)
		);
	}

	public function test_role_capability_update_changes_selected_roles_and_skips_switch_theme_roles(): void {
		$administrator = new StorageServiceFakeRole();
		$editor        = new StorageServiceFakeRole();
		$author        = new StorageServiceFakeRole();

		$GLOBALS['ttc_test_roles'] = array(
			'administrator' => array(
				'name'         => 'Administrator',
				'capabilities' => array(
					'switch_themes'      => true,
					'use_theme_changer'  => true,
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
		$GLOBALS['ttc_test_role_objects'] = array(
			'administrator' => $administrator,
			'editor'        => $editor,
			'author'        => $author,
		);

		$this->load_storage_service();

		TemperedThemeChanger\Roles\update_theme_changer_capability( array( 'author' ) );

		self::assertSame( array(), $administrator->added_caps );
		self::assertSame( array(), $administrator->removed_caps );
		self::assertSame( array(), $editor->added_caps );
		self::assertSame( array( 'use_theme_changer' ), $editor->removed_caps );
		self::assertSame( array( 'use_theme_changer' ), $author->added_caps );
		self::assertSame( array(), $author->removed_caps );
	}

	public function test_sanitize_settings_does_not_update_role_capabilities(): void {
		$author = new StorageServiceFakeRole();

		$GLOBALS['ttc_test_roles'] = array(
			'author' => array(
				'name'         => 'Author',
				'capabilities' => array(),
			),
		);
		$GLOBALS['ttc_test_role_objects'] = array(
			'author' => $author,
		);

		$this->load_storage_service();

		TemperedThemeChanger\Storage\sanitize_settings(
			array(
				'role_capabilities_submitted' => '1',
				'role_capabilities'           => array( 'author' ),
			)
		);

		self::assertSame( array(), $author->added_caps );
		self::assertSame( array(), $author->removed_caps );
	}

	public function test_role_capability_update_only_writes_changed_roles(): void {
		$editor = new StorageServiceFakeRole();
		$author = new StorageServiceFakeRole();

		$GLOBALS['ttc_test_roles'] = array(
			'editor' => array(
				'name'         => 'Editor',
				'capabilities' => array(
					'use_theme_changer' => true,
				),
			),
			'author' => array(
				'name'         => 'Author',
				'capabilities' => array(),
			),
		);
		$GLOBALS['ttc_test_role_objects'] = array(
			'editor' => $editor,
			'author' => $author,
		);

		$this->load_storage_service();

		TemperedThemeChanger\Roles\update_theme_changer_capability( array( 'editor' ) );

		self::assertSame( array(), $editor->added_caps );
		self::assertSame( array(), $editor->removed_caps );
		self::assertSame( array(), $author->added_caps );
		self::assertSame( array(), $author->removed_caps );
	}

	public function test_admin_role_capability_handler_updates_roles_from_settings_submission(): void {
		$author = new StorageServiceFakeRole();

		$GLOBALS['ttc_current_user_can_result'] = true;
		$GLOBALS['ttc_test_roles']              = array(
			'author' => array(
				'name'         => 'Author',
				'capabilities' => array(),
			),
		);
		$GLOBALS['ttc_test_role_objects']       = array(
			'author' => $author,
		);
		$_POST                                 = array(
			'option_page'                 => 'tempered_themechanger',
			'tempered_themechanger_settings' => array(
				'role_capabilities_submitted' => '1',
				'role_capabilities'           => array( 'author' ),
			),
		);

		$this->load_storage_service();

		TemperedThemeChanger\Roles\handle_admin_settings_submission();

		self::assertSame( array( 'switch_themes' ), $GLOBALS['ttc_current_user_can_calls'][0] );
		self::assertSame( array( 'use_theme_changer' ), $author->added_caps );
	}

	public function test_post_meta_auth_checks_the_specific_post(): void {
		$GLOBALS['ttc_current_user_id']    = 7;
		$GLOBALS['ttc_user_can_results'] = array(
			'edit_post'         => true,
			'use_theme_changer' => true,
		);

		$this->load_storage_service();

		self::assertTrue( TemperedThemeChanger\Storage\can_edit_post_meta( true, '_tempered_themechanger_theme', 123 ) );
		self::assertSame(
			array(
				array( 7, 'edit_post', 123 ),
				array( 7, 'switch_themes' ),
				array( 7, 'use_theme_changer' ),
			),
			$GLOBALS['ttc_user_can_calls']
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
