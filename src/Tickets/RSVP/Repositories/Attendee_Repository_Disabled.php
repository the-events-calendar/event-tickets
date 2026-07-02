<?php
/**
 * Null-object implementation of RSVP Attendee Repository.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP\Repositories;

use TEC\Tickets\RSVP\Contracts\Attendee_Repository_Interface;
use Tribe__Repository;

/**
 * Null-object implementation of RSVP Attendee Repository when the feature is disabled.
 *
 * This class extends the repository base class but overrides methods to return empty results,
 * ensuring code that depends on the repository continues to work without exceptions.
 *
 * @since TBD
 */
class Attendee_Repository_Disabled extends Tribe__Repository implements Attendee_Repository_Interface {
	/**
	 * Constructor - does not call parent to avoid side effects.
	 *
	 * @since TBD
	 */
	public function __construct() {
		// Do not call parent::__construct() to avoid side effects.
		$this->filter_name = 'rsvp_attendees_disabled';
	}

	/**
	 * Returns empty collection - no attendees when disabled.
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
	 * Returns 0 - no attendees when disabled.
	 *
	 * @since TBD
	 *
	 * @return int Always 0.
	 */
	public function count() {
		return 0;
	}

	/**
	 * Returns 0 - no attendees when disabled.
	 *
	 * @since TBD
	 *
	 * @return int Always 0.
	 */
	public function found() {
		return 0;
	}

	/**
	 * Returns null - no attendee when disabled.
	 *
	 * @since TBD
	 *
	 * @return null Always null.
	 */
	public function first() {
		return null;
	}

	/**
	 * Returns null - no attendee when disabled.
	 *
	 * @since TBD
	 *
	 * @return null Always null.
	 */
	public function last() {
		return null;
	}

	/**
	 * Returns null - no attendee when disabled.
	 *
	 * @since TBD
	 *
	 * @param int $id The attendee ID.
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
	 * Returns empty array - no attendees when disabled.
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

	/**
	 * Get attendees by email - returns empty when disabled.
	 *
	 * @since TBD
	 *
	 * @param string $email    The email address to search for.
	 * @param int    $page     The page number (1-indexed).
	 * @param int    $per_page Number of results per page.
	 *
	 * @return array{posts: \WP_Post[], has_more: bool}
	 */
	public function get_attendees_by_email( string $email, int $page, int $per_page ): array {
		return [
			'posts'    => [],
			'has_more' => false,
		];
	}

	/**
	 * Delete an attendee - no-op when disabled.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id The attendee post ID to delete.
	 *
	 * @return array{success: bool, event_id: int|null}
	 */
	public function delete_attendee( int $attendee_id ): array {
		return [
			'success'  => false,
			'event_id' => null,
		];
	}

	/**
	 * Get ticket ID - returns 0 when disabled.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id The attendee post ID.
	 *
	 * @return int Always 0.
	 */
	public function get_ticket_id( int $attendee_id ): int {
		return 0;
	}

	/**
	 * Get field value: returns empty string when disabled.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id The attendee post ID to return the field for.
	 * @param string $field   The field to return the value for.
	 *
	 * @return string Always an empty string.
	 */
	public function get_field( int $post_id, string $field ) {
		return '';
	}
}
