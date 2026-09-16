<?php
/**
 * End-to-end regression test for the PayPal order-update credential fallback.
 *
 * The fallback in Order_Endpoint::request_carries_order_credential() is only worth anything if the
 * create-order handler actually issues a credential and hands it to the buyer. This drives the real
 * handle_create_order() and then asks the real permission callback whether a buyer who lost their
 * cart cookie can still finalize the order they just approved.
 *
 * The PayPal API is stubbed out; nothing here touches the network.
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal\REST
 */

namespace TEC\Tickets\Commerce\Gateways\PayPal\REST;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\PayPal\Client;
use TEC\Tickets\Commerce\Gateways\PayPal\Order_Credential;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Utils\Currency;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Settings_Manager;
use WP_REST_Request;

class Order_Endpoint_Credential_Round_Trip_Test extends WPTestCase {

	use With_Uopz;
	use Ticket_Maker;

	private const PAYPAL_ORDER = '37E53548EJ332603U';

	/**
	 * Registers the order post statuses with WordPress, as the plugin does on `init`, so the lookup in
	 * the permission callback matches production.
	 */
	public function setUp(): void {
		parent::setUp();

		tribe( Status_Handler::class )->register_order_statuses();
	}

	/**
	 * make_cart_with_ticket() writes tribe options, which are memoized in an in-memory var the
	 * per-test DB rollback does not touch. Clear it, and the shared cart, so later tests are not
	 * affected.
	 */
	public function tearDown(): void {
		tribe( Cart::class )->clear_cart();
		tribe_set_var( Tribe__Settings_Manager::OPTION_CACHE_VAR_NAME, [] );

		parent::tearDown();
	}

	/**
	 * Creating an order must issue a credential and hand it to the buyer, and that credential must then
	 * authorize the capture after the cart cookie is gone. Before the fallback existed that second
	 * request answered 403, the capture never reached PayPal, and the order stayed pending with no
	 * attendee generated.
	 */
	public function test_created_order_issues_the_credential_that_later_authorizes_the_update(): void {
		$this->make_cart_with_ticket();
		$this->stub_paypal_create_order();

		$response = $this->create_order();

		// handle_create_order() can return a WP_Error, which has no get_data() and would fatal below.
		$this->assertNotWPError( $response, 'The create-order handler must not return an error.' );

		$data = $response->get_data();

		$this->assertTrue( $data['success'] ?? false, 'The order should have been created.' );

		$credential = $data[ Order_Credential::REQUEST_PARAM ] ?? '';

		$this->assertNotEmpty(
			$credential,
			'The create response must hand the buyer a credential, otherwise the fallback can never match.'
		);

		// The buyer approves the payment, comes back, and their cart cookie did not survive.
		$this->set_class_property( tribe( Cart::class )->get_repository(), 'cart_hash', '' );

		$this->assertTrue(
			tribe( Order_Endpoint::class )->current_user_can_edit_order( $this->make_update_request( $credential ) ),
			'A buyer who has approved the payment must be able to finalize the order with the credential this site issued, even with no cart cookie.'
		);

		$this->assertFalse(
			tribe( Order_Endpoint::class )->current_user_can_edit_order( $this->make_update_request( $credential . 'x' ) ),
			'A mismatched credential must still be refused.'
		);
	}

	/**
	 * Puts a single $10 ticket in the shared cart so create_from_cart() has something to work with.
	 */
	private function make_cart_with_ticket(): void {
		tribe_update_option( Currency::$currency_code_option, 'USD' );
		tribe_update_option( 'ticket-enabled-post-types', [ 'post', 'page' ] );

		$post   = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket = $this->create_tc_ticket( $post, 10 );

		tribe( Cart::class )->get_repository()->upsert_item( $ticket, 1 );
	}

	/**
	 * Stubs the one call that would otherwise reach PayPal, returning an order shaped like the real API
	 * response.
	 */
	private function stub_paypal_create_order(): void {
		$this->set_class_fn_return(
			Client::class,
			'create_order',
			[
				'id'          => self::PAYPAL_ORDER,
				'status'      => 'CREATED',
				'create_time' => '2026-09-15T12:00:00Z',
			]
		);
	}

	/**
	 * Runs the real create-order handler for a cart with one ticket.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	private function create_order() {
		$request = new WP_REST_Request( 'POST', '/tec-tickets/v1/commerce/paypal/order' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'purchaser' => [
						'name'  => 'Round Trip Buyer',
						'email' => 'roundtrip-buyer@test.com',
					],
				]
			)
		);

		return tribe( Order_Endpoint::class )->handle_create_order( $request );
	}

	/**
	 * Builds the order-update request the buyer's browser sends after approving the payment.
	 */
	private function make_update_request( string $credential ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/tec-tickets/v1/commerce/paypal/order' );
		$request->set_param( 'order_id', self::PAYPAL_ORDER );
		$request->set_param( Order_Credential::REQUEST_PARAM, $credential );

		return $request;
	}
}
