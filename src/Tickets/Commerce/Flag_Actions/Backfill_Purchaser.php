<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Status_Interface;

/**
 * Class Increase_Stock, normally triggered when refunding on orders get set to not-completed.
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Backfill_Purchaser extends Flag_Action_Abstract {
	/**
	 * {@inheritDoc}
	 */
	protected $flags = [
		'backfill_purchaser',
	];

	/**
	 * {@inheritDoc}
	 */
	protected $post_types = [
		Order::POSTTYPE
	];

	/**
	 * {@inheritDoc}
	 */
	public function handle( Status_Interface $new_status, $old_status, \WP_Post $order ) {
		if ( empty( $order->gateway_payload[ Completed::class ] ) ) {
			return;
		}

		if ( ! empty( $order->purchaser_email ) ) {
			return;
		}

		$payload = $order->gateway_payload[ Completed::class ];

		if ( empty( $payload['payer']['email_address'] ) ) {
			return;
		}

		if ( ! filter_var( $payload['payer']['email_address'], FILTER_VALIDATE_EMAIL ) ) {
			return;
		}

		$email      = trim( $payload['payer']['email_address'] );
		$first_name = null;
		$last_name  = null;
		$full_name  = null;

		if ( ! empty( $payload['name']['given_name'] ) ) {
			$first_name = trim( $payload['name']['given_name'] );
		}

		if ( ! empty( $payload['name']['surname'] ) ) {
			$last_name = trim( $payload['name']['surname'] );
		}

		$full_name = trim( $first_name . ' ' . $last_name );

		update_post_meta( $order->ID, Order::$purchaser_email_meta_key, $email );
		update_post_meta( $order->ID, Order::$purchaser_first_name_meta_key, $first_name );
		update_post_meta( $order->ID, Order::$purchaser_last_name_meta_key, $last_name );
		update_post_meta( $order->ID, Order::$purchaser_full_name_meta_key, $full_name );

		$attendees = tribe( Module::class )->get_attendees( $order->ID );

		if ( empty( $attendees ) ) {
			return;
		}

		foreach ( $attendees as $attendee ) {
			if ( empty( $attendee->email ) ) {
				update_post_meta( $attendee->ID, Attendee::$email_meta_key, $email );
			}

			if ( Order::$placeholder_name == $attendee->first_name ) {
				update_post_meta( $attendee->ID, Attendee::$first_name_meta_key, $first_name );
			}

			if ( Order::$placeholder_name == $attendee->last_name ) {
				update_post_meta( $attendee->ID, Attendee::$last_name_meta_key, $last_name );
			}

			if ( Order::$placeholder_name == $attendee->full_name ) {
				update_post_meta( $attendee->ID, Attendee::$full_name_meta_key, $full_name );
			}
		}
	}
}
