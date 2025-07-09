<?php

namespace TEC\Tickets\Commerce\Order_Modifiers\Fees;

use TEC\Tickets\Commerce\Order_Modifiers\Modifier_Admin_Handler;
use TEC\Tickets\Commerce\Order_Modifiers\Table_Views\Fee_Table;
use TEC\Tickets\Commerce\Order_Modifiers\Table_Views\Order_Modifier_Table;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Testcases\Order_Modifiers_TestCase;

class Create_Fees_Modifiers_Test extends Order_Modifiers_TestCase {

	use With_Uopz;

	/**
	 * The type of order modifier being tested (fee).
	 *
	 * @var string
	 */
	protected string $modifier_type = 'fee';

	/**
	 * @test
	 *
	 * @return void
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
	 */
	public function does_edit_screen_render_with_no_data() {
		$snapshot = $this->does_edit_form_display_properly_with_no_data();
		$this->assertMatchesHtmlSnapshot( $snapshot );
	}

	/**
	 * @test
	 * @return void
	 */
	public function does_table_render_correctly_with_search_and_page() {
		$this->assertMatchesHtmlSnapshot( $this->get_table_display_with_search() );
	}

	protected function get_table_display_with_search(): string {
		$ids = [];
		for ( $i = 0; $i < 11; $i++ ) {
			$data = [
				'modifier'                    => $this->modifier_type,
				'order_modifier_slug'         => sprintf( 'test_%1$s_%2$02d', $this->modifier_type, $i ),
				'order_modifier_display_name' => uniqid( 'fee' ),
			];

			$applied_to = $i % 2 ? 'all' : 'per';

			$ids[] = $this->upsert_order_modifier_for_test( $data, $applied_to )->id;
		}

		// Insert another modifier with a unique display name.
		$ids[] = $this->upsert_order_modifier_for_test(
			[
				'modifier'                    => $this->modifier_type,
				'order_modifier_slug'         => 'test_fee_unique',
				'order_modifier_display_name' => 'XXXX Unique Fee',
			]
		)->id;

		// Clear out any existing search terms and other args.
		unset( $_REQUEST['s'], $_REQUEST['paged'], $_REQUEST['id'], $_REQUEST['edit'], $_POST['edit'] );

		// Set up the request to search for the unique fee.
		$_REQUEST = [
			'modifier' => $this->modifier_type,
			'paged'    => 2,
		];

		ob_start();

		$this->get_table_class_instance()->prepare_items();
		tribe( Modifier_Admin_Handler::class )->render_tec_order_modifiers_page();

		return ob_get_clean();
	}

	protected function get_table_class_instance(): Order_Modifier_Table {
		return tribe( Fee_Table::class );
	}
}
