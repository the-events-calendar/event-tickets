<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use TEC\Tickets\Commerce\Status as Commerce_Status;

/**
 * Class Status
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Status {

	/**
	 * Order Status in PayPal for created.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	CONST CREATED = 'CREATED';

	/**
	 * Order Status in PayPal for saved.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	CONST SAVED = 'SAVED';

	/**
	 * Order Status in PayPal for approved.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	CONST APPROVED = 'APPROVED';

	/**
	 * Order Status in PayPal for voided.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	CONST VOIDED = 'VOIDED';

	/**
	 * Order Status in PayPal for completed.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	CONST COMPLETED = 'COMPLETED';

	/**
	 * Order Status in PayPal for payer action required.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	CONST PAYER_ACTION_REQUIRED = 'PAYER_ACTION_REQUIRED';

	/**
	 * Default mapping from PayPal Status to Tickets Commerce
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected $default_map = [
		self::CREATED => Commerce_Status\Created::SLUG,
		self::SAVED => Commerce_Status\Pending::SLUG,
		self::APPROVED => Commerce_Status\Approved::SLUG,
		self::VOIDED => Commerce_Status\Voided::SLUG,
		self::COMPLETED => Commerce_Status\Completed::SLUG,
		self::PAYER_ACTION_REQUIRED => Commerce_Status\Action_Required::SLUG,
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
	 * Checks if a given PayPal status is valid.
	 *
	 * @since TBD
	 *
	 * @param string $status Status from PayPal.
	 *
	 * @return bool
	 */
	public function is_valid_status( $status ) {
		$statuses = $this->get_valid_statuses();
		return isset( $statuses[ $status ] );
	}

	/**
	 * Converts a valid PayPal status into a commerce status object.
	 *
	 * @since TBD
	 *
	 * @param string $paypal_status A PayPal status string.
	 *
	 * @return false|Commerce_Status\Status_Interface|null
	 */
	public function convert_to_commerce_status( $paypal_status ) {
		if ( ! $this->is_valid_status( $paypal_status ) ) {
			return false;
		}
		$statuses = $this->get_valid_statuses();

		return tribe( Commerce_Status\Status_Handler::class )->get_by_slug( $statuses[ $paypal_status ] );
	}
}