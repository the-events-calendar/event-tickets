<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Stati
 *
 * @since 4.7
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
	public static $pending = 'pending-payment';

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
	 * Casts a payment status to one handled and recognized.
	 *
	 * @since 4.7
	 *
	 * @param string $payment_status
	 */
	public static function cast_payment_status( $payment_status ) {
		$payment_status = strtolower( $payment_status );

		$legit = self::all_statuses();

		return in_array( $payment_status, $legit ) ? $payment_status : self::$undefined;
	}

	/**
	 * Validates a PayPal payment status.
	 *
	 * @since 4.7
	 *
	 * @param string $payment_status
	 *
	 * @return false|string The validated status string or `false` if the
	 *                   status is not valid.
	 */
	public static function validate_payment_status( $payment_status ) {
		$cast = self::cast_payment_status( $payment_status );

		return self::$undefined !== $cast ? $cast : false;
	}

	/**
	 * Registers the post stati with WordPress.
	 *
	 * @since 4.7
	 */
	public static function register_order_stati() {
		register_post_status( self::$completed, array(
			'label'                     => _x( 'Completed', 'A PayPal order status', 'event-tickets' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>', 'event-tickets' ),
		) );

		register_post_status( self::$not_completed, array(
			'label'                     => _x( 'Not completed', 'A PayPal order status', 'event-tickets' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Not completed <span class="count">(%s)</span>', 'Not completed <span class="count">(%s)</span>', 'event-tickets' ),
		) );

		register_post_status( self::$pending, array(
			'label'                     => _x( 'Pending', 'A PayPal order status', 'event-tickets' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', 'event-tickets' ),
		) );

		register_post_status( self::$refunded, array(
			'label'                     => _x( 'Refunded', 'A PayPal order status', 'event-tickets' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>', 'event-tickets' ),
		) );

		register_post_status( self::$denied, array(
			'label'                     => _x( 'Denied', 'A PayPal order status', 'event-tickets' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Denied <span class="count">(%s)</span>', 'Denied <span class="count">(%s)</span>', 'event-tickets' ),
		) );
	}

	/**
	 * Returns all the payment statuses supported by the PayPal
	 * integration.
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	public static function all_statuses() {
		return array(
			self::$completed,
			self::$not_completed,
			self::$pending,
			self::$refunded,
			self::$denied,
		);
	}

	/**
	 * Whether a PayPal payment status will mark a transaction as completed one way or another.
	 *
	 * A transaction might be completed because it successfully completed, because it
	 * was refunded or denied.
	 *
	 * @since 4.7
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
		 * @since 4.7
		 *
		 * @param array  $statuses
		 * @param string $payment_status
		 */
		$statuses = apply_filters( 'tribe_tickets_commerce_paypal_completed_transaction_statuses', $statuses );

		return in_array( $payment_status, $statuses );
	}

	/**
	 * Whether a PayPal payment status will mark a transaction as generating revenue or not.
	 *
	 * @since 4.7
	 *
	 * @param string $payment_status
	 *
	 * @return bool
	 */
	public function is_revenue_generating_status( $payment_status ) {
		$statuses = array( self::$completed );

		/**
		 * Filters the statuses that will mark a PayPal transaction as generating
		 * revenue.
		 *
		 * @since 4.7
		 *
		 * @param array  $statuses
		 * @param string $payment_status
		 */
		$statuses = apply_filters( 'tribe_tickets_commerce_paypal_revenue_generating_statuses', $statuses, $payment_status );

		return in_array( $payment_status, $statuses );
	}
}