<?php
/**
 * Order class for the Square gateway.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

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
use Tribe__Repository;
use WP_User_Query;
use WP_User;
use TEC\Tickets\Exceptions\NotEnoughStockException;
use stdClass;
use TEC\Common\StellarWP\DB\DB;

/**
 * Class Order.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Order extends Abstract_Order {
	/**
	 * The hook to pull the order.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const HOOK_PULL_ORDER_ACTION = 'tec_tickets_commerce_square_order_pull_order';

	/**
	 * The merchant object.
	 *
	 * @since 5.24.0
	 *
	 * @var Merchant
	 */
	private Merchant $merchant;

	/**
	 * The commerce order object.
	 *
	 * @since 5.24.0
	 *
	 * @var Commerce_Order
	 */
	private Commerce_Order $commerce_order;

	/**
	 * The settings object.
	 *
	 * @since 5.24.0
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Order constructor.
	 *
	 * @since 5.24.0
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
	 * Filter the schema for the repository.
	 *
	 * @since 5.24.0
	 *
	 * @param array             $schema     The schema.
	 * @param Tribe__Repository $repository The repository.
	 *
	 * @return array
	 */
	public function filter_schema( array $schema = [], ?Tribe__Repository $repository = null ) {
		$schema['square_payment_id'] = function ( $payment_ids ) use ( $repository ) {
			$this->filter_by_payment_id( $payment_ids, $repository );
		};

		$schema['square_payment_id_not'] = function ( $payment_ids ) use ( $repository ) {
			$this->filter_by_payment_id_not( $payment_ids, $repository );
		};

		$schema['square_refund_id'] = function ( $refund_ids ) use ( $repository ) {
			$this->filter_by_refund_id( $refund_ids, $repository );
		};

		$schema['square_refund_id_not'] = function ( $refund_ids ) use ( $repository ) {
			$this->filter_by_refund_id_not( $refund_ids, $repository );
		};

		return $schema;
	}

	/**
	 * Create a Square order from a Commerce order.
	 *
	 * @since 5.24.0
	 *
	 * @param WP_Post $order The order object.
	 *
	 * @return string The Square order ID.
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
		 * @since 5.24.0
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
		 * @since 5.24.0
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

		$square_order = $this->add_items_to_square_payload( $square_order, $order );

		/**
		 * Filters the Square order payload.
		 *
		 * @since 5.24.0
		 *
		 * @param array   $payload The payload for the Square order.
		 * @param WP_Post $order   The order object.
		 */
		$square_order = apply_filters( 'tec_tickets_commerce_square_order_payload', $square_order, $order );

		$square_order_id = null;

		if ( $order->gateway_order_id ?? false ) {
			$square_order_id = $order->gateway_order_id;
		}

		if ( $square_order_id && ! $this->needs_update( $square_order, $order->ID ) ) {
			return $square_order_id;
		}

		if ( $square_order_id ) {
			$order_version           = (int) ( $order->gateway_order_version ?? 0 );
			$square_order['version'] = $order_version ? $order_version : 1;
		}

		$body = [
			'idempotency_key' => uniqid( 'tec-square-calculate-', true ),
			'order'           => $square_order,
		];

		$calculated_order = Requests::post(
			'orders/calculate',
			[],
			[
				'body' => $body,
			]
		);

		if ( empty( $calculated_order['order']['total_money']['amount'] ) ) {
			do_action( 'tribe_log', 'error', 'Square order calculate failed', [ $calculated_order['errors'] ?? $calculated_order, $square_order, $square_order_id ] );
			throw new RuntimeException( __( 'Failed to calculate the Square order.', 'event-tickets' ), 1 );
		}

		$calculated_total = (int) $calculated_order['order']['total_money']['amount'];
		$local_total      = (int) ( 100 * (float) $order->total );

		$diff = $calculated_total - $local_total;

		if ( 0 !== $diff ) {
			if ( $diff > 0 ) {
				if ( ! ( isset( $body['order']['discounts'] ) && is_array( $body['order']['discounts'] ) ) ) {
					$body['order']['discounts'] = [];
				}

				$body['order']['discounts'][] = [
					'name'         => __( 'Rounding difference discount', 'event-tickets' ),
					'type'         => 'FIXED_AMOUNT',
					'scope'        => 'ORDER',
					'amount_money' => [
						'amount'   => absint( $diff ),
						'currency' => $order->currency,
					],
				];
			} else {
				if ( ! ( isset( $body['order']['service_charges'] ) && is_array( $body['order']['service_charges'] ) ) ) {
					$body['order']['service_charges'] = [];
				}

				$body['order']['service_charges'][] = [
					'name'              => __( 'Rounding difference service charge', 'event-tickets' ),
					'calculation_phase' => 'SUBTOTAL_PHASE',
					'amount_money'      => [
						'amount'   => absint( $diff ),
						'currency' => $order->currency,
					],
				];
			}
		}

		$body['idempotency_key'] = uniqid( $square_order_id ? 'tec-square-update-' : 'tec-square-create-', true );

		/**
		 * Fires before the Square order is upserted.
		 *
		 * @since 5.24.0
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
			do_action( 'tribe_log', 'error', 'Square order upsert failed', [ $response['errors'] ?? $response, $square_order, $square_order_id ] );
			throw new RuntimeException( __( 'Failed to create or update Square order.', 'event-tickets' ), 1 );
		}

		$args = [
			'gateway_order_id'         => $response['order']['id'],
			'gateway_customer_id'      => $customer_id,
			'gateway_order_version'    => $response['order']['version'],
			'latest_payload_hash_sent' => md5( wp_json_encode( $square_order ) ),
			'gateway_order_object'     => wp_json_encode( $response['order'] ),
		];

		// Update the order with the new Square order ID.
		$order_updated = tec_tc_orders()->by( 'id', $order->ID )->set_args(
			$args
		)->save();

		if ( ! $order_updated || ! isset( $order_updated[ $order->ID ] ) || ! $order_updated[ $order->ID ] ) {
			do_action( 'tribe_log', 'error', 'Order update failed', [ $args, $order ] );
			throw new RuntimeException( __( 'Failed to update the order with the new Square order ID.', 'event-tickets' ), 2 );
		}

		/**
		 * Fires after the Square order is upserted.
		 *
		 * @since 5.24.0
		 *
		 * @param array  $response     The Square order.
		 * @param int    $order_id     The order ID.
		 * @param array  $square_order The Square order.
		 */
		do_action( 'tec_tickets_commerce_square_order_after_upsert', $response['order'], $order->ID, $square_order );

		tribe( Syncs\Regulator::class )->schedule( self::HOOK_PULL_ORDER_ACTION, [ $response['order']['id'] ], 2 * MINUTE_IN_SECONDS );

		return $response['order']['id'];
	}

	/**
	 * Upsert a local order from a Square order.
	 *
	 * @since 5.24.0
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
		$order        = null;

		$ref_id = $square_order['reference_id'] ?? false;
		if ( $ref_id && is_numeric( $ref_id ) ) {
			$order     = tec_tc_get_order( $ref_id );
			$is_update = $order instanceof WP_Post;
		}

		$callable  = empty( $square_order['refunds'] ) ? [ tribe( Commerce_Order::class ), 'get_from_gateway_order_id' ] : [ $this, 'get_by_refund_id' ];
		$args      = empty( $square_order['refunds'] ) ? [ $square_order_id ] : [ $square_order['refunds']['0']['id'] ];
		$order     = $order instanceof WP_Post ? $order : call_user_func( $callable, $args );
		$is_update = $order instanceof WP_Post;

		if ( ! $is_update && empty( $square_order['refunds'] ) ) {
			$order     = $this->get_by_original_gateway_order_id( $square_order_id );
			$is_update = $order instanceof WP_Post;
		}

		if ( ! $is_update && ! $this->settings->is_inventory_sync_enabled() ) {
			// When the sync is not enabled, we listen ONLY for UPDATES to existing orders to our DB.
			return null;
		}

		if ( $is_update && $order->gateway_order_id !== $square_order_id && empty( $square_order['refunds'] ) ) {
			// The order has been changed in a way that now is being matched with a different Square order.
			// For example, that's possible when an order has been refunded. The refund is a new Square order,
			// which we store in `gateway_order_id` property.
			return null;
		}

		if ( ! $is_update ) {
			try {
				$items = $this->get_items_from_square_order( $square_order_id );
			} catch ( NotEnoughStockException $e ) {
				do_action( 'tribe_log', 'warning', 'Not enough stock for incoming order - refunding the order.', [ $square_order_id ] );
				$this->refund_remote_order( $square_order_id );
				return null;
			}

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
				'total_value'           => $total_value->get(),
				'subtotal'              => $subtotal_value->get(),
				'currency'              => $net_amounts['total_money']['currency'],
				'hash'                  => $hash,
				'items'                 => $items,
				'gateway'               => Gateway::get_key(),
				'title'                 => $this->commerce_order->generate_order_title( $items, $hash ),
				'purchaser_user_id'     => $purchaser->ID ?? 0,
				'purchaser_full_name'   => $purchaser->display_name ?? '',
				'purchaser_first_name'  => $purchaser->first_name ?? '',
				'purchaser_last_name'   => $purchaser->last_name ?? '',
				'purchaser_email'       => $purchaser->user_email ?? '',
				'gateway_order_id'      => $square_order_id,
				'gateway_order_version' => $square_order['version'] ?? 1,
				'gateway_order_object'  => wp_json_encode( $square_order ),
			];

			$duplicate_order = $this->get_by_original_gateway_order_id( $square_order_id );

			if ( $duplicate_order instanceof WP_Post ) {
				return $duplicate_order;
			}

			DB::beginTransaction();

			$order = $this->commerce_order->create( tribe( Gateway::class ), $order_args );

			$order_ids = $this->commerce_order->get_order_ids_from_gateway_order_id( $square_order_id );

			if ( count( $order_ids ) > 1 ) {
				do_action( 'tribe_log', 'warning', 'Multiple orders found for the same Square order ID - Deleting: ' . $order->ID, [ $order_ids, $square_order_id ] );
				DB::rollback();
				return null;
			}

			DB::commit();

			update_post_meta( $order->ID, Commerce_Order::META_ORDER_TOTAL_AMOUNT_UNACCOUNTED, $missed_money );
			update_post_meta( $order->ID, Commerce_Order::META_ORDER_TOTAL_TAX, ( new Precision_Value( $net_amounts['tax_money']['amount'] / 100 ) )->get() );
			update_post_meta( $order->ID, Commerce_Order::META_ORDER_TOTAL_TIP, ( new Precision_Value( $net_amounts['tip_money']['amount'] / 100 ) )->get() );
			update_post_meta( $order->ID, Commerce_Order::META_ORDER_CREATED_BY, 'square-pos' );
		}

		if ( ! $order instanceof WP_Post ) {
			return null;
		}

		$event_id = $event_data['event_id'] ?? '';

		if ( $event_id ) {
			Commerce_Meta::add( $order->ID, REST\Webhook_Endpoint::KEY_ORDER_WEBHOOK_IDS, $event_id, [], 'post', false );
		}

		$payments = $square_order['tenders'] ?? [];

		if ( ! empty( $payments ) ) {
			$order_payments = array_flip( $this->get_payment_ids( $order ) );
			foreach ( $payments as $payment ) {
				$payment_id = $payment['id'] ?? false;

				if ( ! $payment_id ) {
					continue;
				}

				if ( isset( $order_payments[ $payment_id ] ) ) {
					continue;
				}

				$this->add_payment_id( $order, $payment_id );
			}
		}

		$refunds = $square_order['refunds'] ?? [];

		if ( ! empty( $refunds ) ) {
			$order_refunds = array_flip( $this->get_refund_ids( $order ) );
			foreach ( $refunds as $refund ) {
				$refund_id = $refund['id'] ?? false;

				if ( ! $refund_id ) {
					continue;
				}

				if ( isset( $order_refunds[ $refund_id ] ) ) {
					continue;
				}

				$this->add_refund_id( $order, $refund_id );
			}
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

		$additional_data = [];

		if ( ! empty( $square_order['refunds'] ) ) {
			if ( 'COMPLETED' !== $status ) {
				return $order;
			}

			$status = 'REFUNDED';

			$payload = [];

			foreach ( $square_order['refunds'] as $refund ) {
				$payload[]['data']['object']['refund'] = $refund;
			}

			$additional_data = [
				'gateway_payload' => $payload,
			];

			if ( $order->gateway_order_id !== $square_order_id ) {
				$additional_data['gateway_order_id']          = $square_order_id;
				$additional_data['original_gateway_order_id'] = $order->gateway_order_id;
			}
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

		if ( time() < $order->on_checkout_hold ) {
			tribe( Webhooks::class )->add_pending_webhook( $order->ID, $status_obj->get_wp_slug(), $order->post_status, [ 'gateway_payload' => $event_data ] );

			as_schedule_single_action(
				$order->on_checkout_hold + MINUTE_IN_SECONDS,
				'tec_tickets_commerce_async_webhook_process',
				[
					'order_id' => $order->ID,
					'try'      => 0,
				],
				'tec-tickets-commerce-webhooks'
			);

			return $order;
		}

		$this->commerce_order->modify_status( $order->ID, $status_obj->get_slug(), array_merge( $event_data ? [ 'gateway_payload' => $event_data ] : [], $additional_data ) );

		return $order;
	}

	/**
	 * Get the cached remote data for an order.
	 *
	 * @since 5.24.0
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
			$order = tec_tc_get_order( $order_id );

			if ( ! $order instanceof WP_Post ) {
				return [];
			}

			$cached_data = $order->gateway_order_object ?? [];

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
	 * @since 5.24.0
	 *
	 * @param WP_Post $order The order object.
	 *
	 * @return string
	 */
	public function get_gateway_dashboard_url_by_order( WP_Post $order ): ?string {
		if ( ! $this->merchant->is_active() ) {
			return '';
		}

		$order_id = $order->gateway_order_id ?? false;

		if ( ! $order_id ) {
			return '';
		}

		$is_test_mode = tribe( Gateway::class )->is_test_mode();

		return sprintf( 'https://app.squareup%s.com/dashboard/orders/overview/%s', $is_test_mode ? 'sandbox' : '', $order_id );
	}

	/**
	 * Check if the order needs to be updated.
	 *
	 * @since 5.24.0
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
		 * @since 5.24.0
		 *
		 * @param bool $needs_update Whether the Square order needs to be updated.
		 */
		return apply_filters(
			'tec_tickets_commerce_square_order_needs_update',
			md5( wp_json_encode( $square_order ) ) !== Commerce_Meta::get( $order_id, Commerce_Order::LATEST_PAYLOAD_HASH_SENT_TO_GATEWAY_META_KEY, [], 'post', true, false ),
			$square_order,
			$order_id
		);
	}

	/**
	 * Add items to the Square payload.
	 *
	 * @since 5.24.0
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
	 * @since 5.24.0
	 *
	 * @param string $square_order_id The Square order ID.
	 *
	 * @return array
	 *
	 * @throws NotEnoughStockException If the stock is not enough.
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

			$quantity = $ticket['quantity'] ?? 1;

			/**
			 * Filters whether to prevent overselling or not.
			 *
			 * @since 5.24.0
			 *
			 * @param bool          $prevent_overselling Whether to prevent overselling or not.
			 * @param Ticket_Object $ticket_obj          The ticket object.
			 * @param int           $quantity            The quantity of the ticket.
			 */
			if ( -1 !== $ticket_obj->available() && $quantity > $ticket_obj->available() && apply_filters( 'tec_tickets_commerce_square_prevent_overselling', true, $ticket_obj, $quantity ) ) {
				throw new NotEnoughStockException( sprintf( 'Not enough stock for ticket %s', $ticket_obj->ID ) );
			}

			$items[] = [
				'event_id'          => $ticket_obj->get_event_id(),
				'price'             => ( new Precision_Value( $ticket['base_price_money']['amount'] / 100 ) )->get(),
				'quantity'          => $quantity,
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

		foreach ( $booking_fees as $fee ) {
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

		$taxes = $square_order['taxes'] ?? [];

		// We don's support taxes yet in TC, but ADDITIVE taxes need to be added as a separate item to the
		// order so that the total is reflecting reality. We add them as prefixed booking fees for now.
		foreach ( $taxes as $tax ) {
			if ( $tax['type'] !== 'ADDITIVE' ) {
				continue;
			}

			$items[] = [
				'id'           => 'square-tax-' . $tax['uid'],
				'type'         => 'fee',
				'price'        => ( new Precision_Value( $tax['applied_money']['amount'] / 100 ) )->get(),
				'sub_total'    => ( new Precision_Value( $tax['applied_money']['amount'] / 100 ) )->get(),
				'fee_id'       => 'square-tax-' . $tax['uid'],
				'display_name' => $tax['name'],
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
	 * @since 5.24.0
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

		$family_name = $remote_customer['customer']['family_name'] ?? '';

		$user_obj->ID           = 0;
		$user_obj->user_email   = $remote_customer['customer']['email_address'];
		$user_obj->display_name = $remote_customer['customer']['given_name'] . ( $family_name ? ' ' . $family_name : '' );
		$user_obj->first_name   = $remote_customer['customer']['given_name'];
		$user_obj->last_name    = $family_name;

		return new WP_User( $user_obj );
	}

	/**
	 * Get the Square order.
	 *
	 * @since 5.24.0
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
	 * Add a payment to the order.
	 *
	 * @since 5.24.0
	 *
	 * @param WP_Post $order      The order object.
	 * @param string  $payment_id The payment ID.
	 *
	 * @return bool
	 */
	public function add_payment_id( WP_Post $order, string $payment_id ): bool {
		$added = Commerce_Meta::add( $order->ID, Payment::KEY_ORDER_PAYMENT_ID, $payment_id, [], 'post', false );

		if ( ! $added ) {
			return false;
		}

		Commerce_Meta::set( $order->ID, Payment::KEY_ORDER_PAYMENT_ID_TIME, tec_get_current_milliseconds(), [ $payment_id ], 'post', false );

		return (bool) $added;
	}

	/**
	 * Get the payment IDs.
	 *
	 * @since 5.24.0
	 *
	 * @param WP_Post $order The order object.
	 *
	 * @return string[]
	 */
	public function get_payment_ids( WP_Post $order ): array {
		return (array) Commerce_Meta::get( $order->ID, Payment::KEY_ORDER_PAYMENT_ID, [], 'post', false, false );
	}

	/**
	 * Get the order by payment ID.
	 *
	 * @since 5.24.0
	 *
	 * @param string $payment_id The payment ID.
	 * @param array  $status     The status of the order.
	 *
	 * @return WP_Post|null
	 */
	public function get_by_payment_id( string $payment_id, array $status = [ 'any' ] ): ?WP_Post {
		return tec_tc_orders()->by_args(
			[
				'square_payment_id' => $payment_id,
				'status'            => $status,
			]
		)->first();
	}

	/**
	 * Filters order by payment ID.
	 *
	 * @since
	 *
	 * @param string|string[]   $payment_ids Which payment IDs we are filtering by.
	 * @param Tribe__Repository $repository  The repository.
	 *
	 * @return null
	 */
	public function filter_by_payment_id( $payment_ids = null, ?Tribe__Repository $repository = null ) {
		if ( empty( $payment_ids ) ) {
			return null;
		}

		$payment_ids = array_filter( (array) $payment_ids );

		if ( empty( $payment_ids ) ) {
			return null;
		}

		$repository->by( 'meta_in', Payment::KEY_ORDER_PAYMENT_ID, $payment_ids );

		return null;
	}

	/**
	 * Filters order by payment ID not.
	 *
	 * @since
	 *
	 * @param string|string[]   $payment_ids Which payment IDs we are filtering by.
	 * @param Tribe__Repository $repository  The repository.
	 *
	 * @return null
	 */
	public function filter_by_payment_id_not( $payment_ids = null, ?Tribe__Repository $repository = null ) {
		if ( empty( $payment_ids ) ) {
			return null;
		}

		$payment_ids = array_filter( (array) $payment_ids );

		if ( empty( $payment_ids ) ) {
			return null;
		}

		$repository->by( 'meta_not_in', Payment::KEY_ORDER_PAYMENT_ID, $payment_ids );

		return null;
	}

	/**
	 * Add a refund ID to the order.
	 *
	 * @since 5.24.0
	 *
	 * @param WP_Post $order      The order object.
	 * @param string  $refund_id  The refund ID.
	 *
	 * @return bool
	 */
	public function add_refund_id( WP_Post $order, string $refund_id ): bool {
		$added = Commerce_Meta::add( $order->ID, Payment::KEY_ORDER_REFUND_ID, $refund_id, [], 'post', false );

		if ( ! $added ) {
			return false;
		}

		Commerce_Meta::set( $order->ID, Payment::KEY_ORDER_REFUND_ID_TIME, tec_get_current_milliseconds(), [ $refund_id ], 'post', false );

		return (bool) $added;
	}

	/**
	 * Get the payment IDs.
	 *
	 * @since 5.24.0
	 *
	 * @param WP_Post $order The order object.
	 *
	 * @return string[]
	 */
	public function get_refund_ids( WP_Post $order ): array {
		return (array) Commerce_Meta::get( $order->ID, Payment::KEY_ORDER_REFUND_ID, [], 'post', false, false );
	}

	/**
	 * Get the order by refund ID.
	 *
	 * @since 5.24.0
	 *
	 * @param string $refund_id The refund ID.
	 * @param array  $status    The status of the order.
	 *
	 * @return WP_Post|null
	 */
	public function get_by_refund_id( string $refund_id, array $status = [ 'any' ] ): ?WP_Post {
		return tec_tc_orders()->by_args(
			[
				'square_refund_id' => $refund_id,
				'status'           => $status,
			]
		)->first();
	}

	/**
	 * Get the order by original gateway order ID.
	 *
	 * @since 5.24.0
	 *
	 * @param string $original_gateway_order_id The original gateway order ID.
	 * @param array  $status                    The status of the order.
	 *
	 * @return WP_Post|null
	 */
	public function get_by_original_gateway_order_id( string $original_gateway_order_id, array $status = [ 'any' ] ): ?WP_Post {
		return tec_tc_orders()->by_args(
			[
				'original_gateway_order_id' => $original_gateway_order_id,
				'status'                    => $status,
			]
		)->first();
	}

	/**
	 * Filters order by refund ID.
	 *
	 * @since
	 *
	 * @param string|string[]   $refund_ids Which refund IDs we are filtering by.
	 * @param Tribe__Repository $repository  The repository.
	 *
	 * @return null
	 */
	public function filter_by_refund_id( $refund_ids = null, ?Tribe__Repository $repository = null ) {
		if ( empty( $refund_ids ) ) {
			return null;
		}

		$refund_ids = array_filter( (array) $refund_ids );

		if ( empty( $refund_ids ) ) {
			return null;
		}

		$repository->by( 'meta_in', Payment::KEY_ORDER_REFUND_ID, $refund_ids );

		return null;
	}

	/**
	 * Filters order by refund ID not.
	 *
	 * @since
	 *
	 * @param string|string[]   $refund_ids Which refund IDs we are filtering by.
	 * @param Tribe__Repository $repository  The repository.
	 *
	 * @return null
	 */
	public function filter_by_refund_id_not( $refund_ids = null, ?Tribe__Repository $repository = null ) {
		if ( empty( $refund_ids ) ) {
			return null;
		}

		$refund_ids = array_filter( (array) $refund_ids );

		if ( empty( $refund_ids ) ) {
			return null;
		}

		$repository->by( 'meta_not_in', Payment::KEY_ORDER_REFUND_ID, $refund_ids );

		return null;
	}

	/**
	 * Get the Square customer.
	 *
	 * @since 5.24.0
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

	/**
	 * Refund an order.
	 *
	 * To refund a Square order, you have to grab the Square order. Then in the property `tenders` you have to refund every tender.
	 *
	 * @since 5.24.0
	 *
	 * @param WP_Post $order The order post object.
	 *
	 * @return void
	 *
	 * @throws RuntimeException If the order has no Square order ID or the Square order is not found.
	 */
	public function refund_order( WP_Post $order ): void {
		if ( ! $order->gateway_order_id ) {
			throw new RuntimeException( __( 'Order has no Square order ID.', 'event-tickets' ) );
		}

		$this->refund_remote_order( $order->gateway_order_id, $order );
	}

	/**
	 * Refund a remote order.
	 *
	 * @since 5.24.0
	 *
	 * @param string       $square_order_id The Square order ID.
	 * @param WP_Post|null $order           The order post object.
	 *
	 * @return void
	 *
	 * @throws RuntimeException If the Square order is not found.
	 */
	public function refund_remote_order( string $square_order_id, ?WP_Post $order = null ): void {
		$square_order = $this->get_square_order( $square_order_id );

		if ( empty( $square_order['order'] ) ) {
			throw new RuntimeException( __( 'Square order not found.', 'event-tickets' ) );
		}

		$tenders = $square_order['order']['tenders'] ?? [];

		foreach ( $tenders as $tender ) {
			$id     = $tender['id'] ?? null;
			$amount = $tender['amount_money'] ?? null;

			if ( ! ( $id && $amount ) ) {
				continue;
			}

			$body = [
				'idempotency_key' => md5( 'refund-' . $id ),
				'payment_id'      => $id,
				'amount_money'    => $amount,
			];

			$response = Requests::post(
				'refunds',
				[],
				[ 'body' => $body ]
			);

			if ( empty( $response['refund'] ) ) {
				do_action( 'tribe_log', 'error', 'Square refund failed', [ $body, $response ] );
				continue;
			}

			if ( ! $order instanceof WP_Post ) {
				continue;
			}

			$this->add_refund_id( $order, explode( '_', $response['refund']['id'] )[1] );

			tribe( Syncs\Regulator::class )->schedule( self::HOOK_PULL_ORDER_ACTION, [ $response['refund']['order_id'] ], MINUTE_IN_SECONDS / 3 );
		}
	}
}
