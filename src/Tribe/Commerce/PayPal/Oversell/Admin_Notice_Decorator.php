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

		$output = $this->style();
		$output .= $this->header_html( $qty, $inventory );

		/**
		 * Filters the default policy that should be used to handle overselling.
		 *
		 * @since TBD
		 *
		 * @param string $default
		 * @param int    $post_id   The post ID
		 * @param int    $ticket_id The ticket post ID
		 * @param string $order_id  The Order PayPal ID (hash)
		 */
		$default = apply_filters( 'tribe_tickets_commerce_paypal_oversell_default_policy', 'sell-all', $this->get_post_id(), $this->get_ticket_id(), $this->get_order_id() );

		$output .= $this->options_html( $default );

		tribe_transient_notice( $this->notice_slug(), $output, 'type=warning' );

		return $modified;
	}

	/**
	 * Returns the embedded styles for the notice.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	protected function style() {
		return '<style>
			.tribe-tickets-paypal-oversell-radio + .tribe-tickets-paypal-oversell-radio {
				margin-top: .25em;
			}
			.tribe-tickets-paypal-oversell-submit {
				margin: .5em auto;
				padding-bottom: 2px;
			}
		}</style>';
	}

	/**
	 * Returns the notice header HTML.
	 *
	 * @since TBD
	 *
	 * @param int $qty
	 * @param int $inventory
	 *
	 * @return string
	 */
	protected function header_html( $qty, $inventory ) {
		$post_id         = $this->get_post_id();
		$post            = get_post( $post_id );
		$post_type       = ! empty( $post ) ? get_post_type_object( $post->post_type ) : null;
		$post_type_label = null !== $post_type
			? strtolower( $post_type->labels->singular_name )
			: _x( 'event', 'a generic name used to indicate any post type that has tickets assigned', 'event-tickets' );

		if ( ! empty( $post ) ) {
			$edit_link  = $this->get_user_insensible_edit_link( $post_type, $post_id );
			$post_title = sprintf(
				__(
					'%s "%s" (ID %d)',
					'event-tickets' ),
				ucwords( $post_type_label ),
				sprintf(
					'<a href="%s">%s</a>',
					esc_url( $edit_link ),
					apply_filters( 'the_title', $post->post_title, $post_id )
				),
				$post_id
			);
		} else {
			$post_title = __( 'An event', 'event-tickets' );
		}

		/** @var Tribe__Tickets__Commerce__PayPal__Links $links */
		$links             = tribe( 'tickets.commerce.paypal.links' );
		$order_paypal_link = $links->order_link( 'tag', $this->get_order_id(), __( 'in your PayPal account', 'event-tickets' ) );

		$lines   = array();
		$lines[] = esc_html__(
			'%1$s  is oversold: there are more tickets sold than the available %2$s capacity. This can occur when PayPal does not complete transactions immediately, delaying the decrease in %2$s capacity.',
			'event-tickets'
		);
		$lines[] = esc_html__(
			'Order %3$s includes %4$s ticket(s). There are only %5$s ticket(s) left for this %2$s. Ticket emails have not yet been sent for this order. Choose how to process this order from the options below. You may also want to adjust or refund the order %6$s.',
			'event-tickets'
		);

		$qty       = esc_html( $qty );
		$inventory = esc_html( $inventory );

		return sprintf(
			'<p class="tribe-tickets-paypal-oversell-header">%s</p>',
			sprintf(
				implode( "\n", $lines ),
				$post_title,
				$post_type_label,
				$this->get_order_id(),
				"<strong>{$qty}</strong>",
				"<strong>{$inventory}</strong>",
				$order_paypal_link
			)
		);
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
	 * Returns the post edit link skipping the `current_user_can` check.
	 *
	 * This might happen in the context of a PayPal request handling and the
	 * current user will be set to `0`.
	 *
	 * @param object $post_type
	 * @param int    $post_id
	 *
	 * @return string
	 */
	protected function get_user_insensible_edit_link( $post_type, $post_id ) {
		$action = '&amp;action=edit';

		return admin_url( sprintf( $post_type->_edit_link . $action, $post_id ) );
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
	 * Returns the notice options HTML.
	 *
	 * @since TBD
	 *
	 * @param string $default The default oversell policy that should be used.
	 *
	 * @return string
	 */
	protected function options_html( $default ) {
		$form_inside = '';

		$hidden_inputs = array(
			'tpp_action'   => Tribe__Tickets__Commerce__PayPal__Oversell__Request::$oversell_action,
			'tpp_order_id' => $this->get_order_id(),
			'tpp_slug'     => $this->notice_slug(),
		);

		foreach ( $hidden_inputs as $name => $value ) {
			$form_inside .= sprintf( '<input type="hidden" name="%s" value="%s">', esc_attr( $name ), esc_attr( $value ) );
		}

		$options = array(
			'sell-all'       => __( 'Confirm attendee records and send emails for all tickets in this order (overselling the event)', 'event-tickets' ),
			'sell-available' => __( 'Confirm attendee records and send emails for some tickets in this order without overselling the event', 'event-tickets' ),
			'no-oversell'    => __( 'Delete all attendees for this order and do not send any email', 'event-tickets' ),
		);

		foreach ( $options as $policy => $label ) {
			$form_inside .= sprintf(
				'<div class="tribe-tickets-paypal-oversell-radio"><input type="radio" radiogroup="order-%1$s-actions" value="%2$s" name="tpp_policy" '
				. checked( $default, $policy, false )
				. '><label >%3$s</label></div>',
				$this->get_order_id(),
				$policy,
				$label
			);
		}

		$form_inside .= sprintf(
			'<div class="tribe-tickets-paypal-oversell-submit"><input type="submit" value="%s" class="button button-secondary"></div>',
			__( 'Process order', 'event-tickets' )
		);

		return sprintf(
			'<div class="tribe-tickets-paypal-oversell-form"><form action="%s" method="get">%s</form></div>',
			$this->oversell_url(),
			$form_inside
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
	 * Returns the URL that will be used to trigger an oversell for the Order from the admin UI.
	 *
	 * Note there is no nonce as the order might be generated during a POST request where the user
	 * is `0`.
	 *
	 * @return string
	 */
	protected function oversell_url() {
		return admin_url();
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