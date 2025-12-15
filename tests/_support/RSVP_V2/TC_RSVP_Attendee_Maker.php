<?php
/**
 * Trait for creating TC-RSVP attendees in tests.
 *
 * @since TBD
 *
 * @package Tribe\Tickets\Test\RSVP_V2
 */

namespace Tribe\Tickets\Test\RSVP_V2;

use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\RSVP\V2\Repositories\Attendee_Repository;

/**
 * Trait TC_RSVP_Attendee_Maker
 *
 * Provides methods to create TC-RSVP attendees for testing.
 * TC-RSVP attendees are Tickets Commerce attendees with RSVP status meta.
 *
 * @since TBD
 *
 * @package Tribe\Tickets\Test\RSVP_V2
 */
trait TC_RSVP_Attendee_Maker {

	/**
	 * Counter for generating unique attendee titles.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected static int $tc_rsvp_attendee_counter = 0;

	/**
	 * Creates a TC-RSVP attendee for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int   $ticket_id The ticket ID.
	 * @param int   $event_id  The event/post ID.
	 * @param array $overrides An array of values to override defaults.
	 *
	 * @return int The generated attendee post ID.
	 */
	protected function create_tc_rsvp_attendee( int $ticket_id, int $event_id, array $overrides = [] ): int {
		$rsvp_status = $overrides['rsvp_status'] ?? 'yes';
		$full_name   = $overrides['full_name'] ?? 'Test Attendee ' . self::$tc_rsvp_attendee_counter;
		$email       = $overrides['email'] ?? 'attendee' . self::$tc_rsvp_attendee_counter . '@test.com';

		$meta_input = [
			Attendee::$event_relation_meta_key  => $event_id,
			Attendee::$ticket_relation_meta_key => $ticket_id,
			Attendee::$full_name_meta_key       => $full_name,
			Attendee::$email_meta_key           => $email,
			Attendee::$security_code_meta_key   => md5( uniqid( '', true ) ),
			Attendee::$optout_meta_key          => $overrides['optout'] ?? false,
			Attendee::$checked_in_meta_key      => $overrides['checked_in'] ?? false,
			Attendee_Repository::RSVP_STATUS_META_KEY => $rsvp_status,
		];

		// Merge any additional meta overrides.
		if ( isset( $overrides['meta_input'] ) && is_array( $overrides['meta_input'] ) ) {
			$meta_input = array_merge( $meta_input, $overrides['meta_input'] );
		}

		$post_title = $overrides['post_title'] ?? 'TC-RSVP Attendee ' . self::$tc_rsvp_attendee_counter;

		$attendee_id = wp_insert_post( [
			'post_type'   => Attendee::POSTTYPE,
			'post_status' => 'publish',
			'post_title'  => $post_title,
			'meta_input'  => $meta_input,
		] );

		self::$tc_rsvp_attendee_counter++;

		return $attendee_id;
	}

	/**
	 * Creates multiple TC-RSVP attendees for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int   $count     The number of attendees to create.
	 * @param int   $ticket_id The ticket ID.
	 * @param int   $event_id  The event/post ID.
	 * @param array $overrides An array of values to override defaults.
	 *
	 * @return array An array of the generated attendee post IDs.
	 */
	protected function create_many_tc_rsvp_attendees( int $count, int $ticket_id, int $event_id, array $overrides = [] ): array {
		$attendees = [];

		for ( $i = 0; $i < $count; $i++ ) {
			$attendees[] = $this->create_tc_rsvp_attendee( $ticket_id, $event_id, $overrides );
		}

		return $attendees;
	}

	/**
	 * Creates TC-RSVP attendees with "going" status.
	 *
	 * @since TBD
	 *
	 * @param int   $count     The number of attendees to create.
	 * @param int   $ticket_id The ticket ID.
	 * @param int   $event_id  The event/post ID.
	 * @param array $overrides An array of values to override defaults.
	 *
	 * @return array An array of the generated attendee post IDs.
	 */
	protected function create_going_tc_rsvp_attendees( int $count, int $ticket_id, int $event_id, array $overrides = [] ): array {
		$overrides['rsvp_status'] = 'yes';

		return $this->create_many_tc_rsvp_attendees( $count, $ticket_id, $event_id, $overrides );
	}

	/**
	 * Creates TC-RSVP attendees with "not going" status.
	 *
	 * @since TBD
	 *
	 * @param int   $count     The number of attendees to create.
	 * @param int   $ticket_id The ticket ID.
	 * @param int   $event_id  The event/post ID.
	 * @param array $overrides An array of values to override defaults.
	 *
	 * @return array An array of the generated attendee post IDs.
	 */
	protected function create_not_going_tc_rsvp_attendees( int $count, int $ticket_id, int $event_id, array $overrides = [] ): array {
		$overrides['rsvp_status'] = 'no';

		return $this->create_many_tc_rsvp_attendees( $count, $ticket_id, $event_id, $overrides );
	}
}
