<?php

namespace Tribe\Tickets;

use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__RSVP as RSVP;

/**
 * Class IsProviderActiveTest
 *
 * @package Tribe\Tickets
 *
 * @see     \tribe_tickets_is_provider_active()
 */
class IsProviderActiveTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @test
	 * It should determine RSVP is an active provider, whether passed instance, class string, or slug string.
	 *
	 * @covers ::tribe_tickets_is_provider_active()
	 */
	public function it_should_determine_rsvp_is_active_provider() {
		// Instance.
		$rsvp = tribe( RSVP::class );

		$instance = is_object( $rsvp ) && tribe_tickets_is_provider_active( $rsvp );
		$this->assertTrue( $instance, 'Checking against an instance should have worked.' );

		// Class string.
		$class = tribe_tickets_is_provider_active( RSVP::class );
		$this->assertTrue( $class, 'Checking against a class name string should have worked.' );

		// Slug.
		$slug = tribe_tickets_is_provider_active( 'rsvp' );
		$this->assertTrue( $slug, 'Checking against a slug should have worked.' );
	}

	/**
	 * @test
	 * It should determine Tribe Commerce is NOT an active provider, whether passed instance, class string, or slug.
	 *
	 * @covers ::tribe_tickets_is_provider_active()
	 */
	public function it_should_determine_tribe_commerce_is_not_active_provider() {
		// Instance.
		$tpp = tribe( PayPal::class );

		$instance = is_object( $tpp ) && ! tribe_tickets_is_provider_active( $tpp );
		$this->assertTrue( $instance, 'Checking against an instance should have worked.' );

		// Class string.
		$class = tribe_tickets_is_provider_active( PayPal::class );
		$this->assertFalse( $class, 'Checking against a class name string should have worked.' );

		// Slug.
		$slug = tribe_tickets_is_provider_active( 'tpp' );
		$this->assertFalse( $slug, 'Checking against a slug should have worked.' );
	}

	/**
	 * @test
	 * It should determine Tribe Commerce is an active provider, whether passed instance, class string, or slug string.
	 *
	 * @covers ::tribe_tickets_is_provider_active()
	 */
	public function it_should_determine_tribe_commerce_is_active_provider() {
		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter(
			'tribe_tickets_get_modules',
			function ( $modules ) {
				$modules[ PayPal::class ] = tribe( 'tickets.commerce.paypal' )->plugin_name;

				return $modules;
			}
		);

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );

		// Instance.
		$tpp = tribe( PayPal::class );

		$instance = is_object( $tpp ) && tribe_tickets_is_provider_active( $tpp );
		$this->assertTrue( $instance, 'Checking against an instance should have worked.' );

		// Class string.
		$class = tribe_tickets_is_provider_active( PayPal::class );
		$this->assertTrue( $class, 'Checking against a class name string should have worked.' );

		// Slug.
		$slug = tribe_tickets_is_provider_active( 'tpp' );
		$this->assertTrue( $slug, 'Checking against a slug should have worked.' );
	}

}
