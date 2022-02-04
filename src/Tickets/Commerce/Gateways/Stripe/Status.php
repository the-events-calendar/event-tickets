<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Status as Commerce_Status;

/**
 * Class Status.
 *
 * @todo Create a Contract between this and PayPal.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Status {

	/**
	 * Order Status in Stripe for when the payment intent is first created or when payment is denied.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	CONST REQUIRES_PAYMENT_METHOD = 'requires_payment_method';

	/**
	 * Order Status in Stripe for created and waiting for automatic confirmation to start processing.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	CONST REQUIRES_CONFIRMATION = 'requires_confirmation';

	/**
	 * Order Status in Stripe for created and waiting for user confirmation to start processing.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	CONST REQUIRES_ACTION = 'requires_action';

	/**
	 * Order Status in Stripe for processing.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	CONST PROCESSING = 'processing';

	/**
	 * Order Status in Stripe for a successful hold on funds, waiting for settlement.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	CONST REQUIRES_CAPTURE = 'requires_capture';


	/**
	 * Order Status in Stripe for completed with success.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	CONST SUCCEEDED = 'succeeded';

	/**
	 * Order Status in Stripe for manually cancelled and invalidated.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	CONST CANCELED = 'canceled';

	/**
	 * Default mapping from Stripe Status to Tickets Commerce
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected $default_map = [
		self::REQUIRES_PAYMENT_METHOD => Commerce_Status\Created::SLUG,
		self::REQUIRES_CONFIRMATION => Commerce_Status\Action_Required::SLUG,
		self::REQUIRES_ACTION => Commerce_Status\Action_Required::SLUG,
		self::REQUIRES_CAPTURE => Commerce_Status\Action_Required::SLUG,
		self::PROCESSING => Commerce_Status\Pending::SLUG,
		self::SUCCEEDED => Commerce_Status\Completed::SLUG,
		self::CANCELED => Commerce_Status\Denied::SLUG,
	];

	/**
	 * Gets the valid mapping of the statuses.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_valid_statuses() {
		return $this->default_map;
	}

	/**
	 * Checks if a given Stripe status is valid.
	 *
	 * @since TBD
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
	 * @since TBD
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
}