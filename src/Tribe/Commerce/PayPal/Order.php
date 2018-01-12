<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Order
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Order {

	/**
	 * The meta key that stores the order PayPal ID (hash).
	 * @var string
	 */
	public static $order_id_key = 'paypal_order_id';

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
		$order_id = Tribe__Utils__Array::get( $transaction_data, 'txn_id', false );

		if ( false === $order_id ) {
			throw new InvalidArgumentException( __( 'Order id parameter (`txn_id`) is missing from the transaction data', 'event-tickets' ) );
		}

		unset( $transaction_data['txn_id'] );

		$order = self::from_order_id( $order_id );

		if ( ! $order ) {
			$order = new self();
		}

		$order->hydrate_from_transaction_data( $transaction_data );

		return $order;
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
	 * Searches for an Order by PayPal order ID (hash) and builds it if found.
	 *
	 * @since TBD
	 *
	 * @param $order_id
	 */
	public static function from_order_id( $order_id ) {
		global $wpdb;

		$order_post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id from {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s",
				self::$order_id_key,
				$order_id
			)
		);

		if ( empty( $order_post_id ) ) {
			return false;
		}

		$order = new self();

		$order->hydrate_from_post( $order_post_id );

		return $order;
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

	/**
	 * Sets a meta key value on the Order.
	 *
	 * @since TBD
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set_meta( $key, $value ) {
	}

	/**
	 * Fills an order information from a post stored fields and meta.
	 *
	 * @since TBD
	 *
	 * @param int $order_post_id The Order post ID.
	 */
	public function hydrate_from_post( $order_post_id ) {
		$order_post = get_post( $order_post_id );

		if ( empty( $order_post ) || Tribe__Tickets__Commerce__PayPal__Main::ORDER_OBJECT === $order_post->post_type ) {
			return;
		}

		$post_meta = get_post_meta($order_post_id)
	}

	/**
	 * Fills an order information from a transaction data array.
	 *
	 * @since TBD
	 *
	 * @param array $transaction_data
	 */
	public function hydrate_from_transaction_data( array $transaction_data ) {
	}
}