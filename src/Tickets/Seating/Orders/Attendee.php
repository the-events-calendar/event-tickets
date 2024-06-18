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
		
		if ( empty( $event_layout_id ) ) {
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
		
		if ( empty( $seat_label ) ) {
			return '';
		}
		
		return $seat_label;
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
}
