<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use DateTimeImmutable;
use DateTimeZone;
use Generator;
use TEC\Common\REST\TEC\V1\Exceptions\InvalidRestArgumentException;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use TEC\Tickets\REST\TEC\V1\Endpoints\Ticket as Ticket_Endpoint;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

class Sale_Price_Save_Test extends Controller_Test_Case {
	use Relative_Sale_Dates_Maker;
	use Ticket_Maker;
	use With_Tickets_Commerce;

	/**
	 * The event start most tests use, in UTC.
	 *
	 * @var string
	 */
	private const EVENT_START = '2027-06-24 19:00:00';

	/**
	 * The error shown for a sale price window that does not end after the day it starts.
	 *
	 * @var string
	 */
	private const ENDS_BEFORE_START_MESSAGE = 'The sale price cannot end before it starts. Please adjust the sale price window.';

	/**
	 * The error shown for a sale price that starts outside the ticket sales window.
	 *
	 * @var string
	 */
	private const OUTSIDE_SALES_WINDOW_MESSAGE = 'The sale price window falls outside the ticket sales window. Please adjust the dates.';

	protected $controller_class = Controller::class;

	/**
	 * @before
	 */
	public function register_controller(): void {
		$this->make_controller()->register();
	}

	/**
	 * @return Generator<string,array{0: callable(array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}}): (string|array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}})}>
	 */
	public function rule_encodings_provider(): Generator {
		yield 'as JSON, like the classic editor' => [ static fn( array $rule ) => wp_json_encode( $rule ) ];
		yield 'as an array, like the REST APIs' => [ static fn( array $rule ) => $rule ];
	}

	/**
	 * @test
	 * @dataProvider rule_encodings_provider
	 */
	public function should_store_the_rule_and_write_the_resolved_dates( callable $encode ): void {
		$event_start = new DateTimeImmutable( self::EVENT_START );
		$event_id    = $this->create_event( self::EVENT_START );
		$rule        = $this->get_rule( $this->sale_price_relative( 2, WEEK_IN_SECONDS ), $this->sale_price_relative( 1, WEEK_IN_SECONDS ) );

		$ticket_id = $this->create_sale_price_ticket( $event_id, [ Sale_Price_Save::DATA_KEY => $encode( $rule ) ] );

		$this->assertSame( $rule, $this->get_stored( $ticket_id )[ Sale_Price_Rule::KEY ] );
		$this->assertSame(
			[ $event_start->modify( '-2 weeks' )->format( 'Y-m-d' ), $event_start->modify( '-1 week' )->format( 'Y-m-d' ) ],
			$this->get_sale_price_dates( $ticket_id )
		);
	}

	/**
	 * @test
	 */
	public function should_write_an_empty_start_for_a_now_start(): void {
		$event_start = new DateTimeImmutable( self::EVENT_START );
		$event_id    = $this->create_event( self::EVENT_START );
		$rule        = $this->get_rule( [ 'mode' => Sale_Price_Rule::MODE_NOW ], $this->sale_price_relative( 1, WEEK_IN_SECONDS ) );

		$ticket_id = $this->create_sale_price_ticket(
			$event_id,
			[
				Sale_Price_Save::DATA_KEY => wp_json_encode( $rule ),
				'ticket_sale_start_date'  => '2027-01-04',
			]
		);

		$this->assertSame( [ '', $event_start->modify( '-1 week' )->format( 'Y-m-d' ) ], $this->get_sale_price_dates( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_keep_the_submitted_date_of_a_specific_end(): void {
		$event_start   = new DateTimeImmutable( self::EVENT_START );
		$event_id      = $this->create_event( self::EVENT_START );
		$submitted_end = $event_start->modify( '-3 days' )->format( 'Y-m-d' );
		$rule          = $this->get_rule( $this->sale_price_relative( 2, WEEK_IN_SECONDS ), [ 'mode' => Rule::MODE_SPECIFIC ] );

		$ticket_id = $this->create_sale_price_ticket(
			$event_id,
			[
				Sale_Price_Save::DATA_KEY => wp_json_encode( $rule ),
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
		$sale_price_rule   = $this->get_rule( [ 'mode' => Sale_Price_Rule::MODE_NOW ], $this->sale_price_relative( 1, WEEK_IN_SECONDS ) );

		$ticket_id = $this->create_sale_price_ticket(
			$event_id,
			[
				Ticket_Save::DATA_KEY     => wp_json_encode( $sales_window_rule ),
				Sale_Price_Save::DATA_KEY => wp_json_encode( $sale_price_rule ),
			]
		);

		// The two rules are written by two hooks of the same save, so the order of their keys says nothing.
		$this->assertEquals( array_merge( $sales_window_rule, [ Sale_Price_Rule::KEY => $sale_price_rule ] ), $this->get_stored( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_keep_the_sale_price_rule_when_the_sales_window_rule_is_saved_or_removed(): void {
		$event_id          = $this->create_event( self::EVENT_START );
		$sale_price_rule   = $this->get_rule( [ 'mode' => Sale_Price_Rule::MODE_NOW ], $this->sale_price_relative( 1, WEEK_IN_SECONDS ) );
		$sales_window_rule = [ 'start' => $this->relative( 3, WEEK_IN_SECONDS ), 'end' => [ 'mode' => Rule::MODE_DEFAULT ] ];
		$ticket_id         = $this->create_sale_price_ticket( $event_id, [ Sale_Price_Save::DATA_KEY => wp_json_encode( $sale_price_rule ) ] );

		$this->update_sale_price_ticket( $ticket_id, [ Ticket_Save::DATA_KEY => wp_json_encode( $sales_window_rule ) ] );

		$this->assertSame( array_merge( [ Sale_Price_Rule::KEY => $sale_price_rule ], $sales_window_rule ), $this->get_stored( $ticket_id ) );

		$this->update_sale_price_ticket( $ticket_id, [ Ticket_Save::DATA_KEY => '' ] );

		$this->assertSame( [ Sale_Price_Rule::KEY => $sale_price_rule ], $this->get_stored( $ticket_id ) );
	}

	/**
	 * @return Generator<string,array{0: string|null}>
	 */
	public function removing_values_provider(): Generator {
		yield 'null' => [ null ];
		yield 'an empty string' => [ '' ];
	}

	/**
	 * @test
	 * @dataProvider removing_values_provider
	 */
	public function should_remove_only_the_sale_price_rule_sent_as_empty( ?string $removing_value ): void {
		$event_id          = $this->create_event( self::EVENT_START );
		$sales_window_rule = [ 'start' => $this->relative( 3, WEEK_IN_SECONDS ), 'end' => [ 'mode' => Rule::MODE_DEFAULT ] ];
		$ticket_id         = $this->create_sale_price_ticket(
			$event_id,
			[
				Ticket_Save::DATA_KEY     => wp_json_encode( $sales_window_rule ),
				Sale_Price_Save::DATA_KEY => wp_json_encode( $this->get_rule( [ 'mode' => Sale_Price_Rule::MODE_NOW ], $this->sale_price_relative( 1, WEEK_IN_SECONDS ) ) ),
			]
		);

		$this->update_sale_price_ticket(
			$ticket_id,
			[
				Sale_Price_Save::DATA_KEY => $removing_value,
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
	public function should_apply_the_stored_rule_again_when_the_ticket_data_leaves_it_out(): void {
		$event_start = new DateTimeImmutable( self::EVENT_START );
		$event_id    = $this->create_event( self::EVENT_START );
		$rule        = $this->get_rule( $this->sale_price_relative( 2, WEEK_IN_SECONDS ), $this->sale_price_relative( 1, WEEK_IN_SECONDS ) );
		$ticket_id   = $this->create_sale_price_ticket( $event_id, [ Sale_Price_Save::DATA_KEY => wp_json_encode( $rule ) ] );

		$this->update_sale_price_ticket(
			$ticket_id,
			[
				'ticket_sale_start_date' => '2027-01-04',
				'ticket_sale_end_date'   => '2027-01-11',
			]
		);

		$this->assertSame( $rule, $this->get_stored( $ticket_id )[ Sale_Price_Rule::KEY ] );
		$this->assertSame(
			[ $event_start->modify( '-2 weeks' )->format( 'Y-m-d' ), $event_start->modify( '-1 week' )->format( 'Y-m-d' ) ],
			$this->get_sale_price_dates( $ticket_id )
		);
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
		$sale_price_rule   = wp_json_encode( $this->get_rule( [ 'mode' => Sale_Price_Rule::MODE_NOW ], $this->sale_price_relative( 1, WEEK_IN_SECONDS ) ) );
		$ticket_id         = $this->create_sale_price_ticket(
			$event_id,
			[
				Ticket_Save::DATA_KEY     => wp_json_encode( $sales_window_rule ),
				Sale_Price_Save::DATA_KEY => $sale_price_rule,
			]
		);

		$this->update_sale_price_ticket( $ticket_id, array_merge( [ Sale_Price_Save::DATA_KEY => $sale_price_rule ], $data ) );

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
	public function should_put_the_ticket_on_sale_only_within_the_resolved_window( int $start_days, int $end_days, bool $on_sale ): void {
		$this->set_site_timezone_to_utc();
		$today     = new DateTimeImmutable( 'today', new DateTimeZone( 'UTC' ) );
		$event_id  = $this->create_event( $today->modify( '+10 days' )->format( 'Y-m-d 19:00:00' ) );
		$ticket_id = $this->create_sale_price_ticket(
			$event_id,
			[
				Sale_Price_Save::DATA_KEY => wp_json_encode(
					$this->get_rule( $this->sale_price_relative( $start_days, DAY_IN_SECONDS ), $this->sale_price_relative( $end_days, DAY_IN_SECONDS ) )
				),
			]
		);

		$this->assertSame( $on_sale, $this->get_ticket( $event_id, $ticket_id )->on_sale );
	}

	/**
	 * @test
	 */
	public function should_put_a_now_start_on_sale_until_the_resolved_end(): void {
		$this->set_site_timezone_to_utc();
		$today     = new DateTimeImmutable( 'today', new DateTimeZone( 'UTC' ) );
		$event_id  = $this->create_event( $today->modify( '+10 days' )->format( 'Y-m-d 19:00:00' ) );
		$ticket_id = $this->create_sale_price_ticket(
			$event_id,
			[ Sale_Price_Save::DATA_KEY => wp_json_encode( $this->get_rule( [ 'mode' => Sale_Price_Rule::MODE_NOW ], $this->sale_price_relative( 1, DAY_IN_SECONDS ) ) ) ]
		);

		$this->assertTrue( $this->get_ticket( $event_id, $ticket_id )->on_sale );
	}

	/**
	 * @return Generator<string,array{0: array<string,string|int|bool>, 1: array{0: string, 1: string}}>
	 */
	public function tickets_without_a_rule_provider(): Generator {
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
	 * @dataProvider tickets_without_a_rule_provider
	 */
	public function should_leave_a_ticket_without_a_rule_as_it_is_today( array $data, array $expected_dates ): void {
		$ticket_id = $this->create_sale_price_ticket( $this->create_event( self::EVENT_START ), $data );

		$this->assertSame( [], $this->get_stored( $ticket_id ) );
		$this->assertSame( $expected_dates, $this->get_sale_price_dates( $ticket_id ) );
	}

	/**
	 * @return Generator<string,array{0: callable(self): int, 1: array<string,string>}>
	 */
	public function out_of_scope_ticket_provider(): Generator {
		yield 'ticket on a page' => [ static fn( self $test ): int => static::factory()->post->create( [ 'post_type' => 'page' ] ), [] ];
		yield 'Series Pass' => [ static fn( self $test ): int => $test->create_event( self::EVENT_START ), [ 'ticket_type' => Series_Passes::TICKET_TYPE ] ];
	}

	/**
	 * @test
	 * @dataProvider out_of_scope_ticket_provider
	 */
	public function should_ignore_the_rule_of_a_ticket_out_of_scope( callable $create_post, array $data ): void {
		$ticket_id = $this->create_sale_price_ticket(
			$create_post( $this ),
			array_merge(
				$data,
				[
					Sale_Price_Save::DATA_KEY => wp_json_encode( $this->get_rule( $this->sale_price_relative( 2, WEEK_IN_SECONDS ), $this->sale_price_relative( 1, WEEK_IN_SECONDS ) ) ),
					'ticket_sale_start_date'  => '2027-01-04',
					'ticket_sale_end_date'    => '2027-01-11',
				]
			)
		);

		$this->assertSame( [], $this->get_stored( $ticket_id ) );
		$this->assertSame( [ '2027-01-04', '2027-01-11' ], $this->get_sale_price_dates( $ticket_id ) );
	}

	/**
	 * @return Generator<string,array{0: array<string,string|int|bool|null>, 1: true|string}>
	 */
	public function validated_ticket_data_provider(): Generator {
		$event_start = new DateTimeImmutable( self::EVENT_START );
		$days_before = static fn( int $days ): string => $event_start->modify( "-{$days} days" )->format( 'Y-m-d' );
		$relative    = static fn( int $days ): array => [
			'mode'  => Rule::MODE_RELATIVE,
			'value' => $days,
			'unit'  => Rule::UNIT_DAYS,
		];
		$now         = [ 'mode' => Sale_Price_Rule::MODE_NOW ];
		$specific    = [ 'mode' => Rule::MODE_SPECIFIC ];
		$rule        = static fn( array $start, array $end ): string => wp_json_encode(
			[
				'start' => $start,
				'end'   => $end,
			]
		);
		// Sales open a week before the event starts.
		$sales_open_a_week_before = [
			Ticket_Save::DATA_KEY => wp_json_encode(
				[
					'start' => [
						'mode'   => Rule::MODE_RELATIVE,
						'value'  => 1,
						'unit'   => Rule::UNIT_WEEKS,
						'anchor' => Rule::ANCHOR_START,
					],
					'end'   => [ 'mode' => Rule::MODE_DEFAULT ],
				]
			),
		];

		yield 'from 2 weeks to 1 week before' => [ [ Sale_Price_Save::DATA_KEY => $rule( $relative( 14 ), $relative( 7 ) ) ], true ];
		yield 'an end on the start day' => [ [ Sale_Price_Save::DATA_KEY => $rule( $relative( 7 ), $relative( 7 ) ) ], self::ENDS_BEFORE_START_MESSAGE ];
		yield 'an end before the start' => [ [ Sale_Price_Save::DATA_KEY => $rule( $relative( 7 ), $relative( 14 ) ) ], self::ENDS_BEFORE_START_MESSAGE ];
		yield 'a specific end on the start day' => [
			[
				Sale_Price_Save::DATA_KEY => $rule( $relative( 7 ), $specific ),
				'ticket_sale_end_date'    => $days_before( 7 ),
			],
			self::ENDS_BEFORE_START_MESSAGE,
		];
		yield 'a now start' => [ [ Sale_Price_Save::DATA_KEY => $rule( $now, $relative( 7 ) ) ], true ];
		yield 'a now start ending on the day sales open' => [
			array_merge( $sales_open_a_week_before, [ Sale_Price_Save::DATA_KEY => $rule( $now, $relative( 7 ) ) ] ),
			self::ENDS_BEFORE_START_MESSAGE,
		];
		yield 'a now start with sales opening a week before' => [
			array_merge( $sales_open_a_week_before, [ Sale_Price_Save::DATA_KEY => $rule( $now, $relative( 3 ) ) ] ),
			true,
		];
		yield 'a start before the sales the rule opens' => [
			array_merge( $sales_open_a_week_before, [ Sale_Price_Save::DATA_KEY => $rule( $relative( 14 ), $relative( 3 ) ) ] ),
			self::OUTSIDE_SALES_WINDOW_MESSAGE,
		];
		yield 'a start before the submitted sales start' => [
			[
				Sale_Price_Save::DATA_KEY => $rule( $relative( 14 ), $relative( 3 ) ),
				'ticket_start_date'       => $days_before( 7 ),
				'ticket_start_time'       => '10:00:00',
			],
			self::OUTSIDE_SALES_WINDOW_MESSAGE,
		];
		yield 'a start on the day sales open, before their time' => [
			[
				Sale_Price_Save::DATA_KEY => $rule( $relative( 7 ), $relative( 3 ) ),
				'ticket_start_date'       => $days_before( 7 ),
				'ticket_start_time'       => '10:00:00',
			],
			true,
		];
		yield 'a start after the submitted sales end' => [
			[
				Sale_Price_Save::DATA_KEY => $rule( $relative( 3 ), $specific ),
				'ticket_sale_end_date'    => $days_before( 1 ),
				'ticket_end_date'         => $days_before( 5 ),
				'ticket_end_time'         => '10:00:00',
			],
			self::OUTSIDE_SALES_WINDOW_MESSAGE,
		];
		// Without a sales window rule or a submitted end, sales end when the event starts.
		yield 'a specific start after the event start, when sales end' => [
			[
				Sale_Price_Save::DATA_KEY => $rule( $specific, $specific ),
				'ticket_sale_start_date'  => $event_start->modify( '+1 day' )->format( 'Y-m-d' ),
				'ticket_sale_end_date'    => $event_start->modify( '+2 days' )->format( 'Y-m-d' ),
			],
			self::OUTSIDE_SALES_WINDOW_MESSAGE,
		];
		yield 'a specific start before the submitted sales start' => [
			[
				Sale_Price_Save::DATA_KEY => $rule( $specific, $relative( 3 ) ),
				'ticket_sale_start_date'  => $days_before( 14 ),
				'ticket_start_date'       => $days_before( 7 ),
			],
			self::OUTSIDE_SALES_WINDOW_MESSAGE,
		];
		yield 'an invalid rule' => [
			[ Sale_Price_Save::DATA_KEY => $rule( $relative( Sale_Price_Boundary::MAX_VALUE + 1 ), $relative( 3 ) ) ],
			self::ENDS_BEFORE_START_MESSAGE,
		];
		yield 'a rule sent to be removed' => [ [ Sale_Price_Save::DATA_KEY => null ], true ];
		yield 'no sale price' => [
			[
				Sale_Price_Save::DATA_KEY => $rule( $relative( 7 ), $relative( 14 ) ),
				'ticket_add_sale_price'   => false,
			],
			true,
		];
		// Today's save silently drops a sale price that is not lower than the price, 20 in these tests.
		yield 'a sale price not lower than the price' => [
			[
				Sale_Price_Save::DATA_KEY => $rule( $relative( 7 ), $relative( 14 ) ),
				'ticket_sale_price'       => 20,
			],
			true,
		];
		yield 'fixed sale price dates on the same day' => [
			[
				'ticket_sale_start_date' => $days_before( 7 ),
				'ticket_sale_end_date'   => $days_before( 7 ),
			],
			true,
		];
	}

	/**
	 * @test
	 * @dataProvider validated_ticket_data_provider
	 */
	public function should_validate_the_sale_price_window_of_the_ticket_data( array $data, $expected ): void {
		$valid = apply_filters( 'tec_tickets_ticket_data_validation', true, $this->create_event( self::EVENT_START ), $this->get_ticket_data( $data ) );

		if ( true === $expected ) {
			$this->assertTrue( $valid );

			return;
		}

		$this->assertWPError( $valid );
		$this->assertSame( $expected, $valid->get_error_message() );
		$this->assertSame( 400, $valid->get_error_data()['status'] );
	}

	/**
	 * @test
	 */
	public function should_not_validate_the_sale_price_window_of_a_ticket_out_of_scope(): void {
		$invalid = [
			Sale_Price_Save::DATA_KEY => wp_json_encode(
				$this->get_rule( $this->sale_price_relative( 7, Rule::UNIT_DAYS ), $this->sale_price_relative( 14, Rule::UNIT_DAYS ) )
			),
		];

		$this->assertTrue( apply_filters( 'tec_tickets_ticket_data_validation', true, static::factory()->post->create( [ 'post_type' => 'page' ] ), $this->get_ticket_data( $invalid ) ) );
		$this->assertTrue(
			apply_filters(
				'tec_tickets_ticket_data_validation',
				true,
				$this->create_event( self::EVENT_START ),
				$this->get_ticket_data( array_merge( $invalid, [ 'ticket_type' => Series_Passes::TICKET_TYPE ] ) )
			)
		);
	}

	/**
	 * @test
	 */
	public function should_judge_the_stored_rule_against_a_sales_window_the_update_moves(): void {
		$event_start = new DateTimeImmutable( self::EVENT_START );
		$event_id    = $this->create_event( self::EVENT_START );
		$ticket_id   = $this->create_sale_price_ticket(
			$event_id,
			[ Sale_Price_Save::DATA_KEY => wp_json_encode( $this->get_rule( $this->sale_price_relative( 2, Rule::UNIT_WEEKS ), $this->sale_price_relative( 1, Rule::UNIT_WEEKS ) ) ) ]
		);

		$valid = apply_filters(
			'tec_tickets_ticket_data_validation',
			true,
			$event_id,
			$this->get_ticket_data(
				[
					'ticket_id'         => $ticket_id,
					'ticket_start_date' => $event_start->modify( '-10 days' )->format( 'Y-m-d' ),
				]
			)
		);

		$this->assertWPError( $valid );
		$this->assertSame( self::OUTSIDE_SALES_WINDOW_MESSAGE, $valid->get_error_message() );
	}

	/**
	 * @test
	 */
	public function should_reject_through_the_classic_editor_a_sale_price_that_ends_before_it_starts(): void {
		$event_id = $this->create_event( self::EVENT_START );
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$response = $this->send_classic_ticket_form(
			$event_id,
			http_build_query(
				$this->get_ticket_data(
					[
						'ticket_name'             => 'Classic editor ticket',
						'tribe-ticket'            => [
							'mode'     => 'own',
							'capacity' => '50',
						],
						Sale_Price_Save::DATA_KEY => wp_json_encode( $this->get_rule( $this->sale_price_relative( 7, Rule::UNIT_DAYS ), $this->sale_price_relative( 14, Rule::UNIT_DAYS ) ) ),
					]
				)
			)
		);

		$this->assertSame(
			[
				'success' => false,
				'data'    => [ 'message' => self::ENDS_BEFORE_START_MESSAGE ],
			],
			$response
		);
		$this->assertSame( [], tribe( Module::class )->get_tickets_ids( $event_id ) );
	}

	/**
	 * @test
	 */
	public function should_reject_through_the_block_editor_a_sales_start_moved_past_the_stored_sale_price_start(): void {
		$event_start = new DateTimeImmutable( self::EVENT_START );
		$event_id    = $this->create_event( self::EVENT_START );
		$ticket_id   = $this->create_sale_price_ticket(
			$event_id,
			[ Sale_Price_Save::DATA_KEY => wp_json_encode( $this->get_rule( $this->sale_price_relative( 2, Rule::UNIT_WEEKS ), $this->sale_price_relative( 1, Rule::UNIT_WEEKS ) ) ) ]
		);
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$response = $this->send_block_editor_ticket_save(
			'PUT',
			"/tickets/{$ticket_id}",
			$event_id,
			'edit_ticket_nonce',
			[
				'price'      => '20',
				'start_date' => $event_start->modify( '-10 days' )->format( 'Y-m-d' ),
				'start_time' => '10:00:00',
				'ticket'     => [
					'sale_price' => [
						'checked' => true,
						'price'   => '10',
					],
				],
			]
		);

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( self::OUTSIDE_SALES_WINDOW_MESSAGE, $response->get_data()['message'] );
	}

	/**
	 * @test
	 */
	public function should_reject_through_the_tec_rest_api_a_sales_start_moved_past_the_stored_sale_price_start(): void {
		$event_start = new DateTimeImmutable( self::EVENT_START );
		$event_id    = $this->create_event( self::EVENT_START );
		$ticket_id   = $this->create_sale_price_ticket(
			$event_id,
			[ Sale_Price_Save::DATA_KEY => wp_json_encode( $this->get_rule( $this->sale_price_relative( 2, Rule::UNIT_WEEKS ), $this->sale_price_relative( 1, Rule::UNIT_WEEKS ) ) ) ]
		);
		$endpoint    = tribe( Ticket_Endpoint::class );

		try {
			$endpoint->upsert(
				$endpoint->filter_upsert_params(
					[
						'id'                => $ticket_id,
						'ticket_start_date' => $event_start->modify( '-10 days' )->format( 'Y-m-d' ),
					]
				),
				'update'
			);
			$this->fail( 'The ticket should have been rejected.' );
		} catch ( InvalidRestArgumentException $e ) {
			$this->assertSame( self::OUTSIDE_SALES_WINDOW_MESSAGE, $e->getMessage() );
		}
	}

	/**
	 * Creates a Tickets Commerce ticket priced 20 with a sale price of 10.
	 *
	 * @param int                 $post_id   The ticketed post ID.
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
	 * @param int                 $ticket_id The ticket post ID.
	 * @param array<string,string|int|bool|null|array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}}> $overrides The ticket data to override.
	 *
	 * @return void
	 */
	private function update_sale_price_ticket( int $ticket_id, array $overrides ): void {
		$this->update_ticket( $ticket_id, array_merge( [ 'ticket_price' => 20 ], $this->get_sale_price_data(), $overrides ) );
	}

	/**
	 * @param array<string,string|int|bool|null|array<string,string>> $overrides The ticket data to override.
	 *
	 * @return array<string,string|int|bool|null|array<string,string>> The data of a Tickets Commerce ticket priced 20 with a sale price of 10, as an editor sends it.
	 */
	private function get_ticket_data( array $overrides ): array {
		return array_merge(
			[
				'ticket_provider' => Module::class,
				'ticket_price'    => 20,
			],
			$this->get_sale_price_data(),
			$overrides
		);
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
	private function get_rule( array $start, array $end ): array {
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
