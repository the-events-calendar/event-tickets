<?php

namespace Tribe\Tickets\Events\Views\V2\Models;

use lucatume\WPBrowser\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets as Tickets_Tickets;

class Tickets_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;
	use RSVP_Ticket_Maker;

	/**
	 * @before
	 * @after
	 */
	public function reset_kv_cache(): void {
		tec_kv_cache()->flush();
	}

	/**
	 * @test
	 * @covers \Tribe\Tickets\Events\Views\V2\Models\Tickets::__construct
	 */
	public function should_be_instantiatable(): void {
		$event_id = tribe_events()->set_args( [
			'title'      => 'Test Event',
			'status'     => 'publish',
			'start_date' => '2023-01-01 09:00:00',
			'duration'   => 4 * HOUR_IN_SECONDS,
		] )->create()->ID;

		$model = new Tickets( $event_id );

		$this->assertInstanceOf( Tickets::class, $model );
	}

	/**
	 * @test
	 * @covers \Tribe\Tickets\Events\Views\V2\Models\Tickets::__construct
	 */
	public function should_restore_from_cache_on_construct(): void {
		// Create an Event.
		$event_id = tribe_events()->set_args( [
			'title'      => 'Test Event',
			'status'     => 'publish',
			'start_date' => '2023-01-01 09:00:00',
			'duration'   => 4 * HOUR_IN_SECONDS,
		] )->create()->ID;
		// Create 3 Tickets Commerce tickets for the Event.
		[ $ticket_1_id, $ticket_2_id, $ticket_3_id ] = $this->create_many_tc_tickets( 3, $event_id );
		// For each ticket create an Order for 3 Attendees.
		$this->create_order( [ $ticket_1_id => 3, $ticket_2_id => 3, $ticket_3_id => 3 ] );

		$model_1 = new Tickets( $event_id );

		$this->assertFalse( tec_kv_cache()->has( Tickets::get_cache_key( $event_id ) ) );

		// This call will trigger the cache creation.
		$this->assertTrue( $model_1->exist() );

		$this->assertTrue( tec_kv_cache()->has( Tickets::get_cache_key( $event_id ) ) );

		$queries           = [];
		$intercept_queries = static function ( string $query ) use ( &$queries ): string {
			$queries[] = $query;

			return $query;
		};

		add_filter( 'query', $intercept_queries );
		$model_2 = new Tickets( $event_id );
		remove_filter( 'query', $intercept_queries );

		$this->assertTrue( tec_kv_cache()->has( Tickets::get_cache_key( $event_id ) ) );
		$this->assertCount( 1,
			$queries,
			'There should be only one query to get the model information from the cache.'
		);

		/** @var Ticket_Object $ticket */
		foreach ( \Closure::bind( fn() => $model_2->all_tickets, $model_2, Tickets::class )() as $ticket ) {
			$this->assertInstanceOf( Ticket_Object::class, $ticket );
			$this->assertInstanceOf( Module::class, $ticket->get_provider() );
		}
	}

	/**
	 * @test
	 * @covers \Tribe\Tickets\Events\Views\V2\Models\Tickets::regenerate_caches
	 */
	public function should_regenerate_post_caches(): void {
		// Create a post.
		$post_id = static::factory()->post->create();
		// Create an Event.
		$event_id = tribe_events()->set_args( [
			'title'      => 'Test Event',
			'status'     => 'publish',
			'start_date' => '2023-01-01 09:00:00',
			'duration'   => 4 * HOUR_IN_SECONDS,
		] )->create()->ID;
		// Create 3 Tickets Commerce tickets for the Event.
		[ $ticket_1_id, $ticket_2_id, $ticket_3_id ] = $this->create_many_tc_tickets( 3, $event_id );
		// For each ticket create an Order for 3 Attendees.
		$this->create_order( [ $ticket_1_id => 3, $ticket_2_id => 3, $ticket_3_id => 3 ] );
		$event_cache_key = Tickets::get_cache_key( $event_id );
		$cleanup = function() use ( $event_cache_key ) {
			tec_kv_cache()->delete( $event_cache_key );
			$this->assertFalse( tec_kv_cache()->has( $event_cache_key ) );
		};

		// Post
		Tickets::regenerate_caches( $post_id );
		$this->assertFalse( tec_kv_cache()->has( Tickets::get_cache_key( $post_id ) ) );

		// Event.
		$this->assertFalse( tec_kv_cache()->has( $event_cache_key ) );
		Tickets::regenerate_caches( $event_id );
		$this->assertTrue( tec_kv_cache()->has( $event_cache_key ) );

		$cleanup();

		// Attendee.
		$attendee_id = tribe_attendees()->where( 'event', $event_id )->fields( 'ids' )->first();
		Tickets::regenerate_caches( $attendee_id );
		$this->assertFalse( tec_kv_cache()->has( Tickets::get_cache_key( $attendee_id ) ) );
		$this->assertTrue( tec_kv_cache()->has( $event_cache_key ) );

		$cleanup();

		// Ticket.
		$ticket_id = tribe_tickets()->where( 'event', $event_id )->fields( 'ids' )->first();
		Tickets::regenerate_caches( $ticket_id );
		$this->assertFalse( tec_kv_cache()->has( Tickets::get_cache_key( $ticket_id ) ) );
		$this->assertTrue( tec_kv_cache()->has( $event_cache_key ) );
	}

	/**
	 * @test
	 * @covers \Tribe\Tickets\Events\Views\V2\Models\Tickets::regenerate_caches
	 */
	public function should_regenerate_post_caches_invalidating_ticket_caches(): void {
		// Create an Event.
		$event_id = tribe_events()->set_args( [
			'title'      => 'Test Event',
			'status'     => 'publish',
			'start_date' => '2023-01-01 09:00:00',
			'duration'   => 4 * HOUR_IN_SECONDS,
		] )->create()->ID;
		// Create 3 Tickets Commerce tickets for the Event.
		$ticket_ids = $this->create_many_tc_tickets( 3, $event_id );
		[ $ticket_1_id, $ticket_2_id, $ticket_3_id ] = $ticket_ids;
		// For each ticket create an Order for 3 Attendees.
		$this->create_order( [ $ticket_1_id => 3, $ticket_2_id => 3, $ticket_3_id => 3 ] );
		$event_cache_key = Tickets::get_cache_key( $event_id );
		// Poison the get_tickets cache with a value that would retur no tickets.
		$tickets_class              = Tickets_Tickets::class;
		$cache_key                  = "{$tickets_class}::get_tickets-tickets-commerce-{$event_id}";
		tribe_cache()[ $cache_key ] = [];
		// Poison each ticket cache with an object that would not return the correct ticket values.
		foreach ( $ticket_ids as $ticket_id ) {
			wp_cache_set( $ticket_id, 'not-a-ticket-value', 'tec_tickets' );
		}

		// Event.
		$this->assertFalse( tec_kv_cache()->has( $event_cache_key ) );
		Tickets::regenerate_caches( $event_id );
		$this->assertTrue( tec_kv_cache()->has( $event_cache_key ) );

		$array_model = ( new Tickets( $event_id ) )->to_array();

		$this->assertEquals( [
			'link'  =>
				[
					'anchor'             => 'http://wordpress.test/?tribe_events=test-event#tribe-tickets__tickets-form',
					'label'              => 'Get Tickets',
					'__original_class__' => 'stdClass',
				],
			'stock' =>
				[
					'available'          => '291 tickets left',
					'sold_out'           => '',
					'__original_class__' => 'stdClass',
				],
		], $array_model );
	}

	/**
	 * Tests the bug described in the suggestion for `refresh_cached_stock_display`:
	 * when availability drops to zero after the model cache was primed with "X tickets left",
	 * the next fetch should clear the stale available text and set the sold-out label instead
	 * of silently returning and leaving the stale display in place.
	 *
	 * @test
	 * @covers \Tribe\Tickets\Events\Views\V2\Models\Tickets::refresh_cached_stock_display
	 */
	public function should_show_sold_out_when_ticket_availability_drops_to_zero_while_served_from_model_cache(): void {
		$event_id = tribe_events()->set_args( [
			'title'      => 'Test Event',
			'status'     => 'publish',
			'start_date' => '2030-01-01 09:00:00',
			'duration'   => 4 * HOUR_IN_SECONDS,
		] )->create()->ID;

		// Create one ticket with a small, finite capacity so we can sell it out.
		$this->with_capacity( 3 );
		$ticket_id = $this->create_tc_ticket( $event_id, 1 );

		// Sell one ticket so 2 remain — the cache will be primed with "2 tickets left".
		$this->create_order( [ $ticket_id => 1 ] );

		// Clear the tribe_cache so get_ticket_counts reads fresh DB data when priming.
		tribe( Module::class )->clear_ticket_cache_for_post( $event_id );

		// Prime the model (kv) cache with the partial-availability snapshot.
		$model_1 = new Tickets( $event_id );
		$model_1->exist();
		$this->assertTrue( tec_kv_cache()->has( Tickets::get_cache_key( $event_id ) ) );

		$initial_data = $model_1->to_array();
		$this->assertNotEmpty(
			$initial_data['stock']['available'],
			'The cache should have been primed with an "X tickets left" stock text.'
		);
		$this->assertEmpty(
			$initial_data['stock']['sold_out'],
			'The cache should not mark the event as sold-out yet.'
		);

		// Sell the remaining 2 tickets — the event is now fully sold out.
		$this->create_order( [ $ticket_id => 2 ] );

		// Clear the tribe_cache again so refresh_cached_stock_display reads the updated DB counts.
		tribe( Module::class )->clear_ticket_cache_for_post( $event_id );

		// Construct a new model. The kv-cache still holds the OLD snapshot ("2 tickets left").
		// regenerate_caches() was never called, so the stale entry is still present.
		$model_2 = new Tickets( $event_id );
		$this->assertTrue(
			tec_kv_cache()->has( Tickets::get_cache_key( $event_id ) ),
			'The old model cache should still be present because regenerate_caches() was not called.'
		);

		$result = $model_2->to_array();

		$this->assertEmpty(
			$result['stock']['available'],
			'The stale "X tickets left" text must be cleared once the event is sold out.'
		);
		$this->assertEquals(
			'Sold Out',
			$result['stock']['sold_out'],
			'The sold-out label must be set to "Sold Out" when ticket availability drops to zero.'
		);
		$this->assertTrue(
			$model_2->sold_out(),
			'sold_out() must return true when all tickets have been sold.'
		);
	}

	/**
	 * Tests the RSVP variant of the same bug: when all RSVP spots are taken after the model
	 * cache was primed with "X spots left", the next fetch should replace the stale available
	 * text with the "Currently full" label rather than silently returning.
	 *
	 * @test
	 * @covers \Tribe\Tickets\Events\Views\V2\Models\Tickets::refresh_cached_stock_display
	 */
	public function should_show_currently_full_when_rsvp_availability_drops_to_zero_while_served_from_model_cache(): void {
		$event_id = tribe_events()->set_args( [
			'title'      => 'Test Event',
			'status'     => 'publish',
			'start_date' => '2030-01-01 09:00:00',
			'duration'   => 4 * HOUR_IN_SECONDS,
		] )->create()->ID;

		// Create one RSVP ticket with capacity 3.
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [ '_capacity' => 3 ],
		] );

		// Prime the model (kv) cache: 3 spots remain, so "3 spots left" should be stored.
		$model_1 = new Tickets( $event_id );
		$model_1->exist();
		$this->assertTrue( tec_kv_cache()->has( Tickets::get_cache_key( $event_id ) ) );

		$initial_data = $model_1->to_array();
		$this->assertNotEmpty(
			$initial_data['stock']['available'],
			'The cache should have been primed with an "X spots left" stock text.'
		);
		$this->assertEmpty(
			$initial_data['stock']['sold_out'],
			'The cache should not mark the RSVP as full yet.'
		);

		// Simulate all 3 spots being filled by updating the RSVP post meta directly.
		update_post_meta( $ticket_id, '_stock', 0 );
		update_post_meta( $ticket_id, 'total_sales', 3 );
		clean_post_cache( $ticket_id );

		// Clear the RSVP provider's tribe_cache so get_ticket_counts reads the updated values.
		tribe( 'tickets.rsvp' )->clear_ticket_cache_for_post( $event_id );

		// Construct a new model. The kv-cache still holds the OLD snapshot ("3 spots left").
		$model_2 = new Tickets( $event_id );
		$this->assertTrue(
			tec_kv_cache()->has( Tickets::get_cache_key( $event_id ) ),
			'The old model cache should still be present because regenerate_caches() was not called.'
		);

		$result = $model_2->to_array();

		$this->assertEmpty(
			$result['stock']['available'],
			'The stale "X spots left" text must be cleared once the RSVP is at capacity.'
		);
		$this->assertEquals(
			'Currently full',
			$result['stock']['sold_out'],
			'The sold-out label must be set to "Currently full" when all RSVP spots are taken.'
		);
		$this->assertTrue(
			$model_2->sold_out(),
			'sold_out() must return true when the RSVP is at capacity.'
		);
	}

	/**
	 * get_tickets() serves cached object instances keyed by get_tickets_cache_key(). Because
	 * clear_ticket_cache_for_post() now builds its key from the same helper, clearing the cache must
	 * force get_tickets() to rebuild — proving the writer and the invalidator agree on the key.
	 *
	 * @test
	 * @covers \Tribe__Tickets__Tickets::clear_ticket_cache_for_post
	 * @covers \Tribe__Tickets__Tickets::get_tickets_cache_key
	 */
	public function should_clear_the_get_tickets_cache_for_post(): void {
		$event_id = tribe_events()->set_args( [
			'title'      => 'Test Event',
			'status'     => 'publish',
			'start_date' => '2030-01-01 09:00:00',
			'duration'   => 4 * HOUR_IN_SECONDS,
		] )->create()->ID;

		$this->with_capacity( 10 );
		$this->create_tc_ticket( $event_id, 1 );

		$provider = tribe( Module::class );

		// Prime the cache, then confirm a second read serves the same cached instances.
		$first = $provider->get_tickets( $event_id );
		$this->assertCount( 1, $first, 'The event should have exactly one ticket.' );
		$this->assertSame(
			$first[0],
			$provider->get_tickets( $event_id )[0],
			'get_tickets() should serve the same cached instance until invalidated.'
		);

		// Clear via the provider method, which builds its key from the same get_tickets_cache_key() helper.
		$provider->clear_ticket_cache_for_post( $event_id );

		$this->assertNotSame(
			$first[0],
			$provider->get_tickets( $event_id )[0],
			'After clear_ticket_cache_for_post(), get_tickets() must rebuild fresh instances.'
		);
	}

	/**
	 * The cache key helper is the single source of truth for the get_tickets() cache key; this guards
	 * the format so the various invalidation sites stay in sync with the value get_tickets() stores.
	 *
	 * @test
	 * @covers \Tribe__Tickets__Tickets::get_tickets_cache_key
	 */
	public function should_build_a_stable_get_tickets_cache_key(): void {
		$this->assertSame(
			Tickets_Tickets::class . '::get_tickets-tribe-commerce-123',
			Tickets_Tickets::get_tickets_cache_key( 'tribe-commerce', 123 ),
			'The get_tickets cache key format must remain stable.'
		);
	}
}
