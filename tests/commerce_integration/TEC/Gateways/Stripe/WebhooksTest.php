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

		$_POST['tc_nonce'] = wp_create_nonce( $webhooks::$nonce_key_set_up );

		$response = null;

		$hooks->action_handle_set_up_webhook();

		// // No capability!
		$this->assertEquals( wp_json_encode( $json_error ), $response );

		set_current_user( 1 );

		// refresh nonce.
		$_POST['tc_nonce'] = wp_create_nonce( $webhooks::$nonce_key_set_up );

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
}