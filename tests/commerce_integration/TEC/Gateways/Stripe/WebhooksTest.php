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
}