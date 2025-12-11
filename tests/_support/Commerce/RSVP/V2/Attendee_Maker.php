<?php

namespace TEC\Tickets\Test\Commerce\RSVP\V2;

use TEC\Tickets\Commerce\Attendee as TC_Attendee;
use TEC\Tickets\RSVP\V2\Meta;

/**
 * Trait Attendee_Maker
 *
 * Provides utility methods for creating RSVP V2 attendees in tests.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Test\Commerce\RSVP\V2
 */
trait Attendee_Maker {
	/**
	 * Creates an RSVP V2 attendee.
	 *
	 * @param int    $ticket_id   The ticket ID.
	 * @param int    $post_id     The post ID (event ID).
	 * @param string $rsvp_status Optional. The RSVP status. Defaults to Meta::STATUS_GOING.
	 *
	 * @return int The created attendee ID.
	 */
	protected function create_rsvp_attendee( int $ticket_id, int $post_id, string $rsvp_status = null ): int {
		if ( null === $rsvp_status ) {
			$rsvp_status = Meta::STATUS_GOING;
		}

		$attendee_id = static::factory()->post->create( [
			'post_type'   => TC_Attendee::POSTTYPE,
			'post_status' => 'publish',
		] );

		update_post_meta( $attendee_id, TC_Attendee::$ticket_relation_meta_key, $ticket_id );
		update_post_meta( $attendee_id, TC_Attendee::$event_relation_meta_key, $post_id );
		update_post_meta( $attendee_id, Meta::RSVP_STATUS_KEY, $rsvp_status );

		return $attendee_id;
	}
}
