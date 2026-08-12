<?php
/**
 * End-to-end regression test for the Stripe order-update credential fallback.
 *
 * The fallback in Order_Endpoint::request_carries_order_credential() is only worth anything if the
 * create-order handler actually persists the Payment Intent client secret on the order. This drives
 * the real handle_create_order() and then asks the real permission callback whether a buyer who lost
 * their cart cookie can still finalize the order they just paid for.
 *
 * The Stripe API is stubbed out; nothing here touches the network.
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe\REST
 */

namespace TEC\Tickets\Commerce\Gateways\Stripe\REST;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Stripe\Payment_Intent_Handler;
use TEC\Tickets\Commerce\Utils\Currency;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Settings_Manager;
use WP_REST_Request;

class Order_Endpoint_Credential_Round_Trip_Test extends WPTestCase {

	use With_Uopz;
	use Ticket_Maker;

	private const PAYMENT_INTENT = 'pi_test_roundtrip';
	private const CLIENT_SECRET  = 'pi_test_roundtrip_secret_9f8e7d';

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
	 * Stubs the one call that would otherwise reach Stripe, returning a Payment Intent shaped like the
	 * real API response.
	 */
	private function stub_stripe_payment_intent(): void {
		$this->set_class_fn_return(
			Payment_Intent_Handler::class,
			'update_payment_intent',
			[
				'id'            => self::PAYMENT_INTENT,
				'client_secret' => self::CLIENT_SECRET,
				'status'        => 'requires_payment_method',
				'amount'        => '1000',
				'created'       => 1786000000,
				'currency'      => 'usd',
			]
		);
	}

	/**
	 * Runs the real create-order handler for a cart with one ticket.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	private function create_order() {
		$request = new WP_REST_Request( 'POST', '/tec-tickets/v1/commerce/stripe/order' );
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
	 * Creating an order must store the client secret on it, and a buyer presenting that secret must be
	 * able to finalize the order after their cart cookie is gone. Before the fallback existed, that
	 * second request answered 401 and the paid order stayed stranded in `tec-tc-created`.
	 */
	public function test_created_order_stores_the_secret_that_later_authorizes_the_update(): void {
		$this->make_cart_with_ticket();
		$this->stub_stripe_payment_intent();

		$response = $this->create_order();
		$data     = $response->get_data();

		$this->assertTrue( $data['success'] ?? false, 'The order should have been created.' );
		$this->assertSame(
			self::CLIENT_SECRET,
			$data['client_secret'] ?? '',
			'The create response must hand the buyer the client secret.'
		);

		clean_post_cache( $data['order_id'] );

		$order = tec_tc_get_order( $data['order_id'] );

		$stored_secrets = [];
		foreach ( (array) $order->gateway_payload as $status_payloads ) {
			foreach ( (array) $status_payloads as $payload ) {
				if ( is_array( $payload ) && isset( $payload['client_secret'] ) ) {
					$stored_secrets[] = $payload['client_secret'];
				}
			}
		}

		$this->assertContains(
			self::CLIENT_SECRET,
			$stored_secrets,
			'The order must persist the client secret, otherwise the update fallback can never match.'
		);

		// The buyer pays, comes back, and their cart cookie did not survive the round trip.
		$this->set_class_property( tribe( Cart::class )->get_repository(), 'cart_hash', '' );

		$update_request = new WP_REST_Request( 'POST', '/tec-tickets/v1/commerce/stripe/order' );
		$update_request->set_param( 'order_id', self::PAYMENT_INTENT );
		$update_request->set_param( 'client_secret', self::CLIENT_SECRET );

		$this->assertTrue(
			tribe( Order_Endpoint::class )->current_user_can_edit_order( $update_request ),
			'A buyer who has paid must be able to finalize the order with the secret this site issued, even with no cart cookie.'
		);

		$update_request->set_param( 'client_secret', 'pi_test_roundtrip_secret_WRONG' );

		$this->assertFalse(
			tribe( Order_Endpoint::class )->current_user_can_edit_order( $update_request ),
			'A mismatched client secret must still be refused.'
		);
	}
}
