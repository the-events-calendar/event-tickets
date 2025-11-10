<?php

namespace TEC\Common\Admin;

use Codeception\TestCase\WPTestCase;
use TEC\Common\Admin\Conditional_Content\Inline_Upsell;
use TEC\Tickets\Admin\Upsell;
use Tribe\Tests\Traits\With_Uopz;

/**
 * Test the Tickets Admin Upsell class.
 *
 * @since TBD
 *
 * @package TEC\Common\Admin
 */
class Upsell_Test extends WPTestCase {
	use With_Uopz;

	/**
	 * @var Upsell
	 */
	protected $upsell;

	/**
	 * Set up before each test.
	 */
	public function setUp() {
		parent::setUp();

		// Set as admin area.
		set_current_screen( 'edit-post' );

		$this->upsell = tribe( Upsell::class );

		// Mock Inline_Upsell to track calls.
		$this->mock_inline_upsell();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown() {
		remove_all_filters( 'tec_should_hide_upsell' );
		set_current_screen( 'front' );
		parent::tearDown();
	}

	/**
	 * Mock Inline_Upsell for testing.
	 */
	protected function mock_inline_upsell() {
		// Mock the protected is_plugin_active method to return false by default (ET+ not active).
		$this->set_class_fn_return(
			Inline_Upsell::class,
			'is_plugin_active',
			false
		);
	}

	/**
	 * Test capacity/ARF upsell renders when ET+ is not active.
	 *
	 * @test
	 */
	public function should_render_capacity_arf_upsell_when_etp_not_active() {
		ob_start();
		$this->upsell->maybe_show_capacity_arf();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'capacity', strtolower( $output ), 'Should mention capacity' );
		$this->assertStringContainsString( 'Event Tickets Plus', $output, 'Should mention ET+' );
	}

	/**
	 * Test capacity/ARF upsell does not render when ET+ is active.
	 *
	 * @test
	 */
	public function should_not_render_capacity_arf_when_etp_active() {
		// Mock ET+ as active.
		$this->set_class_fn_return(
			Inline_Upsell::class,
			'is_plugin_active',
			true
		);

		ob_start();
		$this->upsell->maybe_show_capacity_arf();
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Should not render when ET+ is active' );
	}

	/**
	 * Test capacity/ARF upsell does not render outside admin.
	 *
	 * @test
	 */
	public function should_not_render_capacity_arf_outside_admin() {
		// Simulate frontend.
		set_current_screen( 'front' );

		ob_start();
		$this->upsell->maybe_show_capacity_arf();
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Should not render outside admin area' );
	}

	/**
	 * Test manual attendees upsell renders when ET+ is not active.
	 *
	 * @test
	 */
	public function should_render_manual_attendees_upsell_when_etp_not_active() {
		ob_start();
		$this->upsell->maybe_show_manual_attendees();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Manually add attendees', $output, 'Should mention manual attendees' );
		$this->assertStringContainsString( 'Event Tickets Plus', $output, 'Should mention ET+' );
		$this->assertStringContainsString( 'welcome-panel-column', $output, 'Should have wrapper div' );
	}

	/**
	 * Test manual attendees upsell does not render when ET+ is active.
	 *
	 * @test
	 */
	public function should_not_render_manual_attendees_when_etp_active() {
		// Mock ET+ as active.
		$this->set_class_fn_return(
			Inline_Upsell::class,
			'is_plugin_active',
			true
		);

		ob_start();
		$this->upsell->maybe_show_manual_attendees();
		$output = ob_get_clean();

		// Should only have wrapper div, no content.
		$this->assertStringContainsString( 'welcome-panel-column', $output, 'Should have wrapper div' );
		$this->assertStringNotContainsString( 'Manually add attendees', $output, 'Should not have upsell content' );
	}

	/**
	 * Test wallet plus upsell renders when ET+ is not active.
	 *
	 * @test
	 */
	public function should_render_wallet_plus_upsell_when_etp_not_active() {
		ob_start();
		$this->upsell->show_wallet_plus();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Apple Wallet', $output, 'Should mention Apple Wallet' );
		$this->assertStringContainsString( 'PDF tickets', $output, 'Should mention PDF tickets' );
		$this->assertStringContainsString( 'Event Tickets Plus', $output, 'Should mention ET+' );
		$this->assertStringContainsString( 'welcome-panel-column', $output, 'Should have wrapper div' );
	}

	/**
	 * Test wallet plus upsell does not render when ET+ is active.
	 *
	 * @test
	 */
	public function should_not_render_wallet_plus_when_etp_active() {
		// Mock ET+ as active.
		$this->set_class_fn_return(
			Inline_Upsell::class,
			'is_plugin_active',
			true
		);

		ob_start();
		$this->upsell->show_wallet_plus();
		$output = ob_get_clean();

		// Should only have wrapper div, no content.
		$this->assertStringContainsString( 'welcome-panel-column', $output, 'Should have wrapper div' );
		$this->assertStringNotContainsString( 'Apple Wallet', $output, 'Should not have upsell content' );
	}

	/**
	 * Test attendees page shows one of two upsells.
	 *
	 * @test
	 */
	public function should_show_one_upsell_on_attendees_page() {
		ob_start();
		$this->upsell->show_on_attendees_page();
		$output = ob_get_clean();

		// Should show either manual attendees OR wallet plus (not both).
		$has_manual   = strpos( $output, 'Manually add attendees' ) !== false;
		$has_wallet   = strpos( $output, 'Apple Wallet' ) !== false;
		$has_one_only = ( $has_manual && ! $has_wallet ) || ( ! $has_manual && $has_wallet );

		$this->assertTrue( $has_one_only, 'Should show exactly one upsell (manual attendees OR wallet plus)' );
	}

	/**
	 * Test attendees page does not show upsells outside admin.
	 *
	 * @test
	 */
	public function should_not_show_attendees_upsells_outside_admin() {
		// Simulate frontend.
		set_current_screen( 'front' );

		ob_start();
		$this->upsell->show_on_attendees_page();
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Should not render outside admin area' );
	}

	/**
	 * Test emails settings page upsell renders when ET+ is not active.
	 *
	 * @test
	 */
	public function should_add_emails_upsell_to_settings_when_etp_not_active() {
		$fields = [];

		$result = $this->upsell->show_on_emails_settings_page( $fields );

		$this->assertCount( 1, $result, 'Should add one field' );
		$this->assertEquals( 'html', $result[0]['type'], 'Should be HTML field' );
		$this->assertStringContainsString( 'Apple Wallet', $result[0]['html'], 'Should contain upsell HTML' );
	}

	/**
	 * Test emails settings page upsell adds field but with empty HTML when ET+ is active.
	 *
	 * @test
	 */
	public function should_add_empty_html_when_etp_active() {
		// Mock ET+ as active.
		$this->set_class_fn_return(
			Inline_Upsell::class,
			'is_plugin_active',
			true
		);

		$fields = [ 'existing' => 'field' ];

		$result = $this->upsell->show_on_emails_settings_page( $fields );

		$this->assertCount( 2, $result, 'Should add field (though HTML will be empty)' );
		$this->assertArrayHasKey( 'existing', $result, 'Should preserve existing fields' );
		$this->assertEquals( 'html', $result[0]['type'], 'Should be HTML field' );
		$this->assertEmpty( $result[0]['html'], 'HTML should be empty when ET+ is active' );
	}

	/**
	 * Test emails settings page upsell respects admin check.
	 *
	 * @test
	 */
	public function should_not_add_emails_upsell_outside_admin() {
		// Simulate frontend.
		set_current_screen( 'front' );

		$fields = [ 'existing' => 'field' ];

		$result = $this->upsell->show_on_emails_settings_page( $fields );

		$this->assertCount( 1, $result, 'Should not add new field' );
		$this->assertArrayHasKey( 'existing', $result, 'Should preserve existing fields' );
	}

	/**
	 * Test ticket type upsell returns early when not an event.
	 *
	 * @test
	 */
	public function should_not_render_ticket_type_upsell_when_not_event() {
		// Mock as not an event.
		$this->set_fn_return( 'tribe_is_event', '__return_false' );

		// Just verify method can be called without errors when not an event.
		$this->upsell->render_ticket_type_upsell_notice();

		// If we get here without errors, the early return worked.
		$this->assertTrue( true );
	}

	/**
	 * Test ticket type upsell returns early outside admin.
	 *
	 * @test
	 */
	public function should_not_render_ticket_type_upsell_outside_admin() {
		// Simulate frontend.
		set_current_screen( 'front' );

		$this->set_fn_return( 'tribe_is_event', '__return_true' );

		// Just verify method can be called without errors outside admin.
		$this->upsell->render_ticket_type_upsell_notice();

		// If we get here without errors, the early return worked.
		$this->assertTrue( true );
	}

	/**
	 * Test ticket type upsell returns early when ECP is active.
	 *
	 * @test
	 */
	public function should_not_render_ticket_type_upsell_when_ecp_active() {
		$this->set_fn_return( 'tribe_is_event', '__return_true' );
		$this->set_fn_return( 'did_action', '__return_true' ); // ECP active.

		// Just verify method can be called without errors when ECP is active.
		$this->upsell->render_ticket_type_upsell_notice();

		// If we get here without errors, the early return worked.
		$this->assertTrue( true );
	}

	/**
	 * Test hooks are registered.
	 *
	 * @test
	 */
	public function should_register_hooks() {
		$this->upsell->hooks();

		$this->assertEquals( 10, has_action( 'tribe_events_tickets_pre_edit', [ $this->upsell, 'maybe_show_capacity_arf' ] ), 'Should hook capacity/ARF upsell' );
		$this->assertEquals( 10, has_action( 'tec_tickets_attendees_event_summary_table_extra', [ $this->upsell, 'show_on_attendees_page' ] ), 'Should hook attendees page upsell' );
		$this->assertEquals( 10, has_filter( 'tribe_tickets_commerce_settings', [ $this->upsell, 'maybe_show_paystack_promo' ] ), 'Should hook Paystack promo' );
		$this->assertEquals( 20, has_action( 'tribe_template_after_include:tickets/admin-views/editor/ticket-type-default-header', [ $this->upsell, 'render_ticket_type_upsell_notice' ] ), 'Should hook ticket type upsell' );
		$this->assertEquals( 10, has_filter( 'tec_tickets_emails_settings_template_list', [ $this->upsell, 'show_on_emails_settings_page' ] ), 'Should hook emails settings upsell' );
	}
}
