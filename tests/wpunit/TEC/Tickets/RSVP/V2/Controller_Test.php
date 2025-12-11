<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;

class Controller_Test extends WPTestCase {
	public function test_do_register_registers_singletons(): void {
		$controller = new Controller( tribe() );
		$controller->register();

		// Verify Meta and Ticket singletons are registered.
		$this->assertInstanceOf( Meta::class, tribe( Meta::class ) );
		$this->assertInstanceOf( Ticket::class, tribe( Ticket::class ) );

		// Verify same instance is returned (singleton behavior).
		$this->assertSame( tribe( Meta::class ), tribe( Meta::class ) );
		$this->assertSame( tribe( Ticket::class ), tribe( Ticket::class ) );
	}

	public function test_unregister_does_not_throw(): void {
		$controller = new Controller( tribe() );
		$controller->unregister();

		// If we get here without exception, test passes.
		$this->assertTrue( true );
	}

	public function test_is_active_returns_true_by_default(): void {
		$controller = new Controller( tribe() );

		$this->assertTrue( $controller->is_active() );
	}

	public function test_is_active_can_be_filtered(): void {
		add_filter( 'tec_tickets_rsvp_v2_enabled', '__return_false' );

		$controller = new Controller( tribe() );
		$this->assertFalse( $controller->is_active() );

		remove_filter( 'tec_tickets_rsvp_v2_enabled', '__return_false' );
	}

	public function test_registration_action_property_exists(): void {
		// Verify the action property exists and has the expected value.
		$this->assertSame( 'tec_tickets_rsvp_v2_registered', Controller::$registration_action );
	}
}
