<?php
/**
 * The main service provider for Onboarding
 *
 * @since   TBD
 * @package Tribe\Tickets\Onboarding
 */

namespace Tribe\Tickets\Onboarding;

/**
 * Class Service_Provider
 *
 * @since   TBD
 * @package Tribe\Tickets\Onboarding
 */
class Service_Provider extends \tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$this->container->singleton( 'tickets.onboarding', self::class );

		$event_attendees = new Tour\Admin\EventsAttendees( $this->container );
		$this->container->singleton( Tour\Admin\EventsAttendees::class, $event_attendees );
		$this->container->singleton( 'tickets.onboarding.tour.admin.events-attendees', $event_attendees );

		$this->hooks();
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions.
	 *
	 * @since TBD
	 */
	protected function hooks() {
		// Events Settings page.
		add_filter(
			'tribe_onboarding_tour_data',
			tribe_callback( 'tickets.onboarding.tour.admin.events-attendees', 'maybe_localize_tour' )
		);
	}
}
