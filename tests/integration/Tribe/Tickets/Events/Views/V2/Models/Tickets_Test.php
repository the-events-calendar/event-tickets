<?php

namespace Tribe\Tickets\Events\Views\V2\Models;

use lucatume\WPBrowser\TestCase\WPTestCase;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Events\Views\V2\Hooks;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

class Tickets_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;

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
		$warm            = function () use ( $event_cache_key, $event_id ) {
			( new Tickets( $event_id ) )->exist();
			$this->assertTrue( tec_kv_cache()->has( $event_cache_key ) );
		};

		// Post: an unrelated post type leaves the Event entry alone.
		$warm();
		Tickets::regenerate_caches( $post_id );
		$this->assertFalse( tec_kv_cache()->has( Tickets::get_cache_key( $post_id ) ) );
		$this->assertTrue( tec_kv_cache()->has( $event_cache_key ) );

		// Event.
		Tickets::regenerate_caches( $event_id );
		$this->assertFalse( tec_kv_cache()->has( $event_cache_key ) );

		// Attendee.
		$warm();
		$attendee_id = tribe_attendees()->where( 'event', $event_id )->fields( 'ids' )->first();
		Tickets::regenerate_caches( $attendee_id );
		$this->assertFalse( tec_kv_cache()->has( Tickets::get_cache_key( $attendee_id ) ) );
		$this->assertFalse( tec_kv_cache()->has( $event_cache_key ) );

		// Ticket.
		$warm();
		$ticket_id = tribe_tickets()->where( 'event', $event_id )->fields( 'ids' )->first();
		Tickets::regenerate_caches( $ticket_id );
		$this->assertFalse( tec_kv_cache()->has( Tickets::get_cache_key( $ticket_id ) ) );
		$this->assertFalse( tec_kv_cache()->has( $event_cache_key ) );
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
		Tickets::regenerate_caches( $event_id );
		$this->assertFalse( tec_kv_cache()->has( $event_cache_key ) );

		$array_model = ( new Tickets( $event_id ) )->to_array();

		$this->assertEquals( [
			'link'  =>
				[
					'anchor'             => 'http://wordpress.test/?tribe_events=test-event#tribe-tickets__tickets-form',
					'label'              => 'Get Tickets',
				],
			'stock' =>
				[
					'available'          => '291 tickets left',
					'sold_out'           => '',
				],
		], $array_model );
	}

	/**
	 * @test
	 * @covers \Tribe\Tickets\Events\Views\V2\Models\Tickets::regenerate_caches
	 */
	public function should_report_sold_out_after_the_last_available_ticket_is_purchased(): void {
		$event_id = tribe_events()->set_args( [
			'title'      => 'Test Event',
			'status'     => 'publish',
			'start_date' => '2023-01-01 09:00:00',
			'duration'   => 4 * HOUR_IN_SECONDS,
		] )->create()->ID;
		$capacity  = 5;
		$ticket_id = $this->with_capacity( $capacity )->create_tc_ticket( $event_id );

		// Warm the model cache the way a calendar view would, while the ticket is still on sale.
		$on_sale_stock = ( new Tickets( $event_id ) )['stock'];

		$this->assertEquals( "{$capacity} tickets left", $on_sale_stock->available );
		$this->assertEquals( '', $on_sale_stock->sold_out );

		/*
		 * The Views V2 hooks do not boot in this suite, so register the production callback: the order
		 * then fires it on each Attendee insert, which Commerce does before it writes the Ticket stock.
		 */
		add_action( 'wp_insert_post', [ tribe( Hooks::class ), 'regenerate_post_kv_caches' ], 100 );

		$this->create_order( [ $ticket_id => $capacity ] );

		$sold_out_model = new Tickets( $event_id );
		$sold_out_stock = $sold_out_model['stock'];

		$this->assertTrue( $sold_out_model->sold_out() );
		$this->assertEquals( 'Sold Out', $sold_out_stock->sold_out );
		$this->assertEquals( '', $sold_out_stock->available );
	}

	/**
	 * @test
	 * @covers \Tribe\Tickets\Events\Views\V2\Models\Tickets::get_cache_key
	 */
	public function should_key_the_cache_on_the_normalized_event_id(): void {
		$event_id = tribe_events()->set_args( [
			'title'      => 'Test Event',
			'status'     => 'publish',
			'start_date' => '2023-01-01 09:00:00',
			'duration'   => 4 * HOUR_IN_SECONDS,
		] )->create()->ID;

		/* The key only normalizes when Custom Tables is already loaded, as it is on a front-end request. */
		$this->assertTrue( class_exists( Occurrence::class ) );

		/* Custom Tables hands out no provisional IDs in this suite, so stand in for its normalization. */
		$provisional_id = static::factory()->post->create();
		add_filter( 'tec_tickets_normalize_occurrence_id', static fn() => $event_id );

		$this->assertEquals( Tickets::get_cache_key( $event_id ), Tickets::get_cache_key( $provisional_id ) );
	}
}
