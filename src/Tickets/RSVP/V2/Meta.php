<?php
/**
 * Handles the meta for RSVP V2.
 *
 * Single source of truth for all RSVP V2 meta keys and constants.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Tickets\Commerce\Ticket as TC_Ticket;

/**
 * Meta class for RSVP V2.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Meta {

	/**
	 * The Tickets Commerce type for RSVP.
	 *
	 * Used for type discrimination in both _type meta and pinged column.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const TC_RSVP_TYPE = 'tc-rsvp';

	/**
	 * Meta key for storing the ticket type.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const TYPE_META_KEY = '_type';

	/**
	 * Meta key for storing the RSVP status on attendees.
	 *
	 * Values: 'yes' (going) or 'no' (not going).
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const RSVP_STATUS_KEY = '_tec_tickets_commerce_rsvp_status';

	/**
	 * Meta key for the "show not going" option on tickets.
	 *
	 * Values: 'yes' or 'no'.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const SHOW_NOT_GOING_KEY = '_tribe_ticket_show_not_going';

	/**
	 * Meta key for the "show attendees list" option (event-level).
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const SHOW_ATTENDEES_KEY = '_tec_show_attendees_list_rsvp';

	/**
	 * RSVP status value for "going".
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const STATUS_GOING = 'yes';

	/**
	 * RSVP status value for "not going".
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const STATUS_NOT_GOING = 'no';

	/**
	 * Gets the RSVP status for an attendee.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id The attendee post ID.
	 *
	 * @return string The RSVP status ('yes' or 'no'), defaults to 'yes'.
	 */
	public function get_rsvp_status( int $attendee_id ): string {
		$status = get_post_meta( $attendee_id, self::RSVP_STATUS_KEY, true );

		if ( empty( $status ) ) {
			return self::STATUS_GOING;
		}

		return $status;
	}

	/**
	 * Sets the RSVP status for an attendee.
	 *
	 * @since TBD
	 *
	 * @param int    $attendee_id The attendee post ID.
	 * @param string $status      The RSVP status ('yes' or 'no').
	 *
	 * @return bool Whether the meta was updated.
	 */
	public function set_rsvp_status( int $attendee_id, string $status ): bool {
		// Validate status value.
		if ( ! in_array( $status, [ self::STATUS_GOING, self::STATUS_NOT_GOING ], true ) ) {
			return false;
		}

		return (bool) update_post_meta( $attendee_id, self::RSVP_STATUS_KEY, $status );
	}

	/**
	 * Checks if a ticket is an RSVP ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return bool Whether the ticket is an RSVP ticket.
	 */
	public function is_rsvp_ticket( int $ticket_id ): bool {
		$post = get_post( $ticket_id );

		if ( ! $post || TC_Ticket::POSTTYPE !== $post->post_type ) {
			return false;
		}

		// Check the pinged column (primary type discriminator).
		if ( self::TC_RSVP_TYPE === $post->pinged ) {
			return true;
		}

		// Fallback to checking the _type meta.
		$type = get_post_meta( $ticket_id, self::TYPE_META_KEY, true );

		return self::TC_RSVP_TYPE === $type;
	}

	/**
	 * Checks if an attendee is an RSVP attendee.
	 *
	 * An attendee is considered an RSVP attendee if it has the RSVP status meta.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id The attendee post ID.
	 *
	 * @return bool Whether the attendee is an RSVP attendee.
	 */
	public function is_rsvp_attendee( int $attendee_id ): bool {
		// Check if the RSVP status meta key exists (not just has a value).
		$status = get_post_meta( $attendee_id, self::RSVP_STATUS_KEY, true );

		// If the meta key exists with any value, it's an RSVP attendee.
		return metadata_exists( 'post', $attendee_id, self::RSVP_STATUS_KEY );
	}

	/**
	 * Gets the "show not going" setting for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return bool Whether to show the "not going" option.
	 */
	public function get_show_not_going( int $ticket_id ): bool {
		$value = get_post_meta( $ticket_id, self::SHOW_NOT_GOING_KEY, true );

		// Handle both 'yes' (V1 format) and '1' (boolean-registered meta).
		return tribe_is_truthy( $value );
	}

	/**
	 * Sets the "show not going" setting for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int  $ticket_id      The ticket post ID.
	 * @param bool $show_not_going Whether to show the "not going" option.
	 *
	 * @return bool Whether the meta was updated.
	 */
	public function set_show_not_going( int $ticket_id, bool $show_not_going ): bool {
		$value = $show_not_going ? 'yes' : 'no';

		return (bool) update_post_meta( $ticket_id, self::SHOW_NOT_GOING_KEY, $value );
	}

	/**
	 * Gets the "show attendees list" setting for a post (event-level).
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return bool Whether to show the attendees list.
	 */
	public function get_show_attendees_list( int $post_id ): bool {
		$value = get_post_meta( $post_id, self::SHOW_ATTENDEES_KEY, true );

		return 'yes' === $value;
	}

	/**
	 * Sets the "show attendees list" setting for a post (event-level).
	 *
	 * @since TBD
	 *
	 * @param int  $post_id              The post ID.
	 * @param bool $show_attendees_list Whether to show the attendees list.
	 *
	 * @return bool Whether the meta was updated.
	 */
	public function set_show_attendees_list( int $post_id, bool $show_attendees_list ): bool {
		$value = $show_attendees_list ? 'yes' : 'no';

		return (bool) update_post_meta( $post_id, self::SHOW_ATTENDEES_KEY, $value );
	}
}
