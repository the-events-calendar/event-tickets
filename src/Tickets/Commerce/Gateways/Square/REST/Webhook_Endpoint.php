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
use TEC\Tickets\Commerce\Gateways\Square\Webhooks;
use TEC\Tickets\Commerce\Gateways\Square\Order;
use TEC\Tickets\Commerce\Gateways\Square\Status;
use WP_REST_Request;
use WP_REST_Server;
use WP_REST_Response;
use WP_Error;

use TEC\Tickets\Commerce\Order as Commerce_Order;
use TEC\Tickets\Commerce\Status\Refunded;

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

		// Get the webhook secret key from the URL.
		$secret_key = $request->get_param( Webhooks::PARAM_WEBHOOK_KEY );



		if ( ! tribe( Webhooks::class )->verify_signature( $secret_key ) ) {
			do_action(
				'tribe_log',
				'error',
				'Invalid Secret Key',
				[
					'source'    => 'tickets-commerce-square',
					'signature' => $secret_key,
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

		switch ( $event_type ) {
			case 'order.created':
			case 'order.updated':
				$this->process_order_event( $event_data );
				break;

			case 'payment.created':
			case 'payment.updated':
				$this->process_payment_event( $event_data );
				break;

			case 'refund.created':
			case 'refund.updated':
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
					]
				);
				break;
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
		$order_data = $event_data['data']['object']['order'] ?? [];

		if ( empty( $order_data ) || empty( $order_data['id'] ) ) {
			return;
		}

		$order_id = $order_data['id'];
		$status   = $order_data['status'] ?? '';

		// Get the order controller.
		$square_order_controller = tribe( Order::class );

		// Find the order associated with this payment.
		$order = $square_order_controller->get_by_square_order_id( $order_id );

		if ( empty( $order ) ) {
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

		$status_obj = tribe( Status::class )->convert_to_commerce_status( $status );

		if ( ! $status_obj ) {
			do_action(
				'tribe_log',
				'warning',
				'Square order webhook - no matching status found',
				[
					'source'     => 'tickets-commerce-square',
					'order_id'   => $order_id,
					'event_data' => $event_data,
				]
			);
			return;
		}

		// Update the order status.
		tribe( Commerce_Order::class )->modify_status( $order, $status_obj->get_slug(), [ 'gateway_payload' => $event_data ] );
	}

	/**
	 * Process a payment event.
	 *
	 * @since TBD
	 *
	 * @param array $event_data The webhook event data.
	 */
	protected function process_payment_event( array $event_data ) {
		$payment_data = $event_data['data']['object']['payment'] ?? [];

		if ( empty( $payment_data ) || empty( $payment_data['id'] ) ) {
			return;
		}

		$order_id = $payment_data['id'];
		$status   = $payment_data['status'] ?? '';

		// Get the order controller.
		$square_order_controller = tribe( Order::class );

		// Find the order associated with this payment.
		$order = $square_order_controller->get_by_square_order_id( $order_id );

		if ( empty( $order ) ) {
			do_action(
				'tribe_log',
				'warning',
				'Square payment webhook - no matching order found',
				[
					'source'     => 'tickets-commerce-square',
					'order_id'   => $order_id,
					'event_data' => $event_data,
				]
			);
			return;
		}

		$status_obj = tribe( Status::class )->convert_to_commerce_status( $status );

		if ( ! $status_obj ) {
			do_action(
				'tribe_log',
				'warning',
				'Square order webhook - no matching status found',
				[
					'source'     => 'tickets-commerce-square',
					'order_id'   => $order_id,
					'event_data' => $event_data,
				]
			);
			return;
		}

		// Update the order status.
		tribe( Commerce_Order::class )->modify_status( $order, $status_obj->get_slug(), [ 'gateway_payload' => $event_data ] );
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

		$order_id  = $refund_data['order_id'];
		$refund_id = $refund_data['id'] ?? '';
		$status    = $refund_data['status'] ?? '';

		// Skip if refund is not completed.
		if ( 'COMPLETED' !== $status ) {
			return;
		}

		// Get the order controller.
		$order_controller = tribe( Order::class );

		// Find the order associated with this payment.
		$order = $order_controller->get_by_square_order_id( $order_id );

		if ( empty( $order ) ) {
			do_action(
				'tribe_log',
				'warning',
				'Square refund webhook - no matching order found',
				[
					'source'     => 'tickets-commerce-square',
					'order_id'   => $order_id,
					'refund_id'  => $refund_id,
					'event_data' => $event_data,
				]
			);
			return;
		}
		// Update the order status.
		tribe( Commerce_Order::class )->modify_status( $order, Refunded::SLUG, [ 'gateway_payload' => $event_data ] );
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
