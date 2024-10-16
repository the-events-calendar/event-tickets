<?php

namespace TEC\Tickets\Order_Modifiers\Fees;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Order_Modifiers\Modifier_Admin_Handler;
use TEC\Tickets\Tests\Integration\Order_Modifiers\Create_Order_Modifiers_Abstract;

class Create_Fees_Modifiers_Test extends Create_Order_Modifiers_Abstract {

	use SnapshotAssertions;

	/**
	 * The type of order modifier being tested (fee).
	 *
	 * @var string
	 */
	protected string $modifier_type = 'fee';

	/**
	 * @test
	 */
	public function does_table_display_properly() {
		$this->clear_all_modifiers( $this->modifier_type );
		for ( $i = 0; $i < 20; $i++ ) {
			// Step 1: Insert a new modifier.
			$insert_data = [
				'modifier'                    => $this->modifier_type,
				'order_modifier_amount'       => (float) $i,
				'order_modifier_sub_type'     => $i % 2 ? 'percent' : 'flat',
				'order_modifier_slug'         => "test_{$this->modifier_type}_{$i}",
				'order_modifier_display_name' => "Test {$this->modifier_type} Insert",
			];
			$this->upsert_order_modifier_for_test( $insert_data );
		}

		$modifier_admin_handler = new Modifier_Admin_Handler();
		$_POST                  = [
			'modifier' => $this->modifier_type,
		];
		ob_start();
		$modifier_admin_handler->render_tec_order_modifiers_page();
		$test = ob_get_contents();
		ob_end_flush();
		$this->assertMatchesHtmlSnapshot( $test );
	}
}
