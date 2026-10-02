<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;
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
		$event_id = $this->create_event_with_dates( $case['event_start'], $case['event_end'], $case['timezone'] );

		$window = tribe( Sale_Window::class )->resolve_for_event( Rule::from_array( $case['rule'] ), $event_id );

		$expected = $case['expected'];
		$this->assert_date( $expected['start_local'], $case['timezone'], $window->get_start() );
		$this->assert_date( $expected['start_utc'], 'UTC', $this->to_utc( $window->get_start() ) );
		$this->assert_date( $expected['end_local'], $case['timezone'], $window->get_end() );
		$this->assert_date( $expected['end_utc'], 'UTC', $this->to_utc( $window->get_end() ) );

		if ( null === $expected['valid'] ) {
			$this->expectException( RuntimeException::class );
		}

		$this->assertSame( $expected['valid'], $window->is_valid() );
	}

	/**
	 * @return Generator<string,array{0: array{name: string, rule: array{start: array{mode: string, value?: int, unit?: int, anchor?: string}, end: array{mode: string, value?: int, unit?: int, anchor?: string}}, timezone: string, event_start: string, event_end: string}, 1: string}>
	 */
	public function relative_boundaries_provider(): Generator {
		foreach ( $this->fixtures_provider() as $name => [ $case ] ) {
			foreach ( [ 'start', 'end' ] as $key ) {
				if ( 'relative' === $case['rule'][ $key ]['mode'] ) {
					yield "{$name}: {$key}" => [ $case, $key ];
				}
			}
		}
	}

	/**
	 * @test
	 * @dataProvider relative_boundaries_provider
	 */
	public function should_resolve_a_relative_boundary_before_its_anchor( array $case, string $key ): void {
		$timezone = new DateTimeZone( $case['timezone'] );
		$anchors  = [
			'start' => new DateTimeImmutable( $case['event_start'], $timezone ),
			'end'   => new DateTimeImmutable( $case['event_end'], $timezone ),
		];
		$event_id = $this->create_event_with_dates( $case['event_start'], $case['event_end'], $case['timezone'] );

		$window   = tribe( Sale_Window::class )->resolve_for_event( Rule::from_array( $case['rule'] ), $event_id );
		$resolved = 'start' === $key ? $window->get_start() : $window->get_end();

		$this->assertLessThan(
			$anchors[ $case['rule'][ $key ]['anchor'] ]->getTimestamp(),
			$resolved->getTimestamp()
		);
	}

	/**
	 * @test
	 */
	public function should_resolve_in_the_timezone_of_the_event(): void {
		$rule     = Rule::from_array(
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
		$event_id = $this->create_event_with_dates( '2027-06-10 19:00:00', '2027-06-10 21:00:00', 'America/New_York' );

		$window = tribe( Sale_Window::class )->resolve_for_event( $rule, $event_id );

		$this->assert_date( '2027-06-10 20:00:00', 'America/New_York', $window->get_end() );
		$this->assert_date( '2027-06-11 00:00:00', 'UTC', $this->to_utc( $window->get_end() ) );
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
	public function should_resolve_an_end_anchor_at_the_last_second_of_an_all_day_event(): void {
		$event_end = new DateTimeImmutable( '2027-06-10 23:59:59', new DateTimeZone( 'America/New_York' ) );
		$event_id  = $this->create_event_with_dates( '2027-06-10 00:00:00', $event_end->format( 'Y-m-d H:i:s' ), 'America/New_York' );
		$rule      = Rule::from_array(
			[
				'start' => [ 'mode' => 'default' ],
				'end'   => [
					'mode'   => 'relative',
					'value'  => 1,
					'unit'   => DAY_IN_SECONDS,
					'anchor' => 'end',
				],
			]
		);

		$window = tribe( Sale_Window::class )->resolve_for_event( $rule, $event_id );

		$this->assert_date( $event_end->modify( '-1 day' )->format( 'Y-m-d H:i:s' ), 'America/New_York', $window->get_end() );
	}

	/**
	 * @test
	 */
	public function should_not_resolve_a_rule_for_a_post_without_event_dates(): void {
		$rule = Rule::from_array( [ 'start' => [ 'mode' => 'default' ], 'end' => [ 'mode' => 'default' ] ] );

		$this->assertNull( tribe( Sale_Window::class )->resolve_for_event( $rule, static::factory()->post->create() ) );
	}

	/**
	 * @return Generator<string,array{0: mixed, 1: mixed}>
	 */
	public function unreadable_event_dates_provider(): Generator {
		yield 'a start that is not a date' => [ 'not a date', '2027-06-10 21:00:00' ];
		yield 'an empty end' => [ '2027-06-10 19:00:00', '' ];
		yield 'a start stored as an array' => [ [ '2027-06-10 19:00:00' ], '2027-06-10 21:00:00' ];
	}

	/**
	 * @test
	 * @dataProvider unreadable_event_dates_provider
	 *
	 * @param mixed $start The stored event start.
	 * @param mixed $end   The stored event end.
	 */
	public function should_not_resolve_a_rule_for_unreadable_event_dates( $start, $end ): void {
		$event_id = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		update_post_meta( $event_id, '_EventStartDate', $start );
		update_post_meta( $event_id, '_EventEndDate', $end );
		$rule = Rule::from_array( [ 'start' => [ 'mode' => 'default' ], 'end' => [ 'mode' => 'default' ] ] );

		$this->assertNull( tribe( Sale_Window::class )->resolve_for_event( $rule, $event_id ) );
	}

	/**
	 * @test
	 */
	public function should_read_an_event_without_a_timezone_in_the_site_timezone(): void {
		update_option( 'timezone_string', 'Europe/Lisbon' );
		$event_id = $this->create_event_with_dates( '2027-06-10 19:00:00', '2027-06-10 21:00:00', '' );
		$rule     = Rule::from_array( [ 'start' => [ 'mode' => 'default' ], 'end' => [ 'mode' => 'default' ] ] );

		$window = tribe( Sale_Window::class )->resolve_for_event( $rule, $event_id );

		$this->assert_date( '2027-06-10 19:00:00', 'Europe/Lisbon', $window->get_end() );
	}

	/**
	 * @return Generator<string,array{0: string, 1: bool}>
	 */
	public function now_start_provider(): Generator {
		yield 'a ticket start later than now' => [ '+1 week', true ];
		yield 'a ticket start equal to now' => [ '+0 seconds', false ];
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
	 * Creates an event post whose date metas hold the given wall-clock values as they are.
	 *
	 * The fixture dates fall on clock changes, which the events repository would move; the resolver reads the metas.
	 *
	 * @param string $start    The event start, in `Y-m-d H:i:s` format.
	 * @param string $end      The event end, in `Y-m-d H:i:s` format.
	 * @param string $timezone The event timezone, or `''` for none.
	 *
	 * @return int The event post ID.
	 */
	private function create_event_with_dates( string $start, string $end, string $timezone ): int {
		$event_id = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		update_post_meta( $event_id, '_EventStartDate', $start );
		update_post_meta( $event_id, '_EventEndDate', $end );
		update_post_meta( $event_id, '_EventTimezone', $timezone );

		return $event_id;
	}

	/**
	 * Returns a resolved date in UTC.
	 *
	 * @param DateTimeImmutable|null $date The resolved date.
	 *
	 * @return DateTimeImmutable|null The same instant in UTC, or `null` for no date.
	 */
	private function to_utc( ?DateTimeImmutable $date ): ?DateTimeImmutable {
		return $date ? $date->setTimezone( new DateTimeZone( 'UTC' ) ) : null;
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

	/**
	 * @test
	 */
	public function should_resolve_an_event_from_the_metas_it_names_as_event_date_metas(): void {
		$event_id = $this->create_event( '2027-06-24 19:00:00', 'America/New_York' );
		$rule     = Rule::from_array( [ 'start' => [ 'mode' => 'default' ], 'end' => [ 'mode' => 'default' ] ] );
		$this->assertNotNull( tribe( Sale_Window::class )->resolve_for_event( $rule, $event_id ) );

		foreach ( Sale_Window::EVENT_DATE_META_KEYS as $meta_key ) {
			delete_post_meta( $event_id, $meta_key );
		}

		$this->assertNull( tribe( Sale_Window::class )->resolve_for_event( $rule, $event_id ) );
	}
}
