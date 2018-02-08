<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Oversell__Policy
 *
 * @since TBD
 */
abstract class Tribe__Tickets__Commerce__PayPal__Oversell__Policy {

	/**
	 * @var int
	 */
	protected $post_id;

	/**
	 * @var int
	 */
	protected $ticket_id;

	/**
	 * @var string
	 */
	protected $order_id;

	/**
	 * Tribe__Tickets__Commerce__PayPal__Oversell__Policy constructor.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id
	 * @param int    $ticket_id
	 * @param string $order_id
	 */
	public function __construct( $post_id, $ticket_id, $order_id ) {
		$this->post_id   = $post_id;
		$this->ticket_id = $ticket_id;
		$this->order_id  = $order_id;
	}

	/**
	 * Returns the policy post ID.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	public function get_post_id() {
		return $this->post_id;
	}

	/**
	 * Returns the policy ticket post ID.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	public function get_ticket_id() {
		return $this->ticket_id_id;
	}

	/**
	 * Returns the policy Order ID (hash).
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_order_id() {
		return $this->order_id;
	}

	/**
	 * Handles surplus attendees generated from an oversell.
	 *
	 * @since TBD
	 *
	 * @param array $oversold_attendees
	 */
	public function handle_oversold_attendees( array $oversold_attendees ) {
		return;
	}
}
