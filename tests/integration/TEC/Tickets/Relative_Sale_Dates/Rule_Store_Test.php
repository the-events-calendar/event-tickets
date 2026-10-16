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
		$sale_price = [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ] ];
		update_post_meta( $ticket_id, Rule_Store::META_KEY, wp_json_encode( [ 'sale_price' => $sale_price ] ) );
		$window = [
			'start' => [ 'mode' => Rule::MODE_DEFAULT ],
			'end'   => [
				'mode'   => Rule::MODE_RELATIVE,
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
		$sale_price = [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ] ];
		update_post_meta(
			$ticket_id,
			Rule_Store::META_KEY,
			wp_json_encode(
				[
					'sale_price' => $sale_price,
					'start'      => [ 'mode' => Rule::MODE_DEFAULT ],
					'end'        => [ 'mode' => Rule::MODE_DEFAULT ],
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
					'start' => [ 'mode' => Rule::MODE_DEFAULT ],
					'end'   => [ 'mode' => Rule::MODE_DEFAULT ],
				]
			)
		);

		tribe( Rule_Store::class )->remove( $ticket_id, [ 'start', 'end' ] );

		$this->assertFalse( metadata_exists( 'post', $ticket_id, Rule_Store::META_KEY ) );
	}

	/**
	 * @test
	 */
	public function should_save_the_sale_price_rule_under_its_key_next_to_the_sales_window(): void {
		$ticket_id    = static::factory()->post->create();
		$sales_window = [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ], 'end' => [ 'mode' => Rule::MODE_DEFAULT ] ];
		$sale_price   = [ 'start' => [ 'mode' => Rule::MODE_NOW ], 'end' => [ 'mode' => Rule::MODE_SPECIFIC ] ];
		tribe( Rule_Store::class )->save( $ticket_id, $sales_window );

		tribe( Rule_Store::class )->save_rule( $ticket_id, Rule::from_array( $sale_price, Window_Kind::sale_price() ) );

		$this->assertSame( array_merge( $sales_window, [ 'sale_price' => $sale_price ] ), tribe( Rule_Store::class )->get( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_remove_only_the_sale_price_rule(): void {
		$ticket_id    = static::factory()->post->create();
		$sales_window = [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ], 'end' => [ 'mode' => Rule::MODE_DEFAULT ] ];
		tribe( Rule_Store::class )->save( $ticket_id, array_merge( $sales_window, [ 'sale_price' => [ 'start' => [ 'mode' => Rule::MODE_NOW ] ] ] ) );

		$removed = tribe( Rule_Store::class )->remove_rule( $ticket_id, Window_Kind::sale_price() );

		$this->assertTrue( $removed );
		$this->assertSame( $sales_window, tribe( Rule_Store::class )->get( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_remove_the_sales_window_rule_and_leave_the_sale_price_one(): void {
		$ticket_id  = static::factory()->post->create();
		$sale_price = [ 'start' => [ 'mode' => Rule::MODE_NOW ], 'end' => [ 'mode' => Rule::MODE_SPECIFIC ] ];
		tribe( Rule_Store::class )->save( $ticket_id, [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ], 'end' => [ 'mode' => Rule::MODE_DEFAULT ], 'sale_price' => $sale_price ] );

		$this->assertTrue( tribe( Rule_Store::class )->remove_rule( $ticket_id, Window_Kind::sales() ) );
		$this->assertSame( [ 'sale_price' => $sale_price ], tribe( Rule_Store::class )->get( $ticket_id ) );
	}

	/**
	 * @return Generator<string,array{0: Window_Kind}>
	 */
	public function kinds_provider(): Generator {
		foreach ( Window_Kind::all() as $kind ) {
			yield $kind->get_id() => [ $kind ];
		}
	}

	/**
	 * @test
	 * @dataProvider kinds_provider
	 */
	public function should_report_no_removal_without_a_stored_rule_of_the_kind( Window_Kind $kind ): void {
		$ticket_id = static::factory()->post->create();

		$this->assertFalse( tribe( Rule_Store::class )->remove_rule( $ticket_id, $kind ) );
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
		tribe( Rule_Store::class )->save( $ruled_ticket_id, [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ] ] );
		tribe( Rule_Store::class )->save( $draft_ticket_id, [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ] ] );
		tribe( Rule_Store::class )->save( $other_ticket_id, [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ] ] );

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
		tribe( Rule_Store::class )->save( $ticket_id, [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ] ] );
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
			update_post_meta( $ticket_id, Rule_Store::META_KEY, wp_json_encode( [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ] ] ) );
		}

		$logged = [];
		add_action(
			'tribe_log',
			static function ( $level, $source, $context ) use ( &$logged ) {
				if ( Rule_Store::class === $source ) {
					$logged[] = [ $level, $context['event_id'] ];
				}
			},
			10,
			3
		);

		$read_ids = tribe( Rule_Store::class )->get_ticket_ids_for_event( $event_id );

		// The oldest tickets are the ones read.
		$this->assertSame( array_slice( $ticket_ids, 0, Rule_Store::TICKETS_QUERY_LIMIT ), $read_ids );
		$this->assertSame( [ [ 'warning', $event_id ] ], $logged );
	}

	/**
	 * Writes the rules another request stores between this request's read of them and its write.
	 *
	 * @param string              $write_hook The filter that runs before this request's write of the meta.
	 * @param int                 $ticket_id  The ticket post ID.
	 * @param array<string,mixed> $rules      The rules the other request stores.
	 *
	 * @return void
	 */
	private function store_concurrently( string $write_hook, int $ticket_id, array $rules ): void {
		$store = static function ( $short_circuit, $object_id ) use ( &$store, $write_hook, $ticket_id, $rules ) {
			if ( $object_id === $ticket_id ) {
				remove_filter( $write_hook, $store );
				update_post_meta( $ticket_id, Rule_Store::META_KEY, wp_slash( wp_json_encode( $rules ) ) );
			}

			return $short_circuit;
		};

		add_filter( $write_hook, $store, 10, 2 );
	}

	/**
	 * @test
	 */
	public function should_keep_a_rule_another_request_saves_while_saving(): void {
		$ticket_id  = static::factory()->post->create();
		$window     = [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ], 'end' => [ 'mode' => Rule::MODE_DEFAULT ] ];
		$sale_price = [ 'start' => [ 'mode' => Rule::MODE_NOW ] ];
		update_post_meta( $ticket_id, Rule_Store::META_KEY, wp_json_encode( $window ) );
		$this->store_concurrently( 'update_post_metadata', $ticket_id, array_merge( $window, [ 'sale_price' => $sale_price ] ) );
		$end = [ 'mode' => Rule::MODE_RELATIVE, 'value' => 1, 'unit' => DAY_IN_SECONDS, 'anchor' => 'start' ];

		tribe( Rule_Store::class )->save( $ticket_id, [ 'end' => $end ] );

		$this->assertEquals(
			[ 'start' => $window['start'], 'end' => $end, 'sale_price' => $sale_price ],
			tribe( Rule_Store::class )->get( $ticket_id )
		);
	}

	/**
	 * @test
	 */
	public function should_keep_a_rule_another_request_saves_while_removing_the_last_one(): void {
		$ticket_id  = static::factory()->post->create();
		$window     = [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ], 'end' => [ 'mode' => Rule::MODE_DEFAULT ] ];
		$sale_price = [ 'start' => [ 'mode' => Rule::MODE_NOW ] ];
		update_post_meta( $ticket_id, Rule_Store::META_KEY, wp_json_encode( $window ) );
		$this->store_concurrently( 'delete_post_metadata', $ticket_id, array_merge( $window, [ 'sale_price' => $sale_price ] ) );

		$this->assertTrue( tribe( Rule_Store::class )->remove( $ticket_id, [ 'start', 'end' ] ) );

		$this->assertSame( [ 'sale_price' => $sale_price ], tribe( Rule_Store::class )->get( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_report_no_removal_when_every_write_of_it_conflicts(): void {
		$ticket_id = static::factory()->post->create();
		$window    = [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ], 'end' => [ 'mode' => Rule::MODE_DEFAULT ] ];
		update_post_meta( $ticket_id, Rule_Store::META_KEY, wp_json_encode( $window + [ 'sale_price' => [ 'start' => [ 'mode' => Rule::MODE_NOW ] ] ] ) );
		$writes = 0;
		$store  = static function ( $short_circuit, $object_id ) use ( &$store, &$writes, $ticket_id, $window ) {
			if ( $object_id === $ticket_id ) {
				// Another request changes the sale price rule before each of this request's writes.
				remove_filter( 'update_post_metadata', $store );
				update_post_meta( $ticket_id, Rule_Store::META_KEY, wp_json_encode( $window + [ 'sale_price' => [ 'start' => [ 'mode' => Rule::MODE_RELATIVE, 'value' => ++$writes ] ] ] ) );
				add_filter( 'update_post_metadata', $store, 10, 2 );
			}

			return $short_circuit;
		};
		add_filter( 'update_post_metadata', $store, 10, 2 );

		$removed = tribe( Rule_Store::class )->remove( $ticket_id, [ 'start', 'end' ] );
		remove_filter( 'update_post_metadata', $store );

		$this->assertFalse( $removed );
		$this->assertSame( $window, array_intersect_key( tribe( Rule_Store::class )->get( $ticket_id ), $window ) );
	}

	/**
	 * @test
	 */
	public function should_store_a_rule_with_a_backslash_and_compare_it_on_the_next_write(): void {
		$ticket_id = static::factory()->post->create();
		$window    = [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ], 'note' => 'a\\b "c"' ];

		tribe( Rule_Store::class )->save( $ticket_id, $window );
		tribe( Rule_Store::class )->save( $ticket_id, [ 'end' => [ 'mode' => Rule::MODE_DEFAULT ] ] );

		$this->assertSame( array_merge( $window, [ 'end' => [ 'mode' => Rule::MODE_DEFAULT ] ] ), tribe( Rule_Store::class )->get( $ticket_id ) );
		$this->assertCount( 1, get_post_meta( $ticket_id, Rule_Store::META_KEY ) );
	}
}
