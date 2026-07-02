<?php
/**
 * RSVP-specific attendee maker trait for tests.
 *
 * @package Tribe\Tickets\Test\Commerce\RSVP;
 */

namespace Tribe\Tickets\Test\Commerce\RSVP;

use Tribe\Tickets\Test\Commerce\Attendee_Maker as Generic_Attendee_Maker;

/**
 * Trait Attendee_Maker.
 *
 * Provides RSVP-specific methods for creating attendees in tests.
 * Wraps the generic Attendee_Maker trait functionality.
 *
 * @package Tribe\Tickets\Test\Commerce\RSVP;
 */
trait Attendee_Maker {
	use Generic_Attendee_Maker;

	/**
	 * Generates an RSVP attendee for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int   $ticket_id The RSVP ticket ID.
	 * @param int   $post_id   The post/event ID the ticket is attached to.
	 * @param array $overrides Optional. Override values for the attendee. See generic trait for options.
	 *
	 * @return int The generated attendee post ID.
	 */
	protected function create_rsvp_attendee( int $ticket_id, int $post_id, array $overrides = [] ): int {
		return $this->create_attendee_for_ticket( $ticket_id, $post_id, $overrides );
	}

	/**
	 * Generates multiple RSVP attendees for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int   $count     The number of attendees to create.
	 * @param int   $ticket_id The RSVP ticket ID.
	 * @param int   $post_id   The post/event ID the ticket is attached to.
	 * @param array $overrides Optional. Override values for the attendees. See generic trait for options.
	 *
	 * @return array An array of generated attendee post IDs.
	 */
	protected function create_many_rsvp_attendees( int $count, int $ticket_id, int $post_id, array $overrides = [] ): array {
		return $this->create_many_attendees_for_ticket( $count, $ticket_id, $post_id, $overrides );
	}

	/**
	 * Sets the optout option on an RSVP attendee.
	 *
	 * @since TBD
	 *
	 * @param int  $attendee_id The attendee post ID.
	 * @param bool $optout      Optional. Whether to opt out. Default true.
	 */
	protected function optout_rsvp_attendee( int $attendee_id, bool $optout = true ): void {
		$this->optout_attendee( $attendee_id, $optout );
	}

	/**
	 * Sets the optout option on multiple RSVP attendees.
	 *
	 * @since TBD
	 *
	 * @param array $attendees Array of attendee IDs or attendee data arrays.
	 * @param bool  $optout    Optional. Whether to opt out. Default true.
	 */
	protected function optout_rsvp_attendees( array $attendees, bool $optout = true ): void {
		$this->optout_attendees( $attendees, $optout );
	}
}
