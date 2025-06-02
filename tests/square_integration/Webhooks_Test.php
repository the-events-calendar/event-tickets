<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Traits\WhoDat_Mocks;
use Generator;
use Closure;
use Tribe\Tests\Traits\WP_Send_Json_Mocks;
use WP_Error;

class Webhooks_Test extends Controller_Test_Case {
	use With_Uopz;
	use WhoDat_Mocks;
	use WP_Send_Json_Mocks;

	protected string $controller_class = Webhooks::class;

	/**
	 * Reset the webhook option.
	 * @after
	 */
	public function reset(): void {
		tribe_update_option( Webhooks::OPTION_WEBHOOK, require __DIR__ . '/../_data/square-webhook.php' );
		wp_set_current_user( 0 );
		$_REQUEST = [];
	}

	/**
	 * @test
	 */
	public function it_should_return_appropriate_instances(): void {
		$controller = $this->make_controller();

		$this->assertInstanceOf( Webhooks::class, $controller );

		$gateway  = $controller->get_gateway();
		$merchant = $controller->get_merchant();

		$this->assertInstanceOf( Gateway::class, $gateway );
		$this->assertInstanceOf( Merchant::class, $merchant );
		$this->assertSame( $gateway, $controller->get_gateway() );
		$this->assertSame( $merchant, $controller->get_merchant() );
	}

	/**
	 * @test
	 */
	public function it_should_schedule_webhook_registration_refresh(): void {
		$controller = $this->make_controller();
		$this->assertFalse( as_has_scheduled_action( 'tec_tickets_commerce_square_refresh_webhook', [], 'tec-tickets-commerce-webhooks' ) );

		$this->set_class_fn_return( Webhooks::class, 'is_webhook_healthy', false );
		$controller->schedule_webhook_registration_refresh();
		$this->assertFalse( as_has_scheduled_action( 'tec_tickets_commerce_square_refresh_webhook', [], 'tec-tickets-commerce-webhooks' ) );

		$this->unset_uopz_returns();
		$controller->schedule_webhook_registration_refresh();
		$this->assertTrue( as_has_scheduled_action( 'tec_tickets_commerce_square_refresh_webhook', [], 'tec-tickets-commerce-webhooks' ) );
	}

	/**
	 * @test
	 */
	public function it_should_refresh_webhook(): void {
		$controller = $this->make_controller();
		$webhook = tribe_get_option( Webhooks::OPTION_WEBHOOK );
		$webhook_id = $webhook['id'];

		$fetched_at = $webhook['fetched_at'];

		$controller->refresh_webhook();

		$new_webhook = tribe_get_option( Webhooks::OPTION_WEBHOOK );
		$new_webhook_id = $new_webhook['id'];

		$this->assertNotSame( $fetched_at, $new_webhook['fetched_at'] );
		$this->assertGreaterThan( strtotime( $fetched_at ), strtotime( $new_webhook['fetched_at'] ) );
		$this->assertTrue( time() - strtotime( $new_webhook['fetched_at'] ) < 1 );
		$this->assertSame( $webhook_id, $new_webhook_id );
	}

	/**
	 * @test
	 */
	public function it_should_return_webhook_id(): void {
		$controller = $this->make_controller();
		$webhook = tribe_get_option( Webhooks::OPTION_WEBHOOK );
		$webhook_id = $webhook['id'];

		$this->assertSame( $webhook_id, $controller->get_webhook_id() );
	}

	/**
	 * @test
	 */
	public function it_should_verify_signature(): void {
		$controller = $this->make_controller();
		$this->assertFalse( $controller->verify_signature( '' ) );

		$this->assertEmpty( $controller->get_webhook_secret() );

		$this->assertFalse( $controller->verify_signature( 'invalid' ) );

		$unhashed = $controller->get_webhook_secret( false, true );
		$hashed = $controller->get_webhook_secret( true, false );
		$this->assertSame( $unhashed, $controller->get_webhook_secret( false, false ) );

		$this->assertFalse( $controller->verify_signature( 'invalid' ) );
		$this->assertTrue( $controller->verify_signature( $hashed ) );
	}

	/**
	 * @test
	 */
	public function it_should_verify_whodat_signature(): void {
		$controller = $this->make_controller();
		$this->assertFalse( $controller->verify_whodat_signature( '', '', '' ) );

		$this->assertFalse( $controller->verify_whodat_signature( 'invalid', '', '' ) );
		$this->assertFalse( $controller->verify_whodat_signature( 'invalid', 'invalid', '' ) );

		$this->assertFalse( $controller->verify_whodat_signature( '', 'invalid', '' ) );
		$this->assertFalse( $controller->verify_whodat_signature( '', 'invalid', 'invalid' ) );

		$this->assertFalse( $controller->verify_whodat_signature( '', '', 'invalid' ) );
		$this->assertFalse( $controller->verify_whodat_signature( 'invalid', '', 'invalid' ) );

		$this->assertFalse( $controller->verify_whodat_signature( 'invalid', 'invalid', 'invalid' ) );

		$whodat_signature = tec_tickets_tests_get_fake_merchant_data()['whodat_signature'];

		$payload = 'payload';

		$hash = md5( "{$payload}.{$whodat_signature}" );

		$this->assertFalse( $controller->verify_whodat_signature( $payload, $hash, $whodat_signature ) );

		$notification_url = add_query_arg( Webhooks::PARAM_WEBHOOK_KEY, 'secret', $controller->get_webhook_endpoint_url() );

		$hash = md5( "{$notification_url}.{$payload}.{$whodat_signature}" );

		$this->assertTrue( $controller->verify_whodat_signature( $payload, $hash, 'secret' ) );
		$this->assertFalse( $controller->verify_whodat_signature( $payload, $hash, 'known-secret' ) );
		$this->assertFalse( $controller->verify_whodat_signature( $payload . '2', $hash, 'secret' ) );
		$this->assertFalse( $controller->verify_whodat_signature( $payload, $hash . '2', 'secret' ) );
	}

	/**
	 * @test
	 */
	public function it_should_eval_webhook_healthy_and_expired(): void {
		$controller = $this->make_controller();
		$this->assertTrue( $controller->is_webhook_healthy() );
		$this->assertFalse( $controller->is_webhook_expired() );

		$controller->refresh_webhook();

		$this->assertTrue( $controller->is_webhook_healthy() );
		$this->assertFalse( $controller->is_webhook_expired() );

		tribe_remove_option( Webhooks::OPTION_WEBHOOK );

		$this->assertFalse( $controller->is_webhook_healthy() );
		$this->assertTrue( $controller->is_webhook_expired() );
	}

	public function ajax_register_webhook_provider(): Generator {
		yield 'no-nonce' => [
			fn(): array => [ [ 'message' => 'Security check failed. Please refresh the page and try again.' ], 401 ],
		];

		yield 'guest user' => [
			function(): array {
				wp_set_current_user( 0 );
				$_REQUEST['nonce'] = wp_create_nonce( 'square-webhook-register' );
				return [ [ 'message' => 'You do not have permission to perform this action.' ], 401 ];
			},
		];

		yield 'editor user' => [
			function(): array {
				wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );
				$_REQUEST['nonce'] = wp_create_nonce( 'square-webhook-register' );
				return [ [ 'message' => 'You do not have permission to perform this action.' ], 401 ];
			},
		];

		yield 'admin user - WP error' => [
			function(): array {
				wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
				$_REQUEST['nonce'] = wp_create_nonce( 'square-webhook-register' );
				$error = new WP_Error( 'error', 'test' );
				$this->set_class_fn_return( Webhooks::class, 'register_webhook_endpoint', $error );
				return [ $error, 500 ];
			},
		];

		yield 'admin user - no response' => [
			function(): array {
				wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
				$_REQUEST['nonce'] = wp_create_nonce( 'square-webhook-register' );
				$response = null;
				$this->set_class_fn_return( Webhooks::class, 'register_webhook_endpoint', $response );
				return [
					[
						'message'  => 'Failed to register webhook endpoint for Square. Please check your connection settings and try again.',
						'response' => $response,
					],
					500
				];
			},
		];

		yield 'admin user - response with errors' => [
			function(): array {
				wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
				$_REQUEST['nonce'] = wp_create_nonce( 'square-webhook-register' );
				$response = [ 'errors' => [ 'error1', 'error2' ] ];
				$this->set_class_fn_return( Webhooks::class, 'register_webhook_endpoint', $response );
				return [
					[
						'message'  => 'Failed to register webhook endpoint for Square. Please check your connection settings and try again.',
						'response' => $response,
					],
					500
				];
			},
		];

		yield 'admin user - success' => [
			function(): array {
				wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
				$_REQUEST['nonce'] = wp_create_nonce( 'square-webhook-register' );
				return [ [ 'message' => 'Webhook endpoint successfully registered with Square.' ], 200 ];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider ajax_register_webhook_provider
	 */
	public function it_should_register_webhook_through_ajax( Closure $fixture ): void {
		$controller = $this->make_controller();
		$controller->register();

		$webhook = tribe_get_option( Webhooks::OPTION_WEBHOOK );
		$webhook_id = $webhook['id'];

		$fetched_at = $webhook['fetched_at'];

		$args = $fixture();

		$error = $this->mock_wp_send_json_error();
		$success = $this->mock_wp_send_json_success();

		do_action( 'wp_ajax_tec_tickets_commerce_square_register_webhook' );

		if ( $args[1] > 200 ) {
			$this->assertTrue(
				$error->was_called_times_with( 1, ...$args )
			);
			$this->assertFalse( $success->was_called() );
			$this->assertEquals( get_option( tribe( Merchant::class )->get_signup_data_key() ), tec_tickets_tests_get_fake_merchant_data() );
			return;
		}

		$new_webhook = tribe_get_option( Webhooks::OPTION_WEBHOOK );
		$new_webhook_id = $new_webhook['id'];

		$this->assertNotSame( $fetched_at, $new_webhook['fetched_at'] );
		$this->assertGreaterThan( strtotime( $fetched_at ), strtotime( $new_webhook['fetched_at'] ) );
		$this->assertTrue( time() - strtotime( $new_webhook['fetched_at'] ) < 1 );
		$this->assertSame( $webhook_id, $new_webhook_id );

		$this->assertTrue(
			$success->was_called_times_with( 1, ...$args )
		);
		$this->assertFalse( $error->was_called() );
	}
}
