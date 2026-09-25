<?php

namespace TEC\Tickets\Relative_Sale_Dates;

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
				'unit'   => Rule::UNIT_DAYS,
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
}
