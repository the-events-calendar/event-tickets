<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;

class Providers_Test extends WPTestCase {
	use Relative_Sale_Dates_Maker;
	use Ticket_Maker;
	use With_Tickets_Commerce;

	/**
	 * @test
	 */
	public function should_apply_to_tickets_commerce_alone_by_default(): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$tc_id     = $this->create_tc_ticket( $event_id );
		$paypal_id = $this->create_ticket( 'tickets.commerce.paypal', $event_id, 1 );
		$providers = tribe( Providers::class );

		$this->assertSame( [ Module::class ], $providers->get_classes() );
		$this->assertTrue( $providers->accepts_provider( Module::class ) );
		$this->assertFalse( $providers->accepts_provider( tribe( 'tickets.commerce.paypal' )->class_name ) );
		$this->assertTrue( $providers->accepts_ticket( $tc_id ) );
		$this->assertFalse( $providers->accepts_ticket( $paypal_id ) );
		$this->assertSame( [ Module::class ], array_keys( $providers->get_active() ) );
	}

	/**
	 * @test
	 */
	public function should_apply_to_the_tickets_of_an_active_provider_the_filter_adds(): void {
		$paypal    = $this->accept_paypal_tickets();
		$paypal_id = $this->create_ticket( 'tickets.commerce.paypal', $this->create_event( '2027-06-24 19:00:00' ), 1 );
		$providers = tribe( Providers::class );

		$this->assertSame( [ Module::class, $paypal ], $providers->get_classes() );
		$this->assertTrue( $providers->accepts_ticket( $paypal_id ) );
		$this->assertSame( [ Module::class, $paypal ], array_keys( $providers->get_active() ) );
	}

	/**
	 * @test
	 */
	public function should_leave_out_of_the_active_providers_one_that_is_off(): void {
		add_filter( 'tec_tickets_relative_sale_dates_ticket_providers', static fn( array $classes ): array => array_merge( $classes, [ 'Inactive_Provider' ] ) );

		$this->assertTrue( tribe( Providers::class )->accepts_provider( 'Inactive_Provider' ) );
		$this->assertSame( [ Module::class ], array_keys( tribe( Providers::class )->get_active() ) );

		// After the suite's own filter, which turns Tickets Commerce on for every test.
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_false', PHP_INT_MAX );

		$this->assertFalse( tribe( Providers::class )->has_active_provider() );
	}

	/**
	 * @test
	 */
	public function should_keep_only_the_class_names_a_filter_returns(): void {
		add_filter( 'tec_tickets_relative_sale_dates_ticket_providers', static fn( array $classes ): array => array_merge( $classes, [ null, 42 ] ) );

		$this->assertSame( [ Module::class ], tribe( Providers::class )->get_classes() );

		add_filter( 'tec_tickets_relative_sale_dates_ticket_providers', '__return_false', 20 );

		$this->assertSame( [ Module::class ], tribe( Providers::class )->get_classes() );
	}
}
