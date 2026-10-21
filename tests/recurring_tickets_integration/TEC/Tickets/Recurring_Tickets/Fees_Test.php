<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Fees as Checkout_Fees;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier_Relationships;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifier_Relationship;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Order_Modifier_Relationships as Relationships_Table;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Row_Orders;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Fee_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * A row carries the fees of its template; fees stay attached to the template.
 */
class Fees_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;
	use Row_Orders;
	use Fee_Creator;

	/**
	 * @after
	 */
	public function empty_table(): void {
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_put_the_templates_fee_on_the_rows_cart_line_and_order(): void {
		[ $template, $row ] = $this->create_template_and_row();
		$fee                = $this->create_fee_for_ticket( $template, [ 'raw_amount' => 2 ] );

		$lines = tribe( Checkout_Fees::class )->append_fees_to_cart( [ $this->cart_line( $row ) ] );
		$order = $this->create_row_order( [ $row => 1 ] );

		$this->assertSame( [ [ $fee, $row ] ], $this->fees_of( $lines ) );
		$this->assertSame( [ [ $fee, $row ] ], $this->fees_of( $order->fees ), 'Tickets Commerce keeps the fee lines of an order apart from its items.' );
	}

	/**
	 * @test
	 */
	public function it_should_apply_a_fee_for_all_tickets_once(): void {
		[ $template, $row ] = $this->create_template_and_row();
		$fee                = $this->create_fee_for_all();

		$lines = tribe( Checkout_Fees::class )->append_fees_to_cart( [ $this->cart_line( $row ) ] );

		$this->assertSame( [ [ $fee, $row ] ], $this->fees_of( $lines ) );
	}

	/**
	 * @test
	 */
	public function it_should_write_no_fee_relationship_for_a_table_ticket(): void {
		[ $template, $row ] = $this->create_template_and_row();
		$fee                = $this->create_fee();

		tribe( Order_Modifier_Relationship::class )->insert(
			new Order_Modifier_Relationships( [ 'modifier_id' => $fee->id, 'post_id' => $row, 'post_type' => 'tec_tc_ticket' ] )
		);

		$this->assertSame( 0, (int) DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i WHERE post_id = %d', Relationships_Table::table_name(), $row ) ) );
	}

	/**
	 * @return int[] A template and the ticket ID of one of its rows.
	 */
	private function create_template_and_row(): array {
		$event    = $this->create_recurring_event();
		$template = $this->create_tc_ticket( $event, 10 );
		update_post_meta( $template, '_type', Template_Guard::TICKET_TYPE );
		$row = $this->insert_ticket_row( [ 'parent_id' => $template, 'post_id' => $event, 'occurrence_id' => $this->get_dates( $event )[0]->occurrence_id, 'price' => 1000 ] );

		return [ $template, $row ];
	}

	/**
	 * @param int $ticket_id The ticket.
	 *
	 * @return array<string,mixed> A cart line for one of it.
	 */
	private function cart_line( int $ticket_id ): array {
		return [ 'ticket_id' => $ticket_id, 'quantity' => 1, 'type' => 'ticket', 'sub_total' => '10.00', 'price' => '10.00' ];
	}

	/**
	 * @param array<int|string,array<string,mixed>> $items Cart or order items.
	 *
	 * @return array<int,array{0: int, 1: int}> The fee ID and ticket ID of each fee line.
	 */
	private function fees_of( array $items ): array {
		$fees = array_filter( $items, static fn( array $item ) => 'fee' === ( $item['type'] ?? '' ) );

		return array_values( array_map( static fn( array $item ) => [ (int) $item['fee_id'], (int) $item['ticket_id'] ], $fees ) );
	}
}
