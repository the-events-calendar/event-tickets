<?php
namespace Tribe\Tickets\Onboarding\Tour\Admin;

use Tribe\Onboarding\Tour_Abstract;
/**
 * Class EventsAttendees
 *
 * @since   TBD
 */
class EventsAttendees extends Tour_Abstract {

	/**
	 * The tour ID.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $tour_id = 'event_tickets_tour_attendees';

	/**
	 * Returns if it's on the page we want to display the tour for.
	 *
	 * @since TBD
	 *
	 * @return bool True if it's on page.
	 */
	public function is_on_page() {
		/** @var Tribe__Admin__Helpers $admin_helpers */
		$admin_helpers = tribe( 'admin.helpers' );

		$page = tribe_get_request_var( 'page' );

		return $admin_helpers->is_screen( 'tribe_events_page_tribe-common' )
			&& ( ! empty( $page ) && 'tickets-attendees' === $page );
	}

	/**
	 * Tour steps.
	 *
	 * @since TBD
	 *
	 * @return array $steps The tour steps
	 */
	public function steps() {

		$steps = [
			[
				'title'  => __( '👪 Welcome to the attendees panel', 'tec-ext-events-experiments' ),
				'intro'  => __( 'On this section you can do everything attendee related.', 'tec-ext-events-experiments' ),
			],
			[
				'title'  => __( '✉️ The Event Details', 'tec-ext-events-experiments' ),
				'element' => '#welcome-panel-first',
				'intro'   => __( 'Here you can find the event details.', 'tec-ext-events-experiments' ),
			],
			[
				'title'  => __( 'Check the Overview', 'tec-ext-events-experiments' ),
				'element' => '.welcome-panel-middle',
				'intro'  => __( 'This is the overview section, where you see the number of attendees per ticket.', 'tec-ext-events-experiments' )
			],
			[
				'title'  => __( 'Checked in and tickets sold', 'tec-ext-events-experiments' ),
				'element' => '.welcome-panel-last',
				'intro'  => __( 'Here you can find the RSVPs and tickets info.', 'tec-ext-events-experiments' )
			],
		];

		return $steps;
	}

	/**
	 * Tour CSS Classes.
	 *
	 * @since TBD
	 *
	 * @return array $css_classes The tour extra CSS classes.
	 */
	public function css_classes() {

		return [ 'my-awesome-css-class' ];
	}

}
