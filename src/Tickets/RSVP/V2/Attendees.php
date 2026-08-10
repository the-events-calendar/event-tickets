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
use TEC\Tickets\Commerce\Emails\Order_Type_Trait;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Voided;
use TEC\Tickets\Commerce\Ticket as Commerce_Ticket;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_Post;

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
	use Order_Type_Trait;

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
	 * Filters the arguments used to count the Attendees in the Tickets View data link.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $args    The arguments used to count the attendees.
	 * @param int                 $post_id The post ID the Attendees are being counted for.
	 * @param int|null            $user_id The user ID, if any. Unused.
	 * @param string|null         $context The context of the query.
	 *
	 * @return array<string,mixed> The filtered arguments.
	 */
	public function exclude_rsvp_tickets_from_tickets_view_data_link_count( array $args, int $post_id, ?int $user_id, ?string $context ): array {
		if ( 'get_my_tickets_link_data' !== $context ) {
			return $args;
		}

		// In RSVP v2 the Tickets Commerce provider is active by default; an empty provider means TC.
		$provider = tribe_tickets_get_ticket_provider( $post_id );

		// There is a provider and it's not TC, then bail.
		if ( ! empty( $provider ) && ! $provider instanceof Module ) {
			return $args;
		}

		// Exclude RSVP attendees from the count.
		$args['by']['meta_not_equals'] = [ '_type', Constants::TC_RSVP_TYPE ];

		return $args;
	}

	/**
	 * Stamps the RSVP status meta on a TC-RSVP Attendee created through Tickets Commerce.
	 *
	 * Hooked to `tec_tickets_commerce_attendee_after_create`. TC-RSVP Attendees are created down two
	 * separate paths: the RSVP repository (guarded in `Attendee_Repository::filter_postarr_for_create()`)
	 * and this one, which the Attendees screen's "Add Attendee" form reaches by way of a manual Order.
	 * Both have to stamp the status, because the RSVP repository scopes every query to Attendees that
	 * carry it and an Attendee without it cannot be looked up, and so cannot be edited.
	 *
	 * @since TBD
	 *
	 * @param mixed $attendee The created Attendee post.
	 * @param mixed $order    The Order that generated the Attendee.
	 * @param mixed $ticket   The Ticket that generated the Attendee.
	 *
	 * @return void
	 */
	public function ensure_rsvp_status_on_create( $attendee, $order, $ticket ): void {
		if ( ! $attendee instanceof WP_Post ) {
			return;
		}

		if ( ! $ticket instanceof Ticket_Object || Constants::TC_RSVP_TYPE !== $ticket->type() ) {
			return;
		}

		if ( metadata_exists( 'post', $attendee->ID, Constants::RSVP_STATUS_META_KEY ) ) {
			return;
		}

		// Prefer the answer carried on the Order item; an Attendee added by hand has none, so assume Going.
		$status = 'yes';

		if ( $order instanceof WP_Post && ! empty( $order->items ) && is_array( $order->items ) ) {
			foreach ( $order->items as $item ) {
				if ( (int) ( $item['ticket_id'] ?? 0 ) !== (int) $ticket->ID ) {
					continue;
				}

				if ( isset( $item['extra']['order_status'] ) ) {
					$status = tribe_is_truthy( $item['extra']['order_status'] ) ? 'yes' : 'no';
				}

				break;
			}
		}

		update_post_meta( $attendee->ID, Constants::RSVP_STATUS_META_KEY, $status );
	}

	/**
	 * Registers the label and icon the Attendees page Ticket Overview uses for TC-RSVP tickets.
	 *
	 * Hooked to `tec_tickets_attendees_page_render_context`. That page groups tickets by
	 * `Ticket_Object::type()` and looks the group up in two maps that only know `default` and `rsvp`.
	 * A TC-RSVP ticket reports `tc-rsvp`, so without these entries the template falls back to printing
	 * the raw type slug as the heading and renders no icon.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $context The Attendees page render context.
	 *
	 * @return array<string,mixed> The context with the TC-RSVP label and icon registered.
	 */
	public function add_ticket_overview_type_labels( $context ): array {
		if ( ! is_array( $context ) ) {
			return $context;
		}

		// Share the RSVP label and icon: to everyone but the code, a TC-RSVP is just an RSVP.
		if ( isset( $context['type_labels'] ) && is_array( $context['type_labels'] ) ) {
			$context['type_labels'][ Constants::TC_RSVP_TYPE ] = tribe_get_rsvp_label_plural( 'attendee overview' );
		}

		if ( isset( $context['type_icon_classes'] ) && is_array( $context['type_icon_classes'] ) ) {
			$context['type_icon_classes'][ Constants::TC_RSVP_TYPE ] = 'tec-tickets__admin-attendees-overview-ticket-type-icon--rsvp';
		}

		return $context;
	}

	/**
	 * Provides the TC-RSVP attendee data for a user and event.
	 *
	 * The RSVP V1 handler cannot build attendee data for TC-RSVP attendees: this method
	 * queries the RSVP attendee repository and builds the data through the Tickets Commerce
	 * module, shaping it like the RSVP V1 data the My Tickets templates expect.
	 *
	 * Hooked to `tec_tickets_rsvp_get_attendees_by_user_id_pre`.
	 *
	 * @since TBD
	 *
	 * @param null|array<array<string,mixed>> $attendees Null by default.
	 * @param int                             $user_id   The user ID.
	 * @param int                             $post_id   The post ID.
	 *
	 * @return array|null Either the attendee data, or null to let the default logic run.
	 */
	public function get_rsvp_attendees_by_user_id( $attendees, $user_id, $post_id ): ?array {
		if ( $attendees !== null ) {
			// Already filtered, bail.
			return $attendees;
		}

		if ( empty( $post_id ) ) {
			// Let the default logic run.
			return null;
		}

		$repository = tribe( 'tickets.attendee-repository.rsvp' );

		$attendee_ids = $repository
			->where( 'user', $user_id )
			->where( 'event', $post_id )
			->order_by( 'ID', 'ASC' )
			->get_ids( true );

		$commerce = tribe( Module::class );

		$attendee_data = [];

		foreach ( $attendee_ids as $attendee_id ) {
			$data = $commerce->get_attendee( $attendee_id, $post_id );

			if ( ! $data ) {
				continue;
			}

			// The My Tickets RSVP templates expect RSVP V1-shaped attendee data.
			$data['order_status']  = 'no' === get_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, true ) ? 'no' : 'yes';
			$data['purchase_time'] = get_the_date( 'Y-m-d H:i:s', $attendee_id );
			$data['ticket_exists'] = true;

			$attendee_data[] = $data;
		}

		return $attendee_data;
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
		$status_text = $is_going ? __( 'Going', 'event-tickets' ) : __( 'Not Going', 'event-tickets' );

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
	 * Voids an RSVP order once its last attendee is deleted.
	 *
	 * Hooked to `tec_tickets_commerce_attendee_before_delete`, which fires for every Tickets
	 * Commerce attendee deletion — including real paid-ticket orders — so this method bails unless
	 * the attendee's order is composed exclusively of RSVP items and no other live attendee remains
	 * on it.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id The ID of the attendee being deleted.
	 *
	 * @return void
	 */
	public function void_order_after_last_attendee_deleted( int $attendee_id ): void {
		$attendee = get_post( $attendee_id );

		// Bail if the attendee no longer exists, is not a TC attendee, or is not attached to an order.
		if (
			! $attendee instanceof \WP_Post
			|| Attendee::POSTTYPE !== $attendee->post_type
			|| empty( $attendee->post_parent )
		) {
			return;
		}

		$order = tec_tc_get_order( $attendee->post_parent );

		// Bail if the parent order no longer exists.
		if ( ! $order instanceof \WP_Post ) {
			return;
		}

		// This hook fires for ALL Tickets Commerce attendees: never void a real paid-ticket order.
		if ( ! $this->is_rsvp_order( $order ) ) {
			return;
		}

		// Bail if the order was already voided or trashed.
		if ( in_array( $order->post_status, [ tribe( Voided::class )->get_wp_slug(), 'trash' ], true ) ) {
			return;
		}

		// Fetch the other live attendees left on the order, excluding the one being deleted.
		$remaining_attendees = array_diff(
			tribe( 'tickets.attendee-repository.rsvp' )->where( 'order', $order->ID )->get_ids(),
			[ $attendee_id ]
		);

		// Keep the order alive while at least one attendee remains on it.
		if ( ! empty( $remaining_attendees ) ) {
			return;
		}

		tribe( Order::class )->modify_status( $order->ID, Voided::SLUG );
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
