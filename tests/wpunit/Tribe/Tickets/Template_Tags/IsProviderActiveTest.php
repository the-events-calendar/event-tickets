<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;

class IsProviderActiveTest extends \Codeception\TestCase\WPTestCase {

	use PayPal_Ticket_Maker;
	use RSVP_Ticket_Maker;

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		$this->factory()->event = new Event();
		$this->event_id         = $this->factory()->event->create();
		// Set up some reused vars.
		$this->num_tickets = 5;
		$this->capacity    = 5;
		$this->stock       = 3;
		$this->sales       = 2;

		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	public function tearDown() {
		// refresh the event ID for each test.
		unset( $this->event_id );

		// then
		parent::tearDown();
	}

	/**
	 * @test
	 * It should determine RSVP is an active provider, whether passed instance, class string, or slug string.
	 *
	 * @covers ::tribe_tickets_is_provider_active()
	 */
	public function it_should_determine_rsvp_is_active_provider() {
		// Instance.
		$rsvp = tribe( 'Tribe__Tickets__RSVP' );

		$instance = is_object( $rsvp ) && tribe_tickets_is_provider_active( $rsvp );
		$this->assertTrue( $instance, 'Checking against an instance should have worked.' );

		// Class string.
		$class = tribe_tickets_is_provider_active( 'Tribe__Tickets__RSVP' );
		$this->assertTrue( $class, 'Checking against a class name string should have worked.'  );

		// Slug.
		$slug = tribe_tickets_is_provider_active( 'rsvp' );
		$this->assertTrue( $slug, 'Checking against a slug should have worked.'  );
	}

}
