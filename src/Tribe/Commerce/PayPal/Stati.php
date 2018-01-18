<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Stati
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Stati {

	/**
	 * The string representing the slug for a completed payment status.
	 *
	 * @var string
	 */
	public static $completed = 'completed';

	/**
	 * The string representing the slug for a pending payment status.
	 *
	 * @var string
	 */
	public static $pending = 'pending';

	/**
	 * The string representing the slug for a denied payment status.
	 *
	 * @var string
	 */
	public static $denied = 'denied';

	/**
	 * The string representing the slug for a refunded payment status.
	 *
	 * @var string
	 */
	public static $refunded = 'refunded';

	/**
	 * The string representing the slug for an undefined payment status.
	 *
	 * @var string
	 */
	public static $undefined = 'undefined';

	/**
	 * The string representing the slug for a not completed payment status.
	 *
	 * @var string
	 */
	public static $not_completed = 'not-completed';

	/**
	 * Whether a PayPal payment status will mark a transaction as completed one way or another.
	 *
	 * A transaction might be completed because it successfully completed, because it
	 * was refunded or cancelled.
	 *
	 * @since TBD
	 *
	 * @param string $payment_status
	 *
	 * @return bool
	 */
	public function is_complete_transaction_status( $payment_status ) {
		$statuses = array( self::$completed, self::$refunded );

		/**
		 * Filters the statuses that will mark a PayPal transaction as completed.
		 *
		 * @since TBD
		 *
		 * @param array  $statuses
		 * @param string $payment_status
		 */
		$statuses = apply_filters( 'tribe_tickets_commerce_paypal_completed_transaction_statuses', $statuses );

		return in_array( $payment_status, $statuses );
	}
}