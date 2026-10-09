<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use ActionScheduler_Action;
use ActionScheduler_Store;
use DateTimeImmutable;
use DateTimeZone;
use Generator;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use TEC\Tickets\RSVP\V2\Constants as RSVP_V2_Constants;
use TEC\Tickets\Ticket_Actions;
use Tribe\Tests\Traits\With_Clock_Mock;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Tickets__Tickets as Tickets;

class Ticket_Save_Test extends Controller_Test_Case {
	use Ticket_Maker;
	use With_Clock_Mock;
	use With_Tickets_Commerce;

	protected $controller_class = Controller::class;

	/**
	 * @before
	 */
	public function register_controller(): void {
		$this->make_controller()->register();
	}

	/**
	 * @test
	 */
	public function should_store_the_rule_and_write_the_resolved_start(): void {
		$event_start = new DateTimeImmutable( '2027-06-24 19:00:00' );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$rule        = [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => [ 'mode' => 'default' ] ];

		$ticket_id = $this->create_tc_ticket( $event_id, 1, [ 'relative_sale_dates' => wp_json_encode( $rule ) ] );

		$expected_start = $event_start->modify( '-2 weeks' );
		$this->assertSame( $rule, $this->get_stored_rule( $ticket_id ) );
		$this->assertSame( [ $expected_start->format( 'Y-m-d' ), $expected_start->format( 'H:i:s' ) ], $this->get_ticket_start( $ticket_id ) );
		$this->assertSame( [ $event_start->format( 'Y-m-d' ), $event_start->format( 'H:i:s' ) ], $this->get_ticket_end( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_write_the_resolved_dates_in_the_event_timezone(): void {
		/*
		 * Three days before 02:30 is 02:30 on the day New York springs forward, a time its clocks skip, so sales open
		 * at 03:30. Reading the event start in a timezone without that clock change would give 02:30.
		 */
		$event_id = $this->create_event( '2027-03-17 02:30:00', 'America/New_York' );
		$rule     = [ 'start' => $this->relative( 3, DAY_IN_SECONDS ), 'end' => [ 'mode' => 'default' ] ];

		$ticket_id = $this->create_tc_ticket( $event_id, 1, [ 'relative_sale_dates' => wp_json_encode( $rule ) ] );

		$this->assertSame( [ '2027-03-14', '03:30:00' ], $this->get_ticket_start( $ticket_id ) );
		$this->assertSame( [ '2027-03-17', '02:30:00' ], $this->get_ticket_end( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_keep_the_resolved_end_when_the_end_date_is_empty(): void {
		$event_start = '2027-06-24 19:00:00';
		$event_id    = $this->create_event( $event_start );
		$rule        = [ 'start' => [ 'mode' => 'default' ], 'end' => $this->relative( 1, DAY_IN_SECONDS ) ];

		$ticket_id = $this->create_tc_ticket(
			$event_id,
			1,
			[
				'relative_sale_dates' => wp_json_encode( $rule ),
				'ticket_end_date'     => '',
				'ticket_end_time'     => '',
			]
		);

		$expected = ( new DateTimeImmutable( $event_start ) )->modify( '-1 day' );
		$this->assertSame( [ $expected->format( 'Y-m-d' ), $expected->format( 'H:i:s' ) ], $this->get_ticket_end( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_keep_the_resolved_start_when_the_start_date_is_empty(): void {
		$event_start = '2027-06-24 19:00:00';
		$event_id    = $this->create_event( $event_start );
		$rule        = [ 'start' => $this->relative( 3, DAY_IN_SECONDS ), 'end' => [ 'mode' => 'default' ] ];

		$ticket_id = $this->create_tc_ticket(
			$event_id,
			1,
			[
				'relative_sale_dates' => wp_json_encode( $rule ),
				'ticket_start_date'   => '',
				'ticket_start_time'   => '',
			]
		);

		$expected = ( new DateTimeImmutable( $event_start ) )->modify( '-3 days' );
		$this->assertSame( [ $expected->format( 'Y-m-d' ), $expected->format( 'H:i:s' ) ], $this->get_ticket_start( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_keep_the_submitted_date_of_a_specific_end(): void {
		$event_id = $this->create_event( '2027-06-24 19:00:00' );
		$rule     = [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => [ 'mode' => 'specific' ] ];
		$end_date = '2027-06-20';
		$end_time = '12:00:00';

		$ticket_id = $this->create_tc_ticket(
			$event_id,
			1,
			[
				'relative_sale_dates' => wp_json_encode( $rule ),
				'ticket_end_date'     => $end_date,
				'ticket_end_time'     => $end_time,
			]
		);

		$this->assertSame( [ $end_date, $end_time ], $this->get_ticket_end( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_schedule_one_sales_action_per_end_at_the_resolved_dates(): void {
		// Ticket_Actions schedules nothing for a sales window that has already ended.
		$event_start = new DateTimeImmutable( ( new DateTimeImmutable( '+1 year' ) )->format( 'Y-m-d H:00:00' ) );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$rule        = [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => $this->relative( 1, DAY_IN_SECONDS ) ];

		$ticket_id = $this->create_tc_ticket(
			$event_id,
			1,
			[
				'relative_sale_dates' => wp_json_encode( $rule ),
				'ticket_end_date'     => '',
				'ticket_end_time'     => '',
			]
		);

		// Ticket_Actions schedules each action 30 minutes ahead of the date it announces.
		$lead_time = 30 * MINUTE_IN_SECONDS;
		$this->assertSame(
			[ $event_start->modify( '-2 weeks' )->getTimestamp() - $lead_time ],
			$this->get_scheduled_timestamps( Ticket_Actions::TICKET_START_SALES_HOOK, $ticket_id )
		);
		$this->assertSame(
			[ $event_start->modify( '-1 day' )->getTimestamp() - $lead_time ],
			$this->get_scheduled_timestamps( Ticket_Actions::TICKET_END_SALES_HOOK, $ticket_id )
		);
	}

	/**
	 * The rule used here opens sales 2 weeks before the event starts and closes them 2 hours before.
	 *
	 * @return Generator<string,array{0: string, 1: bool, 2: bool, 3: bool}>
	 */
	public function sales_window_moment_provider(): Generator {
		yield 'before the window' => [ '+3 weeks', false, true, false ];
		yield 'during the window' => [ '+1 week', true, false, false ];
		yield 'after the window' => [ '+1 hour', false, false, true ];
	}

	/**
	 * The front end reads the real clock through a `DateTime` subclass the clock mock cannot reach, so the event is
	 * placed relative to now instead.
	 *
	 * @test
	 * @dataProvider sales_window_moment_provider
	 */
	public function should_sell_a_ruled_ticket_only_during_its_resolved_window( string $event_start_from_now, bool $on_sale, bool $sale_future, bool $sale_past ): void {
		$event_start = new DateTimeImmutable( $event_start_from_now, new DateTimeZone( 'America/New_York' ) );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ), 'America/New_York' );
		$rule        = [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => $this->relative( 2, HOUR_IN_SECONDS ) ];
		$ticket_id   = $this->create_tc_ticket( $event_id, 1, [ 'relative_sale_dates' => wp_json_encode( $rule ) ] );

		$ticket = Tickets::load_ticket_object( $ticket_id );
		$block  = tribe( 'tickets.editor.blocks.tickets' );

		$this->assertSame( $on_sale, $ticket->date_in_range() );
		$this->assertSame( $on_sale, tribe_events_ticket_is_on_sale( $ticket ) );
		$this->assertCount( $on_sale ? 1 : 0, $block->get_tickets_on_sale( [ $ticket ] ) );
		$this->assertSame( $sale_future, $block->get_is_sale_future( [ $ticket ] ) );
		$this->assertSame( $sale_past, $block->get_is_sale_past( [ $ticket ] ) );
	}

	/**
	 * @return Generator<string,array{0: callable(self, array<string,string>): int}>
	 */
	public function out_of_scope_ticket_provider(): Generator {
		yield 'RSVP on an event' => [
			static fn( self $test, array $data ): int => $test->create_ticket( 'tickets.rsvp', $test->create_event( '2027-06-24 19:00:00' ), 0, $data ),
		];

		yield 'Tickets Commerce ticket on a page' => [
			static fn( self $test, array $data ): int => $test->create_tc_ticket( static::factory()->post->create( [ 'post_type' => 'page' ] ), 1, $data ),
		];

		yield 'Series Pass' => [
			static fn( self $test, array $data ): int => $test->create_tc_ticket(
				$test->create_event( '2027-06-24 19:00:00' ),
				1,
				array_merge( $data, [ 'ticket_type' => Series_Passes::TICKET_TYPE ] )
			),
		];

		yield 'RSVP V2 on an event' => [
			static fn( self $test, array $data ): int => $test->create_tc_ticket(
				$test->create_event( '2027-06-24 19:00:00' ),
				0,
				array_merge( $data, [ 'ticket_type' => RSVP_V2_Constants::TC_RSVP_TYPE ] )
			),
		];
	}

	/**
	 * @test
	 * @dataProvider out_of_scope_ticket_provider
	 */
	public function should_ignore_the_rule_of_a_ticket_out_of_scope( callable $create ): void {
		$data = [
			'relative_sale_dates' => wp_json_encode(
				[ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => $this->relative( 1, DAY_IN_SECONDS ) ]
			),
			'ticket_start_date'   => '2027-01-02',
			'ticket_start_time'   => '08:00:00',
			'ticket_end_date'     => '2027-03-01',
			'ticket_end_time'     => '20:00:00',
		];

		$ticket_id = $create( $this, $data );

		$this->assertSame( '', get_post_meta( $ticket_id, Rule_Store::META_KEY, true ) );
		// RSVPs store the date and the time together in the date meta.
		$this->assertSame( "{$data['ticket_start_date']} {$data['ticket_start_time']}", trim( implode( ' ', $this->get_ticket_start( $ticket_id ) ) ) );
		$this->assertSame( "{$data['ticket_end_date']} {$data['ticket_end_time']}", trim( implode( ' ', $this->get_ticket_end( $ticket_id ) ) ) );
	}

	/**
	 * @return Generator<string,array{0: null|string}>
	 */
	public function removed_rule_provider(): Generator {
		yield 'null' => [ null ];
		yield 'empty string' => [ '' ];
	}

	/**
	 * @test
	 * @dataProvider removed_rule_provider
	 */
	public function should_remove_the_rule_when_the_ticket_is_saved_with_an_empty_one( ?string $removed ): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$rule      = [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => [ 'mode' => 'default' ] ];
		$ticket_id = $this->create_tc_ticket( $event_id, 1, [ 'relative_sale_dates' => wp_json_encode( $rule ) ] );
		$this->assertSame( $rule, $this->get_stored_rule( $ticket_id ) );

		$this->update_ticket( $ticket_id, [ 'relative_sale_dates' => $removed ] );

		$this->assertSame( '', get_post_meta( $ticket_id, Rule_Store::META_KEY, true ) );
	}

	/**
	 * @return Generator<string,array{0: array<string,string>}>
	 */
	public function rule_kept_provider(): Generator {
		yield 'rule not sent' => [ [] ];
		yield 'invalid rule sent' => [ [ 'relative_sale_dates' => '{"start":' ] ];
	}

	/**
	 * @test
	 * @dataProvider rule_kept_provider
	 */
	public function should_keep_and_apply_the_stored_rule_when_the_ticket_is_saved_without_a_valid_one( array $rule_data ): void {
		$event_start = new DateTimeImmutable( '2027-06-24 19:00:00' );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$rule        = [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => $this->relative( 1, DAY_IN_SECONDS ) ];
		$ticket_id   = $this->create_tc_ticket( $event_id, 1, [ 'relative_sale_dates' => wp_json_encode( $rule ) ] );

		$this->update_ticket(
			$ticket_id,
			array_merge(
				[
					'ticket_start_date' => '2027-01-02',
					'ticket_start_time' => '08:00:00',
					'ticket_end_date'   => '2027-03-01',
					'ticket_end_time'   => '20:00:00',
				],
				$rule_data
			)
		);

		$expected_start = $event_start->modify( '-2 weeks' );
		$expected_end   = $event_start->modify( '-1 day' );
		$this->assertSame( $rule, $this->get_stored_rule( $ticket_id ) );
		$this->assertSame( [ $expected_start->format( 'Y-m-d' ), $expected_start->format( 'H:i:s' ) ], $this->get_ticket_start( $ticket_id ) );
		$this->assertSame( [ $expected_end->format( 'Y-m-d' ), $expected_end->format( 'H:i:s' ) ], $this->get_ticket_end( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_put_an_existing_ticket_on_sale_when_its_start_switches_to_now(): void {
		$now = new DateTimeImmutable( '2027-01-10 12:00:00', new DateTimeZone( 'UTC' ) );
		$this->freeze_time( $now );
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket(
			$event_id,
			1,
			[ 'relative_sale_dates' => wp_json_encode( [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => [ 'mode' => 'default' ] ] ) ]
		);
		[ $future_date, $future_time ] = $this->get_ticket_start( $ticket_id );

		// The block editor sends the start it loaded with the ticket, which the old rule had put in the future.
		$this->update_ticket(
			$ticket_id,
			[
				'relative_sale_dates' => wp_json_encode( [ 'start' => [ 'mode' => 'default' ], 'end' => [ 'mode' => 'default' ] ] ),
				'ticket_start_date'   => $future_date,
				'ticket_start_time'   => $future_time,
			]
		);

		$this->assertSame( [ $now->format( 'Y-m-d' ), $now->format( 'H:i:s' ) ], $this->get_ticket_start( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_keep_the_start_of_a_now_ticket_already_on_sale_when_it_is_saved_again(): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$rule      = [ 'start' => [ 'mode' => 'default' ], 'end' => [ 'mode' => 'default' ] ];
		$ticket_id = $this->create_tc_ticket( $event_id, 1, [ 'relative_sale_dates' => wp_json_encode( $rule ) ] );
		$start     = $this->get_ticket_start( $ticket_id );

		$this->update_ticket( $ticket_id, [ 'ticket_name' => 'Renamed' ] );

		$this->assertSame( $start, $this->get_ticket_start( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_let_the_end_follow_the_event_again_when_the_rule_is_removed_without_an_end_date(): void {
		$event_id        = $this->create_event( '2027-06-24 19:00:00' );
		$rule            = [ 'start' => [ 'mode' => 'default' ], 'end' => $this->relative( 1, DAY_IN_SECONDS ) ];
		$ticket_id       = $this->create_tc_ticket( $event_id, 1, [ 'relative_sale_dates' => wp_json_encode( $rule ) ] );
		$tickets_handler = tribe( 'tickets.handler' );
		$this->assertTrue( $tickets_handler->has_manual_update( $ticket_id, $tickets_handler->key_end_date ) );

		$this->update_ticket(
			$ticket_id,
			[
				'relative_sale_dates' => '',
				'ticket_end_date'     => '',
				'ticket_end_time'     => '',
			]
		);

		$this->assertFalse( $tickets_handler->has_manual_update( $ticket_id, $tickets_handler->key_end_date ) );
	}

	/**
	 * @test
	 */
	public function should_keep_the_end_marker_when_the_rule_is_removed_with_an_end_date(): void {
		$event_id        = $this->create_event( '2027-06-24 19:00:00' );
		$rule            = [ 'start' => [ 'mode' => 'default' ], 'end' => $this->relative( 1, DAY_IN_SECONDS ) ];
		$ticket_id       = $this->create_tc_ticket( $event_id, 1, [ 'relative_sale_dates' => wp_json_encode( $rule ) ] );
		$tickets_handler = tribe( 'tickets.handler' );

		$this->update_ticket( $ticket_id, [ 'relative_sale_dates' => '' ] );

		$this->assertTrue( $tickets_handler->has_manual_update( $ticket_id, $tickets_handler->key_end_date ) );
	}

	/**
	 * @test
	 */
	public function should_keep_the_submitted_dates_of_a_ticket_without_a_rule(): void {
		$event_id = $this->create_event( '2027-06-24 19:00:00' );
		$data     = [
			'ticket_start_date' => '2027-01-02',
			'ticket_start_time' => '08:00:00',
			'ticket_end_date'   => '2027-03-01',
			'ticket_end_time'   => '20:00:00',
		];

		$ticket_id = $this->create_tc_ticket( $event_id, 1, $data );

		$this->assertSame( '', get_post_meta( $ticket_id, Rule_Store::META_KEY, true ) );
		$this->assertSame( [ $data['ticket_start_date'], $data['ticket_start_time'] ], $this->get_ticket_start( $ticket_id ) );
		$this->assertSame( [ $data['ticket_end_date'], $data['ticket_end_time'] ], $this->get_ticket_end( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_default_the_dates_of_a_ticket_without_a_rule_as_before(): void {
		$event_start = '2027-06-24 19:00:00';
		$event_id    = $this->create_event( $event_start );

		$ticket_id = $this->create_tc_ticket(
			$event_id,
			1,
			[
				'ticket_start_date' => '',
				'ticket_start_time' => '',
				'ticket_end_date'   => '',
				'ticket_end_time'   => '',
			]
		);

		$post_date = ( new DateTimeImmutable( get_post( $event_id )->post_date ) )->format( 'Y-m-d 00:00:00' );
		$this->assertSame( $post_date, get_post_meta( $ticket_id, '_ticket_start_date', true ) );
		$this->assertSame( $event_start, get_post_meta( $ticket_id, '_ticket_end_date', true ) );
	}

	/**
	 * @param int $value The number of units before the event start.
	 * @param int $unit  The unit, one of the `*_IN_SECONDS` constants from `MINUTE_IN_SECONDS` to `WEEK_IN_SECONDS`.
	 *
	 * @return array{mode: string, value: int, unit: int, anchor: string} A relative edge of the window, anchored on the event start.
	 */
	private function relative( int $value, int $unit ): array {
		return [
			'mode'   => 'relative',
			'value'  => $value,
			'unit'   => $unit,
			'anchor' => 'start',
		];
	}

	/**
	 * Creates an event lasting three hours.
	 *
	 * @param string $start    The event start, local `Y-m-d H:i:s`.
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
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return array{start: array{mode: string, value?: int, unit?: int, anchor?: string}, end: array{mode: string, value?: int, unit?: int, anchor?: string}}|null The stored rule.
	 */
	private function get_stored_rule( int $ticket_id ): ?array {
		return json_decode( get_post_meta( $ticket_id, Rule_Store::META_KEY, true ), true );
	}

	/**
	 * @param string $hook      The sales action hook.
	 * @param int    $ticket_id The ticket post ID.
	 *
	 * @return int[] The timestamps the pending actions of the ticket are scheduled at.
	 */
	private function get_scheduled_timestamps( string $hook, int $ticket_id ): array {
		$actions = as_get_scheduled_actions(
			[
				'hook'   => $hook,
				'args'   => [ $ticket_id ],
				'group'  => Ticket_Actions::AS_TICKET_ACTIONS_GROUP,
				'status' => ActionScheduler_Store::STATUS_PENDING,
			],
			OBJECT
		);

		return array_values( array_map( static fn( ActionScheduler_Action $action ): int => $action->get_schedule()->get_date()->getTimestamp(), $actions ) );
	}

	/**
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return array{0: string, 1: string} The stored sales start date and time.
	 */
	private function get_ticket_start( int $ticket_id ): array {
		return [ get_post_meta( $ticket_id, '_ticket_start_date', true ), get_post_meta( $ticket_id, '_ticket_start_time', true ) ];
	}

	/**
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return array{0: string, 1: string} The stored sales end date and time.
	 */
	private function get_ticket_end( int $ticket_id ): array {
		return [ get_post_meta( $ticket_id, '_ticket_end_date', true ), get_post_meta( $ticket_id, '_ticket_end_time', true ) ];
	}
}
