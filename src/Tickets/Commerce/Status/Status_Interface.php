<?php

namespace TEC\Tickets\Commerce\Status;

/**
 * Class Status_Interface
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Status
 */
interface Status_Interface {
	/**
	 * Gets the slug of this status in WordPress
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_wp_slug();

	/**
	 * Filters and returns the flags for the get_flags method.
	 *
	 * @since TBD
	 *
	 * @param string[] $flags Which flags will be filtered.
	 *
	 * @return string[]
	 */
	public function filter_get_flags( $flags );

	/**
	 * Gets the name of this status.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Gets the constant slug of this status.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_slug();

	/**
	 * Gets the flags associated with this status.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_flags();

	/**
	 * Determines if this Status has a set of flags.
	 *
	 * @since TBD
	 *
	 * @param array|string $flags    Which flags we are testing.
	 * @param string       $operator Operator for the test.
	 *
	 * @return bool
	 */
	public function has_flags( $flags, $operator = 'AND' );

	/**
	 * Fetches the WordPress arguments required to register this Status.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_wp_arguments();
}