<?php
/**
 * Null-object implementation of RSVP when the feature is disabled.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP;

use Tribe__Tickets__RSVP;

/**
 * Null-object implementation of RSVP when the feature is disabled.
 *
 * This class extends the full RSVP implementation and overrides methods
 * to return empty/null/zero values, ensuring code that depends on RSVP
 * continues to work without exceptions when the feature is disabled.
 *
 * @since TBD
 */
class RSVP_Disabled extends Tribe__Tickets__RSVP {

	/**
	 * Constructor - does not call parent to avoid side effects.
	 *
	 * @since TBD
	 */
	public function __construct() {
		// Do not call parent::__construct() to avoid registering post types, etc.
	}

	/**
	 * Returns empty array - no tickets when disabled.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id The post ID.
	 * @param string $context Optional context.
	 *
	 * @return array Empty array.
	 */
	public function get_tickets( $post_id, string $context = null ) {
		return [];
	}

	/**
	 * Returns null - no ticket when disabled.
	 *
	 * @since TBD
	 *
	 * @param int $event_id  The event ID.
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return null Always null.
	 */
	public function get_ticket( $event_id, $ticket_id ) {
		return null;
	}

	/**
	 * Returns empty array - no attendees when disabled.
	 *
	 * @since TBD
	 *
	 * @param int         $post_id   The post ID.
	 * @param string|null $post_type Optional post type.
	 *
	 * @return array Empty array.
	 */
	public function get_attendees_by_id( $post_id, $post_type = null ) {
		return [];
	}

	/**
	 * Returns false - no attendee when disabled.
	 *
	 * @since TBD
	 *
	 * @param mixed $attendee The attendee.
	 * @param int   $post_id  Optional post ID.
	 *
	 * @return false Always false.
	 */
	public function get_attendee( $attendee, $post_id = 0 ) {
		return false;
	}

	/**
	 * Returns 0 - no going count when disabled.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return int Always 0.
	 */
	public function get_attendees_count_going( $post_id ) {
		return 0;
	}

	/**
	 * Returns 0 - no not going count when disabled.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return int Always 0.
	 */
	public function get_attendees_count_not_going( $post_id ) {
		return 0;
	}

	/**
	 * Returns 0 - no going count for user when disabled.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 * @param int $user_id The user ID.
	 *
	 * @return int Always 0.
	 */
	public function get_attendees_count_going_for_user( $post_id, $user_id ) {
		return 0;
	}

	/**
	 * Returns 0 - no not going count for user when disabled.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 * @param int $user_id The user ID.
	 *
	 * @return int Always 0.
	 */
	public function get_attendees_count_not_going_for_user( $post_id, $user_id ) {
		return 0;
	}

	/**
	 * Returns 0 - no total not going when disabled.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event ID.
	 *
	 * @return int Always 0.
	 */
	public function get_total_not_going( $event_id ) {
		return 0;
	}

	/**
	 * Returns false - cannot save ticket when disabled.
	 *
	 * @since TBD
	 *
	 * @param int   $post_id  The post ID.
	 * @param mixed $ticket   The ticket.
	 * @param array $raw_data Raw data.
	 *
	 * @return false Always false.
	 */
	public function save_ticket( $post_id, $ticket, $raw_data = [] ) {
		return false;
	}

	/**
	 * Returns false - cannot delete ticket when disabled.
	 *
	 * @since TBD
	 *
	 * @param int $event_id  The event ID.
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return false Always false.
	 */
	public function delete_ticket( $event_id, $ticket_id ) {
		return false;
	}

	/**
	 * Returns false - cannot check in when disabled.
	 *
	 * @since TBD
	 *
	 * @param int        $attendee_id The attendee ID.
	 * @param mixed|null $qr          Optional QR data.
	 * @param int|null   $event_id    Optional event ID.
	 * @param array      $details     Optional details.
	 *
	 * @return false Always false.
	 */
	public function checkin( $attendee_id, $qr = null, $event_id = null, $details = [] ) {
		return false;
	}

	/**
	 * Returns false - cannot un-check in when disabled.
	 *
	 * @since TBD
	 *
	 * @param int  $attendee_id The attendee ID.
	 * @param bool $app         Whether from app.
	 *
	 * @return false Always false.
	 */
	public function uncheckin( $attendee_id, $app = false ) {
		return false;
	}

	/**
	 * Returns null - no event for ticket when disabled.
	 *
	 * @since TBD
	 *
	 * @param mixed $ticket_product The ticket product.
	 *
	 * @return null Always null.
	 */
	public function get_event_for_ticket( $ticket_product ) {
		return null;
	}

	/**
	 * Returns empty array - no order data when disabled.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return array Empty array.
	 */
	public function get_order_data( $order_id ) {
		return [];
	}

	/**
	 * Returns empty array - no messages when disabled.
	 *
	 * @since TBD
	 *
	 * @return array Empty array.
	 */
	public function get_messages() {
		return [];
	}

	/**
	 * Does nothing - no-op when disabled.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function init() {
		// No-op.
	}

	/**
	 * Does nothing - no-op when disabled.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_types() {
		// No-op.
	}

	/**
	 * Does nothing - no-op when disabled.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_resources() {
		// No-op.
	}

	/**
	 * Does nothing - no-op when disabled.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function enqueue_resources() {
		// No-op.
	}

	/**
	 * Throws exception - cannot create attendees when disabled.
	 *
	 * @since TBD
	 *
	 * @param mixed $ticket         The ticket.
	 * @param array $attendee_data  Attendee data.
	 *
	 * @throws \Exception Always throws because RSVP is disabled.
	 */
	public function create_attendee_for_ticket( $ticket, $attendee_data ) {
		throw new \Exception( __( 'Cannot create RSVP attendee: RSVP feature is disabled.', 'event-tickets' ) );
	}

	/**
	 * Returns empty array - no statuses by action when disabled.
	 *
	 * @since TBD
	 *
	 * @param string $action The action.
	 *
	 * @return array Empty array.
	 */
	public function get_statuses_by_action( $action ) {
		return [];
	}

	/**
	 * Returns false - not going option is disabled when RSVP is disabled.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return bool Always false.
	 */
	public function is_not_going_enabled( $ticket_id ): bool {
		return false;
	}
}
