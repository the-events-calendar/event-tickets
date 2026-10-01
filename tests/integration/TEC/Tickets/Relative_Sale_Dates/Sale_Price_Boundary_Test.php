<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;
use Generator;
use InvalidArgumentException;

class Sale_Price_Boundary_Test extends WPTestCase {
	/**
	 * @return Generator<string,array{0: array{mode: string, value?: int, unit?: int}}>
	 */
	public function valid_boundaries_provider(): Generator {
		$units = [
			'days'  => DAY_IN_SECONDS,
			'weeks' => WEEK_IN_SECONDS,
		];

		yield 'now' => [ [ 'mode' => Sale_Price_Rule::MODE_NOW ] ];
		yield 'specific' => [ [ 'mode' => Rule::MODE_SPECIFIC ] ];

		foreach ( $units as $unit_name => $unit ) {
			foreach ( [ Sale_Price_Boundary::MIN_VALUE, Sale_Price_Boundary::MAX_VALUE ] as $value ) {
				yield "relative, {$value} {$unit_name}" => [
					[
						'mode'  => Rule::MODE_RELATIVE,
						'value' => $value,
						'unit'  => $unit,
					],
				];
			}
		}
	}

	/**
	 * @return Generator<string,array{0: array<string,mixed>}>
	 */
	public function invalid_boundaries_provider(): Generator {
		$relative = [
			'mode'  => Rule::MODE_RELATIVE,
			'value' => 2,
			'unit'  => WEEK_IN_SECONDS,
		];

		yield 'missing mode' => [ [] ];
		yield 'default mode' => [ [ 'mode' => Rule::MODE_DEFAULT ] ];
		yield 'value below the lowest' => [ array_merge( $relative, [ 'value' => Sale_Price_Boundary::MIN_VALUE - 1 ] ) ];
		yield 'value above the highest' => [ array_merge( $relative, [ 'value' => Sale_Price_Boundary::MAX_VALUE + 1 ] ) ];
		yield 'missing value' => [ [ 'mode' => Rule::MODE_RELATIVE, 'unit' => DAY_IN_SECONDS ] ];
		yield 'hours' => [ array_merge( $relative, [ 'unit' => HOUR_IN_SECONDS ] ) ];
		yield 'unit sent as a string' => [ array_merge( $relative, [ 'unit' => sprintf( '%d', DAY_IN_SECONDS ) ] ) ];
		yield 'an anchor on the event end' => [ array_merge( $relative, [ 'anchor' => Rule::ANCHOR_END ] ) ];
		yield 'a null anchor' => [ array_merge( $relative, [ 'anchor' => null ] ) ];
	}

	/**
	 * @test
	 * @dataProvider valid_boundaries_provider
	 */
	public function should_return_a_valid_boundary_as_its_canonical_array( array $data ): void {
		$this->assertSame( $data, Sale_Price_Boundary::from_array( $data, 'start' )->to_array() );
	}

	/**
	 * @test
	 * @dataProvider valid_boundaries_provider
	 */
	public function should_encode_as_its_canonical_array( array $data ): void {
		$this->assertSame( wp_json_encode( $data ), wp_json_encode( Sale_Price_Boundary::from_array( $data, 'end' ) ) );
	}

	/**
	 * @test
	 * @dataProvider invalid_boundaries_provider
	 */
	public function should_reject_an_invalid_boundary( array $data ): void {
		$this->expectException( InvalidArgumentException::class );

		Sale_Price_Boundary::from_array( $data, 'start' );
	}

	/**
	 * @test
	 */
	public function should_name_the_sale_price_end_it_rejects(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'The sale price end' );

		Sale_Price_Boundary::from_array( [ 'mode' => Rule::MODE_DEFAULT ], 'end' );
	}

	/**
	 * @test
	 */
	public function should_not_write_keys_the_mode_does_not_use(): void {
		$boundary = Sale_Price_Boundary::from_array(
			[
				'mode'   => Rule::MODE_SPECIFIC,
				'value'  => 3,
				'unit'   => DAY_IN_SECONDS,
				'anchor' => Rule::ANCHOR_START,
			],
			'start'
		);

		$this->assertSame( [ 'mode' => Rule::MODE_SPECIFIC ], $boundary->to_array() );
	}

	/**
	 * @test
	 */
	public function should_count_a_relative_boundary_from_the_event_start(): void {
		$data = [
			'mode'  => Rule::MODE_RELATIVE,
			'value' => Sale_Price_Boundary::MAX_VALUE,
			'unit'  => WEEK_IN_SECONDS,
		];

		$boundary = Sale_Price_Boundary::from_array( $data, 'end' )->to_boundary();

		$this->assertInstanceOf( Boundary::class, $boundary );
		$this->assertSame( array_merge( $data, [ 'anchor' => Rule::ANCHOR_START ] ), $boundary->to_array() );
	}

	/**
	 * @return Generator<string,array{0: string}>
	 */
	public function modes_without_a_date_provider(): Generator {
		yield 'now' => [ Sale_Price_Rule::MODE_NOW ];
		yield 'specific' => [ Rule::MODE_SPECIFIC ];
	}

	/**
	 * @test
	 * @dataProvider modes_without_a_date_provider
	 */
	public function should_build_no_boundary_for_a_mode_without_a_relative_date( string $mode ): void {
		$this->assertNull( Sale_Price_Boundary::from_array( [ 'mode' => $mode ], 'start' )->to_boundary() );
	}
}
