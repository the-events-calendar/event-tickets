<?php

namespace Tribe\Tickets\Events\Views\V2\Models;

use lucatume\WPBrowser\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
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
		$order = $this->create_order( [ $ticket_1_id => 3, $ticket_2_id => 3, $ticket_3_id => 3 ] );

		$model_1 = new Tickets( $event_id );

		$this->assertFalse( tec_kv_cache()->has( $model_1->get_cache_key() ) );

		// This call will trigger the cache creation.
		$this->assertTrue( $model_1->exist() );

		$this->assertTrue( tec_kv_cache()->has( $model_1->get_cache_key() ) );

		$queries           = [];
		$intercept_queries = static function ( string $query ) use ( &$queries ): string {
			$queries[] = $query;

			return $query;
		};

		add_filter( 'query', $intercept_queries );
		$model_2 = new Tickets( $event_id );
		remove_filter( 'query', $intercept_queries );

		$this->assertTrue( tec_kv_cache()->has( $model_1->get_cache_key() ) );
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
}
