<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generator;
use RuntimeException;
use Tribe\Tests\Traits\With_Clock_Mock;

class Sale_Window_Test extends WPTestCase {
	use With_Clock_Mock;

	/**
	 * @return Generator<string,array{0: array{name: string, rule: array{start: array{mode: string, value?: int, unit?: int, anchor?: string}, end: array{mode: string, value?: int, unit?: int, anchor?: string}}, timezone: string, event_start: string, event_end: string, expected: array{start_local: ?string, start_utc: ?string, end_local: ?string, end_utc: ?string, valid: ?bool}}}>
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

		if ( null === $expected['valid'] ) {
			$this->expectException( RuntimeException::class );
		}

		$this->assertSame( $expected['valid'], $window->is_valid() );
	}

	/**
	 * @return Generator<string,array{0: array{name: string, rule: array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}}, timezone: string, event_start: string, event_end: string, expected: array{start: ?string, end: ?string}}}>
	 */
	public function sale_price_fixtures_provider(): Generator {
		$fixtures = json_decode( file_get_contents( codecept_data_dir( 'relative-sale-dates/sale-price-cases.json' ) ), true );

		foreach ( $fixtures['cases'] as $case ) {
			yield "sale price: {$case['name']}" => [ $case ];
		}
	}

	/**
	 * @test
	 * @dataProvider sale_price_fixtures_provider
	 */
	public function should_resolve_the_shared_sale_price_fixture_case( array $case ): void {
		$timezone    = new DateTimeZone( $case['timezone'] );
		$event_start = new DateTimeImmutable( $case['event_start'], $timezone );
		$event_end   = new DateTimeImmutable( $case['event_end'], $timezone );
		$rule        = Rule::from_array( $case['rule'], Window_Kind::sale_price() );

		$window = tribe( Sale_Window::class )->resolve( $rule, $event_start, $event_end );

		/*
		 * The fixture holds the dates the sale price metas store: `Y-m-d`, with `''` for a Now start that has no date
		 * and `null` for a specific boundary, which keeps the ticket's own date.
		 */
		foreach ( [ 'start' => $window->get_start(), 'end' => $window->get_end() ] as $key => $date ) {
			if ( ! $case['expected'][ $key ] ) {
				$this->assertNull( $date );

				continue;
			}

			$this->assertInstanceOf( DateTimeImmutable::class, $date );
			$this->assertSame( $case['expected'][ $key ], $date->format( 'Y-m-d' ) );
			$this->assertSame( $timezone->getName(), $date->getTimezone()->getName() );
		}
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: array{name: string, rule: array{start: array{mode: string, value?: int, unit?: int, anchor?: string}, end: array{mode: string, value?: int, unit?: int, anchor?: string}}, timezone: string, event_start: string, event_end: string}, 2: string}>
	 */
	public function relative_boundaries_provider(): Generator {
		$fixtures = [
			''             => [ Window_Kind::sales(), $this->fixtures_provider() ],
			'sale price: ' => [ Window_Kind::sale_price(), $this->sale_price_fixtures_provider() ],
		];

		foreach ( $fixtures as $prefix => [ $kind, $cases ] ) {
			foreach ( $cases as [ $case ] ) {
				foreach ( [ 'start', 'end' ] as $key ) {
					if ( 'relative' === $case['rule'][ $key ]['mode'] ) {
						yield "{$prefix}{$case['name']}: {$key}" => [ $kind, $case, $key ];
					}
				}
			}
		}
	}

	/**
	 * @test
	 * @dataProvider relative_boundaries_provider
	 */
	public function should_resolve_a_relative_boundary_before_its_anchor( Window_Kind $kind, array $case, string $key ): void {
		$timezone = new DateTimeZone( $case['timezone'] );
		$anchors  = [
			'start' => new DateTimeImmutable( $case['event_start'], $timezone ),
			'end'   => new DateTimeImmutable( $case['event_end'], $timezone ),
		];
		$rule     = Rule::from_array( $case['rule'], $kind );
		$boundary = 'start' === $key ? $rule->get_start() : $rule->get_end();

		$window   = tribe( Sale_Window::class )->resolve( $rule, $anchors['start'], $anchors['end'] );
		$resolved = 'start' === $key ? $window->get_start_utc() : $window->get_end_utc();

		$this->assertLessThan( $anchors[ $boundary->get_anchor() ]->getTimestamp(), $resolved->getTimestamp() );
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: array<string,array<string,int|string>>, 2: DateTimeImmutable, 3: DateTimeInterface, 4: string, 5: string}>
	 */
	public function event_timezone_provider(): Generator {
		$new_york = new DateTimeZone( 'America/New_York' );

		yield 'an event end given in UTC' => [
			Window_Kind::sales(),
			[
				'start' => [ 'mode' => 'default' ],
				'end'   => [
					'mode'   => 'relative',
					'value'  => 1,
					'unit'   => HOUR_IN_SECONDS,
					'anchor' => 'end',
				],
			],
			new DateTimeImmutable( '2027-06-10 19:00:00', $new_york ),
			new DateTime( '2027-06-11 01:00:00', new DateTimeZone( 'UTC' ) ),
			'end',
			'2027-06-10 20:00:00',
		];

		// 21:00 in New York is already the next day in UTC, so a date read in UTC would be a day late.
		yield 'sale price: an evening event start' => [
			Window_Kind::sale_price(),
			[
				'start' => [
					'mode'  => 'relative',
					'value' => 1,
					'unit'  => DAY_IN_SECONDS,
				],
				'end'   => [ 'mode' => 'specific' ],
			],
			new DateTimeImmutable( '2027-06-10 21:00:00', $new_york ),
			new DateTimeImmutable( '2027-06-10 23:00:00', $new_york ),
			'start',
			'2027-06-09 21:00:00',
		];
	}

	/**
	 * @test
	 * @dataProvider event_timezone_provider
	 */
	public function should_resolve_in_the_timezone_of_the_event_start(
		Window_Kind $kind,
		array $data,
		DateTimeImmutable $event_start,
		DateTimeInterface $event_end,
		string $key,
		string $expected
	): void {
		$window = tribe( Sale_Window::class )->resolve( Rule::from_array( $data, $kind ), $event_start, $event_end );

		$date     = 'start' === $key ? $window->get_start() : $window->get_end();
		$date_utc = 'start' === $key ? $window->get_start_utc() : $window->get_end_utc();
		$timezone = $event_start->getTimezone()->getName();

		$this->assert_date( $expected, $timezone, $date );
		$this->assert_date(
			( new DateTimeImmutable( $expected, new DateTimeZone( $timezone ) ) )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ),
			'UTC',
			$date_utc
		);
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: string}>
	 */
	public function boundaries_without_a_date_provider(): Generator {
		foreach ( [ 'sales: ' => Window_Kind::sales(), 'sale price: ' => Window_Kind::sale_price() ] as $prefix => $kind ) {
			yield "{$prefix}an open start and a specific end" => [ $kind, $kind->get_open_start_mode() ];
			yield "{$prefix}a specific start and a specific end" => [ $kind, Rule::MODE_SPECIFIC ];
		}
	}

	/**
	 * @test
	 * @dataProvider boundaries_without_a_date_provider
	 */
	public function should_resolve_no_date_for_an_open_start_or_a_specific_boundary( Window_Kind $kind, string $start_mode ): void {
		$rule        = Rule::from_array( [ 'start' => [ 'mode' => $start_mode ], 'end' => [ 'mode' => Rule::MODE_SPECIFIC ] ], $kind );
		$event_start = new DateTimeImmutable( '2027-06-10 19:00:00', new DateTimeZone( 'America/New_York' ) );

		$window = tribe( Sale_Window::class )->resolve( $rule, $event_start, $event_start->modify( '+2 hours' ) );

		$this->assertNull( $window->get_start() );
		$this->assertNull( $window->get_end() );
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: string, 2: array{start: array<string,int|string>, end: array<string,int|string>}, 3: string, 4: ?string}>
	 */
	public function event_in_its_timezone_provider(): Generator {
		yield 'sales' => [
			Window_Kind::sales(),
			'2027-06-24 19:00:00',
			[
				'start' => [
					'mode'   => 'relative',
					'value'  => 2,
					'unit'   => WEEK_IN_SECONDS,
					'anchor' => 'start',
				],
				'end'   => [ 'mode' => 'default' ],
			],
			'-2 weeks',
			'+0 days',
		];
		// 21:00 in New York is already the next day in UTC, so a date read in UTC would be a day late.
		yield 'sale price: an evening event start' => [
			Window_Kind::sale_price(),
			'2027-06-10 21:00:00',
			[
				'start' => [
					'mode'  => 'relative',
					'value' => 1,
					'unit'  => DAY_IN_SECONDS,
				],
				'end'   => [ 'mode' => 'specific' ],
			],
			'-1 day',
			null,
		];
	}

	/**
	 * @test
	 * @dataProvider event_in_its_timezone_provider
	 */
	public function should_resolve_a_rule_for_an_event_in_the_event_timezone( Window_Kind $kind, string $local_start, array $data, string $start_offset, ?string $end_offset ): void {
		$timezone    = new DateTimeZone( 'America/New_York' );
		$event_start = new DateTimeImmutable( $local_start, $timezone );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ), $timezone->getName() );
		$rule        = Rule::from_array( $data, $kind );

		$window = tribe( Sale_Window::class )->resolve_for_event( $rule, $event_id );

		$this->assert_date( $event_start->modify( $start_offset )->format( 'Y-m-d H:i:s' ), $timezone->getName(), $window->get_start() );
		$this->assert_date( null === $end_offset ? null : $event_start->modify( $end_offset )->format( 'Y-m-d H:i:s' ), $timezone->getName(), $window->get_end() );
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: string, 2: string}>
	 */
	public function rules_of_every_kind_provider(): Generator {
		yield 'sales' => [ Window_Kind::sales(), Rule::MODE_DEFAULT, Rule::MODE_DEFAULT ];
		yield 'sale price' => [ Window_Kind::sale_price(), Rule::MODE_NOW, Rule::MODE_SPECIFIC ];
	}

	/**
	 * @test
	 * @dataProvider rules_of_every_kind_provider
	 */
	public function should_not_resolve_a_rule_for_a_post_without_event_dates( Window_Kind $kind, string $start_mode, string $end_mode ): void {
		$rule = Rule::from_array( [ 'start' => [ 'mode' => $start_mode ], 'end' => [ 'mode' => $end_mode ] ], $kind );

		$this->assertNull( tribe( Sale_Window::class )->resolve_for_event( $rule, static::factory()->post->create() ) );
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: string, 2: bool}>
	 */
	public function now_start_provider(): Generator {
		yield 'a ticket start later than now' => [ Window_Kind::sales(), '+1 week', true ];
		yield 'a ticket start already past' => [ Window_Kind::sales(), '-1 week', false ];
		yield 'no ticket start' => [ Window_Kind::sales(), '', false ];
		yield 'sale price: a ticket start later than now' => [ Window_Kind::sale_price(), '+1 week', false ];
	}

	/**
	 * @test
	 * @dataProvider now_start_provider
	 */
	public function should_move_only_a_later_ticket_start_to_now_for_a_now_start( Window_Kind $kind, string $ticket_start_offset, bool $moved ): void {
		$now = new DateTimeImmutable( '2027-01-10 12:00:00', new DateTimeZone( 'UTC' ) );
		$this->freeze_time( $now );
		$event_id     = $this->create_event( '2027-06-24 19:00:00' );
		$rule         = Rule::from_array( [ 'start' => [ 'mode' => $kind->get_open_start_mode() ], 'end' => [ 'mode' => Rule::MODE_SPECIFIC ] ], $kind );
		$ticket_start = $ticket_start_offset ? $now->modify( $ticket_start_offset )->format( 'Y-m-d H:i:s' ) : '';

		$window = tribe( Sale_Window::class )->resolve_for_event( $rule, $event_id, $ticket_start );

		$this->assert_date( $moved ? $now->format( 'Y-m-d H:i:s' ) : null, 'UTC', $window->get_start() );
	}

	/**
	 * Creates an event that lasts three hours.
	 *
	 * @param string $start    The event start, in `Y-m-d H:i:s` format.
	 * @param string $timezone The event timezone.
	 *
	 * @return int The event post ID.
	 */
	private function create_event( string $start, string $timezone = 'UTC' ): int {
		return tribe_events()->set_args(
			[
				'title'      => 'Relative Sale Dates event',
				'status'     => 'publish',
				'start_date' => $start,
				'timezone'   => $timezone,
				'duration'   => 3 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
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
