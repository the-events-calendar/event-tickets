<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Generator;
use InvalidArgumentException;

class Boundary_Test extends WPTestCase {
	/**
	 * @return Generator<string,array{0: Window_Kind, 1: string, 2: array{mode: string, value?: int, unit?: int, anchor?: string}}>
	 */
	public function valid_boundaries_provider(): Generator {
		$sales      = Window_Kind::sales();
		$sale_price = Window_Kind::sale_price();
		$units      = [
			'minutes' => MINUTE_IN_SECONDS,
			'hours'   => HOUR_IN_SECONDS,
			'days'    => DAY_IN_SECONDS,
			'weeks'   => WEEK_IN_SECONDS,
		];
		// The lowest and highest value the product allows.
		$values = [ 1, 60 ];

		yield 'default' => [ $sales, 'start', [ 'mode' => Rule::MODE_DEFAULT ] ];
		yield 'specific' => [ $sales, 'start', [ 'mode' => Rule::MODE_SPECIFIC ] ];

		foreach ( $units as $unit_name => $unit ) {
			foreach ( [ Rule::ANCHOR_START, Rule::ANCHOR_END ] as $anchor ) {
				foreach ( $values as $value ) {
					yield "relative, {$value} {$unit_name} before {$anchor}" => [
						$sales,
						'start',
						[
							'mode'   => Rule::MODE_RELATIVE,
							'value'  => $value,
							'unit'   => $unit,
							'anchor' => $anchor,
						],
					];
				}
			}
		}

		yield 'sale price: now' => [ $sale_price, 'start', [ 'mode' => Rule::MODE_NOW ] ];
		yield 'sale price: specific start' => [ $sale_price, 'start', [ 'mode' => Rule::MODE_SPECIFIC ] ];
		yield 'sale price: specific end' => [ $sale_price, 'end', [ 'mode' => Rule::MODE_SPECIFIC ] ];

		foreach ( [ 'days' => DAY_IN_SECONDS, 'weeks' => WEEK_IN_SECONDS ] as $unit_name => $unit ) {
			foreach ( [ 1, 30 ] as $value ) {
				foreach ( [ 'start', 'end' ] as $end ) {
					yield "sale price: relative {$end}, {$value} {$unit_name}" => [
						$sale_price,
						$end,
						[
							'mode'  => Rule::MODE_RELATIVE,
							'value' => $value,
							'unit'  => $unit,
						],
					];
				}
			}
		}
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: string, 2: array<string,mixed>}>
	 */
	public function invalid_boundaries_provider(): Generator {
		$sales      = Window_Kind::sales();
		$sale_price = Window_Kind::sale_price();
		$relative   = [
			'mode'   => Rule::MODE_RELATIVE,
			'value'  => 2,
			'unit'   => WEEK_IN_SECONDS,
			'anchor' => Rule::ANCHOR_START,
		];

		yield 'unknown mode' => [ $sales, 'start', [ 'mode' => 'after' ] ];
		yield 'missing mode' => [ $sales, 'start', [] ];
		yield 'non-string mode' => [ $sales, 'start', [ 'mode' => 1 ] ];
		yield 'now mode' => [ $sales, 'start', [ 'mode' => Rule::MODE_NOW ] ];
		yield 'unknown anchor' => [ $sales, 'start', array_merge( $relative, [ 'anchor' => 'middle' ] ) ];
		yield 'unit of a month' => [ $sales, 'start', array_merge( $relative, [ 'unit' => MONTH_IN_SECONDS ] ) ];
		yield 'unit as a string' => [ $sales, 'start', array_merge( $relative, [ 'unit' => sprintf( '%d', WEEK_IN_SECONDS ) ] ) ];
		yield 'value 0' => [ $sales, 'start', array_merge( $relative, [ 'value' => 0 ] ) ];
		yield 'value 61' => [ $sales, 'start', array_merge( $relative, [ 'value' => 61 ] ) ];
		yield 'negative value' => [ $sales, 'start', array_merge( $relative, [ 'value' => -2 ] ) ];
		yield 'float value' => [ $sales, 'start', array_merge( $relative, [ 'value' => 2.5 ] ) ];
		yield 'whole float value' => [ $sales, 'start', array_merge( $relative, [ 'value' => 2.0 ] ) ];
		yield 'numeric string value' => [ $sales, 'start', array_merge( $relative, [ 'value' => '2' ] ) ];
		yield 'relative without value' => [ $sales, 'start', array_diff_key( $relative, [ 'value' => true ] ) ];
		yield 'relative without unit' => [ $sales, 'start', array_diff_key( $relative, [ 'unit' => true ] ) ];
		yield 'relative without anchor' => [ $sales, 'start', array_diff_key( $relative, [ 'anchor' => true ] ) ];

		$relative = array_diff_key( $relative, [ 'anchor' => true ] );

		yield 'sale price: missing mode' => [ $sale_price, 'start', [] ];
		yield 'sale price: default start' => [ $sale_price, 'start', [ 'mode' => Rule::MODE_DEFAULT ] ];
		yield 'sale price: default end' => [ $sale_price, 'end', [ 'mode' => Rule::MODE_DEFAULT ] ];
		yield 'sale price: now end' => [ $sale_price, 'end', [ 'mode' => Rule::MODE_NOW ] ];
		yield 'sale price: value below the lowest' => [ $sale_price, 'start', array_merge( $relative, [ 'value' => 0 ] ) ];
		yield 'sale price: value above the highest' => [ $sale_price, 'start', array_merge( $relative, [ 'value' => 31 ] ) ];
		yield 'sale price: missing value' => [ $sale_price, 'start', [ 'mode' => Rule::MODE_RELATIVE, 'unit' => DAY_IN_SECONDS ] ];
		yield 'sale price: hours' => [ $sale_price, 'start', array_merge( $relative, [ 'unit' => HOUR_IN_SECONDS ] ) ];
		yield 'sale price: unit sent as a string' => [ $sale_price, 'start', array_merge( $relative, [ 'unit' => sprintf( '%d', DAY_IN_SECONDS ) ] ) ];
		yield 'sale price: an anchor on the event start' => [ $sale_price, 'start', array_merge( $relative, [ 'anchor' => Rule::ANCHOR_START ] ) ];
		yield 'sale price: an anchor on the event end' => [ $sale_price, 'start', array_merge( $relative, [ 'anchor' => Rule::ANCHOR_END ] ) ];
		yield 'sale price: a null anchor' => [ $sale_price, 'start', array_merge( $relative, [ 'anchor' => null ] ) ];
	}

	/**
	 * @test
	 * @dataProvider valid_boundaries_provider
	 */
	public function should_keep_a_valid_boundary_in_canonical_form( Window_Kind $kind, string $end, array $data ): void {
		$boundary = Boundary::from_array( $data, $kind, $end );

		$this->assertSame( $data, $boundary->to_array() );
		$this->assertSame( $data, $boundary->jsonSerialize() );
		$this->assertSame( wp_json_encode( $data ), wp_json_encode( $boundary ) );
	}

	/**
	 * @test
	 * @dataProvider valid_boundaries_provider
	 */
	public function should_expose_its_parts_and_interval( Window_Kind $kind, string $end, array $data ): void {
		$boundary = Boundary::from_array( $data, $kind, $end );

		$this->assertSame( $kind, $boundary->get_kind() );
		$this->assertSame( $data['mode'], $boundary->get_mode() );

		if ( ! isset( $data['value'], $data['unit'] ) ) {
			$this->assertNull( $boundary->get_anchor() );
			$this->assertNull( $boundary->get_interval() );

			return;
		}

		// A kind that takes no anchor counts every relative boundary from the event start.
		$this->assertSame( $data['anchor'] ?? Rule::ANCHOR_START, $boundary->get_anchor() );

		// UTC has no clock change, so the interval spans exactly its length in seconds.
		$from = new DateTimeImmutable( '2027-06-10 00:00:00', new DateTimeZone( 'UTC' ) );

		$this->assertInstanceOf( DateInterval::class, $boundary->get_interval() );
		$this->assertSame(
			$data['value'] * $data['unit'],
			$from->getTimestamp() - $from->sub( $boundary->get_interval() )->getTimestamp()
		);
	}

	/**
	 * @test
	 * @dataProvider invalid_boundaries_provider
	 */
	public function should_reject_an_invalid_boundary( Window_Kind $kind, string $end, array $data ): void {
		$this->expectException( InvalidArgumentException::class );

		Boundary::from_array( $data, $kind, $end );
	}

	/**
	 * @test
	 */
	public function should_build_a_sales_window_boundary_without_a_kind(): void {
		$data = [
			'mode'   => Rule::MODE_RELATIVE,
			'value'  => 2,
			'unit'   => HOUR_IN_SECONDS,
			'anchor' => Rule::ANCHOR_END,
		];

		$boundary = Boundary::from_array( $data );

		$this->assertSame( Window_Kind::sales(), $boundary->get_kind() );
		$this->assertSame( $data, $boundary->to_array() );
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: array{mode: string, value: int, unit: int, anchor?: string}}>
	 */
	public function relative_boundaries_provider(): Generator {
		yield 'sales' => [
			Window_Kind::sales(),
			[
				'mode'   => Rule::MODE_RELATIVE,
				'value'  => 2,
				'unit'   => HOUR_IN_SECONDS,
				'anchor' => Rule::ANCHOR_END,
			],
		];
		yield 'sale price' => [
			Window_Kind::sale_price(),
			[
				'mode'  => Rule::MODE_RELATIVE,
				'value' => 3,
				'unit'  => DAY_IN_SECONDS,
			],
		];
	}

	/**
	 * @test
	 * @dataProvider relative_boundaries_provider
	 */
	public function should_not_keep_keys_the_mode_does_not_use( Window_Kind $kind, array $relative ): void {
		$specific = Boundary::from_array( array_merge( $relative, [ 'mode' => Rule::MODE_SPECIFIC, 'anchor' => Rule::ANCHOR_START ] ), $kind );
		$extra    = Boundary::from_array( array_merge( $relative, [ 'extra' => 'dropped' ] ), $kind );

		$this->assertSame( [ 'mode' => Rule::MODE_SPECIFIC ], $specific->jsonSerialize() );
		$this->assertSame( $relative, $extra->jsonSerialize() );
	}
}
