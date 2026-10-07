<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * Seating and recurring event tickets exclude each other.
 */
class Seating_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;

	/**
	 * @before
	 */
	public function edit_as_an_administrator(): void {
		( new Tickets() )->empty_table();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_let_a_plugin_turn_seating_off_for_a_post(): void {
		$post = static::factory()->post->create();
		update_post_meta( $post, Meta::META_KEY_LAYOUT_ID, 'some-layout' );
		$this->assertTrue( tec_tickets_seating_enabled( $post ) );

		add_filter( 'tec_tickets_seating_enabled', '__return_false' );

		$this->assertFalse( tec_tickets_seating_enabled( $post ) );
	}

	/**
	 * @test
	 */
	public function it_should_give_an_event_with_a_seating_layout_no_recurring_event_ticket(): void {
		$event = $this->create_recurring_event();
		update_post_meta( $event, Meta::META_KEY_LAYOUT_ID, 'some-layout' );

		$ticket = $this->create_tc_ticket( $event, 10 );

		$this->assertSame( 'default', get_post_meta( $ticket, '_type', true ) );
		$this->assertCount( 0, tribe( Repositories\Tickets::class )->get_by_template( $ticket ) );
	}

	/**
	 * @test
	 */
	public function it_should_give_an_event_with_recurring_event_tickets_no_seating_layout(): void {
		$event = $this->create_recurring_event();
		$this->create_tc_ticket( $event, 10 );
		$date = (int) $this->get_dates( $event )[1]->provisional_id;

		$this->assertFalse( update_post_meta( $event, Meta::META_KEY_LAYOUT_ID, 'some-layout' ) );
		$this->assertSame( '', get_post_meta( $event, Meta::META_KEY_LAYOUT_ID, true ) );
		$this->assertFalse( tec_tickets_seating_enabled( $event ) );
		$this->assertFalse( tec_tickets_seating_enabled( $date ) );
	}
}
