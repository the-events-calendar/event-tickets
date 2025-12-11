<?php
/**
 * Handles RSVP V2 attendee operations.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Tickets\Commerce\Attendee as TC_Attendee;
use TEC\Tickets\RSVP\V2\Traits\Is_RSVP;
use WP_Error;
use WP_Post;

/**
 * Class Attendee.
 *
 * Handles creation and status management for RSVP attendees.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Attendee {
	use Is_RSVP;

	/**
	 * Creates a new RSVP attendee.
	 *
	 * @since TBD
	 *
	 * @param int   $order_id  The order ID this attendee belongs to.
	 * @param int   $ticket_id The ticket ID for this attendee.
	 * @param array $args      The attendee arguments.
	 *                         - event_id: (required) The event/post ID.
	 *                         - name: (optional) Attendee name.
	 *                         - email: (optional) Attendee email.
	 *                         - rsvp_status: (optional) RSVP status ('yes' or 'no'). Default 'yes'.
	 *                         - optout: (optional) Whether to opt out of attendee list. Default false.
	 *
	 * @return int|WP_Error The attendee ID on success, WP_Error on failure.
	 */
	public function create( int $order_id, int $ticket_id, array $args ) {
		$order = get_post( $order_id );

		if ( ! $order ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_invalid_order',
				__( 'Invalid order ID.', 'event-tickets' )
			);
		}

		$ticket = get_post( $ticket_id );

		if ( ! $ticket ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_invalid_ticket',
				__( 'Invalid ticket ID.', 'event-tickets' )
			);
		}

		if ( empty( $args['event_id'] ) ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_missing_event',
				__( 'Event ID is required.', 'event-tickets' )
			);
		}

		$event_id = absint( $args['event_id'] );
		$event    = get_post( $event_id );

		if ( ! $event ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_invalid_event',
				__( 'Invalid event ID.', 'event-tickets' )
			);
		}

		// Generate unique security code.
		$security_code = substr( md5( wp_generate_password( 32, true, true ) . $order_id . $ticket_id . time() ), 0, 10 );

		/**
		 * Fires before an RSVP attendee ticket is created.
		 *
		 * V1 backwards compatibility hook.
		 *
		 * @since TBD
		 *
		 * @param int   $event_id  The event ID.
		 * @param int   $ticket_id The ticket ID.
		 * @param array $args      The attendee arguments.
		 */
		do_action( 'tribe_tickets_rsvp_before_attendee_ticket_creation', $event_id, $ticket_id, $args );

		$attendee_args = [
			'post_status' => 'publish',
			'post_type'   => TC_Attendee::POSTTYPE,
			'post_author' => get_current_user_id(),
			'post_title'  => $this->get_attendee_title( $args, $ticket ),
			'post_parent' => $order_id,
		];

		$attendee_id = wp_insert_post( $attendee_args );

		if ( is_wp_error( $attendee_id ) ) {
			return $attendee_id;
		}

		// Set relationship meta.
		update_post_meta( $attendee_id, TC_Attendee::$event_relation_meta_key, $event_id );
		update_post_meta( $attendee_id, TC_Attendee::$ticket_relation_meta_key, $ticket_id );
		update_post_meta( $attendee_id, TC_Attendee::$order_relation_meta_key, $order_id );

		// Set purchaser info.
		$name  = isset( $args['name'] ) ? sanitize_text_field( $args['name'] ) : '';
		$email = isset( $args['email'] ) ? sanitize_email( $args['email'] ) : '';
		update_post_meta( $attendee_id, TC_Attendee::$purchaser_name_meta_key, $name );
		update_post_meta( $attendee_id, TC_Attendee::$purchaser_email_meta_key, $email );

		// Set security code.
		update_post_meta( $attendee_id, TC_Attendee::$security_code_meta_key, $security_code );

		// Set optout status.
		$optout = isset( $args['optout'] ) && $args['optout'];
		update_post_meta( $attendee_id, TC_Attendee::$optout_meta_key, $optout ? 'yes' : 'no' );

		// Set RSVP status (the key differentiator from regular TC attendees).
		$rsvp_status = $args['rsvp_status'] ?? Meta::STATUS_GOING;
		if ( ! in_array( $rsvp_status, [ Meta::STATUS_GOING, Meta::STATUS_NOT_GOING ], true ) ) {
			$rsvp_status = Meta::STATUS_GOING;
		}
		update_post_meta( $attendee_id, Meta::RSVP_STATUS_KEY, $rsvp_status );

		// Set checked-in status to false.
		update_post_meta( $attendee_id, TC_Attendee::$checked_in_meta_key, false );

		/**
		 * Fires after an RSVP V2 attendee is created.
		 *
		 * @since TBD
		 *
		 * @param int   $attendee_id The attendee ID.
		 * @param int   $order_id    The order ID.
		 * @param int   $ticket_id   The ticket ID.
		 * @param int   $event_id    The event ID.
		 * @param array $args        The attendee arguments.
		 */
		do_action( 'tec_tickets_rsvp_v2_attendee_created', $attendee_id, $order_id, $ticket_id, $event_id, $args );

		/**
		 * Fires after an RSVP attendee is created.
		 *
		 * V1 backwards compatibility hook.
		 *
		 * @since TBD
		 *
		 * @param int $attendee_id The attendee ID.
		 * @param int $event_id    The event/post ID.
		 * @param int $order_id    The order ID.
		 * @param int $ticket_id   The ticket/product ID.
		 */
		do_action( 'event_tickets_rsvp_attendee_created', $attendee_id, $event_id, $order_id, $ticket_id );

		/**
		 * Fires after an RSVP attendee ticket is created.
		 *
		 * V1 backwards compatibility hook. Note: This hook name is confusing because
		 * it refers to attendee creation, not ticket creation.
		 *
		 * @since TBD
		 *
		 * @param int $attendee_id       The attendee ID.
		 * @param int $event_id          The event/post ID.
		 * @param int $ticket_id         The ticket/product ID.
		 * @param int $order_attendee_id The order/attendee ID (same as attendee_id in V2).
		 */
		do_action( 'event_tickets_rsvp_ticket_created', $attendee_id, $event_id, $ticket_id, $attendee_id );

		return $attendee_id;
	}

	/**
	 * Gets the RSVP status for an attendee.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id The attendee post ID.
	 *
	 * @return string The RSVP status ('yes' or 'no'), or empty string if not an RSVP attendee.
	 */
	public function get_status( int $attendee_id ): string {
		if ( ! $this->is_rsvp_attendee( $attendee_id ) ) {
			return '';
		}

		$status = get_post_meta( $attendee_id, Meta::RSVP_STATUS_KEY, true );

		return $status ?: Meta::STATUS_GOING;
	}

	/**
	 * Sets the RSVP status for an attendee.
	 *
	 * @since TBD
	 *
	 * @param int    $attendee_id The attendee post ID.
	 * @param string $status      The RSVP status ('yes' or 'no').
	 *
	 * @return bool Whether the status was updated.
	 */
	public function set_status( int $attendee_id, string $status ): bool {
		if ( ! in_array( $status, [ Meta::STATUS_GOING, Meta::STATUS_NOT_GOING ], true ) ) {
			return false;
		}

		return (bool) update_post_meta( $attendee_id, Meta::RSVP_STATUS_KEY, $status );
	}

	/**
	 * Changes the RSVP status for an attendee with capacity checking.
	 *
	 * @since TBD
	 *
	 * @param int    $attendee_id The attendee post ID.
	 * @param string $new_status  The new RSVP status ('yes' or 'no').
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function change_status( int $attendee_id, string $new_status ) {
		if ( ! $this->is_rsvp_attendee( $attendee_id ) ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_not_rsvp_attendee',
				__( 'This attendee is not an RSVP attendee.', 'event-tickets' )
			);
		}

		if ( ! in_array( $new_status, [ Meta::STATUS_GOING, Meta::STATUS_NOT_GOING ], true ) ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_invalid_status',
				__( 'Invalid RSVP status.', 'event-tickets' )
			);
		}

		$current_status = $this->get_status( $attendee_id );

		// No change needed.
		if ( $current_status === $new_status ) {
			return true;
		}

		$ticket_id = get_post_meta( $attendee_id, TC_Attendee::$ticket_relation_meta_key, true );

		if ( ! $ticket_id ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_no_ticket',
				__( 'Could not find associated ticket.', 'event-tickets' )
			);
		}

		$ticket = tribe( Ticket::class );

		// If changing to "going", check capacity.
		if ( Meta::STATUS_GOING === $new_status ) {
			$available = $ticket->get_available( (int) $ticket_id );

			// -1 means unlimited capacity.
			if ( -1 !== $available && $available < 1 ) {
				return new WP_Error(
					'tec_tickets_rsvp_v2_no_capacity',
					__( 'This RSVP has reached capacity.', 'event-tickets' )
				);
			}
		}

		// Update the status.
		$updated = $this->set_status( $attendee_id, $new_status );

		if ( ! $updated ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_status_update_failed',
				__( 'Failed to update RSVP status.', 'event-tickets' )
			);
		}

		// Adjust stock based on status change.
		if ( Meta::STATUS_GOING === $new_status ) {
			// Changed from not-going to going: decrease available.
			$ticket->update_stock( (int) $ticket_id, 1, 'decrease' );
		} else {
			// Changed from going to not-going: increase available.
			$ticket->update_stock( (int) $ticket_id, 1, 'increase' );
		}

		/**
		 * Fires after an RSVP V2 attendee status is changed.
		 *
		 * @since TBD
		 *
		 * @param int    $attendee_id    The attendee ID.
		 * @param string $new_status     The new RSVP status.
		 * @param string $current_status The previous RSVP status.
		 * @param int    $ticket_id      The ticket ID.
		 */
		do_action( 'tec_tickets_rsvp_v2_status_changed', $attendee_id, $new_status, $current_status, (int) $ticket_id );

		// Get event ID for V1 hook.
		$event_id = get_post_meta( $attendee_id, TC_Attendee::$event_relation_meta_key, true );

		/**
		 * Fires after an RSVP attendee is updated.
		 *
		 * V1 backwards compatibility hook.
		 *
		 * @since TBD
		 *
		 * @param int    $attendee_id The attendee ID.
		 * @param int    $event_id    The event/post ID.
		 * @param string $new_status  The new attendee status.
		 */
		do_action( 'event_tickets_rsvp_after_attendee_update', $attendee_id, (int) $event_id, $new_status );

		return true;
	}

	/**
	 * Gets attendees for an order.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order post ID.
	 *
	 * @return WP_Post[] Array of attendee posts.
	 */
	public function get_by_order( int $order_id ): array {
		$attendees = get_posts(
			[
				'post_type'      => TC_Attendee::POSTTYPE,
				'post_parent'    => $order_id,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			] 
		);

		// Filter to only RSVP attendees.
		return array_filter(
			$attendees,
			function ( $attendee ) {
				return $this->is_rsvp_attendee( $attendee->ID );
			} 
		);
	}

	/**
	 * Gets attendees for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int    $ticket_id   The ticket post ID.
	 * @param string $rsvp_status Optional. Filter by RSVP status ('yes', 'no', or empty for all).
	 *
	 * @return WP_Post[] Array of attendee posts.
	 */
	public function get_by_ticket( int $ticket_id, string $rsvp_status = '' ): array {
		$meta_query = [
			[
				'key'   => TC_Attendee::$ticket_relation_meta_key,
				'value' => $ticket_id,
			],
			[
				'key'     => Meta::RSVP_STATUS_KEY,
				'compare' => 'EXISTS',
			],
		];

		if ( $rsvp_status && in_array( $rsvp_status, [ Meta::STATUS_GOING, Meta::STATUS_NOT_GOING ], true ) ) {
			$meta_query[] = [
				'key'   => Meta::RSVP_STATUS_KEY,
				'value' => $rsvp_status,
			];
		}

		return get_posts(
			[
				'post_type'      => TC_Attendee::POSTTYPE,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'meta_query'     => $meta_query,
			] 
		);
	}

	/**
	 * Gets the attendee title.
	 *
	 * @since TBD
	 *
	 * @param array   $args   The attendee arguments.
	 * @param WP_Post $ticket The ticket post.
	 *
	 * @return string The attendee title.
	 */
	private function get_attendee_title( array $args, WP_Post $ticket ): string {
		$name = isset( $args['name'] ) ? sanitize_text_field( $args['name'] ) : '';

		if ( $name ) {
			return sprintf(
				/* translators: %1$s: Attendee name, %2$s: Ticket title */
				__( '%1$s | %2$s', 'event-tickets' ),
				$name,
				$ticket->post_title
			);
		}

		return $ticket->post_title;
	}
}
