<?php
/**
 * RSVP V1 Controller.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP\V1;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\RSVP\RSVP_Controller_Methods;
use Tribe__Tickets__Repositories__Ticket__RSVP as RSVP_V1_Ticket_Repository;
use Tribe__Tickets__Repositories__Attendee__RSVP as RSVP_V1_Attendee_Repository;

/**
 * V1 Controller for RSVP functionality.
 *
 * This controller registers all hooks for the current RSVP implementation.
 *
 * @since TBD
 */
class Controller extends Controller_Contract {
	use RSVP_Controller_Methods;

	/**
	 * The action that will be fired after the successful registration of this controller.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static string $registration_action = 'tec_tickets_rsvp_v1_registered';

	/**
	 * Registers the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->register_common_rsvp_implementations();

		// Bind the repositories as factories to make sure each instance is different.
		$this->container->bind(
			'tickets.ticket-repository.rsvp',
			RSVP_V1_Ticket_Repository::class
		);
		$this->container->bind(
			'tickets.attendee-repository.rsvp',
			RSVP_V1_Attendee_Repository::class
		);
	}
}
