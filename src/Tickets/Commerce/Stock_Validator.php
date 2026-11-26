<?php
/**
 * Stock Validator with Database Locking.
 *
 * Provides atomic stock validation using database row-level locking
 * to prevent overselling during concurrent purchases.
 *
 * @since 5.26.7
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\DB\Database\Exceptions\DatabaseQueryException;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Traits\Is_Ticket;
use TEC\Tickets\RSVP\V2\Constants as RSVP_V2_Constants;
use TEC\Tickets\Ticket_Cache_Controller;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets;
use Tribe__Utils__Array as Arr;
use WP_Error;
use WP_Post;

/**
 * Class Stock_Validator.
 *
 * @since 5.26.7
 *
 * @package TEC\Tickets\Commerce
 */
class Stock_Validator {

	use Is_Ticket;

	/**
	 * Meta key, on a ticket or on an event with shared capacity, of the stock held by live checkouts.
	 *
	 * Holds the order IDs whose checkout reserved stock, each with its quantity and the time its hold expires.
	 *
	 * @since 5.31.2
	 *
	 * @var string
	 */
	private const HOLDS_META_KEY = '_tec_tc_stock_holds';

	/**
	 * Whether this request already set the connection's snapshot isolation.
	 *
	 * @since 5.31.2
	 *
	 * @var bool
	 */
	private bool $snapshot_isolation_set = false;

	/**
	 * Validates cart stock with database row-level locking.
	 *
	 * This method acquires row-level locks on ticket stock meta rows
	 * using SELECT ... FOR UPDATE to prevent concurrent modifications
	 * during validation.
	 *
	 * @since 5.26.7
	 * @since 5.31.2 Checks shared capacity, and counts the stock other live checkouts hold.
	 *
	 * @param Cart $cart The cart to validate.
	 *
	 * @return true|WP_Error True if stock is available, WP_Error if not.
	 */
	public function validate_cart_stock_with_lock( Cart $cart ) {
		$pools = $this->check_items( $cart->get_items_in_cart(), 0 );

		return is_wp_error( $pools ) ? $pools : true;
	}

	/**
	 * Validates order stock for a specific order.
	 *
	 * Used during order status transitions to provide secondary validation.
	 *
	 * @since 5.26.7
	 * @since 5.31.2 Reads the stored stock with a row lock instead of the cached ticket, checks shared capacity,
	 *            and counts the stock other live checkouts hold.
	 *
	 * @param WP_Post $order The order to validate.
	 *
	 * @return true|WP_Error True if stock is available, WP_Error if not.
	 */
	public function validate_order_stock( WP_Post $order ) {
		// Bail early if order has no items.
		if ( empty( $order->items ) || ! is_array( $order->items ) ) {
			return true;
		}

		// Called inside the order's transaction: the row locks hold until it commits, so a concurrent checkout waits for this one.
		$pools = $this->check_items( $order->items, $order->ID );

		return is_wp_error( $pools ) ? $pools : true;
	}

	/**
	 * Holds an order's stock for its checkout, if the stock is available.
	 *
	 * Call it inside a transaction, after the order's checkout screen hold is set: the hold expires with it.
	 * Other checkouts count held stock as taken until the order decreases the stock or the hold expires.
	 *
	 * @since 5.31.2
	 *
	 * @internal
	 *
	 * @param WP_Post $order The order to hold the stock for.
	 *
	 * @return true|WP_Error True if the stock is held, WP_Error if it is not available.
	 */
	public function hold_order_stock( WP_Post $order ) {
		$pools = $this->check_items( (array) $order->items, $order->ID );

		if ( is_wp_error( $pools ) ) {
			return $pools;
		}

		$expires = (string) get_post_meta( $order->ID, Order::ON_CHECKOUT_SCREEN_HOLD_META, true );

		foreach ( $pools as $post_id => $pool ) {
			$holds               = $this->get_live_holds( $post_id );
			$holds[ $order->ID ] = [
				'quantity' => $pool['demand'],
				'expires'  => $expires,
			];

			update_post_meta( $post_id, self::HOLDS_META_KEY, $holds );
		}

		return true;
	}

	/**
	 * Locks and refreshes an order's stock before it is decreased, and releases the stock its checkout held.
	 *
	 * @since 5.31.2
	 *
	 * @internal
	 *
	 * @param WP_Post $order The order whose stock is about to be decreased.
	 *
	 * @return void
	 */
	public function prepare_order_stock_decrease( WP_Post $order ): void {
		$this->release_order_stock_holds( $order->ID, (array) $order->items );
	}

	/**
	 * Releases the stock an order's checkout held, once the order can no longer be paid.
	 *
	 * Call it inside the transaction that moves the order to a failed, denied or voided status, so the
	 * seats return to other buyers at once instead of when the checkout hold expires.
	 *
	 * @since 5.31.2
	 *
	 * @internal
	 *
	 * @param int   $order_id The order.
	 * @param array $items    The order's items.
	 *
	 * @return void
	 */
	public function release_order_stock_holds( int $order_id, array $items ): void {
		try {
			$this->release_holds( $order_id, $items );
		} catch ( DatabaseQueryException $e ) {
			// The stock is then changed from the cached values, as before the lock existed; the hold expires on its own.
			return;
		}
	}

	/**
	 * Turns MariaDB's snapshot isolation off for this connection, once per request.
	 *
	 * Call it before starting a transaction that saves posts. Every post save writes shared rows, such as
	 * Tribe's last-save option, and with snapshot isolation (on by default since MariaDB 11.6) a concurrent
	 * save anywhere on the site makes that write fail and rolls the whole transaction back: a paid order
	 * left without its attendees. These transactions lock the rows they protect, so they are written for
	 * classic REPEATABLE READ. MySQL and older MariaDB have no such variable, and nothing changes there.
	 *
	 * @since 5.31.2
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function disable_snapshot_isolation(): void {
		if ( $this->snapshot_isolation_set ) {
			return;
		}

		$this->snapshot_isolation_set = true;

		try {
			$variable = DB::get_row( "SHOW SESSION VARIABLES LIKE 'innodb_snapshot_isolation'" );

			if ( isset( $variable->Value ) && 'OFF' !== strtoupper( $variable->Value ) ) {
				DB::query( 'SET SESSION innodb_snapshot_isolation = OFF' );
			}
		} catch ( DatabaseQueryException $e ) {
			// Without the switch, the transactions run as before.
			return;
		}
	}

	/**
	 * Locks the tickets' stock until the current transaction ends, and loads the stored stock into the cache.
	 *
	 * Call it inside a transaction, before any other read in it.
	 *
	 * @since 5.31.2
	 *
	 * @internal
	 *
	 * @param int[] $ticket_ids The ticket IDs.
	 * @param int[] $event_ids  The tickets' events, locked in the same statement so shared capacity is read fresh.
	 *
	 * @return void
	 *
	 * @throws DatabaseQueryException If a query fails, a lock wait times out or a deadlock is detected.
	 */
	public function lock_ticket_stock( array $ticket_ids, array $event_ids = [] ): void {
		$this->lock_stock( $ticket_ids, $event_ids );
	}

	/**
	 * Validates a single ticket's stock with database locking.
	 *
	 * Acquires a row-level lock on the ticket's stock meta using SELECT FOR UPDATE.
	 *
	 * @since 5.26.7
	 * @since 5.31.2 Checks shared capacity, and counts the stock live checkouts hold.
	 *
	 * @param int $ticket_id The ticket ID.
	 * @param int $quantity  The requested quantity.
	 *
	 * @return true|WP_Error True if stock is available, WP_Error if not.
	 */
	protected function validate_ticket_stock_with_lock( int $ticket_id, int $quantity ) {
		$pools = $this->check_items(
			[
				[
					'ticket_id' => $ticket_id,
					'quantity'  => $quantity,
				],
			],
			0
		);

		return is_wp_error( $pools ) ? $pools : true;
	}

	/**
	 * Validates a single ticket's stock without locking.
	 *
	 * Used when validation occurs within an existing transaction/lock context.
	 *
	 * @since 5.26.7
	 *
	 * @param int      $ticket_id    The ticket ID.
	 * @param int      $quantity     The requested quantity.
	 * @param int|null $locked_stock Optional. Already-locked stock value.
	 *
	 * @return true|WP_Error True if stock is available, WP_Error if not.
	 */
	protected function validate_ticket_stock( int $ticket_id, int $quantity, $locked_stock = null ) {
		$ticket = Tribe__Tickets__Tickets::load_ticket_object( $ticket_id );

		// Bail early if ticket is invalid.
		if ( null === $ticket ) {
			return new WP_Error(
				'tec-tc-invalid-ticket',
				__( 'Invalid ticket.', 'event-tickets' ),
				[
					'ticket_id' => $ticket_id,
					'quantity'  => $quantity,
				]
			);
		}

		// Bail early for seated tickets - they have their own stock management via the seating service.
		if ( metadata_exists( 'post', $ticket_id, '_tec_slr_seat_type' ) ) {
			return true;
		}

		// Bail early for tickets that don't manage stock.
		if ( ! $ticket->manage_stock() ) {
			return true;
		}

		// Bail early for unlimited capacity tickets.
		if ( -1 === $ticket->capacity() ) {
			return true;
		}

		// Bail early for shared capacity (global stock) tickets.
		$global_stock_mode  = $ticket->global_stock_mode();
		$is_shared_capacity = ! empty( $global_stock_mode ) && 'own' !== $global_stock_mode;
		if ( $is_shared_capacity ) {
			return true;
		}

		// Use locked stock value if provided, otherwise get current stock.
		$available_stock = null !== $locked_stock ? (int) $locked_stock : $ticket->stock();

		// Bail early if stock is sufficient.
		if ( $available_stock >= $quantity ) {
			return true;
		}

		// Stock is insufficient - return error with data for user-facing message generation.
		return new WP_Error(
			'tec-tc-insufficient-stock',
			sprintf(
				'Stock validation failed: Ticket ID %1$d (%2$s) - requested %3$d, available %4$d',
				$ticket_id,
				$ticket->name,
				$quantity,
				max( 0, $available_stock )
			),
			[
				'ticket_id'    => $ticket_id,
				'ticket_name'  => $ticket->name,
				'requested'    => $quantity,
				'available'    => max( 0, $available_stock ),
				'insufficient' => true,
			]
		);
	}

	/**
	 * Builds a user-friendly WP_Error from validation errors.
	 *
	 * @since 5.26.7
	 *
	 * @param array $validation_errors Array of error data.
	 *
	 * @return WP_Error
	 */
	protected function build_insufficient_stock_error( array $validation_errors ): WP_Error {
		$error_messages = [];

		foreach ( $validation_errors as $error_data ) {
			if ( ! isset( $error_data['insufficient'] ) || ! $error_data['insufficient'] ) {
				continue;
			}

			$ticket_name = Arr::get( $error_data, 'ticket_name', __( 'Unknown Ticket', 'event-tickets' ) );
			$requested   = Arr::get( $error_data, 'requested', 0 );
			$available   = Arr::get( $error_data, 'available', 0 );

			if ( 0 === $available ) {
				$error_messages[] = sprintf(
					/* translators: 1: ticket name, 2: requested quantity */
					__( 'Sorry, "%1$s" tickets are sold out. You requested %2$d.', 'event-tickets' ),
					$ticket_name,
					$requested
				);
			} elseif ( 1 === $available ) {
				$error_messages[] = sprintf(
					/* translators: 1: ticket name, 2: requested quantity */
					__( 'Sorry, some "%1$s" tickets are no longer available. You requested %2$d and there is only 1 available.', 'event-tickets' ),
					$ticket_name,
					$requested
				);
			} else {
				$error_messages[] = sprintf(
					/* translators: 1: ticket name, 2: requested quantity, 3: available quantity */
					__( 'Sorry, some "%1$s" tickets are no longer available. You requested %2$d and there are only %3$d available.', 'event-tickets' ),
					$ticket_name,
					$requested,
					$available
				);
			}
		}

		$main_message = implode( ' ', $error_messages );

		return new WP_Error(
			'tec-tc-insufficient-stock',
			$main_message,
			[
				'status'            => 400,
				'validation_errors' => $validation_errors,
			]
		);
	}

	/**
	 * Determines if stock validation should occur for a status transition.
	 *
	 * @since 5.26.7
	 *
	 * @param Status\Status_Interface $new_status The status being transitioned to.
	 *
	 * @return bool
	 */
	public function should_validate_for_transition( Status\Status_Interface $new_status ): bool {
		// Get the configured stock handling status.
		$stock_handling_status = tribe( Status_Handler::class )->get_inventory_decrease_status();

		// Check if the new status is the one configured for stock decrease.
		if ( $new_status->get_slug() !== $stock_handling_status->get_slug() ) {
			return false;
		}

		// Additional check: ensure this status actually has the decrease_stock flag.
		return $new_status->has_flags( [ 'decrease_stock' ], 'AND' );
	}

	/**
	 * Locks the stock of the items' tickets and checks it can cover the items.
	 *
	 * @since 5.31.2
	 *
	 * @param array $items    The cart or order items.
	 * @param int   $order_id The order the items belong to, whose own hold is not counted; 0 for none.
	 *
	 * @return array<int,array{stock:int,demand:int,tickets:Ticket_Object[]}>|WP_Error The stock pools by post ID, or WP_Error if the stock is not available.
	 */
	private function check_items( array $items, int $order_id ) {
		$quantities = $this->get_ticket_quantities( $items );

		if ( empty( $quantities ) ) {
			return [];
		}

		try {
			$this->lock_stock( array_keys( $quantities ), array_column( $items, 'event_id' ) );
		} catch ( DatabaseQueryException $e ) {
			// The database error stays in the log: the REST responses carrying this error reach anonymous buyers.
			do_action(
				'tribe_log',
				'error',
				__METHOD__,
				[
					'message'  => 'Locking the ticket stock failed.',
					'order_id' => $order_id,
					'error'    => $e->getMessage(),
				]
			);

			return new WP_Error(
				'tec-tc-stock-lock-failed',
				__( 'Unable to verify ticket availability. Please try again.', 'event-tickets' )
			);
		}

		$pools  = [];
		$errors = [];

		foreach ( $quantities as $ticket_id => $quantity ) {
			$ticket = Tribe__Tickets__Tickets::load_ticket_object( $ticket_id );

			if ( null === $ticket ) {
				$errors[ $ticket_id ] = [
					'ticket_id' => $ticket_id,
					'quantity'  => $quantity,
				];
				continue;
			}

			// Seated tickets have their own stock management via the seating service.
			if ( metadata_exists( 'post', $ticket_id, '_tec_slr_seat_type' ) ) {
				continue;
			}

			if ( ! $ticket->manage_stock() || -1 === $ticket->capacity() ) {
				continue;
			}

			$stored_stock = get_post_meta( $ticket_id, Ticket::$stock_meta_key, true );
			$this->add_to_pool( $pools, $ticket_id, '' === $stored_stock ? $ticket->stock() : (int) $stored_stock, $ticket, $quantity );

			$global_stock_mode = $ticket->global_stock_mode();

			if ( ! empty( $global_stock_mode ) && Global_Stock::OWN_STOCK_MODE !== $global_stock_mode ) {
				$event_id     = (int) $ticket->get_event_id();
				$shared_level = get_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, true );

				// No stored shared level: nothing to check against, as before shared capacity was checked.
				if ( '' !== $shared_level ) {
					$this->add_to_pool( $pools, $event_id, (int) $shared_level, $ticket, $quantity );
				}
			}
		}

		foreach ( $pools as $post_id => $pool ) {
			$available = max( 0, $pool['stock'] - $this->get_held_quantity( $post_id, $order_id ) );

			if ( $available >= $pool['demand'] ) {
				continue;
			}

			foreach ( $pool['tickets'] as $ticket ) {
				$errors[ $ticket->ID ] = [
					'ticket_id'    => $ticket->ID,
					'ticket_name'  => $ticket->name,
					'requested'    => $quantities[ $ticket->ID ],
					'available'    => min( $available, $errors[ $ticket->ID ]['available'] ?? $available ),
					'insufficient' => true,
				];
			}
		}

		if ( ! empty( $errors ) ) {
			return $this->build_insufficient_stock_error( array_values( $errors ) );
		}

		return $pools;
	}

	/**
	 * Adds a ticket's quantity to the stock pool of a ticket or of an event's shared capacity.
	 *
	 * @since 5.31.2
	 *
	 * @param array<int,array{stock:int,demand:int,tickets:Ticket_Object[]}> $pools    The stock pools by post ID, modified in place.
	 * @param int                                                            $post_id  The ticket, or the event with shared capacity.
	 * @param int                                                            $stock    The stored stock of the pool.
	 * @param Ticket_Object                                                  $ticket   The ticket.
	 * @param int                                                            $quantity The requested quantity of the ticket.
	 *
	 * @return void
	 */
	private function add_to_pool( array &$pools, int $post_id, int $stock, Ticket_Object $ticket, int $quantity ): void {
		$pools[ $post_id ] ??= [
			'stock'   => $stock,
			'demand'  => 0,
			'tickets' => [],
		];

		$pools[ $post_id ]['demand']   += $quantity;
		$pools[ $post_id ]['tickets'][] = $ticket;
	}

	/**
	 * Sums the requested quantity of each ticket in the items.
	 *
	 * @since 5.31.2
	 *
	 * @param array $items The cart or order items.
	 *
	 * @return array<int,int> The quantities by ticket ID, in ascending ticket ID.
	 */
	private function get_ticket_quantities( array $items ): array {
		$quantities = [];

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || ! $this->is_ticket( $item ) ) {
				continue;
			}

			// An RSVP v2 "Not Going" item holds no seat, as in Decrease_Stock.
			if (
				RSVP_V2_Constants::TC_RSVP_TYPE === ( $item['type'] ?? '' )
				&& ! tribe_is_truthy( $item['extra']['order_status'] ?? 'yes' )
			) {
				continue;
			}

			$ticket_id = (int) Arr::get( $item, 'ticket_id' );
			$quantity  = (int) Arr::get( $item, 'quantity', 1 );

			// Malformed items with no ticket or no quantity are not a request for stock.
			if ( $ticket_id <= 0 || $quantity <= 0 ) {
				continue;
			}

			$quantities[ $ticket_id ] = ( $quantities[ $ticket_id ] ?? 0 ) + $quantity;
		}

		ksort( $quantities );

		return $quantities;
	}

	/**
	 * Locks the tickets and their shared-capacity events, and loads their stored meta into the cache.
	 *
	 * The lock is the post row of each ticket and event, taken by primary key, so no index gap is locked
	 * and the inserts other checkouts make meanwhile are not blocked. All rows are locked in one statement,
	 * in ascending ID, before anything is read: the transaction's snapshot is taken by the first plain
	 * read, so it then holds what every checkout this one waited for committed. Every writer of this stock
	 * takes the same lock first, and loading the meta into the cache makes every later read in this
	 * request, the stock decrease included, start from it. Call it as the transaction's first statement.
	 *
	 * @since 5.31.2
	 *
	 * @param int[] $ticket_ids The ticket IDs.
	 * @param int[] $event_ids  The tickets' events, when known; a shared-capacity event found only in the
	 *                          ticket meta is locked after the read, and is read from the snapshot.
	 *
	 * @return int[] The locked post IDs: the tickets and their shared-capacity events.
	 *
	 * @throws DatabaseQueryException If a query fails, a lock wait times out or a deadlock is detected.
	 */
	private function lock_stock( array $ticket_ids, array $event_ids = [] ): array {
		$ticket_ids = $this->sorted_ids( $ticket_ids );
		$event_ids  = $this->sorted_ids( $event_ids );

		$this->lock_posts( $this->sorted_ids( array_merge( $ticket_ids, $event_ids ) ) );

		$meta = $this->read_meta( $ticket_ids );

		$shared_event_ids = [];

		foreach ( $ticket_ids as $ticket_id ) {
			$global_stock_mode = $meta[ $ticket_id ][ Global_Stock::TICKET_STOCK_MODE ][0] ?? '';

			if ( ! empty( $global_stock_mode ) && Global_Stock::OWN_STOCK_MODE !== $global_stock_mode ) {
				$shared_event_ids[] = absint( $meta[ $ticket_id ][ Ticket::$event_relation_meta_key ][0] ?? 0 );
			}
		}

		$shared_event_ids = $this->sorted_ids( $shared_event_ids );

		$this->lock_posts( array_values( array_diff( $shared_event_ids, $event_ids ) ) );

		$meta += $this->read_meta( $shared_event_ids );

		foreach ( $meta as $post_id => $post_meta ) {
			wp_cache_set( $post_id, $post_meta, 'post_meta' );
			tribe( Ticket_Cache_Controller::class )->clean_ticket_cache( $post_id );
		}

		return array_keys( $meta );
	}

	/**
	 * Returns the positive, unique IDs in ascending order.
	 *
	 * @since 5.31.2
	 *
	 * @param array $ids The IDs.
	 *
	 * @return int[] The IDs.
	 */
	private function sorted_ids( array $ids ): array {
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
		sort( $ids );

		return $ids;
	}

	/**
	 * Locks the posts' rows by primary key until the transaction ends.
	 *
	 * @since 5.31.2
	 *
	 * @param int[] $post_ids The post IDs, in ascending order.
	 *
	 * @return void
	 *
	 * @throws DatabaseQueryException If a query fails, a lock wait times out or a deadlock is detected.
	 */
	private function lock_posts( array $post_ids ): void {
		// Test environments fake transactions, and FOR UPDATE needs a real one.
		if ( empty( $post_ids ) || defined( 'TRIBE_TESTS_HOME_URL' ) || function_exists( 'tec_tickets_tests_fake_transactions_enable' ) ) {
			return;
		}

		$post_ids_in = implode( ',', array_map( 'absint', $post_ids ) );

		DB::get_col( DB::prepare( "SELECT ID FROM %i WHERE ID IN ({$post_ids_in}) ORDER BY ID FOR UPDATE", DB::prefix( 'posts' ) ) );
	}

	/**
	 * Reads all meta of the posts, in the format WordPress caches it.
	 *
	 * @since 5.31.2
	 *
	 * @param int[] $post_ids The post IDs.
	 *
	 * @return array<int,array<string,string[]>> The raw meta values by post ID and key.
	 *
	 * @throws DatabaseQueryException If the query fails.
	 */
	private function read_meta( array $post_ids ): array {
		if ( empty( $post_ids ) ) {
			return [];
		}

		$post_ids_in = implode( ',', array_map( 'absint', $post_ids ) );
		$rows        = DB::get_results(
			DB::prepare(
				"SELECT post_id, meta_key, meta_value FROM %i WHERE post_id IN ({$post_ids_in}) ORDER BY post_id, meta_id",
				DB::prefix( 'postmeta' )
			)
		);

		$meta = array_fill_keys( $post_ids, [] );

		foreach ( (array) $rows as $row ) {
			$meta[ (int) $row->post_id ][ $row->meta_key ][] = $row->meta_value;
		}

		return $meta;
	}

	/**
	 * Locks an order's stock and removes the order from the hold ledgers of its tickets and events.
	 *
	 * @since 5.31.2
	 *
	 * @param int   $order_id The order.
	 * @param array $items    The order's items.
	 *
	 * @return void
	 *
	 * @throws DatabaseQueryException When the stock cannot be locked.
	 */
	private function release_holds( int $order_id, array $items ): void {
		$quantities = $this->get_ticket_quantities( $items );

		if ( empty( $quantities ) ) {
			return;
		}

		$post_ids = $this->lock_stock( array_keys( $quantities ), array_column( $items, 'event_id' ) );

		foreach ( $post_ids as $post_id ) {
			$holds = $this->get_live_holds( $post_id );

			if ( ! isset( $holds[ $order_id ] ) ) {
				continue;
			}

			unset( $holds[ $order_id ] );

			if ( empty( $holds ) ) {
				delete_post_meta( $post_id, self::HOLDS_META_KEY );
				continue;
			}

			update_post_meta( $post_id, self::HOLDS_META_KEY, $holds );
		}
	}

	/**
	 * Returns the unexpired stock holds on a ticket or a shared-capacity event.
	 *
	 * @since 5.31.2
	 *
	 * @param int $post_id The ticket, or the event with shared capacity.
	 *
	 * @return array<int,array{quantity:int,expires:string}> The holds by order ID.
	 */
	private function get_live_holds( int $post_id ): array {
		$holds = get_post_meta( $post_id, self::HOLDS_META_KEY, true );

		if ( ! is_array( $holds ) ) {
			return [];
		}

		$now = tec_get_current_milliseconds();

		return array_filter(
			$holds,
			static fn( $hold ) => is_array( $hold ) && ( $hold['expires'] ?? '' ) > $now
		);
	}

	/**
	 * Sums the stock other orders' live checkouts hold on a ticket or a shared-capacity event.
	 *
	 * @since 5.31.2
	 *
	 * @param int $post_id  The ticket, or the event with shared capacity.
	 * @param int $order_id The order whose own hold is not counted; 0 for none.
	 *
	 * @return int The held quantity.
	 */
	private function get_held_quantity( int $post_id, int $order_id ): int {
		$held = 0;

		foreach ( $this->get_live_holds( $post_id ) as $holder_id => $hold ) {
			if ( $holder_id === $order_id ) {
				continue;
			}

			$held += (int) ( $hold['quantity'] ?? 0 );
		}

		return $held;
	}
}
