<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;
use Generator;
use TEC\Tickets\Commerce\Ticket;

class Rule_Store_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function should_return_an_empty_array_when_nothing_is_stored(): void {
		$ticket_id = static::factory()->post->create();

		$this->assertSame( [], tribe( Rule_Store::class )->get( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_keep_the_sale_price_key_when_saving_the_sales_window(): void {
		$ticket_id  = static::factory()->post->create();
		$sale_price = [ 'start' => [ 'mode' => 'default' ] ];
		update_post_meta( $ticket_id, Rule_Store::META_KEY, wp_json_encode( [ 'sale_price' => $sale_price ] ) );
		$window = [
			'start' => [ 'mode' => 'default' ],
			'end'   => [
				'mode'   => 'relative',
				'value'  => 1,
				'unit'   => DAY_IN_SECONDS,
				'anchor' => 'start',
			],
		];

		tribe( Rule_Store::class )->save( $ticket_id, $window );

		$this->assertSame( array_merge( [ 'sale_price' => $sale_price ], $window ), tribe( Rule_Store::class )->get( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_keep_the_sale_price_key_when_removing_the_sales_window(): void {
		$ticket_id  = static::factory()->post->create();
		$sale_price = [ 'start' => [ 'mode' => 'default' ] ];
		update_post_meta(
			$ticket_id,
			Rule_Store::META_KEY,
			wp_json_encode(
				[
					'sale_price' => $sale_price,
					'start'      => [ 'mode' => 'default' ],
					'end'        => [ 'mode' => 'default' ],
				]
			)
		);

		tribe( Rule_Store::class )->remove( $ticket_id, [ 'start', 'end' ] );

		$this->assertSame( [ 'sale_price' => $sale_price ], tribe( Rule_Store::class )->get( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_delete_the_meta_when_removing_the_last_keys(): void {
		$ticket_id = static::factory()->post->create();
		update_post_meta(
			$ticket_id,
			Rule_Store::META_KEY,
			wp_json_encode(
				[
					'start' => [ 'mode' => 'default' ],
					'end'   => [ 'mode' => 'default' ],
				]
			)
		);

		tribe( Rule_Store::class )->remove( $ticket_id, [ 'start', 'end' ] );

		$this->assertFalse( metadata_exists( 'post', $ticket_id, Rule_Store::META_KEY ) );
	}

	/**
	 * @test
	 */
	public function should_release_the_end_marker_when_removing_the_sales_window_without_an_end(): void {
		$ticket_id       = static::factory()->post->create();
		$tickets_handler = tribe( 'tickets.handler' );
		tribe( Rule_Store::class )->save( $ticket_id, [ 'start' => [ 'mode' => 'default' ], 'end' => [ 'mode' => 'default' ] ] );
		add_post_meta( $ticket_id, $tickets_handler->key_manual_updated, $tickets_handler->key_end_date );

		tribe( Rule_Store::class )->remove_sales_window( $ticket_id, false );

		$this->assertSame( [], tribe( Rule_Store::class )->get( $ticket_id ) );
		$this->assertFalse( $tickets_handler->has_manual_update( $ticket_id, $tickets_handler->key_end_date ) );
	}

	/**
	 * @test
	 */
	public function should_keep_the_end_marker_when_removing_the_sales_window_with_an_end(): void {
		$ticket_id       = static::factory()->post->create();
		$tickets_handler = tribe( 'tickets.handler' );
		tribe( Rule_Store::class )->save( $ticket_id, [ 'start' => [ 'mode' => 'default' ], 'end' => [ 'mode' => 'default' ] ] );
		add_post_meta( $ticket_id, $tickets_handler->key_manual_updated, $tickets_handler->key_end_date );

		tribe( Rule_Store::class )->remove_sales_window( $ticket_id, true );

		$this->assertSame( [], tribe( Rule_Store::class )->get( $ticket_id ) );
		$this->assertTrue( $tickets_handler->has_manual_update( $ticket_id, $tickets_handler->key_end_date ) );
	}

	/**
	 * @test
	 */
	public function should_keep_the_end_marker_of_a_ticket_without_a_sales_window(): void {
		$ticket_id       = static::factory()->post->create();
		$tickets_handler = tribe( 'tickets.handler' );
		add_post_meta( $ticket_id, $tickets_handler->key_manual_updated, $tickets_handler->key_end_date );

		tribe( Rule_Store::class )->remove_sales_window( $ticket_id, false );

		$this->assertTrue( $tickets_handler->has_manual_update( $ticket_id, $tickets_handler->key_end_date ) );
	}

	/**
	 * @return Generator<string,array{0: array{mode: string, value?: int, unit?: int, anchor?: string}|null, 1: array{mode: string, value?: int, unit?: int, anchor?: string}, 2: bool}>
	 */
	public function sales_window_end_switch_provider(): Generator {
		$relative = [
			'mode'   => 'relative',
			'value'  => 1,
			'unit'   => DAY_IN_SECONDS,
			'anchor' => 'start',
		];

		yield 'relative end switched to specific' => [ $relative, [ 'mode' => 'specific' ], true ];
		yield 'default end switched to specific' => [ [ 'mode' => 'default' ], [ 'mode' => 'specific' ], true ];
		// The legacy save flags an end the admin types on a new ticket, when it is not the event start.
		yield 'first rule with a specific end' => [ null, [ 'mode' => 'specific' ], false ];
		yield 'specific end kept' => [ [ 'mode' => 'specific' ], [ 'mode' => 'specific' ], false ];
		yield 'specific end switched to relative' => [ [ 'mode' => 'specific' ], $relative, false ];
	}

	/**
	 * @test
	 * @dataProvider sales_window_end_switch_provider
	 */
	public function should_flag_an_end_switched_to_specific_as_set_by_hand( ?array $previous_end, array $end, bool $flagged ): void {
		$ticket_id       = static::factory()->post->create();
		$tickets_handler = tribe( 'tickets.handler' );
		$start           = [ 'mode' => 'default' ];
		if ( $previous_end ) {
			tribe( Rule_Store::class )->save( $ticket_id, [ 'start' => $start, 'end' => $previous_end ] );
		}

		tribe( Rule_Store::class )->save_sales_window( $ticket_id, Rule::from_array( [ 'start' => $start, 'end' => $end ] ) );

		$this->assertSame( [ 'start' => $start, 'end' => $end ], tribe( Rule_Store::class )->get( $ticket_id ) );
		$this->assertSame( $flagged, $tickets_handler->has_manual_update( $ticket_id, $tickets_handler->key_end_date ) );
	}

	/**
	 * @test
	 */
	public function should_not_flag_an_end_already_set_by_hand_twice(): void {
		$ticket_id       = static::factory()->post->create();
		$tickets_handler = tribe( 'tickets.handler' );
		tribe( Rule_Store::class )->save( $ticket_id, [ 'start' => [ 'mode' => 'default' ], 'end' => [ 'mode' => 'default' ] ] );
		add_post_meta( $ticket_id, $tickets_handler->key_manual_updated, $tickets_handler->key_end_date );

		tribe( Rule_Store::class )->save_sales_window( $ticket_id, Rule::from_array( [ 'start' => [ 'mode' => 'default' ], 'end' => [ 'mode' => 'specific' ] ] ) );

		$this->assertSame( [ $tickets_handler->key_end_date ], get_post_meta( $ticket_id, $tickets_handler->key_manual_updated ) );
	}

	/**
	 * @test
	 */
	public function should_get_the_tickets_of_an_event_that_have_stored_rules(): void {
		$event_id        = static::factory()->post->create();
		$ruled_ticket_id = static::factory()->post->create( [ 'post_type' => Ticket::POSTTYPE ] );
		$plain_ticket_id = static::factory()->post->create( [ 'post_type' => Ticket::POSTTYPE ] );
		$other_ticket_id = static::factory()->post->create( [ 'post_type' => Ticket::POSTTYPE ] );
		$draft_ticket_id = static::factory()->post->create(
			[
				'post_type'   => Ticket::POSTTYPE,
				'post_status' => 'draft',
			]
		);
		update_post_meta( $ruled_ticket_id, Ticket::$event_relation_meta_key, $event_id );
		update_post_meta( $plain_ticket_id, Ticket::$event_relation_meta_key, $event_id );
		update_post_meta( $draft_ticket_id, Ticket::$event_relation_meta_key, $event_id );
		update_post_meta( $other_ticket_id, Ticket::$event_relation_meta_key, static::factory()->post->create() );
		tribe( Rule_Store::class )->save( $ruled_ticket_id, [ 'start' => [ 'mode' => 'default' ] ] );
		tribe( Rule_Store::class )->save( $draft_ticket_id, [ 'start' => [ 'mode' => 'default' ] ] );
		tribe( Rule_Store::class )->save( $other_ticket_id, [ 'start' => [ 'mode' => 'default' ] ] );

		$ticket_ids = tribe( Rule_Store::class )->get_ticket_ids_for_event( $event_id );

		sort( $ticket_ids );
		$this->assertSame( [ $ruled_ticket_id, $draft_ticket_id ], $ticket_ids );
	}

	/**
	 * Without Tickets Commerce its ticket functions are never loaded, yet The Events Calendar still saves occurrences.
	 *
	 * @test
	 */
	public function should_find_no_tickets_while_tickets_commerce_is_off(): void {
		$event_id  = static::factory()->post->create();
		$ticket_id = static::factory()->post->create( [ 'post_type' => Ticket::POSTTYPE ] );
		update_post_meta( $ticket_id, Ticket::$event_relation_meta_key, $event_id );
		tribe( Rule_Store::class )->save( $ticket_id, [ 'start' => [ 'mode' => 'default' ] ] );
		// After the suite's own filter, which turns Tickets Commerce on for every test.
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_false', PHP_INT_MAX );

		$this->assertSame( [], tribe( Rule_Store::class )->get_ticket_ids_for_event( $event_id ) );
	}

	/**
	 * @test
	 */
	public function should_return_at_most_the_query_limit_of_ruled_tickets(): void {
		$event_id = static::factory()->post->create();
		// One more than the limit, so an unbounded query would return them all.
		$ticket_ids = static::factory()->post->create_many( Rule_Store::TICKETS_QUERY_LIMIT + 1, [ 'post_type' => Ticket::POSTTYPE ] );
		foreach ( $ticket_ids as $ticket_id ) {
			update_post_meta( $ticket_id, Ticket::$event_relation_meta_key, $event_id );
			update_post_meta( $ticket_id, Rule_Store::META_KEY, wp_json_encode( [ 'start' => [ 'mode' => 'default' ] ] ) );
		}

		$this->assertCount( Rule_Store::TICKETS_QUERY_LIMIT, tribe( Rule_Store::class )->get_ticket_ids_for_event( $event_id ) );
	}
}
