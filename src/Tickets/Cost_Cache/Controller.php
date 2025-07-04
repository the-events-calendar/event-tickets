<?php
/**
 * Controller for cost caching functionality.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Cost_Cache
 */

namespace TEC\Tickets\Cost_Cache;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use Tribe__Tickets__Ticket_Object;

/**
 * Class Controller
 *
 * @since TBD
 *
 * @package TEC\Tickets\Cost_Cache
 */
class Controller extends Controller_Contract {

	/**
	 * The cache instance.
	 *
	 * @since TBD
	 *
	 * @var Cache
	 */
	private $cache;

	/**
	 * Register the controller.
	 *
	 * @since TBD
	 */
	public function do_register(): void {
		$this->container->singleton( Cache::class );
		$this->cache = $this->container->make( Cache::class );

		if ( $this->cache->is_enabled() ) {
			$this->add_hooks();
		}
	}

	/**
	 * Unregister the controller.
	 *
	 * @since TBD
	 */
	public function unregister(): void {
		$this->remove_hooks();
	}

	/**
	 * Add hooks for cost caching.
	 *
	 * @since TBD
	 */
	protected function add_hooks() {
		// Filter to pre-empt cost calculation with cached value.
		add_filter( 'tec_events_pre_get_cost', [ $this, 'filter_pre_get_cost' ], 10, 3 );

		// Filter to cache the calculated cost.
		add_filter( 'tec_events_get_cost', [ $this, 'filter_get_cost' ], 10, 3 );

		// Cache invalidation hooks.
		$this->add_invalidation_hooks();
	}

	/**
	 * Remove hooks.
	 *
	 * @since TBD
	 */
	protected function remove_hooks() {
		remove_filter( 'tec_events_pre_get_cost', [ $this, 'filter_pre_get_cost' ], 10 );
		remove_filter( 'tec_events_get_cost', [ $this, 'filter_get_cost' ], 10 );
		$this->remove_invalidation_hooks();
	}

	/**
	 * Filter tec_events_pre_get_cost to return cached value and prevent queries.
	 *
	 * @since TBD
	 *
	 * @param string|null $cost                 The pre-filtered cost (null by default).
	 * @param int|null    $post_id              The event ID.
	 * @param bool        $with_currency_symbol Whether to include currency symbol.
	 *
	 * @return string|null The cached cost or null if not cached.
	 */
	public function filter_pre_get_cost( $cost, $post_id, $with_currency_symbol ) {
		// If another filter already set a value, respect it.
		if ( null !== $cost ) {
			return $cost;
		}

		// Check if we have a cached value.
		$cached_cost = $this->cache->get( $post_id, $with_currency_symbol );

		if ( false !== $cached_cost ) {
			return $cached_cost;
		}

		// Return null to allow normal cost calculation.
		return null;
	}

	/**
	 * Filter tec_events_get_cost to cache the calculated cost.
	 *
	 * @since TBD
	 *
	 * @param string|null $cost                 The calculated cost.
	 * @param int|null    $post_id              The event ID.
	 * @param bool        $with_currency_symbol Whether to include currency symbol.
	 *
	 * @return string The cost (unchanged).
	 */
	public function filter_get_cost( $cost, $post_id, $with_currency_symbol ) {
		// Only cache if we have a valid cost and post ID.
		if ( ! empty( $cost ) && ! empty( $post_id ) ) {
			$this->cache->set( $post_id, $with_currency_symbol, $cost );
		}

		return $cost;
	}

	/**
	 * Add cache invalidation hooks.
	 *
	 * @since TBD
	 */
	protected function add_invalidation_hooks() {
		// Event updates.
		add_action( 'save_post_tribe_events', [ $this, 'clear_event_cache' ] );
		add_action( 'delete_post', [ $this, 'clear_event_cache' ] );
		add_action( 'trash_post', [ $this, 'clear_event_cache' ] );

		// Ticket updates (any ticket update might affect event cost).
		add_action( 'event_tickets_after_save_ticket', [ $this, 'clear_cache_for_ticket' ], 10, 3 );
		add_action( 'event_tickets_attendee_ticket_deleted', [ $this, 'clear_cache_for_ticket' ], 10, 2 );
		add_action( 'tec_tickets_ticket_stock_changed', [ $this, 'clear_cache_for_ticket' ], 10, 2 );

		// Tickets Commerce order completion.
		add_action( 'tec_tickets_commerce_order_status_complete', [ $this, 'clear_cache_for_order' ] );
		add_action( 'tec_tickets_commerce_order_status_update', [ $this, 'clear_cache_for_order' ] );

		// WooCommerce order completion.
		add_action( 'woocommerce_order_status_completed', [ $this, 'clear_cache_for_woo_order' ] );
		add_action( 'woocommerce_order_status_processing', [ $this, 'clear_cache_for_woo_order' ] );
		add_action( 'woocommerce_order_status_changed', [ $this, 'clear_cache_for_woo_order' ] );

		// Easy Digital Downloads order completion.
		add_action( 'edd_complete_purchase', [ $this, 'clear_cache_for_edd_order' ] );
		add_action( 'edd_update_payment_status', [ $this, 'clear_cache_for_edd_payment' ], 10, 3 );

		// RSVP updates.
		add_action( 'event_tickets_rsvp_tickets_generated', [ $this, 'clear_cache_for_rsvp' ], 10, 2 );
		add_action( 'event_tickets_rsvp_deleted', [ $this, 'clear_cache_for_rsvp' ], 10, 2 );

		// Meta updates that might affect cost.
		add_action( 'updated_post_meta', [ $this, 'maybe_clear_cache_for_meta' ], 10, 4 );
		add_action( 'added_post_meta', [ $this, 'maybe_clear_cache_for_meta' ], 10, 4 );
		add_action( 'deleted_post_meta', [ $this, 'maybe_clear_cache_for_meta' ], 10, 4 );
	}

	/**
	 * Remove cache invalidation hooks.
	 *
	 * @since TBD
	 */
	protected function remove_invalidation_hooks() {
		remove_action( 'save_post_tribe_events', [ $this, 'clear_event_cache' ] );
		remove_action( 'delete_post', [ $this, 'clear_event_cache' ] );
		remove_action( 'trash_post', [ $this, 'clear_event_cache' ] );
		remove_action( 'event_tickets_after_save_ticket', [ $this, 'clear_cache_for_ticket' ], 10 );
		remove_action( 'event_tickets_attendee_ticket_deleted', [ $this, 'clear_cache_for_ticket' ], 10 );
		remove_action( 'tec_tickets_ticket_stock_changed', [ $this, 'clear_cache_for_ticket' ], 10 );
		remove_action( 'tec_tickets_commerce_order_status_complete', [ $this, 'clear_cache_for_order' ] );
		remove_action( 'tec_tickets_commerce_order_status_update', [ $this, 'clear_cache_for_order' ] );
		remove_action( 'woocommerce_order_status_completed', [ $this, 'clear_cache_for_woo_order' ] );
		remove_action( 'woocommerce_order_status_processing', [ $this, 'clear_cache_for_woo_order' ] );
		remove_action( 'woocommerce_order_status_changed', [ $this, 'clear_cache_for_woo_order' ] );
		remove_action( 'edd_complete_purchase', [ $this, 'clear_cache_for_edd_order' ] );
		remove_action( 'edd_update_payment_status', [ $this, 'clear_cache_for_edd_payment' ], 10 );
		remove_action( 'event_tickets_rsvp_tickets_generated', [ $this, 'clear_cache_for_rsvp' ], 10 );
		remove_action( 'event_tickets_rsvp_deleted', [ $this, 'clear_cache_for_rsvp' ], 10 );
		remove_action( 'updated_post_meta', [ $this, 'maybe_clear_cache_for_meta' ], 10 );
		remove_action( 'added_post_meta', [ $this, 'maybe_clear_cache_for_meta' ], 10 );
		remove_action( 'deleted_post_meta', [ $this, 'maybe_clear_cache_for_meta' ], 10 );
	}

	/**
	 * Clear cache for an event.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 */
	public function clear_event_cache( $post_id ) {
		if ( 'tribe_events' !== get_post_type( $post_id ) ) {
			return;
		}

		$this->cache->clear( $post_id );
	}

	/**
	 * Clear cache for a ticket's event.
	 *
	 * @since TBD
	 *
	 * @param int                           $ticket_id The ticket ID.
	 * @param int                           $event_id  The event ID.
	 * @param Tribe__Tickets__Ticket_Object $ticket    The ticket object (optional).
	 */
	public function clear_cache_for_ticket( $ticket_id, $event_id = null, $ticket = null ) {
		// Handle different parameter scenarios.
		if ( ! empty( $event_id ) && is_numeric( $event_id ) ) {
			$this->cache->clear( $event_id );
			return;
		}

		// Try to get event ID from ticket object.
		if ( $ticket instanceof Tribe__Tickets__Ticket_Object ) {
			$event_id = $ticket->get_event_id();
			if ( $event_id ) {
				$this->cache->clear( $event_id );
				return;
			}
		}

		// Try to get event ID from ticket ID.
		if ( is_numeric( $ticket_id ) ) {
			$event_id = get_post_meta( $ticket_id, '_tribe_rsvp_for_event', true );
			if ( ! $event_id ) {
				$event_id = get_post_meta( $ticket_id, '_tribe_wooticket_for_event', true );
			}
			if ( ! $event_id ) {
				$event_id = get_post_meta( $ticket_id, '_tribe_eddticket_for_event', true );
			}
			if ( ! $event_id ) {
				$event_id = get_post_meta( $ticket_id, '_tec_tickets_commerce_event', true );
			}

			if ( $event_id ) {
				$this->cache->clear( $event_id );
			}
		}
	}

	/**
	 * Clear cache for a Tickets Commerce order.
	 *
	 * @since TBD
	 *
	 * @param \WP_Post|int $order The order object or ID.
	 */
	public function clear_cache_for_order( $order ) {
		$order_id = is_object( $order ) ? $order->ID : $order;
		
		// Get event IDs from order items.
		$event_ids = $this->get_event_ids_from_tc_order( $order_id );
		
		foreach ( $event_ids as $event_id ) {
			$this->cache->clear( $event_id );
		}
	}

	/**
	 * Clear cache for a WooCommerce order.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 */
	public function clear_cache_for_woo_order( $order_id ) {
		$event_ids = $this->get_event_ids_from_woo_order( $order_id );
		
		foreach ( $event_ids as $event_id ) {
			$this->cache->clear( $event_id );
		}
	}

	/**
	 * Clear cache for an EDD order.
	 *
	 * @since TBD
	 *
	 * @param int $payment_id The payment ID.
	 */
	public function clear_cache_for_edd_order( $payment_id ) {
		$event_ids = $this->get_event_ids_from_edd_order( $payment_id );
		
		foreach ( $event_ids as $event_id ) {
			$this->cache->clear( $event_id );
		}
	}

	/**
	 * Clear cache for an EDD payment status update.
	 *
	 * @since TBD
	 *
	 * @param int    $payment_id The payment ID.
	 * @param string $new_status The new status.
	 * @param string $old_status The old status.
	 */
	public function clear_cache_for_edd_payment( $payment_id, $new_status, $old_status ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( in_array( $new_status, [ 'complete', 'publish' ], true ) ) {
			$this->clear_cache_for_edd_order( $payment_id );
		}
	}

	/**
	 * Clear cache for RSVP.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 * @param int $post_id  The event ID.
	 */
	public function clear_cache_for_rsvp( $order_id, $post_id ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( is_numeric( $post_id ) ) {
			$this->cache->clear( $post_id );
		}
	}

	/**
	 * Maybe clear cache when meta is updated.
	 *
	 * @since TBD
	 *
	 * @param int    $meta_id    The meta ID.
	 * @param int    $object_id  The object ID.
	 * @param string $meta_key   The meta key.
	 * @param mixed  $meta_value The meta value.
	 */
	public function maybe_clear_cache_for_meta( $meta_id, $object_id, $meta_key, $meta_value ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		// Check if this is a cost-related meta key.
		$cost_meta_keys = [
			'_EventCost',
			'_EventCurrencySymbol',
			'_EventCurrencyPosition',
		];

		if ( in_array( $meta_key, $cost_meta_keys, true ) ) {
			$this->clear_event_cache( $object_id );
		}
	}

	/**
	 * Get event IDs from a Tickets Commerce order.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return array Event IDs.
	 */
	private function get_event_ids_from_tc_order( $order_id ) {
		$event_ids = [];
		
		// Get order items.
		$items = get_post_meta( $order_id, '_tec_tickets_commerce_order_items', true );
		
		if ( ! empty( $items ) && is_array( $items ) ) {
			foreach ( $items as $item ) {
				if ( ! empty( $item['event_id'] ) ) {
					$event_ids[] = $item['event_id'];
				}
			}
		}
		
		return array_unique( array_filter( $event_ids ) );
	}

	/**
	 * Get event IDs from a WooCommerce order.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return array Event IDs.
	 */
	private function get_event_ids_from_woo_order( $order_id ) {
		$event_ids = [];
		
		if ( ! function_exists( 'wc_get_order' ) ) {
			return $event_ids;
		}
		
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return $event_ids;
		}
		
		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			$event_id   = get_post_meta( $product_id, '_tribe_wooticket_for_event', true );
			
			if ( $event_id ) {
				$event_ids[] = $event_id;
			}
		}
		
		return array_unique( array_filter( $event_ids ) );
	}

	/**
	 * Get event IDs from an EDD order.
	 *
	 * @since TBD
	 *
	 * @param int $payment_id The payment ID.
	 *
	 * @return array Event IDs.
	 */
	private function get_event_ids_from_edd_order( $payment_id ) {
		$event_ids = [];
		
		if ( ! function_exists( 'edd_get_payment' ) ) {
			return $event_ids;
		}
		
		$payment = edd_get_payment( $payment_id );
		if ( ! $payment ) {
			return $event_ids;
		}
		
		$downloads = $payment->downloads;
		if ( ! empty( $downloads ) ) {
			foreach ( $downloads as $download ) {
				$event_id = get_post_meta( $download['id'], '_tribe_eddticket_for_event', true );
				
				if ( $event_id ) {
					$event_ids[] = $event_id;
				}
			}
		}
		
		return array_unique( array_filter( $event_ids ) );
	}
}
