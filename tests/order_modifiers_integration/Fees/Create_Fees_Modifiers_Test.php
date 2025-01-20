<?php

namespace TEC\Tickets\Commerce\Order_Modifiers\Fees;

use Closure;
use TEC\Tickets\Commerce\Order_Modifiers\Modifier_Admin_Handler;
use TEC\Tickets\Commerce\Order_Modifiers\Table_Views\Fee_Table;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Testcases\Order_Modifiers_TestCase;
use WP_List_Table;

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
			's'        => 'Unique Fee',
			'paged'    => 3,
		];

		$fee_table = tribe( Fee_Table::class );
		$set_args  = [];

		// Set up an anon function to used in place of set_pagination_args().
		$pagination_wrapper = Closure::bind(
			function ( $args ) use ( &$set_args ) {
				$set_args               = $args;
				$this->_pagination_args = $args;
			},
			$fee_table,
			$fee_table
		);

		ob_start();

		// Execute the bound function instead of the original.
		$this->set_class_fn_return(
			WP_List_Table::class,
			'set_pagination_args',
			$pagination_wrapper,
			true
		);

		// Render the table.
		tribe( Modifier_Admin_Handler::class )->render_tec_order_modifiers_page();

		// We should have 1 result, and therefore only 1 page.
		$this->assertEquals(
			$set_args,
			[
				'per_page'    => 10,
				'total_items' => 1,
				'total_pages' => 1,
			]
		);

		return ob_get_clean();
	}
}
