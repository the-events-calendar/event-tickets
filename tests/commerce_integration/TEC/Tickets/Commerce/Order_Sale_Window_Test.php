<?php
/**
 * Integration tests for the ticket sale window at order creation.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Gateways\Free\Gateway as Free_Gateway;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Date_Utils as Dates;
use WP_Error;
use WP_Post;
use WP_REST_Request;

/**
 * Class Order_Sale_Window_Test.
 *
 * @since TBD
 *
 * @covers \TEC\Tickets\Commerce\Sale_Window_Validator
 * @covers \TEC\Tickets\Commerce\Order::create_from_cart
 * @covers \TEC\Tickets\Commerce\Gateways\Free\REST\Order_Endpoint::handle_create_order
 * @covers \TEC\Tickets\Commerce\Gateways\PayPal\REST\Order_Endpoint::handle_create_order
 * @covers \TEC\Tickets\Commerce\Gateways\Stripe\REST\Order_Endpoint::handle_create_order
 * @covers \TEC\Tickets\Commerce\Gateways\Square\REST\Order_Endpoint::handle_create_order
 * @covers \TEC\Tickets\Commerce\Status\Pending::can_apply_to
 *
 * @package TEC\Tickets\Commerce
 */
class Order_Sale_Window_Test extends WPTestCase {

	use Ticket_Maker;
	use With_Tickets_Commerce;

	/**
	 * Empties the cart, which the Cart singleton otherwise carries from one test into the next.
	 *
	 * @before
	 * @after
	 */
	public function clear_cart(): void {
		tribe( Cart::class )->clear_cart();
	}

	/**
	 * @test
	 */
	public function it_should_create_an_order_while_the_ticket_is_on_sale(): void {
		$ticket_id = $this->add_free_ticket_to_cart();

		$order = tribe( Order::class )->create_from_cart( tribe( Free_Gateway::class ), $this->purchaser() );

		$this->assertInstanceOf( WP_Post::class, $order );
		$this->assertSame( [ $ticket_id ], array_column( $order->items, 'ticket_id' ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_create_an_order_when_the_sale_ended_after_the_ticket_was_added_to_the_cart(): void {
		$ticket_id = $this->add_free_ticket_to_cart();
		$this->end_sale( $ticket_id );

		$order = tribe( Order::class )->create_from_cart( tribe( Free_Gateway::class ), $this->purchaser() );

		$this->assertFalse( $order );
	}

	/**
	 * @test
	 */
	public function it_should_not_create_an_order_when_one_of_several_tickets_is_no_longer_on_sale(): void {
		$this->add_free_ticket_to_cart();
		$ended_ticket_id = $this->add_free_ticket_to_cart();
		$this->end_sale( $ended_ticket_id );

		$order = tribe( Order::class )->create_from_cart( tribe( Free_Gateway::class ), $this->purchaser() );

		$this->assertFalse( $order );
	}

	/**
	 * The create-order route of every Tickets Commerce gateway, with a ticket price that route accepts.
	 *
	 * @return array<string,array{0:string,1:int}>
	 */
	public function create_order_route_provider(): array {
		return [
			'free'   => [ '/tribe/tickets/v1/commerce/free/order', 0 ],
			'paypal' => [ '/tribe/tickets/v1/commerce/paypal/order', 10 ],
			'stripe' => [ '/tribe/tickets/v1/commerce/stripe/order', 10 ],
			'square' => [ '/tribe/tickets/v1/commerce/square/order', 10 ],
		];
	}

	/**
	 * @test
	 * @dataProvider create_order_route_provider
	 */
	public function it_should_refuse_the_checkout_when_the_sale_ended_after_the_ticket_was_added_to_the_cart( string $route, int $price ): void {
		$ticket_id = $this->add_ticket_to_cart( $price );
		$this->end_sale( $ticket_id );
		$stock_before  = tribe( Ticket::class )->get_ticket( $ticket_id )->stock();
		$orders_before = tec_tc_orders()->by( 'status', 'any' )->count();

		$response = rest_do_request( $this->order_request( $route ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'tec-tc-ticket-unavailable', $response->as_error()->get_error_code() );
		$this->assertSame( $orders_before, tec_tc_orders()->by( 'status', 'any' )->count() );
		$this->assertSame( $stock_before, tribe( Ticket::class )->get_ticket( $ticket_id )->stock() );
	}

	/**
	 * @test
	 */
	public function it_should_complete_the_free_checkout_while_the_ticket_is_on_sale(): void {
		$this->add_free_ticket_to_cart();

		$response = rest_do_request( $this->order_request( '/tribe/tickets/v1/commerce/free/order' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
	}

	/**
	 * @test
	 */
	public function it_should_report_a_ticket_whose_sale_has_ended_as_no_longer_available(): void {
		$ticket_id = $this->add_free_ticket_to_cart();
		$this->end_sale( $ticket_id );

		$result = tribe( Sale_Window_Validator::class )->validate_items( tribe( Cart::class )->get_items_in_cart() );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'tec-tc-ticket-unavailable', $result->get_error_code() );
		$this->assertSame( $ticket_id, $result->get_error_data()['ticket_id'] );
	}

	/**
	 * @test
	 */
	public function it_should_report_a_ticket_whose_sale_has_not_started_with_its_start_date(): void {
		$ticket_id = $this->add_free_ticket_to_cart();
		$start     = Dates::build_date_object( '+2 days' );
		update_post_meta( $ticket_id, Ticket::START_DATE_META_KEY, $start->format( Dates::DBDATEFORMAT ) );
		update_post_meta( $ticket_id, Ticket::START_TIME_META_KEY, $start->format( Dates::DBTIMEFORMAT ) );

		$result = tribe( Sale_Window_Validator::class )->validate_items( tribe( Cart::class )->get_items_in_cart() );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'tec-tc-ticket-unavailable', $result->get_error_code() );
		$this->assertStringContainsString( $start->format( Dates::DBDATEFORMAT ), $result->get_error_message() );
	}

	/**
	 * @test
	 */
	public function it_should_report_a_ticket_with_no_sale_end_on_an_ended_event_as_unavailable(): void {
		$event_id  = tribe_events()->set_args(
			[
				'title'      => 'Ended Event',
				'status'     => 'publish',
				'start_date' => Dates::build_date_object( '-2 days' )->format( Dates::DBDATETIMEFORMAT ),
				'duration'   => HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_tc_ticket( $event_id, 0 );
		delete_post_meta( $ticket_id, Ticket::END_DATE_META_KEY );
		delete_post_meta( $ticket_id, Ticket::END_TIME_META_KEY );

		$result = tribe( Sale_Window_Validator::class )->validate_items( [ [ 'ticket_id' => $ticket_id ] ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame(
			sprintf( 'There are no %s available at this time.', tribe_get_ticket_label_plural( 'unavailable_mixed' ) ),
			$result->get_error_message()
		);
	}

	/**
	 * @test
	 */
	public function it_should_not_let_a_pending_order_complete_once_its_ticket_sale_ended(): void {
		$ticket_id = $this->add_free_ticket_to_cart();
		$order     = tribe( Order::class )->create_from_cart( tribe( Free_Gateway::class ), $this->purchaser() );
		$this->end_sale( $ticket_id );

		$result = tribe( Pending::class )->can_apply_to( $order, tribe( Completed::class ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'tec-tc-ticket-unavailable', $result->get_error_code() );
		$this->assertSame(
			sprintf( '%s are no longer available.', tribe_get_ticket_label_plural( 'unavailable_past' ) ),
			$result->get_error_message()
		);
	}

	/**
	 * @test
	 */
	public function it_should_accept_cart_items_that_are_on_sale(): void {
		$this->add_free_ticket_to_cart();
		$this->add_free_ticket_to_cart();

		$result = tribe( Sale_Window_Validator::class )->validate_items( tribe( Cart::class )->get_items_in_cart() );

		$this->assertTrue( $result );
	}

	/**
	 * Puts one free ticket, on sale now, in the cart.
	 *
	 * @return int The ticket ID.
	 */
	private function add_free_ticket_to_cart(): int {
		return $this->add_ticket_to_cart( 0 );
	}

	/**
	 * Puts one ticket, on sale now, in the cart.
	 *
	 * @param int $price The ticket price.
	 *
	 * @return int The ticket ID.
	 */
	private function add_ticket_to_cart( int $price ): int {
		$ticket_id = $this->create_tc_ticket( self::factory()->post->create(), $price );
		tribe( Cart::class )->add_ticket( $ticket_id, 1 );

		return $ticket_id;
	}

	/**
	 * Moves the end of a ticket's sale window into the past, as an admin editing the ticket would.
	 *
	 * @param int $ticket_id The ticket ID.
	 */
	private function end_sale( int $ticket_id ): void {
		$end = Dates::build_date_object( '-1 day' );
		update_post_meta( $ticket_id, Ticket::END_DATE_META_KEY, $end->format( Dates::DBDATEFORMAT ) );
		update_post_meta( $ticket_id, Ticket::END_TIME_META_KEY, $end->format( Dates::DBTIMEFORMAT ) );
	}

	/**
	 * Builds a JSON create-order request carrying only a purchaser, which every gateway route accepts.
	 *
	 * @param string $route The create-order route of a gateway.
	 *
	 * @return WP_REST_Request
	 */
	private function order_request( string $route ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', $route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'purchaser' => [
						'name'  => 'Test Buyer',
						'email' => 'buyer@example.com',
					],
				]
			)
		);

		return $request;
	}

	/**
	 * Gets the purchaser of the test orders.
	 *
	 * @return array{purchaser_user_id:int,purchaser_full_name:string,purchaser_first_name:string,purchaser_last_name:string,purchaser_email:string}
	 */
	private function purchaser(): array {
		return [
			'purchaser_user_id'    => get_current_user_id(),
			'purchaser_full_name'  => 'Test Buyer',
			'purchaser_first_name' => 'Test',
			'purchaser_last_name'  => 'Buyer',
			'purchaser_email'      => 'buyer@example.com',
		];
	}
}
