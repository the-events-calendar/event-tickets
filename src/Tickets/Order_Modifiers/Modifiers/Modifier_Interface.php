<?php

namespace TEC\Tickets\Order_Modifiers\Modifiers;

/**
 * Interface for Order Modifiers (Coupons and Booking Fees).
 *
 * Defines the contract that all Order Modifiers must follow.
 *
 * @since TBD
 */
interface Modifier_Interface {
	/**
	 * Adds a new Order Modifier to the system.
	 *
	 * @param array $data Form data from the request.
	 * @return mixed The newly created modifier instance (Coupon/Booking Fee).
	 */
	public function insert_modifier( array $data ): mixed;

	/**
	 * Updated an Order Modifier.
	 *
	 * @param array $data Form data from the request.
	 * @return mixed The newly created modifier instance (Coupon/Booking Fee).
	 */
	public function update_modifier( array $data ): mixed;


}
