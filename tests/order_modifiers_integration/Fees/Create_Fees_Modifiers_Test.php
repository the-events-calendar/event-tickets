<?php

namespace TEC\Tickets\Tests\Integration\Order_Modifiers\Fees;

use TEC\Tickets\Tests\Integration\Order_Modifiers\Create_Order_Modifiers_Abstract;

class Create_Fees_Modifiers_Test extends Create_Order_Modifiers_Abstract {

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
	 * @throws \Gajus\Dindent\Exception\InvalidArgumentException
	 */
	public function does_table_render_correctly() {
		// @todo redscar - Test sometimes fails randomly due to race conditions.
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
	 * @throws \Gajus\Dindent\Exception\InvalidArgumentException
	 */
	public function does_edit_screen_render_with_no_data() {
		$snapshot = $this->does_edit_form_display_properly_with_no_data();
		$this->assertMatchesHtmlSnapshot( $snapshot );
	}
}
