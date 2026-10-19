<?php

namespace TEC\Tickets\Recurring_Tickets\Admin;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * A recurring event's Attendees and Orders pages list its dates, where an event's give its one date.
 */
class Event_Details_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;

	/**
	 * @test
	 * @dataProvider pages
	 */
	public function it_should_list_the_dates_of_a_recurring_event_in_place_of_its_one_date( string $hook ): void {
		$event = $this->create_recurring_event( 5 );
		$this->create_tc_ticket( $event, 10 );
		$dates = $this->get_dates( $event );

		$html = $this->details( $hook, $event );

		$this->assertStringContainsString( 'Upcoming dates:', $html );
		$this->assertStringNotContainsString( 'Event Date:', $html );
		$this->assertStringContainsString( esc_html( tribe_format_date( $dates[0]->start_date, true, tribe_get_datetime_format( true ) ) ), $html );
		$this->assertStringContainsString( '+ 2 more', $html, 'Three dates show, the rest fold away.' );
	}

	/**
	 * @test
	 * @dataProvider pages
	 */
	public function it_should_list_the_dates_of_its_event_on_a_dates_page( string $hook ): void {
		$event = $this->create_recurring_event();
		$this->create_tc_ticket( $event, 10 );
		$dates = $this->get_dates( $event );

		$html = $this->details( $hook, (int) $dates[1]->provisional_id );

		$this->assertStringContainsString( 'Upcoming dates:', $html );
		$this->assertStringContainsString( esc_html( tribe_format_date( $dates[0]->start_date, true, tribe_get_datetime_format( true ) ) ), $html );
	}

	/**
	 * @test
	 * @dataProvider pages
	 */
	public function it_should_leave_an_event_without_recurring_event_tickets_its_one_date( string $hook ): void {
		$event = tribe_events()->set_args(
			[
				'title'      => 'One date',
				'status'     => 'publish',
				'start_date' => '+1 day 10:00',
				'duration'   => HOUR_IN_SECONDS,
			]
		)->create()->ID;

		$html = $this->details( $hook, $event );

		$this->assertStringContainsString( 'Event Date:', $html );
		$this->assertStringNotContainsString( 'Upcoming dates:', $html );
	}

	/**
	 * @return array<string,array{0: string}> The action of the details of each page.
	 */
	public function pages(): array {
		return [
			'Attendees page' => [ 'tribe_tickets_attendees_event_details_list_top' ],
			'Orders page'    => [ 'tribe_tickets_report_event_details_list_top' ],
		];
	}

	/**
	 * @param string $hook     The action of the page's details.
	 * @param int    $event_id The event.
	 *
	 * @return string The details the page prints.
	 */
	private function details( string $hook, int $event_id ): string {
		global $wp_filter;
		// The details take TEC's date off the action for the request; the next test needs it back.
		$callbacks = clone $wp_filter[ $hook ];

		try {
			ob_start();
			do_action( $hook, $event_id );

			return (string) ob_get_clean();
		} finally {
			$wp_filter[ $hook ] = $callbacks;
		}
	}
}
