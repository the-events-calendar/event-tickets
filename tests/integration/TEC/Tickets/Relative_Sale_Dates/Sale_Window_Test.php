<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Generator;

class Sale_Window_Test extends WPTestCase {
	/**
	 * @return Generator<string,array{0: array{name: string, rule: array{start: array{mode: string, value?: int, unit?: int, anchor?: string}, end: array{mode: string, value?: int, unit?: int, anchor?: string}}, timezone: string, event_start: string, event_end: string, expected: array{start_local: ?string, start_utc: ?string, end_local: ?string, end_utc: ?string, valid: bool}}}>
	 */
	public function fixtures_provider(): Generator {
		$fixtures = json_decode( file_get_contents( codecept_data_dir( 'relative-sale-dates/sale-window-cases.json' ) ), true );

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

		$window = tribe( Sale_Window::class )->resolve( Rule::from_array( $case['rule'] ), $event_start, $event_end );

		$expected = $case['expected'];
		$this->assert_date( $expected['start_local'], $timezone->getName(), $window->get_start() );
		$this->assert_date( $expected['start_utc'], 'UTC', $window->get_start_utc() );
		$this->assert_date( $expected['end_local'], $timezone->getName(), $window->get_end() );
		$this->assert_date( $expected['end_utc'], 'UTC', $window->get_end_utc() );
		$this->assertSame( $expected['valid'], $window->is_valid() );
	}

	/**
	 * @test
	 */
	public function should_resolve_in_the_timezone_of_the_event_start(): void {
		$rule        = Rule::from_array(
			[
				'start' => [ 'mode' => 'default' ],
				'end'   => [
					'mode'   => 'relative',
					'value'  => 1,
					'unit'   => Rule::UNIT_HOURS,
					'anchor' => 'end',
				],
			]
		);
		$event_start = new DateTimeImmutable( '2027-06-10 19:00:00', new DateTimeZone( 'America/New_York' ) );
		$event_end   = new DateTime( '2027-06-11 01:00:00', new DateTimeZone( 'UTC' ) );

		$window = tribe( Sale_Window::class )->resolve( $rule, $event_start, $event_end );

		$this->assert_date( '2027-06-10 20:00:00', 'America/New_York', $window->get_end() );
		$this->assert_date( '2027-06-11 00:00:00', 'UTC', $window->get_end_utc() );
	}

	/**
	 * Asserts a resolved date matches its expected wall-clock value and timezone, or that both are empty.
	 *
	 * @param string|null            $expected The expected date, in `Y-m-d H:i:s` format, or `null` for no date.
	 * @param string                 $timezone The timezone the date is expected in.
	 * @param DateTimeImmutable|null $actual   The resolved date.
	 *
	 * @return void
	 */
	private function assert_date( ?string $expected, string $timezone, ?DateTimeImmutable $actual ): void {
		if ( null === $expected ) {
			$this->assertNull( $actual );

			return;
		}

		$this->assertInstanceOf( DateTimeImmutable::class, $actual );
		$this->assertSame( $expected, $actual->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( $timezone, $actual->getTimezone()->getName() );
	}
}
