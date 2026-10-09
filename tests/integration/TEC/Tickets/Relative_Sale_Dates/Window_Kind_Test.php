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
		$this->assertSame( [ 'data' => Ticket_Save::DATA_KEY ], $kind->get_rule_keys() );
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
		$this->assertTrue( $kind->is_removed_by_front_end_form() );
		$this->assertSame(
			[
				'start' => [
					'date' => 'ticket_start_date',
					'time' => 'ticket_start_time',
				],
				'end'   => [
					'date' => 'ticket_end_date',
					'time' => 'ticket_end_time',
				],
			],
			$kind->get_submitted_fields()
		);
		$this->assertTrue( $kind->specific_needs_date() );
		$this->assertFalse( $kind->compares_days() );
		$this->assertSame( [ 'relative_sale_dates' ], $kind->get_block_editor_request_path() );
		$this->assertSame( [ 'relative_sale_dates' ], $kind->get_block_editor_response_path() );
		$this->assertSame( 'relative_sale_dates', $kind->get_tec_rest_field() );
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
		$this->assertSame( [ 'data' => 'ticket_sale_price_relative' ], $kind->get_rule_keys() );
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
		$this->assertFalse( $kind->is_removed_by_front_end_form() );
		$this->assertSame(
			[
				'start' => [
					'date' => 'ticket_sale_start_date',
					'time' => null,
				],
				'end'   => [
					'date' => 'ticket_sale_end_date',
					'time' => null,
				],
			],
			$kind->get_submitted_fields()
		);
		$this->assertFalse( $kind->specific_needs_date() );
		$this->assertTrue( $kind->compares_days() );
		$this->assertSame( [ 'sale_price', 'relative' ], $kind->get_block_editor_request_path() );
		$this->assertSame( [ 'sale_price_data', 'relative' ], $kind->get_block_editor_response_path() );
		$this->assertSame( 'sale_price_relative', $kind->get_tec_rest_field() );
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: array<string,array{0: string, 1: string}>}>
	 */
	public function errors_provider(): Generator {
		yield 'sales' => [
			Window_Kind::sales(),
			[
				'endsBeforeStart' => [ 'tec_tickets_relative_sale_dates_invalid_window', 'Ticket sales cannot end before they start. Please adjust the sales window.' ],
			],
		];
		yield 'sale price' => [
			Window_Kind::sale_price(),
			[
				'endsBeforeStart' => [ 'tec_tickets_relative_sale_dates_sale_price_ends_before_start', 'The sale price cannot end before it starts. Please adjust the sale price window.' ],
				'outsideParent'   => [ 'tec_tickets_relative_sale_dates_sale_price_outside_sales_window', 'The sale price window falls outside the ticket sales window. Please adjust the dates.' ],
			],
		];
	}

	/**
	 * @test
	 * @dataProvider errors_provider
	 */
	public function should_reject_with_the_errors_of_the_kind( Window_Kind $kind, array $errors ): void {
		foreach ( [ 'endsBeforeStart', 'outsideParent', 'valueOutOfRange' ] as $key ) {
			$error = $kind->get_error( $key );

			if ( ! isset( $errors[ $key ] ) ) {
				$this->assertNull( $error, $key );

				continue;
			}

			$this->assertSame( $errors[ $key ], [ $error->get_error_code(), $error->get_error_message() ], $key );
			$this->assertSame( [ 'status' => 400 ], $error->get_error_data(), $key );
		}
	}

	/**
	 * Tickets Commerce drops a sale price that is unchecked or not lower than the price.
	 *
	 * @return Generator<string,array{0: Window_Kind, 1: array<string,string|int|bool>, 2: bool}>
	 */
	public function saved_with_provider(): Generator {
		yield 'sales: no data' => [ Window_Kind::sales(), [], true ];
		yield 'sale price: checked and lower' => [ Window_Kind::sale_price(), [ 'ticket_add_sale_price' => 'on', 'ticket_sale_price' => 10, 'ticket_price' => 20 ], true ];
		yield 'sale price: unchecked' => [ Window_Kind::sale_price(), [ 'ticket_add_sale_price' => false, 'ticket_sale_price' => 10, 'ticket_price' => 20 ], false ];
		yield 'sale price: not sent' => [ Window_Kind::sale_price(), [ 'ticket_sale_price' => 10, 'ticket_price' => 20 ], false ];
		yield 'sale price: equal to the price' => [ Window_Kind::sale_price(), [ 'ticket_add_sale_price' => 'on', 'ticket_sale_price' => 20, 'ticket_price' => 20 ], false ];
		yield 'sale price: higher than the price' => [ Window_Kind::sale_price(), [ 'ticket_add_sale_price' => 'on', 'ticket_sale_price' => 30, 'ticket_price' => 20 ], false ];
	}

	/**
	 * @test
	 * @dataProvider saved_with_provider
	 */
	public function should_tell_whether_a_save_of_the_ticket_data_keeps_the_window( Window_Kind $kind, array $data, bool $saved ): void {
		$this->assertSame( $saved, $kind->is_saved_with( $data ) );
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
