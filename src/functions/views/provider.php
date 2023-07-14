<?php
// @todo: Discuss with @be @bordoni how we should approach the duplication here.
/**
 * Checks whether v2 of the Views is enabled or not.
 *
 * In order the function will check the `TRIBE_EVENTS_V2_VIEWS` constant,
 * the `TRIBE_EVENTS_V2_VIEWS` environment variable.
 *
 * @since 4.10.9
 *
 * @return bool Whether v2 of the Views are enabled or not.
 */
function tribe_events_tickets_views_v2_is_enabled() {
	if ( ! function_exists( 'tribe_events_views_v2_is_enabled' ) ) {
		return false;
	}

	return tribe_events_views_v2_is_enabled();
}

/**
 * Normalizes the post ID by converting provisional ID's into regular Post ID's, if necessary.
 * 
 * @since TBD
 *
 * @param int $post_id The Post ID to normalize.
 *
 * @return int The normalized post ID.
 */
function tribe_events_tickets_normalize_post_id( $post_id ) {
	if ( class_exists( '\TEC\Events\Custom_Tables\V1\Models\Occurrence', false ) ) {
		return \TEC\Events\Custom_Tables\V1\Models\Occurrence::normalize_id( $post_id );
	} else {
		return $post_id;
	}
}
