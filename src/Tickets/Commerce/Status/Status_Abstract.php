<?php

namespace TEC\Tickets\Commerce\Status;

use TEC\Tickets\Commerce;

/**
 * Class Status_Abstract
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Status
 */
abstract class Status_Abstract implements Status_Interface {

	/**
	 * Flags associated with this status. List of pre-existing flags:
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
	 * @since TBD
	 *
	 * @var array
	 */
	protected $flags = [];

	/**
	 * Which arguments will be used to register this Status with WordPress.
	 *
	 * @since TBD
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
	public function get_flags() {
		return $this->flags;
	}

	/**
	 * {@inheritdoc}
	 */
	public function has_flags( $flags, $operator = 'AND' ) {
		$intersection = array_intersect( (array) $flags, $this->get_flags() );

		if ( 'AND' === strtoupper( $operator ) ) {
			return count( $flags ) === count( $intersection );
		}

		return 0 < count( $intersection );
	}

	/**
	 * When trying to get a param that doesnt exist we test if it's a flag.
	 *
	 * @since TBD
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
	public function get_wp_arguments() {
		$this->setup_wp_arguments();

		$defaults = [

		];
		$arguments = array_merge( $defaults, $this->wp_arguments );

		return $arguments;
	}

	/**
	 * Allows the configuration of the wp arguments before getting it, specifically used for dynamic arguments like
	 * the ones that will require a translation.
	 *
	 * @since TBD
	 *
	 */
	protected function setup_wp_arguments() {
		$this->wp_arguments['label'] = $this->get_name();
		$this->wp_arguments['label_count'] = _n_noop( $this->get_name() . ' <span class="count">(%s)</span>', $this->get_name() . ' <span class="count">(%s)</span>', 'event-tickets' );
	}

	/**
	 * @todo These methods/props below need to be re-evaluated before release.
	 */

	/**
	 * Status  Quantity
	 *
	 * @var int
	 */
	protected $qty = 0;

	/**
	 * Status Line Total
	 *
	 * @var int
	 */
	protected $line_total = 0;

	/**
	 * Get this Status' Quantity of Tickets by Post Type
	 *
	 * @return int
	 */
	public function get_qty() {
		return $this->qty;
	}

	/**
	 * Add to the  Status' Order Quantity
	 *
	 * @param int $value
	 */
	public function add_qty( $value ) {
		$this->qty += $value;
	}

	/**
	 * Remove from the  Status' Order Quantity
	 *
	 * @param int $value
	 */
	public function remove_qty( $value ) {
		$this->qty -= $value;
	}

	/**
	 * Get  Status' Order Amount of all Orders for a Post Type
	 *
	 * @return int
	 */
	public function get_line_total() {
		return $this->line_total;
	}

	/**
	 * Add to the  Status' Line Total
	 *
	 * @param int $value
	 */
	public function add_line_total( $value ) {
		$this->line_total += $value;
	}

	/**
	 * Remove from the  Status' Line Total
	 *
	 * @param int $value
	 */
	public function remove_line_total( $value ) {
		$this->line_total -= $value;
	}

}