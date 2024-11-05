<?php
/**
 * Handles the integration with the Tickets Commerce module.
 *
 * @since   5.16.0
 *
 * @package TEC\Tickets\Seating\Commerce;
 */

namespace TEC\Tickets\Seating\Commerce;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Service\Service;
use TEC\Tickets\Seating\Tables\Seat_Types as Seat_Types_Table;
use Tribe__Cache_Listener as Cache_Listener;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;
use WP_Post;

/**
 * Class Controller.
 *
 * @since   5.16.0
 *
 * @package TEC\Tickets\Seating\Commerce;
 */
class Controller extends Controller_Contract {
	/**
	 * A reference to the Seating Service facade.
	 *
	 * @since 5.16.0
	 *
	 * @var Service
	 */
	private Service $service;

	/**
	 * A reference to the Tickets Handler.
	 *
	 * @since 5.16.0
	 *
	 * @var Tickets_Handler
	 */
	private Tickets_Handler $tickets_handler;
	/**
	 * A reference to the Seat Types Table handle.
	 *
	 * @since 5.16.0
	 *
	 * @var Seat_Types_Table
	 */
	private Seat_Types_Table $seat_types_table;

	/**
	 * A reference to the Attendees handler.
	 *
	 * @since 5.16.0
	 *
	 * @var Attendees
	 */
	private Attendees $attendees;

	/**
	 * Controller constructor.
	 *
	 * @since 5.16.0
	 *
	 * @param Container        $container        A reference to the DI container instance.
	 * @param Service          $service          A reference to the Seating Service facade.
	 * @param Seat_Types_Table $seat_types_table A reference to the Seat Types Table handler.
	 * @param Attendees        $attendees        A reference to the Attendees data handler.
	 */
	public function __construct( Container $container, Service $service, Seat_Types_Table $seat_types_table, Attendees $attendees ) {
		parent::__construct( $container );
		$this->service = $service;
		/** @var Tickets_Handler $tickets_handler */
		$this->tickets_handler  = tribe( 'tickets.handler' );
		$this->seat_types_table = $seat_types_table;
		$this->attendees        = $attendees;
	}

	/**
	 * Subscribes to the WordPress hooks and actions required by the controller.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter(
			'tec_tickets_seating_timer_token_object_id_entries',
			[ $this, 'filter_timer_token_object_id_entries' ],
		);
		add_filter( 'tribe_tickets_ticket_inventory', [ $this, 'get_seated_ticket_inventory' ], 10, 3 );
		add_filter( 'tec_tickets_get_ticket_counts', [ $this, 'set_event_stock_counts' ], 10, 2 );
		add_filter( 'update_post_metadata', [ $this, 'prevent_capacity_saves_without_service' ], 1, 4 );
		add_filter( 'update_post_metadata', [ $this, 'handle_ticket_meta_update' ], 10, 4 );
		add_action( 'before_delete_post', [ $this, 'restock_ticket_on_attendee_deletion' ], 10, 2 );
		add_action( 'wp_trash_post', [ $this, 'restock_ticket_on_attendee_trash' ] );
	}

	/**
	 * Unregisters the controller by unsubscribing from WordPress hooks.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter(
			'tec_tickets_seating_timer_token_object_id_entries',
			[ $this, 'filter_timer_token_object_id_entries' ],
		);
		remove_filter( 'tribe_tickets_ticket_inventory', [ $this, 'get_seated_ticket_inventory' ] );
		remove_filter( 'tec_tickets_get_ticket_counts', [ $this, 'set_event_stock_counts' ] );
		remove_filter( 'update_post_metadata', [ $this, 'prevent_capacity_saves_without_service' ], 1 );
		remove_filter( 'update_post_metadata', [ $this, 'handle_ticket_meta_update' ], 10 );
		remove_action( 'before_delete_post', [ $this, 'restock_ticket_on_attendee_deletion' ] );
		remove_action( 'wp_trash_post', [ $this, 'restock_ticket_on_attendee_trash' ] );
	}

	/**
	 * Sets the stock counts for the event.
	 *
	 * @since 5.16.0
	 *
	 * @param array<string,array<string|int>> $types   The types of tickets.
	 * @param int                             $post_id The post ID.
	 *
	 * @return array<string,array<string|int>> The types of tickets.
	 */
	public function set_event_stock_counts( $types, $post_id ): array {
		if ( ! tec_tickets_seating_enabled( $post_id ) ) {
			return $types;
		}

		$types['tickets'] = [
			'count'     => 0, // Count of ticket types currently for sale.
			'stock'     => 0, // Current stock of tickets available for sale.
			'global'    => 1, // Numeric boolean if tickets share global stock.
			'unlimited' => 0, // Numeric boolean if any ticket has unlimited stock.
			'available' => 0,
		];

		$tickets = tribe_tickets()
			->where( 'event', $post_id )
			->get_ids( true );

		$capacity_by_type   = [];
		$total_sold_by_type = [];

		foreach ( $tickets as $ticket_id ) {
			$ticket = Tickets::load_ticket_object( $ticket_id );

			if ( ! $ticket instanceof Ticket_Object ) {
				continue;
			}

			if ( ! tribe_events_ticket_is_on_sale( $ticket ) ) {
				continue;
			}

			$seat_type = get_post_meta( $ticket_id, META::META_KEY_SEAT_TYPE, true );

			if ( empty( $seat_type ) ) {
				continue;
			}

			$capacity = $ticket->capacity();
			$stock    = $ticket->stock();
			$sold_qty = $ticket->qty_sold();

			if ( ! isset( $capacity_by_type[ $seat_type ] ) ) {
				$capacity_by_type[ $seat_type ] = $capacity;
			}

			if ( ! isset( $total_sold_by_type[ $seat_type ] ) ) {
				$total_sold_by_type[ $seat_type ] = $sold_qty;
			} else {
				$total_sold_by_type[ $seat_type ] += $sold_qty;
			}

			++$types['tickets']['count'];
		}

		foreach ( $capacity_by_type as $seat_type => $capacity ) {
			$stock_level                    = $capacity - $total_sold_by_type[ $seat_type ];
			$types['tickets']['stock']     += $stock_level;
			$types['tickets']['available'] += $stock_level;
		}

		return $types;
	}

	/**
	 * Adjusts the seated ticket inventory to match the stock.
	 *
	 * @since 5.16.0
	 *
	 * @param int                        $inventory       The current inventory.
	 * @param Ticket_Object              $ticket          The ticket object.
	 * @param array<array<string,mixed>> $event_attendees The post Attendees.
	 *
	 * @return int The adjusted inventory.
	 */
	public function get_seated_ticket_inventory( int $inventory, Ticket_Object $ticket, array $event_attendees ): int {
		$seat_type = get_post_meta( $ticket->ID, Meta::META_KEY_SEAT_TYPE, true );

		if ( ! $seat_type ) {
			return $inventory;
		}

		$event_id = $ticket->get_event_id();
		$capacity = $ticket->capacity();

		// Remove this function from the filter to avoid infinite loops.
		remove_filter( 'tribe_tickets_ticket_inventory', [ $this, 'get_seated_ticket_inventory' ] );

		// Later we'll remove this specific return false filter, not one that might have been added by other code.
		$return_false = static fn() => false;
		add_filter( 'tribe_tickets_ticket_object_is_ticket_cache_enabled', $return_false );

		$ticket_ids = [ $ticket->ID ];

		// Pull the inventory from the other tickets with the same seat type.
		foreach (
			tribe_tickets()
				->where( 'event', $event_id )
				->not_in( $ticket->ID )
				->where( 'meta_equals', Meta::META_KEY_SEAT_TYPE, $seat_type )
				->get_ids( true ) as $ticket_id
		) {
			$ticket_ids[] = (int) $ticket_id;
		}

		$total_sold = 0;
		if ( count( $event_attendees ) ) {
			$total_sold = count(
				array_filter(
					$event_attendees,
					static fn( array $attendee ): bool => in_array( (int) $attendee['product_id'], $ticket_ids, true )
				)
			);
		}

		add_filter(
			'tribe_tickets_ticket_inventory',
			[ $this, 'get_seated_ticket_inventory' ],
			10,
			3
		);
		remove_filter( 'tribe_tickets_ticket_object_is_ticket_cache_enabled', $return_false );

		return $capacity - $total_sold;
	}

	/**
	 * Filters the handler used to get the token and object ID from the cookie.
	 *
	 * @since 5.16.0
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

	/**
	 * Cross-updates the Ticket stock meta across a set of Tickets sharing the same seat type and post.
	 *
	 * @since 5.16.0
	 *
	 * @param int    $ticket_id      The Ticket ID to start the cross-update from.
	 * @param string $seat_type      The seat type UUID.
	 * @param int    $stock_modifier Modify the stock further by this amount. Useful when we already know the value
	 *                               will be modified by a certain amount in the context of this call (e.g. when handling
	 *                               an Attendee deletion).
	 *
	 * @return bool Whether the meta update of the Ticket specified by `$ticket_id` was successful or not.
	 */
	private function update_seated_ticket_stock( int $ticket_id, string $seat_type, int $stock_modifier = 0 ): bool {
		$ticket = Tickets::load_ticket_object( $ticket_id );

		if ( ! $ticket instanceof Ticket_Object ) {
			return false;
		}

		$event = $ticket->get_event();

		if ( ! $event instanceof WP_Post || ! $event->ID ) {
			return false;
		}

		$seat_type_seats           = $this->seat_types_table->get_seats( $seat_type );
		$seat_type_attendees_count = $this->attendees->get_count_by_post_seat_type( $event->ID, $seat_type );
		$updated_stock             = $seat_type_seats - $seat_type_attendees_count + $stock_modifier;

		$updated = update_post_meta( $ticket_id, Ticket::$stock_meta_key, $updated_stock );

		// Trigger the save post cache invalidation for this ticket.
		$cache_listener    = tribe( Cache_Listener::class );
		$to_invalidate_ids = [ $ticket_id ];

		/*
		 * Not memoized as its invalidation could not be handled only in this Controller and would run the risk of
		 * caching the wrong value.
		 */
		foreach (
			tribe_tickets()
				->where( 'event', $event->ID )
				->not_in( $ticket->ID )
				->where( 'meta_equals', Meta::META_KEY_SEAT_TYPE, $seat_type )
				->get_ids( true ) as $seat_type_ticket_id
		) {
			update_post_meta( $seat_type_ticket_id, Ticket::$stock_meta_key, $updated_stock );
			$to_invalidate_ids[] = $seat_type_ticket_id;
		}

		// This cross-update might have skipped some methods that would normally invalidate theirs caches: do it now.
		foreach ( $to_invalidate_ids as $to_invalidate_id ) {
			// Trigger the save post cache invalidation for this ticket.
			$cache_listener->save_post( $to_invalidate_id, get_post( $to_invalidate_id ) );
		}

		return $updated;
	}

	/**
	 * Prevents the update of the capacity meta keys for Tickets that are ASC tickets and Ticket-able Post types that are using Seating.
	 *
	 * @since 5.16.0
	 *
	 * @param null|bool $check      Whether to allow the update (`null`) or whether the update is already being processed.
	 * @param int       $object_id  The ID of the object being updated.
	 * @param string    $meta_key   The meta key being updated.
	 * @param mixed     $meta_value The new value for the meta key.
	 *
	 * @return null|bool Whether to allow the update (`null`) or whether the update is already being processed and
	 *                   what is the update result (`false|true`).
	 */
	public function prevent_capacity_saves_without_service( $check, $object_id, $meta_key, $meta_value ) {
		if ( $check !== null ) {
			// Some other code is already controlling the update, so we should not.
			return $check;
		}

		if ( ! in_array(
			$meta_key,
			[
				Ticket::$stock_meta_key,
				$this->tickets_handler->key_capacity,
			],
			true
		) ) {
			// Not a ticket meta key we care about.
			return $check;
		}

		$ticket_post_types      = tribe_tickets()->ticket_types();
		$ticket_able_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

		if ( ! in_array( get_post_type( $object_id ), $ticket_post_types, true ) && ! in_array( get_post_type( $object_id ), $ticket_able_post_types, true ) ) {
			// Not a ticket post type.
			return $check;
		}

		$seat_type = get_post_meta( $object_id, Meta::META_KEY_SEAT_TYPE, true );

		if ( ! $seat_type && ! tec_tickets_seating_enabled( $object_id ) ) {
			// Not an ASC ticket.
			return $check;
		}

		if ( ! $this->service->get_status()->is_ok() ) {
			// Service status is not OK: prevent the update until the service comes back online.
			return false;
		}

		return $check;
	}

	/**
	 * Handle the update of some ticket meta keys depending on the service status and taking care to update
	 * related meta in other Tickets that should be affected.
	 *
	 * @since 5.16.0
	 *
	 * @param null|bool $check      Whether to allow the update (`null`) or whether the update is already being processed.
	 * @param int       $object_id  The ID of the object being updated.
	 * @param string    $meta_key   The meta key being updated.
	 * @param mixed     $meta_value The new value for the meta key.
	 *
	 * @return null|bool Whether to allow the update (`null`) or whether the update is already being processed and
	 *                   what is the update result (`false|true`).
	 */
	public function handle_ticket_meta_update( $check, $object_id, $meta_key, $meta_value ) {
		if ( $check !== null ) {
			// Some other code is already controlling the update, so we should not.
			return $check;
		}

		if ( ! in_array(
			$meta_key,
			[
				Ticket::$stock_meta_key,
				$this->tickets_handler->key_capacity,
			],
			true
		) ) {
			// Not a ticket meta key we care about.
			return $check;
		}

		$ticket_post_types = tribe_tickets()->ticket_types();

		if ( ! in_array( get_post_type( $object_id ), $ticket_post_types, true ) ) {
			// Not a ticket post type.
			return $check;
		}

		$seat_type = get_post_meta( $object_id, Meta::META_KEY_SEAT_TYPE, true );

		if ( ! $seat_type ) {
			// Not an ASC ticket.
			return $check;
		}

		// Remove this filter to avoid infinite loops.
		remove_filter( 'update_post_metadata', [ $this, 'handle_ticket_meta_update' ] );

		if ( $meta_key === Ticket::$stock_meta_key ) {
			// Meta value might be negative from default calculation: not an issue, we run a different calculation.
			$updated = $this->update_seated_ticket_stock( $object_id, $seat_type );
		} else {
			if ( (int) $meta_value < 0 ) {
				// Not syncing unlimited capacity: no such thing as infinite seats.
				return false;
			}
			$updated = update_post_meta( $object_id, $meta_key, $meta_value );
		}

		add_filter( 'update_post_metadata', [ $this, 'handle_ticket_meta_update' ], 10, 4 );

		return $updated;
	}

	/**
	 * Updates the stock of the Tickets sharing the same seat type when an Attendee is trashed.
	 *
	 * @since 5.16.0
	 *
	 * @param int $post_id The ID of the post being trashed.
	 *
	 * @return void
	 */
	public function restock_ticket_on_attendee_trash( $post_id ) {
		$post = get_post( $post_id );

		$attendee_types = tribe_attendees()->attendee_types();
		if ( ! $post instanceof WP_Post && in_array( $post->post_type, $attendee_types, true ) ) {
			return;
		}

		$this->restock_ticket_on_attendee_deletion( $post_id, $post );
	}

	/**
	 * Updates the stock of the Tickets sharing the same seat type when an Attendee is deleted.
	 *
	 * @since 5.16.0
	 *
	 * @param int     $post_id The ID of the post being deleted.
	 * @param WP_Post $post    The post object being deleted.
	 *
	 * @return void
	 */
	public function restock_ticket_on_attendee_deletion( $post_id, $post ) {
		if ( ! ( $post instanceof WP_Post ) ) {
			return;
		}

		$attendee_types = tribe_attendees()->attendee_types();

		if ( ! in_array( $post->post_type, $attendee_types, true ) ) {
			return;
		}

		// Fetching Post and Ticket ID from the Attendee can require some queries, but the data is already in the meta (cached).
		$attendee_to_post_keys   = array_values( tribe_attendees()->attendee_to_event_keys() );
		$attendee_to_ticket_keys = array_values( tribe_attendees()->attendee_to_ticket_keys() );
		$attendee_meta           = get_post_meta( $post_id );
		$post_id                 = null;
		$ticket_id               = null;
		foreach ( $attendee_meta as $meta_key => $meta_value ) {
			if ( $post_id && $ticket_id ) {
				break;
			}

			if ( in_array( $meta_key, $attendee_to_post_keys, true ) ) {
				$post_id = reset( $meta_value );
				continue;
			}

			if ( in_array( $meta_key, $attendee_to_ticket_keys, true ) ) {
				$ticket_id = reset( $meta_value );
			}
		}

		if ( ! ( $post_id && $ticket_id ) ) {
			return;
		}

		$seat_type = get_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE, true );

		if ( ! $seat_type ) {
			return;
		}

		remove_filter( 'update_post_metadata', [ $this, 'handle_ticket_meta_update' ] );

		// Updating this Ticket will update all the Tickets that share the same seat type.
		$this->update_seated_ticket_stock( $ticket_id, $seat_type, 1 );

		add_filter( 'update_post_metadata', [ $this, 'handle_ticket_meta_update' ], 10, 4 );
	}
}
