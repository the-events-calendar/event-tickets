<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners;

use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\Merchant_Details;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\Webhooks;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Webhook_Register;
use WP_Post;

/**
 * Class PaymentEventListener
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners
 *
 */
abstract class Payment_Event_Listener implements Event_Listener {
	/**
	 * @since 5.1.6
	 *
	 * @var Merchant_Details
	 */
	private $merchant_details;

	/**
	 * @since 5.1.6
	 *
	 * @var Webhooks
	 */
	private $webhook_repository;

	/**
	 * @since 5.1.6
	 *
	 * @var Webhook_Register
	 */
	private $webhook_register;

	/**
	 * The new status to set with successful event.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	protected $new_status = '';

	/**
	 * The event type.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	protected $event_type = '';

	/**
	 * PaymentEventListener constructor.
	 *
	 * @since 5.1.6
	 *
	 * @param Merchant_Details $merchant_details
	 * @param Webhook_Register $register
	 * @param Webhooks         $webhook_repository
	 */
	public function __construct( Merchant_Details $merchant_details, Webhook_Register $register, Webhooks $webhook_repository ) {
		$this->merchant_details   = $merchant_details;
		$this->webhook_register   = $register;
		$this->webhook_repository = $webhook_repository;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.1.6
	 *
	 * @param object $event The PayPal payment event object.
	 *
	 * @return bool Whether the event was processed successfully.
	 */
	public function process_event( $event ) {
		// No status set.
		if ( ! $this->new_status ) {
			return false;
		}

		// Invalid event.
		if ( empty( $event->event_type ) || empty( $event->resource ) ) {
			return false;
		}

		// Check if the event type matches.
		if ( $this->event_type !== $event->event_type ) {
			tribe( 'logger' )->log_debug(
				sprintf(
				// Translators: %s: The PayPal payment event.
					__( 'Mismatched event type for webhook event: %s', 'event-tickets' ),
					json_encode( $event )
				),
				'tickets-commerce-paypal-commerce'
			);

			return false;
		}

		$payment_id = $this->get_parent_payment_id_from_payment( $event->resource );

		if ( ! $payment_id ) {
			tribe( 'logger' )->log_debug(
				sprintf(
				// Translators: %s: The PayPal payment event.
					__( 'Missing PayPal payment for webhook event: %s', 'event-tickets' ),
					json_encode( $event )
				),
				'tickets-commerce-paypal-commerce'
			);

			return false;
		}

		$payment = $this->get_order_by_payment_id( $payment_id );

		// If there's no matching payment then it's not tracked by Tickets Commerce.
		if ( ! $payment ) {
			tribe( 'logger' )->log_debug(
				sprintf(
				// Translators: %s: The PayPal payment ID.
					__( 'Missing order for PayPal payment from webhook: %s', 'event-tickets' ),
					$payment_id
				),
				'tickets-commerce-paypal-commerce'
			);

			return false;
		}

		// Don't do anything if the status is already set.
		if ( $this->new_status === $payment->post_status ) {
			return false;
		}

		// Update the status.
		$post_data = [
			'ID'          => $payment->ID,
			'post_status' => $this->new_status,
		];

		wp_update_post( $post_data );

		tribe( 'logger' )->log_debug(
			sprintf(
			// Translators: %1$s: The status name; %2$s: The payment information.
				__( 'Change %1$s in PayPal from webhook: %2$s', 'event-tickets' ),
				$this->new_status,
				sprintf( '[Order ID: %s; PayPal Payment ID: %s]', $payment->ID, $payment_id )
			),
			'tickets-commerce-paypal-commerce'
		);

		/**
		 * Allow hooking into the listener status.
		 *
		 * @since 5.1.6
		 *
		 * @param WP_Post $payment    The payment object.
		 * @param string  $payment_id The PayPal payment ID.
		 * @param object  $event      The PayPal webhook event.
		 * @param string  $new_status The new order status.
		 */
		do_action( 'tribe_tickets_commerce_gateways_paypal_commerce_webhooks_listeners', $payment, $payment_id, $event, $this->new_status );

		/**
		 * Allow hooking into the listener status for the new status.
		 *
		 * @since 5.1.6
		 *
		 * @param WP_Post $payment    The payment object.
		 * @param string  $payment_id The PayPal payment ID.
		 * @param object  $event      The PayPal webhook event.
		 */
		do_action( "tribe_tickets_commerce_gateways_paypal_commerce_webhooks_listeners_{$this->new_status}", $payment, $payment_id, $event );

		return true;
	}

	/**
	 * Get the order using the PayPal payment ID.
	 *
	 * @since 5.1.6
	 *
	 * @param string $id The PayPal payment ID.
	 *
	 * @return \WP_Post|null The matching order or null if not found.
	 */
	public function get_order_by_payment_id( $id ) {
		$orm = tribe_tickets_orders( 'tribe-commerce' );

		return $orm->by( 'id', $id )->first();
	}

	/**
	 * This uses the links property of the payment to retrieve the Parent Payment ID from PayPal
	 *
	 * @since 5.1.6
	 *
	 * @param object $payment The payment event object.
	 *
	 * @return string|false The parent payment ID or false if not found.
	 */
	private function get_parent_payment_id_from_payment( $payment ) {
		$link = current( array_filter( $payment->links, static function ( $link ) {
			return $link->rel === 'up';
		} ) );

		if ( ! $link ) {
			return false;
		}

		$account_details = $this->merchant_details->get_details();

		$request = wp_remote_request( $link->href, [
			'method'  => $link->method,
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => "Bearer {$account_details->access_token}",
			],
		] );

		if ( is_wp_error( $request ) ) {
			tribe( 'logger' )->log_error( sprintf(
			// Translators: %s: The error message.
				__( 'PayPal capture request error: %s', 'event-tickets' ),
				$request->get_error_message()
			), 'tickets-commerce-paypal-commerce' );

			return false;
		}

		$response = wp_remote_retrieve_body( $request );
		$response = @json_decode( $response );

		if ( ! $response || empty( $response->id ) ) {
			tribe( 'logger' )->log_error( __( 'Unexpected PayPal capture response', 'event-tickets' ), 'tickets-commerce-paypal-commerce' );

			return false;
		}

		return (string) $response->id;
	}
}
