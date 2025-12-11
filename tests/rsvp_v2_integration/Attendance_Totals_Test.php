<?php

namespace TECTicketsRSVPV2Tests;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\RSVP\V2\Attendance_Totals;
use TEC\Tickets\RSVP\V2\Meta;
use TEC\Tickets\RSVP\V2\Ticket;
use TEC\Tickets\Test\Commerce\RSVP\V2\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Attendance_Totals_Test extends WPTestCase {
	use Ticket_Maker;
	use Attendee_Maker;

	/**
	 * @var Attendance_Totals
	 */
	protected $totals;

	protected function setUp(): void {
		parent::setUp();
		$this->totals = tribe( Attendance_Totals::class );
	}

	public function test_should_return_zero_for_post_with_no_tickets(): void {
		$post_id = static::factory()->post->create();

		$this->assertSame( 0, $this->totals->get_total_going( $post_id ) );
		$this->assertSame( 0, $this->totals->get_total_not_going( $post_id ) );
		$this->assertSame( 0, $this->totals->get_total_rsvps( $post_id ) );
	}

	public function test_should_return_zero_for_post_with_ticket_but_no_attendees(): void {
		$post_id = static::factory()->post->create();
		$ticket  = tribe( Ticket::class );

		$ticket_id = $ticket->create( $post_id, [
			'name'     => 'Test RSVP',
			'capacity' => 100,
		] );

		$this->assertSame( 0, $this->totals->get_total_going( $post_id ) );
		$this->assertSame( 0, $this->totals->get_total_not_going( $post_id ) );
		$this->assertSame( 0, $this->totals->get_total_rsvps( $post_id ) );
	}

	public function test_should_count_going_attendees(): void {
		$post_id = static::factory()->post->create();
		$ticket  = tribe( Ticket::class );

		$ticket_id = $ticket->create( $post_id, [
			'name'     => 'Test RSVP',
			'capacity' => 100,
		] );

		// Create attendees with "going" status.
		$this->create_rsvp_attendee( $ticket_id, $post_id, Meta::STATUS_GOING );
		$this->create_rsvp_attendee( $ticket_id, $post_id, Meta::STATUS_GOING );
		$this->create_rsvp_attendee( $ticket_id, $post_id, Meta::STATUS_GOING );

		$this->assertSame( 3, $this->totals->get_total_going( $post_id ) );
		$this->assertSame( 0, $this->totals->get_total_not_going( $post_id ) );
		$this->assertSame( 3, $this->totals->get_total_rsvps( $post_id ) );
	}

	public function test_should_count_not_going_attendees(): void {
		$post_id = static::factory()->post->create();
		$ticket  = tribe( Ticket::class );

		$ticket_id = $ticket->create( $post_id, [
			'name'     => 'Test RSVP',
			'capacity' => 100,
		] );

		// Create attendees with "not going" status.
		$this->create_rsvp_attendee( $ticket_id, $post_id, Meta::STATUS_NOT_GOING );
		$this->create_rsvp_attendee( $ticket_id, $post_id, Meta::STATUS_NOT_GOING );

		$this->assertSame( 0, $this->totals->get_total_going( $post_id ) );
		$this->assertSame( 2, $this->totals->get_total_not_going( $post_id ) );
		$this->assertSame( 2, $this->totals->get_total_rsvps( $post_id ) );
	}

	public function test_should_count_mixed_statuses(): void {
		$post_id = static::factory()->post->create();
		$ticket  = tribe( Ticket::class );

		$ticket_id = $ticket->create( $post_id, [
			'name'     => 'Test RSVP',
			'capacity' => 100,
		] );

		// Create mixed attendees.
		$this->create_rsvp_attendee( $ticket_id, $post_id, Meta::STATUS_GOING );
		$this->create_rsvp_attendee( $ticket_id, $post_id, Meta::STATUS_GOING );
		$this->create_rsvp_attendee( $ticket_id, $post_id, Meta::STATUS_NOT_GOING );
		$this->create_rsvp_attendee( $ticket_id, $post_id, Meta::STATUS_NOT_GOING );
		$this->create_rsvp_attendee( $ticket_id, $post_id, Meta::STATUS_NOT_GOING );

		$this->assertSame( 2, $this->totals->get_total_going( $post_id ) );
		$this->assertSame( 3, $this->totals->get_total_not_going( $post_id ) );
		$this->assertSame( 5, $this->totals->get_total_rsvps( $post_id ) );
	}

	public function test_should_not_render_totals_for_post_with_no_attendees(): void {
		$post_id = static::factory()->post->create();

		ob_start();
		$this->totals->render_totals( $post_id );
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}

	public function test_should_render_totals_for_post_with_attendees(): void {
		$post_id = static::factory()->post->create();
		$ticket  = tribe( Ticket::class );

		$ticket_id = $ticket->create( $post_id, [
			'name'     => 'Test RSVP',
			'capacity' => 100,
		] );

		$this->create_rsvp_attendee( $ticket_id, $post_id, Meta::STATUS_GOING );
		$this->create_rsvp_attendee( $ticket_id, $post_id, Meta::STATUS_NOT_GOING );

		ob_start();
		$this->totals->render_totals( $post_id );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'tribe-tickets-rsvp-v2-totals', $output );
		$this->assertStringContainsString( 'Going:', $output );
		$this->assertStringContainsString( 'Not Going:', $output );
	}

	public function test_totals_filter_should_modify_going_count(): void {
		$post_id = static::factory()->post->create();

		add_filter( 'tec_tickets_rsvp_v2_get_total_going', function ( $count ) {
			return $count + 10;
		} );

		$this->assertSame( 10, $this->totals->get_total_going( $post_id ) );
	}

	public function test_totals_filter_should_modify_not_going_count(): void {
		$post_id = static::factory()->post->create();

		add_filter( 'tec_tickets_rsvp_v2_get_total_not_going', function ( $count ) {
			return $count + 5;
		} );

		$this->assertSame( 5, $this->totals->get_total_not_going( $post_id ) );
	}

}
