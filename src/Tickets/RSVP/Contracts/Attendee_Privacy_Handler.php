<?php
/**
 * Attendee Privacy Handler interface.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\Contracts
 */

namespace TEC\Tickets\RSVP\Contracts;

use WP_Post;

/**
 * Interface Attendee_Privacy_Handler
 *
 * Defines the public API that RSVP attendee repositories must implement
 * to support GDPR privacy export and erasure operations.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\Contracts
 */
interface Attendee_Privacy_Handler {
	/**
	 * Get attendees by email address for privacy operations.
	 *
	 * Returns WP_Post objects to maintain backward compatibility with
	 * existing filter implementations that expect full post objects.
	 *
	 * @since TBD
	 *
	 * @param string $email    The email address to search for.
	 * @param int    $page     The page number (1-indexed).
	 * @param int    $per_page Number of results per page.
	 *
	 * @return array{
	 *     posts: WP_Post[],
	 *     has_more: bool
	 * } The attendees found, and whether there are more results to fetch.
	 */
	public function get_attendees_by_email( string $email, int $page, int $per_page ): array;

	/**
	 * Delete an attendee for privacy erasure.
	 *
	 * Returns the event ID so the caller can invalidate the attendees cache.
	 * This method will immediately delete the Attendee skipping trash.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id The attendee post ID to delete.
	 *
	 * @return array{
	 *     success: bool,
	 *     event_id: int|null
	 * } The success status and event ID, if applicable.
	 */
	public function delete_attendee( int $attendee_id ): array;

	/**
	 * Get the ticket/product ID for an attendee.
	 *
	 * Used by ET+ to retrieve custom meta fields configuration.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id The attendee post ID.
	 *
	 * @return int The ticket/product ID, or 0 if not found.
	 */
	public function get_ticket_id( int $attendee_id ): int;
}
