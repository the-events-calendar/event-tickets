<?php
/**
 * Square Webhook Endpoint
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\REST
 */

namespace TEC\Tickets\Commerce\Gateways\Square\REST;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\Square\Gateway;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;
use TEC\Tickets\Commerce\Gateways\Square\Webhooks;
use TEC\Tickets\Commerce\Gateways\Square\Order;
use WP_REST_Request;
use WP_REST_Server;
use WP_REST_Response;
use WP_Error;
use WP_User_Query;
use TEC\Tickets\Commerce\Ticket as Commerce_Ticket;
use TEC\Tickets\Commerce\Settings as Commerce_Settings;
use TEC\Tickets\Commerce\Meta as Commerce_Meta;
use TEC\Tickets\Commerce\Order as Commerce_Order;
use TEC\Tickets\Commerce\Status\Refunded;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Item;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Controller as Sync_Controller;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\NotSyncableItemException;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Regulator;
use WP_Post;
use TEC\Tickets\Exceptions\DuplicateEntryException;
use TEC\Tickets\Commerce\Models\Webhook as Webhook_Model;

/**
 * Class Webhook_Endpoint.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\REST
 */
class Webhook_Endpoint extends Abstract_REST_Endpoint {
	/**
	 * The key for the order webhook IDs.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const KEY_ORDER_WEBHOOK_IDS = 'tec_tc_order_webhook_ids';

	/**
	 * The REST namespace for this endpoint.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	protected string $namespace = 'tribe/tickets/v1';

	/**
	 * The REST endpoint path for this endpoint.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	protected string $path = '/commerce/square/webhooks';

	/**
	 * The location ID.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	private string $location_id;

	/**
	 * The webhooks instance.
	 *
	 * @since 5.24.0
	 *
	 * @var Webhooks
	 */
	private Webhooks $webhooks;

	/**
	 * Constructor.
	 *
	 * @since 5.24.0
	 *
	 * @param Gateway  $gateway  The gateway instance.
	 * @param Webhooks $webhooks The webhooks instance.
	 */
	public function __construct( Gateway $gateway, Webhooks $webhooks ) {
		$this->location_id = $gateway->get_location_id();
		$this->webhooks    = $webhooks;
	}

	/**
	 * Get the namespace for this endpoint.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_namespace(): string {
		return $this->namespace;
	}

	/**
	 * Get the path for this endpoint.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_path(): string {
		return $this->path;
	}

	/**
	 * Checks if the current user has permissions to the endpoint.
	 * For webhooks, we skip permission checks because this is called by Square,
	 * we validate the request using the webhook signature instead.
	 *
	 * @since 5.24.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return bool|WP_Error Always returns true as we validate using the webhook signature.
	 */
	public function has_permission( WP_REST_Request $request ) {
		// Get the webhook secret key from the URL.
		$secret_key = $request->get_param( Webhooks::PARAM_WEBHOOK_KEY );

		// Get the whodat signature from the request header.
		$whodat_hash = $request->get_header( 'X-WhoDat-Hash' );
		$payload     = $request->get_body();

		if ( ! ( $this->webhooks->verify_signature( $secret_key ) && $this->webhooks->verify_whodat_signature( $payload, $whodat_hash, $secret_key ) ) ) {
			do_action(
				'tribe_log',
				'error',
				'Invalid Secret Key or Whodat Hash',
				[
					'source'      => 'tickets-commerce-square',
					'secret_key'  => $secret_key,
					'whodat_hash' => $whodat_hash,
					'payload'     => $payload,
				]
			);

			return new WP_Error(
				'invalid_signature',
				__( 'Invalid webhook signature', 'event-tickets' ),
				[ 'status' => 401 ]
			);
		}

		return true;
	}

	/**
	 * Register the actual endpoint on WP Rest API.
	 *
	 * @since 5.24.0
	 */
	public function register(): void {
		$namespace = $this->get_namespace();
		$path      = $this->get_path();

		register_rest_route(
			$namespace,
			$path,
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_webhook' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);
	}

	/**
	 * Handles incoming webhook events from Square.
	 *
	 * @since 5.24.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function handle_webhook( WP_REST_Request $request ) {
		// Get the event data.
		$event_data = $request->get_json_params();

		if ( empty( $event_data ) || empty( $event_data['type'] ) ) {
			do_action(
				'tribe_log',
				'error',
				'Invalid Square webhook payload',
				[
					'source' => 'tickets-commerce-square',
					'data'   => $event_data,
				]
			);

			return new WP_Error(
				'invalid_payload',
				__( 'Invalid webhook payload', 'event-tickets' ),
				[ 'status' => 400 ]
			);
		}

		// Log the webhook event.
		do_action(
			'tribe_log',
			'info',
			'Received Square webhook',
			[
				'source'     => 'tickets-commerce-square',
				'event_type' => $event_data['type'],
				'data'       => $event_data,
			]
		);

		// Process the webhook based on event type.
		$this->process_webhook_event( $event_data );

		// Return a successful response.
		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Webhook received successfully', 'event-tickets' ),
			],
			200
		);
	}

	/**
	 * Process a webhook event based on its type.
	 *
	 * @since 5.24.0
	 *
	 * @param array $event_data The webhook event data.
	 */
	public function process_webhook_event( array $event_data ): void {
		$event_type = $event_data['type'] ?? '';

		if ( ! in_array( $event_type, Events::get_types(), true ) ) {
			do_action(
				'tribe_log',
				'warning',
				'Unsupported Square webhook event type',
				[
					'source'     => 'tickets-commerce-square',
					'event_type' => $event_type,
					'data'       => $event_data,
				]
			);
			return;
		}

		$event_mode = $event_data['env'] ?? 'sandbox';
		$is_sandbox = 'sandbox' === $event_mode;

		if ( $is_sandbox !== tec_tickets_commerce_is_sandbox_mode() ) {
			return;
		}

		try {
			Webhook_Model::create(
				[
					'event_id'   => $event_data['event_id'],
					'event_type' => $event_type,
					'event_data' => wp_json_encode( $event_data ),
				]
			);
		} catch ( DuplicateEntryException $e ) {
			return;
		}

		switch ( $event_type ) {
			case Events::ORDER_CREATED:
			case Events::ORDER_UPDATED:
				$this->process_order_event( $event_data );
				break;

			case Events::CUSTOMER_DELETED:
				$this->process_customer_delete_event( $event_data );
				break;

			case Events::INVENTORY_COUNT_UPDATED:
				$this->process_ticket_inventory_updated_event( $event_data );
				break;

			case Events::REFUND_CREATED:
			case Events::REFUND_UPDATED:
				$this->process_refund_event( $event_data );
				break;

			case Events::PAYMENT_CREATED:
			case Events::PAYMENT_UPDATED:
				$this->process_payment_event( $event_data );
				break;

			case Events::OAUTH_AUTHORIZATION_REVOKED:
				$this->process_oauth_authorization_revoked_event( $event_data );
				break;

			default:
				// Log unsupported event type.
				do_action(
					'tribe_log',
					'warning',
					'Unsupported Square webhook event type',
					[
						'source'     => 'tickets-commerce-square',
						'event_type' => $event_type,
						'data'       => $event_data,
					]
				);
		}

		/**
		 * Allows other code to process the Square webhook event.
		 *
		 * @since 5.24.0
		 *
		 * @param array  $event_data The webhook event data.
		 * @param string $event_type The event type.
		 */
		do_action( 'tec_tickets_commerce_square_webhook_event', $event_data, $event_type );
	}

	/**
	 * Process an order event.
	 *
	 * @since 5.24.0
	 *
	 * @param array $event_data The webhook event data.
	 */
	protected function process_order_event( array $event_data ): void {
		$type = $event_data['data']['type'] ?? null;

		if ( ! $type ) {
			return;
		}

		$order_data = $event_data['data']['object'][ $type ] ?? null;

		if ( ! is_array( $order_data ) ) {
			return;
		}

		$order_id    = $order_data['order_id'] ?? false;
		$location_id = $order_data['location_id'] ?? false;
		$status      = $order_data['state'] ?? false;

		if ( ! ( $order_id && $location_id && $status ) ) {
			return;
		}

		if ( $location_id !== $this->location_id ) {
			// This is about a location that wp has nothing to do with, so we skip.
			return;
		}

		// Get the order controller.
		$square_order_controller = tribe( Order::class );

		// Find the order associated with this payment.
		$order = tribe( Commerce_Order::class )->get_from_gateway_order_id( $order_id );

		if ( empty( $order->ID ) && 'order_updated' === $type ) {
			do_action(
				'tribe_log',
				'warning',
				'Square order webhook - no matching order found',
				[
					'source'     => 'tickets-commerce-square',
					'order_id'   => $order_id,
					'event_data' => $event_data,
				]
			);
			return;
		}

		$event_id = $event_data['event_id'] ?? '';

		if ( ! $event_id ) {
			return;
		}

		$event_ids = ! empty( $order->ID ) ? (array) Commerce_Meta::get( $order->ID, self::KEY_ORDER_WEBHOOK_IDS, [], 'post', false, false ) : [];

		if ( in_array( $event_id, $event_ids, true ) ) {
			return;
		}

		$order = $square_order_controller->upsert_local_from_square_order( $order_id, $event_data, $event_id );

		if ( ! $order instanceof WP_Post ) {
			return;
		}

		Webhook_Model::update(
			[
				'event_id'     => $event_id,
				'order_id'     => $order->ID,
				'processed_at' => current_time( 'mysql' ),
			]
		);

		tribe( Regulator::class )->unschedule( Order::HOOK_PULL_ORDER_ACTION, [ $order->ID ] );
	}

	/**
	 * Process a refund event.
	 *
	 * @since 5.24.0
	 *
	 * @param array $event_data The webhook event data.
	 */
	protected function process_refund_event( array $event_data ): void {
		$refund_data = $event_data['data']['object']['refund'] ?? [];

		if ( empty( $refund_data ) || empty( $refund_data['order_id'] ) ) {
			return;
		}

		$payment_id = $refund_data['payment_id'] ?? '';
		$order_id   = $refund_data['order_id'];
		$refund_id  = $refund_data['id'] ?? '';
		$status     = $refund_data['status'] ?? '';

		if ( empty( $payment_id ) ) {
			return;
		}

		// Skip if refund is not completed.
		if ( 'COMPLETED' !== $status ) {
			return;
		}

		$order = tribe( Order::class )->get_by_payment_id( $payment_id );

		if ( empty( $order ) ) {
			do_action(
				'tribe_log',
				'warning',
				'Square refund webhook - no matching order found',
				[
					'source'     => 'tickets-commerce-square',
					'order_id'   => $order_id,
					'payment_id' => $payment_id,
					'refund_id'  => $refund_id,
					'event_data' => $event_data,
				]
			);
			return;
		}

		$event_ids = (array) Commerce_Meta::get( $order->ID, self::KEY_ORDER_WEBHOOK_IDS, [], 'post', false, false );

		$event_id = $event_data['event_id'] ?? '';

		if ( ! $event_id ) {
			return;
		}

		if ( in_array( $event_id, $event_ids, true ) ) {
			return;
		}

		if ( ! $order->original_gateway_order_id ?? 0 ) {
			/**
			 * Store the original and the after the refund gateway order id.
			 *
			 * The gateway_order_id should point to the refunded one which is the order's latest state.
			 */
			tec_tc_orders()
				->by_args(
					[
						'id'     => $order->ID,
						'status' => [ 'any' ],
					]
				)
				->set_args(
					[
						'gateway_order_id'          => $order_id,
						'original_gateway_order_id' => $order->gateway_order_id,
					]
				)
				->save();
		}

		if ( time() < $order->on_checkout_hold ) {
			$this->webhooks->add_pending_webhook( $order->ID, tribe( Refunded::class )->get_wp_slug(), $order->post_status, [ 'gateway_payload' => $event_data ] );

			as_schedule_single_action(
				$order->on_checkout_hold + MINUTE_IN_SECONDS,
				'tec_tickets_commerce_async_webhook_process',
				[
					'order_id' => $order->ID,
					'try'      => 0,
				],
				'tec-tickets-commerce-webhooks'
			);
			return;
		}

			Webhook_Model::update(
				[
					'event_id'     => $event_id,
					'order_id'     => $order->ID,
					'processed_at' => current_time( 'mysql' ),
				]
			);

		// Update the order status.
		tribe( Commerce_Order::class )->modify_status( $order->ID, Refunded::SLUG, [ 'gateway_payload' => $event_data ] );

		tribe( Regulator::class )->unschedule( Order::HOOK_PULL_ORDER_ACTION, [ $order->gateway_order_id ] );
		tribe( Regulator::class )->unschedule( Order::HOOK_PULL_ORDER_ACTION, [ $order->original_gateway_order_id ] );
	}

	/**
	 * Process a payment event.
	 *
	 * @since 5.24.0
	 *
	 * @param array $event_data The webhook event data.
	 */
	protected function process_payment_event( array $event_data ): void {
		$order_id = $event_data['data']['object']['payment']['order_id'] ?? false;

		if ( ! $order_id ) {
			do_action( 'tribe_log', 'warning', 'Square payment webhook - no order id found', [ 'event_data' => $event_data ] );
			return;
		}

		tribe( Regulator::class )->schedule( Order::HOOK_PULL_ORDER_ACTION, [ $order_id ], MINUTE_IN_SECONDS );

		Webhook_Model::update(
			[
				'event_id'     => $event_data['event_id'],
				'processed_at' => current_time( 'mysql' ),
			]
		);
	}

	/**
	 * Delete the customer ID from the user meta.
	 *
	 * @since 5.24.0
	 *
	 * @param array $event_data The webhook event data.
	 */
	protected function process_customer_delete_event( array $event_data ): void {
		$customer_id = $event_data['data']['id'] ?? '';

		if ( ! $customer_id ) {
			return;
		}

		$user_query = new WP_User_Query(
			[
				'meta_key'   => Commerce_Settings::get_key( '_tec_tickets_commerce_gateways_square_customer_id_%s' ),
				'meta_value' => $customer_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'     => 'ID',
			]
		);

		if ( empty( $user_query->get_results() ) ) {
			return;
		}

		foreach ( $user_query->get_results() as $user_id ) {
			Commerce_Meta::delete( $user_id, '_tec_tickets_commerce_gateways_square_customer_id_%s', [], 'user' );
		}

		Webhook_Model::update(
			[
				'event_id'     => $event_data['event_id'],
				'processed_at' => current_time( 'mysql' ),
			]
		);
	}

	/**
	 * Process a ticket inventory updated event.
	 *
	 * @since 5.24.0
	 *
	 * @param array $event_data The webhook event data.
	 */
	protected function process_ticket_inventory_updated_event( array $event_data ): void {
		$inventory_data = $event_data['data']['object']['inventory_counts'] ?? [];

		if ( ! $inventory_data || ! is_array( $inventory_data ) ) {
			return;
		}

		foreach ( $inventory_data as $inventory_item ) {
			$location_id = $inventory_item['location_id'] ?? '';

			if ( ! $location_id || $location_id !== $this->location_id ) {
				// This is about a location that wp has nothing to do with, so we skip.
				continue;
			}

			if ( 'ITEM_VARIATION' !== $inventory_item['catalog_object_type'] ) {
				// This is not about a ticket, so we skip.
				continue;
			}

			$object_id = $inventory_item['catalog_object_id'] ?? false;

			if ( ! $object_id ) {
				continue;
			}

			$ticket_id = Commerce_Meta::get_object_id( Item::SQUARE_ID_META, $object_id );

			if ( ! $ticket_id ) {
				continue;
			}

			$ticket = tribe( Commerce_Ticket::class )->load_ticket_object( $ticket_id );

			if ( ! $ticket instanceof Ticket_Object ) {
				continue;
			}

			$quantity = (int) ( $inventory_item['quantity'] ?? 0 );
			$state    = $inventory_item['state'] ?? '';

			try {
				if ( Sync_Controller::is_ticket_in_sync_with_square_data( $ticket, $quantity, $state ) ) {
					continue;
				}

				/**
				 * We are out of sync!
				 *
				 * The rules of syncing is that single source of truth is the WP site, NOT Square.
				 *
				 * The quantity of tickets is expected to change ONLY via the WP site.
				 *
				 * Either by direct edit on the ticket object or by creating an order including the ticket.
				 *
				 * Orders can be created by Square integration.
				 *
				 * Having that in mind, we will simply schedule a background check in a few minutes to see if the quantity
				 * came back into sync. If not, we will update Square with the correct quantity we have locally.
				 */

				/**
				 * Fire an action so we can schedule a background check in a few minutes to see if the quantity
				 * came back into sync.
				 *
				 * @since 5.24.0
				 *
				 * @param int    $ticket_id The ticket ID.
				 * @param int    $quantity  The quantity of tickets.
				 * @param string $state     The state of the inventory.
				 */
				do_action( 'tec_tickets_commerce_square_ticket_out_of_sync', $ticket_id, $quantity, $state );
			} catch ( NotSyncableItemException $e ) {
				// If the ticket is not syncable, we don't need to sync it.
				continue;
			}
		}

		Webhook_Model::update(
			[
				'event_id'     => $event_data['event_id'],
				'processed_at' => current_time( 'mysql' ),
			]
		);
	}

	/**
	 * Process an OAuth authorization revoked event.
	 *
	 * @since 5.24.0
	 *
	 * @param array $event_data The webhook event data.
	 */
	public function process_oauth_authorization_revoked_event( array $event_data ): void {
		$type = $event_data['data']['type'] ?? '';

		if ( 'revocation' !== $type ) {
			return;
		}

		$merchant = tribe( Merchant::class );

		if ( $merchant->is_active() ) {
			// In this case only, the disconnection was initiated remotely.
			Commerce_Settings::set( 'tickets_commerce_gateways_square_remotely_disconnected_%s', time() );
		}

		$merchant->delete_signup_data();

		Webhook_Model::update(
			[
				'event_id'     => $event_data['event_id'],
				'processed_at' => current_time( 'mysql' ),
			]
		);
	}

	/**
	 * Returns an array in the format used by Swagger 2.0.
	 *
	 * @since 5.24.0
	 *
	 * @link http://swagger.io/
	 *
	 * @return array An array description of a Swagger supported component.
	 */
	public function get_documentation(): array {
		return [
			'post' => [
				'summary'     => esc_html__( 'Handle Square webhook events', 'event-tickets' ),
				'description' => esc_html__( 'Receives and processes webhook events from Square', 'event-tickets' ),
				'consumes'    => [
					'application/json',
				],
				'parameters'  => [
					[
						'name'        => Webhooks::PARAM_WEBHOOK_KEY,
						'in'          => 'query',
						'description' => esc_html__( 'The webhook secret key', 'event-tickets' ),
						'required'    => true,
						'schema'      => [
							'type' => 'string',
						],
					],
					[
						'name'        => 'body',
						'in'          => 'body',
						'description' => esc_html__( 'The webhook payload from Square', 'event-tickets' ),
						'required'    => true,
						'schema'      => [
							'type' => 'object',
						],
					],
				],
				'responses'   => [
					'200' => [
						'description' => esc_html__( 'Webhook received and processed successfully', 'event-tickets' ),
					],
					'400' => [
						'description' => esc_html__( 'Invalid webhook payload', 'event-tickets' ),
					],
					'401' => [
						'description' => esc_html__( 'Invalid webhook signature', 'event-tickets' ),
					],
				],
			],
		];
	}
}
