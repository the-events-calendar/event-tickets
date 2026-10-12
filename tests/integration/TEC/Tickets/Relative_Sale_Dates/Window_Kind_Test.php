<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;
use Generator;
use TEC\Tickets\Commerce\Ticket;

class Window_Kind_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function should_describe_the_sales_window(): void {
		$kind = Window_Kind::sales();

		$this->assertSame( Window_Kind::SALES, $kind->get_id() );
		$this->assertSame( [ Rule::MODE_DEFAULT, Rule::MODE_RELATIVE, Rule::MODE_SPECIFIC ], $kind->get_modes( 'start' ) );
		$this->assertSame( [ Rule::MODE_DEFAULT, Rule::MODE_RELATIVE, Rule::MODE_SPECIFIC ], $kind->get_modes( 'end' ) );
		$this->assertSame( Rule::MODE_DEFAULT, $kind->get_open_start_mode() );
		$this->assertSame( Boundary::MAX_VALUE, $kind->get_max_value() );
		$this->assertSame( [ MINUTE_IN_SECONDS, HOUR_IN_SECONDS, DAY_IN_SECONDS, WEEK_IN_SECONDS ], $kind->get_units() );
		$this->assertSame( [ Rule::ANCHOR_START, Rule::ANCHOR_END ], $kind->get_anchors() );
		$this->assertTrue( $kind->takes_anchor() );
		$this->assertNull( $kind->get_store_key() );
		$this->assertNull( $kind->get_parent() );
		$this->assertTrue( $kind->owns_ticket_sales_dates() );
		$this->assertSame( Ticket_Save::DATA_KEY, $kind->get_data_key() );
		$this->assertSame(
			[
				'start' => [
					'date' => Ticket::START_DATE_META_KEY,
					'time' => Ticket::START_TIME_META_KEY,
				],
				'end'   => [
					'date' => Ticket::END_DATE_META_KEY,
					'time' => Ticket::END_TIME_META_KEY,
				],
			],
			$kind->get_date_metas()
		);
		$this->assertNull( $kind->get_open_start_value() );
		$this->assertTrue( $kind->moves_open_start_to_now() );
		$this->assertTrue( $kind->lets_end_follow_event_start() );
		$this->assertTrue( $kind->has_sales_actions() );
		$this->assertTrue( $kind->is_removed_by_front_end_form() );
	}

	/**
	 * @test
	 */
	public function should_describe_the_sale_price_window(): void {
		$kind = Window_Kind::sale_price();

		$this->assertSame( Window_Kind::SALE_PRICE, $kind->get_id() );
		$this->assertSame( [ Rule::MODE_NOW, Rule::MODE_RELATIVE, Rule::MODE_SPECIFIC ], $kind->get_modes( 'start' ) );
		$this->assertSame( [ Rule::MODE_RELATIVE, Rule::MODE_SPECIFIC ], $kind->get_modes( 'end' ) );
		$this->assertSame( Rule::MODE_NOW, $kind->get_open_start_mode() );
		$this->assertSame( 30, $kind->get_max_value() );
		$this->assertSame( [ DAY_IN_SECONDS, WEEK_IN_SECONDS ], $kind->get_units() );
		$this->assertSame( [ Rule::ANCHOR_START ], $kind->get_anchors() );
		$this->assertFalse( $kind->takes_anchor() );
		$this->assertSame( 'sale_price', $kind->get_store_key() );
		$this->assertSame( Window_Kind::sales(), $kind->get_parent() );
		$this->assertFalse( $kind->owns_ticket_sales_dates() );
		$this->assertSame( 'ticket_sale_price_relative', $kind->get_data_key() );
		$this->assertSame(
			[
				'start' => [
					'date' => Ticket::$sale_price_start_date_key,
					'time' => null,
				],
				'end'   => [
					'date' => Ticket::$sale_price_end_date_key,
					'time' => null,
				],
			],
			$kind->get_date_metas()
		);
		$this->assertSame( '', $kind->get_open_start_value() );
		$this->assertFalse( $kind->moves_open_start_to_now() );
		$this->assertFalse( $kind->lets_end_follow_event_start() );
		$this->assertFalse( $kind->has_sales_actions() );
		$this->assertFalse( $kind->is_removed_by_front_end_form() );
	}

	/**
	 * @test
	 */
	public function should_enable_the_sales_window_for_every_ticket(): void {
		$this->assertTrue( Window_Kind::sales()->is_enabled_for_ticket( static::factory()->post->create() ) );
	}

	/**
	 * @return Generator<string,array{0: string|null, 1: bool}>
	 */
	public function sale_price_checked_provider(): Generator {
		yield 'checked' => [ '1', true ];
		yield 'unchecked' => [ '', false ];
		yield 'never set' => [ null, false ];
	}

	/**
	 * @test
	 * @dataProvider sale_price_checked_provider
	 */
	public function should_enable_the_sale_price_window_only_for_a_ticket_with_its_sale_price_checked( ?string $checked, bool $enabled ): void {
		$ticket_id = static::factory()->post->create();
		if ( null !== $checked ) {
			update_post_meta( $ticket_id, Ticket::$sale_price_checked_key, $checked );
		}

		$this->assertSame( $enabled, Window_Kind::sale_price()->is_enabled_for_ticket( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_take_no_mode_for_an_unknown_end(): void {
		foreach ( Window_Kind::all() as $kind ) {
			$this->assertSame( [], $kind->get_modes( 'middle' ) );
		}
	}

	/**
	 * @test
	 */
	public function should_list_the_sales_window_first(): void {
		$this->assertSame( [ Window_Kind::sales(), Window_Kind::sale_price() ], Window_Kind::all() );
	}

	/**
	 * @test
	 */
	public function should_return_the_same_instance_for_a_kind(): void {
		$this->assertSame( Window_Kind::sales(), Window_Kind::sales() );
		$this->assertSame( Window_Kind::sale_price(), Window_Kind::sale_price() );
	}
}
