<?php
/**
 * Base spike theme functions.
 *
 * @package TtcSpikeBase
 */

add_action(
	'init',
	static function (): void {
		register_block_type(
			'ttc-spike/base-marker',
			array(
				'render_callback' => static function (): string {
					return '<p data-ttc-spike-block="base">TTC spike base block</p>';
				},
			)
		);
	}
);

add_action(
	'wp_body_open',
	static function (): void {
		echo '<div id="ttc-spike-theme-marker" data-theme="base"></div>';
	}
);

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'ttc-spike/v1',
			'/theme',
			array(
				'methods'             => 'GET',
				'callback'            => static function (): array {
					return array(
						'theme'            => 'base',
						'stylesheet'       => get_stylesheet(),
						'template'         => get_template(),
						'block_registered' => WP_Block_Type_Registry::get_instance()->is_registered( 'ttc-spike/base-marker' ),
					);
				},
				'permission_callback' => '__return_true',
			)
		);
	}
);
