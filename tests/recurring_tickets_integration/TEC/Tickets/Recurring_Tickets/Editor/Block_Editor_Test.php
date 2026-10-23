<?php

namespace TEC\Tickets\Recurring_Tickets\Editor;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use TEC\Tickets\Tests\Recurring_Tickets\Recurrence_Tier_Check;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Block_Editor_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;
	use Recurrence_Tier_Check;

	/**
	 * @before
	 */
	public function log_in(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_list_a_dates_copy_of_a_recurring_event_ticket_in_the_block_editor(): void {
		$this->skip_without_recurrence_tier();

		$event    = $this->create_recurring_event();
		$template = $this->create_tc_ticket( $event, 10 );
		$date     = absint( $this->get_dates( $event )[1]->provisional_id );

		$listed = json_decode( tribe( 'tickets.editor.meta' )->register_tickets_list_in_rest( null, $date, '_tribe_tickets_list', true )[0], true );

		$table_tickets = array_filter( array_column( $listed, 'id' ), [ Ticket_ID::class, 'is_table_ticket' ] );
		$this->assertSame( [], $table_tickets, "The date's block already holds the event's ticket {$template}." );
	}
}
