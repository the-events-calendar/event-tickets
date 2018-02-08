<?php

/**
 * Interface Tribe__Tickets__Commerce__PayPal__Oversell__Policy_Interface
 *
 * @since TBD
 */
interface Tribe__Tickets__Commerce__PayPal__Oversell__Policy_Interface {

	/**
	 * Whether this policy allows overselling or not.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function allows_overselling();

	/**
	 * Modifies the quantity of tickets that can actually be over-sold according to
	 * this policy.
	 *
	 * @since TBD
	 *
	 * @param int $qty       The requested quantity
	 * @param int $inventory The current inventory value
	 *
	 * @return int The updated quantity
	 */
	public function modify_quantity( $qty, $inventory );

	/**
	 * Returns the policy post ID.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	public function get_post_id();

	/**
	 * Returns the policy PayPal Order ID (hash).
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_order_id();

	/**
	 * Returns the policy ticket post ID.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_ticket_id();

	/**
	 * Returns the policy nice name.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Handles existing oversold attendees generated from an oversell.
	 *
	 * @since TBD
	 *
	 * @param array $oversold_attendees
	 */
	public function handle_oversold_attendees( array $oversold_attendees );
}