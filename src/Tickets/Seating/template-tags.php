<?php
/**
 * Template tags defined by the Seating Tickets feature.
 *
 * @since TBD
 */

use TEC\Tickets\Seating\Meta;

if ( ! function_exists( 'tec_tickets_seating_enabled' ) ) {
	/**
	 * Returns whether the event is using assigned seating.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID of the event.
	 *
	 * @return bool Whether the event is using assigned seating.
	 */
	function tec_tickets_seating_enabled( int $post_id ): bool {
		// @toDo: remove the inversion once the meta key is updated from editor.
		return ! tribe_is_truthy( get_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, true ) );
	}
}
