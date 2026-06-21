<?php
/**
 * Access helpers.
 *
 * @package TemperedThemeChanger
 */

namespace TemperedThemeChanger\Access;

defined( 'ABSPATH' ) || exit;

const CAPABILITY = 'use_theme_changer';

/**
 * Checks whether a user can change a post's Theme Changer selection.
 *
 * @param int $post_id Post ID, or 0 for a new post screen.
 * @param int $user_id User ID, or 0 for the current user.
 */
function can_change_post_theme( int $post_id, int $user_id = 0 ): bool {
	if ( 0 >= $user_id ) {
		$user_id = function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0;
	}

	if ( 0 >= $user_id || ! function_exists( 'user_can' ) ) {
		return false;
	}

	$can_edit_post          = 0 === $post_id || user_can( $user_id, 'edit_post', $post_id );
	$has_feature_capability = user_can( $user_id, 'switch_themes' ) || user_can( $user_id, CAPABILITY );
	$allowed                = $can_edit_post && $has_feature_capability;

	if ( ! function_exists( 'apply_filters' ) ) {
		return $allowed;
	}

	$filtered = (bool) apply_filters(
		'tempered_themechanger_user_can_change_post_theme',
		$allowed,
		$post_id,
		$user_id
	);

	return $allowed && $filtered;
}
