<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Commerce\BackgroundJobs\SendTicketEmail;
use function TEC\Common\StellarWP\Shepherd\shepherd;

/**
 * Class Send_Email, normally triggered when an order is complete.
 *
 * @since 5.1.9
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Send_Email extends Flag_Action_Abstract {
	/**
	 * {@inheritDoc}
	 *
	 * @var array
	 */
	protected $flags = [
		'send_email',
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

		// temporary fix for manual attendees first email
		// @todo backend review this logic
		if ( ! empty( $order->gateway ) && 'manual' === $order->gateway && empty( $order->events_in_order ) ) {
			$order->events_in_order[] = $order;
		}


		if ( empty( $order->events_in_order ) || ! is_array( $order->events_in_order ) ) {
			return;
		}

		foreach ( $order->events_in_order as $event_id ) {
			$event = get_post( $event_id );
			if ( ! $event instanceof \WP_Post ) {
				continue;
			}

			shepherd()->dispatch( new SendTicketEmail( $order->ID, $event->ID ) );
		}
	}
}
