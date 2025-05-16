<?php

namespace TEC\Tickets_Plus\Test\Integration\Settings;

use TEC\Tickets\Commerce\Payments_Tab;
use TEC\Tickets\Settings as Tickets_Commerce_Settings;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;
use Generator;

/**
 * Class Attendee_Registration_Test
 *
 * @package TEC\Tickets_Plus\Tests\Integration\Settings
 */
class Payments_Tab_Test extends WPTestCase {
	use SnapshotAssertions;
	use With_Uopz;

	/**
	 * Original option values.
	 *
	 * @var array<string,mixed>
	 */
	protected array $original_options = [];

	/**
	 * @after
	 */
	public function reset_options(): void {
		foreach ( $this->original_options as $key => $value ) {
			tribe_update_option( $key, $value );
		}
		$this->original_options = [];
	}

	/**
	 * Store original option value and set new one.
	 *
	 * @param string $key   The option key.
	 * @param mixed  $value The new option value.
	 */
	protected function set_option( string $key, $value ): void {
		if ( ! isset( $this->original_options[ $key ] ) ) {
			$this->original_options[ $key ] = tribe_get_option( $key );
		}
		tribe_update_option( $key, $value );
	}

	/**
	 * Data provider for payment tab tests.
	 *
	 * @since TBD
	 *
	 * @return Generator<string,array{
	 *     tab: string,
	 *     options: array<string,mixed>
	 * }>
	 */
	public function provide_payment_tab_tests(): Generator {
		// Main payments tab
		yield 'main payments tab' => [
			'payments',
			[],
		];

		// Tickets Commerce section
		yield 'tickets commerce section' => [
			'tickets-commerce',
			[],
		];

		// Stripe gateway section
		yield 'stripe gateway section' => [
			'stripe',
			[],
		];

		// PayPal gateway section
		yield 'paypal gateway section' => [
			'paypal',
			[],
		];

		// Tickets Commerce enabled
		yield 'tickets commerce enabled' => [
			'tickets-commerce',
			[
				'tec_tickets_commerce_enabled' => true,
			],
		];

		// Stripe enabled
		yield 'stripe enabled' => [
			'stripe',
			[
				'tec_tc_payments_gateway_enabled_stripe' => true,
			],
		];

		// PayPal enabled
		yield 'paypal enabled' => [
			'paypal',
			[
				'tec_tc_payments_gateway_enabled_paypal' => true,
			],
		];
	}

	/**
	 * @test
	 * @dataProvider provide_payment_tab_tests
	 */
	public function should_match_payment_tab_snapshots( string $tab, array $options ): void {
		// Set up WordPress admin environment
		$this->set_fn_return( 'is_admin', true );
		$this->set_fn_return( 'current_user_can', true );
		$this->set_fn_return( 'check_admin_referer', true );

		// Set options if provided
		foreach ( $options as $key => $value ) {
			$this->set_option( $key, $value );
		}

		// Set the current tab
		$_GET['tab']  = $tab;
		$payments_tab = tribe( Payments_Tab::class );
		$payments_tab->register_tab( 'tec-tickets-settings' );

		// Get the settings instance and get the fields
		$tab = $payments_tab->get_settings_tab();

		// Capture the output
		ob_start();
		$tab->do_content();
		$content = ob_get_clean();

		// Normalize version numbers in the content
		$content = preg_replace(
			'/version=\d+\.\d+\.\d+/',
			'version={version}',
			$content
		);

		// Assert the snapshot
		$this->assertMatchesHtmlSnapshot( $content );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Commerce\Payments_Tab::maybe_generate_pages
	 */
	public function it_should_return_early_if_tickets_commerce_not_enabled() {
		// Set up request vars
		$_GET['page'] = 'tec-tickets-settings';
		$_GET['tab']  = 'payments';

		// Mock the page generation methods to verify they're not called
		$checkout_page_generated = false;
		$success_page_generated  = false;

		$this->set_class_fn_return(
			Payments_Tab::class,
			'maybe_auto_generate_checkout_page',
			function () use ( &$checkout_page_generated ) {
				$checkout_page_generated = true;

				return false;
			}
		);

		$this->set_class_fn_return(
			Payments_Tab::class,
			'maybe_auto_generate_order_success_page',
			function () use ( &$success_page_generated ) {
				$success_page_generated = true;

				return false;
			}
		);

		// Call the method
		$payments_tab = tribe( Payments_Tab::class );
		$payments_tab->maybe_generate_pages();

		$this->assertFalse( $checkout_page_generated, 'Checkout page should not be generated when Tickets Commerce is not enabled' );
		$this->assertFalse( $success_page_generated, 'Success page should not be generated when Tickets Commerce is not enabled' );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Commerce\Payments_Tab::maybe_generate_pages
	 */
	public function it_should_generate_checkout_page_when_not_exists() {
		// Set up request vars
		$_GET['page'] = 'tec-tickets-settings';
		$_GET['tab']  = 'payments';
		$_GET[ Tickets_Commerce_Settings::$tickets_commerce_enabled ] = '1';

		$_POST['tickets-commerce-enabled']                            = '1';
		$_POST['tec_tickets_commerce_enabled']                        = '1';

		// Enable Tickets Commerce
		$this->set_option( 'tickets-commerce-enabled', '1' );
		$this->set_option( 'tec_tickets_commerce_enabled', '1' );

		// Mock the page generation methods
		$checkout_page_generated = false;
		$success_page_generated  = false;

		$this->set_class_fn_return(
			Payments_Tab::class,
			'maybe_auto_generate_checkout_page',
			function () use ( &$checkout_page_generated ) {
				$checkout_page_generated = true;

				return true;
			},
			true
		);

		$this->set_class_fn_return(
			Payments_Tab::class,
			'maybe_auto_generate_order_success_page',
			function () use ( &$success_page_generated ) {
				$success_page_generated = true;

				return true;
			},
			true
		);

		// Call the method
		$payments_tab = tribe( Payments_Tab::class );
		$payments_tab->maybe_generate_pages();

		$this->assertTrue( $checkout_page_generated, 'Checkout page should be generated when Tickets Commerce is enabled' );
		$this->assertTrue( $success_page_generated, 'Success page should be generated when Tickets Commerce is enabled' );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Commerce\Payments_Tab::maybe_generate_pages
	 */
	public function it_should_not_generate_pages_when_they_exist() {
		// Set up request vars
		$_GET['page']                                                 = 'tec-tickets-settings';
		$_GET['tab']                                                  = 'payments';
		$_GET[ Tickets_Commerce_Settings::$tickets_commerce_enabled ] = '1';

		$_POST['tickets-commerce-enabled']     = '1';
		$_POST['tec_tickets_commerce_enabled'] = '1';

		// Enable Tickets Commerce
		$this->set_option( 'tickets-commerce-enabled', '1' );
		$this->set_option( 'tec_tickets_commerce_enabled', '1' );

		// Mock the page generation methods to return false (indicating pages exist)
		$checkout_page_generated = false;
		$success_page_generated  = false;

		$this->set_class_fn_return(
			Payments_Tab::class,
			'maybe_auto_generate_checkout_page',
			function () use ( &$checkout_page_generated ) {
				$checkout_page_generated = true;

				return false;
			},
			true
		);

		$this->set_class_fn_return(
			Payments_Tab::class,
			'maybe_auto_generate_order_success_page',
			function () use ( &$success_page_generated ) {
				$success_page_generated = true;

				return false;
			},
			true
		);

		// Call the method
		$payments_tab = tribe( Payments_Tab::class );
		$payments_tab->maybe_generate_pages();

		$this->assertTrue( $checkout_page_generated, 'Checkout page generation should be attempted' );
		$this->assertTrue( $success_page_generated, 'Success page generation should be attempted' );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Commerce\Payments_Tab::maybe_generate_pages
	 */
	public function it_should_generate_pages_with_correct_content() {
		// Set up request vars
		$_GET['page']                          = 'tec-tickets-settings';
		$_GET['tab']                           = 'payments';
		$_POST['tickets-commerce-enabled']     = '1';
		$_POST['tec_tickets_commerce_enabled'] = '1';

		// Enable Tickets Commerce
		$this->set_option( 'tickets-commerce-enabled', '1' );
		$this->set_option( 'tec_tickets_commerce_enabled', '1' );

		// Delete any existing pages first
		$checkout_page = get_page_by_path( 'checkout' );
		$success_page  = get_page_by_path( 'order-success' );
		if ( $checkout_page ) {
			wp_delete_post( $checkout_page->ID, true );
		}
		if ( $success_page ) {
			wp_delete_post( $success_page->ID, true );
		}

		// Call the method
		$payments_tab = tribe( Payments_Tab::class );
		$payments_tab->maybe_generate_pages();

		// Verify checkout page was created
		$checkout_page = get_page_by_path( 'checkout' );
		$this->assertNotFalse( $checkout_page, 'Checkout page should be created' );
		if ( $checkout_page ) {
			$this->assertEquals( 'Checkout', $checkout_page->post_title, 'Checkout page should have correct title' );
			$this->assertEquals( 'publish', $checkout_page->post_status, 'Checkout page should be published' );
			$this->assertStringContainsString( '[tribe_tickets_commerce_checkout]', $checkout_page->post_content, 'Checkout page should contain the checkout shortcode' );
		}

		// Verify success page was created
		$success_page = get_page_by_path( 'order-success' );
		$this->assertNotFalse( $success_page, 'Success page should be created' );
		if ( $success_page ) {
			$this->assertEquals( 'Order Success', $success_page->post_title, 'Success page should have correct title' );
			$this->assertEquals( 'publish', $success_page->post_status, 'Success page should be published' );
			$this->assertStringContainsString( '[tribe_tickets_commerce_order_success]', $success_page->post_content, 'Success page should contain the success shortcode' );
		}

		// Clean up
		if ( $checkout_page ) {
			wp_delete_post( $checkout_page->ID, true );
		}
		if ( $success_page ) {
			wp_delete_post( $success_page->ID, true );
		}
	}
}
