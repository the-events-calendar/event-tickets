<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Status_Interface;

/**
 * Class Send_Email_Purchase_Receipt, normally triggered when an order is completed.
 *
 * @since 5.5.10
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Send_Email_Purchase_Receipt extends Flag_Action_Abstract {
	/**
	 * {@inheritDoc}
	 *
	 * @var array
	 */
	protected $flags = [
		'send_email_purchase_receipt',
	];

	/**
	 * {@inheritDoc}
	 *
	 * @var array
	 */
	protected $post_types = [
		Order::POSTTYPE,
	];

	/**
	 * {@inheritDoc}
	 */
	public function handle( Status_Interface $new_status, $old_status, \WP_Post $order ) {
		// Bail if tickets emails is not enabled.
		if ( ! tec_tickets_emails_is_enabled() ) {
			return;
		}

		if ( ! empty( $order->gateway ) && 'manual' === $order->gateway && empty( $order->events_in_order ) ) {
			$order->events_in_order[] = $order;
		}

		if ( empty( $order->events_in_order ) || ! is_array( $order->events_in_order ) ) {
			return;
		}

		if ( ! isset( $order->purchaser['email'] ) ) {
			return;
		}

		/**
		 * Filter the order before sending the email about the purchase receipt of the order.
		 *
		 * @since 5.18.0
		 *
		 * @param \WP_Post $order The order.
		 */
		$order = apply_filters( 'tec_tickets_commerce_prepare_order_for_email_send_email_purchase_receipt', $order );

		$provider  = tribe( $order->provider );
		$attendees = $provider->get_attendees_by_order_id( $order->ID );

		$email_class = tribe( \TEC\Tickets\Emails\Email\Purchase_Receipt::class );

		$email_class->set( 'order', $order );
		$email_class->set( 'attendees', $attendees );
		$email_class->recipient = $order->purchaser['email'];

		return $email_class->send();
	}
}
