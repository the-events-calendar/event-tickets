<?php

namespace TEC\Tickets\Test\Commerce\RSVP\V2;

use TEC\Tickets\Commerce\Order as TC_Order;
use TEC\Tickets\RSVP\V2\Ticket;

/**
 * Trait Ticket_Maker
 *
 * Provides utility methods for creating RSVP V2 tickets and mock orders in tests.
 *
 * @package TEC\Tickets\Test\Commerce\RSVP\V2
 */
trait Ticket_Maker {
	/**
	 * Creates an RSVP V2 ticket for a post.
	 *
	 * @param int   $post_id The post ID to attach the ticket to.
	 * @param array $args    Optional. Additional arguments to pass to ticket creation.
	 *                       Defaults to ['name' => 'Test RSVP'].
	 *
	 * @return int The created ticket ID.
	 */
	private function create_rsvp_ticket( int $post_id, array $args = [] ): int {
		$args = array_merge( [ 'name' => 'Test RSVP' ], $args );
		return tribe( Ticket::class )->create( $post_id, $args );
	}

	/**
	 * Creates a mock order for testing RSVP V2 functionality.
	 *
	 * @return int The created order ID.
	 */
	private function create_mock_order(): int {
		return wp_insert_post( [
			'post_type'   => TC_Order::POSTTYPE,
			'post_status' => 'publish',
			'post_title'  => 'Test Order',
		] );
	}
}
