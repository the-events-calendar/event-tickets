<?php
/**
 * Integration tests for the purchase login requirement on the gateway create-order REST routes.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways
 */

namespace TEC\Tickets\Commerce\Gateways;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Free\Gateway as Free_Gateway;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\RSVP\V2\Constants as RSVP_Constants;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_Error;
use WP_Post;
use WP_REST_Request;

/**
 * Class Purchase_Login_Requirement_Test.
 *
 * @since TBD
 *
 * @covers \TEC\Tickets\Commerce\Gateways\Contracts\Traits\Requires_Login_To_Purchase::can_purchase
 * @covers \TEC\Tickets\Commerce\Cart::parse_request
 * @covers \TEC\Tickets\Commerce\Order::create_from_cart
 *
 * @package TEC\Tickets\Commerce\Gateways
 */
class Purchase_Login_Requirement_Test extends WPTestCase {

	use Ticket_Maker;

	/**
	 * Starts and ends every test with an empty cart.
	 *
	 * @before
	 * @after
	 */
	public function clear_cart(): void {
		tribe( Cart::class )->clear_cart();
	}

	/**
	 * The create-order route of every Tickets Commerce gateway.
	 *
	 * @return array<string,array{0:string}>
	 */
	public function create_order_route_provider(): array {
		return [
			'free'   => [ '/tribe/tickets/v1/commerce/free/order' ],
			'paypal' => [ '/tribe/tickets/v1/commerce/paypal/order' ],
			'stripe' => [ '/tribe/tickets/v1/commerce/stripe/order' ],
			'square' => [ '/tribe/tickets/v1/commerce/square/order' ],
		];
	}

	/**
	 * @test
	 * @dataProvider create_order_route_provider
	 */
	public function it_should_refuse_a_guest_when_purchase_requires_login( string $route ): void {
		$this->require_purchase_login( true );
		wp_set_current_user( 0 );

		$response = rest_do_request( new WP_REST_Request( 'POST', $route ) );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'tec_tickets_commerce_login_required', $response->as_error()->get_error_code() );
	}

	/**
	 * @test
	 * @dataProvider create_order_route_provider
	 */
	public function it_should_let_a_guest_through_when_purchase_does_not_require_login( string $route ): void {
		$this->require_purchase_login( false );
		wp_set_current_user( 0 );

		$this->assertTrue( $this->route_permits( $route ) );
	}

	/**
	 * @test
	 * @dataProvider create_order_route_provider
	 */
	public function it_should_let_a_logged_in_user_through_when_purchase_requires_login( string $route ): void {
		$this->require_purchase_login( true );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->assertTrue( $this->route_permits( $route ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_fill_the_cart_of_a_guest_when_purchase_requires_login(): void {
		$this->require_purchase_login( true );
		wp_set_current_user( 0 );

		$this->assertFalse( $this->submit_cart_form() );
		$this->assertEmpty( tribe( Cart::class )->get_items_in_cart() );
	}

	/**
	 * @test
	 */
	public function it_should_fill_the_cart_of_a_logged_in_user_when_purchase_requires_login(): void {
		$this->require_purchase_login( true );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->assertTrue( $this->submit_cart_form() );
		$this->assertCount( 1, tribe( Cart::class )->get_items_in_cart() );
	}

	/**
	 * @test
	 */
	public function it_should_fill_the_cart_of_a_guest_when_purchase_does_not_require_login(): void {
		$this->require_purchase_login( false );
		wp_set_current_user( 0 );

		$this->assertTrue( $this->submit_cart_form() );
		$this->assertCount( 1, tribe( Cart::class )->get_items_in_cart() );
	}

	/**
	 * @test
	 */
	public function it_should_not_create_an_order_for_a_guest_when_purchase_requires_login(): void {
		$this->require_purchase_login( true );
		wp_set_current_user( 0 );
		$this->add_free_ticket_to_cart();

		$order = tribe( Order::class )->create_from_cart( tribe( Free_Gateway::class ), $this->purchaser() );

		$this->assertFalse( $order );
	}

	/**
	 * @test
	 */
	public function it_should_create_an_order_for_a_logged_in_user_when_purchase_requires_login(): void {
		$this->require_purchase_login( true );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$this->add_free_ticket_to_cart();

		$order = tribe( Order::class )->create_from_cart( tribe( Free_Gateway::class ), $this->purchaser() );

		$this->assertInstanceOf( WP_Post::class, $order );
	}

	/**
	 * @test
	 */
	public function it_should_not_create_an_rsvp_order_for_a_guest_when_rsvp_requires_login(): void {
		add_filter( 'tribe_get_option_ticket-authentication-requirements', static fn() => [ 'event-tickets_rsvp' ] );
		wp_set_current_user( 0 );

		$order = tribe( Order::class )->create_from_cart( tribe( Free_Gateway::class ), $this->purchaser(), RSVP_Constants::TC_RSVP_TYPE );

		$this->assertFalse( $order );
	}

	/**
	 * @test
	 */
	public function it_should_not_create_an_order_for_a_guest_with_an_rsvp_in_the_generic_cart_when_rsvp_requires_login(): void {
		add_filter( 'tribe_get_option_ticket-authentication-requirements', static fn() => [ 'event-tickets_rsvp' ] );
		wp_set_current_user( 0 );
		$rsvp_id = $this->create_tc_ticket( self::factory()->post->create(), 0 );
		update_post_meta( $rsvp_id, Ticket::$type_meta_key, RSVP_Constants::TC_RSVP_TYPE );
		// The generic cart takes any ticket ID, so a TC-RSVP ticket can reach a gateway order as a 'ticket' item.
		tribe( Cart::class )->add_ticket( $rsvp_id, 1 );

		$order = tribe( Order::class )->create_from_cart( tribe( Free_Gateway::class ), $this->purchaser() );

		$this->assertFalse( $order );
	}

	/**
	 * @test
	 */
	public function it_should_create_an_order_for_a_guest_without_an_rsvp_in_the_cart_when_only_rsvp_requires_login(): void {
		add_filter( 'tribe_get_option_ticket-authentication-requirements', static fn() => [ 'event-tickets_rsvp' ] );
		wp_set_current_user( 0 );
		$this->add_free_ticket_to_cart();

		$order = tribe( Order::class )->create_from_cart( tribe( Free_Gateway::class ), $this->purchaser() );

		$this->assertInstanceOf( WP_Post::class, $order );
	}

	/**
	 * Posts the front-end tickets form to the cart handler, as `?tec-tc-cart=redirect` does.
	 *
	 * @return bool Whether the cart handler processed the form.
	 */
	private function submit_cart_form(): bool {
		// The cart only takes readable tickets: on a post type that can have tickets (pages by default), with the ticket post type registered.
		$event_id  = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $event_id, 0 );

		if ( ! post_type_exists( Ticket::POSTTYPE ) ) {
			tribe( Ticket::class )->register_post_type();
		}

		$_GET[ Cart::$url_query_arg ] = Cart::REDIRECT_MODE;
		$_POST['tribe_tickets_ar_data'] = wp_json_encode(
			[
				'tribe_tickets_tickets' => [ [ 'ticket_id' => $ticket_id, 'quantity' => 1 ] ],
				'tribe_tickets_post_id' => $event_id,
			]
		);

		// Stay on the page instead of redirecting to checkout.
		add_filter( 'tec_tickets_commerce_cart_to_checkout_redirect_url', '__return_null' );

		try {
			return tribe( Cart::class )->parse_request();
		} finally {
			unset( $_GET[ Cart::$url_query_arg ], $_POST['tribe_tickets_ar_data'] );
		}
	}

	/**
	 * Puts one free ticket in the cart.
	 */
	private function add_free_ticket_to_cart(): void {
		tribe( Cart::class )->add_ticket( $this->create_tc_ticket( self::factory()->post->create(), 0 ), 1 );
	}

	/**
	 * Gets the purchaser of the test orders.
	 *
	 * @return array<string,string>
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

	/**
	 * Runs the permission check of a route's POST handler, without running the handler itself.
	 *
	 * @param string $route The REST route.
	 *
	 * @return bool|WP_Error The permission check result.
	 */
	private function route_permits( string $route ) {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( $route, $routes );
		$handler = $routes[ $route ][0];

		return call_user_func( $handler['permission_callback'], new WP_REST_Request( 'POST', $route ) );
	}

	/**
	 * Turns the "Require users to log in before they purchase tickets" setting on or off for this test.
	 *
	 * A filter rather than the option, because the settings cache outlives the test's database rollback.
	 *
	 * @param bool $required Whether purchase requires login.
	 */
	private function require_purchase_login( bool $required ): void {
		add_filter(
			'tribe_get_option_ticket-authentication-requirements',
			static fn() => $required ? [ 'event-tickets_all' ] : []
		);
	}
}
