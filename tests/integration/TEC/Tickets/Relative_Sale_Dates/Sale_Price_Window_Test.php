<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;
use DateTimeImmutable;
use DateTimeZone;
use Generator;

class Sale_Price_Window_Test extends WPTestCase {
	/**
	 * @return Generator<string,array{0: array{name: string, rule: array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}}, timezone: string, event_start: string, event_end: string, expected: array{start: ?string, end: ?string}}}>
	 */
	public function fixtures_provider(): Generator {
		$fixtures = json_decode( file_get_contents( codecept_data_dir( 'relative-sale-dates/sale-price-cases.json' ) ), true );

		foreach ( $fixtures['cases'] as $case ) {
			yield $case['name'] => [ $case ];
		}
	}

	/**
	 * @test
	 * @dataProvider fixtures_provider
	 */
	public function should_resolve_the_shared_fixture_case( array $case ): void {
		$timezone    = new DateTimeZone( $case['timezone'] );
		$event_start = new DateTimeImmutable( $case['event_start'], $timezone );
		$event_end   = new DateTimeImmutable( $case['event_end'], $timezone );

		$dates = tribe( Sale_Price_Window::class )->resolve( Sale_Price_Rule::from_array( $case['rule'] ), $event_start, $event_end );

		$this->assertSame( $case['expected'], $dates );
	}

	/**
	 * @test
	 */
	public function should_resolve_the_date_in_the_timezone_of_the_event_start(): void {
		$rule        = Sale_Price_Rule::from_array(
			[
				'start' => [
					'mode'  => Rule::MODE_RELATIVE,
					'value' => 1,
					'unit'  => DAY_IN_SECONDS,
				],
				'end'   => [ 'mode' => Rule::MODE_SPECIFIC ],
			]
		);
		// 21:00 in New York is already the next day in UTC, so a date read in UTC would be a day late.
		$event_start = new DateTimeImmutable( '2027-06-10 21:00:00', new DateTimeZone( 'America/New_York' ) );
		$event_end   = $event_start->modify( '+2 hours' );

		$dates = tribe( Sale_Price_Window::class )->resolve( $rule, $event_start, $event_end );

		$this->assertSame( $event_start->modify( '-1 day' )->format( 'Y-m-d' ), $dates['start'] );
	}
}
