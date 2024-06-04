<?php
/**
 * Handle cart data for assigned seat tickets.
 *
 * @since TBD
 *
 * @package TEC/Tickets/Seating/Orders
 */

namespace TEC\Tickets\Seating\Orders;

use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Seating\Meta;
use Tribe__Tickets__Tickets;
use WP_Post;

/**
 * Class Cart
 *
 * @since TBD
 *
 * @package TEC/Tickets/Seating/Orders
 */
class Cart {
	/**
	 * Handles the seat selection for the cart.
	 *
	 * @since TBD
	 *
	 * @param array $data The data to prepare for the cart.
	 *
	 * @return array The prepared data.
	 */
	public function handle_seat_selection( array $data ): array {
		foreach ( $data['tickets'] as $key => $ticket_data ) {
			if ( ! isset( $ticket_data['seat_labels'] ) ) {
				continue;
			}
			
			$ticket_data['extra']['seats'] = $ticket_data['seat_labels'];
			
			$data['tickets'][ $key ] = $ticket_data;
		}
		
		return $data;
	}
	
	/**
	 * Saves the seat data for the attendee.
	 *
	 * @param WP_Post                 $attendee               The generated attendee.
	 * @param Tribe__Tickets__Tickets $ticket The ticket the attendee is generated for.
	 * @param WP_Post                 $order              The order the attendee is generated for.
	 * @param Status_Interface        $new_status      New post status.
	 * @param Status_Interface|null   $old_status Old post status.
	 * @param array                   $item Which cart item this was generated for.
	 * @param int                     $i      Which Attendee index we are generating.
	 */
	public function save_seat_data_for_attendee( $attendee, $ticket, $order, $new_status, $old_status, $item, $i ) {
		$seats = Arr::get( $item, [ 'extra', 'seats' ], false );
		
		if ( empty( $seats ) || ! isset( $seats[ $i ] ) ) {
			return;
		}
		
		update_post_meta( $attendee->ID, Meta::META_KEY_ATTENDEE_SEAT_LABEL, $seats[ $i ] );
		
		$seat_type = get_post_meta( $ticket->ID, Meta::META_KEY_SEAT_TYPE, true );
		update_post_meta( $attendee->ID, Meta::META_KEY_SEAT_TYPE, $seat_type );
		
		$event_id  = get_post_meta( $ticket->ID, Attendee::$event_relation_meta_key, true );
		$layout_id = get_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, true );
		update_post_meta( $attendee->ID, Meta::META_KEY_LAYOUT_ID, $layout_id );
	}
}
