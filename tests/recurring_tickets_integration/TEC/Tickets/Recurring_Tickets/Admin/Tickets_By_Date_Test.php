<?php

namespace TEC\Tickets\Recurring_Tickets\Admin;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Recurring_Tickets\Template_Guard;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use TEC\Tickets\Tests\Recurring_Tickets\Requires_Recurrence_Tier;
use TEC\Tickets\Tests\Recurring_Tickets\Row_Orders;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * Tickets by Date lists every date of a recurring event with its tickets as they stand on that date.
 */
class Tickets_By_Date_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;
	use Row_Orders;
	use Requires_Recurrence_Tier;

	/**
	 * The event.
	 *
	 * @var int
	 */
	private int $event = 0;

	/**
	 * The recurring event ticket.
	 *
	 * @var int
	 */
	private int $template = 0;

	/**
	 * The event's dates.
	 *
	 * @var object[]
	 */
	private array $dates = [];

	/**
	 * @before
	 */
	public function create_event(): void {
		( new Tickets() )->empty_table();
		// Edit links are only given to a user who can edit.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->event    = $this->create_recurring_event( 3, 'Tickets by Date' );
		$this->template = $this->with_capacity( 10 )->create_tc_ticket( $this->event, 20, [ 'ticket_name' => 'General Admission' ] );
		// As the ticket editor saves a ticket on a recurring event.
		update_post_meta( $this->template, '_type', Template_Guard::TICKET_TYPE );
		$this->dates = $this->get_dates( $this->event );
	}

	/**
	 * @after
	 */
	public function clean_up(): void {
		// Completing an order commits the test's database transaction.
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_list_every_date_in_order_with_its_own_numbers(): void {
		$sold = 3;
		$this->create_row_order( [ $this->ticket_on( 1 ) => $sold ] );

		$data = tribe( Tickets_By_Date::class )->for_event( $this->event );

		$this->assertSame( array_map( static fn( $date ) => (int) $date->provisional_id, $this->dates ), array_column( $data['dates'], 'id' ) );
		foreach ( $data['dates'] as $i => $date ) {
			$expected = 1 === $i ? 10 - $sold : 10;
			$this->assertSame( $expected, $date['tickets'][0]['available'], "Date {$i} counts its own sales." );
			$this->assertSame( 10, $date['tickets'][0]['capacity'] );
			$this->assertSame( $expected, $date['available'] );
			$this->assertSame( 10, $date['capacity'] );
		}
	}

	/**
	 * @test
	 */
	public function it_should_label_each_date_and_its_tickets(): void {
		$date   = tribe( Tickets_By_Date::class )->for_event( $this->event )['dates'][0];
		$start  = $this->dates[0]->start_date;
		$ticket = $date['tickets'][0];

		$this->assertSame( tribe_format_date( $start, false, tribe_get_date_format( true ) ), $date['label'] );
		$this->assertSame( tribe_format_date( $start, false, tribe_get_time_format() ), $date['time'] );
		$this->assertFalse( $date['past'] );
		$this->assertSame( 'General Admission', $ticket['name'] );
		$this->assertSame( Ticket_ID::from_row_id( (int) $this->row_on( 0 )->id ), $ticket['id'] );
		$this->assertSame( html_entity_decode( Value::create( 20 )->get_currency(), ENT_QUOTES, 'UTF-8' ), $ticket['price'], 'Plain text, as the section prints it.' );
		$this->assertStringNotContainsString( '&', $ticket['price'] );
		$this->assertNull( $ticket['regular_price'], 'No sale price.' );
		$this->assertSame( [], $ticket['fields'], 'No attendee fields without Event Tickets Plus.' );
	}

	/**
	 * @test
	 */
	public function it_should_link_each_date_to_the_events_attendees_and_orders_narrowed_to_it(): void {
		$date = tribe( Tickets_By_Date::class )->for_event( $this->event )['dates'][1];
		$id   = (int) $this->dates[1]->provisional_id;

		foreach ( [ 'attendees_url' => 'page=tickets-attendees', 'orders_url' => 'page=tickets-commerce-orders' ] as $key => $page ) {
			$this->assertStringContainsString( $page, $date[ $key ] );
			$this->assertStringContainsString( (string) $this->event, $date[ $key ], 'The event\'s page, not the date\'s.' );
			$this->assertStringContainsString( Date_Filter::NAME . '=' . $id, $date[ $key ] );
		}
	}

	/**
	 * @test
	 */
	public function it_should_count_the_upcoming_and_all_dates(): void {
		$this->assertSame( [ 'upcoming' => 3, 'all' => 3 ], tribe( Tickets_By_Date::class )->for_event( $this->event )['counts'] );
	}

	/**
	 * @test
	 */
	public function it_should_mark_past_dates(): void {
		( new Tickets() )->empty_table();
		$start = gmdate( 'Y-m-d', strtotime( '-3 days' ) );
		// Every other day from three days ago: two past dates and one to come.
		$event = tribe_events()->set_args(
			[
				'title'      => 'Past and upcoming',
				'status'     => 'publish',
				'start_date' => "{$start} 10:00:00",
				'end_date'   => "{$start} 12:00:00",
				'recurrence' => 'RRULE:FREQ=DAILY;INTERVAL=2;COUNT=3',
			]
		)->create()->ID;
		$template = $this->create_tc_ticket( $event, 5 );
		update_post_meta( $template, '_type', Template_Guard::TICKET_TYPE );

		$data = tribe( Tickets_By_Date::class )->for_event( $event );

		$this->assertSame( [ true, true, false ], array_column( $data['dates'], 'past' ) );
		$this->assertSame( [ 'upcoming' => 1, 'all' => 3 ], $data['counts'] );
	}

	/**
	 * @test
	 */
	public function it_should_let_a_plugin_name_a_tickets_attendee_fields(): void {
		add_filter(
			'tec_tickets_recurring_tickets_by_date_ticket_fields',
			fn( $fields, $template_id ) => $this->template === $template_id ? [ 'Name', 'Email' ] : $fields,
			10,
			2
		);

		$this->assertSame( [ 'Name', 'Email' ], tribe( Tickets_By_Date::class )->for_event( $this->event )['dates'][0]['tickets'][0]['fields'] );
	}

	/**
	 * @test
	 */
	public function it_should_have_nothing_for_an_event_without_recurring_event_tickets(): void {
		$single = tribe_events()->set_args(
			[
				'title'      => 'Single',
				'status'     => 'publish',
				'start_date' => '+1 week 10:00:00',
				'end_date'   => '+1 week 12:00:00',
			]
		)->create()->ID;
		$this->create_tc_ticket( $single, 5 );

		$this->assertNull( tribe( Tickets_By_Date::class )->for_event( $single ) );
		$this->assertNull( tribe( Tickets_By_Date::class )->for_event( $this->create_recurring_event( 2, 'No tickets' ) ) );
	}

	private function row_on( int $date ): object {
		return array_column( tribe( Rows::class )->get_by_post( $this->event ), null, 'occurrence_id' )[ $this->dates[ $date ]->occurrence_id ];
	}

	private function ticket_on( int $date ): int {
		return Ticket_ID::from_row_id( (int) $this->row_on( $date )->id );
	}
}
