<?php

namespace Tribe\Admin;

use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Admin__Notices;
use Tribe__Tickets__Admin__Notices;

class NoticesTest extends WPTestCase {

	use With_Uopz;

	private const WOO_NOTICE_SLUG = 'event-tickets-plus-missing-woocommerce-support';
	private const EDD_NOTICE_SLUG = 'event-tickets-plus-missing-easydigitaldownloads-support';

	/**
	 * @before
	 */
	public function clear_registered_notices(): void {
		$notices = Tribe__Admin__Notices::instance();
		$notices->remove( self::WOO_NOTICE_SLUG );
		$notices->remove( self::EDD_NOTICE_SLUG );
	}

	/**
	 * @after
	 */
	public function reset_current_screen(): void {
		unset( $GLOBALS['current_screen'] );
	}

	private function fake_plugin_active( array $active_paths ): void {
		$this->set_fn_return(
			'is_plugin_active',
			static function ( $path ) use ( $active_paths ): bool {
				return in_array( $path, $active_paths, true );
			},
			true
		);
	}

	private function get_notices_class(): Tribe__Tickets__Admin__Notices {
		return new Tribe__Tickets__Admin__Notices();
	}

	/**
	 * The screen gate must allow Tickets admin screens.
	 *
	 * @test
	 */
	public function is_plus_commerce_notice_context_should_allow_tickets_admin_screen(): void {
		set_current_screen( 'tickets_page_tec-tickets-settings' );

		$this->assertTrue( $this->get_notices_class()->is_plus_commerce_notice_context() );
	}

	/**
	 * The screen gate must allow the Plugins page so the notice surfaces right after activation.
	 *
	 * @test
	 */
	public function is_plus_commerce_notice_context_should_allow_plugins_screen(): void {
		set_current_screen( 'plugins' );

		$this->assertTrue( $this->get_notices_class()->is_plus_commerce_notice_context() );
	}

	/**
	 * Network admin Plugins page shares the same screen base.
	 *
	 * @test
	 */
	public function is_plus_commerce_notice_context_should_allow_network_plugins_screen(): void {
		set_current_screen( 'plugins-network' );

		$this->assertTrue( $this->get_notices_class()->is_plus_commerce_notice_context() );
	}

	/**
	 * The screen gate must allow The Events Calendar submenu screens.
	 *
	 * @test
	 */
	public function is_plus_commerce_notice_context_should_allow_tec_submenu_screen(): void {
		set_current_screen( 'tribe_events_page_tec-events-settings' );

		$this->assertTrue( $this->get_notices_class()->is_plus_commerce_notice_context() );
	}

	/**
	 * The screen gate must allow the All Events list table.
	 *
	 * @test
	 */
	public function is_plus_commerce_notice_context_should_allow_events_list_screen(): void {
		set_current_screen( 'edit-tribe_events' );

		$this->assertTrue( $this->get_notices_class()->is_plus_commerce_notice_context() );
	}

	/**
	 * The screen gate must allow the single event editor.
	 *
	 * @test
	 */
	public function is_plus_commerce_notice_context_should_allow_single_event_screen(): void {
		set_current_screen( 'tribe_events' );

		$this->assertTrue( $this->get_notices_class()->is_plus_commerce_notice_context() );
	}

	/**
	 * The screen gate must reject screens outside the Tickets, TEC, and Plugins contexts.
	 *
	 * @test
	 */
	public function is_plus_commerce_notice_context_should_reject_unrelated_screen(): void {
		set_current_screen( 'dashboard' );

		$this->assertFalse( $this->get_notices_class()->is_plus_commerce_notice_context() );
	}

	/**
	 * Editing a non-event post should not surface the notice.
	 *
	 * @test
	 */
	public function is_plus_commerce_notice_context_should_reject_non_event_post_editor(): void {
		set_current_screen( 'post' );

		$this->assertFalse( $this->get_notices_class()->is_plus_commerce_notice_context() );
	}

	/**
	 * If no screen is set, the gate must reject (the active_callback runs on admin_notices,
	 * so this case is mostly defensive).
	 *
	 * @test
	 */
	public function is_plus_commerce_notice_context_should_reject_when_no_screen_set(): void {
		unset( $GLOBALS['current_screen'] );

		$this->assertFalse( $this->get_notices_class()->is_plus_commerce_notice_context() );
	}

	/**
	 * The notice should be registered (and gated by the active_callback) when WooCommerce is active.
	 *
	 * @test
	 */
	public function it_should_register_woocommerce_notice_with_screen_gate_active_callback(): void {
		$this->fake_plugin_active( [ 'woocommerce/woocommerce.php' ] );

		$this->get_notices_class()->maybe_display_plus_commerce_notice();

		$notice = Tribe__Admin__Notices::instance()->get( self::WOO_NOTICE_SLUG );

		$this->assertNotNull( $notice, 'WooCommerce notice should be registered when WooCommerce is active.' );
		$this->assertTrue(
			is_callable( $notice->active_callback ?? null ),
			'The notice must be registered with an active_callback so the screen check runs at display time.'
		);
	}

	/**
	 * Same for Easy Digital Downloads.
	 *
	 * @test
	 */
	public function it_should_register_edd_notice_with_screen_gate_active_callback(): void {
		$this->fake_plugin_active( [ 'easy-digital-downloads/easy-digital-downloads.php' ] );

		$this->get_notices_class()->maybe_display_plus_commerce_notice();

		$notice = Tribe__Admin__Notices::instance()->get( self::EDD_NOTICE_SLUG );

		$this->assertNotNull( $notice, 'EDD notice should be registered when EDD is active.' );
		$this->assertTrue(
			is_callable( $notice->active_callback ?? null ),
			'The notice must be registered with an active_callback so the screen check runs at display time.'
		);
	}

	/**
	 * Notices should not be registered when none of the supported providers are active.
	 *
	 * @test
	 */
	public function it_should_not_register_notice_when_no_supported_provider_is_active(): void {
		$this->fake_plugin_active( [] );

		$this->get_notices_class()->maybe_display_plus_commerce_notice();

		$this->assertFalse(
			Tribe__Admin__Notices::instance()->exists( self::WOO_NOTICE_SLUG ),
			'WooCommerce notice should not be registered when WooCommerce is inactive.'
		);
		$this->assertFalse(
			Tribe__Admin__Notices::instance()->exists( self::EDD_NOTICE_SLUG ),
			'EDD notice should not be registered when EDD is inactive.'
		);
	}

	/**
	 * The registered active_callback should drive display: true on Tickets/Plugins screens, false elsewhere.
	 *
	 * @test
	 */
	public function active_callback_should_gate_by_current_screen(): void {
		$this->fake_plugin_active( [ 'woocommerce/woocommerce.php' ] );

		$this->get_notices_class()->maybe_display_plus_commerce_notice();

		$notice = Tribe__Admin__Notices::instance()->get( self::WOO_NOTICE_SLUG );
		$this->assertNotNull( $notice );

		set_current_screen( 'tickets_page_tec-tickets-settings' );
		$this->assertTrue( call_user_func( $notice->active_callback ) );

		set_current_screen( 'plugins' );
		$this->assertTrue( call_user_func( $notice->active_callback ) );

		set_current_screen( 'dashboard' );
		$this->assertFalse( call_user_func( $notice->active_callback ) );
	}
}
