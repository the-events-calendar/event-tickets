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
use WP_Query;
use Tribe__Tickets__Attendee_Repository as Attendee_Repository;
use Tribe__Utils__Array as Arr;
use TEC\Tickets\Commerce\Attendee as Commerce_Attendee;
use TEC\Tickets\Seating\Service\Reservations;

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
	 * @param array<string,string> $columns The columns for the Attendees table.
	 * @param int                  $event_id The event ID.
	 *
	 * @return array<string,string> The filtered columns for the Attendees table.
	 */
	public function add_attendee_seat_column( array $columns, int $event_id ): array {
		$event_layout_id = get_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, true );
		
		if ( $event_id && empty( $event_layout_id ) ) {
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
	 * @return string The rendered value.
	 */
	public function render_seat_column( $value, $item, $column ) {
		if ( 'seat' !== $column ) {
			return $value;
		}
		
		$seat_label = get_post_meta( $item['ID'], Meta::META_KEY_ATTENDEE_SEAT_LABEL, true );
		
		if ( ! empty( $seat_label ) ) {
			return $seat_label;
		}
		
		$ticket_id   = Arr::get( $item, 'product_id' );
		$slr_enabled = get_post_meta( $ticket_id, Meta::META_KEY_ENABLED, true );
		
		return $slr_enabled ? __( 'Unassigned', 'event-tickets' ) : '';
	}
	
	/**
	 * Include seats in sortable columns list.
	 *
	 * @param array<string,string> $columns The list of columns.
	 *
	 * @return array<string,string> The filtered columns.
	 */
	public function filter_sortable_columns( array $columns ): array {
		$columns['seat'] = 'seat';
		
		return $columns;
	}
	
	/**
	 * Handle seat column sorting.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $query_args An array of the query arguments the query will be initialized with.
	 * @param WP_Query            $query The query object, the query arguments have not been parsed yet.
	 * @param Attendee_Repository $repository This repository instance.
	 *
	 * @return array<string,mixed> The query args.
	 */
	public function handle_sorting_seat_column( $query_args, $query, $repository ): array {
		$order_by = Arr::get( $query_args, 'orderby' );
		
		if ( 'seat' !== $order_by ) {
			return $query_args;
		}
		
		$order = Arr::get( $query_args, 'order', 'asc' );
		
		global $wpdb;
		
		$meta_alias     = 'seat_label';
		$meta_key       = Meta::META_KEY_ATTENDEE_SEAT_LABEL;
		$postmeta_table = "orderby_{$meta_alias}_meta";
		$filter_id      = 'order_by_seat_label';
		
		$repository->filter_query->join(
			"
			LEFT JOIN {$wpdb->postmeta} AS {$postmeta_table}
				ON (
					{$postmeta_table}.post_id = {$wpdb->posts}.ID
					AND {$postmeta_table}.meta_key = '{$meta_key}'
				)
			",
			$filter_id,
			true
		);
		
		$repository->filter_query->orderby( [ $meta_alias => $order ], $filter_id, true, false );
		$repository->filter_query->fields( "{$postmeta_table}.meta_value AS {$meta_alias}", $filter_id, true );
		
		return $query_args;
	}
	
	/**
	 * Remove move row action from attendee list for seated tickets.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $actions The list of actions.
	 * @param array<string,mixed> $item    The item being acted upon.
	 *
	 * @return array<string,mixed> The filtered actions.
	 */
	public function remove_move_row_action( $actions, $item ) {
		if ( ! isset( $actions['move-attendee'] ) ) {
			return $actions;
		}
		
		$ticket_id   = Arr::get( $item, 'product_id' );
		$slr_enabled = get_post_meta( $ticket_id, Meta::META_KEY_ENABLED, true );
		
		if ( $slr_enabled ) {
			unset( $actions['move-attendee'] );
		}
		
		return $actions;
	}
	
	/**
	 * Handle attendee delete.
	 *
	 * @param int          $attendee_id The Attendee ID.
	 * @param Reservations $reservations The Reservations object.
	 *
	 * @return int The attendee ID.
	 */
	public function handle_attendee_delete( int $attendee_id, Reservations $reservations ): int {
		$event_id       = get_post_meta( $attendee_id, Commerce_Attendee::$event_relation_meta_key, true );
		$reservation_id = get_post_meta( $attendee_id, Meta::META_KEY_RESERVATION_ID, true );
		
		if ( ! $reservation_id ) {
			return $attendee_id;
		}
		
		$cancelled = $reservations->cancel( $event_id, [ $reservation_id ] );
		
		// Bail attendee deletion by returning 0, if the reservation was not cancelled.
		if ( ! $cancelled ) {
			return 0;
		}
		
		return $attendee_id;
	}
}
