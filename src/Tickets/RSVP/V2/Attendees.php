<?php
/**
 * V2 Attendance Totals class for RSVP.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Voided;
use TEC\Tickets\Commerce\Ticket as Commerce_Ticket;

/**
 * Class Attendees
 *
 * Calculates attendance totals for RSVP tickets in V2 implementation.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Attendees {
	/**
	 * The method filters the default Attendee ID fetching done in RSVP (Tribe__Tickets__RSVP) class to
	 * use the RSVP Tickets Commerce repository instead.
	 *
	 * This method filters a method managing its cache; for this reason this specific method is not caching
	 * to avoid stale values that the original method might have cached.
	 *
	 * @since TBD
	 *
	 * @param null|array<array<string,mixed>> $attendees     The attendee IDs, or null if not set.
	 * @param int                             $post_id       The post ID, it could be the post ID of an Attendee, a
	 *                                                       Ticket, an Order hash or the ID of the post the Attendees
	 *                                                       are related to.
	 *
	 * @return array|null Either the post type of the post indicated by the post ID, or null to indicate
	 */
	public function get_rsvp_attendees_by_id( $attendees, $post_id ): ?array {
		if ( $attendees !== null ) {
			// Already filtered, bail.
			return $attendees;
		}

		$post_type = is_numeric( $post_id ) ?
			get_post_type( $post_id ) :
			// Extending repositories might filter by order hash and support it.
			'rsvp_order_hash';

		$repository = tribe( 'tickets.attendee-repository.rsvp' );

		switch ( $post_type ) {
			case Commerce_Ticket::POSTTYPE:
				$attendees = iterator_to_array(
					$repository
						->where( 'ticket', $post_id )
						->order_by( 'ID', 'ASC' )
						->get_ids(
							true
						),
					false
				);
				break;

			case Attendee::POSTTYPE:
				$attendees = [ $post_id ];
				break;

			case Order::POSTTYPE:
			case 'rsvp_order_hash':
				/**
				 * Filter using the order hash.
				 * By default, sanitized to string, but leave the door open to extensions using hashes.
				 */
				$attendees = iterator_to_array(
					$repository
						->where( 'order', $post_id )
						->order_by( 'ID', 'ASC' )
						->get_ids( true ),
					false
				);
				break;

			default:
				$attendees = iterator_to_array(
					$repository
						->where( 'event', $post_id )
						->order_by( 'ID', 'ASC' )
						->get_ids( true ),
					false
				);
				break;
		}

		$commerce = tribe( Module::class );

		return array_map( static fn( $attendee ) => $commerce->get_attendee( $attendee ), $attendees );
	}

	/**
	 * Replaces the order-status label with a "Going" / "Not Going" indicator for TC RSVP attendees.
	 *
	 * Hooked to `tribe_tickets_attendees_table_order_status`. All RSVP attendees have a
	 * "Completed" order status, so the going/not-going answer is read from attendee meta.
	 *
	 * @since TBD
	 *
	 * @param string                     $label The order-status HTML built by the attendees table.
	 * @param array<string,mixed>|object $item  The attendees-table row item.
	 *
	 * @return string The (possibly) modified status label.
	 */
	public function modify_status_display( $label, $item ): string {
		$status = $this->get_item_rsvp_status( $item );

		if ( null === $status ) {
			return $label;
		}

		$is_going    = 'no' !== $status;
		$status_text = __( 'Not Going', 'event-tickets' );

		if ( $is_going ) {
			$status_text = __( 'Going', 'event-tickets' );
		}

		// Reuse the existing status-pill styling: blue-grey for going, amber for not going.
		$classes = [
			'tec-tickets__admin-table-attendees-order-status',
			'tec-tickets__admin-table-attendees-order-status--tc-rsvp',
			'tec-tickets__admin-table-attendees-order-status--' . ( $is_going ? 'going' : 'not-going' ),
			'tec-tickets__admin-table-attendees-order-status--' . ( $is_going ? 'completed' : 'cancelled' ),
		];

		return sprintf(
			'<div class="tec-tickets__admin-table-attendees-order-status-wrapper"><span class="%1$s">%2$s</span></div>',
			esc_attr( implode( ' ', $classes ) ),
			esc_html( $status_text )
		);
	}

	/**
	 * Hides the check-in column control for TC RSVP attendees who are not going.
	 *
	 * Hooked to `tec_tickets_attendees_table_column_check_in`.
	 *
	 * @since TBD
	 *
	 * @param string                     $content The check-in column HTML.
	 * @param array<string,mixed>|object $item    The attendees-table row item.
	 *
	 * @return string The (possibly) emptied check-in content.
	 */
	public function modify_checkin_display( $content, $item ): string {
		if ( 'no' === $this->get_item_rsvp_status( $item ) ) {
			return '';
		}

		return $content;
	}

	/**
	 * Removes the check-in row action for TC RSVP attendees who are not going.
	 *
	 * Hooked to `event_tickets_attendees_table_row_actions`.
	 *
	 * @since TBD
	 *
	 * @param array<int|string,string>   $actions The row actions.
	 * @param array<string,mixed>|object $item    The attendees-table row item.
	 *
	 * @return array<int|string,string> The (possibly) filtered row actions.
	 */
	public function modify_row_actions( $actions, $item ): array {
		if ( 'no' !== $this->get_item_rsvp_status( $item ) ) {
			return (array) $actions;
		}

		// Drop the check-in / undo check-in action; not-going attendees cannot be checked in.
		foreach ( (array) $actions as $key => $action ) {
			if ( false !== strpos( (string) $action, 'tickets_checkin' ) ) {
				unset( $actions[ $key ] );
			}
		}

		return (array) $actions;
	}

	/**
	 * Resolves the RSVP "going" status for an attendees-table row.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>|object $item The attendees-table row item.
	 *
	 * @return string|null 'no' when the attendee is not going, 'yes' when going, or null when the
	 *                     row is not a TC RSVP attendee and should be left untouched.
	 */
	private function get_item_rsvp_status( $item ): ?string {
		$item = (array) $item;

		if ( empty( $item['ticket_type'] ) || Constants::TC_RSVP_TYPE !== $item['ticket_type'] ) {
			return null;
		}

		$attendee_id = (int) ( $item['attendee_id'] ?? $item['ID'] ?? 0 );

		if ( ! $attendee_id ) {
			return null;
		}

		// Only an explicit "no" counts as not going; anything else is treated as going.
		return 'no' === get_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, true ) ? 'no' : 'yes';
	}

	/**
	 * Voids the hidden TC-RSVP Order once its last Attendee is deleted.
	 *
	 * TC-RSVP Orders are backend-only Orders created to back RSVP attendee records; nothing else marks
	 * them as no-longer-relevant once every Attendee is gone, so they are otherwise left behind as
	 * permanently "Completed" orphans.
	 *
	 * Hooked to `tec_tickets_commerce_attendee_before_delete`, which fires before the Attendee post (and
	 * its meta) is removed, so the Ticket/Order relation is still readable here.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id The Attendee post ID about to be deleted.
	 *
	 * @return void
	 */
	public function void_order_after_last_attendee_deleted( int $attendee_id ): void {
		$ticket_id = (int) get_post_meta( $attendee_id, Attendee::$ticket_relation_meta_key, true );

		if ( ! $ticket_id || ! tribe( Ticket::class )->is_rsvp( $ticket_id ) ) {
			return;
		}

		$order_id = wp_get_post_parent_id( $attendee_id );

		if ( ! $order_id ) {
			return;
		}

		// `order_id` is only a valid alias for *updates* on this repository; `post_parent` is the real query filter.
		$remaining_attendees = tec_tc_attendees()->by( 'post_parent', $order_id )->count();

		if ( $remaining_attendees > 1 ) {
			// Other Attendees still exist on this Order; leave its status alone.
			return;
		}

		tribe( Order::class )->modify_status( $order_id, Voided::SLUG );
	}

	/**
	 * Replaces the order-status label with a "Going" / "Not Going" indicator for TC RSVP attendees.
	 *
	 * Hooked to `tribe_tickets_attendees_table_order_status`. All RSVP attendees have a
	 * "Completed" order status, so the going/not-going answer is read from attendee meta.
	 *
	 * @since TBD
	 *
	 * @param string                     $label The order-status HTML built by the attendees table.
	 * @param array<string,mixed>|object $item  The attendees-table row item.
	 *
	 * @return string The (possibly) modified status label.
	 */
	public function modify_status_display( $label, $item ): string {
		$status = $this->get_item_rsvp_status( $item );

		if ( null === $status ) {
			return $label;
		}

		$is_going    = 'no' !== $status;
		$status_text = __( 'Not Going', 'event-tickets' );

		if ( $is_going ) {
			$status_text = __( 'Going', 'event-tickets' );
		}

		// Reuse the existing status-pill styling: blue-grey for going, amber for not going.
		$classes = [
			'tec-tickets__admin-table-attendees-order-status',
			'tec-tickets__admin-table-attendees-order-status--tc-rsvp',
			'tec-tickets__admin-table-attendees-order-status--' . ( $is_going ? 'going' : 'not-going' ),
			'tec-tickets__admin-table-attendees-order-status--' . ( $is_going ? 'completed' : 'cancelled' ),
		];

		return sprintf(
			'<div class="tec-tickets__admin-table-attendees-order-status-wrapper"><span class="%1$s">%2$s</span></div>',
			esc_attr( implode( ' ', $classes ) ),
			esc_html( $status_text )
		);
	}

	/**
	 * Hides the check-in column control for TC RSVP attendees who are not going.
	 *
	 * Hooked to `tec_tickets_attendees_table_column_check_in`.
	 *
	 * @since TBD
	 *
	 * @param string                     $content The check-in column HTML.
	 * @param array<string,mixed>|object $item    The attendees-table row item.
	 *
	 * @return string The (possibly) emptied check-in content.
	 */
	public function modify_checkin_display( $content, $item ): string {
		if ( 'no' === $this->get_item_rsvp_status( $item ) ) {
			return '';
		}

		return $content;
	}

	/**
	 * Removes the check-in row action for TC RSVP attendees who are not going.
	 *
	 * Hooked to `event_tickets_attendees_table_row_actions`.
	 *
	 * @since TBD
	 *
	 * @param array<int|string,string>   $actions The row actions.
	 * @param array<string,mixed>|object $item    The attendees-table row item.
	 *
	 * @return array<int|string,string> The (possibly) filtered row actions.
	 */
	public function modify_row_actions( $actions, $item ): array {
		if ( 'no' !== $this->get_item_rsvp_status( $item ) ) {
			return (array) $actions;
		}

		// Drop the check-in / undo check-in action; not-going attendees cannot be checked in.
		foreach ( (array) $actions as $key => $action ) {
			if ( false !== strpos( (string) $action, 'tickets_checkin' ) ) {
				unset( $actions[ $key ] );
			}
		}

		return (array) $actions;
	}

	/**
	 * Resolves the RSVP "going" status for an attendees-table row.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>|object $item The attendees-table row item.
	 *
	 * @return string|null 'no' when the attendee is not going, 'yes' when going, or null when the
	 *                     row is not a TC RSVP attendee and should be left untouched.
	 */
	private function get_item_rsvp_status( $item ): ?string {
		$item = (array) $item;

		if ( empty( $item['ticket_type'] ) || Constants::TC_RSVP_TYPE !== $item['ticket_type'] ) {
			return null;
		}

		$attendee_id = (int) ( $item['attendee_id'] ?? $item['ID'] ?? 0 );

		if ( ! $attendee_id ) {
			return null;
		}

		// Only an explicit "no" counts as not going; anything else is treated as going.
		return 'no' === get_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, true ) ? 'no' : 'yes';
	}
}
