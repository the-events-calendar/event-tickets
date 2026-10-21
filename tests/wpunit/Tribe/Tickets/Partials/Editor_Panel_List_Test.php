<?php

namespace Tribe\Tickets\Partials;

use Codeception\TestCase\WPTestCase;
use Generator;
use Tribe\Tickets\Test\Commerce\RSVP\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe__Tickets__Tickets as Tickets;

class Editor_Panel_List_Test extends WPTestCase {
	use Ticket_Maker;
	use Attendee_Maker;

	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function rsvp_attendee_count_provider(): Generator {
		yield 'RSVP with attendees' => [ 2 ];
		yield 'RSVP without attendees' => [ 0 ];
	}

	/**
	 * @test
	 * @dataProvider rsvp_attendee_count_provider
	 */
	public function should_show_view_attendees_link_when_event_has_rsvps( int $attendee_count ): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		if ( $attendee_count ) {
			$this->create_many_attendees_for_ticket( $attendee_count, $ticket_id, $post_id );
		}

		$html = tribe( 'tickets.admin.views' )->template(
			'editor/panel/list',
			[
				'post_id' => $post_id,
				'tickets' => Tickets::get_all_event_tickets( $post_id ),
			],
			false
		);

		$this->assertStringContainsString( 'View Attendees', $html );
	}
}
