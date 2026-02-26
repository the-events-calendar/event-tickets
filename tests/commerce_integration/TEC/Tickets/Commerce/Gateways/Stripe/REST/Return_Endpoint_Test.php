<?php
/**
 * Tests for the Stripe Return Endpoint permission checks.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe\REST
 */

namespace TEC\Tickets\Commerce\Gateways\Stripe\REST;

use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Tickets\Commerce\Gateways\Stripe\WhoDat;
use TEC\Tickets\Commerce\Gateways\Stripe\Merchant;
use WP_REST_Request;

/**
 * Class Return_Endpoint_Test.
 *
 * @since TBD
 *
 * @covers \TEC\Tickets\Commerce\Gateways\Stripe\REST\Return_Endpoint
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe\REST
 */
class Return_Endpoint_Test extends WPTestCase {
	use With_Uopz;

	/**
	 * Clean up request globals after each test.
	 *
	 * @after
	 */
	public function cleanup_request_vars(): void {
		unset(
			$_GET['stripe'],
			$_GET['stripe_disconnected'],
			$_REQUEST['stripe'],
			$_REQUEST['stripe_disconnected']
		);
	}

	/**
	 * Helper to encode a payload the same way WhoDat would.
	 *
	 * @param array $data The data to encode.
	 *
	 * @return string Base64-encoded JSON string.
	 */
	private function encode_payload( array $data ): string {
		return base64_encode( wp_json_encode( $data ) );
	}

	/**
	 * Helper to generate a valid state nonce (as user 0, matching Signup generation).
	 *
	 * @return string A valid WordPress nonce.
	 */
	private function generate_valid_state_nonce(): string {
		$user_id = get_current_user_id();
		wp_set_current_user( 0 );
		$nonce = wp_create_nonce( tribe( WhoDat::class )->get_state_nonce_action() );
		wp_set_current_user( $user_id );

		return $nonce;
	}

	/**
	 * Helper to set the stripe request var and call has_permission.
	 *
	 * @param string|null $stripe_value The value for the stripe query param.
	 *
	 * @return bool|int The result of has_permission.
	 */
	private function call_has_permission( ?string $stripe_value = null ) {
		if ( null !== $stripe_value ) {
			$_GET['stripe']     = $stripe_value;
			$_REQUEST['stripe'] = $stripe_value;
		}

		$endpoint = tribe( Return_Endpoint::class );
		$request  = new WP_REST_Request( 'GET', '/commerce/stripe/return' );

		if ( null !== $stripe_value ) {
			$request->set_query_params( [ 'stripe' => $stripe_value ] );
		}

		return $endpoint->has_permission( $request );
	}

	/**
	 * @test
	 */
	public function should_reject_request_with_no_stripe_parameter(): void {
		$result = $this->call_has_permission();

		$this->assertFalse( $result );
	}

	/**
	 * @test
	 */
	public function should_reject_request_with_empty_stripe_parameter(): void {
		$result = $this->call_has_permission( '' );

		$this->assertFalse( $result );
	}

	/**
	 * @test
	 */
	public function should_reject_request_with_payload_missing_state(): void {
		$payload = $this->encode_payload( [
			'stripe_user_id' => 'acct_UNAUTHORIZED123',
			'live'           => [
				'access_token'    => 'sk_live_UNAUTHORIZED',
				'publishable_key' => 'pk_live_UNAUTHORIZED',
			],
			'sandbox'        => [
				'access_token'    => 'sk_test_UNAUTHORIZED',
				'publishable_key' => 'pk_test_UNAUTHORIZED',
			],
		] );

		$result = $this->call_has_permission( $payload );

		$this->assertFalse( $result );
	}

	/**
	 * @test
	 */
	public function should_reject_request_with_invalid_state_nonce(): void {
		$payload = $this->encode_payload( [
			'stripe_user_id' => 'acct_UNAUTHORIZED123',
			'nonce'          => 'not_a_real_nonce_value',
			'live'           => [
				'access_token'    => 'sk_live_UNAUTHORIZED',
				'publishable_key' => 'pk_live_UNAUTHORIZED',
			],
			'sandbox'        => [
				'access_token'    => 'sk_test_UNAUTHORIZED',
				'publishable_key' => 'pk_test_UNAUTHORIZED',
			],
		] );

		$result = $this->call_has_permission( $payload );

		$this->assertFalse( $result );
	}

	/**
	 * @test
	 */
	public function should_reject_unauthorized_credential_overwrite(): void {
		$unauthorized_payload = [
			'stripe_user_id' => 'acct_UNAUTHORIZED_ACCOUNT_ID',
			'live'           => [
				'access_token'    => 'sk_live_UNAUTHORIZED_SECRET_KEY',
				'publishable_key' => 'pk_live_UNAUTHORIZED_PUBLISHABLE_KEY',
			],
			'sandbox'        => [
				'access_token'    => 'sk_test_UNAUTHORIZED_SECRET_KEY',
				'publishable_key' => 'pk_test_UNAUTHORIZED_PUBLISHABLE_KEY',
			],
		];

		$encoded = $this->encode_payload( $unauthorized_payload );

		$result = $this->call_has_permission( $encoded );

		// The request must be rejected at the permission level.
		$this->assertFalse( $result, 'Unauthorized credential overwrite should be blocked.' );

		// Verify the unauthorized credentials were NOT saved.
		$signup_data = get_option( tribe( Merchant::class )->get_signup_data_key() );

		if ( ! empty( $signup_data['stripe_user_id'] ) ) {
			$this->assertNotEquals( 'acct_UNAUTHORIZED_ACCOUNT_ID', $signup_data['stripe_user_id'] );
		}
	}

	/**
	 * @test
	 */
	public function should_accept_request_with_valid_state_nonce(): void {
		$valid_nonce = $this->generate_valid_state_nonce();

		$payload = $this->encode_payload( [
			'stripe_user_id' => 'acct_LEGITIMATE',
			'nonce'          => $valid_nonce,
			'live'           => [
				'access_token'    => 'sk_live_LEGITIMATE',
				'publishable_key' => 'pk_live_LEGITIMATE',
			],
			'sandbox'        => [
				'access_token'    => 'sk_test_LEGITIMATE',
				'publishable_key' => 'pk_test_LEGITIMATE',
			],
		] );

		$result = $this->call_has_permission( $payload );

		// wp_verify_nonce returns 1 or 2 on success, false on failure.
		$this->assertNotFalse( $result, 'Valid nonce should pass permission check.' );
	}

	/**
	 * @test
	 */
	public function should_reject_request_with_invalid_base64_payload(): void {
		$result = $this->call_has_permission( 'not-valid-base64!!!' );

		$this->assertFalse( $result );
	}

	/**
	 * @test
	 */
	public function should_reject_nonce_generated_for_wrong_action(): void {
		// Generate a valid WP nonce but for a completely different action.
		wp_set_current_user( 0 );
		$wrong_nonce = wp_create_nonce( 'some_other_action' );

		$payload = $this->encode_payload( [
			'stripe_user_id' => 'acct_UNAUTHORIZED123',
			'nonce'          => $wrong_nonce,
			'live'           => [
				'access_token'    => 'sk_live_UNAUTHORIZED',
				'publishable_key' => 'pk_live_UNAUTHORIZED',
			],
		] );

		$result = $this->call_has_permission( $payload );

		$this->assertFalse( $result );
	}

	/**
	 * @test
	 */
	public function should_reject_disconnect_request_without_valid_state(): void {
		$_GET['stripe_disconnected']     = '1';
		$_REQUEST['stripe_disconnected'] = '1';

		// No stripe param means has_permission will get empty payload.
		$result = $this->call_has_permission();

		$this->assertFalse( $result );
	}

	/**
	 * @test
	 */
	public function should_reject_request_with_state_as_empty_string(): void {
		$payload = $this->encode_payload( [
			'stripe_user_id' => 'acct_UNAUTHORIZED123',
			'nonce'          => '',
			'live'           => [
				'access_token'    => 'sk_live_UNAUTHORIZED',
				'publishable_key' => 'pk_live_UNAUTHORIZED',
			],
		] );

		$result = $this->call_has_permission( $payload );

		$this->assertFalse( $result );
	}
}
