<?php

namespace TEC\Tickets\Commerce;

use Tribe\Tickets\Promoter\Triggers\Contracts\Attendee_Model;
use Tribe\Tickets\Promoter\Triggers\Models\Attendee;

class Promoter_Observer {

	/**
	 * @since 4.12.0
	 *
	 * @var Tribe__Tickets__Promoter__Observer $observer ET Observer reference.
	 */
	private $observer;

	/**
	 * Constructor.
	 *
	 */
	public function __construct() {
		$this->observer = tribe( \Tribe__Tickets__Promoter__Observer::class );
		$this->hook();
	}

	/**
	 * Attach hooks for trigger messages.
	 *
	 * @since 5.3.2
	 */
	public function hook() {

		add_action( 'tec_tickets_commerce_flag_action_generated_attendee', [ $this, 'attendee_created' ], 10, 5 );
		add_action( 'tec_tickets_commerce_ticket_deleted', tribe_callback( 'tickets.promoter.observer', 'notify_event_id' ), 10, 2 );
		add_action( 'event_tickets_checkin', [ $this, 'checkin' ], 10, 2 );
	}

	/**
	 * Action fired when a TC attendee is created.
	 *
	 * @since 5.3.2
	 *
	 * @param \WP_Post $attendee Attendee object.
	 */
	public function attendee_created( \WP_Post $attendee ) {
		$this->trigger( 'ticket_purchased', $attendee->ID );
	}

	/**
	 * Responds to a checkin action.
	 *
	 * @since 5.3.2
	 *
	 * @param int       $attendee_id The ID of the attendee utilized.
	 * @param bool|null $qr          Whether it's from a QR scan.
	 */
	public function checkin( $attendee_id, $qr ) {
		$this->trigger( 'checkin', $attendee_id );
	}

	/**
	 * Fire a trigger action using Tickets Commerce as main source of the ticket data.
	 *
	 * @since 5.3.2
	 *
	 * @param string $type        The trigger type.
	 * @param int    $attendee_id The ID of the attendee utilized.
	 */
	private function trigger( $type, $attendee_id ) {

		$attendee  = tec_tc_get_attendee( $attendee_id );
		$ticket    = tribe( Module::class );
		$attendee  = new Attendee( (array) $attendee );

		/**
		 * Create a new action to listen for a trigger associated with an attendee.
		 *
		 * @since 5.3.2
		 *
		 * @param string                  $type     The type of trigger fired.
		 * @param Attendee_Model          $attendee The attendee associated with the trigger.
		 * @param Tribe__Tickets__Tickets $ticket   The ticket where the attendee was created.
		 */
		do_action( 'tribe_tickets_promoter_trigger_attendee', $type, $attendee, $ticket );
	}
}
