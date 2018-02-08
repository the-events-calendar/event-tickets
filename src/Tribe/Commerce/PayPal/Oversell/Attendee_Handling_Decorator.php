<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Oversell__Attendee_Handling_Decorator
 *
 * Decorates an oversell policy object to handle oversold attendees.
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Oversell__Attendee_Handling_Decorator implements Tribe__Tickets__Commerce__PayPal__Oversell__Policy_Interface {

	/**
	 * @var \Tribe__Tickets__Commerce__PayPal__Oversell__Policy_Interface
	 */
	protected $policy;

	/**
	 * Tribe__Tickets__Commerce__PayPal__Oversell__Attendee_Handling_Decorator constructor.
	 *
	 * @since TBD
	 *
	 * @param \Tribe__Tickets__Commerce__PayPal__Oversell__Policy_Interface $policy
	 */
	public function __construct( Tribe__Tickets__Commerce__PayPal__Oversell__Policy_Interface $policy ) {
		$this->policy = $policy;
	}

	/**
	 * Whether this policy allows overselling or not.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function allows_overselling() {
		return $this->policy->allows_overselling();
	}

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
	public function modify_quantity( $qty, $inventory ) {
		return $this->policy->modify_quantity( $qty, $inventory );
	}

	/**
	 * Returns the policy post ID.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	public function get_post_id() {
		return $this->policy->get_post_id();
	}

	/**
	 * Returns the policy PayPal Order ID (hash).
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_order_id() {
		return $this->policy->get_order_id();
	}

	/**
	 * Returns the policy ticket post ID.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_ticket_id() {
		return $this->policy->get_ticket_id();
	}

	/**
	 * Returns the policy nice name.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->policy->get_name();
	}

	/**
	 * Handles existing oversold attendees generated from an oversell.
	 *
	 * @since TBD
	 *
	 * @param array $oversold_attendees
	 */
	public function handle_oversold_attendees( array $oversold_attendees ) {
		/** @var Tribe__Tickets__Commerce__PayPal__Main $paypal */
		$paypal = tribe( 'tickets.commerce.paypal' );

		foreach ( $oversold_attendees as $attendee ) {
			if ( empty( $attendee['attendee_id'] ) ) {
				continue;
			}

			$paypal->delete_ticket( $attendee['event_id'], $attendee['attendee_id'] );
		}
	}
}