<?php
/**
 * Manage seat selection data for attendee.
 *
 * @since TBD
 *
 * @package TEC/Tickets/Seating/Orders
 */

namespace TEC\Tickets\Seating\Orders;

use TEC\Tickets\Seating\Meta;
use Tribe__Main as Common;
use TEC\Tickets\Admin\Attendees\Page as Attendees_Page;

/**
 * Class Attendee
 *
 * @since TBD
 *
 * @package TEC/Tickets/Seating/Orders
 */
class Attendee {
	/**
	 * Adds the attendee seat column to the attendee list.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $columns The columns for the Attendees table.
	 * @param int                 $event_id The event ID.
	 *
	 * @return array The filtered columns for the Attendees table.
	 */
	public function add_attendee_seat_column( array $columns, int $event_id ): array {
		$event_layout_id = get_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, true );
		
		if ( empty( $event_layout_id ) && ! tribe( Attendees_Page::class )->is_on_page() ) {
			return $columns;
		}
		
		return Common::array_insert_after_key(
			'ticket',
			$columns,
			[ 'seat' => esc_html_x( 'Seat', 'attendee table seat column header', 'event-tickets' ) ]
		);
	}
	
	/**
	 * Renders the seat column for the attendee list.
	 *
	 * @since TBD
	 *
	 * @param string              $value  Row item value.
	 * @param array<string,mixed> $item   Row item data.
	 * @param string              $column Column name.
	 *
	 * @return string The rendered column.
	 */
	public function render_seat_column( $value, $item, $column ) {
		if ( 'seat' !== $column ) {
			return $value;
		}
		
		$seat_label = get_post_meta( $item['ID'], Meta::META_KEY_ATTENDEE_SEAT_LABEL, true );
		
		if ( empty( $seat_label ) ) {
			return '';
		}
		
		return $seat_label;
	}
}
