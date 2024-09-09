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
	 * Gets the modifier type (e.g., 'coupon', 'fee').
	 *
	 * @since TBD
	 *
	 * @return string The modifier type (e.g., 'coupon', 'fee').
	 */
	public function get_modifier_type(): string;

	/**
	 * Adds a new Order Modifier to the system.
	 *
	 * @param array $data Form data from the request.
	 * @return mixed The newly created modifier instance (Coupon/Booking Fee).
	 */
	public function insert_modifier( array $data ): mixed;

	/**
	 * Updates an Order Modifier.
	 *
	 * @param array $data Form data from the request.
	 * @return mixed The newly created modifier instance (Coupon/Booking Fee).
	 */
	public function update_modifier( array $data ): mixed;

	/**
	 * Renders the table for this specific modifier type (e.g., coupon table, fee table).
	 *
	 * @since TBD
	 *
	 * @return mixed The rendered table.
	 */
	public function render_table(): mixed;

	/**
	 * Renders the edit screen for this specific modifier type.
	 *
	 * @since TBD
	 *
	 * @return mixed The rendered edit screen.
	 */
	public function render_edit(): mixed;

}
