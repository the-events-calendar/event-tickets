<?php

namespace TEC\Tickets\Admin;

use Tribe__Tickets__Tickets;

/**
 * Class Glance_Items
 *
 * @since 5.5.10
 *
 * @package TEC\Tickets\Admin
 */
class Glance_Items {

	/**
	 * The key for the transient that stores the attendee count.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private string $attendee_count_key = 'tec_tickets_glance_item_attendees_count';

	/**
	 * Method to register glance items related hooks.
	 *
	 * @since 5.5.10
	 */
	public function hooks() {
		add_filter( 'dashboard_glance_items', [ $this, 'custom_glance_items_attendees' ], 10, 1 );
	}

	/**
	 * Custom glance item for Attendees count.
	 *
	 * @param array $items The array of items to be displayed.
	 * @return array $items The maybe modified array of items to be displayed.
	 */
	public function custom_glance_items_attendees( $items = [] ): array {
		$results = Tribe__Tickets__Tickets::get_attendees_by_args( [] );
		$total   = count( $results['attendees'] );

		if ( empty( $total ) ) {
			return $items;
		}

		// Translators: %s Is the number of attendees.
		$text = _n( '%s Attendee', '%s Attendees', $total, 'event-tickets' );
		$text = sprintf( $text, number_format_i18n( $total ) );

		$items[] = sprintf( '<span class="tec-tickets-attendees-count">%1$s</span>', $text ) . "\n";

		return $items;
	}

	/**
	 * Update the attendee count.
	 *
	 * @since TBD
	 */
	public function update_attendee_count() {
		$results = Tribe__Tickets__Tickets::get_attendees_by_args( [] );
		$total   = count( $results['attendees'] );

		if ( empty( $total ) ) {
			return;
		}

		set_transient( $this->attendee_count_key, $total, DAY_IN_SECONDS );
	}
}
