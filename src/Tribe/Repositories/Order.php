<?php

namespace Tribe\Tickets\Repositories;

use Tribe__Repository;

/**
 * The repository functionality for Ticket Orders.
 *
 * @since TBD
 */
class Order extends Tribe__Repository {

	/**
	 * The unique fragment that will be used to identify this repository filters.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $filter_name = 'tickets-orders';

	/**
	 * Key name to use when limiting lists of keys.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $key_name = '';

	/**
	 * The attendee provider object used to interact with the Order.
	 *
	 * @since TBD
	 *
	 * @var Tribe__Tickets__Tickets
	 */
	public $attendee_provider;

	/**
	 * The list of supported order statuses.
	 *
	 * @since TBD
	 *
	 * @var array An array of all the order statuses supported by the repository.
	 */
	protected static $order_statuses;

	/**
	 * The list of supported public order statuses.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected static $public_order_statuses;

	/**
	 * The list of supported private order statuses.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected static $private_order_statuses;

	/**
	 * Tribe__Tickets__Attendee_Repository constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		parent::__construct();

		// The extending repository must specify $this->default_args['post_type'] and set it to a valid post type.
		$this->default_args = array_merge( $this->default_args, [
			'post_type'   => '_no_order_type_set',
			'orderby'     => [ 'date', 'title', 'ID' ],
			'post_status' => 'any',
		] );

		$this->init_order_statuses();
	}

	/**
	 * Initialize the order statuses needed for the Orders repository.
	 *
	 * @since TBD
	 */
	protected function init_order_statuses() {
		// Statuses already generated.
		if ( ! empty( self::$order_statuses ) ) {
			return;
		}

		/** @var Tribe__Tickets__Status__Manager $status_mgr */
		$status_mgr = tribe( 'tickets.status' );

		/**
		 * Allow filtering the list of all order statuses supported by the Orders repository.
		 *
		 * @since TBD
		 *
		 * @param array $statuses List of all order statuses.
		 */
		$statuses = apply_filters( 'tribe_tickets_repositories_order_statuses', [] );

		// Enforce lowercase for comparison purposes.
		$statuses = array_map( 'strtolower', $statuses );

		// Prevent unnecessary duplicates.
		$statuses = array_unique( $statuses );

		// Store for reuse.
		self::$order_statuses = $statuses;

		/**
		 * Allow filtering the list of public order statuses supported by the Orders repository.
		 *
		 * @since TBD
		 *
		 * @param array $public_order_statuses List of public order statuses.
		 */
		self::$public_order_statuses = apply_filters( 'tribe_tickets_repositories_order_public_statuses', [] );

		// Set up the initial private order statuses.
		$private_order_statuses = array_diff( self::$order_statuses, self::$public_order_statuses );

		/**
		 * Allow filtering the list of private order statuses supported by the Orders repository.
		 *
		 * @since TBD
		 *
		 * @param array $private_order_statuses List of private order statuses.
		 */
		self::$private_order_statuses = apply_filters( 'tribe_tickets_repositories_order_private_statuses', $private_order_statuses );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return WP_Post|false The new post object or false if unsuccessful.
	 */
	public function create() {
		// Extended repositories must handle their own order creation.
		return false;
	}
}
