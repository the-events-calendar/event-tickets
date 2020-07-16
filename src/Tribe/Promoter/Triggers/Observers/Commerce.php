<?php


namespace Tribe\Tickets\Promoter\Triggers\Observers;

use Tribe\Tickets\Promoter\Triggers\Contracts\Attendee_Model;
use Tribe\Tickets\Promoter\Triggers\Models\Attendee;
use Tribe__Tickets__Commerce__PayPal__Main;
use Tribe__Tickets__Tickets;

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

		/**
		 * Create a new action to listen for a trigger associated with an attendee.
		 *
		 * @since TBD
		 *
		 * @param string                  $type     The type of trigger fired.
		 * @param Attendee_Model          $attendee The attendee associated with the trigger.
		 * @param Tribe__Tickets__Tickets $ticket   The ticket where the attendee was created.
		 */
		do_action( 'tribe_tickets_promoter_trigger_attendee', 'ticket_purchased', $attendee, $ticket );
	}
}