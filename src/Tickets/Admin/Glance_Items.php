<?php
/**
 * Glance Items for the WordPress Dashboard.
 *
 * @since 5.5.10
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin;

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
	 * @since 5.6.0
	 *
	 * @var string
	 */
	protected static string $attendee_count_key = 'tec_tickets_glance_item_attendees_count';

	/**
	 * Check if the attendee count glance item is enabled.
	 *
	 * Return false to disable the count entirely (no cron scheduled, no display).
	 * Useful on high-volume sites where even the transient-backed display is unwanted.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the glance item attendee count is enabled. Default true.
	 */
	private function is_enabled(): bool {
		/**
		 * Filters whether the attendee count glance item is enabled.
		 *
		 * @since TBD
		 *
		 * @param bool $enabled Whether the glance item attendee count is enabled. Default true.
		 *
		 * @return bool
		 */
		return apply_filters( 'tec_tickets_glance_item_attendee_count_enabled', true );
	}

	/**
	 * Method to register glance items related hooks.
	 *
	 * @since 5.5.10
	 */
	public function hooks() {
		add_filter( 'dashboard_glance_items', [ $this, 'custom_glance_items_attendees' ], 10, 1 );
		add_action( 'tec_tickets_update_glance_item_attendee_counts', [ $this, 'update_attendee_count' ] );
	}

	/**
	 * Custom glance item for Attendees count.
	 *
	 * @since 5.6.0 Make use of transients and cron jobs to avoid performance issues.
	 * @since TBD Add filter to allow disabling the glance item attendee count.
	 *
	 * @param array $items The array of items to be displayed.
	 * @return array $items The maybe modified array of items to be displayed.
	 */
	public function custom_glance_items_attendees( $items = [] ): array {
		if ( ! $this->is_enabled() ) {
			return (array) $items;
		}

		$total = get_transient( static::$attendee_count_key );

		if ( false === $total ) {
			if ( ! wp_next_scheduled( 'tec_tickets_update_glance_item_attendee_counts' ) ) {
				wp_schedule_single_event( time(), 'tec_tickets_update_glance_item_attendee_counts' );
			}
			return (array) $items;
		}

		// Translators: %s Is the number of attendees.
		$text = _n( '%s Attendee', '%s Attendees', $total, 'event-tickets' );
		$text = sprintf( $text, number_format_i18n( $total ) );

		if ( ! tec_tickets_attendees_page_is_enabled() || ! tribe( 'tickets.attendees' )->user_can_manage_attendees() ) {
			$items[] = sprintf( '<span class="tec-tickets-attendees-count">%1$s</span>', $text ) . "\n";
		} else {
			$items[] = sprintf( '<a class="tec-tickets-attendees-count" href="%1$s">%2$s</a>', esc_url( tribe( Attendees\Page::class )->get_url() ), $text ) . "\n";
		}

		return $items;
	}

	/**
	 * Update the attendee count.
	 *
	 * @since 5.6.0
	 * @since TBD Replace full object hydration with a single COUNT query via the Repository.
	 * @since TBD Always persist the transient (even when count is zero) to prevent infinite cron rescheduling.
	 * @since TBD Exclude RSVP "not going" attendees via the `rsvp_status__or_none` Repository filter.
	 */
	public function update_attendee_count() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$total = tribe_attendees()->where( 'rsvp_status__or_none', 'yes' )->per_page( 1 )->found();

		set_transient( static::$attendee_count_key, (int) $total, DAY_IN_SECONDS );
	}
}
