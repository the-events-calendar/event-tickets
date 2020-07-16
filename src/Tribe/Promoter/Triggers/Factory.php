<?php


namespace Tribe\Tickets\Promoter\Triggers;


use Tribe\Tickets\Promoter\Triggers\Builders\AttendeeTrigger;
use Tribe\Tickets\Promoter\Triggers\Models\Attendee as AttendeeModel;
use Tribe__Tickets__Tickets;

class Factory {
	/**
	 * Create new triggers based on the different type of hooks.
	 *
	 * @since TBD
	 */
	public function hook() {
		add_action( 'tribe_tickets_promoter_trigger_attendee', [ $this, 'build_attendee' ], 10, 3 );
	}

	/**
	 * When an action `tribe_tickets_promoter_trigger_attendee` is fired, react with an attendee trigger.
	 *
	 * @since TBD
	 *
	 * @param string                  $type     The type of trigger message.
	 * @param AttendeeModel           $attendee The representation of the attendee.
	 * @param Tribe__Tickets__Tickets $ticket   The ticket provider instance.
	 */
	public function build_attendee( $type, $attendee, $ticket ) {
		do_action( 'tribe_tickets_promoter_trigger', new AttendeeTrigger( $type, $attendee, $ticket ) );
	}
}