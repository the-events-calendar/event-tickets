<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Context as Context;
use Tribe__Editor as Editor;

/**
 * The editor scripts load only where recurring event tickets are offered.
 */
class Assets_Test extends WPTestCase {
	use Ticket_Rows;
	use With_Uopz;

	/**
	 * @before
	 */
	public function edit_an_event_in_the_block_editor(): void {
		// What WordPress tells when it opens an event in the block editor.
		$this->set_class_fn_return( Context::class, 'is_editing_post', true );
		$this->set_class_fn_return( Editor::class, 'should_load_blocks', true );
	}

	/**
	 * @after
	 */
	public function leave_the_editor(): void {
		unset( $GLOBALS['post'] );
	}

	/**
	 * @test
	 */
	public function it_should_load_the_block_editor_script_on_a_recurring_event(): void {
		$GLOBALS['post'] = get_post( $this->create_recurring_event() );

		$this->assertTrue( tribe( Assets::class )->is_editing_an_event_in_the_block_editor() );
	}

	/**
	 * @test
	 */
	public function it_should_not_load_it_on_a_seated_event(): void {
		$event = $this->create_recurring_event();
		update_post_meta( $event, '_tec_slr_layout', 'some-layout' );
		$GLOBALS['post'] = get_post( $event );

		$this->assertFalse( tribe( Assets::class )->is_editing_an_event_in_the_block_editor() );
	}
}
