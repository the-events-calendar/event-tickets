<?php
/**
 * Flag Action for validating stock availability before order completion.
 *
 * This file contains the flag action that validates ticket stock availability
 * before any stock decrease operations, preventing overselling by running with
 * higher priority than the Decrease_Stock action.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Status\Refunded;
use TEC\Tickets\Commerce\Traits\Is_Ticket;
use Tribe__Utils__Array as Arr;

/**
 * Class Validate_Stock_Availability
 *
 * Validates stock availability before any stock decrease operations.
 * Runs with higher priority than Decrease_Stock to prevent overselling.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Validate_Stock_Availability extends Flag_Action_Abstract {

	use Is_Ticket;

	/**
	 * Which flags this action should be triggered on.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected $flags = [
		'decrease_stock',
	];

	/**
	 * Which post types this action should be triggered on.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected $post_types = [
		Order::POSTTYPE,
	];

	/**
	 * Priority for this action. Higher priority runs first.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $priority = 5; // Higher priority than Decrease_Stock (10) to prevent overselling.

	/**
	 * {@inheritDoc}
	 */
	public function handle( Status_Interface $new_status, $old_status, \WP_Post $post ) {
		// Get order items from post meta.
		$items = maybe_unserialize( get_post_meta( $post->ID, Order::$items_meta_key, true ) );
		if ( ! is_array( $items ) || empty( $items ) ) {
			return;
		}
		
		$insufficient_stock_items = [];
		
		foreach ( $items as $item ) {
			if ( ! $this->is_ticket( $item ) ) {
				continue;
			}
			
			$ticket = \Tribe__Tickets__Tickets::load_ticket_object( $item['ticket_id'] );
			if ( null === $ticket ) {
				continue;
			}
			
			if ( ! $ticket->manage_stock() ) {
				continue;
			}

			// Skip seated tickets - they have their own stock management system.
			if ( get_post_meta( $ticket->ID, \TEC\Tickets\Seating\Meta::META_KEY_SEAT_TYPE, true ) ) {
				continue;
			}
			
			$requested_quantity = (int) Arr::get( $item, 'quantity', 1 );
			$available_stock    = $ticket->stock();
			
			if ( $available_stock < $requested_quantity ) {
				$insufficient_stock_items[] = [
					'item'      => $item,
					'ticket'    => $ticket, 
					'requested' => $requested_quantity,
					'available' => $available_stock,
				];
			}
		}
		
		if ( ! empty( $insufficient_stock_items ) ) {
			$this->handle_insufficient_stock( $post, $insufficient_stock_items );
		}
	}
	
	/**
	 * Handles the scenario when insufficient stock is detected.
	 *
	 * @since TBD
	 *
	 * @param \WP_Post $order                    The order post object.
	 * @param array    $insufficient_stock_items Array of items with insufficient stock.
	 */
	private function handle_insufficient_stock( \WP_Post $order, array $insufficient_stock_items ) {
		/**
		 * Fires when insufficient stock is detected during order processing.
		 *
		 * @since TBD
		 *
		 * @param \WP_Post $order                    The order post object.
		 * @param array    $insufficient_stock_items Array of items with insufficient stock.
		 */
		do_action( 'tec_tickets_commerce_insufficient_stock_detected', $order, $insufficient_stock_items );
		
		// Strategy 1: Log the overselling attempt.
		$this->log_overselling_attempt( $order, $insufficient_stock_items );
		
		// Strategy 2: Notify administrators.
		$this->notify_administrators( $order, $insufficient_stock_items );
		
		// Strategy 3: Optionally trigger automatic refund.
		$this->maybe_trigger_automatic_refund( $order, $insufficient_stock_items );
	}

	/**
	 * Logs the overselling attempt for audit purposes.
	 *
	 * @since TBD
	 *
	 * @param \WP_Post $order                    The order post object.
	 * @param array    $insufficient_stock_items Array of items with insufficient stock.
	 */
	private function log_overselling_attempt( \WP_Post $order, array $insufficient_stock_items ) {
		$ticket_details = [];
		
		foreach ( $insufficient_stock_items as $stock_item ) {
			$ticket_details[] = sprintf(
				/* translators: %1$d: ticket ID, %2$s: ticket name, %3$d: requested quantity, %4$d: available quantity */
				'Ticket ID %1$d (%2$s): Requested %3$d, Available %4$d',
				$stock_item['ticket']->ID,
				$stock_item['ticket']->name,
				$stock_item['requested'],
				$stock_item['available']
			);
		}
		
		do_action(
			'tribe_log',
			'error',
			sprintf(
				'Overselling attempt detected for Order #%d. Tickets: %s',
				$order->ID,
				implode( '; ', $ticket_details )
			),
			[
				'source'     => 'tickets-commerce-stock-validation',
				'order_id'   => $order->ID,
				'order_hash' => get_post_meta( $order->ID, Order::$hash_meta_key, true ),
				'gateway'    => get_post_meta( $order->ID, Order::$gateway_meta_key, true ),
			]
		);
	}

	/**
	 * Notifies administrators of the overselling attempt.
	 *
	 * @since TBD
	 *
	 * @param \WP_Post $order                    The order post object.
	 * @param array    $insufficient_stock_items Array of items with insufficient stock.
	 */
	private function notify_administrators( \WP_Post $order, array $insufficient_stock_items ) {
		/**
		 * Filters whether to send notifications when overselling is detected.
		 *
		 * @since TBD
		 *
		 * @param bool     $should_notify            Whether to send notifications.
		 * @param \WP_Post $order                    The order post object.
		 * @param array    $insufficient_stock_items Array of items with insufficient stock.
		 */
		$should_notify = apply_filters( 'tec_tickets_commerce_overselling_notify_admin', true, $order, $insufficient_stock_items );
		
		if ( ! $should_notify ) {
			return;
		}
		
		/**
		 * Fires when administrators should be notified of overselling attempts.
		 *
		 * Other plugins can hook into this to send emails, create admin notices, etc.
		 *
		 * @since TBD
		 *
		 * @param \WP_Post $order                    The order post object.
		 * @param array    $insufficient_stock_items Array of items with insufficient stock.
		 */
		do_action( 'tec_tickets_commerce_overselling_admin_notification', $order, $insufficient_stock_items );
	}

	/**
	 * Optionally triggers automatic refund for orders with insufficient stock.
	 *
	 * @since TBD
	 *
	 * @param \WP_Post $order                    The order post object.
	 * @param array    $insufficient_stock_items Array of items with insufficient stock.
	 */
	private function maybe_trigger_automatic_refund( \WP_Post $order, array $insufficient_stock_items ) {
		/**
		 * Filters whether to automatically refund orders with insufficient stock.
		 *
		 * @since TBD
		 *
		 * @param bool     $should_auto_refund       Whether to automatically refund.
		 * @param \WP_Post $order                    The order post object.
		 * @param array    $insufficient_stock_items Array of items with insufficient stock.
		 */
		$should_auto_refund = apply_filters( 'tec_tickets_commerce_auto_refund_insufficient_stock', false, $order, $insufficient_stock_items );
		
		if ( ! $should_auto_refund ) {
			return;
		}
		
		// Get the configured refund status.
		$refund_status = tribe( Refunded::class );
		
		// Only trigger refund if current status allows transition to refunded.
		$current_status = tribe( Status_Handler::class )->get_by_wp_slug( $order->post_status );
		
		if ( $current_status->can_change_to( $refund_status ) ) {
			( new Order() )->modify_status( 
				$order->ID, 
				$refund_status->get_slug(), 
				[
					'refund_reason' => __( 'Automatic refund due to insufficient stock', 'event-tickets' ),
				] 
			);
		}
	}
} 
