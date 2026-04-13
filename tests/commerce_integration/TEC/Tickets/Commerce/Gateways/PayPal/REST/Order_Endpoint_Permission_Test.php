<?php
/**
 * Tests for the PayPal Order Endpoint permission callbacks.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal\REST
 */

namespace TEC\Tickets\Commerce\Gateways\PayPal\REST;

use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use WP_REST_Request;

/**
 * Class Order_Endpoint_Permission_Test.
 *
 * @since TBD
 *
 * @covers \TEC\Tickets\Commerce\Gateways\PayPal\REST\Order_Endpoint::can_create
 * @covers \TEC\Tickets\Commerce\Gateways\PayPal\REST\Order_Endpoint::can_delete
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal\REST
 */
class Order_Endpoint_Permission_Test extends WPTestCase {
	use With_Uopz;

	/**
	 * Reset the current user after each test.
	 *
	 * @after
	 */
	public function reset_current_user(): void {
		wp_set_current_user( 0 );
	}

	/**
	 * Builds a POST or DELETE WP_REST_Request with an optional X-WP-Nonce header.
	 *
	 * @param string      $method HTTP method ('POST' or 'DELETE').
	 * @param string|null $nonce  Value for the X-WP-Nonce header, or null to omit it.
	 *
	 * @return WP_REST_Request
	 */
	private function make_request( string $method, ?string $nonce = null ): WP_REST_Request {
		$request = new WP_REST_Request( $method, '/tribe/tickets/v1/commerce/paypal/order' );

		if ( null !== $nonce ) {
			$request->set_header( 'X-WP-Nonce', $nonce );
		}

		return $request;
	}

	/**
	 * Mocks Gateway::is_enabled() to return the given value for the duration of the test.
	 *
	 * @param bool $enabled
	 */
	private function set_gateway_enabled( bool $enabled ): void {
		$this->set_class_fn_return( Gateway::class, 'is_enabled', $enabled );
	}

	/**
	 * Asserts that both can_create and can_delete return the expected value.
	 * Since both methods share identical logic, every scenario is verified for both.
	 *
	 * @param WP_REST_Request $post_request   Request used for can_create.
	 * @param WP_REST_Request $delete_request Request used for can_delete.
	 * @param bool            $expected
	 */
	private function assert_permission( WP_REST_Request $post_request, WP_REST_Request $delete_request, bool $expected ): void {
		$endpoint = new Order_Endpoint();

		$this->assertSame( $expected, $endpoint->can_create( $post_request ), 'can_create result mismatch.' );
		$this->assertSame( $expected, $endpoint->can_delete( $delete_request ), 'can_delete result mismatch.' );
	}

	/**
	 * @test
	 */
	public function should_block_anonymous_request_with_no_nonce(): void {
		wp_set_current_user( 0 );
		$this->set_gateway_enabled( true );

		$this->assert_permission(
			$this->make_request( 'POST' ),
			$this->make_request( 'DELETE' ),
			false
		);
	}

	/**
	 * @test
	 */
	public function should_block_request_with_invalid_nonce(): void {
		wp_set_current_user( 0 );
		$this->set_gateway_enabled( true );

		$this->assert_permission(
			$this->make_request( 'POST', 'not-a-real-nonce' ),
			$this->make_request( 'DELETE', 'not-a-real-nonce' ),
			false
		);
	}

	/**
	 * @test
	 */
	public function should_block_request_with_nonce_for_wrong_action(): void {
		wp_set_current_user( 0 );
		$this->set_gateway_enabled( true );

		$wrong_nonce = wp_create_nonce( 'some_other_action' );

		$this->assert_permission(
			$this->make_request( 'POST', $wrong_nonce ),
			$this->make_request( 'DELETE', $wrong_nonce ),
			false
		);
	}

	/**
	 * @test
	 */
	public function should_block_guest_with_valid_nonce_when_gateway_is_disabled(): void {
		wp_set_current_user( 0 );
		$this->set_gateway_enabled( false );

		$nonce = wp_create_nonce( 'wp_rest' );

		$this->assert_permission(
			$this->make_request( 'POST', $nonce ),
			$this->make_request( 'DELETE', $nonce ),
			false
		);
	}

	/**
	 * @test
	 */
	public function should_allow_guest_with_valid_nonce_when_gateway_is_enabled(): void {
		wp_set_current_user( 0 );
		$this->set_gateway_enabled( true );

		$nonce = wp_create_nonce( 'wp_rest' );

		$this->assert_permission(
			$this->make_request( 'POST', $nonce ),
			$this->make_request( 'DELETE', $nonce ),
			true
		);
	}

	/**
	 * @test
	 */
	public function should_allow_admin_user_without_nonce_regardless_of_gateway(): void {
		$admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );
		$this->set_gateway_enabled( false ); // Gateway off — admin bypasses it via manage access.

		$this->assert_permission(
			$this->make_request( 'POST' ),
			$this->make_request( 'DELETE' ),
			true
		);
	}

	/**
	 * @test
	 */
	public function should_block_subscriber_without_nonce_even_when_gateway_is_enabled(): void {
		$subscriber_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber_id );
		$this->set_gateway_enabled( true );

		$this->assert_permission(
			$this->make_request( 'POST' ),
			$this->make_request( 'DELETE' ),
			false
		);
	}

	/**
	 * @test
	 */
	public function should_allow_subscriber_with_valid_nonce_when_gateway_is_enabled(): void {
		$subscriber_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber_id );
		$this->set_gateway_enabled( true );

		$nonce = wp_create_nonce( 'wp_rest' );

		$this->assert_permission(
			$this->make_request( 'POST', $nonce ),
			$this->make_request( 'DELETE', $nonce ),
			true
		);
	}

	/**
	 * @test
	 */
	public function should_block_subscriber_with_valid_nonce_when_gateway_is_disabled(): void {
		$subscriber_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber_id );
		$this->set_gateway_enabled( false );

		$nonce = wp_create_nonce( 'wp_rest' );

		$this->assert_permission(
			$this->make_request( 'POST', $nonce ),
			$this->make_request( 'DELETE', $nonce ),
			false
		);
	}
}
