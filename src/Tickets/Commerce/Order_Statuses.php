<?php

namespace TEC\Tickets\Commerce;

/**
 * Class Order_Statuses
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce
 */
class Order_Statuses {

	/**
	 * The string representing the slug for a completed payment status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $completed = 'completed';

	/**
	 * The string representing the slug for a pending payment status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $pending = 'pending-payment';

	/**
	 * The string representing the slug for a denied payment status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $denied = 'denied';

	/**
	 * The string representing the slug for a refunded payment status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $refunded = 'refunded';

	/**
	 * The string representing the slug for an undefined payment status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $undefined = 'undefined';

	/**
	 * The string representing the slug for a not completed payment status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $not_completed = 'not-completed';

	/**
	 * Casts a payment status to one handled and recognized.
	 *
	 * @since TBD
	 *
	 * @param string $payment_status
	 */
	public static function cast_payment_status( $payment_status ) {
		$payment_status = strtolower( $payment_status );

		$legit = self::all_statuses();

		return in_array( $payment_status, $legit ) ? $payment_status : self::$undefined;
	}

	/**
	 * Validates a Tickets Commerce payment status.
	 *
	 * @since TBD
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
	 * Registers the post statuses with WordPress.
	 *
	 * @since TBD
	 */
	public static function register_order_statuses() {

		$statuses = tribe( 'tickets.status' )->get_all_provider_statuses( \TEC\Tickets\Commerce::ABBR );

		foreach ( $statuses as $status ) {

			if ( 'undefined' === $status->provider_name ) {
				continue;
			}

			register_post_status(
				$status->provider_name,
				[
					'label'                     => _x( $status->name, 'A Tickets Commerce order status', 'event-tickets' ),
					'public'                    => $status->public,
					'exclude_from_search'       => $status->exclude_from_search,
					'show_in_admin_all_list'    => $status->show_in_admin_all_list,
					'show_in_admin_status_list' => $status->show_in_admin_status_list,
					'label_count'               => _n_noop( $status->name . ' <span class="count">(%s)</span>', $status->name . ' <span class="count">(%s)</span>', 'event-tickets' ),
				]
			);
		}
	}

	/**
	 * Returns all the payment statuses supported by the PayPal
	 * integration.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public static function all_statuses() {
		$status_mgr = tribe( 'tickets.status' );

		return $status_mgr->get_statuses_by_action( 'all', 'tpp' );
	}

	/**
	 * Whether a payment status will mark a transaction as completed one way or another.
	 *
	 * A transaction might be completed because it successfully completed, because it
	 * was refunded or denied.
	 *
	 * @since  TBD
	 *
	 * @param string $payment_status
	 *
	 * @return bool
	 */
	public function is_complete_transaction_status( $payment_status ) {
		$status_mgr = tribe( 'tickets.status' );
		$statuses   = $status_mgr->get_statuses_by_action( [ 'count_completed', 'count_refunded' ], \TEC\Tickets\Commerce::ABBR, 'OR' );

		/**
		 * Filters the statuses that will mark a transaction as completed.
		 *
		 * @since TBD
		 *
		 * @param array  $statuses
		 * @param string $payment_status
		 */
		$statuses = apply_filters( 'tec_tickets_commerce_completed_transaction_statuses', $statuses );

		return in_array( $payment_status, $statuses );
	}

	/**
	 * Whether a PayPal payment status will mark a transaction as generating revenue or not.
	 *
	 * @since TBD
	 *
	 * @param string $payment_status
	 *
	 * @return bool
	 */
	public function is_revenue_generating_status( $payment_status ) {
		$status_mgr = tribe( 'tickets.status' );
		$statuses   = $status_mgr->get_statuses_by_action( 'count_completed', \TEC\Tickets\Commerce::ABBR );

		/**
		 * Filters the statuses that will mark a PayPal transaction as generating
		 * revenue.
		 *
		 * @since TBD
		 *
		 * @param array  $statuses
		 * @param string $payment_status
		 */
		$statuses = apply_filters( 'tec_tickets_commerce_revenue_generating_statuses', $statuses, $payment_status );

		return in_array( $payment_status, $statuses );
	}
}
