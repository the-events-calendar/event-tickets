<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Stock_Validator;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;

/**
 * The checkout stock check locks a table ticket's row and answers with the message buyers see today.
 */
class Stock_Validator_Test extends WPTestCase {
	use Ticket_Rows;

	/**
	 * @var Cart
	 */
	private Cart $cart;

	/**
	 * @before
	 */
	public function start_cart(): void {
		$this->cart = new Cart();
		$this->forget_rows_read();
	}

	/**
	 * @after
	 */
	public function clean_up(): void {
		$this->cart->clear_cart();
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_lock_the_row_and_refuse_more_than_it_has_left(): void {
		$id = $this->insert_ticket_row( [ 'name' => 'Evening', 'capacity' => 10, 'stock' => 1, 'sales' => 9 ] );
		$this->cart->get_repository()->upsert_item( $id, 2 );
		$queries = [];
		add_filter(
			'query',
			static function ( string $query ) use ( &$queries ) {
				$queries[] = $query;

				return $query;
			}
		);

		$result = tribe( Stock_Validator::class )->validate_cart_stock_with_lock( $this->cart );

		$this->assertWPError( $result );
		$this->assertSame( 'tec-tc-insufficient-stock', $result->get_error_code() );
		$this->assertStringContainsString( '"Evening"', $result->get_error_message() );
		$this->assertStringContainsString( 'there is only 1 available', $result->get_error_message() );
		$locks = preg_grep( '/^SELECT stock FROM `?' . preg_quote( Tickets::table_name(), '/' ) . '`? WHERE id = \d+ FOR UPDATE$/', array_map( 'trim', $queries ) );
		$this->assertCount( 1, $locks, 'The row is locked once.' );
		$this->assertEmpty( preg_grep( '/postmeta.*' . $id . '/', $queries ), 'A table ticket ID has no stock meta to lock.' );
	}

	/**
	 * @test
	 */
	public function it_should_answer_sold_out_for_a_row_with_nothing_left(): void {
		$id = $this->insert_ticket_row( [ 'name' => 'Evening', 'capacity' => 10, 'stock' => 0, 'sales' => 10 ] );
		$this->cart->get_repository()->upsert_item( $id, 1 );

		$result = tribe( Stock_Validator::class )->validate_cart_stock_with_lock( $this->cart );

		$this->assertWPError( $result );
		$this->assertStringContainsString( 'sold out', $result->get_error_message() );
	}

	/**
	 * @test
	 */
	public function it_should_pass_a_cart_the_row_can_fill(): void {
		$limited   = $this->insert_ticket_row( [ 'occurrence_id' => 1, 'capacity' => 10, 'stock' => 2 ] );
		$unlimited = $this->insert_ticket_row( [ 'occurrence_id' => 2, 'capacity' => -1, 'stock' => null ] );
		$this->cart->get_repository()->upsert_item( $limited, 2 );
		$this->cart->get_repository()->upsert_item( $unlimited, 50 );

		$this->assertTrue( tribe( Stock_Validator::class )->validate_cart_stock_with_lock( $this->cart ) );
	}

	/**
	 * @test
	 */
	public function it_should_check_a_shared_row_against_its_dates_pool(): void {
		$id = $this->insert_ticket_row( [ 'name' => 'Adult', 'parent_id' => 1, 'occurrence_id' => 30, 'stock_mode' => 'global' ] );
		$this->pool( 30, 3 );
		$this->cart->get_repository()->upsert_item( $id, 4 );

		$result = tribe( Stock_Validator::class )->validate_cart_stock_with_lock( $this->cart );

		$this->assertWPError( $result );
		$this->assertStringContainsString( 'there are only 3 available', $result->get_error_message() );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_shared_tickets_of_one_date_that_add_up_past_its_pool(): void {
		$adult = $this->insert_ticket_row( [ 'name' => 'Adult', 'parent_id' => 1, 'occurrence_id' => 30, 'stock_mode' => 'global' ] );
		$child = $this->insert_ticket_row( [ 'name' => 'Child', 'parent_id' => 2, 'occurrence_id' => 30, 'stock_mode' => 'global' ] );
		$other = $this->insert_ticket_row( [ 'name' => 'Other date', 'parent_id' => 1, 'occurrence_id' => 31, 'stock_mode' => 'global' ] );
		$this->pool( 30, 3 );
		$this->pool( 31, 3 );
		$this->cart->get_repository()->upsert_item( $adult, 2 );
		$this->cart->get_repository()->upsert_item( $child, 2 );
		$this->cart->get_repository()->upsert_item( $other, 3 );

		$result = tribe( Stock_Validator::class )->validate_cart_stock_with_lock( $this->cart );

		$this->assertWPError( $result, 'Each fits alone; together they take 4 of 3.' );
		$this->assertStringNotContainsString( 'Other date', $result->get_error_message() );
	}

	/**
	 * Gives a date a pool.
	 *
	 * @param int $occurrence_id The date.
	 * @param int $capacity      The pool's capacity.
	 */
	private function pool( int $occurrence_id, int $capacity ): void {
		tribe( Rows::class )->upsert_pools( 1, $capacity, [ $occurrence_id ] );
		tribe( Rows::class )->refresh_pools( 1 );
	}
}
