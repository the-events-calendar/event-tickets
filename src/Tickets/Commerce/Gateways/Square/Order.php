<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use WP_Post;
use TEC\Tickets\Commerce\Abstract_Order;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Remote_Objects;
use RuntimeException;
use Tribe__Tickets__Main as ET;

/**
 * Class Order.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Order extends Abstract_Order {

	/**
	 * The merchant object.
	 *
	 * @since TBD
	 *
	 * @var Merchant
	 */
	private Merchant $merchant;

	/**
	 * Order constructor.
	 *
	 * @since TBD
	 *
	 * @param Merchant $merchant The merchant object.
	 */
	public function __construct( Merchant $merchant ) {
		$this->merchant = $merchant;
	}

	/**
	 * Create a Square order from a Commerce order.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $order The order object.
	 *
	 * @return WP_Post
	 *
	 * @throws RuntimeException If the order fails to be created or updated.
	 */
	public function upsert_from_order( WP_Post $order ): string {
		$square_order = [
			'location_id'  => $this->merchant->get_location_id(),
			'reference_id' => (string) $order->ID,
			'state'        => 'OPEN',
		];

		$remote_objects = tribe( Remote_Objects::class );

		/**
		 * Filters the customer ID for the Square order.
		 *
		 * @since TBD
		 *
		 * @param string $customer_id The customer ID.
		 * @param WP_Post $order The order object.
		 */
		$customer_id = (string) apply_filters( 'tec_tickets_commerce_square_order_customer_id', $remote_objects->get_customer_id( $order->ID ), $order );

		if ( $customer_id ) {
			$square_order['customer_id'] = $customer_id;
		}

		/**
		 * Filters the metadata for the Square order.
		 *
		 * @since TBD
		 *
		 * @param array $metadata The metadata for the Square order.
		 */
		$square_order['metadata'] = apply_filters(
			'tec_tickets_commerce_square_order_metadata',
			[
				'created_source' => home_url(),
				'et_version'     => ET::VERSION,
			],
			$order,
			$source_id
		);

		$square_order = apply_filters(
			'tec_tickets_commerce_square_order_payload',
			$this->add_items_to_square_payload( $square_order, $order ),
			$order,
			$source_id
		);

		$square_order_id = $this->get_square_order_id( $order->ID );

		if ( $square_order_id && ! $this->needs_update( $square_order, $order->ID ) ) {
			return $square_order_id;
		}

		if ( $square_order_id ) {
			$order_version           = (int) get_post_meta( $order->ID, '_tec_tickets_commerce_gateways_square_order_version', true );
			$square_order['version'] = $order_version ? $order_version : 1;
		}

		$body = [
			'idempotency_key' => uniqid( 'tec-square-cancel-', true ),
			'order'           => $square_order,
		];


		/**
		 * Fires before the Square order is upserted.
		 *
		 * @since TBD
		 *
		 * @param array $square_order The Square order.
		 * @param int   $order_id     The order ID.
		 * @param string $source_id   The source ID.
		 */
		do_action( 'tec_tickets_commerce_square_order_before_upsert', $square_order, $order->ID, $source_id );

		$response = Requests::request(
			$square_order_id ? 'PUT' : 'POST',
			sprintf( 'orders%s', $square_order_id ? "/{$square_order_id}" : '' ),
			[],
			[
				'body' => $body,
			]
		);

		if ( empty( $response['order']['id'] ) ) {
			throw new RuntimeException( 'Failed to create or update Square order.' );
		}

		/**
		 * Fires after the Square order is upserted.
		 *
		 * @since TBD
		 *
		 * @param array  $response     The Square order.
		 * @param int    $order_id     The order ID.
		 * @param array  $square_order The Square order.
		 * @param string $source_id    The source ID.
		 */
		do_action( 'tec_tickets_commerce_square_order_after_upsert', $response['order'], $order->ID, $square_order, $source_id );

		update_post_meta( $order->ID, '_tec_tickets_commerce_gateways_square_order_id', $response['order']['id'] );
		update_post_meta( $order->ID, '_tec_tickets_commerce_gateways_square_order_version', $response['order']['version'] );
		update_post_meta( $order->ID, '_tec_tickets_commerce_gateways_square_order', wp_json_encode( $response['order'] ) );
		update_post_meta( $order->ID, '_tec_tickets_commerce_gateways_square_order_payload', wp_json_encode( $square_order ) );

		/**
		 * Schedule a pull of the order from Square. Making this more reliable than listening to the webhook.
		 *
		 * @todo dimi: This needs to be in sync with webhook handling. Ill get into that soon.
		 * as_schedule_single_event( time() + 20 * MINUTE_IN_SECONDS, 'tec_tickets_commerce_square_order_sync', [ $order->ID ] );
		 */

		return $response['order']['id'];
	}

	/**
	 * Get the cached remote data for an order.
	 *
	 * @since TBD
	 *
	 * @param int    $order_id The order ID.
	 * @param string $local_id The local ID.
	 * @param string $type     The type of data to get.
	 *
	 * @return array
	 */
	public function get_cached_remote_data( int $order_id, string $local_id, string $type = 'line_items' ): array {
		$cache       = tribe_cache();
		$cache_key   = 'tec_tickets_commerce_square_order_' . $order_id;
		$cached_data = $cache[ $cache_key ] ?? false;

		if ( ! is_array( $cached_data ) ) {
			$cached_data = json_decode( get_post_meta( $order_id, '_tec_tickets_commerce_gateways_square_order', true ), true );

			$cache[ $cache_key ] = $cached_data;
		}

		$items = $cached_data[ $type ] ?? [];

		foreach ( $items as $item ) {
			$stored_id = $item['metadata']['local_id'] ?? false;

			if ( ! $stored_id ) {
				continue;
			}

			if ( $stored_id !== $local_id ) {
				continue;
			}

			return $item;
		}

		return [];
	}

	/**
	 * Get the URL to view the order in Square dashboard.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $order The order object.
	 *
	 * @return string
	 */
	public function get_gateway_dashboard_url_by_order( WP_Post $order ): ?string {
		if ( ! $this->merchant->is_active() ) {
			return '';
		}

		$order_id = $this->get_square_order_id( $order->ID );

		if ( empty( $order_id ) ) {
			return '';
		}

		$is_test_mode = tribe( Gateway::class )->is_test_mode();

		return sprintf( 'https://app.squareup%s.com/dashboard/orders/overview/%s', $is_test_mode ? 'sandbox' : '', $order_id );
	}

	/**
	 * Get the Square order ID from the order.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return string
	 */
	public function get_square_order_id( int $order_id ): string {
		/**
		 * Filters the Square order ID.
		 *
		 * @since TBD
		 *
		 * @param string $square_order_id The Square order ID.
		 */
		return (string) apply_filters(
			'tec_tickets_commerce_square_order_id',
			(string) get_post_meta( $order_id, '_tec_tickets_commerce_gateways_square_order_id', true ),
			$order_id
		);
	}

	/**
	 * Check if the order needs to be updated.
	 *
	 * @since TBD
	 *
	 * @param array $square_order The Square order.
	 * @param int   $order_id The order ID.
	 *
	 * @return bool
	 */
	public function needs_update( array $square_order, int $order_id ): bool {
		/**
		 * Filters if the Square order needs to be updated.
		 *
		 * @since TBD
		 *
		 * @param bool $needs_update Whether the Square order needs to be updated.
		 */
		return apply_filters(
			'tec_tickets_commerce_square_order_needs_update',
			md5( wp_json_encode( $square_order ) ) !== md5( get_post_meta( $order_id, '_tec_tickets_commerce_gateways_square_order_payload', true ) ),
			$square_order,
			$order_id
		);
	}

	/**
	 * Add items to the Square payload.
	 *
	 * @since TBD
	 *
	 * @param array   $square_order The Square order.
	 * @param WP_Post $order        The order object.
	 *
	 * @return array
	 */
	public function add_items_to_square_payload( array $square_order, WP_Post $order ): array {
		$remote_objects = tribe( Remote_Objects::class );

		$line_items = [];

		foreach ( $order->fees as $fee ) {
			if ( ! isset( $line_items['service_charges'] ) ) {
				$line_items['service_charges'] = [];
			}

			$charge = $remote_objects->get_service_charge( $fee, $order );

			if ( empty( $charge ) ) {
				continue;
			}

			$line_items['service_charges'][] = $charge;
		}

		foreach ( $order->coupons as $coupon ) {
			if ( ! isset( $line_items['discounts'] ) ) {
				$line_items['discounts'] = [];
			}

			$discount = $remote_objects->get_discount( $coupon, $order );

			if ( empty( $discount ) ) {
				continue;
			}

			$line_items['discounts'][] = $discount;
		}

		foreach ( $order->items as $item ) {
			if ( ! isset( $line_items['line_items'] ) ) {
				$line_items['line_items'] = [];
			}

			$line_item = $remote_objects->get_line_item( $item, $order );

			if ( empty( $line_item ) ) {
				continue;
			}

			$line_items['line_items'][] = $line_item;
		}

		return array_merge( $square_order, $line_items );
	}
}
