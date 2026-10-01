<?php

namespace TEC\Tickets\Tests\Commerce\RSVP\V2;

use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\RSVP\V2\Constants;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker as Base_Ticket_Maker;

trait Ticket_Maker {
	use Base_Ticket_Maker;

	/**
	 * Creates a TC-RSVP ticket for a post.
	 *
	 * @param int   $post_id   The ID of the post this ticket should be related to.
	 * @param array $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return int The generated ticket post ID.
	 */
	protected function create_tc_rsvp_ticket( int $post_id, array $overrides = [] ): int {
		// TC-RSVP tickets are free (price = 0).
		$price = 0;

		// Ensure the ticket type is set to tc-rsvp.
		$overrides['ticket_type'] = Constants::TC_RSVP_TYPE;

		$ticket_id = $this->create_tc_ticket( $post_id, $price, $overrides );

		// Set the ticket type meta.
		update_post_meta( $ticket_id, Ticket::$type_meta_key, Constants::TC_RSVP_TYPE );

		return $ticket_id;
	}

	/**
	 * Creates multiple TC-RSVP tickets for a post.
	 *
	 * @param int   $count     The number of tickets to create.
	 * @param int   $post_id   The ID of the post this ticket should be related to.
	 * @param array $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return array An array of the generated ticket post IDs.
	 */
	protected function create_many_tc_rsvp_tickets( int $count, int $post_id, array $overrides = [] ): array {
		$tickets = [];

		for ( $i = 0; $i < $count; $i++ ) {
			$tickets[] = $this->create_tc_rsvp_ticket( $post_id, $overrides );
		}

		return $tickets;
	}
}
