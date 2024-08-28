<?php
/**
 * Handles the integration with the Tickets Commerce module.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Commerce;
 */

namespace TEC\Tickets\Seating\Commerce;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Seating\Meta;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets as Tickets;
use WP_Post;

/**
 * Class Controller.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Commerce;
 */
class Controller extends Controller_Contract {
	/**
	 * Subscribes to the WordPress hooks and actions required by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter(
			'tec_tickets_seating_timer_token_object_id_entries',
			[ $this, 'filter_timer_token_object_id_entries' ],
		);
		add_filter( 'tribe_tickets_ticket_inventory', [ $this, 'get_seated_ticket_inventory' ], 10, 2 );
		add_action( 'updated_postmeta', [ $this, 'sync_seated_tickets_stock' ], 10, 4 );
	}

	/**
	 * Unregisters the controller by unsubscribing from WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter(
			'tec_tickets_seating_timer_token_object_id_entries',
			[ $this, 'filter_timer_token_object_id_entries' ],
		);
		remove_filter( 'tribe_tickets_ticket_inventory', [ $this, 'get_seated_ticket_inventory' ] );
		remove_action( 'updated_postmeta', [ $this, 'sync_seated_tickets_stock' ] );
	}

	/**
	 * Adjusts the seated ticket inventory to match the stock.
	 *
	 * @since TBD
	 *
	 * @param int           $inventory The current inventory.
	 * @param Ticket_Object $ticket    The ticket object.
	 *
	 * @return int The adjusted inventory.
	 */
	public function get_seated_ticket_inventory( int $inventory, Ticket_Object $ticket ): int {
		$seat_type = get_post_meta( $ticket->ID, Meta::META_KEY_SEAT_TYPE, true );

		if ( ! $seat_type ) {
			return $inventory;
		}

		$event_id      = $ticket->get_event_id();
		$ticket_ids    = [ $ticket->ID ];
		$this_inventory = $inventory;
		$capacity = $ticket->capacity();
		// Protect from over-selling
		$total_sold = max( 0, $capacity - $this_inventory );

		// Remove this function from the filter to avoid infinite loops.
		remove_filter( 'tribe_tickets_ticket_inventory', [ $this, 'get_seated_ticket_inventory' ] );

		// Later we'll remove this specific return false filter, not one that might have been added by other code.
		$return_false = static fn() => false;
		add_filter( 'tribe_tickets_ticket_object_is_ticket_cache_enabled', $return_false );

		// Pull the inventory from the other tickets with the same seat type.
		foreach (
			tribe_tickets()
				->where( 'event', $event_id )
				->not_in( $ticket->ID )
				->where( 'meta_equals', Meta::META_KEY_SEAT_TYPE, $seat_type )
				->get_ids( true ) as $ticket_id
		) {
			$ticket        = Tickets::load_ticket_object( $ticket_id );
			$sold_for_this = $capacity - $ticket->inventory();
			$total_sold    += $sold_for_this;
			$ticket_ids[]  = $ticket_id;
		}

		add_filter( 'tribe_tickets_ticket_inventory',
			[ $this, 'get_seated_ticket_inventory' ],
			10,
			2
		);
		remove_filter( 'tribe_tickets_ticket_object_is_ticket_cache_enabled', $return_false );

		$synced_inventory = $capacity - $total_sold;

		return $synced_inventory;
	}

	/**
	 * Filters the stock update value for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int    $meta_id    ID of the meta entry.
	 * @param int    $object_id  ID of the object.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 *
	 * @return void
	 */
	public function sync_seated_tickets_stock( $meta_id, $object_id, $meta_key, $meta_value ): void {
		if ( Ticket::$stock_meta_key !== $meta_key ) {
			return;
		}

		if ( ! is_numeric( $meta_value ) ) {
			return;
		}

		$stock = (int) $meta_value;

		if ( 0 > $stock ) {
			// We are not syncing bugs. Seats can NOT be infinite.
			return;
		}

		$seat_type = get_post_meta( $object_id, Meta::META_KEY_SEAT_TYPE, true );

		// Not a seating ticket. We should not modify the stock.
		if ( ! $seat_type ) {
			return;
		}

		$ticket = Tickets::load_ticket_object( $object_id );

		if ( ! $ticket instanceof Ticket_Object ) {
			return;
		}

		$event = $ticket->get_event();

		if ( ! $event instanceof WP_Post || ! $event->ID ) {
			return;
		}

		// Remove the action to avoid infinite loops.
		remove_action( 'updated_postmeta', [ $this, 'sync_seated_tickets_stock' ] );

		foreach (
			tribe_tickets()
				->where( 'event', $event->ID )
				->not_in( $ticket->ID )
				->where( 'meta_equals', Meta::META_KEY_SEAT_TYPE, $seat_type )
				->get_ids( true ) as $ticket_id
		) {
			update_post_meta( $ticket_id, Ticket::$stock_meta_key, $stock );
		}

		add_action( 'updated_postmeta', [ $this, 'sync_seated_tickets_stock' ], 10, 4 );
	}

	/**
	 * Filters the handler used to get the token and object ID from the cookie.
	 *
	 * @since TBD
	 *
	 * @param array<string,string> $session_entries The entries from the cookie. A map from object ID to token.
	 *
	 * @return array<string,string> The entries from the cookie. A map from object ID to token.
	 */
	public function filter_timer_token_object_id_entries( $session_entries ): array {
		$tickets_commerce = tribe( Module::class );

		if ( empty( $session_entries ) || ! $tickets_commerce->is_checkout_page() ) {
			// Not a Tickets Commerce checkout page: return the original entries.
			return $session_entries;
		}

		// Get the post IDs in the cart.
		global $wpdb;
		/** @var Cart $cart */
		$cart       = tribe( Cart::class );
		$cart_items = array_keys( $cart->get_items_in_cart() );

		if ( empty( $cart_items ) ) {
			return [];
		}

		$ticket_ids_interval = DB::prepare(
			implode( ',', array_fill( 0, count( $cart_items ), '%d' ) ),
			...$cart_items
		);
		$cart_post_ids       = DB::get_col(
			DB::prepare(
				"SELECT DISTINCT( meta_value ) FROM %i WHERE post_id IN ({$ticket_ids_interval}) AND meta_key = %s ",
				$wpdb->postmeta,
				Module::ATTENDEE_EVENT_KEY
			)
		);

		// Get the post IDs in the session.
		$session_post_ids = array_keys( $session_entries );

		// Find out the post IDs part of both the cart and the seat selection session.
		$cart_and_session_ids = array_intersect( $cart_post_ids, $session_post_ids );

		if ( empty( $cart_and_session_ids ) ) {
			// There are no Tickets for posts using Seat Assignment in the cart.
			return [];
		}

		return array_combine(
			$cart_and_session_ids,
			array_map(
				static function ( $item ) use ( $session_entries ) {
					return $session_entries[ $item ];
				},
				$cart_and_session_ids
			)
		);
	}
}
