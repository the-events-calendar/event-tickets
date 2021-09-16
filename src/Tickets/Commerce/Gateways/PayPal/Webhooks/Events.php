<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks;

use TEC\Tickets\Commerce\Status as Commerce_Status;

/**
 * Class Events
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Webhooks
 */
class Events {
	/**
	 * Webhook Event name for a capture of completed payment.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const PAYMENT_CAPTURE_COMPLETED = 'PAYMENT.CAPTURE.COMPLETED';

	/**
	 * Webhook Event name for a capture of denied payment.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const PAYMENT_CAPTURE_DENIED = 'PAYMENT.CAPTURE.DENIED';

	/**
	 * Webhook Event name for a capture of refunded payment.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const PAYMENT_CAPTURE_REFUNDED = 'PAYMENT.CAPTURE.REFUNDED';

	/**
	 * Webhook Event name for a capture of reversed payment.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const PAYMENT_CAPTURE_REVERSED = 'PAYMENT.CAPTURE.REVERSED';

	/**
	 * Default mapping from PayPal Status to Tickets Commerce
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected $default_map = [
		self::PAYMENT_CAPTURE_COMPLETED => Commerce_Status\Completed::SLUG,
		self::PAYMENT_CAPTURE_DENIED    => Commerce_Status\Denied::SLUG,
		self::PAYMENT_CAPTURE_REFUNDED  => Commerce_Status\Refunded::SLUG,
		self::PAYMENT_CAPTURE_REVERSED  => Commerce_Status\Reversed::SLUG,
	];

	/**
	 * Gets the valid mapping of the webhook events.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_valid() {
		/**
		 * Allows filtering of the Webhook map of events for each one of the types we listen for.
		 *
		 * @since TBD
		 *
		 * @param array $map The default map of which event types that translate to a given Status class.
		 */
		return apply_filters( 'tec_tickets_commerce_gateway_paypal_webook_events_map', $this->default_map );
	}

	/**
	 * Returns of a list of the Webhook events we are listening to.
	 *
	 * @since TBD
	 *
	 * @return string[]
	 */
	public function get_registered_events() {
		return array_keys( $this->get_valid() );
	}

	/**
	 * Checks if a given PayPal webhook event name is valid.
	 *
	 * @since TBD
	 *
	 * @param string $event_name A PayPal Event String.
	 *
	 * @return bool
	 */
	public function is_valid( $event_name ) {
		$events_map = $this->get_valid();

		return isset( $events_map[ $event_name ] );
	}

	/**
	 * Converts a valid PayPal webhook event name into a commerce status object.
	 *
	 * @since TBD
	 *
	 * @param string $event_name A PayPal Event String.
	 *
	 * @return false|Commerce_Status\Status_Interface|null
	 */
	public function convert_to_commerce_status( $event_name ) {
		if ( ! $this->is_valid( $event_name ) ) {
			return false;
		}
		$events_map = $this->get_valid();

		return tribe( Commerce_Status\Status_Handler::class )->get_by_slug( $events_map[ $event_name ] );
	}

}