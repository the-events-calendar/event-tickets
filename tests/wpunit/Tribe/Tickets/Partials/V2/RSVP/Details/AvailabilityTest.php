<?php

namespace Tribe\Tickets\Partials\V2\RSVP\Details;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;
use Tribe\Tickets\Test\Commerce\Attendee_Maker as Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class AvailabilityTest extends WPTestCase {

	use MatchesSnapshots;
	use With_Post_Remapping;

	use RSVP_Ticket_Maker;
	use Attendee_Maker;

	protected $partial_path = 'v2/rsvp/details/availability';

	/**
	 * Provide dates for the days left variations.
	 *
	 * @return \Generator
	 */
	public function provider_days_left_dates() {
		// Timezones are tricky in this test. We'll assume the time is off by a bit.

		yield 'last day to RSVP' => [
			// If there is less than a 24 hours, it will treat it as the last day.
			'+12 hours',
		];

		yield '1 day left to RSVP' => [
			'+2 days',
		];

		yield 'multi day left to RSVP' => [
			'+6 days',
		];

		yield 'week threshold' => [
			// The logic floors the day, so if there's 6 days and 23 hours, it'll treat it as 6 hours.
			'+8 days',
		];
	}

	/**
	 * @test
	 */
	public function test_should_render_5_remaining() {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_rsvp_ticket(
			$event_id,
			[
				'meta_input' => [
					'_capacity' => 5,
				],
			]
		);

		$ticket = tribe( 'tickets.rsvp' )->get_ticket( $event_id, $ticket_id );

		$args = [
			'rsvp'      => $ticket,
			'post_id'   => $event_id,
			'threshold' => 0,
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://test.tribe.dev' );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_respect_availability_not_displaying_if_threshold() {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_rsvp_ticket(
			$event_id,
			[
				'meta_input' => [
					'_capacity' => 5,
				],
			]
		);

		$ticket = tribe( 'tickets.rsvp' )->get_ticket( $event_id, $ticket_id );

		$args = [
			'rsvp'      => $ticket,
			'post_id'   => $event_id,
			'threshold' => 2, // make the threshold lower than the availability.
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://test.tribe.dev' );

		$this->assertMatchesSnapshot( $html, $driver );
	}


	/**
	 * @test
	 * @dataProvider provider_days_left_dates
	 */
	public function test_should_render_full( $ticket_end_date ) {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_rsvp_ticket(
			$event_id,
			[
				'meta_input' => [
					'_capacity' => 3,
					'_ticket_end_date' => date( 'Y-m-d H:i:s', strtotime( $ticket_end_date ) ),
				],
			]
		);

		$this->create_many_attendees_for_ticket( 5, $ticket_id, $event_id );

		$ticket = tribe( 'tickets.rsvp' )->get_ticket( $event_id, $ticket_id );

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
	 * @dataProvider provider_days_left_dates
	 */
	public function test_should_render_unlimited( $ticket_end_date ) {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_rsvp_ticket(
			$event_id,
			[
				'meta_input' => [
					'_capacity' => - 1,
					'_ticket_end_date' => date( 'Y-m-d H:i:s', strtotime( $ticket_end_date ) ),
				],
			]
		);
		$ticket    = tribe( 'tickets.rsvp' )->get_ticket( $event_id, $ticket_id );
		add_filter( 'tribe_rsvp_block_show_unlimited_availability', '__return_true' );

		$args = [
			'rsvp'    => $ticket,
			'post_id' => $event_id,
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://test.tribe.dev' );

		$this->assertMatchesSnapshot( $html, $driver );

	}
}
