<?php

namespace TEC\Tickets\Commerce\Order_Modifiers\Coupons;

use Gajus\Dindent\Exception\InvalidArgumentException;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Modifier_Admin_Handler;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Table_Views\Coupon_Table;
use TEC\Tickets\Commerce\Order_Modifiers\Table_Views\Order_Modifier_Table;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Coupons;
use Tribe\Tickets\Test\Testcases\Order_Modifiers_TestCase;

/**
 * Class Create_Coupon_Modifiers_Test
 *
 * @skip Pending the coupon feature being enabled.
 */
class Create_Coupon_Modifiers_Test extends Order_Modifiers_TestCase {

	use Coupons;

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
	 */
	public function does_table_render_correctly_with_uses() {
		for ( $i = 0; $i < 20; $i++ ) {
			$insert_data = [
				'modifier'                    => 'coupon',
				'order_modifier_amount'       => (float) $i,
				'order_modifier_sub_type'     => $i % 2 ? 'percent' : 'flat',
				'order_modifier_slug'         => sprintf( 'test_coupon_%02d', $i ),
				'order_modifier_display_name' => "Test Coupon Insert {$i}",

			];

			// Insert the modifier and store the ID.
			$modifier = $this->upsert_order_modifier_for_test( $insert_data );

			// Every other one, add a usage limit.
			if ( $i % 2 ) {
				$meta_repo = tribe( Order_Modifiers_Meta::class );
				$meta_repo->upsert_meta(
					new Order_Modifier_Meta(
						[
							'order_modifier_id' => $modifier->id,
							'meta_key'          => 'usage_limit',
							'meta_value'        => 20,
						]
					)
				);
			}

			// For the first 10 coupons, use them.
			if ( $i < 10 ) {
				$this->add_coupon_use( $modifier->id, $i + 1 );
			}
		}

		$modifier_admin_handler = tribe( Modifier_Admin_Handler::class );
		$_REQUEST['modifier']   = $this->modifier_type;

		ob_start();
		$this->get_table_class_instance()->prepare_items();
		$modifier_admin_handler->render_tec_order_modifiers_page();

		$this->assertMatchesHtmlSnapshot( ob_get_clean() );
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

	/**
	 * @before
	 */
	public function clean_request_data() {
		unset(
			$_POST['edit'],
			$_POST['modifier_id'],
			$_REQUEST['id'],
			$_REQUEST['edit'],
			$_REQUEST['modifier']
		);
	}
}
