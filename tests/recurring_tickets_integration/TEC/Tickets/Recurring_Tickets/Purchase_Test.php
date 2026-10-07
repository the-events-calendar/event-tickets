<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Manual\Gateway as Manual;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Shortcodes\Checkout_Shortcode;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Emails\JSON_LD\Order_Schema;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_REST_Request;

/**
 * A recurring event ticket is bought on its date's page from the cart to the attendee.
 */
class Purchase_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;

	/**
	 * The event.
	 *
	 * @var int
	 */
	private int $event = 0;

	/**
	 * The second date's ID.
	 *
	 * @var int
	 */
	private int $date = 0;

	/**
	 * The ticket ID of the second date's row.
	 *
	 * @var int
	 */
	private int $ticket = 0;

	/**
	 * @before
	 */
	public function create_event(): void {
		( new Tickets() )->empty_table();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->event = $this->create_recurring_event();
		$template    = $this->create_tc_ticket( $this->event, 10.5, [ 'ticket_name' => 'General Admission' ] );
		wp_set_current_user( 0 );

		$second       = $this->get_dates( $this->event )[1];
		$this->date   = (int) $second->provisional_id;
		$rows         = array_filter( tribe( Rows::class )->get_by_template( $template ), static fn( $row ) => (int) $row->occurrence_id === (int) $second->occurrence_id );
		$this->ticket = Ticket_ID::from_row_id( (int) reset( $rows )->id );

		$cart = tribe( Cart::class );
		$cart->set_cart_hash( uniqid( 'cart-', true ) );
		$cart->add_ticket( $this->ticket, 2 );
	}

	/**
	 * Completing an order commits the database transaction the test runs in: clean up what it would not undo.
	 *
	 * @after
	 */
	public function clean_up(): void {
		tribe( Cart::class )->clear_cart();
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_show_the_row_on_the_checkout_page(): void {
		$html = tribe( Checkout_Shortcode::class )->get_html();

		$this->assertStringContainsString( 'data-ticket-id="' . $this->ticket . '"', $html );
		$this->assertStringContainsString( 'General Admission', $html );
		$this->assertStringContainsString( 'data-ticket-price="10.50"', $html );
	}

	/**
	 * @test
	 */
	public function it_should_complete_an_order_for_the_row_and_its_date(): void {
		$order = $this->complete_order();

		$this->assertCount( 1, $order->items );
		$item = reset( $order->items );
		$this->assertSame( [ $this->ticket, $this->date, 2 ], [ (int) $item['ticket_id'], (int) $item['event_id'], (int) $item['quantity'] ] );

		$attendees = get_posts( [ 'post_type' => 'tec_tc_attendee', 'post_parent' => $order->ID, 'post_status' => 'any', 'fields' => 'ids' ] );
		$this->assertCount( 2, $attendees );
		foreach ( $attendees as $attendee_id ) {
			$this->assertSame( (string) $this->ticket, get_post_meta( $attendee_id, '_tec_tickets_commerce_ticket', true ) );
			$this->assertSame( (string) $this->date, get_post_meta( $attendee_id, '_tec_tickets_commerce_event', true ) );
			$this->assertSame( 'General Admission', tec_tc_get_attendee( $attendee_id )->ticket_name, 'Not a deleted ticket.' );
		}

		tribe( Rows::class )->forget( Ticket_ID::to_row_id( $this->ticket ) );
		$row = tribe( Rows::class )->find( Ticket_ID::to_row_id( $this->ticket ) );
		$this->assertSame( [ 2, 98 ], [ (int) $row->sales, (int) $row->stock ] );
	}

	/**
	 * @test
	 */
	public function it_should_name_the_row_in_the_order_emails_json_ld(): void {
		$schema = tribe( Order_Schema::class );
		$order  = tec_tc_get_order( $this->complete_order()->ID );
		// The schema takes its order from an email: give it the order directly.
		\Closure::bind( fn() => $this->order = $order, $schema, Order_Schema::class )();

		$offers = $schema->build_data()['acceptedOffer'];

		$this->assertSame( 'General Admission', $offers[0]['itemOffered']['name'] );
	}

	/**
	 * @test
	 */
	public function it_should_read_a_published_row_over_rest(): void {
		$this->assertTrue( tribe( 'tickets.handler' )->is_ticket_readable( $this->ticket ) );
		$this->assertInstanceOf( \TEC\Tickets\Commerce\Module::class, tribe_tickets_get_ticket_provider( $this->ticket ) );

		$response = rest_do_request( new WP_REST_Request( 'GET', "/tribe/tickets/v1/tickets/{$this->ticket}" ) );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertSame( 'General Admission', $response->get_data()['title'] );
	}

	/**
	 * @test
	 */
	public function it_should_give_the_rows_price_and_find_no_missing_row(): void {
		$this->assertSame( '10.50', tribe( Ticket::class )->get_price_value( $this->ticket )->get_string() );

		$missing = tribe( 'tickets.handler' )->is_ticket_readable( Ticket_ID::from_row_id( 999999 ) );
		$this->assertInstanceOf( \WP_Error::class, $missing );
		$this->assertSame( 404, $missing->get_error_data()['status'] );
	}

	/**
	 * Creates the order from the cart and completes it.
	 *
	 * @return \WP_Post The order.
	 */
	private function complete_order(): \WP_Post {
		$order = tribe( Order::class )->create_from_cart(
			tribe( Manual::class ),
			[
				'purchaser_user_id'    => 0,
				'purchaser_full_name'  => 'Row Buyer',
				'purchaser_first_name' => 'Row',
				'purchaser_last_name'  => 'Buyer',
				'purchaser_email'      => 'row-buyer@test.com',
			]
		);
		tribe( Order::class )->modify_status( $order->ID, Completed::SLUG );

		return tec_tc_get_order( $order->ID );
	}
}
