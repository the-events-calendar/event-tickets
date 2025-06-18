<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\REST;

use TEC\Tickets\Commerce\Gateways\Stripe\Merchant;
use TEC\Tickets\Commerce\Gateways\Stripe\Settings;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use Tribe\Tests\Traits\With_Uopz;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Codeception\TestCase\WPTestCase;

class Return_Endpoint_Redirect_Test extends WPTestCase {
	use With_Uopz;
	use SnapshotAssertions;

	/**
	 * @var Return_Endpoint
	 */
	protected $endpoint;

	/**
	 * @var string|null
	 */
	protected $redirect_url;

	/**
	 * @before
	 */
	public function setup_endpoint(): void {
		$this->endpoint = tribe( Return_Endpoint::class );

		// Set up common redirect URL capture
		$this->redirect_url = null;
		$test               = $this;
		$this->set_fn_return(
			'wp_safe_redirect',
			function ( $url ) use ( $test ) {
				$test->redirect_url = $url;

				return true;
			},
			true
		);

		// Mock tribe_exit to prevent actual exit
		$this->set_fn_return( 'tribe_exit', true );

		// Mock common dependencies
		$this->set_class_fn_return( Settings::class, 'setup_account_defaults', true );
		$this->set_class_fn_return( Merchant::class, 'validate_account_is_permitted', 'valid' );
		$this->set_class_fn_return( Abstract_Gateway::class, 'disable', true );
		$this->set_class_fn_return( Merchant::class, 'delete_signup_data', true );
	}

	/**
	 * @test
	 */
	public function it_should_redirect_to_success_url_on_connection_established(): void {
		$payload = (object) [
			'stripe_user_id' => 'test_user_id',
			'webhook'        => [
				'id'     => 'wh_test_123',
				'secret' => 'whsec_test_123',
			],
		];

		$this->endpoint->handle_connection_established( $payload );

		$this->assertMatchesStringSnapshot( $this->redirect_url );
	}

	/**
	 * @test
	 */
	public function it_should_redirect_to_error_url_on_connection_error(): void {
		$payload = (object) [
			'tc-stripe-error' => 'test_error',
		];

		$this->endpoint->handle_connection_error( $payload );

		$this->assertMatchesStringSnapshot( $this->redirect_url );
	}

	/**
	 * @test
	 */
	public function it_should_redirect_to_disconnect_url_on_connection_terminated(): void {
		$reason  = [ 'reason' => 'test_reason' ];
		$payload = (object) [
			'webhook' => (object) [
				'id' => 'wh_test_123',
			],
		];

		$this->endpoint->handle_connection_terminated( $reason, $payload );

		$this->assertMatchesStringSnapshot( $this->redirect_url );
	}
}
