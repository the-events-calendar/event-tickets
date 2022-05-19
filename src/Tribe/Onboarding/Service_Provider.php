<?php
/**
 * The main service provider for Onboarding
 *
 * @since   TBD
 * @package Tribe\Tickets\Onboarding
 */

namespace Tribe\Tickets\Onboarding;

use Tribe\Tickets\Onboarding\Tour\Admin\EventsAttendees;

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
	 * @since TBD
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
		add_filter( 'tribe_onboarding_tours', [ $this, 'filter_register_tours' ] );
	}

	/**
	 * Register tours.
	 *
	 * @see   \Tribe\Onboarding\Main::get_registered_tours()
	 *
	 * @since TBD
	 *
	 * @param array $tours An associative array of tours in the shape `[ <tour_id> => <class> ]`.
	 *
	 * @return array
	 */
	public function filter_register_tours( array $tours ) {
		$tours['event_tickets_tour_attendees'] = EventsAttendees::class;

		return $tours;
	}
}
