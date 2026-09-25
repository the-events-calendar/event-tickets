<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;
use DateTimeImmutable;
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
		$rule        = Rule::from_array( [ 'start' => $this->relative( 3, Rule::UNIT_DAYS ), 'end' => [ 'mode' => 'specific' ] ] );

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
		$rule         = Rule::from_array( [ 'start' => $this->relative( 3, Rule::UNIT_DAYS ), 'end' => $this->relative( 1, Rule::UNIT_DAYS ) ] );

		tribe( Ticket_Dates::class )->write( $ticket_id, $post_id, $rule );

		$this->assertSame( $ticket_start, $this->get_ticket_start( $ticket_id ) );
		$this->assertSame( $ticket_end, $this->get_ticket_end( $ticket_id ) );
	}
}
