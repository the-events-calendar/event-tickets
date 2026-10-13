<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;
use DateTimeImmutable;
use Generator;
use TEC\Tickets\Commerce\Ticket;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;

class Ticket_Dates_Test extends WPTestCase {
	use Relative_Sale_Dates_Maker;
	use Ticket_Maker;
	use With_Tickets_Commerce;

	/**
	 * @test
	 */
	public function should_leave_the_ends_the_rule_does_not_resolve_as_they_are(): void {
		$event_start = new DateTimeImmutable( '2027-06-24 19:00:00' );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_tc_ticket( $event_id );
		$ticket_end  = $this->get_ticket_end( $ticket_id );
		$rule        = Rule::from_array( [ 'start' => $this->relative( 3, DAY_IN_SECONDS ), 'end' => [ 'mode' => 'specific' ] ], Window_Kind::sales() );

		tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, $rule );

		$sales_start = $event_start->modify( '-3 days' );
		$this->assertSame( [ $sales_start->format( 'Y-m-d' ), $sales_start->format( 'H:i:s' ) ], $this->get_ticket_start( $ticket_id ) );
		$this->assertSame( $ticket_end, $this->get_ticket_end( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_write_nothing_for_a_post_without_event_dates(): void {
		$post_id      = static::factory()->post->create();
		$ticket_id    = $this->create_tc_ticket( $post_id );
		$ticket_start = $this->get_ticket_start( $ticket_id );
		$ticket_end   = $this->get_ticket_end( $ticket_id );
		$rule         = Rule::from_array( [ 'start' => $this->relative( 3, DAY_IN_SECONDS ), 'end' => $this->relative( 1, DAY_IN_SECONDS ) ], Window_Kind::sales() );

		$changed = tribe( Ticket_Dates::class )->write( $ticket_id, $post_id, $rule );

		$this->assertFalse( $changed );
		$this->assertSame( $ticket_start, $this->get_ticket_start( $ticket_id ) );
		$this->assertSame( $ticket_end, $this->get_ticket_end( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_report_whether_the_write_changed_a_date(): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket( $event_id );
		$rule      = Rule::from_array( [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => $this->relative( 1, DAY_IN_SECONDS ) ], Window_Kind::sales() );

		$first  = tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, $rule );
		$second = tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, $rule );

		$this->assertTrue( $first );
		$this->assertFalse( $second );
	}

	/**
	 * The dates are wall-clock times in the event timezone, so a new timezone moves them even when the times stay.
	 *
	 * @test
	 */
	public function should_report_a_change_when_only_the_event_timezone_changes(): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket( $event_id );
		$rule      = Rule::from_array( [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => $this->relative( 1, DAY_IN_SECONDS ) ], Window_Kind::sales() );
		tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, $rule );
		update_post_meta( $event_id, '_EventTimezone', 'America/New_York' );

		$this->assertTrue( tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, $rule ) );
		$this->assertFalse( tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, $rule ) );
	}

	/**
	 * @return Generator<string,array{0: array{0: string, 1: string}, 1: array{0: string, 1: string}, 2: bool}>
	 */
	public function written_window_provider(): Generator {
		yield 'an end after the start' => [ [ '2027-06-10', '19:00:00' ], [ '2027-06-11', '09:00:00' ], false ];
		yield 'an end equal to the start' => [ [ '2027-06-10', '19:00:00' ], [ '2027-06-10', '19:00:00' ], true ];
		yield 'an end before the start' => [ [ '2027-06-10', '19:00:00' ], [ '2027-06-10', '18:59:59' ], true ];
		yield 'an end day without a time after the start day' => [ [ '2027-06-10', '' ], [ '2027-06-11', '' ], false ];
		yield 'an end day without a time on the start day' => [ [ '2027-06-10', '19:00:00' ], [ '2027-06-10', '' ], false ];
		yield 'an end day without a time before the start day' => [ [ '2027-06-10', '' ], [ '2027-06-09', '' ], true ];
		yield 'no end' => [ [ '2027-06-10', '19:00:00' ], [ '', '' ], false ];
	}

	/**
	 * @test
	 * @dataProvider written_window_provider
	 *
	 * @param array{0: string, 1: string} $start         The written start date and time.
	 * @param array{0: string, 1: string} $end           The written end date and time.
	 * @param bool                        $ends_before   Whether the window ends before or when it starts.
	 */
	public function should_tell_whether_the_written_window_ends_before_it_starts( array $start, array $end, bool $ends_before ): void {
		$ticket_id = $this->create_tc_ticket( $this->create_event( '2027-06-24 19:00:00' ) );
		update_post_meta( $ticket_id, Ticket::START_DATE_META_KEY, $start[0] );
		update_post_meta( $ticket_id, Ticket::START_TIME_META_KEY, $start[1] );
		update_post_meta( $ticket_id, Ticket::END_DATE_META_KEY, $end[0] );
		update_post_meta( $ticket_id, Ticket::END_TIME_META_KEY, $end[1] );

		$this->assertSame( $ends_before, tribe( Ticket_Dates::class )->ends_before_it_starts( $ticket_id ) );
	}
}
