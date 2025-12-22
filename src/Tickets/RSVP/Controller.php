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
	public const DISABLED = 'TEC_TICKETS_RSVP_DISABLED';

	/**
	 * Name for version 1 of the RSVP implementation.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const VERSION_1 = 'v1';

	/**
	 * Name for version 2 of the RSVP implementation.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const VERSION_2 = 'v2';

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
		if ( ! $this->is_rsvp_enabled() ) {
			$this->register_disabled();

			return;
		}

		/**
		 * Filters the RSVP version to register.
		 *
		 * If the provided version is not one of the supported versions, the feature will be disabled.
		 *
		 * @since TBD
		 *
		 * @param string $version The RSVP version to register.
		 */
		$version = apply_filters( 'tec_tickets_rsvp_version', self::VERSION_1 );

		if ( $version === self::VERSION_1 ) {
			$this->container->register( V1\Controller::class );

			return;
		}

		// RSVP v2 requires Tickets Commerce to be activated to work.
		if ( $version === self::VERSION_2 && tec_tickets_commerce_is_enabled() ) {
			$this->container->register( V2\Controller::class );
			// V2 uses TC infrastructure. Bind repositories but not tickets.rsvp
			// as V2 doesn't need a legacy RSVP provider.
			$this->container->bind( 'tickets.ticket-repository.rsvp', V2\Repositories\Ticket_Repository::class );
			$this->container->bind( 'tickets.attendee-repository.rsvp', V2\Repositories\Attendee_Repository::class );
			// Bind the disabled RSVP for legacy code compatibility.
			$this->container->singleton( 'tickets.rsvp', RSVP_Disabled::class );

			return;
		}

		// If the version is not supported, fallback to disable the feature.
		$this->register_disabled();
	}

	/**
	 * Register null-object implementations for disabled RSVP.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function register_disabled(): void {
		// Register null-object implementations.
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
		if ( ! $this->is_rsvp_enabled() ) {
			return;
		}

		$version = apply_filters( 'tec_tickets_rsvp_version', self::VERSION_1 );

		if ( $version === self::VERSION_1 ) {
			$this->container->get( V1\Controller::class )->unregister();

			return;
		}

		// RSVP v2 requires Tickets Commerce to be activated to work.
		if ( $version === self::VERSION_2 && tec_tickets_commerce_is_enabled() ) {
			$this->container->get( V2\Controller::class )->unregister();
		}
	}
}
