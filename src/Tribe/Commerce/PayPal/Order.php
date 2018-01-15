<?php
// @todo: add filters here and there

/**
 * Class Tribe__Tickets__Commerce__PayPal__Order
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Order {

	/**
	 * The meta key prefix used to store the order post meta.
	 *
	 * @var string
	 */
	public static $meta_prefix = '_paypal_';
	/**
	 * A list of attendees for the order.
	 *
	 * @var array
	 */
	public $attendees = array();
	/**
	 * The PayPal Order ID (hash).
	 *
	 * @var string
	 */
	public $paypal_order_id = '';
	/**
	 * The order post ID in the WordPress database.
	 *
	 * @var int
	 */
	public $post_id;
	/**
	 * The order post status.
	 *
	 * @var string
	 */
	public $status = '';

	/**
	 * All the ticket post IDs related to the Order.
	 *
	 * @var array
	 */
	public $ticket_ids = array();

	/**
	 * All the post IDs related to the Order.
	 *
	 * @var array
	 */
	public $post_ids;

	/**
	 * The meta key that stores the order PayPal hashed meta.
	 *
	 * @var
	 */
	protected $hashed_meta_key = '_paypal_hashed_meta';

	/**
	 * A list of meta keys that are stored one per line in the database
	 * to facilitate SQL queries.
	 *
	 * @var array
	 *
	 * @see update for details about the database persistence.
	 */
	protected $searchable_meta_keys = array(
		'items',
		'mc_gross',
		'mc_currency',
		'payment_date',
		'payment_status',
		'payer_email',
		'attendees',
	);

	/**
	 * An array that stores all the meta for an Order object.
	 *
	 * @var array
	 */
	protected $meta = array();

	/**
	 * Either builds an Order object from a PayPal transaction data and returns it
	 * or fetches an existing Order information.
	 *
	 * @since TBD
	 *
	 * @param array $transaction_data
	 *
	 * @return Tribe__Tickets__Commerce__PayPal__Order|false Either an existing or new order or `false` on
	 *                                                       failure.
	 */
	public static function from_transaction_data( array $transaction_data ) {
		$order_id = Tribe__Utils__Array::get( $transaction_data, 'txn_id', false );

		if ( false === $order_id ) {
			return false;
		}

		$order = self::from_order_id( $order_id );

		if ( ! $order ) {
			$order = new self();
		}

		$order->hydrate_from_transaction_data( $transaction_data );

		return $order;
	}

	/**
	 * Searches for an Order by PayPal order ID (hash), builds and hydrates it if found.
	 *
	 * @since TBD
	 *
	 * @param $order_id
	 */
	public static function from_order_id( $order_id ) {
		$order_post_id = self::find_by_order_id( $order_id );

		if ( empty( $order_post_id ) ) {
			return false;
		}

		$order = new self();

		$order->hydrate_from_post( $order_post_id );

		return $order;
	}

	/**
	 * Finds an order by the PayPal order ID (hash).
	 *
	 * @since TBD
	 *
	 * @param string $order_id The PayPal order ID (hash).
	 *
	 * @return int|false Either an existing order post ID or `false` if not found.
	 */
	public static function find_by_order_id( $order_id ) {
		global $wpdb;

		$order_post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID from {$wpdb->posts} WHERE post_title = %s AND post_type = %s",
				trim( $order_id ),
				Tribe__Tickets__Commerce__PayPal__Main::ORDER_OBJECT
			)
		);

		return ! empty( $order_post_id ) ? (int) $order_post_id : false;
	}

	/**
	 * Fills an order information from a post stored fields and meta.
	 *
	 * This is a database-light operation that will not update
	 * the Order database information, use the `update` method
	 * to update the Order information on the database.
	 *
	 * @since TBD
	 *
	 * @param int $order_post_id The Order post ID.
	 *
	 * @return Tribe__Tickets__Commerce__PayPal__Order
	 *
	 * @see   update
	 */
	public function hydrate_from_post( $order_post_id ) {
		$order_post = get_post( $order_post_id );

		if (
			! $order_post instanceof WP_Post
			|| Tribe__Tickets__Commerce__PayPal__Main::ORDER_OBJECT !== $order_post->post_type
		) {
			return $this;
		}

		$this->paypal_order_id = $order_post->post_title;
		$this->post_id         = $order_post_id;
		$this->status          = $order_post->post_status;

		$hashed_meta = get_post_meta( $order_post_id, $this->hashed_meta_key, true );

		if ( ! empty( $hashed_meta ) ) {
			foreach ( $hashed_meta as $key => $value ) {
				$this->set_meta( $key, $value );
			}
		}

		foreach ( $this->searchable_meta_keys as $key ) {
			$prefixed_key = self::$meta_prefix . $key;
			$this->set_meta( $key, get_post_meta( $order_post_id, $prefixed_key, true ) );
		}

		/**
		 * Fired after an Orde object has been filled from post fields and meta. *
		 *
		 * @since TBD
		 *
		 * @param Tribe__Tickets__Commerce__PayPal__Order $this
		 */
		do_action( 'tribe_tickets_tpp_order_from_post', $this );

		return $this;
	}

	/**
	 * Fills an order information from a transaction data array.
	 *
	 * This is a database-light operation that will not update
	 * the Order database information, use the `update` method
	 * to update the Order information on the database.
	 *
	 * @since TBD
	 *
	 * @param array $transaction_data
	 *
	 * @return Tribe__Tickets__Commerce__PayPal__Order
	 *
	 * @see   update
	 */
	public function hydrate_from_transaction_data( array $transaction_data ) {
		foreach ( $transaction_data as $key => $value ) {
			$this->set_meta( $key, $value );
		}

		/**
		 * Fired after an Order object has been filled from post fields and meta.
		 *
		 * @since TBD
		 *
		 * @param Tribe__Tickets__Commerce__PayPal__Order $this
		 * @param array                                   $transaction_data
		 */
		do_action( 'tribe_tickets_tpp_order_from_transaction', $this, $transaction_data );

		return $this;
	}

	/**
	 * Finds orders by a list of criteria.
	 *
	 * @since TBD
	 *
	 * @param array $criteria  {
	 *                         Optional. Arguments to retrieve orders. See WP_Query::parse_query() for all
	 *                         available arguments.
	 *
	 * @type int    $post_id   ID, or array of IDs, of the post(s) Orders should be related to.
	 * @type int    $ticket_id ID, or array of IDs, of the ticket(s) Orders should be related to.
	 * }
	 *
	 * @return \Tribe__Tickets__Commerce__PayPal__Order[] $criteria
	 */
	public static function find_by( array $args = array() ) {
		$args = wp_parse_args( $args, array(
			'post_type'   => Tribe__Tickets__Commerce__PayPal__Main::ORDER_OBJECT,
			'post_status' => 'any',
		) );

		$meta_query = isset( $args['meta_query'] )
			? $args['meta_query']
			: array( 'relation' => 'AND' );

		if ( ! empty( $args['post_id'] ) ) {
			$related_post_ids              = is_array( $args['post_id'] ) ? $args['post_id'] : array( $args['post_id'] );
			$meta_query['related_post_id'] = array(
				'key'     => self::$meta_prefix . 'post',
				'value'   => $related_post_ids,
				'compare' => 'IN',
			);
			unset( $args['post_id'] );
		}

		if ( ! empty( $args['ticket_id'] ) ) {
			$related_ticket_ids              = is_array( $args['ticket_id'] ) ? $args['ticket_id'] : array( $args['ticket_id'] );
			$meta_query['related_ticket_id'] = array(
				'key'     => self::$meta_prefix . 'ticket',
				'value'   => $related_ticket_ids,
				'compare' => 'IN',
			);
			unset( $args['ticket_id'] );
		}

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		$args['fields'] = 'ids';

		$found = get_posts( $args );

		if ( $found ) {
			foreach ( $found as $order_post_id ) {
				$order    = new self();
				$orders[] = $order->hydrate_from_post( $order_post_id );
			}
		} else {
			$orders = array();
		}

		return $orders;
	}

	/**
	 * Adds an attendee to those related to the Order.
	 *
	 * @param int $attendee_id An attendee post ID.
	 */
	public function add_attendee( $attendee_id ) {
		/** @var Tribe__Tickets__Commerce__PayPal__Main $paypal */
		$paypal = tribe( 'tickets.commerce.paypal' );

		$attendee = $paypal->get_attendee( $attendee_id );

		if ( $this->has_attendee( $attendee_id ) ) {
			$this->remove_attendee( $attendee_id );
		}

		$this->attendees[] = $attendee;
	}

	/**
	 * Whether the Order is related to an attendee or not.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id An attendee post ID
	 *
	 * @return bool
	 */
	public function has_attendee( $attendee_id ) {
		$matching = wp_list_filter( $this->attendees, array( 'attendee_id' => $attendee_id ) );

		return ! empty( $matching );
	}

	/**
	 * Removes an attendee from those associated with the order.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id An attendee post ID
	 *
	 * @return array
	 */
	public function remove_attendee( $attendee_id ) {
		$filtered = array();

		foreach ( $this->attendees as $attendee ) {
			if ( $attendee['attendee_id'] === $attendee_id ) {
				continue;
			}

			$filtered[] = $attendee;
		}

		$this->attendees = $filtered;
	}

	/**
	 * Returns the Order PayPal ID (hash).
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function paypal_id() {
		return $this->paypal_order_id;
	}

	/**
	 * Returns the attendees for this order.
	 *
	 * @since TBD
	 *
	 * @return array An array of attendee information.
	 *
	 * @see Tribe__Tickets__Commerce__PayPal__Main::get_attendee() for the attendee format.
	 */
	public function get_attendees() {
		return $this->attendees;
	}

	/**
	 * Returns the value of a meta field set on the order or all the meta set
	 * on the Order.
	 *
	 * This is a database-light operation: meta is read from the object, not the
	 * database; use `hydrate` methods to populate the meta.
	 *
	 * @param string|null $key
	 *
	 * @return array|mixed Either a specif meta value, `null` if no value is set for
	 *                     the key; all the Order meta if `$key` is `null`.
	 *
	 * @see hydrate_from_post
	 * @see hydrate_from_transaction_data
	 */
	public function get_meta( $key = null ) {
		if ( null === $key ) {
			return $this->meta;
		}

		return isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : null;
	}

	/**
	 * Sets a meta key value on the Order.
	 *
	 * This is a database-light operation: meta is not written to the
	 * database but only in the object array cache; use `udpate` method
	 * to persist the Order meta.
	 *
	 * @since TBD
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @see   update
	 */
	public function set_meta( $key, $value ) {
		if ( 0 === strpos( $key, self::$meta_prefix ) ) {
			$key = str_replace( self::$meta_prefix, '', $key );
		}

		switch ( $key ) {
			case 'payment_status':
				if ( ! empty( $value ) ) {
					$this->status = Tribe__Tickets__Commerce__PayPal__Stati::cast_payment_status( $value );
				}

				return;
			case 'txn_id':
				$this->paypal_order_id = $value;

				return;
			case 'attendees':
				$value = is_array( $value ) ? $value : array( $value );
				/** @var Tribe__Tickets__Commerce__PayPal__Main $paypal */
				$paypal          = tribe( 'tickets.commerce.paypal' );
				$this->attendees = array_filter( array_map( array( $paypal, 'get_attendee' ), $value ) );

				return;
			case 'items':
				$this->meta['items'] = $value;
				$this->ticket_ids    = wp_list_pluck( $value, 'ticket_id' );
				$this->post_ids      = wp_list_pluck( $value, 'post_id' );

				return;
			default:
				$this->meta[ $key ] = $value;

				return;
		}

	}

	/**
	 * Updates an order data on the database.
	 *
	 * @since TBD
	 *
	 * @return int|false Either the updated/created order post ID or `false` if the Order
	 *                   could not be saved.
	 */
	public function update() {
		if ( empty( $this->paypal_order_id ) ) {
			return false;
		}

		$meta_input = array(
			$this->hashed_meta_key           => array(),
			self::$meta_prefix . 'attendees' => wp_list_pluck( $this->attendees, 'attendee_id' ),
		);

		foreach ( $this->meta as $key => $value ) {
			if ( in_array( $key, $this->searchable_meta_keys ) ) {
				$key                 = self::$meta_prefix . $key;
				$meta_input [ $key ] = $value;
			} else {
				$meta_input[ $this->hashed_meta_key ][ $key ] = $value;
			}
		}

		if ( empty( $this->status ) ) {
			$this->status = Tribe__Tickets__Commerce__PayPal__Stati::$undefined;
		}

		$postarr = array(
			'post_type'   => Tribe__Tickets__Commerce__PayPal__Main::ORDER_OBJECT,
			'post_title'  => $this->paypal_order_id,
			'post_status' => $this->status,
			'meta_input'  => $meta_input,
		);

		/**
		 * Filters the post array that will be saved to the database for the Order post.
		 *
		 * @since TBD
		 *
		 * @param array                                   $postarr
		 * @param Tribe__Tickets__Commerce__PayPal__Order $order
		 */
		$postarr = apply_filters( 'tribe_tickets_tpp_order_postarr', $postarr, $this );

		if ( empty( $this->post_id ) ) {
			$post_id = wp_insert_post( $postarr );
		} else {
			// remove any existing PayPal meta before the update
			global $wpdb;
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->postmeta} WHERE post_id = %d and meta_key LIKE %s",
					$this->post_id,
					self::$meta_prefix . '%'
				)
			);
			wp_cache_delete( $this->post_id, 'post_meta' );

			$postarr['ID'] = $this->post_id;

			$post_id = wp_update_post( $postarr );
		}

		if ( ! empty( $post_id ) ) {
			foreach ( $this->ticket_ids as $ticket_id ) {
				add_post_meta( $post_id, self::$meta_prefix . 'ticket', $ticket_id );
			}

			foreach ( $this->post_ids as $related_post_id ) {
				add_post_meta( $post_id, self::$meta_prefix . 'post', $related_post_id );
			}
		}
	}

	/**
	 * Returns the Order status.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_status() {
		return $this->status;
	}
}