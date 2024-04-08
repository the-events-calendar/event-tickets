<?php

namespace Tribe\Tickets;


use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker;
use Tribe__Tickets__Tickets;
use \Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker as TicketsCommerce_Ticket_Maker;

class Ticket_RepositoryTest extends \Codeception\TestCase\WPTestCase {
	use Ticket_Maker;
	use TicketsCommerce_Ticket_Maker;

	/**
	 * It should allow filtering the event ID when filtering tickets by event
	 *
	 * @test
	 */
	public function should_allow_filtering_the_event_id_when_filtering_tickets_by_event(): void {
		$post_1_id   = static::factory()->post->create();
		$post_2_id   = static::factory()->post->create();
		$ticket_1_id = $this->create_paypal_ticket( $post_1_id );
		$ticket_2_id = $this->create_paypal_ticket( $post_2_id );

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
		$ticket_1_id = $this->create_paypal_ticket( $post_1_id );
		$ticket_2_id = $this->create_paypal_ticket( $post_2_id );
		$ticket_3_id = $this->create_paypal_ticket( $post_3_id );

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
	 * It should return empty if event ID is 0.
	 *
	 * @test
	 */
	public function should_return_empty_if_event_id_is_0(): void {
		
		$post_3_id   = static::factory()->post->create();
		$post_4_id   = static::factory()->post->create();
		$ticket_3_id = $this->create_tc_ticket( $post_3_id );
		$ticket_4_id = $this->create_tc_ticket( $post_4_id );
		
		// Test for TicketsCommerce tickets.
		$this->assertEmpty( tec_tc_tickets()->where( 'event', 0 )->get_ids() );
		$this->assertEquals( 0, tec_tc_tickets()->where( 'event', 0 )->count() );
		
		// Test with an array of event IDs for TicketsCommeerce tickets.
		$this->assertEmpty( tec_tc_tickets()->where( 'event', [ 0 ] )->get_ids() );
		$this->assertEquals( 0, tec_tc_tickets()->where( 'event', [ 0 ] )->count() );
		
		$this->assertNotEmpty( tec_tc_tickets()->where( 'event', [ $post_3_id ] )->get_ids() );
		$this->assertNotEmpty( tec_tc_tickets()->where( 'event', [ $post_4_id ] )->get_ids() );
		$this->assertEquals( 1, tec_tc_tickets()->where( 'event', [ $post_3_id ] )->count() );
		$this->assertEquals( 1, tec_tc_tickets()->where( 'event', [ $post_4_id ] )->count() );
		
		$this->assertEmpty( tribe_tickets()->where( 'event', 0 )->get_ids() );
		$this->assertEquals( 0, tribe_tickets()->where( 'event', 0 )->count() );
		
		// Test with an array of event IDs.
		$this->assertEmpty( tribe_tickets()->where( 'event', [ 0 ] )->get_ids() );
		$this->assertEquals( 0, tribe_tickets()->where( 'event', [ 0 ] )->count() );
	}
}
