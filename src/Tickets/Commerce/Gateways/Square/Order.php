<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use WP_Post;
use TEC\Tickets\Commerce\Abstract_Order;
use TEC\Tickets\Commerce\Order as Commerce_Order;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Remote_Objects;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Item;
use RuntimeException;
use Tribe__Tickets__Main as ET;
use TEC\Tickets\Commerce\Values\Precision_Value;
use TEC\Tickets\Commerce\Gateways\Square\Status;
use TEC\Tickets\Commerce\Gateways\Square\Settings;
use TEC\Tickets\Commerce\Settings as Commerce_Settings;
use TEC\Tickets\Commerce\Meta as Commerce_Meta;
use TEC\Tickets\Commerce\Ticket as Ticket_Data;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_User_Query;
use WP_User;
use stdClass;

/**
 * Class Order.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Order extends Abstract_Order {
	/**
	 * The hook to pull the order.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const HOOK_PULL_ORDER_ACTION = 'tec_tickets_commerce_square_order_pull_order';

	/**
	 * The merchant object.
	 *
	 * @since TBD
	 *
	 * @var Merchant
	 */
	private Merchant $merchant;

	/**
	 * The commerce order object.
	 *
	 * @since TBD
	 *
	 * @var Commerce_Order
	 */
	private Commerce_Order $commerce_order;

	/**
	 * The settings object.
	 *
	 * @since TBD
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Order constructor.
	 *
	 * @since TBD
	 *
	 * @param Merchant       $merchant       The merchant object.
	 * @param Commerce_Order $commerce_order The commerce order object.
	 * @param Settings       $settings       The settings object.
	 */
	public function __construct( Merchant $merchant, Commerce_Order $commerce_order, Settings $settings ) {
		$this->merchant       = $merchant;
		$this->commerce_order = $commerce_order;
		$this->settings       = $settings;
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
	public function upsert_square_from_local_order( WP_Post $order ): string {
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
			$order
		);

		$square_order = apply_filters(
			'tec_tickets_commerce_square_order_payload',
			$this->add_items_to_square_payload( $square_order, $order ),
			$order
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
		 */
		do_action( 'tec_tickets_commerce_square_order_before_upsert', $square_order, $order->ID );

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
		 */
		do_action( 'tec_tickets_commerce_square_order_after_upsert', $response['order'], $order->ID, $square_order );

		update_post_meta( $order->ID, '_tec_tickets_commerce_gateways_square_order_id', $response['order']['id'] );
		update_post_meta( $order->ID, '_tec_tickets_commerce_gateways_square_order_version', $response['order']['version'] );
		update_post_meta( $order->ID, '_tec_tickets_commerce_gateways_square_order', wp_json_encode( $response['order'] ) );
		update_post_meta( $order->ID, '_tec_tickets_commerce_gateways_square_order_payload', wp_json_encode( $square_order ) );

		tribe( Syncs\Regulator::class )->schedule( self::HOOK_PULL_ORDER_ACTION, [ $response['order']['id'] ], 2 * MINUTE_IN_SECONDS );

		return $response['order']['id'];
	}

	/**
	 * Upsert a local order from a Square order.
	 *
	 * @since TBD
	 *
	 * @param string $square_order_id The Square order ID.
	 * @param array  $event_data The event data.
	 *
	 * @return ?WP_Post
	 */
	public function upsert_local_from_square_order( string $square_order_id, array $event_data = [] ): ?WP_Post {
		$square_order = $this->get_square_order( $square_order_id );

		if ( empty( $square_order['order'] ) ) {
			return null;
		}

		$square_order = $square_order['order'];

		$ref_id = $square_order['reference_id'] ?? false;
		if ( $ref_id && is_numeric( $ref_id ) ) {
			$order     = tec_tc_get_order( $ref_id );
			$is_update = $order instanceof WP_Post;
		}

		$order     = $order instanceof WP_Post ? $order : $this->get_by_square_order_id( $square_order_id );
		$is_update = $order instanceof WP_Post;

		if ( ! $is_update && ! $this->settings->is_inventory_sync_enabled() ) {
			// When the sync is not enabled, we listen ONLY for UPDATES to existing orders to our DB.
			return null;
		}

		if ( ! $is_update ) {
			$items = $this->get_items_from_square_order( $square_order_id );

			if ( empty( $items ) ) {
				// We don't create orders without at least one item we recognize.
				return null;
			}

			$missed_money = $items['missed_money'] ?? 0;

			unset( $items['missed_money'] );

			$net_amounts = $square_order['net_amounts'];

			$total_value = new Precision_Value( $net_amounts['total_money']['amount'] / 100 );

			$subtotal_value  = $net_amounts['total_money']['amount'] - $net_amounts['tax_money']['amount'] - $net_amounts['tip_money']['amount'] - $net_amounts['service_charge_money']['amount'];
			$subtotal_value += $net_amounts['discount_money']['amount'];
			$subtotal_value  = new Precision_Value( $subtotal_value / 100 );

			$hash = md5( microtime() . wp_json_encode( $items ) );

			$purchaser = $this->get_square_orders_customer( $square_order_id );

			$order_args = [
				'total_value'          => $total_value->get(),
				'subtotal'             => $subtotal_value->get(),
				'currency'             => $net_amounts['total_money']['currency'],
				'hash'                 => $hash,
				'items'                => $items,
				'gateway'              => Gateway::get_key(),
				'title'                => $this->commerce_order->generate_order_title( $items, $hash ),
				'purchaser_user_id'    => $purchaser->ID ?? 0,
				'purchaser_full_name'  => $purchaser->display_name ?? '',
				'purchaser_first_name' => $purchaser->first_name ?? '',
				'purchaser_last_name'  => $purchaser->last_name ?? '',
				'purchaser_email'      => $purchaser->user_email ?? '',
				'gateway_order_id'     => $square_order_id,
			];

			$order = $this->commerce_order->create( tribe( Gateway::class ), $order_args );

			update_post_meta( $order->ID, Commerce_Order::META_ORDER_TOTAL_AMOUNT_UNACCOUNTED, $missed_money );
			update_post_meta( $order->ID, Commerce_Order::META_ORDER_TOTAL_TAX, ( new Precision_Value( $net_amounts['tax_money']['amount'] / 100 ) )->get() );
			update_post_meta( $order->ID, Commerce_Order::META_ORDER_TOTAL_TIP, ( new Precision_Value( $net_amounts['tip_money']['amount'] / 100 ) )->get() );
			update_post_meta( $order->ID, Commerce_Order::META_ORDER_CREATED_BY, 'square-pos' );

			update_post_meta( $order->ID, '_tec_tickets_commerce_gateways_square_order_id', $square_order_id );
			update_post_meta( $order->ID, '_tec_tickets_commerce_gateways_square_order_version', $square_order['version'] ?? 1 );
			update_post_meta( $order->ID, '_tec_tickets_commerce_gateways_square_order', wp_json_encode( $square_order ) );
			update_post_meta( $order->ID, '_tec_tickets_commerce_gateways_square_order_payload', wp_json_encode( $square_order ) );
		}

		if ( ! $order instanceof WP_Post ) {
			return null;
		}

		$status = $square_order['state'] ?? false;

		if ( ! $status ) {
			do_action(
				'tribe_log',
				'error',
				'Square order webhook - no status found',
				[
					'source'     => 'tickets-commerce-square',
					'square_id'  => $square_order_id,
					'order_id'   => $order->ID,
					'event_data' => $event_data,
				]
			);
			return $order;
		}

		$status_obj = tribe( Status::class )->convert_to_commerce_status( $status );

		if ( ! $status_obj ) {
			do_action(
				'tribe_log',
				'error',
				'Square order webhook - no matching status found',
				[
					'source'     => 'tickets-commerce-square',
					'square_id'  => $square_order_id,
					'order_id'   => $order->ID,
					'status'     => $status,
					'event_data' => $event_data,
				]
			);
			return $order;
		}

		$this->commerce_order->modify_status( $order->ID, $status_obj->get_slug(), $event_data ? [ 'gateway_payload' => $event_data ] : [] );

		return $order;
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

	/**
	 * Get the items from the Square order.
	 *
	 * @since TBD
	 *
	 * @param string $square_order_id The Square order ID.
	 *
	 * @return array
	 */
	public function get_items_from_square_order( string $square_order_id ): array {
		$square_order = $this->get_square_order( $square_order_id );

		if ( empty( $square_order['order'] ) ) {
			return [];
		}

		$square_order = $square_order['order'];

		$items = [];

		// This corresponds to our tickets.
		$tickets = $square_order['line_items'] ?? [];

		$missed_money = 0;

		foreach ( $tickets as $ticket ) {
			$object_id = $ticket['metadata']['local_id'] ?? false;
			if ( ! is_numeric( $object_id ) ) {
				$object_id = Commerce_Meta::get_object_id( Item::SQUARE_ID_META, $ticket['catalog_object_id'] );
			}

			$ticket_obj = tribe( Ticket_Data::class )->load_ticket_object( $object_id );

			if ( ! $ticket_obj instanceof Ticket_Object ) {
				$second_id = Commerce_Meta::get_object_id( Item::SQUARE_ID_META, $ticket['catalog_object_id'] );
				if ( $second_id === $object_id ) {
					$missed_money += $ticket['variation_total_price_money']['amount'];
					continue;
				}

				$object_id = $second_id;
			}

			$ticket_obj = tribe( Ticket_Data::class )->load_ticket_object( $object_id );

			if ( ! $ticket_obj instanceof Ticket_Object ) {
				$missed_money += $ticket['variation_total_price_money']['amount'];
				continue;
			}

			$items[] = [
				'event_id'          => $ticket_obj->get_event_id(),
				'price'             => ( new Precision_Value( $ticket['base_price_money']['amount'] / 100 ) )->get(),
				'quantity'          => $ticket['quantity'] ?? 1,
				'ticket_id'         => $ticket_obj->ID,
				'regular_price'     => $ticket_obj->regular_price,
				'regular_sub_total' => ( new Precision_Value( $ticket_obj->regular_price * ( $ticket['quantity'] ?? 1 ) ) )->get(),
				'sub_total'         => ( new Precision_Value( $ticket['variation_total_price_money']['amount'] / 100 ) )->get(),
				'type'              => 'ticket',
			];
		}

		if ( ! $items ) {
			// If no ticket was found, we bail.
			return [];
		}

		if ( $missed_money > 0 ) {
			$items['missed_money'] = ( new Precision_Value( $missed_money / 100 ) )->get();
		}

		// This corresponds to our coupons.
		$coupons = $square_order['discounts'] ?? [];

		foreach ( $coupons as $offset => $coupon ) {
			$items[] = [
				'id'           => $coupon['metadata']['local_id'] ?? 0,
				'type'         => 'coupon',
				'coupon_id'    => $coupon['metadata']['local_id'] ?? 0,
				'price'        => ( new Precision_Value( $coupon['applied_money']['amount'] / 100 ) )->get(),
				'sub_total'    => ( new Precision_Value( -1 * $coupon['applied_money']['amount'] / 100 ) )->get(),
				// translators: %d is the offset of the coupon.
				'display_name' => sprintf( __( 'Square Applied Discount %d', 'event-tickets' ), (int) $offset + 1 ),
				'slug'         => sprintf( 'square-applied-discount-%d', (int) $offset + 1 ),
				'quantity'     => 1,
				'event_id'     => 0,
				'ticket_id'    => 0,
			];
		}

		// Our booking fees are supports as service charges.
		$booking_fees = $square_order['service_charges'] ?? [];

		foreach ( $booking_fees as $offset => $fee ) {
			$items[] = [
				'id'           => $fee['metadata']['local_id'] ?? 0,
				'type'         => 'fee',
				'price'        => ( new Precision_Value( $fee['applied_money']['amount'] / 100 ) )->get(),
				'sub_total'    => ( new Precision_Value( $fee['applied_money']['amount'] / 100 ) )->get(),
				'fee_id'       => $fee['metadata']['local_id'] ?? 0,
				'display_name' => $fee['name'],
				'ticket_id'    => 0,
				'event_id'     => 0,
				'quantity'     => 1,
			];
		}

		return $items;
	}

	/**
	 * Get the customer from the Square order.
	 *
	 * @since TBD
	 *
	 * @param string $square_order_id The Square order ID.
	 *
	 * @return WP_User|null
	 */
	public function get_square_orders_customer( string $square_order_id ): ?WP_User {
		$square_order = $this->get_square_order( $square_order_id );

		if ( empty( $square_order['order'] ) ) {
			return null;
		}

		$customer_id = $square_order['order']['customer_id'] ?? false;

		if ( ! $customer_id ) {
			return null;
		}

		$user_query = new WP_User_Query(
			[
				'meta_key'   => Commerce_Settings::get_key( '_tec_tickets_commerce_gateways_square_customer_id_%s' ),
				'meta_value' => $customer_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'number'     => 1,
			]
		);

		if ( ! empty( $user_query->get_results() ) ) {
			return $user_query->get_results()[0];
		}

		$remote_customer = $this->get_square_customer( $customer_id );
		if ( empty( $remote_customer['customer'] ) ) {
			return null;
		}

		$user_query = new WP_User_Query(
			[
				'search'         => $remote_customer['customer']['email_address'],
				'search_columns' => [ 'user_email' ],
			]
		);

		if ( ! empty( $user_query->get_results() ) ) {
			return $user_query->get_results()[0];
		}

		$user_obj = new stdClass();

		$user_obj->ID   = 0;
		$user_obj->data = new stdClass();

		$user_obj->data->user_email   = $remote_customer['customer']['email_address'];
		$user_obj->data->display_name = $remote_customer['customer']['given_name'] . ' ' . $remote_customer['customer']['family_name'];
		$user_obj->data->first_name   = $remote_customer['customer']['given_name'];
		$user_obj->data->last_name    = $remote_customer['customer']['family_name'];

		return new WP_User( $user_obj );
	}

	/**
	 * Get the order by Square order ID.
	 *
	 * @since TBD
	 *
	 * @param string       $square_order_id The Square order ID.
	 * @param string|array $status          The status of the order.
	 *
	 * @return WP_Post|null
	 */
	public function get_by_square_order_id( string $square_order_id, $status = 'any' ): ?WP_Post {
		return tec_tc_orders()->by_args(
			[
				'status'           => $status,
				'gateway_order_id' => $square_order_id,
			]
		)->first();
	}

	/**
	 * Get the Square order.
	 *
	 * @since TBD
	 *
	 * @param string $square_order_id The Square order ID.
	 *
	 * @return array
	 */
	protected function get_square_order( string $square_order_id ): array {
		$cache     = tribe_cache();
		$cache_key = 'tec_tickets_commerce_square_order_' . $square_order_id;
		// Runtime cache it.
		$square_order = $cache[ $cache_key ] ?? false;
		$square_order = is_array( $square_order ) ?
			$square_order :
			Requests::request(
				'GET',
				sprintf( 'orders/%s', $square_order_id ),
				[],
				[]
			);

		if ( ! empty( $square_order['errors'] ) ) {
			do_action( 'tribe_log', 'error', 'Square order not found', [ $square_order_id, $square_order['errors'] ] );
			return [];
		}

		$cache[ $cache_key ] = $square_order;

		return $square_order;
	}

	/**
	 * Get the Square customer.
	 *
	 * @since TBD
	 *
	 * @param string $customer_id The Square customer ID.
	 *
	 * @return array
	 */
	protected function get_square_customer( string $customer_id ): array {
		$cache     = tribe_cache();
		$cache_key = 'tec_tickets_commerce_square_customer_' . $customer_id;

		$remote_customer = $cache[ $cache_key ] ?? false;

		if ( is_array( $remote_customer ) ) {
			return $remote_customer;
		}

		$remote_customer = Requests::request(
			'GET',
			sprintf( 'customers/%s', $customer_id ),
			[],
		);

		if ( ! empty( $remote_customer['errors'] ) ) {
			do_action( 'tribe_log', 'error', 'Square customer not found', [ $customer_id, $remote_customer['errors'] ] );
			return [];
		}

		$cache->set( $cache_key, $remote_customer, HOUR_IN_SECONDS );

		return $remote_customer;
	}
}
