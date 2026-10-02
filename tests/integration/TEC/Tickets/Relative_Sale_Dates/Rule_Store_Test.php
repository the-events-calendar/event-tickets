<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use TEC\Tickets\Commerce\Ticket;
use Codeception\TestCase\WPTestCase;

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
	 * @test
	 */
	public function should_get_the_tickets_of_an_event_that_have_stored_rules(): void {
		$event_id        = static::factory()->post->create();
		$ruled_ticket_id = static::factory()->post->create( [ 'post_type' => Ticket::POSTTYPE ] );
		$plain_ticket_id = static::factory()->post->create( [ 'post_type' => Ticket::POSTTYPE ] );
		$other_ticket_id = static::factory()->post->create( [ 'post_type' => Ticket::POSTTYPE ] );
		update_post_meta( $ruled_ticket_id, Ticket::$event_relation_meta_key, $event_id );
		update_post_meta( $plain_ticket_id, Ticket::$event_relation_meta_key, $event_id );
		update_post_meta( $other_ticket_id, Ticket::$event_relation_meta_key, static::factory()->post->create() );
		tribe( Rule_Store::class )->save( $ruled_ticket_id, [ 'start' => [ 'mode' => 'default' ] ] );
		tribe( Rule_Store::class )->save( $other_ticket_id, [ 'start' => [ 'mode' => 'default' ] ] );

		$this->assertSame( [ $ruled_ticket_id ], tribe( Rule_Store::class )->get_ticket_ids_for_event( $event_id ) );
	}
}
