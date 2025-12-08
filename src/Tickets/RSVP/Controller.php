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
	 * Version 1 of RSVP.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const VERSION_1 = 'v1';

	/**
	 * Version 2 of RSVP.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const VERSION_2 = 'v2';

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
	 * Returns the filtered RSVP version to use.
	 *
	 * @since TBD
	 *
	 * @return string The RSVP version to use: one of the `VERSION_*` constants.
	 */
	private function get_rsvp_version(): string {
		/**
		 * Filters the RSVP version to use.
		 *
		 * @since TBD
		 *
		 * @param string $version The RSVP version to use.
		 */
		return apply_filters( 'tec_tickets_rsvp_version', self::VERSION_1 );
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
			$rsvp_version = $this->get_rsvp_version();

			if ( $rsvp_version === self::VERSION_1 ) {
				// Register V1 Controller (full RSVP functionality).
				$this->container->register( V1\Controller::class );

				return;
			}

			if ( $rsvp_version === self::VERSION_2 ) {
				// Register V2 Controller (full RSVP functionality).
				$this->container->register( V2\Controller::class );

				return;
			}

			// Any other version will fallback to the disabled controller.
		}

		// Register null-object implementations handling the RSVP disabled case.
		$this->container->singleton( 'tickets.rsvp', RSVP_Disabled::class );

		// Register null-object repositories that return empty results.
		// Repositories must use bind(), not singleton(), to return a fresh instance on each call.
		$this->container->bind( 'tickets.ticket-repository.rsvp', Repositories\Ticket_Repository_Disabled::class );
		$this->container->bind( 'tickets.attendee-repository.rsvp', Repositories\Attendee_Repository_Disabled::class );
	}

	/**
	 * Unregisters the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		if ( $this->is_rsvp_enabled() ) {
			// If RSVP is enabled, chances are we registered V1 Controller, unregister it.
			$this->container->get( V1\Controller::class )->unregister();
		}
	}
}
