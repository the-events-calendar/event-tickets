<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Oversell__Admin_Notice_Decorator
 *
 * Decorates a policy to add an admin notice functionality.
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Oversell__Admin_Notice_Decorator implements Tribe__Tickets__Commerce__PayPal__Oversell__Policy_Interface {

	/**
	 * @var Tribe__Tickets__Commerce__PayPal__Oversell__Policy_Interface
	 */
	protected $policy;

	/**
	 * Tribe__Tickets__Commerce__PayPal__Oversell__Admin_Notice_Decorator constructor.
	 *
	 * @since TBD
	 *
	 * @paramTribe__Tickets__Commerce__PayPal__Oversell__Policy_Interface $instance
	 */
	public function __construct( $policy ) {
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
		$modified = $this->policy->modify_quantity( $qty, $inventory );

		$output = sprintf( '<p>%s</p>',
			sprintf( esc_html__(
				'PayPal Order %1$s caused a possible oversell of tickets when it was completed: %2$d available, %3$d requested, %4$d sold (oversell policy is %5$s).',
				'event-tickets'
			),
				$this->get_order_id(),
				$inventory,
				$qty,
				$modified,
				strtolower( $this->policy->get_name() )
			)
		);

		/** @var Tribe__Tickets__Commerce__PayPal__Notices $notices */
		$notices = tribe( 'tickets.commerce.paypal.notices' );
		$notices->register_transient_notice(
			"oversell-{$this->get_order_id()}-{$this->get_post_id()}",
			$output,
			'dismiss=1&type=warning'
		);

		return $modified;
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
	 * Returns the policy ticket post ID.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_ticket_id() {
		return $this->policy->get_ticket_id();
	}
}