<?php

namespace Tribe\Tickets\Partials\V2\RSVP\Details;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;
use Tribe\Tickets\Test\Commerce\Attendee_Maker as Attendee_Maker;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class AttendanceTest extends WPTestCase {

	use MatchesSnapshots;
	use With_Post_Remapping;

	use RSVP_Ticket_Maker;
	use Attendee_Maker;

	protected $partial_path = 'v2/rsvp/details/attendance';

	/**
	 * @test
	 */
	public function test_should_render_5_going() {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_rsvp_ticket(
			$event_id,
			[
				'meta_input' => [
					'_capacity' => 10,
				],
			]
		);

		$this->create_many_attendees_for_ticket( 5, $ticket_id, $event_id );

		$ticket = tribe( 'tickets.rsvp' )->get_ticket( $event_id, $ticket_id );
		$ticket->qty_sold = 5;

		$args = [
			'rsvp'    => $ticket,
			'post_id' => $event_id,
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://test.tribe.dev' );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_h1_without_description() {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_rsvp_ticket(
			$event_id,
			[
				'meta_input' => [
					'_capacity' => 10,
				],
			]
		);

		$this->create_many_attendees_for_ticket( 5, $ticket_id, $event_id );

		update_post_meta( $ticket_id, tribe( 'tickets.handler' )->key_show_description, false );

		$ticket = tribe( 'tickets.rsvp' )->get_ticket( $event_id, $ticket_id );
		$ticket->qty_sold = 5;

		$args = [
			'rsvp'    => $ticket,
			'post_id' => $event_id,
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://test.tribe.dev' );

		$driver->setTolerableDifferences( [ $ticket_id, $event_id ] );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
