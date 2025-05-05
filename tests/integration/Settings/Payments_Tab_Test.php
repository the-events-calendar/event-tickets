<?php

namespace TEC\Tickets_Plus\Test\Integration\Settings;

use TEC\Tickets\Commerce\Payments_Tab;
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
		$_GET['tab'] = $tab;
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
}
