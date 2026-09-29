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
	 * @return Generator<string,array{0: array{mode: string, value?: int, unit?: int, anchor?: string}}>
	 */
	public function valid_boundaries_provider(): Generator {
		$units = [
			'minutes' => MINUTE_IN_SECONDS,
			'hours'   => HOUR_IN_SECONDS,
			'days'    => DAY_IN_SECONDS,
			'weeks'   => WEEK_IN_SECONDS,
		];
		// The lowest and highest value the product allows.
		$values = [ 1, 60 ];

		yield 'default' => [ [ 'mode' => Rule::MODE_DEFAULT ] ];
		yield 'specific' => [ [ 'mode' => Rule::MODE_SPECIFIC ] ];

		foreach ( $units as $unit_name => $unit ) {
			foreach ( [ Rule::ANCHOR_START, Rule::ANCHOR_END ] as $anchor ) {
				foreach ( $values as $value ) {
					yield "relative, {$value} {$unit_name} before {$anchor}" => [
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
	}

	/**
	 * @return Generator<string,array{0: array<string,mixed>}>
	 */
	public function invalid_boundaries_provider(): Generator {
		$relative = [
			'mode'   => Rule::MODE_RELATIVE,
			'value'  => 2,
			'unit'   => WEEK_IN_SECONDS,
			'anchor' => Rule::ANCHOR_START,
		];

		yield 'unknown mode' => [ [ 'mode' => 'after' ] ];
		yield 'missing mode' => [ [] ];
		yield 'non-string mode' => [ [ 'mode' => 1 ] ];
		yield 'unknown anchor' => [ array_merge( $relative, [ 'anchor' => 'middle' ] ) ];
		yield 'unit of a month' => [ array_merge( $relative, [ 'unit' => MONTH_IN_SECONDS ] ) ];
		yield 'unit as a string' => [ array_merge( $relative, [ 'unit' => sprintf( '%d', WEEK_IN_SECONDS ) ] ) ];
		yield 'value 0' => [ array_merge( $relative, [ 'value' => 0 ] ) ];
		yield 'value 61' => [ array_merge( $relative, [ 'value' => 61 ] ) ];
		yield 'negative value' => [ array_merge( $relative, [ 'value' => -2 ] ) ];
		yield 'float value' => [ array_merge( $relative, [ 'value' => 2.5 ] ) ];
		yield 'whole float value' => [ array_merge( $relative, [ 'value' => 2.0 ] ) ];
		yield 'numeric string value' => [ array_merge( $relative, [ 'value' => '2' ] ) ];
		yield 'relative without value' => [ array_diff_key( $relative, [ 'value' => true ] ) ];
		yield 'relative without unit' => [ array_diff_key( $relative, [ 'unit' => true ] ) ];
		yield 'relative without anchor' => [ array_diff_key( $relative, [ 'anchor' => true ] ) ];
	}

	/**
	 * @test
	 * @dataProvider valid_boundaries_provider
	 */
	public function should_keep_a_valid_boundary_in_canonical_form( array $data ): void {
		$boundary = Boundary::from_array( $data );

		$this->assertSame( $data, $boundary->to_array() );
		$this->assertSame( $data, $boundary->jsonSerialize() );
	}

	/**
	 * @test
	 * @dataProvider valid_boundaries_provider
	 */
	public function should_expose_its_parts_and_interval( array $data ): void {
		$boundary = Boundary::from_array( $data );

		$this->assertSame( $data['mode'], $boundary->get_mode() );
		$this->assertSame( $data['value'] ?? null, $boundary->get_value() );
		$this->assertSame( $data['unit'] ?? null, $boundary->get_unit() );
		$this->assertSame( $data['anchor'] ?? null, $boundary->get_anchor() );

		if ( ! isset( $data['value'], $data['unit'] ) ) {
			$this->assertNull( $boundary->get_interval() );

			return;
		}

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
	public function should_reject_an_invalid_boundary( array $data ): void {
		$this->expectException( InvalidArgumentException::class );

		Boundary::from_array( $data );
	}

	/**
	 * @test
	 */
	public function should_not_keep_keys_the_mode_does_not_use(): void {
		$relative = [
			'mode'   => Rule::MODE_RELATIVE,
			'value'  => 2,
			'unit'   => HOUR_IN_SECONDS,
			'anchor' => Rule::ANCHOR_END,
		];

		$specific = Boundary::from_array( array_merge( $relative, [ 'mode' => Rule::MODE_SPECIFIC ] ) );
		$extra    = Boundary::from_array( array_merge( $relative, [ 'extra' => 'dropped' ] ) );

		$this->assertSame( [ 'mode' => Rule::MODE_SPECIFIC ], $specific->jsonSerialize() );
		$this->assertSame( $relative, $extra->jsonSerialize() );
	}
}
