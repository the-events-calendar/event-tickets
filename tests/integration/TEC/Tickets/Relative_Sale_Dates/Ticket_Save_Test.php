<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use DateTimeImmutable;
use DateTimeZone;
use Generator;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use TEC\Tickets\RSVP\V2\Constants as RSVP_V2_Constants;
use TEC\Tickets\Ticket_Actions;
use Tribe\Tests\Traits\With_Clock_Mock;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets as Tickets;

class Ticket_Save_Test extends Controller_Test_Case {
	use Relative_Sale_Dates_Maker;
	use Ticket_Maker;
	use With_Clock_Mock;
	use With_Tickets_Commerce;

	/**
	 * The error shown for an invalid rule or a sales window that ends before it starts.
	 *
	 * @var string
	 */
	private const INVALID_WINDOW_MESSAGE = 'Ticket sales cannot end before they start. Please adjust the sales window.';

	/**
	 * The event start the sale price tests use, in UTC.
	 *
	 * @var string
	 */
	private const EVENT_START = '2027-06-24 19:00:00';

	/**
	 * The ticket data key that carries the sale price rule.
	 *
	 * @var string
	 */
	private const SALE_PRICE_DATA_KEY = 'ticket_sale_price_relative';

	/**
	 * The key the sale price rule is stored under, next to the sales window rule.
	 *
	 * @var string
	 */
	private const SALE_PRICE_STORE_KEY = 'sale_price';

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
	 * The event starts on 2027-06-24 at 19:00.
	 *
	 * @return Generator<string,array{0: array<string,string>}>
	 */
	public function invalid_sales_window_provider(): Generator {
		yield 'invalid rule' => [
			[ 'relative_sale_dates' => '{"start":{"mode":"relative","value":2,"unit":7,"anchor":"start"},"end":{"mode":"default"}}' ],
		];

		yield 'end before start' => [
			[ 'relative_sale_dates' => wp_json_encode( [ 'start' => $this->relative( 1, HOUR_IN_SECONDS ), 'end' => $this->relative( 2, HOUR_IN_SECONDS ) ] ) ],
		];

		yield 'specific end before the resolved start' => [
			[
				'relative_sale_dates' => wp_json_encode( [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => [ 'mode' => 'specific' ] ] ),
				'ticket_end_date'     => '2027-06-01',
				'ticket_end_time'     => '12:00:00',
			],
		];

		yield 'specific start without a date' => [
			[
				'relative_sale_dates' => wp_json_encode( [ 'start' => [ 'mode' => 'specific' ], 'end' => $this->relative( 2, HOUR_IN_SECONDS ) ] ),
				'ticket_start_date'   => '',
			],
		];

		yield 'specific end without a date' => [
			[
				'relative_sale_dates' => wp_json_encode( [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => [ 'mode' => 'specific' ] ] ),
				'ticket_end_date'     => '',
			],
		];

		yield 'specific start after the resolved end' => [
			[
				'relative_sale_dates' => wp_json_encode( [ 'start' => [ 'mode' => 'specific' ], 'end' => $this->relative( 2, HOUR_IN_SECONDS ) ] ),
				'ticket_start_date'   => '2027-06-24',
				'ticket_start_time'   => '18:00:00',
			],
		];
	}

	/**
	 * The rule ends the sales on 2027-06-17 at 19:00, a week before the event starts, and the Now start is sent as
	 * 2027-06-22 at 12:00, after that end.
	 *
	 * @return Generator<string,array{0: string, 1: bool}>
	 */
	public function default_start_submitted_after_the_resolved_end_provider(): Generator {
		// The save starts the sales now, before the end.
		yield 'the submitted start is still ahead' => [ '2027-01-10 12:00:00', true ];
		// The ticket is already on sale, so the save keeps the submitted start.
		yield 'the submitted start has passed' => [ '2027-06-23 12:00:00', false ];
	}

	/**
	 * @test
	 * @dataProvider default_start_submitted_after_the_resolved_end_provider
	 */
	public function should_judge_a_default_start_by_the_start_the_save_stores( string $now, bool $valid ): void {
		$this->freeze_time( new DateTimeImmutable( $now, new DateTimeZone( 'UTC' ) ) );
		$event_id = $this->create_event( '2027-06-24 19:00:00' );
		$data     = [
			'relative_sale_dates' => wp_json_encode( [ 'start' => [ 'mode' => 'default' ], 'end' => $this->relative( 1, WEEK_IN_SECONDS ) ] ),
			'ticket_start_date'   => '2027-06-22',
			'ticket_start_time'   => '12:00:00',
		];

		$result = apply_filters( 'tec_tickets_ticket_data_validation', true, $event_id, $data );

		$this->assertSame( $valid, true === $result );
	}

	/**
	 * @test
	 * @dataProvider invalid_sales_window_provider
	 */
	public function should_reject_ticket_data_with_an_invalid_sales_window( array $data ): void {
		$event_id = $this->create_event( '2027-06-24 19:00:00' );

		$result = apply_filters( 'tec_tickets_ticket_data_validation', true, $event_id, $data );

		$this->assertWPError( $result );
		$this->assertSame( self::INVALID_WINDOW_MESSAGE, $result->get_error_message() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	/**
	 * @test
	 */
	public function should_reject_a_specific_date_the_datepicker_format_cannot_read(): void {
		// 4 is the day-first `d/m/Y` datepicker format, and 31/02 is not a day it can turn into a date.
		add_filter( 'tribe_datepicker_format_index', static fn() => 4 );
		$event_id = $this->create_event( '2027-06-24 19:00:00' );
		$data     = [
			'relative_sale_dates' => wp_json_encode( [ 'start' => [ 'mode' => 'specific' ], 'end' => $this->relative( 2, HOUR_IN_SECONDS ) ] ),
			'ticket_start_date'   => '31/02/2027',
			'ticket_start_time'   => '12:00:00',
		];

		$result = apply_filters( 'tec_tickets_ticket_data_validation', true, $event_id, $data );

		$this->assertWPError( $result );
		$this->assertSame( self::INVALID_WINDOW_MESSAGE, $result->get_error_message() );
	}

	/**
	 * The rule ends the sales on 2027-06-17 at 19:00, a week before the event starts.
	 *
	 * @return Generator<string,array{0: string, 1: bool}>
	 */
	public function default_start_without_a_date_provider(): Generator {
		yield 'published before the end' => [ '2027-06-16 10:00:00', true ];
		// `ticket_add()` starts the sales at midnight of the day the event was published.
		yield 'published on the day the sales end, after they end' => [ '2027-06-17 20:00:00', true ];
		yield 'published after the end' => [ '2027-06-18 10:00:00', false ];
	}

	/**
	 * @test
	 * @dataProvider default_start_without_a_date_provider
	 */
	public function should_judge_a_default_start_sent_without_a_date_by_the_event_post_date( string $post_date, bool $valid ): void {
		$event_id = $this->create_event( '2027-06-24 19:00:00' );
		wp_update_post(
			[
				'ID'        => $event_id,
				'post_date' => $post_date,
			]
		);
		$data = [
			'relative_sale_dates' => wp_json_encode( [ 'start' => [ 'mode' => 'default' ], 'end' => $this->relative( 1, WEEK_IN_SECONDS ) ] ),
			'ticket_start_date'   => '',
		];

		$result = apply_filters( 'tec_tickets_ticket_data_validation', true, $event_id, $data );

		$this->assertSame( $valid, true === $result );
	}

	/**
	 * @test
	 */
	public function should_judge_a_save_without_a_rule_by_the_stored_rule(): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket(
			$event_id,
			1,
			[
				'relative_sale_dates' => wp_json_encode( [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => [ 'mode' => 'specific' ] ] ),
				'ticket_end_date'     => '2027-06-20',
				'ticket_end_time'     => '12:00:00',
			]
		);
		$data = [
			'ticket_id'       => $ticket_id,
			'ticket_end_date' => '2027-06-01',
			'ticket_end_time' => '12:00:00',
		];

		$kept    = apply_filters( 'tec_tickets_ticket_data_validation', true, $event_id, $data );
		$removed = apply_filters( 'tec_tickets_ticket_data_validation', true, $event_id, array_merge( $data, [ 'relative_sale_dates' => '' ] ) );

		$this->assertWPError( $kept );
		$this->assertSame( self::INVALID_WINDOW_MESSAGE, $kept->get_error_message() );
		$this->assertTrue( $removed );
	}

	/**
	 * @return Generator<string,array{0: string, 1: array<string,string>}>
	 */
	public function accepted_ticket_data_provider(): Generator {
		$end_before_start = wp_json_encode( [ 'start' => $this->relative( 1, HOUR_IN_SECONDS ), 'end' => $this->relative( 2, HOUR_IN_SECONDS ) ] );

		yield 'valid rule' => [
			'tribe_events',
			[ 'relative_sale_dates' => wp_json_encode( [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => $this->relative( 1, DAY_IN_SECONDS ) ] ) ],
		];

		yield 'no rule' => [ 'tribe_events', [ 'ticket_name' => 'No rule' ] ];

		yield 'ticket on a page' => [ 'page', [ 'relative_sale_dates' => $end_before_start ] ];

		yield 'RSVP' => [
			'tribe_events',
			[
				'relative_sale_dates' => $end_before_start,
				'ticket_provider'     => 'Tribe__Tickets__RSVP',
			],
		];

		yield 'Series Pass' => [
			'tribe_events',
			[
				'relative_sale_dates' => $end_before_start,
				'ticket_type'         => Series_Passes::TICKET_TYPE,
			],
		];
	}

	/**
	 * @test
	 * @dataProvider accepted_ticket_data_provider
	 */
	public function should_accept_ticket_data_without_an_invalid_sales_window_to_apply( string $post_type, array $data ): void {
		$post_id = 'tribe_events' === $post_type
			? $this->create_event( '2027-06-24 19:00:00' )
			: static::factory()->post->create( [ 'post_type' => $post_type ] );

		$this->assertTrue( apply_filters( 'tec_tickets_ticket_data_validation', true, $post_id, $data ) );
	}

	/**
	 * @test
	 */
	public function should_reject_through_the_classic_editor_a_window_that_ends_before_it_starts(): void {
		$event_id = $this->create_event( '2027-06-24 19:00:00' );

		$response = $this->send_classic_ticket_add(
			$event_id,
			[ 'relative_sale_dates' => wp_json_encode( [ 'start' => $this->relative( 1, HOUR_IN_SECONDS ), 'end' => $this->relative( 2, HOUR_IN_SECONDS ) ] ) ]
		);

		$this->assertSame(
			[
				'success' => false,
				'data'    => [ 'message' => self::INVALID_WINDOW_MESSAGE ],
			],
			$response
		);
		$this->assertSame( [], tribe( Module::class )->get_tickets_ids( $event_id ) );
	}

	/**
	 * @test
	 */
	public function should_not_change_a_ticket_the_classic_editor_saves_with_an_invalid_rule(): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket( $event_id );
		$name      = get_post( $ticket_id )->post_title;

		$response = $this->send_classic_ticket_add(
			$event_id,
			[
				'ticket_id'           => $ticket_id,
				'ticket_name'         => "{$name} renamed",
				'relative_sale_dates' => '{"start":{"mode":"relative","value":2,"unit":7,"anchor":"start"},"end":{"mode":"default"}}',
			]
		);

		$this->assertFalse( $response['success'] );
		$this->assertSame( $name, get_post( $ticket_id )->post_title );
	}

	/**
	 * @test
	 */
	public function should_save_through_the_classic_editor_a_ticket_with_a_valid_rule(): void {
		$event_id = $this->create_event( '2027-06-24 19:00:00' );
		$rule     = [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => [ 'mode' => 'default' ] ];

		$response = $this->send_classic_ticket_add( $event_id, [ 'relative_sale_dates' => wp_json_encode( $rule ) ] );

		$this->assertTrue( $response['success'] );
		$ticket_ids = tribe( Module::class )->get_tickets_ids( $event_id );
		$this->assertCount( 1, $ticket_ids );
		$this->assertSame( $rule, $this->get_stored_rule( reset( $ticket_ids ) ) );
	}

	/**
	 * @return Generator<string,array{0: callable(array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}}): (string|array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}})}>
	 */
	public function sale_price_rule_encodings_provider(): Generator {
		yield 'as JSON, like the classic editor' => [ static fn( array $rule ) => wp_json_encode( $rule ) ];
		yield 'as an array, like the REST APIs' => [ static fn( array $rule ) => $rule ];
	}

	/**
	 * @test
	 * @dataProvider sale_price_rule_encodings_provider
	 */
	public function should_store_the_sale_price_rule_and_write_the_resolved_sale_price_dates( callable $encode ): void {
		$event_start = new DateTimeImmutable( self::EVENT_START );
		$event_id    = $this->create_event( self::EVENT_START );
		$rule        = $this->get_sale_price_rule( $this->sale_price_relative( 2, WEEK_IN_SECONDS ), $this->sale_price_relative( 1, WEEK_IN_SECONDS ) );

		$ticket_id = $this->create_sale_price_ticket( $event_id, [ self::SALE_PRICE_DATA_KEY => $encode( $rule ) ] );

		$this->assertSame( $rule, $this->get_stored( $ticket_id )[ self::SALE_PRICE_STORE_KEY ] );
		$this->assertSame(
			[ $event_start->modify( '-2 weeks' )->format( 'Y-m-d' ), $event_start->modify( '-1 week' )->format( 'Y-m-d' ) ],
			$this->get_sale_price_dates( $ticket_id )
		);
	}

	/**
	 * @test
	 */
	public function should_write_an_empty_sale_price_start_for_a_now_start(): void {
		$event_start = new DateTimeImmutable( self::EVENT_START );
		$event_id    = $this->create_event( self::EVENT_START );
		$rule        = $this->get_sale_price_rule( [ 'mode' => Rule::MODE_NOW ], $this->sale_price_relative( 1, WEEK_IN_SECONDS ) );

		$ticket_id = $this->create_sale_price_ticket(
			$event_id,
			[
				self::SALE_PRICE_DATA_KEY => wp_json_encode( $rule ),
				'ticket_sale_start_date'  => '2027-01-04',
			]
		);

		$this->assertSame( [ '', $event_start->modify( '-1 week' )->format( 'Y-m-d' ) ], $this->get_sale_price_dates( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_keep_the_submitted_date_of_a_specific_sale_price_end(): void {
		$event_start   = new DateTimeImmutable( self::EVENT_START );
		$event_id      = $this->create_event( self::EVENT_START );
		$submitted_end = $event_start->modify( '-3 days' )->format( 'Y-m-d' );
		$rule          = $this->get_sale_price_rule( $this->sale_price_relative( 2, WEEK_IN_SECONDS ), [ 'mode' => Rule::MODE_SPECIFIC ] );

		$ticket_id = $this->create_sale_price_ticket(
			$event_id,
			[
				self::SALE_PRICE_DATA_KEY => wp_json_encode( $rule ),
				'ticket_sale_end_date'    => $submitted_end,
			]
		);

		$this->assertSame( [ $event_start->modify( '-2 weeks' )->format( 'Y-m-d' ), $submitted_end ], $this->get_sale_price_dates( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_store_the_sale_price_rule_next_to_the_sales_window_rule(): void {
		$event_id          = $this->create_event( self::EVENT_START );
		$sales_window_rule = [ 'start' => $this->relative( 3, WEEK_IN_SECONDS ), 'end' => [ 'mode' => Rule::MODE_DEFAULT ] ];
		$sale_price_rule   = $this->get_sale_price_rule( [ 'mode' => Rule::MODE_NOW ], $this->sale_price_relative( 1, WEEK_IN_SECONDS ) );

		$ticket_id = $this->create_sale_price_ticket(
			$event_id,
			[
				'relative_sale_dates'     => wp_json_encode( $sales_window_rule ),
				self::SALE_PRICE_DATA_KEY => wp_json_encode( $sale_price_rule ),
			]
		);

		$this->assertEquals( array_merge( $sales_window_rule, [ self::SALE_PRICE_STORE_KEY => $sale_price_rule ] ), $this->get_stored( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_keep_the_sale_price_rule_when_the_sales_window_rule_is_saved_or_removed(): void {
		$event_id          = $this->create_event( self::EVENT_START );
		$sale_price_rule   = $this->get_sale_price_rule( [ 'mode' => Rule::MODE_NOW ], $this->sale_price_relative( 1, WEEK_IN_SECONDS ) );
		$sales_window_rule = [ 'start' => $this->relative( 3, WEEK_IN_SECONDS ), 'end' => [ 'mode' => Rule::MODE_DEFAULT ] ];
		$ticket_id         = $this->create_sale_price_ticket( $event_id, [ self::SALE_PRICE_DATA_KEY => wp_json_encode( $sale_price_rule ) ] );

		$this->update_sale_price_ticket( $ticket_id, [ 'relative_sale_dates' => wp_json_encode( $sales_window_rule ) ] );

		$this->assertSame( array_merge( [ self::SALE_PRICE_STORE_KEY => $sale_price_rule ], $sales_window_rule ), $this->get_stored( $ticket_id ) );

		$this->update_sale_price_ticket( $ticket_id, [ 'relative_sale_dates' => '' ] );

		$this->assertSame( [ self::SALE_PRICE_STORE_KEY => $sale_price_rule ], $this->get_stored( $ticket_id ) );
	}

	/**
	 * @test
	 * @dataProvider removed_rule_provider
	 */
	public function should_remove_only_the_sale_price_rule_sent_as_empty( ?string $removed ): void {
		$event_id          = $this->create_event( self::EVENT_START );
		$sales_window_rule = [ 'start' => $this->relative( 3, WEEK_IN_SECONDS ), 'end' => [ 'mode' => Rule::MODE_DEFAULT ] ];
		$ticket_id         = $this->create_sale_price_ticket(
			$event_id,
			[
				'relative_sale_dates'     => wp_json_encode( $sales_window_rule ),
				self::SALE_PRICE_DATA_KEY => wp_json_encode( $this->get_sale_price_rule( [ 'mode' => Rule::MODE_NOW ], $this->sale_price_relative( 1, WEEK_IN_SECONDS ) ) ),
			]
		);

		$this->update_sale_price_ticket(
			$ticket_id,
			[
				self::SALE_PRICE_DATA_KEY => $removed,
				'ticket_sale_start_date'  => '2027-01-04',
				'ticket_sale_end_date'    => '2027-01-11',
			]
		);

		$this->assertSame( $sales_window_rule, $this->get_stored( $ticket_id ) );
		$this->assertSame( [ '2027-01-04', '2027-01-11' ], $this->get_sale_price_dates( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_apply_the_stored_sale_price_rule_again_when_the_ticket_data_leaves_it_out(): void {
		$event_start = new DateTimeImmutable( self::EVENT_START );
		$event_id    = $this->create_event( self::EVENT_START );
		$rule        = $this->get_sale_price_rule( $this->sale_price_relative( 2, WEEK_IN_SECONDS ), $this->sale_price_relative( 1, WEEK_IN_SECONDS ) );
		$ticket_id   = $this->create_sale_price_ticket( $event_id, [ self::SALE_PRICE_DATA_KEY => wp_json_encode( $rule ) ] );

		$this->update_sale_price_ticket(
			$ticket_id,
			[
				'ticket_sale_start_date' => '2027-01-04',
				'ticket_sale_end_date'   => '2027-01-11',
			]
		);

		$this->assertSame( $rule, $this->get_stored( $ticket_id )[ self::SALE_PRICE_STORE_KEY ] );
		$this->assertSame(
			[ $event_start->modify( '-2 weeks' )->format( 'Y-m-d' ), $event_start->modify( '-1 week' )->format( 'Y-m-d' ) ],
			$this->get_sale_price_dates( $ticket_id )
		);
	}

	/**
	 * Only the sales window rule is removed by a front-end form, such as Community Events', that sends no rule.
	 *
	 * @test
	 */
	public function should_keep_the_sale_price_rule_when_a_front_end_form_drops_the_sales_window_rule(): void {
		$event_id        = $this->create_event( self::EVENT_START );
		$sale_price_rule = $this->get_sale_price_rule( [ 'mode' => Rule::MODE_NOW ], $this->sale_price_relative( 1, WEEK_IN_SECONDS ) );
		$ticket_id       = $this->create_sale_price_ticket(
			$event_id,
			[
				'relative_sale_dates'     => wp_json_encode( [ 'start' => $this->relative( 3, WEEK_IN_SECONDS ), 'end' => [ 'mode' => Rule::MODE_DEFAULT ] ] ),
				self::SALE_PRICE_DATA_KEY => wp_json_encode( $sale_price_rule ),
			]
		);
		// What `tickets.js` sends outside wp-admin.
		$_POST['is_admin'] = 'false';

		$this->update_sale_price_ticket( $ticket_id, [] );

		$this->assertSame( [ self::SALE_PRICE_STORE_KEY => $sale_price_rule ], $this->get_stored( $ticket_id ) );
	}

	/**
	 * @return Generator<string,array{0: array<string,array<string,array<string,int|string>>>}>
	 */
	public function without_a_valid_stored_sale_price_rule_provider(): Generator {
		yield 'no stored sale price rule' => [
			[
				'start' => [ 'mode' => Rule::MODE_DEFAULT ],
				'end'   => [ 'mode' => Rule::MODE_DEFAULT ],
			],
		];
		yield 'an invalid stored sale price rule' => [ [ self::SALE_PRICE_STORE_KEY => [ 'start' => [ 'mode' => Rule::MODE_NOW ] ] ] ];
	}

	/**
	 * @test
	 * @dataProvider without_a_valid_stored_sale_price_rule_provider
	 */
	public function should_keep_the_submitted_sale_price_dates_without_a_valid_stored_sale_price_rule( array $stored ): void {
		$ticket_id = $this->create_sale_price_ticket( $this->create_event( self::EVENT_START ), [] );
		tribe( Rule_Store::class )->save( $ticket_id, $stored );

		$this->update_sale_price_ticket(
			$ticket_id,
			[
				'ticket_sale_start_date' => '2027-01-04',
				'ticket_sale_end_date'   => '2027-01-11',
			]
		);

		$this->assertSame( [ '2027-01-04', '2027-01-11' ], $this->get_sale_price_dates( $ticket_id ) );
	}

	/**
	 * @return Generator<string,array{0: array<string,string|int|bool>}>
	 */
	public function removed_sale_price_provider(): Generator {
		yield 'sale price unchecked' => [ [ 'ticket_add_sale_price' => false ] ];
		// Today's save silently drops a sale price that is not lower than the price, 20 in these tests.
		yield 'sale price not lower than the price' => [ [ 'ticket_sale_price' => 20 ] ];
	}

	/**
	 * @test
	 * @dataProvider removed_sale_price_provider
	 */
	public function should_remove_only_the_sale_price_rule_when_the_sale_price_is_removed( array $data ): void {
		$event_id          = $this->create_event( self::EVENT_START );
		$sales_window_rule = [ 'start' => $this->relative( 3, WEEK_IN_SECONDS ), 'end' => [ 'mode' => Rule::MODE_DEFAULT ] ];
		$sale_price_rule   = wp_json_encode( $this->get_sale_price_rule( [ 'mode' => Rule::MODE_NOW ], $this->sale_price_relative( 1, WEEK_IN_SECONDS ) ) );
		$ticket_id         = $this->create_sale_price_ticket(
			$event_id,
			[
				'relative_sale_dates'     => wp_json_encode( $sales_window_rule ),
				self::SALE_PRICE_DATA_KEY => $sale_price_rule,
			]
		);

		$this->update_sale_price_ticket( $ticket_id, array_merge( [ self::SALE_PRICE_DATA_KEY => $sale_price_rule ], $data ) );

		$this->assertSame( $sales_window_rule, $this->get_stored( $ticket_id ) );
		$this->assertSame( [ '', '' ], $this->get_sale_price_dates( $ticket_id ) );
		$this->assertFalse( $this->get_ticket( $event_id, $ticket_id )->on_sale );
	}

	/**
	 * @return Generator<string,array{0: int, 1: int, 2: bool}>
	 */
	public function on_sale_days_provider(): Generator {
		/*
		 * The event starts 10 days from today, so "N days before the start" falls 10 - N days from today. The sale price
		 * start and end are given as days before the event start.
		 */
		yield 'the day before the window' => [ 9, 2, false ];
		yield 'the first day of the window' => [ 10, 2, true ];
		yield 'the last day of the window' => [ 12, 10, true ];
		yield 'the day after the window' => [ 12, 11, false ];
	}

	/**
	 * @test
	 * @dataProvider on_sale_days_provider
	 */
	public function should_put_the_ticket_on_sale_only_within_the_resolved_sale_price_window( int $start_days, int $end_days, bool $on_sale ): void {
		$this->set_site_timezone_to_utc();
		$today     = new DateTimeImmutable( 'today', new DateTimeZone( 'UTC' ) );
		$event_id  = $this->create_event( $today->modify( '+10 days' )->format( 'Y-m-d 19:00:00' ) );
		$ticket_id = $this->create_sale_price_ticket(
			$event_id,
			[
				self::SALE_PRICE_DATA_KEY => wp_json_encode(
					$this->get_sale_price_rule( $this->sale_price_relative( $start_days, DAY_IN_SECONDS ), $this->sale_price_relative( $end_days, DAY_IN_SECONDS ) )
				),
			]
		);

		$this->assertSame( $on_sale, $this->get_ticket( $event_id, $ticket_id )->on_sale );
	}

	/**
	 * @test
	 */
	public function should_put_a_now_sale_price_start_on_sale_until_the_resolved_end(): void {
		$this->set_site_timezone_to_utc();
		$today     = new DateTimeImmutable( 'today', new DateTimeZone( 'UTC' ) );
		$event_id  = $this->create_event( $today->modify( '+10 days' )->format( 'Y-m-d 19:00:00' ) );
		$ticket_id = $this->create_sale_price_ticket(
			$event_id,
			[ self::SALE_PRICE_DATA_KEY => wp_json_encode( $this->get_sale_price_rule( [ 'mode' => Rule::MODE_NOW ], $this->sale_price_relative( 1, DAY_IN_SECONDS ) ) ) ]
		);

		$this->assertTrue( $this->get_ticket( $event_id, $ticket_id )->on_sale );
	}

	/**
	 * @return Generator<string,array{0: array<string,string|int|bool>, 1: array{0: string, 1: string}}>
	 */
	public function tickets_without_a_sale_price_rule_provider(): Generator {
		yield 'fixed sale price dates' => [
			[
				'ticket_sale_start_date' => '2027-01-04',
				'ticket_sale_end_date'   => '2027-01-11',
			],
			[ '2027-01-04', '2027-01-11' ],
		];
		yield 'no sale price' => [ [ 'ticket_add_sale_price' => false ], [ '', '' ] ];
	}

	/**
	 * @test
	 * @dataProvider tickets_without_a_sale_price_rule_provider
	 */
	public function should_leave_the_sale_price_of_a_ticket_without_a_rule_as_it_is_today( array $data, array $expected_dates ): void {
		$ticket_id = $this->create_sale_price_ticket( $this->create_event( self::EVENT_START ), $data );

		$this->assertSame( [], $this->get_stored( $ticket_id ) );
		$this->assertSame( $expected_dates, $this->get_sale_price_dates( $ticket_id ) );
	}

	/**
	 * @return Generator<string,array{0: callable(self): int, 1: array<string,string>}>
	 */
	public function sale_price_out_of_scope_ticket_provider(): Generator {
		yield 'ticket on a page' => [ static fn( self $test ): int => static::factory()->post->create( [ 'post_type' => 'page' ] ), [] ];
		yield 'Series Pass' => [ static fn( self $test ): int => $test->create_event( self::EVENT_START ), [ 'ticket_type' => Series_Passes::TICKET_TYPE ] ];
		// The sales window rule leaves RSVPs V2 out, and the sale price follows the same tickets.
		yield 'RSVP V2' => [ static fn( self $test ): int => $test->create_event( self::EVENT_START ), [ 'ticket_type' => RSVP_V2_Constants::TC_RSVP_TYPE ] ];
	}

	/**
	 * @test
	 * @dataProvider sale_price_out_of_scope_ticket_provider
	 */
	public function should_ignore_the_sale_price_rule_of_a_ticket_out_of_scope( callable $create_post, array $data ): void {
		$ticket_id = $this->create_sale_price_ticket(
			$create_post( $this ),
			array_merge(
				$data,
				[
					self::SALE_PRICE_DATA_KEY => wp_json_encode( $this->get_sale_price_rule( $this->sale_price_relative( 2, WEEK_IN_SECONDS ), $this->sale_price_relative( 1, WEEK_IN_SECONDS ) ) ),
					'ticket_sale_start_date'  => '2027-01-04',
					'ticket_sale_end_date'    => '2027-01-11',
				]
			)
		);

		$this->assertSame( [], $this->get_stored( $ticket_id ) );
		$this->assertSame( [ '2027-01-04', '2027-01-11' ], $this->get_sale_price_dates( $ticket_id ) );
	}

	/**
	 * Sends a ticket save from the classic editor and returns its JSON response.
	 *
	 * @param int                      $event_id The event post ID.
	 * @param array<string,int|string> $data     The ticket form data, merged over a Tickets Commerce ticket.
	 *
	 * @return array{success: bool, data: mixed} The decoded JSON response.
	 */
	private function send_classic_ticket_add( int $event_id, array $data ): array {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		return $this->send_classic_ticket_form(
			$event_id,
			http_build_query(
				array_merge(
					[
						'ticket_name'     => 'Classic editor ticket',
						'ticket_price'    => '10',
						'ticket_provider' => Module::class,
						'tribe-ticket'    => [
							'mode'     => 'own',
							'capacity' => '50',
						],
					],
					$data
				)
			)
		);
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
	 * Creates a Tickets Commerce ticket priced 20 with a sale price of 10.
	 *
	 * @param int                                                                                                                                              $post_id   The ticketed post ID.
	 * @param array<string,string|int|bool|null|array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}}> $overrides The ticket data to override.
	 *
	 * @return int The ticket post ID.
	 */
	private function create_sale_price_ticket( int $post_id, array $overrides ): int {
		return $this->create_tc_ticket( $post_id, 20, array_merge( $this->get_sale_price_data(), $overrides ) );
	}

	/**
	 * Saves the ticket again with a sale price of 10, the way an editor sends all of its fields.
	 *
	 * @param int                                                                                                                                              $ticket_id The ticket post ID.
	 * @param array<string,string|int|bool|null|array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}}> $overrides The ticket data to override.
	 *
	 * @return void
	 */
	private function update_sale_price_ticket( int $ticket_id, array $overrides ): void {
		$this->update_ticket( $ticket_id, array_merge( [ 'ticket_price' => 20 ], $this->get_sale_price_data(), $overrides ) );
	}

	/**
	 * @return array{ticket_add_sale_price: string, ticket_sale_price: int, ticket_sale_start_date: string, ticket_sale_end_date: string} The sale price fields of a ticket form with a sale price of 10.
	 */
	private function get_sale_price_data(): array {
		return [
			'ticket_add_sale_price'  => 'on',
			'ticket_sale_price'      => 10,
			'ticket_sale_start_date' => '',
			'ticket_sale_end_date'   => '',
		];
	}

	/**
	 * @param int $value The number of units before the event start.
	 * @param int $unit  `DAY_IN_SECONDS` or `WEEK_IN_SECONDS`.
	 *
	 * @return array{mode: string, value: int, unit: int} A relative boundary of the sale price window.
	 */
	private function sale_price_relative( int $value, int $unit ): array {
		return [
			'mode'  => Rule::MODE_RELATIVE,
			'value' => $value,
			'unit'  => $unit,
		];
	}

	/**
	 * @param array{mode: string, value?: int, unit?: int} $start The sale price start.
	 * @param array{mode: string, value?: int, unit?: int} $end   The sale price end.
	 *
	 * @return array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}} The sale price rule.
	 */
	private function get_sale_price_rule( array $start, array $end ): array {
		return [
			'start' => $start,
			'end'   => $end,
		];
	}

	/**
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return array<string,array<string,int|string|array<string,int|string>>> Everything stored for the ticket, keyed by the top-level key.
	 */
	private function get_stored( int $ticket_id ): array {
		return tribe( Rule_Store::class )->get( $ticket_id );
	}

	/**
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return array{0: string, 1: string} The stored sale price start and end dates.
	 */
	private function get_sale_price_dates( int $ticket_id ): array {
		return [
			get_post_meta( $ticket_id, Ticket::$sale_price_start_date_key, true ),
			get_post_meta( $ticket_id, Ticket::$sale_price_end_date_key, true ),
		];
	}

	/**
	 * @param int $event_id  The event post ID.
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return Ticket_Object|null The ticket, read the way the front end reads it.
	 */
	private function get_ticket( int $event_id, int $ticket_id ): ?Ticket_Object {
		return tribe( Module::class )->get_ticket( $event_id, $ticket_id );
	}

	/**
	 * Makes the ticket read "today" in UTC, the timezone the test computes its dates in.
	 *
	 * The ticket checks its sale price window against "today" in the site timezone. A site with no timezone string falls
	 * back to its UTC offset, and an offset of 0 resolves to Europe/London, which is an hour ahead of UTC in summer: the
	 * ticket's "today" is then a day ahead of the test's from 23:00 UTC.
	 *
	 * @return void
	 */
	private function set_site_timezone_to_utc(): void {
		update_option( 'timezone_string', 'UTC' );
	}
}
