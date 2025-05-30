<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Settings as Commerce_Settings;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;

/**
 * Test class for the Square Notices Controller
 */
class Notices_Controller_Test extends Controller_Test_Case {
	use With_Uopz;
	use SnapshotAssertions;

	protected string $controller_class = Notices_Controller::class;

	/**
	 * @before
	 */
	public function set_up_mocks(): void {
		$this->set_fn_return( 'is_admin', true );
	}

	/**
	 * @test
	 */
	public function it_should_not_show_webhook_notice_when_gateway_not_enabled(): void {
		$this->set_class_fn_return( Abstract_Gateway::class, 'is_enabled', false );

		$this->assertFalse( $this->make_controller()->should_display_webhook_notice() );
	}

	/**
	 * @test
	 */
	public function it_should_not_show_webhook_notice_when_gateway_not_active(): void {
		$this->set_class_fn_return( Abstract_Gateway::class, 'is_active', false );

		$this->assertFalse( $this->make_controller()->should_display_webhook_notice() );
	}

	/**
	 * @test
	 */
	public function it_should_show_webhook_notice_when_webhook_expired(): void {
		$this->assertTrue( tribe( Gateway::class )->is_enabled() );
		$this->assertTrue( tribe( Gateway::class )->is_active() );

		$screen = (object) ['id' => 'tickets_page_tec-tickets-settings'];
		$this->set_fn_return( 'get_current_screen', $screen );

		$this->set_class_fn_return( Webhooks::class, 'is_webhook_expired', true );

		$this->assertTrue( $this->make_controller()->should_display_webhook_notice() );
	}

	/**
	 * @test
	 */
	public function it_should_show_webhook_notice_when_webhook_unhealthy(): void {
		$screen = (object) ['id' => 'tickets_page_tec-tickets-settings'];
		$this->set_fn_return( 'get_current_screen', $screen );

		$this->set_class_fn_return( Webhooks::class, 'is_webhook_expired', false );
		$this->set_class_fn_return( Webhooks::class, 'is_webhook_healthy', false );

		$this->assertTrue( $this->make_controller()->should_display_webhook_notice() );
	}

	/**
	 * @test
	 */
	public function it_should_not_show_webhook_notice_when_webhook_healthy(): void {
		$screen = (object) ['id' => 'tickets_page_tec-tickets-settings'];
		$this->set_fn_return( 'get_current_screen', $screen );

		$this->set_class_fn_return( Webhooks::class, 'is_webhook_expired', false );
		$this->set_class_fn_return( Webhooks::class, 'is_webhook_healthy', true );

		$this->assertFalse( $this->make_controller()->should_display_webhook_notice() );
	}

	/**
	 * @test
	 */
	public function it_should_not_show_not_ready_to_sell_notice_when_gateway_not_enabled(): void {
		$this->set_class_fn_return( Abstract_Gateway::class, 'is_enabled', false );

		$this->assertFalse( $this->make_controller()->should_display_not_ready_to_sell_notice() );
	}

	/**
	 * @test
	 */
	public function it_should_show_not_ready_to_sell_notice_when_location_not_configured(): void {
		$this->set_class_fn_return( Merchant::class, 'get_location_id', '' );

		$this->assertTrue( $this->make_controller()->should_display_not_ready_to_sell_notice() );
	}

	/**
	 * @test
	 */
	public function it_should_not_show_not_ready_to_sell_notice_when_location_configured(): void {
		$this->assertFalse( $this->make_controller()->should_display_not_ready_to_sell_notice() );
	}

	/**
	 * @test
	 */
	public function it_should_show_currency_mismatch_notice_when_currencies_dont_match(): void {
		$this->set_class_fn_return( Merchant::class, 'is_currency_matching', false );

		$this->assertTrue( $this->make_controller()->should_display_currency_mismatch_notice() );
	}

	/**
	 * @test
	 */
	public function it_should_not_show_currency_mismatch_notice_when_currencies_match(): void {
		$this->set_class_fn_return( Merchant::class, 'is_currency_matching', true );

		$this->assertFalse( $this->make_controller()->should_display_currency_mismatch_notice() );
	}

	/**
	 * @test
	 */
	public function it_should_show_just_onboarded_notice_when_recently_onboarded(): void {
		$this->set_class_fn_return( Assets::class, 'is_square_section', true );

		Commerce_Settings::set( 'tickets_commerce_gateways_square_just_onboarded_%s', time() - 2 );

		$this->assertTrue( $this->make_controller()->should_display_just_onboarded_notice() );
	}

	/**
	 * @test
	 */
	public function it_should_not_show_just_onboarded_notice_when_not_recently_onboarded(): void {
		$this->set_class_fn_return( Assets::class, 'is_square_section', true );

		$this->assertFalse( $this->make_controller()->should_display_just_onboarded_notice() );
	}

	/**
	 * @test
	 */
	public function it_should_show_remotely_disconnected_notice_when_recently_disconnected(): void {
		Commerce_Settings::set( 'tickets_commerce_gateways_square_remotely_disconnected_%s', time() - 10 * DAY_IN_SECONDS );

		$this->assertTrue( $this->make_controller()->should_display_remotely_disconnected_notice() );
	}

	/**
	 * @test
	 */
	public function it_should_not_show_remotely_disconnected_notice_when_not_disconnected(): void {
		$this->assertFalse( $this->make_controller()->should_display_remotely_disconnected_notice() );
	}

	/**
	 * @test
	 */
	public function it_should_render_webhook_notice_with_missing_webhook(): void {
		$this->set_class_fn_return( Webhooks::class, 'get_webhook_id', '' );

		$this->set_fn_return( 'wp_create_nonce', 'test-nonce' );

		$notice = $this->make_controller()->render_webhook_notice();

		$this->assertMatchesHtmlSnapshot($notice);
	}

	/**
	 * @test
	 */
	public function it_should_render_webhook_notice_with_expired_webhook(): void {
		$this->set_class_fn_return( Webhooks::class, 'get_webhook_id', 'webhook_123' );

		$this->set_class_fn_return( Webhooks::class, 'is_webhook_expired', true );

		$this->set_fn_return( 'wp_create_nonce', 'test-nonce' );

		$notice = $this->make_controller()->render_webhook_notice();

		$this->assertMatchesHtmlSnapshot($notice);
	}

	/**
	 * @test
	 */
	public function it_should_render_not_ready_to_sell_notice(): void {
		$notice = $this->make_controller()->render_not_ready_to_sell_notice();

		$this->assertMatchesHtmlSnapshot($notice);
	}

	/**
	 * @test
	 */
	public function it_should_render_currency_mismatch_notice(): void {
		$this->set_class_fn_return( Merchant::class, 'get_merchant_currency', 'EUR' );

		$notice = $this->make_controller()->render_currency_mismatch_notice();

		$this->assertMatchesHtmlSnapshot($notice);
	}

	/**
	 * @test
	 */
	public function it_should_render_just_onboarded_notice_and_delete_flag(): void {
		Commerce_Settings::set( 'tickets_commerce_gateways_square_just_onboarded_%s', true );

		$notice = $this->make_controller()->render_just_onboarded_notice();

		$this->assertFalse( Commerce_Settings::get( 'tickets_commerce_gateways_square_just_onboarded_%s' ) );
		$this->assertMatchesHtmlSnapshot($notice);
	}

	/**
	 * @test
	 */
	public function it_should_render_remotely_disconnected_notice(): void {
		$notice = $this->make_controller()->render_remotely_disconnected_notice();

		$this->assertMatchesHtmlSnapshot($notice);
	}
}
