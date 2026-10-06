<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Refunded;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Row_Orders;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;

/**
 * An order's stock flags sell a table ticket from its row and give it back on a refund.
 */
class Stock_Flags_Test extends WPTestCase {
	use Ticket_Rows;
	use Row_Orders;

	/**
	 * @after
	 */
	public function empty_table(): void {
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_sell_a_table_ticket_from_its_row(): void {
		$id = $this->create_row( [ 'capacity' => 10, 'stock' => 10, 'sales' => 0 ] );

		$this->create_row_order( [ $id => 2 ] );

		$this->assertSame( [ 8, 2 ], $this->stock_and_sales( $id ) );
		$this->assertNoPostMeta( $id );
	}

	/**
	 * @test
	 */
	public function it_should_return_a_refunded_table_ticket_to_its_row(): void {
		$id    = $this->create_row( [ 'capacity' => 10, 'stock' => 10, 'sales' => 0 ] );
		$order = $this->create_row_order( [ $id => 2 ] );
		$this->assertSame( [ 8, 2 ], $this->stock_and_sales( $id ) );

		tribe( Order::class )->modify_status( $order->ID, Refunded::SLUG );

		$this->assertSame( [ 10, 0 ], $this->stock_and_sales( $id ) );
	}

	/**
	 * @test
	 */
	public function it_should_return_a_refunded_table_ticket_when_ecp_provides_no_dates(): void {
		$id    = $this->create_row( [ 'capacity' => 10, 'stock' => 10, 'sales' => 0 ] );
		$order = $this->create_row_order( [ $id => 3 ] );
		$this->assertSame( [ 7, 3 ], $this->stock_and_sales( $id ) );

		// As when ECP is deactivated: its custom tables never finish activating.
		global $wp_actions;
		$fired = $wp_actions['tec_events_pro_custom_tables_v1_fully_activated'] ?? null;
		unset( $wp_actions['tec_events_pro_custom_tables_v1_fully_activated'] );
		tribe( Rows::class )->forget( Ticket_ID::to_row_id( $id ) );

		try {
			tribe( Order::class )->modify_status( $order->ID, Refunded::SLUG );
		} finally {
			if ( null !== $fired ) {
				$wp_actions['tec_events_pro_custom_tables_v1_fully_activated'] = $fired;
			}
		}

		$this->assertSame( [ 10, 0 ], $this->stock_and_sales( $id ) );
	}

	/**
	 * @test
	 */
	public function it_should_count_sales_only_for_an_unlimited_table_ticket(): void {
		$id = $this->create_row( [ 'capacity' => -1, 'stock' => null, 'sales' => 1 ] );

		$this->create_row_order( [ $id => 4 ] );

		$this->assertSame( [ null, 5 ], $this->stock_and_sales( $id ) );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_an_order_for_more_than_the_row_has_left(): void {
		$id = $this->create_row( [ 'capacity' => 10, 'stock' => 1, 'sales' => 9 ] );

		$order = $this->create_row_order( [ $id => 2 ] );

		$this->assertNotSame( 'tec-' . Completed::SLUG, $order->post_status );
		$this->assertSame( [ 1, 9 ], $this->stock_and_sales( $id ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_oversell_a_row_sold_out_between_the_checkout_check_and_the_sale(): void {
		$id   = $this->create_row( [ 'capacity' => 10, 'stock' => 2, 'sales' => 8 ] );
		$logs = [];
		add_action(
			'tribe_log',
			static function ( $level, $message, $context = [] ) use ( &$logs ) {
				if ( 'warning' === $level ) {
					$logs[] = $context;
				}
			},
			10,
			3
		);
		// Another buyer takes one ticket after this order's stock check, before its sale.
		add_action(
			'tec_tickets_commerce_order_status_flag_decrease_stock',
			static function () use ( $id ) {
				tribe( Stock::class )->sell( Ticket_ID::to_row_id( $id ), 1 );
			},
			9
		);

		$this->create_row_order( [ $id => 2 ] );

		$this->assertSame( [ 1, 9 ], $this->stock_and_sales( $id ) );
		$this->assertContains( $id, array_column( $logs, 'ticket_id' ) );
	}

	/**
	 * @param array<string,mixed> $values Column values.
	 *
	 * @return int The ticket ID of a row on a real date of a recurring event.
	 */
	private function create_row( array $values ): int {
		$event = $this->create_recurring_event();

		return $this->insert_ticket_row(
			array_merge( [ 'post_id' => $event, 'occurrence_id' => $this->get_dates( $event )[0]->occurrence_id ], $values )
		);
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
