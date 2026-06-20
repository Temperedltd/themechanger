<?php
/**
 * Block spike theme functions.
 *
 * @package TtcSpikeBlock
 */

add_action(
	'wp_head',
	static function (): void {
		echo '<meta name="ttc-spike-block-functions" content="loaded">' . "\n";
	}
);
