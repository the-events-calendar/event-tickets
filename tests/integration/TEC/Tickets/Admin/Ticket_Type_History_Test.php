<?php

namespace TEC\Tickets\Admin;

use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Post_History as Post_History;

/**
 * Tests the ticket history the classic ticket form shows in its advanced section.
 */
class Ticket_Type_History_Test extends WPTestCase {
	use With_Uopz;
	use Ticket_Maker;
	use With_Tickets_Commerce;

	/**
	 * @before
	 */
	public function set_up_ajax_request(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// The history is printed only while the ticket form loads over AJAX.
		$this->set_const_value( 'DOING_AJAX', true );
	}

	/**
	 * @after
	 */
	public function clean_up_request(): void {
		unset( $_POST['ticket_id'] );
	}

	/**
	 * @test
	 */
	public function it_should_show_a_moved_tickets_history_without_a_toggle(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );
		$message   = 'This ticket was moved from Event A.';
		Post_History::load( $ticket_id )->add_entry( $message );

		$html = $this->render_history( $ticket_id );

		$this->assertStringContainsString( 'Ticket History:', $html );
		$this->assertStringContainsString( $message, $html );
		$this->assertStringNotContainsString( 'toggle-history', $html );
		$this->assertStringNotContainsString( 'Click to view the history', $html );
	}

	/**
	 * @test
	 */
	public function it_should_show_no_history_row_for_a_ticket_without_history(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$this->assertSame( '', $this->render_history( $ticket_id ) );
	}

	/**
	 * Prints the advanced section of the ticket form the way its AJAX request does.
	 *
	 * @param int $ticket_id The ticket being edited.
	 *
	 * @return string The printed HTML.
	 */
	private function render_history( int $ticket_id ): string {
		$_POST['ticket_id'] = $ticket_id;

		ob_start();
		do_action( 'tribe_events_tickets_metabox_edit_ajax_advanced' );

		return trim( ob_get_clean() );
	}
}
