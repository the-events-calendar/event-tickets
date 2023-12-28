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
	 * It should allow getting the shared capacity of tickets
	 *
	 * @test
	 */
	public function should_allow_getting_the_shared_capacity_of_tickets(): void {
		$post_1 = static::factory()->post->create();
		update_post_meta( $post_1, Global_Stock::GLOBAL_STOCK_LEVEL, 117 );

		$global_ticket    = $this->create_tc_ticket( $post_1, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::GLOBAL_STOCK_MODE,
				'capacity' => 117,
			],
		] );
		$capped_ticket    = $this->create_tc_ticket( $post_1, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::CAPPED_STOCK_MODE,
				'capacity' => 47,
			],
		] );
		$capped_ticket_2  = $this->create_tc_ticket( $post_1, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::CAPPED_STOCK_MODE,
				'capacity' => 89,
			],
		] );
		$unlimited_ticket = $this->create_tc_ticket( $post_1, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => - 1,
			],
		] );
		$own_ticket       = $this->create_tc_ticket( $post_1, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => 17,
			],
		] );
		$own_ticket_2     = $this->create_tc_ticket( $post_1, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => 41,
			],
		] );

		$this->assertEquals( -1, tribe_get_event_capacity( $post_1 ) );
		$this->assertEquals(
			17 + 41,
			tribe_tickets()->where( 'event', $post_1 )->get_independent_capacity()
		);
		$this->assertEquals(
			117,
			tribe_tickets()->where( 'event', $post_1 )->get_shared_capacity()
		);
	}
}
