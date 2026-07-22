<?php

namespace TEC\Tickets\Commerce;

use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;

/**
 * Guards `TEC\Tickets\Commerce\Module::get_attendee()` and
 * `Module::get_attendees_by_id()` against being called while Tickets Commerce
 * is disabled — the free function `tec_tc_get_attendee()` isn't loaded in
 * that state and would otherwise raise a fatal.
 */
class Module_Guards_Test extends WPTestCase {

	use With_Uopz;

	private function module(): Module {
		return tribe( Module::class );
	}

	public function test_get_attendee_returns_false_when_tc_is_disabled(): void {
		$this->set_fn_return( 'tec_tickets_commerce_is_enabled', false );

		$this->assertFalse( $this->module()->get_attendee( 12345 ) );
	}

	public function test_get_attendees_by_id_returns_empty_array_when_tc_is_disabled(): void {
		$this->set_fn_return( 'tec_tickets_commerce_is_enabled', false );

		$event_id = static::factory()->post->create( [ 'post_type' => 'post' ] );

		$this->assertSame( [], $this->module()->get_attendees_by_id( $event_id ) );
	}

	public function test_get_attendee_does_not_fatal_for_unknown_attendee_when_tc_disabled(): void {
		// Regression guard: the customer-reported fatal occurs because `tec_tc_get_attendee` is
		// not loaded when TC is disabled. If the guard is ever removed, this test will surface
		// an `Error` instead of returning a value.
		$this->set_fn_return( 'tec_tickets_commerce_is_enabled', false );

		$result = $this->module()->get_attendee( PHP_INT_MAX );

		$this->assertFalse( $result );
	}

	public function test_get_attendees_by_id_does_not_fatal_when_tc_disabled_for_event(): void {
		// Reproduces the customer stack: `Ticket_Object::inventory()` calls
		// `Module::get_attendees_by_id( $event_id )` for a global-stock ticket. If the guard
		// is removed the default switch branch reaches `get_attendee`, which fatals.
		$this->set_fn_return( 'tec_tickets_commerce_is_enabled', false );

		$event_id = static::factory()->post->create( [ 'post_type' => 'post' ] );

		$this->assertSame( [], $this->module()->get_attendees_by_id( $event_id ) );
	}
}
