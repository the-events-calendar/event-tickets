<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;
use DateTimeImmutable;
use DateTimeZone;
use Generator;
use TEC\Tickets\Commerce\Ticket;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;

class Ticket_Dates_Test extends WPTestCase {
	use Relative_Sale_Dates_Maker;
	use Ticket_Maker;
	use With_Tickets_Commerce;

	/**
	 * The sale price start every ticket of these tests is created with.
	 *
	 * @var string
	 */
	private const SALE_PRICE_START = '2027-01-04';

	/**
	 * The sale price end every ticket of these tests is created with.
	 *
	 * @var string
	 */
	private const SALE_PRICE_END = '2027-01-11';

	/**
	 * @return Generator<string,array{0: Window_Kind}>
	 */
	public function kinds_provider(): Generator {
		foreach ( Window_Kind::all() as $kind ) {
			yield $kind->get_id() => [ $kind ];
		}
	}

	/**
	 * @test
	 */
	public function should_leave_the_ends_the_rule_does_not_resolve_as_they_are(): void {
		$event_start = new DateTimeImmutable( '2027-06-24 19:00:00' );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_tc_ticket( $event_id );
		$ticket_end  = $this->get_ticket_end( $ticket_id );
		$rule        = Rule::from_array( [ 'start' => $this->relative( 3, DAY_IN_SECONDS ), 'end' => [ 'mode' => 'specific' ] ] );

		tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, $rule );

		$sales_start = $event_start->modify( '-3 days' );
		$this->assertSame( [ $sales_start->format( 'Y-m-d' ), $sales_start->format( 'H:i:s' ) ], $this->get_ticket_start( $ticket_id ) );
		$this->assertSame( $ticket_end, $this->get_ticket_end( $ticket_id ) );
	}

	/**
	 * @test
	 * @dataProvider kinds_provider
	 */
	public function should_write_the_dates_a_relative_rule_resolves_to_in_the_kind_date_metas( Window_Kind $kind ): void {
		$event_start = new DateTimeImmutable( '2027-06-24 19:00:00' );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_sale_price_ticket( $event_id );
		$rule        = $this->get_relative_rule( $kind, 2, 1 );

		tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, $rule );

		$this->assertSame(
			[
				'start' => $this->format_for( $kind, 'start', $event_start->modify( '-2 weeks' ) ),
				'end'   => $this->format_for( $kind, 'end', $event_start->modify( '-1 week' ) ),
			],
			$this->get_dates( $kind, $ticket_id )
		);
	}

	/**
	 * An event at 21:00 in New York starts at 01:00 the next day in UTC, so a sale price boundary counted back from it
	 * falls on an evening whose day in UTC is the next one.
	 *
	 * @test
	 */
	public function should_write_the_sale_price_days_in_the_event_timezone(): void {
		$event_start = new DateTimeImmutable( '2027-06-24 21:00:00', new DateTimeZone( 'America/New_York' ) );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ), 'America/New_York' );
		$ticket_id   = $this->create_sale_price_ticket( $event_id );
		$kind        = Window_Kind::sale_price();
		$start       = $event_start->modify( '-2 weeks' );
		$end         = $event_start->modify( '-1 week' );
		$this->assertNotSame( $start->format( 'Y-m-d' ), $start->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d' ) );

		tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, $this->get_relative_rule( $kind, 2, 1 ) );

		$this->assertSame(
			[
				'start' => [ 'date' => $start->format( 'Y-m-d' ) ],
				'end'   => [ 'date' => $end->format( 'Y-m-d' ) ],
			],
			$this->get_dates( $kind, $ticket_id )
		);
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: string, 2: ?string}>
	 */
	public function start_without_a_date_provider(): Generator {
		yield 'sales: an open start keeps the ticket start' => [ Window_Kind::sales(), Rule::MODE_DEFAULT, null ];
		// An empty sale price start reads as started.
		yield 'sale price: a now start is written empty' => [ Window_Kind::sale_price(), Rule::MODE_NOW, '' ];
		yield 'sale price: a specific start keeps the ticket start' => [ Window_Kind::sale_price(), Rule::MODE_SPECIFIC, null ];
	}

	/**
	 * @test
	 * @dataProvider start_without_a_date_provider
	 */
	public function should_write_the_open_start_value_only_for_an_open_start( Window_Kind $kind, string $start_mode, ?string $written ): void {
		$event_id   = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id  = $this->create_sale_price_ticket( $event_id );
		$start_meta = $kind->get_date_metas()['start']['date'];
		$before     = get_post_meta( $ticket_id, $start_meta, true );
		$rule       = Rule::from_array( [ 'start' => [ 'mode' => $start_mode ], 'end' => [ 'mode' => Rule::MODE_SPECIFIC ] ], $kind );
		$this->assertNotSame( '', $before );

		tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, $rule );

		$this->assertSame( $written ?? $before, get_post_meta( $ticket_id, $start_meta, true ) );
	}

	/**
	 * @test
	 * @dataProvider kinds_provider
	 */
	public function should_write_nothing_for_a_post_without_event_dates( Window_Kind $kind ): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_sale_price_ticket( $post_id );
		$before    = $this->get_dates( $kind, $ticket_id );

		$changed = tribe( Ticket_Dates::class )->write( $ticket_id, $post_id, $this->get_relative_rule( $kind, 3, 1 ) );

		$this->assertFalse( $changed );
		$this->assertSame( $before, $this->get_dates( $kind, $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_write_nothing_for_a_kind_the_ticket_has_turned_off(): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_sale_price_ticket( $event_id, [ 'ticket_add_sale_price' => false ] );
		$kind      = Window_Kind::sale_price();

		$changed = tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, $this->get_relative_rule( $kind, 2, 1 ) );

		$this->assertFalse( $changed );
		$this->assertSame(
			[
				'start' => [ 'date' => '' ],
				'end'   => [ 'date' => '' ],
			],
			$this->get_dates( $kind, $ticket_id )
		);
	}

	/**
	 * @test
	 * @dataProvider kinds_provider
	 */
	public function should_report_whether_the_write_changed_a_date( Window_Kind $kind ): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_sale_price_ticket( $event_id );
		$rule      = $this->get_relative_rule( $kind, 2, 1 );

		$first  = tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, $rule );
		$second = tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, $rule );

		$this->assertTrue( $first );
		$this->assertFalse( $second );
	}

	/**
	 * The dates are wall-clock times in the event timezone, so a new timezone moves them even when the times stay.
	 *
	 * @test
	 */
	public function should_report_a_change_when_only_the_event_timezone_changes(): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket( $event_id );
		$rule      = Rule::from_array( [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => $this->relative( 1, DAY_IN_SECONDS ) ] );
		tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, $rule );
		update_post_meta( $event_id, '_EventTimezone', 'America/New_York' );

		$this->assertTrue( tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, $rule ) );
		$this->assertFalse( tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, $rule ) );
	}

	/**
	 * The timezone is kept so a timezone-only move reschedules the sales actions, which only the sales window has.
	 *
	 * @test
	 * @dataProvider kinds_provider
	 */
	public function should_keep_the_event_timezone_only_for_a_kind_with_sales_actions( Window_Kind $kind ): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00', 'America/New_York' );
		$ticket_id = $this->create_sale_price_ticket( $event_id );

		tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, $this->get_relative_rule( $kind, 2, 1 ) );

		$this->assertSame( $kind->owns_ticket_sales_dates() ? 'America/New_York' : '', get_post_meta( $ticket_id, Ticket_Dates::TIMEZONE_META_KEY, true ) );
	}

	/**
	 * Creates a Tickets Commerce ticket priced 20 with a sale price of 10 between the fixed sale price dates.
	 *
	 * @param int                       $post_id   The ticketed post ID.
	 * @param array<string,string|bool> $overrides The ticket data to override.
	 *
	 * @return int The ticket post ID.
	 */
	private function create_sale_price_ticket( int $post_id, array $overrides = [] ): int {
		return $this->create_tc_ticket(
			$post_id,
			20,
			array_merge(
				[
					'ticket_add_sale_price'  => 'on',
					'ticket_sale_price'      => 10,
					'ticket_sale_start_date' => self::SALE_PRICE_START,
					'ticket_sale_end_date'   => self::SALE_PRICE_END,
				],
				$overrides
			)
		);
	}

	/**
	 * @param Window_Kind $kind        The kind of window.
	 * @param int         $start_weeks How many weeks before the event start the window starts.
	 * @param int         $end_weeks   How many weeks before the event start the window ends.
	 *
	 * @return Rule A rule of the kind with both ends counted back from the event start.
	 */
	private function get_relative_rule( Window_Kind $kind, int $start_weeks, int $end_weeks ): Rule {
		$boundary = static fn( int $value ): array => array_merge(
			[
				'mode'  => Rule::MODE_RELATIVE,
				'value' => $value,
				'unit'  => WEEK_IN_SECONDS,
			],
			$kind->takes_anchor() ? [ 'anchor' => Rule::ANCHOR_START ] : []
		);

		return Rule::from_array(
			[
				'start' => $boundary( $start_weeks ),
				'end'   => $boundary( $end_weeks ),
			],
			$kind
		);
	}

	/**
	 * @param Window_Kind       $kind The kind of window.
	 * @param string            $end  The end of the window, `start` or `end`.
	 * @param DateTimeImmutable $date The date.
	 *
	 * @return array{date: string, time?: string} The date as the kind stores it.
	 */
	private function format_for( Window_Kind $kind, string $end, DateTimeImmutable $date ): array {
		$formatted = [ 'date' => $date->format( 'Y-m-d' ) ];

		if ( null !== $kind->get_date_metas()[ $end ]['time'] ) {
			$formatted['time'] = $date->format( 'H:i:s' );
		}

		return $formatted;
	}

	/**
	 * @param Window_Kind $kind      The kind of window.
	 * @param int         $ticket_id The ticket post ID.
	 *
	 * @return array{start: array{date: string, time?: string}, end: array{date: string, time?: string}} The stored dates of the kind.
	 */
	private function get_dates( Window_Kind $kind, int $ticket_id ): array {
		$dates = [];

		foreach ( $kind->get_date_metas() as $end => $metas ) {
			$dates[ $end ] = array_map(
				static fn( string $meta_key ): string => get_post_meta( $ticket_id, $meta_key, true ),
				array_filter( $metas )
			);
		}

		return $dates;
	}
}
