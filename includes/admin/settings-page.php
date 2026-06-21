<?php
/**
 * Settings page.
 *
 * @package TemperedThemeChanger
 */

namespace TemperedThemeChanger\Admin\SettingsPage;

use function TemperedThemeChanger\Storage\default_settings;
use function TemperedThemeChanger\Themes\is_invalid_selection;
use function TemperedThemeChanger\Themes\theme_choices;
use const TemperedThemeChanger\Storage\OPTION_NAME;

defined( 'ABSPATH' ) || exit;

const PAGE_SLUG = 'tempered-themechanger';

/**
 * Registers settings page hooks.
 */
function bootstrap(): void {
	add_action( 'admin_menu', __NAMESPACE__ . '\\add_settings_page' );
	add_filter( 'option_page_capability_tempered_themechanger', __NAMESPACE__ . '\\settings_capability' );
}

/**
 * Adds the settings page.
 */
function add_settings_page(): void {
	add_theme_page(
		__( 'Theme Changer', 'tempered-themechanger' ),
		__( 'Theme Changer', 'tempered-themechanger' ),
		settings_capability(),
		PAGE_SLUG,
		__NAMESPACE__ . '\\render_settings_page'
	);
}

/**
 * Returns the capability required to manage Theme Changer defaults.
 */
function settings_capability(): string {
	return 'switch_themes';
}

/**
 * Renders the settings page.
 */
function render_settings_page(): void {
	if ( ! current_user_can( settings_capability() ) ) {
		return;
	}

	$settings = get_option( OPTION_NAME, default_settings() );

	if ( ! is_array( $settings ) ) {
		$settings = default_settings();
	}

	$post_type_defaults = isset( $settings['post_type_defaults'] ) && is_array( $settings['post_type_defaults'] )
		? $settings['post_type_defaults']
		: array();
	$invalid_defaults   = invalid_default_post_type_names( $post_type_defaults );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Theme Changer', 'tempered-themechanger' ); ?></h1>
		<?php if ( ! empty( $invalid_defaults ) ) : ?>
			<div class="notice notice-warning inline">
				<p>
					<?php
					printf(
						/* translators: %s: comma-separated post type labels. */
						esc_html__( 'Some default theme selections are no longer installed or allowed. Choose a new theme or save Active site theme for: %s.', 'tempered-themechanger' ),
						esc_html( implode( ', ', post_type_labels( $invalid_defaults ) ) )
					);
					?>
				</p>
			</div>
		<?php endif; ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'tempered_themechanger' ); ?>
			<table class="form-table" role="presentation">
				<tbody>
					<?php foreach ( defaultable_post_type_names() as $post_type ) : ?>
						<tr>
							<th scope="row">
								<label for="tempered-themechanger-default-<?php echo esc_attr( $post_type ); ?>">
									<?php echo esc_html( post_type_label( $post_type ) ); ?>
								</label>
							</th>
							<td>
								<?php render_theme_select( $post_type, (string) ( $post_type_defaults[ $post_type ] ?? '' ) ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Renders a default theme select for a post type.
 *
 * @param string $post_type Post type key.
 * @param string $selected  Selected theme stylesheet.
 */
function render_theme_select( string $post_type, string $selected ): void {
	$field_name = OPTION_NAME . '[post_type_defaults][' . $post_type . ']';
	?>
	<select id="tempered-themechanger-default-<?php echo esc_attr( $post_type ); ?>" name="<?php echo esc_attr( $field_name ); ?>">
		<option value=""><?php esc_html_e( 'Active site theme', 'tempered-themechanger' ); ?></option>
		<?php foreach ( theme_choices() as $stylesheet => $name ) : ?>
			<option value="<?php echo esc_attr( $stylesheet ); ?>" <?php selected( $selected, $stylesheet ); ?>>
				<?php echo esc_html( $name ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}

/**
 * Returns public post types that can have defaults.
 *
 * @return string[]
 */
function defaultable_post_type_names(): array {
	if ( ! function_exists( 'get_post_types' ) ) {
		return array();
	}

	return filter_defaultable_post_type_names( get_post_types( array( 'public' => true ), 'names' ) );
}

/**
 * Filters post type names to defaultable content types.
 *
 * @param string[] $post_types Post type names.
 * @return string[]
 */
function filter_defaultable_post_type_names( array $post_types ): array {
	$unsupported = array( 'attachment', 'revision', 'nav_menu_item' );
	$defaultable = array();

	foreach ( $post_types as $post_type ) {
		if ( ! is_string( $post_type ) || in_array( $post_type, $unsupported, true ) ) {
			continue;
		}

		$defaultable[] = $post_type;
	}

	return array_values( $defaultable );
}

/**
 * Returns post types with invalid stored default themes.
 *
 * @param array<string, mixed> $post_type_defaults Stored post type defaults.
 * @return string[]
 */
function invalid_default_post_type_names( array $post_type_defaults ): array {
	$invalid = array();

	foreach ( defaultable_post_type_names() as $post_type ) {
		if ( is_invalid_selection( (string) ( $post_type_defaults[ $post_type ] ?? '' ) ) ) {
			$invalid[] = $post_type;
		}
	}

	return $invalid;
}

/**
 * Returns readable post type labels.
 *
 * @param string[] $post_types Post type keys.
 * @return string[]
 */
function post_type_labels( array $post_types ): array {
	return array_map( __NAMESPACE__ . '\\post_type_label', $post_types );
}

/**
 * Returns a readable post type label.
 *
 * @param string $post_type Post type key.
 */
function post_type_label( string $post_type ): string {
	$post_type_object = function_exists( 'get_post_type_object' ) ? get_post_type_object( $post_type ) : null;

	if ( $post_type_object && isset( $post_type_object->labels->singular_name ) ) {
		return (string) $post_type_object->labels->singular_name;
	}

	return $post_type;
}
