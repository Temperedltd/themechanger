<?php
/**
 * Post meta box tests.
 *
 * @package TemperedThemeChanger
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		unset( $domain );

		return $text;
	}
}

if ( ! function_exists( 'get_post_types' ) ) {
	function get_post_types( array $args = array(), string $output = 'names' ): array {
		unset( $args, $output );

		return array( 'post', 'page' );
	}
}

if ( ! function_exists( 'add_meta_box' ) ) {
	function add_meta_box( string $id, string $title, callable $callback, string $screen, string $context = 'advanced' ): void {
		$GLOBALS['ttc_add_meta_box_calls'][] = compact( 'id', 'title', 'callback', 'screen', 'context' );
	}
}

if ( ! function_exists( 'get_current_screen' ) ) {
	function get_current_screen(): ?object {
		return $GLOBALS['ttc_current_screen'] ?? null;
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( string $handle, string $src, array $deps = array(), string|bool|null $ver = false, bool $in_footer = false ): void {
		$GLOBALS['ttc_wp_enqueue_script_calls'][] = compact( 'handle', 'src', 'deps', 'ver', 'in_footer' );
	}
}

if ( ! function_exists( 'wp_localize_script' ) ) {
	function wp_localize_script( string $handle, string $object_name, array $l10n ): bool {
		$GLOBALS['ttc_wp_localize_script_calls'][] = compact( 'handle', 'object_name', 'l10n' );

		return true;
	}
}

final class PostMetaBoxTest extends TestCase {
	protected function tearDown(): void {
		unset(
			$GLOBALS['ttc_add_meta_box_calls'],
			$GLOBALS['ttc_current_screen'],
			$GLOBALS['ttc_wp_enqueue_script_calls'],
			$GLOBALS['ttc_wp_localize_script_calls'],
			$GLOBALS['ttc_current_user_id'],
			$GLOBALS['ttc_user_can_calls'],
			$GLOBALS['ttc_user_can_results'],
			$GLOBALS['ttc_apply_filters_calls'],
			$GLOBALS['ttc_apply_filters_results'],
			$GLOBALS['tempered_themechanger_theme_cache'],
			$_GET['post']
		);
	}

	private function load_post_meta_box(): void {
		$theme_file         = dirname( __DIR__, 2 ) . '/includes/themes.php';
		$access_file        = dirname( __DIR__, 2 ) . '/includes/access.php';
		$post_meta_box_file = dirname( __DIR__, 2 ) . '/includes/admin/post-meta-box.php';

		self::assertFileExists( $theme_file );
		self::assertFileExists( $access_file );
		self::assertFileExists( $post_meta_box_file );

		require_once $theme_file;
		require_once $access_file;
		require_once $post_meta_box_file;

		TemperedThemeChanger\Themes\clear_cache();
	}

	public function test_filters_supported_post_type_names(): void {
		$this->load_post_meta_box();

		self::assertSame(
			array( 'post', 'page', 'product' ),
			TemperedThemeChanger\Admin\PostMetaBox\filter_supported_post_type_names(
				array( 'post', 'page', 'attachment', 'revision', 'product' )
			)
		);
	}

	public function test_does_not_add_meta_box_when_user_lacks_theme_changer_capability(): void {
		$GLOBALS['ttc_current_user_id']    = 7;
		$GLOBALS['ttc_user_can_results'] = array(
			'edit_post' => true,
		);

		$this->load_post_meta_box();

		TemperedThemeChanger\Admin\PostMetaBox\add_meta_boxes(
			'post',
			(object) array(
				'ID' => 123,
			)
		);

		self::assertArrayNotHasKey( 'ttc_add_meta_box_calls', $GLOBALS );
	}

	public function test_adds_meta_box_when_user_has_theme_changer_capability(): void {
		$GLOBALS['ttc_current_user_id']    = 7;
		$GLOBALS['ttc_user_can_results'] = array(
			'edit_post'         => true,
			'use_theme_changer' => true,
		);

		$this->load_post_meta_box();

		TemperedThemeChanger\Admin\PostMetaBox\add_meta_boxes(
			'post',
			(object) array(
				'ID' => 123,
			)
		);

		self::assertSame( 'tempered-themechanger', $GLOBALS['ttc_add_meta_box_calls'][0]['id'] );
		self::assertSame( 'post', $GLOBALS['ttc_add_meta_box_calls'][0]['screen'] );
	}

	public function test_does_not_enqueue_block_editor_assets_when_user_lacks_theme_changer_capability(): void {
		$GLOBALS['ttc_current_screen']      = (object) array( 'post_type' => 'post' );
		$GLOBALS['ttc_current_user_id']     = 7;
		$GLOBALS['ttc_user_can_results']  = array(
			'edit_post' => true,
		);
		$_GET['post']                       = '123';

		$this->load_post_meta_box();

		TemperedThemeChanger\Admin\PostMetaBox\enqueue_block_editor_assets();

		self::assertArrayNotHasKey( 'ttc_wp_enqueue_script_calls', $GLOBALS );
		self::assertArrayNotHasKey( 'ttc_wp_localize_script_calls', $GLOBALS );
	}
}
