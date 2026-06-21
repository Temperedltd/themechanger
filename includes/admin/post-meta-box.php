<?php
/**
 * Post editor controls.
 *
 * @package TemperedThemeChanger
 */

namespace TemperedThemeChanger\Admin\PostMetaBox;

use function TemperedThemeChanger\Access\can_change_post_theme;
use function TemperedThemeChanger\Storage\sanitize_theme_slug;
use function TemperedThemeChanger\Themes\is_invalid_selection;
use function TemperedThemeChanger\Themes\theme_choices;
use const TemperedThemeChanger\Storage\META_KEY;

defined( 'ABSPATH' ) || exit;

const NONCE_ACTION = 'tempered_themechanger_save_post_theme';
const NONCE_NAME   = 'tempered_themechanger_nonce';

/**
 * Registers post editor hooks.
 */
function bootstrap(): void {
	add_action( 'add_meta_boxes', __NAMESPACE__ . '\\add_meta_boxes' );
	add_action( 'save_post', __NAMESPACE__ . '\\save_post', 10, 2 );
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_block_editor_assets' );
}

/**
 * Adds the classic editor meta box.
 *
 * @param string $post_type Post type.
 * @param object $post      Post object.
 */
function add_meta_boxes( string $post_type, ?object $post = null ): void {
	if ( ! in_array( $post_type, supported_post_type_names(), true ) ) {
		return;
	}

	$post_id = is_object( $post ) && isset( $post->ID ) ? absint( $post->ID ) : 0;

	if ( ! can_change_post_theme( $post_id ) ) {
		return;
	}

	add_meta_box(
		'tempered-themechanger',
		__( 'Theme Changer', 'tempered-themechanger' ),
		__NAMESPACE__ . '\\render_meta_box',
		$post_type,
		'side'
	);
}

/**
 * Renders the classic editor meta box.
 *
 * @param \WP_Post $post Post object.
 */
function render_meta_box( \WP_Post $post ): void {
	if ( ! can_change_post_theme( $post->ID ) ) {
		return;
	}

	$selected = (string) get_post_meta( $post->ID, META_KEY, true );

	wp_nonce_field( NONCE_ACTION, NONCE_NAME );
	?>
	<p>
		<label for="tempered-themechanger-theme"><?php esc_html_e( 'Theme', 'tempered-themechanger' ); ?></label>
		<select id="tempered-themechanger-theme" name="<?php echo esc_attr( META_KEY ); ?>" class="widefat">
			<option value=""><?php esc_html_e( 'Inherit default', 'tempered-themechanger' ); ?></option>
			<?php foreach ( theme_choices() as $stylesheet => $name ) : ?>
				<option value="<?php echo esc_attr( $stylesheet ); ?>" <?php selected( $selected, $stylesheet ); ?>>
					<?php echo esc_html( $name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>
	<?php if ( is_invalid_selection( $selected ) ) : ?>
		<p class="notice notice-warning inline">
			<?php esc_html_e( 'The saved theme is no longer installed or allowed. Choose a new theme or save Inherit default.', 'tempered-themechanger' ); ?>
		</p>
	<?php endif; ?>
	<p class="description">
		<?php esc_html_e( 'Save or preview to load the selected theme.', 'tempered-themechanger' ); ?>
	</p>
	<?php
}

/**
 * Saves the classic editor post theme.
 *
 * @param int      $post_id Post ID.
 * @param \WP_Post $post    Post object.
 */
function save_post( int $post_id, \WP_Post $post ): void {
	if ( ! in_array( $post->post_type, supported_post_type_names(), true ) ) {
		return;
	}

	if ( ! isset( $_POST[ NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ NONCE_NAME ] ) ), NONCE_ACTION ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! can_change_post_theme( $post_id ) ) {
		return;
	}

	$theme_slug = isset( $_POST[ META_KEY ] ) ? sanitize_theme_slug( sanitize_text_field( wp_unslash( $_POST[ META_KEY ] ) ) ) : '';

	if ( '' === $theme_slug ) {
		delete_post_meta( $post_id, META_KEY );
		return;
	}

	update_post_meta( $post_id, META_KEY, $theme_slug );
}

/**
 * Enqueues the block editor sidebar script.
 */
function enqueue_block_editor_assets(): void {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || ! in_array( $screen->post_type, supported_post_type_names(), true ) ) {
		return;
	}

	if ( ! can_change_post_theme( current_editor_post_id() ) ) {
		return;
	}

	wp_enqueue_script(
		'tempered-themechanger-editor-sidebar',
		TEMPERED_THEMECHANGER_URL . 'assets/js/editor-sidebar.js',
		array( 'wp-components', 'wp-compose', 'wp-data', 'wp-edit-post', 'wp-element', 'wp-i18n', 'wp-plugins' ),
		TEMPERED_THEMECHANGER_VERSION,
		true
	);

	wp_localize_script(
		'tempered-themechanger-editor-sidebar',
		'temperedThemeChanger',
		array(
			'metaKey' => META_KEY,
			'themes'  => theme_choices(),
		)
	);
}

/**
 * Returns the post ID for the current editor request.
 */
function current_editor_post_id(): int {
	if ( ! isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return 0;
	}

	return absint( wp_unslash( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Returns supported public post type names.
 *
 * @return string[]
 */
function supported_post_type_names(): array {
	if ( ! function_exists( 'get_post_types' ) ) {
		return array();
	}

	return filter_supported_post_type_names( get_post_types( array( 'public' => true ), 'names' ) );
}

/**
 * Filters post type names to editor-supported content types.
 *
 * @param string[] $post_types Post type names.
 * @return string[]
 */
function filter_supported_post_type_names( array $post_types ): array {
	$unsupported = array( 'attachment', 'revision', 'nav_menu_item' );
	$supported   = array();

	foreach ( $post_types as $post_type ) {
		if ( ! is_string( $post_type ) || in_array( $post_type, $unsupported, true ) ) {
			continue;
		}

		$supported[] = $post_type;
	}

	return array_values( $supported );
}
