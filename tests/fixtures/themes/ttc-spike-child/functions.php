<?php
/**
 * Child spike theme functions.
 *
 * @package TtcSpikeChild
 */

add_action(
	'wp_head',
	static function (): void {
		echo '<meta name="ttc-spike-child-functions" content="loaded">' . "\n";
	}
);
