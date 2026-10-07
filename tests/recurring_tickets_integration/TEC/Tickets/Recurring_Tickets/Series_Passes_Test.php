<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Events_Pro\Custom_Tables\V1\Models\Series_Relationship;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Manual\Gateway as Manual;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * Series Passes and recurring event tickets sell from the same date's page.
 */
class Series_Passes_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;

	/**
	 * @after
	 */
	public function clean_up(): void {
		// Completing an order commits the test's database transaction.
		tribe( Cart::class )->clear_cart();
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_list_and_sell_a_dates_rows_and_its_series_pass_together(): void {
		( new Tickets() )->empty_table();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$event  = $this->create_recurring_event();
		$series = (int) Series_Relationship::find( $event, 'event_post_id' )->series_post_id;
		$this->assertGreaterThan( 0, $series, 'ECP puts a recurring event in a Series.' );
		$pass     = $this->create_tc_ticket( $series, 30, [ 'ticket_name' => 'Series Pass', 'ticket_type' => 'series_pass' ] );
		$template = $this->create_tc_ticket( $event, 10, [ 'ticket_name' => 'General Admission' ] );
		wp_set_current_user( 0 );

		$second = $this->get_dates( $event )[1];
		$date   = (int) $second->provisional_id;
		$rows   = array_filter( tribe( Rows::class )->get_by_template( $template ), static fn( $row ) => (int) $row->occurrence_id === (int) $second->occurrence_id );
		$row    = Ticket_ID::from_row_id( (int) reset( $rows )->id );

		$listed = array_map( static fn( $ticket ) => (int) $ticket->ID, tribe( Module::class )->get_tickets( $date ) );
		$this->assertContains( $row, $listed );
		$this->assertContains( $pass, $listed );
		$this->assertNotContains( $template, $listed );

		$cart = tribe( Cart::class );
		$cart->set_cart_hash( uniqid( 'cart-', true ) );
		$cart->add_ticket( $row, 1 );
		$cart->add_ticket( $pass, 1 );
		$order = tribe( Order::class )->create_from_cart(
			tribe( Manual::class ),
			[
				'purchaser_user_id'    => 0,
				'purchaser_full_name'  => 'Both Buyer',
				'purchaser_first_name' => 'Both',
				'purchaser_last_name'  => 'Buyer',
				'purchaser_email'      => 'both@test.com',
			]
		);
		tribe( Order::class )->modify_status( $order->ID, Completed::SLUG );

		$tickets = array_map(
			static fn( $attendee ) => (int) get_post_meta( $attendee, '_tec_tickets_commerce_ticket', true ),
			get_posts( [ 'post_type' => 'tec_tc_attendee', 'post_parent' => $order->ID, 'post_status' => 'any', 'fields' => 'ids' ] )
		);
		sort( $tickets );
		$this->assertSame( [ $pass, $row ], $tickets );

		tribe( Rows::class )->forget( Ticket_ID::to_row_id( $row ) );
		$this->assertSame( 1, (int) tribe( Rows::class )->find( Ticket_ID::to_row_id( $row ) )->sales );
		$this->assertSame( 1, (int) get_post_meta( $pass, 'total_sales', true ) );
	}
}
