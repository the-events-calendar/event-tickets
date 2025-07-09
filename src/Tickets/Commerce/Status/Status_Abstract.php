<?php

namespace TEC\Tickets\Commerce\Status;

use TEC\Tickets\Commerce;

/**
 * Class Status_Abstract
 *
 * @since 5.1.9
 *
 * @package TEC\Tickets\Commerce\Status
 */
abstract class Status_Abstract implements Status_Interface {

	/**
	 * Flags associated with this status, important to remember that the Flag Actions will be triggered in the order
	 * that these flags are returned.
	 *
	 * @see   static::filter_get_flags
	 *
	 * List of pre-existing flags from TPP/Tribe Commerce:
	 *
	 * - incomplete
	 * - warning
	 * - attendee_generation
	 * - attendee_dispatch
	 * - stock_reduced
	 * - count_attendee
	 * - count_sales
	 * - count_completed
	 * - count_canceled
	 * - count_incomplete
	 * - count_refunded
	 * - count_not_going
	 *
	 * @since 5.1.9
	 *
	 * @var string[]
	 */
	protected $flags = [];

	/**
	 * Which arguments will be used to register this Status with WordPress.
	 *
	 * @since 5.1.9
	 *
	 * @var array
	 */
	protected $wp_arguments = [

	];

	/**
	 * {@inheritdoc}
	 */
	public function get_slug() {
		return static::SLUG;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_wp_slug() {
		return 'tec-' . Commerce::ABBR . '-' . static::SLUG;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_flags( \WP_Post $post = null ) {
		return $this->filter_get_flags( $this->flags, $post );
	}

	/**
	 * {@inheritdoc}
	 */
	public function filter_get_flags( $flags, \WP_Post $post = null ) {
		/**
		 * Allows filtering of which flags are associated with this Status.
		 *
		 * @since 5.1.9
		 *
		 * @param string[] $flags  Set of flags we will use.
		 * @param \WP_Post $post   Which order we are testing against.
		 * @param static   $status Which status these flags are associated with.
		 */
		$flags = apply_filters( 'tec_tickets_commerce_order_status_get_flags', $flags, $post, $this );

		/**
		 * Allows filtering of which flags are associated with this Status.
		 *
		 * @since 5.1.9
		 *
		 * @param string[] $flags  Set of flags we will use.
		 * @param \WP_Post $post   Which order we are testing against.
		 * @param static   $status Which status these flags are associated with.
		 */
		return apply_filters( "tec_tickets_commerce_order_status_{$this->get_slug()}_get_flags", $flags, $post, $this );
	}

	/**
	 * {@inheritdoc}
	 */
	public function has_flags( $flags, $operator = 'AND', \WP_Post $post = null ) {
		$intersection = array_intersect( (array) $flags, $this->get_flags( $post ) );

		if ( 'AND' === strtoupper( $operator ) ) {
			return count( $flags ) === count( $intersection );
		}

		return 0 < count( $intersection );
	}

	/**
	 * When trying to get a param that doesnt exist we test if it's a flag.
	 *
	 * @since 5.1.9
	 *
	 * @param string $name Which flag to check.
	 *
	 * @return bool
	 */
	public function __get( $name ) {
		return $this->has_flags( $name );
	}

	/**
	 * {@inheritdoc}
	 */
	public function can_apply_to( $order, $new_status ) {
		$order = tec_tc_get_order( $order, OBJECT, 'raw', true );
		if ( ! $order instanceof \WP_Post ) {
			return false;
		}

		$current_status = tribe( Status_Handler::class )->get_by_wp_slug( $order->post_status );

		if ( $current_status->get_wp_slug() === $new_status->get_wp_slug() ) {
			// Allow from refunded to refunded in order to support multiple refunds.
			return 'refunded' === $current_status->get_slug();
		}

		if ( $current_status->is_final() ) {
			return false;
		}

		return true;
	}

	/**
	 * Whether a Status Interface can be changed to another Status Interface.
	 *
	 * @since 5.18.1
	 *
	 * @param self $new_status The new status.
	 *
	 * @return bool Whether the new status can be applied to the current status.
	 */
	public function can_change_to( $new_status ): bool {
		if ( $this->get_wp_slug() === $new_status->get_wp_slug() ) {
			return false;
		}

		if ( $this->is_final() ) {
			return false;
		}

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function is_final() {
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_wp_arguments() {
		$this->setup_wp_arguments();

		$defaults  = [

		];
		$arguments = array_merge( $defaults, $this->wp_arguments );

		return $this->filter_wp_arguments( $arguments );
	}

	/**
	 * {@inheritdoc}
	 */
	public function filter_wp_arguments( array $arguments = [] ) {
		/**
		 * Allows filtering of which arguments are associated with this Status registering in WP.
		 *
		 * @since 5.1.9
		 *
		 * @param array  $arguments Which arguments we are passing.
		 * @param static $status    Which status these arguments are associated with.
		 */
		$arguments = apply_filters( 'tec_tickets_commerce_order_status_get_wp_arguments', $arguments, $this );

		/**
		 * Allows filtering of which arguments are associated with this Status registering in WP.
		 *
		 * @since 5.1.9
		 *
		 * @param array  $arguments Which arguments we are passing.
		 * @param static $status    Which status these arguments are associated with.
		 */
		return apply_filters( "tec_tickets_commerce_order_status_{$this->get_slug()}_get_wp_arguments", $arguments, $this );
	}

	/**
	 * Allows the configuration of the wp arguments before getting it, specifically used for dynamic arguments like
	 * the ones that will require a translation.
	 *
	 * @since 5.1.9
	 */
	protected function setup_wp_arguments() {
		$this->wp_arguments['label']       = $this->get_name();
		$this->wp_arguments['label_count'] = _n_noop( $this->get_name() . ' <span class="count">(%s)</span>', $this->get_name() . ' <span class="count">(%s)</span>', 'event-tickets' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function can_be_updated_to(): array {
		return [];
	}
	/**
	 * {@inheritdoc}
	 */
	public function required_previous_status(): array {
		return [];
	}
}
