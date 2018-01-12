<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Order
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Order {

	/**
	 * Either builds an Order object from a PayPal transaction data and returns it
	 * or fetches an existing Order information.
	 *
	 * @since TBD
	 *
	 * @param array $transaction_data
	 *
	 * @return Tribe__Tickets__Commerce__PayPal__Order
	 */
	public static function from_transaction_data( array $transaction_data ) {
		throw new Exception( __METHOD__ . ' not implemented' );

		return new self();
	}

	/**
	 * Finds orders by a list of criteria.
	 *
	 * @since TBD
	 *
	 * @param array $criteria {
	 *     Optional. Arguments to retrieve orders. See WP_Query::parse_query() for all
	 *     available arguments.
	 *
	 *     @type int        $post_id     ID, or array of IDs, of the post(s) Orders should be related to.
	 *     @type int        $ticket_id   ID, or array of IDs, of the ticket(s) Orders should be related to.
	 * }
	 *
	 * @return \Tribe__Tickets__Commerce__PayPal__Order[] $criteria
	 */
	public static function find_by( array $args  = array() ) {
		throw new Exception( __METHOD__ . ' not implemented' );
	}

	/**
	 * Updates an Order using the specified transaction data.
	 *
	 * @since TBD
	 *
	 * @param array $transaction_data
	 */
	public function update_with( array $transaction_data ) {
		throw new Exception( __METHOD__ . ' not implemented' );
	}

	/**
	 * Adds an attendee to those related to the Order.
	 *
	 * @param int $attendee_id An attendee post ID.
	 */
	public function add_attendee( $attendee_id ) {
		throw new Exception( __METHOD__ . ' not implemented' );
	}

	/**
	 * Adds a relation between the Order and a ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id A ticket post ID.
	 */
	public function add_ticket_id( $ticket_id ) {
		throw new Exception( __METHOD__ . ' not implemented' );
	}

	/**
	 * Adds a relation between the Order and a post.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id A post ID.
	 */
	public function add_post_id( $post_id ) {
		throw new Exception( __METHOD__ . ' not implemented' );
	}

	/**
	 * Returns the Order PayPal ID (hash).
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function paypal_id() {
		throw new Exception( __METHOD__ . ' not implemented' );
	}

	/**
	 * Returns the attendees for this order.
	 *
	 * @since TBD
	 *
	 * @return array An array of attendee information.
	 */
	public function get_attendees() {
		throw new Exception( __METHOD__ . ' not implemented' );
	}
}