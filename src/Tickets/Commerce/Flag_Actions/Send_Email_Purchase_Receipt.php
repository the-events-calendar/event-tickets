<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Status_Interface;
use WP_Post;

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
	public function handle( Status_Interface $new_status, $old_status, WP_Post $order ) {
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

		return $this->send_for_order( $order );
	}

	/**
	 * Send the Purchase Receipt email.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $order The order to send the purchaser receipt for.
	 *
	 * @return bool
	 */
	public function send_for_order( WP_Post $order ) {
		$provider  = tribe( $order->provider );
		$attendees = $provider->get_attendees_by_order_id( $order->ID );

		$email_class = tribe( \TEC\Tickets\Emails\Email\Purchase_Receipt::class );

		$email_class->set( 'order', $order );
		$email_class->set( 'attendees', $attendees );
		$email_class->recipient = $order->purchaser['email'];

		return $email_class->send();
	}
}
