<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;

/**
 * The Attendees screen groups tickets by `Ticket_Object::type()` and looks each group up in a label map and
 * an icon map that ship with entries for `default` and `rsvp` only. A TC-RSVP ticket reports `tc-rsvp`, and
 * the template falls back to printing the raw group key, so a missing entry shows the user "tc-rsvp" as a
 * section heading with no icon beside it.
 */
class Attendees_Overview_Labels_Test extends WPTestCase {
	use Ticket_Maker;

	public function test_the_ticket_overview_labels_a_tc_rsvp_group(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		$context = tribe( 'tickets.attendees' )->get_render_context( $post_id );

		$this->assertArrayHasKey(
			Constants::TC_RSVP_TYPE,
			$context['tickets_by_type'],
			'A TC-RSVP ticket should form its own group on the Attendees screen.'
		);

		$this->assertArrayHasKey(
			Constants::TC_RSVP_TYPE,
			$context['type_labels'],
			'Without a label the heading falls back to the raw "tc-rsvp" slug.'
		);
		$this->assertSame( tribe_get_rsvp_label_plural( 'attendee overview' ), $context['type_labels'][ Constants::TC_RSVP_TYPE ] );

		$this->assertArrayHasKey(
			Constants::TC_RSVP_TYPE,
			$context['type_icon_classes'],
			'Without an icon class the group heading renders with no icon.'
		);
	}

	public function test_the_existing_label_entries_are_left_alone(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		$context = tribe( 'tickets.attendees' )->get_render_context( $post_id );

		$this->assertArrayHasKey( 'default', $context['type_labels'] );
		$this->assertArrayHasKey( 'rsvp', $context['type_labels'] );
	}
}
