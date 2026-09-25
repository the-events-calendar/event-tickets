<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use DateTimeImmutable;
use DateTimeZone;
use Generator;
use RuntimeException;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use TEC\Tickets\Ticket_Actions;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Tickets__Tickets as Tickets;

class Ticket_Save_Test extends Controller_Test_Case {
	use Relative_Sale_Dates_Maker;
	use Ticket_Maker;
	use With_Tickets_Commerce;

	/**
	 * The error shown for an invalid rule or a sales window that ends before it starts.
	 *
	 * @var string
	 */
	private const INVALID_WINDOW_MESSAGE = 'Ticket sales cannot end before they start. Please adjust the sales window.';

	protected $controller_class = Ticket_Save::class;

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
		$rule        = [ 'start' => $this->relative( 2, Rule::UNIT_WEEKS ), 'end' => [ 'mode' => 'default' ] ];

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
		 * Two hours before 03:30 on the day New York springs forward is 00:30 local time. Reading the event start in
		 * any other timezone would give 01:30.
		 */
		$event_id = $this->create_event( '2027-03-14 03:30:00', 'America/New_York' );
		$rule     = [ 'start' => $this->relative( 2, Rule::UNIT_HOURS ), 'end' => [ 'mode' => 'default' ] ];

		$ticket_id = $this->create_tc_ticket( $event_id, 1, [ 'relative_sale_dates' => wp_json_encode( $rule ) ] );

		$this->assertSame( [ '2027-03-14', '00:30:00' ], $this->get_ticket_start( $ticket_id ) );
		$this->assertSame( [ '2027-03-14', '03:30:00' ], $this->get_ticket_end( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_keep_the_resolved_end_when_the_end_date_is_empty(): void {
		$event_start = '2027-06-24 19:00:00';
		$event_id    = $this->create_event( $event_start );
		$rule        = [ 'start' => [ 'mode' => 'default' ], 'end' => $this->relative( 1, Rule::UNIT_DAYS ) ];

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
		$rule        = [ 'start' => $this->relative( 3, Rule::UNIT_DAYS ), 'end' => [ 'mode' => 'default' ] ];

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
		$rule     = [ 'start' => $this->relative( 2, Rule::UNIT_WEEKS ), 'end' => [ 'mode' => 'specific' ] ];
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
		$rule        = [ 'start' => $this->relative( 2, Rule::UNIT_WEEKS ), 'end' => $this->relative( 1, Rule::UNIT_DAYS ) ];

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
		$rule        = [ 'start' => $this->relative( 2, Rule::UNIT_WEEKS ), 'end' => $this->relative( 2, Rule::UNIT_HOURS ) ];
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
	}

	/**
	 * @test
	 * @dataProvider out_of_scope_ticket_provider
	 */
	public function should_ignore_the_rule_of_a_ticket_out_of_scope( callable $create ): void {
		$data = [
			'relative_sale_dates' => wp_json_encode(
				[ 'start' => $this->relative( 2, Rule::UNIT_WEEKS ), 'end' => $this->relative( 1, Rule::UNIT_DAYS ) ]
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
	 * @test
	 */
	public function should_remove_the_rule_when_the_ticket_is_saved_without_one(): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$rule      = [ 'start' => $this->relative( 2, Rule::UNIT_WEEKS ), 'end' => [ 'mode' => 'default' ] ];
		$ticket_id = $this->create_tc_ticket( $event_id, 1, [ 'relative_sale_dates' => wp_json_encode( $rule ) ] );
		$this->assertSame( $rule, $this->get_stored_rule( $ticket_id ) );

		$this->update_ticket( $ticket_id, [] );

		$this->assertSame( '', get_post_meta( $ticket_id, Rule_Store::META_KEY, true ) );
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
			[ 'relative_sale_dates' => wp_json_encode( [ 'start' => $this->relative( 1, Rule::UNIT_HOURS ), 'end' => $this->relative( 2, Rule::UNIT_HOURS ) ] ) ],
		];

		yield 'specific end before the resolved start' => [
			[
				'relative_sale_dates' => wp_json_encode( [ 'start' => $this->relative( 2, Rule::UNIT_WEEKS ), 'end' => [ 'mode' => 'specific' ] ] ),
				'ticket_end_date'     => '2027-06-01',
				'ticket_end_time'     => '12:00:00',
			],
		];

		yield 'specific start without a date' => [
			[
				'relative_sale_dates' => wp_json_encode( [ 'start' => [ 'mode' => 'specific' ], 'end' => $this->relative( 2, Rule::UNIT_HOURS ) ] ),
				'ticket_start_date'   => '',
			],
		];

		yield 'specific end without a date' => [
			[
				'relative_sale_dates' => wp_json_encode( [ 'start' => $this->relative( 2, Rule::UNIT_WEEKS ), 'end' => [ 'mode' => 'specific' ] ] ),
				'ticket_end_date'     => '',
			],
		];

		yield 'specific start after the resolved end' => [
			[
				'relative_sale_dates' => wp_json_encode( [ 'start' => [ 'mode' => 'specific' ], 'end' => $this->relative( 2, Rule::UNIT_HOURS ) ] ),
				'ticket_start_date'   => '2027-06-24',
				'ticket_start_time'   => '18:00:00',
			],
		];
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
			'relative_sale_dates' => wp_json_encode( [ 'start' => [ 'mode' => 'specific' ], 'end' => $this->relative( 2, Rule::UNIT_HOURS ) ] ),
			'ticket_start_date'   => '31/02/2027',
			'ticket_start_time'   => '12:00:00',
		];

		$result = apply_filters( 'tec_tickets_ticket_data_validation', true, $event_id, $data );

		$this->assertWPError( $result );
		$this->assertSame( self::INVALID_WINDOW_MESSAGE, $result->get_error_message() );
	}

	/**
	 * @return Generator<string,array{0: string, 1: array<string,string>}>
	 */
	public function accepted_ticket_data_provider(): Generator {
		$end_before_start = wp_json_encode( [ 'start' => $this->relative( 1, Rule::UNIT_HOURS ), 'end' => $this->relative( 2, Rule::UNIT_HOURS ) ] );

		yield 'valid rule' => [
			'tribe_events',
			[ 'relative_sale_dates' => wp_json_encode( [ 'start' => $this->relative( 2, Rule::UNIT_WEEKS ), 'end' => $this->relative( 1, Rule::UNIT_DAYS ) ] ) ],
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
			[ 'relative_sale_dates' => wp_json_encode( [ 'start' => $this->relative( 1, Rule::UNIT_HOURS ), 'end' => $this->relative( 2, Rule::UNIT_HOURS ) ] ) ]
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
		$rule     = [ 'start' => $this->relative( 2, Rule::UNIT_WEEKS ), 'end' => [ 'mode' => 'default' ] ];

		$response = $this->send_classic_ticket_add( $event_id, [ 'relative_sale_dates' => wp_json_encode( $rule ) ] );

		$this->assertTrue( $response['success'] );
		$ticket_ids = tribe( Module::class )->get_tickets_ids( $event_id );
		$this->assertCount( 1, $ticket_ids );
		$this->assertSame( $rule, $this->get_stored_rule( reset( $ticket_ids ) ) );
	}

	/**
	 * Sends a ticket save from the classic editor and returns its JSON response.
	 *
	 * @param int                 $event_id The event post ID.
	 * @param array<string,int|string> $data     The ticket form data, merged over a Tickets Commerce ticket.
	 *
	 * @return array{success: bool, data: mixed} The decoded JSON response.
	 */
	private function send_classic_ticket_add( int $event_id, array $data ): array {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		// The classic editor posts its form as one URL-encoded string, which WordPress then slashes.
		$_POST = wp_slash(
			[
				'post_id' => $event_id,
				'nonce'   => wp_create_nonce( 'add_ticket_nonce' ),
				'data'    => http_build_query(
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
				),
			]
		);

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter(
			'wp_die_ajax_handler',
			static fn() => static function () {
				throw new RuntimeException( 'The AJAX response was sent.' );
			}
		);

		ob_start();
		try {
			tribe( 'tickets.metabox' )->ajax_ticket_add();
		} catch ( RuntimeException $e ) {
			// wp_send_json_*() ends the request through the die handler.
		}

		return json_decode( ob_get_clean(), true );
	}

	/**
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return array{start: array{mode: string, value?: int, unit?: int, anchor?: string}, end: array{mode: string, value?: int, unit?: int, anchor?: string}}|null The stored rule.
	 */
	private function get_stored_rule( int $ticket_id ): ?array {
		return json_decode( get_post_meta( $ticket_id, Rule_Store::META_KEY, true ), true );
	}
}
