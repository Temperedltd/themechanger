<?php
/**
 * Plugin Name: Theme Changer
 * Description: Render selected WordPress content with a different installed theme.
 * Version: 0.1.0
 * Requires at least: 7.0
 * Requires PHP: 8.2
 * Author: Tempered Ltd
 * Text Domain: tempered-themechanger
 * Domain Path: /languages
 *
 * @package TemperedThemeChanger
 */

defined( 'ABSPATH' ) || exit;

define( 'TEMPERED_THEMECHANGER_VERSION', '0.1.0' );
define( 'TEMPERED_THEMECHANGER_FILE', __FILE__ );
define( 'TEMPERED_THEMECHANGER_PATH', plugin_dir_path( __FILE__ ) );
define( 'TEMPERED_THEMECHANGER_URL', plugin_dir_url( __FILE__ ) );

require_once TEMPERED_THEMECHANGER_PATH . 'includes/plugin.php';

add_action(
	'plugins_loaded',
	static function (): void {
		TemperedThemeChanger\Plugin\bootstrap();
	}
);
