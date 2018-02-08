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

		$output = sprintf(
			'<p>%s</p>',
			sprintf(
				esc_html__(
					'PayPal Order %1$s caused a possible oversell of tickets: %2$d available, %3$d requested; oversell policy is %4$s.',
					'event-tickets'
				),
				$this->get_order_id(),
				$inventory,
				$qty,
				strtolower( $this->policy->get_name() )
			)
		);

		$sell_all_link = sprintf(
			'<a href="%s">%s</a>',
			$this->oversell_url( 'sell-all' ),
			__( 'generate all requested attendees and oversell', 'event-tickets' )
		);

		$sell_available_link = sprintf(
			'<a href="%s">%s</a>',
			$this->oversell_url( 'sell-available' ),
			__( 'generate attendees only for available tickets and not oversell', 'event-tickets' )
		);

		$no_oversell_link = sprintf(
			'<a href="%s">%s</a>',
			$this->oversell_url( 'no-oversell' ),
			__( 'delete generated attendees and send no emails', 'event-tickets' )
		);

		$output           .= sprintf(
			'<p>%s</p>',
			sprintf(
				esc_html__( 'You can %s, %s, or %s.', 'event-tickets' ),
				$sell_all_link,
				$sell_available_link,
				$no_oversell_link
			)
		);

		tribe_transient_notice( $this->notice_slug(), $output, 'dismiss=1&type=warning' );

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

	/**
	 * Returns the URL that will be used to trigger an oversell for the Order from the admin UI.
	 *
	 * Note there is no nonce as the order might be generated during a POST request where the user
	 * is `0`.
	 *
	 * @param string $policy
	 *
	 * @return string
	 */
	protected function oversell_url( $policy ) {
		$order_id = $this->get_order_id();

		return add_query_arg(
			array(
				'tpp_action'   => Tribe__Tickets__Commerce__PayPal__Oversell__Request::$oversell_action,
				'tpp_policy'   => $policy,
				'tpp_order_id' => $order_id,
				'tpp_slug'     => $this->notice_slug(),
			),
			admin_url()
		);
	}

	/**
	 * Returns the notice slug for this decorator.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	protected function notice_slug() {
		return "tickets-paypal-oversell-{$this->get_order_id()}-{$this->get_post_id()}";
	}

	/**
	 * Handles surplus attendees generated from an oversell.
	 *
	 * @since TBD
	 *
	 * @param array $oversold_attendees
	 *
	 * @return array A list of deleted attendees post IDs if any.
	 */
	public function handle_oversold_attendees( array $oversold_attendees ) {
		return $this->policy->handle_oversold_attendees( $oversold_attendees );
	}
}