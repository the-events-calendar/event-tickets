<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Status as Commerce_Status;

/**
 * Class Status.
 *
 * @todo    Create a Contract between this and PayPal.
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Status {

	/**
	 * Order Status in Stripe for when the payment intent is first created or when payment is denied.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	const REQUIRES_PAYMENT_METHOD = 'requires_payment_method';

	/**
	 * Order Status in Stripe for when the payment intent is first created or when payment is denied.
	 *
	 * Deprecated in favor of REQUIRES_PAYMENT_METHOD.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	const REQUIRES_SOURCE = 'requires_source';

	/**
	 * Order Status in Stripe for created and waiting for automatic confirmation to start processing.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	const REQUIRES_CONFIRMATION = 'requires_confirmation';

	/**
	 * Order Status in Stripe for created and waiting for user confirmation to start processing.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	const REQUIRES_ACTION = 'requires_action';

	/**
	 * Order Status in Stripe for created and waiting for user confirmation to start processing.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	const REQUIRES_SOURCE_ACTION = 'requires_source_action';

	/**
	 * Order Status in Stripe for processing.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	const PROCESSING = 'processing';

	/**
	 * Order Status in Stripe for a successful hold on funds, waiting for settlement.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	const REQUIRES_CAPTURE = 'requires_capture';

	/**
	 * Order Status in Stripe for completed with success.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	const SUCCEEDED = 'succeeded';

	/**
	 * Order Status in Stripe for manually cancelled and invalidated.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	const CANCELED = 'canceled';

	/**
	 * Default mapping from Stripe Status to Tickets Commerce
	 *
	 * This list MUST be kept in order of order Stripe status progression, from creation to completion/refusal, as
	 * described in the link below:
	 *
	 * @link  https://stripe.com/docs/payments/intents
	 *
	 * @since 5.3.0
	 *
	 * @var array
	 */
	protected $default_map = [
		self::REQUIRES_PAYMENT_METHOD => Commerce_Status\Created::SLUG,
		self::REQUIRES_SOURCE         => Commerce_Status\Created::SLUG,
		self::REQUIRES_CONFIRMATION   => Commerce_Status\Action_Required::SLUG,
		self::REQUIRES_ACTION         => Commerce_Status\Action_Required::SLUG,
		self::REQUIRES_SOURCE_ACTION  => Commerce_Status\Action_Required::SLUG,
		self::REQUIRES_CAPTURE        => Commerce_Status\Action_Required::SLUG,
		self::PROCESSING              => Commerce_Status\Pending::SLUG,
		self::SUCCEEDED               => Commerce_Status\Completed::SLUG,
		self::CANCELED                => Commerce_Status\Denied::SLUG,
	];

	/**
	 * Gets the valid mapping of the statuses.
	 *
	 * @since 5.3.0
	 *
	 * @return array
	 */
	public function get_valid_statuses() {
		return $this->default_map;
	}

	/**
	 * Checks if a given Stripe status is valid.
	 *
	 * @since 5.3.0
	 *
	 * @param string $status Status from Stripe.
	 *
	 * @return bool
	 */
	public function is_valid_status( $status ) {
		$statuses = $this->get_valid_statuses();

		return isset( $statuses[ $status ] );
	}

	/**
	 * Converts a valid Stripe status into a commerce status object.
	 *
	 * @since 5.3.0
	 *
	 * @param string $stripe_status A Stripe status string.
	 *
	 * @return false|Commerce_Status\Status_Interface|null
	 */
	public function convert_to_commerce_status( $stripe_status ) {
		if ( ! $this->is_valid_status( $stripe_status ) ) {
			return false;
		}
		$statuses = $this->get_valid_statuses();

		return tribe( Commerce_Status\Status_Handler::class )->get_by_slug( $statuses[ $stripe_status ] );
	}

	/**
	 * Converts a valid Stripe payment intent to a commerce status object.
	 *
	 * @since 5.19.3
	 *
	 * @param array $payment_intent A Stripe payment intent.
	 *
	 * @return false|Commerce_Status\Status_Interface|null
	 */
	public function convert_payment_intent_to_commerce_status( array $payment_intent ) {
		if ( ! isset( $payment_intent['status'] ) ) {
			return false;
		}

		$last_payment_error = $payment_intent['last_payment_error'] ?? null;
		$has_valid_error    = ! empty( $last_payment_error['decline_code'] ) || ! empty( $last_payment_error['type'] );

		if ( $last_payment_error && $has_valid_error ) {
			tribe( Commerce_Status\Denied::class );
		}

		$stripe_status = $payment_intent['status'];

		return $this->convert_to_commerce_status( $stripe_status );
	}
}
