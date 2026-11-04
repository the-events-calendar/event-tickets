<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;

/**
 * A row's stock and sales change in single statements: a sale never takes stock below zero.
 */
class Stock_Test extends WPTestCase {
	use Ticket_Rows;

	/**
	 * @after
	 */
	public function empty_table(): void {
		( new Tickets() )->empty_table();
		$this->forget_rows_read();
	}

	/**
	 * @test
	 */
	public function it_should_sell_from_the_row(): void {
		$row = $this->row( [ 'capacity' => 10, 'stock' => 10, 'sales' => 0 ] );

		$this->assertTrue( tribe( Stock::class )->sell( $row, 3 ) );

		$this->assertSame( [ 7, 3 ], $this->stock_and_sales( $row ) );
	}

	/**
	 * @test
	 */
	public function it_should_sell_the_last_ticket_once(): void {
		$row = $this->row( [ 'capacity' => 10, 'stock' => 1, 'sales' => 9 ] );

		$this->assertTrue( tribe( Stock::class )->sell( $row, 1 ) );
		$this->assertFalse( tribe( Stock::class )->sell( $row, 1 ) );

		$this->assertSame( [ 0, 10 ], $this->stock_and_sales( $row ) );
	}

	/**
	 * @test
	 */
	public function it_should_change_nothing_when_there_is_not_enough_stock(): void {
		$row = $this->row( [ 'capacity' => 10, 'stock' => 2, 'sales' => 8 ] );

		$this->assertFalse( tribe( Stock::class )->sell( $row, 3 ) );

		$this->assertSame( [ 2, 8 ], $this->stock_and_sales( $row ) );
	}

	/**
	 * @test
	 */
	public function it_should_count_sales_only_for_an_unlimited_row(): void {
		$row = $this->row( [ 'capacity' => -1, 'stock' => null, 'sales' => 4 ] );

		$this->assertTrue( tribe( Stock::class )->sell( $row, 5 ) );

		$this->assertSame( [ null, 9 ], $this->stock_and_sales( $row ) );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_to_sell_a_limited_row_without_stock(): void {
		// Inconsistent data fails closed: only a capacity of -1 makes a row unlimited.
		$row = $this->row( [ 'capacity' => 10, 'stock' => null, 'sales' => 0 ] );

		$this->assertFalse( tribe( Stock::class )->sell( $row, 1 ) );

		$this->assertSame( [ null, 0 ], $this->stock_and_sales( $row ) );
	}

	/**
	 * @test
	 */
	public function it_should_leave_an_unlimited_row_without_stock_when_tickets_return(): void {
		$row = $this->row( [ 'capacity' => -1, 'stock' => 5, 'sales' => 4 ] );

		tribe( Stock::class )->release( $row, 2 );
		tribe( Stock::class )->add_stock( $row, 1 );

		$this->assertSame( [ null, 2 ], $this->stock_and_sales( $row ) );
	}

	/**
	 * @test
	 */
	public function it_should_return_tickets_without_going_past_capacity_or_below_zero_sales(): void {
		$row = $this->row( [ 'capacity' => 10, 'stock' => 7, 'sales' => 3 ] );

		tribe( Stock::class )->release( $row, 2 );
		$this->assertSame( [ 9, 1 ], $this->stock_and_sales( $row ) );

		tribe( Stock::class )->release( $row, 5 );
		$this->assertSame( [ 10, 0 ], $this->stock_and_sales( $row ) );
	}

	/**
	 * @test
	 */
	public function it_should_move_one_column_at_a_time(): void {
		$row   = $this->row( [ 'capacity' => 10, 'stock' => 5, 'sales' => 5 ] );
		$stock = tribe( Stock::class );

		$this->assertSame( 7, $stock->add_sales( $row, 2 ) );
		$this->assertSame( 4, $stock->remove_sales( $row, 3 ) );
		$this->assertSame( 0, $stock->remove_sales( $row, 9 ) );
		$this->assertTrue( $stock->remove_stock( $row, 2 ) );
		$this->assertSame( 3, $this->stock_and_sales( $row )[0] );
		$this->assertTrue( $stock->remove_stock( $row, 9 ) );
		$this->assertSame( 0, $this->stock_and_sales( $row )[0] );
		$this->assertTrue( $stock->add_stock( $row, 4 ) );
		$this->assertSame( 4, $this->stock_and_sales( $row )[0] );
	}

	/**
	 * @test
	 */
	public function it_should_read_the_stock_to_lock(): void {
		$limited   = $this->row( [ 'stock' => 6 ] );
		$unlimited = $this->row( [ 'capacity' => -1, 'stock' => null, 'occurrence_id' => 2 ] );

		$this->assertSame( 6, tribe( Stock::class )->lock( $limited ) );
		$this->assertNull( tribe( Stock::class )->lock( $unlimited ) );
		$this->assertNull( tribe( Stock::class )->lock( 999 ) );
	}

	/**
	 * @test
	 */
	public function it_should_show_the_new_stock_to_the_next_load(): void {
		$row = $this->row( [ 'capacity' => 10, 'stock' => 10, 'sales' => 0 ] );
		$id  = Ticket_ID::from_row_id( $row );
		tribe( Hydrator::class )->load( $id )->available();

		tribe( Stock::class )->sell( $row, 4 );

		$ticket = tribe( Hydrator::class )->load( $id );
		$this->assertSame( 6, $ticket->stock() );
		$this->assertSame( 4, $ticket->qty_sold() );
		$this->assertSame( '6', get_post_meta( $id, '_stock', true ) );
	}

	/**
	 * @test
	 */
	public function it_should_sell_a_shared_row_from_its_dates_pool_too(): void {
		$shared = $this->row( [ 'stock_mode' => 'global' ] );
		$own    = $this->row( [ 'stock_mode' => 'own' ] );
		$this->pools( 100, [ 1, 2 ] );

		$this->assertTrue( tribe( Stock::class )->sell( $shared, 3 ) );
		$this->assertTrue( tribe( Stock::class )->sell( $own, 4 ) );

		$this->assertSame( [ 97, 3 ], $this->stock_and_sales( $shared ) );
		$this->assertSame( [ 97, 3 ], $this->pool( 1 ), 'Own capacity is no part of the pool.' );
		$this->assertSame( [ 100, 0 ], $this->pool( 2 ), 'Another date keeps its pool.' );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_a_shared_sale_the_pool_cannot_cover(): void {
		$first  = $this->row( [ 'stock_mode' => 'global' ] );
		$second = $this->row( [ 'stock_mode' => 'global' ] );
		$this->pools( 5, [ 1 ] );
		$this->assertTrue( tribe( Stock::class )->sell( $first, 4 ) );

		$this->assertFalse( tribe( Stock::class )->sell( $second, 2 ), 'One seat left in the pool.' );

		$this->assertSame( [ 100, 0 ], $this->stock_and_sales( $second ) );
		$this->assertSame( [ 1, 4 ], $this->pool( 1 ) );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_a_capped_sale_past_its_cap_and_leave_the_pool_alone(): void {
		$capped = $this->row( [ 'stock_mode' => 'capped', 'capacity' => 2, 'stock' => 2 ] );
		$this->pools( 100, [ 1 ] );

		$this->assertFalse( tribe( Stock::class )->sell( $capped, 3 ) );

		$this->assertSame( [ 2, 0 ], $this->stock_and_sales( $capped ) );
		$this->assertSame( [ 100, 0 ], $this->pool( 1 ) );
	}

	/**
	 * @test
	 */
	public function it_should_give_the_pool_back_what_a_shared_row_returns(): void {
		$shared = $this->row( [ 'stock_mode' => 'global' ] );
		$this->pools( 100, [ 1 ] );
		tribe( Stock::class )->sell( $shared, 5 );

		tribe( Stock::class )->release( $shared, 2 );
		$this->assertSame( [ 97, 3 ], $this->pool( 1 ) );

		// More than the row sold: only what it sold goes back.
		tribe( Stock::class )->release( $shared, 10 );
		$this->assertSame( [ 100, 0 ], $this->stock_and_sales( $shared ) );
		$this->assertSame( [ 100, 0 ], $this->pool( 1 ) );
	}

	/**
	 * @test
	 */
	public function it_should_keep_the_pool_in_step_when_a_ticket_moves_between_shared_rows(): void {
		$from = $this->row( [ 'stock_mode' => 'global' ] );
		$to   = $this->row( [ 'stock_mode' => 'global', 'occurrence_id' => 2 ] );
		$this->pools( 100, [ 1, 2 ] );
		tribe( Stock::class )->sell( $from, 3 );

		// As core's Move does, one column at a time.
		$stock = tribe( Stock::class );
		$stock->remove_sales( $from, 1 );
		$stock->add_stock( $from, 1 );
		$stock->add_sales( $to, 1 );
		$stock->remove_stock( $to, 1 );

		$this->assertSame( [ 98, 2 ], $this->pool( 1 ) );
		$this->assertSame( [ 99, 1 ], $this->pool( 2 ) );
	}

	/**
	 * @test
	 */
	public function it_should_lock_the_lower_of_a_shared_rows_stock_and_its_pools(): void {
		$first  = $this->row( [ 'stock_mode' => 'global' ] );
		$second = $this->row( [ 'stock_mode' => 'global' ] );
		$this->pools( 10, [ 1 ] );
		tribe( Stock::class )->sell( $first, 7 );

		$this->assertSame( 3, tribe( Stock::class )->lock( $second ) );
	}

	/**
	 * Gives dates a pool of the event's shared capacity.
	 *
	 * @param int   $capacity       The capacity.
	 * @param int[] $occurrence_ids The dates.
	 */
	private function pools( int $capacity, array $occurrence_ids ): void {
		tribe( Rows::class )->upsert_pools( 1, $capacity, $occurrence_ids );
		tribe( Rows::class )->refresh_pools( 1 );
	}

	/**
	 * @param int $occurrence_id The date.
	 *
	 * @return array{0: int, 1: int} Its pool's stock and sales.
	 */
	private function pool( int $occurrence_id ): array {
		$this->forget_rows_read();
		$pool = tribe( Rows::class )->get_pool( $occurrence_id );

		return [ (int) $pool->stock, (int) $pool->sales ];
	}

	/**
	 * @param array<string,mixed> $values Column values.
	 *
	 * @return int The row ID.
	 */
	private function row( array $values ): int {
		return Ticket_ID::to_row_id( $this->insert_ticket_row( array_merge( [ 'occurrence_id' => 1 ], $values ) ) );
	}

	/**
	 * @param int $row_id The row ID.
	 *
	 * @return array{0: int|null, 1: int} The row's stock and sales, as stored.
	 */
	private function stock_and_sales( int $row_id ): array {
		$row = tribe( Rows::class )->find( $row_id );

		return [ null === $row->stock ? null : (int) $row->stock, (int) $row->sales ];
	}
}
