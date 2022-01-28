<?php

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce\Utils\Value;

/**
 * @todo backend move common methods from Commerce/Order, Manual/Order and PayPal/Order here.
 */
abstract class Abstract_Order {

	/**
	 * Get a value object set with the combined price of a list of tickets.
	 *
	 * @since 5.2.3
	 *
	 * @param int[]|float[] $items a list of values
	 *
	 * @return Value;
	 */
	public function get_value_total( $items ) {
		$sub_totals = Value::build_list( array_filter( wp_list_pluck( $items, 'sub_total' ) ) );
		$total_value = Value::create();
		return $total_value->total( $sub_totals );
	}

	public function prepare_purchaser_data( $data ) {
		$purchaser = [
			'purchaser_user_id' => 0,
			'purchaser_full_name' => static::$placeholder_name,
			'purchaser_first_name' => static::$placeholder_name,
			'purchaser_last_name' => static::$placeholder_name,
			'purchaser_email' => '',
		];

		if ( empty( $data['billing_details'] ) && is_user_logged_in() ) {
			$user = wp_get_current_user();
			$purchaser['purchaser_user_id']    = $user->ID;
			$purchaser['purchaser_full_name']  = $user->first_name . ' ' . $user->last_name;
			$purchaser['purchaser_first_name'] = $user->first_name;
			$purchaser['purchaser_last_name']  = $user->last_name;
			$purchaser['purchaser_email']      = $user->user_email;
			return $purchaser;
		}

		if ( ! empty( $data['billing_details']['firstName'] ) ) {
			$purchaser['purchaser_first_name'] = $data['billing_details']['firstName'];
		}
		if ( ! empty( $data['billing_details']['lastName'] ) ) {
			$purchaser['purchaser_last_name'] = $data['billing_details']['lastName'];
		}
		if ( ! empty( $data['billing_details']['name'] ) ) {
			$purchaser['purchaser_full_name'] = $data['billing_details']['name'];
		}
		if ( ! empty( $data['billing_details']['email'] ) ) {
			$purchaser['purchaser_email'] = $data['billing_details']['email'];
		}

		return $purchaser;
	}
}