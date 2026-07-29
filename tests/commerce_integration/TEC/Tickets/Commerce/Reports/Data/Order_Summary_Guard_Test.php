<?php

namespace TEC\Tickets\Commerce\Reports\Data;

use Codeception\TestCase\WPTestCase;

/**
 * Regression: `Order_Summary::get_tickets()` used to fatal on an event that
 * had no active ticket provider because it called `$provider->get_tickets()`
 * on a `false` value returned by `Tribe__Tickets__Tickets::get_event_ticket_provider_object()`.
 */
class Order_Summary_Guard_Test extends WPTestCase {

	public function test_get_tickets_returns_empty_array_when_provider_is_missing(): void {
		$post_id = static::factory()->post->create( [ 'post_type' => 'post' ] );

		// A brand-new post has no ticket provider meta, so
		// `Tribe__Tickets__Tickets::get_event_ticket_provider_object()` returns false.
		$summary = new Order_Summary( $post_id );

		$this->assertSame( [], $summary->get_tickets() );
	}

	public function test_build_data_does_not_fatal_when_provider_is_missing(): void {
		// `Order_Summary` calls `build_data()` via `init()`. Prior to the fix, this would
		// walk into `get_tickets()` and dereference a `false` provider — fatal.
		$post_id = static::factory()->post->create( [ 'post_type' => 'post' ] );

		$summary = new Order_Summary( $post_id );
		$summary->init();

		$this->assertSame( [], $summary->get_tickets() );
	}
}
