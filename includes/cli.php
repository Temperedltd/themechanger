<?php
/**
 * WP-CLI bootstrap.
 *
 * @package TemperedThemeChanger
 */

namespace TemperedThemeChanger\CLI;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-command.php';

/**
 * Registers WP-CLI commands.
 */
function bootstrap(): void {
	if ( ! class_exists( '\WP_CLI' ) ) {
		return;
	}

	$command = new Command();

	\WP_CLI::add_command( 'theme-changer', $command );
	\WP_CLI::add_command( 'theme-changer get-default', array( $command, 'get_default' ) );
	\WP_CLI::add_command( 'theme-changer set-default', array( $command, 'set_default' ) );
	\WP_CLI::add_command( 'theme-changer clear-default', array( $command, 'clear_default' ) );
	\WP_CLI::add_command( 'theme-changer get-allow-list', array( $command, 'get_allow_list' ) );
	\WP_CLI::add_command( 'theme-changer add-allowed-theme', array( $command, 'add_allowed_theme' ) );
	\WP_CLI::add_command( 'theme-changer remove-allowed-theme', array( $command, 'remove_allowed_theme' ) );
}
