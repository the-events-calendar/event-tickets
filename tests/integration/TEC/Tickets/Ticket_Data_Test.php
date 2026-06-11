<?php

namespace TEC\Tickets;

use lucatume\WPBrowser\TestCase\WPTestCase as WPBrowserTestCase;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Closure;
use Generator;

class Ticket_Data_Test extends WPBrowserTestCase {
	use Ticket_Maker;
	use Order_Maker;
	use RSVP_Ticket_Maker;
	use With_Tickets_Commerce;

	/**
	 * @test
	 */
	public function it_should_get_sync_able_tickets_of_event(): void {
		$post = self::factory()->post->create();
		$ticket_id_1 = $this->create_on_sale_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_about_to_be_on_sale_tc_ticket( $post, 20 );
		$ticket_id_3 = $this->create_pre_sale_tc_ticket( $post, 30 );
		$ticket_id_4 = $this->create_after_sales_tc_ticket( $post, 40 );

		$ticket_data = tribe( Ticket_Data::class );

		$tickets = $ticket_data->get_sync_able_tickets_of_event( $post );

		$ticket_ids = array_map( fn( $ticket ) => $ticket->ID, $tickets );

		$this->assertCount( 3, $tickets );
		$this->assertContains( $ticket_id_1, $ticket_ids );
		$this->assertContains( $ticket_id_2, $ticket_ids );
		$this->assertNotContains( $ticket_id_3, $ticket_ids );
		$this->assertContains( $ticket_id_4, $ticket_ids );
	}

	/**
	 * @test
	 * @dataProvider load_ticket_object_data_provider
	 */
	public function it_should_load_ticket_object( Closure $fixture ): void {
		[ $ticket_id, $should_load ] = $fixture();

		$ticket_data = tribe( Ticket_Data::class );
		$result      = $ticket_data->load_ticket_object( $ticket_id );

		if ( $should_load ) {
			$this->assertInstanceOf( Ticket_Object::class, $result );
			$this->assertEquals( $ticket_id, $result->ID );
		} else {
			$this->assertNull( $result );
		}
	}

	public function load_ticket_object_data_provider(): Generator {
		yield 'valid TC ticket loads successfully' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				return [ $ticket_id, true ];
			},
		];

		yield 'non-existent ticket returns null' => [
			function (): array {
				return [ PHP_INT_MAX, false ];
			},
		];

		yield 'valid RSVP ticket loads successfully' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_rsvp_ticket( $post_id );

				return [ $ticket_id, true ];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider get_posts_tickets_data_provider
	 */
	public function it_should_get_posts_tickets( Closure $fixture ): void {
		[ $post_id, $expected_ticket_ids ] = $fixture();

		$ticket_data = tribe( Ticket_Data::class );
		$tickets     = iterator_to_array( $ticket_data->get_posts_tickets( $post_id ) );
		$ticket_ids  = array_map( fn( Ticket_Object $ticket ) => $ticket->ID, $tickets );

		$this->assertCount( count( $expected_ticket_ids ), $tickets );

		foreach ( $expected_ticket_ids as $expected_id ) {
			$this->assertContains( $expected_id, $ticket_ids );
		}
	}

	public function get_posts_tickets_data_provider(): Generator {
		yield 'post with multiple TC tickets returns all' => [
			function (): array {
				$post_id    = static::factory()->post->create();
				$ticket_id1 = $this->create_on_sale_tc_ticket( $post_id, 10 );
				$ticket_id2 = $this->create_on_sale_tc_ticket( $post_id, 20 );

				return [ $post_id, [ $ticket_id1, $ticket_id2 ] ];
			},
		];

		yield 'post without tickets returns empty' => [
			function (): array {
				$post_id = static::factory()->post->create();

				return [ $post_id, [] ];
			},
		];

		yield 'RSVP tickets are excluded' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$tc_ticket = $this->create_on_sale_tc_ticket( $post_id, 10 );
				$this->create_rsvp_ticket( $post_id );

				return [ $post_id, [ $tc_ticket ] ];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider get_posts_rsvp_data_provider
	 */
	public function it_should_get_posts_rsvp( Closure $fixture ): void {
		[ $post_id, $expected_rsvp_id ] = $fixture();

		$ticket_data = tribe( Ticket_Data::class );
		$rsvp        = $ticket_data->get_posts_rsvp( $post_id );

		if ( $expected_rsvp_id ) {
			$this->assertInstanceOf( Ticket_Object::class, $rsvp );
			$this->assertEquals( $expected_rsvp_id, $rsvp->ID );
		} else {
			$this->assertNull( $rsvp );
		}
	}

	public function get_posts_rsvp_data_provider(): Generator {
		yield 'post with RSVP returns it' => [
			function (): array {
				$post_id = static::factory()->post->create();
				$rsvp_id = $this->create_rsvp_ticket( $post_id );

				return [ $post_id, $rsvp_id ];
			},
		];

		yield 'post without RSVP returns null' => [
			function (): array {
				$post_id = static::factory()->post->create();

				return [ $post_id, null ];
			},
		];

		yield 'post with only TC ticket returns null' => [
			function (): array {
				$post_id = static::factory()->post->create();
				$this->create_on_sale_tc_ticket( $post_id, 10 );

				return [ $post_id, null ];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider get_posts_tickets_data_stats_provider
	 */
	public function it_should_get_posts_tickets_data( Closure $fixture ): void {
		[ $post_id, $expected ] = $fixture();

		$ticket_data = tribe( Ticket_Data::class );
		$data        = $ticket_data->get_posts_tickets_data( $post_id );

		$this->assertSame( $expected['ticket_count'], $data['ticket_count'] );
		$this->assertCount( count( $expected['tickets_on_sale'] ), $data['tickets_on_sale'] );
		$this->assertCount( count( $expected['tickets_have_not_started_sales'] ), $data['tickets_have_not_started_sales'] );
		$this->assertCount( count( $expected['tickets_have_ended_sales'] ), $data['tickets_have_ended_sales'] );
		$this->assertCount( count( $expected['tickets_about_to_go_to_sale'] ), $data['tickets_about_to_go_to_sale'] );

		foreach ( $expected['tickets_on_sale'] as $id ) {
			$this->assertContains( $id, $data['tickets_on_sale'] );
		}

		foreach ( $expected['tickets_have_ended_sales'] as $id ) {
			$this->assertContains( $id, $data['tickets_have_ended_sales'] );
		}

		foreach ( $expected['tickets_about_to_go_to_sale'] as $id ) {
			$this->assertContains( $id, $data['tickets_about_to_go_to_sale'] );
		}

		foreach ( $expected['tickets_have_not_started_sales'] as $id ) {
			$this->assertContains( $id, $data['tickets_have_not_started_sales'] );
		}
	}

	public function get_posts_tickets_data_stats_provider(): Generator {
		yield 'post with no tickets returns zeroed data' => [
			function (): array {
				$post_id = static::factory()->post->create();

				return [
					$post_id,
					[
						'ticket_count'                   => 0,
						'tickets_on_sale'                => [],
						'tickets_have_not_started_sales' => [],
						'tickets_have_ended_sales'       => [],
						'tickets_about_to_go_to_sale'    => [],
					],
				];
			},
		];

		yield 'post with mixed ticket states returns correct stats' => [
			function (): array {
				$post_id     = static::factory()->post->create();
				$on_sale     = $this->create_on_sale_tc_ticket( $post_id, 10 );
				$about_to    = $this->create_about_to_be_on_sale_tc_ticket( $post_id, 20 );
				$pre_sale    = $this->create_pre_sale_tc_ticket( $post_id, 30 );
				$after_sales = $this->create_after_sales_tc_ticket( $post_id, 40 );

				return [
					$post_id,
					[
						'ticket_count'                   => 4,
						'tickets_on_sale'                => [ $on_sale ],
						'tickets_have_not_started_sales' => [ $about_to, $pre_sale ],
						'tickets_have_ended_sales'       => [ $after_sales ],
						'tickets_about_to_go_to_sale'    => [ $about_to ],
					],
				];
			},
		];

		yield 'post with only on-sale tickets' => [
			function (): array {
				$post_id  = static::factory()->post->create();
				$ticket_1 = $this->create_on_sale_tc_ticket( $post_id, 10 );
				$ticket_2 = $this->create_on_sale_tc_ticket( $post_id, 20 );

				return [
					$post_id,
					[
						'ticket_count'                   => 2,
						'tickets_on_sale'                => [ $ticket_1, $ticket_2 ],
						'tickets_have_not_started_sales' => [],
						'tickets_have_ended_sales'       => [],
						'tickets_about_to_go_to_sale'    => [],
					],
				];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider get_posts_rsvps_data_provider
	 */
	public function it_should_get_posts_rsvps_data( Closure $fixture ): void {
		[ $post_id, $expected_count ] = $fixture();

		$ticket_data = tribe( Ticket_Data::class );
		$data        = $ticket_data->get_posts_rsvps_data( $post_id );

		$this->assertSame( $expected_count, $data['ticket_count'] );
		$this->assertArrayHasKey( 'availability', $data );
		$this->assertArrayHasKey( 'tickets_on_sale', $data );
		$this->assertArrayHasKey( 'tickets_have_not_started_sales', $data );
		$this->assertArrayHasKey( 'tickets_have_ended_sales', $data );
		$this->assertArrayHasKey( 'tickets_about_to_go_to_sale', $data );
	}

	public function get_posts_rsvps_data_provider(): Generator {
		yield 'post with RSVP returns count of 1' => [
			function (): array {
				$post_id = static::factory()->post->create();
				$this->create_rsvp_ticket( $post_id );

				return [ $post_id, 1 ];
			},
		];

		yield 'post without RSVP returns count of 0' => [
			function (): array {
				$post_id = static::factory()->post->create();

				return [ $post_id, 0 ];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider get_ticket_about_to_go_to_sale_seconds_data_provider
	 */
	public function it_should_get_ticket_about_to_go_to_sale_seconds( Closure $fixture ): void {
		[ $ticket_id, $expected_seconds ] = $fixture();

		$result = Ticket_Data::get_ticket_about_to_go_to_sale_seconds( $ticket_id );

		$this->assertSame( $expected_seconds, $result );
	}

	public function get_ticket_about_to_go_to_sale_seconds_data_provider(): Generator {
		yield 'default is 20 minutes' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				return [ $ticket_id, 20 * MINUTE_IN_SECONDS ];
			},
		];

		yield 'filter overrides the default' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_on_sale_tc_ticket( $post_id, 10 );

				add_filter( 'tec_tickets_ticket_about_to_go_to_sale_seconds', fn() => 5 * MINUTE_IN_SECONDS );

				return [ $ticket_id, 5 * MINUTE_IN_SECONDS ];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider get_sync_able_tickets_of_event_data_provider
	 */
	public function it_should_get_sync_able_tickets_of_event_with_various_states( Closure $fixture ): void {
		[ $post_id, $expected_ids, $excluded_ids ] = $fixture();

		$ticket_data = tribe( Ticket_Data::class );
		$tickets     = $ticket_data->get_sync_able_tickets_of_event( $post_id );
		$ticket_ids  = array_map( fn( $ticket ) => $ticket->ID, $tickets );

		$this->assertCount( count( $expected_ids ), $tickets );

		foreach ( $expected_ids as $id ) {
			$this->assertContains( $id, $ticket_ids );
		}

		foreach ( $excluded_ids as $id ) {
			$this->assertNotContains( $id, $ticket_ids );
		}
	}

	public function get_sync_able_tickets_of_event_data_provider(): Generator {
		yield 'post with no tickets returns empty' => [
			function (): array {
				$post_id = static::factory()->post->create();

				return [ $post_id, [], [] ];
			},
		];

		yield 'only pre-sale tickets returns empty' => [
			function (): array {
				$post_id  = static::factory()->post->create();
				$pre_sale = $this->create_pre_sale_tc_ticket( $post_id, 10 );

				return [ $post_id, [], [ $pre_sale ] ];
			},
		];

		yield 'on-sale and after-sales are sync-able, pre-sale is not' => [
			function (): array {
				$post_id     = static::factory()->post->create();
				$on_sale     = $this->create_on_sale_tc_ticket( $post_id, 10 );
				$pre_sale    = $this->create_pre_sale_tc_ticket( $post_id, 20 );
				$after_sales = $this->create_after_sales_tc_ticket( $post_id, 30 );

				return [ $post_id, [ $on_sale, $after_sales ], [ $pre_sale ] ];
			},
		];

		yield 'about-to-go-on-sale tickets are sync-able' => [
			function (): array {
				$post_id  = static::factory()->post->create();
				$about_to = $this->create_about_to_be_on_sale_tc_ticket( $post_id, 10 );

				return [ $post_id, [ $about_to ], [] ];
			},
		];
	}
}
