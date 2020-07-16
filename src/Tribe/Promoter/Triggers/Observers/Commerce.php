<?php


namespace Tribe\Tickets\Promoter\Triggers\Observers;

use Tribe\Tickets\Promoter\Triggers\Models\Attendee;
use Tribe__Tickets__Commerce__PayPal__Main;

class Commerce {
	/**
	 * Attach hooks for trigger messages.
	 *
	 * @since TBD
	 */
	public function hook() {
		add_action( 'event_tickets_tpp_attendee_created', [ $this, 'attendee_created' ], 10, 5 );
	}

	/**
	 * Action fired when an PayPal attendee ticket is created
	 *
	 * @since TBD
	 *
	 * @param int    $attendee_id           Attendee post ID
	 * @param string $order_id              PayPal Order ID
	 * @param int    $product_id            PayPal ticket post ID
	 * @param int    $order_attendee_id     Attendee number in submitted order
	 * @param string $attendee_order_status The order status for the attendee.
	 */
	public function attendee_created( $attendee_id, $order_id, $product_id, $order_attendee_id, $attendee_order_status ) {
		/** @var Tribe__Tickets__Commerce__PayPal__Main $ticket */
		$ticket   = tribe( 'Tribe__Tickets__Commerce__PayPal__Main' );
		$attendee = new Attendee( $ticket->get_attendee( $attendee_id ) );

		do_action( 'tribe_tickets_promoter_trigger_attendee', 'ticket_purchased', $attendee, $ticket );
	}
}