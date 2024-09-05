<?php


namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;
use TEC\Tickets\Commerce\Gateways\Stripe\Hooks;
use TEC\Tickets\Commerce\Gateways\Stripe\Merchant;
use Tribe\Tests\Traits\With_Uopz;

class WebhooksTest extends \Codeception\TestCase\WPTestCase {

	use With_Uopz;

	protected static $webhook_buffer = [];

	/**
	 * @before
	 */
	public function before_test(): void {
		parent::_setUp();

		$merchant = tribe( Merchant::class );
		$merchant->save_signup_data(
			[
				'stripe_user_id' => 'STRIPE_USER_ID',
				'sandbox' => (object) [
					'access_token' => 'STRIPE_SANDBOX_TOKEN'
				],
				'live' => (object) [
					'access_token' => 'STRIPE_LIVE_TOKEN'
				],
			]
		);
	}

	/**
	 * @after
	 */
	public function after_test()
	{
		parent::_tearDown();
		$merchant = tribe( Merchant::class );
		$merchant->save_signup_data( [] );
	}

	/**
	 * @test
	 *
	 * @covers TEC\Tickets\Commerce\Gateways\Stripe\Webhooks::get_known_webhooks
	 */
	public function it_should_always_return_an_array() {
		$this->assertFalse( tec_tickets_commerce_is_sandbox_mode() );
		$webhooks = tribe( Webhooks::class );

		tribe_update_option( Webhooks::OPTION_KNOWN_WEBHOOKS, [] );

		$this->assertIsArray( $webhooks->get_known_webhooks() );
		$this->assertEmpty( $webhooks->get_known_webhooks() );

		tribe_update_option( Webhooks::OPTION_KNOWN_WEBHOOKS, 'STRING' );

		$this->assertIsArray( $webhooks->get_known_webhooks() );
		$this->assertEmpty( $webhooks->get_known_webhooks() );

		tribe_update_option( Webhooks::OPTION_KNOWN_WEBHOOKS, true );

		$this->assertIsArray( $webhooks->get_known_webhooks() );
		$this->assertEmpty( $webhooks->get_known_webhooks() );

		tribe_update_option( Webhooks::OPTION_KNOWN_WEBHOOKS, 1 );

		$this->assertIsArray( $webhooks->get_known_webhooks() );
		$this->assertEmpty( $webhooks->get_known_webhooks() );

		$updated = [ 'test' ];
		tribe_update_option( Webhooks::OPTION_KNOWN_WEBHOOKS, $updated );

		$this->assertIsArray( $webhooks->get_known_webhooks() );
		$this->assertEquals( $updated, $webhooks->get_known_webhooks() );

		// Reset. SHould go back to empty.
		tribe_update_option( Webhooks::OPTION_KNOWN_WEBHOOKS, [] );

		$this->assertIsArray( $webhooks->get_known_webhooks() );
		$this->assertEmpty( $webhooks->get_known_webhooks() );
	}

	/**
	 * @test
	 *
	 * @covers TEC\Tickets\Commerce\Gateways\Stripe\Hooks::setup_stripe_webhook_on_release
	 */
	public function it_should_set_up_webhook_on_release() {
		$webhooks = tribe( Webhooks::class );

		$hooks = tribe( Hooks::class );

		$this->assertFalse( tribe_get_option( 'tec_tickets_commerce_stripe_webhook_version', false ) );

		$this->assertTrue( $webhooks->get_merchant()->is_active() );

		$this->set_fn_return( 'wp_remote_get', static function ( $send_data ) {
			return [
				'body' => wp_json_encode(
					[
						'webhook' => false,
					]
				),
			];
		}, true );

		$this->assertFalse( $hooks->setup_stripe_webhook_on_release() );

		$this->set_fn_return( 'wp_remote_get', static function ( $send_data ) {
			return [
				'body' => wp_json_encode(
					[
						'webhook' => [ 'id' => 'wh_1'],
					]
				),
			];
		}, true );

		tribe_update_option( 'tec_tickets_commerce_stripe_webhook_version', false );

		$this->assertTrue( $hooks->setup_stripe_webhook_on_release() );

		// Then it should fail.
		$this->assertFalse( $hooks->setup_stripe_webhook_on_release() );

		// Reset the option being checked!
		tribe_update_option( 'tec_tickets_commerce_stripe_webhook_version', false );

		// Enable the env variable.
		$this->enable_const_signing_secret();

		$this->set_fn_return( 'wp_remote_get', static function ( $send_data ) {
			return [
				'body' => wp_json_encode(
					[
						'webhook' => false,
					]
				),
			];
		}, true );

		// It should return true even though the remote get is set to return a failed response.
		// The reason is that it should never reach the remote get call because of the env variable.
		$this->assertTrue( $hooks->setup_stripe_webhook_on_release() );

		// Disable the env variable.
		$this->disable_const_signing_secret();

		// Reset the option being checked!
		tribe_update_option( 'tec_tickets_commerce_stripe_webhook_version', false );

		// Now it should fail without the env variable and the remote get returning a false result.
		$this->assertFalse( $hooks->setup_stripe_webhook_on_release() );
	}

	/**
	 * @test
	 *
	 * @covers TEC\Tickets\Commerce\Gateways\Stripe\Hooks::setup_stripe_webhook_on_activation
	 */
	public function it_should_set_up_webhook_on_activation() {
		$webhooks = tribe( Webhooks::class );

		$hooks = tribe( Hooks::class );

		$this->assertFalse( get_transient( 'tec_tickets_commerce_setup_stripe_webhook' ) );

		$this->assertTrue( $webhooks->get_merchant()->is_active() );

		set_transient( 'tec_tickets_commerce_setup_stripe_webhook', true );

		$this->set_fn_return( 'wp_remote_get', static function ( $send_data ) {
			return [
				'body' => wp_json_encode(
					[
						'webhook' => false,
					]
				),
			];
		}, true );

		$this->assertFalse( $hooks->setup_stripe_webhook_on_activation() );

		set_transient( 'tec_tickets_commerce_setup_stripe_webhook', true );

		$this->set_fn_return( 'wp_remote_get', static function ( $send_data ) {
			return [
				'body' => wp_json_encode(
					[
						'webhook' => [ 'id' => 'wh_1'],
					]
				),
			];
		}, true );

		$this->assertTrue( $hooks->setup_stripe_webhook_on_activation() );

		// Then it should fail.
		$this->assertFalse( $hooks->setup_stripe_webhook_on_activation() );

		// Reset the transient being checked!
		set_transient( 'tec_tickets_commerce_setup_stripe_webhook', true );

		// Enable the env variable.
		$this->enable_const_signing_secret();

		// Make the remote get fail.
		$this->set_fn_return( 'wp_remote_get', static function ( $send_data ) {
			return [
				'body' => wp_json_encode(
					[
						'webhook' => false,
					]
				),
			];
		}, true );

		// It should return true even though the remote get is set to return a failed response.
		// The reason is that it should never reach the remote get call because of the env variable.
		$this->assertTrue( $hooks->setup_stripe_webhook_on_activation() );

		// Disable the env variable.
		$this->disable_const_signing_secret();

		// Reset the transient being checked!
		set_transient( 'tec_tickets_commerce_setup_stripe_webhook', true );

		// Now it should fail without the env variable and the remote get returning a false result.
		$this->assertFalse( $hooks->setup_stripe_webhook_on_activation() );
	}

	/**
	 * @test
	 *
	 * @covers TEC\Tickets\Commerce\Gateways\Stripe\Hooks::action_handle_set_up_webhook
	 */
	public function it_should_handle_action_webhook_set_up() {
		$webhooks = tribe( Webhooks::class );

		$hooks = tribe( Hooks::class );

		$this->assertTrue( $webhooks->get_merchant()->is_active() );

		$json_error = [
			'success' => false,
			'data'    => [],
		];

		$response = null;

		$this->set_fn_return( 'wp_send_json_error', static function ( $send_data ) use ( &$response ) {
			$data['success'] = false;
			$data['data'] = $send_data;
			unset( $data['data']['status'] );
			$response = wp_json_encode( $data );
			return null;
		}, true );

		$hooks->action_handle_set_up_webhook();

		// No nonce!
		$this->assertEquals( wp_json_encode( $json_error ), $response );

		$_POST['tc_nonce'] = wp_create_nonce( Webhooks::NONCE_KEY_SETUP );

		$response = null;

		$hooks->action_handle_set_up_webhook();

		// // No capability!
		$this->assertEquals( wp_json_encode( $json_error ), $response );

		set_current_user( 1 );

		// refresh nonce.
		$_POST['tc_nonce'] = wp_create_nonce( Webhooks::NONCE_KEY_SETUP );

		$this->set_fn_return( 'wp_remote_get', static function ( $send_data ) {
			return [
				'body' => wp_json_encode(
					[
						'webhook' => [ 'id' => 'wh_1'],
					]
				),
			];
		}, true );

		$json_error['success'] = true;

		$response = null;

		$this->set_fn_return( 'wp_send_json_success', static function ( $send_data ) use ( &$response ) {
			$data['success'] = true;
			$data['data'] = $send_data;
			unset( $data['data']['status'] );
			$response = wp_json_encode( $data );
			return null;
		}, true );

		$hooks->action_handle_set_up_webhook();

		// Success.
		$this->assertEquals( wp_json_encode( $json_error ), $response );

		$this->set_fn_return( 'wp_remote_get', static function ( $send_data ) {
			return [
				'body' => wp_json_encode(
					[
						'webhook' => false,
					]
				),
			];
		}, true );

		// Invalidate previously stored webhook.
		tribe_update_option( $webhooks::$option_is_valid_webhooks, false );

		$this->enable_const_signing_secret();

		$hooks->action_handle_set_up_webhook();

		$this->assertEquals( wp_json_encode( $json_error ), $response );

		$this->disable_const_signing_secret();

		$json_error['success'] = false;

		$hooks->action_handle_set_up_webhook();

		$this->assertEquals( wp_json_encode( $json_error ), $response );

		set_current_user( 0 );

		unset( $_POST['tc_nonce'] );
	}

	/**
	 * @test
	 *
	 * @covers TEC\Tickets\Commerce\Gateways\Stripe\Webhooks::handle_webhook_setup
	 */
	public function it_should_handle_webhook_set_up() {
		$webhooks = tribe( Webhooks::class );

		$this->assertTrue( $webhooks->get_merchant()->is_active() );

		$this->set_fn_return( 'wp_remote_get', static function ( $send_data ) {
			return [
				'body' => wp_json_encode(
					[
						'webhook' => false,
					]
				),
			];
		}, true );

		$this->enable_const_signing_secret();

		$this->assertTrue( $webhooks->handle_webhook_setup() );

		$this->disable_const_signing_secret();

		$this->assertFalse( $webhooks->handle_webhook_setup() );

		$this->set_fn_return( 'wp_remote_get', static function ( $send_data ) {
			return [
				'body' => wp_json_encode(
					[
						'webhook' => [
							'id' => 'wh_1',
							'secret' => 'wh_secret',
						],
					]
				),
			];
		}, true );

		$this->set_tickets_commerce_on_staging();

		// All is good but tickets commerce is in staging mode. It should fail early.
		$this->assertFalse( $webhooks->handle_webhook_setup() );

		$this->set_tickets_commerce_on_production();

		$this->assertTrue( $webhooks->handle_webhook_setup() );
	}

	/**
	 * @test
	 *
	 * @dataProvider webhook_provider
	 *
	 * @covers TEC\Tickets\Commerce\Gateways\Stripe\Webhooks::has_valid_signing_secret
	 */
	public function it_should_has_valid_signing_secret( array $webhook ) {
		$webhooks = tribe( Webhooks::class );

		$this->assertTrue( $webhooks->get_merchant()->is_active() );

		tribe_update_option( $webhooks::$option_is_valid_webhooks, false );

		$this->enable_const_signing_secret();

		$this->assertTrue( $webhooks->has_valid_signing_secret() );

		$this->disable_const_signing_secret();

		$this->assertFalse( $webhooks->has_valid_signing_secret() );

		$webhooks->add_webhook( $webhook );

		$this->assertTrue( $webhooks->has_valid_signing_secret() );
	}

	/**
	 * @test
	 *
	 * @dataProvider webhook_provider
	 *
	 * @covers TEC\Tickets\Commerce\Gateways\Stripe\Webhooks::add_webhook
	 */
	public function it_should_add_webhook( array $webhook ) {
		$webhooks = tribe( Webhooks::class );

		if ( empty( self::$webhook_buffer ) ) {
			tribe_update_option( Webhooks::OPTION_KNOWN_WEBHOOKS, [] );

			$this->assertEmpty( $webhooks->get_known_webhooks() );
		}

		self::$webhook_buffer[ $webhook['id'] ] = $webhook['secret'];

		$this->assertTrue( $webhooks->get_merchant()->is_active() );

		$webhooks->add_webhook( $webhook );

		$this->assertEquals( $webhook['secret'], tribe_get_option( $webhooks::$option_webhooks_signing_key, [] ) );
		$this->assertEquals( md5( $webhook['secret'] ), tribe_get_option( $webhooks::$option_is_valid_webhooks, false ) );

		if ( count( self::$webhook_buffer ) < 4 ) {
			$this->assertEquals( self::$webhook_buffer, $webhooks->get_known_webhooks() );
		} else {
			$known_webhooks = $webhooks->get_known_webhooks();
			$this->assertTrue( 3 === count( $known_webhooks ) );

			$this->assertTrue( ! empty( $known_webhooks[ $webhook['id'] ] ) && $known_webhooks[ $webhook['id'] ] === $webhook['secret'] );
		}
	}

	/**
	 * @test
	 *
	 * @covers TEC\Tickets\Commerce\Gateways\Stripe\Webhooks::disable_webhook
	 */
	public function it_should_disable_webhook() {
		$webhook = [
			'id' => 'wh_1',
			'secret' => 'wh_secret'
		];

		$webhooks = tribe( Webhooks::class );

		$this->assertTrue( $webhooks->get_merchant()->is_active() );

		$webhooks->add_webhook( $webhook );

		$this->assertTrue( $webhooks->has_valid_signing_secret() );

		$this->set_fn_return( 'wp_remote_get', static function ( $send_data ) {
			return [
				'body' => wp_json_encode(
					[
						'webhook' => false,
					]
				),
			];
		}, true );

		$this->assertFalse( $webhooks->disable_webhook() );
		$this->assertTrue( $webhooks->has_valid_signing_secret() );

		$this->set_fn_return( 'wp_remote_get', static function ( $send_data ) {
			return [
				'body' => wp_json_encode(
					[
						'webhook' => [ 'id' => 'wh_1'],
					]
				),
			];
		}, true );

		$this->enable_const_signing_secret();

		// Even though all is good, when the env variable is there, it should fail to disable webhook.
		$this->assertFalse( $webhooks->disable_webhook() );

		$this->disable_const_signing_secret();

		$this->set_tickets_commerce_on_staging();

		// All is good but tickets commerce is in staging mode. It should fail early.
		$this->assertFalse( $webhooks->disable_webhook() );

		$this->set_tickets_commerce_on_production();

		$this->assertTrue( $webhooks->disable_webhook() );
		$this->assertFalse( $webhooks->has_valid_signing_secret() );
	}

	/**
	 * @test
	 *
	 * @dataProvider webhook_provider
	 *
	 * @covers TEC\Tickets\Commerce\Gateways\Stripe\Webhooks::get_current_webhook_id
	 */
	public function it_should_return_current_webhook_id( array $webhook ) {
		$webhooks = tribe( Webhooks::class );

		static $previous_webhook_id = null;

		$this->assertTrue( $webhooks->get_merchant()->is_active() );

		if ( ! $previous_webhook_id ) {
			tribe_update_option( Webhooks::OPTION_KNOWN_WEBHOOKS, [] );
			$this->assertEmpty( $webhooks->get_current_webhook_id() );
		} else {
			$this->assertEquals( [ $previous_webhook_id ], $webhooks->get_current_webhook_id() );
		}

		$previous_webhook_id = $webhook['id'];


		$webhooks->add_webhook( $webhook );

		$this->assertEquals( [ $webhook['id'] ], $webhooks->get_current_webhook_id() );
	}

	/**
	 * Data provider for testing scenarios with stored webhooks.
	 *
	 * @return array
	 */
	public function webhook_provider() {
		$webhooks = [
			[
				'id' => 'wh_1',
				'secret' => 'wh_secret_1',
			],
			[
				'id' => 'wh_2',
				'secret' => 'wh_secret_2',
			],
			[
				'id' => 'wh_3',
				'secret' => 'wh_secret_3',
			],
			[
				'id' => 'wh_4',
				'secret' => 'wh_secret_4',
			],
			[
				'id' => 'wh_5',
				'secret' => 'wh_secret_5',
			],
			[
				'id' => 'wh_6',
				'secret' => 'wh_secret_6',
			],
		];

		foreach ( $webhooks as $webhook ) {
			yield [ $webhook ];
		}
	}

	/**
	 * Set Tickets Commerce to be in sandbox mode.
	 */
	protected function set_tickets_commerce_on_staging() {
		add_filter( 'tec_tickets_commerce_is_sandbox_mode', '__return_true' );
	}

	/**
	 * Set Tickets Commerce to be in production mode.
	 */
	protected function set_tickets_commerce_on_production() {
		add_filter( 'tec_tickets_commerce_is_sandbox_mode', '__return_false' );
	}

	/**
	 * Enable the constant for the signing secret.
	 */
	protected function enable_const_signing_secret() {
		$this->set_class_fn_return( Webhooks::class, 'is_signing_secret_const_defined', true );
	}

	/**
	 * Disable the constant for the signing secret.
	 */
	protected function disable_const_signing_secret() {
		$this->set_class_fn_return( Webhooks::class, 'is_signing_secret_const_defined', false );
	}
}
