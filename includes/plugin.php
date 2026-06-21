<?php
/**
 * Plugin bootstrap.
 *
 * @package TemperedThemeChanger
 */

namespace TemperedThemeChanger\Plugin;

defined( 'ABSPATH' ) || exit;

require_once TEMPERED_THEMECHANGER_PATH . 'includes/constants.php';
require_once TEMPERED_THEMECHANGER_PATH . 'includes/themes.php';
require_once TEMPERED_THEMECHANGER_PATH . 'includes/access.php';
require_once TEMPERED_THEMECHANGER_PATH . 'includes/roles.php';
require_once TEMPERED_THEMECHANGER_PATH . 'includes/storage.php';
require_once TEMPERED_THEMECHANGER_PATH . 'includes/resolver.php';
require_once TEMPERED_THEMECHANGER_PATH . 'includes/runtime.php';
require_once TEMPERED_THEMECHANGER_PATH . 'includes/admin/post-meta-box.php';
require_once TEMPERED_THEMECHANGER_PATH . 'includes/admin/term-fields.php';
require_once TEMPERED_THEMECHANGER_PATH . 'includes/admin/settings-page.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once TEMPERED_THEMECHANGER_PATH . 'includes/cli.php';
}

/**
 * Starts plugin services.
 */
function bootstrap(): void {
	add_action( 'init', \TemperedThemeChanger\Storage\register( ... ) );

	\TemperedThemeChanger\Runtime\bootstrap();
	\TemperedThemeChanger\Roles\bootstrap();
	\TemperedThemeChanger\Admin\PostMetaBox\bootstrap();
	\TemperedThemeChanger\Admin\TermFields\bootstrap();
	\TemperedThemeChanger\Admin\SettingsPage\bootstrap();

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		\TemperedThemeChanger\CLI\bootstrap();
	}
}
