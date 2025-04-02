<?php
/**
 * Manage seat selection data for attendee.
 *
 * @since 5.16.0
 *
 * @package TEC/Tickets/Seating/Orders
 */

namespace TEC\Tickets\Seating\Orders;

use Tribe__Main as Common;
use Tribe__Tickets__Attendee_Repository as Attendee_Repository;
use Tribe__Utils__Array as Arr;
use Tribe__Template as Template;
use TEC\Tickets\Commerce\Attendee as Commerce_Attendee;
use TEC\Tickets\Seating\Service\Reservations;
use TEC\Tickets\Seating\Meta;
use WP_Query;
use WP_Post;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__RSVP as RSVP_Provider;

/**
 * Class Attendee
 *
 * @since 5.16.0
 *
 * @package TEC/Tickets/Seating/Orders
 */
class Attendee {
	/**
	 * Adds the attendee seat column to the attendee list.
	 *
	 * @since 5.16.0
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
	 * @since 5.16.0
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

		if ( ! isset( $item['attendee_id'] ) ) {
			return '-';
		}

		$seat_label = get_post_meta( $item['attendee_id'], Meta::META_KEY_ATTENDEE_SEAT_LABEL, true );

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
	 * @since 5.16.0
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
	 * @since 5.16.0
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

		if ( ! $event_id || ! $reservation_id ) {
			return $attendee_id;
		}

		$cancelled = $reservations->cancel( $event_id, [ $reservation_id ] );

		// Bail attendee deletion by returning 0, if the reservation was not cancelled.
		if ( ! $cancelled ) {
			return 0;
		}

		return $attendee_id;
	}

	/**
	 * Include seating data into the attendee object.
	 *
	 * @since 5.16.0
	 *
	 * @param WP_Post $post The attendee post object, decorated with a set of custom properties.
	 *
	 * @return WP_Post
	 */
	public function include_seating_data( WP_Post $post ): WP_Post {
		$seating_ticket = get_post_meta( $post->product_id, Meta::META_KEY_ENABLED, true );

		if ( ! $seating_ticket ) {
			return $post;
		}

		$seat_label = get_post_meta( $post->ID, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true );

		if ( $seat_label ) {
			$post->seat_label = $seat_label;
		}

		$seat_type_id = get_post_meta( $post->ID, Meta::META_KEY_SEAT_TYPE, true );

		if ( $seat_type_id ) {
			$post->seat_type_id = $seat_type_id;
		}

		$layout_id = get_post_meta( $post->ID, Meta::META_KEY_LAYOUT_ID, true );

		if ( $layout_id ) {
			$post->layout_id = $layout_id;
		}

		return $post;
	}

	/**
	 * Include seat info in email.
	 *
	 * @since 5.16.0
	 *
	 * @param Template $template The email template instance.
	 *
	 * @return void
	 */
	public function include_seat_info_in_email( Template $template ): void {
		$context    = $template->get_local_values();
		$seat_label = Arr::get( $context, [ 'ticket', 'seat_label' ], false );

		if ( ! $seat_label ) {
			return;
		}

		echo wp_kses(
			sprintf(
				'<div class="tec-tickets__email-table-content-ticket-seat-label">%s</div>
				<div class="tec-tickets__email-table-content-ticket-seat-label-separator">|</div>',
				esc_html( $seat_label )
			),
			[
				'div' => [ 'class' => [] ],
			]
		);
	}

	/**
	 * Inject seating label with ticket name on My Tickets page.
	 *
	 * @since 5.16.0
	 *
	 * @param string   $html The HTML content of ticket information.
	 * @param Template $template The email template instance.
	 *
	 * @return string The HTML content of ticket information.
	 */
	public function inject_seat_info_in_my_tickets( string $html, Template $template ): string {
		$context    = $template->get_local_values();
		$seat_label = Arr::get( $context, [ 'attendee', 'seat_label' ], false );

		if ( ! $seat_label ) {
			$ticket_id   = Arr::get( $context, [ 'attendee', 'product_id' ] );
			$slr_enabled = get_post_meta( $ticket_id, Meta::META_KEY_ENABLED, true );
			$seat_label  = $slr_enabled ? __( 'No assigned seat', 'event-tickets' ) : '';
		}

		if ( empty( $seat_label ) ) {
			return $html;
		}

		$head_div = '<div class="tribe-ticket-information">';
		$label    = $head_div . sprintf( '<span class="tec-tickets__ticket-information__seat-label">%s</span>', esc_html( $seat_label ) );

		return str_replace( $head_div, $label, $html );
	}

	/**
	 * Formats a  set of Attendees to the format expected by the Seats Report AJAX request.
	 *
	 * @since 5.16.0
	 *
	 * @param array<array<string,mixed>> $attendees The Attendees to format.
	 *
	 * @return array<array<string,mixed>> The formatted Attendees.
	 */
	public function format_many( array $attendees ): array {
		$unknown_attendee_name = __( 'Unknown', 'event-tickets' );

		// Filter out attendees that are from the RSVP provider.
		$attendees = array_filter(
			$attendees,
			static function ( array $attendee ): bool {
				return RSVP_Provider::class !== $attendee['provider'];
			}
		);

		$associated_attendees = array_reduce(
			$attendees,
			static function ( array $carry, array $attendee ): array {
				if ( ! isset( $attendee['order_id'] ) ) {
					// Can't work out the associated attendees.
					return $carry;
				}

				$order_id = $attendee['order_id'];
				if ( isset( $carry[ $order_id ] ) ) {
					// Already processed this Order ID.
					return $carry;
				}

				$provider           = tribe_tickets_get_ticket_provider( $attendee['product_id'] );
				$carry[ $order_id ] = count( $provider->get_attendees_by_order_id( $order_id ) );

				return $carry;
			},
			[]
		);

		$formatted_attendees = [];
		foreach ( $attendees as $attendee ) {
			$id      = (int) $attendee['attendee_id'];
			$user_id = (int) ( $attendee['user_id'] ?? 0 );
			if ( $user_id > 0 ) {
				$user                       = get_user_by( 'id', $user_id );
				$attendee['purchaser_name'] = $user ? $user->display_name : $unknown_attendee_name;
			} else {
				$attendee['purchaser_name'] ??= $unknown_attendee_name;
			}

			$name = trim( $attendee['holder_name'] ?? '' );
			if ( ! $name ) {
				$name = $attendee['purchaser_name'];
			}
			$order_id = $attendee['order_id'] ?? false;

			$formatted_attendees[] = [
				'id'            => $id,
				'name'          => $name,
				'purchaser'     => [
					'id'                  => $order_id,
					'name'                => $attendee['purchaser_name'],
					'associatedAttendees' => $order_id ? $associated_attendees[ $order_id ] : 0,
				],
				'ticketId'      => $attendee['product_id'],
				'ticketName'    => $attendee['ticket_name'],
				'seatTypeId'    => get_post_meta( $id, Meta::META_KEY_SEAT_TYPE, true ),
				'seatLabel'     => get_post_meta( $id, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ),
				'reservationId' => get_post_meta( $id, Meta::META_KEY_RESERVATION_ID, true ),
			];
		}

		return $formatted_attendees;
	}

	/**
	 * Inject seating label with ticket name on Order success page.
	 *
	 * @since 5.16.0
	 *
	 * @param string   $html The HTML content of ticket information.
	 * @param Template $template The email template instance.
	 *
	 * @return string The HTML content of ticket information.
	 */
	public function inject_seat_info_in_order_success_page( string $html, Template $template ): string {
		$context    = $template->get_local_values();
		$seat_label = Arr::get( $context, [ 'attendee', 'seat_label' ], false );

		if ( ! $seat_label ) {
			$ticket_id   = Arr::get( $context, [ 'attendee', 'product_id' ] );
			$slr_enabled = get_post_meta( $ticket_id, Meta::META_KEY_ENABLED, true );
			$seat_label  = $slr_enabled ? __( 'Unassigned', 'event-tickets' ) : '';
		}

		if ( empty( $seat_label ) ) {
			return $html;
		}

		$head_div = '<div class="tec-tickets__attendees-list-item-attendee-details-ticket">';
		$label    = $head_div . sprintf( '<span class="tec-tickets__ticket-information__seat-label">%s</span>', esc_html( $seat_label ) );

		return str_replace( $head_div, $label, $html );
	}

	/**
	 * Calculates the total number of available seats for a given set of tickets considering their seat type.
	 *
	 * @since 5.16.0
	 *
	 * @param array<string,mixed>  $render_context The render context for the attendee page.
	 * @param int                  $post_id The post ID.
	 * @param array<Ticket_Object> $tickets The tickets for the event.
	 *
	 * @return array<string,mixed> The updated render context.
	 */
	public function adjust_attendee_page_render_context_for_seating( array $render_context, int $post_id, array $tickets ): array {
		$available_by_seat_type = [];

		foreach ( $tickets as $ticket ) {
			$ticket_seat_type = get_post_meta( $ticket->ID, Meta::META_KEY_SEAT_TYPE, true );
			if ( isset( $available_by_seat_type[ $ticket_seat_type ] ) && $ticket->available() <= $available_by_seat_type[ $ticket_seat_type ] ) {
				continue;
			}

			$available_by_seat_type[ $ticket_seat_type ] = $ticket->available();
		}

		$render_context['ticket_totals']['available'] = array_sum( $available_by_seat_type );

		return $render_context;
	}
}
