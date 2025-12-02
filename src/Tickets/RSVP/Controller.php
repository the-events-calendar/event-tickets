<?php
/**
 * Main RSVP Controller.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Main controller for RSVP functionality.
 *
 * This controller decides whether to register V1 (full functionality)
 * or RSVP_Disabled (null-object) based on configuration.
 *
 * @since TBD
 */
class Controller extends Controller_Contract {

	/**
	 * Constant name for disabling RSVP.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const DISABLED = 'TEC_TICKETS_RSVP_DISABLED';

	/**
	 * Always returns true - this Controller always registers.
	 *
	 * The Controller decides whether to register V1 (full functionality)
	 * or RSVP_Disabled (null-object) based on is_rsvp_enabled().
	 *
	 * @since TBD
	 *
	 * @return bool Always true.
	 */
	public function is_active(): bool {
		return true;
	}

	/**
	 * Checks if RSVP functionality is enabled.
	 *
	 * @since TBD
	 *
	 * @return bool Whether RSVP is enabled.
	 */
	public function is_rsvp_enabled(): bool {
		// Check constant.
		if ( defined( self::DISABLED ) && constant( self::DISABLED ) ) {
			return false;
		}

		// Check environment variable.
		if ( getenv( self::DISABLED ) ) {
			return false;
		}

		// Check option (developer-only, no UI).
		$active = (bool) get_option( 'tec_tickets_rsvp_active', true );

		/**
		 * Filters whether RSVP functionality is enabled.
		 *
		 * @since TBD
		 *
		 * @param bool $active Whether RSVP is active.
		 */
		return (bool) apply_filters( 'tec_tickets_rsvp_enabled', $active );
	}

	/**
	 * Registers the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		if ( $this->is_rsvp_enabled() ) {
			// Register V1 Controller (full RSVP functionality).
			$this->container->register( V1\Controller::class );
		} else {
			// Register null-object implementations.
			$this->container->singleton( 'tickets.rsvp', RSVP_Disabled::class );

			// Register null-object repositories that return empty results.
			// Repositories must use bind(), not singleton(), to return a fresh instance on each call.
			$this->container->bind( 'tickets.ticket-repository.rsvp', Repositories\Ticket_Repository_Disabled::class );
			$this->container->bind( 'tickets.attendee-repository.rsvp', Repositories\Attendee_Repository_Disabled::class );
		}
	}

	/**
	 * Unregisters the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		// V1 Controller handles its own unregistration if registered.
		if ( $this->is_rsvp_enabled() && V1\Controller::is_registered() ) {
			tribe( V1\Controller::class )->unregister();
		}
	}
}
