<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;
use DateTimeImmutable;
use DateTimeZone;
use Generator;
use Tribe\Tests\Traits\With_Clock_Mock;

class Sales_Window_Effects_Test extends WPTestCase {
	use With_Clock_Mock;

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: string, 2: bool}>
	 */
	public function now_start_provider(): Generator {
		yield 'a ticket start later than now' => [ Window_Kind::sales(), '+1 week', true ];
		yield 'a ticket start equal to now' => [ Window_Kind::sales(), '+0 seconds', false ];
		yield 'a ticket start already past' => [ Window_Kind::sales(), '-1 week', false ];
		yield 'no ticket start' => [ Window_Kind::sales(), '', false ];
		yield 'sale price: a ticket start later than now' => [ Window_Kind::sale_price(), '+1 week', false ];
	}

	/**
	 * @test
	 * @dataProvider now_start_provider
	 */
	public function should_move_only_a_later_ticket_start_to_now_for_a_now_start( Window_Kind $kind, string $ticket_start_offset, bool $moved ): void {
		$utc = new DateTimeZone( 'UTC' );
		$now = new DateTimeImmutable( '2027-01-10 12:00:00', $utc );
		$this->freeze_time( $now );
		$rule         = Rule::from_array( [ 'start' => [ 'mode' => $kind->get_open_start_mode() ], 'end' => [ 'mode' => Rule::MODE_SPECIFIC ] ], $kind );
		$ticket_start = $ticket_start_offset ? $now->modify( $ticket_start_offset )->format( 'Y-m-d H:i:s' ) : '';
		$window       = new Resolved_Window( null, null, $utc );

		$start = $this->get_effects()->apply_now_start( $window, $rule, $ticket_start )->get_start();

		$this->assertSame( $moved ? $now->format( 'Y-m-d H:i:s' ) : null, $start ? $start->format( 'Y-m-d H:i:s' ) : null );
	}

	/**
	 * @test
	 */
	public function should_keep_the_event_timezone_with_the_sales_window_dates_only(): void {
		$window = new Resolved_Window( null, null, new DateTimeZone( 'America/New_York' ) );

		$this->assertSame( [ Ticket_Dates::TIMEZONE_META_KEY => 'America/New_York' ], $this->get_effects()->get_extra_metas( $window, Window_Kind::sales() ) );
		$this->assertSame( [], $this->get_effects()->get_extra_metas( $window, Window_Kind::sale_price() ) );
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: array<string,array<string,int|string>>, 2: bool}>
	 */
	public function end_follows_event_start_provider(): Generator {
		$sales        = Window_Kind::sales();
		$before_start = [ 'mode' => 'relative', 'value' => 2, 'unit' => WEEK_IN_SECONDS, 'anchor' => 'start' ];
		$before_end   = [ 'mode' => 'relative', 'value' => 2, 'unit' => HOUR_IN_SECONDS, 'anchor' => 'end' ];

		yield 'specific end, start counted from the event start' => [ $sales, [ 'start' => $before_start, 'end' => [ 'mode' => 'specific' ] ], true ];
		yield 'specific end, start counted from the event end' => [ $sales, [ 'start' => $before_end, 'end' => [ 'mode' => 'specific' ] ], false ];
		yield 'relative end' => [ $sales, [ 'start' => [ 'mode' => 'default' ], 'end' => $before_start ], false ];
		yield 'default end' => [ $sales, [ 'start' => [ 'mode' => 'default' ], 'end' => [ 'mode' => 'default' ] ], false ];
		yield 'sale price: specific end, start counted from the event start' => [
			Window_Kind::sale_price(),
			[
				'start' => [ 'mode' => 'relative', 'value' => 2, 'unit' => WEEK_IN_SECONDS ],
				'end'   => [ 'mode' => 'specific' ],
			],
			false,
		];
	}

	/**
	 * @test
	 * @dataProvider end_follows_event_start_provider
	 */
	public function should_let_only_an_end_left_to_the_ticket_follow_the_event_start( Window_Kind $kind, array $data, bool $follows ): void {
		$this->assertSame( $follows, $this->get_effects()->lets_end_follow_event_start( Rule::from_array( $data, $kind ) ) );
	}

	/**
	 * @return Generator<string,array{0: array{mode: string, value?: int, unit?: int, anchor?: string}|null, 1: array{mode: string, value?: int, unit?: int, anchor?: string}, 2: bool}>
	 */
	public function sales_window_end_switch_provider(): Generator {
		$relative = [
			'mode'   => Rule::MODE_RELATIVE,
			'value'  => 1,
			'unit'   => DAY_IN_SECONDS,
			'anchor' => 'start',
		];

		yield 'relative end switched to specific' => [ $relative, [ 'mode' => Rule::MODE_SPECIFIC ], true ];
		yield 'default end switched to specific' => [ [ 'mode' => Rule::MODE_DEFAULT ], [ 'mode' => Rule::MODE_SPECIFIC ], true ];
		// The legacy save flags an end the admin types on a new ticket, when it is not the event start.
		yield 'first rule with a specific end' => [ null, [ 'mode' => Rule::MODE_SPECIFIC ], false ];
		yield 'specific end kept' => [ [ 'mode' => Rule::MODE_SPECIFIC ], [ 'mode' => Rule::MODE_SPECIFIC ], false ];
		yield 'specific end switched to relative' => [ [ 'mode' => Rule::MODE_SPECIFIC ], $relative, false ];
	}

	/**
	 * @test
	 * @dataProvider sales_window_end_switch_provider
	 */
	public function should_flag_an_end_switched_to_specific_as_set_by_hand( ?array $previous_end, array $end, bool $flagged ): void {
		$ticket_id       = static::factory()->post->create();
		$tickets_handler = tribe( 'tickets.handler' );
		$start           = [ 'mode' => Rule::MODE_DEFAULT ];
		$sales           = Window_Kind::sales();
		$previous        = $previous_end ? Rule::from_array( [ 'start' => $start, 'end' => $previous_end ], $sales ) : null;

		$this->get_effects()->flag_end_left_to_ticket( $ticket_id, $previous, Rule::from_array( [ 'start' => $start, 'end' => $end ], $sales ) );

		$this->assertSame( $flagged, $tickets_handler->has_manual_update( $ticket_id, $tickets_handler->key_end_date ) );
	}

	/**
	 * @test
	 */
	public function should_not_flag_an_end_already_set_by_hand_twice(): void {
		$ticket_id       = static::factory()->post->create();
		$tickets_handler = tribe( 'tickets.handler' );
		$sales           = Window_Kind::sales();
		add_post_meta( $ticket_id, $tickets_handler->key_manual_updated, $tickets_handler->key_end_date );

		$this->get_effects()->flag_end_left_to_ticket(
			$ticket_id,
			Rule::from_array( [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ], 'end' => [ 'mode' => Rule::MODE_DEFAULT ] ], $sales ),
			Rule::from_array( [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ], 'end' => [ 'mode' => Rule::MODE_SPECIFIC ] ], $sales )
		);

		$this->assertSame( [ $tickets_handler->key_end_date ], get_post_meta( $ticket_id, $tickets_handler->key_manual_updated ) );
	}

	/**
	 * @test
	 */
	public function should_not_flag_a_sale_price_end_switched_to_specific_as_set_by_hand(): void {
		$ticket_id       = static::factory()->post->create();
		$tickets_handler = tribe( 'tickets.handler' );
		$kind            = Window_Kind::sale_price();
		$start           = [ 'mode' => Rule::MODE_NOW ];

		$this->get_effects()->flag_end_left_to_ticket(
			$ticket_id,
			Rule::from_array( [ 'start' => $start, 'end' => [ 'mode' => Rule::MODE_RELATIVE, 'value' => 1, 'unit' => DAY_IN_SECONDS ] ], $kind ),
			Rule::from_array( [ 'start' => $start, 'end' => [ 'mode' => Rule::MODE_SPECIFIC ] ], $kind )
		);

		$this->assertFalse( $tickets_handler->has_manual_update( $ticket_id, $tickets_handler->key_end_date ) );
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: bool, 2: bool}>
	 */
	public function released_end_provider(): Generator {
		yield 'sales window removed without an end' => [ Window_Kind::sales(), false, false ];
		yield 'sales window removed with an end of its own' => [ Window_Kind::sales(), true, true ];
		yield 'sale price removed without an end' => [ Window_Kind::sale_price(), false, true ];
	}

	/**
	 * @test
	 * @dataProvider released_end_provider
	 */
	public function should_release_the_end_marker_only_for_a_sales_window_removed_without_an_end( Window_Kind $kind, bool $keeps_end, bool $still_flagged ): void {
		$ticket_id       = static::factory()->post->create();
		$tickets_handler = tribe( 'tickets.handler' );
		add_post_meta( $ticket_id, $tickets_handler->key_manual_updated, $tickets_handler->key_end_date );

		$this->get_effects()->release_end( $ticket_id, $kind, $keeps_end );

		$this->assertSame( $still_flagged, $tickets_handler->has_manual_update( $ticket_id, $tickets_handler->key_end_date ) );
	}

	/**
	 * @return Sales_Window_Effects The effects the controller binds.
	 */
	private function get_effects(): Sales_Window_Effects {
		return tribe( Sales_Window_Effects::class );
	}
}
