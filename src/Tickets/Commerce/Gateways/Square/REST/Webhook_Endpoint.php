<?php
/**
 * Square Webhook Endpoint
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\REST
 */

namespace TEC\Tickets\Commerce\Gateways\Square\REST;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\Square\Gateway;
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

/**
 * Class Webhook_Endpoint.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\REST
 */
class Webhook_Endpoint extends Abstract_REST_Endpoint {

	/**
	 * The REST namespace for this endpoint.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $namespace = 'tribe/tickets/v1';

	/**
	 * The REST endpoint path for this endpoint.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $path = '/commerce/square/webhooks';

	/**
	 * The location ID.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private string $location_id;

	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param Gateway $gateway The gateway instance.
	 */
	public function __construct( Gateway $gateway ) {
		$this->location_id = $gateway->get_location_id();
	}

	/**
	 * Get the namespace for this endpoint.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_namespace(): string {
		return $this->namespace;
	}

	/**
	 * Get the path for this endpoint.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return bool|WP_Error Always returns true as we validate using the webhook signature.
	 */
	public function has_permission( WP_REST_Request $request ) {
		$webhook = tribe( Webhooks::class );

		// Get the webhook secret key from the URL.
		$secret_key = $request->get_param( Webhooks::PARAM_WEBHOOK_KEY );

		// Get the whodat signature from the request header.
		$whodat_hash = $request->get_header( 'X-WhoDat-Hash' );
		$payload     = $request->get_body();

		if ( ! $webhook->verify_signature( $secret_key ) || ! $webhook->verify_whodat_signature( $payload, $whodat_hash ) ) {
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
	 * @since TBD
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
	 * @since TBD
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

		$webhook = tribe( Webhooks::class );

		// We attempt to re-register the webhook if it has not been fetched in the last hour.
		if ( $webhook->should_refresh_webhook() ) {
			$webhook->register_webhook_endpoint();
		}

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
	 * @since TBD
	 *
	 * @param array $event_data The webhook event data.
	 */
	protected function process_webhook_event( array $event_data ) {
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
		 * @since TBD
		 *
		 * @param array  $event_data The webhook event data.
		 * @param string $event_type The event type.
		 */
		do_action( 'tec_tickets_commerce_square_webhook_event', $event_data, $event_type );
	}

	/**
	 * Process an order event.
	 *
	 * @since TBD
	 *
	 * @param array $event_data The webhook event data.
	 */
	protected function process_order_event( array $event_data ) {
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

		if ( empty( $order ) && 'order_updated' === $type ) {
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

		$square_order_controller->upsert_local_from_square_order( $order_id, $event_data );
	}

	/**
	 * Process a refund event.
	 *
	 * @since TBD
	 *
	 * @param array $event_data The webhook event data.
	 */
	protected function process_refund_event( array $event_data ) {
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

		// Update the order status.
		tribe( Commerce_Order::class )->modify_status( $order->ID, Refunded::SLUG, [ 'gateway_payload' => $event_data ] );
	}

	/**
	 * Delete the customer ID from the user meta.
	 *
	 * @since TBD
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
	}

	/**
	 * Process a ticket inventory updated event.
	 *
	 * @since TBD
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
				 * @since TBD
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
	}

	/**
	 * Returns an array in the format used by Swagger 2.0.
	 *
	 * @since TBD
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
