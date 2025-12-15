<?php
/**
 * V2 Metabox class for RSVP.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

/**
 * Class Metabox
 *
 * Handles RSVP-specific metabox rendering and data for V2 implementation.
 * V2 RSVP uses TC (Tickets Commerce) infrastructure.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Metabox {

	/**
	 * Get the ticket type for RSVP metabox.
	 *
	 * @since TBD
	 *
	 * @return string The RSVP ticket type.
	 */
	public function get_type(): string {
		return Constants::TC_RSVP_TYPE;
	}

	/**
	 * Check if the metabox should be rendered for a specific post.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return bool True if the metabox should be rendered.
	 */
	public function should_render( int $post_id ): bool {
		/**
		 * Filters whether the RSVP V2 metabox should be rendered.
		 *
		 * @since TBD
		 *
		 * @param bool $should_render Whether to render the metabox.
		 * @param int  $post_id       The post ID.
		 */
		return (bool) apply_filters( 'tec_tickets_rsvp_v2_metabox_should_render', true, $post_id );
	}
}
