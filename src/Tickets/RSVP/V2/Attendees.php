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
	 * This method filters a method that is managing its cache; for this reason this specific method is not caching
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

		}

		$post_type = is_numeric( $post_id ) ?
			get_post_type( $post_id ) :
			// An order hash that is really an Order ID.
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

		if ( $provider && ! $provider instanceof Module ) {
			return $args;
		}

		// Exclude RSVP attendees from the count.
		$args['by']['meta_not_equals'] = [ '_type', Constants::TC_RSVP_TYPE ];

		return $args;
	}
}
