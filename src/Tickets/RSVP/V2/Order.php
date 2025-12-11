<?php
/**
 * Handles RSVP V2 order operations.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Tickets\Commerce\Gateways\Free\Gateway as Free_Gateway;
use TEC\Tickets\Commerce\Order as TC_Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use WP_Error;
use WP_Post;

/**
 * Class Order.
 *
 * Handles creation of RSVP orders using composition pattern.
 * Wraps TC Order and uses Free Gateway for auto-completion.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Order {
	/**
	 * Creates an RSVP order from cart items.
	 *
	 * @since TBD
	 *
	 * @param Cart\RSVP_Cart $cart        The RSVP cart containing items.
	 * @param array          $purchaser   The purchaser data (name, email).
	 * @param string         $rsvp_status The RSVP status for attendees ('yes' or 'no'). Default 'yes'.
	 *
	 * @return int|WP_Error The order ID on success, WP_Error on failure.
	 */
	public function create( Cart\RSVP_Cart $cart, array $purchaser, string $rsvp_status = 'yes' ) {
		if ( ! $cart->has_items() ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_empty_cart',
				__( 'Cannot create order from empty cart.', 'event-tickets' )
			);
		}

		if ( empty( $purchaser['name'] ) || empty( $purchaser['email'] ) ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_missing_purchaser',
				__( 'Purchaser name and email are required.', 'event-tickets' )
			);
		}

		// Validate RSVP status.
		if ( ! in_array( $rsvp_status, [ Meta::STATUS_GOING, Meta::STATUS_NOT_GOING ], true ) ) {
			$rsvp_status = Meta::STATUS_GOING;
		}

		$tc_order     = tribe( TC_Order::class );
		$free_gateway = tribe( Free_Gateway::class );
		$ticket       = tribe( Ticket::class );
		$attendee     = tribe( Attendee::class );

		// Prepare purchaser data for TC Order.
		$purchaser_data = [
			'purchaser_name'       => sanitize_text_field( $purchaser['name'] ),
			'purchaser_email'      => sanitize_email( $purchaser['email'] ),
			'purchaser_user_id'    => get_current_user_id(),
			'purchaser_full_name'  => sanitize_text_field( $purchaser['name'] ),
			'purchaser_first_name' => '',
			'purchaser_last_name'  => '',
		];

		// Parse name into first/last.
		$name_parts                             = explode( ' ', $purchaser_data['purchaser_name'], 2 );
		$purchaser_data['purchaser_first_name'] = $name_parts[0] ?? '';
		$purchaser_data['purchaser_last_name']  = $name_parts[1] ?? '';

		// Prepare items from RSVP cart.
		$cart_items = $cart->get_items();
		$items      = [];

		foreach ( $cart_items as $ticket_id => $item ) {
			$ticket_post = get_post( $ticket_id );

			if ( ! $ticket_post ) {
				continue;
			}

			$event_id = get_post_meta( $ticket_id, '_tec_tickets_commerce_event', true );

			$items[] = [
				'ticket_id'         => $ticket_id,
				'event_id'          => $event_id,
				'quantity'          => $item['quantity'],
				'price'             => 0,
				'regular_price'     => 0,
				'sub_total'         => 0,
				'regular_sub_total' => 0,
				'extra'             => $item['extra_data'] ?? [],
			];
		}

		if ( empty( $items ) ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_no_valid_items',
				__( 'No valid items in cart.', 'event-tickets' )
			);
		}

		// Create the order using TC Order.
		$order = $tc_order->create( $free_gateway, $purchaser_data, $items );

		if ( is_wp_error( $order ) ) {
			return $order;
		}

		// Set the order to pending, then completed (auto-complete for free orders).
		$pending_result = $tc_order->modify_status( $order->ID, Pending::SLUG );

		if ( is_wp_error( $pending_result ) ) {
			return $pending_result;
		}

		$completed_result = $tc_order->modify_status( $order->ID, Completed::SLUG );

		if ( is_wp_error( $completed_result ) ) {
			return $completed_result;
		}

		// Create attendees for each item.
		foreach ( $items as $item ) {
			$quantity = $item['quantity'];
			$event_id = $item['event_id'];

			for ( $i = 0; $i < $quantity; $i++ ) {
				$attendee_result = $attendee->create(
					$order->ID,
					$item['ticket_id'],
					[
						'event_id'    => $event_id,
						'name'        => $purchaser_data['purchaser_name'],
						'email'       => $purchaser_data['purchaser_email'],
						'rsvp_status' => $rsvp_status,
						'optout'      => false,
					] 
				);

				if ( is_wp_error( $attendee_result ) ) {
					// Log error but continue with other attendees.
					do_action(
						'tribe_log',
						'error',
						'RSVP V2 attendee creation failed',
						[
							'order_id'  => $order->ID,
							'ticket_id' => $item['ticket_id'],
							'error'     => $attendee_result->get_error_message(),
						] 
					);
				}

				// Update stock for "going" RSVPs.
				if ( Meta::STATUS_GOING === $rsvp_status ) {
					$ticket->update_stock( $item['ticket_id'], 1, 'decrease' );
				}
			}

			/**
			 * Fires after RSVP tickets are generated for a specific product.
			 *
			 * V1 backwards compatibility hook.
			 *
			 * @since TBD
			 *
			 * @param int $product_id The product/ticket ID.
			 * @param int $order_id   The order ID.
			 * @param int $qty        The quantity of attendees created.
			 */
			do_action( 'event_tickets_rsvp_tickets_generated_for_product', $item['ticket_id'], $order->ID, $quantity );
		}

		// Clear the cart after successful order creation.
		$cart->clear();

		/**
		 * Fires after an RSVP V2 order is created.
		 *
		 * @since TBD
		 *
		 * @param int    $order_id    The order ID.
		 * @param array  $items       The order items.
		 * @param string $rsvp_status The RSVP status for attendees.
		 */
		do_action( 'tec_tickets_rsvp_v2_order_created', $order->ID, $items, $rsvp_status );

		// Get the event ID from the first item for V1 hook (all items should have same event_id).
		$event_id = ! empty( $items ) ? $items[0]['event_id'] : 0;

		/**
		 * Fires after all RSVP tickets are generated for an order.
		 *
		 * V1 backwards compatibility hook.
		 *
		 * @since TBD
		 *
		 * @param int    $order_id    The order ID.
		 * @param int    $event_id    The event/post ID.
		 * @param string $rsvp_status The RSVP status ('yes' for going, 'no' for not going).
		 */
		do_action( 'event_tickets_rsvp_tickets_generated', $order->ID, $event_id, $rsvp_status );

		return $order->ID;
	}

	/**
	 * Gets an order by ID.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order post ID.
	 *
	 * @return WP_Post|null The order post or null if not found.
	 */
	public function get( int $order_id ): ?WP_Post {
		$order = get_post( $order_id );

		if ( ! $order || TC_Order::POSTTYPE !== $order->post_type ) {
			return null;
		}

		return $order;
	}

	/**
	 * Gets attendees for an order.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order post ID.
	 *
	 * @return array Array of attendee posts.
	 */
	public function get_attendees( int $order_id ): array {
		return tribe( Attendee::class )->get_by_order( $order_id );
	}
}
