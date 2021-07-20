<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners;

use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\MerchantDetails;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\Webhooks;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\WebhookRegister;
use WP_Post;

/**
 * Class PaymentEventListener
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners
 *
 */
abstract class PaymentEventListener implements EventListener {
	/**
	 * @since 5.1.6
	 *
	 * @var MerchantDetails
	 */
	private $merchantRepository;

	/**
	 * @since 5.1.6
	 *
	 * @var Webhooks
	 */
	private $webhookRepository;

	/**
	 * @since 5.1.6
	 *
	 * @var WebhookRegister
	 */
	private $webhookRegister;

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
	 * @param MerchantDetails $merchantRepository
	 * @param WebhookRegister $register
	 * @param Webhooks        $webhookRepository
	 */
	public function __construct( MerchantDetails $merchantRepository, WebhookRegister $register, Webhooks $webhookRepository ) {
		$this->merchantRepository = $merchantRepository;
		$this->webhookRegister    = $register;
		$this->webhookRepository  = $webhookRepository;
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
	public function processEvent( $event ) {
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

		$paymentId = $this->getParentPaymentIdFromPayment( $event->resource );

		if ( ! $paymentId ) {
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

		$payment = $this->getOrderByPaymentId( $paymentId );

		// If there's no matching payment then it's not tracked by Tickets Commerce.
		if ( ! $payment ) {
			tribe( 'logger' )->log_debug(
				sprintf(
					// Translators: %s: The PayPal payment ID.
					__( 'Missing order for PayPal payment from webhook: %s', 'event-tickets' ),
					$paymentId
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
				sprintf( '[Order ID: %s; PayPal Payment ID: %s]', $payment->ID, $paymentId )
			),
			'tickets-commerce-paypal-commerce'
		);

		/**
		 * Allow hooking into the listener status.
		 *
		 * @since 5.1.6
		 *
		 * @param WP_Post $payment    The payment object.
		 * @param string  $paymentId  The PayPal payment ID.
		 * @param object  $event      The PayPal webhook event.
		 * @param string  $new_status The new order status.
		 */
		do_action( 'tribe_tickets_commerce_gateways_paypal_commerce_webhooks_listeners', $payment, $paymentId, $event, $this->new_status );

		/**
		 * Allow hooking into the listener status for the new status.
		 *
		 * @since 5.1.6
		 *
		 * @param WP_Post $payment   The payment object.
		 * @param string  $paymentId The PayPal payment ID.
		 * @param object  $event     The PayPal webhook event.
		 */
		do_action( "tribe_tickets_commerce_gateways_paypal_commerce_webhooks_listeners_{$this->new_status}", $payment, $paymentId, $event );

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
	public function getOrderByPaymentId( $id ) {
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
	private function getParentPaymentIdFromPayment( $payment ) {
		$link = current( array_filter( $payment->links, static function ( $link ) {
			return $link->rel === 'up';
		} ) );

		if ( ! $link ) {
			return false;
		}

		$accountDetails = $this->merchantDetails->getDetails();

		$request = wp_remote_request( $link->href, [
			'method'  => $link->method,
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => "Bearer {$accountDetails->accessToken}",
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
