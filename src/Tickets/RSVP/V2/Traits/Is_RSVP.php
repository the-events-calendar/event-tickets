<?php
/**
 * Is RSVP trait for RSVP V2.
 *
 * Provides methods to check if a ticket or attendee is an RSVP type.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Traits
 */

namespace TEC\Tickets\RSVP\V2\Traits;

use TEC\Tickets\Commerce\Ticket as TC_Ticket;
use TEC\Tickets\RSVP\V2\Meta;

/**
 * Trait Is_RSVP
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Traits
 */
trait Is_RSVP {

	/**
	 * Checks if a ticket is an RSVP ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return bool Whether the ticket is an RSVP ticket.
	 */
	public function is_rsvp_ticket( int $ticket_id ): bool {
		$post = get_post( $ticket_id );

		if ( ! $post || TC_Ticket::POSTTYPE !== $post->post_type ) {
			return false;
		}

		// Check the pinged column (primary type discriminator).
		if ( Meta::TC_RSVP_TYPE === $post->pinged ) {
			return true;
		}

		// Fallback to checking the _type meta.
		$type = get_post_meta( $ticket_id, Meta::TYPE_META_KEY, true );

		return Meta::TC_RSVP_TYPE === $type;
	}

	/**
	 * Checks if an attendee is an RSVP attendee.
	 *
	 * An attendee is considered an RSVP attendee if it has the RSVP status meta.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id The attendee post ID.
	 *
	 * @return bool Whether the attendee is an RSVP attendee.
	 */
	public function is_rsvp_attendee( int $attendee_id ): bool {
		return metadata_exists( 'post', $attendee_id, Meta::RSVP_STATUS_KEY );
	}

	/**
	 * Determine if a data array represents an RSVP.
	 *
	 * This looks to see whether the array of data has the "type" key set to
	 * "tc-rsvp". If the type key is not set, or if it is set to something other
	 * than "tc-rsvp", this will return false.
	 *
	 * @since TBD
	 *
	 * @param array $data The data array to check.
	 *
	 * @return bool Whether the data represents a tc-rsvp.
	 */
	protected function is_rsvp_data( array $data ): bool {
		if ( ! array_key_exists( 'type', $data ) ) {
			return false;
		}

		return Meta::TC_RSVP_TYPE === $data['type'];
	}
}
