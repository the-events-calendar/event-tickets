<?php


namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;
use TEC\Tickets\Commerce\Gateways\Stripe\Hooks;
use TEC\Tickets\Commerce\Gateways\Stripe\Merchant;
use Tribe\Tests\Traits\With_Uopz;

class WebhooksTest extends \Codeception\TestCase\WPTestCase {

	use With_Uopz;

	protected static $current_screen_overwrite;

	protected static $webhook_buffer = [];

	public function _setUp(): void {
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

		global $current_screen;

		self::$current_screen_overwrite = $current_screen;

		// We forcefully set is_admin to true.
		$current_screen = \WP_Screen::get( 'post' );
	}

	public function _tearDown()
	{
		parent::_tearDown();
		$merchant = tribe( Merchant::class );
		$merchant->save_signup_data( [] );

		global $current_screen;

		// We restore the current screen.
		$current_screen = self::$current_screen_overwrite;
	}

	/**
	 * @test
	 *
	 * @covers TEC\Tickets\Commerce\Gateways\Stripe\Hooks::setup_stripe_webhook_on_release
	 */
	public function it_should_set_up_webhook_on_release() {
		$webhooks = tribe( Webhooks::class );

		$hooks = tribe( Hooks::class );

		$this->assertFalse( get_option( 'tec_tickets_commerce_stripe_webhook_version', false ) );

		$this->assertTrue( $webhooks->get_gateway()->is_active() );

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

		update_option( 'tec_tickets_commerce_stripe_webhook_version', false );

		$this->assertTrue( $hooks->setup_stripe_webhook_on_release() );

		// Then it should fail.
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

		$this->assertTrue( $webhooks->get_gateway()->is_active() );

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
	}

	/**
	 * @test
	 *
	 * @covers TEC\Tickets\Commerce\Gateways\Stripe\Hooks::action_handle_set_up_webhook
	 */
	public function it_should_handle_action_webhook_set_up() {
		$webhooks = tribe( Webhooks::class );

		$hooks = tribe( Hooks::class );

		$this->assertTrue( $webhooks->get_gateway()->is_active() );

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

		$_POST['tc_nonce'] = wp_create_nonce( $webhooks::NONCE_KEY_SETUP );

		$response = null;

		$hooks->action_handle_set_up_webhook();

		// // No capability!
		$this->assertEquals( wp_json_encode( $json_error ), $response );

		set_current_user( 1 );

		// refresh nonce.
		$_POST['tc_nonce'] = wp_create_nonce( $webhooks::NONCE_KEY_SETUP );

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

		$this->assertTrue( $webhooks->get_gateway()->is_active() );

		$this->set_fn_return( 'wp_remote_get', static function ( $send_data ) {
			return [
				'body' => wp_json_encode(
					[
						'webhook' => false,
					]
				),
			];
		}, true );

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

		$this->assertTrue( $webhooks->get_gateway()->is_active() );

		tribe_update_option( $webhooks::$option_is_valid_webhooks, false );

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
			tribe_update_option( $webhooks::OPTION_KNOWN_WEBHOOKS, [] );

			$this->assertEmpty( tribe_get_option( $webhooks::OPTION_KNOWN_WEBHOOKS, [] ) );
		}

		self::$webhook_buffer[ $webhook['id'] ] = $webhook['secret'];

		$this->assertTrue( $webhooks->get_gateway()->is_active() );

		$webhooks->add_webhook( $webhook );

		$this->assertEquals( $webhook['secret'], tribe_get_option( $webhooks::$option_webhooks_signing_key, [] ) );
		$this->assertEquals( md5( $webhook['secret'] ), tribe_get_option( $webhooks::$option_is_valid_webhooks, false ) );

		if ( count( self::$webhook_buffer ) < 4 ) {
			$this->assertEquals( self::$webhook_buffer, tribe_get_option( $webhooks::OPTION_KNOWN_WEBHOOKS, [] ) );
		} else {
			$known_webhooks = tribe_get_option( $webhooks::OPTION_KNOWN_WEBHOOKS, [] );
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

		$this->assertTrue( $webhooks->get_gateway()->is_active() );

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

		$this->assertTrue( $webhooks->disable_webhook() );
		$this->assertFalse( $webhooks->has_valid_signing_secret() );
	}

	/**
	 * Data provider for testing different scenarios of get_gateway_dashboard_url_by_order.
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
}