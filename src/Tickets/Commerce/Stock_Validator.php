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
use Tribe__Utils__Array as Arr;
use Tribe__Tickets__Tickets;
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
	 * Validates cart stock with database row-level locking.
	 *
	 * This method acquires row-level locks on ticket stock meta rows
	 * using SELECT ... FOR UPDATE to prevent concurrent modifications
	 * during validation.
	 *
	 * @since 5.26.7
	 *
	 * @param Cart $cart The cart to validate.
	 *
	 * @return true|WP_Error True if stock is available, WP_Error if not.
	 */
	public function validate_cart_stock_with_lock( Cart $cart ) {
		$items = $cart->get_items_in_cart();

		// Bail early if cart is empty.
		if ( empty( $items ) ) {
			return true;
		}

		$validation_errors = [];

		foreach ( $items as $item ) {
			// Skip non-ticket items.
			if ( ! $this->is_ticket( $item ) ) {
				continue;
			}

			$ticket_id = Arr::get( $item, 'ticket_id' );
			$quantity  = (int) Arr::get( $item, 'quantity', 1 );

			// Skip if the requested quantity is invalid (user is trying to purchase 0 or negative tickets).
			// This is an edge case for malformed cart data and should not happen in normal operation.
			if ( $quantity <= 0 ) {
				continue;
			}

			// Lock and validate this ticket's stock.
			$validation_result = $this->validate_ticket_stock_with_lock( $ticket_id, $quantity );

			// Collect any validation errors.
			if ( is_wp_error( $validation_result ) ) {
				$validation_errors[] = $validation_result->get_error_data();
			}
		}

		// Bail early if we have validation errors.
		if ( ! empty( $validation_errors ) ) {
			return $this->build_insufficient_stock_error( $validation_errors );
		}

		return true;
	}

	/**
	 * Validates order stock for a specific order.
	 *
	 * Used during order status transitions to provide secondary validation.
	 *
	 * @since 5.26.7
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

		$validation_errors = [];

		foreach ( $order->items as $item ) {
			// Skip non-ticket items.
			if ( ! $this->is_ticket( $item ) ) {
				continue;
			}

			$ticket_id = Arr::get( $item, 'ticket_id' );
			$quantity  = (int) Arr::get( $item, 'quantity', 1 );

			// Skip if the requested quantity is invalid (user is trying to purchase 0 or negative tickets).
			if ( $quantity <= 0 ) {
				continue;
			}

			// Validate this ticket's stock (already in transaction, so uses existing locks).
			$validation_result = $this->validate_ticket_stock( $ticket_id, $quantity );

			// Collect any validation errors.
			if ( is_wp_error( $validation_result ) ) {
				$validation_errors[] = $validation_result->get_error_data();
			}
		}

		// Bail early if we have validation errors.
		if ( ! empty( $validation_errors ) ) {
			return $this->build_insufficient_stock_error( $validation_errors );
		}

		return true;
	}

	/**
	 * Validates a single ticket's stock with database locking.
	 *
	 * Acquires a row-level lock on the ticket's stock meta using SELECT FOR UPDATE.
	 *
	 * @since 5.26.7
	 *
	 * @param int $ticket_id The ticket ID.
	 * @param int $quantity  The requested quantity.
	 *
	 * @return true|WP_Error True if stock is available, WP_Error if not.
	 */
	protected function validate_ticket_stock_with_lock( int $ticket_id, int $quantity ) {
		try {
			$stock_meta_key = Ticket::$stock_meta_key;

			// In test environments with fake transactions, skip the FOR UPDATE clause.
			// The FOR UPDATE requires a real transaction context to work properly.
			$use_locking = ! ( defined( 'TRIBE_TESTS_HOME_URL' ) || function_exists( 'tec_tickets_tests_fake_transactions_enable' ) );

			if ( $use_locking ) {
				// Production: Lock the stock meta row for this ticket.
				$locked_stock = DB::get_var(
					DB::prepare(
						'SELECT meta_value FROM %i WHERE post_id = %d AND meta_key = %s FOR UPDATE',
						DB::prefix( 'postmeta' ),
						$ticket_id,
						$stock_meta_key
					)
				);
			} else {
				// Test environment: Use regular SELECT without locking.
				$locked_stock = DB::get_var(
					DB::prepare(
						'SELECT meta_value FROM %i WHERE post_id = %d AND meta_key = %s',
						DB::prefix( 'postmeta' ),
						$ticket_id,
						$stock_meta_key
					)
				);
			}

			// If no stock meta exists yet, treat as null (not 0).
			// This handles tickets that haven't had stock set yet.
			if ( false === $locked_stock ) {
				$locked_stock = null;
			}

			// Now validate with the locked value.
			return $this->validate_ticket_stock( $ticket_id, $quantity, $locked_stock );

		} catch ( DatabaseQueryException $e ) {
			return new WP_Error(
				'tec-tc-stock-lock-failed',
				__( 'Unable to verify ticket availability. Please try again.', 'event-tickets' ),
				[
					'ticket_id' => $ticket_id,
					'quantity'  => $quantity,
					'error'     => $e->getMessage(),
				]
			);
		}
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
}
