<?php

namespace TEC\Tickets\Commerce\Status;

/**
 * Class Status_Handler
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Status
 */
class Status_Handler extends \tad_DI52_ServiceProvider {
	/**
	 * Statuses registered.
	 *
	 * @since TBD
	 *
	 * @var Status_Interface[]
	 */
	protected $statuses = [];

	/**
	 * Which classes we will load for order statuses by default.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	protected $default_statuses = [
		Created::class,
		Completed::class,
		Denied::class,
		Not_Completed::class,
//		Pending::class,
//		Refunded::class,
//		Reversed::class,
//		Undefined::class,
//		Voided::class,
	];

	/**
	 * Which status every order will be created with.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $insert_status = Created::class;

	/**
	 * Sets up all the Status instances for the Classes registered in $default_statuses.
	 *
	 * @since TBD
	 */
	public function register() {
		foreach ( $this->default_statuses as $status_class ) {
			// Spawn the new instance.
			$status = new $status_class;

			// Register as a singleton for internal ease of use.
			$this->container->singleton( $status_class, $status );

			// Collect this particular status instance in this class.
			$this->register_status( $status );
		}

		$this->container->singleton( static::class, $this );
	}

	/**
	 * Gets the statuses registered.
	 *
	 * @since TBD
	 *
	 * @return Status_Interface[]
	 */
	public function get_insert_status() {
		return $this->statuses;
	}

	/**
	 * Gets the statuses registered.
	 *
	 * @since TBD
	 *
	 * @return Status_Interface[]
	 */
	public function get_all() {
		return $this->statuses;
	}

	/**
	 * Fetches the first status registered with a given slug.
	 *
	 * @since TBD
	 *
	 * @param string $slug
	 *
	 * @return Status_Interface|null
	 */
	public function get_by_slug( $slug ) {
		foreach ( $this->get_all() as $status ) {
			if ( $status->get_slug() === $slug ) {
				return $status;
			}
		}

		return null;
	}
	/**
	 * Fetches the first status registered with a given wp slug.
	 *
	 * @since TBD
	 *
	 * @param string $slug
	 *
	 * @return Status_Interface|null
	 */
	public function get_by_wp_slug( $slug ) {
		foreach ( $this->get_all() as $status ) {
			if ( $status->get_wp_slug() === $slug ) {
				return $status;
			}
		}

		return null;
	}

	/**
	 * Fetches the status registered with a given class.
	 *
	 * @since TBD
	 *
	 * @param string $class_name
	 *
	 * @return Status_Interface|null
	 */
	public function get_by_class( $class_name ) {
		foreach ( $this->get_all() as $status ) {
			$status_class = get_class( $status );

			if ( $status_class === $class_name ) {
				return $status;
			}
		}

		return null;
	}

	/**
	 * Using `wp_list_filter` fetches which Statuses match the flags and operator passed.
	 *
	 * @since TBD
	 *
	 * @param string|array $flags
	 * @param string       $operator
	 *
	 * @return Status_Interface[]
	 */
	public function get_by_flags( $flags, $operator = 'AND' ) {
		$statuses = wp_list_filter( $this->get_all(), (array) $flags, $operator );

		return $statuses;
	}

	/**
	 * Register a given status into the Handler.
	 *
	 * @since TBD
	 *
	 * @param Status_Interface $status Which status we are registering.
	 */
	public function register_status( Status_Interface $status ) {
		$this->statuses[] = $status;
	}

	/**
	 * Registers the post statuses with WordPress.
	 *
	 * @since TBD
	 */
	public function register_order_statuses() {

		$statuses = $this->get_all();

		foreach ( $statuses as $status ) {
			register_post_status(
				$status->get_wp_slug(),
				$status->get_wp_arguments()
			);
		}
	}

	/**
	 * Fires when a post is transitioned from one status to another so that we can make another hook that is namespaced.
	 *
	 * @since TBD
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 */
	public function transition_order_post_status_hooks( $new_status, $old_status, $post ) {
		$new_status = $this->get_by_wp_slug( $new_status );
		$old_status = $this->get_by_wp_slug( $old_status );

		if ( isset( $new_status, $old_status ) ) {
			return;
		}

		/**
		 * Fires when a post is transitioned from one status to another.
		 *
		 * @since TBD
		 *
		 * @param Status_Interface  $new_status New post status.
		 * @param Status_Interface  $old_status Old post status.
		 * @param \WP_Post $post       Post object.
		 */
		do_action( 'tec_tickets_commerce_order_status_transition', $new_status, $old_status, $post );

		/**
		 * Fires when a post is transitioned from one status to another.
		 *
		 * The dynamic portions of the hook name, `$new_status` and `$old_status`,
		 * refer to the old and new post statuses, respectively.
		 *
		 * @since TBD
		 *
		 * @param Status_Interface  $new_status New post status.
		 * @param Status_Interface  $old_status Old post status.
		 * @param \WP_Post $post       Post object.
		 */
		do_action( "tec_tickets_commerce_order_status_{$old_status->get_slug()}_to_{$new_status->get_slug()}", $new_status, $old_status, $post );

		/**
		 * Fires when a post is transitioned from one status to another.
		 *
		 * The dynamic portions of the hook name, `$new_status`, refer to the new post status.
		 *
		 * @since TBD
		 *
		 * @param Status_Interface  $new_status New post status.
		 * @param Status_Interface  $old_status Old post status.
		 * @param \WP_Post $post       Post object.
		 */
		do_action( "tec_tickets_commerce_order_status_{$new_status->get_slug()}", $new_status, $old_status, $post );
	}

	/**
	 * Whether an order status will mark a transaction as completed one way or another.
	 *
	 * A transaction might be completed because it successfully completed, because it
	 * was refunded or denied.
	 *
	 * @since  TBD
	 *
	 * @param string $payment_status
	 *
	 * @return bool
	 */
	public function is_complete_transaction_status( $payment_status ) {
		$statuses = $this->get_by_flags( [ 'count_completed', 'count_refunded' ], 'OR' );
		$statuses = array_map( static function ( $status ) {
			return $status->get_slug();
		}, $statuses );

		return in_array( $payment_status, $statuses, true );

	}

	/**
	 * Whether an order status will mark a transaction as generating revenue or not.
	 *
	 * @since TBD
	 *
	 * @param string $payment_status
	 *
	 * @return bool
	 */
	public function is_revenue_generating_status( $payment_status ) {
		$statuses = $this->get_by_flags( 'count_completed' );
		$statuses = array_map( static function ( $status ) {
			return $status->get_slug();
		}, $statuses );

		return in_array( $payment_status, $statuses, true );
	}
}