<?php

namespace TEC\Tickets\Commerce\Status;

/**
 * Class Status_Interface
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce\Status
 */
interface Status_Interface {
	/**
	 * Gets the slug of this status in WordPress
	 *
	 * @since 5.1.9
	 *
	 * @return string
	 */
	public function get_wp_slug();

	/**
	 * Filters and returns the flags for the get_flags method.
	 *
	 * @since 5.1.9
	 *
	 * @param string[] $flags Which flags will be filtered.
	 * @param \WP_Post $post  Which order we are testing against.
	 *
	 * @return string[]
	 */
	public function filter_get_flags( $flags, \WP_Post $post = null );

	/**
	 * Gets the name of this status.
	 *
	 * @since 5.1.9
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Gets the constant slug of this status.
	 *
	 * @since 5.1.9
	 *
	 * @return string
	 */
	public function get_slug();

	/**
	 * Gets the flags associated with this status.
	 *
	 * @since 5.1.9
	 *
	 * @param \WP_Post $post Which order we are testing against.
	 *
	 * @return array
	 */
	public function get_flags( \WP_Post $post = null );

	/**
	 * Determines if this Status has a set of flags.
	 *
	 * @since 5.1.9
	 *
	 * @param array|string $flags    Which flags we are testing.
	 * @param string       $operator Operator for the test.
	 * @param \WP_Post     $post     Which order we are testing against.
	 *
	 * @return bool
	 */
	public function has_flags( $flags, $operator = 'AND', \WP_Post $post = null );

	/**
	 * Determines if a given order can be modified to this status.
	 *
	 * @since 5.3.1    $new_status parameter introduced
	 * @since 5.1.9
	 *
	 * @param int|\WP_Post    $order      Which order we are testing against.
	 * @param Status_Abstract $new_status The status to transition to.
	 *
	 * @return boolean|\WP_Error
	 */
	public function can_apply_to( $order, $new_status );

	/**
	 * Filters the WP arguments used to register the status.
	 *
	 * @since 5.1.9
	 *
	 * @param array $arguments Which arguments we are passing.
	 *
	 * @return array
	 */
	public function filter_wp_arguments( array $arguments = [] );

	/**
	 * Fetches the WordPress arguments required to register this Status.
	 *
	 * @since 5.1.9
	 *
	 * @return array
	 */
	public function get_wp_arguments();

	/**
	 * Whether an order in this status can be transitioned away from it.
	 *
	 * @since 5.3.0
	 *
	 * @return bool
	 */
	public function is_final();

	/**
	 * An array of the statuses this status can be updated to.
	 *
	 * @since 5.13.3
	 *
	 * @return array<Status_Abstract>
	 */
	public function can_be_updated_to(): array;

	/**
	 * An array of the statuses this status needs to be have-had before it can be updated to.
	 *
	 * @since 5.19.3
	 *
	 * @return array<Status_Abstract>
	 */
	public function required_previous_status(): array;
}
