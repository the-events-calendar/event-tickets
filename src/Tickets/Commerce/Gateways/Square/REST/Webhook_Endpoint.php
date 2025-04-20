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
use WP_REST_Request;
use WP_REST_Server;
use WP_REST_Response;
use WP_Error;

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
	 * @return bool Always returns true as we validate using the webhook signature.
	 */
	public function has_permission( WP_REST_Request $request ) {
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
		// Get raw body for signature verification
		$body = file_get_contents( 'php://input' );

		// Get the Square-Signature header
		$signature = $request->get_header( 'Square-Signature' );

		// Verify the signature
		$webhooks = tribe( Webhooks::class );
		if ( ! $webhooks->verify_signature( $signature, $body ) ) {
			do_action(
				'tribe_log',
				'error',
				'Invalid Square webhook signature',
				[
					'source'    => 'tickets-commerce-square',
					'signature' => $signature,
				]
			);

			return new WP_Error(
				'invalid_signature',
				__( 'Invalid webhook signature', 'event-tickets' ),
				[ 'status' => 401 ]
			);
		}

		// Get the event data
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

		// Log the webhook event
		do_action(
			'tribe_log',
			'info',
			'Received Square webhook',
			[
				'source'    => 'tickets-commerce-square',
				'event_type' => $event_data['type'],
				'data'      => $event_data,
			]
		);

		// Process the webhook based on event type
		$this->process_webhook_event( $event_data );

		// Return a successful response
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
			case 'payment.created':
			case 'payment.updated':
				$this->process_payment_event( $event_data );
				break;

			case 'refund.created':
			case 'refund.updated':
				$this->process_refund_event( $event_data );
				break;

			default:
				// Log unsupported event type
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

		$payment_id = $payment_data['id'];
		$status = $payment_data['status'] ?? '';

		// Get the order controller
		$order_controller = tribe( Order::class );

		// Find the order associated with this payment
		$order = $order_controller->get_order_by_payment_id( $payment_id );

		if ( empty( $order ) ) {
			do_action(
				'tribe_log',
				'warning',
				'Square payment webhook - no matching order found',
				[
					'source'     => 'tickets-commerce-square',
					'payment_id' => $payment_id,
				]
			);
			return;
		}

		// Update the order status based on payment status
		switch ( $status ) {
			case 'COMPLETED':
				$order_controller->mark_as_completed( $order );
				break;

			case 'FAILED':
				$order_controller->mark_as_failed( $order );
				break;

			case 'CANCELED':
				$order_controller->mark_as_canceled( $order );
				break;

			default:
				// No status change for other payment statuses
				break;
		}
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

		if ( empty( $refund_data ) || empty( $refund_data['payment_id'] ) ) {
			return;
		}

		$payment_id = $refund_data['payment_id'];
		$refund_id = $refund_data['id'] ?? '';
		$status = $refund_data['status'] ?? '';

		// Skip if refund is not completed
		if ( 'COMPLETED' !== $status ) {
			return;
		}

		// Get the order controller
		$order_controller = tribe( Order::class );

		// Find the order associated with this payment
		$order = $order_controller->get_order_by_payment_id( $payment_id );

		if ( empty( $order ) ) {
			do_action(
				'tribe_log',
				'warning',
				'Square refund webhook - no matching order found',
				[
					'source'     => 'tickets-commerce-square',
					'payment_id' => $payment_id,
					'refund_id'  => $refund_id,
				]
			);
			return;
		}

		// Mark the order as refunded
		$order_controller->mark_as_refunded( $order );
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
