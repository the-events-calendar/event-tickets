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
	 * @after
	 */
	public function reset_token_status(): void {
		tribe( Merchant::class )->delete_refresh_status();
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

		$this->set_class_fn_return( Webhooks::class, 'is_webhook_healthy', false );

		$this->assertTrue( $this->make_controller()->should_display_webhook_notice() );
	}

	/**
	 * @test
	 */
	public function it_should_not_show_webhook_notice_when_webhook_healthy(): void {
		$screen = (object) ['id' => 'tickets_page_tec-tickets-settings'];
		$this->set_fn_return( 'get_current_screen', $screen );

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

	/**
	 * @test
	 */
	public function it_should_not_show_token_invalid_notice_while_the_token_works(): void {
		$this->assertFalse( $this->make_controller()->should_display_token_invalid_notice() );
	}

	/**
	 * @test
	 */
	public function it_should_show_token_invalid_notice_when_square_refused_the_refresh(): void {
		tribe( Merchant::class )->update_refresh_status( [ 'invalid_at' => '2026-01-01 00:00:00' ] );

		$this->assertTrue( $this->make_controller()->should_display_token_invalid_notice() );
	}

	/**
	 * The notice explains why the gateway went inactive, so it cannot depend on the gateway being active.
	 *
	 * @test
	 */
	public function it_should_show_token_invalid_notice_even_though_the_gateway_is_inactive(): void {
		tribe( Merchant::class )->update_refresh_status( [ 'invalid_at' => '2026-01-01 00:00:00' ] );

		$this->assertFalse( tribe( Gateway::class )->is_active() );
		$this->assertTrue( $this->make_controller()->should_display_token_invalid_notice() );
	}

	/**
	 * Saving the Payments tab in this state clears the enabled flag, because the enable toggle renders
	 * disabled and so submits nothing. The notice has to outlive that or it erases its own instructions.
	 *
	 * @test
	 */
	public function it_should_show_token_invalid_notice_even_though_the_gateway_is_not_enabled(): void {
		tribe( Merchant::class )->update_refresh_status( [ 'invalid_at' => '2026-01-01 00:00:00' ] );
		$this->set_class_fn_return( Abstract_Gateway::class, 'is_enabled', false );

		$this->assertTrue( $this->make_controller()->should_display_token_invalid_notice() );
	}

	/**
	 * @test
	 */
	public function it_should_render_token_invalid_notice(): void {
		$this->assertMatchesHtmlSnapshot( $this->make_controller()->render_token_invalid_notice() );
	}

	/**
	 * @test
	 */
	public function it_should_only_show_token_refresh_failing_notice_after_repeated_failures(): void {
		$merchant   = tribe( Merchant::class );
		$controller = $this->make_controller();

		$merchant->update( [ 'expires_at' => gmdate( 'c', time() + HOUR_IN_SECONDS ) ] );

		$this->assertFalse( $controller->should_display_token_refresh_failing_notice() );

		$merchant->update_refresh_status( [ 'failures' => 2 ] );
		$this->assertFalse( $controller->should_display_token_refresh_failing_notice() );

		$merchant->update_refresh_status( [ 'failures' => 3 ] );
		$this->assertTrue( $controller->should_display_token_refresh_failing_notice() );
	}

	/**
	 * Failures far from the expiration have every chance of clearing up on their own, and the notice
	 * cannot be dismissed, so warning about them would leave a banner up for weeks over nothing.
	 *
	 * @test
	 */
	public function it_should_not_show_token_refresh_failing_notice_far_from_the_expiration(): void {
		$merchant = tribe( Merchant::class );

		$merchant->update( [ 'expires_at' => gmdate( 'c', time() + 30 * DAY_IN_SECONDS ) ] );
		$merchant->update_refresh_status( [ 'failures' => 5 ] );

		$this->assertFalse( $this->make_controller()->should_display_token_refresh_failing_notice() );
	}

	/**
	 * The two notices describe the same connection, so they must never appear together.
	 *
	 * @test
	 */
	public function it_should_not_stack_the_two_token_notices(): void {
		$merchant   = tribe( Merchant::class );
		$controller = $this->make_controller();

		$merchant->update_refresh_status(
			[
				'failures'   => 5,
				'invalid_at' => '2026-01-01 00:00:00',
			]
		);

		$this->assertTrue( $controller->should_display_token_invalid_notice() );
		$this->assertFalse( $controller->should_display_token_refresh_failing_notice() );
	}

	/**
	 * @test
	 */
	public function it_should_render_token_refresh_failing_notice(): void {
		$this->assertMatchesHtmlSnapshot( $this->make_controller()->render_token_refresh_failing_notice() );
	}
}
