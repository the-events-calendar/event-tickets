<?php

namespace TEC\Tickets\Commerce\Repositories;

use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Global_Stock as Global_Stock;

class Ticket_RepositoryTest extends \Codeception\TestCase\WPTestCase {
	use Ticket_Maker;

	/**
	 * @before
	 */
	public function ensure_post_ticketable(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	/**
	 * It should allow filtering the event ID when filtering tickets by event
	 *
	 * @test
	 */
	public function should_allow_filtering_the_event_id_when_filtering_tickets_by_event(): void {
		$post_1_id   = static::factory()->post->create();
		$post_2_id   = static::factory()->post->create();
		$ticket_1_id = $this->create_tc_ticket( $post_1_id );
		$ticket_2_id = $this->create_tc_ticket( $post_2_id );

		$this->assertEquals(
			[ $ticket_1_id ],
			tribe_tickets()->where( 'event', $post_1_id )->get_ids()
		);
		$this->assertEquals(
			[ $ticket_2_id ],
			tribe_tickets()->where( 'event', $post_2_id )->get_ids()
		);

		// Filter to change a query to fetch the Tickets for Event 1 to fetch the Tickets for Event 2 instead.
		add_filter(
			'tec_tickets_repository_filter_by_event_id',
			function () use ( $post_2_id ) {
				return $post_2_id;
			} );

		$this->assertEquals(
			[ $ticket_2_id ],
			tribe_tickets()->where( 'event', $post_1_id )->get_ids()
		);

		// Filter to change a query to fetch the Tickets for Event 2 to fetch the Tickets for Event 1 instead.
		add_filter(
			'tec_tickets_repository_filter_by_event_id',
			function () use ( $post_1_id ) {
				return $post_1_id;
			}, 20 );

		$this->assertEquals(
			[ $ticket_1_id ],
			tribe_tickets()->where( 'event', $post_2_id )->get_ids()
		);
	}

	/**
	 * It should allow filtering the event ID by returning multiple when filtering tickets by event
	 *
	 * @test
	 */
	public function should_allow_filtering_the_event_id_by_returning_multiple_when_filtering_tickets_by_event(): void {
		$post_1_id   = static::factory()->post->create();
		$post_2_id   = static::factory()->post->create();
		$post_3_id   = static::factory()->post->create();
		$ticket_1_id = $this->create_tc_ticket( $post_1_id );
		$ticket_2_id = $this->create_tc_ticket( $post_2_id );
		$ticket_3_id = $this->create_tc_ticket( $post_3_id );

		$this->assertEquals(
			[ $ticket_1_id ],
			tribe_tickets()->where( 'event', $post_1_id )->get_ids()
		);
		$this->assertEquals(
			[ $ticket_2_id ],
			tribe_tickets()->where( 'event', $post_2_id )->get_ids()
		);
		$this->assertEquals(
			[ $ticket_3_id ],
			tribe_tickets()->where( 'event', $post_3_id )->get_ids()
		);

		add_filter(
			'tec_tickets_repository_filter_by_event_id',
			function () use ( $post_3_id, $post_2_id ) {
				return [ $post_2_id, $post_3_id ];
			} );

		$this->assertEquals(
			[ $ticket_2_id, $ticket_3_id ],
			tribe_tickets()->where( 'event', $post_1_id )->get_ids()
		);
	}

	/**
	 * It should allow getting the capacity of tickets
	 *
	 * @test
	 */
	public function should_allow_getting_the_capacity_of_tickets(): void {
		$post = static::factory()->post->create();

		$this->assertEquals( 0, tribe_get_event_capacity( $post ) );
		$this->assertEquals( 0, tribe_tickets()->where( 'event', $post )->get_independent_capacity() );
		$this->assertEquals( 0, tribe_tickets()->where( 'event', $post )->get_shared_capacity() );

		update_post_meta( $post, Global_Stock::GLOBAL_STOCK_LEVEL, 117 );

		$global_ticket   = $this->create_tc_ticket( $post, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::GLOBAL_STOCK_MODE,
				'capacity' => 117,
			],
		] );
		$capped_ticket   = $this->create_tc_ticket( $post, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::CAPPED_STOCK_MODE,
				'capacity' => 47,
			],
		] );
		$capped_ticket_2 = $this->create_tc_ticket( $post, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::CAPPED_STOCK_MODE,
				'capacity' => 89,
			],
		] );

		$this->assertEquals( 117, tribe_get_event_capacity( $post ) );
		$this->assertEquals( 117, tribe_tickets()->where( 'event', $post )->get_shared_capacity() );
		$this->assertEquals( 0, tribe_tickets()->where( 'event', $post )->get_independent_capacity() );

		$unlimited_ticket = $this->create_tc_ticket( $post, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => - 1,
			],
		] );
		$own_ticket       = $this->create_tc_ticket( $post, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => 17,
			],
		] );
		$own_ticket_2     = $this->create_tc_ticket( $post, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => 41,
			],
		] );

		$this->assertEquals( - 1, tribe_get_event_capacity( $post ) );
		$this->assertEquals(
			117,
			tribe_tickets()->where( 'event', $post )->get_shared_capacity()
		);
		$this->assertEquals(
			17 + 41,
			tribe_tickets()->where( 'event', $post )->get_independent_capacity()
		);
	}

	/**
	 * It should allow fetching tickets by global stock mode
	 *
	 * @test
	 */
	public function should_allow_fetching_tickets_by_global_stock_mode(): void {
		$post = static::factory()->post->create();

		$this->assertEquals( 0,
			tribe_tickets()
				->where( 'event', $post )
				->where( 'global_stock_mode', Global_Stock::OWN_STOCK_MODE, $exclude_unimited = true )
				->count() );
		$this->assertEquals( 0,
			tribe_tickets()
				->where( 'event', $post )
				->where( 'global_stock_mode', Global_Stock::OWN_STOCK_MODE, $exclude_unlimited = false )
				->count() );
		$this->assertEquals( 0,
			tribe_tickets()
				->where( 'event', $post )
				->where( 'global_stock_mode', [
					Global_Stock::GLOBAL_STOCK_MODE,
					Global_Stock::CAPPED_STOCK_MODE
				] )->count() );
		$this->assertEquals( 0,
			tribe_tickets()
				->where( 'event', $post )
				->where( 'global_stock_mode', Global_Stock::UNLIMITED_STOCK_MODE )->count()
		);

		// Set the Event to have a global stock level.
		update_post_meta( $post, Global_Stock::GLOBAL_STOCK_LEVEL, 117 );

		$global_ticket   = $this->create_tc_ticket( $post, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::GLOBAL_STOCK_MODE,
				'capacity' => 117,
			],
		] );
		$capped_ticket   = $this->create_tc_ticket( $post, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::CAPPED_STOCK_MODE,
				'capacity' => 47,
			],
		] );
		$capped_ticket_2 = $this->create_tc_ticket( $post, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::CAPPED_STOCK_MODE,
				'capacity' => 89,
			],
		] );

		$this->assertEquals( 0,
			tribe_tickets()
				->where( 'event', $post )
				->where( 'global_stock_mode', Global_Stock::OWN_STOCK_MODE, $exclude_unlimited = true )
				->count() );
		$this->assertEquals( 0,
			tribe_tickets()
				->where( 'event', $post )
				->where( 'global_stock_mode', Global_Stock::OWN_STOCK_MODE, $exclude_unlimited = false )
				->count() );
		$this->assertEquals( 3,
			tribe_tickets()
				->where( 'event', $post )
				->where( 'global_stock_mode', [
					Global_Stock::GLOBAL_STOCK_MODE,
					Global_Stock::CAPPED_STOCK_MODE
				] )->count() );
		$this->assertEquals( 0,
			tribe_tickets()
				->where( 'event', $post )
				->where( 'global_stock_mode', Global_Stock::UNLIMITED_STOCK_MODE )->count()
		);

		$unlimited_ticket = $this->create_tc_ticket( $post, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => - 1,
			],
		] );
		$own_ticket       = $this->create_tc_ticket( $post, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => 17,
			],
		] );
		$own_ticket_2     = $this->create_tc_ticket( $post, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => 41,
			],
		] );

		$this->assertEquals( 2,
			tribe_tickets()
				->where( 'event', $post )
				->where( 'global_stock_mode', Global_Stock::OWN_STOCK_MODE, $exclude_unlimited = true )
				->count() );
		$this->assertEquals( 3,
			tribe_tickets()
				->where( 'event', $post )
				->where( 'global_stock_mode', Global_Stock::OWN_STOCK_MODE, $exclude_unlimited = false )
				->count() );
		$this->assertEquals( 3,
			tribe_tickets()
				->where( 'event', $post )
				->where( 'global_stock_mode', [
					Global_Stock::GLOBAL_STOCK_MODE,
					Global_Stock::CAPPED_STOCK_MODE
				] )->count() );
		$this->assertEquals( 1,
			tribe_tickets()
				->where( 'event', $post )
				->where( 'global_stock_mode', Global_Stock::UNLIMITED_STOCK_MODE )->count()
		);
	}
	
	/**
	 * It should return empty if event ID is 0.
	 *
	 * @test
	 */
	public function should_return_empty_if_event_id_is_0(): void {
		$this->assertEmpty( tribe_tickets()->where( 'event', 0 )->get_ids() );
		$this->assertEquals( 0, tribe_tickets()->where( 'event', 0 )->count() );
		
		// Test with an array of event IDs.
		$this->assertEmpty( tribe_tickets()->where( 'event', [ 0 ] )->get_ids() );
		$this->assertEquals( 0, tribe_tickets()->where( 'event', [ 0 ] )->count() );
	}
}
