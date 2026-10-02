<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;
use DateTime;
use DateTimeImmutable;
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
	 * @test
	 */
	public function should_resolve_in_the_timezone_of_the_event_start(): void {
		$rule        = Rule::from_array(
			[
				'start' => [ 'mode' => 'default' ],
				'end'   => [
					'mode'   => 'relative',
					'value'  => 1,
					'unit'   => HOUR_IN_SECONDS,
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
	 * @test
	 */
	public function should_resolve_a_rule_for_an_event_in_the_event_timezone(): void {
		$timezone    = new DateTimeZone( 'America/New_York' );
		$event_start = new DateTimeImmutable( '2027-06-24 19:00:00', $timezone );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ), $timezone->getName() );
		$rule        = Rule::from_array(
			[
				'start' => [
					'mode'   => 'relative',
					'value'  => 2,
					'unit'   => WEEK_IN_SECONDS,
					'anchor' => 'start',
				],
				'end'   => [ 'mode' => 'default' ],
			]
		);

		$window = tribe( Sale_Window::class )->resolve_for_event( $rule, $event_id );

		$this->assert_date( $event_start->modify( '-2 weeks' )->format( 'Y-m-d H:i:s' ), $timezone->getName(), $window->get_start() );
		$this->assert_date( $event_start->format( 'Y-m-d H:i:s' ), $timezone->getName(), $window->get_end() );
	}

	/**
	 * @test
	 */
	public function should_not_resolve_a_rule_for_a_post_without_event_dates(): void {
		$rule = Rule::from_array( [ 'start' => [ 'mode' => 'default' ], 'end' => [ 'mode' => 'default' ] ] );

		$this->assertNull( tribe( Sale_Window::class )->resolve_for_event( $rule, static::factory()->post->create() ) );
	}

	/**
	 * @return Generator<string,array{0: string, 1: bool}>
	 */
	public function now_start_provider(): Generator {
		yield 'a ticket start later than now' => [ '+1 week', true ];
		yield 'a ticket start already past' => [ '-1 week', false ];
		yield 'no ticket start' => [ '', false ];
	}

	/**
	 * @test
	 * @dataProvider now_start_provider
	 */
	public function should_move_only_a_later_ticket_start_to_now_for_a_now_start( string $ticket_start_offset, bool $moved ): void {
		$now = new DateTimeImmutable( '2027-01-10 12:00:00', new DateTimeZone( 'UTC' ) );
		$this->freeze_time( $now );
		$event_id     = $this->create_event( '2027-06-24 19:00:00' );
		$rule         = Rule::from_array( [ 'start' => [ 'mode' => 'default' ], 'end' => [ 'mode' => 'default' ] ] );
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
