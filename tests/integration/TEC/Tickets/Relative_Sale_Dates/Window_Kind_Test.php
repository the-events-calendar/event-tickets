<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;

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
		$this->assertSame( Boundary::MIN_VALUE, $kind->get_min_value() );
		$this->assertSame( Boundary::MAX_VALUE, $kind->get_max_value() );
		$this->assertSame( [ MINUTE_IN_SECONDS, HOUR_IN_SECONDS, DAY_IN_SECONDS, WEEK_IN_SECONDS ], $kind->get_units() );
		$this->assertSame( [ Rule::ANCHOR_START, Rule::ANCHOR_END ], $kind->get_anchors() );
		$this->assertTrue( $kind->takes_anchor() );
		$this->assertNull( $kind->get_store_key() );
		$this->assertNull( $kind->get_parent() );
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
		$this->assertSame( 1, $kind->get_min_value() );
		$this->assertSame( 30, $kind->get_max_value() );
		$this->assertSame( [ DAY_IN_SECONDS, WEEK_IN_SECONDS ], $kind->get_units() );
		$this->assertSame( [ Rule::ANCHOR_START ], $kind->get_anchors() );
		$this->assertFalse( $kind->takes_anchor() );
		$this->assertSame( 'sale_price', $kind->get_store_key() );
		$this->assertSame( Window_Kind::sales(), $kind->get_parent() );
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
