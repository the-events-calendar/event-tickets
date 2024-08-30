<?php
/**
 * Handle cart data for assigned seat tickets.
 *
 * @since TBD
 *
 * @package TEC/Tickets/Seating/Orders
 */

namespace TEC\Tickets\Seating\Orders;

use Generator;
use TEC\Tickets\Seating\Frontend\Session;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Tables\Sessions;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use TEC\Tickets\Commerce\Cart as TicketsCommerce_Cart;
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
	 * A reference to the Session handler.
	 *
	 * @since TBD
	 *
	 * @var Session
	 */
	private Session $session;

	/**
	 * A reference to the Sessions table handler.
	 *
	 * @since TBD
	 *
	 * @var Sessions
	 */
	private Sessions $sessions;

	/**
	 * A memoized list of reservation stack Generators from object ID to ticket ID to reservation data.
	 * We're storing Generators and not arrays to keep the pointer to the current reservation data across
	 * multiple calls to the same ticket.
	 *
	 * @since TBD
	 *
	 * @var array<int,array<int,array<int,Generator<array{
	 *     reservation_id: string,
	 *     seat_type_id: string,
	 *     seat_label: string,
	 * }>>>>
	 */
	private array $session_stacks = [];

	/**
	 * Cart constructor.
	 *
	 * @since TBD
	 *
	 * @param Session  $session A reference to the Session handler.
	 * @param Sessions $sessions A reference to the Sessions table handler.
	 */
	public function __construct( Session $session, Sessions $sessions ) {
		$this->sessions = $sessions;
		$this->session  = $session;
	}

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
	 * Returns a Generator for the reservation stack for the given object ID and ticket ID.
	 *
	 * The Generator is memoized at the class instance level, following calls to the same object ID and ticket ID
	 * will return the same Generator with `current()` pointing to the current reservation data. It's the caller's
	 * responsibility to call `next()` to advance the Generator.
	 *
	 * @since TBD
	 *
	 * @param string $token     The ephemeral token used to secure the iframe communication with the service.
	 * @param int    $object_id The object ID of the post the tickets are attached to.
	 * @param int    $ticket_id The ticket ID the request is for.
	 *
	 * @return Generator<array{
	 *     reservation_id: string,
	 *     seat_type_id: string,
	 *     seat_label: string,
	 * }> The reservation stack for the given object ID and ticket ID.
	 */
	private function get_session_stack( string $token, int $object_id, int $ticket_id ): Generator {
		if ( ! isset( $this->session_stacks[ $object_id ][ $ticket_id ] ) ) {
			$reservations = $this->sessions->get_reservations_for_token( $token );
			foreach ( $reservations as $reservation_ticket_id => $reservation_data ) {
				$generator = static fn(): Generator => yield from $reservation_data;
				$this->session_stacks[ $object_id ][ $reservation_ticket_id ] = $generator();
			}
		}

		return $this->session_stacks[ $object_id ][ $ticket_id ] ?? ( static fn() => yield from [] )();
	}

	/**
	 * Returns the memoized session token and object ID.
	 *
	 * If the values are not memoized, they will be memoized and returned.
	 *
	 * @since TBD
	 *
	 * @return array{0: string, 1: int} The memoized session token and object ID.
	 */
	private function get_session_token_object_id(): array {
		$cache                = tribe_cache();
		$cached_session_token = $cache['tec_tc_session_token_object_id_session_token'] ?? null;
		$cached_object_id     = $cache['tec_tc_session_token_object_id_object_id'] ?? null;

		if ( null === $cached_session_token || null === $cached_object_id ) {
			[ $token, $object_id ]                                 = $this->session->get_session_token_object_id();
			$cached_session_token                                  = $token;
			$cached_object_id                                      = $object_id;
			$cache['tec_tc_session_token_object_id_session_token'] = $cached_session_token;
			$cache['tec_tc_session_token_object_id_object_id']     = $cached_object_id;
		}

		return [ $cached_session_token, $cached_object_id ];
	}

	/**
	 * Saves the seat data for the attendee.
	 *
	 * @since TBD
	 *
	 * @param WP_Post       $attendee   The generated attendee.
	 * @param Ticket_Object $ticket     The ticket the attendee is generated for.
	 */
	public function save_seat_data_for_attendee( WP_Post $attendee, Ticket_Object $ticket ): void {
		[ $token, $object_id ] = $this->get_session_token_object_id();
		$event_id              = (int) $attendee->event_id;

		if ( $event_id && $event_id === (int) $object_id ) {
			$session_stack    = $this->get_session_stack( (string) $token, (int) $object_id, (int) $ticket->ID );
			$reservation_data = $session_stack->current();
			$session_stack->next();
			$reservation_id = $reservation_data['reservation_id'] ?? '';
			update_post_meta( $attendee->ID, Meta::META_KEY_RESERVATION_ID, $reservation_id );
			$seat_label = $reservation_data['seat_label'] ?? '';
			update_post_meta( $attendee->ID, Meta::META_KEY_ATTENDEE_SEAT_LABEL, $seat_label );
			$seat_type_id = $reservation_data['seat_type_id'] ?? '';
			update_post_meta( $attendee->ID, Meta::META_KEY_SEAT_TYPE, $seat_type_id );
		}

		$layout_id = $attendee->product_id ? get_post_meta( $attendee->product_id, Meta::META_KEY_LAYOUT_ID, true ) : false;

		// Add the layout ID to the attendee if it exists for the attendee product.
		if ( $layout_id ) {
			update_post_meta( $attendee->ID, Meta::META_KEY_LAYOUT_ID, $layout_id );
		}
	}
	
	/**
	 * Maybe clear the cart if the session is expired or the session is empty but cart has seated tickets.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function maybe_clear_cart_for_empty_session(): void {
		[ $token, $object_id ] = $this->get_session_token_object_id();
		$cart                  = tribe( TicketsCommerce_Cart::class );
		
		// Check if there are any seating sessions available.
		if ( ! empty( $token ) || ! empty( $object_id ) ) {
			/**
			 * If we have a valid session, we should check if the token is expired or not.
			 * This is to force clear cart for cases where AJAX request may have failed to delete an expired token.
			 */
			if ( $this->sessions->get_seconds_left( $token ) <= 0 ) {
				$cart->clear_cart();
			}
			
			return;
		}
		
		// If the cart has any item with seating enabled then we need to clear the cart.
		foreach ( $cart->get_items_in_cart() as $ticket_id => $item ) {
			if ( get_post_meta( $ticket_id, META::META_KEY_ENABLED, true ) ) {
				$cart->clear_cart();
				break;
			}
		}
	}
}
