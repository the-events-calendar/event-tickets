<?php
/**
 * Maintains the relation between a Series ticket provider and the ticket provider of the Events part of the Series.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Events_Pro\Custom_Tables\V1\Models\Series_Relationship;

/**
 * Class Ticket_Provider_Handler.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Ticket_Provider_Handler {

	/**
	 * Deletes the ticket provider of the Events part of a Series following the deletion of the Series ticket provider.
	 *
	 * @since 5.8.0
	 *
	 * @param int $series_id The ID of the Series.
	 *
	 * @return void The ticket provider of the Events part of the Series are deleted.
	 */
	public function delete_from_series( int $series_id ): void {
		foreach ( tribe_events()->where( 'series', $series_id )->get_ids( true ) as $event_id ) {
			delete_post_meta( $event_id, '_tribe_default_ticket_provider' );
		}

		foreach ( Series_Relationship::where( 'series_post_id', '=', $series_id )->all() as $relationship ) {
			$event_id = $relationship->event_post_id;
			delete_post_meta( $event_id, '_tribe_default_ticket_provider' );
		}
	}

	/**
	 * Updates the ticket provider of the Events part of a Series following the update of the Series ticket provider.
	 *
	 * @since 5.8.0
	 *
	 * @param int         $series_id The ID of the Series.
	 * @param string|null $value     The new ticket provider of the Events part of the Series; if `null` then the
	 *                               current ticket provider of the Series is read from the database.
	 *
	 * @return void The ticket provider of the Events part of the Series are updated.
	 */
	public function update_from_series( int $series_id, string $value = null ): void {
		if ( $value === null ) {
			$value = get_post_meta( $series_id, '_tribe_default_ticket_provider', true );
		}

		foreach ( tribe_events()->where( 'series', $series_id )->get_ids( true ) as $event_id ) {
			update_post_meta( $event_id, '_tribe_default_ticket_provider', $value );
		}

		foreach ( Series_Relationship::where( 'series_post_id', '=', $series_id )->all() as $relationship ) {
			$event_id = $relationship->event_post_id;
			update_post_meta( $event_id, '_tribe_default_ticket_provider', $value );
		}
	}
}