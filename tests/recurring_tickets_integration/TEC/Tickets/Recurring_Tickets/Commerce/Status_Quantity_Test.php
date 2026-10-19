<?php

namespace TEC\Tickets\Recurring_Tickets\Commerce;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Reports\Data\Order_Summary;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Recurring_Tickets\Template_Guard;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use TEC\Tickets\Tests\Recurring_Tickets\Row_Orders;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * A row's ticket, which has no post, gives the quantity ordered in each status from its orders.
 */
class Status_Quantity_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;
	use Row_Orders;

	/**
	 * The first date of the event.
	 *
	 * @var object
	 */
	private object $date;

	/**
	 * The ticket of the first date's row.
	 *
	 * @var int
	 */
	private int $ticket_id = 0;

	/**
	 * @before
	 */
	public function create_row(): void {
		( new Tickets() )->empty_table();
		$event    = $this->create_recurring_event();
		$template = $this->create_tc_ticket( $event, 10 );
		// As the ticket editor saves a ticket on a recurring event.
		update_post_meta( $template, '_type', Template_Guard::TICKET_TYPE );
		$this->date = $this->get_dates( $event )[0];
		$row        = array_column( tribe( Rows::class )->get_by_post( $event ), null, 'occurrence_id' )[ $this->date->occurrence_id ];

		$this->ticket_id = Ticket_ID::from_row_id( (int) $row->id );
	}

	/**
	 * @after
	 */
	public function clean_up(): void {
		set_current_screen( 'front' );
		// Completing an order commits the test's database transaction.
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_count_a_rows_orders_by_status(): void {
		$completed = 2;
		$pending   = 1;
		$this->create_row_order( [ $this->ticket_id => $completed ] );
		$this->create_row_order( [ $this->ticket_id => $pending ], Pending::SLUG );

		$quantities = tribe( Ticket::class )->get_status_quantity( $this->ticket_id );

		$this->assertIsArray( $quantities );
		$this->assertSame( $completed, $quantities[ Completed::SLUG ] );
		$this->assertSame( $pending, $quantities[ Pending::SLUG ] );
	}

	/**
	 * @test
	 */
	public function it_should_summarize_the_orders_of_one_date(): void {
		$quantity = 3;
		$this->create_row_order( [ $this->ticket_id => $quantity ] );
		// Only an admin screen lists a date's rows among its tickets.
		set_current_screen( 'tribe_events_page_tickets-commerce-orders' );

		// As the Orders page of the date does.
		$summary = new Order_Summary( (int) $this->date->provisional_id );
		$summary->init();

		$line = $summary->get_tickets_by_type()[ Template_Guard::TICKET_TYPE ][0];
		$this->assertSame( $this->ticket_id, (int) $line['ticket']->ID );
		$this->assertSame( $quantity, $line['qty_data'][ Completed::SLUG ] );
		$this->assertSame( $quantity, $summary->get_event_sales_data()['total_sales']['qty'] );
	}
}
