<?php
/**
 * Term editor controls.
 *
 * @package TemperedThemeChanger
 */

namespace TemperedThemeChanger\Admin\TermFields;

use function TemperedThemeChanger\Storage\sanitize_theme_slug;
use function TemperedThemeChanger\Themes\is_invalid_selection;
use function TemperedThemeChanger\Themes\theme_choices;
use const TemperedThemeChanger\Storage\META_KEY;

defined( 'ABSPATH' ) || exit;

const NONCE_ACTION = 'tempered_themechanger_save_term_theme';
const NONCE_NAME   = 'tempered_themechanger_term_nonce';

/**
 * Registers term field hooks.
 */
function bootstrap(): void {
	add_action( 'init', __NAMESPACE__ . '\\register_taxonomy_hooks' );
}

/**
 * Registers hooks for public taxonomies.
 */
function register_taxonomy_hooks(): void {
	foreach ( supported_taxonomy_names() as $taxonomy ) {
		add_action( "{$taxonomy}_add_form_fields", __NAMESPACE__ . '\\render_add_field' );
		add_action( "{$taxonomy}_edit_form_fields", __NAMESPACE__ . '\\render_edit_field' );
		add_action(
			"created_{$taxonomy}",
			static function ( int $term_id ) use ( $taxonomy ): void {
				save_term( $term_id, $taxonomy );
			}
		);
		add_action(
			"edited_{$taxonomy}",
			static function ( int $term_id ) use ( $taxonomy ): void {
				save_term( $term_id, $taxonomy );
			}
		);
	}
}

/**
 * Renders the add-term field.
 *
 * @param string $taxonomy Taxonomy name.
 */
function render_add_field( string $taxonomy ): void {
	?>
	<div class="form-field term-tempered-themechanger-wrap" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>">
		<label for="tempered-themechanger-term-theme"><?php esc_html_e( 'Theme', 'tempered-themechanger' ); ?></label>
		<?php render_select( '' ); ?>
		<p><?php esc_html_e( 'Save to load this term archive with the selected theme where supported.', 'tempered-themechanger' ); ?></p>
		<?php wp_nonce_field( NONCE_ACTION, NONCE_NAME ); ?>
	</div>
	<?php
}

/**
 * Renders the edit-term field.
 *
 * @param \WP_Term $term Term object.
 */
function render_edit_field( \WP_Term $term ): void {
	$selected = (string) get_term_meta( $term->term_id, META_KEY, true );
	?>
	<tr class="form-field term-tempered-themechanger-wrap">
		<th scope="row">
			<label for="tempered-themechanger-term-theme"><?php esc_html_e( 'Theme', 'tempered-themechanger' ); ?></label>
		</th>
		<td>
			<?php render_select( $selected ); ?>
			<?php if ( is_invalid_selection( $selected ) ) : ?>
				<p class="notice notice-warning inline">
					<?php esc_html_e( 'The saved theme is no longer installed or allowed. Choose a new theme or save Inherit default.', 'tempered-themechanger' ); ?>
				</p>
			<?php endif; ?>
			<p class="description"><?php esc_html_e( 'Save to load this term archive with the selected theme where supported.', 'tempered-themechanger' ); ?></p>
			<?php wp_nonce_field( NONCE_ACTION, NONCE_NAME ); ?>
		</td>
	</tr>
	<?php
}

/**
 * Renders the shared term theme select.
 *
 * @param string $selected Selected theme stylesheet.
 */
function render_select( string $selected ): void {
	?>
	<select id="tempered-themechanger-term-theme" name="<?php echo esc_attr( META_KEY ); ?>">
		<option value=""><?php esc_html_e( 'Inherit default', 'tempered-themechanger' ); ?></option>
		<?php foreach ( theme_choices() as $stylesheet => $name ) : ?>
			<option value="<?php echo esc_attr( $stylesheet ); ?>" <?php selected( $selected, $stylesheet ); ?>>
				<?php echo esc_html( $name ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}

/**
 * Saves the term theme value.
 *
 * @param int    $term_id  Term ID.
 * @param string $taxonomy Taxonomy name.
 */
function save_term( int $term_id, string $taxonomy ): void {
	if ( ! isset( $_POST[ NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ NONCE_NAME ] ) ), NONCE_ACTION ) ) {
		return;
	}

	if ( ! can_edit_term_theme( $term_id, $taxonomy ) ) {
		return;
	}

	$theme_slug = isset( $_POST[ META_KEY ] ) ? sanitize_theme_slug( sanitize_text_field( wp_unslash( $_POST[ META_KEY ] ) ) ) : '';

	if ( '' === $theme_slug ) {
		delete_term_meta( $term_id, META_KEY );
		return;
	}

	update_term_meta( $term_id, META_KEY, $theme_slug );
}

/**
 * Checks whether the current user can edit a term theme selection.
 *
 * @param int    $term_id  Term ID.
 * @param string $taxonomy Taxonomy name.
 */
function can_edit_term_theme( int $term_id, string $taxonomy ): bool {
	if ( ! function_exists( 'current_user_can' ) ) {
		return false;
	}

	$taxonomy_object = function_exists( 'get_taxonomy' ) ? get_taxonomy( $taxonomy ) : false;

	if ( is_object( $taxonomy_object ) && ! empty( $taxonomy_object->cap->edit_terms ) ) {
		return current_user_can( (string) $taxonomy_object->cap->edit_terms );
	}

	return current_user_can( 'edit_term', $term_id );
}

/**
 * Returns supported public taxonomy names.
 *
 * @return string[]
 */
function supported_taxonomy_names(): array {
	if ( ! function_exists( 'get_taxonomies' ) ) {
		return array();
	}

	return filter_supported_taxonomy_names( get_taxonomies( array( 'public' => true ), 'names' ) );
}

/**
 * Filters taxonomy names to content taxonomies.
 *
 * @param string[] $taxonomies Taxonomy names.
 * @return string[]
 */
function filter_supported_taxonomy_names( array $taxonomies ): array {
	$unsupported = array( 'nav_menu', 'post_format', 'wp_pattern_category' );
	$supported   = array();

	foreach ( $taxonomies as $taxonomy ) {
		if ( ! is_string( $taxonomy ) || in_array( $taxonomy, $unsupported, true ) ) {
			continue;
		}

		$supported[] = $taxonomy;
	}

	return array_values( $supported );
}
