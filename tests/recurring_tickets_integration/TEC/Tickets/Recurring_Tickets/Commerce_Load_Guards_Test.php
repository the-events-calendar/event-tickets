<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Tickets Commerce loads a table ticket from its row, and loading it writes nothing.
 */
class Commerce_Load_Guards_Test extends WPTestCase {
	use Ticket_Rows;

	/**
	 * @after
	 */
	public function empty_table(): void {
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_get_a_table_ticket(): void {
		[ $id ] = $this->create_row();

		$ticket = tribe( Ticket::class )->get_ticket( $id );

		$this->assertInstanceOf( Ticket_Object::class, $ticket );
		$this->assertSame( 'Commerce row', $ticket->name );
		$this->assertNull( tribe( Ticket::class )->get_ticket( Ticket_ID::from_row_id( 999 ) ) );
	}

	/**
	 * @test
	 */
	public function it_should_get_a_table_ticket_through_the_module_without_writing_meta(): void {
		[ $id, $date ] = $this->create_row();

		$ticket = tribe( Module::class )->get_ticket( $date->provisional_id, $id );

		$this->assertInstanceOf( Ticket_Object::class, $ticket );
		$this->assertSame( $id, $ticket->ID );
		$this->assertSame(
			0,
			(int) DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i WHERE post_id = %d', DB::prefix( 'postmeta' ), $id ) ),
			'Loading a table ticket must not write post meta for its ID.'
		);
	}

	/**
	 * @test
	 */
	public function it_should_answer_the_date_as_the_related_event(): void {
		[ $id, $date ] = $this->create_row();

		$this->assertSame( $date->provisional_id, (int) tribe( Ticket::class )->get_related_event_id( $id ) );
	}

	/**
	 * @return array{0: int, 1: object} The ticket ID and the date.
	 */
	private function create_row(): array {
		$event = $this->create_recurring_event();
		$date  = $this->get_dates( $event )[0];
		$id    = $this->insert_ticket_row( [ 'post_id' => $event, 'occurrence_id' => $date->occurrence_id, 'name' => 'Commerce row' ] );

		return [ $id, $date ];
	}
}
