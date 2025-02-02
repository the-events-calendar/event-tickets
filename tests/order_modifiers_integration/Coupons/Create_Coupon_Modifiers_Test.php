<?php

namespace TEC\Tickets\Commerce\Order_Modifiers\Coupons;

use Gajus\Dindent\Exception\InvalidArgumentException;
use TEC\Tickets\Commerce\Order_Modifiers\Table_Views\Coupon_Table;
use TEC\Tickets\Commerce\Order_Modifiers\Table_Views\Order_Modifier_Table;
use Tribe\Tickets\Test\Testcases\Order_Modifiers_TestCase;

/**
 * Class Create_Coupon_Modifiers_Test
 *
 * @skip Pending the coupon feature being enabled.
 */
class Create_Coupon_Modifiers_Test extends Order_Modifiers_TestCase {

	/**
	 * The type of order modifier being tested (coupon).
	 *
	 * @var string
	 */
	protected string $modifier_type = 'coupon';

	/**
	 * @test
	 *
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function does_table_render_correctly() {
		$this->assertMatchesHtmlSnapshot( $this->get_table_display() );
	}

	/**
	 * @test
	 * @dataProvider modifier_edit_form_data_provider
	 */
	public function does_edit_screen_render_correctly( array $insert_data, array $post_data ) {
		$snapshot = $this->does_edit_form_display_properly_with_data( $insert_data, $post_data );
		$this->assertMatchesHtmlSnapshot( $snapshot );
	}

	/**
	 * @test
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function does_edit_screen_render_with_no_data() {
		$snapshot = $this->does_edit_form_display_properly_with_no_data();
		$this->assertMatchesHtmlSnapshot( $snapshot );
	}

	protected function get_table_class_instance(): Order_Modifier_Table {
		return tribe( Coupon_Table::class );
	}
}
