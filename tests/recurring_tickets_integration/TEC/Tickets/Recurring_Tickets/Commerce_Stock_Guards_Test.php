<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * The Tickets Commerce stock functions move a table ticket's stock and sales on its row, never in post meta.
 */
class Commerce_Stock_Guards_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;

	/**
	 * @after
	 */
	public function empty_table(): void {
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_move_a_table_tickets_sales_on_its_row(): void {
		$id = $this->insert_ticket_row( [ 'capacity' => 10, 'stock' => 5, 'sales' => 5 ] );

		$this->assertSame( 7, tribe( Ticket::class )->increase_ticket_sales_by( $id, 2 ) );
		$this->assertSame( 6, tribe( Ticket::class )->decrease_ticket_sales_by( $id, 1 ) );

		$this->assertSame( [ 5, 6 ], $this->stock_and_sales( $id ) );
		$this->assertNoPostMeta( $id );
	}

	/**
	 * @test
	 */
	public function it_should_move_a_table_tickets_stock_on_its_row(): void {
		$id = $this->insert_ticket_row( [ 'capacity' => 10, 'stock' => 5, 'sales' => 5 ] );

		$this->assertTrue( tribe( Ticket::class )->increase_ticket_stock_by( $id, 2 ) );
		$this->assertTrue( tribe( Ticket::class )->decrease_ticket_stock_by( $id, 4 ) );

		$this->assertSame( [ 3, 5 ], $this->stock_and_sales( $id ) );
		$this->assertNoPostMeta( $id );
	}

	/**
	 * @test
	 */
	public function it_should_return_a_ticket_to_the_row_when_its_attendee_is_deleted(): void {
		$event    = $this->create_recurring_event();
		$id       = $this->insert_ticket_row( [ 'post_id' => $event, 'capacity' => 10, 'stock' => 7, 'sales' => 3 ] );
		$attendee = static::factory()->post->create(
			[
				'post_type'  => Attendee::POSTTYPE,
				'meta_input' => [
					Attendee::$ticket_relation_meta_key => $id,
					Attendee::$event_relation_meta_key  => $event,
				],
			]
		);

		tribe( Attendee::class )->delete( $attendee );

		$this->assertSame( [ 8, 2 ], $this->stock_and_sales( $id ) );
		$this->assertNoPostMeta( $id );
	}

	/**
	 * @test
	 */
	public function it_should_move_a_ticket_posts_stock_as_before(): void {
		$ticket = $this->create_tc_ticket( static::factory()->post->create(), 10 );
		update_post_meta( $ticket, '_stock', 5 );
		update_post_meta( $ticket, 'total_sales', 5 );

		tribe( Ticket::class )->increase_ticket_sales_by( $ticket, 2 );
		tribe( Ticket::class )->decrease_ticket_stock_by( $ticket, 2 );

		$this->assertSame( '7', get_post_meta( $ticket, 'total_sales', true ) );
		$this->assertSame( '3', get_post_meta( $ticket, '_stock', true ) );
	}

	/**
	 * @param int $ticket_id The table ticket ID.
	 *
	 * @return array{0: int|null, 1: int} The row's stock and sales, as stored.
	 */
	private function stock_and_sales( int $ticket_id ): array {
		$row = tribe( Rows::class )->find( Ticket_ID::to_row_id( $ticket_id ) );

		return [ null === $row->stock ? null : (int) $row->stock, (int) $row->sales ];
	}

	/**
	 * @param int $ticket_id The table ticket ID.
	 */
	private function assertNoPostMeta( int $ticket_id ): void {
		$this->assertSame( 0, (int) DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i WHERE post_id = %d', DB::prefix( 'postmeta' ), $ticket_id ) ) );
	}
}
