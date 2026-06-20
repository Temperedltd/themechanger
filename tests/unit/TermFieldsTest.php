<?php
/**
 * Term field tests.
 *
 * @package TemperedThemeChanger
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'get_taxonomy' ) ) {
	function get_taxonomy( string $taxonomy ): object|false {
		if ( empty( $GLOBALS['ttc_test_taxonomies'][ $taxonomy ] ) ) {
			return false;
		}

		return $GLOBALS['ttc_test_taxonomies'][ $taxonomy ];
	}
}

final class TermFieldsTest extends TestCase {
	protected function tearDown(): void {
		unset( $GLOBALS['ttc_current_user_can_calls'], $GLOBALS['ttc_current_user_can_result'], $GLOBALS['ttc_test_taxonomies'] );
	}

	private function load_term_fields(): void {
		$term_fields_file = dirname( __DIR__, 2 ) . '/includes/admin/term-fields.php';

		self::assertFileExists( $term_fields_file );

		require_once $term_fields_file;
	}

	public function test_filters_supported_taxonomy_names(): void {
		$this->load_term_fields();

		self::assertSame(
			array( 'category', 'post_tag', 'product_cat' ),
			TemperedThemeChanger\Admin\TermFields\filter_supported_taxonomy_names(
				array( 'category', 'nav_menu', 'post_format', 'post_tag', 'product_cat' )
			)
		);
	}

	public function test_term_capability_uses_taxonomy_edit_terms_capability(): void {
		$GLOBALS['ttc_current_user_can_result'] = true;
		$GLOBALS['ttc_test_taxonomies']         = array(
			'product_cat' => (object) array(
				'cap' => (object) array(
					'edit_terms' => 'manage_product_terms',
				),
			),
		);

		$this->load_term_fields();

		self::assertTrue( TemperedThemeChanger\Admin\TermFields\can_edit_term_theme( 20, 'product_cat' ) );
		self::assertSame(
			array(
				array( 'manage_product_terms' ),
			),
			$GLOBALS['ttc_current_user_can_calls']
		);
	}

	public function test_term_capability_falls_back_to_specific_term_capability(): void {
		$GLOBALS['ttc_current_user_can_result'] = true;
		$GLOBALS['ttc_test_taxonomies']         = array(
			'custom_tax' => (object) array(
				'cap' => (object) array(),
			),
		);

		$this->load_term_fields();

		self::assertTrue( TemperedThemeChanger\Admin\TermFields\can_edit_term_theme( 30, 'custom_tax' ) );
		self::assertSame(
			array(
				array( 'edit_term', 30 ),
			),
			$GLOBALS['ttc_current_user_can_calls']
		);
	}
}
