<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;
use DateTimeImmutable;
use Generator;
use TEC\Tickets\Commerce\Ticket;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;

class Sale_Price_Dates_Test extends WPTestCase {
	use Relative_Sale_Dates_Maker;
	use Ticket_Maker;
	use With_Tickets_Commerce;

	/**
	 * The sale price rule most tests store: from 2 weeks to 1 week before the event start.
	 *
	 * @var array{start: array{mode: string, value: int, unit: int}, end: array{mode: string, value: int, unit: int}}
	 */
	private const RULE = [
		'start' => [
			'mode'  => Rule::MODE_RELATIVE,
			'value' => 2,
			'unit'  => WEEK_IN_SECONDS,
		],
		'end'   => [
			'mode'  => Rule::MODE_RELATIVE,
			'value' => 1,
			'unit'  => WEEK_IN_SECONDS,
		],
	];

	/**
	 * @test
	 */
	public function should_write_the_dates_the_stored_rule_resolves_to(): void {
		$event_start = new DateTimeImmutable( '2027-06-24 19:00:00' );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_ticket_with_fixed_sale_price_dates( $event_id, [] );
		tribe( Rule_Store::class )->save( $ticket_id, [ Sale_Price_Rule::KEY => self::RULE ] );

		tribe( Sale_Price_Dates::class )->write( $ticket_id, $event_id );

		$this->assertSame(
			[ $event_start->modify( '-2 weeks' )->format( 'Y-m-d' ), $event_start->modify( '-1 week' )->format( 'Y-m-d' ) ],
			$this->get_sale_price_dates( $ticket_id )
		);
	}

	/**
	 * @return Generator<string,array{0: callable(self): int, 1: array<string,array<string,array<string,int|string>>>, 2: array{0: string, 1: string}}>
	 */
	public function nothing_to_write_provider(): Generator {
		$event = static fn( self $test ): int => $test->create_event( '2027-06-24 19:00:00' );

		yield 'no stored sale price rule' => [
			$event,
			[
				'start' => [ 'mode' => Rule::MODE_DEFAULT ],
				'end'   => [ 'mode' => Rule::MODE_DEFAULT ],
			],
			[ '2027-01-04', '2027-01-11' ],
		];
		yield 'an invalid stored sale price rule' => [ $event, [ Sale_Price_Rule::KEY => [ 'start' => [ 'mode' => Sale_Price_Rule::MODE_NOW ] ] ], [ '2027-01-04', '2027-01-11' ] ];
		yield 'a post without event dates' => [
			static fn( self $test ): int => static::factory()->post->create( [ 'post_type' => 'page' ] ),
			[ Sale_Price_Rule::KEY => self::RULE ],
			[ '2027-01-04', '2027-01-11' ],
		];
	}

	/**
	 * @test
	 * @dataProvider nothing_to_write_provider
	 */
	public function should_write_nothing_without_a_rule_to_resolve( callable $create_post, array $stored, array $expected_dates ): void {
		$post_id   = $create_post( $this );
		$ticket_id = $this->create_ticket_with_fixed_sale_price_dates( $post_id, [] );
		tribe( Rule_Store::class )->save( $ticket_id, $stored );

		tribe( Sale_Price_Dates::class )->write( $ticket_id, $post_id );

		$this->assertSame( $expected_dates, $this->get_sale_price_dates( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_write_nothing_for_a_ticket_without_a_sale_price(): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_ticket_with_fixed_sale_price_dates( $event_id, [ 'ticket_add_sale_price' => false ] );
		tribe( Rule_Store::class )->save( $ticket_id, [ Sale_Price_Rule::KEY => self::RULE ] );

		tribe( Sale_Price_Dates::class )->write( $ticket_id, $event_id );

		$this->assertFalse( tribe( Sale_Price_Dates::class )->has_sale_price( $ticket_id ) );
		$this->assertSame( [ '', '' ], $this->get_sale_price_dates( $ticket_id ) );
	}

	/**
	 * Creates a Tickets Commerce ticket priced 20 with a sale price of 10 from 2027-01-04 to 2027-01-11.
	 *
	 * @param int                      $post_id   The ticketed post ID.
	 * @param array<string,string|bool> $overrides The ticket data to override.
	 *
	 * @return int The ticket post ID.
	 */
	private function create_ticket_with_fixed_sale_price_dates( int $post_id, array $overrides ): int {
		return $this->create_tc_ticket(
			$post_id,
			20,
			array_merge(
				[
					'ticket_add_sale_price'  => 'on',
					'ticket_sale_price'      => 10,
					'ticket_sale_start_date' => '2027-01-04',
					'ticket_sale_end_date'   => '2027-01-11',
				],
				$overrides
			)
		);
	}

	/**
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return array{0: string, 1: string} The stored sale price start and end dates.
	 */
	private function get_sale_price_dates( int $ticket_id ): array {
		return [
			get_post_meta( $ticket_id, Ticket::$sale_price_start_date_key, true ),
			get_post_meta( $ticket_id, Ticket::$sale_price_end_date_key, true ),
		];
	}
}
