<?php
/**
 * V2 RSVP Controller - TC-based implementation.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\RSVP\RSVP_Controller_Methods;

/**
 * Class Controller
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
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
	public static string $registration_action = 'tec_tickets_rsvp_v2_registered';

	/**
	 * Register the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->singleton( Constants::class );
		$this->container->register( Assets::class );

		$this->register_common_rsvp_implementations();

		// Bind the repositories as factories to make sure each instance is different.
		$this->container->bind(
			'tickets.ticket-repository.rsvp',
			Repositories\Ticket_Repository::class
		);
		$this->container->bind(
			'tickets.attendee-repository.rsvp',
			Repositories\Attendee_Repository::class
		);
	}
}
