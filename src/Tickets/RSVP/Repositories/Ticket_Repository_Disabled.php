<?php
/**
 * Null-object implementation of RSVP Ticket Repository.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP\Repositories;

use Tribe__Repository;

/**
 * Null-object implementation of RSVP Ticket Repository when the feature is disabled.
 *
 * This class extends the repository base class but overrides methods to return empty results,
 * ensuring code that depends on the repository continues to work without exceptions.
 *
 * @since TBD
 */
class Ticket_Repository_Disabled extends Tribe__Repository {

	/**
	 * Constructor - does not call parent to avoid side effects.
	 *
	 * @since TBD
	 */
	public function __construct() {
		// Do not call parent::__construct() to avoid side effects.
		$this->filter_name = 'rsvp_tickets_disabled';
	}

	/**
	 * Returns empty collection - no tickets when disabled.
	 *
	 * @since TBD
	 *
	 * @param bool $return_generator Whether to return a generator.
	 * @param int  $batch_size       The batch size for generator.
	 *
	 * @return array Empty array.
	 */
	public function all( $return_generator = false, int $batch_size = 50 ) {
		return [];
	}

	/**
	 * Returns 0 - no tickets when disabled.
	 *
	 * @since TBD
	 *
	 * @return int Always 0.
	 */
	public function count() {
		return 0;
	}

	/**
	 * Returns 0 - no tickets when disabled.
	 *
	 * @since TBD
	 *
	 * @return int Always 0.
	 */
	public function found() {
		return 0;
	}

	/**
	 * Returns null - no ticket when disabled.
	 *
	 * @since TBD
	 *
	 * @return null Always null.
	 */
	public function first() {
		return null;
	}

	/**
	 * Returns null - no ticket when disabled.
	 *
	 * @since TBD
	 *
	 * @return null Always null.
	 */
	public function last() {
		return null;
	}

	/**
	 * Returns null - no ticket when disabled.
	 *
	 * @since TBD
	 *
	 * @param int $id The ticket ID.
	 *
	 * @return null Always null.
	 */
	public function by_primary_key( $id ) {
		return null;
	}

	/**
	 * Returns $this for method chaining - no-op when disabled.
	 *
	 * @since TBD
	 *
	 * @param string $key   The filter key.
	 * @param mixed  $value The filter value.
	 *
	 * @return $this
	 */
	public function by( $key, $value = null ) {
		return $this;
	}

	/**
	 * Returns $this for method chaining - no-op when disabled.
	 *
	 * @since TBD
	 *
	 * @param string $key   The filter key.
	 * @param mixed  $value The filter value.
	 *
	 * @return $this
	 */
	public function where( $key, $value = null ) {
		return $this;
	}

	/**
	 * Returns empty array - no tickets when disabled.
	 *
	 * @since TBD
	 *
	 * @param bool $return_generator Whether to return a generator.
	 * @param int  $batch_size       The batch size for generator.
	 *
	 * @return array Empty array.
	 */
	public function get_ids( $return_generator = false, int $batch_size = 50 ) {
		return [];
	}
}
