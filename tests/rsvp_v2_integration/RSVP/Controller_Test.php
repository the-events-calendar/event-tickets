<?php

namespace TEC\Tickets\RSVP;

use lucatume\WPBrowser\TestCase\WPTestCase;

class Controller_Test extends WPTestCase {
	public function test_activates_tickets_commerce(): void {
		$this->assertTrue( tec_tickets_commerce_is_enabled() );
	}

	public function test_allows_overriding_tickets_commerce_activation_with_filter(): void {
		// The Controller will filter Tickets Commerce active at priority 10, filter later to deactivate it.
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_false', 20 );
		// This will be already set by the suite setup, but we're explicitly doing it here for clarity.
		add_filter( 'tec_tickets_rsvp_version', static fn() => Controller::VERSION_2 );

		Controller::maybe_activate_tickets_commerce();

		$this->assertFalse( apply_filters( 'tec_tickets_commerce_is_enabled', true ) );
	}
}
