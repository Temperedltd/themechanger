<?php
/**
 * Post meta box tests.
 *
 * @package TemperedThemeChanger
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

final class PostMetaBoxTest extends TestCase {
	private function load_post_meta_box(): void {
		$post_meta_box_file = dirname( __DIR__, 2 ) . '/includes/admin/post-meta-box.php';

		self::assertFileExists( $post_meta_box_file );

		require_once $post_meta_box_file;
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
}
